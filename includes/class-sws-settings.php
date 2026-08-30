<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SWS_Settings {
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        register_setting( 'sws_settings', 'sws_client_id', array(
            'type' => 'string',
            'sanitize_callback' => function( $value ) { return preg_replace( '/[^0-9A-Za-z_-]/', '', (string) $value ); },
        ) );

        register_setting( 'sws_settings', 'sws_api_base_url', array(
            'type' => 'string',
            'sanitize_callback' => function( $value ) { return esc_url_raw( trim( (string) $value ) ); },
        ) );

        register_setting( 'sws_settings', 'sws_language', array(
            'type' => 'string',
            'sanitize_callback' => function( $value ) { return in_array( strtoupper( (string) $value ), array( 'PT' ), true ) ? strtoupper( (string) $value ) : 'PT'; },
        ) );

        // The submitted value is encrypted during sanitisation. This avoids relying
        // on update_option_sws_access_key_new, which does not fire when the option
        // is being added for the first time.
        register_setting( 'sws_settings', 'sws_access_key_new', array(
            'type' => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_access_key' ),
        ) );

        add_settings_section(
            'sws_connection',
            'Conexão com a Stricker',
            function() {
                echo '<p>As credenciais são armazenadas no servidor. A Access Key salva nunca é devolvida ao navegador.</p>';
            },
            'sws-connection'
        );

        add_settings_field( 'sws_client_id', 'Client ID', array( __CLASS__, 'field_client_id' ), 'sws-connection', 'sws_connection' );
        add_settings_field( 'sws_access_key', 'Access Key', array( __CLASS__, 'field_access_key' ), 'sws-connection', 'sws_connection' );
        add_settings_field( 'sws_api_base_url', 'URL base da API', array( __CLASS__, 'field_base_url' ), 'sws-connection', 'sws_connection' );
        add_settings_field( 'sws_language', 'Idioma do catálogo', array( __CLASS__, 'field_language' ), 'sws-connection', 'sws_connection' );
    }

    public static function field_client_id() {
        printf( '<input type="text" name="sws_client_id" value="%s" class="regular-text" autocomplete="off">', esc_attr( get_option( 'sws_client_id', '' ) ) );
    }

    public static function field_access_key() {
        $exists = (bool) get_option( SWS_Crypto::OPTION, '' );
        echo '<input type="password" name="sws_access_key_new" value="" class="regular-text" autocomplete="new-password" placeholder="' . esc_attr( $exists ? 'Chave salva — digite apenas para substituir' : 'Digite a Access Key' ) . '">';
        if ( $exists ) echo '<p class="description">Uma chave já está salva. Por segurança, o valor real não é enviado ao navegador.</p>';
    }

    public static function field_base_url() {
        printf( '<input type="url" name="sws_api_base_url" value="%s" class="regular-text" placeholder="https://...">', esc_attr( get_option( 'sws_api_base_url', '' ) ) );
        echo '<p class="description">Por padrão usamos o endpoint REST/HTTPS do manual da Stricker.</p>';
    }

    public static function field_language() {
        printf( '<input type="text" name="sws_language" value="%s" class="small-text" readonly>', esc_attr( get_option( 'sws_language', 'PT' ) ) );
        echo '<p class="description">Nesta versão, o catálogo será consultado em PT, conforme a documentação fornecida.</p>';
    }

    public static function sanitize_access_key( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }

        $encrypted = SWS_Crypto::encrypt( $value );
        if ( ! $encrypted ) {
            add_settings_error( 'sws_settings', 'sws_access_key_encrypt', 'Não foi possível proteger a Access Key no servidor.', 'error' );
            return '';
        }

        update_option( SWS_Crypto::OPTION, $encrypted, false );
        return '';
    }
}
