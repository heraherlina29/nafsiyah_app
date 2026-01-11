</main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const menuToggle = document.getElementById('menuToggle');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        // Toggle sidebar untuk desktop
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');

                // Ubah ikon toggle
                const icon = sidebarToggle.querySelector('i');
                if (sidebar.classList.contains('collapsed')) {
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                }
            });
        }

        // Toggle sidebar untuk mobile
        if (menuToggle) {
            menuToggle.addEventListener('click', function () {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                body.classList.add('overflow-hidden');
            });
        }

        // Tutup sidebar saat overlay diklik
        if (overlay) {
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                body.classList.remove('overflow-hidden');
            });
        }

        // Tutup sidebar saat resize window (jika mobile berubah ke desktop)
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                body.classList.remove('overflow-hidden');
            }
        });
    });
    // User Activity Chart
    const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
    const userActivityChart = new Chart(userActivityCtx, {
        type: 'line',
        data: {
            labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            datasets: [{
                label: 'Active Users',
                data: [120, 190, 300, 500, 200, 300, 450],
                backgroundColor: 'rgba(128, 90, 213, 0.2)',
                borderColor: 'rgba(128, 90, 213, 1)',
                borderWidth: 2,
                tension: 0.3,
                pointBackgroundColor: 'rgba(128, 90, 213, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Data Distribution Chart
    const dataDistributionCtx = document.getElementById('dataDistributionChart').getContext('2d');
    const dataDistributionChart = new Chart(dataDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Users', 'Admins', 'Nafsiyah Data'],
            datasets: [{
                data: [1248, 24, 5678],
                backgroundColor: [
                    'rgba(128, 90, 213, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)'
                ],
                borderColor: [
                    'rgba(128, 90, 213, 1)',
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

</script>
</body>

</html>