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
    initializeNotificationTrigger(pair);
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


  var modalCache = null;
  var modalContext = null;

  function getNotificationState(button) {
    if (!button) {
      return null;
    }

    if (!button.__notificationState) {
      var limitAttr = parseInt(button.getAttribute('data-limit') || '10', 10);
      var limit = isNaN(limitAttr) ? 10 : Math.min(Math.max(limitAttr, 1), 50);
      button.__notificationState = {
        limit: limit,
        offset: 0,
        hasMore: false,
        notifications: [],
        loading: false,
        loaded: false,
        lastFetchedAt: 0,
        index: {}
      };
    }

    return button.__notificationState;
  }

  function buildUrl(base, params) {
    var url = base || '';
    if (!params) {
      return url;
    }

    var parts = [];
    Object.keys(params).forEach(function (key) {
      var value = params[key];
      if (value === undefined || value === null) {
        return;
      }
      parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
    });

    if (parts.length === 0) {
      return url;
    }

    return url + (url.indexOf('?') === -1 ? '?' : '&') + parts.join('&');
  }

  function getNotificationElements(menu) {
    if (!menu) {
      return {};
    }

    return {
      spinner: menu.querySelector('[data-notification-spinner]'),
      list: menu.querySelector('[data-notification-list]'),
      empty: menu.querySelector('[data-notification-empty]'),
      error: menu.querySelector('[data-notification-error]'),
      loadMore: menu.querySelector('[data-notification-action="load-more"]'),
      scroll: menu.querySelector('[data-notification-scroll]')
    };
  }

  function toggleEmptyState(element, show) {
    if (!element) {
      return;
    }
    element.classList.toggle('hidden', !show);
  }

  function clearNotificationError(element) {
    if (!element) {
      return;
    }
    element.textContent = '';
    element.classList.add('hidden');
  }

  function updateLoadMoreButton(button, hasMore) {
    if (!button) {
      return;
    }
    button.classList.toggle('hidden', !hasMore);
    button.disabled = !hasMore;
  }

  function findPairByMenu(menu) {
    for (var i = 0; i < state.pairs.length; i++) {
      if (state.pairs[i].menu === menu) {
        return state.pairs[i];
      }
    }
    return null;
  }

  function sanitizeUrl(url) {
    if (!url) {
      return null;
    }

    try {
      var base = window.location.origin || document.baseURI || '';
      var normalized = new URL(url, base);
      if (normalized.protocol === 'http:' || normalized.protocol === 'https:') {
        return normalized.href;
      }
    } catch (err) {
      return null;
    }

    return null;
  }

  function formatNotificationMessage(notification) {
    var meta = notification && typeof notification.meta === 'object' ? notification.meta : null;
    var safeMessage = escapeHtml(notification.message || '').replace(/\n/g, '<br>');
    var chunks = [safeMessage];

    if (meta && meta.news_article && meta.news_article.title) {
      var safeNewsTitle = escapeHtml(meta.news_article.title);
      var safeSummary = '';

      if (meta.news_article.summary) {
        safeSummary = escapeHtml(meta.news_article.summary).replace(/\n/g, '<br>');
      }

      var newsParts = [
        '<div class="pt-3 text-sm leading-relaxed" data-notification-news>',
        '  <p class="text-xs font-semibold uppercase tracking-wide text-copihue-600">Noticia vinculada</p>',
        '  <p class="mt-1 font-medium text-gray-900">' + safeNewsTitle + '</p>'
      ];

      if (safeSummary) {
        newsParts.push('  <p class="mt-1 text-sm text-gray-600">' + safeSummary + '</p>');
      }

      newsParts.push('</div>');
      chunks.push(newsParts.join(''));
    }

    if (meta && meta.url) {
      var safeUrl = sanitizeUrl(meta.url);
      if (safeUrl) {
        var linkLabel = 'Abrir enlace relacionado';
        if (meta.link_label && typeof meta.link_label === 'string' && meta.link_label.trim() !== '') {
          linkLabel = meta.link_label.trim();
        } else if (meta.news_article && meta.news_article.title) {
          linkLabel = 'Leer noticia completa';
        }

        var safeLabel = escapeHtml(linkLabel);
        var linkParts = [
          '<p class="pt-3">',
          '  <a href="' + safeUrl + '" class="inline-flex items-center gap-1 text-copihue-600 hover:text-copihue-700 font-medium" target="_blank" rel="noopener">',
          safeLabel,
          '    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">',
          '      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />',
          '    </svg>',
          '  </a>',
          '</p>'
        ];
        chunks.push(linkParts.join(''));
      }
    }

    return chunks.join('');
  }

  function ensureNotificationModal() {
    if (modalCache !== null) {
      return modalCache;
    }

    var root = document.querySelector('[data-notification-modal]');
    if (!root) {
      modalCache = null;
      return null;
    }

    modalCache = {
      root: root,
      title: root.querySelector('[data-notification-modal-title]'),
      type: root.querySelector('[data-notification-modal-type]'),
      message: root.querySelector('[data-notification-modal-message]'),
      time: root.querySelector('[data-notification-modal-time]'),
      markButton: root.querySelector('[data-notification-modal-action="mark"]'),
      deleteButton: root.querySelector('[data-notification-modal-action="delete"]'),
      closeButtons: root.querySelectorAll('[data-notification-modal-close]')
    };

    if (modalCache.closeButtons) {
      modalCache.closeButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
          event.preventDefault();
          hideNotificationModal();
        });
      });
    }

    root.addEventListener('click', function (event) {
      if (event.target === root) {
        hideNotificationModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' || event.key === 'Esc') {
        hideNotificationModal();
      }
    });

    if (modalCache.markButton) {
      modalCache.markButton.addEventListener('click', function (event) {
        event.preventDefault();
        if (!modalContext || !modalContext.button) {
          return;
        }
        var id = modalContext.notificationId;
        markNotificationsAsRead(modalContext.button, [id], {
          menu: modalContext.menu,
          forceRender: true
        }).finally(function () {
          hideNotificationModal();
        });
      });
    }

    if (modalCache.deleteButton) {
      modalCache.deleteButton.addEventListener('click', function (event) {
        event.preventDefault();
        if (!modalContext || !modalContext.button) {
          return;
        }
        var id = modalContext.notificationId;
        deleteNotifications(modalContext.button, modalContext.menu, [id]).finally(function () {
          hideNotificationModal();
        });
      });
    }

    return modalCache;
  }

  function showNotificationModal(notification, button, menu) {
    var modal = ensureNotificationModal();
    if (!modal || !modal.root) {
      return;
    }

    modalContext = {
      button: button,
      menu: menu,
      notificationId: notification.id
    };

    if (modal.title) {
      modal.title.textContent = notification.title || 'Notificación';
    }
    if (modal.type) {
      modal.type.textContent = (notification.type || 'info').toUpperCase();
    }
    if (modal.message) {
      modal.message.innerHTML = formatNotificationMessage(notification);
    }
    if (modal.time) {
      modal.time.textContent = notification.time_ago || '';
    }
    if (modal.markButton) {
      if (notification.is_read) {
        modal.markButton.classList.add('hidden');
      } else {
        modal.markButton.classList.remove('hidden');
      }
    }

    modal.root.classList.remove('hidden');
    modal.root.setAttribute('data-current-id', String(notification.id));
    document.body.classList.add('overflow-hidden');
  }

  function hideNotificationModal() {
    if (!modalCache || !modalCache.root) {
      return;
    }

    modalCache.root.classList.add('hidden');
    modalCache.root.removeAttribute('data-current-id');
    document.body.classList.remove('overflow-hidden');
    modalContext = null;
  }

  function openNotificationDetail(button, menu, notificationId) {
    var stateObj = getNotificationState(button);
    if (!stateObj) {
      return;
    }

    var notification = stateObj.index && stateObj.index[notificationId];
    if (!notification) {
      for (var i = 0; i < stateObj.notifications.length; i++) {
        if (stateObj.notifications[i].id === notificationId) {
          notification = stateObj.notifications[i];
          break;
        }
      }
    }

    if (!notification) {
      return;
    }

    var pair = findPairByMenu(menu);
    if (pair) {
      setToggleState(pair, false);
    }

    showNotificationModal(notification, button, menu);

    if (!notification.is_read) {
      markNotificationsAsRead(button, [notificationId], {
        menu: menu,
        state: stateObj,
        forceRender: true
      });
    }
  }

  function refreshNotificationSummary(button) {
    var summaryEndpoint = button.getAttribute('data-summary-endpoint');
    if (!summaryEndpoint) {
      return;
    }

    fetch(summaryEndpoint, {
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
        if (data && typeof data.unread === 'number') {
          updateBadge(button, data.unread);
        }
      })
      .catch(function () {
        // silencioso
      });
  }

  function scheduleNotificationSummary(button) {
    if (!button) {
      return;
    }

    refreshNotificationSummary(button);

    var intervalAttr = parseInt(button.getAttribute('data-refresh-interval') || '0', 10);
    var interval = isNaN(intervalAttr) ? 0 : intervalAttr;

    if (interval <= 0) {
      return;
    }

    if (button.__notificationSummaryTimer) {
      return;
    }

    button.__notificationSummaryTimer = window.setInterval(function () {
      if (document.hidden) {
        return;
      }
      refreshNotificationSummary(button);
    }, interval);
  }

  function handleNotificationAction(element, event) {
    var action = element.getAttribute('data-notification-action');
    if (!action) {
      return;
    }

    var menu = element.closest('[data-menu]');
    if (!menu) {
      return;
    }

    var pair = findPairByMenu(menu);
    if (!pair) {
      return;
    }

    var button = pair.button;
    var stateObj = getNotificationState(button);

    event.preventDefault();
    event.stopPropagation();

    if (action === 'refresh') {
      loadNotifications(button, menu, { force: true, reset: true });
      return;
    }

    if (action === 'mark-all') {
      markNotificationsAsRead(button, [], { menu: menu, state: stateObj, forceRender: true });
      return;
    }

    if (action === 'load-more') {
      loadNotifications(button, menu, { append: true });
      return;
    }

    var idAttr = element.getAttribute('data-notification-id');
    var notificationId = parseInt(idAttr || '0', 10);

    if (!notificationId) {
      return;
    }

    if (action === 'open') {
      openNotificationDetail(button, menu, notificationId);
      return;
    }

    if (action === 'delete') {
      deleteNotifications(button, menu, [notificationId]);
    }
  }

  function markNotificationsAsRead(button, ids, options) {
    options = options || {};
    var stateObj = options.state || getNotificationState(button);
    var menu = options.menu;

    var endpoint = button.getAttribute('data-read-endpoint');
    var csrfName = button.getAttribute('data-csrf-name');
    var csrfValue = button.getAttribute('data-csrf-value');

    if (!endpoint || !csrfName || !csrfValue) {
      return Promise.resolve(null);
    }

    var formData = new FormData();
    formData.append(csrfName, csrfValue);

    if (Array.isArray(ids) && ids.length > 0) {
      ids.forEach(function (id) {
        formData.append('ids[]', id);
      });
    }

    return fetch(endpoint, {
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

        if (!stateObj) {
          return data;
        }

        var idsToMark;
        if (Array.isArray(ids) && ids.length > 0) {
          idsToMark = ids.map(function (id) { return parseInt(id, 10); });
        } else {
          idsToMark = stateObj.notifications.map(function (item) { return item.id; });
        }

        var readAt = new Date().toISOString();

        stateObj.notifications.forEach(function (item) {
          if (idsToMark.indexOf(item.id) !== -1) {
            item.is_read = true;
            if (!item.read_at) {
              item.read_at = readAt;
            }
          }
        });

        if (menu && options.forceRender !== false) {
          renderNotifications(button, menu, stateObj, typeColors);
        }

        return data;
      })
      .catch(function () {
        return null;
      });
  }

  function deleteNotifications(button, menu, ids) {
    if (!Array.isArray(ids) || ids.length === 0) {
      return Promise.resolve(null);
    }

    var endpoint = button.getAttribute('data-delete-endpoint');
    var csrfName = button.getAttribute('data-csrf-name');
    var csrfValue = button.getAttribute('data-csrf-value');

    if (!endpoint || !csrfName || !csrfValue) {
      return Promise.resolve(null);
    }

    var elements = getNotificationElements(menu);
    clearNotificationError(elements.error);

    var formData = new FormData();
    formData.append(csrfName, csrfValue);
    ids.forEach(function (id) {
      formData.append('ids[]', id);
    });

    return fetch(endpoint, {
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
        return loadNotifications(button, menu, { force: true, reset: true });
      })
      .catch(function () {
        showNotificationError(elements.error, 'No se pudo eliminar la notificación.');
        return null;
      });
  }

  function loadNotifications(button, menu, options) {
    options = options || {};
    var stateObj = getNotificationState(button);
    if (!stateObj) {
      return Promise.resolve(null);
    }

    if (stateObj.loading && !options.force) {
      return Promise.resolve(null);
    }

    var endpoint = button.getAttribute('data-endpoint');
    if (!endpoint) {
      return Promise.resolve(null);
    }

    var elements = getNotificationElements(menu);
    if (!options.silent && elements.spinner) {
      elements.spinner.classList.remove('hidden');
    }
    if (!options.silent && elements.list) {
      elements.list.classList.add('hidden');
    }
    if (!options.silent) {
      toggleEmptyState(elements.empty, false);
      clearNotificationError(elements.error);
    }

    stateObj.loading = true;
    button.setAttribute('data-loading', 'true');

    var params = {
      limit: stateObj.limit,
      offset: options.append ? stateObj.offset : 0
    };

    if (options.reset) {
      params.offset = 0;
    }

    return fetch(buildUrl(endpoint, params), {
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
        if (!data || data.success !== true) {
          showNotificationError(elements.error, 'No se pudieron cargar las notificaciones.');
          updateBadge(button, data && typeof data.unread === 'number' ? data.unread : 0);
          return data;
        }

        var items = Array.isArray(data.notifications) ? data.notifications : [];

        if (options.append && stateObj.notifications.length > 0) {
          stateObj.notifications = stateObj.notifications.concat(items);
        } else {
          stateObj.notifications = items;
          if (elements.scroll) {
            elements.scroll.scrollTop = 0;
          }
        }

        if (typeof data.next_offset === 'number') {
          stateObj.offset = data.next_offset;
        } else if (options.append) {
          stateObj.offset = stateObj.notifications.length;
        } else {
          stateObj.offset = items.length;
        }

        stateObj.hasMore = !!data.has_more;
        stateObj.lastFetchedAt = Date.now();
        stateObj.loaded = true;

        renderNotifications(button, menu, stateObj, typeColors);
        updateLoadMoreButton(elements.loadMore, stateObj.hasMore);
        toggleEmptyState(elements.empty, stateObj.notifications.length === 0);
        updateBadge(button, data.unread || 0);
        return data;
      })
      .catch(function () {
        showNotificationError(elements.error, 'Error de conexión. Inténtalo nuevamente.');
        return null;
      })
      .finally(function () {
        stateObj.loading = false;
        button.setAttribute('data-loading', 'false');
        if (elements.spinner) {
          elements.spinner.classList.add('hidden');
        }
      });
  }

  function showNotificationError(container, message) {
    if (!container) {
      return;
    }
    container.textContent = message;
    container.classList.remove('hidden');
  }

  function renderNotifications(button, menu, stateObj, typeColorsMap) {
    var elements = getNotificationElements(menu);
    var list = elements.list;
    if (!list) {
      return;
    }

    list.innerHTML = '';
    var notifications = stateObj && Array.isArray(stateObj.notifications) ? stateObj.notifications : [];
    stateObj.index = {};

    if (notifications.length === 0) {
      list.classList.add('hidden');
      return;
    }

    list.classList.remove('hidden');

    notifications.forEach(function (notification) {
      stateObj.index[notification.id] = notification;

      var unread = !notification.is_read;
      var item = document.createElement('li');
      item.className = 'group relative px-4 py-3 transition-colors duration-150' + (unread ? ' bg-copihue-50' : '');
      item.setAttribute('data-notification-item', '');
      item.setAttribute('data-notification-id', notification.id);
      item.setAttribute('data-notification-read', unread ? '0' : '1');

      var typeKey = typeof notification.type === 'string' ? notification.type : 'info';
      var badgeClass = typeColorsMap[typeKey] || 'bg-gray-100 text-gray-700';
      var safeTitle = escapeHtml(notification.title || '');
      var messageHtml = formatNotificationMessage(notification);
      var timeAgo = escapeHtml(notification.time_ago || '');
      var typeLabel = escapeHtml(typeKey.toUpperCase());
      var dotClass = unread ? 'bg-copihue-500' : 'bg-gray-300';

      item.innerHTML =
        '<div class="flex items-start gap-3">' +
        '  <button type="button" class="flex-1 text-left" data-notification-action="open" data-notification-id="' + notification.id + '">' +
        '    <div class="flex items-start gap-3">' +
        '      <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full ' + dotClass + '"></span>' +
        '      <div>' +
        '        <p class="text-sm font-semibold text-gray-900 flex items-center gap-2">' + safeTitle +
        '          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' + badgeClass + '">' + typeLabel + '</span>' +
        '        </p>' +
        '        <div class="mt-1 text-sm text-gray-600 leading-snug">' + messageHtml + '</div>' +
        '        <p class="mt-1 text-xs text-gray-400">' + timeAgo + '</p>' +
        '      </div>' +
        '    </div>' +
        '  </button>' +
        '  <button type="button" class="ml-2 shrink-0 text-gray-300 hover:text-red-500 focus:outline-none" data-notification-action="delete" data-notification-id="' + notification.id + '">' +
        '    <span class="sr-only">Eliminar</span>' +
        '    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">' +
        '      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />' +
        '    </svg>' +
        '  </button>' +
        '</div>';

      list.appendChild(item);
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

    if (typeof button.__hasSeenUnread === 'undefined') {
      button.__hasSeenUnread = false;
    }

    var previous = typeof button.__previousUnread === 'number' ? button.__previousUnread : null;

    if (!button.__hasSeenUnread) {
      button.__hasSeenUnread = true;
      button.__previousUnread = unread;
      return;
    }

    if (previous !== null && unread > previous) {
      announceNotificationArrival(button, unread - previous, unread);
    }

    button.__previousUnread = unread;
  }

  function announceNotificationArrival(button, delta, totalUnread) {
    if (!button || delta <= 0) {
      return;
    }

    var now = Date.now();
    if (typeof button.__notificationAnnounceAt === 'number' && now - button.__notificationAnnounceAt < 4000) {
      button.__notificationAnnounceAt = now;
      highlightNotificationTrigger(button);
      return;
    }

    button.__notificationAnnounceAt = now;
    highlightNotificationTrigger(button);

    var endpoint = button.getAttribute('data-endpoint');
    var limitAttr = parseInt(button.getAttribute('data-limit') || '10', 10);
    var previewLimit = Math.max(1, Math.min(delta, Math.max(1, Math.min(limitAttr || 3, 3))));

    var fallback = function () {
      var plural = delta === 1 ? '' : 'es';
      showSiteToast('Tienes ' + delta + ' nueva' + plural + ' notificación' + plural + '.', 'info', {
        actionLabel: 'Ver',
        duration: 8000,
        onAction: function () {
          openNotificationsPanel(button);
        }
      });
    };

    if (!endpoint) {
      fallback();
      return;
    }

    if (typeof AbortController === 'function') {
      if (button.__notificationPreviewAbort instanceof AbortController) {
        button.__notificationPreviewAbort.abort();
      }
      button.__notificationPreviewAbort = new AbortController();
    }

    fetch(buildUrl(endpoint, { limit: previewLimit }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      signal: button.__notificationPreviewAbort ? button.__notificationPreviewAbort.signal : undefined
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        var items = data && Array.isArray(data.notifications) ? data.notifications : [];
        if (!items.length) {
          fallback();
          return;
        }

        button.__toastShownIds = button.__toastShownIds || new Set();
        var shown = 0;

        items.forEach(function (notification) {
          if (shown >= previewLimit) {
            return;
          }

          if (!notification || typeof notification.id === 'undefined') {
            return;
          }

          if (button.__toastShownIds.has(notification.id)) {
            return;
          }

          button.__toastShownIds.add(notification.id);
          shown += 1;

          var title = (notification.title || '').toString().trim() || 'Nueva notificación';
          var message = (notification.message || '').toString().replace(/\s+/g, ' ').trim();
          if (message.length > 140) {
            message = message.slice(0, 137).trim() + '…';
          }

          var toastType = typeof notification.type === 'string' ? notification.type.toLowerCase() : 'info';

          showSiteToast(message || title, toastType, {
            title: title,
            duration: 9000,
            actionLabel: 'Ver',
            onAction: function () {
              openNotificationsPanel(button, notification.id);
            }
          });
        });

        if (shown === 0) {
          fallback();
        }
      })
      .catch(function () {
        fallback();
      })
      .finally(function () {
        if (button.__notificationPreviewAbort instanceof AbortController) {
          button.__notificationPreviewAbort = null;
        }
      });
  }

  function highlightNotificationTrigger(button) {
    if (!button) {
      return;
    }

    button.classList.add('notification-trigger--highlight');
    window.setTimeout(function () {
      button.classList.remove('notification-trigger--highlight');
    }, 1800);
  }

  function openNotificationsPanel(button, focusId) {
    var pair = getOrRegisterPair(button);
    if (!pair) {
      return;
    }

    var wasHidden = pair.menu.classList.contains('hidden');
    closeAll(button);
    setToggleState(pair, true);

    var ensureLoaded = function () {
      if (!focusId) {
        return;
      }
      focusNotificationItem(pair.menu, focusId);
    };

    if (wasHidden) {
      loadNotifications(button, pair.menu, { force: true, reset: true, silent: true })
        .then(function () {
          focusNotificationItem(pair.menu, focusId);
        });
    } else {
      ensureLoaded();
    }
  }

  function focusNotificationItem(menu, notificationId) {
    if (!menu) {
      return;
    }

    var selector = notificationId
      ? '[data-notification-item][data-notification-id="' + notificationId + '"] button[data-notification-action="open"]'
      : '[data-notification-item] button[data-notification-action="open"]';

    var target = menu.querySelector(selector);
    if (target && typeof target.focus === 'function') {
      target.focus();
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

  function initializeNotificationTrigger(pair) {
    if (!pair || !pair.button || !pair.menu) {
      return;
    }

    if (!pair.button.hasAttribute('data-notification-trigger')) {
      return;
    }

    if (pair.button.__notificationInitDone) {
      return;
    }

    pair.button.__notificationInitDone = true;
    getNotificationState(pair.button);
    scheduleNotificationSummary(pair.button);
  }

  function handleToggle(button) {
    var pair = getOrRegisterPair(button);
    if (!pair) {
      return;
    }

    var willOpen = pair.menu.classList.contains('hidden');

    closeAll(button);
    setToggleState(pair, willOpen);

    if (willOpen && button.hasAttribute('data-notification-trigger')) {
      loadNotifications(button, pair.menu, { force: true, reset: true });
    }
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

      var actionElement = event.target.closest('[data-notification-action]');
      if (actionElement) {
        handleNotificationAction(actionElement, event);
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
        initializeNotificationTrigger(pair);
        var isOpen = !pair.menu.classList.contains('hidden');
        setToggleState(pair, isOpen);
      }
    }
  }


  primeExistingToggles();
  ensureNotificationModal();
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

var shareModalState = {
  modal: null,
  panel: null,
  overlay: null,
  input: null,
  copyButton: null,
  feedback: null,
  nameLabel: null,
  networkLinks: [],
  lastFocus: null,
  copyTimeout: null,
  currentUrl: '',
  currentTitle: '',
  currentIdentifier: ''
};

var FAVORITES_STORAGE_KEY = 'lucaton_favs';
var galleryLightboxState = {
  items: [],
  modal: null,
  containerEl: null,
  imageEl: null,
  captionEl: null,
  counterEl: null,
  prevButton: null,
  nextButton: null,
  lastFocus: null,
  currentIndex: 0,
  isOpen: false
};
var updateHeartStorageKey = 'lucaton_update_hearts';
var creatorProfileModalState = {
  modal: null,
  lastFocus: null,
  isOpen: false,
  data: null,
  avatarEl: null,
  nameEl: null,
  usernameEl: null,
  socialWrapper: null,
  socialListEl: null
};

function initShareModal() {
  var modal = document.getElementById('share-modal');
  if (!modal) {
    shareModalState.modal = null;
    shareModalState.panel = null;
    return;
  }

  if (modal.dataset.bound === '1') {
    return;
  }

  shareModalState.modal = modal;
  shareModalState.panel = modal.querySelector('[data-share-panel]') || null;
  shareModalState.overlay = modal.querySelector('[data-share-overlay]') || null;
  shareModalState.input = modal.querySelector('[data-share-url]') || null;
  shareModalState.copyButton = modal.querySelector('[data-share-copy]') || null;
  shareModalState.feedback = modal.querySelector('[data-share-feedback]') || null;
  shareModalState.nameLabel = modal.querySelector('[data-share-name]') || null;
  shareModalState.networkLinks = modal.querySelectorAll('[data-share-network]');
  shareModalState.copyTimeout = null;
  shareModalState.currentUrl = '';
  shareModalState.currentTitle = document.title;
  shareModalState.currentIdentifier = '';
  shareModalState.lastFocus = null;

  if (shareModalState.networkLinks && shareModalState.networkLinks.forEach) {
    shareModalState.networkLinks.forEach(function (link) {
      if (!link || link.dataset.shareBound === '1') {
        return;
      }
      link.addEventListener('click', function () {
        var network = link.getAttribute('data-share-network') || 'network';
        registerShareMetric('network:' + network);
      });
      link.dataset.shareBound = '1';
    });
  }

  modal.dataset.bound = '1';

  var closeButtons = modal.querySelectorAll('[data-share-close]');
  closeButtons.forEach(function (button) {
    button.addEventListener('click', closeShareModal);
  });

  if (shareModalState.overlay) {
    shareModalState.overlay.addEventListener('click', closeShareModal);
  }

  modal.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeShareModal();
    }
  });

  if (shareModalState.copyButton) {
    shareModalState.copyButton.addEventListener('click', handleShareCopy);
  }
}

