<?php
/*
Plugin Name: Comunicados Internos - Dashboard & Acuse de Recibo
Description: Registra qué encargados han leído los comunicados, ofrece un panel de estadísticas y restringe acceso.
Version: 2.1
Author: Denis Willy Soria Zambrana
*/

// ==========================================
// 1. INSTALACIÓN Y BASE DE DATOS
// ==========================================
function ci_crear_tabla() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'comunicados_recibidos';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        post_id bigint(20) NOT NULL,
        fecha datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'ci_crear_tabla' );

function ci_cargar_scripts() {
    if ( is_single() ) {
        wp_enqueue_script( 'ci-ajax-script', plugin_dir_url( __FILE__ ) . 'script.js', array('jquery'), '2.1', true );
        wp_localize_script( 'ci-ajax-script', 'ci_ajax_obj', array(
            'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            'current_user_id' => get_current_user_id()
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'ci_cargar_scripts' );


// ==========================================
// 2. PANEL DE ADMINISTRADOR (DASHBOARD)
// ==========================================

function ci_agregar_menu_admin() {
    add_menu_page(
        'Panel de Comunicados',
        'Comunicados',
        'manage_options',
        'ci-dashboard',
        'ci_renderizar_dashboard',
        'dashicons-megaphone',
        6
    );
}
add_action( 'admin_menu', 'ci_agregar_menu_admin' );

function ci_renderizar_dashboard() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'comunicados_recibidos';

    // Guardar usuarios seleccionados
    if ( isset($_POST['ci_guardar_usuarios']) && check_admin_referer('ci_guardar_usuarios_nonce') ) {
        $usuarios_seleccionados = isset($_POST['usuarios_rastreados']) ? array_map('intval', $_POST['usuarios_rastreados']) : array();
        update_option('ci_usuarios_rastreados', $usuarios_seleccionados);
        echo '<div class="notice notice-success is-dismissible"><p>Usuarios actualizados correctamente.</p></div>';
    }

    $usuarios_rastreados = get_option('ci_usuarios_rastreados', array());
    $total_usuarios_rastreados = count($usuarios_rastreados);

    // Cálculos
    $total_comunicados = wp_count_posts('post')->publish;
    $total_lecturas = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    $porcentaje_respuesta = 0;
    if ($total_usuarios_rastreados > 0 && $total_comunicados > 0) {
        $lecturas_esperadas = $total_usuarios_rastreados * $total_comunicados;
        $porcentaje_respuesta = round(($total_lecturas / $lecturas_esperadas) * 100, 1);
        if($porcentaje_respuesta > 100) $porcentaje_respuesta = 100;
    }

    $encargado_rapido = "N/A";
    $tiempo_rapido = "";
    if ($total_lecturas > 0) {
        $rapido_query = $wpdb->get_row("
            SELECT r.user_id, AVG(TIMESTAMPDIFF(MINUTE, p.post_date, r.fecha)) as avg_minutos
            FROM $table_name r
            JOIN {$wpdb->prefix}posts p ON r.post_id = p.ID
            WHERE p.post_status = 'publish'
            GROUP BY r.user_id
            ORDER BY avg_minutos ASC
            LIMIT 1
        ");
        if ($rapido_query) {
            $user_info = get_userdata($rapido_query->user_id);
            $encargado_rapido = $user_info ? $user_info->display_name : 'Usuario borrado';
            
            $mins = round($rapido_query->avg_minutos);
            if ($mins < 60) {
                $tiempo_rapido = $mins . ' minutos en promedio';
            } else {
                $tiempo_rapido = round($mins / 60, 1) . ' horas en promedio';
            }
        }
    }

    $ultimos_posts = wp_get_recent_posts(array('numberposts' => 1, 'post_status' => 'publish'));
    $ultimo_post_id = !empty($ultimos_posts) ? $ultimos_posts[0]['ID'] : 0;
    $ultimo_post_titulo = !empty($ultimos_posts) ? $ultimos_posts[0]['post_title'] : 'No hay comunicados';
    
    $pendientes = array();
    if ($ultimo_post_id && $total_usuarios_rastreados > 0) {
        $lecturas_ultimo = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $table_name WHERE post_id = %d", $ultimo_post_id));
        foreach ($usuarios_rastreados as $uid) {
            if (!in_array($uid, $lecturas_ultimo)) {
                $user_info = get_userdata($uid);
                if($user_info) $pendientes[] = $user_info->display_name;
            }
        }
    }

    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 20px;">📊 Panel de Control de Comunicados</h1>

        <div style="display: flex; gap: 20px; margin-bottom: 30px;">
            <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); flex:1; text-align:center;">
                <h3 style="margin-top:0; color:#666;">Total Comunicados</h3>
                <span style="font-size:32px; font-weight:bold; color:#0073aa;"><?php echo $total_comunicados; ?></span>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); flex:1; text-align:center;">
                <h3 style="margin-top:0; color:#666;">% Respuesta Global</h3>
                <span style="font-size:32px; font-weight:bold; color:<?php echo $porcentaje_respuesta >= 80 ? '#2ca02c' : '#d63638'; ?>;"><?php echo $porcentaje_respuesta; ?>%</span>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); flex:1; text-align:center;">
                <h3 style="margin-top:0; color:#666;">⚡ Más Rápido en Leer</h3>
                <span style="font-size:24px; font-weight:bold; color:#d63638;"><?php echo esc_html($encargado_rapido); ?></span>
                <p style="margin:5px 0 0 0; color:#888; font-size:12px;"><?php echo esc_html($tiempo_rapido); ?></p>
            </div>
        </div>

        <div style="display: flex; gap: 30px;">
            <div style="flex: 1; background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">⚙️ Encargados a Evaluar</h2>
                <p style="font-size:13px; color:#666;">Selecciona qué usuarios deben confirmar lectura. Los demás no verán el botón ni saldrán en las listas.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('ci_guardar_usuarios_nonce'); ?>
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <?php
                        $todos_los_usuarios = get_users(array('fields' => array('ID', 'display_name')));
                        foreach ($todos_los_usuarios as $u) {
                            $checked = in_array($u->ID, $usuarios_rastreados) ? 'checked' : '';
                            echo '<label style="display:block; margin-bottom:5px;"><input type="checkbox" name="usuarios_rastreados[]" value="' . $u->ID . '" ' . $checked . '> ' . esc_html($u->display_name) . '</label>';
                        }
                        ?>
                    </div>
                    <button type="submit" name="ci_guardar_usuarios" class="button button-primary">Guardar Selección</button>
                </form>
            </div>

            <div style="flex: 1; background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; color:#d63638;">📞 Pendientes de Lectura</h2>
                <p style="font-size:13px; color:#666;">Estos encargados <strong>no han leído</strong> el último comunicado: <em>"<?php echo esc_html($ultimo_post_titulo); ?>"</em>.</p>
                
                <?php if (empty($usuarios_rastreados)) : ?>
                    <p style="color:#d63638;">⚠️ Primero debes seleccionar encargados a evaluar a la izquierda.</p>
                <?php elseif (empty($pendientes)) : ?>
                    <div style="padding:15px; background:#e5f5e0; color:#2ca02c; border-radius:5px; text-align:center; font-weight:bold;">
                        ¡Excelente! Todos han leído el último comunicado.
                    </div>
                <?php else: ?>
                    <ul style="list-style-type: none; padding:0; margin:0;">
                        <?php foreach($pendientes as $p): ?>
                            <li style="padding:10px; border-bottom:1px solid #eee; font-size:14px;">
                                ❌ <strong><?php echo esc_html($p); ?></strong> <span style="float:right; font-size:12px; color:#999;">Llamar</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}


