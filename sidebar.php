<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Sidebar Navigation - Dedicated 10 SRS Modules
================================================================
*/
$current_page = basename($_SERVER['PHP_SELF']);

// Count unread notifications for badge
$notif_count = 0;
if (isset($_SESSION['academic_db'])) {
    $db_ref = $_SESSION['academic_db'];
    foreach ($db_ref['class_sessions'] ?? [] as $cs) {
        if (($cs['status'] ?? '') === 'Cancelled' || ($cs['status'] ?? '') === 'Not Conducted') $notif_count++;
    }
    foreach ($db_ref['students'] ?? [] as $s) {
        if (($s['attendance_pct'] ?? 80) < 75) $notif_count++;
    }
    foreach ($db_ref['issues'] ?? [] as $iss) {
        if (($iss['status'] ?? '') === 'Pending') $notif_count++;
    }
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:var(--primary);">
                <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9 12 3zm6.82 8.18L12 14.91 5.18 11.18 12 7.45l6.82 3.73zM12 18.72l-5-2.73v-3.72l5 2.73 5-2.73v3.72l-5 2.73z"/>
            </svg>
            <span>Team <span>2</span></span>
        </a>
    </div>

    <ul class="sidebar-menu">

        <li style="padding: .4rem 1rem .15rem; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted);">Overview &amp; Dashboards</li>

        <!-- HOD Dashboard -->
        <li class="sidebar-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span>HOD Dashboard</span>
            </a>
        </li>

        <!-- Daily Monitoring -->
        <li class="sidebar-item <?= $current_page == 'daily_monitoring.php' ? 'active' : '' ?>">
            <a href="daily_monitoring.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                <span>Daily Monitoring</span>
            </a>
        </li>

        <!-- Notifications -->
        <li class="sidebar-item <?= $current_page == 'notifications.php' ? 'active' : '' ?>">
            <a href="notifications.php" class="sidebar-link" style="position:relative;">
                <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                <span>Notifications</span>
                <?php if ($notif_count > 0): ?>
                <span style="background:#ef4444;color:white;font-size:.6rem;font-weight:800;padding:.1rem .35rem;border-radius:99px;margin-left:auto;"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li style="padding: .6rem 1rem .15rem; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); margin-top:.5rem;">Academic Modules</li>

        <!-- Semester Planner -->
        <li class="sidebar-item <?= $current_page == 'calendar.php' ? 'active' : '' ?>">
            <a href="calendar.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                <span>Semester Planner</span>
            </a>
        </li>

        <!-- Attendance Management -->
        <li class="sidebar-item <?= $current_page == 'attendance.php' ? 'active' : '' ?>">
            <a href="attendance.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                <span>Attendance Management</span>
            </a>
        </li>

        <!-- HOD Attendance Monitoring (Dedicated Page) -->
        <li class="sidebar-item <?= $current_page == 'hod_attendance.php' ? 'active' : '' ?>">
            <a href="hod_attendance.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.83 1.97 1.97 1.97 3.45V20h6v-3.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <span>HOD Attendance Monitoring</span>
            </a>
        </li>

        <!-- Student Directory -->
        <li class="sidebar-item <?= $current_page == 'students.php' ? 'active' : '' ?>">
            <a href="students.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <span>Student Directory</span>
            </a>
        </li>

        <!-- Timetable Grid -->
        <li class="sidebar-item <?= $current_page == 'timetable.php' ? 'active' : '' ?>">
            <a href="timetable.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M19 4H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V8h14v12zm0-14H5V6h14v2z"/></svg>
                <span>Timetable Grid</span>
            </a>
        </li>

        <!-- Lab Monitoring -->
        <li class="sidebar-item <?= $current_page == 'labs.php' ? 'active' : '' ?>">
            <a href="labs.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M7 2v2h1v14c0 2.21 1.79 4 4 4s4-1.79 4-4V4h1V2H7zm5 16c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm2-5H10V4h4v9z"/></svg>
                <span>Lab Monitoring</span>
            </a>
        </li>

        <!-- Classroom Status -->
        <li class="sidebar-item <?= $current_page == 'classrooms.php' ? 'active' : '' ?>">
            <a href="classrooms.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                <span>Classroom Status</span>
            </a>
        </li>

        <li style="padding: .6rem 1rem .15rem; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); margin-top:.5rem;">Reports &amp; Config</li>

        <!-- Faculty Allocation -->
        <li class="sidebar-item <?= $current_page == 'faculty.php' ? 'active' : '' ?>">
            <a href="faculty.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <span>Faculty Allocation</span>
            </a>
        </li>

        <!-- Reports & Analytics -->
        <li class="sidebar-item <?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <a href="reports.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 14H7v-2h10v2zm0-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                <span>Reports &amp; Analytics</span>
            </a>
        </li>

        <!-- Settings -->
        <li class="sidebar-item <?= $current_page == 'settings.php' ? 'active' : '' ?>">
            <a href="settings.php" class="sidebar-link">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6-3.6z"/></svg>
                <span>Settings Portal</span>
            </a>
        </li>

    </ul>

    <div class="sidebar-footer">
        <a href="logout.php" style="display:flex;align-items:center;gap:.5rem;color:var(--text-muted);font-size:.78rem;font-weight:600;padding:.5rem 1rem;border-radius:var(--radius-md);transition:all .2s;text-decoration:none;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
            Log Out
        </a>
    </div>
</aside>
