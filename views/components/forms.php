<?php
/**
 * Componentes de formularios reutilizables
 */

/**
 * Campo de entrada de texto
 */
function render_input($options = []) {
    $defaults = [
        'type' => 'text',
        'name' => '',
        'id' => '',
        'label' => '',
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'error' => '',
        'help_text' => '',
        'attributes' => [],
        'wrapper_class' => '',
        'label_class' => '',
        'input_class' => '',
        'autocomplete' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // Generar ID si no se proporciona
    if (empty($options['id'])) {
        $options['id'] = 'input_' . uniqid();
    }
    
    // Clases CSS
    $base_input_class = 'form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm';
    
    if ($options['error']) {
        $base_input_class = 'form-input block w-full rounded-md border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500 sm:text-sm';
    }
    
    if ($options['disabled']) {
        $base_input_class .= ' bg-gray-50 text-gray-500 cursor-not-allowed';
    }
    
    $input_class = $base_input_class . ($options['input_class'] ? ' ' . $options['input_class'] : '');
    
    // Construir atributos
    $attributes = array_merge([
        'type' => $options['type'],
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => $input_class,
        'value' => $options['value'],
        'placeholder' => $options['placeholder']
    ], $options['attributes']);
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    if ($options['readonly']) {
        $attributes['readonly'] = 'readonly';
    }
    
    if ($options['autocomplete']) {
        $attributes['autocomplete'] = $options['autocomplete'];
    }
    
    if ($options['error']) {
        $attributes['aria-invalid'] = 'true';
        $attributes['aria-describedby'] = $options['id'] . '_error';
    }
    
    if ($options['help_text']) {
        $attributes['aria-describedby'] = $options['id'] . '_help';
    }
    
    // Construir string de atributos
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if ($value !== '' && $value !== null) {
            $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    // Renderizar
    $html = '<div class="' . ($options['wrapper_class'] ?: 'mb-4') . '">';
    
    // Label
    if ($options['label']) {
        $label_class = 'block text-sm font-medium text-gray-700 mb-1' . ($options['label_class'] ? ' ' . $options['label_class'] : '');
        $html .= '<label for="' . htmlspecialchars($options['id']) . '" class="' . $label_class . '">';
        $html .= htmlspecialchars($options['label']);
        if ($options['required']) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        $html .= '</label>';
    }
    
    // Input
    $html .= '<input' . $attr_string . '>';
    
    // Error message
    if ($options['error']) {
        $html .= '<p class="mt-1 text-sm text-red-600" id="' . htmlspecialchars($options['id']) . '_error">';
        $html .= htmlspecialchars($options['error']);
        $html .= '</p>';
    }
    
    // Help text
    if ($options['help_text'] && !$options['error']) {
        $html .= '<p class="mt-1 text-sm text-gray-500" id="' . htmlspecialchars($options['id']) . '_help">';
        $html .= htmlspecialchars($options['help_text']);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// Aliases para compatibilidad con llamadas existentes
function render_text_input($options = []) {
    if (!isset($options['type'])) {
        $options['type'] = 'text';
    }
    
    // Si tiene icono, usar la versión con icono
    if (isset($options['icon']) && !empty($options['icon'])) {
        return render_input_with_icon($options);
    }
    
    return render_input($options);
}

/**
 * Campo de entrada de texto con icono
 */
function render_input_with_icon($options = []) {
    $defaults = [
        'type' => 'text',
        'name' => '',
        'id' => '',
        'label' => '',
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'error' => '',
        'help_text' => '',
        'icon' => '',
        'icon_position' => 'left',
        'attributes' => [],
        'wrapper_class' => '',
        'label_class' => '',
        'input_class' => '',
        'autocomplete' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // Generar ID si no se proporciona
    if (empty($options['id'])) {
        $options['id'] = 'input_' . uniqid();
    }
    
    // Clases CSS base para el input con icono (sin focus styles en el input)
    $base_input_class = 'form-input block w-full border-0 bg-transparent focus:ring-0 focus:outline-none sm:text-sm';
    
    // Ajustar padding según posición del icono
    if ($options['icon_position'] === 'left') {
        $base_input_class .= ' pl-10';
    } else {
        $base_input_class .= ' pr-10';
    }
    
    if ($options['error']) {
        $base_input_class .= ' text-red-900 placeholder-red-300';
    }
    
    if ($options['disabled']) {
        $base_input_class .= ' bg-gray-50 text-gray-500 cursor-not-allowed';
    }
    
    $input_class = $base_input_class . ($options['input_class'] ? ' ' . $options['input_class'] : '');
    
    // Clases para el contenedor con focus styles
    $container_class = 'relative rounded-md border shadow-sm transition-colors';
    
    if ($options['error']) {
        $container_class .= ' border-red-300 focus-within:border-red-500 focus-within:ring-1 focus-within:ring-red-500';
    } else {
        $container_class .= ' border-gray-300 focus-within:border-copihue-500 focus-within:ring-1 focus-within:ring-copihue-500';
    }
    
    if ($options['disabled']) {
        $container_class .= ' bg-gray-50';
    } else {
        $container_class .= ' bg-white';
    }
    
    // Construir atributos
    $attributes = array_merge([
        'type' => $options['type'],
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => $input_class,
        'value' => $options['value'],
        'placeholder' => $options['placeholder']
    ], $options['attributes']);
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    if ($options['readonly']) {
        $attributes['readonly'] = 'readonly';
    }
    
    if ($options['autocomplete']) {
        $attributes['autocomplete'] = $options['autocomplete'];
    }
    
    if ($options['error']) {
        $attributes['aria-invalid'] = 'true';
        $attributes['aria-describedby'] = $options['id'] . '_error';
    }
    
    if ($options['help_text']) {
        $attributes['aria-describedby'] = $options['id'] . '_help';
    }
    
    // Construir string de atributos
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if ($value !== '' && $value !== null) {
            $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    // Renderizar
    $html = '<div class="' . ($options['wrapper_class'] ?: 'mb-4') . '">';
    
    // Label
    if ($options['label']) {
        $label_class = 'block text-sm font-medium text-gray-700 mb-1' . ($options['label_class'] ? ' ' . $options['label_class'] : '');
        $html .= '<label for="' . htmlspecialchars($options['id']) . '" class="' . $label_class . '">';
        $html .= htmlspecialchars($options['label']);
        if ($options['required']) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        $html .= '</label>';
    }
    
    // Contenedor del input con icono
    $html .= '<div class="' . $container_class . '">';
    
    // Input
    $html .= '<input' . $attr_string . '>';
    
    // Icono
    if ($options['icon']) {
        $icon_position_class = $options['icon_position'] === 'left' ? 'left-0 pl-3' : 'right-0 pr-3';
        $icon_color_class = $options['error'] ? 'text-red-400' : 'text-gray-400';
        
        $html .= '<div class="absolute inset-y-0 ' . $icon_position_class . ' flex items-center pointer-events-none">';
        $html .= '<svg class="h-5 w-5 ' . $icon_color_class . '" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
        $html .= $options['icon'];
        $html .= '</svg>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Error message
    if ($options['error']) {
        $html .= '<p class="mt-1 text-sm text-red-600" id="' . htmlspecialchars($options['id']) . '_error">';
        $html .= htmlspecialchars($options['error']);
        $html .= '</p>';
    }
    
    // Help text
    if ($options['help_text'] && !$options['error']) {
        $html .= '<p class="mt-1 text-sm text-gray-500" id="' . htmlspecialchars($options['id']) . '_help">';
        $html .= htmlspecialchars($options['help_text']);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

function render_email_input($options = []) {
    $options['type'] = 'email';
    return render_input($options);
}

function render_password_input($options = []) {
    $options['type'] = 'password';
    return render_input($options);
}

/**
 * Campo de textarea
 */
function render_textarea($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'label' => '',
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'error' => '',
        'help_text' => '',
        'rows' => 4,
        'attributes' => [],
        'wrapper_class' => '',
        'label_class' => '',
        'textarea_class' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    if (empty($options['id'])) {
        $options['id'] = 'textarea_' . uniqid();
    }
    
    $base_class = 'form-textarea block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm';
    
    if ($options['error']) {
        $base_class = 'form-textarea block w-full rounded-md border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500 sm:text-sm';
    }
    
    if ($options['disabled']) {
        $base_class .= ' bg-gray-50 text-gray-500 cursor-not-allowed';
    }
    
    $textarea_class = $base_class . ($options['textarea_class'] ? ' ' . $options['textarea_class'] : '');
    
    $attributes = array_merge([
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => $textarea_class,
        'placeholder' => $options['placeholder'],
        'rows' => $options['rows']
    ], $options['attributes']);
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    if ($options['readonly']) {
        $attributes['readonly'] = 'readonly';
    }
    
    if ($options['error']) {
        $attributes['aria-invalid'] = 'true';
        $attributes['aria-describedby'] = $options['id'] . '_error';
    }
    
    if ($options['help_text']) {
        $attributes['aria-describedby'] = $options['id'] . '_help';
    }
    
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if ($value !== '' && $value !== null) {
            $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    $html = '<div class="' . ($options['wrapper_class'] ?: 'mb-4') . '">';
    
    if ($options['label']) {
        $label_class = 'block text-sm font-medium text-gray-700 mb-1' . ($options['label_class'] ? ' ' . $options['label_class'] : '');
        $html .= '<label for="' . htmlspecialchars($options['id']) . '" class="' . $label_class . '">';
        $html .= htmlspecialchars($options['label']);
        if ($options['required']) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        $html .= '</label>';
    }
    
    $html .= '<textarea' . $attr_string . '>' . htmlspecialchars($options['value']) . '</textarea>';
    
    if ($options['error']) {
        $html .= '<p class="mt-1 text-sm text-red-600" id="' . htmlspecialchars($options['id']) . '_error">';
        $html .= htmlspecialchars($options['error']);
        $html .= '</p>';
    }
    
    if ($options['help_text'] && !$options['error']) {
        $html .= '<p class="mt-1 text-sm text-gray-500" id="' . htmlspecialchars($options['id']) . '_help">';
        $html .= htmlspecialchars($options['help_text']);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Campo select
 */
function render_select($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'label' => '',
        'value' => '',
        'required' => false,
        'disabled' => false,
        'error' => '',
        'help_text' => '',
        'options' => [],
        'placeholder' => 'Seleccionar...',
        'attributes' => [],
        'wrapper_class' => '',
        'label_class' => '',
        'select_class' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    if (empty($options['id'])) {
        $options['id'] = 'select_' . uniqid();
    }
    
    $base_class = 'form-select block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm';
    
    if ($options['error']) {
        $base_class = 'form-select block w-full rounded-md border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 sm:text-sm';
    }
    
    if ($options['disabled']) {
        $base_class .= ' bg-gray-50 text-gray-500 cursor-not-allowed';
    }
    
    $select_class = $base_class . ($options['select_class'] ? ' ' . $options['select_class'] : '');
    
    $attributes = array_merge([
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => $select_class
    ], $options['attributes']);
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    if ($options['error']) {
        $attributes['aria-invalid'] = 'true';
        $attributes['aria-describedby'] = $options['id'] . '_error';
    }
    
    if ($options['help_text']) {
        $attributes['aria-describedby'] = $options['id'] . '_help';
    }
    
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if ($value !== '' && $value !== null) {
            $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    $html = '<div class="' . ($options['wrapper_class'] ?: 'mb-4') . '">';
    
    if ($options['label']) {
        $label_class = 'block text-sm font-medium text-gray-700 mb-1' . ($options['label_class'] ? ' ' . $options['label_class'] : '');
        $html .= '<label for="' . htmlspecialchars($options['id']) . '" class="' . $label_class . '">';
        $html .= htmlspecialchars($options['label']);
        if ($options['required']) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        $html .= '</label>';
    }
    
    $html .= '<select' . $attr_string . '>';
    
    if ($options['placeholder']) {
        $html .= '<option value="">' . htmlspecialchars($options['placeholder']) . '</option>';
    }
    
    foreach ($options['options'] as $value => $text) {
        $optionValue = $value;
        $label = $text;
        $disabledAttr = '';
        $extraAttr = '';

        if (is_array($text)) {
            $optionValue = array_key_exists('value', $text) ? $text['value'] : $value;
            $label = $text['label'] ?? ($text['value'] ?? '');
            if (!empty($text['disabled'])) {
                $disabledAttr = ' disabled';
            }
            if (!empty($text['attributes']) && is_array($text['attributes'])) {
                foreach ($text['attributes'] as $attrKey => $attrValue) {
                    if ($attrValue === '' || $attrValue === null) {
                        continue;
                    }
                    $extraAttr .= ' ' . htmlspecialchars($attrKey) . '="' . htmlspecialchars($attrValue) . '"';
                }
            }
        }

        $isSelected = ((string)$optionValue === (string)$options['value']) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars((string)$optionValue) . '"' . $isSelected . $disabledAttr . $extraAttr . '>';
        $html .= htmlspecialchars((string)$label);
        $html .= '</option>';
    }
    
    $html .= '</select>';
    
    if ($options['error']) {
        $html .= '<p class="mt-1 text-sm text-red-600" id="' . htmlspecialchars($options['id']) . '_error">';
        $html .= htmlspecialchars($options['error']);
        $html .= '</p>';
    }
    
    if ($options['help_text'] && !$options['error']) {
        $html .= '<p class="mt-1 text-sm text-gray-500" id="' . htmlspecialchars($options['id']) . '_help">';
        $html .= htmlspecialchars($options['help_text']);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Campo checkbox
 */
function render_checkbox($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'label' => '',
        'value' => '1',
        'checked' => false,
        'required' => false,
        'disabled' => false,
        'error' => '',
        'help_text' => '',
        'attributes' => [],
        'wrapper_class' => '',
        'label_class' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    if (empty($options['id'])) {
        $options['id'] = 'checkbox_' . uniqid();
    }
    
    $html = '<div class="' . ($options['wrapper_class'] ?: 'mb-4') . '">';
    $html .= '<div class="flex items-center">';
    
    $attributes = array_merge([
        'type' => 'checkbox',
        'name' => $options['name'],
        'id' => $options['id'],
        'value' => $options['value'],
        'class' => 'h-4 w-4 text-copihue-600 focus:ring-copihue-500 border-gray-300 rounded'
    ], $options['attributes']);
    
    if ($options['checked']) {
        $attributes['checked'] = 'checked';
    }
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
        $attributes['class'] .= ' bg-gray-50 cursor-not-allowed';
    }
    
    if ($options['error']) {
        $attributes['aria-invalid'] = 'true';
        $attributes['aria-describedby'] = $options['id'] . '_error';
    }
    
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if ($value !== '' && $value !== null) {
            $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    $html .= '<input' . $attr_string . '>';
    
    if ($options['label']) {
        $label_class = 'ml-2 block text-sm text-gray-900' . ($options['label_class'] ? ' ' . $options['label_class'] : '');
        $html .= '<label for="' . htmlspecialchars($options['id']) . '" class="' . $label_class . '">';
        $html .= htmlspecialchars($options['label']);
        if ($options['required']) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        $html .= '</label>';
    }
    
    $html .= '</div>';
    
    if ($options['error']) {
        $html .= '<p class="mt-1 text-sm text-red-600" id="' . htmlspecialchars($options['id']) . '_error">';
        $html .= htmlspecialchars($options['error']);
        $html .= '</p>';
    }
    
    if ($options['help_text'] && !$options['error']) {
        $html .= '<p class="mt-1 text-sm text-gray-500">';
        $html .= htmlspecialchars($options['help_text']);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Campo de archivo
 */
function render_file_input($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'label' => '',
        'required' => false,
        'disabled' => false,
        'error' => '',
        'help_text' => '',
        'accept' => '',
        'multiple' => false,
        'attributes' => [],
        'wrapper_class' => '',
        'label_class' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    if (empty($options['id'])) {
        $options['id'] = 'file_' . uniqid();
    }
    
    $html = '<div class="' . ($options['wrapper_class'] ?: 'mb-4') . '">';
    
    if ($options['label']) {
        $label_class = 'block text-sm font-medium text-gray-700 mb-1' . ($options['label_class'] ? ' ' . $options['label_class'] : '');
        $html .= '<label for="' . htmlspecialchars($options['id']) . '" class="' . $label_class . '">';
        $html .= htmlspecialchars($options['label']);
        if ($options['required']) {
            $html .= ' <span class="text-red-500">*</span>';
        }
        $html .= '</label>';
    }
    
    $attributes = array_merge([
        'type' => 'file',
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-copihue-50 file:text-copihue-700 hover:file:bg-copihue-100'
    ], $options['attributes']);
    
    if ($options['accept']) {
        $attributes['accept'] = $options['accept'];
    }
    
    if ($options['multiple']) {
        $attributes['multiple'] = 'multiple';
    }
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    if ($options['error']) {
        $attributes['aria-invalid'] = 'true';
        $attributes['aria-describedby'] = $options['id'] . '_error';
    }
    
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if ($value !== '' && $value !== null) {
            $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    $html .= '<input' . $attr_string . '>';
    
    if ($options['error']) {
        $html .= '<p class="mt-1 text-sm text-red-600" id="' . htmlspecialchars($options['id']) . '_error">';
        $html .= htmlspecialchars($options['error']);
        $html .= '</p>';
    }
    
    if ($options['help_text'] && !$options['error']) {
        $html .= '<p class="mt-1 text-sm text-gray-500">';
        $html .= htmlspecialchars($options['help_text']);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>
