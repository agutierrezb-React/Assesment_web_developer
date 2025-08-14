<?php
/**
 * Clase para manejo de frontend - Lógica de descuentos
 * 
 * @package WooBolsaDescuentos
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase WBD_Front
 * 
 * Maneja toda la lógica de aplicación de descuentos en el frontend
 */
class WBD_Front {
    
    /**
     * Instancia única de la clase
     * 
     * @var WBD_Front|null
     */
    private static $instance = null;
    
    /**
     * Array para almacenar descuentos aplicados temporalmente
     * 
     * @var array
     */
    private $descuentos_aplicados = array();
    
    /**
     * Constructor privado para Singleton
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Obtener instancia única
     * 
     * @return WBD_Front
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar hooks del frontend
     */
    private function init_hooks() {
        // Hook principal para aplicar descuentos en carrito
        add_action('woocommerce_before_calculate_totals', array($this, 'aplicar_descuentos_carrito'), 20);
        
        // Mostrar información de descuentos en carrito
        add_action('woocommerce_cart_totals_after_order_total', array($this, 'mostrar_info_descuentos_carrito'));
        
        // Procesar descuentos al completar pedido
        add_action('woocommerce_order_status_completed', array($this, 'procesar_descuentos_pedido_completado'));
        add_action('woocommerce_checkout_order_processed', array($this, 'procesar_descuentos_checkout'));
        
        // Mostrar información en producto individual
        add_action('woocommerce_single_product_summary', array($this, 'mostrar_info_descuento_producto'), 25);
        
        // Agregar estilos CSS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Aplicar descuentos en el carrito
     * 
     * @param WC_Cart $cart Objeto del carrito
     */
    public function aplicar_descuentos_carrito($cart) {
        // Evitar bucles infinitos y ejecuciones en admin
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }
        
        // Limpiar descuentos aplicados anteriormente
        $this->descuentos_aplicados = array();
        
        // Procesar cada item del carrito
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $this->procesar_item_carrito($cart_item_key, $cart_item);
        }
        
