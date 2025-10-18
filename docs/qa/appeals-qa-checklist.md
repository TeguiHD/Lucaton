# QA Manual — Apelaciones y Archivos Adjuntos

Objetivo: validar que la migración `020_create_campaign_appeal_files_table.sql` funciona correctamente junto con los flujos de apelaciones, descargas y notificaciones asociadas.

## 1. Preparación del entorno
- Ejecutar `php database/migrations/run_migrations.php` y verificar que la tabla `campaign_appeal_files` exista junto al registro correspondiente en `migrations`.
- Confirmar que las variables de entorno (`DB_*`, `MAIL_*`) estén configuradas para un ambiente de pruebas y que el correo saliente apunte a un sandbox.
- Limpiar cachés/logs previos: `rm -f storage/logs/*.log` para facilitar la trazabilidad.

## 2. Adjuntar evidencia durante una apelación
- Crear o identificar una campaña en estado `rechazada`.
- Desde el panel de usuario, iniciar una apelación cargando al menos dos archivos (1 PDF y 1 imagen) y enviar el formulario.
- Revisar la tabla `campaign_appeal_files` y comprobar que:
  - Los campos `appeal_id`, `storage_path`, `original_name`, `mime_type`, `size_bytes` y `uploaded_by` contengan los valores esperados.
  - La marca de tiempo `created_at` refleje la hora del envío.
- Revisar `storage/private/` y confirmar que los archivos existen y respetan la convención de nombres definida por `CampaignAppealUploadService`.

## 3. Descarga y visibilidad para administradores
- Ingresar al panel administrador en la sección de apelaciones.
- Abrir la apelación creada y descargar cada archivo adjunto:
  - Verificar que la descarga obligue al login y respete permisos (403 si se intenta sin sesión).
  - Confirmar que el nombre del archivo descargado coincide con `original_name`.
- Revisar los logs (`storage/logs/laravel.log` o equivalente) y validar que se registre el evento de descarga.

## 4. Notificaciones y estados
- Cambiar el estado de la apelación a `under_review` y luego a `approved`:
  - Validar que el creador reciba correos de confirmación (usar bandeja de pruebas o mailtrap).
  - Confirmar que los registros aparezcan en `audit_logs` o la tabla equivalente con detalle del cambio.
- Repetir la prueba cambiando el estado a `rejected` y revisar que se incluya el motivo.
- Desde el panel de campaña, verificar que los estados e indicadores visuales se actualicen sin errores PHP.

## 5. Escenarios negativos
- Intentar subir un archivo con extensión no permitida y comprobar que la validación bloquea el proceso.
- Eliminar manualmente un archivo de `storage/private/` y cargar la vista admin para asegurarse de que maneje el faltante (mensaje de error y log de advertencia).
- Simular un usuario no autenticado intentando acceder al enlace de descarga y confirmar redirección o error controlado.

## 6. Documentación
- Registrar evidencias (capturas) en `capturasPantalla/` y actualizar el informe de QA con resultados y hallazgos.
- Si se detecta cualquier anomalía, abrir un ticket en la herramienta interna indicando pasos para reproducir y logs relacionados.
