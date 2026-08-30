<?php
/**
 * Plugin Name: Stricker WooCommerce Sync
 * Description: Integra o catálogo da Stricker ao WooCommerce com autenticação segura, consulta de categorias, consulta de produtos e estrutura preparada para sincronização em lotes/WP-Cron.
 * Version: 0.4.0
 * Author: OpenAI
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: stricker-woocommerce-sync
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SWS_VERSION', '0.4.0' );
define( 'SWS_FILE', __FILE__ );
define( 'SWS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SWS_URL', plugin_dir_url( __FILE__ ) );
define( 'SWS_PLUGIN_DIR', SWS_DIR );

require_once SWS_DIR . 'includes/class-sws-crypto.php';
require_once SWS_DIR . 'includes/class-sws-api.php';
require_once SWS_DIR . 'includes/class-sws-settings.php';
require_once SWS_DIR . 'includes/class-sws-admin.php';

add_action( 'plugins_loaded', function() {
    SWS_Settings::init();
    SWS_Admin::init();

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            if ( current_user_can( 'activate_plugins' ) ) {
                echo '<div class="notice notice-warning"><p><strong>Stricker WooCommerce Sync:</strong> o plugin está ativo, mas o WooCommerce ainda não está instalado ou ativo. A configuração da API pode ser feita agora; a importação de produtos dependerá do WooCommerce.</p></div>';
            }
        } );
    }
} );
