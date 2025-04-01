/**
 * Randomized Color Extractor for Spiral Tower
 * Implements a simple approach with random application of colors
 */
document.addEventListener('DOMContentLoaded', function() {
    const floorWrapper = document.querySelector('.spiral-tower-floor-wrapper');
    if (!floorWrapper) return;
    
    // Get custom colors from data attributes
    const body = document.body;
    const customColors = {
        titleColor: body.getAttribute('data-title-color'),
        titleBgColor: body.getAttribute('data-title-bg-color'),
        contentColor: body.getAttribute('data-content-color'),
        contentBgColor: body.getAttribute('data-content-bg-color'),
        floorNumberColor: body.getAttribute('data-floor-number-color')
    };
    
    // Check if any custom colors are set
    const hasAnyCustomColors = Object.values(customColors).some(color => color && color !== '');
    
    // Apply custom colors directly if they exist
    if (hasAnyCustomColors) {
        applyCustomColors(customColors);
    }
    
    // If ALL colors are set manually, don't run color extraction
    const allCustomColorsSet = Object.values(customColors).every(color => color && color !== '');
    if (allCustomColorsSet) {
        console.log('All colors are set manually');
        return;
    }

    // Get background image URL
    const style = getComputedStyle(floorWrapper);
    const bgImageValue = style.backgroundImage;
    
    const match = bgImageValue.match(/url\(['"]?(.*?)['"]?\)/);
    if (!match || !match[1]) return;
    
    const imageUrl = match[1];
    
    // Create an image object to load the background image
    const img = new Image();
    img.crossOrigin = "Anonymous";
    img.src = imageUrl;
    
    img.onload = function() {
        try {
            // Extract colors once the image is loaded
            const generatedColors = extractColors(img);
            
            // Apply colors, but respect manually set ones
            applyColorsWithCustomOverrides(generatedColors, customColors);
        } catch (e) {
            console.error("Error extracting colors:", e);
        }
    };
    
    img.onerror = function() {
        console.error("Error loading the background image");
    };
});

/**
 * Apply custom colors directly (when some or all are provided)
 */
function applyCustomColors(customColors) {
    const root = document.documentElement;
    
    if (customColors.titleColor) {
        root.style.setProperty('--title-text-color', customColors.titleColor);
    }
    
    if (customColors.titleBgColor) {
        root.style.setProperty('--title-bg-color', customColors.titleBgColor);
    }
    
    if (customColors.contentColor) {
        root.style.setProperty('--content-text-color', customColors.contentColor);
    }
    
    if (customColors.contentBgColor) {
        root.style.setProperty('--content-bg-color', customColors.contentBgColor);
    }
    
    if (customColors.floorNumberColor) {
        root.style.setProperty('--subtitle-color', customColors.floorNumberColor);
    }
    
    console.log('Applied custom colors:', customColors);
}

/**
 * Extract colors from image
 */
function extractColors(img) {
    // Create a canvas element
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // Size down for performance
    const size = 64;
    canvas.width = size;
    canvas.height = size;
    
    // Draw image on canvas
    ctx.drawImage(img, 0, 0, size, size);
    
    // Get image data
    const imageData = ctx.getImageData(0, 0, size, size);
    const data = imageData.data;
    
    // Arrays to hold different color categories
    const darkColors = [];
    const brightColors = [];
    const vividColors = [];
    
    // Process pixels
    for (let i = 0; i < data.length; i += 4) {
        const r = data[i];
        const g = data[i+1];
        const b = data[i+2];
        
        // Skip transparent pixels
        if (data[i+3] < 128) continue;
        
        // Calculate brightness and saturation
        const brightness = r + g + b;
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        const saturation = max === 0 ? 0 : (max - min) / max;
        
        // Categorize colors
        if (brightness < 350) {
            darkColors.push({r, g, b, brightness, saturation});
        } else {
            brightColors.push({r, g, b, brightness, saturation});
        }
        
        // Track vivid colors separately (high saturation)
        if (saturation > 0.3) {
            vividColors.push({r, g, b, brightness, saturation});
        }
    }
    
    // Sort all arrays by saturation to find most interesting colors
    vividColors.sort((a, b) => b.saturation - a.saturation);
    darkColors.sort((a, b) => b.saturation - a.saturation);
    brightColors.sort((a, b) => b.saturation - a.saturation);
    
    // Get a vibrant color
    let vividColor = vividColors.length > 0 ? vividColors[0] : 
                    (brightColors.length > 0 ? brightColors[0] : {r: 200, g: 200, b: 200});
    
    // Get corresponding dark and bright colors
    let darkColor, brightColor;
    
    if (vividColor.brightness < 350) {
        // Vivid color is dark, find a bright complement
        darkColor = vividColor;
        brightColor = brightColors.length > 0 ? brightColors[0] : {r: 220, g: 220, b: 220};
    } else {
        // Vivid color is bright, find a dark complement
        brightColor = vividColor;
        darkColor = darkColors.length > 0 ? darkColors[0] : {r: 50, g: 50, b: 50};
    }
    
    // Randomly decide whether to use light-on-dark or dark-on-light
    const useLightText = Math.random() < 0.5;
    
    // Set title colors
    const titleBgColor = useLightText ? darkColor : brightColor;
    const titleTextColor = useLightText ? brightColor : darkColor;
    
    // Set content colors (slightly different shade for interest)
    const contentBgColor = useLightText ? 
                          (darkColors.length > 1 ? darkColors[1] : {r: darkColor.r * 0.8, g: darkColor.g * 0.8, b: darkColor.b * 0.8}) : 
                          (brightColors.length > 1 ? brightColors[1] : {r: brightColor.r * 0.9, g: brightColor.g * 0.9, b: brightColor.b * 0.9});
    
    const contentTextColor = useLightText ? 
                            (brightColors.length > 1 ? brightColors[1] : {r: brightColor.r * 0.9, g: brightColor.g * 0.9, b: brightColor.b * 0.9}) : 
                            (darkColors.length > 1 ? darkColors[1] : {r: darkColor.r * 0.8, g: darkColor.g * 0.8, b: darkColor.b * 0.8});
    
    // Choose accent color (another color similar to text)
    const accentColor = useLightText ? 
                       (brightColors.length > 2 ? brightColors[2] : {r: Math.min(255, brightColor.r * 1.2), g: Math.min(255, brightColor.g * 1.2), b: Math.min(255, brightColor.b * 1.2)}) : 
                       (darkColors.length > 2 ? darkColors[2] : {r: Math.max(20, darkColor.r * 0.7), g: Math.max(20, darkColor.g * 0.7), b: Math.max(20, darkColor.b * 0.7)});
    
    // Ensure backgrounds are transparent
    return {
        bgColor: `rgba(${Math.round(darkColor.r * 0.8)}, ${Math.round(darkColor.g * 0.8)}, ${Math.round(darkColor.b * 0.8)}, 0.85)`,
        titleBgColor: `rgba(${titleBgColor.r}, ${titleBgColor.g}, ${titleBgColor.b}, 0.85)`,
        contentBgColor: `rgba(${contentBgColor.r}, ${contentBgColor.g}, ${contentBgColor.b}, 0.85)`,
        titleTextColor: `rgb(${titleTextColor.r}, ${titleTextColor.g}, ${titleTextColor.b})`,
        contentTextColor: `rgb(${contentTextColor.r}, ${contentTextColor.g}, ${contentTextColor.b})`,
        accentColor: `rgb(${accentColor.r}, ${accentColor.g}, ${accentColor.b})`
    };
}

/**
 * Apply colors, but respect manually set ones
 */
function applyColorsWithCustomOverrides(generatedColors, customColors) {
    const root = document.documentElement;
    
    // Set background colors
    root.style.setProperty('--background-color', generatedColors.bgColor.replace('0.85', '1'));
    
    // Apply title background color
    if (customColors.titleBgColor) {
        root.style.setProperty('--title-bg-color', customColors.titleBgColor);
    } else {
        root.style.setProperty('--title-bg-color', generatedColors.titleBgColor);
    }
    
    // Apply content background color
    if (customColors.contentBgColor) {
        root.style.setProperty('--content-bg-color', customColors.contentBgColor);
    } else {
        root.style.setProperty('--content-bg-color', generatedColors.contentBgColor);
    }
    
    // Apply title text color
    if (customColors.titleColor) {
        root.style.setProperty('--title-text-color', customColors.titleColor);
    } else {
        root.style.setProperty('--title-text-color', generatedColors.titleTextColor);
    }
    
    // Apply content text color
    if (customColors.contentColor) {
        root.style.setProperty('--content-text-color', customColors.contentColor);
    } else {
        root.style.setProperty('--content-text-color', generatedColors.contentTextColor);
    }
    
    // Apply floor number color
    if (customColors.floorNumberColor) {
        root.style.setProperty('--subtitle-color', customColors.floorNumberColor);
    } else {
        root.style.setProperty('--subtitle-color', generatedColors.accentColor);
    }
    
    // Only log if some colors were generated
    const anyGenerated = !customColors.titleColor || 
                         !customColors.titleBgColor || 
                         !customColors.contentColor || 
                         !customColors.contentBgColor || 
                         !customColors.floorNumberColor;
    
    if (anyGenerated) {
        console.log('Colors applied with custom overrides:', {
            generated: generatedColors,
            custom: customColors
        });
    }
}