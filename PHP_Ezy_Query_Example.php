<?php
/*
*  @File: PHP_Ezy_Query_Example.php
*  @Description: Example file
*  @Version: 1.0.0
*  @Autore: Bijaya Kumar
*  @Email:  it.bijaya@gmail.com
*  @Mobile: +91 9911033016
*  @Country: India
*/
?>
<?php
	$verbose = 0;
	global $_CONFIGS;

	// Load  Lib
	require_once ('./systems/tgr_db.class.php');

	// Load DB Confiuration file
	$_CONFIGS  = require_once ('./.configs/system.cron.config.php');

	# verbose
	$verbose ? var_dump($_CONFIGS) : null;

	$TEST = 4 ;
	#######################################################
	# This example uses of MySQLi Extension
	# Create a new connection useing config TESTCONFIG1
	########################################################
	$TEST == 1 ? Example1 (): '';


	#######################################################
	# This example uses of MySQL Extension
	# Create a new connection useing config TESTCONFIG2
	########################################################
	$TEST == 2 ? Example2 (): '';

	#######################################################
	# This example uses of pdo_mysql
	# Create a new connection useing config TESTCONFIG3
	########################################################
	$TEST == 3 ? Example3 (): '';

	#######################################################
	# This example uses of mongo
	# Create a new connection useing config TESTCONFIG4
	########################################################
	$TEST == 4 ? Example4 (): '';




function dump($lable, $data) {
	echo "<pre> <b><u>{$lable}::</b></u><br />";
 	print_r($data);
 	echo "</pre>";
}

function Example4 () {
 global $verbose;

 # Initialise a connection
 $TCDB = TGR_DB::init("TESTCONFIG4");

 # Error check 
 if (TGR_DB::error($TCDB, true)) {
    echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
	exit(1);
 }

 ###################################################
 # Simple Query
 $qry=array( 'database' => 'test', 
 			 'table'  => '@@_table1',
 			 'commnd' => 'find',
 			 'where' => array( 'id' => array( '$gt' => 1 ) ), 
 			 'fields' => array('id' => true), 
 			 'limit'=>0, 
 			 'skip'=>0,
 			 'sort' => array()
 			);
 $ExampleResult1 = TGR_DB::query($TCDB, $qry);
 // Error Check
 if ( TGR_DB::error($TCDB1, true) ) {
	echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB1, false ) .  "</h4>";
	exit(1);
 }
 $verbose? dump("Query Output", $ExampleResult1) : false;
 ###################################################
 
 ###################################################
 # Execute  Query and return the native result set 
 $qry=array( 'database' => 'test', 
 			 'table'    => '@@_table1',
 			 'commnd'   => 'find',
 			 'where'    => array( 'id' => array( '$gt' => 1 ) ), 
 			 'fields'   => array('id' => true), 
 			 'limit'    => 0, 
 			 'skip'     => 0,
 			 'sort'     => array()
 			);
 $ExampleResult4 = TGR_DB::query($TCDB, $qry, null);
 // Get Last Error No 
 if (  TGR_DB::error($TCDB, true) ) {
   echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
   exit(1);
 }
 $ExampleResult_4=array();
 while( ( $rec= @$ExampleResult4->getNext()) ) 
		$ExampleResult_4[]=$rec;
 $verbose?dump("Native Result Set Output::", $ExampleResult_4):false;
 ###################################################


 ###################################################
 # Close a active connection
 TGR_DB::close($TCDB);
 ###################################################
 exit(0);
}

function Example3 () {
 global $verbose;

 # Initialise a connection
 $TCDB = TGR_DB::init("TESTCONFIG3");
 
 # Error check 
 if (TGR_DB::error($TCDB, true)) {
    echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
	exit(1);
 }

 ###################################################
 # Execute Simple Query
 $qry = "SELECT * FROM @@_table1";
 $ExampleResult1 = TGR_DB::query($TCDB, $qry);
 // Error Check
 if ( TGR_DB::error($TCDB, true) ) {
	echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
	exit(1);
 }
 $verbose?dump("Query Output ", $ExampleResult1):false;
 ###################################################

 ###################################################
 # Execute Multi Query
 $qry2 = "SELECT * FROM @@_table1; SELECT * FROM @@_table2;";
 $ExampleResult2 = TGR_DB::multi_query($TCDB1, $qry2);
 // Get Last Error No 
 if (  TGR_DB::error($TCDB, true) ) {
   echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
   exit(1);
 }
 $verbose?dump("Multi Query Output", $ExampleResult2):false;
 ###################################################

 ###################################################
 # Execute Store Procedure
 $qry3 = "call mysp1();";
 $ExampleResult3 = TGR_DB::query($TCDB, $qry3);
 // Get Last Error No 
 if (  TGR_DB::error($TCDB, true) ) {
   echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
   exit(1);
 }
 $verbose?dump("Store Procedure Output", $ExampleResult3);
 ###################################################

 ###################################################
 # Execute  Query and return the native result set 
 $qry4 = "SELECT * FROM @@_table1";
 $ExampleResult4 = TGR_DB::query($TCDB, $qry4, null);
 // Get Last Error No 
 if (  TGR_DB::error($TCDB, true) ) {
   echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
   exit(1);
 }
 $ExampleResult_4=array();
 while( ( $rec= @$ExampleResult4->fetch()) ) 
		$ExampleResult_4[]=$rec;
 $ExampleResult4->closeCursor();
 $verbose?dump("Native Result Set Output", $ExampleResult_4);
 ###################################################

 ###################################################
 # Close a active connection
 TGR_DB::close($TCDB);
 ###################################################
 
}
function Example2 () {
 global $verbose;

 $TCDB = TGR_DB::init("TESTCONFIG2");

 // Get Last Error No 
 if (TGR_DB::error($TCDB, true)) {
 	echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB, true) . " <hr /><h4>" . TGR_DB::error($TCDB, false ) .  "</h4>";
	exit(1);
 }

