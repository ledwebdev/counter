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

$sql_entries = "select date_format(entry,'%Y-%m-%d_%H-%i') as min_entry, " .
        "date_format(entry,'%Y-%m-%d') as day_entry, count(id) as min_count " .
        "from visits where entry > '" . $epoch . "' group by day_entry, min_entry;";

$sql_exits = "select date_format(depart,'%Y-%m-%d_%H-%i') as min_exit, " .
        "date_format(depart,'%Y-%m-%d') as day_exit, " .
        "count(id) as min_count from departures " .
	"where depart > '" . $epoch . "' " .
        "group by day_exit, min_exit;";

$sql_entries_d = "select date_format(entry,'%Y-%m-%d_%H-%i') as min_entry, " .
        "date_format(entry,'%Y-%m-%d') as day_entry, count(id) as min_count " .
        "from visits where entry>=curdate() " .
        "group by day_entry, min_entry;";

$sql_exits_d = "select date_format(depart,'%Y-%m-%d_%H-%i') as min_exit, " .
        "date_format(depart,'%Y-%m-%d') as day_exit, count(id) as min_count " .
        "from departures where depart>=curdate() " .
        "group by day_exit, min_exit;";

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

function minsSoFar(&$exits,$day,$min) {
    $cur_min = date_create_from_format('Y-m-d_H-i',$min);
    $min_cnt = 0;
    foreach ($exits as $key=>$exit) {
	    if ($exit["day_exit"] == $day) {
		    $exit_min = date_create_from_format("Y-m-d_H-i",
			    $exit["min_exit"]);
		    
		    if ($cur_min >= $exit_min) {
			    $min_cnt += intval($exit["min_count"]);
			    unset($exits[$key]);
		    } else {
			    break;
		    }//if

            }//if
    }//foreach

    return $min_cnt;
}

function resultToArray($sql_results) {
    $rows = [];
    foreach ($sql_results as $result) {
	$rows[] = $result;
    }//while
    return $rows;
}

function sortOutMins($conn, $sql_e, $sql_d) {
    $sentries = $conn->query($sql_e);
    $entries = resultToArray($sentries);
    $sexits = $conn->query($sql_d);
    $exits = resultToArray($sexits);
    $high_min = 0;
    $hmins = 0;
    $prev_day = "";

    foreach ($entries as $entry) {
	    if ($prev_day != $entry["day_entry"]) $hmins = 0;
	    $hmins += intval($entry["min_count"]);
	    $dmins = minsSoFar($exits,$entry["day_entry"],
		    $entry["min_entry"]);
	    $hmins -= $dmins;
	    if ($hmins > $high_min) {
		    $high_min = $hmins;
		    $hmin = $entry["min_entry"];
	    }//if
	    $prev_day = $entry["day_entry"];
    }//foreach

    $high_pt = array_merge(array("high_min" => $high_min),
	    array("hmin" => $hmin));
    return $high_pt;
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
    //get highwater count for all dates since epoch
    $high_pt = sortOutMins($conn, $sql_entries, $sql_exits);
    $high_min = $high_pt["high_min"];
    $hmin = date_create_from_format("Y-m-d_H-i", $high_pt["hmin"]);

    //get highwater count for today
    $high_pt_d = sortOutMins($conn, $sql_entries_d, $sql_exits_d);
    $high_min_d = $high_pt_d["high_min"];
    $hmin_d = date_create_from_format("Y-m-d_H-i", $high_pt_d["hmin"]);

    echo "<h4>Highest number in building (so far): <em>$high_min</em></h4>\n";
    echo "<h4>Recorded at: <em>" . $hmin->format('Y-m-d h:i A') . "</em></h4>\n";
    echo "<h4>Highest number today (so far):<em> $high_min_d</em></h4>\n";
    echo "<h4>Recorded at: <em>" . $hmin_d->format('h:i A') . "</em></h4>\n";
    echo "<table class=report>\n";
    echo "<tr><th>Date</th>\n";
    echo "<th>Hour Begins</th>\n";
    echo "<th>Visits (per hour)</th>\n";
    echo "<th>Exits (per hour)</th>\n";
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

    fputcsv($fp, array("Date","Hour","Visits (per hour)","Exits (per hour)","In-Building"));
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
<p>Numbers generated: <i><?php echo date("Y-m-d h:i:s A") ?></i></p>
<form method="post">
   <input type="submit" name="export" class="export" 
       value="Export data for all dates" />
</form>
</body>

</html>
<?php endif; ?>