function openShareModal(payload) {
  initShareModal();
  if (!shareModalState.modal) {
    return;
  }

  var modal = shareModalState.modal;
  shareModalState.lastFocus = document.activeElement;
  shareModalState.currentUrl = payload.url || '';
  shareModalState.currentTitle = payload.title || document.title;
  shareModalState.currentIdentifier = payload.identifier || '';

  modal.classList.remove('hidden');
  modal.classList.add('flex');
  modal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('overflow-hidden');

  if (shareModalState.panel) {
    var panel = shareModalState.panel;
    panel.classList.remove('share-modal-pop');
    void panel.offsetWidth;
    panel.classList.add('share-modal-pop');
    var onPanelAnimationEnd = function () {
      panel.classList.remove('share-modal-pop');
      panel.removeEventListener('animationend', onPanelAnimationEnd);
    };
    panel.addEventListener('animationend', onPanelAnimationEnd);
  }

  if (shareModalState.input) {
    shareModalState.input.value = shareModalState.currentUrl;
    shareModalState.input.setAttribute('value', shareModalState.currentUrl);
    try {
      shareModalState.input.focus({ preventScroll: true });
      shareModalState.input.select();
    } catch (err) {
      shareModalState.input.focus();
      shareModalState.input.select();
    }
  }

  if (shareModalState.feedback) {
    shareModalState.feedback.classList.add('hidden');
  }

  if (shareModalState.nameLabel) {
    shareModalState.nameLabel.textContent = payload.title || 'esta campaña';
  }

  updateShareNetworks(shareModalState.currentUrl, shareModalState.currentTitle);
}

