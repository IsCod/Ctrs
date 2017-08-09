<?php
//Ctres system redis
namespace Ctrs;
use Redis;

Class Rcache{
    public static $link = NULL;

    function __construct()
    {
    }

    private function getLink()
    {
        if (self::$link !== NULL) return self::$link;

        self::$link = new Redis();

        try {
            self::$link->connect('redis', 6379);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->code());
        }

        return self::$link;

    }

    public function __call($name, $args)
    {
        return call_user_func_array(array(self::getLink(), $name), $args);
    }

    public static function __callStatic($name, $args){
        return call_user_func_array(array(self::getLink(), $name), $args);
    }
}
