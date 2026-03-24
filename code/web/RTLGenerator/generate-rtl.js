#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

/**
 * Properties that need to be flipped for RTL
 */
const RTL_PROPERTIES = {
  'left': 'right',
  'right': 'left',
  'margin-left': 'margin-right',
  'margin-right': 'margin-left',
  'padding-left': 'padding-right',
  'padding-right': 'padding-left',
  'border-left': 'border-right',
  'border-right': 'border-left',
  'border-left-width': 'border-right-width',
  'border-right-width': 'border-left-width',
  'border-left-color': 'border-right-color',
  'border-right-color': 'border-left-color',
  'border-left-style': 'border-right-style',
  'border-right-style': 'border-left-style',
  'border-top-left-radius': 'border-top-right-radius',
  'border-top-right-radius': 'border-top-left-radius',
  'border-bottom-left-radius': 'border-bottom-right-radius',
  'border-bottom-right-radius': 'border-bottom-left-radius',
  'text-align': (value) => {
    if (value === 'left') return 'right';
    if (value === 'right') return 'left';
    return value;
  },
  'float': (value) => {
    if (value === 'left') return 'right';
    if (value === 'right') return 'left';
    return value;
  },
  'clear': (value) => {
    if (value === 'left') return 'right';
    if (value === 'right') return 'left';
    return value;
  },
  'content': (value) => {
    // Handle directional characters in content property
    return transformContentForRTL(value);
  }
};

/**
 * Properties that should be ignored (don't affect RTL layout)
 */
const IGNORE_PROPERTIES = new Set([
  'color', 'background-color', 'background', 'font-size', 'font-family',
  'font-weight', 'line-height', 'letter-spacing', 'text-decoration',
  'text-transform', 'vertical-align', 'top', 'bottom', 'width', 'height',
  'min-width', 'max-width', 'min-height', 'max-height', 'display',
  'position', 'z-index', 'opacity', 'visibility', 'overflow',
  'overflow-x', 'overflow-y', 'margin-top', 'margin-bottom',
  'padding-top', 'padding-bottom', 'border-top', 'border-bottom',
  'border-top-width', 'border-bottom-width', 'border-top-color',
  'border-bottom-color', 'border-top-style', 'border-bottom-style',
  'box-shadow', 'text-shadow', 'transition', 'transform-origin'
]);

/**
 * Font Awesome directional icons that need flipping for RTL
 * Each icon maps to its opposite direction counterpart
 */
const FONT_AWESOME_RTL_MAPPING = new Map([
  // Chevrons
  ['\\f053', '\\f054'], // fa-chevron-left -> fa-chevron-right
  ['\\f054', '\\f053'], // fa-chevron-right -> fa-chevron-left
  ['\\f104', '\\f105'], // fa-angle-left -> fa-angle-right
  ['\\f105', '\\f104'], // fa-angle-right -> fa-angle-left

  // Double chevrons
  ['\\f100', '\\f101'], // fa-angle-double-left -> fa-angle-double-right
  ['\\f101', '\\f100'], // fa-angle-double-right -> fa-angle-double-left

  // Arrows
  ['\\f060', '\\f061'], // fa-arrow-left -> fa-arrow-right
  ['\\f061', '\\f060'], // fa-arrow-right -> fa-arrow-left
  ['\\f177', '\\f178'], // fa-arrow-circle-left -> fa-arrow-circle-right
  ['\\f178', '\\f177'], // fa-arrow-circle-right -> fa-arrow-circle-left
  ['\\f0a8', '\\f0a9'], // fa-arrow-circle-o-left -> fa-arrow-circle-o-right
  ['\\f0a9', '\\f0a8'], // fa-arrow-circle-o-right -> fa-arrow-circle-o-left

  // Hand/pointer
  ['\\f0a4', '\\f0a5'], // fa-hand-o-left -> fa-hand-o-right
  ['\\f0a5', '\\f0a4'], // fa-hand-o-right -> fa-hand-o-left

  // Step/skip
  ['\\f048', '\\f051'], // fa-step-backward -> fa-step-forward
  ['\\f051', '\\f048'], // fa-step-forward -> fa-step-backward
  ['\\f04a', '\\f04e'], // fa-fast-backward -> fa-fast-forward
  ['\\f04e', '\\f04a'], // fa-fast-forward -> fa-fast-backward

  // Caret
  ['\\f0d9', '\\f0da'], // fa-caret-left -> fa-caret-right
  ['\\f0da', '\\f0d9'], // fa-caret-right -> fa-caret-left
  ['\\f150', '\\f152'], // fa-caret-square-o-left -> fa-caret-square-o-right
  ['\\f152', '\\f150'], // fa-caret-square-o-right -> fa-caret-square-o-left

  // Indent
  ['\\f03c', '\\f03b'], // fa-indent -> fa-outdent
  ['\\f03b', '\\f03c'], // fa-outdent -> fa-indent
]);

/**
 * Common directional characters that need flipping in content
 */
