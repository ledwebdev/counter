<?php if(!isset($_POST['export'])) : ?>
<!DOCTYPE html>
<html>

<head>
    <title>
    Leddy Library Counter Report
    </title>
<style>
.export {
  display: block;
  width: 25%;
  border-radius: 50px;
  background-color: green;
  color: white;
  padding: 14px 28px;
  font-size: 16px;
  cursor: pointer;
  text-align: center;
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 25px;
}
table {
  margin-left: auto;
  margin-right: auto;
}
.report tr, td, th {
  border: 1px solid black;
}
</style>
</head>

<body style="text-align:center;">

    <h1 style="color:green;">
    Leddy Library Counter Report
    </h1>
<?php endif; ?>
<?php

date_default_timezone_set('America/New_York');
$servername = "localhost";
$username = "MODIFY";
$password = "MODIFY";
$dbname = "MODIFY";
$epoch = "2021-09-07 07:00:00";//MODIFY as well if not starting date

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql_hours = "select dow, cap, late, open, closed from opening_hours where dow=";
$sql_vstart = "select count(entry) as num, hour(entry) as hour, date(entry) as " .
	"date from visits where entry between ";
$sql_dstart = "select count(depart) as num, hour(depart) as hour, date(depart) " .
	"as date from departures where depart between ";
$sql_vend = " group by date(entry), hour(entry) order by date(entry), hour(entry);";
$sql_dend = " group by date(depart), hour(depart) order by date(depart), hour(depart);";

function getDoW() {
	$today = date("Y-m-d");
        return date('N', strtotime($today)) - 1;
}

function getTimeValue($strtime) {
	$bits = explode(" ",$strtime);
	return $bits[1];
}

function getVisits($conn, $sql_start, $sql_end, $dstr1, $dstr2) {
    $range_sql = $sql_start . "'" . $dstr1 .
	"' and '" . $dstr2 .
	"'" . $sql_end;
    //echo "=>" . $range_sql . "\n";
    $result = $conn->query($range_sql);
    return $result;
}

function sortOutOnSite($exits,$date,$hour,$walk_ins) {
    $walk_outs = 0;
    $num = 0;
    $slot = array();
    foreach ($exits as $exit) {
	    if ($exit["date"] == $date && $exit["hour"] <= $hour) {
		    $walk_outs += $exit["num"];
		    $num = $exit["num"];
            }//if
    }//foreach

    $slot = array_merge(array("walk_outs" => $walk_outs),array("num" => $num));
    return $slot;
}

$dow = getDoW();
$sql = $sql_hours . $dow . ";";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$open = getTimeValue($row["open"]);
$closed = getTimeValue($row["closed"]);

$late = true;
if ($row["late"] == 0) $late = false;

$date_open =  date_create_from_format("H:i:s", $open);
if(isset($_POST['export'])) $date_open = 
	date_create_from_format("Y-m-d H:i:s",$epoch);
$date_closed =  date_create_from_format("H:i:s", $closed);
if ($late) $date_closed->modify('+1 day');
$entries = getVisits($conn, $sql_vstart, $sql_vend, 
	$date_open->format('Y-m-d H:i:s'),
	$date_closed->format('Y-m-d H:i:s'));
$exits = getVisits($conn, $sql_dstart, $sql_dend, 
	$date_open->format('Y-m-d H:i:s'),
	$date_closed->format('Y-m-d H:i:s'));

$walk_ins = 0;
$last_date = "0-0-0";

if(!isset($_POST['export'])) {
    echo "<table class=report>\n";
    echo "<tr><th>Date</th>\n";
    echo "<th>Hour</th>\n";
    echo "<th>Visits</th>\n";
    echo "<th>Exits</th>\n";
    echo "<th>In-Building</th></tr>\n";
    
    foreach ($entries as $entry) {
	if ($last_date != $entry["date"]) $walk_ins = 0;
	$walk_ins += $entry["num"];
	$slot = sortOutOnSite($exits,$entry["date"],
		$entry["hour"],$walk_ins);
	$walk_outs = $slot["walk_outs"];
	echo "<tr><td>" .  $entry["date"] . "</td>\n";
	echo "<td>" .  date("g:i:s A",strtotime($entry["hour"] . 
		":00")) . "</td>\n";
	echo "<td>" .  $entry["num"] . "</td>\n";
	echo "<td>" .  $slot["num"] . "</td>\n";
	echo "<td><strong>" .  ($walk_ins - $walk_outs) . 
		"</strong></td></tr>\n";
	$last_date = $entry["date"];
    }//foreach
    echo "</table>\n";
} else {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="export.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $fp = fopen('php://output', 'w');

    fputcsv($fp, array("Daee","Hour","Visits","Exits","In-Building"));
    foreach ($entries as $entry) {
	if ($last_date != $entry["date"]) $walk_ins = 0;
	$walk_ins += $entry["num"];
	$slot = sortOutOnSite($exits,$entry["date"],
		$entry["hour"],$walk_ins);
	$walk_outs = $slot["walk_outs"];
	fputcsv($fp, array( 
		$entry["date"],
		date("g:i:s A",strtotime($entry["hour"] . ":00")),
		$entry["num"],
		$slot["num"],
		($walk_ins - $walk_outs)));
	$last_date = $entry["date"];
    }//foreach
}//if
$conn->close();
?>

<?php if(!isset($_POST['export'])) : ?>
<br clear="left"/>
<form method="post">
   <input type="submit" name="export" class="export" 
       value="Export data for all dates" />
</form>
</body>

</html>
<?php endif; ?>
