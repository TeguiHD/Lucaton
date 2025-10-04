# Pendientes para el despliegue con dominio propio

## Correo transaccional
- [ ] Configurar credenciales `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` y probar conectividad SMTP.
- [ ] Sustituir la generación de previews (`storage/logs/mail-previews`) por envíos reales usando la librería elegida (PHPMailer, Symfony Mailer u otra) dentro de `CampaignLifecycleMailer`.
- [ ] Incorporar plantillas HTML accesibles para campañas (creada, aprobada, rechazada, meta alcanzada, porcentaje clave) y pruebas de rendering en clientes comunes.
- [ ] Añadir logs y manejo de reintentos/colas si el envío falla para no perder notificaciones críticas.

## Integraciones IA y contenidos
- [ ] Conectar el `CampaignLifecycleMailer` con el proveedor de IA (OpenAI/Gemini) para generar los incentivos y publicaciones usando los prompts incluidos en el payload.
- [ ] Exponer los resultados de IA en el panel del creador de campaña o adjuntarlos al correo de notificación.
- [ ] Definir límites diarios de generación y auditoría de uso (registro en `storage/logs`).

## Moderación y UX
- [ ] Validar el modal de rechazo en distintos navegadores y pantallas; añadir focus trap y cierre con tecla Esc.
- [ ] Registrar en `CampaignLifecycleMailer` la URL de apelación cuando se implemente el flujo definitivo de apelaciones.

## Métricas y simulaciones
- [ ] Reemplazar las donaciones simuladas por pasarela real o sandbox oficial; asegurar que `CampaignMilestoneNotifier` recibe los hooks correctos.
- [ ] Programar pruebas automáticas para los hitos de notificación (unitarias + integración) y revisar cobertura antes de producción.
