<?php
	$servername = "localhost";
	$user = "chris";
	$pass = "f9B04pL1&";
	$db = "ezdslim30004";
	
	//Create connection 
	$dbc = @mysqli_connect($servername,$user, $pass, $db);
	if(mysqli_connect_errno())
	{
		$errors[] = "Database connection unsuccessful.";
	}
?>