<?php
/**
 * Plugin Name: WooCommerce Checkout Documento
 * Description: Añade un campo "Número de Documento" obligatorio en el checkout y lo guarda como metadato del pedido.
 * Version: 1.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// 1️⃣ Mostrar el campo en el checkout
add_action('woocommerce_after_order_notes', 'wcd_agregar_campo_documento');
function wcd_agregar_campo_documento($checkout) {
    woocommerce_form_field('numero_documento', array(
        'type'        => 'text',
        'class'       => array('form-row-wide'),
        'label'       => __('Número de Documento *'),
        'placeholder' => __('Ingresa tu número de documento'),
        'required'    => true,
    ), $checkout->get_value('numero_documento'));
}

// 2️⃣ Validar el campo
add_action('woocommerce_checkout_process', 'wcd_validar_campo_documento');
function wcd_validar_campo_documento() {
    if ( empty($_POST['numero_documento']) ) {
        wc_add_notice(__('Por favor, ingresa tu número de documento.'), 'error');
    }
}

// 3️⃣ Guardar el valor como metadato del pedido
add_action('woocommerce_checkout_update_order_meta', 'wcd_guardar_campo_documento');
function wcd_guardar_campo_documento($order_id) {
    if ( ! empty($_POST['numero_documento']) ) {
        update_post_meta($order_id, '_numero_documento', sanitize_text_field($_POST['numero_documento']));
    }
}

// 4️⃣ Mostrar el valor en la página de edición del pedido en el admin
add_action('woocommerce_admin_order_data_after_billing_address', 'wcd_mostrar_campo_documento_admin', 10, 1);
function wcd_mostrar_campo_documento_admin($order) {
    $numero_documento = get_post_meta($order->get_id(), '_numero_documento', true);
    if ($numero_documento) {
        echo '<p><strong>' . __('Número de Documento') . ':</strong> ' . esc_html($numero_documento) . '</p>';
    }
}
