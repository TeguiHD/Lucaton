## Resumen del Proyecto
"Lucatón" es una plataforma web de crowdfunding social para causas urgentes. Incorpora asistencia por IA (texto e imagen) para crear campañas de alto impacto. Roles: Usuario (crea/gestiona campañas) y Administrador (modera/gestiona la plataforma).

### Justificación de la IA (propósito social y ético)
- Mitigar brecha digital: acompañar a personas con baja alfabetización digital para que logren campañas completas y comprensibles.
- Persuasión ética: ayudar a comunicar con dignidad, claridad y empatía, evitando exageraciones/manipulaciones.
- Transparencia y confianza: guiar al usuario para declarar uso de fondos, plazos, evidencias y contacto; etiquetar contenido generado por IA.
- Accesibilidad: mejorar texto e imagen (incluyendo alt‑text) dentro de la plataforma, sin depender de herramientas externas.
- Acompañamiento: chatbot/ayudante para resolver dudas de creación, proceso, pagos (explicativo) y políticas de transparencia.

### Cambios clave (v1.8)
- IA segura desde servidor (OpenAI texto, Gemini imagen) con PHP cURL.
- Tailwind con build local (pnpm) en producción para control de diseño y CSP sólida; CDN opcional sólo en desarrollo.
- Mobile‑First obligatorio; componentes reutilizables (partials PHP).
- Seguridad reforzada: CSP, headers, .htaccess, CSRF, sesiones seguras, rate limiting.
- BD con usuario de mínimos privilegios y tablas con utf8mb4 + índices.

## Alcance (Incluido)
- Registro/login/logout con roles (Usuario, Administrador).
- Perfil de usuario (nombre y redes sociales).
- Creación/edición de campañas con IA (texto/imagen) y evidencias.
- Sección de transparencia en la campaña (uso de fondos, plazos, evidencias, contacto) con sugerencias IA.
- Moderación admin (Aprobar, Rechazar con motivo, Pausar, Finalizar) y apelaciones.
- Listado/Detalle público de campañas aprobadas; progreso y donadores simulados.
- Panel Usuario (mis campañas) y Panel Admin (campañas/usuarios/apelaciones).
- Auditoría de cambios de estado.

## Fuera de Alcance (Trabajo futuro)
- Pasarela de pago real (donaciones simuladas en MVP).
- Email (notificaciones), timeline social avanzado, analíticas avanzadas.
- Publicación directa en redes sociales.
- Moderación automática compleja por IA.
- Chatbot N8N como iframe (widget) e IA adicional.

## Opcionales (si hay tiempo)
- Composer (dotenv/guzzle/monolog/uuid).
- CDN de Tailwind para prototipos rápidos en desarrollo.
- Rate limiting persistente en BD (tabla ai_rate_limit) vs. sesión.
- Cache de listados (archivo/BD) y ETag/Last‑Modified.
- Colas simples para IA (tabla ai_jobs).
- Email y pasarela de pago real (Webpay/Stripe/PayPal con webhooks).
- Chatbot N8N embebido para guía paso a paso y FAQ (ajustar `frame-src` en CSP).
