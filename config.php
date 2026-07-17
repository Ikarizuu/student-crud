<?php
// Your Google Apps Script Web App URL
define("GOOGLE_API_URL", "https://script.google.com/macros/s/AKfycbwWuiV1qxeHqCVVcuQKv3vvhTEoIB8WY36kKTNgnEiRnq4JvebRN5fJ5Xs7CYBHAu1O/exec");

// Helper function to send HTTP requests to Google Sheets API
function callGoogleSheetAPI($data, $is_post = true) {
    $ch = curl_init(GOOGLE_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Crucial for Google redirect responses
    
    if ($is_post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    } else {
        // GET Request formatting
        $query = http_build_query($data);
        curl_setopt($ch, CURLOPT_URL, GOOGLE_API_URL . "?" . $query);
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>