function closeShareModal() {
  if (!shareModalState.modal) {
    return;
  }

  var modal = shareModalState.modal;
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  modal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('overflow-hidden');

  if (shareModalState.panel) {
    shareModalState.panel.classList.remove('share-modal-pop');
  }

  if (shareModalState.feedback) {
    shareModalState.feedback.classList.add('hidden');
  }

  if (shareModalState.copyTimeout) {
    window.clearTimeout(shareModalState.copyTimeout);
    shareModalState.copyTimeout = null;
  }

  if (shareModalState.lastFocus && typeof shareModalState.lastFocus.focus === 'function') {
    try {
      shareModalState.lastFocus.focus({ preventScroll: true });
    } catch (err) {
      shareModalState.lastFocus.focus();
    }
  }

  shareModalState.currentIdentifier = '';
}

function handleShareCopy(event) {
  event.preventDefault();
  if (!shareModalState.input) {
    return;
  }

  var url = shareModalState.input.value;
  if (!url) {
    return;
  }

  var showFeedback = function () {
    registerShareMetric('copy');
    if (!shareModalState.feedback) {
      return;
    }
    shareModalState.feedback.classList.remove('hidden');
    if (shareModalState.copyTimeout) {
      window.clearTimeout(shareModalState.copyTimeout);
    }
    shareModalState.copyTimeout = window.setTimeout(function () {
      shareModalState.feedback.classList.add('hidden');
    }, 2000);
  };

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(url).then(showFeedback).catch(function () {
      fallbackCopy(url, showFeedback);
    });
  } else {
    fallbackCopy(url, showFeedback);
  }
}

