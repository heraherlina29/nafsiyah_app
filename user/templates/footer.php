</html>
</div>
</main>
</div>
</div>

<!-- Overlay for mobile menu -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>

<script>
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    function toggleMenu() {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    mobileMenuButton.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);

    // Close menu when clicking outside on mobile
    document.addEventListener('click', (event) => {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnMenuButton = mobileMenuButton.contains(event.target);

        if (!isClickInsideSidebar && !isClickOnMenuButton && !sidebar.classList.contains('-translate-x-full')) {
            toggleMenu();
        }
    });
</script>
</body>

</html>