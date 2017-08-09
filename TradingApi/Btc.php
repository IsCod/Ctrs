<?php
#Curve Trading System Btc
namespace ctrs;
use BTCChinaAPI;
require_once __DIR__ . '/../btcchina-api-php/BTCChinaLibrary.php';

Class Btc{
    private static $accessKey = '';
    private static $secretKey = '';
    private static $btcApi = NULL;

    function __construct()
    {
    }

    private static function getApi()
    {
        if (self::$btcApi !== NULL) return self::$btcApi;
        self::$btcApi = new BTCChinaAPI(self::$accessKey, self::$secretKey);
        return self::$btcApi;
    }

    public function __call($name, $args)
    {
        return call_user_func_array(array(self::getApi(), $name), $args);
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array(array(self::getApi(), $name), $args);
    }
}
