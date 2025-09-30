## Componentes Reutilizables (partials PHP en `includes/`)

- Layout base (`includes/layout.php`)
  - Head con `<link rel="/assets/app.css">`, `navbar`, `footer`, `<main>` contenedor.
- Navbar (`includes/navbar.php`)
  - Logo a `/`; links: Inicio, Crear campaña (si logueado), Panel; Admin si rol=admin.
  - Acciones: Ingresar/Registrar o menú usuario (logout). Menú móvil (hamburger).
- Footer (`includes/footer.php`)
  - Términos, Privacidad, Contacto; redes; copyright/año.
- Card de campaña (`includes/campaign-card.php`)
  - Imagen, título, extracto, meta y barra de progreso + CTA.
- Botones (`includes/button.php`)
  - Variantes: primario, secundario, peligro.
- Inputs con error (`includes/input.php`)
  - Label, input, help text, mensaje de error.
- Barra de progreso (`includes/progress.php`)
  - Contenedor y barra con %.
- Modal (`includes/modal.php`) y Alertas (`includes/alert.php`)
  - Modal accesible (overlay + foco); alertas success/warn/error.
- Tabla base (`includes/table.php`)
  - Cabecera sticky opcional; filas listradas; clases utilitarias.

Ver clases concretas en `requerimientos/11-ui-clases-tailwind.md` y guía general en `requerimientos/08-ui-guia.md`.
