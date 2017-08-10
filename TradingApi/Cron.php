<?php
namespace Ctrs;
require_once __DIR__ . '/Ctrs.php';

if (!isset($argv[1])) {
    die("userage: php {$argv[0]} start stop restart or status\n");
}

//Create ctrs
$c = new Ctrs(100, 400, 1);

switch ($argv[1]) :
    case 'createUnitList':
            $c->createUnitList();
        break;
    case 'scanprice':
            while (true) :
                echo "New Price:\n";
                $allprice = $c->createPrice();
                foreach ($allprice as $key => $value) :
                    $value->ask->price = $value->ask->price * 0.01;
                    $value->bid->price = $value->bid->price * 0.01;
                    echo "{$key} :\t askPrice : {$value->ask->price}, \tbidPrice : {$value->bid->price}\n";
                endforeach;
            endwhile;
        break;
    case 'start':
            Ctrs::setOff(TRUE);
            $c->on();
        break;
    case 'stop':
            Ctrs::setOff(FALSE);
        break;
    case 'status':
            $c->getInfo();
        break;
    case 'reset':
            $c->clearData();
        break;
    case 'test':
            Ctrs::test();
        break;
    default:
            echo "userage: php {$argv[0]} start stop reset restart or status\n";
        break;

endswitch;
