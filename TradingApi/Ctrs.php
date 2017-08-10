<?php
#Curve Trading System Unit
namespace Ctrs;
require_once __DIR__ . '/Library.php';

use ctrs\Btc;
use Ctrs\Db;
use Ctrs\Unit;
use Ctrs\Rcache;
use Ctrs\Log;


/**
* 内部单位采用分为计算单位
* @param float start default 1.00
* @param float end default 100.00
* @param float setp default 1.00
*/
class Ctrs{

    public $orderList = array();
    public $amount = 0.1;
    public $start = 100;
    public $end = 10000;
    public $setp = 100;
    public $btcAPI = NULL;
    public $db = NULL;
    public $price = NULL;
    public $market = 'LTCCNY'; // OR BTCCNY || LTCBTC
    private static $switching = FALSE;// switch FALSE OR TRUE;

    function __construct($start = 1.0, $end = 100.00, $setp = 1.00)
    {
        $this->start = (int)($start * 100);
        $this->end = (int)($end * 100);
        $this->setp = (int)($setp * 100);
        $this->btcAPI = new Btc();
        // $this->db = new Db();
        $this->orderList = $this->createData();
    }

    /**
    * Create Initialize Order Data List
    * Internal calculation unit points
    * @param float $start
    * @param float $end
    * @param float $setp
    * @return array list
    *
    */
    private function createData()
    {
        // conversion points
        $arr = range($this->start, $this->end, $this->setp);

        $unitlist = array();

        $rcache = new Rcache();

        $price = $this->getPrice();

        foreach ($arr as $key => $value)
        {

            $rvalue = unserialize($rcache->hGet('CtrsUnitList', $key));
            if (!is_object($rvalue))
            {
                $value = new Unit($value);

                 if ($value->price > $price['ask']) $value->state = 1;

                $rcache->hSet('CtrsUnitList', $key, serialize($value));
            }else{
                $value = $rvalue;
            }

            $unitlist[] = $value;
        }

        return $unitlist;
    }

    public function createUnitList(){
        $this->createData();
    }

    public function clearData(){
        try {
            return Rcache::delete('CtrsUnitList');
        } catch (Exception $e) {
            throw new Exception("Error Processing Request" . $e->getMessage(), $e->Code());
        }
    }

    //生成价格，使用脚本实时刷新
    public function createPrice(){

        $price = $this->btcAPI->getMarketDepth(1, 'ALL');

        if (!$price) return FALSE;

        $price = (object)array(
            'BTCCNY' => (object)array(
                'ask' => (object)array(
                    'price' => $price->market_depth_btccny->ask[0]->price * 100,
                    'amount' => $price->market_depth_btccny->ask[0]->amount,
                ),
                'bid' => (object)array(
                    'price' => $price->market_depth_btccny->bid[0]->price * 100,
                    'amount' => $price->market_depth_btccny->bid[0]->amount,
                )
            ),
            'LTCCNY' => (object)array(
                'ask' => (object)array(
                    'price' => $price->market_depth_ltccny->ask[0]->price * 100,
                    'amount' => $price->market_depth_ltccny->ask[0]->amount,
                ),
                'bid' => (object)array(
                    'price' => $price->market_depth_ltccny->bid[0]->price * 100,
                    'amount' => $price->market_depth_ltccny->bid[0]->amount,
                )
            ),
             'LTCBTC' => (object)array(
                'ask' => (object)array(
                    'price' => $price->market_depth_ltcbtc->ask[0]->price * 100,
                    'amount' => $price->market_depth_ltcbtc->ask[0]->amount,
                ),
                'bid' => (object)array(
                    'price' => $price->market_depth_ltcbtc->bid[0]->price * 100,
                    'amount' => $price->market_depth_ltcbtc->bid[0]->amount,
                )
            )
        );


        Rcache::set('CtrsPriceAll', serialize($price));
        Rcache::setTimeout('CtrsPriceAll', 5);
        $this->price = $price;
        return $this->price;
    }

    //获取价格
    public function getPrice($market = FALSE)
    {
        $price = Rcache::get('CtrsPriceAll');
        if (!$price) {
            $this->price = $this->createPrice();
        }else{
            $this->price = unserialize($price);
        }

        if ($market === FALSE) $market = $this->market;

        return $this->price->{$market};
    }

