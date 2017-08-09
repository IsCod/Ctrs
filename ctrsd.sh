#! /bin/bash
#
# ctrs
#
# chkconfig: 345 10 90
# description: Curve daemon Trading System

# author iscodd
# score https://github.com/IsCod/BtcCtrs

CWD=$(pwd)

#初始化List
function createUnitList()
{
    echo "start ctrsd.server for createUnitList"
    php $CWD/TradingApi/Cron.php createUnitList > /dev/null &
}

function ScanningPrice()
{
    while [[ true ]]; do

        SIGINT=`ps -fe|grep php |grep -v grep |grep scanprice |awk -F " " '{print $2}'`
        if [[ ! -n $SIGINT ]];
            then
                php $CWD/TradingApi/Cron.php scanprice > /dev/null &
        fi
    done
}

function start(){
    while [[ true ]]; do
        SIGINT=`ps -fe|grep php |grep -v grep |grep start |awk -F " " '{print $2}'`
        if [[ ! -n $SIGINT ]];
            then
                php $CWD/TradingApi/Cron.php start > /dev/null &
        fi
    done
}

function stop(){
    SIGINT=`ps -fe|grep sh |grep -v grep |grep ctrsd|grep start |awk -F " " '{print $2}'`
    if [[ -n $SIGINT ]];
        then
            kill -9 $SIGINT
    fi

    SIGINT=`ps -fe|grep php |grep -v grep| grep Cron.php |awk -F " " '{print $2}'`
    if [[ -n $SIGINT ]]; then
        kill -9 $SIGINT
    fi
}

rc=0
case "$1" in
    start)
        echo "ctrsd.server uping ..."
        createUnitList
        ScanningPrice > /dev/null &
        start > /dev/null &
        rc=$?
    ;;

    stop)
        echo "ctrsd.server stoping ..."
        stop
        rc=$?
    ;;

    restart|reload|force-reload)
        $0 stop
        $0 start
        rc=$?
    ;;

    status)
        status
    ;;

    *)
        echo $"Usage: $0 {start|stop|status|restart|reload|force-reload}"
        exit 2
esac

exit $rc;
