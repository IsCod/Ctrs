<?php
    // Ctrs require_once file
    function my_autoload($class){
        $classname = str_replace('Ctrs\\', '/', $class);
        include_once __DIR__ . "/" . $classname . ".php";
    }

    if(!function_exists("sql_auto_register")){
        function __autoload($class){
            my_autoload($class);
        }
    }else{
        sql_auto_register("my_autoload");
    }
