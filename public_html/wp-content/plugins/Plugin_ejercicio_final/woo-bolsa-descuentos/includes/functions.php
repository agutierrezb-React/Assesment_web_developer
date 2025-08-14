<?php
/**
 * Funciones helper del plugin
 * 
 * @package WooBolsaDescuentos
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener productos de WooCommerce para selectores
 * 
 * @return array Lista de productos con ID y nombre
 */
function wbd_get_products_for_select() {
    $products = array();
    
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_visibility',
                'value' => array('catalog', 'visible'),
                'compare' => 'IN'
            )
        )
    );
    
    $product_query = new WP_Query($args);
    
    if ($product_query->have_posts()) {
        while ($product_query->have_posts()) {
            $product_query->the_post();
            $product_id = get_the_ID();
            $product_title = get_the_title();
            $product_price = get_post_meta($product_id, '_price', true);
            
            $products[$product_id] = sprintf(
                '%s (#%d) - %s',
                $product_title,
                $product_id,
                $product_price ? wc_price($product_price) : __('Sin precio', 'woo-bolsa-descuentos')
            );
        }
    }
    
    wp_reset_postdata();
    
    return $products;
}

/**
 * Formatear moneda para mostrar
 * 
 * @param float $amount Cantidad a formatear
 * @return string Cantidad formateada
 */
function wbd_format_currency($amount) {
    return wc_price($amount);
}

/**
 * Calcular descuento para la segunda unidad
 * 
 * @param float $precio_unitario Precio del producto
 * @param float $porcentaje_descuento Porcentaje de descuento
 * @param int $cantidad Cantidad de productos
 * @return array Array con información del descuento
 */
function wbd_calcular_descuento_segunda_unidad($precio_unitario, $porcentaje_descuento, $cantidad) {
    $descuento_info = array(
        'descuento_aplicable' => false,
        'monto_descuento' => 0,
        'unidades_con_descuento' => 0,
        'precio_total_original' => $precio_unitario * $cantidad,
        'precio_total_final' => $precio_unitario * $cantidad
    );
    
    // Solo aplicar descuento si hay 2 o más unidades
    if ($cantidad >= 2) {
        $unidades_con_descuento = floor($cantidad / 2); // Una unidad con descuento por cada 2 unidades
        $monto_descuento_unitario = $precio_unitario * ($porcentaje_descuento / 100);
        $monto_descuento_total = $monto_descuento_unitario * $unidades_con_descuento;
        
        $descuento_info['descuento_aplicable'] = true;
        $descuento_info['monto_descuento'] = $monto_descuento_total;
        $descuento_info['unidades_con_descuento'] = $unidades_con_descuento;
        $descuento_info['precio_total_final'] = $descuento_info['precio_total_original'] - $monto_descuento_total;
    }
    
    return $descuento_info;
}

/**
 * Validar si una bolsa tiene fondos suficientes
 * 
 * @param array $bolsa Datos de la bolsa
 * @param float $monto_requerido Monto que se necesita descontar
 * @return bool True si tiene fondos suficientes
 */
function wbd_bolsa_tiene_fondos($bolsa, $monto_requerido) {
    return (float) $bolsa['monto_disponible'] >= $monto_requerido;
}

/**
 * Obtener la mejor bolsa para aplicar descuento
 * 
 * @param array $bolsas Lista de bolsas disponibles
 * @param float $monto_requerido Monto necesario para el descuento
 * @return array|false Datos de la mejor bolsa o false si no hay ninguna adecuada
 */
function wbd_obtener_mejor_bolsa($bolsas, $monto_requerido) {
    if (empty($bolsas)) {
        return false;
    }
    
    // Filtrar bolsas que tienen fondos suficientes
    $bolsas_disponibles = array_filter($bolsas, function($bolsa) use ($monto_requerido) {
        return wbd_bolsa_tiene_fondos($bolsa, $monto_requerido);
    });
    
    if (empty($bolsas_disponibles)) {
        return false;
    }
    
    // Ordenar por monto disponible (mayor primero) como criterio por defecto
    usort($bolsas_disponibles, function($a, $b) {
        return (float) $b['monto_disponible'] <=> (float) $a['monto_disponible'];
    });
    
    return $bolsas_disponibles[0];
}

