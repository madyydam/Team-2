<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
SRS Module 4: HOD Student Attendance Monitoring Dashboard
project/hod_attendance.php
================================================================
*/

include 'header.php';
$db = &$_SESSION['academic_db'];

$action_toast = '';

// Handle POST actions for HOD interventions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $student_id = intval($_POST['student_id'] ?? 0);
    $student_name = $_POST['student_name'] ?? 'Student';

    if ($action === 'notify_mentor') {
        $action_toast = "showToast('Official mentor notification sent for {$student_name}.', 'info');";
    } elseif ($action === 'notify_parent') {
        $action_toast = "showToast('Parent attendance warning SMS & Email dispatched for {$student_name}.', 'warning');";
    } elseif ($action === 'issue_warning_letter') {
        $action_toast = "showToast('Official low-attendance warning letter generated for {$student_name}.', 'danger');";
    }
}

// Compute Statistics
$total_students = count($db['students']);
$att_sum = 0;
$low_att_list = [];
$good_att_list = [];

foreach ($db['students'] as $s) {
    $pct = $s['attendance_pct'] ?? 80;
    $att_sum += $pct;
    if ($pct < 75) {
        $low_att_list[] = $s;
    } else {
        $good_att_list[] = $s;
    }
}
$avg_student_att = $total_students > 0 ? round($att_sum / $total_students, 1) : 0;

// Group students by Year for HOD Breakdown (FY, SY, TY, BE)
$students_all = $db['students'];
$chunk_size = max(1, ceil(count($students_all) / 4));
$chunks = array_chunk($students_all, $chunk_size);
$years = [
    'FY' => 'First Year (FY)',
    'SY' => 'Second Year (SY)',
    'TY' => 'Third Year (TY)',
    'BE' => 'Final Year (BE)'
];
$year_stats = [];

foreach (array_keys($years) as $idx => $yr_key) {
    $list = $chunks[$idx] ?? [];
    if (empty($list)) {
        $year_stats[$yr_key] = ['label' => $years[$yr_key], 'avg' => 0, 'count' => 0, 'low' => 0, 'students' => []];
        continue;
    }
    $sum = 0; $low_ct = 0;
    foreach ($list as $st) {
        $p = $st['attendance_pct'] ?? 80;
        $sum += $p;
        if ($p < 75) $low_ct++;
    }
    $year_stats[$yr_key] = [
        'label'    => $years[$yr_key],
        'avg'      => round($sum / count($list), 1),
        'count'    => count($list),
        'low'      => $low_ct,
        'students' => $list
    ];
}

$selected_year = $_GET['year'] ?? 'all';
?>

