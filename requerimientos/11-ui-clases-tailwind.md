## Clases Tailwind de Componentes (copiar/pegar)

Tipografía
- Título: `text-2xl sm:text-3xl font-semibold text-[#0E375A]`
- Subtítulo: `text-lg sm:text-xl font-medium text-[#374151]`
- Texto: `text-[15px] leading-6 text-[#1F2937]`
- Link: `text-[#0B63CE] hover:underline`

Botones
- Primario (CTA): `inline-flex items-center gap-2 rounded-lg bg-[#D7263D] px-4 py-2 text-white shadow-sm hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0B63CE] disabled:opacity-60`
- Secundario: `inline-flex items-center gap-2 rounded-lg border border-[#0B63CE] text-[#0B63CE] px-4 py-2 bg-white hover:bg-[#0B63CE]/5 focus:outline-none focus:ring-2 focus:ring-[#0B63CE]`
- Peligro: `inline-flex items-center gap-2 rounded-lg bg-[#DC2626] px-4 py-2 text-white hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-[#DC2626]/60`
- Pequeño: `px-3 py-1.5 text-sm`

Navbar
- Contenedor: `sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-[#F3F4F6]`
- Interno: `max-w-5xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between text-[#0E375A]`
- Link: `text-sm font-medium text-[#0E375A] hover:text-[#0B63CE]`
- Activo: `text-[#0B63CE]`
- Menú móvil: `sm:hidden inline-flex items-center p-2 rounded-md text-[#0E375A] hover:bg-[#F3F4F6]`

Cards
- Card: `rounded-xl border border-[#F3F4F6] bg-white shadow-sm p-4 sm:p-6`
- Título card: `text-base font-semibold text-[#0E375A]`
- Meta info: `text-sm text-[#374151]`

Formularios
- Label: `block text-sm font-medium text-[#0E375A]`
- Input/Select: `block w-full rounded-lg border border-[#D1D5DB] focus:border-[#0B63CE] focus:ring-[#0B63CE] text-[#1F2937] placeholder-[#9CA3AF]`
- Textarea: `block w-full rounded-lg border border-[#D1D5DB] focus:border-[#0B63CE] focus:ring-[#0B63CE] text-[#1F2937] placeholder-[#9CA3AF] min-h-[120px]`
- Help text: `mt-1 text-xs text-[#374151]`
- Error text: `mt-1 text-xs text-[#DC2626]`

Alertas
- Éxito: `rounded-lg border-l-4 border-[#10B981] bg-[#10B981]/10 text-[#0E375A] p-3`
- Advertencia: `rounded-lg border-l-4 border-[#F59E0B] bg-[#F59E0B]/10 text-[#0E375A] p-3`
- Error: `rounded-lg border-l-4 border-[#DC2626] bg-[#DC2626]/10 text-[#0E375A] p-3`

Progreso de campaña
- Contenedor: `h-2 w-full rounded-full bg-[#F3F4F6]`
- Barra: `h-2 rounded-full bg-[#0B63CE]` + `style="width: NN%"`

Badges de estado
- Base: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border`
- Aprobada: `bg-[#10B981]/10 border-[#10B981]/30 text-[#0E375A]`
- Pendiente: `bg-[#0B63CE]/10 border-[#0B63CE]/30 text-[#0E375A]`
- Rechazada: `bg-[#DC2626]/10 border-[#DC2626]/30 text-[#0E375A]`
- Pausada: `bg-[#9CA3AF]/10 border-[#9CA3AF]/30 text-[#0E375A]`

Modal
- Overlay: `fixed inset-0 z-50 bg-black/50`
- Contenedor: `fixed inset-x-4 top-[10%] sm:inset-0 sm:flex sm:items-center sm:justify-center`
- Panel: `mx-auto w-full max-w-lg rounded-xl bg-white p-4 sm:p-6 shadow-lg`

Tabla base (admin)
- Wrapper: `overflow-x-auto`
- Tabla: `min-w-full divide-y divide-[#F3F4F6] text-sm`
- Thead: `bg-[#F9FAFB] text-[#374151]`
- Th/td: `px-4 py-3 text-left`

Contenedores
- Página: `max-w-5xl mx-auto px-4 sm:px-6`
- Sección: `py-6 sm:py-8`
