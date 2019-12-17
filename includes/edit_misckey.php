<?php
	session_start(); // Start the session.

	// If no session value is present, redirect the user:
	// Also validate the HTTP_USER_AGENT!
	if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT']) OR ($_SESSION['admin_level'] < 1))) 
	{
		require ('includes/login_functions.inc.php');
		redirect_user('index.php');
	}
	
	require ('includes/mysqli_connect.php');
	require ('includes/login_functions.inc.php');	
	if(isset($_GET['id']))
	{
		$id = $_GET['id'];
	}
	else
	{
		redirect_user('locksmith.php');
	}
	
	$query = "SELECT * FROM misckeys WHERE misc_id = $id";
	$rs = @mysqli_query($dbc, $query);
	if(mysqli_num_rows($rs) > 0)
	{
		$row = mysqli_fetch_array($rs);
		$miscname = $row['miscnum'];
		$miscbuild = $row['miscbld'];
		$miscbrd = $row['miscbrand'];
		$miscdes = $row['miscdesc'];
		$misctypes = $row['misctype'];
		
	}
	mysqli_free_result($rs);
	
	if($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		
		if($_POST['door_brand_select'] != '0')
		{			
			$miscbrd = $_POST['door_brand_select'];
		}
		else if(!empty($_POST['door_brand']))
		{
			$miscbrd = $_POST['door_brand'];
		}
		else
		{
			$miscbrd = NULL;
		}
		if($_POST['door_type_select'] != '0')
		{			
			$misctypes = $_POST['door_type_select'];
		}
		else if(!empty($_POST['door_type']))
		{
			$misctypes = $_POST['door_type'];
		}
		else
		{
			$misctypes = NULL;
		}
		
		if($_POST['bld_name'] != '0')
		{			
			$miscbuild = $_POST['bld_name'];			
		}
		else if(!empty($_POST['door_building']))
		{
			$bld_class = $_POST['new_building_type'];
			$bld_name = $_POST['door_building'];
			$stmt = mysqli_prepare($dbc, "INSERT INTO lock_buildings (bld_name, building_class) VALUES (?,$bld_class)");
			mysqli_stmt_bind_param($stmt, "s", $bld_name);
			mysqli_stmt_execute($stmt);
			$miscbuild = mysqli_insert_id($dbc);
			mysqli_stmt_close($stmt);
		}
		else
		{
			$miscbuild = NULL;			
		}
		
		$miscname = (empty($_POST['door_name']))? $miscname:$_POST['door_name'];
		$misdes = (empty($_POST['door_desc']))? NULL:$_POST['door_desc'];
		$s = mysqli_prepare($dbc, "UPDATE misckeys SET miscnum = ?, miscbld = ?, miscbrand = ?, miscdesc = ?, misctype = ? WHERE misc_id = $id");
		mysqli_stmt_bind_param($s, "sssssssisssss", $miscname, $miscbuild, $miscbrd, $miscdes, $misctypes);
		mysqli_stmt_execute($s);
		if(mysqli_error($dbc))
		{
			$errors[] = mysqli_error($dbc);
		}
		mysqli_stmt_close($s);
	}

	$page_title = 'Edit Key';
	include ('includes/header.html');
