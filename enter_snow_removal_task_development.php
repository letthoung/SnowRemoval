<?php
	session_start(); // Start the session.

	// If no session value is present, redirect the user:
	// Also validate the HTTP_USER_AGENT!
	if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT']) OR ($_SESSION['admin_level'] < 1) ))
	{
		require ('includes/login_functions.inc.php');
		redirect_user('index.php');
	}

	$user_id = $_SESSION['user_number'];
	$task_id = (isset($_GET['task']))? $_GET['task']:0;
	$purpose = $_GET['purpose'];
	require ('includes/mysqli_connect.php');

	if($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		if($purpose == "create")
		{
			$arr = json_decode($_POST['arr'],true);

			for ($i = 0; $i< count($arr); $i++){

				$salted = (isset($_POST['salted']))? 1:0;
				$plowed = (isset($_POST['plowed']))? 1:0;
				$user_id = $_POST['employee_select'];
				$sec = $_POST['sections'];
				$area = $arr[$i];
				$eq = $_POST['equip'];
				$des = (!empty($_POST['description']))? $_POST['description']:'';

				if($_SESSION['admin_level'] > 1)
				{
					$created_by = $_SESSION['user_number'];
					$s = mysqli_prepare($dbc, "INSERT INTO snow_removal (area, start, employee, equipment, description, salted, plowed, created_by) VALUES (?,NOW(),?,?,?,?,?,?)");
					mysqli_stmt_bind_param($s, "iissiii", $area, $user_id, $eq, $des, $salted, $plowed, $created_by);
				}
				else
				{
					$s = mysqli_prepare($dbc, "INSERT INTO snow_removal (area, start, employee, equipment, description, salted, plowed) VALUES (?,NOW(),?,?,?,?,?)");
					mysqli_stmt_bind_param($s, "iissii", $area, $user_id, $eq, $des, $salted, $plowed);
				}
				mysqli_stmt_execute($s);
				mysqli_stmt_close($s);

			}
			require ('includes/login_functions.inc.php');
			redirect_user('snow_admin.php');
			exit;
		}
		else if( $purpose == "image" )
		{

			if($_FILES['task_pic']['size'] !== 0)
			{
				$file = basename($_FILES['task_pic']['name']);
				$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				$f_image = 'SnowImages/' . $task_id . '.' . $ext;

				if(file_exists($f_image))
				{
					unlink($f_image);
				}
				if(!(move_uploaded_file($_FILES['task_pic']['tmp_name'], $f_image)))
				{
					switch($_FILES['task_pic']['error'])
					{
						case 1:
							$errors[] = "The file is bigger than PHP installation allows";
							break;
						case 2:
							$errors[] = "The file is bigger than this form allows";
							break;
						case 3:
							$errors[] = "Only part of the file was uploaded.";
							break;
					}
					$errors[] = "No file was uploaded. Error number: " . $_FILES['task_pic']['error'];
				}
			}
			if(empty($errors))
			{
				require ('includes/login_functions.inc.php');
				redirect_user('snow_admin.php');
				exit;
			}
		}
		else if( $purpose == "description" )
		{
			$des = (!empty($_POST['description']))? $_POST['description']:'';
			$s = mysqli_prepare($dbc, "UPDATE snow_removal SET description = ? WHERE task_number = $task_id");
			mysqli_stmt_bind_param($s, "s", $des);
			mysqli_stmt_execute($s);
			mysqli_stmt_close($s);
			if(empty($errors))
			{
				require ('includes/login_functions.inc.php');
				redirect_user('snow_admin.php');
				exit;
			}
		}
	}

	$page_title = 'Snow Removal';
	include ('includes/header.html');
	if(!empty($errors))
	{
		foreach ($errors as $msg)
		{ // Print each error.
			echo '<p class = "error"> - ' . $msg . '</p>';
		}
	}
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
	/*window.addEventListener("load", initAreas);*/
    
    // This function manage the Areas box. If click +, show all, if -, hide all
	function showAreas(){

		var area = document.getElementById("area-toggle");
		var sign = document.getElementById("area-icon").innerText;

		if(sign == "+"){
			area.style.display="block";
			document.getElementById("area-icon").innerText = "-";
		}
		else if (sign == "-"){
			area.style.display="none";
			document.getElementById("area-icon").innerHTML = "&#43;";
		}
	}
    
    /*function initAreas(){
        
    }*/
    
	function setAreas()
	{
		document.getElementById("areas").innerHTML = '';
		var s = document.getElementById("sections").value;
		var l = document.getElementById("locations").value;
		var res;
		var str;
		var area_list;
		var valtext;
		var xmlhttp;

		$.ajax({
			type:"GET",
			url: "get_areas_new.php",
			data: {
				s: s,
				l: l
			},
			dataType: "text",
			success: function(data){
				str = data;
				res = str.split("::");
				area_list = document.getElementById("areas");
				area_list.length = 0;
				for(var i = 0; i < res.length; i++)
				{
					var div = document.createElement('div');
					valtext = res[i].split("||");
					var opt = document.createElement('input');
					opt.value = valtext[0];
					//opt.textContent = valtext[1];
					opt.name = "area-loc";
					opt.type = "checkbox";
					opt.class = "area-loc";
                    opt.id="areas-"+valtext[0];
					var lab = document.createElement('label');
					lab.for= '"'+valtext[0]+'"';
					lab.textContent = valtext[1];
					div.appendChild(opt);
					div.appendChild(lab);
					area_list.appendChild(div);
				}
			}

		});


	}

	function toggle(source) {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i] != source)
            checkboxes[i].checked = source.checked;
    }
}

	function sendCheckedAreas(){
		var checkboxes = document.getElementsByName("area-loc");
		var jsarray = [];
		for (var i = 0; i < checkboxes.length; i++){
			if (checkboxes[i].checked){
				jsarray.push(checkboxes[i].value);
			}
		}
		console.log(jsarray);
		$.post( $("#snowForm").attr("action"),
					 $('#arr').val(JSON.stringify(jsarray)),
				);
	}
    
    
    
    
    
    
    
    
    
    
    
    
    function update(){
        updateEquipment();
        updateArea();
    }
	
    function updateEquipment(){
		var select = document.getElementById("employee_select");
		var equipments = document.getElementById("equip");
		var workerId = select.options[select.selectedIndex].value;
		$.ajax({
			type:'post',
			url: 'update_equipment_ajax.php',
			data:{
				workerId:workerId
			},
			dataType: "text",
			success: function(data){
				//console.log(data);
				equipments.options[data-1].selected = true;
			}
		});
	}
    
    function updateArea(){
        var select = document.getElementById("employee_select");
		var workerId = select.options[select.selectedIndex].value;
        
        
        document.getElementById("areas").innerHTML = "";
        
        //console.log(workerId);
		
       $.ajax({
			type:'post',
			url: 'update_area_ajax.php',
			data:{
				workerId:workerId
			},
			dataType: "text",
			success: function(data){
                var res = data.split(";");
                var sec = res[0];
                var loc = res[1];
                var areaArray = res[2].split(",");
                var idArray = res[3].split(",");
                var allAreaArray = res[4].split(",");
                
                /*console.log(sec);
                console.log(loc);
                console.log(areaArray);
                console.log(idArray);
                console.log(allAreaArray);*/
                
                area_list = document.getElementById("areas");
				for(var i = 0; i < idArray.length; i++)
				{
					var div = document.createElement('div');
					var opt = document.createElement('input');
					opt.value = idArray[i];
					//opt.textContent = valtext[1];
					opt.name = "area-loc";
					opt.type = "checkbox";
					opt.class = "area-loc";
                    opt.id="areas-"+idArray[i];
					var lab = document.createElement('label');
					lab.for= '"'+idArray[i]+'"';
					lab.textContent = allAreaArray[i];
					div.appendChild(opt);
					div.appendChild(lab);
					area_list.appendChild(div);
				}
                
                document.getElementById("sections-" + sec).selected = true;
                document.getElementById("locations-" + loc).selected = true;
                
                areaArray.forEach(current => {
                    if (document.getElementById("areas-" + current) != null)
                        document.getElementById("areas-" + current).checked = true;    
                });
                
			}
		});
    }

	
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    function insertNewEquip(){
		//console.log("data");
		var equipName = document.getElementById("new_equip").value;
		$.ajax({
			type:'post',
			url: 'update_equipment_ajax.php',
			data:{
				insertEquip:equipName
			},
			dataType: "text",
			success: function(data){
				//console.log(data+"--Equipment Added");
			}
		});
	}

	$(function(){
    $('input[id="rad"]').click(function(){
        var $radio = $(this);

        // if this was previously checked
        if ($radio.data('waschecked') == true)
        {
            $radio.prop('checked', false);
            $radio.data('waschecked', false);
        }
        else
            $radio.data('waschecked', true);

        // remove was checked from other radios
        $radio.siblings('input[name="rad"]').data('waschecked', false);
    });
});
function showNewEquipBox(){
	//console.log(document.getElementsByClassName("new_equip"));
	document.getElementById("new_equip").style.display = "inline";
	document.getElementById("new_equip_sub").style.display = "inline";
}
</script>
<body onload = "updateEquipment()">
	<div>
	<img src="norse.jpeg" style="width:150px; position: absolute; right: 3.5%; top: 3.5%;">
	</div>
   <div class="container">
