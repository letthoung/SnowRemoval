<?php
	session_start(); // Start the session.

	// If no session value is present, redirect the user:
	// Also validate the HTTP_USER_AGENT!
	if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT']) )) 
	{
		require ('includes/login_functions.inc.php');
		redirect_user('index.php');
	}
	
function get_form($id, $table, $dbc)
{
	$gen_table = '';
	$query = "SELECT * FROM $table WHERE asset_id = $id";
	$result = @mysqli_query($dbc, $query);
	$q = "SELECT fgen_field_id FROM $table WHERE asset_id = $id LIMIT 1";
	$rs = @mysqli_query($dbc, $q);
	$row = mysqli_fetch_array($rs);
	$field_id = $row[0];
	mysqli_free_result($rs);
	$row = NULL;
	$q = "SELECT fgen_structure_id FROM fgen_fields WHERE fgen_field_id = $field_id LIMIT 1";
	$rs = @mysqli_query($dbc, $q);
	$row = mysqli_fetch_array($rs);
	$structure = $row[0];
	mysqli_free_result($rs);
	$rowq = NULL;
	$field_id = NULL;
	$q = "SELECT * FROM fgen_fields WHERE fgen_structure_id = $structure";
	$rs = @mysqli_query($dbc, $q);
	
	if(mysqli_num_rows($result) > 0)
	{
		$queries = [];
		$vals = [];
		$value = '';
		$n = '';
		$name = '';
		$max_rows = 0;
		$max_cols = 0;
		while($row = mysqli_fetch_array($rs))
		{
			$found = false;
			while( !$found && ($row2 = mysqli_fetch_array($result)) )
			{
				if($row2['fgen_field_id'] == $row['fgen_field_id'])
				{
					$n = $row2['field_value'];
					$found = true;
				}
			}
			if(!$found)
			{
				$queries[] = "INSERT INTO $table (fgen_field_id, asset_id, field_value) VALUES (" . $row['fgen_field_id'] . ",$id, '')"; 
				$n = '';
			}
			$vals[] = array($row['col'],$row['row'],$row['field_name'],$n);
			if($row['col'] > $max_cols)
			{
				$max_cols = $row['col'];
			}
			if($row['row'] > $max_rows)
			{
				$max_rows = $row['row'];
			}
			mysqli_data_seek($result, 0);
		}
		for($i = 0; $i < count($queries); $i++)
		{
			@mysqli_query($dbc, $queries[$i]);
		}
		$gen_table .= '<table class = "t02">';
		$gen_table .= '<tr><th>Unique Fields <a href="http://norsegis.nku.edu/edit_tool.php?purpose=epg&sort=n&s=0&labels=&p=2&shape_group=groups&form_used=&ts=&id='.$id.'" target="_blank">EDIT ID '.$id.'</th></tr>';
		$gen_table .= '</table>';
		$gen_table .= '<table class = "t02">';
		for($i = 0; $i <= $max_rows; $i++)
		{
			$gen_table .= '<tr>';
			for($j = 0; $j <= $max_cols; $j++)
			{
				$found = false;
				for($k = 0; $k < count($vals) && !$found; $k++)
				{
					if($i == $vals[$k][1] && $j == $vals[$k][0])
					{
						$name = $vals[$k][2];
						$value = $vals[$k][3];
						$found = true;
					}
				}
				$gen_table .= '<th>';
				$gen_table .= $name;
				$gen_table .= '</th><td>';
				if(filter_var($value, FILTER_VALIDATE_URL))
				{
					$gen_table .= '<a href = "' . $value . '" target = "_blank"> Click Here </a>';
				}
				else
				{
					$gen_table .= $value;
				}
				$gen_table .= '</td>';
				$name = '';
				$value = '';
			}			
			$gen_table .= '</tr>';
		}
		$gen_table .= '</table>';
	}
	return $gen_table;
}

function get_specific_form($structure, $dbc)
{
	$gen_table = '';	
	$q = "SELECT * FROM fgen_fields WHERE fgen_structure_id = $structure";
	$rs = @mysqli_query($dbc, $q);
	
	if(mysqli_num_rows($rs) > 0)
	{
		$max_rows = 0;
		$max_cols = 0;
		$values = [];
		while($row = mysqli_fetch_array($rs))
		{
			$vals[] = array($row['field_name'], $row['col'], $row['row']);
			
			if($row['col'] > $max_cols)
			{
				$max_cols = $row['col'];
			}
			if($row['row'] > $max_rows)
			{
				$max_rows = $row['row'];
			}
		}

		$gen_table .= '<table class = "t01">';
		$gen_table .= '<tr><th>Form</th></tr>';
		$gen_table .= '</table>';
		$gen_table .= '<table class = "t01">';
		for($i = 0; $i <= $max_rows; $i++)
		{
			$gen_table .= '<tr>';
			for($j = 0; $j <= $max_cols; $j++)
			{
				$found = false;
				for($k = 0; $k < count($vals) && !$found; $k++)
				{
					if($i == $vals[$k][2] && $j == $vals[$k][1])
					{
						$name = $vals[$k][0];
						$found = true;
					}
				}
				$gen_table .= '<td>';
				$gen_table .= ($found)? $name:'';
				$gen_table .= '</td>';
				$name = '';
			}			
			$gen_table .= '</tr>';
		}
		$gen_table .= '</table>';
	}
	return $gen_table;
}