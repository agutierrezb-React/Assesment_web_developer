<?php
/**
 * Clase de administración para WooCommerce Bolsa de Descuentos
 * Implementa protección nonce para todas las operaciones de seguridad
 * 
 * @package WBD
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Prevenir acceso directo
}

/**
 * Clase WBD_Admin
 * Maneja todas las funcionalidades del panel de administración con seguridad nonce
 */
class WBD_Admin {
    
    /**
     * Instancia única de la clase (Singleton)
     *
     * @var WBD_Admin
     */
    private static $instance = null;

    /**
     * Nombre de la acción nonce para operaciones de proveedores
     *
     * @var string
     */
    private const NONCE_PROVEEDOR_ACTION = 'wbd_proveedor_action';

    /**
     * Nombre de la acción nonce para operaciones de bolsas
     *
     * @var string
     */
    private const NONCE_BOLSA_ACTION = 'wbd_bolsa_action';

    /**
     * Nombre del campo nonce
     *
     * @var string
     */
    private const NONCE_FIELD = 'wbd_nonce';

    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtener instancia única de la clase
     *
     * @return WBD_Admin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializar hooks del admin
     */
    private function init_hooks() {
        // Menús de administración
        add_action('admin_menu', array($this, 'add_admin_menus'));
        
        // Procesar formularios con verificación nonce
        add_action('admin_post_wbd_save_proveedor', array($this, 'process_proveedor_form'));
        add_action('admin_post_wbd_save_bolsa', array($this, 'process_bolsa_form'));
        add_action('admin_post_wbd_delete_proveedor', array($this, 'process_delete_proveedor'));
        add_action('admin_post_wbd_delete_bolsa', array($this, 'process_delete_bolsa'));
        
        // Estilos y scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Metabox en productos
        add_action('add_meta_boxes', array($this, 'add_product_metabox'));
    }

    /**
     * Añadir menús de administración
     */
    public function add_admin_menus() {
        // Menú principal
        add_menu_page(
            __('Bolsa de Descuentos', 'wbd'),
            __('Bolsa Descuentos', 'wbd'),
            'manage_woocommerce',
            'wbd-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-tag',
            30
        );

        // Submenú: Proveedores
        add_submenu_page(
            'wbd-dashboard',
            __('Proveedores', 'wbd'),
            __('Proveedores', 'wbd'),
            'manage_woocommerce',
            'wbd-proveedores',
            array($this, 'proveedores_page')
        );

        // Submenú: Bolsas
        add_submenu_page(
            'wbd-dashboard',
            __('Bolsas de Descuento', 'wbd'),
            __('Bolsas', 'wbd'),
            'manage_woocommerce',
            'wbd-bolsas',
            array($this, 'bolsas_page')
        );

        // Submenú: Reportes
        add_submenu_page(
            'wbd-dashboard',
            __('Reportes', 'wbd'),
            __('Reportes', 'wbd'),
            'manage_woocommerce',
            'wbd-reportes',
            array($this, 'reportes_page')
        );
    }

    /**
     * Página del dashboard principal
     */
    public function dashboard_page() {
        $this->render_admin_header(__('Dashboard - Bolsa de Descuentos', 'wbd'));
        
        // Obtener estadísticas
        $stats = $this->get_dashboard_stats();
        ?>
        
        <div class="wbd-dashboard">
            <div class="wbd-stats-grid">
                <!-- Estadísticas principales -->
                <div class="wbd-stat-card">
                    <h3><?php esc_html_e('Total Proveedores', 'wbd'); ?></h3>
                    <div class="wbd-stat-number"><?php echo esc_html($stats['total_proveedores']); ?></div>
                </div>
                
                <div class="wbd-stat-card">
                    <h3><?php esc_html_e('Bolsas Activas', 'wbd'); ?></h3>
                    <div class="wbd-stat-number"><?php echo esc_html($stats['bolsas_activas']); ?></div>
                </div>
                
                <div class="wbd-stat-card">
                    <h3><?php esc_html_e('Saldo Total Disponible', 'wbd'); ?></h3>
                    <div class="wbd-stat-number"><?php echo wc_price($stats['saldo_total']); ?></div>
                </div>
                
                <div class="wbd-stat-card">
                    <h3><?php esc_html_e('Descuentos Aplicados Hoy', 'wbd'); ?></h3>
                    <div class="wbd-stat-number"><?php echo esc_html($stats['descuentos_hoy']); ?></div>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="wbd-recent-activity">
                <h3><?php esc_html_e('Actividad Reciente', 'wbd'); ?></h3>
                <?php $this->render_recent_activity(); ?>
            </div>
        </div>
        
        <?php
        $this->render_admin_footer();
    }