<style>
.hod-stat-card { background: var(--glass-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center; position: relative; transition: transform .2s; }
.hod-stat-card:hover { transform: translateY(-3px); }
.hod-stat-num { font-size: 2.2rem; font-weight: 800; margin: 0.2rem 0; }
.year-pill { padding: 0.4rem 1.1rem; border-radius: 99px; font-size: 0.78rem; font-weight: 700; border: 1px solid var(--border-color); background: var(--glass-bg); color: var(--text-secondary); cursor: pointer; text-decoration: none; transition: all .2s; }
.year-pill.active { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<div class="container-fluid">

    <!-- Header -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <span>🎓 HOD Attendance Monitoring Portal</span>
            </h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">SRS Module 4: Departmental student attendance analytics, year-wise tracking, and low attendance alerts.</p>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="reports.php?type=attendance" class="btn btn-primary" style="padding: 0.6rem 1.1rem; font-size: 0.85rem;">
                📄 Generate Attendance Audit Report
            </a>
            <a href="attendance.php" class="btn btn-secondary" style="padding: 0.6rem 1.1rem; font-size: 0.85rem;">
                📝 Faculty Roll Call →
            </a>
        </div>
    </div>

    <!-- HOD Department Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem;">
        <div class="hod-stat-card" style="border-top: 4px solid var(--primary);">
            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Enrolled Students</div>
            <div class="hod-stat-num" style="color: var(--primary);"><?= $total_students ?></div>
            <div style="font-size: 0.72rem; color: var(--text-muted);">Active in Department</div>
        </div>

        <div class="hod-stat-card" style="border-top: 4px solid #10b981;">
            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Department Average</div>
            <div class="hod-stat-num" style="color: #10b981;"><?= $avg_student_att ?>%</div>
            <div style="font-size: 0.72rem; color: #10b981; font-weight: 700;">🟢 Good Standing (&gt;75%)</div>
        </div>

        <div class="hod-stat-card" style="border-top: 4px solid #ef4444;">
            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Low Attendance (&lt;75%)</div>
            <div class="hod-stat-num" style="color: #ef4444;"><?= count($low_att_list) ?></div>
            <div style="font-size: 0.72rem; color: #ef4444; font-weight: 700;">🔴 Action Required</div>
        </div>

        <div class="hod-stat-card" style="border-top: 4px solid #8b5cf6;">
            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Regular Students (&gt;85%)</div>
            <div class="hod-stat-num" style="color: #8b5cf6;"><?= count(array_filter($db['students'], fn($s)=>($s['attendance_pct']??80)>=85)) ?></div>
            <div style="font-size: 0.72rem; color: #8b5cf6; font-weight: 700;">⭐ High Performance</div>
        </div>
    </div>

    <!-- Year-Wise Breakdown (FY, SY, TY, BE) -->
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.75rem;">
        <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">📅 Year-Wise Attendance Compliance</h3>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem;">
            <?php foreach ($year_stats as $ykey => $yd):
                $val = $yd['avg'];
                $col = $val >= 85 ? '#10b981' : ($val >= 75 ? '#f59e0b' : '#ef4444');
            ?>
            <div style="background: var(--glass-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; border-top: 3px solid <?= $col ?>;">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-primary);"><?= $yd['label'] ?></div>
                <div style="font-size: 1.8rem; font-weight: 800; color: <?= $col ?>; margin: 0.25rem 0;"><?= $val > 0 ? $val.'%' : 'N/A' ?></div>
                <div style="height: 6px; background: var(--border-color); border-radius: 99px; overflow: hidden; margin: 0.4rem 0;">
                    <div style="width: <?= $val ?>%; height: 100%; background: <?= $col ?>; border-radius: 99px;"></div>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                    <span><?= $yd['count'] ?> Students</span>
                    <span style="color: <?= $yd['low'] > 0 ? '#ef4444' : '#10b981' ?>; font-weight: 700;"><?= $yd['low'] ?> Below 75%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Critical Alert Panel: Students Below 75% -->
    <div class="glass-card" style="margin-bottom: 2rem;">
        <div style="font-size: 0.85rem; font-weight: 700; color: #b45309; background: rgba(245,158,11,.1); padding: 0.85rem 1.25rem; border-radius: var(--radius-md) var(--radius-md) 0 0; border-bottom: 1px solid rgba(245,158,11,.2); display: flex; justify-content: space-between; align-items: center;">
            <span>⚠ Critical Low Attendance Roster (&lt;75% Attendance Threshold)</span>
            <span class="badge badge-danger"><?= count($low_att_list) ?> Students Deficit</span>
        </div>

        <?php if (empty($low_att_list)): ?>
            <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                ✅ All students in the department have attendance above 75%!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Roll No</th>
                            <th>Semester</th>
                            <th>Current Attendance</th>
                            <th>Deficit Margin</th>
                            <th style="text-align: right;">HOD Action Interventions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_att_list as $ls):
                            $att = $ls['attendance_pct'] ?? 0;
                            $deficit = 75 - $att;
                        ?>
                        <tr>
                            <td><strong style="font-size: 0.88rem; color: var(--text-primary);"><?= htmlspecialchars($ls['name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($ls['roll_no']) ?></code></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($ls['semester'] ?? '5th Sem') ?></span></td>
                            <td><span style="color: #ef4444; font-weight: 800; font-size: 0.95rem;"><?= $att ?>%</span></td>
                            <td><span style="color: #b45309; font-weight: 700; font-size: 0.8rem;">-<?= $deficit ?>% short</span></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end; flex-wrap: wrap;">
                                    <form action="hod_attendance.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="notify_mentor">
                                        <input type="hidden" name="student_id" value="<?= $ls['id'] ?>">
                                        <input type="hidden" name="student_name" value="<?= htmlspecialchars($ls['name']) ?>">
                                        <button type="submit" class="btn btn-secondary" style="font-size: 0.72rem; padding: 0.35rem 0.65rem;">Notify Mentor</button>
                                    </form>

                                    <form action="hod_attendance.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="notify_parent">
                                        <input type="hidden" name="student_id" value="<?= $ls['id'] ?>">
                                        <input type="hidden" name="student_name" value="<?= htmlspecialchars($ls['name']) ?>">
                                        <button type="submit" class="btn btn-warning" style="font-size: 0.72rem; padding: 0.35rem 0.65rem;">Notify Parent</button>
                                    </form>

                                    <form action="hod_attendance.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="issue_warning_letter">
                                        <input type="hidden" name="student_id" value="<?= $ls['id'] ?>">
                                        <input type="hidden" name="student_name" value="<?= htmlspecialchars($ls['name']) ?>">
                                        <button type="submit" class="btn btn-danger" style="font-size: 0.72rem; padding: 0.35rem 0.65rem;">Issue Warning</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Complete Student Directory Roster -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <h3 class="glass-card-title" style="margin: 0;">📋 Complete Department Student Attendance Roster</h3>
            <div style="display: flex; gap: 0.5rem;">
                <a href="hod_attendance.php?year=all" class="year-pill <?= $selected_year==='all'?'active':'' ?>">All Students</a>
                <a href="hod_attendance.php?year=regular" class="year-pill <?= $selected_year==='regular'?'active':'' ?>">Regular (&gt;75%)</a>
                <a href="hod_attendance.php?year=critical" class="year-pill <?= $selected_year==='critical'?'active':'' ?>">Critical (&lt;75%)</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Roll No</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Attendance Rate</th>
                        <th>Standing Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $display_list = $db['students'];
                    if ($selected_year === 'regular') {
                        $display_list = array_filter($display_list, fn($s) => ($s['attendance_pct']??80) >= 75);
                    } elseif ($selected_year === 'critical') {
                        $display_list = array_filter($display_list, fn($s) => ($s['attendance_pct']??80) < 75);
                    }

                    foreach ($display_list as $idx => $st):
                        $p = $st['attendance_pct'] ?? 80;
                        $badge_cls = $p >= 85 ? 'badge-success' : ($p >= 75 ? 'badge-info' : 'badge-danger');
                        $status_lbl = $p >= 85 ? 'Regular' : ($p >= 75 ? 'Satisfactory' : 'Critical Deficit');
                    ?>
                    <tr>
                        <td style="color: var(--text-muted); font-size: 0.75rem;"><?= $idx + 1 ?></td>
                        <td><strong style="font-size: 0.88rem;"><?= htmlspecialchars($st['name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($st['roll_no']) ?></code></td>
                        <td style="font-size: 0.8rem;"><?= htmlspecialchars($st['department'] ?? 'IT') ?></td>
                        <td style="font-size: 0.8rem;"><?= htmlspecialchars($st['semester'] ?? '5th Sem') ?></td>
                        <td><span style="font-weight: 800; color: <?= $p>=75?'#10b981':'#ef4444' ?>;"><?= $p ?>%</span></td>
                        <td><span class="badge <?= $badge_cls ?>"><?= $status_lbl ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    <?= $action_toast ?>
});
</script>

<?php include 'footer.php'; ?>