function fallbackCopy(content, callback) {
  if (!shareModalState.input) {
    return;
  }

  var input = shareModalState.input;
  var previousReadOnly = input.hasAttribute('readonly');
  if (previousReadOnly) {
    input.removeAttribute('readonly');
  }
  input.value = content;
  input.select();

  try {
    var successful = document.execCommand('copy');
    if (!successful) {
      throw new Error('Copy command failed');
    }
    callback();
  } catch (err) {
    window.prompt('Copia el enlace:', content);
  }

  input.value = shareModalState.currentUrl || content;
  if (previousReadOnly) {
    input.setAttribute('readonly', 'readonly');
  }
  input.blur();
}

function updateShareNetworks(url, title) {
  if (!shareModalState.networkLinks || shareModalState.networkLinks.length === 0) {
    return;
  }

  var cleanTitle = title ? String(title).trim() : 'esta campaña';
  if (cleanTitle === '') {
    cleanTitle = 'esta campaña';
  }

  var encodedUrl = encodeURIComponent(url);
  var encodedTitle = encodeURIComponent(cleanTitle);
  var baseMessage = 'Estoy apoyando "' + cleanTitle + '" en Lucatón.';
  var actionMessage = 'Conoce la campaña y súmate: ' + url;
  var encodedBaseMessage = encodeURIComponent(baseMessage);
  var encodedActionMessage = encodeURIComponent(actionMessage);
  var linkedinSummary = 'Estoy apoyando "' + cleanTitle + '" en Lucatón. Conoce la campaña y súmate.';
  var encodedLinkedinSummary = encodeURIComponent(linkedinSummary);
  var emailSubject = encodeURIComponent('Te comparto "' + cleanTitle + '" en Lucatón');
  var emailBody = encodeURIComponent(baseMessage + '\n\n' + actionMessage + '\n\nGracias por apoyar proyectos solidarios.');

  shareModalState.networkLinks.forEach(function (link) {
    var network = link.getAttribute('data-share-network');
    var href = url;
    var targetAttr = link.getAttribute('target');

    if (network === 'whatsapp') {
      href = 'https://wa.me/?text=' + encodedBaseMessage + '%0A%0A' + encodedActionMessage;
    } else if (network === 'facebook') {
      href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl + '&quote=' + encodedBaseMessage;
    } else if (network === 'twitter' || network === 'x') {
      href = 'https://twitter.com/intent/tweet?text=' + encodedBaseMessage + '&url=' + encodedUrl;
    } else if (network === 'linkedin') {
      href = 'https://www.linkedin.com/shareArticle?mini=true&url=' + encodedUrl + '&title=' + encodedTitle + '&summary=' + encodedLinkedinSummary + '&source=Lucaton';
    } else if (network === 'instagram') {
      href = 'https://www.instagram.com/?url=' + encodedUrl;
    } else if (network === 'email') {
      href = 'mailto:?subject=' + emailSubject + '&body=' + emailBody;
      link.setAttribute('target', '_self');
      link.removeAttribute('rel');
    }

    if (network !== 'email' && (!targetAttr || targetAttr === '_self')) {
      link.setAttribute('target', '_blank');
      link.setAttribute('rel', 'noopener');
    }

    link.setAttribute('href', href);
  });
}

