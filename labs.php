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

// Handle filing a complaint specifically for labs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'report_lab_issue') {
        $lab_name = $_POST['lab_name'] ?? 'General Lab';
        $issue_title = trim($_POST['title'] ?? '');
        $type = $_POST['type'] ?? 'Equipment';
        
        if (empty($issue_title)) {
            $action_toast = "showToast('Failed to log issue: Description cannot be empty.', 'danger');";
        } else {
            $new_id = count($db['issues']) > 0 ? max(array_column($db['issues'], 'id')) + 1 : 1;
            $db['issues'][] = [
                'id' => $new_id,
                'title' => $issue_title,
                'room' => $lab_name,
                'type' => $type,
                'status' => 'Pending',
                'reported_by' => $_SESSION['user_name'] ?? 'Faculty Member',
                'date' => date('Y-m-d')
            ];
            
            // Adjust lab maintenance status if equipment issue is filed
            foreach ($db['labs'] as &$lab) {
                if ($lab['name'] === $lab_name) {
                    $lab['status'] = 'Under Maintenance';
                    $lab['equipment_status'] = 'Under Maintenance';
                    break;
                }
            }
            
            $action_toast = "showToast('Complaint #{$new_id} recorded. Lab status shifted to Maintenance.', 'warning');";
        }
    }
}
?>

<div class="container-fluid">
    
    <!-- Header -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Laboratory Monitoring</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Monitor real-time system functionality, internet stability, and hardware complaints.</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('reportIssueModal').classList.add('active')">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            Report Lab Issue
        </button>
    </div>

    <!-- Labs Grid -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        <?php foreach ($db['labs'] as $lab): ?>
            <?php 
            $sys_ratio = round(($lab['systems_working'] / $lab['total_systems']) * 100);
            $net_class = $lab['network_status'] === 'Excellent' ? 'badge-success' : 'badge-warning';
            $status_class = $lab['status'] === 'Conducted' ? 'badge-success' : ($lab['status'] === 'Free' ? 'badge-info' : 'badge-danger');
            ?>
            <div class="glass-card">
                <div class="glass-card-header" style="margin-bottom: 1rem;">
                    <h3 class="glass-card-title"><?= htmlspecialchars($lab['name']) ?></h3>
                    <span class="badge <?= $status_class ?>"><?= $lab['status'] ?></span>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight:600; text-transform:uppercase;">Systems Health</div>
                        <div style="font-size: 1.15rem; font-weight:700; margin: 0.25rem 0;"><?= $lab['systems_working'] ?> / <?= $lab['total_systems'] ?> Working</div>
                        <!-- Progress bar -->
                        <div style="width: 100%; height: 5px; background-color: var(--border-color); border-radius:99px; overflow:hidden;">
                            <div style="width: <?= $sys_ratio ?>%; height:100%; background-color: var(--primary); border-radius:99px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight:600; text-transform:uppercase;">Network Health</div>
                        <div style="margin-top: 0.25rem;">
                            <span class="badge <?= $net_class ?>"><?= $lab['network_status'] ?> Link</span>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary);">
                    <div>Equipment: <strong><?= $lab['equipment_status'] ?></strong></div>
                    <div>Default OS: <strong>Ubuntu / Windows 11</strong></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Software Catalog & Bookings -->
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
        
        <!-- Software Catalog -->
        <div class="glass-card">
            <h3 class="glass-card-title" style="margin-bottom: 1rem;">Core Software Registry</h3>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem;">
                <li style="display:flex; justify-content:space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border-color);">
                    <span>VS Code IDE</span>
                    <span style="font-weight:600; color:var(--accent);">Installed v1.91</span>
                </li>
                <li style="display:flex; justify-content:space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border-color);">
                    <span>Python Runtime</span>
                    <span style="font-weight:600; color:var(--accent);">Installed v3.12</span>
                </li>
                <li style="display:flex; justify-content:space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border-color);">
                    <span>MySQL Database</span>
                    <span style="font-weight:600; color:var(--accent);">Installed v8.4</span>
                </li>
                <li style="display:flex; justify-content:space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border-color);">
                    <span>Docker Engine</span>
                    <span style="font-weight:600; color:var(--warning);">Requested update</span>
                </li>
            </ul>
        </div>

        <!-- Current Lab Bookings -->
        <div class="glass-card">
            <h3 class="glass-card-title" style="margin-bottom: 1rem;">Active Bookings (Timetable)</h3>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Laboratory Room</th>
                            <th>Allocated Session</th>
                            <th>Time Block</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: 600;">Programming Lab 1</td>
                            <td>CS503 Web App Development</td>
                            <td>11:15 AM - 01:15 PM</td>
                            <td>Computer Science</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Data Science Lab</td>
                            <td>CS608 Machine Learning Practical</td>
                            <td>02:00 PM - 04:00 PM</td>
                            <td>Computer Science</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Embedded Systems Lab</td>
                            <td>EC501 Microprocessors Practical</td>
                            <td>09:00 AM - 11:00 AM</td>
                            <td>Electronics & Comm.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- Modal: Report Lab Issue -->
<div class="modal-backdrop" id="reportIssueModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">File Lab Complaint</h3>
            <button class="modal-close" onclick="document.getElementById('reportIssueModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="labs.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="report_lab_issue">
                
                <div class="form-group">
                    <label class="form-label">Select Laboratory</label>
                    <select class="form-control" name="lab_name">
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
                        <option value="Equipment">Hardware/Equipment</option>
                        <option value="Internet">Network/Internet Connectivity</option>
                        <option value="Software">Software Crash/License</option>
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
    window.addEventListener('DOMContentLoaded', (event) => {
        <?= $action_toast ?>
    });
</script>

<?php include 'footer.php'; ?>
