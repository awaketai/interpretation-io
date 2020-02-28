<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/29
 * Time: 9:42 下午
 */

namespace Asher\Io\Reactor\Swoole\Single;

use \Swoole\Event;

class Worker
{
    // 自定义服务事件注册事件
    public $onReceive; // 数据接收事件
    public $onConnect; // 连接事件
    public $onClose; // 连接关闭事件

    public $socket;
    public $protocol = 'tcp://';

    public function __construct($ip,$port)
    {
        $addr = $this->protocol.$ip.':'.$port;
        $this->socket = stream_socket_server($addr);
        debug($addr);
    }

    // 接收连接和处理
    public function accept(){
        Event::add($this->socket,[$this,'swooleEventAdd']);
    }

    public function swooleEventAdd($fp){
        $client = stream_socket_accept($this->socket);
        if(!$client){
            return;
        }
        // 缓存当前连接的时间
        if (is_callable($this->onConnect)){
            // 执行闭包函数
            ($this->onConnect)($this);
        }
        // 事件嵌套，避免stream_socket_accept阻塞
        Event::add($client,[$this,'clientEvent']);
    }

    public function clientEvent($fd){
        //        fwrite($client,'server swoole event add ');
        $data = fread($fd,65535);
        if(empty($data) || !is_resource($fd)){
            //socket处理完成后，从epoll事件中移除socket
            // 避免服务端不知道客户端断开了，还给客户端不断发送消息
            swoole_event_del($fd);
            fclose($fd);
            return ;
        }
        if(is_callable($this->onReceive)){
            ($this->onReceive)($this,$fd,$data);
        }
//
    }

    /**
     * 启动服务
     * @Author:
     * @Date:2020/1/29
     * @Time:9:43 下午
     */
    public function start(){

        $this->accept();
    }
}