    /**
    * 扫描单元
    * Scanning List
    */
    private function ScanningOrder()
    {

        $price = $this->getPrice();
        $min_price = $price->bid->price * 0.5;

        $rcache = new Rcache();

        foreach ($this->orderList as $key => $unit)
        {
            if ($unit->price < $min_price && $unit->state === 0) continue;
            $unit = $this->manageUnit($unit, $price);
            $rcache->hSet('CtrsUnitList', $key, serialize($unit));
            if (is_object($unit)) $this->orderList[$key] = $unit;
        }
    }

    /**
    * 处理单元
    */
    private function manageUnit($unit, $price)
    {

        //进行买入交易
        if ($unit->state === 0 && $unit->price > $price->ask->price)
        {

            //创建订单
            try {
                $orderId = $this->btcAPI->placeOrder($price->ask->price*0.001, $this->amount,  $this->market);
                $unit->state = 2;
                $unit->tradid = $orderId;
                Log::write('unit-list', array('state' => 'open', 'orderId' => $orderId, 'type' => 'bid', 'price' => $price->bid->price*0.01, 'amount' => $this->amount));
            } catch (Exception $e) {
                throw new Exception("Place Order error in manageUnit : " . $e->geMessage(), $e->getCode());
            }
        }

        //进行卖出交易
        if ($unit->state === 1 && $unit->price * 1.008 < $price->bid->price)
        {
            try {
                $orderId = $this->btcAPI->placeOrder($price->bid->price*0.01, -$this->amount, $this->market);
                $unit->state = 2;
                $unit->tradid = $orderId;
                Log::write('unit-list', array('state' => 'open', 'orderId' => $orderId, 'type' => 'ask', 'price' => $price->bid->price*0.01, 'amount' => $this->amount));
            } catch (Exception $e) {
                throw new Exception("Place Order error in manageUnit : " . $e->geMessage(), $e->getCode());
            }
        }
        //订单正在进行时
        if ($unit->state === 2)
        {
            $res = $this->btcAPI->getOrder($unit->tradid, $this->market);
            switch ($res->order->status)
            {
                //cancell order
                case 'cancelled':
                    if ($res->order->type == 'bid') {
                        $unit = new Unit($unit->price);
                    }else{
                        $unit->state = 1;
                    }
                    Log::write('unit-list', array('state' => 'cancelled', 'orderId' => $res->order->id, 'type' => $res->order->type, 'price' => $res->order->price));
                    break;

                //closed order
                case 'closed':
                    if($res->order->type == "bid")
                    {
                        $unit->state = 1;
                    } else
                    {

                        $unit = new Unit($unit->price);
                    }

                    Log::write('unit-list', array('state' => 'closed', 'orderId' => $res->order->id, 'type' => $res->order->type, 'price' => $res->order->price));
                    break;

                // open order
                case 'open':
                    if((time() - $res->order->date) > 20)
                    {
                        $this->btcAPI->cancelOrder((int)$unit->tradid, 'LTCCNY');//cancel order
                    }
                    break;
                default:
                        $unit = new Unit($unit->price);
                    break;
            }
        }

        return $unit;
    }

    //设置开关
    public static function setOff($value = TRUE){
        Rcache::set('Ctrs:SwitchIng', $value);
        self::$switching = $value;
        return self::$switching;
    }

    public function getOff(){
        self::$switching = (bool)Rcache::get('Ctrs:SwitchIng');
        return self::$switching;
    }

    //开启循环扫描模式
    public function on(){
        while ($this->getOff()) {
            echo "Start Scanning Unit List\n";
            $this->ScanningOrder();
            echo "End Scanning Unit List\n";
        }
        return true;
    }

    //获取当前的列表状态，并格式化输出
    public function getinfo(){
        echo "Ctrs status:\t";
        echo $this->getOff() ? "runing......" : "stop......";
        echo "\n";
        $count = count($this->orderList);
        echo "Count: " . $count . "\n";
        echo "Start collect ";
        $askNum = $bidList = 0;
        foreach ($this->orderList as $key => $unit):
            $unit->price = $unit->price * 0.01;
            if ($unit->tradid > 0):
                $res = $this->btcAPI->getOrder($unit->tradid, $this->market);
                if ($res):
                    if ($key % 10 == 0) echo '.';
                    $date = date('Y-m-d H:i:s' , $res->order->date);
                    $str = "price :{$unit->price} \tstate : {$unit->state}, orderId : {$unit->tradid}";
                    $str .= "\torderType : {$res->order->type}, orderPrice : {$res->order->price} amount : {$res->order->amount}, date : {$date}";
                    $str .= "\n";
                    $return[$res->order->type][] = $str;
                endif;
            endif;
        endforeach;

        echo "\n";
        foreach ($return as $key => $value) {
            echo "[" . $key . "]" . $value;
        }
    }
}
