<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/1
 * Time: 9:27 下午
 */
class e{

    protected $eventBase;
    protected $client;
    protected $count;

    public function __construct($eventBase,$client,&$count)
    {
        $this->eventBase = $eventBase;
        $this->client = $client;
        $this->count = $count;
    }

    public function handler(){
        $event = new \Event($this->eventBase,$this->client,\Event::PERSIST | \Event::READ | \Event::WRITE,[$this,'newEventHandler']);
        $event->add();
        $this->count[(int)$this->client][\Event::PERSIST | \Event::READ | \Event::WRITE] = $event;
    }

    public function newEventHandler($socket){
        var_dump(fread($socket,65535));
        fwrite($socket,'this is new event call');
        fclose($socket);
        // 移除事件,避免多次请求后阻塞
        ($this->count[(int)$this->client][\Event::PERSIST | \Event::READ | \Event::WRITE])->free();
    }
}