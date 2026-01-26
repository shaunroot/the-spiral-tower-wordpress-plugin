<?php
/**
 * Template for How It Works page
 * Save this file as: templates/how-it-works.php in your plugin directory
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Remove all theme actions to create a clean template
remove_all_actions('wp_head');
remove_all_actions('wp_footer');

// Re-add essential WordPress actions
add_action('wp_head', 'wp_enqueue_scripts', 1);
add_action('wp_head', 'wp_print_styles', 8);
add_action('wp_head', 'wp_print_head_scripts', 9);
add_action('wp_head', 'wp_site_icon', 99);
add_action('wp_head', '_wp_render_title_tag', 1);
add_action('wp_footer', 'wp_print_footer_scripts', 20);

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('tower-how-it-works-page how-it-works-template'); ?>>
<?php include 'menu.php'; ?>
<div class="how-it-works-wrapper">
    <header class="how-it-works-header">
        <nav class="how-it-works-nav">
            <a href="<?php echo home_url(); ?>">← Back to Tower</a>
        </nav>
        <h1>How The Spiral Tower Works</h1>
    </header>

    <main class="how-it-works-content">
        


        <section class="hiw-section">
            <h2>
                Content
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
            </h2>
            <div class="hiw-description">
                <p>Floor descriptions will be front and center when you first view a floor. The first button in the toolbar will toggle the content on and off. Hovering the title bar will always show the content. You will likely want to hide the content occasionally to get a better view of the floor and find portals to other floors.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>
                Text Only Mode
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-text-mode">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" x2="8" y1="13" y2="13"/>
                    <line x1="16" x2="8" y1="17" y2="17"/>
                    <line x1="10" x2="8" y1="9" y2="9"/>
                </svg>
            </h2>
            <div class="hiw-description">
                <p>Sick of seeing all the generated images or you are still chilling with the Leather Goddesses of Phobos? Turn on text only mode. You will see all the content. It's also useful if there is a portal on the page you just can't find.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>
                Sound
                <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="28" height="28" viewBox="0 0 75 75">
                    <path d="M39.389,13.769 L22.235,28.606 L6,28.606 L6,47.699 L21.989,47.699 L39.389,62.75 L39.389,13.769z" fill="#ffd700" stroke="#000" stroke-width="2"/>
                    <path d="M48,27.6a19.5,19.5 0 0 1 0,21.4M55.1,20.5a30,30 0 0 1 0,35.6M61.6,14a38.8,38.8 0 0 1 0,48.6" fill="none" stroke="#ffd700" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </h2>
            <div class="hiw-description">
                <p>Some places in the tower will add a sleeping volume icon. Tap it if you want to hear the sound for that location. Some forests have ambient bird sounds. Some night clubs have DJs spinning tracks. Like any polite modern website, the sound on every location will be off by default.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>
                Floors
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                    <path d="m15 5 4 4"/>
                </svg>
            </h2>
            <div class="hiw-description">
                <p>Floors are the foundation of everything. A floor can be a simple block of text or a massive maze of rooms filled with secrets and treasures.</p>
                <p>To create your own floor, post a new message in this subreddit using the following format:</p>
                <p><strong>[New Floor][8090] Name of your floor</strong></p>
                <p>The text field of your post will become the floor's description. About a minute later, the bot will:</p>
                <ul>
                    <li>Create the floor on the website</li>
                    <li>Generate an image from your description</li>
                    <li>Comment on your post with a link to the new floor</li>
                </ul>
                <p>You can click the link to edit the title, description, or image. You can also upload your own image, embed a YouTube video, or add ambient sound via a YouTube link. For the technically inclined, custom CSS and JavaScript are supported too.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>
                Rooms
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                    <path d="m15 5 4 4"/>
                </svg>
            </h2>
            <div class="hiw-description">
                <p>After exploring the Tower for a bit, you'll notice that many floors have rooms.</p>
                <p>To create a room, just make a comment on one of your floors as such:</p>
                <p><strong>/create room This is the room title.</strong></p>
                <p>The room will be created and portals from your floor entrance and back will be created for you.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>
                Portals
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 24 24" width="24" height="24">
                    <circle fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" cx="12" cy="12" r="10"/>
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.2,6.8c.5-.5,1.4-.5,1.9,0s.5,1.4,0,1.9l-8.1,8.1-2.6.6.6-2.6L15.2,6.8Z"/>
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="14" y1="9" x2="14.9" y2="9.9"/>
                </svg>
            </h2>
            <div class="hiw-description">
                <p>Portals are one way to get around the Tower. You can link a portal from any floor or room to any other floor, room, or external URL. Portals can be a text box, invisible click area, an image, or anything else you can come up with. You can even use portals as JavaScript triggers to do more interesting things.</p>
                <p>If you are the owner of a floor, you can hit the Edit Portals button in the toolbar, then resize and place them where you like. There are additional instructions in the interface.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>
                The STAIRS
                <img src="/wp-content/plugins/the-spiral-tower/dist/images/stairs.svg" alt="Stairs Icon" style="width: 28px; height: 28px; filter: brightness(0) saturate(100%) invert(84%) sepia(75%) saturate(2489%) hue-rotate(346deg) brightness(104%) contrast(101%);"/>
            </h2>
            <div class="hiw-description">
                <p>The Spiral Tower All-Inclusive Rail System (STAIRS) can be used to access all of the floors that normal stairs and elevators can reach.</p>
                <p>Like their architectural namesake, the STAIRS is zero-fare and open 24/7/365. It's faster than taking the elevator when moving across a large number of floors.</p>
                <p>STAIRS only go to whole-number floors and those that have not opted out of public transit. You'll have to find another way to reach those floors. There is always a way.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>
                The TWIST
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 139.6 135.1" width="28" height="28">
                    <path fill="#ffd700" d="M53.6,61.4C34.1,56.7-1.6,43,8.4,17.1-3.2,26.3-1.9,42.3,6.9,53.1c19.8,24.3,75.1,27.1,103.3,20.3,8.6-2.1,18.4-5.7,25.2-11.3-26.8,7.4-55,5.7-81.8-.7Z"/>
                    <path fill="#ffd700" d="M26.9,44.1c1.9.8,1.5-1,1.3-2.3-1.2-5.9-3-7.3,1.2-13.2,10.9-15.3,46.4-9.4,62-5,6.5,1.8,12.5,4.8,19,6.5C93.1,13.7,68.6,6.3,44.9,8.1c-13.8,1.1-41.1,8.2-27.9,26.9,1.2,1.6,8.5,8.4,9.9,9.1Z"/>
                    <path fill="#ffd700" d="M114.4,61.1c16-2.7,29.3-11.2,24-29.5C130.7,4.4,76.7-.8,53.4,0l27.8,4.7c26.6,6.3,69.4,29.1,33.2,56.3Z"/>
                    <path fill="#ffd700" d="M21.4,72.1c9.5,32.9,58.1,26.1,79,9-26.2,5.7-56.2,7.3-79-9Z"/>
                    <path fill="#ffd700" d="M60.1,42.3c15.2-6.1,32.1,1.5,46.3,6.8-12.6-11.4-33.7-17.5-50.4-13.9-6.6,1.4-16.4,6.2-11.7,14.4,1.3,2.3,9.5,7.7,12,6.9-5.6-5.2-2.8-11.7,3.7-14.3Z"/>
                    <path fill="#ffd700" d="M90.4,100.1c-3.3,1.1-5.2,3.4-8.7,4.8-10.7,4.2-20.2,3.2-31.3,1.7,11,9.9,39.7,14.2,40-6.5Z"/>
                    <path fill="#ffd700" d="M93.7,122.1c-2.9,1.8-5.9,2.3-9.3,2-1,.9,7.9,9.9,9,10.5,1.4.7,1.6.7,2.5-.5l-.5-14c-1.5-.4-.7,1.4-1.7,2Z"/>
                </svg>
            </h2>
            <div class="hiw-description">
                <p>This is the Teleportation Wizard In Spiral Tower, or TWIST. Every whole number floor (except the ones that opted out) are reachable from it. There is a text box accessible from the toolbar; just type in where you want to go and it will automatically send you there. Type in "170" and you're instantly teleported to Floor 170. But it's not just numerical; type in "Moat" and zap! You're in the moat.</p>
                <p><strong>Use at your own risk!</strong> If your term matches multiple destinations, you will be randomly teleported to one of those destinations. If your term doesn't match any destination, you could end up anywhere!</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>Other Elevators and Transportation</h2>
            <div class="hiw-description">
                <p>There are various elevators and transportation systems hidden in rooms throughout the Tower. For example, floors 9001 through 9009 are only accessible from the VIP elevator on floor 9000. Please have your VIP card ready for inspection if you plan on going there.</p>
                <p>The roof of the tower is lovely this time of year. It is so choice. If you have the means, I highly recommend visiting it.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>Achievements</h2>
            <div class="hiw-description">
                <p>There are achievements for creating, exploring, solving puzzles, and more. You must be logged in with your own account to earn them.</p>
                <?php if (is_user_logged_in()): ?>
                    <p>Check your <a href="/u/<?php echo esc_html(wp_get_current_user()->user_login); ?>">profile page</a> to see your progress.</p>
                <?php else: ?>
                    <p>Login to track your achievements and see your progress through the tower.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="hiw-section">
            <h2>Prizes!</h2>
            <div class="hiw-description">
                <p>Prizes will be given to the first person to earn a specific achievement—and perhaps to others as well.</p>
                <p>There are no official rules. Admins will award people with appropriate items at their discretion, based on the difficulty of the achievement or whatever makes them laugh the most.</p>
            </div>
        </section>

        <section class="hiw-section">
            <h2>Other Stuff</h2>
            <div class="hiw-description">
                <p>It's out there. Lots and lots of it. I'm not telling where or what it is, though.</p>
            </div>
        </section>

    </main>

    <nav class="tower-navigation">
        <h3>Quick Navigation</h3>
        <div class="nav-links">
            <a href="/" class="nav-link">Home</a>
            <a href="/stairs" class="nav-link">Stairs</a>
            <a href="/about" class="nav-link">About</a>
            <a href="/authors" class="nav-link">Authors</a>
        </div>
    </nav>
</div>

<?php wp_footer(); ?>
</body>
</html>