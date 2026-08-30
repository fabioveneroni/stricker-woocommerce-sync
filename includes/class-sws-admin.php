<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SWS_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_sws_test_connection', array( __CLASS__, 'test_connection' ) );
        add_action( 'admin_post_sws_fetch_categories', array( __CLASS__, 'fetch_categories' ) );
    }

    public static function menu() {
        add_menu_page(
            'Stricker Sync', 'Stricker Sync', 'manage_options', 'sws-dashboard',
            array( __CLASS__, 'dashboard' ), 'dashicons-update', 56
        );
        add_submenu_page( 'sws-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'sws-dashboard', array( __CLASS__, 'dashboard' ) );
        add_submenu_page( 'sws-dashboard', 'Conexão', 'Conexão', 'manage_options', 'sws-connection', array( __CLASS__, 'connection' ) );
        add_submenu_page( 'sws-dashboard', 'Categorias', 'Categorias', 'manage_options', 'sws-categories', array( __CLASS__, 'categories' ) );
    }

    private static function header( $title ) {
        echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
    }

    private static function notice_from_transient( $key ) {
        $notice = get_transient( $key );
        if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
            delete_transient( $key );
            $type = in_array( $notice['type'], array( 'success', 'error', 'warning', 'info' ), true ) ? $notice['type'] : 'info';
            echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
        }
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
        self::notice_from_transient( 'sws_connection_notice_' . get_current_user_id() );
        echo '<form method="post" action="options.php">';
        settings_fields( 'sws_settings' );
        do_settings_sections( 'sws-connection' );
        submit_button( 'Salvar configurações' );
        echo '</form><hr><h2>Testar conexão</h2>';
        echo '<p>O teste usa as credenciais salvas e não exibe a Access Key.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'sws_test_connection' );
        echo '<input type="hidden" name="action" value="sws_test_connection">';
        submit_button( 'Testar conexão com a Stricker', 'secondary' );
        echo '</form></div>';
    }

    public static function categories() {
        self::header( 'Stricker Sync — Categorias' );
        self::notice_from_transient( 'sws_categories_notice_' . get_current_user_id() );

        echo '<p>Consulte os ProductTypes disponíveis na Stricker. O resultado é usado como base para o futuro mapeamento das categorias do WooCommerce.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:15px 0;">';
        wp_nonce_field( 'sws_fetch_categories' );
        echo '<input type="hidden" name="action" value="sws_fetch_categories">';
        submit_button( 'Consultar categorias na Stricker', 'primary', 'submit', false );
        echo '</form>';

        $result = get_transient( 'sws_categories_result_' . get_current_user_id() );
        if ( false === $result ) {
            echo '<div class="notice notice-info"><p>Nenhuma consulta realizada nesta sessão. Clique no botão acima para buscar os ProductTypes.</p></div>';
            echo '</div>';
            return;
        }

        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div></div>';
            return;
        }

        $items = array();
        self::extract_category_rows( $result, $items );
        $items = self::unique_category_rows( $items );

        echo '<div class="card" style="max-width:1100px;padding:20px;">';
        echo '<h2>Resultado da API</h2>';
        echo '<p><strong>Itens identificados:</strong> ' . esc_html( count( $items ) ) . '</p>';
        if ( ! empty( $items ) ) {
            echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Nome / descrição</th><th>Subtipo</th></tr></thead><tbody>';
            foreach ( $items as $item ) {
                echo '<tr><td>' . esc_html( $item['id'] ) . '</td><td>' . esc_html( $item['name'] ) . '</td><td>' . esc_html( $item['subtype'] ) . '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="notice notice-warning"><p>A API respondeu, mas não foi possível identificar automaticamente os ProductTypes. O retorno bruto está abaixo para ajustarmos o parser com a estrutura real.</p></div>';
        }
        echo '<details style="margin-top:20px;"><summary><strong>Ver resposta bruta da API</strong></summary>';
        echo '<pre style="background:#fff;padding:15px;max-height:500px;overflow:auto;">' . esc_html( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre></details>';
        echo '</div></div>';
    }

    private static function extract_category_rows( $data, &$items ) {
        if ( ! is_array( $data ) ) return;
        foreach ( $data as $value ) {
            if ( ! is_array( $value ) ) continue;
            $id = '';
            $name = '';
            $subtype = '';
            foreach ( array( 'id', 'ID', 'ProductTypeID', 'ProductTypeId', 'TypeID', 'TypeId', 'code', 'Code' ) as $key ) {
                if ( isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) && '' !== (string) $value[ $key ] ) { $id = (string) $value[ $key ]; break; }
            }
            foreach ( array( 'description', 'Description', 'name', 'Name', 'ProductType', 'Type' ) as $key ) {
                if ( isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) && '' !== (string) $value[ $key ] ) { $name = (string) $value[ $key ]; break; }
            }
            foreach ( array( 'subtype', 'SubType', 'ProductSubType', 'Subtype' ) as $key ) {
                if ( isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) && '' !== (string) $value[ $key ] ) { $subtype = (string) $value[ $key ]; break; }
            }
            if ( $name || $id ) $items[] = array( 'id' => $id, 'name' => $name, 'subtype' => $subtype );
            self::extract_category_rows( $value, $items );
        }
    }

    private static function unique_category_rows( $items ) {
        $unique = array();
        $seen = array();
        foreach ( $items as $item ) {
            $key = strtolower( $item['id'] . '|' . $item['name'] . '|' . $item['subtype'] );
            if ( isset( $seen[ $key ] ) ) continue;
            $seen[ $key ] = true;
            $unique[] = $item;
        }
        return $unique;
    }

    public static function test_connection() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
        check_admin_referer( 'sws_test_connection' );
        $api = new SWS_API();
        $result = $api->authenticate();
        set_transient( 'sws_connection_notice_' . get_current_user_id(), array(
            'type' => is_wp_error( $result ) ? 'error' : 'success',
            'message' => is_wp_error( $result ) ? $result->get_error_message() : 'Conexão validada com sucesso!',
        ), 60 );
        wp_safe_redirect( add_query_arg( array( 'page' => 'sws-connection' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function fetch_categories() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
        check_admin_referer( 'sws_fetch_categories' );
        $api = new SWS_API();
        $result = $api->get_categories();
        set_transient( 'sws_categories_result_' . get_current_user_id(), $result, 10 * MINUTE_IN_SECONDS );
        set_transient( 'sws_categories_notice_' . get_current_user_id(), array(
            'type' => is_wp_error( $result ) ? 'error' : 'success',
            'message' => is_wp_error( $result ) ? 'Falha ao consultar os ProductTypes: ' . $result->get_error_message() : 'ProductTypes consultados com sucesso.',
        ), 60 );
        wp_safe_redirect( add_query_arg( array( 'page' => 'sws-categories' ), admin_url( 'admin.php' ) ) );
        exit;
    }
}
