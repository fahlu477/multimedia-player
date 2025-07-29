<?php
// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

// Log all requests for debugging
$requestInfo = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'query' => $_GET,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
];
error_log("API Request: " . json_encode($requestInfo));

include_once '../config.php';

// Helper function for PHP compatibility
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Set proper headers for API responses
function setJsonHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
}

// API endpoint to get current video playlist
if (isset($_GET['action']) && $_GET['action'] === 'getCurrentVideoPlaylist') {
    setJsonHeaders();
    
    try {
        error_log("=== getCurrentVideoPlaylist API called ===");
        
        // Test database connection first
        if (!$conn) {
            throw new Exception("Database connection is null");
        }
        
        // Test a simple query first (SQL Server syntax)
        $testStmt = $conn->query("SELECT TOP 1 1 as test");
        $testResult = $testStmt->fetch();
        error_log("Database test query result: " . json_encode($testResult));
        
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        error_log("Date: $currentDate, Time: $currentTime");
        
        $playlist = [];
        
        // Simplified query first to test (SQL Server syntax)
        $sql = "SELECT TOP 5 lv.Path, lv.NamaFile FROM dbMultimedia.dbo.tbListDataVideo lv";
        error_log("Testing simple query: $sql");
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $testVideos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Simple query results: " . json_encode($testVideos));
        
        // Check if we have any videos at all
        if (empty($testVideos)) {
            error_log("No videos found in database!");
            echo json_encode([
                'playlist' => ['/multimedia-player/uploads/videos/LOGO LOOPING.mp4'],
                'error' => 'No videos in database'
            ], JSON_UNESCAPED_SLASHES);
            exit();
        }
        
        // Now try the main query (SQL Server syntax)
        $sql = "
            SELECT lv.Path, dm.Urutan, dm.ID, lv.NamaFile,
                   dm.JamMulai1, dm.JamAkhir1, dm.JamMulai2, dm.JamAkhir2, dm.JamMulai3, dm.JamAkhir3
            FROM dbMultimedia.dbo.tbDataMaster dm
            JOIN dbMultimedia.dbo.tbListDataVideo lv ON dm.videoId = lv.ID
            WHERE dm.Tanggal = ?
              AND dm.Status = 'Aktif'
              AND (
                    (dm.JamMulai1 IS NOT NULL AND dm.JamAkhir1 IS NOT NULL AND 
                     CAST(? AS TIME) >= CAST(dm.JamMulai1 AS TIME) AND CAST(? AS TIME) <= CAST(dm.JamAkhir1 AS TIME)) OR
                    (dm.JamMulai2 IS NOT NULL AND dm.JamAkhir2 IS NOT NULL AND 
                     CAST(? AS TIME) >= CAST(dm.JamMulai2 AS TIME) AND CAST(? AS TIME) <= CAST(dm.JamAkhir2 AS TIME)) OR
                    (dm.JamMulai3 IS NOT NULL AND dm.JamAkhir3 IS NOT NULL AND 
                     CAST(? AS TIME) >= CAST(dm.JamMulai3 AS TIME) AND CAST(? AS TIME) <= CAST(dm.JamAkhir3 AS TIME))
                  )
            ORDER BY dm.Urutan ASC, dm.ID DESC
        ";
        
        error_log("Executing main query with parameters: " . json_encode([$currentDate, $currentTime, $currentTime, $currentTime, $currentTime, $currentTime, $currentTime]));
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$currentDate, $currentTime, $currentTime, $currentTime, $currentTime, $currentTime, $currentTime]);
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Main query results: " . count($videos) . " videos found");
        error_log("Videos data: " . json_encode($videos));

        if (count($videos) > 0) {
            foreach ($videos as $vid) {
                $videoPath = $vid['Path'];
                
                // Remove leading slash if exists
                $videoPath = ltrim($videoPath, '/');
                
                // Ensure it starts with multimedia-player (PHP compatible way)
                if (strpos($videoPath, 'multimedia-player/') !== 0) {
                    $videoPath = 'multimedia-player/' . $videoPath;
                }
                
                // Add leading slash for web access
                $webAccessiblePath = '/' . $videoPath;
                
                $playlist[] = $webAccessiblePath;
                error_log("Added to playlist: $webAccessiblePath");
                
                // Also log the physical file check
                $physicalPath = $_SERVER['DOCUMENT_ROOT'] . $webAccessiblePath;
                $fileExists = file_exists($physicalPath);
                error_log("Physical file check: $physicalPath - Exists: " . ($fileExists ? 'YES' : 'NO'));
                
                if (!$fileExists) {
                    // Try alternative path
                    $altPath = dirname($_SERVER['SCRIPT_FILENAME']) . '/../' . $videoPath;
                    $altExists = file_exists($altPath);
                    error_log("Alternative path check: $altPath - Exists: " . ($altExists ? 'YES' : 'NO'));
                }
            }
        } else {
            error_log("No matching schedule found for current time");
            
            // Let's also check what schedules exist for today
            $debugSql = "
                SELECT dm.*, lv.NamaFile 
                FROM dbMultimedia.dbo.tbDataMaster dm 
                JOIN dbMultimedia.dbo.tbListDataVideo lv ON dm.videoId = lv.ID 
                WHERE dm.Tanggal = ? 
                ORDER BY dm.ID DESC
            ";
            $debugStmt = $conn->prepare($debugSql);
            $debugStmt->execute([$currentDate]);
            $allSchedules = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("All schedules for today: " . json_encode($allSchedules));
            
            // Check current time against each schedule
            foreach ($allSchedules as $schedule) {
                if ($schedule['Status'] === 'Aktif') {
                    error_log("Checking schedule ID {$schedule['ID']} ({$schedule['NamaFile']}):");
                    
                    // Check each slot
                    for ($i = 1; $i <= 3; $i++) {
                        $startTime = $schedule["JamMulai$i"];
                        $endTime = $schedule["JamAkhir$i"];
                        
                        if ($startTime && $endTime) {
                            $inSlot = ($currentTime >= $startTime && $currentTime <= $endTime);
                            error_log("  Slot $i: $currentTime between $startTime and $endTime = " . ($inSlot ? 'TRUE' : 'FALSE'));
                        }
                    }
                }
            }
            
            // Use default video
            $playlist[] = '/multimedia-player/uploads/videos/LOGO LOOPING.mp4';
        }
        
        error_log("Final playlist: " . json_encode($playlist));
        echo json_encode(['playlist' => $playlist], JSON_UNESCAPED_SLASHES);
        
    } catch (PDOException $e) {
        error_log("PDOException in getCurrentVideoPlaylist: " . $e->getMessage());
        error_log("PDO Error Info: " . json_encode($e->errorInfo ?? []));
        echo json_encode([
            'playlist' => ['/multimedia-player/uploads/videos/LOGO LOOPING.mp4'],
            'error' => 'Database error: ' . $e->getMessage()
        ], JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        error_log("Exception in getCurrentVideoPlaylist: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        echo json_encode([
            'playlist' => ['/multimedia-player/uploads/videos/LOGO LOOPING.mp4'],
            'error' => 'Error: ' . $e->getMessage()
        ], JSON_UNESCAPED_SLASHES);
    }
    exit();
}