// ==========================================
// 3. FRONTEND: MOSTRAR BOTÓN Y TABLA
// ==========================================
function ci_agregar_boton_y_tabla( $content ) {
    if ( ! is_single() || ! is_user_logged_in() || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    // --- SEGURO ANTI-DUPLICADOS PARA AVADA ---
    static $tabla_ya_impresa = array();
    $post_id = get_the_ID();
    
    if ( in_array( $post_id, $tabla_ya_impresa ) ) {
        return $content; // Evita imprimir la tabla dos veces en la misma página
    }
    $tabla_ya_impresa[] = $post_id;
    // -----------------------------------------

    $usuarios_rastreados = get_option('ci_usuarios_rastreados', array());
    $current_user_id = get_current_user_id();

    // Si no hay usuarios rastreados configurados, no mostramos nada
    if (empty($usuarios_rastreados)) return $content;

    global $wpdb;
    $table_name = $wpdb->prefix . 'comunicados_recibidos';

    $bloque_accion = '';

    // Solo mostramos el botón al usuario si está en la lista de los rastreados
    if ( in_array($current_user_id, $usuarios_rastreados) ) {
        $current_user = wp_get_current_user();
        
        $fecha_leido = $wpdb->get_var( $wpdb->prepare(
            "SELECT fecha FROM $table_name WHERE user_id = %d AND post_id = %d",
            $current_user_id, $post_id
        ) );

        if ( $fecha_leido ) {
            $fecha_formateada = date_i18n( 'd \d\e F \d\e Y \a \l\a\s H:i', strtotime( $fecha_leido ) );
            $bloque_accion = '
            <div style="padding:15px; background:#e5f5e0; color:#2ca02c; border: 1px solid #c3e6cb; border-radius:5px; margin-top:20px;">
                <strong style="display:block; margin-bottom:5px;">✅ Acuse de recibo registrado</strong>
                Confirmado por: <strong>' . esc_html( $current_user->display_name ) . '</strong><br>
                Fecha y hora: <strong>' . esc_html( $fecha_formateada ) . '</strong>
            </div>';
        } else {
            $nonce = wp_create_nonce( 'ci_nonce_seguridad' );
            $bloque_accion = '
            <div id="ci-contenedor-boton" style="margin-top:30px; text-align:center; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px;">
                <p style="margin-bottom: 10px; font-weight: bold;">Por favor, confirma que has leído esta información:</p>
                <button id="ci-btn-confirmar" data-post="' . $post_id . '" data-nonce="' . $nonce . '" style="padding: 12px 24px; background: #0073aa; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;">He leído y comprendido</button>
            </div>';
        }
    }

    // --- TABLA DE SEGUIMIENTO ---
    $args_usuarios = array('include' => $usuarios_rastreados);
    $usuarios = get_users( $args_usuarios );

    $lecturas_post = $wpdb->get_results( $wpdb->prepare(
        "SELECT user_id, fecha FROM $table_name WHERE post_id = %d",
        $post_id
    ), OBJECT_K );

    $tabla_html = '
    <div class="ci-control-lectura" style="margin-top:50px; margin-bottom:60px; border-top:2px solid #eee; padding-top:20px;">
        <h3 style="margin-bottom:15px; color:#333;">📋 Registro de Control de Lectura</h3>
        <table style="width:100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background:#f2f2f2; border-bottom:2px solid #ddd;">
                    <th style="padding:10px; border:1px solid #ddd;">Encargado / Usuario</th>
                    <th style="padding:10px; border:1px solid #ddd;">Estado</th>
                    <th style="padding:10px; border:1px solid #ddd;">Fecha y Hora de Confirmación</th>
                </tr>
            </thead>
            <tbody>';

    foreach ( $usuarios as $u ) {
        if ( isset( $lecturas_post[$u->ID] ) ) {
            $fecha_f = date_i18n( 'd/m/Y H:i', strtotime( $lecturas_post[$u->ID]->fecha ) );
            $estado = '<span style="color:#2ca02c; font-weight:bold;">✅ Leído</span>';
            $fecha_col = $fecha_f;
        } else {
            $estado = '<span style="color:#de2d26; font-weight:bold;">❌ No leído</span>';
            $fecha_col = '-';
        }

        $tabla_html .= '
                <tr class="fila-usuario-lectura" data-user-id="' . $u->ID . '" style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; border:1px solid #ddd;">' . esc_html( $u->display_name ) . '</td>
                    <td class="ci-estado-celda" style="padding:10px; border:1px solid #ddd;">' . $estado . '</td>
                    <td class="ci-fecha-celda" style="padding:10px; border:1px solid #ddd;">' . $fecha_col . '</td>
                </tr>';
    }

    $tabla_html .= '
            </tbody>
        </table>
    </div>';

    return $content . $bloque_accion . $tabla_html;
}
add_filter( 'the_content', 'ci_agregar_boton_y_tabla' );


// ==========================================
// 4. BACKEND AJAX: PROCESAR CLIC
// ==========================================
function ci_procesar_lectura() {
    check_ajax_referer( 'ci_nonce_seguridad', 'seguridad' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Debes iniciar sesión.' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'comunicados_recibidos';
    $current_user = wp_get_current_user();
    
    $insert = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $current_user->ID,
            'post_id' => intval( $_POST['post_id'] ),
            'fecha'   => current_time( 'mysql' )
        )
    );

    if ( $insert ) {
        wp_send_json_success( array(
            'nombre'      => $current_user->display_name,
            'fecha_larga' => date_i18n( 'd \d\e F \d\e Y \a \l\a\s H:i', current_time('timestamp') ),
            'fecha_corta' => date_i18n( 'd/m/Y H:i', current_time('timestamp') )
        ) );
    } else {
        wp_send_json_error( 'Error al guardar.' );
    }
}
add_action( 'wp_ajax_ci_guardar_lectura', 'ci_procesar_lectura' );


// ==========================================
// 5. CREAR NUEVO ROL DE USUARIO "TIENDAS"
// ==========================================
function ci_crear_rol_tiendas() {
    add_role(
        'tiendas',
        'Encargado de Tienda',
        array(
            'read' => true,
        )
    );
}
add_action( 'init', 'ci_crear_rol_tiendas' );


// ==========================================
// 6. RESTRICCIÓN DE ACCESO SOLO PARA TIENDAS
// ==========================================
function ci_restringir_acceso_tiendas() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        
        if ( in_array( 'tiendas', (array) $current_user->roles ) ) {
            
            // ¿A dónde SÍ pueden entrar?
            $puede_ver = is_single() || is_page( 'comunicados' );

            if ( ! $puede_ver ) {
                wp_redirect( home_url( '/comunicados/' ) );
                exit;
            }
        }
    }
}
add_action( 'template_redirect', 'ci_restringir_acceso_tiendas' );
?>