// Execute Query
$qry = "SELECT * FROM @@_table1";
$ExampleResult1 = TGR_DB::query($TCDB1, $qry);
$verbose?dump("Simple Query Output", $ExampleResult_4);
$verbose ?  var_dump($ExampleResult1) :  null;

// Error Check
if ( TGR_DB::error($TCDB1, true) ) {
	echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB1, true) . " <hr /><h4>" . TGR_DB::error($TCDB1, false ) .  "</h4>";
	exit(1);
}

// Execute Multi Query
$qry2 = "SELECT * FROM @@_table1; SELECT * FROM @@_table2;";
$ExampleResult2 = TGR_DB::multi_query($TCDB1, $qry2);
$verbose ?  var_dump($ExampleResult2) :  null;

// Get Last Error No 
if (  TGR_DB::error($TCDB1, true) ) {
	echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB1, true) . " <hr /><h4>" . TGR_DB::error($TCDB1, false ) .  "</h4>";
	exit(1);
}

echo "<pre> <b><u>Output ::</b></u><br />";
print_r($ExampleResult2);

// Execute Store Procedure
$qry2 = "call mysp1();";
// Not implemented yet


// Execute  Query and return the native result set 
$qry2 = "SELECT * FROM @@_table1";
$ExampleResult3 = TGR_DB::query($TCDB1, $qry2, null);
$verbose ?  var_dump($ExampleResult3) :  null;
echo "<pre> <b><u>Output from result set::</b></u><br /> ";
while( ( $rec= @mysql_fetch_array( $ExampleResult3) ) ) 
print_r($rec );
@mysql_free_result( $ExampleResult3);


// Execute  Query using mysqli native object
$qry4 = "SELECT * FROM tgr_table1";
$ExampleResult4 = mysql_query ($qry4, $TCDB1[0]) ;
if (!$ExampleResult4) {
	print_r(mysql_error($TCDB1[0]));exit(1);
}
echo "<pre> <b><u>Output from result set::</b></u><br /> ";
while( ( $rec= @mysql_fetch_array($ExampleResult4) ) ) 
	print_r($rec );
@mysqli_free_result( $ExampleResult4);

###################################################
# Close a active connection
	TGR_DB::close($TCDB1);
	###################################################
exit(0);
	}

	function Example1 () {
		global $verbose;

		
		$TCDB1 = TGR_DB::init("TESTCONFIG1");

		// Get Last Error No 
		if (TGR_DB::error($TCDB1, true)) {
			echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB1, true) . " <hr /><h4>" . TGR_DB::error($TCDB1, false ) .  "</h4>";
			exit(1);
		}

		// Execute Query
		$qry = "SELECT * FROM @@_table1";
		$ExampleResult1 = TGR_DB::query($TCDB1, $qry);
		$verbose ?  var_dump($ExampleResult1) :  null;

		// Get Last Error No 
		if (  TGR_DB::error($TCDB1, true) ) {
			echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB1, true) . " <hr /><h4>" . TGR_DB::error($TCDB1, false ) .  "</h4>";
			exit(1);
		}

		// Execute Multi Query
		$qry2 = "SELECT * FROM @@_table1; SELECT * FROM @@_table2;";
		$ExampleResult2 = TGR_DB::multi_query($TCDB1, $qry2);
		$verbose ?  var_dump($ExampleResult2) :  null;

		// Get Last Error No 
		if (  TGR_DB::error($TCDB1, true) ) {
			echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB1, true) . " <hr /><h4>" . TGR_DB::error($TCDB1, false ) .  "</h4>";
			exit(1);
		}

		echo "<pre> <b><u>Output ::</b></u><br />";
		print_r($ExampleResult2);

		// Execute Store Procedure
		$qry2 = "call mysp1()";
		$ExampleResult4 = TGR_DB::multi_query($TCDB1, $qry2);
		$verbose ?  var_dump($ExampleResult4) :  null;
		// Get Last Error No 
		if (  TGR_DB::error($TCDB1, true) ) {
			echo "<br /><b>Err No </b>: " . TGR_DB::error($TCDB1, true) . " <hr /><h4>" . TGR_DB::error($TCDB1, false ) .  "</h4>";
			exit(1);
		}
		echo "<pre> <b><u>Out of Store Procedure Call ::</b></u><br />";
		print_r($ExampleResult4);


		// Execute  Query and return the native result set 
		$qry2 = "SELECT * FROM @@_table1";
		$ExampleResult3 = TGR_DB::query($TCDB1, $qry2, null);
		$verbose ?  var_dump($ExampleResult3) :  null;
		echo "<pre> <b><u>Output from result set::</b></u><br /> ";
		while( ( $rec= @mysqli_fetch_array( $ExampleResult3) ) ) 
		print_r($rec );
		@mysqli_free_result( $ExampleResult3);


		// Execute  Query using mysqli native object
		$qry4 = "SELECT * FROM tgr_table1";
		$ExampleResult4 = mysqli_query ($TCDB1[0], $qry4) ;
		if (!$ExampleResult4) {
			print_r($TCDB1[0]->errno);exit(1);
		}
		echo "<pre> <b><u>Output from result set::</b></u><br /> ";
		while( ( $rec= @mysqli_fetch_array( $ExampleResult4) ) ) 
			print_r($rec );
		@mysqli_free_result( $ExampleResult4);

		###################################################
		# Close a active connection
 		TGR_DB::close($TCDB1);
 		###################################################
		exit(0);
	}

?>