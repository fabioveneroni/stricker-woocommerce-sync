<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Safe handler for the ProductsTree test action.
 *
 * The official Stricker SOAP client exposes ProductsTree(token, lang) only;
 * it does not accept a product reference. Therefore ProductsTree cannot be
 * safely converted into a one-product request without an additional Stricker
 * endpoint that supports product-level filtering.
 */
class SWS_Safe_Tree {
    public static function init() {
        remove_action( 'admin_post_sws_fetch_products_tree', array( 'SWS_Admin', 'fetch_products_tree' ) );
        add_action( 'admin_post_sws_fetch_products_tree', array( __CLASS__, 'handle' ) );
    }

    public static function handle() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissão.' );
        }

        check_admin_referer( 'sws_fetch_products_tree' );

        $message = 'ProductsTree não foi consultado. O cliente oficial da Stricker disponibiliza ProductsTree(token, lang) sem parâmetro de produto, portanto esta operação retorna o catálogo completo. A consulta foi bloqueada para evitar nova sobrecarga/timeout do servidor. Precisamos de uma operação de detalhe por produto antes de reativar essa chamada.';

        set_transient(
            'sws_products_tree_notice_' . get_current_user_id(),
            array( 'type' => 'warning', 'message' => $message ),
            60
        );

        wp_safe_redirect( add_query_arg( array( 'page' => 'sws-products' ), admin_url( 'admin.php' ) ) );
        exit;
    }
}
