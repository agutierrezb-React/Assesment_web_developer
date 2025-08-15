<?php
/**
 * Plugin Name: Licores - Shortcode Total Pedidos
 * Description: Shortcode [total_pedidos] que muestra cuántos pedidos completados tiene el usuario actual.
 * Version: 1.0
 * Author: Aldair Gutierrez Desarrollador Web MEICO S.A
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// 1️⃣ Registrar el shortcode
add_shortcode('total_pedidos', 'lsp_shortcode_total_pedidos');

// 2️⃣ Función del shortcode
function lsp_shortcode_total_pedidos($atts) {
    // Verificar si el usuario está logueado
    if (!is_user_logged_in()) {
        return '<p class="woocommerce-info">🔒 Debes iniciar sesión para ver tus pedidos.</p>';
    }
    
    // Obtener ID del usuario actual
    $user_id = get_current_user_id();
    
    // Consultar pedidos completados del usuario
    $args = array(
        'customer_id' => $user_id,
        'status'      => 'completed',
        'limit'       => -1, // Sin límite para contar todos
        'return'      => 'ids' // Solo necesitamos los IDs para contar
    );
    
    $pedidos = wc_get_orders($args);
    $total_pedidos = count($pedidos);
    
    // Obtener información adicional del usuario
    $user_info = get_userdata($user_id);
    $nombre_usuario = $user_info->display_name;
    
    // Crear el HTML de respuesta
    $html = '<div class="shortcode-total-pedidos" style="
        background: #f8f9fa; 
        border: 2px solid #28a745; 
        border-radius: 8px; 
        padding: 20px; 
        margin: 15px 0; 
        text-align: center;
    ">';
    
   // Crear el HTML de respuesta con contenedor único
$html = '<div class="shortcode-total-pedidos wpedidos-container" style="all: unset; display: block; background: #f8f9fa; border: 2px solid #28a745; border-radius: 8px; padding: 20px; margin: 15px 0; text-align: center; font-family: Arial, sans-serif;">';

$html .= '<h3 style="color: #28a745; margin-top: 0;"> Resumen de Compras</h3>';
$html .= '<p><strong>Cliente:</strong> ' . esc_html($nombre_usuario) . '</p>';
    
    if ($total_pedidos > 0) {
        $html .= '<p style="font-size: 24px; color: #28a745; font-weight: bold;"> ' . $total_pedidos . ' pedido(s) completado(s)</p>';
        
        // Mensaje personalizado según cantidad de pedidos
        if ($total_pedidos == 1) {
            $html .= '<p style="color: #6c757d;">¡Gracias por tu primera compra en nuestra licorería!</p>';
        } elseif ($total_pedidos >= 2 && $total_pedidos <= 5) {
            $html .= '<p style="color: #6c757d;">¡Eres un cliente frecuente! Gracias por tu confianza.</p>';
        } else {
            $html .= '<p style="color: #6c757d;">🏆 ¡Cliente VIP! Más de 5 pedidos completados.</p>';
        }
        
        // Mostrar último pedido
        $ultimo_pedido = wc_get_orders(array(
            'customer_id' => $user_id,
            'status'      => 'completed',
            'limit'       => 1
        ));
        
        if (!empty($ultimo_pedido)) {
            $fecha_ultimo = $ultimo_pedido[0]->get_date_completed();
            $html .= '<p><small>Último pedido: ' . $fecha_ultimo->format('d/m/Y') . '</small></p>';
        }
        
    } else {
        $html .= '<p style="font-size: 18px; color: #dc3545;">📋 No tienes pedidos completados aún</p>';
        $html .= '<p style="color: #6c757d;">¡Explora nuestro catálogo de licores premium!</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// 3️⃣ Agregar estilos CSS adicionales (opcional)
add_action('wp_head', 'lsp_shortcode_estilos');
function lsp_shortcode_estilos() {
    echo '<style>
        .shortcode-total-pedidos {
            font-family: Arial, sans-serif;
        }
        .shortcode-total-pedidos h3 {
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .shortcode-total-pedidos {
                padding: 15px;
                margin: 10px 0;
            }
        }
    </style>';
}

// 4️⃣ Función auxiliar para mostrar información en widgets (opcional)
add_shortcode('total_pedidos_simple', 'lsp_shortcode_total_pedidos_simple');
function lsp_shortcode_total_pedidos_simple($atts) {
    if (!is_user_logged_in()) {
        return 'Inicia sesión';
    }
    
    $user_id = get_current_user_id();
    $pedidos = wc_get_orders(array(
        'customer_id' => $user_id,
        'status'      => 'completed',
        'limit'       => -1,
        'return'      => 'ids'
    ));
    
    return count($pedidos) . ' pedidos';
}
