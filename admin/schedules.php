<?php
include_once '../config.php';
include_once 'includes/header.php';

// Get filter date
$filterDate = $_GET['filter_date'] ?? date('Y-m-d');

// Fetch available videos for dropdown
$availableVideos = [];
try {
    $stmt = $conn->query("SELECT ID, NamaFile FROM tbListDataVideo ORDER BY NamaFile ASC");
    $availableVideos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <strong class='font-bold'>Error!</strong>
                <span class='block sm:inline'>Failed to fetch videos: " . $e->getMessage() . "</span>
              </div>";
}

// Handle Schedule Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $tanggal = $_POST['tanggal'] ?? null;
    $jamMulai1 = $_POST['jamMulai1'] ?? null;
    $jamAkhir1 = $_POST['jamAkhir1'] ?? null;
    $jamMulai2 = $_POST['jamMulai2'] ?? null;
    $jamAkhir2 = $_POST['jamAkhir2'] ?? null;
    $jamMulai3 = $_POST['jamMulai3'] ?? null;
    $jamAkhir3 = $_POST['jamAkhir3'] ?? null;
    $videoId = $_POST['videoId'] ?? null;
    $status = $_POST['status'] ?? 'Aktif';
    $urutan = $_POST['urutan'] ?? 1;

    if (empty($videoId)) {
        echo "<script>
                $(document).ready(function() {
                    Swal.fire('Error!', 'Please select a video.', 'error');
                });
              </script>";
    } else {
        try {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO tbDataMaster (Tanggal, JamMulai1, JamAkhir1, JamMulai2, JamAkhir2, JamMulai3, JamAkhir3, videoId, Status, Urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tanggal, $jamMulai1, $jamAkhir1, $jamMulai2, $jamAkhir2, $jamMulai3, $jamAkhir3, $videoId, $status, $urutan]);
                echo "<script>
                        $(document).ready(function() {
                            Swal.fire('Success!', 'Schedule added successfully.', 'success');
                        });
                      </script>";
            } elseif ($action === 'edit') {
                $id = $_POST['id'] ?? null;
                if (!empty($id)) {
                    $stmt = $conn->prepare("UPDATE tbDataMaster SET Tanggal = ?, JamMulai1 = ?, JamAkhir1 = ?, JamMulai2 = ?, JamAkhir2 = ?, JamMulai3 = ?, JamAkhir3 = ?, videoId = ?, Status = ?, Urutan = ? WHERE ID = ?");
                    $stmt->execute([$tanggal, $jamMulai1, $jamAkhir1, $jamMulai2, $jamAkhir2, $jamMulai3, $jamAkhir3, $videoId, $status, $urutan, $id]);
                    echo "<script>
                            $(document).ready(function() {
                                Swal.fire('Success!', 'Schedule updated successfully.', 'success');
                            });
                          </script>";
                }
            }
        } catch (PDOException $e) {
            echo "<script>
                    $(document).ready(function() {
                        Swal.fire('Error!', 'Database error: " . $e->getMessage() . "', 'error');
                    });
                  </script>";
        }
    }
}

// Handle Schedule Deletion
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM tbDataMaster WHERE ID = ?");
        $stmt->execute([$id]);
        echo "<script>
                $(document).ready(function() {
                    Swal.fire('Success!', 'Schedule deleted successfully.', 'success');
                });
              </script>";
    } catch (PDOException $e) {
        echo "<script>
                $(document).ready(function() {
                    Swal.fire('Error!', 'Failed to delete schedule: " . $e->getMessage() . "', 'error');
                });
              </script>";
    }
}

// Fetch schedule for editing
$editSchedule = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    try {
        $stmt = $conn->prepare("SELECT ID, Tanggal, JamMulai1, JamAkhir1, JamMulai2, JamAkhir2, JamMulai3, JamAkhir3, videoId, Status, Urutan FROM tbDataMaster WHERE ID = ?");
        $stmt->execute([$id]);
        $editSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <strong class='font-bold'>Error!</strong>
                <span class='block sm:inline'>Failed to fetch schedule: " . $e->getMessage() . "</span>
              </div>";
    }
}

