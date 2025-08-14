<?php
/**
 * Plugin Name: Licores - Descuentos Condicionales
 * Description: Aplica descuentos automáticos según condiciones específicas
 * Version: 1.0.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 * Text Domain: licores-descuentos
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aplicar descuentos condicionales a productos de licores
 *
 * @param float $precio Precio original del producto
 * @param WC_Product $product Objeto del producto
 * @return float Precio con descuento aplicado
 */
function licores_aplicar_descuento_condicional($precio, $product) {
    // Solo en frontend
    if (is_admin() && !wp_doing_ajax()) {
        return $precio;
    }
    
    // Verificar precio válido
    if (empty($precio) || !is_numeric($precio)) {
        return $precio;
    }
    
    // CONDICIÓN 1: Viernes de Vinos - 15% descuento
    if (date('w') == 5 && has_term('vinos', 'product_cat', $product->get_id())) {
        return $precio * 0.85;
    }
    
    // CONDICIÓN 2: Stock bajo - 10% descuento
    $stock = $product->get_stock_quantity();
    if ($stock && $stock <= 3) {
        return $precio * 0.9;
    }
    
    // CONDICIÓN 3: Usuarios VIP - 8% descuento
    if (is_user_logged_in() && current_user_can('licores_vip')) {
        return $precio * 0.92;
    }
    
    return $precio;
}

// Aplicar filtros a todos los tipos de precio
add_filter('woocommerce_product_get_price', 'licores_aplicar_descuento_condicional', 10, 2);
add_filter('woocommerce_product_get_regular_price', 'licores_aplicar_descuento_condicional', 10, 2);
add_filter('woocommerce_product_get_sale_price', 'licores_aplicar_descuento_condicional', 10, 2);

/**
 * Mostrar descuento aplicado en la página del producto
 */
function licores_mostrar_descuento_aplicado() {
    global $product;
    
    $precio_regular = $product->get_regular_price();
    $precio_actual = $product->get_price();
    
    if ($precio_regular > $precio_actual) {
        $ahorro = $precio_regular - $precio_actual;
        $porcentaje = round(($ahorro / $precio_regular) * 100);
        
        echo '<div class="licores-descuento-info" style="background: #e8f5e8; padding: 10px; margin: 10px 0;">';
        echo '<strong>🎉 ¡' . $porcentaje . '% de descuento aplicado!</strong><br>';
        echo '<small>Ahorras: ' . wc_price($ahorro) . '</small>';
        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'licores_mostrar_descuento_aplicado', 25);
