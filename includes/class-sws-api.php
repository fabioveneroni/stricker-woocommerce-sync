<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SWS_API {
    private $client_id;
    private $access_key;
    private $token;
    private $base_url;

    public function __construct() {
        $this->client_id  = get_option( 'sws_client_id', '' );
        $encrypted        = get_option( SWS_Crypto::OPTION, '' );
        $this->access_key = SWS_Crypto::decrypt( $encrypted );
        $this->token      = get_transient( 'sws_session_token' );
        $this->base_url   = untrailingslashit( get_option( 'sws_api_base_url', 'https://ws.spotgifts.com.br/api/v1SSL' ) );
    }

    public function is_configured() {
        return ! empty( $this->client_id ) && ! empty( $this->access_key ) && ! empty( $this->base_url );
    }

    public function authenticate() {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'sws_not_configured', 'Configure o Client ID e a Access Key antes de testar a conexão.' );
        }

        // O manual da Stricker documenta a autenticação como GET + AccessKey.
        $url = add_query_arg( array( 'AccessKey' => $this->access_key ), $this->base_url . '/authenticateclient' );
        $response = wp_remote_get( $url, array(
            'timeout' => 30,
            'headers' => array( 'Accept' => 'application/json' ),
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $code = wp_remote_retrieve_response_code( $response );
        $raw  = wp_remote_retrieve_body( $response );
        $data = json_decode( $raw, true );

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error( 'sws_api_error', 'A Stricker retornou HTTP ' . $code . '.', array( 'response' => $raw ) );
        }

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'sws_invalid_response', 'A resposta de autenticação não está em JSON válido.' );
        }

        if ( isset( $data['ErrorCode'] ) && $data['ErrorCode'] ) {
            return new WP_Error( 'sws_auth_error', ! empty( $data['ErrorMessage'] ) ? sanitize_text_field( $data['ErrorMessage'] ) : 'A Stricker recusou a autenticação.' );
        }

        $token = $this->find_value_recursive( $data, array( 'session_token', 'sessionToken', 'SessionToken', 'token', 'Token' ) );
        if ( $token ) {
            $this->token = sanitize_text_field( $token );
            set_transient( 'sws_session_token', $this->token, 23 * HOUR_IN_SECONDS );
            return true;
        }

        // Algumas respostas de autenticação são booleanas/numéricas; neste caso
        // ainda registramos o payload em memória para diagnóstico no próximo passo.
        return new WP_Error( 'sws_no_token', 'A Stricker respondeu sem session token reconhecível. A resposta retornada foi registrada para diagnóstico.' );
    }

    private function find_value_recursive( $data, $keys ) {
        if ( ! is_array( $data ) ) return '';
        foreach ( $keys as $key ) {
            if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) && '' !== (string) $data[ $key ] ) return (string) $data[ $key ];
        }
        foreach ( $data as $value ) {
            if ( is_array( $value ) ) {
                $found = $this->find_value_recursive( $value, $keys );
                if ( $found ) return $found;
            }
        }
        return '';
    }

    private function request( $endpoint, $args = array() ) {
        if ( ! $this->token ) {
            $auth = $this->authenticate();
            if ( is_wp_error( $auth ) ) return $auth;
        }

        $query = array_merge( array( 'token' => $this->token ), $args );
        $url = add_query_arg( $query, $this->base_url . '/' . ltrim( $endpoint, '/' ) );

        $response = wp_remote_get( $url, array(
            'timeout' => 60,
            'headers' => array( 'Accept' => 'application/json' ),
        ) );
        if ( is_wp_error( $response ) ) return $response;

        $code = wp_remote_retrieve_response_code( $response );
        $raw  = wp_remote_retrieve_body( $response );
        $data = json_decode( $raw, true );

        if ( $code === 401 || ( is_array( $data ) && isset( $data['ErrorCode'] ) && '13' === (string) $data['ErrorCode'] ) ) {
            delete_transient( 'sws_session_token' );
            $this->token = '';
            $auth = $this->authenticate();
            if ( is_wp_error( $auth ) ) return $auth;
            return $this->request( $endpoint, $args );
        }

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error( 'sws_api_error', 'Erro HTTP ' . $code . ' na API.', array( 'response' => $raw ) );
        }

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'sws_invalid_response', 'A API retornou conteúdo que não pôde ser interpretado como JSON.' );
        }

        if ( isset( $data['ErrorCode'] ) && $data['ErrorCode'] ) {
            return new WP_Error( 'sws_api_error', ! empty( $data['ErrorMessage'] ) ? sanitize_text_field( $data['ErrorMessage'] ) : 'A API retornou um erro.' );
        }

        return $data;
    }

    public function get_categories() {
        return $this->request( 'productTypes', array( 'lang' => get_option( 'sws_language', 'PT' ) ) );
    }

    public function get_products() {
        return $this->request( 'products', array( 'lang' => get_option( 'sws_language', 'PT' ) ) );
    }
}
