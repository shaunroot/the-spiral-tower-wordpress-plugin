<?php
/**
 * Custom 404 Template for "void" floors in Spiral Tower
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e("The Void", "spiral-tower"); ?> - <?php bloginfo('name'); ?></title>
    
    <!-- Import Metamorphous font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Metamorphous&display=swap" rel="stylesheet">
    
    <!-- Core styles defined in the head to avoid dependency on external CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        body {
            background-color: #000;
            color: #fff;
            height: 100vh;
            width: 100vw;
            position: relative;
            font-family: 'Metamorphous', serif;
        }
        
        .spiral-void {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .spiral-void video {
            object-fit: cover;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .spiral-void-content {
            position: relative;
            z-index: 2;
            height: 100vh;
            width: 100vw;
        }
        
        h1 {
            display: none;
        }
        
        .scrolling-text {
            position: absolute;
            white-space: nowrap;
            font-size: 10vh;
            letter-spacing: 0.2vw;
            color: black;
            opacity: 0.9;
            bottom: 100px;
            animation: scrollText 45s linear infinite;
            text-shadow: 0 0 10px white, 0 0 15px white;
            text-align: left;
            font-family: 'Metamorphous', serif;
            overflow: visible !important;
        }
        
        @keyframes scrollText {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }
        
        .letter {
            display: inline-block;
            opacity: 0.5;
            overflow: visible !important;
        }
        
        .emoji-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 5rem;
            z-index: 5;
            text-align: center;
            will-change: transform, opacity;
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));
            cursor: pointer; /* Add cursor pointer to indicate clickability */
        }
        
        a {
            color: #ff00ff;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
    <?php 
    // Prepare data for JavaScript search functionality
    $ajax_url = admin_url('admin-ajax.php');
    $ajax_nonce = wp_create_nonce('spiral_tower_floor_search_nonce');
    ?>
</head>

<body <?php body_class("spiral-void-page"); ?>>
    <div class="spiral-void">
        <video autoplay loop muted playsinline>
            <source src="<?php echo plugin_dir_url( __FILE__ ) . '../dist/images/the-void.mp4'; ?>" type="video/mp4">
        </video>
        
        <div class="spiral-void-content">
            <h1 id="originalText">Uh oh. You took a majorly wrong turn. It doesn't feel so much like falling as it does watching the tower fly by you like a bullet train. <a search=".">Grab onto something</a></h1>
            <div id="textContainer" class="scrolling-text"></div>
        </div>
    </div>
    
    <!-- Multiple emoji containers -->
    <div id="emojiContainer"></div>
    
    <!-- Hard-coded JavaScript, independent of any theme scripts -->
    <script>
        // Implementation using modern JavaScript standards 
        window.addEventListener('DOMContentLoaded', () => {
            // Text phrases
            const textPhrases = [
                "Uh oh.",
                "You took a majorly wrong turn.",
                "It doesn't feel so much like falling as it does watching the time and space fly by you.",
                "You better grab onto something."
            ];
            
            // Get references to elements
            const textContainer = document.getElementById('textContainer');
            const emojiContainer = document.getElementById('emojiContainer');
            
            // Setup search functionality
            const ajaxUrl = '<?php echo esc_url($ajax_url); ?>';
            const ajaxNonce = '<?php echo esc_js($ajax_nonce); ?>';
            
            // Function to perform search for "."
            function performDotSearch() {
                const formData = new FormData();
                formData.append('action', 'spiral_tower_floor_search');
                formData.append('nonce', ajaxNonce);
                formData.append('search_term', '.');
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.redirect_url) {
                        window.location.href = data.data.redirect_url;
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
            }
            
            // Setup text
            if (textContainer) {
                let processedText = '';
                
                // Add spacing between phrases
                textPhrases.forEach((phrase, phraseIndex) => {
                    if (phraseIndex > 0) {
                        processedText += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    
                    // Process each character
                    for (let i = 0; i < phrase.length; i++) {
                        const char = phrase[i];
                        
                        if (char !== ' ') {
                            // Create unique keyframes for each letter
                            const animName = `pulse${Math.floor(Math.random() * 1000000)}`;
                            const delay = Math.random() * 3;
                            const duration = 2 + Math.random() * 3; // 2-5 seconds
                            const scale = 1.1 + Math.random() * 0.4; // Reduced to 1.1-1.5 scale
                            const rotation = -5 + Math.random() * 10; // Random rotation between -5 and +5 degrees
                            
                            // Create style element with unique keyframes
                            const style = document.createElement('style');
                            style.textContent = `
                                @keyframes ${animName} {
                                    0% { transform: scale(1) rotate(${rotation}deg); }
                                    50% { transform: scale(${scale}) rotate(${rotation}deg); }
                                    100% { transform: scale(1) rotate(${rotation}deg); }
                                }
                            `;
                            document.head.appendChild(style);
                            
                            // Add styled letter
                            processedText += `<span class="letter" style="animation: ${animName} ${duration}s infinite ease-in-out; animation-delay: ${delay}s; transform: rotate(${rotation}deg);">${char}</span>`;
                        } else {
                            processedText += '&nbsp;';
                        }
                    }
                });
                
                // Set content
                textContainer.innerHTML = processedText;
            }
            
            // Setup emojis animation
            const emojis = ['√-1', '💀', '👁️', '💩', '🌌', '🕳️', '☄️', '▓', 'ʥ', '𝞹', '🔥', 'ѯ', '🎇', '✨', 'ᴁ', '🪐'];
          
            // Track active emoji elements
            let activeEmojis = [];
            const MAX_EMOJIS = 4;
            
            // Function to create and animate a new emoji
            function createEmoji() {
                // If we already have max emojis, don't create more
                if (activeEmojis.length >= MAX_EMOJIS) {
                    return;
                }
                
                // Create new emoji element
                const emojiElement = document.createElement('div');
                emojiElement.className = 'emoji-container';
                emojiElement.textContent = emojis[Math.floor(Math.random() * emojis.length)];
                
                // Add click handler for search
                emojiElement.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    performDotSearch();
                });
                
                // Add glow effect
                emojiElement.style.filter = 'drop-shadow(0 0 8px rgba(255, 255, 255, 0.8))';
                
                // Add to DOM
                document.body.appendChild(emojiElement);
                
                // Add to tracking array
                activeEmojis.push(emojiElement);
                
                // Manual animation - no reliance on CSS animations
                let startTime = null;
                const duration = 8000; // 8 seconds (50% slower than original 4s)
                
                // Generate random direction
                const angle = Math.random() * Math.PI * 2;
                const distance = window.innerWidth > window.innerHeight ? 
                                window.innerWidth * 1.5 : window.innerHeight * 1.5;
                
                // Generate random rotation speed (-20 to 20 degrees per second)
                const rotationSpeed = -20 + Math.random() * 40;
                
                // Initial setup - start with 0.01% scale and 0 opacity
                emojiElement.style.transition = 'none';
                emojiElement.style.transform = 'translate(-50%, -50%) scale(0.0001) rotate(0deg)';
                emojiElement.style.opacity = '0';
                
                // Force reflow
                void emojiElement.offsetWidth;
                
                // Animation frame function with fade-in while moving
                function animateFrame(timestamp) {
                    if (!startTime) startTime = timestamp;
                    const elapsed = timestamp - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Ease-in effect for movement
                    const easedProgress = Math.pow(progress, 1.5);
                    
                    // Calculate current position
                    const endX = Math.cos(angle) * distance * easedProgress;
                    const endY = Math.sin(angle) * distance * easedProgress;
                    
                    // Calculate current rotation (linear)
                    const currentRotation = rotationSpeed * progress * 8; // 8 seconds worth of rotation
                    
                    // Scale and opacity curves
                    const growCurve = progress < 0.6 ? progress / 0.6 : 1;
                    const fadeInCurve = progress < 0.3 ? progress / 0.3 : 1; // Faster fade in
                    
                    // Calculate values with adjusted ranges
                    const baseScale = 0.0001 + growCurve * 2; // Smaller max size (was 5)
                    
                    // Scale decreases in final phase - end at 1/8 of max size (was 1/4)
                    const finalScale = progress > 0.7 ? 
                        baseScale - ((progress - 0.7) / 0.3) * (baseScale - baseScale * 0.125) : 
                        baseScale;
                    
                    // Opacity maxes at 0.8 then fades out
                    const opacityCurve = progress < 0.3 ? 
                        fadeInCurve * 0.8 : // Fade in to 0.8
                        0.8 - ((progress - 0.3) / 0.7) * 0.8; // Fade out from 0.8 to 0
                    
                    // Apply styles with rotation
                    emojiElement.style.transform = `translate(calc(-50% + ${endX}px), calc(-50% + ${endY}px)) scale(${finalScale}) rotate(${currentRotation}deg)`;
                    emojiElement.style.opacity = `${opacityCurve}`;
                    
                    // Adjust glow based on opacity
                    const glowIntensity = opacityCurve * 10; // Stronger glow at peak opacity
                    emojiElement.style.filter = `drop-shadow(0 0 ${glowIntensity}px rgba(255, 255, 255, 0.8))`;
                    
                    // Continue animation or clean up
                    if (progress < 1) {
                        requestAnimationFrame(animateFrame);
                    } else {
                        // Remove from active emojis
                        const index = activeEmojis.indexOf(emojiElement);
                        if (index > -1) {
                            activeEmojis.splice(index, 1);
                        }
                        
                        // Remove from DOM
                        document.body.removeChild(emojiElement);
                    }
                }
                
                // Start animation
                requestAnimationFrame(animateFrame);
            }
            
            // Spawn new emojis at random intervals
            function spawnEmoji() {
                createEmoji();
                
                // Schedule next emoji with random delay between 500ms and 1500ms
                const nextDelay = 500 + Math.random() * 1000;
                setTimeout(spawnEmoji, nextDelay);
            }
            
            // Start spawning emojis
            spawnEmoji();
        });
    </script>
</body>
</html>