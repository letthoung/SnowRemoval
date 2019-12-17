<?php
	session_start(); // Start the session.

	// If no session value is present, redirect the user:
	// Also validate the HTTP_USER_AGENT!
	/*if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT']) ))
	{
		require ('includes/login_functions.inc.php');
		redirect_user('index.php');
	}
	require ('includes/mysqli_connect.php');*/

	$s = $_GET['s'];
	$l = $_GET['l'];
	$q = "SELECT * FROM areas_new WHERE section = $s AND location = '$l' ORDER BY area ASC";
	$rs = @mysqli_query($dbc, $q);
	$answer = '';
	while($row = mysqli_fetch_array($rs))
	{
		if($answer !== '')
		{
			$answer = $answer . '::' . $row['area_id'] . '||' . $row['area'];
		}
		else
		{
			$answer = $row['area_id'] . '||' . $row['area'];
		}
	}
	echo $answer;
	mysqli_free_result($rs);
	mysqli_close($dbc);