        wbd_log('Descuentos aplicados en carrito: ' . count($this->descuentos_aplicados));
    }
    
    /**
     * Procesar un item individual del carrito
     * 
     * @param string $cart_item_key Clave del item en carrito
     * @param array $cart_item Datos del item
     */
    private function procesar_item_carrito($cart_item_key, $cart_item) {
        $producto_id = $cart_item['product_id'];
        $cantidad = $cart_item['quantity'];
        $product = $cart_item['data'];
        
        // Solo aplicar descuento si hay 2 o más unidades
        if ($cantidad < 2) {
            return;
        }
        
        // Obtener bolsas disponibles para este producto
        $bolsas = WBD_Database::get_bolsas_by_producto($producto_id);
        
        if (empty($bolsas)) {
            return;
        }
        
        $precio_unitario = $product->get_price();
        
        // Calcular cuántas unidades tendrían descuento
        $unidades_con_descuento = floor($cantidad / 2);
        
        // Intentar aplicar descuento con la mejor bolsa disponible
        foreach ($bolsas as $bolsa) {
            $porcentaje_descuento = (float) $bolsa['porcentaje_descuento'];
            $monto_descuento_unitario = $precio_unitario * ($porcentaje_descuento / 100);
            $monto_descuento_total = $monto_descuento_unitario * $unidades_con_descuento;
            
            // Verificar si la bolsa tiene fondos suficientes
            if (wbd_bolsa_tiene_fondos($bolsa, $monto_descuento_total)) {
                // Aplicar descuento
                $nuevo_precio = $precio_unitario - ($monto_descuento_unitario / $cantidad);
                $product->set_price($nuevo_precio);
                
                // Registrar descuento aplicado temporalmente
                $this->descuentos_aplicados[$cart_item_key] = array(
                    'producto_id' => $producto_id,
                    'bolsa_id' => $bolsa['id'],
                    'proveedor_id' => $bolsa['proveedor_id'],
                    'proveedor_nombre' => $bolsa['proveedor_nombre'],
                    'porcentaje_descuento' => $porcentaje_descuento,
                    'monto_descuento' => $monto_descuento_total,
                    'unidades_con_descuento' => $unidades_con_descuento,
                    'precio_unitario_original' => $precio_unitario,
                    'saldo_bolsa_original' => $bolsa['monto_disponible']
                );
                
                wbd_log(sprintf(
                    'Descuento aplicado: Producto %d, Proveedor %s, Monto: %s',
                    $producto_id,
                    $bolsa['proveedor_nombre'],
                    wbd_format_currency($monto_descuento_total)
                ));
                
                break; // Solo usar una bolsa por producto
            }
        }
    }
    
    /**
     * Mostrar información de descuentos en carrito
     */
    public function mostrar_info_descuentos_carrito() {
        if (empty($this->descuentos_aplicados)) {
            return;
        }
        
        echo '<tr class="wbd-descuentos-info">';
        echo '<th colspan="2">' . __('Información de Descuentos', 'woo-bolsa-descuentos') . '</th>';
        echo '</tr>';
        
        foreach ($this->descuentos_aplicados as $descuento) {
            $saldo_restante = $descuento['saldo_bolsa_original'] - $descuento['monto_descuento'];
            
            echo '<tr class="wbd-descuento-detalle">';
            echo '<td>' . sprintf(
                __('Descuento %s%% por %s', 'woo-bolsa-descuentos'),
                $descuento['porcentaje_descuento'],
                $descuento['proveedor_nombre']
            ) . '</td>';
            echo '<td>-' . wbd_format_currency($descuento['monto_descuento']) . '</td>';
            echo '</tr>';
            
            echo '<tr class="wbd-saldo-restante">';
            echo '<td><small>' . sprintf(
                __('Saldo restante: %s', 'woo-bolsa-descuentos'),
                wbd_format_currency($saldo_restante)
            ) . '</small></td>';
            echo '<td></td>';
            echo '</tr>';
        }
    }
    
    /**
     * Procesar descuentos cuando se completa un pedido
     * 
     * @param int $order_id ID del pedido
     */
    public function procesar_descuentos_pedido_completado($order_id) {
        wbd_log('Procesando descuentos para pedido completado: ' . $order_id);
        $this->procesar_descuentos_pedido($order_id);
    }
    
    /**
     * Procesar descuentos en checkout
     * 
     * @param int $order_id ID del pedido
     */
    public function procesar_descuentos_checkout($order_id) {
        // Solo procesar si el pedido está en estado que permite descuentos
        $order = wc_get_order($order_id);
        if (!$order || in_array($order->get_status(), array('cancelled', 'refunded', 'failed'))) {
            return;
        }
        
        wbd_log('Procesando descuentos en checkout: ' . $order_id);
        $this->procesar_descuentos_pedido($order_id);
    }
    
    /**
     * Procesar descuentos de un pedido y actualizar base de datos
     * 
     * @param int $order_id ID del pedido
     */
    private function procesar_descuentos_pedido($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wbd_log('Orden no encontrada: ' . $order_id, 'error');
            return;
        }
        
        // Verificar si ya se procesaron los descuentos para este pedido
        if (get_post_meta($order_id, '_wbd_descuentos_procesados', true)) {
            wbd_log('Descuentos ya procesados para pedido: ' . $order_id);
            return;
        }
        
        foreach ($order->get_items() as $item_id => $item) {
            $producto_id = $item->get_product_id();
            $cantidad = $item->get_quantity();
            
            // Solo procesar si hay 2 o más unidades
            if ($cantidad < 2) {
                continue;
            }
            
            $this->procesar_descuento_item_pedido($order_id, $producto_id, $cantidad);
        }
        
        // Marcar pedido como procesado
        update_post_meta($order_id, '_wbd_descuentos_procesados', true);
        
        wbd_log('Descuentos procesados completamente para pedido: ' . $order_id);
    }
    
    /**
     * Procesar descuento para un item específico del pedido
     * 
     * @param int $order_id ID del pedido
     * @param int $producto_id ID del producto
     * @param int $cantidad Cantidad del producto
     */
    private function procesar_descuento_item_pedido($order_id, $producto_id, $cantidad) {
        $bolsas = WBD_Database::get_bolsas_by_producto($producto_id);
        
        if (empty($bolsas)) {
            return;
        }
        
        $product = wc_get_product($producto_id);
        $precio_unitario = $product->get_price();
        $unidades_con_descuento = floor($cantidad / 2);
        
        // Buscar la mejor bolsa disponible
        foreach ($bolsas as $bolsa) {
            $porcentaje_descuento = (float) $bolsa['porcentaje_descuento'];
            $monto_descuento_unitario = $precio_unitario * ($porcentaje_descuento / 100);
            $monto_descuento_total = $monto_descuento_unitario * $unidades_con_descuento;
            
            if (wbd_bolsa_tiene_fondos($bolsa, $monto_descuento_total)) {
                // Actualizar saldo de la bolsa
                $nuevo_saldo = $bolsa['monto_disponible'] - $monto_descuento_total;
                WBD_Database::update_saldo_bolsa($bolsa['id'], $nuevo_saldo);
                
                // Registrar descuento aplicado
                $descuento_data = array(
                    'pedido_id' => $order_id,
                    'producto_id' => $producto_id,
                    'proveedor_id' => $bolsa['proveedor_id'],
                    'bolsa_id' => $bolsa['id'],
                    'monto_descontado' => $monto_descuento_total,
                    'saldo_restante' => $nuevo_saldo,
                    'porcentaje_aplicado' => $porcentaje_descuento,
                    'cantidad_productos' => $cantidad,
                    'fecha_aplicacion' => current_time('mysql')
                );
                
                $registro_id = WBD_Database::registrar_descuento_aplicado($descuento_data);
                
                if ($registro_id) {
                    wbd_log(sprintf(
                        'Descuento registrado: ID %d, Pedido %d, Producto %d, Monto %s',
                        $registro_id,
                        $order_id,
                        $producto_id,
                        $monto_descuento_total
                    ));
                } else {
                    wbd_log('Error al registrar descuento en base de datos', 'error');
                }
                
                break; // Solo usar una bolsa por producto
            }
        }
    }
    
    /**
     * Mostrar información de descuento en página de producto
     */
    public function mostrar_info_descuento_producto() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $producto_id = $product->get_id();
        $bolsas = WBD_Database::get_bolsas_by_producto($producto_id);
        
        if (empty($bolsas)) {
            return;
        }
        
        echo '<div class="wbd-producto-descuento-info">';
        echo '<h4>' . __('¡Descuentos disponibles!', 'woo-bolsa-descuentos') . '</h4>';
        echo '<p>' . __('Obtén descuento en la segunda unidad:', 'woo-bolsa-descuentos') . '</p>';
        echo '<ul>';
        
        foreach ($bolsas as $bolsa) {
            if ($bolsa['monto_disponible'] > 0) {
                echo '<li>';
                echo sprintf(
                    __('%s%% de descuento (Proveedor: %s)', 'woo-bolsa-descuentos'),
                    $bolsa['porcentaje_descuento'],
                    $bolsa['proveedor_nombre']
                );
                echo ' - ' . sprintf(
                    __('Fondos disponibles: %s', 'woo-bolsa-descuentos'),
                    wbd_format_currency($bolsa['monto_disponible'])
                );
                echo '</li>';
            }
        }
        
        echo '</ul>';
        echo '<p><small>' . __('*Descuento aplicado automáticamente en carrito al comprar 2 o más unidades.', 'woo-bolsa-descuentos') . '</small></p>';
        echo '</div>';
    }
    
    /**
     * Cargar estilos CSS
     */
    public function enqueue_styles() {
        if (is_cart() || is_checkout() || is_product()) {
            wp_enqueue_style(
                'wbd-frontend-styles',
                WBD_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                WBD_VERSION
            );
        }
    }
}