/**
 * Registrar log de actividad del plugin
 * 
 * @param string $mensaje Mensaje a registrar
 * @param string $nivel Nivel del log (info, warning, error)
 */
function wbd_log($mensaje, $nivel = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = sprintf('[%s] [WBD-%s] %s', $timestamp, strtoupper($nivel), $mensaje);
        error_log($log_message);
    }
}

/**
 * Verificar si un producto tiene bolsas de descuento disponibles
 * 
 * @param int $producto_id ID del producto
 * @return bool True si tiene bolsas disponibles
 */
function wbd_producto_tiene_bolsas($producto_id) {
    $bolsas = WBD_Database::get_bolsas_by_producto($producto_id);
    return !empty($bolsas);
}

/**
 * Obtener información de descuentos para mostrar en carrito
 * 
 * @param int $producto_id ID del producto
 * @param int $cantidad Cantidad en el carrito
 * @return array Información de descuentos disponibles
 */
function wbd_get_descuento_info_carrito($producto_id, $cantidad) {
    $info = array(
        'tiene_descuento' => false,
        'mensaje' => '',
        'bolsas_utilizadas' => array()
    );
    
    $bolsas = WBD_Database::get_bolsas_by_producto($producto_id);
    
    if (empty($bolsas) || $cantidad < 2) {
        return $info;
    }
    
    $product = wc_get_product($producto_id);
    $precio_unitario = $product->get_price();
    
    foreach ($bolsas as $bolsa) {
        $descuento_calc = wbd_calcular_descuento_segunda_unidad(
            $precio_unitario, 
            $bolsa['porcentaje_descuento'], 
            $cantidad
        );
        
        if ($descuento_calc['descuento_aplicable'] && 
            wbd_bolsa_tiene_fondos($bolsa, $descuento_calc['monto_descuento'])) {
            
            $info['tiene_descuento'] = true;
            $info['bolsas_utilizadas'][] = array(
                'proveedor' => $bolsa['proveedor_nombre'],
                'porcentaje' => $bolsa['porcentaje_descuento'],
                'monto_descuento' => $descuento_calc['monto_descuento'],
                'saldo_restante' => $bolsa['monto_disponible'] - $descuento_calc['monto_descuento']
            );
            break; // Solo usar la primera bolsa disponible
        }
    }
    
    if ($info['tiene_descuento']) {
        $bolsa_usada = $info['bolsas_utilizadas'][0];
        $info['mensaje'] = sprintf(
            __('Descuento del %s%% aplicado por %s. Saldo restante: %s', 'woo-bolsa-descuentos'),
            $bolsa_usada['porcentaje'],
            $bolsa_usada['proveedor'],
            wbd_format_currency($bolsa_usada['saldo_restante'])
        );
    }
    
    return $info;
}

/**
 * Sanitizar datos de entrada
 * 
 * @param mixed $data Datos a sanitizar
 * @param string $type Tipo de sanitización
 * @return mixed Datos sanitizados
 */
function wbd_sanitize_input($data, $type = 'text') {
    switch ($type) {
        case 'email':
            return sanitize_email($data);
        case 'url':
            return esc_url_raw($data);
        case 'int':
            return intval($data);
        case 'float':
            return floatval($data);
        case 'html':
            return wp_kses_post($data);
        case 'text':
        default:
            return sanitize_text_field($data);
    }
}

/**
 * Verificar capacidades de usuario para administrar el plugin
 * 
 * @return bool True si el usuario puede administrar
 */
function wbd_can_manage_plugin() {
    return current_user_can('manage_woocommerce');
}

/**
 * Obtener URL del admin del plugin
 * 
 * @param string $page Página específica
 * @return string URL del admin
 */
function wbd_get_admin_url($page = 'proveedores') {
    return admin_url('admin.php?page=wbd-' . $page);
}
