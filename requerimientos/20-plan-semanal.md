## Plan semanal (5–6 semanas)

Semana 1 — Autenticación y base
- Estructura de carpetas, `.env`, bootstrap (sesiones, timezone).
- BD y usuario mínimo; seed de admin inicial (SQL simple o script).
- Páginas auth (login/registro/logout) y layout + navbar/footer.
- CSP y .htaccess; subida de imagen con validación básica.

Semana 2 — Campañas (CRUD mínimo) y panel usuario
- Modelo campanas/evidencias; formulario crear campaña (sin IA aún).
- Panel “Mis campañas”; ver detalle de campaña (privado) y estados.
- Donaciones simuladas y barra de progreso.

Semana 3 — IA Texto/Imagen (server‑side)
- Endpoints `generate_text.php` y `generate_image.php` con cURL y rate‑limit.
- Botones en formulario; previsualización; guardar generados en privado y registro en `ai_generations`.
- Agente global y pre‑chequeo de políticas; manejo de “DENEGADO”.

Semana 4 — Admin y publicación
- Listado/Moderación admin: aprobar/rechazar/pausar/finalizar (auditoría).
- Adjuntar IA a campaña y publicar copia de imagen a público.
- Páginas `/faq` y `/transparencia`; alt‑text opcional automático.

Semana 5 — Seguridad/UX/Pruebas
- CSRF, rate‑limit, control de acceso a `/file/ai/{id}`.
- Optimización de imágenes (resize/compress) y validaciones extras.
- Accesibilidad (contraste/focus/labels) y SUS/UEQ corto.

Semana 6 — Evaluación y cierre (si hay 6 semanas)
- Ejecución A/B (con vs sin IA) con 10–15 usuarios.
- Análisis de métricas y documentación final (arquitectura, endpoints, prompts, resultados).
