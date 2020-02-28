<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/1
 * Time: 8:24 下午
 */

class EventSocket{
    public $socketAddr = 'tcp://0.0.0.0:9000';
    public $eventBase;
    public $server;

    public function run(){
        $this->socketServer();
        $this->eventBase = new \EventBase();
        $this->event();
        $this->loop();
    }

    public function socketServer(){

        $this->server = stream_socket_server($this->socketAddr);
        var_dump($this->server);
    }

    public function event(){

        $event = new \Event($this->eventBase,$this->server,\Event::PERSIST | \Event::READ,[$this,'newEventHandler']);
        $event->add(); // 添加事件
    }

    public function eventHandler($socket){
//        echo "connection start \n\r";
//        // 接收socket连接
//        $client = stream_socket_accept($socket);
//        $this->newEvent($this->eventBase,$client);
//        echo "connection end \n\r";
    }

    public function newEvent($eventBase,$client){
        $event = new \Event($eventBase,$client,\Event::PERSIST | \Event::READ,[$this,'newEventHandler']);
        $event->add();
    }

    public function newEventHandler($socket){
        var_dump(fread($socket,65535));
        fwrite($socket,'this is new event call');
        fclose($socket);
    }

    public function loop(){
        echo 'loop';
        $this->eventBase->loop();
    }
}

$event = new EventSocket();
$event->run();