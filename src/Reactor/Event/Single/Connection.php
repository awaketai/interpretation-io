<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/2
 * Time: 11:00 下午
 */

namespace Asher\Io\Reactor\Event\Single;


class Connection
{
    protected $conn;

    protected $server;
    protected $readBuffer = '';
    protected $writerBuffer = '';

    public function __construct($conn,Worker $server)
    {
        $this->conn = $conn;
        $this->server = $server;
    }

    public function handler(){

        Reactor::getInstance()->add($this->conn,Reactor::READ,[$this,'read']);
    }

    public function read($conn){
        debug('read info start...');
        $this->readBuffer = '';
        if(is_resource($conn)){
            while($content = fread($conn,65535)){
                $this->readBuffer .= $content;
            }
        }
        if($this->readBuffer){
            Reactor::getInstance()->add($conn,Reactor::WRITE,[$this,'write']);
        }else{
            Reactor::getInstance()->del($conn);
            fclose($conn);
            debug('connection closed');
        }
        debug('connection read info send');
    }

    public function write($conn){
        debug('connection write send msg');
        if(is_resource($conn)){
            if(is_callable($this->server->onMessage)){
                call_user_func($this->server->onMessage,$this->server,$conn,$this->readBuffer);
            }
        }
    }

}