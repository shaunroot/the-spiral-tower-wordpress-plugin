<?php
/**
 * DALL-E API Test Script
 * Run this from the WordPress root or access directly to test the Azure OpenAI API
 * DELETE THIS FILE AFTER TESTING
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Only allow admins
if (!current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

// Get the API configuration
$api_key = get_option('spiral_tower_dalle_api_key');
$api_endpoint = get_option('spiral_tower_dalle_api_endpoint', 'https://shauntest.openai.azure.com/openai/deployments/dall-e-3/images/generations?api-version=2024-02-01');

// Get submitted prompt
$test_prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
$submitted = !empty($test_prompt);

?>
<!DOCTYPE html>
<html>
<head>
    <title>DALL-E API Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f0f0f1; }
        .card { background: #fff; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { color: #1d2327; margin-top: 0; }
        label { font-weight: 600; display: block; margin-bottom: 8px; }
        textarea { width: 100%; padding: 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        button { background: #2271b1; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #135e96; }
        .config { background: #f6f7f7; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
        .config code { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; }
        .success { color: #00a32a; }
        .error { color: #d63638; }
        pre { background: #2c3338; color: #f0f0f1; padding: 15px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; }
        .result-image { max-width: 100%; margin-top: 15px; border-radius: 4px; }
        .status-code { font-size: 24px; font-weight: bold; }
        .warning { background: #fcf9e8; border-left: 4px solid #dba617; padding: 12px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>DALL-E API Test</h1>

    <div class="warning">
        <strong>Warning:</strong> Delete this file after testing. It exposes API diagnostics.
    </div>

    <div class="card">
        <h2>Configuration</h2>
        <div class="config">
            <p><strong>API Endpoint:</strong><br><code><?php echo esc_html($api_endpoint); ?></code></p>
            <p><strong>API Key:</strong> <?php echo empty($api_key) ? '<span class="error">NOT CONFIGURED</span>' : '<span class="success">Configured (length: ' . strlen($api_key) . ')</span>'; ?></p>
        </div>

        <?php if (empty($api_key)): ?>
            <p class="error"><strong>Error:</strong> No API key configured. Please set it in the <a href="<?php echo admin_url('edit.php?post_type=floor&page=spiral-tower-settings'); ?>">plugin settings</a>.</p>
        <?php else: ?>
            <form method="post">
                <p>
                    <label for="prompt">Test Prompt:</label>
                    <textarea name="prompt" id="prompt" rows="4" placeholder="Enter a prompt to test image generation..."><?php echo esc_textarea($test_prompt ?: 'A simple blue circle on a white background'); ?></textarea>
                </p>
                <p>
                    <button type="submit">Generate Image</button>
                </p>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($submitted && !empty($api_key)): ?>
    <?php
        $start_time = microtime(true);

        $response = wp_remote_post(
            $api_endpoint,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'api-key' => $api_key
                ),
                'body' => json_encode(array(
                    'prompt' => $test_prompt,
                    'n' => 1,
                    'size' => '1024x1024'
                )),
                'timeout' => 60
            )
        );

        $elapsed = round(microtime(true) - $start_time, 2);
    ?>

    <div class="card">
        <h2>Response <small>(<?php echo $elapsed; ?>s)</small></h2>

        <?php if (is_wp_error($response)): ?>
            <p class="status-code error">WP_ERROR</p>
            <p><strong>Error Code:</strong> <?php echo esc_html($response->get_error_code()); ?></p>
            <p><strong>Error Message:</strong> <?php echo esc_html($response->get_error_message()); ?></p>
        <?php else:
            $status_code = wp_remote_retrieve_response_code($response);
            $status_message = wp_remote_retrieve_response_message($response);
            $headers = wp_remote_retrieve_headers($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
        ?>
            <p class="status-code <?php echo $status_code >= 200 && $status_code < 300 ? 'success' : 'error'; ?>">
                HTTP <?php echo esc_html($status_code . ' ' . $status_message); ?>
            </p>

            <?php if (isset($data['data'][0]['url'])): ?>
                <p class="success"><strong>Success!</strong> Image generated.</p>
                <p><strong>Image URL:</strong><br><code><?php echo esc_html($data['data'][0]['url']); ?></code></p>
                <img src="<?php echo esc_url($data['data'][0]['url']); ?>" class="result-image" alt="Generated image">
            <?php elseif (isset($data['error'])): ?>
                <p class="error"><strong>API Error</strong></p>
                <p><strong>Code:</strong> <?php echo esc_html($data['error']['code'] ?? 'N/A'); ?></p>
                <p><strong>Message:</strong> <?php echo esc_html($data['error']['message'] ?? 'N/A'); ?></p>
                <?php if (isset($data['error']['innererror'])): ?>
                    <p><strong>Inner Error:</strong></p>
                    <pre><?php echo esc_html(json_encode($data['error']['innererror'], JSON_PRETTY_PRINT)); ?></pre>
                <?php endif; ?>
            <?php endif; ?>

            <h3>Raw Response Body</h3>
            <pre><?php echo esc_html($body); ?></pre>

            <details>
                <summary>Response Headers</summary>
                <pre><?php
                    foreach ($headers as $key => $value) {
                        echo esc_html($key . ': ' . $value) . "\n";
                    }
                ?></pre>
            </details>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>