?>
	<script>
	<!--

		function deleteKey(id)
		{
			var xmlhttp;
			xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
			xmlhttp.onreadystatechange = function()//Function called when there is a change of state for the server
			{                                      //request
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200)//when request is complete and no issues
				{		
					location.reload();
				}
			};
			xmlhttp.open("GET","misc_key.php?id="+id+"&pur=del",true);
			xmlhttp.send(); 
		}
		function addDoorKey()
		{
			var miscKey = document.getElementById("door_key");
			var dK = miscKey.value;
			var id = document.getElementById("doorId").value;
			var door_date = document.getElementById("door_key_date");
			var dd = door_date.value, xmlhttp;
			if(dd == '' || (/^(19|20)\d\d[/](0[1-9]|1[012])[/](0[1-9]|[12][0-9]|3[01])$/.test(dd)))
			{						
				xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
				xmlhttp.onreadystatechange = function()//Function called when there is a change of state for the server
				{                                      //request
					if (xmlhttp.readyState == 4 && xmlhttp.status == 200)//when request is complete and no issues
					{
						miscKey.value = '';	
						door_date.value = '';
						location.reload();
					}
				};
				/* xmlhttp.open("GET","misc_key.php?id="+id+"&k="+dK+"&d="+dd+"&pur=add",true);
				xmlhttp.send(); */
				xmlhttp.open("POST","misc_key.php",true);//prune will accept the feature description and return a string with only the
				xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xmlhttp.send("pur=add&id="+id+"&k="+dK+"&d="+dd);
			}
			else
			{
				alert("You did not enter the date in a correct format.");
			}
		}

	// -->
	</script>
	<br />

		<?php
			if(!empty($errors))
			{
				echo '<p class = "error">Changes not made because:</p>';			
				foreach($errors as $value) 
				{
					echo '<p class = "error">' . $value . '</p>';
				}
			}
		?>
	<form style = "width:100%" action = "edit_submit.php" method = "POST">
		<input type = "hidden" id = "doorId" name = "doorId" value = "<?php echo $id; ?>">
		<table class = "t01">
			<tr>
				<th>Key Number:</th>
				<td><input type ="text" name = "door_name" id = "door_name" maxlength="255" value = "<?php echo (isset($miscname))? $miscname:'';?>"></td>			
			</tr>
			
			<tr>
				<th><label for="door_brand">Brand:</label></th>
				<td>
					<?php				
						$query = "SELECT DISTINCT miscbrand FROM misckeys WHERE miscbrand != '' ORDER BY miscbrand";
						$rs = @mysqli_query($dbc, $query);
						if(mysqli_num_rows($rs) != 0)
						{
							echo '<select name = "door_brand_select" id = "door_brand_select">';
							echo '<option value = "0">New Brand</option>';
							while($row = mysqli_fetch_array($rs))
							{
								echo '<option value = "' . $row['miscbrand'] . '">' . $row['miscbrand'] . '</option>';
							}
							echo '</select>';
							mysqli_free_result($rs);
						}					
					?>
					<input type = "text" id = "door_brand" name = "door_brand" value = "<?php echo (empty($miscbrd))? '':$miscbrd; ?>">
				</td>
			</tr>
			<tr>
				<th><label for="door_type">Type:</label></th>
				<td>
					<?php				
						$query = "SELECT DISTINCT misctype FROM misckeys WHERE misctype != '' ORDER BY misctype";
						$rs = @mysqli_query($dbc, $query);
						if(mysqli_num_rows($rs) != 0)
						{
							echo '<select name = "door_type_select" id = "door_type_select">';
							echo '<option value = "0">New Type</option>';
							while($row = mysqli_fetch_array($rs))
							{
								echo '<option value = "' . $row['misctype'] . '">' . $row['misctype'] . '</option>';
							}
							echo '</select>';
							mysqli_free_result($rs);
						}					
					?>
					<input type = "text" id = "door_type" name = "door_type" value = "<?php echo (empty($misctypes))? '':$misctypes; ?>">
				</td>
			</tr>
			<tr>
				<th><label for="bld_name">Building:</label></th>
				<td>
					<?php				
						$query = "SELECT * FROM lock_buildings ORDER BY bld_name";
						$rs = @mysqli_query($dbc, $query);
						if(mysqli_num_rows($rs) != 0)
						{
							echo '<select name = "bld_name" id = "bld_name">';
							echo '<option value = "0">New Building</option>';
							while($row = mysqli_fetch_array($rs))
							{
								echo '<option value = "' . $row['bld_no'];
								if($miscbuild == $row['bld_no'])
								{
									echo '" selected >';
								}
								else
								{
									echo '">';
								}
								echo $row['bld_name'] . '</option>';
							}
							echo '</select>';
							mysqli_free_result($rs);
						}					
					?>
					<select name = "new_building_type" id = "new_building_type">
						<option value = "1">Main Campus</option>
						<option value = "2">Real Property Development</option>
						<option value = "3">University Housing</option>
					</select>
					<input type = "text" id = "door_building" name = "door_building">
				</td>
			</tr>
			<tr>
				<th><label for="door_desc">Description:</label></th>
				<td><textarea style = "resize:none;" name = "door_desc" id="door_desc" maxlength="255" rows="5" cols="40"><?php echo (isset($miscdes))? $miscdes:'';?></textarea></td>
			</tr>
			<tr>
				<th>Add key identifiers:</th>
				<td>
					<input type = "text" id = "door_key" placeholder = "Key identifier" name = "door_key"> Date Installed:
					<input type = "text" id = "door_key_date" maxlength="50" placeholder = "YYYY/MM/DD" name = "door_key_date">
					<input type = "button" value = "Add" onclick="addDoorKey();">
				</td>
			</tr>
		</table>
		<p><input style = "border:thin solid black;" type = "submit" value = "Submit Changes" onClick = "return confirmAction();"></p>		
		<br>
	</form>
	
	<?php
		$q = "SELECT * FROM misckeys WHERE misc_id = $id";
		$rs = @mysqli_query($dbc, $q);
		if(mysqli_num_rows($rs) > 0)
		{
			echo '<table class = "t01">';
			while($row = mysqli_fetch_array($rs))
			{
				echo '<tr><td><input style = "border:thin solid black;" type = "button" value = "Delete" onClick = "deleteKey(' . $row['misc_id'] . ');"></td>';
				echo '<td>Key: ' . $row['miscnum'] . '</td><td>Date Installed: ' . $row['date_installed'] . '</td></tr>';
			}			
			echo '</table>';
		}
		mysqli_free_result($rs);
	?>
	<p><a href="new_door.php" target="_self">Create New Door</a></p>
<?php	
	mysqli_close($dbc);
	include ('includes/footer.html');
?>