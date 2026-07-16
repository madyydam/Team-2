<?php
/*
================================================================
EduFlow AI - Academic Planning & Monitoring System
Faculty Allocation - project/faculty.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$action_toast = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ADD FACULTY
    if (isset($_POST['action']) && $_POST['action'] === 'add_faculty') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dept = $_POST['department'] ?? '';
        $workload = intval($_POST['workload'] ?? 0);
        $status = $_POST['status'] ?? 'Active';
        $qualification = trim($_POST['qualification'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $expertise = trim($_POST['expertise'] ?? '');
        $photo = trim($_POST['photo'] ?? '');
        
        if (empty($name) || empty($email)) {
            $action_toast = "showToast('Failed to add: Name and Email are required.', 'danger');";
        } else {
            $new_id = count($db['faculty']) > 0 ? max(array_column($db['faculty'], 'id')) + 1 : 1;
            $db['faculty'][] = [
                'id' => $new_id,
                'name' => $name,
                'email' => $email,
                'department' => $dept,
                'workload' => $workload,
                'status' => $status,
                'qualification' => $qualification,
                'designation' => $designation,
                'experience' => $experience,
                'expertise' => $expertise,
                'photo' => $photo
            ];
            $action_toast = "showToast('Faculty member \"".htmlspecialchars($name)."\" added successfully.', 'success');";
        }
    }
    
    // 2. EDIT FACULTY
    if (isset($_POST['action']) && $_POST['action'] === 'edit_faculty') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dept = $_POST['department'] ?? '';
        $workload = intval($_POST['workload'] ?? 0);
        $status = $_POST['status'] ?? 'Active';
        $qualification = trim($_POST['qualification'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $expertise = trim($_POST['expertise'] ?? '');
        $photo = trim($_POST['photo'] ?? '');
        
        $updated = false;
        foreach ($db['faculty'] as &$fac) {
            if ($fac['id'] === $id) {
                $fac['name'] = $name;
                $fac['email'] = $email;
                $fac['department'] = $dept;
                $fac['workload'] = $workload;
                $fac['status'] = $status;
                $fac['qualification'] = $qualification;
                $fac['designation'] = $designation;
                $fac['experience'] = $experience;
                $fac['expertise'] = $expertise;
                $fac['photo'] = $photo;
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            $action_toast = "showToast('Faculty record updated successfully.', 'success');";
        } else {
            $action_toast = "showToast('Failed to update faculty record.', 'danger');";
        }
    }
    
    // 3. DELETE FACULTY
    if (isset($_POST['action']) && $_POST['action'] === 'delete_faculty') {
        $id = intval($_POST['id'] ?? 0);
        foreach ($db['faculty'] as $index => $fac) {
            if ($fac['id'] === $id) {
                unset($db['faculty'][$index]);
                $db['faculty'] = array_values($db['faculty']);
                $action_toast = "showToast('Faculty member removed from system.', 'warning');";
                break;
            }
        }
    }
}

// Filters logic (PHP based filtering for initial load, Javascript search does live filter)
$selected_dept = $_GET['dept'] ?? '';
$filtered_faculty = $db['faculty'];
if (!empty($selected_dept)) {
    $filtered_faculty = array_filter($db['faculty'], function($f) use ($selected_dept) {
        return $f['department'] === $selected_dept;
    });
}
?>

<div class="container-fluid">
    
    <!-- Title Section -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Faculty Allocation & Workload</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Monitor teaching loads, departmental allocations, and active schedules.</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addFacultyModal').classList.add('active')">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Allocate Faculty
        </button>
    </div>

    <!-- Filters Section -->
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.25rem 1.75rem;">
        <form action="faculty.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px;">
                <input class="form-control" type="text" placeholder="Live filter list..." id="navSearchInput" onkeyup="globalSearchFilter()">
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
                <a href="faculty.php" class="btn btn-secondary" style="padding: 0.65rem 1rem;">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Faculty Directory -->
    <div class="glass-card">
        <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Faculty Directory</h3>
        
        <div class="table-responsive">
            <table class="modern-table modern-table-zebra" style="vertical-align: middle;">
                <thead>
                    <tr>
                        <th style="min-width: 260px;">Faculty Profile</th>
                        <th>Credentials & Experience</th>
                        <th style="min-width: 200px;">Core Expertise</th>
                        <th>Email Contact</th>
                        <th style="width: 200px;">Teaching Workload</th>
                        <th>Status</th>
                        <th style="text-align: right; width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filtered_faculty)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem 0; color: var(--text-muted);">
                                No faculty members found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filtered_faculty as $fac): ?>
                            <?php 
                            // Determine workload percentage progress bar style
                            $workload_pct = min(100, round(($fac['workload'] / 20) * 100)); // Max ideal workload is 20 hrs
                            $workload_color = 'var(--accent)';
                            if ($fac['workload'] > 16) {
                                $workload_color = 'var(--danger)'; // Overloaded
                            } elseif ($fac['workload'] < 10) {
                                $workload_color = 'var(--warning)'; // Underutilized
                            }
                            // Detect HOD
                            $is_hod = (stripos($fac['designation'] ?? '', 'head') !== false);
                            $row_style = $is_hod ? 'background: linear-gradient(90deg, rgba(245,158,11,0.10) 0%, rgba(251,191,36,0.04) 100%); border-left: 3px solid #f59e0b;' : '';
                            $desig_color = $is_hod ? '#b45309' : 'var(--primary)';
                            ?>
                            <tr style="<?= $row_style ?>">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <?php if (!empty($fac['photo']) && file_exists(__DIR__ . '/' . $fac['photo'])): ?>
                                            <img src="<?= htmlspecialchars($fac['photo']) ?>" alt="<?= htmlspecialchars($fac['name']) ?>"
                                                 style="width: 110px; height: 110px; border-radius: 12px; object-fit: cover; flex-shrink: 0;
                                                        border: 3px solid <?= $is_hod ? '#f59e0b' : 'var(--border-color)' ?>;
                                                        box-shadow: <?= $is_hod ? '0 0 0 4px rgba(245,158,11,0.15), 0 6px 16px rgba(0,0,0,0.18)' : '0 2px 10px rgba(0,0,0,0.12)' ?>;
                                                        transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                                        <?php else: ?>
                                            <?php 
                                            $initials = '';
                                            $words = explode(' ', $fac['name']);
                                            foreach ($words as $w) {
                                                if (!empty($w)) $initials .= strtoupper($w[0]);
                                            }
                                            $initials = substr($initials, 0, 2);
                                            $hash = crc32($fac['name']);
                                            $hue1 = abs($hash) % 360;
                                            $hue2 = ($hue1 + 40) % 360;
                                            $gradient = $is_hod
                                                ? 'linear-gradient(135deg, #f59e0b, #d97706)'
                                                : "linear-gradient(135deg, hsl($hue1, 70%, 55%), hsl($hue2, 80%, 45%))";
                                            ?>
                                            <div style="width: 110px; height: 110px; border-radius: 12px; background: <?= $gradient ?>; color: white;
                                                        display: flex; align-items: center; justify-content: center; font-weight: 700;
                                                        font-size: 1.8rem; flex-shrink: 0; letter-spacing: 0.05em;
                                                        border: 3px solid <?= $is_hod ? '#f59e0b' : 'var(--border-color)' ?>;
                                                        box-shadow: <?= $is_hod ? '0 0 0 4px rgba(245,158,11,0.15), 0 6px 16px rgba(0,0,0,0.18)' : '0 2px 10px rgba(0,0,0,0.12)' ?>;
                                                        transition: transform 0.2s ease;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                                                <?= $initials ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-primary); font-size: 0.92rem; line-height: 1.2; display: flex; align-items: center; gap: 0.4rem;">
                                                <?= htmlspecialchars($fac['name']) ?>
                                                <?php if ($is_hod): ?>
                                                    <span title="Head of Department" style="font-size: 0.65rem; background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; padding: 0.1rem 0.4rem; border-radius: 99px; font-weight: 600; letter-spacing: 0.02em;">HOD</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 0.72rem; color: <?= $desig_color ?>; font-weight: 600; margin-top: 0.2rem;">
                                                <?php if ($is_hod): ?>👑 <?php endif; ?><?= htmlspecialchars($fac['designation'] ?? 'Faculty Member') ?>
                                            </div>
                                            <span style="font-size: 0.65rem; color: var(--text-muted);">ID: FAC-00<?= $fac['id'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--text-primary); font-size: 0.82rem;"><?= htmlspecialchars($fac['qualification'] ?? 'N/A') ?></div>
                                    <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 0.2rem;">
                                        <svg viewBox="0 0 24 24" style="width: 11px; height: 11px; fill: currentColor; vertical-align: middle; margin-right: 4px; display: inline-block;"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
                                        <?= htmlspecialchars($fac['experience'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        <?php 
                                        $expertise_str = $fac['expertise'] ?? '';
                                        if (!empty($expertise_str) && $expertise_str !== '-'): 
                                            $tags = explode(',', $expertise_str);
                                            foreach ($tags as $tag): 
                                                $tag = trim($tag);
                                                if (empty($tag)) continue;
                                                $tag_bg = $is_hod ? 'rgba(245,158,11,0.08)' : 'rgba(var(--primary-rgb), 0.06)';
                                                $tag_color = $is_hod ? '#92400e' : 'var(--primary)';
                                                $tag_border = $is_hod ? 'rgba(245,158,11,0.25)' : 'rgba(var(--primary-rgb), 0.12)';
                                                ?>
                                                <span style="background: <?= $tag_bg ?>; color: <?= $tag_color ?>; border: 1px solid <?= $tag_border ?>; padding: 0.15rem 0.45rem; border-radius: 99px; font-size: 0.65rem; font-weight: 500; display: inline-block;">
                                                    <?= htmlspecialchars($tag) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.72rem;">N/A</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="font-size: 0.8rem; font-weight: 500; color: var(--text-secondary);"><?= htmlspecialchars($fac['email']) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.3rem; font-size: 0.72rem;">
                                        <span style="font-weight: 600;"><?= $fac['workload'] ?> hrs / week</span>
                                        <span style="color: var(--text-muted);"><?= $workload_pct ?>%</span>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div style="width: 100%; height: 5px; background-color: var(--border-color); border-radius: 99px; overflow: hidden;">
                                        <div style="width: <?= $workload_pct ?>%; height: 100%; background-color: <?= $is_hod ? '#f59e0b' : $workload_color ?>; border-radius: 99px;"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $fac['status'] === 'Active' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $fac['status'] ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <button class="btn-icon" onclick='openEditModal(<?= json_encode($fac) ?>)' style="margin-right: 0.25rem;">
                                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </button>
                                    <form action="faculty.php" method="POST" style="display: inline;" onsubmit="return confirm('Remove this faculty member?');">
                                        <input type="hidden" name="action" value="delete_faculty">
                                        <input type="hidden" name="id" value="<?= $fac['id'] ?>">
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

<!-- Modal 1: Add Faculty -->
<div class="modal-backdrop" id="addFacultyModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Allocate Faculty Member</h3>
            <button class="modal-close" onclick="document.getElementById('addFacultyModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="faculty.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_faculty">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" type="text" name="name" placeholder="e.g. Dr. Richard Feynman" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input class="form-control" type="email" name="email" placeholder="e.g. feynman.r@zealeducation.com" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <input class="form-control" type="text" name="qualification" placeholder="e.g. M.E. (Computer Engineering)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <input class="form-control" type="text" name="designation" placeholder="e.g. Assistant Professor">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Experience</label>
                    <input class="form-control" type="text" name="experience" placeholder="e.g. Teaching - 5 Years, Industrial - 2 Years">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Core Expertise <span style="font-weight: 400; color: var(--text-muted);">(comma-separated)</span></label>
                    <input class="form-control" type="text" name="expertise" placeholder="e.g. Data Science, Machine Learning, Python">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-control" name="department">
                            <?php foreach ($db['departments'] as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo Path <span style="font-weight: 400; color: var(--text-muted);">(relative)</span></label>
                        <input class="form-control" type="text" name="photo" placeholder="e.g. img/name.jpg">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Workload (Hours/Week)</label>
                        <input class="form-control" type="number" name="workload" min="0" max="30" value="12" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status">
                            <option value="Active">Active</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addFacultyModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Allocate Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Faculty -->
<div class="modal-backdrop" id="editFacultyModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Edit Faculty Record</h3>
            <button class="modal-close" onclick="document.getElementById('editFacultyModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="faculty.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_faculty">
                <input type="hidden" name="id" id="edit_faculty_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input class="form-control" type="email" name="email" id="edit_email" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <input class="form-control" type="text" name="qualification" id="edit_qualification">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <input class="form-control" type="text" name="designation" id="edit_designation">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Experience</label>
                    <input class="form-control" type="text" name="experience" id="edit_experience">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Core Expertise <span style="font-weight: 400; color: var(--text-muted);">(comma-separated)</span></label>
                    <input class="form-control" type="text" name="expertise" id="edit_expertise">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-control" name="department" id="edit_department">
                            <?php foreach ($db['departments'] as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo Path</label>
                        <input class="form-control" type="text" name="photo" id="edit_photo">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Workload (Hours/Week)</label>
                        <input class="form-control" type="number" name="workload" id="edit_workload" min="0" max="30" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" id="edit_status">
                            <option value="Active">Active</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editFacultyModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Record</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Open edit modal and populate all fields
    function openEditModal(faculty) {
        document.getElementById('edit_faculty_id').value = faculty.id;
        document.getElementById('edit_name').value = faculty.name;
        document.getElementById('edit_email').value = faculty.email;
        document.getElementById('edit_department').value = faculty.department;
        document.getElementById('edit_workload').value = faculty.workload;
        document.getElementById('edit_status').value = faculty.status;
        document.getElementById('edit_qualification').value = faculty.qualification || '';
        document.getElementById('edit_designation').value = faculty.designation || '';
        document.getElementById('edit_experience').value = faculty.experience || '';
        document.getElementById('edit_expertise').value = faculty.expertise || '';
        document.getElementById('edit_photo').value = faculty.photo || '';
        document.getElementById('editFacultyModal').classList.add('active');
    }

    window.addEventListener('DOMContentLoaded', (event) => {
        <?= $action_toast ?>
    });
</script>

<?php include 'footer.php'; ?>
