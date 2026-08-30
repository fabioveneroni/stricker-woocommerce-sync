<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class StrickerSoapClient {
    public static $_WsdlUriHTTP = 'http://ws.spotgifts.com.br/StrickerService.svc?wsdl';
    public static $_WsdlUriHTTPS = 'https://ws.spotgifts.com.br/StrickerService.svc?wsdl';
    public static $protocol;
    public static $clientWCFAZURE;

    public function __construct( $protocol = 'https' ) { self::$protocol = ( 'http' === strtolower( $protocol ) ) ? 'http' : 'https'; }
    public function StrickerSOAPClient( $protocol ) { $this->__construct( $protocol ); }

    public static function InitializeSoap() {
        if ( self::$clientWCFAZURE === null ) {
            $wsdl = ( 'http' === self::$protocol ) ? self::$_WsdlUriHTTP : self::$_WsdlUriHTTPS;
            self::$clientWCFAZURE = new SoapClient( $wsdl, array('cache_wsdl'=>WSDL_CACHE_NONE,'connection_timeout'=>120,'exceptions'=>true,'trace'=>false) );
        }
    }
    public static function AuthenticateClient( $accessKey ) { self::InitializeSoap(); $r=self::$clientWCFAZURE->AuthenticateClient(array('accessKey'=>$accessKey)); return $r->AuthenticateClientResult; }
    public static function ValidateSession( $token ) { self::InitializeSoap(); $r=self::$clientWCFAZURE->ValidateSession(array('token'=>$token)); return $r->ValidateSessionResult; }
    public static function ProductTypes( $token, $language ) { self::InitializeSoap(); $r=self::$clientWCFAZURE->ProductTypes(array('token'=>$token,'lang'=>$language)); return $r->ProductTypesResult; }
    public static function Products( $token, $language ) { self::InitializeSoap(); $r=self::$clientWCFAZURE->Products(array('token'=>$token,'lang'=>$language)); return $r->ProductsResult; }
    public static function ProductsTree( $token, $language ) { self::InitializeSoap(); $r=self::$clientWCFAZURE->ProductsTree(array('token'=>$token,'lang'=>$language)); return $r->ProductsTreeResult; }
}
