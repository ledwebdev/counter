<!DOCTYPE html>
<html>

<head>
    <title>
    Leddy Library Counter
    </title>
<style>
.incoming {
  display: block;
  width: 50%;
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
.outgoing {
  display: block;
  width: 50%;
  border-radius: 50px;
  background-color: red;
  color: white;
  padding: 14px 28px;
  font-size: 16px;
  cursor: pointer;
  text-align: center;
  margin-left: auto;
  margin-right: auto;
}
</style>
</head>

<body style="text-align:center;">

    <h1 style="color:green;">
    Leddy Library Counter
    </h1>

<?php

if (!isset($_SESSION)) {
    session_start();
}

date_default_timezone_set('America/New_York');
$servername = "localhost";
$username = "MODIFY";
$password = "MODIFY";
$dbname = "MODIFY";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql_visit = "INSERT INTO visits () VALUES();";
$sql_exit = "INSERT INTO departures() VALUES();";
$sql_hours = "select dow, cap, late, open, closed from opening_hours where dow=";

function updateTable($conn,$sql) {
	$is_ok = false;
	if ($conn->query($sql) === true) $is_ok = true;
	return $is_ok;
}

function getDoW() {
	$today = date("Y-m-d");
        return date('N', strtotime($today)) - 1;
}

function getTimeValue($strtime) {
	$bits = explode(" ",$strtime);
	return $bits[1];
}

function getCounter($conn, $table, $col, $dstr1, $dstr2) {
	$counter = 0;
	$range_sql = "select count(id) as counter from " .
		$table . " where " . $col . " between " .
		"'" . $dstr1 . "' and '" . $dstr2 . "';";
        $result = $conn->query($range_sql);
        $row = $result->fetch_assoc();
        $counter = $row["counter"];
	if ($counter < 0) $counter = 0;
	return $counter;
}

if(isset($_POST['entry'])) updateTable($conn, $sql_visit);
if(isset($_POST['exit'])) updateTable($conn, $sql_exit);
$dow = getDoW();
$sql = $sql_hours . $dow . ";";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$limit = $row["cap"];
$late = $row["late"];
$open = getTimeValue($row["open"]);
$closed = getTimeValue($row["closed"]);
if ($limit <= 0) die("no limit set for day!");

$late = true;
if ($row["late"] == 0) $late = false;

$date_open =  date_create_from_format("H:i:s", $open);
$date_closed =  date_create_from_format("H:i:s", $closed);
if ($late) $date_closed->modify('+1 day');

$entries = getCounter($conn, "visits","entry",
        $date_open->format('Y-m-d H:i:s'),
        $date_closed->format('Y-m-d H:i:s'));
$exits = getCounter($conn, "departures","depart",
	$date_open->format('Y-m-d H:i:s'),
	$date_closed->format('Y-m-d H:i:s'));

$total = $entries - $exits;
if ($total < 0) $total = 0;

echo "<h3>Counting hours: <em>" . $date_open->format('Y-m-d g:i:s A') . 
	"</em> to <em>" .  $date_closed->format('Y-m-d g:i:s A') . 
	"</em></h3>\n";
echo "<h3>Limit for building: <font style='color:red;'>" . $limit . 
	"</font></h3>\n";
echo "<h3>Incoming (so far): <font style='color:red;'>" . $entries . 
	"</font></h3>\n";
echo "<h3>Outgoing (so far): <font style='color:red;'>" . $exits . 
	"</font></h3>\n";
echo "<h3>Current # of visitors in building: <font style='color:red;'>" . 
	$total . "</font></h3>\n";
$conn->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['postdata'] = $_POST;
    unset($_POST);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>

<form method="post"> 
   <input type="submit" name="entry" class="incoming" value="Incoming"/>
   <input type="submit" name="exit" class="outgoing" value="Outgoing"/>
</form>

</body>

</html>