// Fetch schedules with filter
$schedules = [];
try {
    $sql = "
        SELECT dm.ID, dm.Tanggal, dm.JamMulai1, dm.JamAkhir1, dm.JamMulai2, dm.JamAkhir2, dm.JamMulai3, dm.JamAkhir3, dm.Status, dm.Urutan,
               v.NamaFile as VideoNamaFile
        FROM tbDataMaster dm
        JOIN tbListDataVideo v ON dm.videoId = v.ID
        WHERE dm.Tanggal = ?
        ORDER BY dm.ID DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$filterDate]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
            <strong class='font-bold'>Error!</strong>
            <span class='block sm:inline'>Failed to fetch schedules: " . $e->getMessage() . "</span>
          </div>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="space-y-6">
    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-filter text-green-600 text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-800">Filter Schedules</h3>
            </div>
            <form method="GET" class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Date:</label>
                    <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filterDate); ?>" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Add/Edit Schedule Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg">
                <i class="fas fa-plus text-blue-600 text-xl"></i>
            </div>
            <h3 class="ml-4 text-xl font-semibold text-gray-800">
                <?php echo $editSchedule ? 'Edit Schedule' : 'Add New Schedule'; ?>
            </h3>
        </div>

        <form action="schedules.php" method="POST" class="space-y-6">
            <?php if ($editSchedule): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editSchedule['ID']); ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-2"></i>Date
                    </label>
                    <input type="date" name="tanggal" value="<?php echo htmlspecialchars($editSchedule['Tanggal'] ?? $filterDate); ?>" 
                           required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-video mr-2"></i>Video
                    </label>
                    <select name="videoId" class="select2 w-full" required>
                        <option value="">-- Select Video --</option>
                        <?php foreach ($availableVideos as $video): ?>
                            <option value="<?php echo htmlspecialchars($video['ID']); ?>" 
                                    <?php echo ($editSchedule && isset($editSchedule['videoId']) && $editSchedule['videoId'] === $video['ID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($video['NamaFile']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Time Slots -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-medium text-gray-800 mb-4">
                    <i class="fas fa-clock mr-2"></i>Time Slots
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Slot 1 -->
                    <div class="space-y-3">
                        <h5 class="font-medium text-gray-700">Slot 1</h5>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                            <input type="time" name="jamMulai1" value="<?php echo htmlspecialchars($editSchedule['JamMulai1'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End Time</label>
                            <input type="time" name="jamAkhir1" value="<?php echo htmlspecialchars($editSchedule['JamAkhir1'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Slot 2 -->
                    <div class="space-y-3">
                        <h5 class="font-medium text-gray-700">Slot 2</h5>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                            <input type="time" name="jamMulai2" value="<?php echo htmlspecialchars($editSchedule['JamMulai2'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End Time</label>
                            <input type="time" name="jamAkhir2" value="<?php echo htmlspecialchars($editSchedule['JamAkhir2'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Slot 3 -->
                    <div class="space-y-3">
                        <h5 class="font-medium text-gray-700">Slot 3</h5>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                            <input type="time" name="jamMulai3" value="<?php echo htmlspecialchars($editSchedule['JamMulai3'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End Time</label>
                            <input type="time" name="jamAkhir3" value="<?php echo htmlspecialchars($editSchedule['JamAkhir3'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-toggle-on mr-2"></i>Status
                    </label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Aktif" <?php echo ($editSchedule && $editSchedule['Status'] === 'Aktif') ? 'selected' : ''; ?>>Active</option>
                        <option value="Nonaktif" <?php echo ($editSchedule && $editSchedule['Status'] === 'Nonaktif') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sort-numeric-up mr-2"></i>Order
                    </label>
                    <input type="number" name="urutan" value="<?php echo htmlspecialchars($editSchedule['Urutan'] ?? 1); ?>" 
                           min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo $editSchedule ? 'Update Schedule' : 'Add Schedule'; ?>
                </button>
                <?php if ($editSchedule): ?>
                    <a href="schedules.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Schedules Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-list text-purple-600 text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-800">
                    Schedules for <?php echo date('F j, Y', strtotime($filterDate)); ?>
                </h3>
            </div>
            <div class="text-sm text-gray-500">
                Total: <?php echo count($schedules); ?> schedules
            </div>
        </div>

        <?php if (empty($schedules)): ?>
            <div class="text-center py-12">
                <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">No schedules found for this date.</p>
                <p class="text-gray-400">Create your first schedule above!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slot 1</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slot 2</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slot 3</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($schedules as $schedule): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($schedule['ID']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-video text-blue-500 mr-2"></i>
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['VideoNamaFile']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $schedule['JamMulai1'] ? substr($schedule['JamMulai1'], 0, 5) . ' - ' . substr($schedule['JamAkhir1'], 0, 5) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $schedule['JamMulai2'] ? substr($schedule['JamMulai2'], 0, 5) . ' - ' . substr($schedule['JamAkhir2'], 0, 5) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $schedule['JamMulai3'] ? substr($schedule['JamMulai3'], 0, 5) . ' - ' . substr($schedule['JamAkhir3'], 0, 5) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $schedule['Status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo htmlspecialchars($schedule['Status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="schedules.php?edit_id=<?php echo $schedule['ID']; ?>&filter_date=<?php echo $filterDate; ?>" 
                                       class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded-lg transition-colors duration-200">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <button onclick="deleteSchedule(<?php echo $schedule['ID']; ?>)" 
                                            class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded-lg transition-colors duration-200">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteSchedule(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'schedules.php?delete_id=' + id + '&filter_date=<?php echo $filterDate; ?>';
        }
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>
