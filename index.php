<?php

require 'vendor/autoload.php';

// Reading .env file
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

if(!isset($_ENV['THUMCNO_PATH'])) {
    $_ENV['THUMCNO_PATH'] = __DIR__;
}

foreach(glob('./src/Tacnoman/*.php') as $filename) {
    require $filename;
}

$thumcno = new \Tacnoman\Thumcno();
$thumcno->start();
