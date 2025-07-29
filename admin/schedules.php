<?php
session_start();
include_once '../config.php';

// Get filter date
$filterDate = $_GET['filter_date'] ?? date('Y-m-d');

// Handle Schedule Submission with POST-Redirect-GET
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
        $_SESSION['form_result'] = ['status' => 'error', 'message' => 'Please select a video.'];
    } else {
        try {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO tbDataMaster (Tanggal, JamMulai1, JamAkhir1, JamMulai2, JamAkhir2, JamMulai3, JamAkhir3, videoId, Status, Urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tanggal, $jamMulai1, $jamAkhir1, $jamMulai2, $jamAkhir2, $jamMulai3, $jamAkhir3, $videoId, $status, $urutan]);
                $_SESSION['form_result'] = ['status' => 'success', 'message' => 'Schedule added successfully.'];
            } elseif ($action === 'edit') {
                $id = $_POST['id'] ?? null;
                if (!empty($id)) {
                    $stmt = $conn->prepare("UPDATE tbDataMaster SET Tanggal = ?, JamMulai1 = ?, JamAkhir1 = ?, JamMulai2 = ?, JamAkhir2 = ?, JamMulai3 = ?, JamAkhir3 = ?, videoId = ?, Status = ?, Urutan = ? WHERE ID = ?");
                    $stmt->execute([$tanggal, $jamMulai1, $jamAkhir1, $jamMulai2, $jamAkhir2, $jamMulai3, $jamAkhir3, $videoId, $status, $urutan, $id]);
                    $_SESSION['form_result'] = ['status' => 'success', 'message' => 'Schedule updated successfully.'];
                }
            } elseif ($action === 'bulk_delete') {
                $selectedIds = $_POST['selected_schedules'] ?? [];
                $deletedCount = 0;
                
                if (!empty($selectedIds)) {
                    foreach ($selectedIds as $id) {
                        $stmt = $conn->prepare("DELETE FROM tbDataMaster WHERE ID = ?");
                        $stmt->execute([$id]);
                        $deletedCount++;
                    }
                    $_SESSION['form_result'] = ['status' => 'success', 'message' => "Successfully deleted $deletedCount schedule(s)."];
                }
            }
        } catch (PDOException $e) {
            $_SESSION['form_result'] = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    $redirectUrl = 'schedules.php';
    if (isset($_POST['filter_date'])) {
        $redirectUrl .= '?filter_date=' . urlencode($_POST['filter_date']);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Handle Schedule Deletion
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM tbDataMaster WHERE ID = ?");
        $stmt->execute([$id]);
        $_SESSION['form_result'] = ['status' => 'success', 'message' => 'Schedule deleted successfully.'];
    } catch (PDOException $e) {
        $_SESSION['form_result'] = ['status' => 'error', 'message' => 'Failed to delete schedule: ' . $e->getMessage()];
    }
    
    $redirectUrl = 'schedules.php';
    if (isset($_GET['filter_date'])) {
        $redirectUrl .= '?filter_date=' . urlencode($_GET['filter_date']);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

// Now include header after all processing is done
include_once 'includes/header.php';

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

// Get form result from session
$formResult = null;
if (isset($_SESSION['form_result'])) {
    $formResult = $_SESSION['form_result'];
    unset($_SESSION['form_result']);
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
    <!-- Show Results -->
    <?php if ($formResult): ?>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center p-3 rounded-lg <?php echo $formResult['status'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                <i class="fas <?php echo $formResult['status'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-3"></i>
                <span><?php echo htmlspecialchars($formResult['message']); ?></span>
            </div>
        </div>
    <?php endif; ?>

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

        <form method="POST" class="space-y-6">
            <?php if ($editSchedule): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editSchedule['ID']); ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>
            <input type="hidden" name="filter_date" value="<?php echo htmlspecialchars($filterDate); ?>">

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
                    <a href="schedules.php?filter_date=<?php echo urlencode($filterDate); ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200">
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
            <!-- Bulk Actions -->
            <div id="bulkActions" class="bulk-actions">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                        <span id="selectedCount">0</span> schedule(s) selected
                    </div>
                    <div class="space-x-2">
                        <button onclick="clearSelection()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-times mr-1"></i>Clear Selection
                        </button>
                        <button onclick="bulkDelete()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-trash mr-1"></i>Delete Selected
                        </button>
                    </div>
                </div>
            </div>

            <form id="bulkDeleteForm" method="POST" style="display: none;">
                <input type="hidden" name="action" value="bulk_delete">
                <input type="hidden" name="filter_date" value="<?php echo htmlspecialchars($filterDate); ?>">
                <div id="selectedSchedulesInputs"></div>
            </form>

            <div class="overflow-x-auto">
                <table id="schedulesTable" class="min-w-full">
                    <thead>
                        <tr>
                            <th class="w-12">
                                <input type="checkbox" id="selectAll" class="custom-checkbox" onchange="toggleSelectAll()">
                            </th>
                            <th>ID</th>
                            <th>Video</th>
                            <th>Slot 1</th>
                            <th>Slot 2</th>
                            <th>Slot 3</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="schedule-checkbox custom-checkbox" value="<?php echo $schedule['ID']; ?>" onchange="updateBulkActions()">
                                </td>
                                <td><?php echo htmlspecialchars($schedule['ID']); ?></td>
                                <td>
                                    <div class="flex items-center">
                                        <i class="fas fa-video text-blue-500 mr-2"></i>
                                        <span class="font-medium"><?php echo htmlspecialchars($schedule['VideoNamaFile']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo $schedule['JamMulai1'] ? substr($schedule['JamMulai1'], 0, 5) . ' - ' . substr($schedule['JamAkhir1'], 0, 5) : '-'; ?></td>
                                <td><?php echo $schedule['JamMulai2'] ? substr($schedule['JamMulai2'], 0, 5) . ' - ' . substr($schedule['JamAkhir2'], 0, 5) : '-'; ?></td>
                                <td><?php echo $schedule['JamMulai3'] ? substr($schedule['JamMulai3'], 0, 5) . ' - ' . substr($schedule['JamAkhir3'], 0, 5) : '-'; ?></td>
                                <td>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $schedule['Status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo htmlspecialchars($schedule['Status']); ?>
                                    </span>
                                </td>
                                <td class="space-x-2">
                                    <a href="schedules.php?edit_id=<?php echo $schedule['ID']; ?>&filter_date=<?php echo $filterDate; ?>" 
                                       class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded-lg transition-colors duration-200 text-sm">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <button onclick="deleteSchedule(<?php echo $schedule['ID']; ?>)" 
                                            class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded-lg transition-colors duration-200 text-sm">
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
$(document).ready(function() {
    $('.select2').select2({
        theme: 'default',
        width: '100%'
    });
    
    if ($('#schedulesTable').length && $('#schedulesTable tbody tr').length > 0) {
        $('#schedulesTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[1, 'desc']], // Sort by ID descending
            language: {
                search: "Search schedules:",
                lengthMenu: "Show _MENU_ schedules per page",
                info: "Showing _START_ to _END_ of _TOTAL_ schedules",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                },
                emptyTable: "No schedules available",
                zeroRecords: "No matching schedules found"
            },
            columnDefs: [
                { orderable: false, targets: [0, 7] } // Disable sorting on checkbox and actions columns
            ],
            drawCallback: function() {
                updateBulkActions();
            }
        });
    }
});

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.schedule-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.schedule-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = checkboxes.length;
    
    if (checkboxes.length > 0) {
        bulkActions.classList.add('show');
    } else {
        bulkActions.classList.remove('show');
    }
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.schedule-checkbox');
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = checkboxes.length === allCheckboxes.length;
    selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.schedule-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}

function bulkDelete() {
    const checkboxes = document.querySelectorAll('.schedule-checkbox:checked');
    
    if (checkboxes.length === 0) {
        Swal.fire('Warning!', 'Please select schedules to delete.', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete ${checkboxes.length} schedule(s). This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('bulkDeleteForm');
            const inputsContainer = document.getElementById('selectedSchedulesInputs');
            
            // Clear previous inputs
            inputsContainer.innerHTML = '';
            
            // Add selected schedule IDs as hidden inputs
            checkboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_schedules[]';
                input.value = checkbox.value;
                inputsContainer.appendChild(input);
            });
            
            form.submit();
        }
    });
}

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
