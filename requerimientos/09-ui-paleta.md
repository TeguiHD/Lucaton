## Paleta de Colores — Lucatón: Corazón de Chile

Propósito: chilenidad, optimismo, solidaridad; guía digna y transparente.

### Colores de Marca
- Rojo Copihue `#D7263D` — CTA principal, urgentes.
- Azul Marino `#0E375A` — Títulos, texto, navegación, modo oscuro.
- Azul Pacífico `#0B63CE` — Links, progreso, foco, bordes secundarios.

### Neutros
- Grafito `#1F2937` — Texto general en fondo claro.
- Gris 700 `#374151` — Texto secundario, divisores, bordes.
- Gris 400 `#9CA3AF` — Placeholder, deshabilitados.
- Nube `#F3F4F6` — Fondos claros y tarjetas.
- Blanco `#FFFFFF` — Fondos principales.

### Estados
- Éxito `#10B981` — Confirmaciones y progreso positivo.
- Advertencia `#F59E0B` — Alertas no críticas.
- Error `#DC2626` — Validaciones y acciones destructivas.

### Reglas de uso
- Accesibilidad: contrastes WCAG AA en combinaciones clave.
- Armonía: máximo dos colores de marca por vista; neutros predominan.
- Consistencia: cada color cumple un propósito (CTA, info, estados).
- Modo oscuro: recomendado si el tiempo alcanza (usar Azul Marino de base).

### Variables CSS sugeridas
```
:root {
  --brand-primary: #D7263D;  /* Rojo Copihue */
  --brand-navy:    #0E375A;  /* Azul Marino */
  --brand-link:    #0B63CE;  /* Azul Pacífico */
  --neutral-900:   #1F2937;  /* Grafito */
  --neutral-700:   #374151;  /* Gris 700 */
  --neutral-400:   #9CA3AF;  /* Gris 400 */
  --neutral-100:   #F3F4F6;  /* Nube */
  --white:         #FFFFFF;
  --success:       #10B981;
  --warning:       #F59E0B;
  --error:         #DC2626;
}
```
