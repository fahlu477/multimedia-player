<?php
// Set timezone if needed
date_default_timezone_set('Asia/Jakarta'); // Adjust to your timezone

// Display current server information
echo "<h2>Server Time Information</h2>";
echo "<p><strong>Current Server Date:</strong> " . date('Y-m-d') . "</p>";
echo "<p><strong>Current Server Time:</strong> " . date('H:i:s') . "</p>";
echo "<p><strong>Current Server DateTime:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Timestamp:</strong> " . time() . "</p>";

// Test database connection
try {
    include_once 'config.php';
    echo "<p><strong>Database Connection:</strong> <span style='color: green;'>✓ Connected</span></p>";
    
    // Test query
    $stmt = $conn->query("SELECT GETDATE() as ServerDateTime");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Database Server Time:</strong> " . $result['ServerDateTime'] . "</p>";
    
} catch (Exception $e) {
    echo "<p><strong>Database Connection:</strong> <span style='color: red;'>✗ Failed - " . $e->getMessage() . "</span></p>";
}

// Auto refresh every 5 seconds
echo "<script>setTimeout(function(){ location.reload(); }, 5000);</script>";
?>
