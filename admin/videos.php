<?php
include_once '../config.php';
include_once 'includes/header.php';

$uploadDir = '../uploads/videos/';
$webPathPrefix = 'uploads/videos/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Multiple Video Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['videoFiles'])) {
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
}

// Handle Video Deletion
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
        echo "<script>
                $(document).ready(function() {
                    Swal.fire('Success!', 'Video deleted successfully.', 'success');
                });
              </script>";
    } catch (PDOException $e) {
        echo "<script>
                $(document).ready(function() {
                    Swal.fire('Error!', 'Failed to delete video: " . $e->getMessage() . "', 'error');
                });
              </script>";
    }
}

// Fetch all videos (tanpa TanggalUpload)
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
    <!-- Upload Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg">
                <i class="fas fa-cloud-upload-alt text-blue-600 text-xl"></i>
            </div>
            <h3 class="ml-4 text-xl font-semibold text-gray-800">Upload Videos</h3>
        </div>
        
        <form action="videos.php" method="POST" enctype="multipart/form-data" class="space-y-4">
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

        <?php if (isset($uploadResults)): ?>
            <div class="mt-6 space-y-2">
                <?php foreach ($uploadResults as $result): ?>
                    <div class="flex items-center p-3 rounded-lg <?php echo $result['status'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                        <i class="fas <?php echo $result['status'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-3"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($result['file']); ?>:</span>
                        <span class="ml-2"><?php echo htmlspecialchars($result['message']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
            <div class="overflow-x-auto">
                <table id="videosTable" class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Path</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($videos as $video): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($video['ID']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-file-video text-blue-500 mr-2"></i>
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($video['NamaFile']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 break-all"><?php echo htmlspecialchars($video['Path']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
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
    // Pastikan tabel ada sebelum inisialisasi DataTables
    if ($('#videosTable').length && $('#videosTable tbody tr').length > 0) {
        $('#videosTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[0, 'desc']], // Sort by ID descending
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
                { orderable: false, targets: [3] } // Disable sorting on Actions column
            ]
        });
    }
});

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
