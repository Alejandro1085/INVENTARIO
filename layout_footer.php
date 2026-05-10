            </main>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');

            if (window.innerWidth <= 768 &&
                !sidebar.contains(event.target) &&
                !menuToggle.contains(event.target) &&
                sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                document.getElementById('main-content').classList.remove('expanded');
            }
        });

        // Auto-hide sidebar on mobile
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('main-content').classList.add('expanded');
        }

        // Responsive adjustments
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('main-content').classList.add('expanded');
            } else {
                document.getElementById('sidebar').classList.remove('collapsed');
                document.getElementById('main-content').classList.remove('expanded');
            }
        });
    </script>
</body>
</html>