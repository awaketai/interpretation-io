<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/1
 * Time: 5:21 下午
 */

use \Event as Event;
use \EventBase as EventBase;

$eventBase = new EventBase();
// | 事件累加
$event = new Event($eventBase,-1,Event::PERSIST | Event::TIMEOUT,function(){
    echo "\n\r hello world event \n\r";
});

$event->add(0.1); // 添加一个事件
$eventBase->loop(); // 循环调用设置在EventBase中的事件执行事件

//EventBase 事件库，存储创建的事件
// event :事件

// Event::PERSIST : 表示事件循环执行
// Event::TIMEOUT : 表示间隔多久执行

// public Event::__construct ( EventBase $base , mixed $fd , int $what , callable $cb [, mixed $arg = NULL ] )

// $fd
// -1 : 计时器
// 信号 : 信号的标识 如 SIGIO SIGHUP等
// socket : 传递socket资源

// 1.Event::PERSIST 针对回调函数
// 2.$event->add([]);中间的参数是间隔多久
// 3.不添加add()方法？

// ****************
