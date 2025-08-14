<?php
/**
 * ========================================
 * HEADER COMPLETO DEL PLUGIN NORMAL
 * ========================================
 * 
 * Plugin Name: Tienda Licores - Sistema de Promociones
 * Plugin URI: https://tienda-licores.com
 * Description: Sistema de descuentos y promociones para productos alcohólicos
 * Version: 1.2.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 * Text Domain: licores-promociones
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * 
 * ANÁLISIS DEL HEADER:
 * 
 * Plugin Name: OBLIGATORIO - Nombre visible en admin de WordPress
 * Plugin URI: URL del plugin (sitio web, repositorio GitHub, etc.)
 * Description: Descripción que aparece en la lista de plugins
 * Version: Control de versiones (importante para actualizaciones)
 * Author: Desarrollador o empresa responsable
 * Text Domain: CRÍTICO - Identificador para traducciones (debe coincidir con slug)
 * Domain Path: Ruta donde están los archivos de traducción (.po/.mo)
 * Requires at least: Versión mínima de WordPress requerida
 * Tested up to: Última versión de WordPress donde se probó
 * WC requires at least: Versión mínima de WooCommerce requerida
 * WC tested up to: Última versión de WooCommerce donde se probó
 * 
 * DIFERENCIAS CON MU-PLUGINS:
 * - Header más completo (mu-plugins solo necesitan Plugin Name)
 * - Información de compatibilidad para repositorio oficial
 * - Control de dependencias y versiones
 * - Metadata para actualizaciones automáticas
 */

/**
 * MEDIDA DE SEGURIDAD: PREVENIR ACCESO DIRECTO
 * 
 * ABSPATH: Constante definida por WordPress en wp-config.php
 * - Contiene la ruta absoluta al directorio raíz de WordPress
 * - Solo está definida cuando se carga WordPress correctamente
 * 
 * ¿Qué previene esto?
 * - Acceso directo vía URL: ejemplo.com/wp-content/plugins/mi-plugin/mi-archivo.php
 * - Ejecución de código fuera del contexto de WordPress
 * - Posibles vulnerabilidades de seguridad
 * - Exposición de información sensible
 * 
 * BUENA PRÁCTICA: Incluir en TODOS los archivos PHP de plugins/temas
 */
if (!defined('ABSPATH')) {
    exit; // Terminar ejecución inmediatamente si no hay contexto de WordPress
}

/**
 * ========================================
 * VERIFICACIÓN DE DEPENDENCIAS
 * ========================================
 * 
 * PROPÓSITO: Verificar que WooCommerce esté activo antes de continuar
 * 
 * ANÁLISIS DE LA VERIFICACIÓN:
 * 
 * get_option('active_plugins'): Obtiene array de plugins activos desde la base de datos
 * - Devuelve array con paths relativos de plugins activos
 * - Ejemplo: ['woocommerce/woocommerce.php', 'akismet/akismet.php']
 * 
 * apply_filters('active_plugins', ...): Aplica filtros al array de plugins activos
 * - Permite que otros plugins modifiquen la lista
 * - Importante para compatibilidad con mu-plugins y networks
 * 
 * in_array(): Verifica si un valor existe en un array
 * - Busca 'woocommerce/woocommerce.php' en la lista de plugins activos
 * - Devuelve boolean (true si está activo, false si no)
 * 
 * !in_array(): Negación - true si WooCommerce NO está activo
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    /**
     * MOSTRAR AVISO DE ERROR EN ADMIN
     * 
     * add_action('admin_notices', ...): Hook para mostrar avisos en admin
     * - Se ejecuta en todas las páginas del admin
     * - Lugar estándar para mostrar errores, advertencias o información
     * 
     * Anonymous function: Función sin nombre para código simple
     * - Evita contaminar namespace global
     * - Código inline para lógica específica
     */
    add_action('admin_notices', function() {
        /**
         * ESTRUCTURA HTML PARA AVISOS DE WORDPRESS
         * 
         * .notice: Clase CSS base para avisos en WordPress admin
         * .notice-error: Tipo de aviso (error = rojo, warning = amarillo, success = verde)
         * 
         * TIPOS DE AVISOS:
         * - notice-error: Errores críticos
         * - notice-warning: Advertencias
         * - notice-success: Confirmaciones exitosas
         * - notice-info: Información general
         */
        echo '<div class="notice notice-error"><p>';
        echo __('El plugin Licores Promociones requiere WooCommerce para funcionar.', 'licores-promociones');
        echo '</p></div>';
    });
    
    /**
     * RETURN: Detener ejecución del plugin
     * 
     * Al usar return aquí:
     * - Se detiene la ejecución del resto del archivo
     * - El plugin no se carga si falta WooCommerce
     * - Previene errores fatales por funciones inexistentes
     * - Muestra el aviso pero no rompe el sitio
     */
    return;
}

