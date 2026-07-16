<?php
/*
================================================================
EduFlow AI - Academic Planning & Monitoring System
Timetable Planner - project/timetable.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$action_toast = '';

// Helper to resolve faculty name by ID
function resolveFacName($id, $list) {
    foreach ($list as $f) {
        if ($f['id'] == $id) return $f['name'];
    }
    return 'Unknown Faculty';
}

// Helper to resolve subject name by Code
function resolveSubName($code, $list) {
    foreach ($list as $s) {
        if ($s['code'] == $code) return $s['name'];
    }
    return $code;
}

// Handle Add Class to Timetable
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_timetable_slot') {
        $subject = $_POST['subject'] ?? '';
        $faculty_id = intval($_POST['faculty_id'] ?? 0);
        $room = $_POST['room'] ?? '';
        $time_slot = $_POST['time_slot'] ?? '';
        $day_of_week = $_POST['day_of_week'] ?? '';
        $dept = $_POST['department'] ?? '';
        $sem = $_POST['semester'] ?? '5th';
        
        // --- CONFLICT DETECTION ENGINE ---
        $clash_reason = '';
        foreach ($db['timetable'] as $item) {
            if ($item['day_of_week'] === $day_of_week && $item['time_slot'] === $time_slot) {
                // 1. Faculty Collision check
                if ($item['faculty_id'] === $faculty_id) {
                    $fac_name = resolveFacName($faculty_id, $db['faculty']);
                    $clash_reason = "Conflict Detected: $fac_name is already assigned to a session during $time_slot on $day_of_week.";
                    break;
                }
                // 2. Classroom Occupation check
                if ($item['room'] === $room) {
                    $clash_reason = "Conflict Detected: $room is already occupied by subject {$item['subject']} during $time_slot on $day_of_week.";
                    break;
                }
            }
        }
        
        if (!empty($clash_reason)) {
            // Reject booking
            $action_toast = "showToast('".addslashes($clash_reason)."', 'danger');";
        } else {
            // Book slot
            $new_id = count($db['timetable']) > 0 ? max(array_column($db['timetable'], 'id')) + 1 : 1;
            $db['timetable'][] = [
                'id' => $new_id,
                'subject' => $subject,
                'faculty_id' => $faculty_id,
                'room' => $room,
                'time_slot' => $time_slot,
                'day_of_week' => $day_of_week,
                'department' => $dept,
                'semester' => $sem
            ];
            $action_toast = "showToast('Timetable session allocated successfully for subject $subject in $room.', 'success');";
        }
    }
    
    // Handle Delete slot
    if (isset($_POST['action']) && $_POST['action'] === 'delete_slot') {
        $id = intval($_POST['id'] ?? 0);
        foreach ($db['timetable'] as $index => $item) {
            if ($item['id'] === $id) {
                unset($db['timetable'][$index]);
                $db['timetable'] = array_values($db['timetable']);
                $action_toast = "showToast('Timetable entry deleted.', 'warning');";
                break;
            }
        }
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$slots = [
    '09:00 AM - 10:00 AM',
    '10:00 AM - 11:00 AM',
    '11:15 AM - 01:15 PM',
    '02:00 PM - 03:00 PM',
    '03:15 PM - 04:15 PM'
];
?>

<div class="container-fluid">
    
    <!-- Title -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Weekly Timetable Planner</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Analyze room capacities, allocate time blocks, and prevent scheduler collisions automatically.</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addSlotModal').classList.add('active')">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Generate Session
        </button>
    </div>

    <!-- Timetable Weekly Grid View -->
    <div class="glass-card" style="margin-bottom: 2rem;">
        <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Semester Timetable Grid (5th Semester)</h3>
        
        <div class="table-responsive">
            <table class="modern-table" style="border: 1px solid var(--border-color);">
                <thead>
                    <tr>
                        <th style="width: 150px; background: rgba(15,23,42,0.03);">Day \ Time Slot</th>
                        <?php foreach ($slots as $slot): ?>
                            <th style="text-align: center; font-size: 0.7rem;"><?= $slot ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                        <tr>
                            <td style="font-weight: 700; background: rgba(15,23,42,0.01); color: var(--text-primary);"><?= $day ?></td>
                            <?php foreach ($slots as $slot): ?>
                                <td style="text-align: center; vertical-align: middle; padding: 0.75rem;">
                                    <?php 
                                    // Find entry
                                    $entry = null;
                                    foreach ($db['timetable'] as $item) {
                                        if ($item['day_of_week'] === $day && $item['time_slot'] === $slot) {
                                            $entry = $item;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($entry): ?>
                                        <div style="background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: var(--radius-md); padding: 0.5rem; text-align: left; position: relative;">
                                            <div style="font-weight: 600; font-size: 0.8rem; color: var(--primary);"><?= $entry['subject'] ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-secondary); margin: 0.15rem 0;">
                                                <?= htmlspecialchars(resolveFacName($entry['faculty_id'], $db['faculty'])) ?>
                                            </div>
                                            <div style="display:flex; justify-content:space-between; font-size:0.65rem; color: var(--text-muted);">
                                                <span><?= htmlspecialchars($entry['room']) ?></span>
                                                <!-- Delete button -->
                                                <form action="timetable.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this session?');">
                                                    <input type="hidden" name="action" value="delete_slot">
                                                    <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                                    <button type="submit" style="background:none; color:var(--danger); cursor:pointer; padding:0; display:flex; align-items:center;">
                                                        <svg viewBox="0 0 24 24" style="width:12px; height:12px; fill:currentColor;"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">- Free Slot -</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Dialog: Create Timetable Entry -->
<div class="modal-backdrop" id="addSlotModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Book Timetable Class</h3>
            <button class="modal-close" onclick="document.getElementById('addSlotModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="timetable.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_timetable_slot">
                
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <select class="form-control" name="subject">
                        <?php foreach ($db['subjects'] as $sub): ?>
                            <option value="<?= htmlspecialchars($sub['code']) ?>"><?= htmlspecialchars($sub['code']) ?> - <?= htmlspecialchars($sub['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Faculty Member</label>
                    <select class="form-control" name="faculty_id">
                        <?php foreach ($db['faculty'] as $fac): ?>
                            <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?> (<?= htmlspecialchars($fac['department']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Classroom / Laboratory</label>
                    <select class="form-control" name="room">
                        <option value="Room 301">Room 301</option>
                        <option value="Room 302">Room 302</option>
                        <option value="Room 303">Room 303</option>
                        <option value="Room 304">Room 304</option>
                        <option value="Programming Lab 1">Programming Lab 1</option>
                        <option value="Data Science Lab">Data Science Lab</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Day of Week</label>
                        <select class="form-control" name="day_of_week">
                            <?php foreach ($days as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Time Block</label>
                        <select class="form-control" name="time_slot">
                            <?php foreach ($slots as $sl): ?>
                                <option value="<?= htmlspecialchars($sl) ?>"><?= htmlspecialchars($sl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-control" name="department">
                            <?php foreach ($db['departments'] as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addSlotModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Verify & Book</button>
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
