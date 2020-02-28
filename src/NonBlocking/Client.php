<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/1/30
 * Time: 9:57 下午
 */

namespace Asher\Io\NonBlocking;


class Client
{
    public $socketClient;
    public $socketAddr;

    public function __construct($socketAddr,$outData = false)
    {
        $this->socketAddr = $socketAddr;
        $this->run($outData);
    }

    /**
     * @param bool $outData 是否直接输出数据
     */
    public function run($outData){

        $this->socketClient = stream_socket_client($this->socketAddr);
        $this->setBlocking();
        $this->writeData();
//        $this->readData();
        if($outData){
            $data = $this->readDataIntval($this->socketClient);
            echo $data;
        }
    }

    /**
     * 是否为非阻塞模式 0 ：是 1：否
     * @param int $isBlocking
     */
    public function setBlocking($isBlocking = 0){

        stream_set_blocking($this->socketClient,$isBlocking);
    }

    public function readData($len = 65535){

        return fread($this->socketClient,$len);
    }

    public function writeData($data = ''){
        if(!$data){
            $data = 'non-blocking data';
        }
        return fwrite($this->socketClient,$data);
    }

    public function close(){

        fclose($this->socketClient);
    }

    public function readDataIntval($socketClient){
        $data = '';
        while(!feof($this->socketClient)){
            $data .= $this->readData();
            sleep(2);
        }
        return $data;
//        $this->close();
    }

}