<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/1
 * Time: 9:09 下午
 */

require_once 'e.php';
$socketAddr = 'tcp://0.0.0.0:9000';
$server = stream_socket_server($socketAddr);

set_error_handler('errorHandler');
 function errorHandler($no,$msg,$file,$line){
     var_dump($no,$msg,$file,$line);
 }
echo $socketAddr . "\n\r";
$count = []; // 这个变量必须得有，记录所创建的事件
$eventBase = new \EventBase();
$event = new \Event($eventBase,$server,\Event::PERSIST | \Event::READ| \Event::WRITE,'eventHandler');

// 第一个event事件回调函数
function eventHandler($socket){
    global $eventBase;
    echo "connection start \n\r";
    // 接收socket连接
    $client = stream_socket_accept($socket);
    newEvent($eventBase,$client);
    echo "connection end \n\r";
}

function newEvent($eventBase,$client){
    global $count;
    $e = new e($eventBase,$client,$count);
    $e->handler();
}

function newEventHandler($socket){
    var_dump(fread($socket,65535));
    fwrite($socket,'this is new event call');
    fclose($socket);
}

$event->add(); // 添加事件
$count[(int)$server][\Event::PERSIST | \Event::READ| \Event::WRITE] = $server;
$eventBase->loop(); // 循环执行事件,只能设置一次