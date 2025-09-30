const fs = require('fs');
const path = require('path');

const cssPath = path.join(__dirname, '..', 'public', 'assets', 'css', 'app.css');

try {
  let css = fs.readFileSync(cssPath, 'utf8');
  const originalLength = css.length;

  css = css
    .replace(/-webkit-text-size-adjust:\s*100%;?/g, '')
    .replace(/-moz-osx-font-smoothing:\s*grayscale;?/g, '');

  if (css.length !== originalLength) {
    fs.writeFileSync(cssPath, css, 'utf8');
    console.log('✓ Limpieza aplicada a app.css');
  } else {
    console.log('ℹ️  No se encontraron ajustes de limpieza pendientes en app.css');
  }
} catch (error) {
  console.error('No fue posible limpiar app.css:', error.message);
  process.exitCode = 1;
}