<p><a href = "snow_admin.php">List tasks</a></p><br>
<form id="snowForm" action = "enter_snow_removal_task_development.php?purpose=<?php echo $purpose;?>&task=<?php echo $task_id;?>" enctype="multipart/form-data" method = "POST">
<?php
	echo ($purpose == "image")? '<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />':'';
	echo '<fieldset><legend> ';
	echo ($purpose == "image")? 'Upload an image:':'';
	echo ($purpose == "create")? '<p style="text-align: center; font-size: 1.5em; font-weight: bold; color: #996600; margin-bottom: 15px;">Create a snow removal task:</p>':'';
	echo ($purpose == "description")? 'Edit Description:':'';
	echo '<span class="error">* is required</span></legend>';
	echo '<table class="table table-striped table-bordered table-hover table-condensed" style = "margin:auto;">';
	if($purpose == "create")
	{
		echo '<tr><th>Worker:<span class="error"><sup>*</sup></span></th><td><select name = "employee_select" id = "employee_select" onchange = "update()">';
		$q = "SELECT user_id, first_name, last_name FROM users WHERE snowteam ='1' ORDER BY last_name";
		$rs = @mysqli_query($dbc, $q);
		while($row = mysqli_fetch_array($rs))
		{
			echo '<option ';
			echo ($user_id == $row['user_id'])? 'selected ':'';
			echo 'value = "' . $row['user_id'] . '">' . $row['last_name'] . ', ' . $row['first_name'] . '</option>';
		}
		echo '</select></td></tr>';
		mysqli_free_result($rs);

        
        
        
        
        
        
        
        $query = "SELECT * FROM snow_removal WHERE employee = $user_id ORDER BY start DESC";
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
        
        
        
        $query = "SELECT area FROM snow_removal WHERE employee = $user_id AND start > '$timeInterval'";
        $result = mysqli_query($dbc, $query);
        if (!$result){
            die("Third query for area failed!" . mysqli_error($dbc));
        }
        
        $areaArray = array();
        while($row = mysqli_fetch_array($result)){
            array_push($areaArray, $row[0]);
        }
        
        
        // Section
		echo '<tr><th>Section:<span class="error">*</span></th>';
		echo '<td><select name = "sections" id = "sections" onchange="setAreas();">';

		$q = "SELECT DISTINCT section FROM areas_new WHERE Section != 0 ORDER BY section";
		$rs = @mysqli_query($dbc, $q);
		while($row = mysqli_fetch_array($rs))
		{
			echo '<option ';
			
            /*if (isset($sec) AND $sec == $row[0])
                echo 'selected ';
            else */if ($row[0] == $section)
                echo 'selected ';
            
			echo 'id = "sections-' . $row[0] . '" value = "' . $row[0] . '">' . $row[0] . '</option>';
		}
		mysqli_free_result($rs);
		echo '</select></td>';
        
        
        // Location
		echo '<tr><th>Location:<span class="error">*</span></th>';
		echo '<td><select name = "loacations" id = "locations" onchange="setAreas();">';

        $q = "SELECT DISTINCT location FROM areas_new WHERE location!='' ORDER BY location";
        $rs = @mysqli_query($dbc, $q);
        while($row = mysqli_fetch_array($rs))
        {
            echo '<option ';
            if ($row[0] == $location)
                echo 'selected ';
            echo 'id = "locations-' . $row[0] . '"value = "' . $row[0] . '"> '.$row[0].'</option>';
        }

		mysqli_free_result($rs);
		echo '</select></td>';
        

		echo '</tr>';
		echo '<tr><th ><span id = "area-icon" style= "font-size:20px;" onclick="showAreas();">&#43;</span> Areas:<span class="error">*</span></th>';

		echo '<td id = "area-toggle" style = "display:inline"><input type="checkbox" onclick="toggle(this);" />
        <b>Toggle All Checkboxes</b>
        <br><br>
        <div id = "areas" name = "areas" size="12">';
        
        
        
        $query2 = "SELECT * FROM areas_new WHERE section = " . $section . " AND location = '" . $location . "' ORDER BY area ASC";
        $result2 = mysqli_query($dbc, $query2);
        if (!$result2){
            die("QUERY FAILED! " . mysqli_error($dbc));
        }
        while ($row2 = mysqli_fetch_assoc($result2)){
            echo "<input name = 'area-loc' type = 'checkbox' value = '" . $row2['area_id'] . "' id = 'areas-" . $row2['area_id'] . "'";
            if (in_array($row2['area_id'], $areaArray))
                echo " checked";
            echo "><label>" . $row2['area'] . "</label><br>";
        }
        echo '</div>';
        echo '</td>';
		echo '</tr>';
		
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        echo '<tr><th>Equipment:</th>';
		echo '<td><select name = "equip" id = "equip">';

			$sql = "SELECT * FROM snow_removal_equipment";
			$eq = mysqli_query($dbc, $sql);
			while($row = mysqli_fetch_assoc($eq))
			{
				echo '<option ';
			 	echo 'value = "' . $row['id'] . '"> '.$row['equipment'].'</option>';
			}

		// echo '<option value = "1">Massey w/plow</option>';
		// echo '<option value = "2">4115 Tractor</option>';
		// echo '<option value = "3">1246 Gator</option>';
		// echo '<option value = "4">1445</option>';
		// echo '<option value = "5">0046</option>';
		// echo '<option value = "6">Cub Cadet</option>';
		// echo '<option value = "7">0936 W/Plow and V-Box</option>';
		// echo '<option value = "8">1346 Gator</option>';
		// echo '<option value = "9">1036 Gator W/V-Box</option>';
		// echo '<option value = "10">0536</option>';
		// echo '<option value = "11">1536 Truck W/Plow and V-Box</option>';
		// echo '<option value = "12">Shovels</option>';
		// echo '<option value = "13">9436</option>';
		// echo '<option value = "14">Cart or E-gator</option>';
		// echo '<option value = "15">Truck 0937</option>';
		// echo '<option value = "16">Truck 0636</option>';
		// echo '<option value = "17">Truck 9736</option>';
		// echo '<option value = "18">Truck 0846</option>';
		// echo '<option value = "19">Ventrac</option>';
		// echo '<option value = "20">Truck 1182</option>';
		// echo '<option value = "21">John Deere 1445</option>';
		// echo '<option value = "21">John Deere Tractor 0451</option>';
		// echo '<option value = "21">John Deere Mower 0553</option>';
		// echo '<option value = "22">International Truck 1636</option>';
		// echo '<option value = "23">Gator 1132 </option>';
		echo '</select>';
		echo '  <button type="button" id = "new_equip_btn" onClick="showNewEquipBox()">New</button>  ';
		echo '<input type = "text"  id = "new_equip" style = "display:none">';
		echo '<button type="button" id = "new_equip_sub" style = "display:none"onClick="insertNewEquip()">Submit</button>  ';
		echo '</td>';
		echo '</tr>';
		echo '<tr><th>Description:</th>';
		echo '<td><textarea style = "resize:none;" name = "description" id="description" maxlength="255" rows="5" cols="40"></textarea></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<th>Details:</th>';
		echo '<td>Salted?   <input type = "radio" style = "margin-right:10px;" name = "salted" id = "rad" > Plowed?   <input type = "radio" name = "plowed" id = "rad" style = "margin-right:10px;" ></td>';
		echo '</tr>';
	}
	else if($purpose == "image")
	{
		echo '<tr>';
		echo '<th>Picture</th>';
		echo '<td><input type = "file" name = "task_pic" id = "task_pic" accept = "image/*, video/*"></td>';
		echo '</tr>';
	}
	else if($purpose == "description")
	{
		$query = "SELECT description from snow_removal WHERE task_number = $task_id LIMIT 1";
		$rs = @mysqli_query($dbc, $query);
		$row = mysqli_fetch_array($rs);
		echo '<tr>';
		echo '<th>Description:<span class="error"><sup>*</sup></span></th>';
		echo '<td><textarea style = "resize:none;" name = "description" id="description" maxlength="255" rows="5" cols="40">' . $row['description'] . '</textarea></td>';
		echo '</tr>';
		mysqli_free_result($rs);
	}
?>
		<input type="hidden" name="arr" id="arr" value=""/>
		<tr><td colspan="2"><input type="submit" class="btn btn-default" style="width:40%;display:block;margin:auto;" value="Submit" onclick="sendCheckedAreas()"></td></tr>
	</table>
</fieldset>
</form>
</div>
</body>
<?php
	mysqli_close($dbc);
	include ('includes/footer.html');
?>
