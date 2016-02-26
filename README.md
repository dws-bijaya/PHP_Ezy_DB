  ___ _  _ ___   ___            ___                    
 | _ \ || | _ \ | __|____  _   / _ \ _  _ ___ _ _ _  _ 
 |  _/ __ |  _/ | _||_ / || | | (_) | || / -_) '_| || |
 |_| |_||_|_|   |___/__|\_, |  \__\_\\_,_\___|_|  \_, |
                        |__/                      |__/  1.0.0

*****************************************
# `PHP_Ezy_Query 1.0.0`
Version: 1.0.0
Author: Bijaya Kumar
Email: it.bijaya@gmail.com
Mobile: +91 9911033016

A simple, Eazy and light weightPHP Database wrapper class includes mysql, mongo and memcache and best for runable server like  cron job

Feature:
	*. support mysqli, pdo, mongo drives
	*. auto reconnection before query
	*. support multi query / store procedure
	*. support IP or sock base connection
	*. support escape 

Eaxmple:
	# Use it as  global 
	global $_CONFIGS;

	# Load config 
	$_CONFIGS = require './.configs' . DS . 'system.cron.config.php';
	
	# Load PHP Ezy DB class file
	require_once( './systems/tgr_db.class.php');
	
	# Initialise a connection
 	$TCDB = TGR_DB::init("TESTCONFIG4");

 	# Query 
 	$rows = TGR_DB::query($TCDB, $qry);

 	# Close a active connection
 	TGR_DB::close($TCDB);