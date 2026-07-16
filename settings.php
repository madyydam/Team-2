<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
System Settings & Admin - project/settings.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$action_toast = '';

// Handle Settings Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. SWITCH ROLE
    if ($action === 'switch_role') {
        $role = $_POST['role'] ?? 'Head of Department';
        $_SESSION['user_role'] = $role;
        // Adjust mock name based on role
        if ($role === 'Administrator') {
            $_SESSION['user_name'] = 'Dr. Balaji Chaugule';
        } elseif ($role === 'Head of Department') {
            $_SESSION['user_name'] = 'Prof. Grace Hopper';
        } else {
            $_SESSION['user_name'] = 'Dr. Grace Hopper';
        }
        $action_toast = "showToast('View role altered: Operating as $role now.', 'info');";
    }
    
    // 2. REGISTER SUBJECT
    if ($action === 'add_subject') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $credits = intval($_POST['credits'] ?? 3);
        $sem = $_POST['semester'] ?? '5th';
        $dept = $_POST['department'] ?? '';
        
        if (empty($code) || empty($name)) {
            $action_toast = "showToast('Failed to register: Subject code and name are required.', 'danger');";
        } else {
            $db['subjects'][] = [
                'code' => $code,
                'name' => $name,
                'credits' => $credits,
                'semester' => $sem,
                'department' => $dept
            ];
            $action_toast = "showToast('Subject $code ($name) added to syllabus catalog.', 'success');";
        }
    }
    
    // 3. FILE INFRASCTRUCTURE COMPLAINT
    if ($action === 'add_complaint') {
        $title = trim($_POST['title'] ?? '');
        $room = $_POST['room'] ?? 'General';
        $type = $_POST['type'] ?? 'Projector';
        
        if (empty($title)) {
            $action_toast = "showToast('Failed to file: Description is required.', 'danger');";
        } else {
            $new_id = count($db['issues']) > 0 ? max(array_column($db['issues'], 'id')) + 1 : 1;
            $db['issues'][] = [
                'id' => $new_id,
                'title' => $title,
                'room' => $room,
                'type' => $type,
                'status' => 'Pending',
                'reported_by' => $_SESSION['user_name'] ?? 'Faculty Member',
                'date' => date('Y-m-d')
            ];
            $action_toast = "showToast('Infrastructure complaint logged under ticket #{$new_id}. Technical staff notified.', 'warning');";
        }
    }
    
    // 4. POST ANNOUNCEMENT
    if ($action === 'add_announcement') {
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            $action_toast = "showToast('Failed to post announcement: Title is empty.', 'danger');";
        } else {
            $new_id = count($db['announcements']) > 0 ? max(array_column($db['announcements'], 'id')) + 1 : 1;
            array_unshift($db['announcements'], [
                'id' => $new_id,
                'title' => $title,
                'time' => 'Just now',
                'type' => 'general'
            ]);
            $action_toast = "showToast('Notice Board updated with new announcement.', 'success');";
        }
    }
    
    // 5. ADD DEPARTMENT
    if ($action === 'add_department') {
        $new_dept = trim($_POST['new_dept'] ?? '');
        if (empty($new_dept)) {
            $action_toast = "showToast('Department name cannot be blank.', 'danger');";
        } elseif (in_array($new_dept, $db['departments'])) {
            $action_toast = "showToast('Department already exists.', 'warning');";
        } else {
            $db['departments'][] = $new_dept;
            $action_toast = "showToast('Department \"$new_dept\" has been successfully integrated.', 'success');";
        }
    }
}
?>

