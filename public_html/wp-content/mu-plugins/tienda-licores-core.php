<?php
/**
 * Plugin Name: Tienda Licores - Funcionalidades Core
 * Description: Funcionalidades críticas que nunca deben desactivarse
 * Version: 1.0.0
 */

// Verificación de edad obligatoria - NUNCA puede desactivarse
function licores_verificacion_edad_global() {
    if (!is_admin() && !current_user_can('administrator')) {
        // Verificar si hay productos alcohólicos en el carrito
        if (WC()->cart && !WC()->cart->is_empty()) {
            $tiene_alcohol = false;
            
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product = $cart_item['data'];
                if (has_term(['vinos', 'whisky', 'cerveza', 'ron'], 'product_cat', $product->get_id())) {
                    $tiene_alcohol = true;
                    break;
                }
            }
            
            if ($tiene_alcohol && !WC()->session->get('edad_verificada')) {
                wp_redirect(home_url('/verificacion-edad/'));
                exit;
            }
        }
    }
}
add_action('wp', 'licores_verificacion_edad_global');

// Configuraciones críticas de la tienda
function licores_configuraciones_criticas() {
    // Deshabilitar compra de invitados para productos alcohólicos
    add_filter('woocommerce_checkout_registration_required', '__return_true');
    
    // Mensaje de responsabilidad legal
    add_action('woocommerce_before_cart', function() {
        echo '<div class="aviso-legal">';
        echo '<p><strong>AVISO:</strong> ' . __('La venta de bebidas alcohólicas está prohibida a menores de 18 años.', 'licores-core') . '</p>';
        echo '</div>';
    });
}
add_action('init', 'licores_configuraciones_criticas');