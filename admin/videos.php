<?php
session_start();
include_once '../config.php';

$uploadDir = '../uploads/videos/';
$webPathPrefix = 'uploads/videos/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Multiple Video Upload with POST-Redirect-GET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $files = $_FILES['videoFiles'];
    $uploadResults = [];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = basename($files['name'][$i]);
            $filePath = $uploadDir . $fileName;
            $dbFilePath = $webPathPrefix . $fileName;
            $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            $allowedTypes = ['mp4', 'webm', 'ogg'];
            if (!in_array($fileType, $allowedTypes)) {
                $uploadResults[] = ['status' => 'error', 'file' => $fileName, 'message' => 'Invalid file type'];
                continue;
            }

            if ($files['size'][$i] > 500 * 1024 * 1024) {
                $uploadResults[] = ['status' => 'error', 'file' => $fileName, 'message' => 'File too large (max 500MB)'];
                continue;
            }

            if (file_exists($filePath)) {
                $uploadResults[] = ['status' => 'error', 'file' => $fileName, 'message' => 'File already exists'];
                continue;
            }

            if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO tbListDataVideo (NamaFile, Path) VALUES (?, ?)");
                    $stmt->execute([$fileName, $dbFilePath]);
                    $uploadResults[] = ['status' => 'success', 'file' => $fileName, 'message' => 'Uploaded successfully'];
                } catch (PDOException $e) {
                    $uploadResults[] = ['status' => 'error', 'file' => $fileName, 'message' => 'Database error: ' . $e->getMessage()];
                }
            } else {
                $uploadResults[] = ['status' => 'error', 'file' => $fileName, 'message' => 'Upload failed'];
            }
        }
    }
    
    // Store results in session and redirect
    $_SESSION['upload_results'] = $uploadResults;
    header('Location: videos.php?uploaded=1');
    exit();
}

