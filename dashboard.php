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
                $status = $db['semester_active'] ? 'Started' : 'Suspended';
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

// Calculate Statistics
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

$semester_progress = $db['semester_active'] ? 42 : 0; // Simulated semester completion percentage
// SVG Circular Progress Calculations (Radius 36, Circumference = 2 * PI * 36 ≈ 226)
$stroke_dashoffset = 226 - ($semester_progress / 100) * 226;
?>

<div class="container-fluid">
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-welcome">
            <span class="hero-date">
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                <span>Academic Year: AY 2026-27 | Fall Semester</span>
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
                <div class="badge <?= $db['semester_active'] ? 'badge-success' : 'badge-warning' ?>" style="margin-top: 0.25rem;">
                    <?= $db['semester_active'] ? 'Active Session' : 'Paused Session' ?>
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
                <div class="stat-value"><?= $faculty_present ?> / <?= $total_faculty ?></div>
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
                <div class="stat-value"><?= $labs_running ?> / <?= count($db['labs']) ?></div>
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
                
                <!-- Horizontal Points: Week 1 to Week 5 -->
                <!-- Coordinates calculated: W1=100, W2=200, W3=300, W4=400, W5=500 -->
                <!-- Vertical values: W1=78% (y=54), W2=88% (y=36), W3=65% (y=77), W4=92% (y=29), W5=85% (y=41) -->
                
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
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="action" value="toggle_semester">
                    <button class="btn <?= $db['semester_active'] ? 'btn-danger' : 'btn-accent' ?>" style="width: 100%; height: 90px; flex-direction: column; gap: 0.35rem; border-radius: var(--radius-md);">
                        <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                            <?php if ($db['semester_active']): ?>
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
                            <?php else: ?>
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                            <?php endif; ?>
                        </svg>
                        <span><?= $db['semester_active'] ? 'Pause Semester' : 'Start Semester' ?></span>
                    </button>
                </form>
                
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
                
                <!-- Blank days to shift starting of July 2026 (July 1st is Wednesday -> 2 blank cells) -->
                <div></div><div></div>
                <div class="calendar-day">1</div>
                <div class="calendar-day">2</div>
                <div class="calendar-day">3</div>
                <div class="calendar-day">4</div>
                <div class="calendar-day">5</div>
                
                <div class="calendar-day">6</div>
                <div class="calendar-day">7</div>
                <div class="calendar-day">8</div>
                <div class="calendar-day">9</div>
                <div class="calendar-day">10</div>
                <div class="calendar-day">11</div>
                <div class="calendar-day">12</div>
                
                <div class="calendar-day">13</div>
                <div class="calendar-day font-weight-bold event">14</div>
                <div class="calendar-day active event">15</div> <!-- Today -->
                <div class="calendar-day">16</div>
                <div class="calendar-day">17</div>
                <div class="calendar-day">18</div>
                <div class="calendar-day">19</div>
                
                <div class="calendar-day">20</div>
                <div class="calendar-day">21</div>
                <div class="calendar-day">22</div>
                <div class="calendar-day">23</div>
                <div class="calendar-day">24</div>
                <div class="calendar-day">25</div>
                <div class="calendar-day">26</div>
                
                <div class="calendar-day">27</div>
                <div class="calendar-day">28</div>
                <div class="calendar-day">29</div>
                <div class="calendar-day">30</div>
                <div class="calendar-day">31</div>
            </div>
            
            <!-- Quick list of events -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <?php foreach (array_slice($db['calendar'], 0, 2) as $ev): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; margin-bottom: 0.5rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span class="badge <?= $ev['type'] === 'Holiday' ? 'badge-danger' : 'badge-success' ?>" style="padding: 0.15rem 0.45rem; font-size: 0.6rem;"><?= $ev['type'] ?></span>
                            <span style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($ev['title']) ?></span>
                        </div>
                        <span style="color: var(--text-muted);"><?= date('M d', strtotime($ev['start_date'])) ?></span>
                    </div>
                <?php endforeach; ?>
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

</div>

<!-- Floating Action Button for filing a complaint directly -->
<a href="settings.php#complaint" class="fab" aria-label="Add Ticket" title="Submit Complaint Ticket">
    <svg viewBox="0 0 24 24">
        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
    </svg>
</a>

<!-- Trigger Postback Toast messages -->
<script>
    window.addEventListener('DOMContentLoaded', (event) => {
        <?= $action_toast ?>
    });
</script>

<?php include 'footer.php'; ?>
