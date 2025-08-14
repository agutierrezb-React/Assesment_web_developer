<?php
/**
 * ========================================
 * HEADER DEL PLUGIN (Must-Use Plugin)
 * ========================================
 * 
 * Plugin Name: Tienda Licores - Funcionalidades Core
 * Description: Funcionalidades críticas que nunca deben desactivarse
 * Version: 1.0.0
 * 
 * CONCEPTOS CLAVE:
 * - Este header es OBLIGATORIO para que WordPress reconozca el archivo como plugin
 * - Al estar en mu-plugins, se activa automáticamente y NO puede desactivarse
 * - Plugin Name: Nombre que aparece en el admin de WordPress
 * - Description: Descripción visible en la lista de plugins
 * - Version: Control de versiones para actualizaciones
 */

// Prevenir acceso directo al archivo (Buena práctica de seguridad)
if (!defined('ABSPATH')) {
    exit; // Si alguien accede directamente al archivo, terminar ejecución
}

/**
 * ========================================
 * FUNCIÓN 1: VERIFICACIÓN DE EDAD GLOBAL
 * ========================================
 * 
 * PROPÓSITO: Redirigir usuarios a página de verificación de edad cuando 
 * tengan productos alcohólicos en el carrito y no hayan verificado su edad
 * 
 * CONCEPTOS IMPORTANTES:
 * - Hook 'wp': Se ejecuta después de que WordPress termina de cargar
 * - Verificación de contexto: Solo frontend, no admin
 * - Singleton pattern: WC() devuelve la instancia única de WooCommerce
 * - Session management: Usar sesiones de WooCommerce para persistir datos
 */
function licores_verificacion_edad_global() {
    /**
     * VALIDACIÓN DE CONTEXTO
     * 
     * is_admin(): Verifica si estamos en el panel administrativo
     * - Devuelve true si estamos en /wp-admin/
     * - Previene ejecución innecesaria en backend
     * 
     * current_user_can('administrator'): Verifica capacidades del usuario
     * - Capability-based permissions de WordPress
     * - 'administrator' es el rol con máximos permisos
     * - Los administradores pueden saltarse verificaciones
     * 
     * ¿Por qué esta validación?
     * - Evitar ejecutar en admin (mejora rendimiento)
     * - Los administradores no necesitan verificación de edad
     * - Prevenir redirects infinitos en backend
     */
    if (!is_admin() && !current_user_can('administrator')) {
        
        /**
         * VERIFICACIÓN DEL CARRITO DE WOOCOMMERCE
         * 
         * WC(): Función singleton que devuelve la instancia principal de WooCommerce
         * - Patrón singleton: Una sola instancia global del objeto
         * - Evita múltiples instanciaciones innecesarias
         * 
         * ->cart: Acceso al objeto carrito de WooCommerce (WC_Cart)
         * - Maneja todos los productos en el carrito de compras
         * - Persiste datos entre requests usando sessions
         * 
         * ->is_empty(): Método que verifica si el carrito tiene productos
         * - Devuelve boolean (true/false)
         * - Más eficiente que contar productos
         * 
         * Verificamos que:
         * 1. WooCommerce esté disponible (WC() existe)
         * 2. El carrito exista ($cart no sea null)
         * 3. El carrito no esté vacío (!is_empty())
         */
        if (WC()->cart && !WC()->cart->is_empty()) {
            $tiene_alcohol = false; // Flag/bandera para detectar productos alcohólicos
            
            /**
             * ITERACIÓN DEL CARRITO
             * 
             * get_cart(): Devuelve array asociativo con todos los items del carrito
             * 
             * Cada $cart_item es un array que contiene:
             * - 'data': Objeto WC_Product (el producto en sí)
             * - 'quantity': Cantidad del producto en el carrito
             * - 'variation_id': ID de variación (para productos variables)
             * - 'variation': Array con datos de la variación
             * - 'line_total': Total de la línea (precio × cantidad)
             * - 'line_tax': Impuestos de la línea
             * 
             * foreach: Itera cada item del carrito
             */
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product = $cart_item['data']; // Extraer el objeto producto
                
                /**
                 * VERIFICACIÓN DE CATEGORÍAS ALCOHÓLICAS
                 * 
                 * has_term(): Función nativa de WordPress para verificar términos/taxonomías
                 * 
                 * Parámetros:
                 * 1. $terms: Array de términos a buscar ['vinos', 'whisky', 'cerveza', 'ron']
                 * 2. $taxonomy: 'product_cat' (taxonomía de categorías de productos WooCommerce)
                 * 3. $object_id: ID del producto a verificar
                 * 
                 * Devuelve: boolean
                 * - true: Si el producto tiene al menos una de estas categorías
                 * - false: Si no tiene ninguna de estas categorías
                 * 
                 * get_id(): Método del objeto WC_Product que devuelve el ID único
                 * 
                 * TAXONOMÍAS EN WORDPRESS:
                 * - Sistema de clasificación jerárquica
                 * - 'product_cat' es la taxonomía para categorías de productos
                 * - Cada término ('vinos', 'whisky') es un valor dentro de la taxonomía
                 */
                if (has_term(['vinos', 'whisky', 'cerveza', 'ron'], 'product_cat', $product->get_id())) {
                    $tiene_alcohol = true;
                    break; // OPTIMIZACIÓN: salir del bucle al encontrar el primer producto alcohólico
                }
            }
            
            /**
             * LÓGICA DE REDIRECCIÓN CONDICIONAL
             * 
             * WC()->session: Objeto de sesión de WooCommerce (WC_Session)
             * - Extiende las sesiones nativas de PHP
             * - Persiste datos entre páginas para usuarios no logueados
             * - Se limpia automáticamente después de un tiempo
             * 
             * ->get('edad_verificada'): Obtener valor de una variable de sesión
             * - Si no existe, devuelve null (falsy)
             * - Si existe y es true, indica que ya verificó la edad
             * 
             * CONDICIÓN LÓGICA:
             * $tiene_alcohol = true Y !WC()->session->get('edad_verificada') = true
             * Significa: Hay alcohol en carrito Y no se ha verificado la edad
             * 
             * wp_redirect(): Función nativa de WordPress para redirecciones HTTP
             * - Envía header Location al navegador
             * - Status code 302 (temporal) por defecto
             * 
             * home_url(): Genera URL base del sitio web
             * - Respeta protocolo (http/https)
             * - Incluye subdirectorios si WordPress está en subcarpeta
             * - '/verificacion-edad/' es la ruta de la página de verificación
             * 
             * exit: CRÍTICO - Detener ejecución del script
             * - Sin exit, el código continúa ejecutándose
             * - Podría causar comportamientos inesperados
             * - Es una buena práctica SIEMPRE usar exit después de wp_redirect
             */
            if ($tiene_alcohol && !WC()->session->get('edad_verificada')) {
                wp_redirect(home_url('/verificacion-edad/'));
                exit; // OBLIGATORIO después de redirección
            }
        }
    }
}

