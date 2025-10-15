<div data-notification-modal class="hidden fixed inset-0 z-[80] flex items-center justify-center px-4 py-6 sm:px-6">
    <div class="absolute inset-0 glass-dark" data-notification-modal-close></div>
    <div class="relative w-full max-w-md rounded-2xl glass-strong shadow-2xl">
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400" data-notification-modal-type></p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900" data-notification-modal-title></h3>
                </div>
                <button type="button" class="ml-4 text-gray-400 hover:text-gray-600 focus:outline-none" data-notification-modal-close>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="mt-4 text-sm text-gray-600 leading-relaxed space-y-3" data-notification-modal-message></div>
            <div class="mt-4 flex items-center justify-between">
                <div class="text-xs text-gray-400" data-notification-modal-time></div>
                <div class="flex gap-2">
                    <button type="button" class="hidden rounded-md border border-copihue-200 px-3 py-1.5 text-xs font-medium text-copihue-600 hover:bg-copihue-50" data-notification-modal-action="mark">Marcar como leída</button>
                    <button type="button" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50" data-notification-modal-action="delete">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>