const DIRECTIONAL_CHARS = {
  '←': '→',
  '→': '←',
  '‹': '›',
  '›': '‹',
  '«': '»',
  '»': '«',
  '⟨': '⟩',
  '⟩': '⟨',
  '❮': '❯',
  '❯': '❮',
  '◀': '▶',
  '▶': '◀',
  '◄': '►',
  '►': '◄',
  '⇐': '⇒',
  '⇒': '⇐',
  '⇦': '⇨',
  '⇨': '⇦'
};

/**
 * Transform content property for RTL by flipping directional characters and Font Awesome icons
 */
function transformContentForRTL(value) {
  let transformedValue = value;

  // Handle quoted strings (both single and double quotes)
  if ((value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))) {
    const quote = value[0];
    const content = value.slice(1, -1);
    let newContent = content;

    // Replace Font Awesome icons
    for (const [ltr, rtl] of FONT_AWESOME_RTL_MAPPING) {
      // Handle both single and double backslash patterns
      const singleLtr = ltr.replace('\\\\', '\\');
      const singleRtl = rtl.replace('\\\\', '\\');

      // Check for exact match or pattern match
      if (newContent === singleLtr) {
        newContent = singleRtl;
        break; // Only one transformation per content
      }
    }

    // Replace directional characters
    for (const [ltr, rtl] of Object.entries(DIRECTIONAL_CHARS)) {
      newContent = newContent.replace(new RegExp(ltr.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), rtl);
    }

    transformedValue = quote + newContent + quote;
  }

  return transformedValue;
}

/**
 * Check if a selector contains ::before or ::after pseudo-elements
 */
function hasPseudoElements(selector) {
  return /::?(before|after)\b/.test(selector);
}

/**
 * Check if a selector or its declarations are RTL-relevant
 */
function isRTLRelevant(selector, declarations) {
  // Always include ::before and ::after pseudo-elements as they often contain directional content
  if (hasPseudoElements(selector)) {
    return true;
  }

  // Check if any declarations are RTL-relevant
  return declarations.length > 0;
}

/**
 * Parse CSS and extract only RTL-relevant rules
 */
function generateRTLCSS(cssContent) {
  const rtlRules = [];

  // Regular expression to match CSS rules
  const ruleRegex = /([^{}]+)\s*\{([^{}]*)\}/g;

  let match;
  while ((match = ruleRegex.exec(cssContent)) !== null) {
    const selector = match[1].trim();
    const declarations = match[2].trim();

    // Skip @import, @media, etc. for now - handle them separately
    if (selector.startsWith('@')) {
      continue;
    }


    const rtlDeclarations = [];

    // Parse individual declarations
    const declRegex = /([^:;]+):\s*([^:;!]+)(\s*!important)?(?:;|$)/g;
    let declMatch;

    while ((declMatch = declRegex.exec(declarations)) !== null) {
      const property = declMatch[1].trim();
      const value = declMatch[2].trim();
      const important = declMatch[3] ? declMatch[3].trim() : '';

      // Skip if property should be ignored
      if (IGNORE_PROPERTIES.has(property)) {
        continue;
      }

      // Check if property needs RTL transformation
      if (RTL_PROPERTIES[property]) {
        const transform = RTL_PROPERTIES[property];

        if (typeof transform === 'function') {
          const newValue = transform(value);
          if (newValue !== value) {
            const importantStr = important ? ` ${important}` : '';
            rtlDeclarations.push(`  ${property}: ${newValue}${importantStr}`);
          }
        } else {
          // Simple property name mapping
          const importantStr = important ? ` ${important}` : '';
          rtlDeclarations.push(`  ${transform}: ${value}${importantStr}`);
        }
      }

      // Handle margin and padding shorthand
      else if (property === 'margin' || property === 'padding') {
        const newValue = transformShorthandValue(value);
        if (newValue !== value) {
          const importantStr = important ? ` ${important}` : '';
          rtlDeclarations.push(`  ${property}: ${newValue}${importantStr}`);
        }
      }

      // Handle border-radius shorthand
      else if (property === 'border-radius') {
        const newValue = transformBorderRadius(value);
        if (newValue !== value) {
          const importantStr = important ? ` ${important}` : '';
          rtlDeclarations.push(`  ${property}: ${newValue}${importantStr}`);
        }
      }

      // Handle transform property
      else if (property === 'transform' && value.includes('translate')) {
        const newValue = transformTranslate(value);
        if (newValue !== value) {
          const importantStr = important ? ` ${important}` : '';
          rtlDeclarations.push(`  ${property}: ${newValue}${importantStr}`);
        }
      }
    }


    // Check if this rule is RTL-relevant (either has RTL declarations or is a pseudo-element)
    if (isRTLRelevant(selector, rtlDeclarations)) {
      // For pseudo-elements, include content property transformations even if no other RTL properties exist
      if (hasPseudoElements(selector) && rtlDeclarations.length === 0) {
        // Parse declarations again but look for content property specifically
        const declRegex = /([^:;]+):\s*([^:;!]+)(\s*!important)?(?:;|$)/g;
        let declMatch;

        while ((declMatch = declRegex.exec(declarations)) !== null) {
          const property = declMatch[1].trim();
          const value = declMatch[2].trim();
          const important = declMatch[3] ? declMatch[3].trim() : '';

          // Check content property for Font Awesome icons and directional characters
          if (property === 'content') {
            const transformedValue = transformContentForRTL(value);
            if (transformedValue !== value) {
              const importantStr = important ? ` ${important}` : '';
              rtlDeclarations.push(`  ${property}: ${transformedValue}${importantStr}`);
            }
          }
        }
      }

      // Add rule if it has any declarations
      if (rtlDeclarations.length > 0) {
        rtlRules.push(`${selector} {\n${rtlDeclarations.join(';\n')};\n}`);
      }
    }
  }


  // Handle @media queries
  const mediaQueries = extractMediaQueries(cssContent);
  rtlRules.push(...mediaQueries);

  return rtlRules.join('\n\n');
}

