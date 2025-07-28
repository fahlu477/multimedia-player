<?php
include_once '../config.php';

// API endpoint to get current video playlist
if (isset($_GET['action']) && $_GET['action'] === 'getCurrentVideoPlaylist') {
    header('Content-Type: application/json');
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    error_log("getCurrentVideoPlaylist API called.");
    error_log("Current Date (PHP): " . $currentDate);
    error_log("Current Time (PHP): " . $currentTime);

    $playlist = [];
    try {
        $sql = "
            SELECT lv.Path
            FROM dbMultimedia.dbo.tbDataMaster dm
            JOIN dbMultimedia.dbo.tbListDataVideo lv ON dm.videoId = lv.ID
            WHERE dm.Tanggal = ?
              AND dm.Status = 'Aktif'
              AND (
                    (? BETWEEN dm.JamMulai1 AND dm.JamAkhir1) OR
                    (? BETWEEN dm.JamMulai2 AND dm.JamAkhir2) OR
                    (? BETWEEN dm.JamMulai3 AND dm.JamAkhir3)
                  )
            ORDER BY dm.ID DESC;
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$currentDate, $currentTime, $currentTime, $currentTime]);
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("SQL Query executed. Number of videos found: " . count($videos));
        error_log("Raw SQL Result: " . json_encode($videos));

        if (count($videos) > 0) {
            // Ada jadwal video
            foreach ($videos as $vid) {
                $webAccessiblePath = '/multimedia-player/' . $vid['Path'];
                $playlist[] = $webAccessiblePath;
            }
        } else {
            $playlist[] = '/multimedia-player/uploads/videos/LOGO LOOPING.mp4';
        }
        
        echo json_encode(['playlist' => $playlist]);
    } catch (PDOException $e) {
        error_log("PDOException in getCurrentVideoPlaylist: " . $e->getMessage());
        echo json_encode(['playlist' => ['/multimedia-player/uploads/videos/LOGO LOOPING.mp4']]);
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'getCurrentBanner') {
    header('Content-Type: application/json');
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    error_log("getCurrentBanner API called.");
    error_log("Current Date (PHP): " . $currentDate);
    error_log("Current Time (PHP): " . $currentTime);

    try {
        $stmt = $conn->prepare("
            SELECT TOP 1 IsiText
            FROM tblBanner
            WHERE Tanggal = ?
              AND Status = 'Aktif'
              AND (? BETWEEN JamMulai AND JamAkhir)
            ORDER BY JamMulai ASC
        ");
        $stmt->execute([$currentDate, $currentTime]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Banner Query Result: " . json_encode($banner));

        if ($banner) {
            echo json_encode(['bannerText' => $banner['IsiText']]);
        } else {
            echo json_encode(['bannerText' => '']);
        }
    } catch (PDOException $e) {
        error_log("PDOException in getCurrentBanner: " . $e->getMessage());
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
    <title>Automatic Video Player</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100%;
            width: 100%;
            background-color: #000;
        }
        #videoPlayer {
            width: 100vw;
            height: calc(100vh - 60px);
            object-fit: cover;
            background-color: #000;
        }
        #bannerContainer {
            height: 60px;
            background-color: #000;
            color: white;
            display: flex;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        #runningText {
            white-space: nowrap;
            position: absolute;
            animation: marquee 160s linear infinite;
            padding-left: 100%;
            font-size: 4.5rem;
            font-weight: bold;
        }

        @keyframes marquee {
            0%   { transform: translateX(0%); }
            100% { transform: translateX(-100%); }
        }

        video:-webkit-full-screen {
            width: 100%;
            height: 100%;
        }
        video:-moz-full-screen {
            width: 100%;
            height: 100%;
        }
        video:-ms-fullscreen {
            width: 100%;
            height: 100%;
        }
        video:fullscreen {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body class="flex flex-col h-screen w-screen">
    <video id="videoPlayer" autoplay playsinline loop></video>

    <div id="bannerContainer" class="w-full">
        <div id="runningText" class="px-4">Loading banner...</div>
    </div>

    <script src="js/player.js"></script>
</body>
</html>