/**
 * ========================================
 * CLASE PRINCIPAL DEL PLUGIN (OOP)
 * ========================================
 * 
 * PATRÓN DE DISEÑO: Programación Orientada a Objetos
 * 
 * ¿Por qué usar una clase?
 * - Encapsulación: Agrupa funcionalidad relacionada
 * - Namespace: Evita colisiones de nombres de funciones
 * - Mantenimiento: Código más organizado y fácil de mantener
 * - Escalabilidad: Fácil agregar nuevas características
 * - Singleton pattern: Una sola instancia del plugin
 * 
 * CONVENCIÓN DE NOMENCLATURA:
 * - PascalCase para nombres de clase (LicoresPromociones)
 * - Nombres descriptivos que indican propósito
 * - Prefijo del dominio para evitar colisiones
 */
class LicoresPromociones {
    
    /**
     * ========================================
     * CONSTRUCTOR: INICIALIZACIÓN DEL PLUGIN
     * ========================================
     * 
     * __construct(): Método especial que se ejecuta al instanciar la clase
     * - Se ejecuta automáticamente con 'new LicoresPromociones()'
     * - Lugar ideal para registrar hooks y configuración inicial
     * - No debe contener lógica pesada (delegarla a métodos específicos)
     * 
     * PATRON: Hook Registration en Constructor
     * - Registrar todos los hooks principales aquí
     * - Delegar funcionalidad específica a métodos dedicados
     * - Mantener constructor limpio y legible
     */
    public function __construct() {
        /**
         * HOOK: 'init' - Inicialización de WordPress
         * 
         * array($this, 'init'): Callback a método de la clase actual
         * - $this: Referencia a la instancia actual de la clase
         * - 'init': Nombre del método a ejecutar
         * - Equivale a: [$this, 'init'] en PHP 5.4+
         * 
         * ¿Por qué usar array($this, 'método')?
         * - Permite usar métodos de clase como callbacks
         * - Mantiene el contexto del objeto ($this)
         * - Acceso a propiedades y métodos privados/protected
         */
        add_action('init', array($this, 'init'));
        
        /**
         * HOOK: WooCommerce - Antes de calcular totales
         * 
         * 'woocommerce_before_calculate_totals': Hook crítico para modificar precios
         * - Se ejecuta cada vez que se recalcula el carrito
         * - Momento perfecto para aplicar descuentos dinámicos
         * - Tiene acceso completo al carrito y productos
         */
        add_action('woocommerce_before_calculate_totals', array($this, 'aplicar_descuentos_especiales'));
        
        /**
         * HOOKS DE ACTIVACIÓN/DESACTIVACIÓN
         * 
         * register_activation_hook(): Se ejecuta al activar el plugin
         * - __FILE__: Constante que contiene la ruta del archivo actual
         * - Ideal para: crear tablas, opciones por defecto, migraciones
         * 
         * register_deactivation_hook(): Se ejecuta al desactivar el plugin
         * - Ideal para: limpiar datos temporales, eventos programados
         * - NO eliminar datos permanentes (usar uninstall.php)
         * 
         * IMPORTANTE: Estos hooks NO funcionan en mu-plugins
         */
        register_activation_hook(__FILE__, array($this, 'activar_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'desactivar_plugin'));
    }
    
    /**
     * ========================================
     * MÉTODO: INICIALIZACIÓN DEL PLUGIN
     * ========================================
     * 
     * init(): Configuración que requiere que WordPress esté completamente cargado
     * - Separar del constructor para mejor organización
     * - Momento seguro para configuraciones que dependen de WordPress
     */
    public function init() {
        /**
         * CARGA DE TRADUCCIONES
         * 
         * load_plugin_textdomain(): Cargar archivos de traducción del plugin
         * 
         * Parámetros:
         * 1. 'licores-promociones': Text domain (debe coincidir con header)
         * 2. false: Deprecated parameter (siempre usar false)
         * 3. Ruta relativa a la carpeta de traducciones
         * 
         * dirname(plugin_basename(__FILE__)): Obtiene directorio del plugin
         * - plugin_basename(): Convierte ruta absoluta a relativa desde wp-content/plugins/
         * - dirname(): Obtiene el directorio padre
         * - . '/languages': Concatena ruta a carpeta de traducciones
         * 
         * ARCHIVOS DE TRADUCCIÓN:
         * - .pot: Template de traducción (para traductores)
         * - .po: Archivo de traducción editable
         * - .mo: Archivo compilado que usa WordPress
         * 
         * CONVENCIÓN DE NOMBRES:
         * - licores-promociones-es_ES.po (español de España)
         * - licores-promociones-en_US.po (inglés de Estados Unidos)
         */
        load_plugin_textdomain('licores-promociones', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        /**
         * REGISTRAR MENÚ DE ADMINISTRACIÓN
         * 
         * Hook 'admin_menu': Se ejecuta al construir el menú de admin
         * - Solo se ejecuta en el área administrativa
         * - Momento perfecto para agregar páginas y menús
         */
        add_action('admin_menu', array($this, 'agregar_menu_admin'));
    }
    
    /**
     * ========================================
     * MÉTODO: APLICAR DESCUENTOS ESPECIALES
     * ========================================
     * 
     * PROPÓSITO: Descuentos automáticos basados en día de la semana
     * - Viernes: 20% descuento en vinos
     * - Sábado: 15% descuento en whisky
     * 
     * PATRÓN: Time-based dynamic pricing
     */
    public function aplicar_descuentos_especiales() {
        /**
         * VALIDACIÓN DE CONTEXTO
         * 
         * is_admin(): Verificar si estamos en admin
         * DOING_AJAX: Constante definida durante requests AJAX
         * 
         * ¿Por qué verificar DOING_AJAX?
         * - Algunos procesos admin usan AJAX desde frontend
         * - Carrito en WooCommerce puede usar AJAX
         * - Necesitamos permitir ejecución durante AJAX del carrito
         */
        if (is_admin() && !defined('DOING_AJAX')) return;
        
        /**
         * OBTENER DÍA DE LA SEMANA
         * 
         * date('w'): Obtiene día de la semana como número
         * - 0 = Domingo
         * - 1 = Lunes
         * - 2 = Martes
         * - 3 = Miércoles
         * - 4 = Jueves
         * - 5 = Viernes
         * - 6 = Sábado
         * 
         * ALTERNATIVAS:
         * - date('N'): 1-7 (Lunes = 1, Domingo = 7)
         * - date('l'): Nombre completo del día ('Monday', 'Tuesday')
         */
        $dia_semana = date('w');
        
        /**
         * ITERAR PRODUCTOS EN EL CARRITO
         * 
         * WC()->cart->get_cart(): Array con todos los items del carrito
         * Cada item contiene información completa del producto y cantidad
         */
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data']; // Objeto WC_Product
            $descuento = 0; // Inicializar descuento
            
            /**
             * LÓGICA DE DESCUENTOS POR DÍA Y CATEGORÍA
             * 
             * Estructura if-elseif para evaluar múltiples condiciones:
             * 1. Verificar día de la semana
             * 2. Verificar categoría del producto
             * 3. Aplicar descuento correspondiente
             * 
             * VIERNES DE VINOS (día 5)
             * - 20% de descuento en productos de categoría 'vinos'
             * - has_term(): Verifica si producto tiene la categoría especificada
             */
            if ($dia_semana == 5 && has_term('vinos', 'product_cat', $product->get_id())) {
                $descuento = 0.20; // 20% = 0.20 en decimal
            }
            /**
             * SÁBADO DE WHISKY (día 6)
             * - 15% de descuento en productos de categoría 'whisky'
             * - elseif: Solo se evalúa si la condición anterior fue falsa
             */
            elseif ($dia_semana == 6 && has_term('whisky', 'product_cat', $product->get_id())) {
                $descuento = 0.15; // 15% = 0.15 en decimal
            }
            
            /**
             * APLICAR DESCUENTO SI CORRESPONDE
             * 
             * Solo proceder si hay descuento que aplicar (> 0)
             * Evita cálculos innecesarios en productos sin descuento
             */
            if ($descuento > 0) {
                /**
                 * CÁLCULO DEL NUEVO PRECIO
                 * 
                 * get_price(): Obtiene precio actual del producto
                 * - Puede ser precio base o ya modificado por otros descuentos
                 * - Devuelve float/decimal
                 * 
                 * FÓRMULA: nuevo_precio = precio_original × (1 - descuento)
                 * - Ejemplo: $100 × (1 - 0.20) = $100 × 0.80 = $80
                 * - Más eficiente que restar porcentaje manualmente
                 */
                $precio_original = $product->get_price();
                $nuevo_precio = $precio_original * (1 - $descuento);
                
                /**
                 * APLICAR NUEVO PRECIO AL PRODUCTO
                 * 
                 * set_price(): Modifica temporalmente el precio del producto
                 * - Solo afecta la sesión actual del carrito
                 * - NO modifica el precio en base de datos
                 * - El precio se recalcula en cada carga de página
                 * 
                 * IMPORTANTE: Este cambio es temporal y dinámico
                 */
                $product->set_price($nuevo_precio);
            }
        }
    }
    
    /**
     * ========================================
     * MÉTODO: AGREGAR MENÚ EN ADMINISTRACIÓN
     * ========================================
     * 
     * PROPÓSITO: Crear página de configuración en el admin de WordPress
     */
    public function agregar_menu_admin() {
        /**
         * AGREGAR SUBMENÚ A WOOCOMMERCE
         * 
         * add_submenu_page(): Agrega página como submenú de otra existente
         * 
         * Parámetros:
         * 1. 'woocommerce': Slug del menú padre (aparece bajo WooCommerce)
         * 2. Page title: Título que aparece en la etiqueta <title> del navegador
         * 3. Menu title: Texto que aparece en el menú del admin
         * 4. Capability: Permiso requerido para acceder a la página
         * 5. Menu slug: Identificador único de la página
         * 6. Callback: Función que genera el contenido de la página
         * 
         * CAPABILITIES COMUNES:
         * - 'manage_options': Solo administradores
         * - 'manage_woocommerce': Puede gestionar WooCommerce
         * - 'edit_posts': Puede editar posts
         * - 'read': Cualquier usuario logueado
         */
        add_submenu_page(
            'woocommerce',                                          // Menú padre
            __('Promociones Licores', 'licores-promociones'),       // Page title
            __('Promociones', 'licores-promociones'),               // Menu title
            'manage_woocommerce',                                   // Capability
            'licores-promociones',                                  // Menu slug
            array($this, 'pagina_admin')                           // Callback
        );
    }
    
    /**
     * ========================================
     * MÉTODO: PÁGINA DE ADMINISTRACIÓN
     * ========================================
     * 
     * PROPÓSITO: Generar el HTML de la página de configuración
     * 
     * PATRÓN: Esta función se ejecuta cuando el usuario accede al menú
     */
    public function pagina_admin() {
        /**
         * ESTRUCTURA HTML ESTÁNDAR DE WORDPRESS ADMIN
         * 
         * .wrap: Clase CSS estándar para páginas de admin
         * - Proporciona padding y styling consistente
         * - Usado en todas las páginas nativas de WordPress
         * 
         * <h1>: Título principal de la página
         * - Debe usar traducción para soporte multiidioma
         * - Identifica claramente la función de la página
         */
        echo '<div class="wrap">';
        echo '<h1>' . __('Gestión de Promociones', 'licores-promociones') . '</h1>';
        echo '<p>' . __('Configure aquí las promociones especiales para su tienda de licores.', 'licores-promociones') . '</p>';
        // TODO: Aquí iría el formulario de configuración, tablas de datos, etc.
        echo '</div>';
    }
    
    /**
     * ========================================
     * MÉTODO: ACTIVACIÓN DEL PLUGIN
     * ========================================
     * 
     * PROPÓSITO: Configuración inicial cuando se activa el plugin
     * - Se ejecuta UNA SOLA VEZ al activar
     * - Ideal para: crear opciones, tablas, configuración inicial
     */
    public function activar_plugin() {
        /**
         * CREAR OPCIÓN POR DEFECTO
         * 
         * add_option(): Agrega nueva opción a la base de datos
         * - Solo agrega si no existe (no sobrescribe)
         * - Se almacena en tabla wp_options
         * 
         * Parámetros:
         * 1. Nombre de la opción (clave única)
         * 2. Valor por defecto
         * 3. Autoload (opcional): si cargar automáticamente
         * 
         * CONVENCIÓN DE NOMENCLATURA:
         * - Prefijo del plugin + descripción descriptiva
         * - Usar guiones bajos para separar palabras
         */
        add_option('licores_promociones_activas', 'yes');
        
        /**
         * PROGRAMAR EVENTO RECURRENTE
         * 
         * wp_next_scheduled(): Verifica si hay un evento programado
         * - Devuelve timestamp si existe, false si no
         * - Evita duplicar eventos programados
         * 
         * wp_schedule_event(): Programa evento recurrente
         * 
         * Parámetros:
         * 1. time(): Timestamp de la primera ejecución
         * 2. 'daily': Frecuencia (hourly, twicedaily, daily, weekly)
         * 3. Hook name: Nombre del hook a ejecutar
         * 
         * CRON EN WORDPRESS:
         * - Sistema de tareas programadas interno
         * - Se ejecuta cuando hay visitantes al sitio
         * - Para tareas como: limpieza, envío de emails, backups
         */
        if (!wp_next_scheduled('licores_promociones_cleanup')) {
            wp_schedule_event(time(), 'daily', 'licores_promociones_cleanup');
        }
    }
    
    /**
     * ========================================
     * MÉTODO: DESACTIVACIÓN DEL PLUGIN
     * ========================================
     * 
     * PROPÓSITO: Limpieza al desactivar el plugin
     * - Se ejecuta UNA VEZ al desactivar
     * - NO eliminar datos permanentes (usar uninstall.php)
     * - Solo limpiar datos temporales y eventos programados
     */
    public function desactivar_plugin() {
        /**
         * LIMPIAR EVENTOS PROGRAMADOS
         * 
         * wp_clear_scheduled_hook(): Elimina todos los eventos de un hook
         * - Limpia todos los eventos programados con ese nombre
         * - Importante para evitar ejecuciones huérfanas
         * - Libera recursos del sistema cron de WordPress
         * 
         * BUENA PRÁCTICA:
         * - Siempre limpiar eventos programados al desactivar
         * - No dejar tareas programadas de plugins inactivos
         */
        wp_clear_scheduled_hook('licores_promociones_cleanup');
        
        // NOTA: NO eliminar opciones aquí, usar uninstall.php para eso
        // NOTA: NO eliminar tablas aquí, solo en desinstalación completa
    }
}

/**
 * ========================================
 * INSTANCIACIÓN DEL PLUGIN
 * ========================================
 * 
 * new LicoresPromociones(): Crear instancia de la clase
 * - Ejecuta automáticamente el constructor
 * - Inicia todos los hooks registrados
 * - El plugin comienza a funcionar
 * 
 * PATRÓN: Single Instance
 * - Una sola instancia del plugin en memoria
 * - Evita múltiples ejecuciones y conflictos
 * 
 * ALTERNATIVA AVANZADA (Singleton Pattern):
 * - Crear método estático get_instance()
 * - Garantizar una sola instancia globalmente
 * - Útil para plugins más complejos
 */
new LicoresPromociones();

/**
 * ========================================
 * CONCEPTOS CLAVE PARA ENTREVISTA
 * ========================================
 * 
 * 1. ESTRUCTURA DE PLUGIN NORMAL:
 *    - Header completo con metadatos
 *    - Verificación de dependencias
 *    - Manejo de errores graceful
 *    - Hooks de activación/desactivación
 * 
 * 2. PROGRAMACIÓN ORIENTADA A OBJETOS:
 *    - Encapsulación de funcionalidad
 *    - Namespace limpio
 *    - Métodos organizados por responsabilidad
 *    - Constructor para inicialización
 * 
 * 3. HOOKS Y CALLBACKS:
 *    - array($this, 'método') para callbacks de clase
 *    - Registro en constructor vs delegación en init
 *    - Hooks específicos de WooCommerce
 * 
 * 4. ADMINISTRACIÓN DE WORDPRESS:
 *    - Menús y submenús personalizados
 *    - Capabilities y permisos
 *    - Estructura HTML estándar del admin
 * 
 * 5. PRICING DINÁMICO:
 *    - Modificación temporal de precios
 *    - Lógica basada en condiciones externas
 *    - Performance considerations
 * 
 * 6. EVENTOS PROGRAMADOS (CRON):
 *    - Sistema de tareas recurrentes
 *    - Limpieza y mantenimiento automático
 *    - Gestión de eventos huérfanos
 * 
 * 7. INTERNACIONALIZACIÓN:
 *    - Text domains consistentes
 *    - Carga de traducciones
 *    - Preparación para múltiples idiomas
 */
