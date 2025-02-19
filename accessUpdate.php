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
//Read data from submitted form
require_once 'dbconnect.php';
// -- for closed account we have $closeid
// -- for approval of account we have $apprid
// -- for removal of an auth email we have $id

// -- delete from emp_details table --//
if(!empty($_GET['id']) && empty($_GET['closeid']) && empty($_GET['apprid']) && empty($_GET['actid'])){
    $sql = "DELETE FROM emp_details WHERE email = '".$_GET['id']."'";
    $result = $link ->query($sql);
    header("location: users.php");
    exit;
}
// -- deactivate account --//
elseif(empty($_GET['id']) && !empty($_GET['closeid']) && empty($_GET['apprid']) && empty($_GET['actid'])){
    $sql = "UPDATE users SET active = '0' WHERE email = '".$_GET['closeid']."'";
    $result = $link ->query($sql);
    header("location: users.php");
    exit;
}
// -- activate account --//
elseif(empty($_GET['id']) && empty($_GET['closeid']) && empty($_GET['apprid']) && !empty($_GET['actid'])){
    $sql = "UPDATE users SET active = '1' WHERE email = '".$_GET['actid']."'";
    $result = $link ->query($sql);
    header("location: users.php");
    exit;
}
// -- confirm request --//
elseif(empty($_GET['id']) && empty($_GET['closeid']) && !empty($_GET['apprid']) && empty($_GET['actid'])){
    $sql = "UPDATE users SET request_confirmed = '1' WHERE email = '".$_GET['apprid']."'";
    $result = $link ->query($sql);
    $sql_con = "UPDATE emp_details SET confirmed = '1' WHERE email = '".$_GET['apprid']."'";
    $result = $link ->query($sql_con);
    header("location: users.php");
    exit;
}
