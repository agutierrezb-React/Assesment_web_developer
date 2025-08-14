<?php
/**
 * Plugin Name: Licores - Campo Personalizado Checkout
 * Plugin URI: https://tienda-licores.com
 * Description: Agrega campo de verificación de edad en checkout y lo guarda en metadatos del pedido
 * Version: 1.0.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 * Text Domain: licores-checkout
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar que WooCommerce esté activo
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('El plugin Licores Checkout requiere WooCommerce para funcionar.', 'licores-checkout');
        echo '</p></div>';
    });
    return;
}

class LicoresCheckoutPersonalizado {
    
    public function __construct() {
        add_filter('woocommerce_checkout_fields', array($this, 'agregar_campo_checkout'));
        add_action('woocommerce_after_checkout_validation', array($this, 'validar_campo_checkout'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'guardar_campo_pedido'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'mostrar_campo_admin'));
    }
    
    /**
     * Agregar campo de fecha de nacimiento al checkout
     */
    public function agregar_campo_checkout($fields) {
        $fields['billing']['billing_fecha_nacimiento'] = array(
            'label'       => __('Fecha de Nacimiento *', 'licores-checkout'),
            'placeholder' => __('Selecciona tu fecha de nacimiento', 'licores-checkout'),
            'required'    => true,
            'type'        => 'date',
            'class'       => array('form-row-wide'),
            'priority'    => 25,
            'description' => __('Requerido para verificar edad legal (18+ años)', 'licores-checkout')
        );
        
        return $fields;
    }
    
    /**
     * Validar campo de fecha de nacimiento
     */
    public function validar_campo_checkout($data, $errors) {
        if (empty($_POST['billing_fecha_nacimiento'])) {
            $errors->add('validation', __('La fecha de nacimiento es obligatoria.', 'licores-checkout'));
            return;
        }
        
        $fecha_nacimiento = sanitize_text_field($_POST['billing_fecha_nacimiento']);
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
        
        if (!$fecha_obj) {
            $errors->add('validation', __('Formato de fecha inválido.', 'licores-checkout'));
            return;
        }
        
        // Calcular edad
        $edad = $fecha_obj->diff(new DateTime())->y;
        
        if ($edad < 18) {
            $errors->add('validation', 
                sprintf(__('Debes ser mayor de 18 años. Tu edad calculada: %d años.', 'licores-checkout'), $edad)
            );
        }
        
        if ($fecha_obj > new DateTime()) {
            $errors->add('validation', __('La fecha de nacimiento no puede ser futura.', 'licores-checkout'));
        }
    }
    
    /**
     * Guardar campo en metadatos del pedido
     */
    public function guardar_campo_pedido($order_id) {
        if (!empty($_POST['billing_fecha_nacimiento'])) {
            $fecha_nacimiento = sanitize_text_field($_POST['billing_fecha_nacimiento']);
            $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
            $edad = $fecha_obj->diff(new DateTime())->y;
            
            // Guardar en metadatos del pedido
            update_post_meta($order_id, '_fecha_nacimiento_cliente', $fecha_nacimiento);
            update_post_meta($order_id, '_edad_cliente_verificada', $edad);
            
            // Agregar nota al pedido
            $order = wc_get_order($order_id);
            $order->add_order_note(
                sprintf(__('Cliente verificado: %d años de edad', 'licores-checkout'), $edad)
            );
        }
    }
    
    /**
     * Mostrar campo en admin del pedido
     */
    public function mostrar_campo_admin($order) {
        $edad = get_post_meta($order->get_id(), '_edad_cliente_verificada', true);
        $fecha = get_post_meta($order->get_id(), '_fecha_nacimiento_cliente', true);
        
        if ($edad && $fecha) {
            echo '<div style="background: #f0f8ff; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa;">';
            echo '<h3>' . __('Verificación de Edad', 'licores-checkout') . '</h3>';
            echo '<p><strong>' . __('Edad verificada:', 'licores-checkout') . '</strong> ' . $edad . ' años</p>';
            echo '<p><strong>' . __('Fecha de nacimiento:', 'licores-checkout') . '</strong> ' . date('d/m/Y', strtotime($fecha)) . '</p>';
            echo '</div>';
        }
    }
}

// Inicializar el plugin
new LicoresCheckoutPersonalizado();
