<?php
	$servername = "localhost:8082";
	$user = "root";
	$pass = "root";
	$db = "ezdslim30004";
	
	//Create connection 
	$dbc = mysqli_connect($servername,$user, $pass, $db);
	if(!$dbc)
	{
		die("CONNECT FAILED! " . mysqli_error($dbc));
	}
?>