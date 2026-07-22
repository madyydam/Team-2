<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Daily Class Monitoring Dashboard - SRS Module 3, Feature 2, 5, 9, 10
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$is_semester_active = !empty($db['semester_active']);

// Ensure class_sessions initialized
if (!isset($db['class_sessions'])) {
    $db['class_sessions'] = [
        ['id'=>1,'subject'=>'CS501','faculty'=>'Prof. Balaji A. Chaugule','room'=>'Room 301','time'=>'09:00 AM - 10:00 AM','status'=>'Conducted','reason'=>''],
        ['id'=>2,'subject'=>'CS502','faculty'=>'Dr. Neeti Rathore','room'=>'Room 302','time'=>'10:00 AM - 11:00 AM','status'=>'Conducted','reason'=>''],
        ['id'=>3,'subject'=>'CS503','faculty'=>'Prof. Sumesh S. Shinde','room'=>'Programming Lab 1','time'=>'11:15 AM - 01:15 PM','status'=>'Not Conducted','reason'=>''],
        ['id'=>4,'subject'=>'IT501','faculty'=>'Dr. Neeti Rathore','room'=>'Room 303','time'=>'02:00 PM - 03:00 PM','status'=>'Cancelled','reason'=>'Faculty Leave'],
        ['id'=>5,'subject'=>'EC501','faculty'=>'Prof. Ashwini M. Agarwal','room'=>'Room 304','time'=>'03:15 PM - 04:15 PM','status'=>'Not Conducted','reason'=>''],
    ];
}

// Handle POST: update class status from timetable compliance
$action_toast = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_class_status') {
        $class_id  = intval($_POST['class_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        $reason     = trim($_POST['reason'] ?? '');
        foreach ($db['class_sessions'] as &$cs) {
            if ($cs['id'] === $class_id) {
                $cs['status'] = $new_status;
                $cs['reason'] = $reason;
                break;
            }
        }
        unset($cs);
        $action_toast = "showToast('Class status updated to $new_status.', 'success');";
    }
}

// Compute Stats
$total_scheduled = count($db['class_sessions']);
$conducted   = 0; $pending = 0; $cancelled = 0;
foreach ($db['class_sessions'] as $cs) {
    if ($cs['status'] === 'Conducted') $conducted++;
    elseif ($cs['status'] === 'Cancelled') $cancelled++;
    else $pending++;
}

$total_faculty = count($db['faculty']);
$faculty_present = 0;
foreach ($db['faculty'] as $f) {
    if (($f['status'] ?? 'Active') === 'Active') $faculty_present++;
}

$total_students  = count($db['students']);
$att_sum = 0; $low_att = 0;
foreach ($db['students'] as $s) {
    $a = $s['attendance_pct'] ?? 80;
    $att_sum += $a;
    if ($a < 75) $low_att++;
}
$avg_att = $total_students > 0 ? round($att_sum / $total_students, 1) : 0;
$att_marked_pct = $total_scheduled > 0 ? min(100, round(($conducted / $total_scheduled) * 100)) : 95;

$total_labs = count($db['labs']); $conducted_labs = 0;
foreach ($db['labs'] as $l) {
    if (($l['status'] ?? '') === 'Conducted') $conducted_labs++;
}

$open_issues = count(array_filter($db['issues'] ?? [], fn($i) => $i['status'] === 'Pending'));

$date_display = date('F j, Y');

