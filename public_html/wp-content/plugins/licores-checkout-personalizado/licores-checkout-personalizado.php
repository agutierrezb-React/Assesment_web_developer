<?php
/**
 * Plugin Name: Licores - Campo Personalizado Checkout
 * Description: Ejemplo simple de cómo añadir un campo en checkout y guardarlo como metadato
 * Version: 1.0.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// 1️⃣ AÑADIR CAMPO AL CHECKOUT
add_action('woocommerce_after_order_notes', 'agregar_campo_personalizado');
function agregar_campo_personalizado($checkout) {
    woocommerce_form_field('campo_personalizado', array(
        'type'        => 'text',
        'class'       => array('form-row-wide'),
        'label'       => __('Campo Personalizado *'),
        'placeholder' => __('Escribe algo aquí'),
        'required'    => true,
    ), $checkout->get_value('campo_personalizado'));
}

// 2️⃣ VALIDAR EL CAMPO
add_action('woocommerce_checkout_process', 'validar_campo_personalizado');
function validar_campo_personalizado() {
    if (empty($_POST['campo_personalizado'])) {
        wc_add_notice(__('El campo personalizado es obligatorio.'), 'error');
    }
}

// 3️⃣ GUARDAR COMO METADATO DEL PEDIDO
add_action('woocommerce_checkout_update_order_meta', 'guardar_campo_personalizado');
function guardar_campo_personalizado($order_id) {
    if (!empty($_POST['campo_personalizado'])) {
        // AQUÍ SE GUARDA COMO METADATO
        update_post_meta($order_id, '_campo_personalizado', sanitize_text_field($_POST['campo_personalizado']));
    }
}

// 4️⃣ MOSTRAR EN ADMIN DEL PEDIDO
add_action('woocommerce_admin_order_data_after_billing_address', 'mostrar_campo_en_admin');
function mostrar_campo_en_admin($order) {
    $valor = get_post_meta($order->get_id(), '_campo_personalizado', true);
    if ($valor) {
        echo '<p><strong>Campo Personalizado:</strong> ' . esc_html($valor) . '</p>';
    }
}
