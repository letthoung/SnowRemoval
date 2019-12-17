<?php
// This page defines two functions used by the login/logout process.

/* This function determines an absolute URL and redirects the user there.
 * The function takes one argument: the page to be redirected to.
 * The argument defaults to index.php.
 */

function redirect_user ($page = 'index.php') 
{

	// Start defining the URL...
	// URL is http:// plus the host name plus the current directory:
	$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	
	// Remove any trailing slashes:
	$url = rtrim($url, '/\\');
	
	// Add the page:
	$url .= '/' . $page;
	
	// Redirect the user:
	header("Location: $url");
	exit(); // Quit the script.

} // End of redirect_user() function.


/* This function validates the form data (the user id and password).
 * If both are present, the database is queried.
 * The function requires a database connection.
 * The function returns an array of information, including:
 * - a TRUE/FALSE variable indicating success
 * - an array of either errors or the database result
 */
function check_login($dbc, $user = '', $pass = '') 
{
	$errors = array(); // Initialize error array.

	// Validate the email address:
	if (empty($user)) 
	{
		$errors[] = 'You forgot to enter your user id.';
	} 
	else 
	{
		$u = mysqli_real_escape_string($dbc, trim($user));
	}

	// Validate the password:
	if (empty($pass)) 
	{
		$errors[] = 'You forgot to enter your password.';
	} 
	else 
	{
		//$p = $pass;
		$p = mysqli_real_escape_string($dbc, trim($pass));
	}

	if (empty($errors)) 
	{ // If everything's OK.

		// Retrieve the user_id and first_name for that email/password combination: 
		
		$q = "SELECT uId, user_id, first_name, last_name, admin_level, sec_question, pass FROM users WHERE uId='$u' AND reset_tries < 3 AND (pass_exp IS NULL OR CURRENT_DATE < pass_exp)";		
		$r = @mysqli_query ($dbc, $q); // Run the query.
		echo mysqli_num_rows($r);	
		exit;
		// Check the result:
		if (mysqli_num_rows($r) == 1) 
		{
			// Fetch the record:
			$row = mysqli_fetch_array ($r, MYSQLI_ASSOC);
				
			$parts = explode('@', $user);
			$namePart = $parts[0];
			require('includes/ldap_auth.php');
			$ldapFunc = new ldap_auth();
			if (is_null($ldapFunc->authenticate($namePart, $_POST['pass']))) {
				
				//Authenticated
				return array(true, $row);
				
			} else {
				$errors[] =  "Wrong credentials. The user id and password entered do not match those on file, or you have tried to reset too many times, or you have exceeded the temporary password time limit.";
			}
		} 
		else 
		{ // Not a match!
			$errors[] = 'The user id and password entered do not match those on file, or you have tried to reset too many times, or you have exceeded the temporary password time limit.';
		}
		
	} // End of empty($errors) IF.
	
	// Return false and the errors:
	return array(false, $errors);

} // End of check_login() function.


//This function validates the user question matches the chosen question and that the 
//answer is correct
function check_security($dbc, $user, $question, $answer) 
{
	$errors = array(); // Initialize error array.
	$q = "SELECT user_id, first_name, last_name, uId, sec_question, sec_answer, reset_tries FROM users WHERE uId = '$user' LIMIT 1";
	$result = @mysqli_query($dbc, $q);
	$row = mysqli_fetch_array($result);
	if (mysqli_num_rows($result) != 1) 
	{
		$errors[] = 'No users matched that email.';
	}
	else if($row['reset_tries'] > 2)
	{
		$errors[] = 'You have tried to reset the password too many times for this account, contact Work Control to unlock your account.';
	}
	else if(($row['sec_question'] != $question) || ($row['sec_answer'] !== SHA1($answer)))
	{
		$errors[] = 'Your question and answer did not match.';
		$q = "UPDATE users SET reset_tries = (reset_tries + 1) WHERE uId = '$user'";
		@mysqli_query($dbc, $q);
	}
	else
	{
		mysqli_free_result($result);
		
		$i = $row['user_id'];
		$q = "SELECT * FROM recovery_emails WHERE user_id = $i";
		$result = @mysqli_query($dbc, $q);
		if(mysqli_num_rows($result) >= 1)
		{
			$errors[] = 'You already have a change request pending, please follow that link to update your email.';	
		}
	}
	
	mysqli_free_result($result);
	
	if (empty($errors)) 
	{
		$q = "UPDATE users SET reset_tries = 0 WHERE uId = '$user'";
		@mysqli_query($dbc,$q);
		return array(true, $row);
	} 
	
	return array(false, $errors);
}


