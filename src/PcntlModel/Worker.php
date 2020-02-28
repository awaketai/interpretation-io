<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/29
 * Time: 9:42 下午
 */

namespace Asher\Io\PcntlModel;


class Worker
{
    // 自定义服务事件注册事件
    public $onReceive; // 数据接收事件
    public $onConnect; // 连接事件
    public $onClose; // 连接关闭事件

    public $socket;
    public $protocol = 'tcp://';

    public $config = [
        'worker_num' => 3,
    ];
    public function __construct($ip,$port)
    {
        echo $this->protocol.$ip.':'.$port."\n\r";
        $this->socket = stream_socket_server($this->protocol.$ip.':'.$port);

    }

    // 接收连接和处理
    public function accept(){
        while (true){
            debug(posix_getpid());
            // 监听的过程是阻塞的，此函数
            $client = stream_socket_accept($this->socket);
            if(!$client){
                return;
            }
            // 缓存当前连接的时间
            if (is_callable($this->onConnect)){
                // 执行闭包函数
                ($this->onConnect)($this);
            }
            $data = fread($client,65535);
            if(is_callable($this->onReceive)){
                ($this->onReceive)($this,$client,$data);
            }
            // 模拟长连接，服务端不主动去关闭连接
//            fclose($client);
            // 服务端可以实现心跳检测进行连接的关闭

        }
    }

    public function fork(){
        goFork($this->config['worker_num'],[$this,'accept']);
    }

    /**
     * 启动服务
     * @Author:
     * @Date:2020/1/29
     * @Time:9:43 下午
     */
    public function start(){

        $this->fork();
    }
}