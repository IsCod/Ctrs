<?php
namespace Ctrs;
require_once __DIR__ . '/Ctrs.php';

if (!isset($argv[1])) {
    die("userage: php {$argv[0]} start stop restart or status\n");
}

//Create ctrs
$c = new Ctrs(100, 500, 1);

switch ($argv[1]) :
    case 'createUnitList':
        $c->createUnitList();
        break;
    case 'scanprice':
        $c->scanprice();
        break;
    case 'start':
            $c->on();
        break;
    case 'off':
            Ctrs::setOff(TRUE);
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
