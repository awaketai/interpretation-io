<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/29
 * Time: 9:42 下午
 */

namespace Asher\Io\Reactor\Swoole\Multi;

use http\Env\Request;
use \Swoole\Event;

class Worker
{
    // 自定义服务事件注册事件
    public $onReceive; // 数据接收事件
    public $onConnect; // 连接事件
    public $onClose; // 连接关闭事件

    public $socket;
    public $protocol = 'tcp://';
    public $socketAddress;
    public $config = [
        'worker_num' => 4
    ];
    public $workerPidfile = __DIR__ . 'worker_pid.txt';
    public $workerPids = []; // 记录当前进程pid到内存中

    public function __construct($ip,$port)
    {
        $addr = $this->protocol.$ip.':'.$port;
//        $this->socket = stream_socket_server($addr);
        $this->socketAddress = $addr;
        debug($addr);
    }

    // 接收连接和处理
    public function accept(){
        // 事件对socket重复监听了，存在问题，事件只能针对一个套接字进行监听
//        Event::add($this->socket,[$this,'swooleEventAdd']);
        // 设置端口可重复,参考workerman中的写法
        $opts = [
            'socket' => [
                'backlog' => 102400
            ]
        ];
        $context = stream_context_create($opts);
        // 设置创建的上下文
        stream_context_set_option($context,'socket','so_reuseport',1);
        $this->socket = stream_socket_server($this->socketAddress,$errno,$errstr,STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,$context);
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
            debug(posix_getpid());
            ($this->onReceive)($this,$fd,$data);
        }
//
    }

    public function fork(){
        goFork($this->config['worker_num'],[$this,'accept'],$this->workerPidfile,$this->workerPids);
    }

    // 重启 1.采用记录方式，文本记录
    // 在文本中记录所创建的进程id
    // 2.内存记录
    public function reload(){

        // 先终止所有子进程，然后重新创建
        // 这样的话相当于重新创建新的主进程，然后主进程再fork子进程
        // 适用于命令行的方式去管理进程 php server start|reload|stop
        $this->stop();
        $this->fork();
    }

    // kill掉一个进程后重新fork一个
    public function reloadSig(){
        foreach ($this->workerPids as $ko => $workerPid){
            posix_kill($workerPid,9);
            // 重新创建进程
            unset($this->workerPids[$ko]); // 删除原来的进程pid
            // 重新创建新的进程
            goFork(1,[$this,'accept'],$this->workerPidfile,$this->workerPids);
        }
    }

    public function sigHandler($sig){
        switch ($sig){
            case SIGUSR1:
                // 重启
                $this->reloadSig();
                break;
            case SIGKILL:
                // 停止
                $this->stop();
                break;
        }
    }

    // 信号分发
    public function monitorWorkersForLinux(){
        // 信号安装
        pcntl_signal(SIGUSR1,[$this,'sigHandler'],false);
        while(1){
            pcntl_signal_dispatch();
            $status = 0;
            $pid = pcntl_wait($status,WUNTRACED);
            pcntl_signal_dispatch();
        }
    }


    /**
     * 启动服务
     * @Author:
     * @Date:2020/1/29
     * @Time:9:43 下午
     */
    public function start(){
        // 清空pid记录文件
        pidPut(null,$this->workerPidfile);
        $this->fork();
    }

    // 此时启动服务出现了错误：
    // PHP Warning:  stream_socket_server(): unable to connect to tcp://0.0.0.0:9000 (Address already in use) in /www/wwwroot/study/swoole/swoole_test/io/src/Reactor/Swoole/Multi/Worker.php on line 38

    public function stop(){
        $workerPIds = pidGet($this->workerPidfile);
        if($workerPIds){
            foreach ($workerPIds as $workerPId) {
                // 循环停止进程
                posix_kill((int)$workerPId,9);
            }
        }
        // 在停止子进程后，发现父进程也终止了，是在没有终止父进程的情况下
        // 是因为父进城没有阻塞了，所以父进程的流程执行完了。因此终止了
    }

}