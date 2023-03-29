<!DOCTYPE html>
<html>
<head>
	<title>Statistiche OWA</title>
	<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Droid Sans'>
	<style>
	body {
		font-family: 'Droid Sans';
		font-size: 16px;
	}
	
	select, input, button {
		font-family: 'Droid Sans';
		font-size: 16px;
		font-weight: bold;
	}
	</style>
</head>
<body>

<?php

//connect to database and made a first query for select sites
$db_host = "localhost"; // host name of the server housing the database
$db_user = "dbuser"; // database user
$db_psw = "dbpassword"; // database user's password
$db_name = "dbname"; // name of the database
	
// Create connection
$conn = new mysqli($db_host, $db_user, $db_psw, $db_name);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
	
//SELECT * FROM `owa_site`
$sql_sites = "SELECT * FROM `owa_site`";
$result_sites = $conn->query($sql_sites);
if ($result_sites->num_rows > 0) {
  // output data of each row
  while($row = $result_sites->fetch_assoc()) {
		$sql_result_sites[$row["site_id"]] = $row["name"];
  }
}
	
//check and initialize GET vars and make sql call
if (isset($_GET['site_id'])) {
	$site_id = $_GET['site_id'];
	$uri = $_GET['uri'];
	$date_start = $_GET['date_start'];
	$date_end = $_GET['date_end'];
		
  //SELECT * FROM `owa_request` JOIN `owa_document` WHERE `owa_request`.`site_id` = '1234567890' AND `yyyymmdd` BETWEEN 20230101 AND 20230103 AND `owa_request`.`document_id` = `owa_document`.`id` AND `owa_document`.`uri` LIKE '%%' order by `owa_document`.`id`
	$sql_pageviews = "SELECT * FROM `owa_request` JOIN `owa_document` WHERE `owa_request`.`site_id` = '".$site_id."' AND `yyyymmdd` BETWEEN ".$date_start." AND ".$date_end." AND `owa_request`.`document_id` = `owa_document`.`id` AND `owa_document`.`uri` LIKE '%".$uri."%'";
	$result_pageviews = $conn->query($sql_pageviews);
	$result_pageviews_rows = $result_pageviews->num_rows;
		
  //SELECT * FROM `owa_request` JOIN `owa_document` WHERE `owa_request`.`site_id` = '1234567890' AND `yyyymmdd` BETWEEN 20230101 AND 20230103 AND `owa_request`.`document_id` = `owa_document`.`id` AND `owa_document`.`uri` LIKE '%lucretia-borgia%' group by `owa_request`.`ip_address`
	$sql_visits = "SELECT * FROM `owa_request` JOIN `owa_document` WHERE `owa_request`.`site_id` = '".$site_id."' AND `yyyymmdd` BETWEEN ".$date_start." AND ".$date_end." AND `owa_request`.`document_id` = `owa_document`.`id` AND `owa_document`.`uri` LIKE '%".$uri."%' group by `owa_request`.`ip_address`";
	$result_visits = $conn->query($sql_visits);

  if ($result_pageviews->num_rows > 0) {
	  // output data of each row
	  $sql_result_pageviews = '<span id=result>'.$result_pageviews_rows." Page Views | ".$result_visits->num_rows." Visits</span><br/><br/>\n";
	  if ($result_pageviews->num_rows < 1000) {
		  while($row = $result_pageviews->fetch_assoc()) {
			$sql_result_pageviews .= gmdate("Y-m-d H:i:s", $row["timestamp"])." | ". $row["uri"]."<br/>\n";
		  }
	  }
	} else {
	  $sql_result_pageviews = "0 results";
	}
		
}//end if isset($_GET[])
	
//close connection to database
$conn->close();
	
//check content GET vars
if(!isset($uri)) $uri = "";
if(!isset($date_start)) $date_start = "";
if(!isset($date_end)) $date_end = "";
	
//create select prefix sites
$sites_select='<label for="site_id">Site ID:</label>
<select id="site_id" name="site_id" form="form_stats">'."\n";
foreach($sql_result_sites as $x => $val) {
	if($site_id == $x)
		$sites_select.="\t\t".'<option value="'.$x.'" selected>'.$val."</option>\n";
	else
		$sites_select.="\t\t".'<option value="'.$x.'">'.$val."</option>\n";
}
$sites_select.="\t".'</select> | ';

//create select prefix date
$today  = date("Ymd");
$lastweek = date("Ymd",mktime(0, 0, 0, date("m"),   date("d")-7,   date("Y")));
$thismonth = date("Ymd",mktime(0, 0, 0, date("m"),   date("01"),   date("Y")));
$lastmonth = date("Ymd",mktime(0, 0, 0, date("m")-1,   date("d"),   date("Y")));
$thisyear  = date("Ymd",mktime(0, 0, 0, date("01"),   date("01"),   date("Y")));
$lastyear  = date("Ymd",mktime(0, 0, 0, date("m"),   date("d"),   date("Y")-1));
$day_select='<select id="select_date" onchange="selectDate()">
	<option value="">Presets..</option>
	<option value="'.$today.'">today</option>
	<option value="'.$lastweek.'">last week</option>
	<option value="'.$thismonth.'">this month</option>
	<option value="'.$lastmonth.'">last month</option>
	<option value="'.$thisyear.'">this year</option>
	<option value="'.$lastyear.'">last year</option>
</select>';
	
//Display input form
echo '<form action="./stats.php" method="get" id="form_stats">
'.$sites_select.'
	<label for="uri">Uri:</label>
	<input type="text" id="uri" name="uri" size="12" value="'.$uri.'"> | 
	<label for="date_start">Data (yyyymmdd) from:</label>
	<input type="text" id="date_start" name="date_start" maxlength="8" size="8" value="'.$date_start.'"> 
	<label for="date_end">to:</label>
	<input type="text" id="date_end" name="date_end" maxlength="8" size="8" value="'.$date_end.'">
	'.$day_select.'
	<button type="submit" form="form_stats" value="Submit">Submit</button>
</form>';

//Display result
echo $sql_pageviews."<br>".$sql_visits."<br>";
echo "<br>\n".$sql_result_pageviews."<br><br>\n";

?>

<script>
// change input date with presets
function selectDate() {
  var x = document.getElementById("select_date").value;
  document.getElementById("date_start").value = x;
  document.getElementById("date_end").value = "<?php echo $today; ?>";
}
</script>

</body>
</html>
