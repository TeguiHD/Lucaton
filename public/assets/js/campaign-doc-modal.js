(() => {
  function initializeDocumentModal() {
    const docModal = document.querySelector('[data-doc-modal]');
    if (!docModal) return;

    const docFrame = docModal.querySelector('[data-doc-modal-frame]');
    const docFallback = docModal.querySelector('[data-doc-modal-fallback]');
    const footer = docModal.querySelector('[data-doc-modal-footer]');

    function applyDynamicHeight() {
      if (!docModal || !docFrame || !footer) return;
      const viewportHeight = window.innerHeight;
      const header = docModal.querySelector('[data-doc-modal-header]');
      const headerHeight = header ? header.offsetHeight : 0;
      const footerHeight = footer.offsetHeight;
      const maxContentHeight = Math.max(280, viewportHeight - headerHeight - footerHeight - 40);

      docFrame.style.minHeight = `${maxContentHeight}px`;
      docFrame.style.maxHeight = `${maxContentHeight}px`;
      docFrame.style.display = docFrame.classList.contains('hidden') ? 'none' : 'block';

      if (docFallback) {
        docFallback.style.minHeight = `${maxContentHeight}px`;
        docFallback.style.maxHeight = `${maxContentHeight}px`;
      }
    }

    window.addEventListener('resize', applyDynamicHeight);

    const observer = new MutationObserver(() => {
      applyDynamicHeight();
    });

    observer.observe(docModal, { attributes: true, attributeFilter: ['class'] });

    applyDynamicHeight();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDocumentModal);
  } else {
    initializeDocumentModal();
  }
})();
