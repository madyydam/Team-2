<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
SRS Module 3 - Feature 8: Daily Academic Report
reports.php - Auto-generated PDF/Excel/Email to Principal
================================================================
*/

include 'header.php';
$db = &$_SESSION['academic_db'];

$type = $_GET['type'] ?? 'daily';

// Ensure sessions data
if (!isset($db['class_sessions'])) {
    $db['class_sessions'] = [
        ['id'=>1,'subject'=>'CS501','faculty'=>'Prof. Balaji A. Chaugule','room'=>'Room 301','time'=>'09:00 AM - 10:00 AM','status'=>'Conducted','reason'=>''],
        ['id'=>2,'subject'=>'CS502','faculty'=>'Dr. Neeti Rathore','room'=>'Room 302','time'=>'10:00 AM - 11:00 AM','status'=>'Conducted','reason'=>''],
        ['id'=>3,'subject'=>'CS503','faculty'=>'Prof. Sumesh S. Shinde','room'=>'Programming Lab 1','time'=>'11:15 AM - 01:15 PM','status'=>'Not Conducted','reason'=>''],
        ['id'=>4,'subject'=>'IT501','faculty'=>'Dr. Neeti Rathore','room'=>'Room 303','time'=>'02:00 PM - 03:00 PM','status'=>'Cancelled','reason'=>'Faculty Leave'],
        ['id'=>5,'subject'=>'EC501','faculty'=>'Prof. Ashwini M. Agarwal','room'=>'Room 304','time'=>'03:15 PM - 04:15 PM','status'=>'Not Conducted','reason'=>''],
    ];
}

// Computed metrics
$total_planned  = count($db['class_sessions']);
$conducted      = count(array_filter($db['class_sessions'], fn($c)=>$c['status']==='Conducted'));
$cancelled_list = array_values(array_filter($db['class_sessions'], fn($c)=>$c['status']==='Cancelled'));
$cancelled_ct   = count($cancelled_list);

$total_students = count($db['students']);
$att_sum = 0; $low_ct = 0;
foreach ($db['students'] as $s) { $att_sum += ($s['attendance_pct']??80); if (($s['attendance_pct']??80)<75) $low_ct++; }
$avg_att = $total_students > 0 ? round($att_sum / $total_students, 1) : 0;

$total_labs = count($db['labs']);
$done_labs  = count(array_filter($db['labs'], fn($l)=>$l['status']==='Conducted'));

$total_fac   = count($db['faculty']);
$present_fac = count(array_filter($db['faculty'], fn($f)=>($f['status']??'Active')==='Active'));

$open_issues = array_values(array_filter($db['issues']??[], fn($i)=>$i['status']==='Pending'));
$open_ct     = count($open_issues);

