<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/2
 * Time: 3:58 下午
 */

$fp = stream_socket_client("tcp://127.0.0.1:9000", $errno, $errstr, 30);
fwrite($fp,"信息");

// 异步执行
Swoole\Event::add($fp, function($fp) {
    $resp = fread($fp, 8192);
    echo $resp;
    //socket处理完成后，从epoll事件中移除socket
    swoole_event_del($fp);
    fclose($fp);
});
echo "Finish\n";  //Swoole\Event::add 不会阻塞进程，这行代码会顺序执行