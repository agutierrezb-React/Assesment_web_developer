/**
 * JavaScript para el admin del plugin Bolsa de Descuentos
 * 
 * @package WooBolsaDescuentos
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Inicializar funcionalidades del admin
     */
    $(document).ready(function() {
        initFormValidation();
        initDataTables();
        initDatePickers();
        initConfirmDialogs();
        initAjaxActions();
    });
    
    /**
     * Validación de formularios
     */
    function initFormValidation() {
        // Validar formulario de proveedor
        $('#proveedor-form').on('submit', function(e) {
            var nombre = $('#nombre').val().trim();
            var email = $('#email').val().trim();
            
            if (!nombre) {
                alert('El nombre del proveedor es obligatorio.');
                e.preventDefault();
                return false;
            }
            
            if (email && !isValidEmail(email)) {
                alert('Por favor, ingresa un email válido.');
                e.preventDefault();
                return false;
            }
        });
        
        // Validar formulario de bolsa
        $('#bolsa-form').on('submit', function(e) {
            var productoId = $('#producto_id').val();
            var proveedorId = $('#proveedor_id').val();
            var montoInicial = parseFloat($('#monto_inicial').val());
            var porcentaje = parseFloat($('#porcentaje_descuento').val());
            
            if (!productoId) {
                alert('Debe seleccionar un producto.');
                e.preventDefault();
                return false;
            }
            
            if (!proveedorId) {
                alert('Debe seleccionar un proveedor.');
                e.preventDefault();
                return false;
            }
            
            if (isNaN(montoInicial) || montoInicial <= 0) {
                alert('El monto inicial debe ser mayor a 0.');
                e.preventDefault();
                return false;
            }
            
            if (isNaN(porcentaje) || porcentaje <= 0 || porcentaje > 100) {
                alert('El porcentaje debe estar entre 0 y 100.');
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Inicializar DataTables para tablas mejoradas
     */
    function initDataTables() {
        if ($.fn.DataTable) {
            $('.wbd-data-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                pageLength: 25,
                responsive: true,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: -1 } // Última columna (acciones) no ordenable
                ]
            });
        }
    }
    
    /**
     * Inicializar selectores de fecha
     */
    function initDatePickers() {
        // Configurar fecha por defecto para reportes (último mes)
        if ($('#fecha_desde').length && !$('#fecha_desde').val()) {
            var fechaHace30Dias = new Date();
            fechaHace30Dias.setDate(fechaHace30Dias.getDate() - 30);
            $('#fecha_desde').val(formatDate(fechaHace30Dias));
        }
        
        if ($('#fecha_hasta').length && !$('#fecha_hasta').val()) {
            $('#fecha_hasta').val(formatDate(new Date()));
        }
    }
    
    /**
     * Diálogos de confirmación
     */
    function initConfirmDialogs() {
        // Confirmar eliminación
        $('.wbd-delete-action').on('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres eliminar este elemento?')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Confirmar desactivación de proveedores
        $('.wbd-deactivate-proveedor').on('click', function(e) {
            if (!confirm('¿Desactivar este proveedor? Se pausarán todas sus bolsas de descuento.')) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Acciones AJAX
     */
    function initAjaxActions() {
        // Actualizar saldo de bolsa en tiempo real
        $('.wbd-update-saldo').on('click', function(e) {
            e.preventDefault();
            
            var bolsaId = $(this).data('bolsa-id');
            var nuevoSaldo = prompt('Ingresa el nuevo saldo:');
            
            if (nuevoSaldo !== null && !isNaN(nuevoSaldo) && nuevoSaldo >= 0) {
                updateSaldoBolsa(bolsaId, parseFloat(nuevoSaldo));
            }
        });
        
        // Exportar reporte
        $('.wbd-export-reporte').on('click', function(e) {
            e.preventDefault();
            exportarReporte();
        });
        
        // Refrescar estadísticas
        $('.wbd-refresh-stats').on('click', function(e) {
            e.preventDefault();
            refreshEstadisticas();
        });
    }
    
    /**
     * Actualizar saldo de bolsa vía AJAX
     */
    function updateSaldoBolsa(bolsaId, nuevoSaldo) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wbd_update_saldo',
                bolsa_id: bolsaId,
                nuevo_saldo: nuevoSaldo,
                nonce: wbd_admin.nonce
            },
            beforeSend: function() {
                $('.wbd-update-saldo[data-bolsa-id="' + bolsaId + '"]')
                    .addClass('wbd-loading')
                    .text('Actualizando...');
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Saldo actualizado correctamente', 'success');
                    location.reload(); // Recargar para mostrar cambios
                } else {
                    showNotice('Error al actualizar saldo: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('Error de conexión', 'error');
            },
            complete: function() {
                $('.wbd-update-saldo[data-bolsa-id="' + bolsaId + '"]')
                    .removeClass('wbd-loading')
                    .text('Actualizar');
            }
        });
    }
    
    /**
     * Exportar reporte a CSV
     */
    function exportarReporte() {
        var filtros = {
            fecha_desde: $('#fecha_desde').val(),
            fecha_hasta: $('#fecha_hasta').val(),
            proveedor_id: $('#proveedor_id').val(),
            producto_id: $('#producto_id').val()
        };
        
        var url = wbd_admin.export_url + '&' + $.param(filtros);
        window.open(url, '_blank');
    }
    
    /**
     * Refrescar estadísticas del dashboard
     */
    function refreshEstadisticas() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wbd_refresh_stats',
                nonce: wbd_admin.nonce
            },
            beforeSend: function() {
                $('.wbd-widget').addClass('wbd-loading');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showNotice('Error al refrescar estadísticas', 'error');
                }
            },
            error: function() {
                showNotice('Error de conexión', 'error');
            },
            complete: function() {
                $('.wbd-widget').removeClass('wbd-loading');
            }
        });
    }
    
    /**
     * Mostrar notificación
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        var notice = $('<div class="notice notice-' + type + ' is-dismissible wbd-notice">')
            .append('<p>' + message + '</p>')
            .append('<button type="button" class="notice-dismiss"></button>');
        
        $('.wrap h1').after(notice);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
        
        // Botón de cerrar
        notice.on('click', '.notice-dismiss', function() {
            notice.fadeOut();
        });
    }
    
    /**
     * Validar email
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Formatear fecha para input type="date"
     */
    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    /**
     * Función para cargar productos dinámicamente
     */
    function loadProductos(searchTerm) {
        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wbd_search_productos',
                search: searchTerm,
                nonce: wbd_admin.nonce
            }
        });
    }
    
    /**
     * Inicializar select2 para productos (si está disponible)
     */
    if ($.fn.select2) {
        $('#producto_id').select2({
            placeholder: 'Buscar producto...',
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'wbd_search_productos',
                        search: params.term,
                        nonce: wbd_admin.nonce
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data || []
                    };
                },
                cache: true
            },
            minimumInputLength: 2
        });
    }
    
    /**
     * Calculadora de descuentos en tiempo real
     */
    $('#precio_unitario, #porcentaje_descuento, #cantidad_ejemplo').on('input', function() {
        calcularEjemploDescuento();
    });
    
    function calcularEjemploDescuento() {
        var precio = parseFloat($('#precio_unitario').val()) || 0;
        var porcentaje = parseFloat($('#porcentaje_descuento').val()) || 0;
        var cantidad = parseInt($('#cantidad_ejemplo').val()) || 2;
        
        if (precio > 0 && porcentaje > 0 && cantidad >= 2) {
            var unidadesConDescuento = Math.floor(cantidad / 2);
            var descuentoUnitario = precio * (porcentaje / 100);
            var totalDescuento = descuentoUnitario * unidadesConDescuento;
            var totalOriginal = precio * cantidad;
            var totalFinal = totalOriginal - totalDescuento;
            
            $('#ejemplo-calculo').html(
                '<strong>Ejemplo de cálculo:</strong><br>' +
                'Precio original: $' + totalOriginal.toFixed(2) + '<br>' +
                'Descuento aplicado: -$' + totalDescuento.toFixed(2) + '<br>' +
                'Total final: $' + totalFinal.toFixed(2) + '<br>' +
                '<small>(' + unidadesConDescuento + ' unidad(es) con descuento)</small>'
            ).show();
        } else {
            $('#ejemplo-calculo').hide();
        }
    }

})(jQuery);
