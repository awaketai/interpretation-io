<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/2
 * Time: 11:00 下午
 */

namespace Asher\Io\Reactor\Event\Single;

use \Event as Event;
use \EventBase as EventBase;


class Reactor
{
    protected $reactor;
    protected $events;

    public static $instance = null;

    const READ = Event::READ | Event::PERSIST;
    const WRITE = Event::WRITE | Event::PERSIST;

    public static function getInstance(){
        if(is_null(self::$instance)){
            self::$instance = new self;
            self::$instance->reactor = new EventBase;
        }
        return self::$instance;
    }

    public function add($fd,$what,$cb,$arg = null){
        switch ($what){
            case self::READ:
                $event = new Event($this->reactor,$fd,self::READ,$cb,$arg);
                break;
            case self::WRITE:
                $event = new Event($this->reactor,$fd,self::WRITE,$cb,$arg);
                break;
            default:
                $event = new Event($this->reactor,$fd,$what,$cb,$arg);
                break;
        }
        $event->add();
        $this->events[(int)$fd][$what] = $event;
    }

    public function del($fd,$what = null){
        $events = $this->events[(int) $fd];
        if($what == 'all'){
            foreach ($events as $event){
                // 移除事件
                $event->free();
            }
        }else{
            if($what != self::READ && $what != self::WRITE){
                throw new \Exception('the events not exists!');
            }
            $events[$what]->free();
        }
    }

    public function run(){

        $this->reactor->loop();
    }

    public function stop(){
        foreach ($this->events as $event){
            foreach ($event as $e){
                $e->free();
            }
        }
        $this->reactoro->stop();
    }

}