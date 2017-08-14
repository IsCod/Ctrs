#! /bin/bash
#
# ctrs
#
# chkconfig: 345 10 90
# description: Curve daemon Trading System

# author iscodd
# score https://github.com/IsCod/ctrs
CWD=$(pwd)
if [[ $CWD = '/' ]]; then
    CWD='/usr/local/src/ctrs'
fi

#初始化List
function createUnitList()
{
    php $CWD/TradingApi/Cron.php createUnitList > /dev/null 2>&1 &
}

function ScanningPrice()
{
    while [[ true ]]; do
        SIGINT=`ps -fe|grep php |grep -v grep |grep scanprice |awk -F " " '{print $2}'`
        if [[ ! -n $SIGINT ]];
            then
                php $CWD/TradingApi/Cron.php scanprice > /dev/null 2>&1 &
        fi
    done
}

function start(){
    php $CWD/TradingApi/Cron.php off > /dev/null 2>&1 &
    while [[ true ]]; do
        SIGINT=`ps -fe|grep php |grep -v grep |grep start |awk -F " " '{print $2}'`
        if [[ ! -n $SIGINT ]];
            then
                php $CWD/TradingApi/Cron.php start > /dev/null 2>&1 &
        fi
    done
}

function stop(){
    php $CWD/TradingApi/Cron.php stop > /dev/null 2>&1 &
    SIGINT=`ps -fe|grep sh |grep -v grep |grep ctrsd|grep start |awk -F " " '{print $2}'`
    if [[ -n $SIGINT ]];
        then
            kill -9 $SIGINT
    fi
}

rc=0
case "$1" in
    "")
        echo "ctrsd.server uping"
        createUnitList
        ScanningPrice > /dev/null 2>&1 &
        start
    start)
        echo "ctrsd.server uping"
        createUnitList
        ScanningPrice > /dev/null 2>&1 &
        start > /dev/null 2>&1 &
        rc=$?
    ;;

    stop)
        echo "ctrsd.server stoping ..."
        stop
        rc=$?
    ;;

    reset)
        read -p "Are you sure clean data  [y|n]? : " pat
        pat="$pat"
        if [[ $pat = 'y' ]]; then
            echo "start clean data ..."
            php $CWD/TradingApi/Cron.php reset
        fi
    ;;

    status)
        php $CWD/TradingApi/Cron.php status
    ;;

    restart|reload|force-reload)
        $0 stop && $0 start
        rc=$?
    ;;

    *)
        echo $"Usage: $0 {start|stop|status|reset|restart|reload|force-reload}"
        exit 2
esac

exit $rc;
