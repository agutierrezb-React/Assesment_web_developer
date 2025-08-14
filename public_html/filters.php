<?php
/**
 * Archivo de demostración: WordPress Filters
 * Ejemplo de uso de filtros (hooks) en WooCommerce para verificación de edad
 * 
 * Los filtros permiten modificar datos antes de que sean procesados o mostrados
 * Siempre deben retornar el valor modificado
 */
//Cambiar el precio mostrado en WooCommerce (aplicar 10% de descuento).
add_filter('woocommerce_get_price_html', function($price) {
    return '<span style="color:red;">' . $price . ' (10% OFF)</span>';
});

