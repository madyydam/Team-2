<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Main Dashboard - project/dashboard.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];

// Auto-correct stale session names from old code
if (isset($_SESSION['user_name']) && $_SESSION['user_name'] === 'Dr. Andrew Ng') {
    $_SESSION['user_name'] = 'Dr. Balaji Chaugule';
}
// Also patch global $user_name used by header
$user_name = $_SESSION['user_name'] ?? 'Guest User';
$user_avatar = strtoupper(substr($user_name, 0, 1));

// Handle quick actions postbacks
$action_toast = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_semester':
                $db['semester_active'] = !$db['semester_active'];
                if ($db['semester_active']) {
                    $db['academic_year'] = $_POST['academic_year'] ?? 'AY 2026-27';
                    $db['semester_name'] = $_POST['semester_name'] ?? 'Semester 1';
                    $status = 'Started (' . addslashes($db['academic_year'] . ' - ' . $db['semester_name']) . ')';
                } else {
                    $status = 'Ended';
                }
                $type = $db['semester_active'] ? 'success' : 'warning';
                $action_toast = "showToast('Academic Semester has been successfully $status!', '$type');";
                break;
            case 'resolve_issue':
                $issue_id = intval($_POST['issue_id'] ?? 0);
                foreach ($db['issues'] as &$issue) {
                    if ($issue['id'] === $issue_id) {
                        $issue['status'] = 'Resolved';
                        $action_toast = "showToast('Issue ticket #{$issue_id} marked as Resolved.', 'success');";
                        break;
                    }
                }
                break;
        }
    }
}

// Calculate Statistics - conditional on semester being active
$is_semester_active = !empty($db['semester_active']);

if ($is_semester_active) {
    $total_classes = count($db['timetable']);
    $faculty_present = 0;
    foreach ($db['faculty'] as $f) {
        if ($f['status'] === 'Active') $faculty_present++;
    }
    $total_faculty = count($db['faculty']);

    $total_students = count($db['students']);
    $attendance_sum = 0;
    foreach ($db['students'] as $s) {
        $attendance_sum += $s['attendance_pct'];
    }
    $avg_attendance = $total_students > 0 ? round($attendance_sum / $total_students, 1) : 0;

    $labs_running = 0;
    foreach ($db['labs'] as $l) {
        if ($l['status'] === 'Conducted') $labs_running++;
    }

    $pending_issues = 0;
    foreach ($db['issues'] as $i) {
        if ($i['status'] === 'Pending') $pending_issues++;
    }

    $semester_progress = 42; // Active semester completion percentage
    $session_badge = 'Active Session';
    $session_badge_class = 'badge-success';
} else {
    $total_classes = 0;
    $faculty_present = 0;
    $total_faculty = 0;
    $avg_attendance = 0;
    $labs_running = 0;
    $pending_issues = 0;
    $semester_progress = 0;
    $session_badge = 'Not Started / Ended';
    $session_badge_class = 'badge-warning';
}

// SVG Circular Progress Calculations (Radius 36, Circumference = 2 * PI * 36 ≈ 226)
$stroke_dashoffset = 226 - ($semester_progress / 100) * 226;
?>

