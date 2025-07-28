<?php
include_once '../config.php';
include_once 'includes/header.php';

// Get filter date
$filterDate = $_GET['filter_date'] ?? date('Y-m-d');

// Handle Banner Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $tanggal = $_POST['tanggal'] ?? null;
    $jamMulai = $_POST['jamMulai'] ?? null;
    $jamAkhir = $_POST['jamAkhir'] ?? null;
    $isiText = $_POST['isiText'] ?? null;
    $status = $_POST['status'] ?? 'Aktif';

    try {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO tblBanner (Tanggal, JamMulai, JamAkhir, IsiText, Status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tanggal, $jamMulai, $jamAkhir, $isiText, $status]);
            echo "<script>
                    $(document).ready(function() {
                        Swal.fire('Success!', 'Banner added successfully.', 'success');
                    });
                  </script>";
        } elseif ($action === 'edit') {
            $id = $_POST['id'] ?? null;
            if (!empty($id)) {
                $stmt = $conn->prepare("UPDATE tblBanner SET Tanggal = ?, JamMulai = ?, JamAkhir = ?, IsiText = ?, Status = ? WHERE ID = ?");
                $stmt->execute([$tanggal, $jamMulai, $jamAkhir, $isiText, $status, $id]);
                echo "<script>
                        $(document).ready(function() {
                            Swal.fire('Success!', 'Banner updated successfully.', 'success');
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

// Handle Banner Deletion
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM tblBanner WHERE ID = ?");
        $stmt->execute([$id]);
        echo "<script>
                $(document).ready(function() {
                    Swal.fire('Success!', 'Banner deleted successfully.', 'success');
                });
              </script>";
    } catch (PDOException $e) {
        echo "<script>
                $(document).ready(function() {
                    Swal.fire('Error!', 'Failed to delete banner: " . $e->getMessage() . "', 'error');
                });
              </script>";
    }
}

// Fetch banner for editing
$editBanner = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM tblBanner WHERE ID = ?");
        $stmt->execute([$id]);
        $editBanner = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <strong class='font-bold'>Error!</strong>
                <span class='block sm:inline'>Failed to fetch banner: " . $e->getMessage() . "</span>
              </div>";
    }
}

// Fetch banners with filter
$banners = [];
try {
    $sql = "SELECT ID, Tanggal, JamMulai, JamAkhir, IsiText, Status FROM tblBanner WHERE Tanggal = ? ORDER BY JamMulai ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$filterDate]);
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
            <strong class='font-bold'>Error!</strong>
            <span class='block sm:inline'>Failed to fetch banners: " . $e->getMessage() . "</span>
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
                <h3 class="ml-4 text-xl font-semibold text-gray-800">Filter Banners</h3>
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

    <!-- Add/Edit Banner Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg">
                <i class="fas fa-plus text-blue-600 text-xl"></i>
            </div>
            <h3 class="ml-4 text-xl font-semibold text-gray-800">
                <?php echo $editBanner ? 'Edit Banner' : 'Add New Banner'; ?>
            </h3>
        </div>

        <form action="banners.php" method="POST" class="space-y-6">
            <?php if ($editBanner): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editBanner['ID']); ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-2"></i>Date
                    </label>
                    <input type="date" name="tanggal" value="<?php echo htmlspecialchars($editBanner['Tanggal'] ?? $filterDate); ?>" 
                           required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-toggle-on mr-2"></i>Status
                    </label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Aktif" <?php echo ($editBanner && $editBanner['Status'] === 'Aktif') ? 'selected' : ''; ?>>Active</option>
                        <option value="Nonaktif" <?php echo ($editBanner && $editBanner['Status'] === 'Nonaktif') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-clock mr-2"></i>Start Time
                    </label>
                    <input type="time" name="jamMulai" value="<?php echo htmlspecialchars($editBanner['JamMulai'] ?? ''); ?>" 
                           required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-clock mr-2"></i>End Time
                    </label>
                    <input type="time" name="jamAkhir" value="<?php echo htmlspecialchars($editBanner['JamAkhir'] ?? ''); ?>" 
                           required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-comment mr-2"></i>Banner Text
                </label>
                <textarea name="isiText" rows="4" required 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Enter your banner text here..."><?php echo htmlspecialchars($editBanner['IsiText'] ?? ''); ?></textarea>
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo $editBanner ? 'Update Banner' : 'Add Banner'; ?>
                </button>
                <?php if ($editBanner): ?>
                    <a href="banners.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Banners Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-bullhorn text-purple-600 text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-800">
                    Banners for <?php echo date('F j, Y', strtotime($filterDate)); ?>
                </h3>
            </div>
            <div class="text-sm text-gray-500">
                Total: <?php echo count($banners); ?> banners
            </div>
        </div>

        <?php if (empty($banners)): ?>
            <div class="text-center py-12">
                <i class="fas fa-bullhorn text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">Tidak ada Text</p>
                <p class="text-gray-400">Create your first banner above!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Banner Text</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($banners as $banner): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($banner['ID']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-blue-500 mr-2"></i>
                                        <?php echo substr($banner['JamMulai'], 0, 5) . ' - ' . substr($banner['JamAkhir'], 0, 5); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                    <?php echo htmlspecialchars($banner['IsiText']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $banner['Status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo htmlspecialchars($banner['Status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="banners.php?edit_id=<?php echo $banner['ID']; ?>&filter_date=<?php echo $filterDate; ?>" 
                                       class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded-lg transition-colors duration-200">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <button onclick="deleteBanner(<?php echo $banner['ID']; ?>)" 
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
function deleteBanner(id) {
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
            window.location.href = 'banners.php?delete_id=' + id + '&filter_date=<?php echo $filterDate; ?>';
        }
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>