// API endpoint to get current banner
if (isset($_GET['action']) && $_GET['action'] === 'getCurrentBanner') {
    setJsonHeaders();
    
    try {
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');

        error_log("getCurrentBanner API called - Date: $currentDate, Time: $currentTime");

        $sql = "
            SELECT TOP 1 IsiText
            FROM dbMultimedia.dbo.tblBanner
            WHERE Tanggal = ?
              AND Status = 'Aktif'
              AND CAST(? AS TIME) BETWEEN CAST(JamMulai AS TIME) AND CAST(JamAkhir AS TIME)
            ORDER BY JamMulai ASC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$currentDate, $currentTime]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Banner Query Result: " . json_encode($banner));

        if ($banner && !empty($banner['IsiText'])) {
            echo json_encode(['bannerText' => $banner['IsiText']]);
        } else {
            echo json_encode(['bannerText' => '']);
        }
        
    } catch (PDOException $e) {
        error_log("PDOException in getCurrentBanner: " . $e->getMessage());
        echo json_encode(['bannerText' => '', 'error' => 'Database error occurred']);
    } catch (Exception $e) {
        error_log("Exception in getCurrentBanner: " . $e->getMessage());
        echo json_encode(['bannerText' => '', 'error' => 'An error occurred']);
    }
    exit();
}

