<?php
// Initialize the session
if(session_status() == PHP_SESSION_NONE){
session_start();
}
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

//database connection codes
require_once "dbconnect.php";

//pick the client id and the module to add
$clientModule = $_POST['moduleSelect'];

// Check if the ID parameter is valid
if (isset($_GET['id'])) {
    $clientID2 = base64_decode($_GET['id']);
    if (!is_numeric($clientID2)) {
        die("Invalid ID.");
    }
    // Validate $leave_id against the database...
} else {
    die("No ID provided.");
}

//check if module has been picked
if(!empty($clientModule)){
    $sql_module = "INSERT INTO client_module (client_ref,mod_name,mod_status) VALUES ('$clientID2','$clientModule',(SELECT ID FROM module_status WHERE is_default = 1))";
    $result_module = $link ->query($sql_module);
    //display success message
    $_SESSION['errorModule'] = "Module added successfully";
    //return to the page after successfull addition
    $hashed_id = base64_encode($clientID2); 
    header('location:viewClient.php?id='.$hashed_id);
    exit;
}else{
    //display error if no module picked
    $_SESSION['errorModule'] = "Pick a module to add from drop-down list";
    $hashed_id = base64_encode($clientID2); 
    header('location:viewClient.php?id='.$hashed_id);
    exit;
}