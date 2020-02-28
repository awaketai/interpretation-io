<?php
/**
 * Created by PhpStorm.
 * User:
 */

// 安装信号处理器,安装多个不同的信号
pcntl_signal(SIGIO,'sig_handler');

function sig_handler($sig){
    sleep(2);
    echo "\n\r signal handler func \n\r";
}

// 给指定进程发送信号,告诉指定进程那个信号是进行什么处理的
// posix_getpid() 当先进程pid
// 信号针对多进程
posix_kill(posix_getpid(),SIGIO);

echo "\n\r the other thing \n\r";

// 信号分发,调用每个等待信号通过pcntl_signal()安装的处理器
// 执行每个信号发送的任务
pcntl_signal_dispatch();

