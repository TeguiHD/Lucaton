Lucatón — Requerimientos (v1.8)

Estructura modular para facilitar el análisis por IDEs (TRAE/Claude) y el trabajo por fases. Cada archivo cubre un área específica del proyecto.

Archivos
- requerimientos/01-resumen-alcance.md — Resumen, cambios clave, alcance, fuera de alcance y opcionales.
- requerimientos/02-stack-y-configuracion.md — Stack (HTML/CSS/JS/PHP/MariaDB), Tailwind con build local (pnpm), CSP y variables .env.
- requerimientos/03-modelo-datos-y-sql.md — Modelo de datos y esquema SQL con índices y usuario de BD mínimo.
- requerimientos/04-requisitos-funcionales.md — Roles, funcionalidades y casos de uso principales.
- requerimientos/05-requisitos-no-funcionales-y-seguridad.md — Seguridad (OWASP, CSP, .htaccess, sesiones, CSRF), rendimiento, accesibilidad y mantenibilidad.
- requerimientos/06-plan-fases.md — Plan de desarrollo por 6 semanas (1.5 meses).
- requerimientos/07-ia-prompts-implementacion.md — Prompts de IA (texto/imagen) e implementación PHP (cURL) y endpoints.
- requerimientos/08-ui-guia.md — Guía UI/UX mobile‑first (layout, espaciado, accesibilidad).
- requerimientos/09-ui-paleta.md — Paleta de marca (Rojo Copihue, Azul Marino, Azul Pacífico, neutros y estados) y reglas de uso.
- requerimientos/10-ui-componentes.md — Partials PHP reutilizables (navbar, footer, card, botones, inputs, progreso, modal, alertas, tabla).
- requerimientos/11-ui-clases-tailwind.md — Clases Tailwind de componentes listas para copiar/pegar.
- requerimientos/12-endpoints.md — Endpoints del MVP (públicos, usuario, admin e IA).
- requerimientos/13-tailwind-pnpm.md — Tailwind con build local usando pnpm (config, safelist, build y CSP).
- requerimientos/14-env-plantilla.md — Plantilla de archivo .env.
- requerimientos/15-criterios-tesis.md — Criterios/entregables para aprobación como tesis (hipótesis, métricas, evaluación A/B, ética, etc.).
 - requerimientos/00-checklist-implementacion.md — Pasos ejecutables para implementar el MVP.
 - requerimientos/18-deploy-checklist.md — Checklist de despliegue (Apache/PHP).
 - requerimientos/19-paginas-mvp.md — Listado de páginas mínimas del MVP.
 - requerimientos/20-plan-semanal.md — Plan semanal con entregables.
- requerimientos/16-ai-extensiones-sugeridas.md — Propuestas de IA extra (moderación, alt‑text, tags, A/B, embeddings).
 - requerimientos/17-ia-seguridad-y-politicas.md — Políticas, agente global, flujo seguro, control de acceso y auditoría.

Notas
- Versión del documento: 1.8 (23/Sep/2025)
- Decisión clave: Tailwind con build local (pnpm) en producción para máximo control de diseño; CDN sólo opcional en desarrollo.
