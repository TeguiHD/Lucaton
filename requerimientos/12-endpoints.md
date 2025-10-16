## Endpoints MVP

Público
- GET `/` → Listado de campañas aprobadas (paginado)
- GET `/campana/{id}` → Detalle público + donadores recientes

Autenticación
- GET `/login`, `/registro` → Formularios
- POST `/login`, `/logout`, `/registro`

Usuario
- GET `/usuario/panel` → Mis campañas
- GET `/usuario/campana/nueva`
- POST `/usuario/campana` → Crear campaña
- POST `/usuario/campana/{id}/apelar`
- POST `/donar` (simulado; requiere login)

Admin
- GET `/admin` → Dashboard
- GET `/admin/campanas?estado=...`
- GET `/admin/campana/{id}`
- POST `/admin/campana/{id}/aprobar|rechazar|pausar|finalizar`
- POST `/admin/usuario/{id}/activar|desactivar|rol`

IA
- POST `/generate_text.php` → Generar texto IA (OpenRouter)
- POST `/generate_image.php` → Generar imagen IA (Gemini)
- POST `/ai/moderate_text.php` → Moderación ligera (opcional; integrado en generate_text)
- GET  `/file/ai/{id}` → Servir asset privado (sólo owner/admin)
- POST `/usuario/campana/{id}/adjuntar_ai/{generation_id}` → Adjuntar publicación de asset IA a campaña

Informativos / base de conocimiento
- GET `/faq` → Preguntas frecuentes (para alimentar chatbot)
- GET `/transparencia` → Política de transparencia y cómo usar la sección en campañas
- GET `/terminos` → Términos y condiciones
- GET `/privacidad` → Política de privacidad
