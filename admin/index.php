<?php
include_once '../config.php';
include_once 'includes/header.php';

// Get statistics
$totalVideos = 0;
$totalSchedules = 0;
$totalBanners = 0;
$todaySchedules = 0;

try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tbListDataVideo");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalVideos = $result['count'];

    $stmt = $conn->query("SELECT COUNT(*) as count FROM tbDataMaster");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSchedules = $result['count'];

    $stmt = $conn->query("SELECT COUNT(*) as count FROM tblBanner");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalBanners = $result['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbDataMaster WHERE Tanggal = ? AND Status = 'Aktif'");
    $stmt->execute([date('Y-m-d')]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $todaySchedules = $result['count'];
} catch (PDOException $e) {
    // Handle error silently for dashboard
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Welcome to Admin Dashboard</h1>
                <p class="text-blue-100">Manage your multimedia player system efficiently</p>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-75">Current Time</div>
                <div class="text-xl font-semibold" id="currentTime"><?php echo date('H:i:s'); ?></div>
                <div class="text-sm opacity-75"><?php echo date('F j, Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Videos</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalVideos; ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-video text-blue-600 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <a href="videos.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Manage Videos <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Schedules</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalSchedules; ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-calendar-alt text-green-600 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <a href="schedules.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                    Manage Schedules <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Banners</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalBanners; ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-bullhorn text-purple-600 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <a href="banners.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                    Manage Banners <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today's Active</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $todaySchedules; ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <i class="fas fa-play-circle text-yellow-600 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <a href="../public/index.php" target="_blank" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">
                    View Player <i class="fas fa-external-link-alt ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-bolt text-blue-600 text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-800">Quick Actions</h3>
            </div>
            <div class="space-y-4">
                <a href="videos.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-upload text-blue-600 mr-4"></i>
                    <div>
                        <div class="font-medium text-gray-900">Upload New Videos</div>
                        <div class="text-sm text-gray-500">Add multiple video files to your library</div>
                    </div>
                </a>
                <a href="schedules.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-plus text-green-600 mr-4"></i>
                    <div>
                        <div class="font-medium text-gray-900">Create Schedule</div>
                        <div class="text-sm text-gray-500">Set up video playback schedules</div>
                    </div>
                </a>
                <a href="banners.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-comment text-purple-600 mr-4"></i>
                    <div>
                        <div class="font-medium text-gray-900">Add Banner</div>
                        <div class="text-sm text-gray-500">Create running text banners</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-6">
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-info-circle text-green-600 text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-800">System Status</h3>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <span class="font-medium text-gray-900">Database Connection</span>
                    </div>
                    <span class="text-green-600 font-medium">Active</span>
                </div>
                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-play text-green-600 mr-3"></i>
                        <span class="font-medium text-gray-900">Player Status</span>
                    </div>
                    <span class="text-green-600 font-medium">Running</span>
                </div>
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-server text-blue-600 mr-3"></i>
                        <span class="font-medium text-gray-900">Server Time</span>
                    </div>
                    <span class="text-blue-600 font-medium"><?php echo date('H:i:s'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update current time every second
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    document.getElementById('currentTime').textContent = timeString;
}

setInterval(updateTime, 1000);
</script>

<?php include_once 'includes/footer.php'; ?>
