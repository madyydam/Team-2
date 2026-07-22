<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
SRS Module 3 - Faculty Attendance Marking & Roll Call
project/attendance.php
================================================================
*/

include 'header.php';
$db = &$_SESSION['academic_db'];

// Initialize class_sessions if not set
if (!isset($db['class_sessions']) || empty($db['class_sessions'])) {
    $db['class_sessions'] = [
        ['id' => 1, 'subject' => 'CS501', 'faculty' => 'Prof. Balaji A. Chaugule', 'room' => 'Room 301', 'time' => '09:00 AM - 10:00 AM', 'status' => 'Completed', 'reason' => ''],
        ['id' => 2, 'subject' => 'CS502', 'faculty' => 'Dr. Neeti Rathore', 'room' => 'Room 302', 'time' => '10:00 AM - 11:00 AM', 'status' => 'Completed', 'reason' => ''],
        ['id' => 3, 'subject' => 'CS503', 'faculty' => 'Prof. Sumesh S. Shinde', 'room' => 'Programming Lab 1', 'time' => '11:15 AM - 01:15 PM', 'status' => 'Pending', 'reason' => ''],
        ['id' => 4, 'subject' => 'IT501', 'faculty' => 'Dr. Neeti Rathore', 'room' => 'Room 303', 'time' => '02:00 PM - 03:00 PM', 'status' => 'Cancelled', 'reason' => 'Faculty Leave'],
        ['id' => 5, 'subject' => 'EC501', 'faculty' => 'Prof. Ashwini M. Agarwal', 'room' => 'Room 304', 'time' => '03:15 PM - 04:15 PM', 'status' => 'Pending', 'reason' => ''],
    ];
}

$action_toast = '';

// Handle POST actions (P / A roll call buttons & class status updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Toggle Student Attendance (P / A buttons)
    if ($action === 'toggle_student_att') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $new_status = $_POST['status'] ?? 'Present';

        foreach ($db['students'] as &$s) {
            if ($s['id'] === $student_id) {
                if ($new_status === 'Present') {
                    $s['attendance_pct'] = min(100, ($s['attendance_pct'] ?? 80) + 2);
                    $action_toast = "showToast('{$s['name']} marked Present (P). Attendance updated to {$s['attendance_pct']}%', 'success');";
                } else {
                    $s['attendance_pct'] = max(0, ($s['attendance_pct'] ?? 80) - 3);
                    $action_toast = "showToast('{$s['name']} marked Absent (A). Attendance adjusted to {$s['attendance_pct']}%', 'danger');";
                }
                break;
            }
        }
        unset($s);
    }

    // 2. Class session status update
    if ($action === 'update_class_status') {
        $class_id   = intval($_POST['class_id'] ?? 0);
        $new_status = $_POST['status'] ?? 'Pending';
        $reason     = trim($_POST['reason'] ?? '');

        foreach ($db['class_sessions'] as &$c) {
            if ($c['id'] === $class_id) {
                $c['status'] = $new_status;
                $c['reason'] = $reason;

                foreach ($db['timetable'] as &$t_item) {
                    if (($t_item['subject'] ?? '') === $c['subject']) {
                        $t_item['status'] = $new_status;
                    }
                }
                unset($t_item);

                $toast_type = ($new_status === 'Completed' || $new_status === 'Conducted') ? 'success' : (($new_status === 'Cancelled') ? 'danger' : 'info');
                $action_toast = "showToast('Session {$c['subject']} status set to $new_status', '$toast_type');";
                break;
            }
        }
        unset($c);
    }

    // 3. Bulk Mark All Present
    if ($action === 'mark_all_present') {
        foreach ($db['students'] as &$s) {
            $s['attendance_pct'] = min(100, ($s['attendance_pct'] ?? 80) + 2);
        }
        unset($s);
        $action_toast = "showToast('All students marked Present (P) for current session!', 'success');";
    }
}

// Compute Statistics
$total_classes     = count($db['class_sessions']);
$completed_classes = count(array_filter($db['class_sessions'], fn($cs) => in_array($cs['status'], ['Completed', 'Conducted'])));
$pending_classes   = count(array_filter($db['class_sessions'], fn($cs) => $cs['status'] === 'Pending' || $cs['status'] === 'Not Conducted'));
$cancelled_classes = count(array_filter($db['class_sessions'], fn($cs) => $cs['status'] === 'Cancelled'));

$total_students = count($db['students']);
$att_sum = 0;
foreach ($db['students'] as $s) {
    $att_sum += ($s['attendance_pct'] ?? 80);
}
$avg_student_att = $total_students > 0 ? round($att_sum / $total_students, 1) : 0;
?>

