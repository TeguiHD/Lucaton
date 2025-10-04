# Roadmap: Actualizaciones de campaña y reacciones

Este documento describe la arquitectura propuesta para habilitar publicaciones dentro de cada campaña y permitir reacciones tipo "me gusta".

## Objetivos funcionales
- Permitir que la persona creadora publique avances (texto + imágenes + adjuntos ligeros) desde su panel.
- Mostrar un timeline cronológico en la página pública de la campaña.
- Habilitar reacciones rápidas (corazón, aplausos, apoyo) de personas autenticadas.
- Registrar actividad para métricas y transparencia (quién publicó, cuándo, evidencia asociada).

## Diseño propuesto
1. **Tabla `campaign_updates`**
   - Campos sugeridos: `id`, `campaign_id`, `author_id`, `body`, `visibility`, `posted_at`, `created_at`, `updated_at`.
   - Índices por `campaign_id` y `posted_at` para paginación eficiente.
2. **Tabla `campaign_update_media`**
   - Almacena archivos asociados a cada update (imágenes comprimidas o PDFs ligeros).
   - Campos: `id`, `update_id`, `storage_path`, `media_type`, `metadata`.
3. **Tabla `campaign_update_reactions`**
   - Interacciones tipo reacción (corazón, aplausos, etc.).
   - Campos: `id`, `update_id`, `user_id`, `reaction_type`, `created_at`.
   - Índice compuesto único (`update_id`, `user_id`, `reaction_type`) para evitar duplicados.
4. **Servicio `CampaignUpdateService`**
   - Orquestará la creación de updates, gestión de archivos, paginación y eliminación segura.
5. **API y controladores**
   - Endpoints REST: listado (`GET /api/campaigns/{id}/updates`), creación (`POST`) y reacciones (`POST /reactions`).
   - Respuestas incluirán totales de reacciones pre-agregados.
6. **UI**
   - Timeline con tarjetas compactas, soporte para galería (carousel) y adjuntos descargables.
   - Botones de reacción con conteo en tiempo real mediante fetch ligero.

## Seguridad y privacidad
- Validar ownership antes de crear/editar updates.
- Utilizar storage privado para adjuntos que no deban ser públicos.
- Sanitizar HTML (se recomienda mantener solo texto plano + enlaces verificados).

## Próximos pasos
1. Definir migraciones según el esquema anterior.
2. Implementar `CampaignUpdateService` con los mismos patrones de `CampaignMediaUploadService`.
3. Extender el panel del creador con formulario para nuevas publicaciones.
4. Añadir componente React/Vue liviano o JS vanilla para reacciones (optimista + sincronización).

Este roadmap permite implementar las funcionalidades solicitadas de manera incremental, manteniendo la coherencia con los servicios de medios y las políticas de seguridad del proyecto.
