<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SWS_Crypto {
    const OPTION = 'sws_access_key_enc';

    public static function encrypt( $value ) {
        if ( ! $value ) return '';
        if ( ! function_exists( 'openssl_encrypt' ) ) {
            return '';
        }
        $key = hash( 'sha256', wp_salt( 'auth' ), true );
        $iv  = random_bytes( 16 );
        $cipher = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
        if ( false === $cipher ) return '';
        return base64_encode( $iv . $cipher );
    }

    public static function decrypt( $encoded ) {
        if ( ! $encoded || ! function_exists( 'openssl_decrypt' ) ) return '';
        $raw = base64_decode( $encoded, true );
        if ( false === $raw || strlen( $raw ) < 17 ) return '';
        $iv = substr( $raw, 0, 16 );
        $cipher = substr( $raw, 16 );
        $key = hash( 'sha256', wp_salt( 'auth' ), true );
        $value = openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
        return false === $value ? '' : $value;
    }
}
