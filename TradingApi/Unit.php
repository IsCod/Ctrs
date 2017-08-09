<?php
#Curve Trading System Unit
namespace Ctrs;

/**
* Unit
* body object->state 0,1,-1; 0 初始化, 1 表示流程完成， 2 表示流程进入阶段
*/
class Unit{
    private $data = array();

    function __construct($price = 0)
    {
        $this->data = array(
            'state' => 0,
            'price' => $price,
            'tradid' => 0
        );
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __get($key)
    {
        return $this->data[$key];
    }
}
