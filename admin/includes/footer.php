</main>

    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    © <?php echo date('Y'); ?> Multimedia Player Admin. All rights reserved.
                </div>
                <div class="text-sm text-gray-500">
                    Server Time: <span id="serverTime"><?php echo date('H:i:s'); ?></span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'default',
                width: '100%'
            });
            
            // Update server time every second
            setInterval(function() {
                const now = new Date();
                document.getElementById('serverTime').textContent = now.toLocaleTimeString();
            }, 1000);
        });
    </script>
</body>
</html>
