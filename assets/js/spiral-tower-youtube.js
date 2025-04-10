/**
 * Spiral Tower - YouTube Module
 * Handles YouTube player functionality and sound toggle
 */

window.SpiralTower = window.SpiralTower || {};

// Initialize YouTube module
SpiralTower.youtube = (function() {
    // Module state
    let ytPlayer = null;
    let isPlayerReady = false;
    let videoPlayer = null;

    // Update sound toggle button visuals
    function updateSoundToggleVisuals(isMuted) {
        console.log('>>> FN: updateSoundToggleVisuals START', { isMuted });
        const btnOn = document.getElementById('volume-on-icon');
        const btnOff = document.getElementById('volume-off-icon');
        const parentBtn = document.getElementById('button-sound-toggle');
        const currentContainer = document.querySelector('[data-barba="container"]');
        const hasYoutubeOnPage = currentContainer ? currentContainer.querySelector('#youtube-player') : null;

        if (!btnOn || !btnOff || !parentBtn) {
            console.log('<<< FN: updateSoundToggleVisuals END - Elements missing');
            return;
        }

        if (hasYoutubeOnPage) {
            parentBtn.style.display = 'block';
            parentBtn.style.visibility = 'visible';
            parentBtn.style.opacity = '1';
            btnOff.style.display = isMuted ? 'block' : 'none';
            btnOn.style.display = isMuted ? 'none' : 'block';
            console.log('--- Sound toggle VISIBLE, Muted:', isMuted);
        } else {
            parentBtn.style.display = 'none';
            console.log('--- Sound toggle HIDDEN (no player)');
        }
        console.log('<<< FN: updateSoundToggleVisuals END');
    }

    // Initialize player for container
    function initializePlayerForContainer(container) {
        console.log('>>> FN: initializePlayerForContainer START');
        if (!container) {
            console.log("LOG: initializePlayer - No container provided.");
            updateSoundToggleVisuals(true);
            console.log('<<< FN: initializePlayerForContainer END - No container');
            return;
        }
        const iframe = container.querySelector('#youtube-player');
        videoPlayer = iframe; // Update reference

        if (iframe) {
            console.log("LOG: Found iframe (#youtube-player), attempting to create YT.Player...");
            if (ytPlayer && typeof ytPlayer.destroy === 'function') {
                console.log("LOG: Destroying existing ytPlayer before creating new one.");
                destroyPlayer();
            }
            try {
                ytPlayer = new YT.Player('youtube-player', {
                    events: {
                        'onReady': onPlayerReady,
                        'onError': onPlayerError,
                        'onStateChange': onPlayerStateChange
                    }
                });
                console.log("LOG: YT.Player object requested/created:", ytPlayer);
            } catch (e) {
                console.error("LOG: Error creating YT.Player:", e);
                ytPlayer = null;
                isPlayerReady = false;
                updateSoundToggleVisuals(true);
            }
        } else {
            console.log("LOG: Iframe '#youtube-player' not found in this container.");
            ytPlayer = null;
            isPlayerReady = false;
            updateSoundToggleVisuals(true);
        }
        console.log('<<< FN: initializePlayerForContainer END');
    }

    // YouTube player event handlers
    function onPlayerReady(event) {
        console.log('LOG: YouTube Player Ready.');
        if (event && event.target) {
            ytPlayer = event.target;
            isPlayerReady = true;
            updateSoundToggleVisuals(ytPlayer.isMuted());
        } else {
            console.warn('LOG: onPlayerReady called but event.target is missing.');
            isPlayerReady = false;
            updateSoundToggleVisuals(true);
        }
    }

    function onPlayerStateChange(event) {
        // console.log("Player state changed:", event.data);
        if (event.data === YT.PlayerState.ENDED && ytPlayer) { /* Loop logic if needed */ }
    }

    function onPlayerError(event) {
        console.error('LOG: YouTube Player Error Code:', event.data);
        ytPlayer = null;
        isPlayerReady = false;
        updateSoundToggleVisuals(true);
    }

    // Toggle sound
    function toggleSound() {
        console.log('>>> FN: toggleSound START');
        console.log("LOG: toggleSound called. Current ytPlayer:", ytPlayer, "isPlayerReady:", isPlayerReady);
        if (!ytPlayer || !isPlayerReady) {
            console.warn('LOG: Player not ready or not available to toggle sound.');
            console.log('<<< FN: toggleSound END - Player not ready');
            return;
        }
        try {
            if (ytPlayer.isMuted()) {
                ytPlayer.unMute();
                console.log('LOG: Sound Unmuted');
                updateSoundToggleVisuals(false);
            } else {
                ytPlayer.mute();
                console.log('LOG: Sound Muted');
                updateSoundToggleVisuals(true);
            }
        } catch (e) {
            console.error("LOG: Error toggling sound:", e);
        }
        console.log('<<< FN: toggleSound END');
    }

    // Destroy player
    function destroyPlayer() {
        console.log('>>> FN: destroyPlayer START');
        console.log('LOG: Attempting to destroy YouTube player...');
        if (ytPlayer && typeof ytPlayer.destroy === 'function') {
            try {
                if (typeof ytPlayer.stopVideo === 'function' && typeof ytPlayer.getPlayerState === 'function') {
                    const state = ytPlayer.getPlayerState();
                    if (state === YT.PlayerState.PLAYING || state === YT.PlayerState.PAUSED || state === YT.PlayerState.BUFFERING) {
                        ytPlayer.stopVideo();
                        console.log('LOG: Called stopVideo().');
                    }
                }
                ytPlayer.destroy();
                console.log('LOG: YouTube Player Destroyed.');
            } catch (e) {
                console.error("LOG: Error destroying player:", e);
            }
        } else {
            console.log('LOG: No player instance found to destroy or destroy function unavailable.');
        }
        ytPlayer = null;
        isPlayerReady = false;
        updateSoundToggleVisuals(true);
        console.log('<<< FN: destroyPlayer END');
    }

    // YouTube IFrame API Ready handler - defined on window for API callback
    window.onYouTubeIframeAPIReady = function() {
        console.log('%cLOG: window.onYouTubeIframeAPIReady CALLED.', 'color: green; font-weight: bold;');
        const currentContainer = document.querySelector('[data-barba="container"]');
        initializePlayerForContainer(currentContainer);
    };

    // Public API
    return {
        // Initialize module
        init: function() {
            console.log("YouTube module initialized");
            return Promise.resolve();
        },
        
        // Export functions
        updateSoundToggleVisuals,
        initializePlayerForContainer,
        toggleSound,
        destroyPlayer,
        getPlayer: function() { return ytPlayer; }
    };
})();