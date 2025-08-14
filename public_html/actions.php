<?php
/**
 * Archivo de demostración: WordPress Actions
 * Ejemplo de uso de acciones (hooks) en WooCommerce para tienda de licores
 * 
 * Las acciones permiten ejecutar código en momentos específicos del ciclo de WordPress
 * sin modificar el código core del sistema
 */
//Enviar un correo cuando se publica un post.
add_action('publish_post', function($post_id) {
    wp_mail('admin@tienda.com', 'Nuevo producto publicado', 'Se ha publicado un nuevo producto.');
});