/**
 * Transform shorthand values like "10px 20px 30px 40px" to "10px 40px 30px 20px"
 */
function transformShorthandValue(value) {
  const parts = value.trim().split(/\s+/);

  if (parts.length === 4) {
    // top right bottom left -> top left bottom right
    return `${parts[0]} ${parts[3]} ${parts[2]} ${parts[1]}`;
  } else if (parts.length === 3) {
    // top horizontal bottom -> top bottom bottom horizontal
    return `${parts[0]} ${parts[1]} ${parts[2]} ${parts[1]}`;
  } else if (parts.length === 2) {
    // vertical horizontal -> vertical horizontal (no change needed)
    return value;
  }

  return value;
}

/**
 * Transform border-radius values
 */
function transformBorderRadius(value) {
  const parts = value.trim().split(/\s+/);

  if (parts.length === 4) {
    // top-left top-right bottom-right bottom-left -> top-right top-left bottom-left bottom-right
    return `${parts[1]} ${parts[0]} ${parts[3]} ${parts[2]}`;
  } else if (parts.length === 3) {
    // top-left top-right-and-bottom-left bottom-right -> top-right top-left-and-bottom-right bottom-left
    return `${parts[1]} ${parts[0]} ${parts[1]} ${parts[2]}`;
  } else if (parts.length === 2) {
    // top-left-and-bottom-right top-right-and-bottom-left -> top-right-and-bottom-left top-left-and-bottom-right
    return `${parts[1]} ${parts[0]}`;
  }

  return value;
}

/**
 * Transform translate values
 */
function transformTranslate(value) {
  return value.replace(/translateX\(([^)]+)\)/g, (match, x) => {
    if (x.startsWith('-')) {
      return `translateX(${x.substring(1)})`;
    } else if (x !== '0' && x !== '0px') {
      return `translateX(-${x})`;
    }
    return match;
  });
}

/**
 * Extract and process media queries
 */
function extractMediaQueries(cssContent) {
  const mediaRules = [];
  const mediaRegex = /@media[^{]+\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/g;

  let match;
  while ((match = mediaRegex.exec(cssContent)) !== null) {
    const mediaQuery = match[0];
    const mediaContent = match[1];

    const rtlContent = generateRTLCSS(mediaContent);
    if (rtlContent.trim()) {
      const mediaStart = mediaQuery.substring(0, mediaQuery.indexOf('{') + 1);
      mediaRules.push(`${mediaStart}\n${rtlContent}\n}`);
    }
  }

  return mediaRules;
}

/**
 * Main function to process CSS file
 */
function processCSS(inputPath, outputPath) {
  try {
    const cssContent = fs.readFileSync(inputPath, 'utf8');
    const rtlCSS = generateRTLCSS(cssContent);

    // Add RTL directive at the top
    const output = `/* Auto-generated RTL stylesheet from ${path.basename(inputPath)} */\n/* Only contains properties that differ for RTL layouts */\n\n${rtlCSS}`;

    fs.writeFileSync(outputPath, output);
    console.log(`✓ Generated RTL stylesheet: ${outputPath}`);
    return true;
  } catch (error) {
    console.error(`✗ Error processing ${inputPath}:`, error.message);
    return false;
  }
}

// Main execution
if (require.main === module) {
  const inputFile = process.argv[2];
  const outputFile = process.argv[3];

  if (!inputFile || !outputFile) {
    console.log('Usage: node generate-rtl.js <input-css-file> <output-rtl-css-file>');
    console.log('Example: node generate-rtl.js main.css main-rtl.css');
    process.exit(1);
  }

  if (!fs.existsSync(inputFile)) {
    console.error(`Input file does not exist: ${inputFile}`);
    process.exit(1);
  }

  const success = processCSS(inputFile, outputFile);
  process.exit(success ? 0 : 1);
}

module.exports = {processCSS, generateRTLCSS, transformContentForRTL};
