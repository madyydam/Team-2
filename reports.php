<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Reports & Analytics - project/reports.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];

$type = $_GET['type'] ?? 'attendance';
$dept = $_GET['dept'] ?? '';

// Generate report title
$report_title = 'Attendance Compliance Report';
if ($type === 'workload') $report_title = 'Faculty Workload & Allocation Report';
elseif ($type === 'labs') $report_title = 'Laboratory Status & Usage Report';
elseif ($type === 'cancellations') $report_title = 'Class Cancellation & Compliance Report';

// Filter data for reports
$report_rows = [];
if ($type === 'attendance') {
    $report_rows = $db['students'];
    if (!empty($dept)) {
        $report_rows = array_filter($report_rows, function($r) use ($dept) { return $r['department'] === $dept; });
    }
} elseif ($type === 'workload') {
    $report_rows = $db['faculty'];
    if (!empty($dept)) {
        $report_rows = array_filter($report_rows, function($r) use ($dept) { return $r['department'] === $dept; });
    }
} elseif ($type === 'labs') {
    $report_rows = $db['labs'];
} elseif ($type === 'cancellations') {
    // If sessions not set, load default
    if (!isset($db['class_sessions'])) {
        $db['class_sessions'] = [
            ['id' => 1, 'subject' => 'CS501', 'faculty' => 'Dr. Balaji Chaugule', 'room' => 'Room 301', 'time' => '09:00 AM - 10:00 AM', 'status' => 'Completed', 'reason' => ''],
            ['id' => 2, 'subject' => 'CS502', 'faculty' => 'Prof. Alan Turing', 'room' => 'Room 302', 'time' => '10:00 AM - 11:00 AM', 'status' => 'Completed', 'reason' => ''],
            ['id' => 3, 'subject' => 'CS503', 'faculty' => 'Dr. Grace Hopper', 'room' => 'Programming Lab 1', 'time' => '11:15 AM - 01:15 PM', 'status' => 'Pending', 'reason' => ''],
            ['id' => 4, 'subject' => 'IT501', 'faculty' => 'Prof. Ada Lovelace', 'room' => 'Room 303', 'time' => '02:00 PM - 03:00 PM', 'status' => 'Cancelled', 'reason' => 'Faculty Leave'],
            ['id' => 5, 'subject' => 'EC501', 'faculty' => 'Dr. Nikola Tesla', 'room' => 'Room 304', 'time' => '03:15 PM - 04:15 PM', 'status' => 'Pending', 'reason' => ''],
        ];
    }
    $report_rows = array_filter($db['class_sessions'], function($c) { return $c['status'] === 'Cancelled'; });
}
?>

<div class="container-fluid">
    
    <!-- Title Section -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Reports & Analytics Console</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Review comprehensive semester parameters, download spreadsheets, or print administrative audits.</p>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-secondary" onclick="window.print()">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                Print Report
            </button>
            <button class="btn btn-primary" onclick="showToast('Exporting to spreadsheet format. Download will start in a moment...', 'success')">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
                Export Excel
            </button>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.25rem 1.75rem;">
        <form action="reports.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="min-width: 180px;">
                <label class="form-label">Report Category</label>
                <select class="form-control" name="type" onchange="this.form.submit()">
                    <option value="attendance" <?= $type === 'attendance' ? 'selected' : '' ?>>Attendance Compliance</option>
                    <option value="workload" <?= $type === 'workload' ? 'selected' : '' ?>>Faculty Workload</option>
                    <option value="labs" <?= $type === 'labs' ? 'selected' : '' ?>>Lab Status Registry</option>
                    <option value="cancellations" <?= $type === 'cancellations' ? 'selected' : '' ?>>Class Cancellations</option>
                </select>
            </div>
            
            <?php if ($type === 'attendance' || $type === 'workload'): ?>
                <div style="min-width: 180px;">
                    <label class="form-label">Department</label>
                    <select class="form-control" name="dept" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($db['departments'] as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $dept === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Report Output Card -->
    <div class="glass-card">
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary);"><?= $report_title ?></h2>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Generated on: <?= date('Y-m-d H:i:s') ?> | Scope: <?= empty($dept) ? 'Institution-wide' : htmlspecialchars($dept) ?></span>
        </div>

        <div class="table-responsive">
            <!-- 1. Attendance Report -->
            <?php if ($type === 'attendance'): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Student Roll</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Compliance Rate</th>
                            <th>Audit Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_rows as $row): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($row['roll_no']) ?></code></td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td>
                                    <span class="badge <?= $row['attendance_pct'] < 75 ? 'badge-danger' : 'badge-success' ?>">
                                        <?= $row['attendance_pct'] ?>%
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: <?= $row['attendance_pct'] < 75 ? 'var(--danger)' : 'var(--accent)' ?>;">
                                        <?= $row['attendance_pct'] < 75 ? 'Critical Alert' : 'Compliant' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <!-- 2. Workload Report -->
            <?php elseif ($type === 'workload'): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID Code</th>
                            <th>Faculty Name</th>
                            <th>Department</th>
                            <th>Weekly Load</th>
                            <th>Status Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_rows as $row): ?>
                            <tr>
                                <td><code>FAC-00<?= $row['id'] ?></code></td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><strong><?= $row['workload'] ?> Hours</strong></td>
                                <td>
                                    <span class="badge <?= $row['status'] === 'Active' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <!-- 3. Labs Report -->
            <?php elseif ($type === 'labs'): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Laboratory Room</th>
                            <th>Allocated Status</th>
                            <th>Systems Working Ratio</th>
                            <th>Equipment Audit</th>
                            <th>Network Speed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_rows as $row): ?>
                            <tr>
                                <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                                <td>
                                    <span class="badge <?= $row['status'] === 'Conducted' ? 'badge-success' : ($row['status'] === 'Free' ? 'badge-info' : 'badge-danger') ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td><strong><?= $row['systems_working'] ?> / <?= $row['total_systems'] ?> Working</strong> (<?= round(($row['systems_working']/$row['total_systems'])*100) ?>%)</td>
                                <td><?= $row['equipment_status'] ?></td>
                                <td><?= $row['network_status'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <!-- 4. Class Cancellations Report -->
            <?php elseif ($type === 'cancellations'): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Session Subject</th>
                            <th>Time block</th>
                            <th>Classroom Assigned</th>
                            <th>Assigned Instructor</th>
                            <th>Cancellation Justification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_rows)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem 0; color: var(--text-muted);">
                                    No class cancellations recorded in the active snapshot database.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($report_rows as $row): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--danger);"><?= htmlspecialchars($row['subject']) ?></td>
                                    <td><?= htmlspecialchars($row['time']) ?></td>
                                    <td><?= htmlspecialchars($row['room']) ?></td>
                                    <td><?= htmlspecialchars($row['faculty']) ?></td>
                                    <td><span style="font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($row['reason']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
