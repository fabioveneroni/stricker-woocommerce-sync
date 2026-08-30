<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class StrickerSoapClient {
    public static $_WsdlUriHTTP = 'http://ws.spotgifts.com.br/StrickerService.svc?wsdl';
    public static $_WsdlUriHTTPS = 'https://ws.spotgifts.com.br/StrickerService.svc?wsdl';
    public static $protocol;
    public static $clientWCFAZURE;

    public function __construct( $protocol = 'https' ) {
        self::$protocol = ( 'http' === strtolower( $protocol ) ) ? 'http' : 'https';
    }

    public function StrickerSOAPClient( $protocol ) {
        $this->__construct( $protocol );
    }

    public static function InitializeSoap() {
        if ( self::$clientWCFAZURE !== null ) {
            return;
        }

        $wsdl = ( 'http' === self::$protocol ) ? self::$_WsdlUriHTTP : self::$_WsdlUriHTTPS;

        /*
         * The Stricker WSDL imports additional WSDL/XSD resources. PHP's
         * SoapClient follows those imports itself, but some hosting providers
         * block the secondary ?wsdl=wsdl0 request. Keep the official WSDL URL
         * and explicitly enable WSDL caching off, while allowing the PHP SOAP
         * client to resolve imported schemas normally.
         */
        self::$clientWCFAZURE = new SoapClient(
            $wsdl,
            array(
                'cache_wsdl'        => WSDL_CACHE_NONE,
                'connection_timeout'=> 120,
                'exceptions'        => true,
                'trace'             => false,
                'features'          => SOAP_SINGLE_ELEMENT_ARRAYS,
                'keep_alive'        => false,
            )
        );
    }

    public static function AuthenticateClient( $accessKey ) {
        self::InitializeSoap();
        $response = self::$clientWCFAZURE->AuthenticateClient(array('accessKey' => $accessKey));
        return $response->AuthenticateClientResult;
    }

    public static function ValidateSession( $token ) {
        self::InitializeSoap();
        $response = self::$clientWCFAZURE->ValidateSession(array('token' => $token));
        return $response->ValidateSessionResult;
    }

    public static function ProductTypes( $token, $language ) {
        self::InitializeSoap();
        $response = self::$clientWCFAZURE->ProductTypes(array('token' => $token, 'lang' => $language));
        return $response->ProductTypesResult;
    }

    public static function Products( $token, $language ) {
        self::InitializeSoap();
        $response = self::$clientWCFAZURE->Products(array('token' => $token, 'lang' => $language));
        return $response->ProductsResult;
    }

    public static function ProductsTree( $token, $language ) {
        self::InitializeSoap();
        $response = self::$clientWCFAZURE->ProductsTree(array('token' => $token, 'lang' => $language));
        return $response->ProductsTreeResult;
    }
}
