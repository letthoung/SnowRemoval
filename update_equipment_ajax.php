<?php
	session_start(); // Start the session.

// If no session value is present, redirect the user:
// Also validate the HTTP_USER_AGENT!
if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT']) ))
{
	// Need the functions:
	require ('includes/login_functions.inc.php');
	redirect_user('index.php');
}
	require("includes/mysqli_connect.php");
if (isset($_POST['workerId'])){
  $workerid = $_POST['workerId'];
  $sql = "SELECT equipment FROM snow_removal WHERE employee = '$workerid' ORDER BY task_number DESC";
  $result = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($result);
  echo $row[0];
}
else if(!empty($_POST["insertEquip"])){
	$equipName = $_POST["insertEquip"];
	$sql = "INSERT INTO snow_removal_equipment (equipment) VALUES ('$equipName')";
	mysqli_query($dbc, $sql);
}
