<?php
//session_start();

//define('TESTING',false);
// configuration parameters
// production
mssql_connect("sqlc2main\maindb2", "PWChangeUser", "*-m)nm%QM#HJ");
#mssql_connect("sqlc1dev\devdb", "webusertest", "wednesday");
mssql_select_db('useraccounts');
#$connect_url = "mssql://webpwchangeuser:bombastic@SQLc1DEV:51667/useraccounts";
#$connect_url = "mssql://webpwchangeuser:bombastic@sqlc2main:51667/useraccounts";
$mail_host = "nku.edu";

/*require_once("MDB2.php");
$connection = MDB2::factory($connect_url);
if(PEAR::isError($connection)) {
	die("Error while connecting.");
}

// development
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $connect_url = "mysql://useraccounts:useraccounts@localhost/useraccounts";
    $connect_url = array(
            'phptype' => 'mssql',
            'username'=> 'useraccounts',
            'password'=> 'useraccounts',
            'hostspec'=> 'localhost\SQLEXPRESS,4236',
            'database'=> 'useraccounts'
    );
    $mail_host = "localhost";
}
*/
?>
