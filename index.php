<?php
define('PATH',__DIR__);

foreach(glob('./src/*.php') as $filename) {
    require $filename;
}

$thumcno = new Thumcno();
require './thumcno-timthumb.php';