// Color helpers
function statusColor($val, $warn, $danger, $invert=false) {
    if (!$invert) {
        if ($val >= $warn) return '#10b981';
        if ($val >= $danger) return '#f59e0b';
        return '#ef4444';
    } else {
        if ($val <= $danger) return '#10b981';
        if ($val <= $warn) return '#f59e0b';
        return '#ef4444';
    }
}
function statusIcon($val, $warn, $danger, $invert=false) {
    if (!$invert) {
        if ($val >= $warn) return '🟢';
        if ($val >= $danger) return '🟡';
        return '🔴';
    } else {
        if ($val <= $danger) return '🟢';
        if ($val <= $warn) return '🟡';
        return '🔴';
    }
}
?>
<style>
.stat-grid-8 { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.25rem; margin-bottom: 2rem; }
.stat-cell { background: var(--glass-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem 1.25rem; text-align: center; position: relative; transition: transform .2s, box-shadow .2s; }
.stat-cell:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,.12); }
.stat-num { font-size: 2.2rem; font-weight: 800; line-height: 1; margin: 0.35rem 0; }
.stat-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); }
.stat-icon { font-size: 1.1rem; position: absolute; top: 0.75rem; right: 0.9rem; }
.stat-badge { font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; margin-top: 0.35rem; display: inline-block; }
.compliance-table td, .compliance-table th { vertical-align: middle; }
.alert-strip { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 1rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 600; margin-bottom: 0.5rem; border-left: 3px solid; }
.alert-strip.warn { background: rgba(245,158,11,.08); border-color: #f59e0b; color: #b45309; }
.alert-strip.danger { background: rgba(239,68,68,.08); border-color: #ef4444; color: #dc2626; }
.alert-strip.info { background: rgba(59,130,246,.08); border-color: #3b82f6; color: #1d4ed8; }
.reason-select { font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 6px; border: 1px solid var(--border-color); background: var(--glass-bg); color: var(--text-primary); }
@media(max-width:900px) { .stat-grid-8 { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px) { .stat-grid-8 { grid-template-columns: 1fr; } }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="glass-card-header" style="margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="font-size:1.75rem; font-weight:700; display:flex; align-items:center; gap:0.5rem;">
                📊 Daily Class Monitoring Dashboard
            </h1>
            <p style="color:var(--text-secondary); font-size:.9rem; margin-top:.25rem;">
                Real-time academic execution for today (<?= $date_display ?>). SRS Module 3.
            </p>
        </div>
        <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <a href="reports.php?type=daily" class="btn btn-primary" style="font-size:.82rem; padding:.6rem 1.1rem; display:flex; align-items:center; gap:.4rem;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 14H7v-2h10v2zm0-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                Generate Daily Report
            </a>
            <a href="attendance.php" class="btn btn-secondary" style="font-size:.82rem; padding:.6rem 1.1rem;">
                Take Attendance →
            </a>
        </div>
    </div>

    <!-- SRS Feature 2: Today's Academic Status (8-stat grid) -->
    <div class="glass-card" style="padding:1.75rem; margin-bottom:2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem;">
            <div>
                <h3 style="font-size:1.2rem; font-weight:700; margin:0;">Today's Academic Status</h3>
                <p style="font-size:.75rem; color:var(--text-muted); margin:.2rem 0 0;">Real-time monitoring connected to active session database</p>
            </div>
            <div style="display:flex; gap:1.25rem; font-size:.8rem; font-weight:700; background:rgba(15,23,42,.03); padding:.5rem 1rem; border-radius:99px; border:1px solid var(--border-color);">
                <span style="color:#10b981;">🟢 Normal</span>
                <span style="color:#f59e0b;">🟡 Attention</span>
                <span style="color:#ef4444;">🔴 Critical</span>
            </div>
        </div>

        <div class="stat-grid-8">
            <?php
            $stats = [
                ['label'=>'Classes Scheduled', 'value'=>$total_scheduled, 'icon'=>'📋', 'color'=>'var(--primary)', 'badge'=>'🟢 Normal', 'badge_bg'=>'rgba(16,185,129,.15)', 'badge_color'=>'#10b981', 'suffix'=>''],
                ['label'=>'Classes Conducted', 'value'=>$conducted, 'icon'=>'✅', 'color'=>statusColor($conducted,$total_scheduled*.9,$total_scheduled*.7), 'badge'=>statusIcon($conducted,$total_scheduled*.9,$total_scheduled*.7).' '.($conducted>=$total_scheduled*.9?'Normal':'Attention'), 'badge_bg'=>'rgba(16,185,129,.15)', 'badge_color'=>statusColor($conducted,$total_scheduled*.9,$total_scheduled*.7), 'suffix'=>''],
                ['label'=>'Pending', 'value'=>$pending, 'icon'=>'⏳', 'color'=>statusColor($pending,0,1,true), 'badge'=>statusIcon($pending,2,4,true).' '.($pending<=1?'Normal':'Attention'), 'badge_bg'=>'rgba(245,158,11,.15)', 'badge_color'=>'#f59e0b', 'suffix'=>''],
                ['label'=>'Cancelled', 'value'=>$cancelled, 'icon'=>'❌', 'color'=>statusColor($cancelled,0,1,true), 'badge'=>$cancelled===0?'🟢 Normal':'🔴 Critical', 'badge_bg'=>$cancelled===0?'rgba(16,185,129,.15)':'rgba(239,68,68,.15)', 'badge_color'=>$cancelled===0?'#10b981':'#ef4444', 'suffix'=>''],
                ['label'=>'Attendance Marked', 'value'=>$att_marked_pct, 'icon'=>'📝', 'color'=>statusColor($att_marked_pct,90,70), 'badge'=>statusIcon($att_marked_pct,90,70).' '.($att_marked_pct>=90?'Normal':($att_marked_pct>=70?'Attention':'Critical')), 'badge_bg'=>'rgba(16,185,129,.15)', 'badge_color'=>statusColor($att_marked_pct,90,70), 'suffix'=>'%'],
                ['label'=>'Laboratories Conducted', 'value'=>"$conducted_labs/$total_labs", 'icon'=>'🧪', 'color'=>'#8b5cf6', 'badge'=>'🟢 Normal', 'badge_bg'=>'rgba(139,92,246,.15)', 'badge_color'=>'#8b5cf6', 'suffix'=>''],
                ['label'=>'Faculty Present', 'value'=>"$faculty_present/$total_faculty", 'icon'=>'👨‍🏫', 'color'=>statusColor($faculty_present,$total_faculty*.93,$total_faculty*.8), 'badge'=>statusIcon($faculty_present,$total_faculty*.93,$total_faculty*.8).' '.($faculty_present>=$total_faculty*.93?'Normal':'Attention'), 'badge_bg'=>'rgba(16,185,129,.15)', 'badge_color'=>statusColor($faculty_present,$total_faculty*.93,$total_faculty*.8), 'suffix'=>''],
                ['label'=>'Students Present', 'value'=>$avg_att, 'icon'=>'🎓', 'color'=>statusColor($avg_att,85,75), 'badge'=>statusIcon($avg_att,85,75).' '.($avg_att>=85?'Normal':($avg_att>=75?'Attention':'Critical')), 'badge_bg'=>'rgba(16,185,129,.15)', 'badge_color'=>statusColor($avg_att,85,75), 'suffix'=>'%'],
            ];
            foreach ($stats as $stat): ?>
            <div class="stat-cell" style="border-top:3px solid <?= $stat['color'] ?>;">
                <div class="stat-icon"><?= $stat['icon'] ?></div>
                <div class="stat-num" style="color:<?= $stat['color'] ?>"><?= $stat['value'] ?><?= $stat['suffix'] ?></div>
                <div class="stat-label"><?= $stat['label'] ?></div>
                <div class="stat-badge" style="background:<?= $stat['badge_bg'] ?>; color:<?= $stat['badge_color'] ?>;"><?= $stat['badge'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SRS Feature 5: Timetable Compliance Monitoring -->
    <div class="glass-card" style="margin-bottom:2rem;">
        <div class="glass-card-header" style="margin-bottom:1.25rem;">
            <div>
                <h3 class="glass-card-title">⏱ Timetable Compliance Monitoring</h3>
                <p style="color:var(--text-muted); font-size:.75rem; margin-top:.15rem;">Scheduled vs. Actual conducted classes. Mark reasons for non-conducted sessions.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="modern-table compliance-table">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Subject</th>
                        <th>Faculty</th>
                        <th>Room</th>
                        <th>Scheduled</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($db['class_sessions'] as $cs):
                        $status = $cs['status'];
                        $sc = $status === 'Conducted' ? '#10b981' : ($status === 'Cancelled' ? '#ef4444' : '#f59e0b');
                        $si = $status === 'Conducted' ? '✔' : ($status === 'Cancelled' ? '❌' : '⏳');
                        $subject_label = isset($db['subjects']) ? (array_filter($db['subjects'], fn($s)=>$s['code']===$cs['subject']) ? array_values(array_filter($db['subjects'], fn($s)=>$s['code']===$cs['subject']))[0]['name'] ?? $cs['subject'] : $cs['subject']) : $cs['subject'];
                    ?>
                    <tr>
                        <td style="font-weight:600; font-size:.82rem;"><?= htmlspecialchars($cs['time']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($subject_label) ?></strong>
                            <br><span style="font-size:.68rem; color:var(--text-muted);"><?= $cs['subject'] ?></span>
                        </td>
                        <td style="font-size:.82rem;"><?= htmlspecialchars($cs['faculty']) ?></td>
                        <td style="font-size:.82rem;"><?= htmlspecialchars($cs['room']) ?></td>
                        <td><span class="badge badge-info" style="font-size:.65rem;">Scheduled</span></td>
                        <td>
                            <span style="color:<?= $sc ?>; font-weight:700; font-size:.82rem;"><?= $si ?> <?= htmlspecialchars($status) ?></span>
                        </td>
                        <td style="font-size:.78rem; color:var(--text-muted);"><?= htmlspecialchars($cs['reason'] ?: '—') ?></td>
                        <td>
                            <form action="daily_monitoring.php" method="POST" style="display:flex; gap:.3rem; align-items:center; flex-wrap:wrap;">
                                <input type="hidden" name="action" value="update_class_status">
                                <input type="hidden" name="class_id" value="<?= $cs['id'] ?>">
                                <select name="status" class="reason-select">
                                    <option value="Conducted" <?= $status==='Conducted'?'selected':'' ?>>Conducted</option>
                                    <option value="Not Conducted" <?= $status==='Not Conducted'?'selected':'' ?>>Not Conducted</option>
                                    <option value="Cancelled" <?= $status==='Cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                                <select name="reason" class="reason-select">
                                    <option value="">No Reason</option>
                                    <option value="Faculty Leave" <?= $cs['reason']==='Faculty Leave'?'selected':'' ?>>Faculty Leave</option>
                                    <option value="Holiday" <?= $cs['reason']==='Holiday'?'selected':'' ?>>Holiday</option>
                                    <option value="Network Issue" <?= $cs['reason']==='Network Issue'?'selected':'' ?>>Network Issue</option>
                                    <option value="Student Event" <?= $cs['reason']==='Student Event'?'selected':'' ?>>Student Event</option>
                                    <option value="Other" <?= $cs['reason']==='Other'?'selected':'' ?>>Other</option>
                                </select>
                                <button type="submit" class="btn btn-primary" style="padding:.25rem .7rem; font-size:.7rem;">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SRS Feature 9+10: Notifications Panel + Recent Alerts -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
        <!-- Recent Alerts -->
        <div class="glass-card" style="padding:1.5rem;">
            <div class="glass-card-header" style="margin-bottom:1rem;">
                <h3 class="glass-card-title">🔔 Recent Alerts</h3>
            </div>
            <?php
            $alerts = [];
            foreach ($db['class_sessions'] as $cs) {
                if ($cs['status'] === 'Not Conducted' || $cs['status'] === 'Cancelled') {
                    $alerts[] = ['type'=>'danger', 'msg'=>"⚠ {$cs['subject']} ".($cs['reason']?'— '.$cs['reason']:'Not Conducted')." at {$cs['time']}"];
                }
            }
            foreach ($db['students'] as $s) {
                if (($s['attendance_pct'] ?? 80) < 75) {
                    $alerts[] = ['type'=>'warn', 'msg'=>"⚠ Attendance Below 75% — {$s['name']} ({$s['attendance_pct']}%)"];
                }
            }
            foreach ($db['issues'] as $issue) {
                if ($issue['status'] === 'Pending') {
                    $alerts[] = ['type'=>'warn', 'msg'=>"⚠ Issue — {$issue['title']} in {$issue['room']}"];
                }
            }
            if (empty($alerts)): ?>
                <p style="color:var(--text-muted); font-size:.85rem; text-align:center; padding:1.5rem 0;">✅ All systems normal — No alerts</p>
            <?php else:
                foreach (array_slice($alerts, 0, 8) as $a): ?>
                <div class="alert-strip <?= $a['type'] ?>"><?= htmlspecialchars($a['msg']) ?></div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Auto Notifications Log -->
        <div class="glass-card" style="padding:1.5rem;">
            <div class="glass-card-header" style="margin-bottom:1rem;">
                <h3 class="glass-card-title">🛎 System Notifications</h3>
            </div>
            <div class="alert-strip info">📢 Faculty reminder: Mark today's attendance before 5 PM</div>
            <div class="alert-strip info">📢 Timetable is active — classes begin at 09:00 AM</div>
            <?php if ($conducted_labs < $total_labs): ?>
            <div class="alert-strip warn">🧪 Lab session pending: <?= $total_labs - $conducted_labs ?> lab(s) not yet marked as conducted</div>
            <?php endif; ?>
            <?php if ($open_issues > 0): ?>
            <div class="alert-strip danger">🔧 <?= $open_issues ?> maintenance ticket(s) open — check <a href="classrooms.php" style="color:inherit; font-weight:700;">Classroom Status</a></div>
            <?php endif; ?>
            <?php if ($low_att > 0): ?>
            <div class="alert-strip warn">🎓 <?= $low_att ?> student(s) with attendance below 75% — action needed</div>
            <?php endif; ?>
            <div class="alert-strip info" style="margin-top:.75rem;">
                📊 End-of-day report will auto-generate at 6:00 PM — 
                <a href="reports.php?type=daily" style="color:inherit; font-weight:700;">View Reports</a>
            </div>
        </div>
    </div>

    <!-- Pending Makeup Classes -->
    <?php
    $makeups = array_filter($db['class_sessions'], fn($cs)=>$cs['status']==='Cancelled' || $cs['status']==='Not Conducted');
    if (count($makeups) > 0): ?>
    <div class="glass-card" style="margin-top:1.5rem; padding:1.5rem;">
        <div class="glass-card-header" style="margin-bottom:1rem;">
            <h3 class="glass-card-title">📌 Make-up Class Pending List</h3>
            <span class="badge badge-danger" style="font-size:.7rem;"><?= count($makeups) ?> Pending</span>
        </div>
        <table class="modern-table">
            <thead><tr><th>Subject</th><th>Faculty</th><th>Original Time</th><th>Status</th><th>Reason</th></tr></thead>
            <tbody>
                <?php foreach ($makeups as $mk): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($mk['subject']) ?></strong></td>
                    <td><?= htmlspecialchars($mk['faculty']) ?></td>
                    <td><?= htmlspecialchars($mk['time']) ?></td>
                    <td><span class="badge badge-danger" style="font-size:.65rem;"><?= $mk['status'] ?></span></td>
                    <td style="font-size:.8rem; color:var(--text-muted);"><?= htmlspecialchars($mk['reason'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => { <?= $action_toast ?> });
</script>
<?php include 'footer.php'; ?>
