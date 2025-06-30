/**
 * Spiral Tower - Achievement Test (BASIC CENTER TEST)
 * spiral-tower-achievements.js
 */

window.SpiralTower = window.SpiralTower || {};
window.SpiralTower.logger = window.SpiralTower.logger || {
    log: function(module, ...args) { console.log(`[SpiralTower/${module}]`, ...args); },
    warn: function(module, ...args) { console.warn(`[SpiralTower/${module}] WARN:`, ...args); },
    error: function(module, ...args) { console.error(`[SpiralTower/${module}] ERROR:`, ...args); }
};

SpiralTower.achievements = (function () {
    const MODULE_NAME = 'achievements';
    const logger = SpiralTower.logger;

    let achievementQueue = [];
    let isShowingAchievement = false;
    let miniAchievements = []; // Track the mini achievements on the left

    function loadAchievementData() {
        const achievementData = window.spiralTowerAchievements || null;
        if (achievementData && achievementData.achievements && achievementData.achievements.length > 0) {
            achievementQueue = [...achievementData.achievements];
            return true;
        }
        return false;
    }

    function testCenterAlignment(achievement) {
        // Create notification - starts at bottom
        const notification = document.createElement('img');
        notification.src = '/wp-content/plugins/the-spiral-tower/assets/images/achievements/notification.png';
        notification.style.cssText = `
            position: fixed;
            left: 50%;
            top: 150vh;
            transform: translateX(-50%) translateY(-50%) scale(0.4);
            z-index: 10001;
            transition: top 0.6s linear, opacity 2s ease-out;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.9));
        `;

        // Create icon - starts at top
        const icon = document.createElement('img');
        icon.src = achievement.image;
        icon.style.cssText = `
            position: fixed;
            left: 50%;
            top: -50vh;
            transform: translateX(-50%) translateY(-50%) scale(0.27);
            z-index: 10002;
            transition: top 0.6s linear;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.9));
        `;

        document.body.appendChild(notification);
        document.body.appendChild(icon);

        // Move them past each other first
        setTimeout(() => {
            notification.style.top = '40vh';  // notification goes past center
            icon.style.top = '70vh';          // icon goes past center
            
            console.log("Images moving past each other");
            
            // Then bounce back to final center positions  
            setTimeout(() => {
                notification.style.transition = 'top 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                icon.style.transition = 'top 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                
                notification.style.top = '53vh';
                icon.style.top = '55vh';
                
                console.log("Images bouncing back to center");
            }, 600);
            
        }, 100);
        
        // Stay in center for 4 seconds, then fade/move
        setTimeout(() => {
            console.log("Starting fade and move...");
            
            // Add transform transition to notification for smooth scaling
            notification.style.transition = 'all 2s ease-out';
            notification.style.zIndex = '998'; // Put notification below the icon
            
            // Fade out, scale down, and drop the notification
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(-50%) translateY(-50%) scale(0.1)';
            notification.style.top = '120vh'; // Fall down past bottom of screen
            
            // Remove the old icon and create a new div that starts at center
            const iconRect = icon.getBoundingClientRect();
            icon.remove();
            
            const newIcon = document.createElement('div');
            newIcon.style.cssText = `
                position: fixed !important;
                left: ${iconRect.left}px !important;
                top: ${iconRect.top}px !important;
                width: ${iconRect.width}px !important;
                height: ${iconRect.height}px !important;
                background-image: url(${achievement.image}) !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
                z-index: 10003 !important;
                filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.9)) !important;
                transition: all 1s ease-out !important;
                cursor: pointer !important;
            `;
            
            // Add hover tooltip for achievement description
            newIcon.title = achievement.description || achievement.name || 'Achievement Unlocked';
            
            // Add hover effect
            newIcon.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.filter = 'drop-shadow(0 20px 40px rgba(0, 0, 0, 0.9)) brightness(1.2)';
            });
            
            newIcon.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.filter = 'drop-shadow(0 20px 40px rgba(0, 0, 0, 0.9))';
            });
            
            document.body.appendChild(newIcon);
            
            // Calculate position in the column
            const columnPosition = 110 + (miniAchievements.length * 130); // Increased spacing from 90 to 130
            
            // Then animate it to final position in the column (2x faster - 1s instead of 2s)
            setTimeout(() => {
                newIcon.style.left = '10px';
                newIcon.style.top = `${columnPosition}px`;
                newIcon.style.width = '120px';  // 50% larger than 80px
                newIcon.style.height = '120px'; // 50% larger than 80px
            }, 50);
            
            // Add to miniAchievements array
            miniAchievements.push(newIcon);
            
            console.log("Icon moved to column position", columnPosition);
            
            // Ready for next achievement
            setTimeout(() => {
                isShowingAchievement = false;
                showNextAchievement();
            }, 1000);
            
        }, 6100); // 100ms + 600ms + 400ms + 5000ms (5 second center display)
    }

    function showNextAchievement() {
        if (isShowingAchievement || achievementQueue.length === 0) {
            return;
        }
        isShowingAchievement = true;
        const achievement = achievementQueue.shift();
        testCenterAlignment(achievement);
    }

    async function init() {
        if (!document.querySelector('.spiral-tower-floor-container')) {
            return;
        }

        const hasAchievements = loadAchievementData();
        if (hasAchievements) {
            setTimeout(showNextAchievement, 1000);
        }
    }

    return {
        init: init,
        addAchievement: function() {},
        getQueueLength: () => achievementQueue.length,
        clearQueue: () => { achievementQueue = []; },
        clearMiniAchievements: function() {}
    };

})();