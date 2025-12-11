<?php
$conn = new mysqli("localhost", "root", "", "scm_predictive_db");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>