function getCsrfToken() {
  if (typeof getCsrfToken.cachedToken !== 'undefined') {
    return getCsrfToken.cachedToken;
  }

  var meta = document.querySelector('meta[name="csrf-token"]');
  getCsrfToken.cachedToken = meta ? meta.getAttribute('content') || '' : '';
  return getCsrfToken.cachedToken;
}

function registerShareMetric(source) {
  var identifier = shareModalState.currentIdentifier;
  if (!identifier || typeof window.fetch !== 'function') {
    return;
  }

  var token = getCsrfToken();
  if (!token) {
    return;
  }

  var base = window.location.origin + (window.APP_BASE_PATH || '');
  var endpoint = base + '/api/campanas/' + encodeURIComponent(identifier) + '/compartir';
  var payload = {};
  if (source) {
    payload.source = source;
  }

  try {
    fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(payload),
      keepalive: true
    }).catch(function () {});
  } catch (err) {
    // Ignorar errores de red silenciosamente
  }
}

function initCampaignGalleryLightbox() {
  var modal = document.querySelector('[data-gallery-lightbox]');
  var triggers = document.querySelectorAll('[data-gallery-trigger]');
  if (!modal || !triggers.length) {
    return;
  }

  var triggerItems = [];
  triggers.forEach(function (trigger) {
    var url = trigger.getAttribute('data-gallery-url') || '';
    if (!url) {
      var imgEl = trigger.querySelector('img');
      if (imgEl) {
        url = imgEl.getAttribute('src') || '';
      }
    }
    if (!url) {
      return;
    }
    trigger.setAttribute('data-gallery-index', String(triggerItems.length));
    triggerItems.push({
      url: url,
      caption: trigger.getAttribute('data-gallery-caption') || ''
    });
  });

  if (!triggerItems.length && Array.isArray(window.__campaignGallery)) {
    triggerItems = window.__campaignGallery;
  }

  if (!Array.isArray(triggerItems) || triggerItems.length === 0) {
    return;
  }

  galleryLightboxState.items = triggerItems;
  galleryLightboxState.modal = modal;
  galleryLightboxState.containerEl = modal.querySelector('[data-gallery-modal-container]');
  galleryLightboxState.imageEl = modal.querySelector('[data-gallery-current-image]');
  galleryLightboxState.captionEl = modal.querySelector('[data-gallery-current-caption]');
  galleryLightboxState.counterEl = modal.querySelector('[data-gallery-counter]');
  galleryLightboxState.prevButton = modal.querySelector('[data-gallery-prev]');
  galleryLightboxState.nextButton = modal.querySelector('[data-gallery-next]');

  function adjustGalleryLayout() {
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 1024;
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 768;

    if (galleryLightboxState.containerEl) {
      var targetWidth = Math.min(Math.max(360, viewportWidth * 0.9), 1100);
      galleryLightboxState.containerEl.style.width = targetWidth + 'px';
    }

    if (galleryLightboxState.imageEl) {
      var maxHeight = Math.min(Math.max(240, viewportHeight * 0.78), viewportHeight - 160);
      galleryLightboxState.imageEl.style.maxHeight = maxHeight + 'px';
      galleryLightboxState.imageEl.style.maxWidth = Math.min(Math.max(320, viewportWidth * 0.88), 1400) + 'px';
    }
  }

  function updateLightbox() {
    var index = galleryLightboxState.currentIndex;
    var item = galleryLightboxState.items[index];
    if (!item || !galleryLightboxState.imageEl) {
      return;
    }

    galleryLightboxState.imageEl.src = item.url || '';
    galleryLightboxState.imageEl.alt = item.caption ? item.caption : 'Imagen de la campaña';

    if (galleryLightboxState.captionEl) {
      if (item.caption) {
        galleryLightboxState.captionEl.textContent = item.caption;
        galleryLightboxState.captionEl.classList.remove('hidden');
      } else {
        galleryLightboxState.captionEl.textContent = '';
        galleryLightboxState.captionEl.classList.add('hidden');
      }
    }

    if (galleryLightboxState.counterEl) {
      galleryLightboxState.counterEl.textContent = (index + 1) + ' / ' + galleryLightboxState.items.length;
    }

    var hideControls = galleryLightboxState.items.length <= 1;
    if (galleryLightboxState.prevButton) {
      galleryLightboxState.prevButton.disabled = hideControls;
      galleryLightboxState.prevButton.style.display = hideControls ? 'none' : '';
    }
    if (galleryLightboxState.nextButton) {
      galleryLightboxState.nextButton.disabled = hideControls;
      galleryLightboxState.nextButton.style.display = hideControls ? 'none' : '';
    }

    adjustGalleryLayout();
  }

  function openGallery(index) {
    galleryLightboxState.currentIndex = index >= 0 ? index % galleryLightboxState.items.length : 0;
    galleryLightboxState.lastFocus = document.activeElement;
    galleryLightboxState.isOpen = true;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    updateLightbox();
    try {
      modal.focus({ preventScroll: true });
    } catch (err) {
      modal.focus();
    }
    document.body.classList.add('overflow-hidden');
    adjustGalleryLayout();
  }

  function closeGallery() {
    galleryLightboxState.isOpen = false;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    if (galleryLightboxState.lastFocus && typeof galleryLightboxState.lastFocus.focus === 'function') {
      try {
        galleryLightboxState.lastFocus.focus({ preventScroll: true });
      } catch (err) {
        galleryLightboxState.lastFocus.focus();
      }
    }
  }

  function changeSlide(delta) {
    var total = galleryLightboxState.items.length;
    if (total <= 1) {
      return;
    }
    var nextIndex = (galleryLightboxState.currentIndex + delta + total) % total;
    galleryLightboxState.currentIndex = nextIndex;
    updateLightbox();
  }

  triggers.forEach(function (trigger) {
    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      var index = parseInt(trigger.getAttribute('data-gallery-index') || '0', 10);
      if (isNaN(index)) {
        index = 0;
      }
      openGallery(index);
    });
  });

  var closeButtons = modal.querySelectorAll('[data-gallery-close]');
  closeButtons.forEach(function (btn) {
    btn.addEventListener('click', function (event) {
      event.preventDefault();
      closeGallery();
    });
  });

  if (galleryLightboxState.prevButton) {
    galleryLightboxState.prevButton.addEventListener('click', function (event) {
      event.preventDefault();
      changeSlide(-1);
    });
  }

  if (galleryLightboxState.nextButton) {
    galleryLightboxState.nextButton.addEventListener('click', function (event) {
      event.preventDefault();
      changeSlide(1);
    });
  }

  window.addEventListener('keydown', function (event) {
    if (!galleryLightboxState.isOpen) {
      return;
    }

    if (event.key === 'Escape') {
      closeGallery();
    } else if (event.key === 'ArrowRight') {
      changeSlide(1);
    } else if (event.key === 'ArrowLeft') {
      changeSlide(-1);
    }
  });

  window.addEventListener('resize', function () {
    if (galleryLightboxState.isOpen) {
      adjustGalleryLayout();
    }
  });
}

function readUpdateHeartStorage() {
  try {
    var raw = localStorage.getItem(updateHeartStorageKey);
    return raw ? JSON.parse(raw) : {};
  } catch (err) {
    return {};
  }
}

function writeUpdateHeartStorage(map) {
  try {
    localStorage.setItem(updateHeartStorageKey, JSON.stringify(map));
  } catch (err) {
    // ignore quota errors silently
  }
}

function updateHeartButtonStyles(button, icon, path, active) {
  if (!button) {
    return;
  }

  button.setAttribute('aria-pressed', active ? 'true' : 'false');
  if (active) {
    button.classList.add('bg-rose-500', 'text-white');
    button.classList.remove('bg-white', 'text-rose-500', 'border-rose-200');
  } else {
    button.classList.add('bg-white', 'text-rose-500', 'border-rose-200');
    button.classList.remove('bg-rose-500', 'text-white');
  }

  if (icon) {
    if (active) {
      icon.setAttribute('fill', 'currentColor');
      icon.setAttribute('stroke', 'currentColor');
    } else {
      icon.setAttribute('fill', 'none');
      icon.setAttribute('stroke', 'currentColor');
    }
  }

  if (path) {
    if (active) {
      path.setAttribute('fill', 'currentColor');
      path.setAttribute('stroke', 'currentColor');
    } else {
      path.setAttribute('fill', 'none');
      path.setAttribute('stroke', 'currentColor');
    }
  }
}

