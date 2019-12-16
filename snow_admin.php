<?php
	session_start(); // Start the session.

	// If no session value is present, redirect the user:
	// Also validate the HTTP_USER_AGENT!
	require ('includes/login_functions.inc.php');
	if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT']) ))
	{
		redirect_user('index.php');
	}

	require ('includes/mysqli_connect.php');

	//$equipment_list = array('Massey w/plow', '4115 Tractor', '1246 Gator', '1445', '0046', 'Cub Cadet', '0936 W/Plow and V-Box', '1346 Gator', '1036 Gator W/V-Box', '0536', '1536 Truck W/Plow and V-Box', 'Shovels', '9436', 'Cart or E-gator', 'Truck 0937', 'Truck 0636', 'Truck 9736', 'Truck 0846', 'Ventrac', 'Truck 1182');

	if (isset($_GET['s']) && is_numeric($_GET['s'])) //Find the starting point or initialize to zero
	{
		$start = $_GET['s'];
	}
	else
	{
		$start = 0;
	}
	$display = 10;
	$sort = (isset($_GET['sort'])) ? $_GET['sort'] : 'sd'; //Get the sort parameter

	switch ($sort) // Set up sort ascending or descending
	{
		case 'ad':
			$order_by = 'area DESC';
			break;
		case 'ed':
			$order_by = 'employee DESC';
			break;
		case 'sd':
			$order_by = 'start DESC';
			break;
		case 'end':
			$order_by = 'end DESC';
			break;
		case 'eqd':
			$order_by = 'Equipment DESC';
			break;
		case 'dd':
			$order_by = 'description DESC';
			break;
		case 'sad':
			$order_by = 'salted DESC';
			break;
		case 'pld':
			$order_by = 'plowed DESC';
			break;
		case 'sa':
			$order_by = 'salted ASC';
			break;
		case 'pl':
			$order_by = 'plowed ASC';
			break;
		case 'a':
			$order_by = 'area ASC';
			break;
		case 'e':
			$order_by = 'employee ASC';
			break;
		case 's':
			$order_by = 'start ASC';
			break;
		case 'en':
			$order_by = 'end ASC';
			break;
		case 'eq':
			$order_by = 'equipment ASC';
			break;
		case 'd':
			$order_by = 'description ASC';
			break;
		default:
			$order_by = 'start DESC';
			$sort = 'sd';
			break;
	}





	if ($_SERVER['REQUEST_METHOD'] == 'POST') //Get information from the form if user submitted a search
	{
		$e_id = $_POST['event_id'];
		$q = "SELECT * FROM events WHERE end_time IS NULL LIMIT 1";
		$rs = @mysqli_query($dbc, $q);
		$row = mysqli_fetch_array($rs);
		$des = $row['event_description'];
		$start_time = $row['start_time'];
		$ongoing = (mysqli_num_rows($rs) == 1)? true:false;
		$current_event = (mysqli_num_rows($rs) == 1 && $row['event_id'] == $e_id)? true:false;
		mysqli_free_result($rs);
	}
	else
	{
		$q = "SELECT * FROM events WHERE end_time IS NULL LIMIT 1";
		$rs = @mysqli_query($dbc,$q);
		if(mysqli_num_rows($rs) == 1)
		{
			$ongoing = true;
			$row = mysqli_fetch_array($rs);
			$e_id = (isset($_GET['id']))? $_GET['id']:$row['event_id'];
			$des = $row['event_description'];
			$start_time = $row['start_time'];
			$current_event = ($e_id == $row['event_id'])? true:false;
		}
		else
		{
			$current_event = false;
			$e_id = (isset($_GET['id']))? $_GET['id']:0;
		}
		mysqli_free_result($rs);
	}

	if($current_event)//If there is an ongoing event, display all snow removal tasks associated with it
	{
		$q = "SELECT start_time FROM events WHERE event_id = $e_id LIMIT 1";
		$rs = @mysqli_query($dbc, $q);
		$row = mysqli_fetch_array($rs);
		$st = $row[0];
		mysqli_free_result($rs);
		$query = "SELECT snow_removal.*, users.first_name, users.last_name FROM snow_removal JOIN users ON snow_removal.employee = users.user_id WHERE start > '$st' ORDER BY $order_by LIMIT $start, $display";
		$queryCount = "SELECT * FROM snow_removal WHERE start > '$st'";
	}
	else
	{
		if($e_id == 0)//If there is not an event selected, display all snow removal tasks
		{
			$query = "SELECT snow_removal.*, users.first_name, users.last_name FROM snow_removal JOIN users ON snow_removal.employee = users.user_id ORDER BY $order_by LIMIT $start, $display";
			$queryCount = "SELECT * FROM snow_removal";
		}
		else//If there is an event selected, display all snow removal tasks associated with it
		{
			$q = "SELECT start_time, end_time FROM events WHERE event_id = $e_id LIMIT 1";
			$rs = @mysqli_query($dbc, $q);
			$row = mysqli_fetch_array($rs);
			$st = $row['start_time'];
			$et = $row['end_time'];
			mysqli_free_result($rs);
			$query = "SELECT snow_removal.*, users.first_name, users.last_name FROM snow_removal JOIN users ON snow_removal.employee = users.user_id WHERE start >= '$st' AND start <= '$et' ORDER BY $order_by LIMIT $start, $display";
			$queryCount = "SELECT * FROM snow_removal WHERE start >= '$st' AND start <= '$et'";
		}
	}
	$rs = @mysqli_query($dbc, $query);
	$rsCount = @mysqli_query($dbc, $queryCount);

	$records = mysqli_num_rows($rsCount);

	if (isset($_GET['p']) && is_numeric($_GET['p'])) //Get the number of pages, either from $_GET or calculate from query results
	{
		$pages = $_GET['p'];
	}
	else
	{
		if ($records > $display)
		{
			$pages = ceil ($records/$display);
		}
		else
		{
			$pages = 1;
		}
	}

	$page_title = 'Snow Removal Admin page';
	include ('includes/header.html');
