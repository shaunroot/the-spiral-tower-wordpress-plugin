/**
 * Spiral Tower - Achievement Notification Module
 * Handles displaying achievement notifications when users earn achievements
 * spiral-tower-achievements.js
 */

// Ensure the global namespace and logger exist
window.SpiralTower = window.SpiralTower || {};
window.SpiralTower.logger = window.SpiralTower.logger || {
    log: function(module, ...args) { console.log(`[SpiralTower/${module}]`, ...args); },
    warn: function(module, ...args) { console.warn(`[SpiralTower/${module}] WARN:`, ...args); },
    error: function(module, ...args) { console.error(`[SpiralTower/${module}] ERROR:`, ...args); }
};

SpiralTower.achievements = (function () {
    const MODULE_NAME = 'achievements';
    const logger = SpiralTower.logger;

    // --- Private Variables ---
    let achievementQueue = [];
    let isShowingAchievement = false;
    let notificationContainer = null;
    let notification = null;
    let titleEl = null;
    let descriptionEl = null;
    let pointsEl = null;
    let imgEl = null;

    // --- Private Functions ---

    /**
     * Create the achievement notification HTML structure
     */
    function createNotificationHTML() {
        const html = `
            <div id="achievement-notification-container" class="achievement-notification-container">
                <div id="achievement-notification" class="achievement-notification">
                    <div class="achievement-content">
                        <div class="achievement-image">
                            <img id="achievement-img" src="" alt="" />
                        </div>
                        <div class="achievement-text">
                            <div class="achievement-title" id="achievement-title"></div>
                            <div class="achievement-description" id="achievement-description"></div>
                            <div class="achievement-points" id="achievement-points"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Insert the HTML into the page
        document.body.insertAdjacentHTML('beforeend', html);
        
        // Cache DOM elements
        notificationContainer = document.getElementById('achievement-notification-container');
        notification = document.getElementById('achievement-notification');
        titleEl = document.getElementById('achievement-title');
        descriptionEl = document.getElementById('achievement-description');
        pointsEl = document.getElementById('achievement-points');
        imgEl = document.getElementById('achievement-img');
        
        logger.log(MODULE_NAME, "Achievement notification HTML structure created");
    }

    /**
     * Load achievement data from PHP and populate the queue
     */
    function loadAchievementData() {
        // Get achievement data passed from PHP
        const achievementData = window.spiralTowerAchievements || null;

        if (achievementData && achievementData.achievements && achievementData.achievements.length > 0) {
            logger.log(MODULE_NAME, "Found achievements from PHP:", achievementData.achievements);
            // Add achievements to queue 
            achievementQueue = [...achievementData.achievements];
            return true;
        } else {
            logger.log(MODULE_NAME, "No achievement data found from PHP");
            return false;
        }
    }

    /**
     * Show the next achievement in the queue
     */
    function showNextAchievement() {
        if (isShowingAchievement || achievementQueue.length === 0) {
            logger.log(MODULE_NAME, "Skipping showNextAchievement - isShowing:", isShowingAchievement, "queueLength:", achievementQueue.length);
            return;
        }

        isShowingAchievement = true;
        const achievement = achievementQueue.shift();
        logger.log(MODULE_NAME, "Showing achievement:", achievement);

        // Validate DOM elements exist
        if (!notification || !titleEl || !descriptionEl || !pointsEl || !imgEl) {
            logger.error(MODULE_NAME, "Achievement notification elements not found");
            isShowingAchievement = false;
            return;
        }

        // Set achievement data
        titleEl.textContent = achievement.title;
        descriptionEl.textContent = achievement.description;
        pointsEl.textContent = achievement.points;
        imgEl.src = achievement.image;
        imgEl.alt = achievement.title;

        logger.log(MODULE_NAME, "Set notification content:", {
            title: achievement.title,
            description: achievement.description,
            points: achievement.points,
            image: achievement.image
        });

        // Handle image load error
        imgEl.onerror = function() {
            this.style.display = 'none';
            logger.warn(MODULE_NAME, "Achievement image not found:", achievement.image);
        };

        // Show notification
        notification.classList.add('show');
        logger.log(MODULE_NAME, "Added show class to notification");

        // Add pulse effect after slide-in
        setTimeout(() => {
            notification.classList.add('pulse');
        }, 600);

        // Remove pulse effect
        setTimeout(() => {
            notification.classList.remove('pulse');
        }, 1200);

        // Hide notification after 4 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            
            // Reset for next achievement after slide-out completes
            setTimeout(() => {
                isShowingAchievement = false;
                showNextAchievement(); // Show next achievement if any
            }, 500);
        }, 4000);
    }

    /**
     * Start the achievement notification process
     */
    function startNotifications() {
        // Start showing achievements after a brief delay
        setTimeout(() => {
            showNextAchievement();
        }, 1000);
    }

    // --- Public API ---

    /**
     * Initialize the achievement notification system
     */
    async function init() {
        logger.log(MODULE_NAME, "Initializing achievement notification system...");
        logger.log(MODULE_NAME, "Current page URL:", window.location.href);
        logger.log(MODULE_NAME, "Floor container exists:", !!document.querySelector('.spiral-tower-floor-container'));
        logger.log(MODULE_NAME, "spiralTowerAchievements data:", window.spiralTowerAchievements);

        // Only initialize on floor/room pages where achievements can be awarded
        if (!document.querySelector('.spiral-tower-floor-container')) {
            logger.log(MODULE_NAME, "Not on a floor/room page, skipping achievement notifications");
            return;
        }

        // Create the notification HTML structure
        createNotificationHTML();

        // Load achievement data from PHP
        const hasAchievements = loadAchievementData();

        if (hasAchievements) {
            logger.log(MODULE_NAME, `Found ${achievementQueue.length} achievements to display`);
            startNotifications();
        } else {
            logger.log(MODULE_NAME, "No achievements to display");
        }

        logger.log(MODULE_NAME, "Achievement notification system initialized");
    }

    /**
     * Manually add an achievement to the queue (for testing or dynamic awards)
     */
    function addAchievement(achievement) {
        if (!achievement || !achievement.title || !achievement.description) {
            logger.error(MODULE_NAME, "Invalid achievement data provided to addAchievement");
            return;
        }

        logger.log(MODULE_NAME, "Adding achievement to queue:", achievement);
        achievementQueue.push(achievement);

        // If not currently showing an achievement, start the process
        if (!isShowingAchievement) {
            showNextAchievement();
        }
    }

    /**
     * Get the current queue length (for debugging)
     */
    function getQueueLength() {
        return achievementQueue.length;
    }

    /**
     * Clear the achievement queue (for testing)
     */
    function clearQueue() {
        logger.log(MODULE_NAME, "Clearing achievement queue");
        achievementQueue = [];
    }

    // Return the public API
    return {
        init: init,
        addAchievement: addAchievement,
        getQueueLength: getQueueLength,
        clearQueue: clearQueue
    };

})(); // End of Achievement Module IIFE