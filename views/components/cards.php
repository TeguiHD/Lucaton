<?php
/**
 * Componentes de tarjetas reutilizables
 */

/**
 * Tarjeta de campaña
 */
function render_campaign_card($campaign, $options = []) {
    $defaults = [
        'show_progress' => true,
        'show_creator' => true,
        'show_category' => true,
        'show_actions' => true,
        'card_class' => '',
        'link_class' => '',
        'compact' => false,
        'show_id' => true,
    ];

    $options = array_merge($defaults, $options);

    $goal_amount = (float)($campaign['goal_amount'] ?? ($campaign['goal'] ?? 0));
    $raised_amount = (float)($campaign['raised_amount'] ?? ($campaign['current_amount'] ?? ($campaign['raised'] ?? 0)));

    $imageCandidates = [
        $campaign['image_url'] ?? null,
        $campaign['cover_image_url'] ?? null,
        $campaign['featured_image_url'] ?? null,
        $campaign['featured_image'] ?? null,
        $campaign['banner_image_url'] ?? null,
        $campaign['banner_url'] ?? null,
        $campaign['main_image_url'] ?? null,
        $campaign['image'] ?? null,
        $campaign['owner_avatar'] ?? null,
        $campaign['creator_avatar'] ?? null,
    ];

    $image_url = null;
    foreach ($imageCandidates as $candidate) {
        if ($candidate === null || $candidate === '') {
            continue;
        }

        $normalized = CampaignMediaUploadService::normalizePublicUrl($candidate);
        if ($normalized !== null) {
            $image_url = $normalized;
            break;
        }
    }

    if ($image_url === null) {
        $image_url = APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';
    }
    $creator_name = $campaign['owner_name']
        ?? $campaign['creator_name']
        ?? $campaign['creator']
        ?? '';
    $creator_avatar = $campaign['owner_avatar'] ?? $campaign['creator_avatar'] ?? null;

    $slug = $campaign['slug'] ?? null;
    if ($slug === null) {
        if (!empty($campaign['id'])) {
            $slug = (string)$campaign['id'];
        } elseif (!empty($campaign['title'])) {
            $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $campaign['title'])), '-');
        } else {
            $slug = 'detalle';
        }
    }
    $detail_url = Router::url('campana/' . rawurlencode($slug));

    $progress_percentage = $goal_amount > 0 ? min(100, ($raised_amount / $goal_amount) * 100) : 0;

    $status = $campaign['status'] ?? 'draft';
    $status_meta = CampaignPresenter::statusMeta($status);
    $status_badge_class = $status_meta['badge_class'] ?? 'bg-gray-100 text-gray-700';
    $status_label = $status_meta['label'] ?? 'Sin estado';

    $days_left = $campaign['days_left'] ?? null;
    if ($days_left === null && !empty($campaign['end_date']) && is_string($campaign['end_date'])) {
        try {
            $end_date = new DateTime($campaign['end_date']);
            $days_left = max(0, $end_date->diff(new DateTime())->days);
        } catch (Exception $e) {
            $days_left = null;
        }
    }

    $category_label = $campaign['category_name'] ?? $campaign['category'] ?? null;
    $summary_text = $campaign['summary'] ?? $campaign['description'] ?? '';

    $card_classes = 'bg-white rounded-xl shadow-soft hover:shadow-strong transform hover:-translate-y-0.5 hover:scale-[1.01] transition-all duration-300 overflow-hidden relative group focus-within:ring-2 focus-within:ring-copihue-500 focus-within:ring-offset-2 focus-within:ring-offset-white';
    if (!empty($options['card_class'])) {
        $card_classes .= ' ' . $options['card_class'];
    }
    if (!empty($options['compact'])) {
        $card_classes .= ' max-w-sm';
    }

    $cardAttributes = [
        'class' => $card_classes . ' cursor-pointer',
        'data-campaign-link' => $detail_url,
        'tabindex' => '0',
        'role' => 'link',
        'aria-label' => 'Ver campaña ' . ($campaign['title'] ?? ''),
    ];

    $attributeString = '';
    foreach ($cardAttributes as $attr => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $attributeString .= ' ' . $attr . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }

    $html = '<article' . $attributeString . '>';

    if (!empty($image_url)) {
        $html .= '<div class="relative">';
        $html .= '<img class="w-full h-48 object-cover" src="' . htmlspecialchars($image_url) . '" alt="Imagen de la campaña ' . htmlspecialchars($campaign['title']) . '">';

        $html .= '<div class="absolute top-2 right-2">';
        $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . htmlspecialchars($status_badge_class) . '">';
        $html .= htmlspecialchars($status_label);
        $html .= '</span>';
        $html .= '</div>';

        if (!empty($options['show_category']) && !empty($category_label)) {
            $html .= '<div class="absolute top-2 left-2">';
            $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/90 text-gray-800">';
            $html .= htmlspecialchars($category_label);
            $html .= '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= '<div class="p-6">';

    $html .= '<div class="mb-4">';
    $html .= '<h3 class="text-lg font-semibold text-gray-900 mb-1 line-clamp-2">';
    $html .= '<a href="' . htmlspecialchars($detail_url) . '" class="hover:text-copihue-600 transition-colors duration-200">';
    $html .= htmlspecialchars($campaign['title']);
    $html .= '</a>';
    $html .= '</h3>';

    if (!empty($options['show_id']) && !empty($campaign['id'])) {
        $html .= '<p class="text-xs font-medium uppercase tracking-wide text-gray-400">ID #' . (int)$campaign['id'] . '</p>';
    }

    if (empty($options['compact']) && $summary_text !== '') {
        $excerpt = mb_substr($summary_text, 0, 150);
        if (mb_strlen($summary_text) > 150) {
            $excerpt .= '...';
        }
        $html .= '<p class="text-gray-600 text-sm line-clamp-3">' . htmlspecialchars($excerpt) . '</p>';
    }
    $html .= '</div>';

    if (!empty($options['show_creator']) && $creator_name !== '') {
        $html .= '<div class="flex items-center mb-4">';
        if (!empty($creator_avatar)) {
            $html .= '<img class="h-8 w-8 rounded-full mr-2" src="' . htmlspecialchars($creator_avatar) . '" alt="' . htmlspecialchars($creator_name) . '">';
        } else {
            $html .= '<div class="h-8 w-8 rounded-full bg-copihue-500 flex items-center justify-center mr-2">';
            $html .= '<span class="text-xs font-medium text-white">' . htmlspecialchars(mb_strtoupper(mb_substr($creator_name, 0, 1))) . '</span>';
            $html .= '</div>';
        }
        $html .= '<span class="text-sm text-gray-600">por ' . htmlspecialchars($creator_name) . '</span>';
        $html .= '</div>';
    }

    if (!empty($options['show_progress'])) {
        $html .= '<div class="mb-4">';
        $html .= '<div class="w-full bg-gray-200 rounded-full h-2 mb-2 overflow-hidden">';
        $html .= '<div class="bg-gradient-to-r from-copihue-500 to-copihue-600 h-2 rounded-full transition-all duration-500 ease-out" style="width: ' . min(100, max(0, $progress_percentage)) . '%"></div>';
        $html .= '</div>';

        $html .= '<div class="flex justify-between text-sm">';
        $html .= '<div><span class="font-semibold text-gray-900">$' . number_format($raised_amount, 0, ',', '.') . '</span><span class="text-gray-600"> recaudados</span></div>';
        $html .= '<div class="text-gray-600">' . number_format($progress_percentage, 1) . '%</div>';
        $html .= '</div>';

        $html .= '<div class="flex justify-between text-xs text-gray-500 mt-1">';
        $html .= '<span>Meta: $' . number_format($goal_amount, 0, ',', '.') . '</span>';
        if (in_array($status, ['published', 'active'], true) && $days_left !== null) {
            $html .= '<span>' . max(0, (int)$days_left) . ' días restantes</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }

    if (!empty($options['show_actions'])) {
        $html .= '<div class="flex space-x-2 mt-4">';
        if (in_array($status, ['published', 'active'], true)) {
            $html .= '<a href="' . htmlspecialchars($detail_url) . '" class="btn-primary flex-1 text-center">Apoyar Proyecto</a>';
        } else {
            $html .= '<a href="' . htmlspecialchars($detail_url) . '" class="btn-outline flex-1 text-center">Ver Detalles</a>';
        }

        $share_target = $slug ?? ($campaign['id'] ?? '');
        $share_payload = [
            'slug' => $share_target,
            'title' => $campaign['title'] ?? 'Campaña Lucatón'
        ];
        $share_attr = htmlspecialchars(json_encode($share_payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $html .= '<button type="button" class="btn-ghost p-2" onclick="shareCampaign(' . $share_attr . ')" title="Compartir">';
        $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path></svg>';
        $html .= '</button>';

        $favorite_target = $campaign['id'] ?? $share_target;
        $favorite_payload = [
            'id' => $favorite_target,
            'slug' => $share_target,
            'title' => $campaign['title'] ?? 'Campaña Lucatón'
        ];
        $favorite_attr = htmlspecialchars(json_encode($favorite_payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $favoriteIdAttr = htmlspecialchars((string)$favorite_target, ENT_QUOTES, 'UTF-8');
        $html .= '<button type="button" class="btn-ghost p-2" data-favorite-button data-favorite-id="' . $favoriteIdAttr . '" onclick="toggleFavorite(event, ' . $favorite_attr . ')" title="Guardar campaña" aria-pressed="false">';
        $html .= '<svg class="w-5 h-5 transition-colors" data-favorite-icon fill="none" stroke="currentColor" viewBox="0 0 24 24"><path data-favorite-path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>';
        $html .= '</button>';

        $html .= '</div>';
    }


    $html .= '</div>';
    $html .= '</article>';

    return $html;
}


/**
 * Tarjeta simple de contenido
 */
function render_content_card($options = []) {
    $defaults = [
        'title' => '',
        'content' => '',
        'image' => '',
        'link' => '',
        'link_text' => 'Leer más',
        'card_class' => '',
        'show_image' => true,
        'show_footer' => true
    ];
    
    $options = array_merge($defaults, $options);
    
    $card_classes = 'bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden' . 
                   ($options['card_class'] ? ' ' . $options['card_class'] : '');
    
    $html = '<div class="' . $card_classes . '">';
    
    // Imagen
    if ($options['show_image'] && $options['image']) {
        $html .= '<div class="aspect-w-16 aspect-h-9">';
        $html .= '<img class="w-full h-48 object-cover" src="' . htmlspecialchars($options['image']) . '" alt="' . htmlspecialchars($options['title']) . '">';
        $html .= '</div>';
    }
    
    // Contenido
    $html .= '<div class="p-6">';
    
    if ($options['title']) {
        $html .= '<h3 class="text-lg font-semibold text-gray-900 mb-3">';
        if ($options['link']) {
            $html .= '<a href="' . htmlspecialchars($options['link']) . '" class="hover:text-copihue-600 transition-colors duration-200">';
            $html .= htmlspecialchars($options['title']);
            $html .= '</a>';
        } else {
            $html .= htmlspecialchars($options['title']);
        }
        $html .= '</h3>';
    }
    
    if ($options['content']) {
        $html .= '<p class="text-gray-600 mb-4">';
        $html .= htmlspecialchars($options['content']);
        $html .= '</p>';
    }
    
    // Footer con enlace
    if ($options['show_footer'] && $options['link']) {
        $html .= '<div class="flex justify-end">';
        $html .= '<a href="' . htmlspecialchars($options['link']) . '" class="text-copihue-600 hover:text-copihue-700 text-sm font-medium transition-colors duration-200">';
        $html .= htmlspecialchars($options['link_text']);
        $html .= ' →';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Tarjeta de estadística
 */
function render_stat_card($options = []) {
    $defaults = [
        'title' => '',
        'value' => '',
        'description' => '',
        'icon' => '',
        'color' => 'copihue', // copihue, marino, pacifico, green, red, yellow
        'trend' => null, // 'up', 'down', null
        'trend_value' => '',
        'card_class' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // Colores según el tipo
    $color_classes = [
        'copihue' => 'text-copihue-600 bg-copihue-50',
        'marino' => 'text-marino-600 bg-marino-50',
        'pacifico' => 'text-pacifico-600 bg-pacifico-50',
        'green' => 'text-green-600 bg-green-50',
        'red' => 'text-red-600 bg-red-50',
        'yellow' => 'text-yellow-600 bg-yellow-50'
    ];
    
    $color_class = $color_classes[$options['color']] ?? $color_classes['copihue'];
    
    $card_classes = 'bg-white rounded-lg shadow-md p-6' . 
                   ($options['card_class'] ? ' ' . $options['card_class'] : '');
    
    $html = '<div class="' . $card_classes . '">';
    
    // Header con icono
    $html .= '<div class="flex items-center">';
    
    if ($options['icon']) {
        $html .= '<div class="flex-shrink-0">';
        $html .= '<div class="w-8 h-8 ' . $color_class . ' rounded-md flex items-center justify-center">';
        $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= $options['icon'];
        $html .= '</svg>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="ml-5 w-0 flex-1">';
    } else {
        $html .= '<div class="w-full">';
    }
    
    // Título
    if ($options['title']) {
        $html .= '<dl>';
        $html .= '<dt class="text-sm font-medium text-gray-500 truncate">';
        $html .= htmlspecialchars($options['title']);
        $html .= '</dt>';
        
        // Valor principal
        $html .= '<dd class="flex items-baseline">';
        $html .= '<div class="text-2xl font-semibold text-gray-900">';
        $html .= htmlspecialchars($options['value']);
        $html .= '</div>';
        
        // Tendencia
        if ($options['trend'] && $options['trend_value']) {
            $trend_class = $options['trend'] === 'up' ? 'text-green-600' : 'text-red-600';
            $trend_icon = $options['trend'] === 'up' ? 
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>' :
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>';
            
            $html .= '<div class="ml-2 flex items-baseline text-sm font-semibold ' . $trend_class . '">';
            $html .= '<svg class="self-center flex-shrink-0 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= $trend_icon;
            $html .= '</svg>';
            $html .= '<span class="sr-only">' . ($options['trend'] === 'up' ? 'Aumentó' : 'Disminuyó') . ' en </span>';
            $html .= htmlspecialchars($options['trend_value']);
            $html .= '</div>';
        }
        
        $html .= '</dd>';
        $html .= '</dl>';
    }
    
    // Descripción
    if ($options['description']) {
        $html .= '<p class="mt-1 text-sm text-gray-500">';
        $html .= htmlspecialchars($options['description']);
        $html .= '</p>';
    }
    
    $html .= '</div>'; // Cierre del contenedor de contenido
    $html .= '</div>'; // Cierre del header
    $html .= '</div>'; // Cierre de la tarjeta
    
    return $html;
}
?>