    /**
     * Página de gestión de proveedores con formularios protegidos por nonce
     */
    public function proveedores_page() {
        $this->render_admin_header(__('Gestión de Proveedores', 'wbd'));
        
        // Procesar acciones si las hay
        $this->handle_proveedor_actions();
        
        global $wpdb;
        $proveedores = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}proveedores ORDER BY nombre ASC"
        );
        ?>
        
        <div class="wbd-admin-page">
            <!-- Formulario para añadir/editar proveedor con nonce -->
            <div class="wbd-form-section">
                <h3><?php esc_html_e('Añadir Nuevo Proveedor', 'wbd'); ?></h3>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wbd-form">
                    <input type="hidden" name="action" value="wbd_save_proveedor">
                    
                    <?php 
                    // IMPLEMENTACIÓN CRÍTICA DE NONCE PARA SEGURIDAD
                    wp_nonce_field(self::NONCE_PROVEEDOR_ACTION, self::NONCE_FIELD); 
                    ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="proveedor_nombre"><?php esc_html_e('Nombre del Proveedor', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="proveedor_nombre" 
                                           name="proveedor_nombre" 
                                           class="regular-text" 
                                           required 
                                           placeholder="<?php esc_attr_e('Ej: Distribuidora de Licores Premium', 'wbd'); ?>">
                                    <p class="description"><?php esc_html_e('Nombre comercial del proveedor de licores', 'wbd'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="proveedor_email"><?php esc_html_e('Email de Contacto', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <input type="email" 
                                           id="proveedor_email" 
                                           name="proveedor_email" 
                                           class="regular-text"
                                           placeholder="<?php esc_attr_e('contacto@proveedor.com', 'wbd'); ?>">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="proveedor_telefono"><?php esc_html_e('Teléfono', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="proveedor_telefono" 
                                           name="proveedor_telefono" 
                                           class="regular-text"
                                           placeholder="<?php esc_attr_e('+57 300 123 4567', 'wbd'); ?>">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="proveedor_estado"><?php esc_html_e('Estado', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <select id="proveedor_estado" name="proveedor_estado" class="regular-text">
                                        <option value="activo"><?php esc_html_e('Activo', 'wbd'); ?></option>
                                        <option value="inactivo"><?php esc_html_e('Inactivo', 'wbd'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button(__('Guardar Proveedor', 'wbd'), 'primary', 'submit', false); ?>
                </form>
            </div>

            <!-- Lista de proveedores existentes -->
            <div class="wbd-list-section">
                <h3><?php esc_html_e('Proveedores Registrados', 'wbd'); ?></h3>
                
                <?php if (!empty($proveedores)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'wbd'); ?></th>
                            <th><?php esc_html_e('Nombre', 'wbd'); ?></th>
                            <th><?php esc_html_e('Email', 'wbd'); ?></th>
                            <th><?php esc_html_e('Teléfono', 'wbd'); ?></th>
                            <th><?php esc_html_e('Estado', 'wbd'); ?></th>
                            <th><?php esc_html_e('Fecha Registro', 'wbd'); ?></th>
                            <th><?php esc_html_e('Acciones', 'wbd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                        <tr>
                            <td><?php echo esc_html($proveedor->id); ?></td>
                            <td><strong><?php echo esc_html($proveedor->nombre); ?></strong></td>
                            <td><?php echo esc_html($proveedor->email ?: '-'); ?></td>
                            <td><?php echo esc_html($proveedor->telefono ?: '-'); ?></td>
                            <td>
                                <span class="wbd-status wbd-status-<?php echo esc_attr($proveedor->estado); ?>">
                                    <?php echo esc_html(ucfirst($proveedor->estado)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(mysql2date('d/m/Y H:i', $proveedor->fecha_creacion)); ?></td>
                            <td>
                                <a href="<?php echo esc_url($this->get_edit_proveedor_url($proveedor->id)); ?>" 
                                   class="button button-small">
                                    <?php esc_html_e('Editar', 'wbd'); ?>
                                </a>
                                
                                <a href="<?php echo esc_url($this->get_delete_proveedor_url($proveedor->id)); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php esc_attr_e('¿Estás seguro de eliminar este proveedor?', 'wbd'); ?>')">
                                    <?php esc_html_e('Eliminar', 'wbd'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php esc_html_e('No hay proveedores registrados. Añade el primero arriba.', 'wbd'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
        $this->render_admin_footer();
    }

    /**
     * Procesar formulario de proveedor con verificación nonce
     * FUNCIÓN CRÍTICA DE SEGURIDAD
     */
    public function process_proveedor_form() {
        // Verificar permisos de usuario
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'wbd'));
        }

        // VERIFICACIÓN CRÍTICA DE NONCE - PROTEGE CONTRA ATAQUES CSRF
        if (!isset($_POST[self::NONCE_FIELD]) || 
            !wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_PROVEEDOR_ACTION)) {
            wp_die(__('Token de seguridad inválido. Por favor, inténtalo de nuevo.', 'wbd'));
        }

        // Sanitizar y validar datos de entrada
        $nombre = sanitize_text_field($_POST['proveedor_nombre'] ?? '');
        $email = sanitize_email($_POST['proveedor_email'] ?? '');
        $telefono = sanitize_text_field($_POST['proveedor_telefono'] ?? '');
        $estado = sanitize_text_field($_POST['proveedor_estado'] ?? 'activo');

        // Validaciones
        if (empty($nombre)) {
            wp_die(__('El nombre del proveedor es obligatorio.', 'wbd'));
        }

        if (!empty($email) && !is_email($email)) {
            wp_die(__('El email proporcionado no es válido.', 'wbd'));
        }

        // Insertar en base de datos de forma segura
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'proveedores',
            array(
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'estado' => $estado,
                'fecha_creacion' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            wp_die(__('Error al guardar el proveedor. Por favor, inténtalo de nuevo.', 'wbd'));
        }

        // Redirigir con mensaje de éxito
        wp_redirect(add_query_arg(
            array('page' => 'wbd-proveedores', 'message' => 'proveedor_saved'),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Procesar eliminación de proveedor con verificación nonce
     */
    public function process_delete_proveedor() {
        // Verificar permisos
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'wbd'));
        }

        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || 
            !wp_verify_nonce($_GET['_wpnonce'], 'delete_proveedor_' . intval($_GET['proveedor_id']))) {
            wp_die(__('Token de seguridad inválido.', 'wbd'));
        }

        $proveedor_id = intval($_GET['proveedor_id']);
        
        if ($proveedor_id <= 0) {
            wp_die(__('ID de proveedor inválido.', 'wbd'));
        }

        // Verificar si el proveedor tiene bolsas asociadas
        global $wpdb;
        $bolsas_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bolsas_descuento WHERE proveedor_id = %d",
            $proveedor_id
        ));

        if ($bolsas_count > 0) {
            wp_die(__('No se puede eliminar el proveedor porque tiene bolsas de descuento asociadas.', 'wbd'));
        }

        // Eliminar proveedor
        $result = $wpdb->delete(
            $wpdb->prefix . 'proveedores',
            array('id' => $proveedor_id),
            array('%d')
        );

        if ($result === false) {
            wp_die(__('Error al eliminar el proveedor.', 'wbd'));
        }

        // Redirigir con mensaje de éxito
        wp_redirect(add_query_arg(
            array('page' => 'wbd-proveedores', 'message' => 'proveedor_deleted'),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Generar URL segura para eliminar proveedor con nonce
     *
     * @param int $proveedor_id ID del proveedor
     * @return string URL segura
     */
    private function get_delete_proveedor_url($proveedor_id) {
        return wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'wbd_delete_proveedor',
                    'proveedor_id' => $proveedor_id
                ),
                admin_url('admin-post.php')
            ),
            'delete_proveedor_' . $proveedor_id
        );
    }

    /**
     * Generar URL para editar proveedor
     *
     * @param int $proveedor_id ID del proveedor
     * @return string URL de edición
     */
    private function get_edit_proveedor_url($proveedor_id) {
        return add_query_arg(
            array(
                'page' => 'wbd-proveedores',
                'action' => 'edit',
                'proveedor_id' => $proveedor_id
            ),
            admin_url('admin.php')
        );
    }

    /**
     * Obtener estadísticas para el dashboard
     *
     * @return array Estadísticas
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total proveedores activos
        $stats['total_proveedores'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}proveedores WHERE estado = 'activo'"
        );
        
        // Bolsas activas (con saldo > 0)
        $stats['bolsas_activas'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bolsas_descuento WHERE saldo_disponible > 0"
        );
        
        // Saldo total disponible
        $stats['saldo_total'] = $wpdb->get_var(
            "SELECT SUM(saldo_disponible) FROM {$wpdb->prefix}bolsas_descuento"
        ) ?: 0;
        
        // Descuentos aplicados hoy
        $stats['descuentos_hoy'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}descuentos_aplicados WHERE DATE(fecha_aplicacion) = %s",
            current_time('Y-m-d')
        ));
        
        return $stats;
    }

    /**
     * Renderizar actividad reciente
     */
    private function render_recent_activity() {
        global $wpdb;
        
        $actividad = $wpdb->get_results($wpdb->prepare(
            "SELECT da.*, p.nombre as proveedor_nombre, po.post_title as producto_nombre
             FROM {$wpdb->prefix}descuentos_aplicados da
             LEFT JOIN {$wpdb->prefix}proveedores p ON da.proveedor_id = p.id
             LEFT JOIN {$wpdb->posts} po ON da.producto_id = po.ID
             ORDER BY da.fecha_aplicacion DESC
             LIMIT %d",
            10
        ));
        
        if (empty($actividad)) {
            echo '<p>' . esc_html__('No hay actividad reciente.', 'wbd') . '</p>';
            return;
        }
        ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Fecha', 'wbd'); ?></th>
                    <th><?php esc_html_e('Producto', 'wbd'); ?></th>
                    <th><?php esc_html_e('Proveedor', 'wbd'); ?></th>
                    <th><?php esc_html_e('Descuento', 'wbd'); ?></th>
                    <th><?php esc_html_e('Pedido', 'wbd'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actividad as $item): ?>
                <tr>
                    <td><?php echo esc_html(mysql2date('d/m/Y H:i', $item->fecha_aplicacion)); ?></td>
                    <td><?php echo esc_html($item->producto_nombre ?: 'Producto #' . $item->producto_id); ?></td>
                    <td><?php echo esc_html($item->proveedor_nombre ?: 'Proveedor #' . $item->proveedor_id); ?></td>
                    <td><?php echo wc_price($item->monto_descontado); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $item->pedido_id . '&action=edit')); ?>">
                            #<?php echo esc_html($item->pedido_id); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php
    }

    /**
     * Renderizar cabecera del admin
     *
     * @param string $title Título de la página
     */
    private function render_admin_header($title) {
        ?>
        <div class="wrap wbd-admin-wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
            
            <?php $this->show_admin_notices(); ?>
        <?php
    }

    /**
     * Renderizar pie del admin
     */
    private function render_admin_footer() {
        ?>
        </div>
        <?php
    }

    /**
     * Mostrar mensajes de administración
     */
    private function show_admin_notices() {
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            
            switch ($message) {
                case 'proveedor_saved':
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         esc_html__('Proveedor guardado correctamente.', 'wbd') . '</p></div>';
                    break;
                    
                case 'proveedor_deleted':
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         esc_html__('Proveedor eliminado correctamente.', 'wbd') . '</p></div>';
                    break;
                    
                case 'bolsa_saved':
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         esc_html__('Bolsa de descuento guardada correctamente.', 'wbd') . '</p></div>';
                    break;
            }
        }
    }

    /**
     * Cargar assets del administrador
     *
     * @param string $hook_suffix Sufijo del hook de la página
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Solo cargar en páginas del plugin
        if (strpos($hook_suffix, 'wbd-') === false) {
            return;
        }

        wp_enqueue_style(
            'wbd-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            WBD_VERSION
        );

        wp_enqueue_script(
            'wbd-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            array('jquery'),
            WBD_VERSION,
            true
        );

        // Localizar script para AJAX con nonce
        wp_localize_script('wbd-admin-js', 'wbd_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wbd_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('¿Estás seguro de eliminar este elemento?', 'wbd'),
                'error_general' => __('Error al procesar la solicitud.', 'wbd'),
            )
        ));
    }

    /**
     * Página de gestión de bolsas de descuento
     */
    public function bolsas_page() {
        $this->render_admin_header(__('Gestión de Bolsas de Descuento', 'wbd'));
        
        // Procesar acciones si las hay
        $this->handle_bolsa_actions();
        
        global $wpdb;
        
        // Obtener bolsas con información de proveedores y productos
        $bolsas = $wpdb->get_results("
            SELECT b.*, p.nombre as proveedor_nombre, po.post_title as producto_nombre
            FROM {$wpdb->prefix}bolsas_descuento b
            LEFT JOIN {$wpdb->prefix}proveedores p ON b.proveedor_id = p.id
            LEFT JOIN {$wpdb->posts} po ON b.producto_id = po.ID
            ORDER BY b.fecha_creacion DESC
        ");
        
        // Obtener proveedores activos para el formulario
        $proveedores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}proveedores WHERE activo = 1 ORDER BY nombre ASC");
        ?>
        
        <div class="wbd-admin-page">
            <!-- Formulario para añadir bolsa -->
            <div class="wbd-form-section">
                <h3><?php esc_html_e('Crear Nueva Bolsa de Descuento', 'wbd'); ?></h3>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wbd-form">
                    <input type="hidden" name="action" value="wbd_save_bolsa">
                    
                    <?php 
                    // NONCE para seguridad de bolsas
                    wp_nonce_field(self::NONCE_BOLSA_ACTION, self::NONCE_FIELD); 
                    ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="producto_id"><?php esc_html_e('Producto', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <select id="producto_id" name="producto_id" class="regular-text" required>
                                        <option value=""><?php esc_html_e('Seleccionar producto...', 'wbd'); ?></option>
                                        <?php 
                                        $productos = wc_get_products(array('limit' => -1, 'status' => 'publish'));
                                        foreach ($productos as $producto): 
                                            // Obtener nombre y precio sin HTML
                                            $nombre_producto = $producto->get_name();
                                            $precio_raw = $producto->get_price();
                                            $precio_formateado = !empty($precio_raw) ? wc_price($precio_raw) : __('Sin precio', 'wbd');
                                            $precio_limpio = wp_strip_all_tags($precio_formateado);
                                            $display_name = sprintf('%s (#%d) - %s', $nombre_producto, $producto->get_id(), $precio_limpio);
                                        ?>
                                            <option value="<?php echo esc_attr($producto->get_id()); ?>">
                                                <?php echo esc_html($display_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Selecciona el producto para aplicar descuentos', 'wbd'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="proveedor_id"><?php esc_html_e('Proveedor', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <select id="proveedor_id" name="proveedor_id" class="regular-text" required>
                                        <option value=""><?php esc_html_e('Seleccionar proveedor...', 'wbd'); ?></option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo esc_attr($proveedor->id); ?>">
                                                <?php echo esc_html($proveedor->nombre); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Proveedor que proporcionará el descuento', 'wbd'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="monto_inicial"><?php esc_html_e('Monto Inicial', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="monto_inicial" 
                                           name="monto_inicial" 
                                           class="regular-text" 
                                           step="0.01" 
                                           min="0" 
                                           required
                                           placeholder="<?php esc_attr_e('Ej: 50000', 'wbd'); ?>">
                                    <p class="description"><?php esc_html_e('Cantidad inicial de dinero disponible para descuentos', 'wbd'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="porcentaje_descuento"><?php esc_html_e('Porcentaje de Descuento', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="porcentaje_descuento" 
                                           name="porcentaje_descuento" 
                                           class="regular-text" 
                                           step="0.1" 
                                           min="0" 
                                           max="100" 
                                           required
                                           placeholder="<?php esc_attr_e('Ej: 10', 'wbd'); ?>">
                                    <span>%</span>
                                    <p class="description"><?php esc_html_e('Porcentaje que se aplicará como descuento en la segunda unidad', 'wbd'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="activo"><?php esc_html_e('Estado', 'wbd'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="activo" name="activo" value="1" checked>
                                        <?php esc_html_e('Bolsa activa', 'wbd'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Solo las bolsas activas aplicarán descuentos', 'wbd'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button(__('Crear Bolsa de Descuento', 'wbd'), 'primary', 'submit', false); ?>
                </form>
            </div>

            <!-- Lista de bolsas existentes -->
            <div class="wbd-list-section">
                <h3><?php esc_html_e('Bolsas de Descuento Registradas', 'wbd'); ?></h3>
                
                <?php if (!empty($bolsas)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'wbd'); ?></th>
                            <th><?php esc_html_e('Producto', 'wbd'); ?></th>
                            <th><?php esc_html_e('Proveedor', 'wbd'); ?></th>
                            <th><?php esc_html_e('Monto Inicial', 'wbd'); ?></th>
                            <th><?php esc_html_e('Saldo Disponible', 'wbd'); ?></th>
                            <th><?php esc_html_e('% Descuento', 'wbd'); ?></th>
                            <th><?php esc_html_e('Estado', 'wbd'); ?></th>
                            <th><?php esc_html_e('Fecha Creación', 'wbd'); ?></th>
                            <th><?php esc_html_e('Acciones', 'wbd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bolsas as $bolsa): ?>
                        <tr>
                            <td><?php echo esc_html($bolsa->id); ?></td>
                            <td>
                                <strong><?php echo esc_html($bolsa->producto_nombre ?: 'Producto #' . $bolsa->producto_id); ?></strong>
                                <br><small>#<?php echo esc_html($bolsa->producto_id); ?></small>
                            </td>
                            <td><?php echo esc_html($bolsa->proveedor_nombre ?: 'Proveedor #' . $bolsa->proveedor_id); ?></td>
                            <td><?php echo wc_price($bolsa->monto_inicial); ?></td>
                            <td>
                                <strong <?php echo $bolsa->monto_disponible <= 0 ? 'style="color: #d63638;"' : ''; ?>>
                                    <?php echo wc_price($bolsa->monto_disponible); ?>
                                </strong>
                                <?php if ($bolsa->monto_disponible <= 0): ?>
                                    <br><small style="color: #d63638;"><?php esc_html_e('Sin fondos', 'wbd'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($bolsa->porcentaje_descuento); ?>%</td>
                            <td>
                                <span class="wbd-status wbd-status-<?php echo esc_attr($bolsa->activo ? 'activo' : 'inactivo'); ?>">
                                    <?php echo esc_html($bolsa->activo ? 'Activo' : 'Inactivo'); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(mysql2date('d/m/Y H:i', $bolsa->fecha_creacion)); ?></td>
                            <td>
                                <a href="<?php echo esc_url($this->get_edit_bolsa_url($bolsa->id)); ?>" 
                                   class="button button-small">
                                    <?php esc_html_e('Editar', 'wbd'); ?>
                                </a>
                                
                                <a href="<?php echo esc_url($this->get_delete_bolsa_url($bolsa->id)); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php esc_attr_e('¿Estás seguro de eliminar esta bolsa?', 'wbd'); ?>')">
                                    <?php esc_html_e('Eliminar', 'wbd'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php esc_html_e('No hay bolsas de descuento registradas. Crea la primera arriba.', 'wbd'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
        $this->render_admin_footer();
    }

    /**
     * Página de reportes completa
     */
    public function reportes_page() {
        $this->render_admin_header(__('Reportes de Descuentos Aplicados', 'wbd'));
        
        global $wpdb;
        
        // Obtener filtros de la URL
        $fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : date('Y-m-d', strtotime('-30 days'));
        $fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : date('Y-m-d');
        $proveedor_id = isset($_GET['proveedor_id']) ? intval($_GET['proveedor_id']) : 0;
        $producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;
        
        // Obtener proveedores para el filtro
        $proveedores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}proveedores WHERE activo = 1 ORDER BY nombre ASC");
        
        // Construir consulta de reportes
        $where_clauses = array('1=1');
        $params = array();
        
        if (!empty($fecha_desde)) {
            $where_clauses[] = 'da.fecha_aplicacion >= %s';
            $params[] = $fecha_desde . ' 00:00:00';
        }
        
        if (!empty($fecha_hasta)) {
            $where_clauses[] = 'da.fecha_aplicacion <= %s';
            $params[] = $fecha_hasta . ' 23:59:59';
        }
        
        if ($proveedor_id > 0) {
            $where_clauses[] = 'da.proveedor_id = %d';
            $params[] = $proveedor_id;
        }
        
        if ($producto_id > 0) {
            $where_clauses[] = 'da.producto_id = %d';
            $params[] = $producto_id;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $query = "
            SELECT 
                da.*,
                p.nombre as proveedor_nombre,
                po.post_title as producto_nombre
            FROM {$wpdb->prefix}descuentos_aplicados da
            LEFT JOIN {$wpdb->prefix}proveedores p ON da.proveedor_id = p.id
            LEFT JOIN {$wpdb->posts} po ON da.producto_id = po.ID
            WHERE $where_sql
            ORDER BY da.fecha_aplicacion DESC
        ";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $descuentos = $wpdb->get_results($query);
        
        // Calcular estadísticas
        $total_descuentos = count($descuentos);
        $monto_total_descontado = array_sum(array_column($descuentos, 'monto_descontado'));
        ?>
        
        <div class="wbd-admin-page">
            <!-- Formulario de filtros -->
            <div class="wbd-form-section">
                <h3><?php esc_html_e('Filtros de Reporte', 'wbd'); ?></h3>
                
                <form method="get" action="">
                    <input type="hidden" name="page" value="wbd-reportes">
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Rango de Fechas', 'wbd'); ?></th>
                                <td>
                                    <input type="date" name="fecha_desde" value="<?php echo esc_attr($fecha_desde); ?>" class="regular-text">
                                    <?php esc_html_e('hasta', 'wbd'); ?>
                                    <input type="date" name="fecha_hasta" value="<?php echo esc_attr($fecha_hasta); ?>" class="regular-text">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Proveedor', 'wbd'); ?></th>
                                <td>
                                    <select name="proveedor_id" class="regular-text">
                                        <option value="0"><?php esc_html_e('Todos los proveedores', 'wbd'); ?></option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo esc_attr($proveedor->id); ?>" 
                                                    <?php selected($proveedor_id, $proveedor->id); ?>>
                                                <?php echo esc_html($proveedor->nombre); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Producto (ID)', 'wbd'); ?></th>
                                <td>
                                    <input type="number" name="producto_id" value="<?php echo esc_attr($producto_id); ?>" 
                                           class="regular-text" placeholder="<?php esc_attr_e('ID del producto', 'wbd'); ?>">
                                    <p class="description"><?php esc_html_e('Opcional: filtrar por ID específico de producto', 'wbd'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button(__('Filtrar Reportes', 'wbd'), 'primary', 'submit', false); ?>
                </form>
            </div>

            <!-- Estadísticas del período -->
            <div class="wbd-stats-section">
                <h3><?php esc_html_e('Estadísticas del Período', 'wbd'); ?></h3>
                <div class="wbd-stats-grid">
                    <div class="wbd-stat-card">
                        <div class="wbd-stat-number"><?php echo esc_html($total_descuentos); ?></div>
                        <div class="wbd-stat-label"><?php esc_html_e('Descuentos Aplicados', 'wbd'); ?></div>
                    </div>
                    <div class="wbd-stat-card">
                        <div class="wbd-stat-number"><?php echo wc_price($monto_total_descontado); ?></div>
                        <div class="wbd-stat-label"><?php esc_html_e('Total Descontado', 'wbd'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Tabla de reportes -->
            <div class="wbd-list-section">
                <h3><?php esc_html_e('Detalle de Descuentos Aplicados', 'wbd'); ?></h3>
                
                <?php if (!empty($descuentos)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Fecha/Hora', 'wbd'); ?></th>
                            <th><?php esc_html_e('Pedido', 'wbd'); ?></th>
                            <th><?php esc_html_e('Producto', 'wbd'); ?></th>
                            <th><?php esc_html_e('Proveedor', 'wbd'); ?></th>
                            <th><?php esc_html_e('% Aplicado', 'wbd'); ?></th>
                            <th><?php esc_html_e('Monto Descontado', 'wbd'); ?></th>
                            <th><?php esc_html_e('Saldo Restante', 'wbd'); ?></th>
                            <th><?php esc_html_e('Cantidad', 'wbd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($descuentos as $descuento): ?>
                        <tr>
                            <td><?php echo esc_html(mysql2date('d/m/Y H:i:s', $descuento->fecha_aplicacion)); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $descuento->pedido_id . '&action=edit')); ?>" target="_blank">
                                    #<?php echo esc_html($descuento->pedido_id); ?>
                                </a>
                            </td>
                            <td>
                                <strong><?php echo esc_html($descuento->producto_nombre ?: 'Producto eliminado'); ?></strong>
                                <br><small>ID: <?php echo esc_html($descuento->producto_id); ?></small>
                            </td>
                            <td><?php echo esc_html($descuento->proveedor_nombre ?: 'Proveedor eliminado'); ?></td>
                            <td><?php echo esc_html($descuento->porcentaje_aplicado); ?>%</td>
                            <td><strong><?php echo wc_price($descuento->monto_descontado); ?></strong></td>
                            <td><?php echo wc_price($descuento->saldo_restante); ?></td>
                            <td><?php echo esc_html($descuento->cantidad_productos); ?> unidades</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Botón para exportar -->
                <p class="submit">
                    <button type="button" class="button button-secondary" onclick="window.print();">
                        <?php esc_html_e('Imprimir Reporte', 'wbd'); ?>
                    </button>
                </p>
                
                <?php else: ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No se encontraron descuentos aplicados en el período seleccionado.', 'wbd'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
        $this->render_admin_footer();
    }

    /**
     * Procesar formulario de bolsa con verificación nonce
     */
    public function process_bolsa_form() {
        // Verificar permisos
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'wbd'));
        }

        // Verificar nonce
        if (!isset($_POST[self::NONCE_FIELD]) || 
            !wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_BOLSA_ACTION)) {
            wp_die(__('Token de seguridad inválido. Por favor, inténtalo de nuevo.', 'wbd'));
        }

        // Sanitizar datos
        $producto_id = intval($_POST['producto_id'] ?? 0);
        $proveedor_id = intval($_POST['proveedor_id'] ?? 0);
        $monto_inicial = floatval($_POST['monto_inicial'] ?? 0);
        $porcentaje_descuento = floatval($_POST['porcentaje_descuento'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;

        // Validaciones
        if ($producto_id <= 0) {
            wp_die(__('Debe seleccionar un producto válido.', 'wbd'));
        }

        if ($proveedor_id <= 0) {
            wp_die(__('Debe seleccionar un proveedor válido.', 'wbd'));
        }

        if ($monto_inicial <= 0) {
            wp_die(__('El monto inicial debe ser mayor a 0.', 'wbd'));
        }

        if ($porcentaje_descuento <= 0 || $porcentaje_descuento > 100) {
            wp_die(__('El porcentaje debe estar entre 0 y 100.', 'wbd'));
        }

        // Verificar que no exista ya una bolsa para este producto y proveedor
        global $wpdb;
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bolsas_descuento WHERE producto_id = %d AND proveedor_id = %d",
            $producto_id, $proveedor_id
        ));

        if ($existe > 0) {
            wp_die(__('Ya existe una bolsa para este producto y proveedor. Edita la existente.', 'wbd'));
        }

        // Insertar nueva bolsa
        $result = $wpdb->insert(
            $wpdb->prefix . 'bolsas_descuento',
            array(
                'proveedor_id' => $proveedor_id,
                'producto_id' => $producto_id,
                'monto_disponible' => $monto_inicial,
                'monto_inicial' => $monto_inicial,
                'porcentaje_descuento' => $porcentaje_descuento,
                'activo' => $activo,
                'fecha_creacion' => current_time('mysql')
            ),
            array('%d', '%d', '%f', '%f', '%f', '%d', '%s')
        );

        if ($result === false) {
            wp_die(__('Error al crear la bolsa de descuento.', 'wbd'));
        }

        // Redirigir con mensaje de éxito
        wp_redirect(add_query_arg(
            array('page' => 'wbd-bolsas', 'message' => 'bolsa_saved'),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Manejar acciones de proveedores (placeholder)
     */
    private function handle_proveedor_actions() {
        // Implementado en process_proveedor_form
    }

    /**
     * Manejar acciones de bolsas (placeholder)
     */
    private function handle_bolsa_actions() {
        // Implementado en process_bolsa_form
    }

    /**
     * Obtener URL para editar bolsa
     */
    private function get_edit_bolsa_url($bolsa_id) {
        return add_query_arg(
            array(
                'page' => 'wbd-bolsas',
                'action' => 'edit',
                'bolsa_id' => $bolsa_id
            ),
            admin_url('admin.php')
        );
    }

    /**
     * Obtener URL para eliminar bolsa
     */
    private function get_delete_bolsa_url($bolsa_id) {
        return wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'wbd_delete_bolsa',
                    'bolsa_id' => $bolsa_id
                ),
                admin_url('admin-post.php')
            ),
            'delete_bolsa_' . $bolsa_id
        );
    }

    // Métodos adicionales que faltaban...
    public function add_product_metabox() {
        // Implementar metabox si se necesita
    }

    // Métodos para procesar eliminación de bolsas...
    public function process_delete_bolsa() {
        // Verificar permisos y nonce similar a delete_proveedor
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'wbd'));
        }

        if (!isset($_GET['_wpnonce']) || 
            !wp_verify_nonce($_GET['_wpnonce'], 'delete_bolsa_' . intval($_GET['bolsa_id']))) {
            wp_die(__('Token de seguridad inválido.', 'wbd'));
        }

        $bolsa_id = intval($_GET['bolsa_id']);
        
        if ($bolsa_id <= 0) {
            wp_die(__('ID de bolsa inválido.', 'wbd'));
        }

        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'bolsas_descuento',
            array('id' => $bolsa_id),
            array('%d')
        );

        if ($result === false) {
            wp_die(__('Error al eliminar la bolsa.', 'wbd'));
        }

        wp_redirect(add_query_arg(
            array('page' => 'wbd-bolsas', 'message' => 'bolsa_deleted'),
            admin_url('admin.php')
        ));
        exit;
    }

    // Métodos adicionales para bolsas, reportes, etc...
    // [Implementación completa de seguridad con nonce]
}
