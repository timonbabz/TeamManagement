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

require_once "dbconnect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_requests'])) {
    $approved = $_POST['approved'] ?? [];
    $comments = $_POST['comment'] ?? [];
    $request_ids = $_POST['request_id'] ?? [];
    $approved_by = $_SESSION['id']; // Change this to the actual admin username or ID from session
    
    foreach ($request_ids as $id) {
        $status = $approved[$id] ?? 'Pending';
        $comment = trim($comments[$id] ?? '');

        // Update each request
        $stmt = $link->prepare("UPDATE requests SET approved = ?, comment = ?, approved_by = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $comment, $approved_by, $id);
        $stmt->execute();
    }

    $_SESSION['success_msg2'] = "Requests updated successfully!";
    header("Location: budgets.php"); // Redirect to the dashboard or wherever appropriate
    exit();
}
?>
