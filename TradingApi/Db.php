<?php
namespace Ctrs;
use Pdo;
/*
* Db PDO CONNECT MYSQL
*/
Class Db{

    private static $link = NULL;

    // construct
    function __construct()
    {
        $this->getLink();
    }

    function __toString()
    {
        return "Pdo Connenect Mysql And Usage Config.ini for Mysql Host....\n";
    }

    private static function getLink()
    {
        if (self::$link !== NULL) return self::$link;

        $parse = parse_ini_file(__DIR__ . '/config.ini');
        $driver = $parse['driver'];
        $host = $parse['host'];
        $port = $parse['port'];
        $dbname = $parse['dbname'];
        $user = $parse['user'];
        $password = $parse['password'];
        $options = explode('=', $parse['options']);
        $options = array($options[0] => $options[1]);

        $dns = "{$driver}:host={$host};port={$port};dbname={$dbname}";

        self::$link = new Pdo($dns, $user, $password, $options);

        return self::$link ;
    }

    public function __call($name, $args)
    {
        return call_user_func_array(array(self::$link, $name), $args);
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array(array(self::$link, $name), $args);
    }
}
