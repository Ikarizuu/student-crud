<?php
// Start session to persist success/error banners
session_start();
include 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Call the Google Sheets API with a delete payload
    $response = callGoogleSheetAPI([
        'action' => 'delete',
        'id' => $id
    ], true);
    
    // Check if Google Sheets responded with a success status
    if (isset($response['status']) && $response['status'] === "success") {
        $_SESSION['msg'] = "Student record deleted successfully!";
    } else {
        $_SESSION['err'] = "Deletion failed: " . ($response['msg'] ?? 'Unexpected response format');
    }
} else {
    $_SESSION['err'] = "No valid student ID specified for deletion.";
}

// Route back to the clean dashboard
header("Location: index.php");
exit();
?>