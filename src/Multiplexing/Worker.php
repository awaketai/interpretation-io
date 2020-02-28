<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/29
 * Time: 9:42 下午
 */

namespace Asher\Io\Multiplexing;


class Worker
{
    // 自定义服务事件注册事件
    public $onReceive; // 数据接收事件
    public $onConnect; // 连接事件
    public $onClose; // 连接关闭事件

    public $socketServer;
    public $protocol = 'tcp://';

    public $sockets = []; // socket 连接池
    public function __construct($ip,$port)
    {

        $address = $this->protocol.$ip.':'.$port;
        echo "\n\r {$address} \n\r";
        $this->socketServer = stream_socket_server($address);
        // 此socket可能正在使用，由于stream_socket_accept函数阻塞
        // 设置为非阻塞
        stream_set_blocking($this->socketServer,0);
        // 将socket放入连接池
        $this->sockets[(int)$this->socketServer] = $this->socketServer;
    }

    // 接收连接和处理
    public function accept(){
        while (true) {
            $read = $this->sockets;
            $write = $except = null;
            // 筛选出可用的socket连接
            stream_select($read, $write, $except, 1);
//            if(empty($read)){
//                continue;
//            }

            // 对可用连接进行处理
            foreach ($read as $socket) {
                // $socket 1. 可能是主 worker，也就是stream_socket_server
                // 2. 也可能是通过stream_socket_accept创建的连接
                if ($socket === $this->socketServer) {

                    // 如果是当前连接，创建新的连接
                    $this->createSocket();
                } else {

                    // 否则发送信息
                    $this->sendMsg($socket);
                }
            }
        }
    }

    /**
     * 相应，如果不设置相应头，由于这是tcp协议，在用浏览器访问的时候是http协议
     * 导致浏览器请求时得不到正确的相应
     * 问题：开始使用的是 \n\r 进行EOF，然后出现服务端一直发送消息阻塞的情况，换成 \r\n就好了
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
     * 创建连接，并将连接放入连接池中
     */
    public function createSocket(){
        $client = stream_socket_accept($this->socketServer);
        if(is_callable($this->onConnect)){
            ($this->onConnect)($this,$client);
        }
        // 将新创建的连接放入连接池
        $this->sockets[(int)$client] = $client;
    }

    /**
     * 发送信息
     * @param $client
     */
    public function sendMsg($client){
//        $data = fread($client,65535);
        $data = '';
//        while(!feof($client)){
//            echo "\n\r reading ... \n\r";
//            $data .= fgets($client,65535);
//        }
        $data = fread($client,65535);
        if(empty($data)){
            echo '连接即将close';
            // 关闭连接，并将连接从连接池中移除
            fclose($client);
            unset($this->sockets[(int)$client]);
            return;
        }
        if(is_callable($this->onReceive)){

            ($this->onReceive)($this,$client,$data);
        }
    }

    /**
     * 启动服务
     */
    public function start(){

        $this->accept();
    }

    public function debug($data,$flag = false){
        if($flag){
            var_dump($data);
        }else{
            echo "\n\r ==== >>>> : {$data} .\n\r";
        }
    }
}