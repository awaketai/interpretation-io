<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/31
 * Time: 10:39 下午
 */

namespace Asher\Io\SignalDriven;


class Worker
{
    // 自定义服务事件注册事件
    public $onReceive; // 数据接收事件
    public $onConnect; // 连接事件
    public $onClose; // 连接关闭事件

    public $socket;
    public $client;

    public $protocol = 'tcp://';
    public function __construct($ip,$port)
    {

        $this->socket = stream_socket_server($this->protocol.$ip.':'.$port);

    }

    // 接收连接和处理
    public function accept(){
        while (true){
            // 监听的过程是阻塞的，此函数
            $this->client = stream_socket_accept($this->socket);
            if(!$this->client){
                return;
            }
            // 安装信号处理器
            pcntl_signal(SIGIO,[$this,'signalHandler']);
            // 给指定信号发送消息
            posix_kill(posix_getpid(),SIGIO);
            // 信号分发
            pcntl_signal_dispatch();
            echo "\n\r accept end \n\r ";
        }
    }

    /**
     * 相应，如果不设置相应头，由于这是tcp协议，在用浏览器访问的时候是http协议
     * 导致浏览器请求时得不到正确的相应
     * @param $client
     * @param $data
     * @Author:
     * @Date:2020/1/30
     * @Time:7:16 下午
     */
    public function send($client,$data){
        $responseHeader = "HTTP/1.1 200 OK \r\n";
        $responseHeader .= "Content-type:text/html;charset=UTF-8 \r\n";
//        $responseHeader .= "Content-Encoding: gzip \r\n"; // 如果内容是经过gip编码的，可以设置此相应头，
        $responseHeader .= "Connection:keep-alive \r\n";
        $responseHeader .= "Content-length:".strlen($data) ."\r\n\r\n";
        $responseHeader .= $data;
        fwrite($client,$responseHeader);
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

    public function signalHandler($sig){
        var_dump($sig);
        // 缓存当前连接的时间
        if (is_callable($this->onConnect)){
            // 执行闭包函数
            ($this->onConnect)($this);
        }
        $data = fread($this->client,65535);
        if(is_callable($this->onReceive)){
            ($this->onReceive)($this,$this->client,$data);
        }
    }

}