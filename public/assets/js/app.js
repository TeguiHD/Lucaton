// Simple UI interactions without Alpine
// Derive base path from script src (supports subfolder like /Tesis)
(function deriveBase() {
  try {
    var script = document.querySelector('script[src*="/public/assets/js/app.js"]');
    if (script) {
      var url = new URL(script.src);
      window.APP_BASE_PATH = url.pathname.replace(/\/public\/assets\/js\/app\.js.*$/, '');
    }
  } catch (err) {
    // ignore
  }
})();

function initLucatonUI() {
  var state = window.__lucatonUI || (window.__lucatonUI = { pairs: [], listenersBound: false });
  var typeColors = {
    info: 'bg-blue-100 text-blue-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-yellow-100 text-yellow-700',
    error: 'bg-red-100 text-red-700',
    system: 'bg-purple-100 text-purple-700'
  };

  function getOrRegisterPair(button) {
    if (!button) {
      return null;
    }

    var existing = state.pairs.find(function (pair) {
      return pair.button === button;
    });
    if (existing) {
      return existing;
    }

    var target = button.getAttribute('data-toggle');
    if (!target) {
      return null;
    }

    var menu = findMenuFor(button, target);
    if (!menu) {
      return null;
    }

    if (!button.hasAttribute('aria-expanded')) {
      button.setAttribute('aria-expanded', 'false');
    }

    var pair = { button: button, menu: menu, target: target };
    state.pairs.push(pair);
    return pair;
  }

  function findMenuFor(button, target) {
    if (!target) {
      return null;
    }

    var within = button.parentElement;
    while (within) {
      if (within.querySelector) {
        var scoped = within.querySelector('[data-menu="' + target + '"]');
        if (scoped) {
          return scoped;
        }
      }
      within = within.parentElement;
    }

    return document.querySelector('[data-menu="' + target + '"]') || document.getElementById(target);
  }

  function toggleIcons(button, isOpen) {
    if (!button) {
      return;
    }

    var openIcon = button.querySelector('[data-toggle-icon="open"]');
    var closeIcon = button.querySelector('[data-toggle-icon="close"]');

    if (openIcon) {
      openIcon.classList.toggle('hidden', isOpen);
    }
    if (closeIcon) {
      closeIcon.classList.toggle('hidden', !isOpen);
    }
  }

  function anyMenuOpen() {
    return state.pairs.some(function (pair) {
      return !pair.menu.classList.contains('hidden');
    });
  }

  function updateStickyLock(pair, isOpen) {
    var header = pair && pair.button ? pair.button.closest('[data-sticky-header]') : null;
    if (!header) {
      return;
    }

    if (isOpen) {
      header.setAttribute('data-sticky-locked', 'true');
      header.classList.remove('-translate-y-full');
    } else if (!anyMenuOpen()) {
      header.removeAttribute('data-sticky-locked');
    }
  }

  function triggerMenuAnimation(menu) {
    if (!menu || !menu.classList || !menu.classList.contains('mobile-menu-panel')) {
      return;
    }

    menu.classList.remove('is-opening');
    // force reflow to allow re-triggering the animation
    void menu.offsetWidth;

    menu.classList.add('is-opening');
    menu.addEventListener('animationend', function handleAnimationEnd() {
      menu.classList.remove('is-opening');
      menu.removeEventListener('animationend', handleAnimationEnd);
    }, { once: true });
  }

  function setToggleState(pair, isOpen) {
    if (!pair) {
      return;
    }

    if (isOpen) {
      pair.menu.classList.remove('hidden');
      triggerMenuAnimation(pair.menu);
      pair.button.setAttribute('aria-expanded', 'true');
    } else {
      pair.menu.classList.add('hidden');
      if (pair.menu && pair.menu.classList) {
        pair.menu.classList.remove('is-opening');
      }
      pair.button.setAttribute('aria-expanded', 'false');
    }

    toggleIcons(pair.button, isOpen);
    updateStickyLock(pair, isOpen);
  }

  function closeAll(exceptButton) {
    state.pairs.forEach(function (pair) {
      if (exceptButton && pair.button === exceptButton) {
        return;
      }
      setToggleState(pair, false);
    });
  }

  function loadNotifications(button, menu) {
    if (button.getAttribute('data-loading') === 'true') {
      return;
    }

    var endpoint = button.getAttribute('data-endpoint');
    if (!endpoint) {
      return;
    }

    var spinner = menu.querySelector('[data-notification-spinner]');
    var list = menu.querySelector('[data-notification-list]');
    var emptyState = menu.querySelector('[data-notification-empty]');
    var errorState = menu.querySelector('[data-notification-error]');

    if (spinner) {
      spinner.classList.remove('hidden');
    }
    if (list) {
      list.classList.add('hidden');
    }
    if (emptyState) {
      emptyState.classList.add('hidden');
    }
    if (errorState) {
      errorState.classList.add('hidden');
    }

    button.setAttribute('data-loading', 'true');

    fetch(endpoint, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        if (spinner) {
          spinner.classList.add('hidden');
        }

        if (!data || data.success !== true) {
          showNotificationError(errorState, 'No se pudieron cargar las notificaciones.');
          updateBadge(button, data && typeof data.unread === 'number' ? data.unread : 0);
          return;
        }

        var notifications = Array.isArray(data.notifications) ? data.notifications : [];
        var unreadIds = renderNotifications(menu, notifications, typeColors);
        updateBadge(button, data.unread || 0);

        if (notifications.length === 0 && emptyState) {
          emptyState.classList.remove('hidden');
        }

        if (unreadIds.length > 0) {
          markNotificationsAsRead(button, unreadIds);
        }
      })
      .catch(function () {
        if (spinner) {
          spinner.classList.add('hidden');
        }
        showNotificationError(errorState, 'Error de conexión. Inténtalo nuevamente.');
      })
      .finally(function () {
        button.setAttribute('data-loading', 'false');
      });
  }

  function showNotificationError(container, message) {
    if (!container) {
      return;
    }
    container.textContent = message;
    container.classList.remove('hidden');
  }

  function renderNotifications(menu, notifications, typeColorsMap) {
    var list = menu.querySelector('[data-notification-list]');
    if (!list) {
      return [];
    }

    list.innerHTML = '';
    list.classList.remove('hidden');

    var unreadIds = [];

    notifications.forEach(function (notification) {
      var item = document.createElement('li');
      item.className = 'px-4 py-3 hover:bg-gray-50 transition-colors duration-150';

      var typeKey = typeof notification.type === 'string' ? notification.type : 'info';
      var badgeClass = typeColorsMap[typeKey] || 'bg-gray-100 text-gray-700';
      var unreadDot = !notification.is_read ? '<span class="ml-2 inline-flex h-2 w-2 rounded-full bg-copihue-500"></span>' : '';

      if (!notification.is_read) {
        unreadIds.push(notification.id);
      }

      var safeTitle = escapeHtml(notification.title || '');
      var safeMessage = escapeHtml(notification.message || '');
      var displayMessage = safeMessage.replace(/\n/g, '<br>');
      var timeAgo = escapeHtml(notification.time_ago || '');
      var typeLabel = escapeHtml(typeKey.toUpperCase());

      item.innerHTML =
        '<div class="flex items-start justify-between">' +
        '  <div class="pr-4">' +
        '    <p class="text-sm font-semibold text-gray-900">' + safeTitle +
        '      <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' + badgeClass + '">' +
        typeLabel +
        '      </span>' +
        '    </p>' +
        '    <p class="mt-1 text-sm text-gray-600">' + displayMessage + '</p>' +
        '    <p class="mt-1 text-xs text-gray-400">' + timeAgo + '</p>' +
        '  </div>' +
        '  ' + unreadDot +
        '</div>';

      list.appendChild(item);
    });

    return unreadIds;
  }

  function markNotificationsAsRead(button, ids) {
    if (!Array.isArray(ids) || ids.length === 0) {
      return;
    }

    var endpoint = button.getAttribute('data-read-endpoint');
    var csrfName = button.getAttribute('data-csrf-name');
    var csrfValue = button.getAttribute('data-csrf-value');

    if (!endpoint || !csrfName || !csrfValue) {
      return;
    }

    var formData = new FormData();
    formData.append(csrfName, csrfValue);
    ids.forEach(function (id) {
      formData.append('ids[]', id);
    });

    fetch(endpoint, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (response) {
        return response.json().catch(function () {
          return null;
        });
      })
      .then(function (data) {
        if (data && typeof data.unread === 'number') {
          updateBadge(button, data.unread);
        }
      })
      .catch(function () {
        // silencioso
      });
  }

  function updateBadge(button, unread) {
    var badge = button.querySelector('[data-notification-count]');
    if (!badge) {
      return;
    }

    if (unread && unread > 0) {
      badge.textContent = unread > 9 ? '9+' : String(unread);
      badge.classList.remove('hidden');
    } else {
      badge.textContent = '';
      badge.classList.add('hidden');
    }
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function handleToggle(button) {
    var pair = getOrRegisterPair(button);
    if (!pair) {
      return;
    }

    var willOpen = pair.menu.classList.contains('hidden');

    closeAll(button);

    if (willOpen && button.hasAttribute('data-notification-trigger')) {
      loadNotifications(button, pair.menu);
    }

    setToggleState(pair, willOpen);
  }

  function bindGlobalListeners() {
    if (state.listenersBound) {
      return;
    }

    document.addEventListener('click', function (event) {
      var toggleButton = event.target.closest('[data-toggle]');
      if (toggleButton) {
        event.preventDefault();
        event.stopPropagation();
        handleToggle(toggleButton);
        return;
      }

      if (!event.target.closest('[data-menu]')) {
        closeAll();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' || event.key === 'Esc') {
        closeAll();
      }
    });

    state.listenersBound = true;
  }

  function primeExistingToggles() {
    var toggles = document.querySelectorAll('[data-toggle]');
    for (var i = 0; i < toggles.length; i++) {
      var pair = getOrRegisterPair(toggles[i]);
      if (pair) {
        var isOpen = !pair.menu.classList.contains('hidden');
        setToggleState(pair, isOpen);
      }
    }
  }

  primeExistingToggles();
  bindGlobalListeners();
  state.closeAll = closeAll;
  state.anyMenuOpen = anyMenuOpen;
}

