<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SWS_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_sws_test_connection', array( __CLASS__, 'test_connection' ) );
        add_action( 'admin_post_sws_fetch_categories', array( __CLASS__, 'fetch_categories' ) );
        add_action( 'admin_post_sws_fetch_products', array( __CLASS__, 'fetch_products' ) );
    }

    public static function menu() {
        add_menu_page( 'Stricker Sync', 'Stricker Sync', 'manage_options', 'sws-dashboard', array( __CLASS__, 'dashboard' ), 'dashicons-update', 56 );
        add_submenu_page( 'sws-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'sws-dashboard', array( __CLASS__, 'dashboard' ) );
        add_submenu_page( 'sws-dashboard', 'Conexão', 'Conexão', 'manage_options', 'sws-connection', array( __CLASS__, 'connection' ) );
        add_submenu_page( 'sws-dashboard', 'Categorias', 'Categorias', 'manage_options', 'sws-categories', array( __CLASS__, 'categories' ) );
        add_submenu_page( 'sws-dashboard', 'Produtos', 'Produtos', 'manage_options', 'sws-products', array( __CLASS__, 'products' ) );
    }

    private static function header( $title ) { echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>'; }

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
        echo '<div class="card" style="max-width:900px;padding:20px;"><h2>Status</h2><p><strong>Configuração:</strong> ' . ( $configured ? '<span style="color:green;">Pronta para teste</span>' : '<span style="color:#b32d2e;">Incompleta</span>' ) . '</p><p><strong>Importação:</strong> ainda não executada.</p><p><strong>Produtos importados:</strong> 0</p><p><strong>Variações importadas:</strong> 0</p><p><strong>Última sincronização:</strong> —</p></div>';
        echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=sws-connection' ) ) . '">Configurar conexão</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=sws-categories' ) ) . '">Consultar categorias</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=sws-products' ) ) . '">Consultar produtos</a></p></div>';
    }

    public static function connection() {
        self::header( 'Stricker Sync — Conexão' ); self::notice_from_transient( 'sws_connection_notice_' . get_current_user_id() );
        echo '<form method="post" action="options.php">'; settings_fields( 'sws_settings' ); do_settings_sections( 'sws-connection' ); submit_button( 'Salvar configurações' ); echo '</form><hr><h2>Testar conexão</h2><p>O teste usa as credenciais salvas e não exibe a Access Key.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'; wp_nonce_field( 'sws_test_connection' ); echo '<input type="hidden" name="action" value="sws_test_connection">'; submit_button( 'Testar conexão com a Stricker', 'secondary' ); echo '</form></div>';
    }

    public static function categories() {
        self::header( 'Stricker Sync — Categorias' ); self::notice_from_transient( 'sws_categories_notice_' . get_current_user_id() );
        echo '<p>Consulte os ProductTypes disponíveis na Stricker. O resultado é usado como base para o futuro mapeamento das categorias do WooCommerce.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:15px 0;">'; wp_nonce_field( 'sws_fetch_categories' ); echo '<input type="hidden" name="action" value="sws_fetch_categories">'; submit_button( 'Consultar categorias na Stricker', 'primary', 'submit', false ); echo '</form>';
        $result = get_transient( 'sws_categories_result_' . get_current_user_id() );
        if ( false === $result ) { echo '<div class="notice notice-info"><p>Nenhuma consulta realizada nesta sessão. Clique no botão acima para buscar os ProductTypes.</p></div></div>'; return; }
        if ( is_wp_error( $result ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div></div>'; return; }
        $items = array(); self::extract_category_rows( $result, $items ); $items = self::unique_category_rows( $items );
        echo '<div class="card" style="max-width:1100px;padding:20px;"><h2>Resultado da API</h2><p><strong>Itens identificados:</strong> ' . esc_html( count( $items ) ) . '</p>';
        if ( ! empty( $items ) ) { echo '<table class="widefat striped"><thead><tr><th>Código do tipo</th><th>Tipo de produto</th><th>Código do subtipo</th><th>Subtipo</th></tr></thead><tbody>'; foreach ( $items as $item ) echo '<tr><td>' . esc_html( $item['type_id'] ) . '</td><td>' . esc_html( $item['type_name'] ) . '</td><td>' . esc_html( $item['subtype_id'] ) . '</td><td>' . esc_html( $item['subtype_name'] ) . '</td></tr>'; echo '</tbody></table>'; } else echo '<div class="notice notice-warning"><p>A API respondeu, mas não foi possível identificar automaticamente os ProductTypes. O retorno bruto está abaixo para ajustarmos o parser com a estrutura real.</p></div>';
        echo '<details style="margin-top:20px;"><summary><strong>Ver resposta bruta da API</strong></summary><pre style="background:#fff;padding:15px;max-height:500px;overflow:auto;">' . esc_html( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre></details></div></div>';
    }

    public static function products() {
        self::header( 'Stricker Sync — Produtos' ); self::notice_from_transient( 'sws_products_notice_' . get_current_user_id() );
        echo '<p>Esta etapa consulta o catálogo de produtos da Stricker e exibe o retorno para validarmos a estrutura real antes de implementar importação para o WooCommerce.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:15px 0;">'; wp_nonce_field( 'sws_fetch_products' ); echo '<input type="hidden" name="action" value="sws_fetch_products">'; submit_button( 'Consultar produtos na Stricker', 'primary', 'submit', false ); echo '</form>';
        $result = get_transient( 'sws_products_result_' . get_current_user_id() );
        if ( false === $result ) { echo '<div class="notice notice-info"><p>Nenhuma consulta realizada. Clique no botão acima para buscar o catálogo.</p></div></div>'; return; }
        if ( is_wp_error( $result ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div></div>'; return; }
        $items = array(); self::extract_product_rows( $result, $items );
        echo '<div class="card" style="max-width:1200px;padding:20px;"><h2>Resultado da API</h2><p><strong>Registros de produto identificados:</strong> ' . esc_html( count( $items ) ) . '</p>';
        if ( ! empty( $items ) ) { echo '<table class="widefat striped"><thead><tr><th>Referência / SKU</th><th>Nome</th><th>Descrição</th><th>Campos identificados</th></tr></thead><tbody>'; foreach ( $items as $item ) echo '<tr><td>' . esc_html( $item['sku'] ) . '</td><td>' . esc_html( $item['name'] ) . '</td><td>' . esc_html( $item['description'] ) . '</td><td>' . esc_html( $item['fields'] ) . '</td></tr>'; echo '</tbody></table>'; } else echo '<div class="notice notice-warning"><p>A API respondeu, mas o formato dos produtos ainda não foi mapeado. O retorno bruto abaixo será usado para criar o parser específico.</p></div>';
        echo '<details style="margin-top:20px;"><summary><strong>Ver resposta bruta da API</strong></summary><pre style="background:#fff;padding:15px;max-height:600px;overflow:auto;">' . esc_html( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre></details></div></div>';
    }

    private static function extract_category_rows( $data, &$items ) {
        if ( ! is_array( $data ) ) return;
        if ( isset( $data['Types']['Type'] ) ) {
            $types = $data['Types']['Type']; if ( isset( $types['TypeCode'] ) || isset( $types['TypeDescription'] ) ) $types = array( $types );
            if ( is_array( $types ) ) foreach ( $types as $type ) {
                if ( ! is_array( $type ) ) continue; $type_id = isset( $type['TypeCode'] ) ? (string)$type['TypeCode'] : ''; $type_name = isset( $type['TypeDescription'] ) ? (string)$type['TypeDescription'] : '';
                $subtypes = isset( $type['SubTypes']['SubType'] ) ? $type['SubTypes']['SubType'] : array(); if ( isset( $subtypes['SubTypeCode'] ) || isset( $subtypes['SubTypeDescription'] ) ) $subtypes = array( $subtypes ); if ( ! is_array($subtypes) ) $subtypes=array();
                if ( empty($subtypes) ) { if($type_id || $type_name) $items[]=array('type_id'=>$type_id,'type_name'=>$type_name,'subtype_id'=>'','subtype_name'=>''); continue; }
                foreach($subtypes as $subtype) if(is_array($subtype)) $items[]=array('type_id'=>$type_id,'type_name'=>$type_name,'subtype_id'=>isset($subtype['SubTypeCode'])?(string)$subtype['SubTypeCode']:'','subtype_name'=>isset($subtype['SubTypeDescription'])?(string)$subtype['SubTypeDescription']:'');
            }
            return;
        }
        foreach($data as $value) if(is_array($value)) self::extract_category_rows($value,$items);
    }

    private static function unique_category_rows( $items ) { $unique=array(); $seen=array(); foreach($items as $item){$key=strtolower($item['type_id'].'|'.$item['type_name'].'|'.$item['subtype_id'].'|'.$item['subtype_name']);if(isset($seen[$key]))continue;$seen[$key]=true;$unique[]=$item;}return $unique; }

    private static function extract_product_rows( $data, &$items ) {
        if ( ! is_array( $data ) ) return;
        foreach ( $data as $value ) {
            if ( ! is_array( $value ) ) continue;
            if ( self::looks_like_product( $value ) ) {
                $sku = self::first_scalar( $value, array('Sku','SKU','ProductCode','ProductReference','Reference','Code') );
                $name = self::first_scalar( $value, array('Name','ProductName','ProductDescription','Description') );
                $description = self::first_scalar( $value, array('ProductDescription','Description','LongDescription') );
                $items[] = array('sku'=>$sku,'name'=>$name,'description'=>$description,'fields'=>implode(', ', array_slice(array_keys($value),0,12)));
            }
            self::extract_product_rows( $value, $items );
        }
    }

    private static function looks_like_product( $value ) {
        $keys = array_map('strtolower', array_keys($value)); $signals=0;
        foreach(array('sku','productcode','productreference','reference','productname','productdescription','description') as $candidate) if(in_array($candidate,$keys,true)) $signals++;
        return $signals >= 2 || ($signals >= 1 && count($value) >= 4);
    }

    private static function first_scalar( $array, $keys ) { foreach($keys as $key) if(isset($array[$key]) && is_scalar($array[$key])) return (string)$array[$key]; return ''; }

    public static function test_connection() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' ); check_admin_referer( 'sws_test_connection' ); $api=new SWS_API(); $result=$api->authenticate();
        set_transient('sws_connection_notice_'.get_current_user_id(),array('type'=>is_wp_error($result)?'error':'success','message'=>is_wp_error($result)?$result->get_error_message():'Conexão validada com sucesso!'),60); wp_safe_redirect(add_query_arg(array('page'=>'sws-connection'),admin_url('admin.php'))); exit;
    }

    public static function fetch_categories() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' ); check_admin_referer( 'sws_fetch_categories' ); $api=new SWS_API(); $result=$api->get_categories();
        set_transient('sws_categories_result_'.get_current_user_id(),$result,10*MINUTE_IN_SECONDS); set_transient('sws_categories_notice_'.get_current_user_id(),array('type'=>is_wp_error($result)?'error':'success','message'=>is_wp_error($result)?'Falha ao consultar os ProductTypes: '.$result->get_error_message():'ProductTypes consultados com sucesso.'),60); wp_safe_redirect(add_query_arg(array('page'=>'sws-categories'),admin_url('admin.php'))); exit;
    }

    public static function fetch_products() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' ); check_admin_referer( 'sws_fetch_products' ); $api=new SWS_API(); $result=$api->get_products();
        set_transient('sws_products_result_'.get_current_user_id(),$result,10*MINUTE_IN_SECONDS); set_transient('sws_products_notice_'.get_current_user_id(),array('type'=>is_wp_error($result)?'error':'success','message'=>is_wp_error($result)?'Falha ao consultar os Products: '.$result->get_error_message():'Produtos consultados com sucesso.'),60); wp_safe_redirect(add_query_arg(array('page'=>'sws-products'),admin_url('admin.php'))); exit;
    }
}
