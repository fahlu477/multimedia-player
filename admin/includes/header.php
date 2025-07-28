<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Multimedia Player</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {"50":"#eff6ff","100":"#dbeafe","200":"#bfdbfe","300":"#93c5fd","400":"#60a5fa","500":"#3b82f6","600":"#2563eb","700":"#1d4ed8","800":"#1e40af","900":"#1e3a8a"}
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>

    
    <style>
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 8px 12px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px !important;
            color: #374151 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        .sidebar-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tailwind-warning {
            display: none !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out" id="sidebar">
        <div class="flex items-center justify-center h-16 bg-gradient-to-r from-blue-600 to-purple-600">
            <h1 class="text-xl font-bold text-white">
                <i class="fas fa-video mr-2"></i>
                Admin Panel
            </h1>
        </div>
        <nav class="mt-8">
            <div class="px-4 space-y-2">
                <a href="index.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'sidebar-active' : ''; ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="videos.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'videos.php' ? 'sidebar-active' : ''; ?>">
                    <i class="fas fa-film mr-3"></i>
                    Manage Videos
                </a>
                <a href="schedules.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'schedules.php' ? 'sidebar-active' : ''; ?>">
                    <i class="fas fa-calendar-alt mr-3"></i>
                    Manage Schedules
                </a>
                <a href="banners.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'sidebar-active' : ''; ?>">
                    <i class="fas fa-bullhorn mr-3"></i>
                    Manage Banners
                </a>
                <a href="../public/index.php" target="_blank" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-external-link-alt mr-3"></i>
                    View Player
                </a>
            </div>
        </nav>
    </div>

    <div class="ml-64">
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 lg:hidden">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="ml-4 text-xl font-semibold text-gray-800">
                        <?php 
                        $page = basename($_SERVER['PHP_SELF'], '.php');
                        switch($page) {
                            case 'videos': echo 'Video Management'; break;
                            case 'schedules': echo 'Schedule Management'; break;
                            case 'banners': echo 'Banner Management'; break;
                            default: echo 'Dashboard'; break;
                        }
                        ?>
                    </h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <?php echo date('Y-m-d H:i:s'); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
