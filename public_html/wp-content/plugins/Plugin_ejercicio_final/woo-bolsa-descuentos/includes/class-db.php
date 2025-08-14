<?php
/**
 * Clase para manejo de base de datos
 * 
 * @package WooBolsaDescuentos
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase WBD_Database
 * 
 * Maneja la creación y gestión de las tablas personalizadas del plugin
 */
class WBD_Database {
    
    /**
     * Instancia única de la clase
     * 
     * @var WBD_Database|null
     */
    private static $instance = null;
    
    /**
     * Constructor privado para Singleton
     */
    private function __construct() {
        // Constructor vacío
    }
    
    /**
     * Obtener instancia única
     * 
     * @return WBD_Database
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Crear todas las tablas necesarias
     * 
     * @since 1.0.0
     */
    public static function create_tables() {
        global $wpdb;
        
        // Incluir archivo de upgrade para dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Crear tabla de proveedores
        self::create_proveedores_table($charset_collate);
        
        // Crear tabla de bolsas de descuento
        self::create_bolsas_descuento_table($charset_collate);
        
        // Crear tabla de descuentos aplicados
        self::create_descuentos_aplicados_table($charset_collate);
        
        // Actualizar versión de base de datos
        update_option('wbd_db_version', WBD_VERSION);
    }
    
    /**
     * Crear tabla wxy_proveedores
     * 
     * @param string $charset_collate Charset y collation de la base de datos
     */
    private static function create_proveedores_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'proveedores';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            email varchar(100) DEFAULT '',
            telefono varchar(20) DEFAULT '',
            activo tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY nombre (nombre),
            KEY activo (activo)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Crear tabla wxy_bolsas_descuento
     * 
     * @param string $charset_collate Charset y collation de la base de datos
     */
    private static function create_bolsas_descuento_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bolsas_descuento';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            proveedor_id mediumint(9) NOT NULL,
            producto_id bigint(20) NOT NULL,
            monto_disponible decimal(10,2) NOT NULL DEFAULT 0.00,
            monto_inicial decimal(10,2) NOT NULL DEFAULT 0.00,
            porcentaje_descuento decimal(5,2) NOT NULL DEFAULT 0.00,
            activo tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY proveedor_id (proveedor_id),
            KEY producto_id (producto_id),
            KEY activo (activo),
            KEY monto_disponible (monto_disponible),
            UNIQUE KEY proveedor_producto (proveedor_id, producto_id),
            FOREIGN KEY (proveedor_id) REFERENCES {$wpdb->prefix}proveedores(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Crear tabla wxy_descuentos_aplicados
     * 
     * @param string $charset_collate Charset y collation de la base de datos
     */
    private static function create_descuentos_aplicados_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'descuentos_aplicados';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            pedido_id bigint(20) NOT NULL,
            producto_id bigint(20) NOT NULL,
            proveedor_id mediumint(9) NOT NULL,
            bolsa_id mediumint(9) NOT NULL,
            monto_descontado decimal(10,2) NOT NULL,
            saldo_restante decimal(10,2) NOT NULL,
            porcentaje_aplicado decimal(5,2) NOT NULL,
            cantidad_productos int(11) NOT NULL DEFAULT 1,
            fecha_aplicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pedido_id (pedido_id),
            KEY producto_id (producto_id),
            KEY proveedor_id (proveedor_id),
            KEY bolsa_id (bolsa_id),
            KEY fecha_aplicacion (fecha_aplicacion),
            FOREIGN KEY (proveedor_id) REFERENCES {$wpdb->prefix}proveedores(id) ON DELETE CASCADE,
            FOREIGN KEY (bolsa_id) REFERENCES {$wpdb->prefix}bolsas_descuento(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Obtener todos los proveedores activos
     * 
     * @return array Lista de proveedores
     */
    public static function get_proveedores($activos_solo = true) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'proveedores';
        $where = $activos_solo ? 'WHERE activo = 1' : '';
        
        $results = $wpdb->get_results(
            "SELECT * FROM $table_name $where ORDER BY nombre ASC",
            ARRAY_A
        );
        
        return $results ? $results : array();
    }
    
    /**
     * Obtener bolsas de descuento para un producto
     * 
     * @param int $producto_id ID del producto
     * @return array Lista de bolsas ordenadas por monto disponible
     */
    public static function get_bolsas_by_producto($producto_id) {
        global $wpdb;
        
        $bolsas_table = $wpdb->prefix . 'bolsas_descuento';
        $proveedores_table = $wpdb->prefix . 'proveedores';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.*, p.nombre as proveedor_nombre 
                FROM $bolsas_table b
                LEFT JOIN $proveedores_table p ON b.proveedor_id = p.id
                WHERE b.producto_id = %d 
                AND b.activo = 1 
                AND p.activo = 1
                AND b.monto_disponible > 0
                ORDER BY b.monto_disponible DESC",
                $producto_id
            ),
            ARRAY_A
        );
        
        return $results ? $results : array();
    }
    
    /**
     * Actualizar saldo de una bolsa de descuento
     * 
     * @param int $bolsa_id ID de la bolsa
     * @param float $nuevo_saldo Nuevo saldo
     * @return bool True si se actualizó correctamente
     */
    public static function update_saldo_bolsa($bolsa_id, $nuevo_saldo) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bolsas_descuento';
        
        $result = $wpdb->update(
            $table_name,
            array('monto_disponible' => $nuevo_saldo),
            array('id' => $bolsa_id),
            array('%f'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Registrar descuento aplicado
     * 
     * @param array $data Datos del descuento aplicado
     * @return int|false ID del registro insertado o false en caso de error
     */
    public static function registrar_descuento_aplicado($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'descuentos_aplicados';
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            array('%d', '%d', '%d', '%d', '%f', '%f', '%f', '%d', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Obtener reportes de descuentos aplicados
     * 
     * @param array $filtros Filtros para la consulta
     * @return array Resultados del reporte
     */
    public static function get_reporte_descuentos($filtros = array()) {
        global $wpdb;
        
        $descuentos_table = $wpdb->prefix . 'descuentos_aplicados';
        $proveedores_table = $wpdb->prefix . 'proveedores';
        
        $where_clauses = array('1=1');
        $params = array();
        
        // Filtro por fecha
        if (!empty($filtros['fecha_desde'])) {
            $where_clauses[] = 'da.fecha_aplicacion >= %s';
            $params[] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $where_clauses[] = 'da.fecha_aplicacion <= %s';
            $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }
        
        // Filtro por proveedor
        if (!empty($filtros['proveedor_id'])) {
            $where_clauses[] = 'da.proveedor_id = %d';
            $params[] = $filtros['proveedor_id'];
        }
        
        // Filtro por producto
        if (!empty($filtros['producto_id'])) {
            $where_clauses[] = 'da.producto_id = %d';
            $params[] = $filtros['producto_id'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $query = "
            SELECT 
                da.*,
                p.nombre as proveedor_nombre,
                SUM(da.monto_descontado) as total_descontado,
                COUNT(*) as total_aplicaciones
            FROM $descuentos_table da
            LEFT JOIN $proveedores_table p ON da.proveedor_id = p.id
            WHERE $where_sql
            GROUP BY da.proveedor_id, da.producto_id
            ORDER BY da.fecha_aplicacion DESC
        ";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $results ? $results : array();
    }
}