function initCampaignUpdateHearts() {
  var buttons = document.querySelectorAll('[data-update-heart]');
  if (!buttons.length) {
    return;
  }

  var storage = readUpdateHeartStorage();

  buttons.forEach(function (button) {
    var updateId = button.getAttribute('data-update-id');
    if (!updateId) {
      return;
    }

    var baseCount = parseInt(button.getAttribute('data-update-initial') || '0', 10);
    if (isNaN(baseCount)) {
      baseCount = 0;
    }

    var icon = button.querySelector('[data-update-heart-icon]');
    var path = button.querySelector('[data-update-heart-path]');
    var countEl = button.parentElement.querySelector('[data-update-heart-count]');

    var hasHearted = Boolean(storage[updateId]);
    var displayCount = baseCount + (hasHearted ? 1 : 0);

    if (countEl) {
      countEl.textContent = displayCount;
    }
    updateHeartButtonStyles(button, icon, path, hasHearted);

    button.addEventListener('click', function (event) {
      event.preventDefault();
      storage = readUpdateHeartStorage();
      hasHearted = Boolean(storage[updateId]);

      if (hasHearted) {
        delete storage[updateId];
        displayCount = baseCount;
        updateHeartButtonStyles(button, icon, path, false);
      } else {
        storage[updateId] = true;
        displayCount = baseCount + 1;
        updateHeartButtonStyles(button, icon, path, true);
      }

      if (countEl) {
        countEl.textContent = displayCount;
      }

      writeUpdateHeartStorage(storage);
    });
  });
}

function initCreatorProfileModal() {
  if (!window.__creatorProfile) {
    return;
  }

  var modal = document.querySelector('[data-creator-profile-modal]');
  var triggers = document.querySelectorAll('[data-creator-profile-trigger]');
  if (!modal || !triggers.length) {
    return;
  }

  creatorProfileModalState.modal = modal;
  creatorProfileModalState.data = window.__creatorProfile;
  creatorProfileModalState.avatarEl = modal.querySelector('[data-creator-profile-avatar]');
  creatorProfileModalState.nameEl = modal.querySelector('[data-creator-profile-name]');
  creatorProfileModalState.usernameEl = modal.querySelector('[data-creator-profile-username]');
  creatorProfileModalState.socialWrapper = modal.querySelector('[data-creator-profile-socials-wrapper]');
  creatorProfileModalState.socialListEl = modal.querySelector('[data-creator-profile-socials]');

  function renderCreatorProfile() {
    var data = creatorProfileModalState.data || {};
    if (creatorProfileModalState.avatarEl) {
      if (data.avatar) {
        creatorProfileModalState.avatarEl.innerHTML = '<img src="' + data.avatar + '" alt="Avatar de ' + (data.name || 'Usuario') + '" class="h-14 w-14 rounded-full object-cover">';
      } else {
        var initial = 'U';
        if (data.name) {
          initial = data.name.trim().charAt(0).toUpperCase();
        }
        creatorProfileModalState.avatarEl.textContent = initial;
      }
    }

    if (creatorProfileModalState.nameEl) {
      creatorProfileModalState.nameEl.textContent = data.name || 'Usuario';
    }

    if (creatorProfileModalState.usernameEl) {
      creatorProfileModalState.usernameEl.textContent = data.username ? '@' + data.username : '';
    }

    if (creatorProfileModalState.socialWrapper && creatorProfileModalState.socialListEl) {
      creatorProfileModalState.socialListEl.innerHTML = '';
      var socials = Array.isArray(data.social) ? data.social : [];
      if (socials.length) {
        creatorProfileModalState.socialWrapper.classList.remove('hidden');
        socials.forEach(function (item) {
          if (!item || !item.url) {
            return;
          }

          var link = document.createElement('a');
          link.href = item.url;
          link.target = '_blank';
          link.rel = 'noopener noreferrer';
          link.className = 'inline-flex items-center gap-2 rounded-full border border-copihue-200 bg-white px-3 py-1 text-xs font-semibold text-copihue-700 transition hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-500';

          var badgeInitial = (item.initial || '').toString().toUpperCase().slice(0, 2) || 'EN';
          var badgeLabel = item.label || 'Enlace';

          link.innerHTML = '<span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-copihue-100 text-copihue-600 text-[11px] font-bold">'
            + badgeInitial + '</span><span>' + badgeLabel + '</span>';

          creatorProfileModalState.socialListEl.appendChild(link);
        });
      } else {
        creatorProfileModalState.socialWrapper.classList.add('hidden');
      }
    }

  }

  function openCreatorProfile() {
    creatorProfileModalState.lastFocus = document.activeElement;
    creatorProfileModalState.isOpen = true;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    renderCreatorProfile();
    try {
      modal.focus({ preventScroll: true });
    } catch (err) {
      modal.focus();
    }
  }

  function closeCreatorProfile() {
    creatorProfileModalState.isOpen = false;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    if (creatorProfileModalState.lastFocus && typeof creatorProfileModalState.lastFocus.focus === 'function') {
      try {
        creatorProfileModalState.lastFocus.focus({ preventScroll: true });
      } catch (err) {
        creatorProfileModalState.lastFocus.focus();
      }
    }
  }

  triggers.forEach(function (trigger) {
    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      openCreatorProfile();
    });
  });

  var closeButtons = modal.querySelectorAll('[data-creator-profile-close]');
  closeButtons.forEach(function (btn) {
    btn.addEventListener('click', function (event) {
      event.preventDefault();
      closeCreatorProfile();
    });
  });

  window.addEventListener('keydown', function (event) {
    if (!creatorProfileModalState.isOpen) {
      return;
    }

    if (event.key === 'Escape') {
      closeCreatorProfile();
    }
  });
}

function normalizeSharePayload(target) {
  var payload = {
    id: null,
    slug: '',
    url: '',
    title: ''
  };

  if (typeof target === 'string' || typeof target === 'number') {
    var normalized = String(target);
    if (/^\d+$/.test(normalized)) {
      payload.id = normalized;
    } else {
      payload.slug = normalized;
    }
  } else if (target && typeof target === 'object') {
    if (target.id !== undefined && target.id !== null && target.id !== '') {
      payload.id = String(target.id);
    } else if (target.identifier !== undefined && target.identifier !== null && target.identifier !== '') {
      payload.id = String(target.identifier);
    }
    if (target.slug !== undefined && target.slug !== null && target.slug !== '') {
      payload.slug = String(target.slug);
    }
    if (target.url) {
      payload.url = String(target.url);
    }
    if (target.title) {
      payload.title = String(target.title);
    }
  }

  var identifier = '';
  if (payload.id !== null && payload.id !== '') {
    identifier = String(payload.id);
  } else if (payload.slug !== '') {
    identifier = payload.slug;
  }

  if (!payload.url) {
    payload.url = buildCampaignUrl(identifier || payload.slug);
  }

  if (!payload.title) {
    payload.title = 'Campaña Lucatón';
  }

  return {
    url: payload.url,
    title: payload.title,
    identifier: identifier,
    slug: payload.slug,
    id: payload.id
  };
}

function buildCampaignUrl(identifier) {
  var base = window.location.origin + (window.APP_BASE_PATH || '');
  if (!identifier) {
    return base + '/campana/detalle';
  }

  var slug = typeof identifier === 'string' ? identifier : String(identifier);
  if (/^https?:\/\//i.test(slug)) {
    return slug;
  }

  if (slug.charAt(0) === '/') {
    return base + slug;
  }

  return base + '/campana/' + encodeURIComponent(slug);
}

function readFavorites() {
  try {
    var stored = localStorage.getItem(FAVORITES_STORAGE_KEY);
    if (!stored) {
      return new Set();
    }
    var parsed = JSON.parse(stored);
    if (!Array.isArray(parsed)) {
      return new Set();
    }
    return new Set(parsed.map(function (value) {
      return String(value);
    }));
  } catch (err) {
    return new Set();
  }
}

function writeFavorites(values) {
  try {
    var serialized = JSON.stringify(Array.from(values));
    localStorage.setItem(FAVORITES_STORAGE_KEY, serialized);
  } catch (err) {
    // Ignorar errores de almacenamiento (modo incógnito, etc.)
  }
}

