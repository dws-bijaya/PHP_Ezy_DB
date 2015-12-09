<?php
/**
*  PHP Ezy DB Example Config File
*  Version: 1.0.1
*  FileName: system.cron.config.php
*  Author: Bijaya Kumar
*  Email: it.bijaya@gmail.com
*  Mobile: +91 9911033016
*
*  Example file for Config
*
**/
return array(
     'DB' => array(
        'TESTCONFIG1' => array(
            'use' => 'memcache',
            'host' => '192.168.1.102',
            'port' => 22122,
            'user' => '',
            'charset'=>'utf8',
            'timeout'=> 1,
            'password' => '123456',
            'database' => 'backlinks',
            'prefix' => 'bl_'
        ),
        'TESTCONFIG2' => array(
            'use' => 'pdo_mysqli',
            'host' => 'localhost',
            'port' => 3307,
            'user' => 'postgres',
            'charset'=>'utf8',
            'password' => '123456',
            'database' => 'backlinks',
            'prefix' => 'bl_'
        ),
        'TESTCONFIG3' => array(
            'use' => 'pdo_mysqli',
            'host' => 'localhost',
            'port' => 3308,
            'user' => 'root',
            'password' => '',
            'database' => 'backlinks2',
            'prefix' => 'bl_'
        )
    )
);
?>