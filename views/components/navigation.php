<?php
/**
 * Componentes de navegación reutilizables
 */

/**
 * Breadcrumb navigation
 */
function render_breadcrumb($items = []) {
    if (empty($items)) {
        return '';
    }
    
    $html = '<nav class="flex mb-6" aria-label="Breadcrumb">';
    $html .= '<ol role="list" class="flex items-center space-x-4">';
    
    foreach ($items as $index => $item) {
        $is_last = ($index === count($items) - 1);
        
        $html .= '<li>';
        
        if ($index > 0) {
            // Separador
            $html .= '<div class="flex items-center">';
            $html .= '<svg class="flex-shrink-0 h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">';
            $html .= '<path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />';
            $html .= '</svg>';
        }
        
        if ($is_last) {
            // Último elemento (actual)
            $html .= '<span class="ml-4 text-sm font-medium text-gray-500" aria-current="page">';
            $html .= htmlspecialchars($item['name']);
            $html .= '</span>';
        } else {
            // Enlaces
            $html .= '<a href="' . htmlspecialchars($item['href']) . '" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">';
            $html .= htmlspecialchars($item['name']);
            $html .= '</a>';
        }
        
        if ($index > 0) {
            $html .= '</div>';
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Pagination component
 */
function render_pagination($options = []) {
    $defaults = [
        'current_page' => 1,
        'total_pages' => 1,
        'base_url' => '',
        'query_params' => [],
        'show_first_last' => true,
        'show_prev_next' => true,
        'max_links' => 7
    ];
    
    $options = array_merge($defaults, $options);
    
    if ($options['total_pages'] <= 1) {
        return '';
    }
    
    $current = $options['current_page'];
    $total = $options['total_pages'];
    $base_url = $options['base_url'];
    $query_params = $options['query_params'];
    
    // Función para generar URL
    $generate_url = function($page) use ($base_url, $query_params) {
        $params = array_merge($query_params, ['page' => $page]);
        return $base_url . '?' . http_build_query($params);
    };
    
    $html = '<nav class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" aria-label="Pagination">';
    
    // Mobile: Previous/Next only
    $html .= '<div class="flex-1 flex justify-between sm:hidden">';
    
    if ($current > 1) {
        $html .= '<a href="' . $generate_url($current - 1) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">';
        $html .= 'Anterior';
        $html .= '</a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">';
        $html .= 'Anterior';
        $html .= '</span>';
    }
    
    if ($current < $total) {
        $html .= '<a href="' . $generate_url($current + 1) . '" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">';
        $html .= 'Siguiente';
        $html .= '</a>';
    } else {
        $html .= '<span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">';
        $html .= 'Siguiente';
        $html .= '</span>';
    }
    
    $html .= '</div>';
    
    // Desktop: Full pagination
    $html .= '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
    
    // Info text
    $start = ($current - 1) * 10 + 1; // Asumiendo 10 items por página
    $end = min($current * 10, $total * 10);
    $html .= '<div>';
    $html .= '<p class="text-sm text-gray-700">';
    $html .= 'Mostrando <span class="font-medium">' . $start . '</span> a <span class="font-medium">' . $end . '</span> de <span class="font-medium">' . ($total * 10) . '</span> resultados';
    $html .= '</p>';
    $html .= '</div>';
    
    // Page links
    $html .= '<div>';
    $html .= '<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';
    
    // Previous button
    if ($options['show_prev_next']) {
        if ($current > 1) {
            $html .= '<a href="' . $generate_url($current - 1) . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">';
            $html .= '<span class="sr-only">Anterior</span>';
            $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            $html .= '<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />';
            $html .= '</svg>';
            $html .= '</a>';
        } else {
            $html .= '<span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">';
            $html .= '<span class="sr-only">Anterior</span>';
            $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            $html .= '<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />';
            $html .= '</svg>';
            $html .= '</span>';
        }
    }
    
    // First page
    if ($options['show_first_last'] && $current > 3) {
        $html .= '<a href="' . $generate_url(1) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">';
        $html .= '1';
        $html .= '</a>';
        
        if ($current > 4) {
            $html .= '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">';
            $html .= '...';
            $html .= '</span>';
        }
    }
    
    // Page numbers around current
    $start_page = max(1, $current - 2);
    $end_page = min($total, $current + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current) {
            $html .= '<span aria-current="page" class="z-10 bg-copihue-50 border-copihue-500 text-copihue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">';
            $html .= $i;
            $html .= '</span>';
        } else {
            $html .= '<a href="' . $generate_url($i) . '" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">';
            $html .= $i;
            $html .= '</a>';
        }
    }
    
    // Last page
    if ($options['show_first_last'] && $current < $total - 2) {
        if ($current < $total - 3) {
            $html .= '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">';
            $html .= '...';
            $html .= '</span>';
        }
        
        $html .= '<a href="' . $generate_url($total) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">';
        $html .= $total;
        $html .= '</a>';
    }
    
    // Next button
    if ($options['show_prev_next']) {
        if ($current < $total) {
            $html .= '<a href="' . $generate_url($current + 1) . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">';
            $html .= '<span class="sr-only">Siguiente</span>';
            $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            $html .= '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />';
            $html .= '</svg>';
            $html .= '</a>';
        } else {
            $html .= '<span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">';
            $html .= '<span class="sr-only">Siguiente</span>';
            $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
            $html .= '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />';
            $html .= '</svg>';
            $html .= '</span>';
        }
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Tab navigation
 */
function render_tabs($tabs = [], $active_tab = '') {
    if (empty($tabs)) {
        return '';
    }
    
    $html = '<div>';
    $html .= '<div class="sm:hidden">';
    
    // Mobile select dropdown
    $html .= '<label for="tabs" class="sr-only">Seleccionar pestaña</label>';
    $html .= '<select id="tabs" name="tabs" class="block w-full focus:ring-copihue-500 focus:border-copihue-500 border-gray-300 rounded-md" onchange="window.location.href=this.value">';
    
    foreach ($tabs as $tab) {
        $selected = ($tab['key'] === $active_tab) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($tab['href']) . '" ' . $selected . '>';
        $html .= htmlspecialchars($tab['name']);
        $html .= '</option>';
    }
    
    $html .= '</select>';
    $html .= '</div>';
    
    // Desktop tabs
    $html .= '<div class="hidden sm:block">';
    $html .= '<div class="border-b border-gray-200">';
    $html .= '<nav class="-mb-px flex space-x-8" aria-label="Tabs">';
    
    foreach ($tabs as $tab) {
        $is_active = ($tab['key'] === $active_tab);
        $class = $is_active ? 
            'border-copihue-500 text-copihue-600' : 
            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
        
        $html .= '<a href="' . htmlspecialchars($tab['href']) . '" class="' . $class . ' whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"';
        
        if ($is_active) {
            $html .= ' aria-current="page"';
        }
        
        $html .= '>';
        
        if (isset($tab['icon'])) {
            $html .= '<svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= $tab['icon'];
            $html .= '</svg>';
        }
        
        $html .= htmlspecialchars($tab['name']);
        
        if (isset($tab['count'])) {
            $count_class = $is_active ? 'bg-copihue-100 text-copihue-600' : 'bg-gray-100 text-gray-900';
            $html .= '<span class="' . $count_class . ' hidden ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium md:inline-block">';
            $html .= $tab['count'];
            $html .= '</span>';
        }
        
        $html .= '</a>';
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Steps navigation (wizard)
 */
function render_steps($steps = [], $current_step = 1) {
    if (empty($steps)) {
        return '';
    }
    
    $html = '<nav aria-label="Progress">';
    $html .= '<ol role="list" class="space-y-4 md:flex md:space-y-0 md:space-x-8">';
    
    foreach ($steps as $index => $step) {
        $step_number = $index + 1;
        $is_current = ($step_number === $current_step);
        $is_completed = ($step_number < $current_step);
        $is_upcoming = ($step_number > $current_step);
        
        $html .= '<li class="md:flex-1">';
        
        if ($is_completed) {
            // Completed step
            $html .= '<div class="group pl-4 py-2 flex flex-col border-l-4 border-copihue-600 hover:border-copihue-800 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4">';
            $html .= '<span class="text-xs text-copihue-600 font-semibold tracking-wide uppercase group-hover:text-copihue-800">';
            $html .= 'Paso ' . $step_number;
            $html .= '</span>';
            $html .= '<span class="text-sm font-medium">';
            $html .= htmlspecialchars($step['name']);
            $html .= '</span>';
            $html .= '</div>';
        } elseif ($is_current) {
            // Current step
            $html .= '<div class="pl-4 py-2 flex flex-col border-l-4 border-copihue-600 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4" aria-current="step">';
            $html .= '<span class="text-xs text-copihue-600 font-semibold tracking-wide uppercase">';
            $html .= 'Paso ' . $step_number;
            $html .= '</span>';
            $html .= '<span class="text-sm font-medium">';
            $html .= htmlspecialchars($step['name']);
            $html .= '</span>';
            $html .= '</div>';
        } else {
            // Upcoming step
            $html .= '<div class="group pl-4 py-2 flex flex-col border-l-4 border-gray-200 hover:border-gray-300 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4">';
            $html .= '<span class="text-xs text-gray-500 font-semibold tracking-wide uppercase group-hover:text-gray-700">';
            $html .= 'Paso ' . $step_number;
            $html .= '</span>';
            $html .= '<span class="text-sm font-medium">';
            $html .= htmlspecialchars($step['name']);
            $html .= '</span>';
            $html .= '</div>';
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Sidebar navigation
 */
function render_sidebar_nav($nav_items = [], $current_path = '') {
    if (empty($nav_items)) {
        return '';
    }
    
    $html = '<nav class="space-y-1" aria-label="Sidebar">';
    
    foreach ($nav_items as $item) {
        $is_active = ($item['href'] === $current_path || 
                     (isset($item['active_pattern']) && preg_match($item['active_pattern'], $current_path)));
        
        $base_class = 'group flex items-center px-2 py-2 text-sm font-medium rounded-md';
        $active_class = $is_active ? 
            'bg-copihue-100 text-copihue-900' : 
            'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        
        $html .= '<a href="' . htmlspecialchars($item['href']) . '" class="' . $base_class . ' ' . $active_class . '"';
        
        if ($is_active) {
            $html .= ' aria-current="page"';
        }
        
        $html .= '>';
        
        // Icon
        if (isset($item['icon'])) {
            $icon_class = $is_active ? 'text-copihue-500' : 'text-gray-400 group-hover:text-gray-500';
            $html .= '<svg class="' . $icon_class . ' mr-3 flex-shrink-0 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= $item['icon'];
            $html .= '</svg>';
        }
        
        $html .= htmlspecialchars($item['name']);
        
        // Badge/count
        if (isset($item['count']) && $item['count'] > 0) {
            $badge_class = $is_active ? 
                'bg-white ml-3 inline-block py-0.5 px-3 text-xs font-medium rounded-full' : 
                'bg-gray-100 ml-3 inline-block py-0.5 px-3 text-xs font-medium rounded-full';
            $html .= '<span class="' . $badge_class . '">';
            $html .= $item['count'];
            $html .= '</span>';
        }
        
        $html .= '</a>';
    }
    
    $html .= '</nav>';
    
    return $html;
}
?>