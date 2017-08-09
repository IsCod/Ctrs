# ctrs

Curve Trading System for btcchina api

## Installation
Firstly, download the ctrs
```
$ git clone https://github.com/IsCod/ctrs.git
```

## ctrswork command
You can help for ctrswork
```
$ ./ctrswork.sh help
```

## change config for btcchina api key
```
$ vim TradingApi/Btc.php
```

set you accessKey and secretKey

For example,
```
private static $accessKey = 'youaccesskey';
private static $secretKey = 'yousecretkey';
```

## Initialize service
```
$ ./ctrswork.sh init
```

## start service
```
$ ./ctrswork.sh start
```

## stop service
```
$ ./ctrswork.sh stop
```

## restart service
```
$ ./ctrswork.sh restart
```


## docker-compose build
You can use docker-compose build for fast create server

```
$ docker-compose up -d
```
docker-compose create ctrs_app_1 and ctrs_mariadb_1 and ctrs_redis_1 three container

ctrs_app_1 is core code run container