function updateFavoriteButtonState(button, active) {
  button.setAttribute('aria-pressed', active ? 'true' : 'false');
  button.classList.toggle('text-copihue-600', active);
  button.classList.toggle('text-gray-400', !active);

  var icon = button.querySelector('[data-favorite-icon]');
  var path = button.querySelector('[data-favorite-path]');
  if (icon) {
    icon.setAttribute('fill', active ? 'currentColor' : 'none');
    icon.setAttribute('stroke', active ? 'currentColor' : 'currentColor');
  }
  if (path) {
    path.setAttribute('stroke', active ? 'currentColor' : 'currentColor');
  }

  button.setAttribute('title', active ? 'Quitar de favoritos' : 'Guardar campaña');
}

function normalizeFavoritePayload(payload) {
  if (payload === null || payload === undefined) {
    return { id: null };
  }

  if (typeof payload === 'string' || typeof payload === 'number' || typeof payload === 'boolean') {
    return { id: String(payload) };
  }

  if (typeof payload === 'object') {
    if (payload.id !== undefined && payload.id !== null && payload.id !== '') {
      return { id: String(payload.id) };
    }
    if (payload.slug !== undefined && payload.slug !== null && payload.slug !== '') {
      return { id: String(payload.slug) };
    }
  }

  return { id: null };
}

function initFavoriteButtons() {
  var buttons = document.querySelectorAll('[data-favorite-button]');
  if (!buttons.length) {
    return;
  }

  var favorites = readFavorites();
  buttons.forEach(function (button) {
    if (button.dataset.favoriteBound === '1') {
      updateFavoriteButtonState(button, favorites.has(button.getAttribute('data-favorite-id')));
      return;
    }

    var id = button.getAttribute('data-favorite-id');
    if (!id) {
      return;
    }

    updateFavoriteButtonState(button, favorites.has(id));
    button.dataset.favoriteBound = '1';
  });
}

function initCampaignCards() {
  var cards = document.querySelectorAll('[data-campaign-link]');
  if (!cards.length) {
    return;
  }

  cards.forEach(function (card) {
    if (card.dataset.cardLinkBound === '1') {
      return;
    }

    card.dataset.cardLinkBound = '1';
    card.addEventListener('click', handleCardLinkClick);
    card.addEventListener('keydown', handleCardLinkKeydown);
  });
}

function handleCardLinkClick(event) {
  if (shouldIgnoreCardInteraction(event.target)) {
    return;
  }

  var card = event.currentTarget;
  var url = card.getAttribute('data-campaign-link');
  if (!url) {
    return;
  }

  event.preventDefault();
  window.location.href = url;
}

function handleCardLinkKeydown(event) {
  if (event.key !== 'Enter' && event.key !== ' ') {
    return;
  }

  var card = event.currentTarget;
  var url = card.getAttribute('data-campaign-link');
  if (!url) {
    return;
  }

  event.preventDefault();
  window.location.href = url;
}

function shouldIgnoreCardInteraction(target) {
  if (!target) {
    return false;
  }

  var interactive = target.closest('a, button, input, textarea, select, label, [role="button"], [data-ignore-card-link]');
  return Boolean(interactive);
}

function initNewsletterSignup() {
  var forms = document.querySelectorAll('[data-newsletter-form]');
  if (!forms.length) {
    return;
  }

  forms.forEach(function (form) {
    if (form.dataset.bound === '1') {
      return;
    }

    form.dataset.bound = '1';

    var emailInput = form.querySelector('[data-newsletter-email]');
    var submitButton = form.querySelector('[data-newsletter-submit]');
    var originalText = submitButton ? submitButton.textContent : '';

    form.addEventListener('submit', function (event) {
      if (!emailInput) {
        return;
      }

      event.preventDefault();

      var email = emailInput.value.trim();
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showNewsletterToast('Ingresa un correo válido para suscribirte.', 'error');
        emailInput.focus();
        return;
      }

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.classList.add('opacity-60', 'pointer-events-none');
        submitButton.textContent = 'Enviando…';
      }

      var formData = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        },
        credentials: 'same-origin'
      })
        .then(function (response) {
          if (response.redirected) {
            window.location.assign(response.url);
            return null;
          }

          var contentType = response.headers.get('content-type') || '';
          if (contentType.indexOf('application/json') === -1) {
            if (response.ok) {
              showNewsletterToast('Suscripción registrada correctamente.', 'success');
              form.reset();
            } else {
              showNewsletterToast('No pudimos registrar tu correo. Intenta nuevamente.', 'error');
            }
            return null;
          }

          return response.json();
        })
        .then(function (payload) {
          if (!payload) {
            return;
          }

          if (payload.requires_auth) {
            if (payload.message) {
              showNewsletterToast(payload.message, 'warning');
            }
            var redirectUrl = payload.redirect || window.location.origin + '/login';
            setTimeout(function () {
              window.location.href = redirectUrl;
            }, 800);
            return;
          }

          if (payload.success) {
            showNewsletterToast(payload.message || 'Suscripción registrada correctamente.', 'success');
            form.reset();
          } else {
            showNewsletterToast(payload.message || 'No pudimos registrar tu correo. Intenta nuevamente.', 'error');
          }
        })
        .catch(function () {
          showNewsletterToast('Ocurrió un problema al procesar tu solicitud.', 'error');
        })
        .finally(function () {
          if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-60', 'pointer-events-none');
            submitButton.textContent = originalText;
          }
        });
    });
  });
}

function showNewsletterToast(message, type) {
  showSiteToast(message, type);
}

