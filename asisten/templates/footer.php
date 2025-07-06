            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarOpenButton = document.getElementById('sidebar-open-button');
            const sidebarCloseButton = document.getElementById('sidebar-close-button');

            // Function to open sidebar
            function openSidebar() {
                sidebar.classList.remove('sidebar-closed');
                sidebar.classList.add('sidebar-open');
            }

            // Function to close sidebar
            function closeSidebar() {
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-closed');
            }

            // Event listeners
            if (sidebarOpenButton) {
                sidebarOpenButton.addEventListener('click', openSidebar);
            }
            if (sidebarCloseButton) {
                sidebarCloseButton.addEventListener('click', closeSidebar);
            }

            // Close sidebar if screen size is resized to desktop from mobile
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) { // md breakpoint
                    // Ensure sidebar is visible on desktop if it was closed on mobile
                    sidebar.classList.remove('sidebar-closed');
                    sidebar.classList.add('sidebar-open');
                } else {
                    // On mobile, ensure it's closed by default unless opened
                    sidebar.classList.remove('sidebar-open');
                    sidebar.classList.add('sidebar-closed');
                }
            });

            // Initial check on load for desktop view
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('sidebar-closed');
                sidebar.classList.add('sidebar-open');
            }
        });
    </script>
</body>
</html>
