<?php
#Curve Trading System Unit
namespace Ctrs;

require_once __DIR__ . '/Library.php';

// use Ctrs\Btc;
// use Ctrs\Db;
// use Ctrs\Unit;
// use Ctrs\Rcache;
// use Ctrs\Log;


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
        $this->orderList = $this->createUnitList();
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
    public function createUnitList()
    {
        // conversion points
        $arr = range($this->start, $this->end, $this->setp);

        $unitlist = array();

        $rcache = new Rcache();

        $price = $this->getPrice();

        foreach ($arr as $key => $value):

            $rvalue = unserialize($rcache->hGet('CtrsUnitList', $value));
            $valueunit = $rvalue;

            if (!is_object($valueunit)):
                $valueunit = new Unit($value);
                 if ($valueunit->price > $price->ask->price) $valueunit->state = 1;
                $rcache->hSet('CtrsUnitList', $value, serialize($valueunit));
            endif;

            $unitlist[$value] = $valueunit;
        endforeach;

        return $unitlist;
    }

    public function clearData()
    {
        try {
            return Rcache::delete('CtrsUnitList');
        } catch (Exception $e) {
            throw new Exception("Error Processing Request" . $e->getMessage(), $e->Code());
        }
    }

    //生成价格，使用脚本实时刷新
    public function createPrice()
    {
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
        return isset($this->price->{$market}) ? $this->price->{$market} : $this->price;
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

        foreach ($this->orderList as $key => $unit):
            if ($unit->price < $min_price && $unit->state === 0) continue;
            $unit = $this->manageUnit($unit, $price);
            $rcache->hSet('CtrsUnitList', $key, serialize($unit));
            if (is_object($unit)) $this->orderList[$key] = $unit;
        endforeach;
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
                if ($price->ask->amount < $unit->amount) $unit->amount = $price->ask->amount;
                $orderId = $this->btcAPI->placeOrder($price->ask->price*0.01, $unit->amount,  $this->market);
                $unit->state = 2;
                $unit->tradid = $orderId;
                Log::write('unit-list', array('state' => 'open', 'orderId' => $orderId, 'type' => 'bid', 'price' => $price->bid->price*0.01, 'amount' => $unit->amount));
            } catch (Exception $e) {
                throw new Exception("Place Order error in manageUnit : " . $e->geMessage(), $e->getCode());
            }
        }

        //进行卖出交易
        if ($unit->state === 1 && $unit->price * 1.008 < $price->bid->price)
        {
            try {
                $orderId = $this->btcAPI->placeOrder($price->bid->price*0.01, -$unit->amount, $this->market);
                $unit->state = 2;
                $unit->tradid = $orderId;
                Log::write('unit-list', array('state' => 'open', 'orderId' => $orderId, 'type' => 'ask', 'price' => $price->bid->price*0.01, 'amount' => $unit->amount));
            } catch (Exception $e) {
                throw new Exception("Place Order error in manageUnit : " . $e->geMessage(), $e->getCode());
            }
        }
        //订单正在进行时
        if ($unit->state === 2)
        {
            $res = $this->btcAPI->getOrder($unit->tradid, $this->market);
            switch ($res->order->status):
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
            endswitch;
        }

        return $unit;
    }

    //设置开关
    public static function setOff($value = TRUE)
    {
        Rcache::set('Ctrs:SwitchIng', $value);
        self::$switching = $value;
        return self::$switching;
    }

    public function getOff()
    {
        self::$switching = (bool)Rcache::get('Ctrs:SwitchIng');
        return self::$switching;
    }

    //开启循环扫描模式
    public function on()
    {
        while ($this->getOff()):
            echo "Start Scanning Unit List\n";
            $this->ScanningOrder();
            echo "End Scanning Unit List\n";
        endwhile;
    }

    public function scanprice()
    {
        while ($this->getOff()):
            echo "New Price:\n";
            $allprice = $this->createPrice();
            foreach ($allprice as $key => $value) :
                $value->ask->price = $value->ask->price * 0.01;
                $value->bid->price = $value->bid->price * 0.01;
                echo "{$key} :\t askPrice : {$value->ask->price}, \tbidPrice : {$value->bid->price}\n";
            endforeach;
        endwhile;
    }

    public function getinfo()
    {
        echo "\nOn/Off:   ";
        echo $this->getOff() ? "On" : "Off";
        echo "\n\n";

        $price = $this->getPrice("ALL");

        $accoutInfo = $this->btcAPI->getAccountInfo();
        echo "You AccountInfo:\n";
        echo "  Balance: \n";

        $cny = $accoutInfo->balance->cny->amount + $accoutInfo->frozen->cny->amount;
        $btc = $accoutInfo->balance->btc->amount + $accoutInfo->frozen->btc->amount;
        $ltc = $accoutInfo->balance->ltc->amount + $accoutInfo->frozen->ltc->amount;

        echo "  Cny: " . $cny . "\tBtc: " . $btc . "\tLtc: " . $ltc;
        echo "\tSumCny:" . ($cny + $ltc * $price->LTCCNY->bid->price * 0.01 + $btc * $price->BTCCNY->bid->price * 0.01) . "\n\n";

        echo "Price: \n";
        echo "  Bid: " . $price->{$this->market}->bid->price . "\tAsk: " . $price->{$this->market}->ask->price . "\n\n";
        echo "Unitlist status:\n";
        echo "  *------------------------------------------------*\n";

        $list = $this->orderList;
        ksort($list);

        $count = count($list);
        $count_all = array(0=>0, 1=>0, 2=>0);

        $i = 0;
        foreach ($list as $key => $value) :
            $i++;
            if ($i < 3 || $i > ($count - 3)):
                echo "  * key: ".$key . "   state: " . $value->state . "   price: " . $value->price . "   tradid: " . $value->tradid . "    unit-amount: " . $value->amount . "\n";
            ;else :
                $bid_diff = $price->{$this->market}->bid->price - $key - 200;
                $ask_diff = $price->{$this->market}->ask->price - $key + 200;
                $bid_diff += $value->state;
                if ($bid_diff < 0 && $ask_diff > 0):

                    echo "  * key: ".$key . "   state: " . $value->state . "   price: " . $value->price . "   tradid: " . $value->tradid . "    unit-amount: " . $value->amount;
                    if ($value->tradid > 0):
                        $res = $this->btcAPI->getOrder($value->tradid, $this->market);
                        $date = date("Y-m-d H:i:s" , $res->order->date);
                        echo "  orderType: {$res->order->type}  date: {$date}   amount : {$res->order->amount_original}";
                    endif;

                    echo "\n";
                endif;

            endif;

            if ($i == 3 || $i == ($count - 3)):
                echo "  *\n";
            endif;

            $count_all[$value->state] +=  1;
        endforeach;
        echo "  *------------------------------------------------*\n\n";
        echo "Unitlist count:\n";
        echo "  Initial:\t{$count_all[0]}\n";
        echo "  Open:\t\t{$count_all[1]}\n";
        echo "  Ordering:\t{$count_all[2]}\n";
        echo "  Sum:\t\t" . array_sum($count_all) . "\n\n";
    }
}