function showSiteToast(message, type, options) {
  options = options || {};
  var container = getSiteToastContainer();
  if (!container) {
    return;
  }

  var normalized = typeof type === 'string' ? type.toLowerCase() : 'info';
  if (['success', 'error', 'warning', 'info', 'system'].indexOf(normalized) === -1) {
    normalized = 'info';
  }

  var textContent = typeof message === 'string' ? message : String(message || '');
  var titleText = typeof options.title === 'string' ? options.title : '';
  var descriptionText = typeof options.description === 'string' ? options.description : '';

  if (!textContent && descriptionText) {
    textContent = descriptionText;
    descriptionText = '';
  }

  var toast = document.createElement('div');
  toast.className = 'site-toast site-toast--' + normalized;
  toast.setAttribute('role', normalized === 'error' ? 'alert' : 'status');
  toast.setAttribute('data-toast-type', normalized);

  var icon = document.createElement('span');
  icon.className = 'site-toast__icon';
  icon.innerHTML = getSiteToastIcon(normalized);

  var content = document.createElement('div');
  content.className = 'site-toast__content';

  if (titleText) {
    var titleEl = document.createElement('p');
    titleEl.className = 'site-toast__title';
    titleEl.textContent = titleText;
    content.appendChild(titleEl);
  }

  if (textContent) {
    var messageEl = document.createElement('p');
    messageEl.className = 'site-toast__message';
    messageEl.textContent = textContent;
    content.appendChild(messageEl);
  }

  if (descriptionText) {
    var descriptionEl = document.createElement('p');
    descriptionEl.className = 'site-toast__description';
    descriptionEl.textContent = descriptionText;
    content.appendChild(descriptionEl);
  }

  var controls = document.createElement('div');
  controls.className = 'site-toast__controls';

  if (options.actionLabel && typeof options.onAction === 'function') {
    var actionButton = document.createElement('button');
    actionButton.type = 'button';
    actionButton.className = 'site-toast__action';
    actionButton.textContent = options.actionLabel;
    actionButton.addEventListener('click', function (event) {
      event.preventDefault();
      try {
        options.onAction();
      } catch (err) {
        // silent
      }
      dismissToast(true);
    });
    controls.appendChild(actionButton);
  }

  var dismissButton = document.createElement('button');
  dismissButton.type = 'button';
  dismissButton.className = 'site-toast__dismiss';
  dismissButton.setAttribute('aria-label', 'Cerrar notificación');
  dismissButton.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
  controls.appendChild(dismissButton);

  toast.appendChild(icon);
  toast.appendChild(content);
  toast.appendChild(controls);

  container.appendChild(toast);

  while (container.children.length > 5) {
    container.removeChild(container.firstElementChild);
  }

  var autoClose = options.autoClose !== false;
  var duration = typeof options.duration === 'number' ? Math.max(2000, options.duration) : 7000;
  var autoTimer = null;

  function startAutoClose() {
    if (!autoClose) {
      return;
    }
    clearTimeout(autoTimer);
    autoTimer = window.setTimeout(function () {
      dismissToast(true);
    }, duration);
  }

  function stopAutoClose() {
    clearTimeout(autoTimer);
  }

  function dismissToast(animate) {
    if (toast.dataset.dismissed === '1') {
      return;
    }
    toast.dataset.dismissed = '1';
    stopAutoClose();

    if (animate !== false) {
      toast.classList.add('site-toast--leaving');
      window.setTimeout(function () {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 260);
    } else if (toast.parentNode) {
      toast.parentNode.removeChild(toast);
    }
  }

  dismissButton.addEventListener('click', function (event) {
    event.preventDefault();
    dismissToast(true);
  });

  toast.addEventListener('mouseenter', stopAutoClose);
  toast.addEventListener('mouseleave', startAutoClose);
  toast.addEventListener('focusin', stopAutoClose);
  toast.addEventListener('focusout', startAutoClose);

  bindToastSwipe(toast, dismissToast);

  startAutoClose();
}

function bindToastSwipe(toast, dismiss) {
  var pointerId = null;
  var startX = 0;
  var startY = 0;
  var swiping = false;

  toast.addEventListener('pointerdown', function (event) {
    if (event.pointerType === 'mouse' && event.button !== 0) {
      return;
    }
    if (event.target.closest('.site-toast__dismiss, .site-toast__action')) {
      return;
    }
    pointerId = event.pointerId;
    startX = event.clientX;
    startY = event.clientY;
    swiping = false;
    toast.setPointerCapture(pointerId);
  });

  toast.addEventListener('pointermove', function (event) {
    if (pointerId !== event.pointerId) {
      return;
    }

    var deltaX = event.clientX - startX;
    var deltaY = Math.abs(event.clientY - startY);

    if (!swiping && deltaX > 6 && deltaY < 24) {
      swiping = true;
    }

    if (!swiping || deltaX <= 0) {
      return;
    }

    toast.style.transition = 'transform 0.05s ease';
    toast.style.transform = 'translate3d(' + deltaX + 'px, -6px, 0)';
    toast.style.opacity = String(Math.max(0.35, 1 - deltaX / 220));
  });

  function endSwipe(event) {
    if (pointerId !== event.pointerId) {
      return;
    }

    toast.releasePointerCapture(pointerId);
    var deltaX = event.clientX - startX;
    pointerId = null;

    if (swiping && deltaX > 120) {
      toast.style.transition = 'transform 0.18s ease, opacity 0.18s ease';
      toast.style.transform = 'translate3d(120%, -6px, 0)';
      toast.style.opacity = '0';
      window.setTimeout(function () {
        dismiss(false);
      }, 160);
    } else if (swiping) {
      toast.style.transition = 'transform 0.2s ease, opacity 0.2s ease';
      toast.style.transform = '';
      toast.style.opacity = '';
      window.setTimeout(function () {
        toast.style.transition = '';
      }, 220);
    }

    swiping = false;
  }

  toast.addEventListener('pointerup', endSwipe);
  toast.addEventListener('pointercancel', endSwipe);
}

function getSiteToastContainer() {
  var container = document.querySelector('[data-site-toast-container]');
  if (container) {
    return container;
  }

  var body = document.body;
  if (!body) {
    return null;
  }

  container = document.createElement('div');
  container.className = 'site-toast-stack';
  container.setAttribute('data-site-toast-container', '');
  container.setAttribute('role', 'region');
  container.setAttribute('aria-live', 'polite');
  container.setAttribute('aria-atomic', 'false');
  body.appendChild(container);
  return container;
}

function initSiteToasts() {
  if (!window.__SITE_TOASTS__) {
    return;
  }

  var entries = window.__SITE_TOASTS__;
  if (!Array.isArray(entries)) {
    entries = [entries];
  }

  entries.forEach(function (entry) {
    if (!entry || !entry.message) {
      return;
    }

    showSiteToast(entry.message, entry.type || 'info', {
      title: entry.title || '',
      description: entry.description || '',
      duration: entry.duration
    });
  });

  window.__SITE_TOASTS__ = null;
}

function getSiteToastIcon(type) {
  if (type === 'success') {
    return '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8.25 8.25a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414l2.293 2.293 7.543-7.543a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';
  }
  if (type === 'error') {
    return '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 011.414 0L10 7.586l.293-.293a1 1 0 011.414 1.414L11.414 9l.293-.293a1 1 0 011.414 1.414L12.828 11.828l.293.293a1 1 0 01-1.414 1.414L11.414 12l-.293.293a1 1 0 01-1.414-1.414L9 11.414l-.293.293a1 1 0 01-1.414-1.414L7.586 10l-.293-.293a1 1 0 011.414-1.414L9 8.586l.293-.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';
  }
  if (type === 'warning') {
    return '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.721-1.36 3.486 0l6.52 11.6A1.95 1.95 0 0116.52 18H3.48a1.95 1.95 0 01-1.743-3.301l6.52-11.6zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-.002-5a.998.998 0 00-1.996 0l.149 3.5a.848.848 0 001.698 0L10.998 8z" clip-rule="evenodd"/></svg>';
  }
  if (type === 'system') {
    return '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M11.3 1.046a1.3 1.3 0 00-2.6 0L8.293 4.9H4.3a1.3 1.3 0 000 2.6h3.201l-.79 4.8H2.9a1.3 1.3 0 100 2.6h3.247l-.447 2.716a1.3 1.3 0 102.565.423l.524-3.139h3.905l-.447 2.716a1.3 1.3 0 102.565.423l.524-3.139h3.048a1.3 1.3 0 000-2.6h-2.694l.79-4.8H17.1a1.3 1.3 0 000-2.6h-3.701z"/></svg>';
  }

  return '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-3a1 1 0 10-2 0 1 1 0 002 0zm-1 3a1 1 0 00-.993.883L9 11v3a1 1 0 001.993.117L11 14v-3a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
}

function getNewsletterToastContainer() {
  return getSiteToastContainer();
}

if (typeof window !== 'undefined') {
  window.showSiteToast = showSiteToast;
}

function bootstrapLucaton() {
  initLucatonUI();
  initStickyHeader();
  initPasswordVisibility();
  initShareModal();
  initCampaignCards();
  initFavoriteButtons();
  initCampaignGalleryLightbox();
  initCampaignUpdateHearts();
  initCreatorProfileModal();
  initSiteToasts();
  initNewsletterSignup();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrapLucaton, { once: true });
} else {
  bootstrapLucaton();
}

// Utility: abrir modal de compartir
function shareCampaign(triggerOrPayload, maybePayload) {
  var button = null;
  var payloadTarget;

  var isElement = triggerOrPayload && typeof triggerOrPayload === 'object' && triggerOrPayload.nodeType === 1;
  if (isElement) {
    button = triggerOrPayload;
    payloadTarget = maybePayload;
  } else if (maybePayload !== undefined) {
    payloadTarget = maybePayload;
  } else {
    payloadTarget = triggerOrPayload;
  }

  if (button && button.classList) {
    button.classList.remove('share-pop-animate');
    // Force reflow to restart animation when clicking in succession
    void button.offsetWidth;
    button.classList.add('share-pop-animate');
    var onAnimationEnd = function () {
      button.classList.remove('share-pop-animate');
      button.removeEventListener('animationend', onAnimationEnd);
    };
    button.addEventListener('animationend', onAnimationEnd);
  }

  var payload = normalizeSharePayload(payloadTarget);
  if (!payload.url) {
    return;
  }

  openShareModal(payload);
}

// Utility: alternar favoritos con retroalimentación UI
function toggleFavorite(event, payload) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
    event.stopPropagation();
  }

  var normalized = normalizeFavoritePayload(payload);
  if (!normalized.id) {
    return;
  }

  var favorites = readFavorites();
  var isActive = favorites.has(normalized.id);
  if (isActive) {
    favorites.delete(normalized.id);
  } else {
    favorites.add(normalized.id);
  }
  writeFavorites(favorites);

  var buttons = document.querySelectorAll('[data-favorite-button]');
  buttons.forEach(function (button) {
    if (button.getAttribute('data-favorite-id') === normalized.id) {
      updateFavoriteButtonState(button, !isActive);
    }
  });
}
