<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/30
 * Time: 7:44 下午
 */

require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';
$socketAddr = "tcp://127.0.0.1:9000";

$client = stream_socket_client($socketAddr);

// 设置为非阻塞状态
stream_set_blocking($client,0);
$time = time();
fwrite($client,'non-blocking hello world');
var_dump($client,65535);

echo fread($client,65535);
echo "\n\r其他的业务\n\r";

echo time() - $time."\n\r";
$write = $expect = null;

// 客户端定时检测是否有数据
while (!feof($client)){
    $read[] = $client;
    var_dump(socket_select($read,$write,$expect,1));

//    echo fgets($client,1024);
    echo fread($client,65535);
    sleep(2);
}
// 当然，也可通过swoole的定时去去异步获取数据

// 客户端也不主动关闭连接，模拟长连接
fclose($client);


//$client = new \Asher\Io\NonBlocking\Client($socketAddr);
//$data = $client->readDataIntval($client->socketClient);
//echo $data;