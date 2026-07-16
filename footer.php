<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Footer Include - project/footer.php
================================================================
*/

$current_page = basename($_SERVER['PHP_SELF']);
?>
        <?php if ($current_page != 'login.php' && $current_page != 'index.php'): ?>
                <footer class="app-footer">
                    <div>
                        &copy; 2026 <strong>Team 2</strong>. Academic Planning & Monitoring System. All rights reserved.
                    </div>
                    <div class="footer-links">
                        <a href="#">About</a>
                        <a href="#">Support</a>
                        <a href="#">Privacy Policy</a>
                        <span style="color: var(--text-muted);">|</span>
                        <span>Version 2.1.0-Stable</span>
                    </div>
                </footer>
            </div> <!-- End main-content -->
        <?php endif; ?>
    </div> <!-- End app-container -->

    <!-- ========================================== -->
    <!-- JAVASCRIPT CONTROLLERS -->
    <!-- ========================================== -->
    <script>
    // 1. Sidebar Toggling
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (sidebarToggleBtn && sidebar && mainContent) {
        sidebarToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (window.innerWidth > 992) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
            } else {
                sidebar.classList.toggle('active');
            }
        });
        
        // Close sidebar on mobile clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== sidebarToggleBtn) {
                sidebar.classList.remove('active');
            }
        });
    }

    // 2. Dropdown Menu Toggle (Profile and Notifications)
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        if (!dropdown) return;
        
        // Close other open dropdowns
        document.querySelectorAll('.dropdown-menu').forEach((menu) => {
            if (menu.id !== dropdownId) {
                menu.classList.remove('active');
            }
        });
        
        dropdown.classList.toggle('active');
    }

    // Close dropdowns on clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.profile-dropdown') && !e.target.closest('.nav-icon-btn')) {
            document.querySelectorAll('.dropdown-menu').forEach((menu) => {
                menu.classList.remove('active');
            } );
        }
    });

    // 3. Dark/Light Mode Theme Toggle
    function toggleTheme() {
        const body = document.body;
        const track = document.getElementById('themeToggleTrack');
        
        body.classList.toggle('dark-mode');
        
        const isDark = body.classList.contains('dark-mode');
        
        // Toggle switch visual
        if (track) track.classList.toggle('active', isDark);
        
        // Save preferences to cookie for PHP persistence (expires in 30 days)
        document.cookie = `theme_mode=${isDark ? 'dark' : 'light'}; max-age=${30 * 24 * 60 * 60}; path=/`;
        
        if (isDark) {
            showToast("Dark mode activated", "info");
        } else {
            showToast("Light mode activated", "info");
        }
    }

    // 4. Live Toast System
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let iconSvg = '';
        if (type === 'success') {
            iconSvg = `<svg viewBox="0 0 24 24" style="width: 18px; height:18px; fill: var(--accent);"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`;
        } else if (type === 'warning') {
            iconSvg = `<svg viewBox="0 0 24 24" style="width: 18px; height:18px; fill: var(--warning);"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`;
        } else if (type === 'danger') {
            iconSvg = `<svg viewBox="0 0 24 24" style="width: 18px; height:18px; fill: var(--danger);"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`;
        } else {
            iconSvg = `<svg viewBox="0 0 24 24" style="width: 18px; height:18px; fill: var(--primary);"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`;
        }

        toast.innerHTML = `
            ${iconSvg}
            <div class="toast-message">${message}</div>
            <div class="toast-close" onclick="this.parentElement.remove()">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Auto remove toast after 4.5 seconds
        setTimeout(() => {
            if (toast) {
                toast.style.animation = 'slideUp 0.3s ease reverse';
                setTimeout(() => { toast.remove(); }, 280);
            }
        }, 4500);
    }

    // 5. Global Table Search Filter
    function globalSearchFilter() {
        const query = document.getElementById('navSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.modern-table tbody tr');
        
        if (rows.length === 0) return;
        
        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>
