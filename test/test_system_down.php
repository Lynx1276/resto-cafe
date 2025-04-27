<?php
// Include the functions.php file
require_once __DIR__ . '/../includes/functions.php'; // Adjust the path as needed

// Optional: Restrict access to this script (e.g., require admin login)
// require_admin(); // Uncomment if you want to restrict access

// Call the system down handler
echo "Starting system down simulation...\n";
$result = system_down_handler();

// Output the result for debugging
echo "Backup Files Created:\n";
print_r($result['backup_files']);
echo "\nNotification Status: " . ($result['notification_sent'] ? "Sent" : "Failed") . "\n";
