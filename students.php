<?php
/*
================================================================
EduFlow AI - Academic Planning & Monitoring System
Student Directory - project/students.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$action_toast = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ADD STUDENT
    if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
        $name = trim($_POST['name'] ?? '');
        $roll = trim($_POST['roll_no'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dept = $_POST['department'] ?? '';
        $sem = $_POST['semester'] ?? '5th';
        $att = intval($_POST['attendance_pct'] ?? 80);
        
        if (empty($name) || empty($roll) || empty($email)) {
            $action_toast = "showToast('Failed to add student: Name, Roll No and Email are required.', 'danger');";
        } else {
            $new_id = count($db['students']) > 0 ? max(array_column($db['students'], 'id')) + 1 : 101;
            $db['students'][] = [
                'id' => $new_id,
                'name' => $name,
                'roll_no' => $roll,
                'email' => $email,
                'department' => $dept,
                'semester' => $sem,
                'attendance_pct' => $att
            ];
            $action_toast = "showToast('Student record created for \"".htmlspecialchars($name)."\".', 'success');";
        }
    }
    
    // 2. SEND PARENT ALERT
    if (isset($_POST['action']) && $_POST['action'] === 'send_alert') {
        $student_name = $_POST['student_name'] ?? 'Student';
        $email = $_POST['student_email'] ?? 'parent@student.com';
        $attendance = $_POST['student_attendance'] ?? '0';
        
        // Simulating the email trigger
        $action_toast = "showToast('Parent Alert Notification sent successfully to parents of ".htmlspecialchars($student_name)." (Current Attendance: {$attendance}%).', 'warning');";
    }
    
    // 3. DELETE STUDENT
    if (isset($_POST['action']) && $_POST['action'] === 'delete_student') {
        $id = intval($_POST['id'] ?? 0);
        foreach ($db['students'] as $index => $stud) {
            if ($stud['id'] === $id) {
                unset($db['students'][$index]);
                $db['students'] = array_values($db['students']);
                $action_toast = "showToast('Student record deleted.', 'warning');";
                break;
            }
        }
    }
}

// Filter logic
$selected_dept = $_GET['dept'] ?? '';
$filtered_students = $db['students'];
if (!empty($selected_dept)) {
    $filtered_students = array_filter($db['students'], function($s) use ($selected_dept) {
        return $s['department'] === $selected_dept;
    });
}
?>

<div class="container-fluid">
    
    <!-- Title Section -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Student Directory & Compliance</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Track attendance percentages and coordinate parent warnings for low compliance.</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addStudentModal').classList.add('active')">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Add Student
        </button>
    </div>

    <!-- Filters Section -->
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.25rem 1.75rem;">
        <form action="students.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px;">
                <input class="form-control" type="text" placeholder="Live filter student rows..." id="navSearchInput" onkeyup="globalSearchFilter()">
            </div>
            <div>
                <select class="form-control" name="dept" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($db['departments'] as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= $selected_dept === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($selected_dept)): ?>
                <a href="students.php" class="btn btn-secondary" style="padding: 0.65rem 1rem;">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Directory Listing -->
    <div class="glass-card">
        <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Enrolled Students</h3>
        
        <div class="table-responsive">
            <table class="modern-table modern-table-zebra">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Email Contact</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Attendance Rate</th>
                        <th style="text-align: right; width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filtered_students)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem 0; color: var(--text-muted);">
                                No student records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filtered_students as $stud): ?>
                            <?php 
                            $is_low = $stud['attendance_pct'] < 75;
                            ?>
                            <tr <?= $is_low ? 'style="background-color: rgba(239, 68, 68, 0.03);"' : '' ?>>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($stud['name']) ?></div>
                                </td>
                                <td><code style="font-size: 0.8rem;"><?= htmlspecialchars($stud['roll_no']) ?></code></td>
                                <td><?= htmlspecialchars($stud['email']) ?></td>
                                <td><?= htmlspecialchars($stud['department']) ?></td>
                                <td><?= htmlspecialchars($stud['semester']) ?></td>
                                <td>
                                    <span class="badge <?= $is_low ? 'badge-danger' : 'badge-success' ?>" style="font-size: 0.75rem;">
                                        <?= $stud['attendance_pct'] ?>%
                                    </span>
                                </td>
                                <td style="text-align: right; display: flex; gap: 0.35rem; justify-content: flex-end;">
                                    <?php if ($is_low): ?>
                                        <form action="students.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="send_alert">
                                            <input type="hidden" name="student_name" value="<?= htmlspecialchars($stud['name']) ?>">
                                            <input type="hidden" name="student_email" value="<?= htmlspecialchars($stud['email']) ?>">
                                            <input type="hidden" name="student_attendance" value="<?= $stud['attendance_pct'] ?>">
                                            <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.7rem; border-color: rgba(245, 158, 11, 0.3); color: var(--warning);" title="Notify Parents">
                                                Notify Parent
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form action="students.php" method="POST" style="display: inline;" onsubmit="return confirm('Remove student record?');">
                                        <input type="hidden" name="action" value="delete_student">
                                        <input type="hidden" name="id" value="<?= $stud['id'] ?>">
                                        <button type="submit" class="btn-icon" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2);">
                                            <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Dialog: Add Student -->
<div class="modal-backdrop" id="addStudentModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Enroll Student Record</h3>
            <button class="modal-close" onclick="document.getElementById('addStudentModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="students.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_student">
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input class="form-control" type="text" name="name" placeholder="e.g. Peter Parker" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Roll Number</label>
                    <input class="form-control" type="text" name="roll_no" placeholder="e.g. CS-2026-08" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input class="form-control" type="email" name="email" placeholder="e.g. parker.p@student.edu" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select class="form-control" name="department">
                        <?php foreach ($db['departments'] as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Semester</label>
                        <select class="form-control" name="semester">
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="3rd">3rd Semester</option>
                            <option value="4th">4th Semester</option>
                            <option value="5th" selected>5th Semester</option>
                            <option value="6th">6th Semester</option>
                            <option value="7th">7th Semester</option>
                            <option value="8th">8th Semester</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mock Attendance %</label>
                        <input class="form-control" type="number" name="attendance_pct" min="0" max="100" value="85" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addStudentModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Enroll Student</button>
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