// Handle Bulk Video Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    $selectedIds = $_POST['selected_videos'] ?? [];
    $deletedCount = 0;
    
    if (!empty($selectedIds)) {
        try {
            foreach ($selectedIds as $id) {
                $stmt = $conn->prepare("SELECT Path FROM tbListDataVideo WHERE ID = ?");
                $stmt->execute([$id]);
                $video = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($video) {
                    $physicalPath = str_replace($webPathPrefix, $uploadDir, $video['Path']);
                    if (file_exists($physicalPath)) {
                        unlink($physicalPath);
                    }
                }

                $stmt = $conn->prepare("DELETE FROM tbListDataVideo WHERE ID = ?");
                $stmt->execute([$id]);
                $deletedCount++;
            }
            
            $_SESSION['bulk_delete_result'] = ['status' => 'success', 'count' => $deletedCount];
        } catch (PDOException $e) {
            $_SESSION['bulk_delete_result'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    header('Location: videos.php?bulk_deleted=1');
    exit();
}

// Handle Single Video Deletion
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("SELECT Path FROM tbListDataVideo WHERE ID = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($video) {
            $physicalPath = str_replace($webPathPrefix, $uploadDir, $video['Path']);
            if (file_exists($physicalPath)) {
                unlink($physicalPath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM tbListDataVideo WHERE ID = ?");
        $stmt->execute([$id]);
        $_SESSION['delete_result'] = ['status' => 'success', 'message' => 'Video deleted successfully'];
    } catch (PDOException $e) {
        $_SESSION['delete_result'] = ['status' => 'error', 'message' => 'Failed to delete video: ' . $e->getMessage()];
    }
    
    header('Location: videos.php?deleted=1');
    exit();
}

// Now include header after all processing is done
include_once 'includes/header.php';

// Get upload results from session
$uploadResults = null;
if (isset($_SESSION['upload_results'])) {
    $uploadResults = $_SESSION['upload_results'];
    unset($_SESSION['upload_results']);
}

// Get bulk delete results from session
$bulkDeleteResult = null;
if (isset($_SESSION['bulk_delete_result'])) {
    $bulkDeleteResult = $_SESSION['bulk_delete_result'];
    unset($_SESSION['bulk_delete_result']);
}

// Get delete result from session
$deleteResult = null;
if (isset($_SESSION['delete_result'])) {
    $deleteResult = $_SESSION['delete_result'];
    unset($_SESSION['delete_result']);
}

// Fetch all videos
$videos = [];
try {
    $stmt = $conn->query("SELECT ID, NamaFile, Path FROM tbListDataVideo ORDER BY ID DESC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
            <strong class='font-bold'>Error!</strong>
            <span class='block sm:inline'>Failed to fetch videos: " . $e->getMessage() . "</span>
          </div>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="space-y-6">
    <!-- Show Results -->
    <?php if ($uploadResults): ?>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Upload Results</h3>
            <div class="space-y-2">
                <?php foreach ($uploadResults as $result): ?>
                    <div class="flex items-center p-3 rounded-lg <?php echo $result['status'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                        <i class="fas <?php echo $result['status'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-3"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($result['file']); ?>:</span>
                        <span class="ml-2"><?php echo htmlspecialchars($result['message']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($bulkDeleteResult): ?>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center p-3 rounded-lg <?php echo $bulkDeleteResult['status'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                <i class="fas <?php echo $bulkDeleteResult['status'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-3"></i>
                <span>
                    <?php if ($bulkDeleteResult['status'] === 'success'): ?>
                        Successfully deleted <?php echo $bulkDeleteResult['count']; ?> video(s)
                    <?php else: ?>
                        Error: <?php echo htmlspecialchars($bulkDeleteResult['message']); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($deleteResult): ?>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center p-3 rounded-lg <?php echo $deleteResult['status'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                <i class="fas <?php echo $deleteResult['status'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-3"></i>
                <span><?php echo htmlspecialchars($deleteResult['message']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upload Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg">
                <i class="fas fa-cloud-upload-alt text-blue-600 text-xl"></i>
            </div>
            <h3 class="ml-4 text-xl font-semibold text-gray-800">Upload Videos</h3>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="upload">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-file-video mr-2"></i>Select Video Files (Multiple)
                </label>
                <input type="file" name="videoFiles[]" multiple accept="video/mp4,video/webm,video/ogg" 
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-lg cursor-pointer">
                <p class="mt-1 text-sm text-gray-500">Supported formats: MP4, WebM, OGG. Max size: 500MB per file.</p>
            </div>
            <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105">
                <i class="fas fa-upload mr-2"></i>Upload Videos
            </button>
        </form>
    </div>

    <!-- Videos Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-video text-purple-600 text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-800">Video Library</h3>
            </div>
            <div class="text-sm text-gray-500">
                Total: <?php echo count($videos); ?> videos
            </div>
        </div>

        <?php if (empty($videos)): ?>
            <div class="text-center py-12">
                <i class="fas fa-video text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">No videos uploaded yet.</p>
                <p class="text-gray-400">Upload your first video to get started!</p>
            </div>
        <?php else: ?>
            <!-- Bulk Actions -->
            <div id="bulkActions" class="bulk-actions">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                        <span id="selectedCount">0</span> video(s) selected
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
                <div id="selectedVideosInputs"></div>
            </form>

            <div class="overflow-x-auto">
                <table id="videosTable" class="min-w-full">
                    <thead>
                        <tr>
                            <th class="w-12">
                                <input type="checkbox" id="selectAll" class="custom-checkbox" onchange="toggleSelectAll()">
                            </th>
                            <th>ID</th>
                            <th>File Name</th>
                            <th>Path</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="video-checkbox custom-checkbox" value="<?php echo $video['ID']; ?>" onchange="updateBulkActions()">
                                </td>
                                <td><?php echo htmlspecialchars($video['ID']); ?></td>
                                <td>
                                    <div class="flex items-center">
                                        <i class="fas fa-file-video text-blue-500 mr-2"></i>
                                        <span class="font-medium"><?php echo htmlspecialchars($video['NamaFile']); ?></span>
                                    </div>
                                </td>
                                <td class="break-all"><?php echo htmlspecialchars($video['Path']); ?></td>
                                <td>
                                    <button onclick="deleteVideo(<?php echo $video['ID']; ?>)" 
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
$(document).ready(function() {
    if ($('#videosTable').length && $('#videosTable tbody tr').length > 0) {
        $('#videosTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[1, 'desc']], // Sort by ID descending
            language: {
                search: "Search videos:",
                lengthMenu: "Show _MENU_ videos per page",
                info: "Showing _START_ to _END_ of _TOTAL_ videos",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                },
                emptyTable: "No videos available",
                zeroRecords: "No matching videos found"
            },
            columnDefs: [
                { orderable: false, targets: [0, 4] } // Disable sorting on checkbox and actions columns
            ],
            drawCallback: function() {
                updateBulkActions();
            }
        });
    }
});

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.video-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.video-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = checkboxes.length;
    
    if (checkboxes.length > 0) {
        bulkActions.classList.add('show');
    } else {
        bulkActions.classList.remove('show');
    }
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.video-checkbox');
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = checkboxes.length === allCheckboxes.length;
    selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.video-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}

function bulkDelete() {
    const checkboxes = document.querySelectorAll('.video-checkbox:checked');
    
    if (checkboxes.length === 0) {
        Swal.fire('Warning!', 'Please select videos to delete.', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete ${checkboxes.length} video(s). This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('bulkDeleteForm');
            const inputsContainer = document.getElementById('selectedVideosInputs');
            
            // Clear previous inputs
            inputsContainer.innerHTML = '';
            
            // Add selected video IDs as hidden inputs
            checkboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_videos[]';
                input.value = checkbox.value;
                inputsContainer.appendChild(input);
            });
            
            form.submit();
        }
    });
}

function deleteVideo(id) {
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
            window.location.href = 'videos.php?delete_id=' + id;
        }
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>