// API endpoint to check if video file exists
if (isset($_GET['action']) && $_GET['action'] === 'checkVideoFile') {
    setJsonHeaders();
    
    $videoPath = $_GET['path'] ?? '';
    if (empty($videoPath)) {
        echo json_encode(['exists' => false, 'error' => 'No path provided']);
        exit();
    }
    
    // Check multiple possible locations
    $possiblePaths = [
        $_SERVER['DOCUMENT_ROOT'] . $videoPath,
        dirname($_SERVER['SCRIPT_FILENAME']) . '/../' . ltrim($videoPath, '/'),
        dirname($_SERVER['SCRIPT_FILENAME']) . '/' . ltrim($videoPath, '/'),
    ];
    
    $foundPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $foundPath = $path;
            break;
        }
    }
    
    echo json_encode([
        'exists' => $foundPath !== null,
        'foundPath' => $foundPath,
        'checkedPaths' => $possiblePaths,
        'fileSize' => $foundPath ? filesize($foundPath) : 0
    ]);
    exit();
}

// Test endpoint to check database structure
if (isset($_GET['action']) && $_GET['action'] === 'testDatabase') {
    setJsonHeaders();
    
    try {
        $results = [];
        
        // Test connection
        $results['connection'] = $conn ? 'OK' : 'FAILED';
        
        // Test video table
        $stmt = $conn->query("SELECT COUNT(*) as count FROM dbMultimedia.dbo.tbListDataVideo");
        $result = $stmt->fetch();
        $results['videos_count'] = $result['count'];
        
        // Test schedule table
        $stmt = $conn->query("SELECT COUNT(*) as count FROM dbMultimedia.dbo.tbDataMaster");
        $result = $stmt->fetch();
        $results['schedules_count'] = $result['count'];
        
        // Test today's schedules
        $currentDate = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM dbMultimedia.dbo.tbDataMaster WHERE Tanggal = ?");
        $stmt->execute([$currentDate]);
        $result = $stmt->fetch();
        $results['today_schedules'] = $result['count'];
        
        // Get sample data
        $stmt = $conn->query("SELECT TOP 3 * FROM dbMultimedia.dbo.tbListDataVideo");
        $results['sample_videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("SELECT TOP 3 * FROM dbMultimedia.dbo.tbDataMaster WHERE Tanggal = ?");
        $stmt->execute([$currentDate]);
        $results['sample_schedules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multimedia Player</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100%;
            width: 100%;
            background-color: #000;
            font-family: 'Arial', sans-serif;
        }
        
        #videoPlayer {
            width: 100vw;
            height: calc(100vh - 60px);
            object-fit: cover;
            background-color: #000;
        }
        
        #bannerContainer {
            height: 60px;
            background: linear-gradient(90deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            color: white;
            display: flex;
            align-items: center;
            overflow: hidden;
            position: relative;
            border-top: 2px solid #333;
        }
        
        #runningText {
            white-space: nowrap;
            position: absolute;
            animation: marquee 180s linear infinite;
            padding-left: 100%;
            font-size: 3.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
            color: #f3cf03ff;
        }

        @keyframes marquee {
            0%   { transform: translateX(0%); }
            100% { transform: translateX(-100%); }
        }

        /* Debug info */
        #debugInfo {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            max-width: 300px;
            z-index: 1000;
            display: none;
        }

        /* Fullscreen styles */
        video:-webkit-full-screen {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }
        
        video:-moz-full-screen {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }
        
        video:-ms-fullscreen {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }
        
        video:fullscreen {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        /* Loading indicator */
        .loading-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 1.5rem;
            z-index: 10;
        }

        /* Error message */
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ff6b6b;
            font-size: 1.2rem;
            text-align: center;
            z-index: 10;
            background: rgba(0,0,0,0.8);
            padding: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="flex flex-col h-screen w-screen">
    <div class="relative flex-1">
        <video id="videoPlayer" autoplay playsinline muted>
            Your browser does not support the video tag.
        </video>
        <div id="loadingIndicator" class="loading-indicator" style="display: none;">
            Loading video...
        </div>
        <div id="errorMessage" class="error-message" style="display: none;">
            Video playback error. Please check your connection.
        </div>
    </div>

    <div id="bannerContainer" class="w-full">
        <div id="runningText" class="px-4">Loading banner...</div>
    </div>

    <!-- Debug info (press 'D' to toggle) -->
    <div id="debugInfo">
        <div id="debugContent"></div>
    </div>

    <script src="js/player.js"></script>
</body>
</html>
