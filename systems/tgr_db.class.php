<?php
/*########################[General Info]################################
# @Script file : tgr_db.clas.php
# @Version     : 1.0.0
# @Author      : Bijaya Kumar
# @Email       : it.bijaya@gmail.com
# @Description : Wrapper for PHP Database Driver
##########################[General Info]################################
*/
/*
*  @Class TGR_DB
*/
class TGR_DB {
	/*
	* @description check the configuration & create a new connection resources or objects
	* @method : check
	* @param string Config name
	* @return string|array Returns string if any error occured or array[ $conn, $dns , $config, '' ] 
	* @hint 
	*/
 	private static function check ($config) {
	 	global $_CONFIGS;		
		if ( !isset($_CONFIGS['DB'][$config]) ) 
			return "9001:No configuration found";
		$init_cmds = array_filter(explode(",", isset($_CONFIGS['DB'][$config]['init_commnad'])?$_CONFIGS['DB'][$config]['init_commnad']: '')); 
		$non_pdo= array('mysqli', 'mysql', 'mongo', 'memcache');
		$dns = str_replace("pdo_mysqli", "mysqli", $_CONFIGS['DB'][$config]['use']);
		
		//
		if ( ! in_array($dns, array('mysql', 'mysqli', 'pdo_mysql', 'mongo', 'memcache', 'pdo_pgsql') )  ) 
			return "9002:Driver not found";
		
		// 
		if( $dns === 'mysql' ) {
			if( !function_exists('mysql_connect')  ) 
				return "9005:mysql extension not found.";
			$mysqllink = null;
			$server = is_int($_CONFIGS['DB'][$config]['port']) ? "{$_CONFIGS['DB'][$config]['host']}:{$_CONFIGS['DB'][$config]['port']}" : ":{$_CONFIGS['DB'][$config]['port']}";
			$username = $_CONFIGS['DB'][$config]['user'];
			$password = $_CONFIGS['DB'][$config]['password'];
			$newlink = isset($_CONFIGS['DB'][$config]['newlink']) && $_CONFIGS['DB'][$config]['newlink'] == true;
			$persistent = isset($_CONFIGS['DB'][$config]['persistent']) && $_CONFIGS['DB'][$config]['persistent'] == true;
			$client_flags = isset($_CONFIGS['DB'][$config]['driver_options']) ?$_CONFIGS['DB'][$config]['driver_options'] : 0;
			$charset = isset($_CONFIGS['DB'][$config]['charset']) ?$_CONFIGS['DB'][$config]['charset'] : false;
			$_CONFIGS['DB'][$config]['_default_charset'] = isset($_CONFIGS['DB'][$config]['_default_charset']) ? $_CONFIGS['DB'][$config]['_default_charset'] : '';
			if (!$persistent) {
				$mysqllink = @mysql_connect($server, $username, $password, $newlink, $client_flags);			
			} else {
				$mysqllink = mysql_pconnect($server, $username, $password, $client_flags);
			}
			if (!$mysqllink ) {
				return  mysql_errno() .":" . mysql_error() ;
			}

			$database = $_CONFIGS['DB'][$config]['database'];
			if ( !mysql_select_db($database, $mysqllink)) {
				return  mysql_errno($mysqllink) .":" . mysql_error($mysqllink) ;
			}
			if ($persistent && $charset) {
				if ( !isset($_CONFIGS['DB'][$config]['_default_charset']) ) {
					$ret = mysql_query("SHOW VARIABLES LIKE 'character_set_results'", $mysqllink);
					$_charset  = @mysql_fetch_assoc($ret);
					$_CONFIGS['DB'][$config]['_default_charset'] = isset($_charset['Value'])?$_charset['Value']:'';
				}
			}
			if( $charset && !@mysql_query("SET SESSION character_set_results = '{$charset}'", $mysqllink))
				 return @mysql_errno($mysqllink) .":" . @mysql_error($mysqllink) ;

			return array ( $mysqllink, $dns , $config, '', 'charset' => $charset  );
		}



		if( $dns === 'mysqli' ) {
			//
			if ( !class_exists('MySQLi') ) return "9003:MySQLi class not found.";
			$MySQLi = mysqli_init() ;
			if (!$MySQLi ) {
				return  "9004:Unable to Initialise MySQLi.";
			}						
			@mysqli_options($MySQLi, MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 1');
			@mysqli_options($MySQLi, MYSQLI_OPT_CONNECT_TIMEOUT, 60  ); 
			if ( isset($_CONFIGS['DB'][$config]['charset']) )	
				@mysqli_set_charset($MySQLi, $_CONFIGS['DB'][$config]['charset']);
			@mysqli_real_connect($MySQLi, $_CONFIGS['DB'][$config]['host'], $_CONFIGS['DB'][$config]['user'] , $_CONFIGS['DB'][$config]['password'], $_CONFIGS['DB'][$config]['database'] , $_CONFIGS['DB'][$config]['port'] , null , MYSQLI_CLIENT_COMPRESS | MYSQLI_CLIENT_FOUND_ROWS  );
			if ( mysqli_connect_errno($MySQLi) ) {
				return mysqli_connect_errno($MySQLi) . ':' . mysqli_connect_error($MySQLi);
			}
			return array ( $MySQLi, $dns, $config, '' );
		} 

		if( $dns === 'mongo' ) {
			if( !class_exists('MongoClient')  ) 
				return "9005:Mongo class not found.";
			$user_pass = ( isset($_CONFIGS['DB'][$config]['user']) ? $_CONFIGS['DB'][$config]['user'] : '');
			$user_pass .=":" . ( isset($_CONFIGS['DB'][$config]['pass']) ? $_CONFIGS['DB'][$config]['pass'] : ''). "@";
			if ( !isset($_CONFIGS['DB'][$config]['user']) || empty($_CONFIGS['DB'][$config]['user']) )
				$user_pass ='';
			$driver = "mongodb://$user_pass". $_CONFIGS['DB'][$config]['host'] . ":" .$_CONFIGS['DB'][$config]['port'] . ($_CONFIGS['DB'][$config]['database'] === '' ? '' : "/{$_CONFIGS['DB'][$config]['database']}")  ;
			if (stripos($_CONFIGS['DB'][$config]['host'], 'mongodb://') === 0 )
				$driver = $_CONFIGS['DB'][$config]['host'];
			$m_options = isset($_CONFIGS['DB'][$config]['options']) ?$_CONFIGS['DB'][$config]['options'] : array();
			$driver_options = isset($_CONFIGS['DB'][$config]['driver_options']) ?$_CONFIGS['DB'][$config]['driver_options'] : array();
			try{
				$conn =new MongoClient($driver, $m_options, $driver_options);
			} catch( Exception $e ) {
				return  "9006:Could not connect to host.";
			}
			return array ( $conn, $dns , $config, '' );
		}		


		if( $dns === 'memcache' ) {
			if( !function_exists('memcache_connect')  ) 
				return "9007:memcache class not found.";
			$conn = null;
			$timeout = 1 ;
			$timeout = isset($_CONFIGS['DB'][$config]['timeout']) ? (int) $_CONFIGS['DB'][$config]['timeout'] : $timeout;
			if ( isset($_CONFIGS['DB'][$config]['persistent']) && $_CONFIGS['DB'][$config]['persistent'] && function_exists('memcache_pconnect') ) {
				$conn = @memcache_pconnect ($_CONFIGS['DB'][$config]['host'], $_CONFIGS['DB'][$config]['port'], $timeout);	
			} else {
				$conn = @memcache_pconnect ($_CONFIGS['DB'][$config]['host'], $_CONFIGS['DB'][$config]['port'], $timeout);	
			}
			if ( $conn == null or $conn == false )
				return  "9008:Could not connect to host.";
			return array ( $conn, $dns , $config, '' );
		}

		// Else PDO
		$dns=explode("_", $dns);
		if ($dns[0] !== 'pdo') 
		 	return "9009:invalid pdo driver";

		if( !class_exists('PDO')  ) {
			return "9010:pdo class not found";
		}
		
		if ( !in_array($dns[1], PDO::getAvailableDrivers()) ) 
			return "9010:pdo driver not found";

		$options = array();//PDO::ATTR_ERRMODE=>PDO::ERRMODE_SILENT);
		$PDO_DSN="{$dns[1]}:dbname={$_CONFIGS['DB'][$config]['database']};host={$_CONFIGS['DB'][$config]['host']};port={$_CONFIGS['DB'][$config]['port']}";
		try{
			$conn =new PDO($PDO_DSN, $_CONFIGS['DB'][$config]['user'], $_CONFIGS['DB'][$config]['password'], $options);
		}catch(PDOException $e){
			return "{$e->getCode()}:{$e->getMessage()}";
		}
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		foreach($init_cmds as $key=>$value) {
			try {
			$keyval= explode("=", $value);
			$key   = preg_replace("/[^a-z:_0-9]/i", "", $keyval[0]);
			$val   = preg_replace("/[^a-z_:0-9]/i", "", $keyval[1]);
			eval("\$key=$key;"); eval("\$val=$val;");
			@$conn->setAttribute($key,$val);
			}
			catch(PDOException $e){}
		}
		return array ( $conn, join("_", $dns) , $config, '' );
 	}
	/*
	* @description initilisation a fresh connection to database server
	* @method : init
	* @param string Config name
	* @return string|array Returns string if any error occured or array[ $conn, $dns , $config, '' ] 
	*/
	static function init ($config) {
		global $_CONFIGS; 
		$LINK =  self::check($config) ;
		// Upadate link
		$_CONFIGS['DB']['LINK'] = is_string($LINK) ? null : $LINK[0] ;
		return $LINK;			
 	}

	/*
	* @description ping/reconnect connection to database server
	* @method : ping
	* @param array $conn connection array
	* @return bool|array Returns bool if any error/success  occured otherwise retrn array as data set
	*/
	public static function ping (&$conn) {
		if ( !is_array($conn) )
			return false;
		if ( $conn[1] == 'memcache' ) {
			return  @memcache_get_version($conn[0]);
		}
		
		if ($conn[1] == "mysql" ) {
			if ( function_exists('mysql_ping') && mysql_ping($conn[0]) ) {
				return true;
			}
			// 
			if ( !@mysql_query("SELECT 1", $conn[0]) ) {
				//try to reconnect for pdo
       			$conn2 = self::init($conn[2]);
       			var_dump($conn2);
       			if (is_string($conn2) ) { 
					$conn[0] = $conn2; return false;
				}
				$conn = $conn2;	return true;							
			}	
			return true;
		} 


		if ( current( explode("_", $conn[1])) == 'pdo'  ) {
			try {
				if ( is_object($conn[0]) && $conn[0]->query('select 1') !== false ) 
					return true;
    		} catch (PDOException $e) {}

       		//try to reconnect for pdo
       		$conn2 = self::init($conn[2]);
       		if (is_string($conn2) ) { // has error
				$conn[0] = $conn2; // error stored on 0 index
				return false;
			}
			else {
				$conn = $conn2;
				return true;							
			}
		}
		else if ( $conn[1] == 'mongo' ) {
			try{
				return $conn[0]->listDBs();
			}catch(Exception $e ){ $conn[4] = array($e->getCode(), $e->getMessage() ) ;  return false;}
		}
		

		// for mysqli ...
		if ( isset($conn[0]->sqlstate) && $conn[0]->sqlstate === '00000' ) {
				return true;
		}
			
		// In Any case closed the connection, make fresh conection
		is_object($conn[0]) ? self::close($conn) : $conn[0]=null;
	
		//
		$conn2 = self::init($conn[2]) ;
		if (is_string($conn2) ) { // has error
			$conn[0] = $conn2;
			return false;
		}
		else {
			$conn = $conn2;
			if ( isset($conn2[0]) && is_object($conn2[0]) && isset($conn[0]->sqlstate) && $conn[0]->sqlstate === '00000' ) {
				return true;
			}			
		}
		return false;	
 	}

	/*
	*
	* @description escape a string
	* @method escape
	* @param mixed $conn
	* @param string $escape
	* @return string 
	*/
 	static function escape(&$conn, $escape) {
 		if ( $conn[1] == 'memcache'  or $conn[1] == 'mongo'  ) {
		return addslashes($escape);
	}
	if ( !isset($conn[0]) or  !is_object($conn[0])  )
		return FALSE; 
	if ( current( explode("_", $conn[1])) == 'pdo' ) 
		return $conn[0]->quote($escape);
	return mysqli_real_escape_string($conn[0], $escape);
 	}
 	/*
	* @description execute a query 
	* @method : query
	* @param array $conn connection array
	* @param string $query sql query
	* @param int $mode result fetch mode
	* @return bool|array Returns bool if any error/success  occured otherwise retrn array as data set
	*/
 	static function query (&$conn, $query = null, $mode = MYSQLI_ASSOC ) {
		global $_CONFIGS;
		$conn['tooks'] = 0;
		if ( !is_array($conn) || !$query )
			return false;

		if ( !is_array($query) ) {
			$query = str_replace( "@@_", (isset($_CONFIGS['DB'][$conn[2]]['prefix']) ? $_CONFIGS['DB'][$conn[2]]['prefix'] : ''), $query ) ; 
			$query = str_replace( "\\\\@\\\\@_", "@@_", $query ) ; 
		}				
		
		// Ping & try to reconnect
		if ( ! self::ping($conn) ) {
	    	return false;
		}

		// Store last query
		$conn[3]  =  $query ;
		$conn['tooks'] = -microtime(true);
		if ( $conn[1] == 'mongo' ) {
			$_db = isset($query['database']) ? $query['database']  : false;
			if ( !$_db) 
		 	{
		 		$conn['tooks'] +=microtime(true);
		 		$conn[4] = array("4001", "No DB selected.");
				return false;
			}

			$_tbl = isset($query['table']) ? $query['table']  : false;
			if ( !$_tbl) 
		 	{
		 		$conn['tooks'] +=microtime(true);
		 		$conn[4] = array("4002", "No Table Selected.");
				return false;
			}

			$_fld = isset($query['fields']) ? $query['fields'] : array();
			$_fld = !$_fld ? array() : $_fld ;
			
			$_srt = isset($query['sort']) ? $query['sort'] : array();
			$_srt = !$_srt ? array() : $_srt ;
			

			$_tbl = str_replace( "@@_", (isset($_CONFIGS['DB'][$conn[2]]['prefix']) ? $_CONFIGS['DB'][$conn[2]]['prefix'] : ''), $_tbl ) ; 
			$_tbl = str_replace( "\\\\@\\\\@_", "@@_", $_tbl ) ; 

			$_whr = isset($query['where']) ? $query['where']  : array();
			$_whr = !$_whr ? array() : $_whr ;

			$_lmt = isset($query['limit']) ? abs((int) $query['limit'])  : false;
			$_skp = isset($query['skip']) ? abs((int) $query['skip'] ) : false;
			
			$_lmt = $_lmt ? $_lmt : false;
			$_skp = $_skp ? $_skp : false;
			$_cmd = isset($query['command']) ? $query['command'] : 'find';
			try
			{
				$_db = $conn[0]->selectDB($_db);
				$_cln = new MongoCollection($_db, $_tbl);
				$_csr = $_cln->{$_cmd}($_whr, $_fld);
				if ( $_lmt ) 
					$_csr->limit($_lmt);
				if ( $_skp ) 
					$_csr->skip($_skp);
				if ( $_srt ) 
					$_csr->skip($_srt);

				$_csr->hasNext();
				$conn['tooks'] +=microtime(true);

				if ( !$mode ) {
					return $_csr;
				}
				//
				$arr = array();	
				while ( ( $_row = $_csr->getNext() ) ) {
					$arr[] =  $_row;
				}

				return $arr;


			}catch(Exception $e ){$conn[4] = array($e->getCode(), $e->getMessage() ) ;  return false;}
		}else if ( $conn[1]  == 'mysql') {
			$result = @mysql_query ($query, $conn[0]) ;
			$conn['tooks'] +=microtime(true);
			$conn['tooks']  = sprintf("%.2f Sec(s)", $conn['tooks']);
			if ( $result === FALSE or $result === NULL ) 
				return false ;
			if ( is_null($mode) ) 
				return $result;
			if ( TRUE === $result ) 
				return true;
			$arr = array() ;
			while( ( $rec= @mysql_fetch_array( $result, $mode) ) ) 
				$arr[] = $rec ;
			@mysqli_free_result( $result );
			return $arr;
		}else if ( current( explode("_", $conn[1])) == 'pdo' ) {
			if (!is_object($conn[0]) ) {
				$conn['tooks'] +=microtime(true);
				return false;
			}

			try{
				$result = @$conn[0]->query($query);
			}catch(PDOException $e)
        	{
        		$conn[4] = array($e->getCode(), $conn[0]->getMessage());
				return false;
        	}
			$conn['tooks'] += microtime(true);
			$conn['tooks']  = sprintf("%.2f Sec(s)", $conn['tooks']);
			
			// cleared
			$conn[4] = array(0, "");
			if ( $result === false  or !is_object($result)) {
				$conn[4] = array($conn[0]->errorCode(), $conn[0]->errorInfo());
				return false;
			}

			// 
			if ( $mode  === null ) 
				return $result;

			//
			if ( $result === true ) 
				return true;
			
			//
			$arr = array();	
			if (!$result->rowCount()){
				return $arr;
			}			
		
			while( ( $rec= @$result->fetch()) ) 
				$arr[] = $rec ;	
		
			$result->closeCursor();	
			return $arr;
		} 
		/* Memcache */
		else if (  $conn[1] == 'memcache' ) {
	    	$fun = array_shift( $query );
	    	$chk_fun =  "memcache_$fun";
	    	if ( !function_exists($chk_fun)) {
	    		$conn[4] = array ( 1, "function $chk_fun does not exist.");
	    	  	return false;
	    	}

	    	$db = $_CONFIGS['DB'][$conn[2]]['database'];
	    	$db = str_replace( "@@_", $_CONFIGS['DB'][$conn[2]]['prefix'], $db ) ; 
	    	if ( in_array($fun , array('add','decrement', 'delete','get','increment','replace','set') ) ) {
				$query[0] = "$db" . "." . $query[0];
			}
			array_unshift($query, $conn[0]);
			try {
				$res = call_user_func_array($chk_fun, $query);
				return $res;
			} catch(Exception $e ){  $conn[4] = array($e->getCode(), $e->getMessage() ) ;  return false;} 
			
			// unreachable code 
			return false;
		}
		/* default mysqli */
		else {
			$result = @mysqli_query ($conn[0],$query) ;
			$conn['tooks'] +=microtime(true);
			$conn['tooks']  = sprintf("%.2f Sec(s)", $conn['tooks']);
			if ( $result === FALSE or $result === NULL ) 
				return false ;
			if ( is_null($mode) ) 
				return $result;
			if ( TRUE === $result ) 
				return true;
			$arr = array() ;
			while( ( $rec= @mysqli_fetch_array( $result, $mode) ) ) 
				$arr[] = $rec ;
			@mysqli_free_result( $result );
			return $arr;
		}
		
 	}
	/*
	* @description execute multi query 
	* @method : multi_query
	* @param array $conn connection array
	* @param string $escape string to be escape
	* @return bool|string Returns bool if any error occured otherwise return escaped string 
	*/
	static function multi_query (&$conn, $query = null, $mode =MYSQLI_ASSOC  ) {
		global $_CONFIGS;
		$conn['tooks'] = 0;

		if ( $conn[1] == 'mongo' ) {
			$conn[4] = array( 4000 , "multiple queries are not supported.");
			return false;
		}

		if ( !is_array($conn) || !$query )
			return false;			
		$query = str_replace( "@@_", (isset($_CONFIGS['DB'][$conn[2]]['prefix']) ? $_CONFIGS['DB'][$conn[2]]['prefix'] : ''), $query ) ; 
		$query = str_replace( "\\\\@\\\\@_", "@@_", $query ) ; 
		if ( ! self::ping ($conn) ) {
	    	return false;
		}
		
		// Store the last query
		$conn[3]  =  $query ;
		$conn['tooks'] = -microtime(true);
		if ( $conn[1] == "mysql" ) {
			$mqry_results = array() ;
			$mqry = explode("; ", trim($query, "; "));
			$tooks = array();
			foreach ($mqry as $key => $qry ) {
				$qry = str_replace("\\\\; ", "; ", $qry);
				$mqry_results[$key]	= array();
				$tooks[$key] = -microtime(true);
				$result = @mysql_query($qry, $conn[0]) ;
				$tooks[$key] +=microtime(true);	
				if ( $result === FALSE or $result === NULL )
					$mqry_results[$key] = $result;
				else if (TRUE === $result) {
					$mqry_results[$key] = true;					
				} else {
					if ( $mode == null )
						$mode  =  MYSQL_BOTH;
					$arr = array() ;
					while( ( $rec= @mysql_fetch_array( $result, $mode) ) ) 
						$arr[] = $rec ;
					$mqry_results[$key]=$arr;
					mysql_free_result( $result );
				}

			}
			$conn['tooks'] = array_sum($tooks);
			$conn['tooks'] = sprintf("%.2f Sec(s)", $conn['tooks']);
			return $mqry_results;
		} else if ( current( explode("_", $conn[1])) == 'pdo' ) {
			if (!is_object($conn[0]) ){
				$conn['tooks'] +=microtime(true);
				return false;
			}

			$result = @$conn[0]->query($query);
			$conn['tooks'] +=microtime(true);
			$conn['tooks']  = sprintf("%.2f Sec(s)", $conn['tooks']);
			// cleared
			$conn[4] = array (0, "");
			if ( $result === false  or !is_object($result)) {
				$conn[4] = array($conn[0]->errorCode(), $conn[0]->errorInfo());
				return false;
			}

			if ( $result === true ) 
				return true;
			$arr = array();
			$i = 1;
			do {
		    	$rowset = $result->fetchAll();
		    	if ($rowset) {
		        	$arr[$i] =$rowset;
		    	}
		    	$i++;
			} while (@$result->nextRowset());	
			return $arr;
		}


		// Default mysqli
		$result = @mysqli_multi_query ($conn[0],$query ) ;
		$conn['tooks'] +=microtime(true);
		$conn['tooks']  = sprintf("%.2f Sec(s)", $conn['tooks']);
		if ( $result === FALSE or $result === NULL ) 
				return false ;
		if ( is_null($mode) ) 
			return $result;
		
		$arr = array() ;
		$index = 0 ;
		do {
			/* store first result set */
			if ( $result = @mysqli_store_result( $conn[0] )  ) {
				if (  $result->num_rows ) {
					while ($rec = @mysqli_fetch_array($result, $mode )  ) {
						$arr[$index][] = $rec ;
					}
					@mysqli_free_result($result);
				} else {
					$arr[$index] = array ();
				}
			} else {
				$arr[$index][] =  @mysqli_errno( $conn[0]  ) ? false : true ;
			}
			$index++;
        	if( mysqli_more_results($conn[0]) == false )
            	break;
		} while (  @mysqli_next_result($conn[0])  ) ;	
	
		if (  @mysqli_errno( $conn[0]  )  ) {
			$arr[$index] =  false ;
		}
		return $arr;	
	}
	
	/*
	* @description get alst error from exection of query 
	* @method  error
	* @param array $conn connection array
	* @param bool $no boolean yes / no
	* @return bool|string|int Returns bool if any error occured otherwise return int if $no is yes otherwise return error no as string
	*/
	static function error ( &$conn, $no = false ) {
		if ( !isset($conn[0])  or  !( is_resource($conn[0]) || is_object($conn[0]) or is_string($conn[0]) )  ) 
			return false;
		
		// initial error		
		if (  ( $err = is_string($conn) ? $conn :  ( is_string($conn[0]) ? $conn[0] : false) ) !==FALSE ) {
			preg_match('`(\d+):(.*)`im',$err, $err );
			return $no ? (int) $err[1] : $err[2];
		}

		// mongo 
		if ( $conn[1] == 'mongo' ) {
			return isset($conn[4]) ?  ( $no ? $conn[4][0] : $conn[4][1] ) : false; 
		} 
		/* pdo  */
		else if ( stripos($conn[1],'pdo') !== FALSE )  {
			return isset($conn[4]) ?  ( $no ? $conn[4][0] : $conn[4][1][2] ) : false; 
		}
		/* mysql */
		else if ( $conn[1] == 'mysql' ) 
			return   $no ? @mysql_errno($conn[0]) :  @mysql_error($conn[0]) ;
		/* mysqli */
		else
			return   $no ? @mysqli_errno($conn[0]) :  @mysqli_error($conn[0]) ;		
	}

	/*
	* @description closed a connection 
	* @method  close
	* @param array $conn connection array
	* @return bool Returns true on succes otherwise false
	*/
	static function close (&$conn) {
		if ( !is_array($conn) )
			return false;
		if ( $conn[1] == 'mongo' ){
			try{
				return $conn[0]->close();
			}catch(Exception $e ){ $conn[4] = array($e->getCode(), $e->getMessage() ) ;  return false;}
		}
		else if ( $conn[1] == 'memcache' )
			return @memcache_close($conn[0]);
		/* pdo  */
		else if ( stripos($conn[1], "pdo_") === 0 ) {
			return is_object($conn[0]) && @$conn[0]->close();
		}
		else 
			return @mysqli_close($conn[0]);
	}
}
?>