<!DOCTYPE html>
<html>
<head>
<title>Statistiche OWA</title>
<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Droid Sans'>
<style>
	* {
		font-family: 'Droid Sans';
		font-size: 16px;
	}
	
	select, input, button {
		font-weight: bold;
	}
	
	pre {
		display: inline;
		margin: 0;
	}
</style>
</head>
<body>

<?php
//conf vars
$max_results = "1000";

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
// ?site_id=2fda3234&uri=path-name&date_start=20230101&date_end=20230131
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
	$result_visits_rows = $result_visits->num_rows;
	
	//SELECT * FROM `owa_request` JOIN `owa_document` WHERE `owa_request`.`site_id` = '1234567890' AND `yyyymmdd` BETWEEN 20230322 AND 20230329 AND `owa_request`.`document_id` = `owa_document`.`id` AND `owa_document`.`uri` LIKE '%%' group by `owa_document`.`url`
	$sql_uniquepage = "SELECT * FROM `owa_request` JOIN `owa_document` WHERE `owa_request`.`site_id` = '".$site_id."' AND `yyyymmdd` BETWEEN ".$date_start." AND ".$date_end." AND `owa_request`.`document_id` = `owa_document`.`id` AND `owa_document`.`uri` LIKE '%".$uri."%' group by `owa_document`.`url`";
	$result_uniquepage = $conn->query($sql_uniquepage);
	$result_uniquepage_rows = $result_uniquepage->num_rows;
	
  if ($result_pageviews->num_rows > 0) {
	  // output data of each row
	  $sql_result_pageviews = '<span id=result>'.$result_pageviews_rows." Page Views | ".$result_visits_rows." Visits | ".$result_uniquepage_rows." Unique Page Views</span><br/><br/>\n";
	  $sql_result_pageviews .= "<pre>Date                            | Uri </pre><br/>";
	  if ($result_pageviews->num_rows < $max_results) {
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
echo '<form action="?" method="get" id="form_stats">
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
//echo $sql_pageviews."<br>".$sql_visits."<br>";
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
