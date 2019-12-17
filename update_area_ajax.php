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

    $query = "SELECT * FROM snow_removal WHERE employee = $workerid ORDER BY start DESC";
    $result = mysqli_query($dbc, $query);
    if (!result){
        die("First query for user failed!" . mysqli_error($dbc));
    }
    $row = mysqli_fetch_assoc($result);
    $area_id = $row['area'];
    $timeInterval = $row['start'];
    $timeInterval = date("Y-m-d H:i:s", strtotime($timeInterval) - (30*60));

    $query = "SELECT * FROM areas_new WHERE area_id = $area_id";
    $result = mysqli_query($dbc, $query);
    if (!result){
        die("Second query for area failed!" . mysqli_error($dbc));
    }
    $row = mysqli_fetch_assoc($result);
    $section = $row['section'];
    $location = $row['location'];



    $query = "SELECT area FROM snow_removal WHERE employee = " . $workerid . " AND start > '$timeInterval'";
    $result = mysqli_query($dbc, $query);
    if (!$result){
        die("Third query for area failed!" . mysqli_error($dbc));
    }
    
    $areaArray = array();
    while($row = mysqli_fetch_array($result)){
        array_push($areaArray, $row[0]);
    }
    
    $query = "SELECT * FROM areas_new WHERE location = '$location' AND section = $section ORDER BY area ASC";
    $result = mysqli_query($dbc, $query);
    $array1 = array();
    $array2 = array();
    if (!result){
        die("Fourth query for area failed!" . mysqli_error($dbc));
    }
    while($row = mysqli_fetch_assoc($result)){
        array_push($array1, $row['area_id']);
        array_push($array2, $row['area']);
    }
    
    echo $section .  ";" . $location . ";" . implode(",",$areaArray) . ";" . implode(",",$array1) . ";" . implode(",",$array2);
}