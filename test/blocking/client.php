<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/30
 * Time: 7:44 下午
 */

$client = stream_socket_client("tcp://127.0.0.1:9000");

while (true){
    echo "\n\r start send msg \n\r";
    fwrite($client,'hello world');
    echo "\n\r end send msg \n\r";
    var_dump(fread($client,65535));
    echo "\n\r end --- \n\r";

    sleep(1);
}

// 客户端也不主动关闭连接，模拟长连接
//fclose($client);

// 启动服务端后，再启动客户端，可以看到客户端接收到一次数据之后被阻塞了