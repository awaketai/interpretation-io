<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/2
 * Time: 4:12 下午
 */

require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

$host = '0.0.0.0';
$port = 9000;
$server = new \Asher\Io\AsyncModel\Worker($host,$port);

$server->onConnect = function ($socket){
    echo "connect success \n\r";
};
$server->onReceive = function ($socket,$client,$data){
    echo "发送数据\n\r";
    send($client,'接收到数据'.$data);
};

$server->start();
