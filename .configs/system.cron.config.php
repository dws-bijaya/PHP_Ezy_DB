<?php
/**
*  @File: system.cron.config.php
*  @Description PHP Ezy DB Example Config File
*  @Version: 1.0.0
*  @Autore: Bijaya Kumar
*  @Email:  it.bijaya@gmail.com
*  @Mobile: +91 9911033016
*  @Country: India
**/
return array(
     'DB' => array(
        'TESTCONFIG1' => array(
            'use' => 'mysqli',
            'host' => 'localhost',
            'port' => 3306,
            'user' => 'root',
            'charset'=>'utf8',
            'timeout'=> 1,
            'password' => '',
            'database' => 'test',
            'prefix' => 'tgr_'
        ),
        'TESTCONFIG2' => array(
            'use' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            /*
                // sock file 
                'host' => '',
                'port' => '/tmp/mysql.sock',
            */
            'newlink' => false, /* true for new connection | false for reuse connection */
            'persistent' => true, /* true | false */
            'driver_options' => 0, /* parameter can be a combination of the following constants: 128 (enable LOAD DATA LOCAL handling), MYSQL_CLIENT_SSL, MYSQL_CLIENT_COMPRESS, MYSQL_CLIENT_IGNORE_SPACE or MYSQL_CLIENT_INTERACTIVE */
            'user' => 'root',
            'charset'=>'utf8',
            'password' => '',
            'database' => 'test',
            'prefix' => 'tgr_'
        ),
        'TESTCONFIG3' => array(
            'use' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => 3306,
            'user' => 'root',
            'password' => '',
            'database' => 'test',
            'prefix' => 'tgr_'
        ),
        'TESTCONFIG4' => array(
            'use' => 'mongo',
            'host' => 'localhost',
            /*  
                # For sock file
                'host' => 'mongodb://mongod.sock',
            */
            'port' => 27017,
            'user' => '',
            'password' => '',
            'database' => 'test',
            'prefix' => 'tgr_'
        )
    )
);
?>