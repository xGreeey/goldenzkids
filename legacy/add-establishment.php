<?php
$conn = new mysqli("localhost", "root", "", "goldenz5");

 session_start();

$company_id = $_SESSION['company_id'];


$sql = "INSERT INTO list_of_establishment (Company_ID, Establishment, Territory) VALUES ('$company_id', 'ADMIN', 'LOGOUT', NOW());";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$logging_out = $conn->query($sql);

?>