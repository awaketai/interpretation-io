<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/29
 * Time: 9:42 下午
 */

namespace Asher\Io\Reactor\Swoole\MultiEndhanced;

use Asher\Io\Util\Inotify;
use http\Env\Request;
use http\Exception\BadUrlException;
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
        'worker_num' => 4,
        'worker_pid_files' => __DIR__ . '/worker_pid.txt',
        'master_pid_files' => __DIR__ . '/master_pid.txt',
        'context' => [
            'socket' => [
                'backlog' => '102400'
            ],
        ],
        'watch_file' => true,
        'heartbeat_check_interval' => 3 // 心跳检测时间
    ];
    public $workerPidfile = __DIR__ . 'worker_pid.txt';
    public $workerPids = []; // 记录当前进程pid到内存中
    protected $clients = []; // 客户端连接
    protected $timerIds = []; // 定时器id

    public function __construct($ip,$port)
    {
        $addr = $this->protocol.$ip.':'.$port;
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
        if(!$this->socket){
            debug('服务端不存在');
            return ;
        }
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
        // 如果能执行到此方法，则肯定在指定的心跳检测范围之内
        // 判断定时器是否存在
        if(isset($this->timerIds[(int)$fd])){
            // 清空定时器,避免定时器重复执行多次
           \Swoole\Timer::clear($this->timerIds[(int)$fd]);
           debug('清空定时器'.$this->timerIds[(int)$fd]);
        }
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
        // 发送消息之后进行心跳检测
        $this->heartbeat($fd);
//
    }

    public function fork(){
        goFork($this->config['worker_num'],[$this,'accept'],$this->config['worker_pid_files'],$this->workerPids);
    }

    // 重启 1.采用记录方式，文本记录
    // 在文本中记录所创建的进程id
    // 2.内存记录
    public function reload(){

        // 先终止所有子进程，然后重新创建
        // 这样的话相当于重新创建新的主进程，然后主进程再fork子进程
        // 适用于命令行的方式去管理进程 php server start|reload|stop
        $this->stop(false);
        $this->fork();
    }

    // kill掉一个进程后重新fork一个
    public function reloadSig(){
        debug($this->workerPids);
        foreach ($this->workerPids as $ko => $workerPid){
            posix_kill($workerPid,9);
            // 重新创建进程
            unset($this->workerPids[$ko]); // 删除原来的进程pid
            // 重新创建新的进程
            goFork(1,[$this,'accept'],$this->config['worker_pid_files'],$this->workerPids);
        }
    }

    public function sigHandler($sig){
        debug('receive type');
        debug($sig);
        switch ($sig){
            case SIGUSR1:
                // 重启
                debug('restart --- ');
                $this->reloadSig();
                break;
            case SIGINT:
                // 停止
                debug('stop -----');
                $this->stop();
                break;
        }
    }

    // 信号分发
    // SIGINT与SIGTERM区别
    //1）SIGINT关联ctrl+c
    //2）SIGINT只能结束前台进程
    //3）通过ctrl+c对当前进程发送结束信号，信号被进程树接收到（即：不仅当前进程，子进程也会收到结束信号）
    //SIGTERM与SIGKILL
    //1）SIGTERM可以被阻塞、处理和忽略；因此有的进程不能按预期的结束
    //2）kill不使用参数：发送SIGTERM信号，只有当前进程收到信号，若当前进程被kill，则子进程的父进程就会更改为init，即pid为1
    //3）kill命令的默认不带参数发生的信号就是SIGTERM，让程序友好的退出 ，当程序未退出时，可以使用kill -9强制退出
    public function monitorWorkersForLinux(){
        debug('linux console ');
        // 信号安装
        // SIGUSR1 用户自定义信号 默认处理：进程终止
        $r = pcntl_signal(SIGUSR1,[$this,'sigHandler'],false);
        debug('自定义信号11');
        debug($r);
        // 监控ctrl + c
        pcntl_signal(SIGINT,[$this,'sigHandler'],false);
        while(1){
            pcntl_signal_dispatch();
            $status = 0;
            // 循环保证有几个子进程就回收几个
            $pid = pcntl_wait($status,WUNTRACED);
            debug($pid);
            pcntl_signal_dispatch();
        }
    }

    /**
     * 监控文件变化，进行热重启
     * @param $event
     */
    public function watchEvent($event){
        $action = 'file:';
        switch ($event){
            case IN_CREATE ;
                $action = 'IN_CREATE';
                break;
            case IN_DELETE ;
                $action = 'IN_DELETE';
                break;
            case IN_MODIFY ;
                $action = 'IN_MODIFY';
            break;
                case IN_MOVE ;
                $action = 'IN_MOVE';
                break;
        }
        debug('worker reload by :'.$action.' file :'.$event['name']);
        $masterPid = pidGet($this->config['master_pid_files'])[0];
        posix_kill((int)$masterPid,SIGUSR1);
    }

    /**
     * 启动服务
     * @Author:
     * @Date:2020/1/29
     * @Time:9:43 下午
     */
    public function start(){
        // 清空子进程和父进程pid
        pidPut(null,$this->config['worker_pid_files']);
        pidPut(null,$this->config['master_pid_files']);
        // 循环终止子进程后，父进程还在循环的监听安装信号，导致子进程结束后，父进程依然存在
        // 记录主进程pid
        pidPut(posix_getpid(),$this->config['master_pid_files']);
        // 是否热重启
        if($this->config['watch_file']){
            $this->inotify = new Inotify(basePath(),[$this,'watchEvent']);
            $this->inotify->start();
        }
        $this->fork();

        $this->monitorWorkersForLinux();
    }

    // 此时启动服务出现了错误：
    // PHP Warning:  stream_socket_server(): unable to connect to tcp://0.0.0.0:9000 (Address already in use) in /www/wwwroot/study/swoole/swoole_test/io/src/Reactor/Swoole/Multi/Worker.php on line 38

    public function stop($masterKill = true){
        $workerPIds = pidGet($this->config['worker_pid_files']);
        if($workerPIds){
            foreach ($workerPIds as $workerPId) {
                if(empty($workerPId)){
                    continue;
                }
                // 循环停止进程
                $r = posix_kill((int)$workerPId,9);
            }
        }
        // 在停止子进程后，发现父进程也终止了，是在没有终止父进程的情况下
        // 是因为父进城没有阻塞了，所以父进程的流程执行完了。因此终止了
        // 读取父进程pid,并结束父进程
        if($masterKill){
            $masterPid = pidGet($this->config['master_pid_files'])[0];
            $t = posix_kill((int)$masterPid,9);
            $this->inotify = new Inotify(basePath(),[$this,'watchEvent']);
            $this->inotify->stop();
        }
    }

    // 心跳检测
    // 1.连接会很多
    // 2.
    public function heartbeat($socket){
        debug($socket);
        $time = $this->config['heartbeat_check_interval'];
        if(empty($time)){
            return ;
        }
        // 记录客户端最后一次发送信息的时间
        $this->clients[(int)$socket] = time();
        $timerId = \Swoole\Timer::after($time * 1000,function() use ($socket,$time){
            // 客户端在指定时间内是否还有发送消息的动作
            if((time() - $this->clients[(int)$socket]) >= $time){
                var_dump($socket);
                // 已经超时,事件移除
                \Swoole\Event::del($socket);
                fclose($socket);
                unset($this->clients[(int)$socket]);
                debug('info:client connection time out in '.$time.' -> '.(int)$socket);
                debug('closed conection : '.(int)$socket);
            }
            debug('closed conectionn8888 : '.(int)$socket);
        });
        $this->timerIds[(int)$socket] = $timerId;
    }
}