function initPasswordVisibility(context) {
  var root = context || document;
  var buttons = root.querySelectorAll('[data-password-toggle]');

  buttons.forEach(function (button) {
    if (button.__passwordToggleInitialized) {
      return;
    }
    button.__passwordToggleInitialized = true;

    var selector = button.getAttribute('data-password-toggle');
    var input = null;

    if (selector) {
      try {
        input = root.querySelector(selector) || document.querySelector(selector);
      } catch (err) {
        input = null;
      }
    }

    if (!input) {
      var wrapper = button.closest('[data-password-wrapper]') || button.parentElement;
      if (wrapper) {
        input = wrapper.querySelector('input[type="password"], input[data-password-input]');
      }
    }

    if (!input) {
      return;
    }

    var hiddenLabel = button.getAttribute('data-password-label-hidden') || 'Mostrar contraseña';
    var visibleLabel = button.getAttribute('data-password-label-visible') || 'Ocultar contraseña';
    var hiddenIcon = button.querySelector('[data-password-icon="hidden"]');
    var visibleIcon = button.querySelector('[data-password-icon="visible"]');

    function applyState(isVisible) {
      input.setAttribute('type', isVisible ? 'text' : 'password');
      button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
      button.setAttribute('aria-label', isVisible ? visibleLabel : hiddenLabel);
      button.setAttribute('title', isVisible ? visibleLabel : hiddenLabel);
      if (hiddenIcon) {
        hiddenIcon.classList.toggle('hidden', isVisible);
      }
      if (visibleIcon) {
        visibleIcon.classList.toggle('hidden', !isVisible);
      }
    }

    var initialVisible = input.getAttribute('type') === 'text';
    applyState(initialVisible);

    button.addEventListener('click', function (event) {
      event.preventDefault();
      var nextVisible = input.getAttribute('type') === 'password';
      applyState(nextVisible);
      try {
        input.focus({ preventScroll: true });
      } catch (err) {
        input.focus();
      }
    });
  });
}

