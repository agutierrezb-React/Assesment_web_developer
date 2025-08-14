<?php
/**
 * Plugin Name: Licores - Descuento Ofertas
 * Description: Aplica 10% de descuento a productos de la categoría "ofertas" para usuarios logueados.
 * Version: 1.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// 1️⃣ Modificar precio mostrado en tienda y producto individual
add_filter('woocommerce_get_price_html', 'ldo_mostrar_precio_con_descuento', 10, 2);
function ldo_mostrar_precio_con_descuento($price_html, $product) {
    // Solo para usuarios logueados
    if (!is_user_logged_in()) {
        return $price_html;
    }
    
    // Verificar si el producto está en la categoría "ofertas"
    if (!ldo_producto_en_categoria_ofertas($product)) {
        return $price_html;
    }
    
    // Calcular precio con descuento
    $precio_original = $product->get_price();
    $precio_descuento = $precio_original * 0.9; // 10% descuento
    
    // Mostrar precio original tachado y nuevo precio
    $precio_formateado = '<del>' . wc_price($precio_original) . '</del> <ins>' . wc_price($precio_descuento) . '</ins>';
    $precio_formateado .= ' <small style="color: #28a745;">¡10% OFF para miembros!</small>';
    
    return $precio_formateado;
}

// 2️⃣ Modificar precio real en carrito y checkout
add_action('woocommerce_before_calculate_totals', 'ldo_aplicar_descuento_carrito');
function ldo_aplicar_descuento_carrito($cart) {
    // Evitar bucles infinitos
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    // Solo para usuarios logueados
    if (!is_user_logged_in()) {
        return;
    }
    
    // Recorrer productos del carrito
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        
        // Verificar si está en categoría "ofertas"
        if (ldo_producto_en_categoria_ofertas($product)) {
            $precio_original = $product->get_regular_price();
            $precio_descuento = $precio_original * 0.9; // 10% descuento
            $product->set_price($precio_descuento);
        }
    }
}

// 3️⃣ Función auxiliar para verificar categoría "ofertas"
function ldo_producto_en_categoria_ofertas($product) {
    $categorias = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'slugs'));
    return in_array('ofertas', $categorias);
}

// 4️⃣ Mostrar mensaje en carrito cuando se aplica descuento
add_action('woocommerce_before_cart_table', 'ldo_mostrar_mensaje_descuento');
function ldo_mostrar_mensaje_descuento() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $tiene_productos_ofertas = false;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        if (ldo_producto_en_categoria_ofertas($product)) {
            $tiene_productos_ofertas = true;
            break;
        }
    }
    
    if ($tiene_productos_ofertas) {
        echo '<div class="woocommerce-message">🎉 ¡Estás ahorrando 10% en productos de ofertas por ser miembro registrado!</div>';
    }
}