?>
<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.css">
<style>
body{
		background-color: #ECEBE4;
		border: 5px groove #BDC4A7;
		margin: 6px;
		margin-right: 8px;
	}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>

<!--

	//Function will finish an event by sending requesting a finish time for the given task.
	function finish_task(id)
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

				xmlhttp.open("GET","finish_task.php?id="+id,true);
				xmlhttp.send();
	}
	//Function will get event description, start time, and end time to create an event. Sends this info to the database via xmlhttp request
	function makeE()
	{
		var h,hstart;
		var d,dstart;
		var m,mstart;
		var y,ystart;
		var mm,mmstart;
		var startTime,endTime,s,e;
		var des;
		des = document.getElementById("eventLabel").value;
		if(!des)//Validate input, insuring there is a description
		{
			alert("You did not enter a description for the event.");
		}
		else
		{
			hstart = document.getElementById("hourSelectStart").value;
			dstart = document.getElementById("daySelectStart").value;
			mstart = document.getElementById("monthSelectStart").value - 1;
			ystart = document.getElementById("yearSelectStart").value;
			mmstart = document.getElementById("minuteSelectStart").value;
			h = document.getElementById("hourSelect").value;
			d = document.getElementById("daySelect").value;
			m = document.getElementById("monthSelect").value - 1;
			y = document.getElementById("yearSelect").value;
			mm = document.getElementById("minuteSelect").value;

			startTime = new Date(ystart,mstart,dstart,hstart,mmstart);//Create date objects for start and end with user input info
			endTime = new Date(y,m,d,h,mm);
			if(endTime.getTime() <= startTime.getTime())//Validate that the end time is AFTER the start time
			{
				alert("The ending time needs to be set to be after the start time.");
			}
			else
			{
				//Change the format of the start and end times to match mysql datetime format so they can be sent to the database
				s = startTime.getFullYear() + "-" + ("0" + (startTime.getMonth() + 1)).slice(-2) + "-" + ("0" + startTime.getDate()).slice(-2) + " " + ("0" + startTime.getHours()).slice(-2) + ":" + ("0" + startTime.getMinutes()).slice(-2) + ":" + ("0" + startTime.getSeconds()).slice(-2);
				e = endTime.getFullYear() + "-" + ("0" + (endTime.getMonth() + 1)).slice(-2) + "-" + ("0" + endTime.getDate()).slice(-2) + " " + ("0" + endTime.getHours()).slice(-2) + ":" + ("0" + endTime.getMinutes()).slice(-2) + ":" + ("0" + endTime.getSeconds()).slice(-2);
				var xmlhttp;
				xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
				xmlhttp.onreadystatechange = function()//Function called when there is a change of state for the server
				{                                      //request
					if (xmlhttp.readyState == 4 && xmlhttp.status == 200)//when request is complete and no issues
					{
						document.getElementById("create").value = "Create Event";
						document.getElementById("t02").style.display = "none";
					}
				};

				xmlhttp.open("GET","make_event.php?s="+s+"&e="+e+"&d="+des,true);
				xmlhttp.send();
			}
		}
	}

	//This function will be used to start an event that is ongoing, and therefore does not have an end time. Also used when the event is finished, to end it.
	function startE()
	{
		var xmlhttp;
		if (document.getElementById("startEvent").value == "Start Event")//If the event is being started
		{
			document.getElementById("startEvent").value = "Finish";//Change the display to properly reflect that the next action is to finish the event
			document.getElementById("t02").style.display = "none";//Hide the Create Event button and inputs
			document.getElementById("create").style.display = "none";
			document.getElementById("eventStart").style.display = "inline";
			var newEventDate = new Date();
			var e = "no";
			var des = prompt("Please enter a description for this event: ");//Get a description for the event
			while (!des)//Ensure a description is entered
			{
				des = prompt("Please enter a description for this event: ");
			}
			var s = newEventDate.getFullYear() + "-" + ("0" + (newEventDate.getMonth() + 1)).slice(-2) + "-" + ("0" + newEventDate.getDate()).slice(-2) + " " + ("0" + newEventDate.getHours()).slice(-2) + ":" + ("0" + newEventDate.getMinutes()).slice(-2) + ":" + ("0" + newEventDate.getSeconds()).slice(-2);
			xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
			xmlhttp.onreadystatechange = function ()//Function called when there is a change of state for the server
			{                                      //request
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200)//when request is complete and no issues
				{
					document.getElementById("eventStart").innerHTML = "Event: " + des + " started: " + s;
				}
			};
			xmlhttp.open("GET", "make_event.php?e=" + e + "&d=" + des, true);//Send event to database
			xmlhttp.send();
		}
		else//If event is being ended
		{
			document.getElementById("startEvent").value = "Start Event";//Reflect change to event status
			document.getElementById("eventStart").style.display = "none";
			document.getElementById("create").style.display = "inline";//Display Create Event button again
			if(document.getElementById("create").value == "Hide")
			{
				document.getElementById("t02").style.display = "block";
			}
			else
			{
				document.getElementById("t02").style.display = "none";
			}
			document.getElementById("eventStart").innerHTML = "";
			var e = "yes";
			var xmlhttp;
			xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");

			xmlhttp.open("GET","make_event.php?e="+e,true);//Update database
			xmlhttp.send();
		};
	}
	//Function will display the input elements needed to create an event, or hide them when needed
	function createE()
	{
		if(document.getElementById("create").value == "Create Event")
		{
			document.getElementById("create").value = "Hide";
			document.getElementById("t02").style.display = "block";
		}
		else
		{
			document.getElementById("create").value = "Create Event";
			document.getElementById("t02").style.display = "none";
		}
	}
	//Used to set the number of days in the select dropdown, based on the month entered (i.e. show 31 days if Jan is selected)
	function setDays(i)
	{
		var daySelect;
		var monthSelected;
		if(i == 2)//i is used to indicate whether the function is operating on the start or end time dropdowns
		{
			monthSelected = document.getElementById("monthSelect").value;
			daySelect = document.getElementById("daySelect");
		}
		else
		{
			monthSelected = document.getElementById("monthSelectStart").value;
			daySelect = document.getElementById("daySelectStart");
		}
		daySelect.length = 0;
		if(monthSelected == 2)
		{
			for(var i = 0; i < 29; i++)
			{
				var opt = document.createElement('option');
				opt.value = (i + 1);
				opt.text = (i + 1);
				daySelect.appendChild(opt);
			}
		}
		else if(monthSelected == 1 || monthSelected == 3 || monthSelected == 5 || monthSelected == 7 || monthSelected == 8 || monthSelected == 10 || monthSelected == 12)
		{
			for(var i = 0; i < 31; i++)
			{
				var opt = document.createElement('option');
				opt.value = (i + 1);
				opt.text = (i + 1);
				daySelect.appendChild(opt);
			}
		}
		else
		{
			for(var i = 0; i < 30; i++)
			{
				var opt = document.createElement('option');
				opt.value = (i + 1);
				opt.text = (i + 1);
				daySelect.appendChild(opt);
			}
		}
	}
