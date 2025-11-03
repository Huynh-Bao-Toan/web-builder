const fs = require('fs');
const path = require('path');

// Ensure bundle directory exists
if (!fs.existsSync('bundle')) {
  fs.mkdirSync('bundle');
}

// Minify CSS: Remove comments, trim whitespace
function minifyCSS(content) {
  return content
    .replace(/\/\*[\s\S]*?\*\//g, '') // Remove comments
    .replace(/\s+/g, ' ') // Collapse whitespace
    .replace(/\s*{\s*/g, '{')
    .replace(/\s*}\s*/g, '}')
    .replace(/\s*:\s*/g, ':')
    .replace(/\s*;\s*/g, ';')
    .replace(/\s*,\s*/g, ',')
    .trim();
}

// Minify HTML: Remove comments, trim whitespace
function minifyHTML(content) {
  return content
    .replace(/<!--[\s\S]*?-->/g, '') // Remove comments
    .replace(/\s+/g, ' ') // Collapse whitespace
    .replace(/>\s+</g, '><') // Remove space between tags
    .trim();
}

// Minify JS: Basic minification (remove comments, trim whitespace)
function minifyJS(content) {
  return content
    .replace(/\/\*\*[\s\S]*?\*\//g, '') // Remove block comments
    .replace(/\/\/.*$/gm, '') // Remove line comments
    .replace(/\s+/g, ' ') // Collapse whitespace
    .replace(/\s*{\s*/g, '{')
    .replace(/\s*}\s*/g, '}')
    .replace(/\s*;\s*/g, ';')
    .replace(/\s*,\s*/g, ',')
    .replace(/\s*\(\s*/g, '(')
    .replace(/\s*\)\s*/g, ')')
    .trim();
}

// Read and minify files
const cssContent = fs.readFileSync('output/landing-page.css', 'utf8');
const htmlContent = fs.readFileSync('output/landing-page.html', 'utf8');
const jsContent = fs.readFileSync('output/landing-page.js', 'utf8');

const minifiedCSS = minifyCSS(cssContent);
const minifiedHTML = minifyHTML(htmlContent);
const minifiedJS = minifyJS(jsContent);

// Write minified files
fs.writeFileSync('bundle/landing-page.css.min.txt', minifiedCSS);
fs.writeFileSync('bundle/landing-page.html.min.txt', minifiedHTML);
fs.writeFileSync('bundle/landing-page.js.min.txt', minifiedJS);

console.log('Minified files created:');
console.log(`- CSS: ${minifiedCSS.length} chars`);
console.log(`- HTML: ${minifiedHTML.length} chars`);
console.log(`- JS: ${minifiedJS.length} chars`);

// Create bundle JSON for database structure
const bundle = {
  version: '1.0.0',
  created_at: new Date().toISOString(),
  manifest: {
    css: {
      filename: 'landing-page.css.min.txt',
      size: minifiedCSS.length,
      hash: require('crypto').createHash('md5').update(minifiedCSS).digest('hex')
    },
    html: {
      filename: 'landing-page.html.min.txt',
      size: minifiedHTML.length,
      hash: require('crypto').createHash('md5').update(minifiedHTML).digest('hex')
    },
    js: {
      filename: 'landing-page.js.min.txt',
      size: minifiedJS.length,
      hash: require('crypto').createHash('md5').update(minifiedJS).digest('hex')
    }
  },
  assets: {
    css: minifiedCSS,
    html: minifiedHTML,
    js: minifiedJS
  }
};

fs.writeFileSync('bundle/bundle.json', JSON.stringify(bundle, null, 2));

console.log('\nBundle JSON created: bundle/bundle.json');

