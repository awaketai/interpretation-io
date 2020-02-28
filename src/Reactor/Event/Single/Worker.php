<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/2
 * Time: 11:00 下午
 */

namespace Asher\Io\Reactor\Event\Single;


class Worker
{
    public $socket = null;
    public $onConnect = null;
    public $onMessage = null;
    public $reactor = null;

    public function __construct($socketAddress)
    {
        $this->socket = stream_socket_server($socketAddress);
        // 设置为非阻塞
        stream_set_blocking(0);
    }

    public function start(){
        Reactor::getInstance()->add($this->socket,Reactor::READ,[$this,'createSocket']);
        Reactor::getInstance()->run();
    }

    public function createSocket($socket){
        $client = stream_socket_accept($socket);
        stream_set_blocking(0);
        if(is_callable($this->onConnect)){
            call_user_func($this->onConnect,$client);
        }
        debug('worker create socket set deal request');
        (new Connection($client,$this))->handler();
    }

}