<div data-notification-modal class="hidden fixed inset-0 z-[80] flex items-center justify-center px-4 py-6 sm:px-6">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" data-notification-modal-close></div>
    <div class="relative z-10 w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400" data-notification-modal-type></p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900" data-notification-modal-title></h3>
                </div>
                <button type="button" class="ml-4 text-gray-400 hover:text-gray-600 focus:outline-none" data-notification-modal-close>
                    <span class="sr-only">Cerrar</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-4 text-sm text-gray-600 leading-relaxed space-y-3" data-notification-modal-message></div>
            <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-xs text-gray-400" data-notification-modal-time></div>
                <div class="flex items-center gap-2">
                    <button type="button" class="hidden rounded-md border border-copihue-200 px-3 py-1.5 text-xs font-medium text-copihue-600 hover:bg-copihue-50" data-notification-modal-action="mark">Marcar como leída</button>
                    <button type="button" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50" data-notification-modal-action="delete">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>