function send_email($dbc, $user, $user_id, $first_name, $last_name) 
{
	define(PW_SALT,'(+3%_');	
	$u_name = $first_name . ' ' . $last_name;
	$expFormat = mktime(date("H"), date("i"), date("s"), date("m")  , date("d")+3, date("Y"));
    $expDate = date("Y-m-d H:i:s",$expFormat);
    $key = SHA1($user_id . '_' . $user . rand(0,10000) .$expDate . PW_SALT);

	$stmt = mysqli_prepare($dbc,"INSERT INTO recovery_emails (user_id,key_token,exp_date) VALUES (?,?,?)");
    mysqli_stmt_bind_param($stmt,'iss',$user_id,$key,$expDate);
    mysqli_stmt_execute($stmt);
    
	$passwordLink = "<a href=\"norsegis.nku.edu/reset_pass.php?purpose=r&email=" . $key . "&u=" . urlencode(base64_encode($user_id)) . "\">norsegis.nku.edu/reset_pass.php?purpose=r&email=" . $key . "&u=" . urlencode(base64_encode($user_id)) . "</a>" . PHP_EOL;        
    $message = "Dear $u_name,<br>" . PHP_EOL;
    $message .= "Please visit the following link to reset your password:<br>" . PHP_EOL;
    $message .= "-----------------------<br>" . PHP_EOL;
    $message .= "$passwordLink" . PHP_EOL;
    $message .= "<br>-----------------------<br>" . PHP_EOL;
    $message .= "Please be sure to copy the entire link into your browser. The link will expire after 3 days for security reasons.<br>" . PHP_EOL;
    $message .= "If you did not request this forgotten password email, no action is needed, your password will not be reset as long as the link above is not visited.<br>" . PHP_EOL;
    $message .= "Thanks,<br>" . PHP_EOL;
    $message .= "-- Our site team<br>" . PHP_EOL;
	$headers = "From: NorseGIS" . PHP_EOL;
        //$headers .= "To-Sender: \n";
        //$headers .= "X-Mailer: PHP\r\n"; // mailer
    $headers .= "Reply-To: workcontroloffice@nku.edu" . PHP_EOL; // Reply address
        //$headers .= "Return-Path: webmaster@oursite.com\n"; //Return Path for errors
    $headers .= "Content-Type: text/html; charset=utf-8" . PHP_EOL; //Enc-type        
    $subject = "Your Lost Password";
    @mail($user,$subject,$message, $headers);
	mysqli_stmt_close($stmt);
}

function checkEmailKey($dbc,$key,$user_id)
{
	$curDate = date("Y-m-d H:i:s");	
    if ($stmt = mysqli_prepare($dbc,"SELECT user_id FROM recovery_emails WHERE key_token = ? AND user_id = ? AND exp_date >= ?"))
    {
		mysqli_stmt_bind_param($stmt,'sis',$key,$user_id,$curDate);
        mysqli_stmt_execute($stmt);      
		mysqli_stmt_store_result($stmt);      
        if (mysqli_stmt_num_rows($stmt) > 0)
        {
			return true;
        }
		else
		{
			return false;
		}
		
	}
	else
	{
		return false;
	}
}

function paginate($pages, $start, $display, $link)
{
	$pl = '<br><p>';
	$current_page = ($start/$display) + 1;
	$first = (($current_page - 10) < 1)? 1:($current_page - 10);
	$last =  (($current_page + 10) > $pages)? $pages:($current_page + 10);
	
		
	if($current_page != 1) 
	{
		if($first != 1)
		{
			$pl .= '<li class= "page-item"><a class="page-link" href="' . $link . '&s=0">First </a></li> ';
			//echo '<a href="light_assets.php?sort=' . $sort . '&s=0&p=' . $pages . '&bld=' . $feedSource . '&functional=' . $functional . '&ts=' . $textSearch . '">First </a> ';
		}
		$pl .= '<li class= "page-item"><a class="page-link" href="' . $link . '&s=' . ($start - $display) .'">Previous</a></li>';			
	}
	if($first != 1)
	{
		$pl .= '<li> <a href ="#">. . .</a></li>';
	}
	
	for($i = $first; $i <= $last; $i++) 
	{
		if ($i != $current_page) 
		{
			$pl .= '<li class= "page-item"><a class="page-link" href="' . $link . '&s=' . (($display * ($i - 1))) . '">' . $i . '</a> </li>';				
		} 
		else 
		{
			$pl .= '<li class= "page-item"><a class="page-link" href="#" style ="background:#7faef9; color:white">'. $i . '</a></li> ';
		}
	}
	
	if($last != $pages)
	{
		$pl .=  '<li><a href ="#">. . .</a></li>';
	}

	if($current_page != $pages) 
	{
		$pl .= '<li class= "page-item"><a class="page-link" href="' . $link . '&s=' . ($start + $display) . '">Next</a></li>';
		if($last != $pages)
		{
			$pl .= '<li class= "page-item"><a class="page-link" href="' . $link . '&s=' . (($display * ($pages - 1))) . '"> Last</a></li>';				
		}
	}
	/* if($pages > 20)
	{
		$pl .= ' <select>';
		for($i = 0; $i < $pages; $i++)
		{
			$pl .= '<option';
			$pl .= (($i + 1) == $current_page)? ' selected ':'';
			$pl .= '<a href="' . $link . '&s=' . ($display * $i) . '"> ' . ($i + 1) . '</a>';
			$pl .= '</option>';
		}
		$pl .= '</select>';
	} */
	$pl .= '</p>';
	return $pl;
}

