## Requisitos Funcionales

### Rol: Usuario
- Autenticación: registro/login/logout (password_hash Argon2id recomendado; verify en login).
- Perfil: ver/editar nombre y redes.
- Campañas: crear/editar borrador; usar IA (texto/imagen); adjuntar evidencias; enviar a revisión; ver estado y motivos.
- Donaciones simuladas: donar, actualizar progreso y ver donadores recientes.
- Apelaciones: enviar y ver estado.

### Rol: Administrador
- Dashboard con métricas básicas.
- Campañas: listar/filtrar; ver detalle/evidencias; Aprobar/Rechazar (con motivo)/Pausar/Finalizar; editar fecha_fin; auditoría automática.
- Usuarios: listar/editar, cambiar rol, activar/desactivar.
- Apelaciones: ver, responder, marcar como resuelta.

## Casos de Uso
- CU01 Crear Campaña con Asistencia de IA
  - Reglas: imagen obligatoria (propia o IA), fecha_fin > fecha_inicio, meta_monetaria > 0.
- CU02 Modera Campaña Pendiente (Admin)
- CU03 Apelar Campaña Rechazada (Usuario)
- CU04 Donar (Simulado)
