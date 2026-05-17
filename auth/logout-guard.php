<?php 
//to-do: fix log-out
require_once __DIR__ . '/../config/app.php';
 $d = strtotime("now");
 $time_of_event = date("Y-m-d h:i:s", $d);   

$sql = "INSERT INTO recording (Designation, Event, Time_Of_Event) VALUES ('GUARD', 'LOGOUT', '$time_of_event');";


$logging_out = $conn->query($sql);


if ($logging_out == TRUE) {
     $_SESSION = [];

    session_destroy();

     header('Location: ' . app_url('index.php'));
    exit();
} else {
    die("logout failure");
}

?>
