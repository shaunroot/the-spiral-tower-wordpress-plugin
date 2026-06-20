<?php
/**
 * Image Generator Component
 * Handles DALL-E API integration for generating featured images
 */
class Spiral_Tower_Image_Generator
{

    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Add meta boxes for the generate image button
        add_action('add_meta_boxes', array($this, 'add_image_generator_meta_boxes'));

        // Add AJAX handlers
        add_action('wp_ajax_spiral_tower_generate_image', array($this, 'handle_generate_image_ajax'));

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add meta boxes for generating images
     */
    public function add_image_generator_meta_boxes()
    {
        // Add to floor post type
        add_meta_box(
            'floor_image_generator',
            'Featured Image Generator',
            array($this, 'render_image_generator_meta_box'),
            'floor',
            'side',
            'low'
        );

        // Add to room post type
        add_meta_box(
            'room_image_generator',
            'Featured Image Generator',
            array($this, 'render_image_generator_meta_box'),
            'room',
            'side',
            'low'
        );
    }

    /**
     * Render the image generator meta box
     */
    public function render_image_generator_meta_box($post)
    {
        // Check if API key is configured for the selected provider
        $provider = get_option('spiral_tower_image_provider', 'openai');
        $has_key = false;
        if ($provider === 'openai') {
            $has_key = !empty(get_option('spiral_tower_openai_api_key'));
        } elseif ($provider === 'huggingface') {
            $has_key = !empty(get_option('spiral_tower_huggingface_api_key'));
        } elseif ($provider === 'gemini') {
            $has_key = !empty(get_option('spiral_tower_gemini_api_key'));
        } elseif ($provider === 'dalle') {
            $has_key = !empty(get_option('spiral_tower_dalle_api_key'));
        } else {
            $has_key = true; // Pollinations doesn't need a key
        }
        if (!$has_key) {
            echo '<p>To use the image generator, please <a href="' . admin_url('edit.php?post_type=floor&page=spiral-tower-settings') . '">configure your API key</a> first.</p>';
            return;
        }

        // Get post type and generate a suggested prompt
        $post_type = get_post_type($post);
        $suggested_prompt = $this->generate_suggested_prompt($post);

        wp_nonce_field('spiral_tower_image_generator', 'spiral_tower_image_generator_nonce');

        ?>
        <div class="spiral-tower-image-generator">
            <p><label for="st_image_prompt">Image Description:</label></p>
            <textarea id="st_image_prompt" name="st_image_prompt" rows="4"
                style="width: 100%;"><?php echo esc_textarea($suggested_prompt); ?></textarea>

            <p>
                <button type="button" id="st_generate_image_button" class="button button-primary"
                    data-post-id="<?php echo esc_attr($post->ID); ?>" data-post-type="<?php echo esc_attr($post_type); ?>">
                    Generate Image
                </button>
                <span id="st_image_status" style="display: none; margin-left: 10px;"></span>
            </p>

            <div id="st_image_preview" style="margin-top: 10px; display: none;">
                <img id="st_generated_image_preview" src="" style="max-width: 100%; height: auto;" />
                <p>
                    <button type="button" id="st_set_featured_image" class="button">
                        Set as Featured Image
                    </button>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Generate a suggested prompt based on the post
     */
    private function generate_suggested_prompt($post)
    {
        $title = $post->post_title;
        $post_type = get_post_type($post);
        $prompt = '';

        // Get content and truncate to 100 words
        $content = wp_strip_all_tags($post->post_content);
        $content = wp_trim_words($content, 100, '');

        if ($post_type === 'floor') {
            $prompt = $title . '. ' . $content;
        } else if ($post_type === 'room') {
            $room_type = get_post_meta($post->ID, '_room_type', true) ?: 'normal';

            $prompt = $title . ' (' . ucfirst($room_type) . ' room). ' . $content;
        }

        // Trim to ensure it's not too long for the API
        return trim($prompt);
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post;

        // Only enqueue on post edit screens for our post types
        if (
            ('post.php' === $hook || 'post-new.php' === $hook) &&
            ($post && (get_post_type($post) === 'floor' || get_post_type($post) === 'room'))
        ) {

            wp_enqueue_script(
                'spiral-tower-image-generator',
                SPIRAL_TOWER_PLUGIN_URL . 'assets/js/image-generator.js',
                array('jquery'),
                '1.0.0',
                true
            );

            wp_localize_script(
                'spiral-tower-image-generator',
                'spiralTowerImageGenerator',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('spiral_tower_generate_image_nonce')
                )
            );
        }
    }