//-->
</script>
<style>
	table#t02
	{
		border:none;
		display:none;
		padding:0;
		width:600px;
		margin:auto;
	}
	table#t02 tr:nth-child(even)
	{
		background-color: #CEE4F5;
	}
	table.t02 tr:nth-child(odd)
	{
		background-color: #eeeeee;
	}
	a:hover{
		color: green;
		text-decoration-line: none;
		font-size: 1.2em;
	}
</style>
<body>
   <div>
	<img src="norse.jpeg" style="width:150px; position: absolute; right: 3.5%; top: 3.5%;">
	</div>
    <div class="container">
	<p><?php if($_SESSION['admin_level'] >= 1){ echo '<a href = "enter_snow_removal_task_development.php?purpose=create" style="margin-left: 30%;">Create a Snow Removal Task </a> &nbsp; &nbsp; | &nbsp; &nbsp;';}?> <a href = "event_map.php?sort=<?php echo $sort . '&s=' . $start . '&p=' . $pages . '&id=' . $e_id;?>">  Map</a></p>
	<?php
		if($_SESSION['admin_level'] >= 2)
		{
			echo '<p><input type = "button" style="margin-left: 30%;" class="btn btn-default" name = "create" id = "create" style = "display:';
			echo ($current_event || $ongoing)? "none;":"inline; margin-right: 100px;";
			echo '" value = "Create Event" onclick = "createE();"
			>';
			echo '&nbsp;';
			echo '<input type = "button" class="btn btn-default" name = "startEvent" id = "startEvent" value = "';

			echo ($current_event || $ongoing)? "Finish":"Start Event";
			echo '" onclick = "startE();">';
			echo '<span style = "display:';
			echo ($current_event || $ongoing)? "inline;":"none;";
			echo '" id = "eventStart" name = "eventStart">';
			echo ($current_event || $ongoing)? "Event $des started $start_time":'';
			echo '</span></p>';
		}
	?>
	<p>
		<form action = "snow_admin.php" method = "post">
				<select name = "event_id" id = "event_id" style="margin-left: 15%;">
					<option value = "0">All events</option>
					<?php
						//Display all events to show snow removal tasks for
						$q = "SELECT * FROM events ORDER BY start_time DESC";
						$result = @mysqli_query($dbc, $q);
						while($row = mysqli_fetch_array($result))
						{
							echo '<option value = "' . $row['event_id'] . '"';
							echo ($row['event_id'] == $e_id)? ' selected':'';
							echo '>' . $row['event_description'] . ': From  ' . $row['start_time'];
							if($row['end_time'] != NULL)
							{
								echo '  To  ' . $row['end_time'];
							}
							else
							{
								echo '  CURRENT EVENT  ';
							}
							echo '</option>';
						}
						mysqli_free_result($result);
					?>
				</select>
				<input type = "submit" class="btn btn-default" value = "Search tasks" style="margin-left: 7px;">
		</form>
	</p>
	<!-- Create table to get event info -->
	<table id = "t02" class="table table-striped table-bordered table-hover table-condensed">
		<tr>
		<th>Event Label:</th>
			<td colspan = "10">
				<input type = "text" name = "eventLabel" id = "eventLabel">
			</td>
		</tr>
		<tr>
			<th>Begin:</th>
			<th>Month:</th>
			<td><select name = "monthSelectStart" id = "monthSelectStart" onchange = "setDays(1);">
				<option value = "1">January</option>
				<option value = "2">February</option>
				<option value = "3">March</option>
				<option value = "4">April</option>
				<option value = "5">May</option>
				<option value = "6">June</option>
				<option value = "7">July</option>
				<option value = "8">August</option>
				<option value = "9">September</option>
				<option value = "10">October</option>
				<option value = "11">November</option>
				<option value = "12">December</option>
				</select>
			</td>
			<th>Day:</th>
			<td>
				<select name = "daySelectStart" id = "daySelectStart">
					<?php
						for($i = 0; $i < 31; $i++)
						{
							echo '<option value = "' . ($i + 1) . '">' . ($i + 1) . '</option>';
						}
					?>
				</select>
			</td>
			<th>Year:</th>
			<td>
				<select name = "yearSelectStart" id = "yearSelectStart">
					<?php
						for($i = 0; $i < 100; $i++)
						{
							echo '<option value = "' . ($i + 2015) . '">' . ($i + 2015) . '</option>';
						}
					?>
				</select>
			</td>
			<th>Hour:</th>
			<td>
				<select name = "hourSelectStart" id = "hourSelectStart">
					<?php
						for($i = 0; $i < 24; $i++)
						{
							echo '<option value = "' . $i . '">' . $i . '</option>';
						}
					?>
				</select>
			</td>
			<th>Minute:</th>
			<td>
				<select name = "minuteSelectStart" id = "minuteSelectStart">
					<?php
						for($i = 0; $i < 60; $i++)
						{
							echo '<option value = "' . $i . '">' . $i . '</option>';
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Ending:</th>
			<th>Month:</th>
			<td><select name = "monthSelect" id = "monthSelect" onchange = "setDays(2);">
				<option value = "1">January</option>
				<option value = "2">February</option>
				<option value = "3">March</option>
				<option value = "4">April</option>
				<option value = "5">May</option>
				<option value = "6">June</option>
				<option value = "7">July</option>
				<option value = "8">August</option>
				<option value = "9">September</option>
				<option value = "10">October</option>
				<option value = "11">November</option>
				<option value = "12">December</option>
				</select>
			</td>
			<th>Day:</th>
			<td>
				<select name = "daySelect" id = "daySelect">
					<?php
						for($i = 0; $i < 31; $i++)
						{
							echo '<option value = "' . ($i + 1) . '">' . ($i + 1) . '</option>';
						}
					?>
				</select>
			</td>
			<th>Year:</th>
			<td>
				<select name = "yearSelect" id = "yearSelect">
					<?php
						for($i = 0; $i < 100; $i++)
						{
							echo '<option value = "' . ($i + 2015) . '">' . ($i + 2015) . '</option>';
						}
					?>
				</select>
			</td>
			<th>Hour:</th>
			<td>
				<select name = "hourSelect" id = "hourSelect">
					<?php
						for($i = 0; $i < 24; $i++)
						{
							echo '<option value = "' . $i . '">' . $i . '</option>';
						}
					?>
				</select>
			</td>
			<th>Minute:</th>
			<td>
				<select name = "minuteSelect" id = "minuteSelect">
					<?php
						for($i = 0; $i < 60; $i++)
						{
							echo '<option value = "' . $i . '">' . $i . '</option>';
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan = "11">
				<input style = "width:40%;display:block;margin:auto;" type = "button" value = "Create" onclick = "makeE();">
			</td>
		</tr>
	</table>
	<p><?php echo $records . ' tasks found.';?></p>
	<table class = "table table-striped table-bordered table-hover table-condensed">
		<tr>
		<?php
			$link = 'snow_admin.php?s=' . $start . '&p=' . $pages . '&id=' . $e_id;
			//Show links to let user sort through snow removal tasks
			$s = ($sort == 'a')? 'ad':'a';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Area</a></b></th>';
			$s = ($sort == 'e')? 'ed':'e';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Worker</a></b></th>';
			$s = ($sort == 's')? 'sd':'s';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Start</a></b></th>';
			$s = ($sort == 'en')? 'end':'en';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Finish</a></b></th>';
			$s = ($sort == 'eq')? 'eqd':'eq';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Equipment</a></b></th>';
			$s = ($sort == 'd')? 'dd':'d';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Description</a></b></th>';
			$s = ($sort == 'sa')? 'sad':'sa';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Salted</a></b></th>';
			$s = ($sort == 'pl')? 'pld':'pl';
            echo '<th><b><a href="' . $link . '&sort=' . $s . '">Plowed</a></b></th>';
		?>
			<td><b>Images</b></td><td><b>Time Spent<br>HH:MM:SS</b></td>
		</tr>
		<?php
			$total_time_spent = 0;
			//Actually display snow removal tasks
			while($row = mysqli_fetch_array($rs))
			{
				$a = $row['area'];
				$s_time = strtotime($row['start']);
				$e_time = strtotime($row['end']);
				$total_time_spent += ($e_time == '')? 0:($e_time - $s_time);
				$time_spent = ($e_time == '')? "Not yet finished.":(date("H:i:s",($e_time - $s_time)));
				$query1 = "SELECT area FROM areas_new WHERE area_id = $a LIMIT 1";
				$rs1 = @mysqli_query($dbc, $query1);
				$row1 = mysqli_fetch_array($rs1);
				echo '<tr><td>';
				echo $row1[0];
				echo '</td><td>';
				echo $row['last_name'] . ', ' . $row['first_name'];
				echo '</td><td>';
				echo $row['start'];
				echo '</td><td>';
				echo ($row['end'] == null)? '<input type = "button" name = "task_finish" value = "Finish Task" onclick = "finish_task(' . $row['task_number'] . ');">':$row['end'];
				echo '</td><td>';
					$sql = 'SELECT * FROM snow_removal_equipment WHERE id = "'.$row['equipment'].'"';
					$eq = mysqli_query($dbc, $sql);
					$equipment_list = mysqli_fetch_array($eq);
				echo $equipment_list['equipment'];
				echo '</td><td>';
				echo $row['description'];
				if($_SESSION['admin_level'] >= 1)
				{
					echo '<a href = "enter_snow_removal_task.php?purpose=description&task=' . $row['task_number'] . '" style = "float:right;">edit</a>';
				}
				echo '</td><td>';
				echo ($row['salted'] == 0)? 'No':'Yes';
				echo '</td><td>';
				echo ($row['plowed'] == 0)? 'No':'Yes';
				echo '</td><td>';
				$found = false;
				$images = scandir('SnowImages/');
				for($i = 0; $i < count($images) && !$found; $i++)
				{
					if(pathinfo($images[$i], PATHINFO_FILENAME) == $row['task_number'])
					{
						$task_image = 'SnowImages/' . $row['task_number'] . '.' . pathinfo($images[$i], PATHINFO_EXTENSION);
						$found = true;
					}
				}

				if($found)
				{
					//echo '<img src="' . $task_image . '" height = "150" width = "150" alt="" />';
					echo '<a href="' . $task_image . '" target = "_blank"><u>View Image</u></a>';
				}
				else
				{
					echo '<a href="enter_snow_removal_task.php?purpose=image&task=' .$row['task_number'] . '" target = "_SELF"><u>Upload Image</u></a>';
				}
				echo '</td><td>';
				echo $time_spent;
				echo '</td></tr>';
				mysqli_free_result($rs1);
			}
			mysqli_free_result($rs);
			$hours = floor($total_time_spent / 3600);
			$mins = floor(($total_time_spent - ($hours*3600)) / 60);
			$secs = floor($total_time_spent % 60);
			echo '<tr><td colspan = "8" style="border:none;background-color:white;"></td><th>Total Time:</th><td>';
			echo $hours . ":" . $mins . ":" . $secs;
			echo '</td></tr>';
		?>
	</table>
	</div>
	</body>
	<center>
	<ul class = "pagination">
<?php

	if($pages > 1) //Set up pages if necessary
	{
		$link = 'snow_admin.php?sort=' . $sort . '&p=' . $pages . '&id=' . $e_id;
		echo paginate($pages, $start, $display, $link);
	}

	mysqli_close($dbc);
	include ('includes/footer.html');
?>
</ul>
</center>
