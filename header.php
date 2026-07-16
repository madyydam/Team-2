<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Header Include - project/header.php
================================================================
*/

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// 1. DYNAMIC MOCK DATA INITIALIZATION
// ---------------------------------------------------------
if (!isset($_SESSION['academic_db'])) {
    $_SESSION['academic_db'] = [
        'semester_active' => false,
        'departments' => ['Computer Science', 'Information Technology', 'Electronics & Comm.', 'Mechanical Eng.'],
        
        'faculty' => [
            ['id' => 1, 'name' => 'Dr. Balaji Chaugule', 'email' => 'chaugule.b@team2.edu', 'department' => 'Computer Science', 'workload' => 16, 'status' => 'Active'],
            ['id' => 2, 'name' => 'Prof. Alan Turing', 'email' => 'turing.a@eduflow.edu', 'department' => 'Computer Science', 'workload' => 12, 'status' => 'Active'],
            ['id' => 3, 'name' => 'Dr. Grace Hopper', 'email' => 'hopper.g@eduflow.edu', 'department' => 'Information Technology', 'workload' => 14, 'status' => 'Active'],
            ['id' => 4, 'name' => 'Prof. Ada Lovelace', 'email' => 'lovelace.a@eduflow.edu', 'department' => 'Information Technology', 'workload' => 18, 'status' => 'On Leave'],
            ['id' => 5, 'name' => 'Dr. Nikola Tesla', 'email' => 'tesla.n@eduflow.edu', 'department' => 'Electronics & Comm.', 'workload' => 8, 'status' => 'Active'],
        ],
        
        'students' => [
            ['id' => 101, 'name' => 'Alice Smith', 'roll_no' => 'CS-2026-01', 'email' => 'alice@student.edu', 'department' => 'Computer Science', 'semester' => '5th', 'attendance_pct' => 92],
            ['id' => 102, 'name' => 'Bob Johnson', 'roll_no' => 'CS-2026-02', 'email' => 'bob@student.edu', 'department' => 'Computer Science', 'semester' => '5th', 'attendance_pct' => 68], // Warning
            ['id' => 103, 'name' => 'Charlie Brown', 'roll_no' => 'CS-2026-03', 'email' => 'charlie@student.edu', 'department' => 'Computer Science', 'semester' => '5th', 'attendance_pct' => 84],
            ['id' => 104, 'name' => 'Diana Prince', 'roll_no' => 'IT-2026-01', 'email' => 'diana@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 95],
            ['id' => 105, 'name' => 'Ethan Hunt', 'roll_no' => 'IT-2026-02', 'email' => 'ethan@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 61], // Warning
            ['id' => 106, 'name' => 'Fiona Gallagher', 'roll_no' => 'EC-2026-01', 'email' => 'fiona@student.edu', 'department' => 'Electronics & Comm.', 'semester' => '5th', 'attendance_pct' => 88],
            ['id' => 107, 'name' => 'George Clark', 'roll_no' => 'EC-2026-02', 'email' => 'george@student.edu', 'department' => 'Electronics & Comm.', 'semester' => '5th', 'attendance_pct' => 71], // Warning
        ],
        
        'calendar' => [
            ['id' => 1, 'title' => 'Semester Commencement', 'type' => 'Academic Event', 'start_date' => '2026-08-03', 'end_date' => '2026-08-03'],
            ['id' => 2, 'title' => 'Independence Day Holiday', 'type' => 'Holiday', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15'],
            ['id' => 3, 'title' => 'Mid-Semester Examinations', 'type' => 'Academic Event', 'start_date' => '2026-10-12', 'end_date' => '2026-10-17'],
            ['id' => 4, 'title' => 'Winter Vacation Break', 'type' => 'Holiday', 'start_date' => '2026-12-21', 'end_date' => '2026-12-31'],
        ],
        
        'subjects' => [
            ['code' => 'CS501', 'name' => 'Artificial Intelligence', 'credits' => 4, 'semester' => '5th', 'department' => 'Computer Science'],
            ['code' => 'CS502', 'name' => 'Database Management Systems', 'credits' => 3, 'semester' => '5th', 'department' => 'Computer Science'],
            ['code' => 'CS503', 'name' => 'Web Application Development', 'credits' => 4, 'semester' => '5th', 'department' => 'Computer Science'],
            ['code' => 'IT501', 'name' => 'Software Engineering', 'credits' => 3, 'semester' => '5th', 'department' => 'Information Technology'],
            ['code' => 'EC501', 'name' => 'Microprocessors & Controllers', 'credits' => 4, 'semester' => '5th', 'department' => 'Electronics & Comm.'],
        ],

        'timetable' => [
            ['id' => 1, 'subject' => 'CS501', 'faculty_id' => 1, 'room' => 'Room 301', 'time_slot' => '09:00 AM - 10:00 AM', 'day_of_week' => 'Monday', 'department' => 'Computer Science', 'semester' => '5th'],
            ['id' => 2, 'subject' => 'CS502', 'faculty_id' => 2, 'room' => 'Room 302', 'time_slot' => '10:00 AM - 11:00 AM', 'day_of_week' => 'Monday', 'department' => 'Computer Science', 'semester' => '5th'],
            ['id' => 3, 'subject' => 'CS503', 'faculty_id' => 3, 'room' => 'Programming Lab 1', 'time_slot' => '11:15 AM - 01:15 PM', 'day_of_week' => 'Tuesday', 'department' => 'Computer Science', 'semester' => '5th'],
            ['id' => 4, 'subject' => 'IT501', 'faculty_id' => 3, 'room' => 'Room 303', 'time_slot' => '02:00 PM - 03:00 PM', 'day_of_week' => 'Wednesday', 'department' => 'Information Technology', 'semester' => '5th'],
            ['id' => 5, 'subject' => 'EC501', 'faculty_id' => 5, 'room' => 'Room 304', 'time_slot' => '09:00 AM - 10:00 AM', 'day_of_week' => 'Thursday', 'department' => 'Electronics & Comm.', 'semester' => '5th'],
        ],
        
        'labs' => [
            ['id' => 1, 'name' => 'Programming Lab 1', 'status' => 'Conducted', 'systems_working' => 58, 'total_systems' => 60, 'network_status' => 'Excellent', 'equipment_status' => 'Good'],
            ['id' => 2, 'name' => 'Data Science Lab', 'status' => 'Conducted', 'systems_working' => 45, 'total_systems' => 50, 'network_status' => 'Excellent', 'equipment_status' => 'Good'],
            ['id' => 3, 'name' => 'Hardware Lab 1', 'status' => 'Under Maintenance', 'systems_working' => 20, 'total_systems' => 30, 'network_status' => 'Fair', 'equipment_status' => 'Under Maintenance'],
            ['id' => 4, 'name' => 'Embedded Systems Lab', 'status' => 'Free', 'systems_working' => 25, 'total_systems' => 25, 'network_status' => 'Excellent', 'equipment_status' => 'Good'],
        ],
        
        'issues' => [
            ['id' => 1, 'title' => 'Projector Not Working', 'room' => 'Room 301', 'type' => 'Projector', 'status' => 'Pending', 'reported_by' => 'Dr. Balaji Chaugule', 'date' => '2026-07-15'],
            ['id' => 2, 'title' => 'Slow Fiber Network Connectivity', 'room' => 'Hardware Lab 1', 'type' => 'Internet', 'status' => 'In Progress', 'reported_by' => 'Dr. Nikola Tesla', 'date' => '2026-07-14'],
        ],
        
        'announcements' => [
            ['id' => 1, 'title' => 'Syllabus Review Meeting scheduled for Friday', 'time' => '2 hours ago', 'type' => 'general'],
            ['id' => 2, 'title' => 'Midterm examination dates finalized', 'time' => '1 day ago', 'type' => 'exam'],
            ['id' => 3, 'title' => 'Network maintenance notice in block B', 'time' => '2 days ago', 'type' => 'warning'],
        ],
    ];
}

// ---------------------------------------------------------
// 2. ROUTING & ACCESS CONTROL
// ---------------------------------------------------------
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && $current_page != 'login.php' && $current_page != 'index.php') {
    header("Location: login.php");
    exit();
}

$theme_mode = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'light';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest User';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'HOD';
$user_avatar = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team 2 - <?= ucwords(str_replace('.php', '', str_replace('_', ' ', $current_page))) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body class="<?= $theme_mode === 'dark' ? 'dark-mode' : '' ?>">

    <!-- Toast container for live updates -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="app-container">
        <!-- Sidebar and Layout structure starts here -->
        <?php if ($current_page != 'login.php' && $current_page != 'index.php'): ?>
            <?php include 'sidebar.php'; ?>
            <div class="main-content" id="mainContent">
                <header class="navbar">
                    <div class="nav-left">
                        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                            </svg>
                        </button>
                        <div class="search-bar">
                            <svg viewBox="0 0 24 24">
                                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                            </svg>
                            <input type="text" placeholder="Search students, faculty, or logs..." id="navSearchInput" onkeyup="globalSearchFilter()">
                        </div>
                    </div>
                    
                    <div class="nav-right">
                        <!-- Dark Mode Toggle Button -->
                        <button class="nav-icon-btn" id="themeToggleBtn" onclick="toggleTheme()" aria-label="Toggle Dark Mode">
                            <!-- Sun icon -->
                            <svg viewBox="0 0 24 24" id="themeSunIcon" style="display: <?= $theme_mode === 'dark' ? 'block' : 'none' ?>;">
                                <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41l-1.06-1.06zm1.06-12.37c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06c.39-.39.39-1.03 0-1.41zm-12.37 12.37c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06c.39-.39.39-1.03 0-1.41z"/>
                            </svg>
                            <!-- Moon icon -->
                            <svg viewBox="0 0 24 24" id="themeMoonIcon" style="display: <?= $theme_mode === 'dark' ? 'none' : 'block' ?>;">
                                <path d="M12.3 2.03c-1.3-.1-2.6.2-3.8.8-4.4 2.1-6.7 7-5.3 11.7 1.3 4.2 5.1 6.9 9.4 6.9 2.5 0 4.9-1 6.7-2.8 3.5-3.5 3.9-9.1 1-13-1.1-1.5-2.7-2.6-4.5-3.1-.5-.1-1 .2-1 .7s.3.9.8 1.1c4.5 1.5 6.6 6.5 4.6 10.8-1.9 4-6.6 5.8-10.6 3.9-3.4-1.6-5.3-5.4-4.5-9.1.7-3.2 3.4-5.6 6.7-5.9.5 0 .9-.4.9-.9s-.3-.9-.9-.9c-.1 0-.2 0-.3.1z"/>
                            </svg>
                        </button>
                        
                        <!-- Notifications Center Trigger -->
                        <button class="nav-icon-btn" onclick="toggleDropdown('notificationMenu')" aria-label="Notifications">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
                            </svg>
                            <span class="badge-dot"></span>
                            
                            <div class="dropdown-menu" id="notificationMenu" style="width: 280px; padding: 0.75rem;">
                                <h4 style="margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 0.35rem;">Notifications</h4>
                                <?php foreach ($_SESSION['academic_db']['announcements'] as $announce): ?>
                                    <div style="font-size: 0.75rem; padding: 0.45rem 0; border-bottom: 1px dotted var(--border-color); line-height: 1.4;">
                                        <p style="color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($announce['title']) ?></p>
                                        <span style="color: var(--text-muted); font-size: 0.65rem;"><?= $announce['time'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <a href="reports.php" style="text-align: center; display: block; font-size: 0.75rem; color: var(--primary); font-weight: 600; margin-top: 0.5rem;">View Notification Center</a>
                            </div>
                        </button>
                        
                        <!-- User Profile Dropdown -->
                        <div class="profile-dropdown" onclick="toggleDropdown('profileMenu')">
                            <div class="profile-avatar">
                                <?= $user_avatar ?>
                            </div>
                            <div class="profile-info">
                                <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                                <div class="profile-role"><?= htmlspecialchars($user_role) ?> Dashboard</div>
                            </div>
                            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: var(--text-secondary);">
                                <path d="M7 10l5 5 5-5z"/>
                            </svg>
                            
                            <div class="dropdown-menu" id="profileMenu">
                                <a href="settings.php" class="dropdown-item">
                                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                                    System Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-item" style="color: var(--danger);">
                                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                                    Log Out
                                </a>
                            </div>
                        </div>
                    </div>
                </header>
        <?php endif; ?>
