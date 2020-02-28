<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2020/2/4
 * Time: 10:54 下午
 */
// 初始化inotify
$fd = inotify_init();

// inotify_add_watch : 针对于某一个文件进行监听
// string $pathname 监听文件的地址
// int $mask 监听事件 IN_MODIFY
$watchDescriptor = inotify_add_watch($fd,__DIR__ . '/r.txt',IN_MODIFY);

echo 1;
\Swoole\Event::add($fd,function ($fd){
    $events = inotify_read($fd);
    var_dump($events);
});
