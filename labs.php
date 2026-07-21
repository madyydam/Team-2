<?php
/*
================================================================
EduFlow AI - Academic Planning & Monitoring System
Laboratory Monitoring - project/labs.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$action_toast = '';

// Ensure lab_sessions structure exists in DB
if (!isset($db['lab_sessions'])) {
    $db['lab_sessions'] = [];
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_lab_complete') {
        $lab_name    = trim($_POST['lab_name'] ?? '');
        $experiment  = trim($_POST['experiment'] ?? '');
        $students    = intval($_POST['students_present'] ?? 0);
        $sys_working = intval($_POST['systems_working'] ?? 0);
        $remarks     = trim($_POST['remarks'] ?? '');

        $session_key = $lab_name . '_' . date('Y-m-d');
        $db['lab_sessions'][$session_key] = [
            'lab_name'         => $lab_name,
            'date'             => date('Y-m-d'),
            'experiment'       => $experiment,
            'status'           => 'Completed',
            'students_present' => $students,
            'systems_working'  => $sys_working,
            'remarks'          => $remarks,
            'marked_by'        => $_SESSION['user_name'] ?? 'Faculty',
        ];

        foreach ($db['labs'] as &$lab) {
            if ($lab['name'] === $lab_name) {
                $lab['systems_working'] = $sys_working;
                $lab['status'] = 'Conducted';
                break;
            }
        }
        unset($lab);

        $action_toast = "showToast('Lab session marked as Completed successfully!', 'success');";
    }

    if ($action === 'report_lab_issue') {
        $lab_name    = $_POST['lab_name'] ?? 'General Lab';
        $issue_title = trim($_POST['title'] ?? '');
        $type        = $_POST['type'] ?? 'Equipment';

        if (empty($issue_title)) {
            $action_toast = "showToast('Failed to log issue: Description cannot be empty.', 'danger');";
        } else {
            $new_id = count($db['issues']) > 0 ? max(array_column($db['issues'], 'id')) + 1 : 1;
            $db['issues'][] = [
                'id'          => $new_id,
                'title'       => $issue_title,
                'room'        => $lab_name,
                'type'        => $type,
                'status'      => 'Pending',
                'reported_by' => $_SESSION['user_name'] ?? 'Faculty Member',
                'date'        => date('Y-m-d'),
            ];
            foreach ($db['labs'] as &$lab) {
                if ($lab['name'] === $lab_name) {
                    $lab['status'] = 'Under Maintenance';
                    $lab['equipment_status'] = 'Under Maintenance';
                    break;
                }
            }
            unset($lab);
            $action_toast = "showToast('Complaint #{$new_id} recorded. Lab status shifted to Maintenance.', 'warning');";
        }
    }
}

$lab_software = [
    'Programming' => ['Java', 'Python', 'MySQL', 'VS Code'],
    'Data'        => ['Python', 'Jupyter', 'TensorFlow', 'MySQL'],
    'Embedded'    => ['Keil µVision', 'Proteus', 'MATLAB'],
    'Network'     => ['Cisco Packet Tracer', 'Wireshark', 'Ubuntu'],
];
?>
<style>
.lab-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.lab-stat-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin: 1rem 0; }
.lab-stat-box { background: var(--glass-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem; text-align: center; }
.lab-stat-box .sv { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.lab-stat-box .sl { font-size: 0.62rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-top: 0.25rem; }
.sw-tag { display: inline-flex; align-items: center; gap: 0.3rem; background: rgba(37,99,235,0.07); color: var(--primary); border: 1px solid rgba(37,99,235,0.15); padding: 0.18rem 0.55rem; border-radius: 99px; font-size: 0.68rem; font-weight: 600; }
.net-dot { width: 8px; height: 8px; border-radius: 50%; animation: pd 1.5s infinite; }
@keyframes pd { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }
.lab-act-bar { display: flex; gap: 0.6rem; margin-top: 1rem; flex-wrap: wrap; }
@media(max-width:768px){ .lab-grid{grid-template-columns:1fr} .lab-stat-row{grid-template-columns:1fr 1fr} }
</style>

<div class="container-fluid">

    <div class="glass-card-header" style="margin-bottom:2rem;">
        <div>
            <h1 style="font-size:1.75rem;font-weight:700;">Laboratory Monitoring</h1>
            <p style="color:var(--text-secondary);font-size:.9rem;margin-top:.25rem;">Faculty marks lab session completion &amp; tracks experiments, attendance, system health, software &amp; network status.</p>
        </div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <button class="btn btn-primary" onclick="document.getElementById('markCompleteModal').classList.add('active')">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Mark Lab Completed
            </button>
            <button class="btn btn-secondary" style="color:var(--danger);border-color:rgba(239,68,68,.3);" onclick="document.getElementById('reportIssueModal').classList.add('active')">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                Report Issue
            </button>
        </div>
    </div>

    <div class="lab-grid">
    <?php foreach ($db['labs'] as $lab):
        $sys_total   = $lab['total_systems'] ?? 60;
        $sys_working = $lab['systems_working'] ?? $sys_total;
        $sys_broken  = $sys_total - $sys_working;
        $sys_ratio   = $sys_total > 0 ? round(($sys_working / $sys_total) * 100) : 0;
        $net_ok      = ($lab['network_status'] ?? 'Good') !== 'Down';
        $status      = $lab['status'] ?? 'Free';
        $status_class = match($status) { 'Conducted' => 'badge-success', 'Free' => 'badge-info', default => 'badge-danger' };
        $status_icon  = match($status) { 'Conducted' => '✔', 'Free' => '○', default => '⚠' };
        $session_key  = $lab['name'] . '_' . date('Y-m-d');
        $session      = $db['lab_sessions'][$session_key] ?? null;
        $sys_color    = $sys_ratio >= 90 ? 'var(--accent)' : ($sys_ratio >= 70 ? 'var(--warning)' : 'var(--danger)');
        $top_color    = $status === 'Conducted' ? 'var(--accent)' : ($status === 'Under Maintenance' ? 'var(--danger)' : 'var(--border-color)');

        $soft_list = ['Java', 'Python', 'MySQL'];
        foreach ($lab_software as $k => $v) {
            if (stripos($lab['name'], $k) !== false) { $soft_list = $v; break; }
        }
    ?>
    <div class="glass-card" style="padding:1.5rem;position:relative;border-top:3px solid <?= $top_color ?>;">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;">
            <div>
                <h3 style="font-size:1.05rem;font-weight:700;color:var(--text-primary);margin:0;"><?= htmlspecialchars($lab['name']) ?></h3>
                <div style="font-size:.7rem;color:var(--text-muted);margin-top:.15rem;">Today's Status</div>
            </div>
            <span class="badge <?= $status_class ?>" style="font-size:.72rem;padding:.3rem .8rem;"><?= $status_icon ?> <?= htmlspecialchars($status) ?></span>
        </div>

        <?php if ($session): ?>
        <div style="background:rgba(37,99,235,.04);border:1px solid rgba(37,99,235,.12);border-radius:var(--radius-sm);padding:.6rem .85rem;margin-bottom:.6rem;">
            <div style="font-size:.65rem;text-transform:uppercase;font-weight:700;color:var(--text-muted);letter-spacing:.05em;margin-bottom:.2rem;">Experiment Conducted</div>
            <div style="font-weight:600;font-size:.85rem;color:var(--text-primary);"><?= htmlspecialchars($session['experiment']) ?></div>
            <?php if (!empty($session['remarks'])): ?>
            <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.2rem;">📝 <?= htmlspecialchars($session['remarks']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="lab-stat-row">
            <div class="lab-stat-box">
                <div class="sv" style="color:var(--primary);"><?= $session ? intval($session['students_present']) : '—' ?></div>
                <div class="sl">Students Present</div>
            </div>
            <div class="lab-stat-box">
                <div class="sv" style="color:<?= $sys_color ?>;"><?= $sys_working ?></div>
                <div class="sl">Systems Working</div>
            </div>
            <div class="lab-stat-box">
                <div class="sv" style="color:<?= $sys_broken > 0 ? 'var(--danger)' : 'var(--accent)' ?>;"><?= $sys_broken ?></div>
                <div class="sl">Under Repair</div>
            </div>
        </div>

        <div style="margin-bottom:.75rem;">
            <div style="display:flex;justify-content:space-between;font-size:.68rem;color:var(--text-muted);margin-bottom:.25rem;">
                <span>Computer Status</span><span><?= $sys_working ?> / <?= $sys_total ?> Working</span>
            </div>
            <div style="width:100%;height:6px;background:var(--border-color);border-radius:99px;overflow:hidden;">
                <div style="width:<?= $sys_ratio ?>%;height:100%;background:<?= $sys_color ?>;border-radius:99px;transition:width .5s ease;"></div>
            </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border-color);margin:.75rem 0;">

        <div style="margin-bottom:.65rem;">
            <div style="font-size:.65rem;text-transform:uppercase;font-weight:700;color:var(--text-muted);letter-spacing:.05em;margin-bottom:.4rem;">Software</div>
            <div style="display:flex;flex-wrap:wrap;gap:.3rem;">
                <?php foreach ($soft_list as $sw): ?>
                <span class="sw-tag">
                    <svg viewBox="0 0 24 24" style="width:9px;height:9px;fill:var(--accent);"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    <?= htmlspecialchars($sw) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:600;">
            <div class="net-dot" style="background:<?= $net_ok ? 'var(--accent)' : 'var(--danger)' ?>;box-shadow:0 0 6px <?= $net_ok ? 'var(--accent)' : 'var(--danger)' ?>;"></div>
            <span style="color:<?= $net_ok ? 'var(--accent)' : 'var(--danger)' ?>;">Network: <?= $net_ok ? '✔ Online' : '✖ Offline' ?></span>
            <span style="color:var(--text-muted);font-weight:400;">(<?= htmlspecialchars($lab['network_status'] ?? 'Good') ?>)</span>
        </div>

        <div class="lab-act-bar">
            <button class="btn btn-primary" style="font-size:.78rem;padding:.45rem .9rem;"
                onclick="openMarkModal('<?= htmlspecialchars(addslashes($lab['name'])) ?>', <?= $sys_working ?>, <?= $sys_total ?>)">
                <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Mark Completed
            </button>
            <button class="btn btn-secondary" style="font-size:.78rem;padding:.45rem .9rem;color:var(--danger);border-color:rgba(239,68,68,.25);"
                onclick="openIssueModal('<?= htmlspecialchars(addslashes($lab['name'])) ?>')">
                <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                Report Issue
            </button>
        </div>

    </div>
    <?php endforeach; ?>
    </div>

    <!-- Today's Session Log -->
    <div class="glass-card" style="margin-bottom:2rem;">
        <h3 class="glass-card-title" style="margin-bottom:1rem;">🔬 Today's Lab Session Log</h3>
        <?php
        $today = date('Y-m-d');
        $today_sessions = array_filter($db['lab_sessions'] ?? [], fn($s) => ($s['date'] ?? '') === $today);
        ?>
        <?php if (empty($today_sessions)): ?>
            <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted);font-size:.85rem;">
                <div style="font-size:2rem;margin-bottom:.5rem;">🔬</div>
                No lab sessions marked today. Faculty can use "Mark Lab Completed" to log a session.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="modern-table">
                <thead><tr>
                    <th>Laboratory</th><th>Experiment</th><th>Status</th>
                    <th>Students</th><th>Systems Working</th><th>Remarks</th><th>Marked By</th>
                </tr></thead>
                <tbody>
                <?php foreach ($today_sessions as $s): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($s['lab_name']) ?></td>
                    <td><?= htmlspecialchars($s['experiment'] ?? '—') ?></td>
                    <td><span class="badge badge-success"><?= htmlspecialchars($s['status']) ?></span></td>
                    <td><?= intval($s['students_present']) ?></td>
                    <td><?= intval($s['systems_working']) ?></td>
                    <td style="font-size:.78rem;color:var(--text-secondary);"><?= htmlspecialchars($s['remarks'] ?? '—') ?></td>
                    <td style="font-size:.78rem;"><?= htmlspecialchars($s['marked_by'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Open Issues -->
    <?php $open_issues = array_filter($db['issues'] ?? [], fn($i) => ($i['status'] ?? '') !== 'Resolved'); ?>
    <?php if (!empty($open_issues)): ?>
    <div class="glass-card">
        <h3 class="glass-card-title" style="margin-bottom:1rem;">⚠ Open Lab Issues (<?= count($open_issues) ?>)</h3>
        <div class="table-responsive">
            <table class="modern-table">
                <thead><tr><th>#</th><th>Lab / Room</th><th>Issue</th><th>Category</th><th>Reported</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($open_issues as $iss): ?>
                <tr>
                    <td style="font-weight:700;color:var(--text-muted);">#<?= $iss['id'] ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($iss['room']) ?></td>
                    <td><?= htmlspecialchars($iss['title']) ?></td>
                    <td><span class="badge badge-warning"><?= htmlspecialchars($iss['type']) ?></span></td>
                    <td style="font-size:.78rem;color:var(--text-muted);"><?= htmlspecialchars($iss['date']) ?></td>
                    <td><span class="badge badge-danger"><?= htmlspecialchars($iss['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Modal: Mark Lab Completed -->
<div class="modal-backdrop" id="markCompleteModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">✔ Mark Lab Session Completed</h3>
            <button class="modal-close" onclick="document.getElementById('markCompleteModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="labs.php" method="POST">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
                <input type="hidden" name="action" value="mark_lab_complete">
                <div class="form-group">
                    <label class="form-label">Laboratory</label>
                    <select class="form-control" name="lab_name" id="modal_lab_name" required>
                        <?php foreach ($db['labs'] as $l): ?>
                        <option value="<?= htmlspecialchars($l['name']) ?>"><?= htmlspecialchars($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Experiment Conducted</label>
                    <input class="form-control" type="text" name="experiment" placeholder="e.g. Experiment 1 – JDBC Connectivity" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Students Present</label>
                        <input class="form-control" type="number" name="students_present" min="0" max="100" value="58" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Systems Working</label>
                        <input class="form-control" type="number" name="systems_working" id="modal_sys_working" min="0" max="120" value="58" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Remarks <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input class="form-control" type="text" name="remarks" placeholder="e.g. 2 Systems Under Maintenance">
                </div>
            </div>
            <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.75rem;border-top:1px solid var(--border-color);padding-top:1rem;margin-top:1rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('markCompleteModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Completion</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Report Issue -->
<div class="modal-backdrop" id="reportIssueModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">⚠ File Lab Complaint</h3>
            <button class="modal-close" onclick="document.getElementById('reportIssueModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="labs.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="report_lab_issue">
                <div class="form-group">
                    <label class="form-label">Select Laboratory</label>
                    <select class="form-control" name="lab_name" id="issue_lab_name">
                        <?php foreach ($db['labs'] as $l): ?>
                        <option value="<?= htmlspecialchars($l['name']) ?>"><?= htmlspecialchars($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Complaint Title</label>
                    <input class="form-control" type="text" name="title" placeholder="e.g. Ethernet Port Faulty, Slow OS boot" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Issue Category</label>
                    <select class="form-control" name="type">
                        <option value="Equipment">Hardware / Equipment</option>
                        <option value="Internet">Network / Internet</option>
                        <option value="Software">Software / License</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('reportIssueModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-danger">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMarkModal(labName, sysWorking, sysTotal) {
    document.getElementById('modal_lab_name').value = labName;
    document.getElementById('modal_sys_working').value = sysWorking;
    document.getElementById('markCompleteModal').classList.add('active');
}
function openIssueModal(labName) {
    document.getElementById('issue_lab_name').value = labName;
    document.getElementById('reportIssueModal').classList.add('active');
}
window.addEventListener('DOMContentLoaded', () => { <?= $action_toast ?> });
</script>

<?php include 'footer.php'; ?>
