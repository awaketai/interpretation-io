<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/3
 * Time: 10:18 下午
 */

require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/vendor/autoload.php';

$host = '0.0.0.0';
$port = 9000;
$worker = new \Asher\Io\Reactor\Swoole\Multi\Worker($host,$port);
$worker->stop();