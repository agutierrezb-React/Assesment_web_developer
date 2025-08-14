<?php
/**
 * Plugin Name: WooCommerce Bolsa de Descuentos
 * Plugin URI: https://ookatech.com
 * Description: Sistema avanzado de bolsas de descuento por producto y proveedor para tienda de licores.
 * Version: 1.0.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 * Author URI: https://ookatech.com
 * Text Domain: woo-bolsa-descuentos
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WBD_PLUGIN_FILE', __FILE__);
define('WBD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WBD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WBD_VERSION', '1.0.0');

/**
 * Clase principal del plugin WooCommerce Bolsa de Descuentos
 * 
 * @since 1.0.0
 */
class WooBolsaDescuentos {
    
    /**
     * Instancia única del plugin (Singleton)
     * 
     * @var WooBolsaDescuentos|null
     */
    private static $instance = null;
    
    /**
     * Constructor privado para Singleton
     */
    private function __construct() {
        // Hooks de activación y desactivación deben estar aquí
        register_activation_hook(WBD_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WBD_PLUGIN_FILE, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Obtener instancia única del plugin (Singleton Pattern)
     * 
     * @return WooBolsaDescuentos
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar el plugin
     */
    public function init() {
        // Verificar si WooCommerce está activo
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Cargar archivos necesarios
        $this->load_dependencies();
        
        // Inicializar componentes
        $this->init_components();
        
        // Cargar traducciones
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Cargar dependencias del plugin
     */
    private function load_dependencies() {
        // Cargar funciones helper
        require_once WBD_PLUGIN_DIR . 'includes/functions.php';
        
        // Cargar clase de base de datos
        require_once WBD_PLUGIN_DIR . 'includes/class-db.php';
        
        // Cargar clase de administración
        require_once WBD_PLUGIN_DIR . 'includes/class-admin.php';
        
        // Cargar clase de frontend
        require_once WBD_PLUGIN_DIR . 'includes/class-front.php';
    }
    
    /**
     * Inicializar componentes del plugin
     */
    private function init_components() {
        // Inicializar base de datos
        WBD_Database::get_instance();
        
        // Inicializar administración
        if (is_admin()) {
            WBD_Admin::get_instance();
        }
        
        // Inicializar frontend
        WBD_Front::get_instance();
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Cargar dependencias necesarias para la activación
        $this->load_dependencies();
        
        // Crear tablas de base de datos
        WBD_Database::create_tables();
        
        // Crear datos de ejemplo (opcional)
        $this->create_sample_data();
        
        // Limpiar cache
        flush_rewrite_rules();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar cache
        flush_rewrite_rules();
    }
    
    /**
     * Crear datos de ejemplo para testing
     */
    private function create_sample_data() {
        global $wpdb;
        
        // Insertar proveedores de ejemplo
        $proveedores = array(
            array('nombre' => 'Licores Premium SA', 'email' => 'ventas@licores-premium.com', 'telefono' => '555-0001'),
            array('nombre' => 'Distribuidora Central', 'email' => 'pedidos@distcentral.com', 'telefono' => '555-0002'),
            array('nombre' => 'Vinos y Licores del Valle', 'email' => 'info@vinosvalle.com', 'telefono' => '555-0003')
        );
        
        foreach ($proveedores as $proveedor) {
            $wpdb->insert(
                $wpdb->prefix . 'proveedores',
                $proveedor,
                array('%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Aviso si WooCommerce no está instalado
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('WooCommerce Bolsa de Descuentos requiere que WooCommerce esté instalado y activado.', 'woo-bolsa-descuentos');
        echo '</p></div>';
    }
    
    /**
     * Cargar traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-bolsa-descuentos',
            false,
            dirname(plugin_basename(WBD_PLUGIN_FILE)) . '/languages/'
        );
    }
}

/**
 * Función principal para obtener la instancia del plugin
 * 
 * @return WooBolsaDescuentos
 */
function woo_bolsa_descuentos() {
    return WooBolsaDescuentos::get_instance();
}

// Inicializar el plugin
woo_bolsa_descuentos();
