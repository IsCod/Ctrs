# ctrs

Curve Trading System for btcchina api

## Installation
Firstly, download the ctrs
```
$ git clone https://github.com/IsCod/ctrs.git
```

## ctrsd command
You can help for ctrsd
```
$ ./ctrsd.sh help
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
$ ./ctrsd.sh reset
```

## start service
```
$ ./ctrsd.sh start
```

## stop service
```
$ ./ctrsd.sh stop
```

## restart service
```
$ ./ctrsd.sh restart (testing)
```

## docker-compose build
You can use docker-compose build for fast create server

up docker is start ctrsd daemon

create container:

ctrs_app_1, ctrs_mariadb_1 and ctrs_redis_1

```
$ docker-compose up -d
```

## Trading status for docker

```
$ docker exec -it ctrs_app_1 service ctrsd status
```

## status for fflush

print for dynamic information

you can use fflush-status but you require compile .c file
```
$ gcc fflush-status.c -o fflush-status
$ ./fflush-status
```
