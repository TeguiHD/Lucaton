# Esquema de Datos Lucatón

Esta guía resume la estructura vigente de la base de datos tras la revisión de febrero 2025. La finalidad es documentar las entidades clave, sus campos y relaciones para facilitar mantenimiento, integraciones y futuras migraciones.

## Tablas principales

### `users`
Campos destacados: `id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `status`, `email_verified_at`, `failed_login_attempts`, `locked_until`.

- **Rol** controla autorización (`user`, `admin`).
- **Status** (`active`, `pending_verification`, `suspended`, etc.) habilita o deshabilita flujos.
- Índices en `email`, `username`, `status`, `role` garantizan búsquedas eficientes.

### `campaigns`
Campos: `id`, `user_id`, `title`, `slug`, `short_description`, `description`, `goal_amount`, `current_amount`, `currency`, `category`, `status`, `featured_image_url`, `video_url`, `location`, `beneficiary_type`, `beneficiary_name`, `beneficiary_contact`, `ai_generated`, `featured`, `published_at`, `end_date`.

- FK `user_id → users.id` (ON DELETE CASCADE).
- Estados posibles: `draft`, `under_review`, `published`, `completed`, `rejected`, `cancelled`.
- Índices en `status`, `category`, `featured`, `slug`, `end_date`.
- `short_description` reduce coste de listados y tarjetas.

### `donations`
Campos: `id`, `campaign_id`, `user_id`, `donor_name`, `donor_email`, `amount`, `currency`, `message`, `is_anonymous`, `payment_method`, `transaction_id`, `status`, `donor_ip`, `created_at`.

- FK `campaign_id → campaigns.id`.
- Estados: `pending`, `processing`, `completed`, `failed`, `refunded`.
- Registro de IP y método de pago permiten trazabilidad y auditoría.

### `campaign_evidence`
Gestiona las evidencias documentales de una campaña (`type`, `file_url`, `description`, `ai_generated`).

### `campaign_appeals`
Soporta el flujo de apelaciones tras revisión (`motivo`, `estado`, `respuesta_admin`).

### Tablas de auditoría e IA
- `audit_log`: guarda cambios de estado y acciones sensibles.
- `ai_generations`: prompts y respuestas de asistencias IA textual/visual.
- `ai_policy_logs`: tracea validaciones y bloqueos por políticas éticas.
- `campaign_tags` y `embeddings`: soporte para categorización inteligente y búsquedas semánticas.

## Relaciones relevantes

```
users (1) ─── (n) campaigns
campaigns (1) ─── (n) donations
campaigns (1) ─── (n) campaign_evidence
campaigns (1) ─── (n) campaign_appeals
users (1) ─── (n) ai_generations
campaigns (1) ─── (n) campaign_tags
```

> Todas las relaciones usan claves foráneas con `ON DELETE CASCADE`, simplificando limpieza al eliminar usuarios o campañas completas.

## Normalización y buenas prácticas

- Cada entidad core se mantiene en su propia tabla, evitando duplicación de datos (3FN).
- Catálogos (`category`, `beneficiary_type`, `status`) se controlan vía ENUM y validaciones en capa de aplicación.
- Campos de auditoría (`created_at`, `updated_at`) están presentes en tablas críticas.
- Índices definidos en columnas de filtrado (`status`, `category`, `slug`, `end_date`, `email`) optimizan las consultas usadas en listados y buscadores.
- `current_amount` puede recalcularse a partir de donaciones completadas; se conserva para lecturas rápidas y se mantiene vía triggers/lógica de aplicación.

## Consultas clave revisadas

- **Panel principal**: sumatorias de `donations` completadas y conteo de campañas activas (ver `HomeController::fetchImpactStats`).
- **Buscador de campañas**: filtrado por `status`, `category` y búsqueda por texto (`title`, `short_description`) parametrizada en `CampaignController` utilizando `LIKE` parametrizado.
- **Categorías destacadas**: agregaciones (`COUNT`, `SUM`) en `campaigns` sin datos redundantes, sólo lectura.

## Recomendaciones futuras

1. Implementar vistas materializadas o caché para métricas históricas si el volumen de donaciones crece significativamente.
2. Añadir tabla `campaign_updates` para publicaciones periódicas de los creadores, reforzando transparencia.
3. Documentar en `docs/api/` los endpoints REST actuales y sus parámetros de filtrado, asegurando coherencia con esta capa de datos.

Con esta estructura mantenemos coherencia entre backend, frontend y métricas, facilitando nuevas funcionalidades sin comprometer integridad.
