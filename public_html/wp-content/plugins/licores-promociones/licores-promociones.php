<?php
/**
 * Plugin Name: Tienda Licores - Sistema de Promociones
 * Plugin URI: https://tienda-licores.com
 * Description: Sistema de descuentos y promociones para productos alcohólicos
 * Version: 1.2.0
 * Author: Aldair Gutierrez
 * Text Domain: licores-promociones
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

// Verificar si WooCommerce está activo
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('El plugin Licores Promociones requiere WooCommerce para funcionar.', 'licores-promociones');
        echo '</p></div>';
    });
    return;
}

class LicoresPromociones {
    
    /**
     * Constructor del plugin
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('woocommerce_before_calculate_totals', array($this, 'aplicar_descuentos_especiales'));
        register_activation_hook(__FILE__, array($this, 'activar_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'desactivar_plugin'));
    }
    
    /**
     * Inicializar el plugin
     */
    public function init() {
        load_plugin_textdomain('licores-promociones', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Agregar menú en admin
        add_action('admin_menu', array($this, 'agregar_menu_admin'));
    }
    
    /**
     * Aplicar descuentos especiales según el día de la semana
     */
    public function aplicar_descuentos_especiales() {
        if (is_admin() && !defined('DOING_AJAX')) return;
        
        $dia_semana = date('w'); // 0 = domingo, 1 = lunes, etc.
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $descuento = 0;
            
            // Viernes de Vinos - 20% descuento
            if ($dia_semana == 5 && has_term('vinos', 'product_cat', $product->get_id())) {
                $descuento = 0.20;
            }
            // Sábado de Whisky - 15% descuento
            elseif ($dia_semana == 6 && has_term('whisky', 'product_cat', $product->get_id())) {
                $descuento = 0.15;
            }
            
            if ($descuento > 0) {
                $precio_original = $product->get_price();
                $nuevo_precio = $precio_original * (1 - $descuento);
                $product->set_price($nuevo_precio);
            }
        }
    }
    
    /**
     * Agregar menú en el admin
     */
    public function agregar_menu_admin() {
        add_submenu_page(
            'woocommerce',
            __('Promociones Licores', 'licores-promociones'),
            __('Promociones', 'licores-promociones'),
            'manage_woocommerce',
            'licores-promociones',
            array($this, 'pagina_admin')
        );
    }
    
    /**
     * Página de administración
     */
    public function pagina_admin() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Gestión de Promociones', 'licores-promociones') . '</h1>';
        echo '<p>' . __('Configure aquí las promociones especiales para su tienda de licores.', 'licores-promociones') . '</p>';
        echo '</div>';
    }
    
    /**
     * Activación del plugin
     */
    public function activar_plugin() {
        // Crear opciones por defecto
        add_option('licores_promociones_activas', 'yes');
        
        // Programar eventos si es necesario
        if (!wp_next_scheduled('licores_promociones_cleanup')) {
            wp_schedule_event(time(), 'daily', 'licores_promociones_cleanup');
        }
    }
    
    /**
     * Desactivación del plugin
     */
    public function desactivar_plugin() {
        // Limpiar eventos programados
        wp_clear_scheduled_hook('licores_promociones_cleanup');
    }
}

// Inicializar el plugin
new LicoresPromociones();