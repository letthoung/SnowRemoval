<?php
// The user is redirected here from login.php.

session_start(); // Start the session.

// If no session value is present, redirect the user:
// Also validate the HTTP_USER_AGENT!
if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT']) )) {

	// Need the functions:
	require ('includes/login_functions.inc.php');
	redirect_user();	

}

// Set the page title and include the HTML header:
$page_title = 'Home Page';
include ('includes/header.html');
require ('includes/mysqli_connect.php');

?>

				<div id = "iframeholder" style="width:100%;float:right;margin-top:53px; margin:auto;">
			<p style="margin:auto;"><b>Snow Removal</b></p>
			<?php $today = (int) date('n');

if( $today <= 4 || $today == 11 || $today == 12)
{
	echo '<p><b>Snow Removal</b></p>';
	include ('includes/snow_events.html');
}?>
		</div>																
																			
		
																						
																						<?php
mysqli_close($dbc);
include ('includes/footer.html');

				