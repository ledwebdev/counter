<?php
header('Access-Control-Allow-Origin: http://MODIFY', false);
header('Content-Type: application/json');
$booking_types = ["PCs","Rooms","Desks"];
date_default_timezone_set('America/New_York');
$servername = "localhost";
$username = "MODIFY";
$password = "MODIFY";
$dbname = "MODIFY";

$oauth_server = "https://MODIFY-ca.libcal.com/1.1";
$client_id = "MODIFY";
$client_secret = "MODIFY";
$content="grant_type=client_credentials&client_id=" . $client_id .
    "&client_secret=" . $client_secret;

//class for Library space bookings
class LSpace {
    // constructor
    public function __construct($space_id,$cat,$counter,$capacity) {
        $this->space_id = $space_id;
        $this->category = $cat;
        $this->counter = $counter;
        $this->capacity = $capacity;
    }//public
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
//
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql_hours = "select dow, cap, late, open, closed from opening_hours where dow=";

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

function getBookingIds($conn) {
	$booking_sql = "select distinct space_id " .
		"from bookings";
        $result = $conn->query($booking_sql);
	$all_results = $result->fetch_all(MYSQLI_ASSOC);
	return $all_results;
}

function getCapacity($conn,$space_id,$cat) {
	$booking_sql = "select capacity from bookings " .
		"where space_id=" .
		$space_id . " and booking_type='" .
		$cat . "'";
        $result = $conn->query($booking_sql);
        $row = $result->fetch_assoc();
	return intval($row["capacity"]);
}

function getToken($oauth_server, $content) {
	$token = null;
	try {
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $oauth_server . "/oauth/token",
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $content
		));

		$response = curl_exec($curl);
		curl_close($curl);

		$obj = json_decode($response);
		if (isset($obj->access_token)) $token = $obj->access_token;
	} catch (Exception $e) {}

	return $token;
}

function getBookings($oauth_server, $access_token, $resource_id) {
	$obj = null;
	$header = array("Authorization: Bearer {$access_token}");
	try {

		$curl = curl_init();

		$url = $oauth_server . "/space/bookings?lid=" . 
			$resource_id . "&date=" . date("Y-M-d");

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true
		));

		$response = curl_exec($curl);
		curl_close($curl);

		$obj = json_decode($response);
	} catch (Exception $e) {}

	return $obj;
}

function getCategory($cat_name,$booking_types) {
	foreach ($booking_types as $cat) {
		if (strstr($cat_name,$cat)) return $cat;
	}//foreach
	return null;
}

function calCounts($bookings,$booking_types) {
	$now = date("Y-m-d\TH:i:sP");

	$bspace = [];
	foreach($bookings as $key => $value) {
		$cat = getCategory($value->category_name,$booking_types);
		$from_date = $value->fromDate;
		$date_start = date_create_from_format("Y-m-d\TH:i:sP",$from_date);
		$to_date = $value->toDate;
		$date_end = date_create_from_format("Y-m-d\TH:i:sP",$to_date);
		if ($from_date <= $now && $to_date >= $now) {
			if (isset($bspace[$cat])) {
				$bspace[$cat] = $bspace[$cat] + 1;
			} else {
				$bspace[$cat] = 1;
			}//if
		}
	}//foreach

	return $bspace;
}


$dow = getDoW();
$sql = $sql_hours . $dow . ";";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$limit = $row["cap"];

$late = $row["late"];
$open = getTimeValue($row["open"]);
$closed = getTimeValue($row["closed"]);

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
$token = getToken($oauth_server, $content);

$bspaces = [];
if (!is_null($token)) {
	$bparms = getBookingIds($conn);
	foreach($bparms AS $row) {
		$bookings = getBookings($oauth_server, 
			$token, $row["space_id"]);
		if (!is_null($bookings)) {
			$bspaces[$row["space_id"]] = calCounts($bookings,$booking_types);
		}//if
	}//foreach
}//if

$lspaces = [];
$bparms = getBookingIds($conn);
foreach($bparms AS $row) {
	foreach ($booking_types as $cat) { 
		$space_id = $row["space_id"];
		$capacity = getCapacity($conn,$space_id,$cat);
		$counter = 0;
		if (isset($bspaces[$space_id])) { 
			if (isset($bspaces[$space_id][$cat])) {
				$counter = $bspaces[$space_id][$cat];
			}//if
		} //if
		$lspace = new LSpace(intval($space_id),$cat,$counter,$capacity);
		array_push($lspaces,$lspace);

	}//foreach
}//foreach

$conn->close();
//print_r($lspaces);
$obj = (object) [
	'total' => intval($total),
	'limit' => intval($limit), 
	'bookings' => $lspaces
];
echo json_encode($obj);

?>
