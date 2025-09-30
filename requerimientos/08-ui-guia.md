## Guía de Diseño UI/UX (Mobile‑First)

- Base móvil (360–400px) primero; mejora progresiva con `sm:` y `md:`.
- Layout: contenedor `max-w-5xl mx-auto px-4 sm:px-6`.
- Espaciado: `p-4/p-6`, `gap-4/gap-6` consistente.
- Tipografía: system-ui, sans-serif (sin fuentes externas, para rapidez).
- Accesibilidad: contraste WCAG AA, focus visible, labels y `aria-*` en formularios, blancos de toque ≥ 40px.
- CTA principal en Rojo Copihue; navegación en Azul Marino; links/estados en Azul Pacífico.
- Estados vacíos con mensajes claros y CTA visibles.

Ver colores en `requerimientos/09-ui-paleta.md` y clases en `requerimientos/11-ui-clases-tailwind.md`.
