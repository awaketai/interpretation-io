<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/5
 * Time: 2:39 下午
 */

namespace Asher\Io\Util;


class Inotify
{
    private $fd;
    private $watchPath;
    private $watchMask;
    private $watchHandler;
    private $doing = false;
    private $fileTypes = [
        '.php' => true
    ];
    private $wdPath = [];
    private $pathWd = [];

    public function __construct($watchPath,callable $watchHandler,$watchMask = IN_CREATE | IN_DELETE | IN_MODIFY | IN_MOVE)
    {
        if(!extension_loaded('inotify')){
            debug('the inotify extension not found');
            exit;
        }
        $this->fd = inotify_init();
        $this->watchPath = $watchPath;
        $this->watchMask = $watchMask;
        $this->watchHandler = $watchHandler;
        $this->watch($this->watchPath);
    }

    // 添加需要校验的文件类型
    public function addFileType($type){
        $type = '.'.trim($type,'.');
        $this->fileTypes[$type] = true;
    }

    public function addFileTypes(array $types){
        foreach ($types as $type){
            $this->addFileType($type);
        }
    }

    public function watch($path){
        $wd = inotify_add_watch($this->fd,$path,$this->watchMask);
        if($wd === false){
            return false;
        }
        $this->bind($wd,$path);
        if(is_dir($path)){
            $wd = inotify_add_watch($this->fd,$path,$this->watchMask);
            if($wd === false){
                return false;
            }
            $this->bind($wd,$path);
            $files = scandir($path);
            foreach ($files as $file){
                if($file == '.' || $file == '..'){
                    continue;
                }
                $file = $path . DIRECTORY_SEPARATOR . $file;
                if(is_dir($file)){
                    $this->watch($file);
                }

            }
        }
        return true;
    }

    public function clearWatch(){
        foreach ($this->wdPath as $wd => $path){
            inotify_rm_watch($this->fd,$wd);
        }
        $this->wdPath = [];
        $this->pathWd = [];
    }

    public function bind($wd,$path){
        $this->pathWd[$path] = $wd;
        $this->wdPath[$wd] = $path;
    }

    public function unbind($wd,$path = null){
        unset($this->wdPath[$wd]);
        if($path !== null){
            unset($this->pathWd[$path]);
        }
    }

    public function start(){

        \Swoole\Event::add($this->fd,[$this,'eventHandler']);
    }

    public function eventHandler($fp){
        $events = inotify_read($fp);
        // 多个进程都可以获取到信息，但是只有一个进程执行回调函数，导致如果多进程，其他进程会抛出错误
        if(empty($events)){
            return null;
        }
        foreach ($events as $event){
            if($event['mask'] == IN_IGNORED){
                continue;
            }
            // 返回 haystack 字符串从 needle 第一次出现的位置开始到 haystack 结尾的字符串。区分大小写
            $fileType = strchr($event['name'],'.');
            if(!isset($this->fileTypes[$fileType])){
                continue;
            }
            if($this->doing){
                continue;
            }
            // 延迟更新
            \Swoole\Timer::after(100,function () use ($event){
                call_user_func_array($this->watchHandler,[$event]);
                // 标记当前已重启接收
                $this->doing = false;
            });
            $this->doing = true;
            break;

        }
    }

    public function stop(){
        \Swoole\Event::del($this->fd);
        fclose($this->fd);
    }

    public function getWatchedFileCount(){

        return count($this->wdPath);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->stop();
    }
}