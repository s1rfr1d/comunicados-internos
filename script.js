jQuery(document).ready(function($) {
    $('#ci-btn-confirmar').on('click', function(e) {
        e.preventDefault();
        
        var boton = $(this);
        var post_id = boton.data('post');
        var nonce = boton.data('nonce');

        boton.text('Registrando lectura...').prop('disabled', true).css('background', '#666');

        $.ajax({
            url: ci_ajax_obj.ajaxurl,
            type: 'POST',
            data: {
                action: 'ci_guardar_lectura',
                post_id: post_id,
                seguridad: nonce
            },
            success: function(response) {
                if(response.success) {
                    var nombre = response.data.nombre;
                    var fecha_larga = response.data.fecha_larga;
                    var fecha_corta = response.data.fecha_corta;
                    
                    // 1. Actualizar el bloque superior (reemplazar el botón por el aviso verde)
                    var htmlExito = '<div style="padding:15px; background:#e5f5e0; color:#2ca02c; border: 1px solid #c3e6cb; border-radius:5px;">';
                    htmlExito += '<strong style="display:block; margin-bottom:5px;">✅ Acuse de recibo registrado</strong>';
                    htmlExito += 'Confirmado por: <strong>' + nombre + '</strong><br>';
                    htmlExito += 'Fecha y hora: <strong>' + fecha_larga + '</strong>';
                    htmlExito += '</div>';
                    $('#ci-contenedor-boton').replaceWith(htmlExito);

                    // 2. ACTUALIZACIÓN EN VIVO DE LA TABLA
                    // Buscamos la fila que tiene el ID del usuario actual
                    var miFila = $('tr.fila-usuario-lectura[data-user-id="' + ci_ajax_obj.current_user_id + '"]');
                    if(miFila.length > 0) {
                        // Cambiamos el estado a Leído
                        miFila.find('.ci-estado-celda').html('<span style="color:#2ca02c; font-weight:bold;">✅ Leído</span>');
                        // Escribimos la fecha compacta en su columna correspondiente
                        miFila.find('.ci-fecha-celda').text(fecha_corta);
                    }
                } else {
                    alert('Hubo un error al procesar tu lectura. Reintenta.');
                    boton.text('He leído y comprendido').prop('disabled', false).css('background', '#0073aa');
                }
            },
            error: function() {
                alert('Error de conexión con el servidor.');
                boton.text('He leído y comprendido').prop('disabled', false).css('background', '#0073aa');
            }
        });
    });
});
