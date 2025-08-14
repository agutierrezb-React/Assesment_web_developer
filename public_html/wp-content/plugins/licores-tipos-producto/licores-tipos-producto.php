<?php
/**
 * Plugin Name: Licores - Tipos de Producto WooCommerce
 * Description: Ejemplo CORTO para assessment - wc_register_product_type()
 * Version: 1.0.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 */

if (!defined('ABSPATH')) exit;


class LicoresTiposProducto {
    
    public function __construct() {
        add_action('init', array($this, 'registrar_tipos_producto'));
        add_filter('product_type_selector', array($this, 'agregar_tipos_selector'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'mostrar_campos'));
        add_action('woocommerce_process_product_meta', array($this, 'guardar_campos'));
    }
    
    /**
     * PUNTO CLAVE: Usar wc_register_product_type() para WooCommerce
     */
    public function registrar_tipos_producto() {
        if (function_exists('wc_register_product_type')) {
            wc_register_product_type('licor_premium', array(
                'label' => 'Licor Premium',
                'supports' => array('title', 'editor', 'thumbnail')
            ));
        }
    }
    
    public function agregar_tipos_selector($types) {
        $types['licor_premium'] = 'Licor Premium';
        return $types;
    }
    
    public function mostrar_campos() {
        woocommerce_wp_text_input(array(
            'id' => '_graduacion_alcoholica',
            'label' => 'Graduación Alcohólica (%)',
            'type' => 'number'
        ));
    }
    
    public function guardar_campos($post_id) {
        if (isset($_POST['_graduacion_alcoholica'])) {
            update_post_meta($post_id, '_graduacion_alcoholica', sanitize_text_field($_POST['_graduacion_alcoholica']));
        }
    }
}

new LicoresTiposProducto();
