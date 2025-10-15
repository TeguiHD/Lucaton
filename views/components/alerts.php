<?php
/**
 * Componentes de alertas y notificaciones reutilizables
 */

/**
 * Alert component básico
 */
function render_alert($options = []) {
    $defaults = [
        'type' => 'info', // success, error, warning, info
        'title' => '',
        'message' => '',
        'dismissible' => true,
        'icon' => true,
        'actions' => [],
        'class' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // Configuración por tipo
    $type_config = [
        'success' => [
            'bg_class' => 'bg-green-50',
            'border_class' => 'border-green-200',
            'icon_class' => 'text-green-400',
            'title_class' => 'text-green-800',
            'message_class' => 'text-green-700',
            'button_class' => 'text-green-500 hover:text-green-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        'error' => [
            'bg_class' => 'bg-red-50',
            'border_class' => 'border-red-200',
            'icon_class' => 'text-red-400',
            'title_class' => 'text-red-800',
            'message_class' => 'text-red-700',
            'button_class' => 'text-red-500 hover:text-red-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        'warning' => [
            'bg_class' => 'bg-yellow-50',
            'border_class' => 'border-yellow-200',
            'icon_class' => 'text-yellow-400',
            'title_class' => 'text-yellow-800',
            'message_class' => 'text-yellow-700',
            'button_class' => 'text-yellow-500 hover:text-yellow-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />'
        ],
        'info' => [
            'bg_class' => 'bg-blue-50',
            'border_class' => 'border-blue-200',
            'icon_class' => 'text-blue-400',
            'title_class' => 'text-blue-800',
            'message_class' => 'text-blue-700',
            'button_class' => 'text-blue-500 hover:text-blue-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ]
    ];
    
    $config = $type_config[$options['type']] ?? $type_config['info'];
    
    $html = '<div class="rounded-md border p-4 ' . $config['bg_class'] . ' ' . $config['border_class'] . ' ' . $options['class'] . '"';
    
    if ($options['dismissible']) {
        $html .= ' x-data="{ show: true }" x-show="show" x-transition';
    }
    
    $html .= '>';
    
    $html .= '<div class="flex">';
    
    // Icon
    if ($options['icon']) {
        $html .= '<div class="flex-shrink-0">';
        $html .= '<svg class="h-5 w-5 ' . $config['icon_class'] . '" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
        $html .= $config['icon_path'];
        $html .= '</svg>';
        $html .= '</div>';
    }
    
    // Content
    $html .= '<div class="ml-3 flex-1">';
    
    if ($options['title']) {
        $html .= '<h3 class="text-sm font-medium ' . $config['title_class'] . '">';
        $html .= htmlspecialchars($options['title']);
        $html .= '</h3>';
    }
    
    if ($options['message']) {
        $message_tag = $options['title'] ? 'div' : 'h3';
        $message_class = $options['title'] ? 'mt-2 text-sm ' . $config['message_class'] : 'text-sm font-medium ' . $config['title_class'];
        
        $html .= '<' . $message_tag . ' class="' . $message_class . '">';
        $html .= htmlspecialchars($options['message']);
        $html .= '</' . $message_tag . '>';
    }
    
    // Actions
    if (!empty($options['actions'])) {
        $html .= '<div class="mt-4">';
        $html .= '<div class="-mx-2 -my-1.5 flex">';
        
        foreach ($options['actions'] as $action) {
            $html .= '<button type="button" class="' . $config['button_class'] . ' px-2 py-1.5 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-' . str_replace('bg-', '', $config['bg_class']) . ' focus:ring-' . str_replace('text-', '', $config['icon_class']) . '"';
            
            if (isset($action['onclick'])) {
                $html .= ' onclick="' . htmlspecialchars($action['onclick']) . '"';
            }
            
            $html .= '>';
            $html .= htmlspecialchars($action['text']);
            $html .= '</button>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Dismiss button
    if ($options['dismissible']) {
        $html .= '<div class="ml-auto pl-3">';
        $html .= '<div class="-mx-1.5 -my-1.5">';
        $html .= '<button type="button" class="inline-flex rounded-md p-1.5 ' . $config['button_class'] . ' focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-' . str_replace('bg-', '', $config['bg_class']) . ' focus:ring-' . str_replace('text-', '', $config['icon_class']) . '" @click="show = false">';
        $html .= '<span class="sr-only">Cerrar</span>';
        $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
        $html .= '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Toast notification
 */
function render_toast($options = []) {
    $defaults = [
        'type' => 'info',
        'title' => '',
        'message' => '',
        'duration' => 5000, // milliseconds
        'position' => 'top-right', // top-right, top-left, bottom-right, bottom-left
        'show_progress' => true,
        'id' => 'toast_' . uniqid()
    ];
    
    $options = array_merge($defaults, $options);
    
    // Position classes
    $position_classes = [
        'top-right' => 'top-0 right-0',
        'top-left' => 'top-0 left-0',
        'bottom-right' => 'bottom-0 right-0',
        'bottom-left' => 'bottom-0 left-0'
    ];
    
    $position_class = $position_classes[$options['position']] ?? $position_classes['top-right'];
    
    // Type configuration
    $type_config = [
        'success' => [
            'bg_class' => 'bg-white',
            'border_class' => 'border-l-4 border-green-400',
            'icon_class' => 'text-green-400',
            'title_class' => 'text-gray-900',
            'message_class' => 'text-gray-500',
            'progress_class' => 'bg-green-400',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        'error' => [
            'bg_class' => 'bg-white',
            'border_class' => 'border-l-4 border-red-400',
            'icon_class' => 'text-red-400',
            'title_class' => 'text-gray-900',
            'message_class' => 'text-gray-500',
            'progress_class' => 'bg-red-400',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        'warning' => [
            'bg_class' => 'bg-white',
            'border_class' => 'border-l-4 border-yellow-400',
            'icon_class' => 'text-yellow-400',
            'title_class' => 'text-gray-900',
            'message_class' => 'text-gray-500',
            'progress_class' => 'bg-yellow-400',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />'
        ],
        'info' => [
            'bg_class' => 'bg-white',
            'border_class' => 'border-l-4 border-blue-400',
            'icon_class' => 'text-blue-400',
            'title_class' => 'text-gray-900',
            'message_class' => 'text-gray-500',
            'progress_class' => 'bg-blue-400',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ]
    ];
    
    $config = $type_config[$options['type']] ?? $type_config['info'];
    
    $html = '<div id="' . $options['id'] . '" 
                  x-data="{ 
                      show: false, 
                      progress: 100,
                      init() {
                          this.show = true;
                          if (' . $options['duration'] . ' > 0) {
                              let startTime = Date.now();
                              let duration = ' . $options['duration'] . ';
                              let updateProgress = () => {
                                  let elapsed = Date.now() - startTime;
                                  this.progress = Math.max(0, 100 - (elapsed / duration * 100));
                                  if (elapsed < duration) {
                                      requestAnimationFrame(updateProgress);
                                  } else {
                                      this.show = false;
                                  }
                              };
                              requestAnimationFrame(updateProgress);
                          }
                      }
                  }"
                  x-show="show"
                  x-transition:enter="transform ease-out duration-300 transition"
                  x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                  x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                  x-transition:leave="transition ease-in duration-100"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="fixed z-50 ' . $position_class . ' m-6 max-w-sm w-full shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">';
    
    $html .= '<div class="' . $config['bg_class'] . ' ' . $config['border_class'] . '">';
    
    $html .= '<div class="p-4">';
    $html .= '<div class="flex items-start">';
    
    // Icon
    $html .= '<div class="flex-shrink-0">';
    $html .= '<svg class="h-6 w-6 ' . $config['icon_class'] . '" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
    $html .= $config['icon_path'];
    $html .= '</svg>';
    $html .= '</div>';
    
    // Content
    $html .= '<div class="ml-3 w-0 flex-1 pt-0.5">';
    
    if ($options['title']) {
        $html .= '<p class="text-sm font-medium ' . $config['title_class'] . '">';
        $html .= htmlspecialchars($options['title']);
        $html .= '</p>';
    }
    
    if ($options['message']) {
        $html .= '<p class="mt-1 text-sm ' . $config['message_class'] . '">';
        $html .= htmlspecialchars($options['message']);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    // Close button
    $html .= '<div class="ml-4 flex-shrink-0 flex">';
    $html .= '<button class="rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" @click="show = false">';
    $html .= '<span class="sr-only">Cerrar</span>';
    $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
    $html .= '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />';
    $html .= '</svg>';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</div>';
    $html .= '</div>';
    
    // Progress bar
    if ($options['show_progress'] && $options['duration'] > 0) {
        $html .= '<div class="bg-gray-200 h-1">';
        $html .= '<div class="h-1 ' . $config['progress_class'] . ' transition-all duration-100 ease-linear" :style="\'width: \' + progress + \'%\'"></div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Banner alert (full width)
 */
function render_banner($options = []) {
    $defaults = [
        'type' => 'info',
        'message' => '',
        'link_text' => '',
        'link_href' => '',
        'dismissible' => true,
        'centered' => false
    ];
    
    $options = array_merge($defaults, $options);
    
    $type_config = [
        'success' => [
            'bg_class' => 'bg-green-600',
            'text_class' => 'text-white',
            'link_class' => 'text-green-200 underline hover:text-white',
            'button_class' => 'text-green-200 hover:text-white'
        ],
        'error' => [
            'bg_class' => 'bg-red-600',
            'text_class' => 'text-white',
            'link_class' => 'text-red-200 underline hover:text-white',
            'button_class' => 'text-red-200 hover:text-white'
        ],
        'warning' => [
            'bg_class' => 'bg-yellow-600',
            'text_class' => 'text-white',
            'link_class' => 'text-yellow-200 underline hover:text-white',
            'button_class' => 'text-yellow-200 hover:text-white'
        ],
        'info' => [
            'bg_class' => 'bg-copihue-600',
            'text_class' => 'text-white',
            'link_class' => 'text-copihue-200 underline hover:text-white',
            'button_class' => 'text-copihue-200 hover:text-white'
        ]
    ];
    
    $config = $type_config[$options['type']] ?? $type_config['info'];
    
    $html = '<div class="' . $config['bg_class'] . '"';
    
    if ($options['dismissible']) {
        $html .= ' x-data="{ show: true }" x-show="show"';
    }
    
    $html .= '>';
    
    $container_class = $options['centered'] ? 'max-w-7xl mx-auto' : '';
    
    $html .= '<div class="' . $container_class . ' py-3 px-3 sm:px-6 lg:px-8">';
    $html .= '<div class="flex items-center justify-between flex-wrap">';
    
    $html .= '<div class="w-0 flex-1 flex items-center">';
    $html .= '<p class="ml-3 font-medium ' . $config['text_class'] . ' truncate">';
    $html .= '<span class="md:hidden">';
    $html .= htmlspecialchars($options['message']);
    $html .= '</span>';
    $html .= '<span class="hidden md:inline">';
    $html .= htmlspecialchars($options['message']);
    $html .= '</span>';
    $html .= '</p>';
    $html .= '</div>';
    
    if ($options['link_text'] && $options['link_href']) {
        $html .= '<div class="order-3 mt-2 flex-shrink-0 w-full sm:order-2 sm:mt-0 sm:w-auto">';
        $html .= '<a href="' . htmlspecialchars($options['link_href']) . '" class="flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium ' . $config['link_class'] . '">';
        $html .= htmlspecialchars($options['link_text']);
        $html .= '</a>';
        $html .= '</div>';
    }
    
    if ($options['dismissible']) {
        $html .= '<div class="order-2 flex-shrink-0 sm:order-3 sm:ml-3">';
        $html .= '<button type="button" class="-mr-1 flex p-2 rounded-md hover:bg-black hover:bg-opacity-10 focus:outline-none focus:ring-2 focus:ring-white sm:-mr-2" @click="show = false">';
        $html .= '<span class="sr-only">Cerrar</span>';
        $html .= '<svg class="h-6 w-6 ' . $config['button_class'] . '" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Empty state component
 */
function render_empty_state($options = []) {
    $defaults = [
        'icon' => '',
        'title' => 'No hay elementos',
        'description' => '',
        'action_text' => '',
        'action_href' => '',
        'secondary_action_text' => '',
        'secondary_action_href' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    $html = '<div class="text-center py-12">';
    
    // Icon
    if ($options['icon']) {
        $html .= '<svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= $options['icon'];
        $html .= '</svg>';
    }
    
    // Title
    $html .= '<h3 class="mt-2 text-sm font-medium text-gray-900">';
    $html .= htmlspecialchars($options['title']);
    $html .= '</h3>';
    
    // Description
    if ($options['description']) {
        $html .= '<p class="mt-1 text-sm text-gray-500">';
        $html .= htmlspecialchars($options['description']);
        $html .= '</p>';
    }
    
    // Actions
    if ($options['action_text'] && $options['action_href']) {
        $html .= '<div class="mt-6">';
        $html .= '<a href="' . htmlspecialchars($options['action_href']) . '" class="btn-primary">';
        $html .= htmlspecialchars($options['action_text']);
        $html .= '</a>';
        
        if ($options['secondary_action_text'] && $options['secondary_action_href']) {
            $html .= '<a href="' . htmlspecialchars($options['secondary_action_href']) . '" class="ml-3 btn-secondary">';
            $html .= htmlspecialchars($options['secondary_action_text']);
            $html .= '</a>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>
