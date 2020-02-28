<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/29
 * Time: 9:54 下午
 */

require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

$host = '0.0.0.0';
$port = 9000;
$server = new \Asher\Io\Multiplexing\Worker($host,$port);

$server->onConnect = function ($socket){
    echo "\n\r connect success \n\r";
};
$server->onReceive = function ($socket,$client,$data){
    echo "\n\r 发送数据 \n\r";
    $socket->send($client,'multiplexing .client');
    echo "\n\r send end \n\r";

};

$server->start();
