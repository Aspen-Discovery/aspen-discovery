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
    const declRegex = /([^:;]+):\s*([^:;]+)(?:;|$)/g;
    let declMatch;

    while ((declMatch = declRegex.exec(declarations)) !== null) {
      const property = declMatch[1].trim();
      const value = declMatch[2].trim();

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
            rtlDeclarations.push(`  ${property}: ${newValue}`);
          }
        } else {
          // Simple property name mapping
          rtlDeclarations.push(`  ${transform}: ${value}`);
        }
      }

      // Handle margin and padding shorthand
      else if (property === 'margin' || property === 'padding') {
        const newValue = transformShorthandValue(value);
        if (newValue !== value) {
          rtlDeclarations.push(`  ${property}: ${newValue}`);
        }
      }

      // Handle border-radius shorthand
      else if (property === 'border-radius') {
        const newValue = transformBorderRadius(value);
        if (newValue !== value) {
          rtlDeclarations.push(`  ${property}: ${newValue}`);
        }
      }

      // Handle transform property
      else if (property === 'transform' && value.includes('translate')) {
        const newValue = transformTranslate(value);
        if (newValue !== value) {
          rtlDeclarations.push(`  ${property}: ${newValue}`);
        }
      }
    }

    // Add rule if it has RTL-relevant declarations
    if (rtlDeclarations.length > 0) {
      rtlRules.push(`${selector} {\n${rtlDeclarations.join(';\n')};\n}`);
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

module.exports = { processCSS, generateRTLCSS };