    /**
     * Handle the AJAX request to generate an image
     */
    public function handle_generate_image_ajax()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spiral_tower_generate_image_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Check for required parameters
        if (!isset($_POST['post_id']) || !isset($_POST['prompt'])) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
        }

        $post_id = intval($_POST['post_id']);
        $prompt = sanitize_textarea_field($_POST['prompt']);

        // Ensure current user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'You do not have permission to edit this post'));
        }

        // Generate the image via API
        $image_data = $this->generate_image_from_api($prompt);

        if (is_wp_error($image_data)) {
            wp_send_json_error(array('message' => $image_data->get_error_message()));
        }

        // Store the image URL temporarily in post meta
        $result = update_post_meta($post_id, '_temp_generated_image_url', $image_data['url']);
        update_post_meta($post_id, '_temp_generated_image_prompt', $prompt);

        wp_send_json_success(array(
            'image_url' => $image_data['url'],
            'message' => 'Image generated successfully',
            'meta_update_result' => $result // Add this for debugging
        ));
    }

    /**
     * Generate an image using the configured provider
     */
    public function generate_image_from_api($prompt)
    {
        $provider = get_option('spiral_tower_image_provider', 'openai');

        if ($provider === 'openai') {
            return $this->generate_image_openai($prompt);
        }

        if ($provider === 'huggingface') {
            return $this->generate_image_huggingface($prompt);
        }

        if ($provider === 'gemini') {
            return $this->generate_image_gemini($prompt);
        }

        if ($provider === 'pollinations') {
            return $this->generate_image_pollinations($prompt);
        }

        return $this->generate_image_dalle($prompt);
    }

    /**
     * Generate an image using OpenAI API (gpt-image-2)
     */
    private function generate_image_openai($prompt)
    {
        $api_key = get_option('spiral_tower_openai_api_key');

        if (empty($api_key)) {
            return new WP_Error('missing_api_config', 'OpenAI API key is missing');
        }

        $response = wp_remote_post(
            'https://api.openai.com/v1/images/generations',
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-image-2',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '2048x2048',
                )),
                'timeout' => 120,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code < 200 || $status_code >= 300) {
            $error_message = 'OpenAI API returned HTTP ' . $status_code;
            if (isset($data['error']['message'])) {
                $error_message .= ': ' . $data['error']['message'];
            } elseif (!empty($body)) {
                $error_message .= ': ' . substr($body, 0, 500);
            }
            return new WP_Error('api_error', $error_message);
        }

        // Check for URL response format first
        if (isset($data['data'][0]['url'])) {
            return array('url' => $data['data'][0]['url']);
        }

        // Handle base64 response format
        if (isset($data['data'][0]['b64_json'])) {
            $upload_dir = wp_upload_dir();
            $filename = 'openai-temp-' . time() . '-' . wp_rand() . '.png';
            $filepath = $upload_dir['path'] . '/' . $filename;

            $decoded = base64_decode($data['data'][0]['b64_json']);
            if ($decoded === false) {
                return new WP_Error('api_error', 'Failed to decode image data from OpenAI');
            }

            if (file_put_contents($filepath, $decoded) === false) {
                return new WP_Error('file_error', 'Failed to save generated image to disk');
            }

            return array('url' => $upload_dir['url'] . '/' . $filename);
        }

        $error_message = 'Unexpected OpenAI API response format';
        if (!empty($body)) {
            $error_message .= '. Response: ' . substr($body, 0, 500);
        }
        return new WP_Error('api_error', $error_message);
    }

    /**
     * Generate an image using Hugging Face Inference API
     */
    private function generate_image_huggingface($prompt)
    {
        $api_key = get_option('spiral_tower_huggingface_api_key');

        if (empty($api_key)) {
            return new WP_Error('missing_api_config', 'Hugging Face API token is missing');
        }

        $model = 'black-forest-labs/FLUX.1-schnell';
        $url = 'https://router.huggingface.co/hf-inference/models/' . $model;

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(array(
                    'inputs' => $prompt,
                    'parameters' => array(
                        'width' => 1024,
                        'height' => 1024,
                    ),
                )),
                'timeout' => 120,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if ($status_code < 200 || $status_code >= 300) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error_message = 'Hugging Face API returned HTTP ' . $status_code;
            if (isset($data['error'])) {
                $error_message .= ': ' . $data['error'];
            } elseif (!empty($body)) {
                $error_message .= ': ' . substr($body, 0, 500);
            }
            return new WP_Error('api_error', $error_message);
        }

        // Response is raw image bytes
        $image_bytes = wp_remote_retrieve_body($response);

        if (empty($image_bytes)) {
            return new WP_Error('api_error', 'Empty image response from Hugging Face');
        }

        // Determine extension from content type
        $ext = '.png';
        if (strpos($content_type, 'jpeg') !== false || strpos($content_type, 'jpg') !== false) {
            $ext = '.jpg';
        }

        // Save to temp file in uploads
        $upload_dir = wp_upload_dir();
        $filename = 'hf-temp-' . time() . '-' . wp_rand() . $ext;
        $filepath = $upload_dir['path'] . '/' . $filename;

        if (file_put_contents($filepath, $image_bytes) === false) {
            return new WP_Error('file_error', 'Failed to save generated image to disk');
        }

        return array(
            'url' => $upload_dir['url'] . '/' . $filename,
        );
    }

    /**
     * Generate an image using Google Gemini API
     */
    private function generate_image_gemini($prompt)
    {
        $api_key = get_option('spiral_tower_gemini_api_key');

        if (empty($api_key)) {
            return new WP_Error('missing_api_config', 'Gemini API key is missing');
        }

        $model = 'gemini-2.5-flash-image';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $api_key,
                ),
                'body' => json_encode(array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array('text' => 'Generate an image: ' . $prompt),
                            ),
                        ),
                    ),
                    'generationConfig' => array(
                        'responseModalities' => array('TEXT', 'IMAGE'),
                    ),
                )),
                'timeout' => 90,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code < 200 || $status_code >= 300) {
            $error_message = 'Gemini API returned HTTP ' . $status_code;
            if (isset($data['error']['message'])) {
                $error_message .= ': ' . $data['error']['message'];
            } elseif (!empty($body)) {
                $error_message .= ': ' . substr($body, 0, 500);
            }
            return new WP_Error('api_error', $error_message);
        }

        // Extract base64 image from response
        $parts = isset($data['candidates'][0]['content']['parts']) ? $data['candidates'][0]['content']['parts'] : array();
        $image_data = null;
        $mime_type = 'image/png';

        foreach ($parts as $part) {
            if (isset($part['inlineData'])) {
                $image_data = $part['inlineData']['data'];
                $mime_type = $part['inlineData']['mimeType'];
                break;
            }
        }

        if (empty($image_data)) {
            $error_message = 'No image data in Gemini response';
            if (!empty($body)) {
                $error_message .= '. Response: ' . substr($body, 0, 500);
            }
            return new WP_Error('api_error', $error_message);
        }

        // Save base64 image to a temp file in uploads
        $upload_dir = wp_upload_dir();
        $ext = ($mime_type === 'image/jpeg') ? '.jpg' : '.png';
        $filename = 'gemini-temp-' . time() . '-' . wp_rand() . $ext;
        $filepath = $upload_dir['path'] . '/' . $filename;

        $decoded = base64_decode($image_data);
        if ($decoded === false) {
            return new WP_Error('api_error', 'Failed to decode image data from Gemini');
        }

        if (file_put_contents($filepath, $decoded) === false) {
            return new WP_Error('file_error', 'Failed to save generated image to disk');
        }

        return array(
            'url' => $upload_dir['url'] . '/' . $filename,
        );
    }

    /**
     * Generate an image using DALL-E API
     */
    private function generate_image_dalle($prompt)
    {
        $api_key = get_option('spiral_tower_dalle_api_key');
        $api_endpoint = get_option('spiral_tower_dalle_api_endpoint', 'https://shauntest.openai.azure.com/openai/deployments/dall-e-3/images/generations?api-version=2024-02-01');

        if (empty($api_key) || empty($api_endpoint)) {
            return new WP_Error('missing_api_config', 'DALL-E API configuration is missing');
        }

        $response = wp_remote_post(
            $api_endpoint,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'api-key' => $api_key
                ),
                'body' => json_encode(array(
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024'
                )),
                'timeout' => 60
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Handle HTTP-level errors
        if ($status_code < 200 || $status_code >= 300) {
            $error_message = 'Azure OpenAI API returned HTTP ' . $status_code;
            if (isset($data['error']['message'])) {
                $error_message .= ': ' . $data['error']['message'];
            } elseif (!empty($body)) {
                $error_message .= ': ' . substr($body, 0, 500);
            }
            return new WP_Error('api_error', $error_message);
        }

        if (empty($data) || !isset($data['data']) || !isset($data['data'][0]['url'])) {
            $error_message = 'Unexpected API response format';
            if (isset($data['error']['message'])) {
                $error_message .= ': ' . $data['error']['message'];
            } elseif (!empty($body)) {
                $error_message .= '. Response: ' . substr($body, 0, 500);
            }
            return new WP_Error('api_error', $error_message);
        }

        return array(
            'url' => $data['data'][0]['url']
        );
    }

    /**
     * Generate an image using Pollinations.ai
     */
    private function generate_image_pollinations($prompt)
    {
        // Pollinations.ai returns the image directly from a GET URL
        $encoded_prompt = urlencode($prompt);
        $image_url = 'https://gen.pollinations.ai/image/' . $encoded_prompt . '?width=1024&height=1024&nologo=true&model=flux';

        // Verify the URL is accessible by making a HEAD request
        $response = wp_remote_head($image_url, array(
            'timeout' => 120,
            'redirection' => 5
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            return new WP_Error('api_error', 'Pollinations.ai returned status code: ' . $status_code);
        }

        return array(
            'url' => $image_url
        );
    }


    /**
     * AJAX handler to set the generated image as featured image
     */
    public function set_featured_image_ajax()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spiral_tower_generate_image_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Check for required parameters
        if (!isset($_POST['post_id'])) {
            wp_send_json_error(array('message' => 'Missing post ID'));
        }

        $post_id = intval($_POST['post_id']);

        // Ensure current user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'You do not have permission to edit this post'));
        }

        // Get the temporary image URL
        $image_url = get_post_meta($post_id, '_temp_generated_image_url', true);

        // Debug information
        if (empty($image_url)) {
            // List all post meta to see if the URL was saved under a different key
            $all_meta = get_post_meta($post_id);
            $meta_keys = array_keys($all_meta);
            wp_send_json_error(array(
                'message' => 'No generated image found. Available meta keys: ' . implode(', ', $meta_keys)
            ));
        }

        // Download the image and attach it to the post
        $attachment_id = $this->download_and_attach_image($image_url, $post_id);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);

        // Clean up temporary meta
        delete_post_meta($post_id, '_temp_generated_image_url');

        wp_send_json_success(array(
            'message' => 'Featured image set successfully',
            'attachment_id' => $attachment_id
        ));
    }



    /**
     * Download an image from a URL and attach it to a post
     */
    public function download_and_attach_image($image_url, $post_id, $prompt = '')
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Get the image prompt for use in the attachment title/description
        $image_prompt = !empty($prompt) ? $prompt : get_post_meta($post_id, '_temp_generated_image_prompt', true);
        $post = get_post($post_id);
        $post_title = $post ? $post->post_title : '';

        // Download the file
        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return $tmp;
        }

        $file_array = array(
            'name' => sanitize_file_name($post_title . '-' . time() . '.jpg'),
            'tmp_name' => $tmp
        );

        // Move the temporary file into the uploads directory
        $attachment_id = media_handle_sideload($file_array, $post_id, $image_prompt);

        // If error, clean up
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return $attachment_id;
        }

        // Save the prompt as attachment metadata
        if (!empty($image_prompt)) {
            update_post_meta($attachment_id, '_generated_image_prompt', $image_prompt);
        }

        return $attachment_id;
    }
}