<div class="container-fluid">

    <!-- Header -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">📝 Attendance Management</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">SRS Module 3: Daily class completion, roll call marking (P / A), and session logs.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <form action="attendance.php" method="POST" style="display: inline;">
                <input type="hidden" name="action" value="mark_all_present">
                <button type="submit" class="btn btn-accent" style="padding: 0.6rem 1.1rem; font-size: 0.85rem;">✔ Mark All Present (P)</button>
            </form>
            <a href="hod_attendance.php" class="btn btn-primary" style="padding: 0.6rem 1.1rem; font-size: 0.85rem;">🎓 HOD Attendance Monitoring →</a>
        </div>
    </div>

    <!-- Daily Snapshot Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
        <div class="glass-card stat-card" style="border-left: 4px solid var(--primary);">
            <div class="stat-icon-wrapper stat-blue">
                <svg viewBox="0 0 24 24"><path d="M19 3h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $total_classes ?></div>
                <div class="stat-label">Classes Scheduled</div>
            </div>
        </div>

        <div class="glass-card stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-icon-wrapper stat-green">
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $completed_classes ?></div>
                <div class="stat-label">Conducted</div>
            </div>
        </div>

        <div class="glass-card stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-icon-wrapper stat-orange">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $pending_classes ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>

        <div class="glass-card stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-icon-wrapper stat-red">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $cancelled_classes ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
    </div>

    <!-- Layout Grid: Left (Class Snapshot), Right (Student Roll Call P / A Buttons) -->
    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 1.75rem; align-items: start; margin-bottom: 2rem;">

        <!-- Class status list -->
        <div class="glass-card">
            <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Daily Class Snapshot</h3>

            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Class &amp; Time</th>
                            <th>Room / Faculty</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($db['class_sessions'] as $cs):
                            $is_done = in_array($cs['status'], ['Completed', 'Conducted']);
                            $is_canc = ($cs['status'] === 'Cancelled');
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($cs['subject']) ?></div>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($cs['time']) ?></span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;"><?= htmlspecialchars($cs['faculty']) ?></div>
                                    <span style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($cs['room']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $is_done ? 'badge-success' : ($is_canc ? 'badge-danger' : 'badge-warning') ?>">
                                        <?= htmlspecialchars($cs['status']) ?>
                                    </span>
                                    <?php if (!empty($cs['reason'])): ?>
                                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 0.25rem;">(<?= htmlspecialchars($cs['reason']) ?>)</div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if (!$is_done && !$is_canc): ?>
                                        <div style="display: flex; gap: 0.25rem; justify-content: flex-end;">
                                            <form action="attendance.php" method="POST">
                                                <input type="hidden" name="action" value="update_class_status">
                                                <input type="hidden" name="class_id" value="<?= $cs['id'] ?>">
                                                <input type="hidden" name="status" value="Completed">
                                                <button type="submit" class="btn btn-accent" style="padding: 0.35rem 0.5rem; font-size: 0.7rem;">Conduct</button>
                                            </form>
                                            <button class="btn btn-danger" onclick="openCancelModal(<?= $cs['id'] ?>)" style="padding: 0.35rem 0.5rem; font-size: 0.7rem;">Cancel</button>
                                        </div>
                                    <?php else: ?>
                                        <form action="attendance.php" method="POST">
                                            <input type="hidden" name="action" value="update_class_status">
                                            <input type="hidden" name="class_id" value="<?= $cs['id'] ?>">
                                            <input type="hidden" name="status" value="Pending">
                                            <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.5rem; font-size: 0.7rem;">Reset</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Student Roll Call Registry with P & A Buttons -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 class="glass-card-title" style="margin: 0;">Live Roll Call Registry (P / A)</h3>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Avg Att: <strong style="color:var(--primary);"><?= $avg_student_att ?>%</strong></span>
            </div>

            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Roll No</th>
                            <th>Attendance</th>
                            <th style="text-align: right; width: 130px;">Mark (P / A)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($db['students'] as $stud):
                            $att_pct = $stud['attendance_pct'] ?? 80;
                            $badge_cls = $att_pct < 75 ? 'badge-danger' : ($att_pct >= 85 ? 'badge-success' : 'badge-warning');
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);"><?= htmlspecialchars($stud['name']) ?></div>
                                    <span style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($stud['department'] ?? 'IT') ?> - <?= htmlspecialchars($stud['semester'] ?? '5th Sem') ?></span>
                                </td>
                                <td><code style="font-size: 0.78rem;"><?= htmlspecialchars($stud['roll_no']) ?></code></td>
                                <td>
                                    <span class="badge <?= $badge_cls ?>" style="font-size: 0.72rem; font-weight: 700;">
                                        <?= $att_pct ?>%
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                        <!-- P Button -->
                                        <form action="attendance.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_student_att">
                                            <input type="hidden" name="student_id" value="<?= $stud['id'] ?>">
                                            <input type="hidden" name="status" value="Present">
                                            <button type="submit" class="btn btn-accent" title="Mark Present (+2%)" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; font-weight: 800; border-radius: var(--radius-sm); min-width: 32px;">P</button>
                                        </form>

                                        <!-- A Button -->
                                        <form action="attendance.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_student_att">
                                            <input type="hidden" name="student_id" value="<?= $stud['id'] ?>">
                                            <input type="hidden" name="status" value="Absent">
                                            <button type="submit" class="btn btn-danger" title="Mark Absent (-3%)" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; font-weight: 800; border-radius: var(--radius-sm); min-width: 32px;">A</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- Modal Dialog: Cancel Class Reason -->
<div class="modal-backdrop" id="cancelClassModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Record Class Cancellation</h3>
            <button class="modal-close" onclick="document.getElementById('cancelClassModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="attendance.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update_class_status">
                <input type="hidden" name="status" value="Cancelled">
                <input type="hidden" name="class_id" id="cancel_class_id">

                <div class="form-group">
                    <label class="form-label">Cancellation Reason</label>
                    <select class="form-control" name="reason" required>
                        <option value="Faculty Leave">Faculty Leave</option>
                        <option value="College Event">College Event / Holiday</option>
                        <option value="Lab Network Issue">Lab / Network Issue</option>
                        <option value="Power Outage">Electricity / Hardware Fault</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('cancelClassModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCancelModal(classId) {
        document.getElementById('cancel_class_id').value = classId;
        document.getElementById('cancelClassModal').classList.add('active');
    }

    window.addEventListener('DOMContentLoaded', () => {
        <?= $action_toast ?>
    });
</script>

<?php include 'footer.php'; ?>