/**
 * REGISTRO DEL HOOK - ACCIÓN
 * 
 * add_action(): Registra una función para ejecutarse en un momento específico
 * 
 * Parámetros:
 * 1. $hook_name: 'wp' - Nombre del hook donde conectar la función
 * 2. $callback: 'licores_verificacion_edad_global' - Función a ejecutar
 * 3. $priority: 10 (default) - Orden de ejecución (números menores = antes)
 * 4. $accepted_args: 1 (default) - Cuántos argumentos acepta la función
 * 
 * Hook 'wp': Se ejecuta después de que:
 * - WordPress ha terminado de cargar completamente
 * - La query principal ha sido ejecutada
 * - Todo el contexto global está disponible (current_user, WC(), etc.)
 * - Se han cargado plugins y temas
 * 
 * ¿Por qué 'wp' y no 'init'?
 * - En 'init' WooCommerce cart puede no estar completamente disponible
 * - 'wp' garantiza que todo el contexto esté listo
 * - Es el momento ideal para lógica que depende del contexto completo
 * 
 * OTROS HOOKS IMPORTANTES:
 * - 'init': Después de cargar WordPress, antes del contenido
 * - 'wp_loaded': Después de cargar plugins, antes de queries
 * - 'template_redirect': Antes de cargar templates, ideal para redirects
 */
add_action('wp', 'licores_verificacion_edad_global');

/**
 * ========================================
 * FUNCIÓN 2: CONFIGURACIONES CRÍTICAS
 * ========================================
 * 
 * PROPÓSITO: Establecer configuraciones de seguridad y cumplimiento legal
 * que deben aplicarse SIEMPRE en la tienda de licores
 * 
 * CONCEPTO: Esta función actúa como un "contenedor" que registra 
 * múltiples hooks internos, organizando la funcionalidad relacionada
 */
