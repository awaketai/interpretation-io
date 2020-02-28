<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/5
 * Time: 4:21 下午
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/vendor/autoload.php';

$client = stream_socket_client("tcp://127.0.0.1:9000");
$t = time();

fwrite($client,"the first msg ...");
debug('第一次发送信息');
var_dump(fread($client,65535));

sleep(1);

fwrite($client,"the second msg ...");
debug('第二次发送信息');
var_dump(fread($client,65535));


sleep(4);
fwrite($client,"the three msg ...");
debug('第三次发送信息');
var_dump(fread($client,65535));

