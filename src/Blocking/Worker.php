<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/29
 * Time: 9:42 下午
 */

namespace Asher\Io\Blocking;


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

        $this->socket = stream_socket_server($this->protocol.$ip.':'.$port);

    }

    // 接收连接和处理
    public function accept(){
        while (true){
            // 监听的过程是阻塞的，此函数
            $client = stream_socket_accept($this->socket);
//            $buffer = '';
//            while (!feof($client)){
//                $buffer .= fread($client,65535);
//
//            }
//            var_dump($buffer);
//            var_dump($client);
            if(!$client){
                return;
            }
            // 缓存当前连接的时间
            if (is_callable($this->onConnect)){
                // 执行闭包函数
                ($this->onConnect)($this);
            }
//            fwrite($client,'server hello world');
            $data = fread($client,65535);
//            $this->heartCheck($client,$data);
            if(is_callable($this->onReceive)){
                ($this->onReceive)($this,$client,$data);
            }
            // 模拟长连接，服务端不主动去关闭连接
//            fclose($client);
            // 服务端可以实现心跳检测进行连接的关闭

        }
    }

    /**
     * 相应，如果不设置相应头，由于这是tcp协议，在用浏览器访问的时候是http协议
     * 导致浏览器请求时得不到正确的相应
     * @param $client
     * @param $data
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

    /**
     * 心跳检测：一段时间内，客户端是否向服务端发送信息
     * @param $client
     * @param $data
     * @param int $heartbeatIdleTime
     * @param int $heartbeatCheckIntavl
     * @Author:
     * @Date:2020/1/30
     * @Time:8:18 下午
     */
    public function heartCheck($client,$data,$heartbeatIdleTime = 10,$heartbeatCheckIntavl = 3){
        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
//        $redis->auth();
        $dataKey = md5((string)$client).'data';
        $timeKey = md5((string)$client).'time';
        $redis->set($dataKey,$data,$heartbeatIdleTime); // 一个连接10秒未发送数据，关闭连接
//        $redis->set($timeKey,1,$heartbeatCheckIntavl);
        $this->heartCheckIntval($client);
    }

    // 多长时间检测一次
    public function heartCheckIntval($client,$heartbeatCheckIntval = 3){
        while (true){
            sleep($heartbeatCheckIntval); // 多长时间检测一次
            $data = fread($client,65535);
            if(empty($data)){
                echo '连接'.$client .'长时间未发送数据，将被关闭'.PHP_EOL;
                fclose($client);
            }
        }
    }
}