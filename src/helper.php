<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/2
 * Time: 12:24 下午
 */

/**
 * 相应，如果不设置相应头，由于这是tcp协议，在用浏览器访问的时候是http协议
 * 导致浏览器请求时得不到正确的相应
 * @param resource $client stream_socket_accept返回的 resource
 * @param string $data
 * @param bool $flag
 */
function send($client,$data,$flag = true){
    if($flag){
        fwrite($client,$data);
    }else{
        $responseHeader = "HTTP/1.1 200 OK \r\n";
        $responseHeader .= "Content-type:text/html;charset=UTF-8 \r\n";
//        $responseHeader .= "Content-Encoding: gzip \r\n"; // 如果内容是经过gip编码的，可以设置此相应头，
        $responseHeader .= "Connection:keep-alive \r\n";
        $responseHeader .= "Content-length:".strlen($data) ."\r\n\r\n";
        $responseHeader .= $data;
        fwrite($client,$responseHeader);
    }
}

function debug($data){
    if(is_string($data) || is_int($data)){
        echo "\n\r ==== >>>> : {$data} \n\r";

    }else{
        var_dump($data);
    }
}

/**
 * 创建多进程，父进程管理子进程
 * @param int $workerNum 创建的子进程数量
 * @param callable $callback 子进程要执行的函数
 */
function goFork($workerNum,callable $callback,$workerPidile = '',&$workerPids = []){
    for($i = 0;$i < $workerNum; $i++){
        // 创建子进程
        $son = pcntl_fork();
        if($son > 0){
            // 父进程
        }elseif($son < 0 ){
            // 创建失败
        }else{
            if($workerPidile){
                // 记录worker pid到文件
                pidPut(posix_getpid(),$workerPidile);
            }
            if($workerPids){
                $workerPids[] = posix_getpid();
            }
            debug(posix_getpid());
            $callback();
//            break;
            // 停止子进程
            exit;
        }
    }
    // 回收子进程处理，可说使用for循环进行处理
    for($i = 0;$i < $workerNum; $i++) {
        if ($son > 0) {
            $status = 0;
            $pid = pcntl_wait($status);
            debug('回收子进程' . $pid);
        }
    }
}

/**
 * record the created worker pid
 * @param int $data worker pid
 * @param string $path
 */
function pidPut($data,$path){
    empty($data) ?
        file_put_contents($path,null) :
        file_put_contents($path,$data.'|',8);
}

/**
 * get worker id
 * @param $path
 * @return array
 */
function pidGet($path){
    $str = file_get_contents($path);
    return explode('|',substr($str,0,strlen($str)));
}

function basePath(){
    return __DIR__ ;
}