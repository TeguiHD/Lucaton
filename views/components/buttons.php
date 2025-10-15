<?php
/**
 * Componente de botón reutilizable
 * 
 * @param string $text - Texto del botón
 * @param string $type - Tipo de botón (primary, secondary, danger, success, outline)
 * @param string $size - Tamaño (sm, md, lg, xl)
 * @param string $href - URL si es un enlace
 * @param string $onclick - Función JavaScript onclick
 * @param bool $disabled - Si el botón está deshabilitado
 * @param string $icon - Icono SVG (opcional)
 * @param string $icon_position - Posición del icono (left, right)
 * @param array $attributes - Atributos HTML adicionales
 */

function render_button($options = []) {
    // Valores por defecto
    $defaults = [
        'text' => 'Botón',
        'type' => 'primary',
        'size' => 'md',
        'href' => null,
        'onclick' => null,
        'disabled' => false,
        'icon' => null,
        'icon_position' => 'left',
        'attributes' => [],
        'form_type' => 'button', // button, submit, reset
        'loading' => false,
        'full_width' => false
    ];
    
    $options = array_merge($defaults, $options);
    
    // Clases base según el tipo
    $type_classes = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary', 
        'danger' => 'btn-danger',
        'success' => 'btn-success',
        'outline' => 'btn-outline',
        'ghost' => 'btn-ghost'
    ];
    
    // Clases de tamaño
    $size_classes = [
        'xs' => 'px-2.5 py-1.5 text-xs',
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-4 py-2 text-base',
        'xl' => 'px-6 py-3 text-base'
    ];
    
    // Construir clases CSS
    $classes = [
        $type_classes[$options['type']] ?? $type_classes['primary'],
        $size_classes[$options['size']] ?? $size_classes['md']
    ];
    
    if ($options['full_width']) {
        $classes[] = 'w-full';
    }
    
    if ($options['disabled']) {
        $classes[] = 'opacity-50 cursor-not-allowed';
    }
    
    if ($options['loading']) {
        $classes[] = 'relative';
    }
    
    $class_string = implode(' ', $classes);
    
    // Construir atributos
    $attributes = $options['attributes'];
    $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' ' . $class_string : $class_string;
    
    if ($options['onclick']) {
        $attributes['onclick'] = $options['onclick'];
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
        $attributes['aria-disabled'] = 'true';
    }
    
    // Construir string de atributos
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    // Contenido del botón
    $content = '';
    
    // Loading spinner
    if ($options['loading']) {
        $content .= '<span class="absolute inset-0 flex items-center justify-center">';
        $content .= '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">';
        $content .= '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>';
        $content .= '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>';
        $content .= '</svg>';
        $content .= '</span>';
        $content .= '<span class="' . ($options['loading'] ? 'invisible' : '') . '">';
    }
    
    // Icono izquierdo
    if ($options['icon'] && $options['icon_position'] === 'left') {
        $content .= '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">';
        $content .= $options['icon'];
        $content .= '</svg>';
    }
    
    // Texto
    $content .= htmlspecialchars($options['text']);
    
    // Icono derecho
    if ($options['icon'] && $options['icon_position'] === 'right') {
        $content .= '<svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">';
        $content .= $options['icon'];
        $content .= '</svg>';
    }
    
    if ($options['loading']) {
        $content .= '</span>';
    }
    
    // Renderizar como enlace o botón
    if ($options['href']) {
        return "<a href=\"{$options['href']}\"$attr_string>$content</a>";
    } else {
        $type_attr = $options['form_type'] ? ' type="' . htmlspecialchars($options['form_type']) . '"' : '';
        return "<button$type_attr$attr_string>$content</button>";
    }
}

// Funciones helper para tipos específicos de botones
function primary_button($text, $options = []) {
    return render_button(array_merge(['text' => $text, 'type' => 'primary'], $options));
}

function secondary_button($text, $options = []) {
    return render_button(array_merge(['text' => $text, 'type' => 'secondary'], $options));
}

function danger_button($text, $options = []) {
    return render_button(array_merge(['text' => $text, 'type' => 'danger'], $options));
}

function success_button($text, $options = []) {
    return render_button(array_merge(['text' => $text, 'type' => 'success'], $options));
}

function outline_button($text, $options = []) {
    return render_button(array_merge(['text' => $text, 'type' => 'outline'], $options));
}

// Iconos comunes
$common_icons = [
    'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />',
    'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />',
    'delete' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />',
    'save' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3-3-3m3-3v12" />',
    'cancel' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
    'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />',
    'filter' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />',
    'download' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
    'upload' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />',
    'external' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />'
];
?>

<!-- Ejemplos de uso de los componentes de botones -->
<?php if (false): // Solo para documentación ?>
<div class="space-y-4 p-6">
    <h3 class="text-lg font-semibold">Ejemplos de Botones</h3>
    
    <!-- Botones primarios -->
    <div class="space-x-2">
        <?= primary_button('Crear Campaña', ['icon' => $common_icons['plus']]) ?>
        <?= secondary_button('Cancelar', ['icon' => $common_icons['cancel']]) ?>
        <?= danger_button('Eliminar', ['icon' => $common_icons['delete']]) ?>
        <?= success_button('Guardar', ['icon' => $common_icons['save']]) ?>
    </div>
    
    <!-- Diferentes tamaños -->
    <div class="space-x-2">
        <?= primary_button('Extra Small', ['size' => 'xs']) ?>
        <?= primary_button('Small', ['size' => 'sm']) ?>
        <?= primary_button('Medium', ['size' => 'md']) ?>
        <?= primary_button('Large', ['size' => 'lg']) ?>
        <?= primary_button('Extra Large', ['size' => 'xl']) ?>
    </div>
    
    <!-- Estados especiales -->
    <div class="space-x-2">
        <?= primary_button('Deshabilitado', ['disabled' => true]) ?>
        <?= primary_button('Cargando', ['loading' => true]) ?>
        <?= primary_button('Ancho completo', ['full_width' => true]) ?>
    </div>
    
    <!-- Como enlaces -->
    <div class="space-x-2">
        <?= primary_button('Ir a Campañas', ['href' => '/campanas', 'icon' => $common_icons['external']]) ?>
    </div>
</div>
<?php endif; ?>
