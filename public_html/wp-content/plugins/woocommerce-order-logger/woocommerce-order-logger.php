<?php
/**
 * EJERCICIO 1: Plugin básico - WooCommerce Order Logger
 * 
 * Plugin Name: WooCommerce Order Logger
 * Plugin URI: https://tienda-licores.com
 * Description: Registra automáticamente cada pedido completado de la tienda de licores en un archivo de log con ID y fecha
 * Version: 1.0.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 * Text Domain: wc-order-logger
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin WooCommerce Order Logger
 * EJERCICIO 1: Sistema de logging para pedidos completados
 */
class WC_Order_Logger {

    /**
     * Constructor del plugin
     * Inicializa los hooks necesarios
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('woocommerce_order_status_completed', array($this, 'log_completed_order'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    /**
     * Inicializar el plugin
     * Verifica dependencias y carga configuración
     */
    public function init() {
        // Verificar que WooCommerce esté activo
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Cargar traducciones
        load_plugin_textdomain('wc-order-logger', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Activación del plugin
     * Verifica requisitos y prepara el entorno
     */
    public function activate() {
        // Verificar que WooCommerce esté instalado
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Este plugin requiere WooCommerce para funcionar en la tienda de licores.', 'wc-order-logger'));
        }

        // Verificar/crear directorio uploads
        $upload_dir = wp_upload_dir();
        if (!file_exists($upload_dir['basedir'])) {
            wp_mkdir_p($upload_dir['basedir']);
        }
    }

    /**
     * EJERCICIO 1: Función principal - Registrar pedido completado
     * Se ejecuta cuando un pedido cambia a estado "completed"
     *
     * @param int $order_id ID del pedido completado
     */
    public function log_completed_order($order_id) {
        // Validar que el order_id sea válido
        if (!$order_id || !is_numeric($order_id)) {
            return;
        }

        // Obtener objeto del pedido para validación adicional
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Preparar datos para el log según requisitos del ejercicio
        $fecha_completado = current_time('Y-m-d H:i:s');
        
        // Crear línea de log con ID y fecha (requisitos del ejercicio 1)
        $log_line = sprintf(
            "[%s] Pedido ID: %d - Estado: Completado\n",
            $fecha_completado,
            $order_id
        );

        // Escribir al archivo de log especificado
        $this->write_to_log_file($log_line);
    }

    /**
     * EJERCICIO 1: Escribir al archivo wp-content/uploads/order-log.txt
     * Función que maneja la escritura del log según especificaciones
     *
     * @param string $log_line Línea a escribir en el archivo
     */
    private function write_to_log_file($log_line) {
        // Ruta específica requerida en el ejercicio 1
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/order-log.txt';

        // Verificar permisos de escritura
        if (!is_writable($upload_dir['basedir'])) {
            error_log('WC Order Logger (Ejercicio 1): No se puede escribir en el directorio uploads');
            return;
        }

        // Escribir al archivo (append mode para mantener historial)
        $result = file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        if ($result === false) {
            error_log('WC Order Logger (Ejercicio 1): Error al escribir en order-log.txt');
        }
    }

    /**
     * Mostrar aviso si WooCommerce no está activo
     * Mejora la experiencia de administración
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('WooCommerce Order Logger (Ejercicio 1):', 'wc-order-logger'); ?></strong>
                <?php _e('Este plugin requiere que WooCommerce esté instalado y activo para funcionar en la tienda de licores.', 'wc-order-logger'); ?>
            </p>
        </div>
        <?php
    }
}

// EJERCICIO 1: Inicializar el plugin
new WC_Order_Logger();

/**
 * EJERCICIO 1 - FUNCIÓN DE PRUEBA
 * Agregar ?test_order_logger=1 a cualquier URL del sitio para probar
 */
add_action('init', function() {
    if (isset($_GET['test_order_logger']) && current_user_can('manage_options')) {
        $logger = new WC_Order_Logger();
        
        // Simular ID de pedido para prueba
        $test_order_id = 999;
        
        // Ejecutar la función de logging
        $logger->log_completed_order($test_order_id);
        
        // Mostrar mensaje de confirmación
        wp_die('
            <h2>🧪 PRUEBA DEL PLUGIN - EJERCICIO 1</h2>
            <p><strong>✅ Función ejecutada correctamente</strong></p>
            <p>📝 Se ha simulado el pedido ID: ' . $test_order_id . '</p>
            <p>📁 Verifica el archivo: <code>wp-content/uploads/order-log.txt</code></p>
            <p><a href="javascript:history.back()">← Volver</a></p>
        ');
    }
});

/**
 * EJERCICIO 1 - NOTAS DEL DESARROLLADOR:
 * 
 * Este plugin cumple con los requisitos específicos:
 * ✅ Cabecera estándar de WordPress incluida
 * ✅ Se ejecuta cuando un pedido se completa usando 'woocommerce_order_status_completed'
 * ✅ Guarda en wp-content/uploads/order-log.txt
 * ✅ Registra ID del pedido y fecha de completado
 * ✅ Manejo de errores y verificaciones de seguridad
 * ✅ Compatibilidad con estándares de WordPress/WooCommerce
 * ✅ Contexto de tienda de licores en descripciones
 */
