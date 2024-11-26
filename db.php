<?php
//$servername = "sql308.infinityfree.com";
//$username = "if0_37546030";
//$password = "7aMMPngagKbIOK";
//$dbname = "if0_37546030_school_reservation";

$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "school_reservation";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