<div class="container-fluid">
    
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">System Settings & Administration</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Manage user viewpoints, file classroom utility issues, configure noticeboards, and modify college curriculums.</p>
        </div>
    </div>

    <!-- Layout Grid: Tab links Left, Tab views Right -->
    <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 2rem; align-items: start;">
        
        <!-- Tabs Selector List -->
        <div class="glass-card" style="padding: 1rem 0;">
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.25rem;">
                <li><button class="settings-tab-btn active" onclick="switchSettingsTab(event, 'tabRole')">Role Privileges</button></li>
                <li><button class="settings-tab-btn" onclick="switchSettingsTab(event, 'tabComplaint')">Utility Complaint</button></li>
                <li><button class="settings-tab-btn" onclick="switchSettingsTab(event, 'tabAnnouncement')">Notice Board Desk</button></li>
                <li><button class="settings-tab-btn" onclick="switchSettingsTab(event, 'tabSubject')">Manage Subjects</button></li>
                <li><button class="settings-tab-btn" onclick="switchSettingsTab(event, 'tabDept')">Department Settings</button></li>
            </ul>
        </div>
        
        <!-- Tab Content Cards -->
        <div>
            
            <!-- Tab 1: Role Configuration -->
            <div class="settings-tab-content active" id="tabRole">
                <div class="glass-card">
                    <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Console Viewpoint Simulation</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.6;">
                        Team 2 provides role-specific dashboards. Switch roles below to simulate system dashboards from the perspective of an administrator, department head, or professor.
                    </p>
                    
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="action" value="switch_role">
                        
                        <div class="form-group" style="max-width: 320px;">
                            <label class="form-label">Active Role Perspective</label>
                            <select class="form-control" name="role" onchange="this.form.submit()">
                                <option value="Administrator" <?= $_SESSION['user_role'] === 'Administrator' ? 'selected' : '' ?>>Administrator (Balaji Chaugule)</option>
                                <option value="Head of Department" <?= $_SESSION['user_role'] === 'Head of Department' ? 'selected' : '' ?>>Head of Department (Grace Hopper)</option>
                                <option value="Faculty Member" <?= $_SESSION['user_role'] === 'Faculty Member' ? 'selected' : '' ?>>Faculty Member (Grace Hopper)</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tab 2: Utility Complaint Registration -->
            <div class="settings-tab-content" id="tabComplaint">
                <div class="glass-card">
                    <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">File Classroom Infrastructure Ticket</h3>
                    
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="action" value="add_complaint">
                        
                        <div class="form-group">
                            <label class="form-label">Classroom / Facility Room</label>
                            <select class="form-control" name="room">
                                <option value="Room 301">Room 301</option>
                                <option value="Room 302">Room 302</option>
                                <option value="Room 303">Room 303</option>
                                <option value="Room 304">Room 304</option>
                                <option value="Programming Lab 1">Programming Lab 1</option>
                                <option value="Data Science Lab">Data Science Lab</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Utility Category</label>
                            <select class="form-control" name="type">
                                <option value="Projector">Projector System</option>
                                <option value="Internet">Internet Network</option>
                                <option value="Smart Board">Smart Board Controller</option>
                                <option value="Whiteboard">Glass / Whiteboard Marker</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Issue Details</label>
                            <input class="form-control" type="text" name="title" placeholder="e.g. HDMI cable missing, display flickers" required>
                        </div>
                        
                        <button class="btn btn-primary" type="submit" style="margin-top: 0.5rem;">File Ticket</button>
                    </form>
                </div>
            </div>
            
            <!-- Tab 3: Announcement Noticeboard -->
            <div class="settings-tab-content" id="tabAnnouncement">
                <div class="glass-card">
                    <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Broadcast Institutional notice</h3>
                    
                    <form action="settings.php" method="POST" style="margin-bottom: 2rem;">
                        <input type="hidden" name="action" value="add_announcement">
                        
                        <div class="form-group">
                            <label class="form-label">Notice Board Title</label>
                            <input class="form-control" type="text" name="title" placeholder="e.g. Lab network maintenance on Sunday morning" required>
                        </div>
                        
                        <button class="btn btn-primary" type="submit">Broadcast Notice</button>
                    </form>
                    
                    <h4 style="font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 700;">Broadcast History</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem;">
                        <?php foreach ($db['announcements'] as $item): ?>
                            <li style="display:flex; justify-content:space-between; padding: 0.45rem 0; border-bottom: 1px solid var(--border-color);">
                                <span><?= htmlspecialchars($item['title']) ?></span>
                                <span style="color: var(--text-muted); font-size: 0.7rem;"><?= $item['time'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Tab 4: Curriculum / Subject Manager -->
            <div class="settings-tab-content" id="tabSubject">
                <div class="glass-card">
                    <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Curriculum Subject Registry</h3>
                    
                    <form action="settings.php" method="POST" style="margin-bottom: 2rem;">
                        <input type="hidden" name="action" value="add_subject">
                        
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Subject Code</label>
                                <input class="form-control" type="text" name="code" placeholder="e.g. CS504" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Subject Title</label>
                                <input class="form-control" type="text" name="name" placeholder="e.g. Computer Networks" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Credits</label>
                                <input class="form-control" type="number" name="credits" min="1" max="6" value="3" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Semester</label>
                                <select class="form-control" name="semester">
                                    <option value="1st">1st Sem</option>
                                    <option value="3rd">3rd Sem</option>
                                    <option value="5th" selected>5th Sem</option>
                                    <option value="7th">7th Sem</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <select class="form-control" name="department">
                                    <?php foreach ($db['departments'] as $d): ?>
                                        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary" type="submit">Register Subject</button>
                    </form>
                    
                    <h4 style="font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 700;">Active Catalog</h4>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Subject Title</th>
                                    <th>Credits</th>
                                    <th>Semester</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($db['subjects'] as $sub): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($sub['code']) ?></code></td>
                                        <td style="font-weight: 600;"><?= htmlspecialchars($sub['name']) ?></td>
                                        <td><?= $sub['credits'] ?> credits</td>
                                        <td><?= $sub['semester'] ?> Semester</td>
                                        <td><?= htmlspecialchars($sub['department']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tab 5: Department Manager -->
            <div class="settings-tab-content" id="tabDept">
                <div class="glass-card">
                    <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Integrated Department Setups</h3>
                    
                    <form action="settings.php" method="POST" style="margin-bottom: 2rem;">
                        <input type="hidden" name="action" value="add_department">
                        <div class="form-group" style="max-width: 320px;">
                            <label class="form-label">New Department Name</label>
                            <input class="form-control" type="text" name="new_dept" placeholder="e.g. Civil Engineering" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Register Department</button>
                    </form>
                    
                    <h4 style="font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 700;">Registered Departments</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem;">
                        <?php foreach ($db['departments'] as $d): ?>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-weight:600;"><?= htmlspecialchars($d) ?></span>
                                <span class="badge badge-success">Active Division</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
        </div>
    </div>

</div>

<style>
    /* Settings-specific styles for neat vertical tabs */
    .settings-tab-btn {
        width: 100%;
        background: none;
        border: none;
        padding: 0.85rem 1.5rem;
        text-align: left;
        color: var(--text-secondary);
        font-weight: 500;
        font-size: 0.85rem;
        cursor: pointer;
        border-left: 3px solid transparent;
        transition: var(--transition);
    }
    
    .settings-tab-btn.active, .settings-tab-btn:hover {
        background: rgba(37, 99, 235, 0.05);
        color: var(--primary);
        border-left-color: var(--primary);
        font-weight: 600;
    }
    
    .settings-tab-content {
        display: none;
    }
    
    .settings-tab-content.active {
        display: block;
        animation: scaleUp 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<script>
    // Tab switching engine
    function switchSettingsTab(evt, tabId) {
        document.querySelectorAll('.settings-tab-content').forEach((tab) => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.settings-tab-btn').forEach((btn) => {
            btn.classList.remove('active');
        });
        
        document.getElementById(tabId).classList.add('active');
        evt.currentTarget.classList.add('active');
        
        // Save target tab hash
        window.location.hash = tabId;
    }

    // Auto load tab from location hash if page contains one
    window.addEventListener('DOMContentLoaded', (event) => {
        const hash = window.location.hash;
        if (hash) {
            const cleanedHash = hash.replace('#', '');
            const targetBtn = document.querySelector(`button[onclick*="${cleanedHash}"]`);
            if (targetBtn) {
                targetBtn.click();
            }
        }
        <?= $action_toast ?>
    });
</script>

<?php include 'footer.php'; ?>
