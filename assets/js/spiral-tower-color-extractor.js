/**
 * Spiral Tower - Color Extractor Module
 * Extracts colors from the background image and applies them as CSS variables.
 * Respects manually set colors via data attributes.
 * Integrates with the Spiral Tower module system.
 * Includes detailed logging for video background scenarios.
 */

// Ensure the global namespace exists
window.SpiralTower = window.SpiralTower || {};
// Ensure the logger exists (basic fallback)
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error };

SpiralTower.colorExtractor = (function () {
    const MODULE_NAME = 'colorExtractor';
    const logger = SpiralTower.logger;

    // --- State ---
    let state = {
        initialized: false,
        activeImageSrc: null // Track the source of the image we processed
    };

    // --- Default Fallback Colors ---
    // Define these constants so they can be referenced in logging
    const FALLBACK_COLORS = {
        titleTextColor: '#FFFFFF',
        titleBgColor: 'rgba(0, 0, 0, 0.7)',
        contentTextColor: '#F0F0F0',
        contentBgColor: 'rgba(30, 30, 30, 0.7)',
        subtitleColor: '#CCCCCC' // For floor number
    };

    // --- Private Functions ---

    /**
     * Reads custom color overrides from body data attributes.
     */
    function getCustomColors() {
        const body = document.body;
        // Return null for attributes that are missing or empty
        const getColorAttr = (attr) => body.getAttribute(attr) || null;
        return {
            titleColor: getColorAttr('data-title-color'),
            titleBgColor: getColorAttr('data-title-bg-color'),
            contentColor: getColorAttr('data-content-color'),
            contentBgColor: getColorAttr('data-content-bg-color'),
            floorNumberColor: getColorAttr('data-floor-number-color')
        };
    }

    /**
     * Applies only the explicitly provided custom colors as CSS variables.
     * Returns true if any custom colors were applied.
     */
    function applyExplicitCustomColors(customColors) {
        const root = document.documentElement;
        let appliedAny = false;
        const appliedList = {}; // Track which were applied

        if (customColors.titleColor) {
            root.style.setProperty('--title-text-color', customColors.titleColor);
            appliedAny = true;
            appliedList.titleColor = customColors.titleColor;
        }
        if (customColors.titleBgColor) {
            root.style.setProperty('--title-bg-color', customColors.titleBgColor);
            appliedAny = true;
            appliedList.titleBgColor = customColors.titleBgColor;
        }
        if (customColors.contentColor) {
            root.style.setProperty('--content-text-color', customColors.contentColor);
            appliedAny = true;
            appliedList.contentColor = customColors.contentColor;
        }
        if (customColors.contentBgColor) {
            root.style.setProperty('--content-bg-color', customColors.contentBgColor);
            appliedAny = true;
            appliedList.contentBgColor = customColors.contentBgColor;
        }
        if (customColors.floorNumberColor) {
            root.style.setProperty('--subtitle-color', customColors.floorNumberColor);
            appliedAny = true;
            appliedList.floorNumberColor = customColors.floorNumberColor;
        }

        if (appliedAny) {
            logger.log(MODULE_NAME, 'Applied explicit custom colors from data attributes:', appliedList);
        } else {
            logger.log(MODULE_NAME, 'No explicit custom colors found in data attributes.');
        }
        return appliedAny;
    }

    /**
     * Extracts dominant/interesting colors from a loaded image element.
     * (This function remains the same as before)
     */
    function extractColorsFromImage(imgElement) {
        // ... (extraction logic as before - no changes needed here) ...
        logger.log(MODULE_NAME, `Extracting colors from image: ${imgElement.src}`);
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const size = 64; canvas.width = size; canvas.height = size;
            ctx.drawImage(imgElement, 0, 0, size, size);
            const imageData = ctx.getImageData(0, 0, size, size); const data = imageData.data;
            const darkColors = [], brightColors = [], vividColors = [];
            for (let i = 0; i < data.length; i += 4) {
                const r = data[i], g = data[i + 1], b = data[i + 2]; if (data[i + 3] < 128) continue;
                const brightness = r + g + b; const max = Math.max(r, g, b); const min = Math.min(r, g, b);
                const saturation = (max === 0) ? 0 : (max - min) / max; const colorData = { r, g, b, brightness, saturation };
                if (brightness < 350) { darkColors.push(colorData); } else { brightColors.push(colorData); }
                if (saturation > 0.3) { vividColors.push(colorData); }
            }
            vividColors.sort((a, b) => b.saturation - a.saturation); darkColors.sort((a, b) => b.saturation - a.saturation); brightColors.sort((a, b) => b.saturation - a.saturation);
            let vividColor = vividColors.length > 0 ? vividColors[0] : (brightColors.length > 0 ? brightColors[0] : { r: 200, g: 200, b: 200 });
            let darkColor, brightColor; if (vividColor.brightness < 350) { darkColor = vividColor; brightColor = brightColors.length > 0 ? brightColors[0] : { r: 220, g: 220, b: 220 }; } else { brightColor = vividColor; darkColor = darkColors.length > 0 ? darkColors[0] : { r: 50, g: 50, b: 50 }; }
            const useLightText = Math.random() < 0.5; const titleBgColor = useLightText ? darkColor : brightColor; const titleTextColor = useLightText ? brightColor : darkColor;
            const secondaryDark = darkColors.length > 1 ? darkColors[1] : { r: Math.max(0, darkColor.r - 20), g: Math.max(0, darkColor.g - 20), b: Math.max(0, darkColor.b - 20) };
            const secondaryBright = brightColors.length > 1 ? brightColors[1] : { r: Math.min(255, brightColor.r + 20), g: Math.min(255, brightColor.g + 20), b: Math.min(255, brightColor.b + 20) };
            const contentBgColor = useLightText ? secondaryDark : secondaryBright; const contentTextColor = useLightText ? secondaryBright : secondaryDark;
            const tertiaryDark = darkColors.length > 2 ? darkColors[2] : { r: Math.max(0, secondaryDark.r - 15), g: Math.max(0, secondaryDark.g - 15), b: Math.max(0, secondaryDark.b - 15) };
            const tertiaryBright = brightColors.length > 2 ? brightColors[2] : { r: Math.min(255, secondaryBright.r + 15), g: Math.min(255, secondaryBright.g + 15), b: Math.min(255, secondaryBright.b + 15) };
            const accentColor = useLightText ? tertiaryBright : tertiaryDark;
            const formatRgb = (c) => `rgb(${Math.round(c.r)}, ${Math.round(c.g)}, ${Math.round(c.b)})`;
            const formatRgba = (c, alpha = 0.85) => `rgba(${Math.round(c.r)}, ${Math.round(c.g)}, ${Math.round(c.b)}, ${alpha})`;
            return { titleBgColor: formatRgba(titleBgColor), contentBgColor: formatRgba(contentBgColor), titleTextColor: formatRgb(titleTextColor), contentTextColor: formatRgb(contentTextColor), accentColor: formatRgb(accentColor) };
        } catch (error) { logger.error(MODULE_NAME, "Error during color extraction process:", error); return null; }
    }

    /**
     * Applies the generated colors, respecting any custom overrides already set.
     */
    function applyGeneratedColors(generatedColors, customColors) {
        // ... (logic as before - no changes needed here) ...
        if (!generatedColors) { logger.warn(MODULE_NAME, "No generated colors to apply."); return; }
        const root = document.documentElement; let appliedAnyGenerated = false; logger.log(MODULE_NAME, "Applying generated colors:", generatedColors);
        if (!customColors.titleBgColor) { root.style.setProperty('--title-bg-color', generatedColors.titleBgColor); appliedAnyGenerated = true; }
        if (!customColors.contentBgColor) { root.style.setProperty('--content-bg-color', generatedColors.contentBgColor); appliedAnyGenerated = true; }
        if (!customColors.titleColor) { root.style.setProperty('--title-text-color', generatedColors.titleTextColor); appliedAnyGenerated = true; }
        if (!customColors.contentColor) { root.style.setProperty('--content-text-color', generatedColors.contentTextColor); appliedAnyGenerated = true; }
        if (!customColors.floorNumberColor) { root.style.setProperty('--subtitle-color', generatedColors.accentColor); appliedAnyGenerated = true; }
        if (appliedAnyGenerated) { logger.log(MODULE_NAME, 'Applied generated colors where custom ones were missing.'); } else { logger.log(MODULE_NAME, 'All colors were already set manually or via custom attributes, no generated colors applied.'); }
    }

    /**
     * Ensures basic fallback CSS variables are set if no custom/generated ones applied.
     */
    function ensureFallbackColors(customColors) {
        const root = document.documentElement;
        const style = root.style;
        let appliedFallback = false;
        const appliedList = {};

        // Check each property: If no custom color exists *and* the CSS variable isn't already set, apply fallback.
        if (!customColors.titleColor && !style.getPropertyValue('--title-text-color')) {
            style.setProperty('--title-text-color', FALLBACK_COLORS.titleTextColor);
            appliedFallback = true; appliedList['--title-text-color'] = FALLBACK_COLORS.titleTextColor;
        }
        if (!customColors.titleBgColor && !style.getPropertyValue('--title-bg-color')) {
            style.setProperty('--title-bg-color', FALLBACK_COLORS.titleBgColor);
             appliedFallback = true; appliedList['--title-bg-color'] = FALLBACK_COLORS.titleBgColor;
        }
        if (!customColors.contentColor && !style.getPropertyValue('--content-text-color')) {
            style.setProperty('--content-text-color', FALLBACK_COLORS.contentTextColor);
             appliedFallback = true; appliedList['--content-text-color'] = FALLBACK_COLORS.contentTextColor;
        }
        if (!customColors.contentBgColor && !style.getPropertyValue('--content-bg-color')) {
            style.setProperty('--content-bg-color', FALLBACK_COLORS.contentBgColor);
             appliedFallback = true; appliedList['--content-bg-color'] = FALLBACK_COLORS.contentBgColor;
        }
        if (!customColors.floorNumberColor && !style.getPropertyValue('--subtitle-color')) {
            style.setProperty('--subtitle-color', FALLBACK_COLORS.subtitleColor);
            appliedFallback = true; appliedList['--subtitle-color'] = FALLBACK_COLORS.subtitleColor;
        }

        if (appliedFallback) {
             logger.log(MODULE_NAME, "Applied fallback colors where needed:", appliedList);
        } else {
             logger.log(MODULE_NAME, "No fallback colors needed (all covered by custom or generated).");
        }
    }

    /**
     * Logs the final computed values for colors in the video scenario.
     * Should be called AFTER applyExplicitCustomColors and ensureFallbackColors.
     */
    function logFinalVideoColors(customColors) {
         logger.log(MODULE_NAME, "--- Final Color Check (Video Background Scenario) ---");
         const root = document.documentElement;
         const computedStyle = getComputedStyle(root);

         const checkColor = (varName, customColorValue, fallbackColorValue) => {
             const finalValue = computedStyle.getPropertyValue(varName).trim();
             let source = "Unknown";
             if (customColorValue) {
                 // Check if the final value matches the custom value (tricky with color formats)
                 // This simple check might not always work if formats differ (e.g., hex vs rgb)
                 // For logging purposes, we'll assume if custom was provided, it's the source.
                 source = `Custom (${customColorValue})`;
             } else if (finalValue === fallbackColorValue || !finalValue) {
                  // If no custom value, and final matches fallback (or is empty), assume fallback
                  // Note: If CSS default exists, 'finalValue' might not be empty.
                  source = `Fallback (${fallbackColorValue})`;
             } else {
                 // It might be set by CSS defaults or previous steps
                 source = "CSS Default / Other";
             }
              logger.log(MODULE_NAME, `${varName}: "${finalValue}" (Source: ${source})`);
         };

         checkColor('--title-text-color', customColors.titleColor, FALLBACK_COLORS.titleTextColor);
         checkColor('--title-bg-color', customColors.titleBgColor, FALLBACK_COLORS.titleBgColor);
         checkColor('--content-text-color', customColors.contentColor, FALLBACK_COLORS.contentTextColor);
         checkColor('--content-bg-color', customColors.contentBgColor, FALLBACK_COLORS.contentBgColor);
         checkColor('--subtitle-color', customColors.floorNumberColor, FALLBACK_COLORS.subtitleColor);
         logger.log(MODULE_NAME, "--- End Final Color Check ---");
    }


    /**
     * Main logic to find the image, extract, and apply colors.
     */
    function runExtraction() {
        logger.log(MODULE_NAME, "Running color extraction logic...");

        // 1. Get custom color settings first
        const customColors = getCustomColors();
        // Apply any manual overrides immediately (will log inside the function)
        applyExplicitCustomColors(customColors);

        // 2. Check if all colors are manually set
        const allCustomSet = Object.values(customColors).every(color => color !== null); // Check against null now
        if (allCustomSet) {
            logger.log(MODULE_NAME, "All colors are set manually via data attributes. Skipping extraction.");
            state.activeImageSrc = 'manual';
            // Log the final state even when all are custom
            logFinalVideoColors(customColors); // Use the video logger for consistency here
            return;
        }

        // 3. Find the background image element OR check for video type
        const imageContainer = document.querySelector('#image-background');
        const imgElement = imageContainer ? imageContainer.querySelector('#background-image') : null;
        const isVideoBackground = document.body.getAttribute('data-bg-type') === 'video';

        if (isVideoBackground) {
            logger.log(MODULE_NAME, "Background type is video. Extraction skipped.");
            logger.log(MODULE_NAME, "Custom colors found for video page:", customColors);
            state.activeImageSrc = 'video';
            // Ensure fallbacks are applied if custom colors didn't cover everything
            ensureFallbackColors(customColors);
            // Log the final state
            logFinalVideoColors(customColors); // <<< ADDED LOGGING CALL
            return; // Stop processing for video
        }

        // --- Image processing path ---
        if (!imgElement) {
             logger.warn(MODULE_NAME, "No background image element (#background-image) found and not video type. Cannot extract colors.");
             state.activeImageSrc = 'none';
             ensureFallbackColors(customColors); // Ensure fallbacks even if no image
             logFinalVideoColors(customColors); // Log the final state
             return;
        }

        // Avoid reprocessing the same image
        if (imgElement.src === state.activeImageSrc) {
             logger.log(MODULE_NAME, `Image source (${imgElement.src}) hasn't changed. Skipping re-extraction.`);
             // Log the current state just in case
             logFinalVideoColors(customColors); // Use video logger for consistency
             return;
        }

        logger.log(MODULE_NAME, `Found background image: ${imgElement.src}`);

        // 4. Handle image loading
        const processLoadedImage = () => {
             logger.log(MODULE_NAME, `Processing image: ${imgElement.src}`);
             const generated = extractColorsFromImage(imgElement);
             applyGeneratedColors(generated, customColors); // Apply generated only where custom is missing
             ensureFallbackColors(customColors); // Ensure nothing is left unset
             logFinalVideoColors(customColors); // Log final state
             state.activeImageSrc = imgElement.src;
        };

        if (imgElement.complete && imgElement.naturalWidth > 0) {
             logger.log(MODULE_NAME, "Image already loaded.");
             processLoadedImage();
        } else {
            logger.log(MODULE_NAME, "Image not yet loaded. Attaching listeners.");
            // Clear previous listeners if any (important for reinit)
            imgElement.onload = null;
            imgElement.onerror = null;

            imgElement.onload = processLoadedImage;
            imgElement.onerror = () => {
                 logger.error(MODULE_NAME, `Error loading image: ${imgElement.src}. Cannot extract colors.`);
                 state.activeImageSrc = 'error';
                 ensureFallbackColors(customColors); // Apply fallbacks on error
                 logFinalVideoColors(customColors); // Log final state
            };
            // Set crossOrigin
            if (imgElement.src && !imgElement.src.startsWith(window.location.origin)) {
                 logger.log(MODULE_NAME, "Image appears to be cross-origin. Setting crossOrigin='Anonymous'. Ensure server sends CORS headers.");
                 imgElement.crossOrigin = "Anonymous";
                 // Re-setting src might be needed after setting crossOrigin if already requested by browser
                 // imgElement.src = imgElement.src; // Uncomment cautiously if needed
            }
        }
    }

    // --- Public API ---
    function init() {
        if (state.initialized) {
            logger.log(MODULE_NAME, "Already initialized.");
            return Promise.resolve();
        }
        logger.log(MODULE_NAME, "Initializing...");
        runExtraction();
        state.initialized = true;
        logger.log(MODULE_NAME, "Initialization sequence started.");
        return Promise.resolve();
    }

    function reinit() {
        logger.log(MODULE_NAME, "Re-initializing for new content...");
        // Reset activeImageSrc to ensure comparison works correctly if the element is the same but src might change
        // state.activeImageSrc = null; // Resetting might cause unnecessary re-extraction if src IS the same. Let runExtraction handle comparison.
        state.initialized = false;
        return init();
    }

    logger.log(MODULE_NAME, "Module loaded.");
    return {
        init: init,
        reinit: reinit,
        isInitialized: () => state.initialized
    };

})();