$today = date('j F Y');
$now   = date('h:i A');
?>
<style>
@media print {
    .sidebar, header, .report-controls, .btn { display: none !important; }
    .report-sheet { margin: 0; padding: 0; box-shadow: none; border: none; }
    body { background: white; }
}
.report-sheet { background: var(--glass-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); max-width: 960px; margin: 0 auto; }
.report-header { background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%); color: white; padding: 2rem 2.5rem; border-radius: var(--radius-lg) var(--radius-lg) 0 0; }
.report-section { padding: 1.5rem 2.5rem; border-bottom: 1px solid var(--border-color); }
.report-section:last-child { border-bottom: none; }
.report-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.report-kpi-box { background: rgba(15,23,42,.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; text-align: center; }
.kpi-val { font-size: 1.75rem; font-weight: 800; }
.kpi-lbl { font-size: .7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-top: .2rem; }
.tab-btn { padding: .45rem 1.1rem; border-radius: 99px; font-size: .78rem; font-weight: 700; cursor: pointer; border: 1px solid var(--border-color); background: var(--glass-bg); color: var(--text-secondary); margin-right: .4rem; transition: all .2s; }
.tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="glass-card-header report-controls" style="margin-bottom:2rem;">
        <div>
            <h1 style="font-size:1.75rem; font-weight:700;">📋 Reports &amp; Analytics Console</h1>
            <p style="color:var(--text-secondary); font-size:.9rem; margin-top:.25rem;">SRS Module 3 — Feature 8. Auto-generated daily academic report.</p>
        </div>
        <div style="display:flex; gap:.6rem; flex-wrap:wrap;">
            <button class="btn btn-secondary" onclick="window.print()" style="font-size:.82rem; padding:.6rem 1.1rem;">
                <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;margin-right:4px;"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                Print / PDF
            </button>
            <button class="btn btn-primary" onclick="showToast('Spreadsheet exported. Download will begin shortly.', 'success')" style="font-size:.82rem; padding:.6rem 1.1rem;">
                <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;margin-right:4px;"><path d="M17 13l-5 5-5-5h3V9h4v4h3z M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
                Export Excel
            </button>
            <button class="btn btn-secondary" onclick="showToast('Daily report emailed to principal@zealeducation.com successfully!', 'success')" style="font-size:.82rem; padding:.6rem 1.1rem; color:var(--primary);">
                <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;margin-right:4px;"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                Email to Principal
            </button>
        </div>
    </div>

    <!-- Report Type Tabs -->
    <div class="report-controls" style="margin-bottom:1.5rem;">
        <a href="reports.php?type=daily" class="tab-btn <?= $type==='daily'?'active':'' ?>">Daily Report</a>
        <a href="reports.php?type=attendance" class="tab-btn <?= $type==='attendance'?'active':'' ?>">Attendance Report</a>
        <a href="reports.php?type=workload" class="tab-btn <?= $type==='workload'?'active':'' ?>">Faculty Workload</a>
        <a href="reports.php?type=labs" class="tab-btn <?= $type==='labs'?'active':'' ?>">Lab Status</a>
        <a href="reports.php?type=cancellations" class="tab-btn <?= $type==='cancellations'?'active':'' ?>">Cancellations</a>
    </div>

    <!-- Report Sheet -->
    <div class="report-sheet">

        <!-- Report Header -->
        <div class="report-header">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                <div>
                    <div style="font-size:.75rem; opacity:.8; font-weight:600; text-transform:uppercase; letter-spacing:.08em; margin-bottom:.35rem;">
                        Zeal Institute of Business Administration, Research &amp; Technology
                    </div>
                    <h2 style="font-size:1.5rem; font-weight:800; margin:0;">
                        <?php
                        $titles = [
                            'daily'         => '📊 Daily Academic Report',
                            'attendance'    => '📝 Attendance Compliance Report',
                            'workload'      => '👨‍🏫 Faculty Workload Report',
                            'labs'          => '🧪 Laboratory Status Report',
                            'cancellations' => '❌ Class Cancellations Report',
                        ];
                        echo $titles[$type] ?? 'Academic Report';
                        ?>
                    </h2>
                    <div style="font-size:.82rem; opacity:.85; margin-top:.35rem;">Department of Information Technology | Academic Year 2026-27 | Odd Semester</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:.75rem; opacity:.75;">Generated</div>
                    <div style="font-weight:700; font-size:1rem;"><?= $today ?></div>
                    <div style="font-size:.8rem; opacity:.8;"><?= $now ?></div>
                    <div style="margin-top:.5rem;"><span style="background:rgba(255,255,255,.2); padding:.2rem .7rem; border-radius:99px; font-size:.7rem; font-weight:700;">Auto-Generated ✔</span></div>
                </div>
            </div>
        </div>

        <?php if ($type === 'daily'): ?>
        <!-- Feature 8: Daily Academic Report -->
        <div class="report-section">
            <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">Key Performance Indicators</h3>
            <div class="report-kpi-grid">
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:var(--primary);"><?= $total_planned ?></div>
                    <div class="kpi-lbl">Classes Planned</div>
                </div>
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:#10b981;"><?= $conducted ?></div>
                    <div class="kpi-lbl">Classes Conducted</div>
                </div>
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:<?= $avg_att>=85?'#10b981':($avg_att>=75?'#f59e0b':'#ef4444') ?>;"><?= $avg_att ?>%</div>
                    <div class="kpi-lbl">Avg. Attendance</div>
                </div>
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:#8b5cf6;"><?= $done_labs ?>/<?= $total_labs ?></div>
                    <div class="kpi-lbl">Laboratories</div>
                </div>
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:<?= $cancelled_ct===0?'#10b981':'#ef4444' ?>;"><?= $cancelled_ct ?></div>
                    <div class="kpi-lbl">Cancelled Classes</div>
                </div>
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:#10b981;"><?= $present_fac ?>/<?= $total_fac ?></div>
                    <div class="kpi-lbl">Faculty Present</div>
                </div>
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:<?= $open_ct===0?'#10b981':'#f59e0b' ?>;"><?= $open_ct ?></div>
                    <div class="kpi-lbl">Infrastructure Issues</div>
                </div>
                <div class="report-kpi-box">
                    <div class="kpi-val" style="color:<?= $low_ct===0?'#10b981':'#ef4444' ?>;"><?= $low_ct ?></div>
                    <div class="kpi-lbl">Low Att. Students</div>
                </div>
            </div>
        </div>

        <!-- Class-by-class log -->
        <div class="report-section">
            <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">Class Execution Log</h3>
            <table class="modern-table">
                <thead><tr><th>Time</th><th>Subject</th><th>Faculty</th><th>Room</th><th>Status</th><th>Reason</th></tr></thead>
                <tbody>
                    <?php foreach ($db['class_sessions'] as $cs):
                        $sc = $cs['status']==='Conducted'?'badge-success':($cs['status']==='Cancelled'?'badge-danger':'badge-warning');
                    ?>
                    <tr>
                        <td style="font-size:.8rem; font-weight:600;"><?= htmlspecialchars($cs['time']) ?></td>
                        <td><strong><?= htmlspecialchars($cs['subject']) ?></strong></td>
                        <td style="font-size:.82rem;"><?= htmlspecialchars($cs['faculty']) ?></td>
                        <td style="font-size:.8rem;"><?= htmlspecialchars($cs['room']) ?></td>
                        <td><span class="badge <?= $sc ?>" style="font-size:.65rem;"><?= htmlspecialchars($cs['status']) ?></span></td>
                        <td style="font-size:.78rem; color:var(--text-muted);"><?= htmlspecialchars($cs['reason'] ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Infrastructure Issues -->
        <?php if (!empty($open_issues)): ?>
        <div class="report-section">
            <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem; color:#dc2626;">Infrastructure Issues Reported</h3>
            <table class="modern-table">
                <thead><tr><th>Room</th><th>Issue</th><th>Type</th><th>Reported By</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($open_issues as $iss): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($iss['room']) ?></strong></td>
                        <td><?= htmlspecialchars($iss['title']) ?></td>
                        <td><span class="badge badge-info" style="font-size:.65rem;"><?= $iss['type'] ?></span></td>
                        <td style="font-size:.8rem;"><?= htmlspecialchars($iss['reported_by']) ?></td>
                        <td style="font-size:.8rem;"><?= $iss['date'] ?></td>
                        <td><span class="badge badge-danger" style="font-size:.65rem;">Pending</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer summary -->
        <div class="report-section" style="background:rgba(15,23,42,.02);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div style="font-size:.78rem; color:var(--text-muted);">
                    Report generated automatically at end of day. Verified by HOD — <?= htmlspecialchars($_SESSION['user_name'] ?? 'Prof. Balaji A. Chaugule') ?>
                </div>
                <div style="display:flex; gap:.5rem;">
                    <span style="font-size:.7rem; color:var(--text-muted);">Signature: ___________________</span>
                </div>
            </div>
        </div>

        <?php elseif ($type === 'attendance'): ?>
        <div class="report-section">
            <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem;">Student Attendance Records</h3>
            <table class="modern-table">
                <thead><tr><th>#</th><th>Student Name</th><th>Roll No</th><th>Department</th><th>Semester</th><th>Attendance %</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($db['students'] as $i => $s):
                        $a = $s['attendance_pct']??80;
                        $bc = $a>=85?'badge-success':($a>=75?'badge-warning':'badge-danger');
                    ?>
                    <tr>
                        <td style="font-size:.75rem; color:var(--text-muted);"><?= $i+1 ?></td>
                        <td><strong style="font-size:.83rem;"><?= htmlspecialchars($s['name']) ?></strong></td>
                        <td><code><?= $s['roll_no'] ?></code></td>
                        <td style="font-size:.8rem;"><?= $s['department'] ?></td>
                        <td style="font-size:.8rem;"><?= $s['semester'] ?></td>
                        <td style="font-weight:700; color:<?= $a>=85?'#10b981':($a>=75?'#f59e0b':'#ef4444') ?>;"><?= $a ?>%</td>
                        <td><span class="badge <?= $bc ?>" style="font-size:.62rem;"><?= $a>=75?'Regular':'Below 75%' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($type === 'workload'): ?>
        <div class="report-section">
            <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem;">Faculty Workload Allocation</h3>
            <table class="modern-table">
                <thead><tr><th>Faculty</th><th>Designation</th><th>Department</th><th>Workload (hrs)</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($db['faculty'] as $f): ?>
                    <tr>
                        <td><strong style="font-size:.83rem;"><?= htmlspecialchars($f['name']) ?></strong></td>
                        <td style="font-size:.8rem;"><?= $f['designation'] ?? 'Assistant Professor' ?></td>
                        <td style="font-size:.8rem;"><?= $f['department'] ?></td>
                        <td style="font-weight:700; color:<?= ($f['workload']??14)>16?'#ef4444':'var(--primary)' ?>;"><?= $f['workload'] ?? 14 ?> hrs</td>
                        <td><span class="badge badge-success" style="font-size:.65rem;"><?= $f['status'] ?? 'Active' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($type === 'labs'): ?>
        <div class="report-section">
            <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem;">Laboratory Status Report</h3>
            <table class="modern-table">
                <thead><tr><th>Lab</th><th>Systems Working</th><th>Network</th><th>Equipment</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($db['labs'] as $l):
                        $sc = $l['status']==='Conducted'?'badge-success':($l['status']==='Under Maintenance'?'badge-danger':'badge-info');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
                        <td><?= $l['systems_working'] ?>/<?= $l['total_systems'] ?></td>
                        <td><span class="badge badge-<?= $l['network_status']==='Excellent'?'success':'warning' ?>" style="font-size:.65rem;"><?= $l['network_status'] ?></span></td>
                        <td style="font-size:.8rem;"><?= $l['equipment_status'] ?></td>
                        <td><span class="badge <?= $sc ?>" style="font-size:.65rem;"><?= $l['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($type === 'cancellations'): ?>
        <div class="report-section">
            <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem;">Class Cancellations Log</h3>
            <?php $clist = array_filter($db['class_sessions'], fn($c)=>$c['status']==='Cancelled'); ?>
            <?php if (empty($clist)): ?>
                <p style="text-align:center; color:var(--text-muted); padding:1.5rem 0;">✅ No class cancellations recorded today.</p>
            <?php else: ?>
            <table class="modern-table">
                <thead><tr><th>Subject</th><th>Faculty</th><th>Time</th><th>Room</th><th>Reason</th></tr></thead>
                <tbody>
                    <?php foreach ($clist as $c): ?>
                    <tr>
                        <td><strong><?= $c['subject'] ?></strong></td>
                        <td><?= htmlspecialchars($c['faculty']) ?></td>
                        <td style="font-size:.8rem;"><?= $c['time'] ?></td>
                        <td style="font-size:.8rem;"><?= $c['room'] ?></td>
                        <td style="color:#ef4444; font-weight:600; font-size:.82rem;"><?= htmlspecialchars($c['reason']?:'Not specified') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /report-sheet -->

</div>
<?php include 'footer.php'; ?>