function initStickyHeader() {
  var headers = document.querySelectorAll('[data-sticky-header]');
  if (!headers.length) {
    return;
  }

  var lastScroll = window.scrollY || 0;
  var ticking = false;
  var hideOffset = 80;
  var deltaThreshold = 6;

  function update() {
    var current = window.scrollY || 0;
    var delta = current - lastScroll;
    var goingDown = delta > 0;

    headers.forEach(function (header) {
      if (header.hasAttribute('data-sticky-locked')) {
        header.classList.remove('-translate-y-full');
        return;
      }

      if (current <= 8) {
        header.classList.remove('-translate-y-full');
        return;
      }

      if (goingDown && current > hideOffset && Math.abs(delta) > deltaThreshold) {
        header.classList.add('-translate-y-full');
      } else if (!goingDown && Math.abs(delta) > deltaThreshold) {
        header.classList.remove('-translate-y-full');
      }
    });

    lastScroll = current;
  }

  window.addEventListener(
    'scroll',
    function () {
      if (!ticking) {
        window.requestAnimationFrame(function () {
          update();
          ticking = false;
        });
        ticking = true;
      }
    },
    { passive: true }
  );

  update();
}

function bootstrapLucaton() {
  initLucatonUI();
  initStickyHeader();
  initPasswordVisibility();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrapLucaton, { once: true });
} else {
  bootstrapLucaton();
}

// Utility: share campaign (graceful fallback)
function shareCampaign(slug) {
  var path = '/campana/' + encodeURIComponent(slug || 'detalle');
  var url = window.location.origin + (window.APP_BASE_PATH || '') + path;
  if (navigator.share) {
    navigator.share({ title: 'Lucatón', text: 'Mira esta campaña', url: url }).catch(function () {});
  } else {
    try {
      navigator.clipboard.writeText(url);
      alert('Enlace copiado al portapapeles');
    } catch (err) {
      prompt('Copia el enlace:', url);
    }
  }
}

// Utility: toggle favorite using localStorage (demo)
function toggleFavorite(id) {
  var key = 'lucaton_favs';
  var favs = new Set(JSON.parse(localStorage.getItem(key) || '[]'));
  if (favs.has(String(id))) {
    favs.delete(String(id));
  } else {
    favs.add(String(id));
  }
  localStorage.setItem(key, JSON.stringify(Array.from(favs)));
}
