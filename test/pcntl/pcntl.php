<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/2
 * Time: 9:14 下午
 */

$pid = pcntl_fork();
//// 父进程内执行返回 子进程pid
//// 子进程内执行返回 0
//// 失败时，返回-1
//
//
//pcntl_fork();
//// 父进程和子进程同时执行以下代码
//echo 1;
//
//while (true){
//
//}

for($i = 0;$i < 3;$i++){
    $son = pcntl_fork();
    if($son > 0){
        // 父进程
        echo "\n\r 这是父进程 \n\r";
        echo posix_getpid() . "\n\r";
    }elseif($son < 0){
        // 创建失败

    }else{
        // 子进程
        echo "\n\r".$pid . "--{$i} \n";
        echo "\n\r".$son . "--{$i} \n";
        echo posix_getpid() . "\n\r";
        break;
    }
    var_dump(pcntl_wait($statu));
}

while (true){

}
