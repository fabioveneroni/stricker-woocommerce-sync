<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SWS_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_sws_test_connection', array( __CLASS__, 'test_connection' ) );
    }

    public static function menu() {
        add_menu_page(
            'Stricker Sync',
            'Stricker Sync',
            'manage_options',
            'sws-dashboard',
            array( __CLASS__, 'dashboard' ),
            'dashicons-update',
            56
        );

        add_submenu_page(
            'sws-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'sws-dashboard',
            array( __CLASS__, 'dashboard' )
        );

        add_submenu_page(
            'sws-dashboard',
            'Conexão',
            'Conexão',
            'manage_options',
            'sws-connection',
            array( __CLASS__, 'connection' )
        );
    }

    private static function header( $title ) {
        echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
    }

    public static function dashboard() {
        self::header( 'Stricker Sync' );
        $configured = get_option( 'sws_client_id', '' ) && get_option( SWS_Crypto::OPTION, '' ) && get_option( 'sws_api_base_url', '' );
        echo '<div class="card" style="max-width:900px;padding:20px;">';
        echo '<h2>Status</h2>';
        echo '<p><strong>Configuração:</strong> ' . ( $configured ? '<span style="color:green;">Pronta para teste</span>' : '<span style="color:#b32d2e;">Incompleta</span>' ) . '</p>';
        echo '<p><strong>Importação:</strong> ainda não executada.</p>';
        echo '<p><strong>Produtos importados:</strong> 0</p>';
        echo '<p><strong>Variações importadas:</strong> 0</p>';
        echo '<p><strong>Última sincronização:</strong> —</p>';
        echo '</div>';
        echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=sws-connection' ) ) . '">Configurar conexão</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=sws-categories' ) ) . '">Consultar categorias</a></p>';
        echo '</div>';
    }

    public static function connection() {
        self::header( 'Stricker Sync — Conexão' );

        if ( isset( $_GET['sws_test'] ) ) {
            $ok = 'success' === sanitize_key( $_GET['sws_test'] );
            echo '<div class="notice notice-' . ( $ok ? 'success' : 'error' ) . '"><p>' .
                esc_html( $ok ? 'Conexão/autenticação concluída.' : ( isset( $_GET['sws_msg'] ) ? rawurldecode( wp_unslash( $_GET['sws_msg'] ) ) : 'Falha na conexão.' ) ) .
                '</p></div>';
        }

        echo '<form method="post" action="options.php">';
        settings_fields( 'sws_settings' );
        do_settings_sections( 'sws-connection' );
        submit_button( 'Salvar configurações' );
        echo '</form>';

        echo '<hr>';
        echo '<h2>Testar conexão</h2>';
        echo '<p>O teste usa as credenciais salvas e não exibe a Access Key.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'sws_test_connection' );
        echo '<input type="hidden" name="action" value="sws_test_connection">';
        submit_button( 'Testar conexão com a Stricker', 'secondary' );
        echo '</form>';

        echo '</div>';
    }

    public static function categories() {
        self::header( 'Stricker Sync — Categorias' );
        echo '<p>Consulte as categorias/tipos disponíveis na Stricker antes da importação.</p>';
        $api = new SWS_API();
        $result = $api->get_categories();
        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            $items = array();
            self::extract_category_rows( $result, $items );
            if ( empty( $items ) ) {
                echo '<div class="notice notice-warning"><p>A API respondeu, mas o formato exato dos tipos ainda não foi identificado. O retorno bruto está abaixo para ajustarmos o parser.</p></div>';
                echo '<pre style="background:#fff;padding:15px;max-height:500px;overflow:auto;">' . esc_html( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre>';
            } else {
                echo '<table class="widefat striped"><thead><tr><th>Nome</th><th>Subtipo</th></tr></thead><tbody>';
                foreach ( $items as $item ) {
                    echo '<tr><td>' . esc_html( $item['name'] ) . '</td><td>' . esc_html( $item['subtype'] ) . '</td></tr>';
                }
                echo '</tbody></table>';
            }
        }
        echo '</div>';
    }

    private static function extract_category_rows( $data, &$items ) {
        if ( ! is_array( $data ) ) return;
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $name = ''; $subtype = '';
                foreach ( array( 'description', 'Description', 'name', 'Name', 'ProductType', 'Type' ) as $k ) {
                    if ( isset( $value[ $k ] ) && is_scalar( $value[ $k ] ) && $value[ $k ] !== '' ) { $name = (string) $value[ $k ]; break; }
                }
                foreach ( array( 'subtype', 'SubType', 'ProductSubType', 'Subtype' ) as $k ) {
                    if ( isset( $value[ $k ] ) && is_scalar( $value[ $k ] ) && $value[ $k ] !== '' ) { $subtype = (string) $value[ $k ]; break; }
                }
                if ( $name ) $items[] = array( 'name' => $name, 'subtype' => $subtype );
                self::extract_category_rows( $value, $items );
            }
        }
    }

    public static function test_connection() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
        check_admin_referer( 'sws_test_connection' );

        $api = new SWS_API();
        $result = $api->authenticate();

        if ( is_wp_error( $result ) ) {
            $url = add_query_arg( array(
                'page' => 'sws-connection',
                'sws_test' => 'error',
                'sws_msg' => rawurlencode( $result->get_error_message() ),
            ), admin_url( 'admin.php' ) );
        } else {
            $url = add_query_arg( array(
                'page' => 'sws-connection',
                'sws_test' => 'success',
            ), admin_url( 'admin.php' ) );
        }

        wp_safe_redirect( $url );
        exit;
    }
}