function licores_configuraciones_criticas() {
    
    /**
     * CONFIGURACIÓN 1: FORZAR REGISTRO DE USUARIOS
     * 
     * add_filter(): Modifica un valor antes de que sea usado por WordPress
     * 
     * Hook: 'woocommerce_checkout_registration_required'
     * - Se ejecuta cuando WooCommerce verifica si requiere registro
     * - Por defecto, WooCommerce permite compras como invitado
     * 
     * Callback: '__return_true'
     * - Función helper nativa de WordPress
     * - Siempre devuelve boolean true
     * - Equivale a: function() { return true; }
     * 
     * RESULTADO: Los invitados NO pueden comprar, deben crear cuenta
     * 
     * ¿Por qué es importante en tienda de licores?
     * - Trazabilidad legal: saber quién compra alcohol
     * - Cumplimiento normativo: verificar edad por cuenta de usuario
     * - Control de compras: historial para detectar patrones sospechosos
     * - Responsabilidad corporativa: demostrar debida diligencia
     * 
     * FILTROS vs ACCIONES:
     * - Filtros: MODIFICAN datos y DEBEN devolver un valor
     * - Acciones: EJECUTAN código pero NO devuelven nada
     */
    add_filter('woocommerce_checkout_registration_required', '__return_true');
    
    /**
     * CONFIGURACIÓN 2: AVISO LEGAL EN CARRITO
     * 
     * Hook: 'woocommerce_before_cart'
     * - Se ejecuta antes de mostrar el contenido del carrito
     * - Ideal para avisos, promociones o información legal
     * - Solo se muestra en la página del carrito (/cart/)
     * 
     * Callback: Función anónima (closure/lambda)
     * - function() { ... } - No tiene nombre, se define inline
     * - Útil para código pequeño que solo se usa en un lugar
     * - Evita contaminar el namespace global con funciones pequeñas
     * 
     * ANONYMOUS FUNCTIONS en PHP:
     * - Introducidas en PHP 5.3
     * - Pueden capturar variables del scope padre con 'use'
     * - Se almacenan como valores en variables o se pasan directamente
     */
    add_action('woocommerce_before_cart', function() {
        echo '<div class="aviso-legal">'; // Contenedor con clase CSS para styling
        
        /**
         * INTERNACIONALIZACIÓN (i18n)
         * 
         * __(): Función de traducción de WordPress (double underscore)
         * 
         * Parámetros:
         * 1. $text: Texto en idioma base (generalmente inglés o español)
         * 2. $domain: Text domain 'licores-core' - identificador para traducciones
         * 
         * PROCESS:
         * 1. Busca archivo de traducción (.po/.mo) para el idioma actual
         * 2. Si encuentra traducción para el text domain, la devuelve
         * 3. Si no encuentra traducción, devuelve el texto original
         * 
         * TEXT DOMAIN:
         * - Identificador único para agrupar traducciones de un plugin/tema
         * - Debe coincidir con el slug del plugin
         * - Permite tener múltiples plugins con las mismas frases traducidas independientemente
         * 
         * BUENAS PRÁCTICAS:
         * - Siempre usar funciones de traducción para texto visible
         * - Text domain consistente en todo el plugin
         * - Preparar código para múltiples idiomas desde el inicio
         * 
         * OTRAS FUNCIONES DE TRADUCCIÓN:
         * - _e(): Imprime directamente (echo + __)
         * - _n(): Para plurales (singular/plural)
         * - _x(): Con contexto adicional
         * - esc_html__(): Traducción + escape para HTML
         */
        echo '<p><strong>AVISO:</strong> ' . __('La venta de bebidas alcohólicas está prohibida a menores de 18 años.', 'licores-core') . '</p>';
        echo '</div>';
    });
}

/**
 * REGISTRO DEL HOOK - CONFIGURACIONES
 * 
 * Hook 'init': Se ejecuta después de que WordPress ha cargado pero antes del contenido
 * 
 * ORDEN DE EJECUCIÓN DE HOOKS PRINCIPALES:
 * 1. 'plugins_loaded' - Después de cargar plugins
 * 2. 'init' - Inicialización de WordPress (👈 AQUÍ)
 * 3. 'wp_loaded' - WordPress completamente cargado
 * 4. 'wp' - Query principal ejecutada
 * 5. 'template_redirect' - Antes de cargar templates
 * 6. 'wp_head' - En la sección <head> del HTML
 * 
 * ¿Por qué usar 'init' aquí?
 * - Momento adecuado para registrar hooks adicionales
 * - WordPress está listo para recibir filtros y acciones
 * - Antes de que se procese el contenido de la página
 * - WooCommerce ya está disponible para hooks
 * 
 * PATRÓN DE DISEÑO:
 * - Función contenedora que agrupa hooks relacionados
 * - Facilita mantenimiento y organización del código
 * - Permite activar/desactivar grupos de funcionalidad
 */
add_action('init', 'licores_configuraciones_criticas');

/**
 * ========================================
 * CONCEPTOS CLAVE PARA ENTREVISTA
 * ========================================
 * 
 * 1. MU-PLUGINS:
 *    - Se cargan automáticamente
 *    - No pueden desactivarse
 *    - Ideales para funcionalidad crítica
 *    - Se cargan antes que plugins normales
 * 
 * 2. HOOKS SYSTEM:
 *    - Actions: Ejecutan código en momentos específicos
 *    - Filters: Modifican datos antes de usarlos
 *    - Prioridades: Controlan orden de ejecución
 * 
 * 3. WOOCOMMERCE INTEGRATION:
 *    - WC(): Singleton principal
 *    - Sessions: Persistir datos entre requests
 *    - Product Categories: Taxonomía 'product_cat'
 *    - Cart Management: WC()->cart
 * 
 * 4. SECURITY BEST PRACTICES:
 *    - Validación de contexto (is_admin, user capabilities)
 *    - Prevenir acceso directo (ABSPATH check)
 *    - Sanitización de datos
 *    - Exit después de redirects
 * 
 * 5. INTERNATIONALIZATION:
 *    - __() para traducciones
 *    - Text domains consistentes
 *    - Preparación para múltiples idiomas
 * 
 * 6. CODE ORGANIZATION:
 *    - Funciones descriptivas
 *    - Comentarios detallados
 *    - Agrupación lógica de funcionalidad
 *    - Uso de anonymous functions cuando apropiado
 */
