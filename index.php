<?php
define('THUMCNO_PATH',__DIR__);

foreach(glob('./src/Tacnoman/*.php') as $filename) {
    require $filename;
}

$thumcno = new Tacnoman\Thumcno();
$thumcno->start();
