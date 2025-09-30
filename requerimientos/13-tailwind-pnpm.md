## Tailwind con build local (pnpm)

Objetivo: compilar CSS estático con la paleta de marca para control total, mejor rendimiento y CSP sólida.

Requisitos
- Node.js 18+ y pnpm instalado.

Instalación (dev)
```
pnpm add -D tailwindcss postcss autoprefixer
pnpm exec tailwindcss init -p
```

tailwind.config.js (content, paleta y safelist)
```js
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./**/*.php','./**/*.html','./**/*.js'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: { primary: '#D7263D', navy: '#0E375A', link: '#0B63CE' },
        neutralx: { 900:'#1F2937',700:'#374151',400:'#9CA3AF',100:'#F3F4F6' },
        success:'#10B981', warning:'#F59E0B', error:'#DC2626',
      }
    }
  },
  safelist: [
    'text-brand-navy','bg-brand-primary','text-brand-link',
    'focus:border-brand-link','focus:ring-brand-link',
    'bg-success','bg-warning','bg-error',
    'border-success','border-warning','border-error',
    'text-success','text-warning','text-error',
    { pattern: /^(bg|text|border)-(brand-(primary|navy|link)|neutralx-(900|700|400|100)|success|warning|error)(\/\d+)?$/ },
    { pattern: /^ring-(brand-(primary|link)|success|warning|error)$/ , variants: ['focus'] },
  ],
  plugins: []
}
```

CSS de entrada
```
@tailwind base;
@tailwind components;
@tailwind utilities;
```

Compilar
```
# Dev (watch)
pnpm exec tailwindcss -i assets/input.css -o public/assets/app.css --watch
# Prod (min)
pnpm exec tailwindcss -i assets/input.css -o public/assets/app.css --minify
```

HTML y CSP
- HTML: `<link rel="stylesheet" href="/assets/app.css">`
- `.htaccess` (producción, sin CDN): ver `requerimientos/05-requisitos-no-funcionales-y-seguridad.md`