<div class="container-fluid">
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-welcome">
            <span class="hero-date">
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                <span>Academic Year: <?= htmlspecialchars($db['academic_year'] ?? 'AY 2026-27') ?> | <?= htmlspecialchars($db['semester_name'] ?? 'Semester 1') ?></span>
            </span>
            <h1 style="margin-top: 0.75rem;">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
            <p>Here is your daily administrative digest and academic roadmap for today.</p>
        </div>
        
        <!-- Semester Progress Circle -->
        <div class="progress-ring-container glass-card" style="padding: 0.75rem 1.25rem; display: flex; align-items: center; gap: 1rem; margin: 0; min-width: 250px;">
            <svg class="progress-ring" width="80" height="80">
                <circle class="progress-ring-circle" stroke="var(--border-color)" stroke-width="6" fill="transparent" r="32" cx="40" cy="40"/>
                <circle class="progress-ring-circle" stroke="var(--primary)" stroke-width="6" stroke-dasharray="201" stroke-dashoffset="<?= 201 - ($semester_progress / 100) * 201 ?>" fill="transparent" r="32" cx="40" cy="40" stroke-linecap="round"/>
            </svg>
            <div class="progress-ring-text">
                <div class="progress-ring-percent"><?= $semester_progress ?>%</div>
                <div class="progress-ring-label">Semester Progress</div>
                <div class="badge <?= $session_badge_class ?>" style="margin-top: 0.25rem;">
                    <?= $session_badge ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards Grid -->
    <div class="stats-grid">
        <!-- Stats Card 1 -->
        <div class="glass-card stat-card">
            <div class="stat-icon-wrapper stat-blue">
                <svg viewBox="0 0 24 24"><path d="M5 4h14v2H5V4zm0 5h14v2H5V9zm0 5h14v2H5v-2zm0 5h14v2H5v-2z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $total_classes ?></div>
                <div class="stat-label">Scheduled Classes</div>
            </div>
        </div>
        
        <!-- Stats Card 2 -->
        <div class="glass-card stat-card">
            <div class="stat-icon-wrapper stat-green">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $is_semester_active ? "$faculty_present / $total_faculty" : "0 / 0" ?></div>
                <div class="stat-label">Faculty Present</div>
            </div>
        </div>
        
        <!-- Stats Card 3 -->
        <div class="glass-card stat-card">
            <div class="stat-icon-wrapper stat-purple">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 14H7v-2h10v2zm0-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $avg_attendance ?>%</div>
                <div class="stat-label">Avg Attendance</div>
            </div>
        </div>
        
        <!-- Stats Card 4 -->
        <div class="glass-card stat-card">
            <div class="stat-icon-wrapper stat-orange">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H5v-2h4V7h2v4h4v2z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $is_semester_active ? "$labs_running / " . count($db['labs']) : "0 / 0" ?></div>
                <div class="stat-label">Labs Conducted</div>
            </div>
        </div>
        
        <!-- Stats Card 5 -->
        <div class="glass-card stat-card">
            <div class="stat-icon-wrapper stat-red">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $pending_issues ?></div>
                <div class="stat-label">Pending Issues</div>
            </div>
        </div>
    </div>

    <!-- Charts & Action Section Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Chart Widget -->
        <div class="glass-card">
            <div class="glass-card-header">
                <h3 class="glass-card-title">Attendance Trends (Weekly)</h3>
                <span class="badge badge-info">Target Metric: 75%</span>
            </div>
            
            <!-- SVG Line Chart Mockup -->
            <svg class="trend-chart-svg" viewBox="0 0 600 180">
                <!-- Grid Lines -->
                <line x1="50" y1="20" x2="550" y2="20" class="trend-grid-line" />
                <line x1="50" y1="65" x2="550" y2="65" class="trend-grid-line" />
                <line x1="50" y1="110" x2="550" y2="110" class="trend-grid-line" />
                <line x1="50" y1="150" x2="550" y2="150" class="trend-grid-line" />
                
                <!-- Axis Labels -->
                <text x="15" y="25" fill="var(--text-muted)" font-size="10">100%</text>
                <text x="15" y="70" fill="var(--text-muted)" font-size="10">75%</text>
                <text x="15" y="115" fill="var(--text-muted)" font-size="10">50%</text>
                <text x="15" y="155" fill="var(--text-muted)" font-size="10">0%</text>
                
                <?php if ($is_semester_active): ?>
                    <!-- Area Fill Gradient -->
                    <defs>
                        <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--primary)" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="var(--primary)" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <path d="M 100 150 L 100 54 L 200 36 L 300 77 L 400 29 L 500 41 L 500 150 Z" fill="url(#chartGradient)" />
                    
                    <!-- Trend line -->
                    <path d="M 100 54 L 200 36 L 300 77 L 400 29 L 500 41" class="trend-path-primary" />
                    
                    <!-- Interactive dots -->
                    <circle cx="100" cy="54" r="5" fill="var(--primary)" stroke="white" stroke-width="2" />
                    <circle cx="200" cy="36" r="5" fill="var(--primary)" stroke="white" stroke-width="2" />
                    <circle cx="300" cy="77" r="5" fill="var(--danger)" stroke="white" stroke-width="2" />
                    <circle cx="400" cy="29" r="5" fill="var(--primary)" stroke="white" stroke-width="2" />
                    <circle cx="500" cy="41" r="5" fill="var(--primary)" stroke="white" stroke-width="2" />
                <?php else: ?>
                    <!-- Flat line when semester not active -->
                    <path d="M 100 150 L 200 150 L 300 150 L 400 150 L 500 150" class="trend-path-primary" style="opacity:0.3; stroke-dasharray: 4;" />
                    <circle cx="100" cy="150" r="4" fill="var(--text-muted)" />
                    <circle cx="200" cy="150" r="4" fill="var(--text-muted)" />
                    <circle cx="300" cy="150" r="4" fill="var(--text-muted)" />
                    <circle cx="400" cy="150" r="4" fill="var(--text-muted)" />
                    <circle cx="500" cy="150" r="4" fill="var(--text-muted)" />
                    <text x="300" y="100" text-anchor="middle" fill="var(--text-muted)" font-size="12" font-weight="500">Semester Not Started (Start Semester to track attendance)</text>
                <?php endif; ?>
                
                <!-- Week labels -->
                <text x="90" y="172" fill="var(--text-muted)" font-size="11" font-weight="500">Week 1</text>
                <text x="190" y="172" fill="var(--text-muted)" font-size="11" font-weight="500">Week 2</text>
                <text x="290" y="172" fill="var(--text-muted)" font-size="11" font-weight="500">Week 3</text>
                <text x="390" y="172" fill="var(--text-muted)" font-size="11" font-weight="500">Week 4</text>
                <text x="490" y="172" fill="var(--text-muted)" font-size="11" font-weight="500">Week 5</text>
            </svg>
        </div>
        
        <!-- Quick Actions Panel -->
        <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div class="glass-card-header" style="margin-bottom: 1rem;">
                <h3 class="glass-card-title">Console Actions</h3>
            </div>
            
            <div class="actions-grid">
                <!-- Semester activation toggle -->
                <?php if ($db['semester_active']): ?>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="toggle_semester">
                        <button type="submit" class="btn btn-danger" style="width: 100%; height: 90px; flex-direction: column; gap: 0.35rem; border-radius: var(--radius-md);">
                            <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
                            </svg>
                            <span>End Semester</span>
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn btn-accent" onclick="document.getElementById('startSemesterModal').classList.add('active')" style="width: 100%; height: 90px; flex-direction: column; gap: 0.35rem; border-radius: var(--radius-md);">
                        <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                        </svg>
                        <span>Start Semester</span>
                    </button>
                <?php endif; ?>
                
                <a href="reports.php" class="btn btn-primary" style="height: 90px; flex-direction: column; gap: 0.35rem; border-radius: var(--radius-md);">
                    <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 14H7v-2h10v2zm0-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                    <span>Generate Report</span>
                </a>
                
                <a href="faculty.php" class="btn btn-secondary" style="height: 90px; flex-direction: column; gap: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    <span>Assign Subjects</span>
                </a>
                
                <a href="timetable.php" class="btn btn-secondary" style="height: 90px; flex-direction: column; gap: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                        <path d="M19 4H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V8h14v12zm0-14H5V6h14v2z"/>
                    </svg>
                    <span>View Timetable</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Below: Calendar Widgets & Active Tickets Timeline -->
    <div style="display: grid; grid-template-columns: 1.2fr 1.8fr; gap: 1.5rem;">
        
        <!-- Upcoming events -->
        <div class="glass-card">
            <div class="glass-card-header">
                <h3 class="glass-card-title">Academic Schedule Preview</h3>
                <a href="calendar.php" style="font-size: 0.75rem; color: var(--primary); font-weight:600;">Full Calendar</a>
            </div>
            
            <div class="calendar-widget-grid" style="margin-bottom: 1.5rem;">
                <div class="calendar-header-day">M</div>
                <div class="calendar-header-day">T</div>
                <div class="calendar-header-day">W</div>
                <div class="calendar-header-day">T</div>
                <div class="calendar-header-day">F</div>
                <div class="calendar-header-day">S</div>
                <div class="calendar-header-day">S</div>
                
                <?php
                // July 2026 details (Mocking current view of dashboard)
                $year = 2026;
                $month = 7;
                $today_day = 15; // Active highlighted day
                $first_day_of_week = 3; // Wednesday (1=Mon, 2=Tue, 3=Wed, ...)
                $blank_cells = $first_day_of_week - 1; // 2 blank cells
                $days_in_month = 31;
                
                // Render blank cells
                for ($i = 0; $i < $blank_cells; $i++) {
                    echo '<div></div>';
                }
                
                // Render days dynamically
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    
                    // Check if this day falls within any event duration in the calendar
                    $has_event = false;
                    $event_titles = [];
                    foreach ($db['calendar'] as $ev) {
                        $start = $ev['start_date'];
                        $end = $ev['end_date'] ?? $start;
                        if ($date_str >= $start && $date_str <= $end) {
                            $has_event = true;
                            $event_titles[] = $ev['title'];
                        }
                    }
                    
                    $is_today = ($day == $today_day);
                    
                    $classes = ['calendar-day'];
                    if ($is_today) $classes[] = 'active';
                    if ($has_event) $classes[] = 'event';
                    
                    $class_attr = implode(' ', $classes);
                    $title_attr = !empty($event_titles) ? 'title="' . htmlspecialchars(implode(', ', $event_titles)) . '"' : '';
                    
                    echo '<div class="' . $class_attr . '" ' . $title_attr . '>' . $day . '</div>';
                }
                ?>
            </div>
            
            <!-- Quick list of events -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; max-height: 180px; overflow-y: auto;">
                <?php if (empty($db['calendar'])): ?>
                    <div style="color: var(--text-muted); font-size: 0.78rem; text-align: center; padding: 0.5rem 0;">No upcoming milestones</div>
                <?php else: ?>
                    <?php foreach ($db['calendar'] as $ev): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; margin-bottom: 0.5rem;">
                            <div style="display: flex; gap: 0.5rem; align-items: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 75%;">
                                <span class="badge <?= $ev['type'] === 'Holiday' ? 'badge-danger' : ($ev['type'] === 'Exam' ? 'badge-warning' : 'badge-success') ?>" style="padding: 0.15rem 0.45rem; font-size: 0.6rem; flex-shrink: 0;"><?= htmlspecialchars($ev['type']) ?></span>
                                <span style="font-weight: 500; color: var(--text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= htmlspecialchars($ev['title']) ?>"><?= htmlspecialchars($ev['title']) ?></span>
                            </div>
                            <span style="color: var(--text-muted); font-size: 0.72rem; flex-shrink: 0;"><?= date('M d', strtotime($ev['start_date'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Infrastructure Maintenance Issues / Announcements -->
        <div class="glass-card">
            <div class="glass-card-header">
                <h3 class="glass-card-title">Live Infrastructure Complaints</h3>
                <span class="badge badge-danger"><?= $pending_issues ?> Active</span>
            </div>
            
            <div class="timeline" style="margin-top: 0.5rem;">
                <?php if (empty($db['issues'])): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        <h3>Zero Active Complaints</h3>
                        <p>All projectors, smartboards, and labs are fully operational.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($db['issues'] as $issue): ?>
                        <div class="timeline-item">
                            <span class="timeline-dot <?= $issue['status'] === 'Pending' ? 'danger' : 'accent' ?>"></span>
                            <div class="timeline-time"><?= $issue['date'] ?> | Room: <?= $issue['room'] ?></div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div class="timeline-title"><?= htmlspecialchars($issue['title']) ?></div>
                                    <div class="timeline-desc">Type: <?= $issue['type'] ?> | Reported by: <?= htmlspecialchars($issue['reported_by']) ?></div>
                                </div>
                                <div>
                                    <?php if ($issue['status'] !== 'Resolved'): ?>
                                        <form action="dashboard.php" method="POST">
                                            <input type="hidden" name="action" value="resolve_issue">
                                            <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                                            <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.7rem;">Mark Resolved</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-success">Resolved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mini Timetable Grid Section -->
    <div class="glass-card" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
        <div class="glass-card-header" style="margin-bottom: 0.75rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 class="glass-card-title">Semester Timetable Grid (Mini Version)</h3>
                <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.2rem;">Quick view of scheduled courses. Filter by semester to view specific department sessions.</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <select id="miniTimetableSemFilter" onchange="filterMiniTimetable()" style="width: auto; padding: 0.4rem 1.75rem 0.4rem 0.75rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px; border: 1px solid var(--border-color); background: var(--glass-bg); color: var(--text-primary); cursor: pointer; min-width: 140px;">
                    <option value="all">All Semesters</option>
                    <option value="1st">1st Semester</option>
                    <option value="3rd">3rd Semester</option>
                    <option value="5th" selected>5th Semester</option>
                    <option value="7th">7th Semester</option>
                </select>
                
                <a href="timetable.php" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem; font-weight: 600; border: 1px solid var(--border-color);">
                    Manage
                    <svg viewBox="0 0 24 24" style="width: 12px; height: 12px; fill: currentColor;"><path d="M5 13h11.86l-5.43 5.43 1.42 1.42L21.14 12l-8.29-8.29-1.42 1.42L16.86 11H5v2z"/></svg>
                </a>
            </div>
        </div>
        
        <?php
        if (!function_exists('resolveFacNameLocal')) {
            function resolveFacNameLocal($id, $list) {
                foreach ($list as $f) {
                    if ($f['id'] == $id) return $f['name'];
                }
                return 'Unknown Faculty';
            }
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $slots = [
            '09:00 AM - 10:00 AM',
            '10:00 AM - 11:00 AM',
            '11:15 AM - 01:15 PM',
            '02:00 PM - 03:00 PM',
            '03:15 PM - 04:15 PM'
        ];
        ?>
        <div class="table-responsive">
            <table class="modern-table" style="border: 1px solid var(--border-color); width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="width: 110px; background: rgba(15,23,42,0.03); padding: 0.5rem; font-size: 0.68rem; text-align: left;">Day \ Time</th>
                        <?php foreach ($slots as $slot): ?>
                            <th style="text-align: center; font-size: 0.65rem; padding: 0.5rem;"><?= $slot ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                        <tr>
                            <td style="font-weight: 700; background: rgba(15,23,42,0.01); color: var(--text-primary); font-size: 0.75rem; padding: 0.6rem 0.5rem; border-bottom: 1px solid var(--border-color);"><?= $day ?></td>
                            <?php foreach ($slots as $slot): ?>
                                <td class="timetable-cell" style="text-align: center; vertical-align: middle; padding: 0.4rem; border-bottom: 1px solid var(--border-color); border-left: 1px solid var(--border-color);">
                                    <?php 
                                    $entries = [];
                                    foreach ($db['timetable'] as $item) {
                                        if ($item['day_of_week'] === $day && $item['time_slot'] === $slot) {
                                            $entries[] = $item;
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($entries)): ?>
                                        <?php foreach ($entries as $entry): ?>
                                            <?php
                                            $st = 'Pending';
                                            if (isset($db['class_sessions'])) {
                                                foreach ($db['class_sessions'] as $cs) {
                                                    if ($cs['subject'] === $entry['subject']) {
                                                        $st = $cs['status'];
                                                        break;
                                                    }
                                                }
                                            }
                                            $cardBg = ($st === 'Completed') ? 'background: rgba(16, 185, 129, 0.12); border: 1.5px solid #10b981;' : (($st === 'Cancelled') ? 'background: rgba(239, 68, 68, 0.12); border: 1.5px solid #ef4444;' : 'background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.15);');
                                            $titleCol = ($st === 'Completed') ? '#047857' : (($st === 'Cancelled') ? '#b91c1c' : 'var(--primary)');
                                            $tagBadge = ($st === 'Completed') ? '<span style="font-size: 0.52rem; background: #10b981; color: #fff; padding: 0.05rem 0.25rem; border-radius: 4px; font-weight: 700;">Conducted</span>' : (($st === 'Cancelled') ? '<span style="font-size: 0.52rem; background: #ef4444; color: #fff; padding: 0.05rem 0.25rem; border-radius: 4px; font-weight: 700;">Cancelled</span>' : '<span style="font-size: 0.55rem; background: rgba(37, 99, 235, 0.1); color: var(--primary); padding: 0.05rem 0.25rem; border-radius: 4px; font-weight: 700;">' . htmlspecialchars($entry['semester']) . ' Sem</span>');
                                            ?>
                                            <!-- Booked Slot Container -->
                                            <div class="timetable-entry" data-semester="<?= htmlspecialchars($entry['semester']) ?>" style="<?= $cardBg ?> border-radius: 6px; padding: 0.35rem 0.5rem; text-align: left; margin-bottom: 0.25rem;">
                                                <div style="font-weight: 700; font-size: 0.72rem; color: <?= $titleCol ?>;"><?= htmlspecialchars($entry['subject']) ?></div>
                                                <div style="font-size: 0.62rem; color: var(--text-secondary); margin: 0.05rem 0; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars(resolveFacNameLocal($entry['faculty_id'], $db['faculty'])) ?>">
                                                    <?= htmlspecialchars(resolveFacNameLocal($entry['faculty_id'], $db['faculty'])) ?>
                                                </div>
                                                <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                                                    <span><?= htmlspecialchars($entry['room']) ?></span>
                                                    <?= $tagBadge ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <!-- Placeholder for Free Slot (hidden by default when match exists) -->
                                        <span class="free-slot-placeholder" style="color: var(--text-muted); font-size: 0.68rem; font-weight: 400; display: none;">- Free -</span>
                                    <?php else: ?>
                                        <span class="free-slot-placeholder" style="color: var(--text-muted); font-size: 0.68rem; font-weight: 400;">- Free -</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Floating Action Button for filing a complaint directly -->
<!-- Modal Dialog: Start Semester Configuration -->
<div class="modal-backdrop" id="startSemesterModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Configure & Start Semester</h3>
            <button class="modal-close" onclick="document.getElementById('startSemesterModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="dashboard.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="toggle_semester">
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary);">Academic Year</label>
                    <select class="form-control" name="academic_year" required style="width: 100%;">
                        <option value="AY 2026-27" <?= ($db['academic_year'] ?? '') === 'AY 2026-27' ? 'selected' : '' ?>>AY 2026-27 (Current)</option>
                        <option value="AY 2027-28" <?= ($db['academic_year'] ?? '') === 'AY 2027-28' ? 'selected' : '' ?>>AY 2027-28</option>
                        <option value="AY 2028-29" <?= ($db['academic_year'] ?? '') === 'AY 2028-29' ? 'selected' : '' ?>>AY 2028-29</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary);">Semester Term</label>
                    <select class="form-control" name="semester_name" required style="width: 100%;">
                        <option value="Semester 1" <?= ($db['semester_name'] ?? '') === 'Semester 1' ? 'selected' : '' ?>>Semester 1</option>
                        <option value="Semester 2" <?= ($db['semester_name'] ?? '') === 'Semester 2' ? 'selected' : '' ?>>Semester 2</option>
                    </select>
                </div>

                <div style="font-size: 0.78rem; background: rgba(37, 99, 235, 0.04); border-left: 3px solid var(--primary); padding: 0.75rem 1rem; border-radius: 4px; margin-top: 1rem; color: var(--text-secondary); line-height: 1.45;">
                    💡 Starting the academic term will activate the syllabus planner, enable student attendance recording, and resume laboratory tracking metrics.
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('startSemesterModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Activate Term</button>
            </div>
        </form>
    </div>
</div>

<a href="settings.php#complaint" class="fab" aria-label="Add Ticket" title="Submit Complaint Ticket">
    <svg viewBox="0 0 24 24">
        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
    </svg>
</a>

<!-- Trigger Postback Toast messages & Mini Timetable Filtering -->
<script>
    function filterMiniTimetable() {
        const sem = document.getElementById('miniTimetableSemFilter').value;
        const cells = document.querySelectorAll('.timetable-cell');
        
        cells.forEach(cell => {
            const entries = cell.querySelectorAll('.timetable-entry');
            const placeholder = cell.querySelector('.free-slot-placeholder');
            
            if (entries.length === 0) return; // Always keep standard empty slots as Free
            
            let visibleCount = 0;
            entries.forEach(entry => {
                const entrySem = entry.getAttribute('data-semester');
                if (sem === 'all' || entrySem === sem) {
                    entry.style.display = 'block';
                    visibleCount++;
                } else {
                    entry.style.display = 'none';
                }
            });
            
            if (visibleCount > 0) {
                if (placeholder) placeholder.style.display = 'none';
            } else {
                if (placeholder) placeholder.style.display = 'inline';
            }
        });
    }

    window.addEventListener('DOMContentLoaded', (event) => {
        <?= $action_toast ?>
        // Initialize mini timetable filter
        filterMiniTimetable();
    });
</script>

<?php include 'footer.php'; ?>
