<?php
/*######################################################################
	script file : db.lib
	version     : 1.0.0
	description : functions	for mysqli
	###################################################################### 
*/
	/*
	* check the configuration & create a connection resources or objects
	* function : db_check
	* @params string Config name
	* @return string|array Returns string if any error occured or array[ $conn, $dns , $config, '' ] 
	*/
	function db_check ($config) {
		global $_CONFIGS;		
		if ( !isset($_CONFIGS['DB'][$config]) ) 
			return "9001:No configuration found";
		$init_cmds = array_filter(explode(",", isset($_CONFIGS['DB'][$config]['init_commnad'])?$_CONFIGS['DB'][$config]['init_commnad']: '')); 
		$non_pdo= array('mysqli', 'mongo', 'memcache');
		$dns = str_replace("pdo_mysqli", "mysqli", $_CONFIGS['DB'][$config]['use']);
		//
		if ( ! in_array($dns, array('mysqli', 'mongo', 'memcache', 'pdo_pgsql') )  ) 
			return "9002:Driver not found";
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

		// will depreicated next version
		if( $dns === 'mongo-sock' ) {
			if( !class_exists('MongoClient')  ) 
				return "9005:Mongo class not found.";
			try{
				$conn =new MongoClient("mongodb://".$_CONFIGS['DB'][$config]['host'] ."/".$_CONFIGS['DB'][$config]['database']);
			}catch( Exception $e ) {
				return  "9006:Could not connect to host.";
			}			
			return array( $conn, $dns , $config, '' );
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
		 	return "9009:pdo driver not found";

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
		#PRINT_R($init_cmds);
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
		// Unreachable code ..	
	}

	/*
	* initilisation a fresh connection to database server
	* function : db_init
	* @params string Config name
	* @return string|array Returns string if any error occured or array[ $conn, $dns , $config, '' ] 
	*/
	function db_init ($config) {
		global $_CONFIGS; 
		$LINK =  db_check($config) ;
		// Upadate link
		$_CONFIGS['DB']['LINK'] = is_string($LINK) ? null : $LINK[0] ;
		return $LINK;			
	}
	
	/*
	* ping/reconnect connection to database server
	* function : db_ping
	* @params array $conn connection array
	* @return bool|array Returns bool if any error/success  occured otherwise retrn array as data set
	*/
	function db_ping (&$conn) {
		if ( !is_array($conn) )
			return false;
		
		// will check later 
		if ( $conn[1] == 'memcache' ) {
			return  @memcache_get_version($conn[0]);
		}
		
		if ( current( explode("_", $conn[1])) == 'pdo'  ) {
			try {
				if ( is_object($conn[0]) && $conn[0]->query('select 1') !== false ) 
						return true;
        	} catch (PDOException $e) {}

           	//try to reconnect for pdo
           	$conn2 = db_init($conn[2]);
           	if (is_string($conn2) ) { // has error
				$conn[0] = $conn2; // error stored on 0 index
				return false;
			}
			else {
				$conn = $conn2;
				return true;							
			}
    	}


		if ( $conn[1] == 'mongo' ) {
			try{
				return $conn[0]->listDBs();
			}catch(Exception $e ){ $conn[4] = array($e->getCode(), $e->getMessage() ) ;  return false;}
		}

		// for mysqli ...
		if ( isset($conn[0]->sqlstate) && $conn[0]->sqlstate === '00000' ) {
			return true;
		}

		// Any other case closed the connection, make fesh conection
		is_object($conn[0]) ? db_close($conn) : $conn[0]=null;
		//
		$conn2 = db_init($conn[2]) ;
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
	* esacpe string 
	* function : db_escape
	* @params array $conn connection array
	* @params string $escape string to be escape
	* @return bool|string Returns bool if any error occured otherwise return escaped string 
	*/
	function db_escape(&$conn, $escape )
	{
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
	* execute a query 
	* function : db_query
	* @params array $conn connection array
	* @params string $query sql query
	* @params int $mode result fetch mode
	* @return bool|array Returns bool if any error/success  occured otherwise retrn array as data set
	*/
	function db_query (&$conn, $query = null, $mode = MYSQLI_ASSOC ) {
		global $_CONFIGS;
		
		if ( !is_array($conn) )
			return FALSE;			
		
		if ( $query === NULL )  
			return $conn[3]; // return last executed query

		$query = str_replace( "@@_", (isset($_CONFIGS['DB'][$conn[2]]['prefix']) ? $_CONFIGS['DB'][$conn[2]]['prefix'] : ''), $query ) ; 
		if ( ! db_ping ($conn) ) {
		    return false;
		}
		$conn[3]  =  $query ;
		if ( current( explode("_", $conn[1])) == 'pdo' ) {
			if (!is_object($conn[0]) )
				return false;

			$result = @$conn[0]->query($query);
			// cleared
			$conn[4] = array ( 0, "");
			if ( $result === false  or !is_object($result)) {
				$conn[4] = array($conn[0]->errorCode(), $conn[0]->errorInfo());
				return false;
			}
			if ( $result === true ) 
				return true;
			$arr = array();	
			if (!$result->rowCount()){
				return $arr;
			}			
			while( ( $rec= @$result->fetch()) ) 
				$arr[] = $rec ;	
			$result->closeCursor();	
			return $arr;
		}


	    if ( $conn[1] == 'memcache' ) {
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
			return false	;
	    }

		$result = @mysqli_query ($conn[0],$query) ;
		//var_dump($result);
		if ( $mode == null or $result == NULL ) 
			return $result;
		if ( $result === FALSE ) 
			return false;
		if ( $result === true ) 
			return true;
			
		$arr = array() ;
		while( ( $rec= @mysqli_fetch_array( $result, $mode) ) ) 
			$arr[] = $rec ;
		mysqli_free_result( $result );
		return $arr;	
	}
	
	/*
	* execute multi query 
	* function : db_multi_query
	* @params array $conn connection array
	* @params string $escape string to be escape
	* @return bool|string Returns bool if any error occured otherwise return escaped string 
	*/
	function db_multi_query (&$conn, $query = null, $mode =MYSQLI_ASSOC  ) {
		global $_CONFIGS;
		if ( !is_array($conn) )
			return FALSE;
		
		if ( $query == NULL ) 
			return $conn[3];
		$query = str_replace( "@@_", (isset($_CONFIGS['DB'][$conn[2]]['prefix']) ? $_CONFIGS['DB'][$conn[2]]['prefix'] : '') , $query ) ; 
		if ( ! db_ping ($conn) ) {
		    return false;
		}
		$conn[3]  =  $query ;
		if ( current( explode("_", $conn[1])) == 'pdo' ) {
			if (!is_object($conn[0]) )
				return false;

			$result = @$conn[0]->query($query);
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



		$result = @mysqli_multi_query ($conn[0],$query ) ;
		if ( $result === FALSE or $result === NULL ) 
			return false ;
			
		if ( $mode === NULL ) 
			return $result ;
		
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
	* get alst error from exection of query 
	* function : db_error
	* @params array $conn connection array
	* @params bool $no boolean yes / no
	* @return bool|string|int Returns bool if any error occured otherwise return int if $no is yes otherwise return error no as string
	*/
	function db_error ( &$conn, $no = false ) {
		if ( !isset($conn[0])  or  !( is_object($conn[0]) or is_string($conn[0]) )  ) 
			return false;
		// Has errors
		if (  ( $err = is_string($conn) ? $conn :  ( is_string($conn[0]) ? $conn[0] : false) ) !==FALSE ) {
			preg_match('`(\d+):(.*)`im',$err, $err );
			return $no ? (int) $err[1] : $err[2];
		}
		if ( $conn[1] == 'mongo' ) {
			return isset($conn[4]) ?  ( $no ? $conn[4][0] : $conn[4][1] ) : false; 
		}	
		return   $no ? @mysqli_errno($conn[0]) :  @mysqli_error($conn[0]) ;		
	}

	/*
	* closed connection 
	* function : db_close
	* @params array $conn connection array
	* @return bool Returns true for succes otherwise false
	*/
	function db_close (&$conn) {
		if ( !is_array($conn) )
			return FALSE;
		if ( $conn[1] == 'mongo' ){
			try{
				return $conn[0]->close();
			}catch(Exception $e ){ $conn[4] = array($e->getCode(), $e->getMessage() ) ;  return false;}
		}
		else if ( $conn[1] == 'memcache' )
			return @memcache_close($conn[0]);
		else
			return @mysqli_close($conn[0]);
		return false;
	}	
?>