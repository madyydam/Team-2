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
    
    <!-- Title Header & View Switcher -->
    <div class="glass-card-header" style="margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Weekly Timetable Planner</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Analyze room capacities, allocate time blocks, and prevent scheduler collisions automatically.</p>
        </div>
        
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <!-- Day / Week / Month Mode Tabs -->
            <div style="display: flex; background: rgba(15,23,42,0.06); padding: 4px; border-radius: 8px; gap: 4px;">
                <button type="button" class="view-mode-btn" id="btnViewDay" onclick="switchTimetableMode('day')" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600; border: none; background: transparent; border-radius: 6px; cursor: pointer; color: var(--text-secondary); transition: all 0.2s;">
                    📅 Day View
                </button>
                <button type="button" class="view-mode-btn active" id="btnViewWeek" onclick="switchTimetableMode('week')" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600; border: none; background: var(--primary); color: #fff; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(37,99,235,0.3);">
                    📆 Week View
                </button>
                <button type="button" class="view-mode-btn" id="btnViewMonth" onclick="switchTimetableMode('month')" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600; border: none; background: transparent; border-radius: 6px; cursor: pointer; color: var(--text-secondary); transition: all 0.2s;">
                    🗓️ Month View
                </button>
            </div>
            
            <button class="btn btn-primary" onclick="document.getElementById('addSlotModal').classList.add('active')">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Generate Session
            </button>
        </div>
    </div>

    <?php
    // Helper to resolve session status from Attendance Monitoring
    function resolveSessionStatus($subjectCode, $db) {
        if (isset($db['class_sessions'])) {
            foreach ($db['class_sessions'] as $cs) {
                if ($cs['subject'] === $subjectCode) {
                    return $cs['status'];
                }
            }
        }
        return 'Pending';
    }
    ?>

    <!-- ======================================================== -->
    <!-- VIEW 1: WEEKLY GRID VIEW (Default) -->
    <!-- ======================================================== -->
    <div id="timetableWeekView" class="glass-card" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 class="glass-card-title">Semester Timetable Grid (5th Semester)</h3>
            
            <!-- Legend indicators for Green (Conducted) and Red (Cancelled) -->
            <div style="display: flex; gap: 1rem; font-size: 0.75rem; font-weight: 600;">
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block;"></span> Conducted (Green)
                </span>
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444; display: inline-block;"></span> Cancelled (Red)
                </span>
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #2563eb; display: inline-block;"></span> Pending (Blue)
                </span>
            </div>
        </div>
        
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
                                        <?php 
                                        $status = isset($entry['status']) ? $entry['status'] : resolveSessionStatus($entry['subject'], $db);
                                        
                                        if ($status === 'Completed') {
                                            $cardStyle = 'background: rgba(16, 185, 129, 0.12); border: 2px solid #10b981;';
                                            $titleColor = '#047857';
                                            $badgeTag = '<span class="badge badge-success" style="font-size:0.6rem; padding: 2px 6px; margin-top: 4px; display: inline-block;">✓ Conducted</span>';
                                        } elseif ($status === 'Cancelled') {
                                            $cardStyle = 'background: rgba(239, 68, 68, 0.12); border: 2px solid #ef4444;';
                                            $titleColor = '#b91c1c';
                                            $badgeTag = '<span class="badge badge-danger" style="font-size:0.6rem; padding: 2px 6px; margin-top: 4px; display: inline-block;">✗ Cancelled</span>';
                                        } else {
                                            $cardStyle = 'background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.2);';
                                            $titleColor = 'var(--primary)';
                                            $badgeTag = '<span class="badge badge-warning" style="font-size:0.6rem; padding: 2px 6px; margin-top: 4px; display: inline-block;">⏳ Pending</span>';
                                        }
                                        ?>
                                        <div style="<?= $cardStyle ?> border-radius: var(--radius-md); padding: 0.5rem; text-align: left; position: relative;">
                                            <div style="font-weight: 700; font-size: 0.82rem; color: <?= $titleColor ?>; display: flex; justify-content: space-between; align-items: center;">
                                                <span><?= $entry['subject'] ?></span>
                                            </div>
                                            <div style="font-size: 0.7rem; color: var(--text-secondary); margin: 0.15rem 0; font-weight: 500;">
                                                <?= htmlspecialchars(resolveFacName($entry['faculty_id'], $db['faculty'])) ?>
                                            </div>
                                            <div style="display:flex; justify-content:space-between; align-items: center; font-size:0.65rem; color: var(--text-muted);">
                                                <span><?= htmlspecialchars($entry['room']) ?></span>
                                                <!-- Delete button -->
                                                <form action="timetable.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this session?');">
                                                    <input type="hidden" name="action" value="delete_slot">
                                                    <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                                    <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; padding:0; display:flex; align-items:center;">
                                                        <svg viewBox="0 0 24 24" style="width:12px; height:12px; fill:currentColor;"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                            <div><?= $badgeTag ?></div>
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

    <!-- ======================================================== -->
    <!-- VIEW 2: DAILY TIMETABLE VIEW -->
    <!-- ======================================================== -->
    <div id="timetableDayView" class="glass-card" style="display: none; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 class="glass-card-title">Daily Agenda Schedule</h3>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">Select a day to view its complete time-block breakdown.</p>
            </div>
            
            <!-- Day Pill Filter Buttons -->
            <div style="display: flex; gap: 0.5rem;" id="dayPillsContainer">
                <?php foreach ($days as $idx => $d): ?>
                    <button type="button" class="day-pill-btn <?= $idx === 0 ? 'active' : '' ?>" onclick="selectDayFilter('<?= $d ?>', this)" style="padding: 0.4rem 0.85rem; font-size: 0.78rem; font-weight: 600; border-radius: 99px; border: 1px solid var(--border-color); background: <?= $idx === 0 ? 'var(--primary)' : 'var(--glass-bg)' ?>; color: <?= $idx === 0 ? '#fff' : 'var(--text-primary)' ?>; cursor: pointer; transition: all 0.2s;">
                        <?= $d ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php foreach ($days as $idx => $d): ?>
            <div class="day-schedule-panel" id="panelDay_<?= $d ?>" style="display: <?= $idx === 0 ? 'block' : 'none' ?>;">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php 
                    $dayEntries = array_filter($db['timetable'], function($item) use ($d) {
                        return $item['day_of_week'] === $d;
                    });
                    ?>
                    <?php if (empty($dayEntries)): ?>
                        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎉</div>
                            <h4 style="margin: 0; color: var(--text-primary);">No Sessions Scheduled for <?= $d ?></h4>
                            <p style="font-size: 0.8rem; margin-top: 0.25rem;">This day is open for self-study or faculty research work.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($dayEntries as $entry): ?>
                            <?php 
                            $status = isset($entry['status']) ? $entry['status'] : resolveSessionStatus($entry['subject'], $db);
                            $badgeClass = ($status === 'Completed') ? 'badge-success' : (($status === 'Cancelled') ? 'badge-danger' : 'badge-warning');
                            $borderColor = ($status === 'Completed') ? '#10b981' : (($status === 'Cancelled') ? '#ef4444' : 'var(--primary)');
                            ?>
                            <div style="display: flex; align-items: center; gap: 1.5rem; background: var(--glass-bg); border-left: 4px solid <?= $borderColor ?>; border-radius: var(--radius-md); padding: 1rem 1.25rem; border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                <div style="min-width: 140px; font-weight: 700; color: var(--text-primary); font-size: 0.85rem;">
                                    ⏰ <?= htmlspecialchars($entry['time_slot']) ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <h4 style="margin: 0; font-size: 1rem; color: var(--primary);"><?= htmlspecialchars($entry['subject']) ?></h4>
                                        <span class="badge <?= $badgeClass ?>"><?= $status === 'Completed' ? 'Conducted' : $status ?></span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                        Faculty: <strong><?= htmlspecialchars(resolveFacName($entry['faculty_id'], $db['faculty'])) ?></strong>
                                        &nbsp;·&nbsp; Location: <strong><?= htmlspecialchars($entry['room']) ?></strong>
                                    </div>
                                </div>
                                <div>
                                    <a href="attendance.php" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; border: 1px solid var(--border-color);">Manage Attendance</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ======================================================== -->
    <!-- VIEW 3: MONTHLY CALENDAR VIEW -->
    <!-- ======================================================== -->
    <div id="timetableMonthView" class="glass-card" style="display: none; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div>
                <h3 class="glass-card-title">Monthly Academic Calendar Grid (July 2026)</h3>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">Overview of full monthly course distribution and milestones.</p>
            </div>
            <a href="calendar.php" class="btn btn-secondary" style="padding: 0.4rem 0.85rem; font-size: 0.75rem; border: 1px solid var(--border-color);">Full Events Calendar →</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center; font-weight: 700; font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary);">
            <div style="padding: 0.5rem; background: rgba(15,23,42,0.03); border-radius: 6px;">Mon</div>
            <div style="padding: 0.5rem; background: rgba(15,23,42,0.03); border-radius: 6px;">Tue</div>
            <div style="padding: 0.5rem; background: rgba(15,23,42,0.03); border-radius: 6px;">Wed</div>
            <div style="padding: 0.5rem; background: rgba(15,23,42,0.03); border-radius: 6px;">Thu</div>
            <div style="padding: 0.5rem; background: rgba(15,23,42,0.03); border-radius: 6px;">Fri</div>
            <div style="padding: 0.5rem; background: rgba(239,68,68,0.05); color: var(--danger); border-radius: 6px;">Sat</div>
            <div style="padding: 0.5rem; background: rgba(239,68,68,0.05); color: var(--danger); border-radius: 6px;">Sun</div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px;">
            <?php
            // July 2026 layout
            // Starts on Wednesday (day 3 of week) -> 2 blank cells before 1st
            echo '<div></div><div></div>'; // Blank Mon, Tue
            
            $dayNamesMap = [1=>'Wednesday', 2=>'Thursday', 3=>'Friday', 4=>'Saturday', 5=>'Sunday', 6=>'Monday', 7=>'Tuesday'];
            
            for ($mDay = 1; $mDay <= 31; $mDay++) {
                // Map date to day name
                $weekdayIdx = ($mDay + 1) % 7; // 1=Wed, 2=Thu...
                $dayName = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'][($mDay + 1) % 7];
                $isWeekend = ($dayName === 'Saturday' || $dayName === 'Sunday');
                
                // Get sessions for this weekday
                $mEntries = [];
                if (!$isWeekend) {
                    foreach ($db['timetable'] as $t_item) {
                        if ($t_item['day_of_week'] === $dayName) {
                            $mEntries[] = $t_item;
                        }
                    }
                }
                
                $isToday = ($mDay === 15);
                $bgStyle = $isToday ? 'background: rgba(37, 99, 235, 0.08); border: 2px solid var(--primary);' : ($isWeekend ? 'background: rgba(15,23,42,0.01); opacity: 0.6;' : 'background: var(--glass-bg); border: 1px solid var(--border-color);');
                
                echo '<div style="min-height: 90px; border-radius: 8px; padding: 0.5rem; ' . $bgStyle . ' text-align: left; display: flex; flex-direction: column; justify-content: space-between;">';
                echo '<div style="font-weight: 700; font-size: 0.85rem; color: ' . ($isToday ? 'var(--primary)' : 'var(--text-primary)') . ';">' . $mDay . ($isToday ? ' <span style="font-size:0.6rem; background:var(--primary); color:#fff; padding:1px 4px; border-radius:4px;">Today</span>' : '') . '</div>';
                
                if (!empty($mEntries)) {
                    echo '<div style="display:flex; flex-direction:column; gap:3px; margin-top:4px;">';
                    foreach ($mEntries as $me) {
                        $st = resolveSessionStatus($me['subject'], $db);
                        $dotColor = ($st === 'Completed') ? '#10b981' : (($st === 'Cancelled') ? '#ef4444' : '#2563eb');
                        echo '<div style="font-size:0.62rem; background:rgba(15,23,42,0.04); padding:2px 4px; border-radius:4px; border-left: 2px solid '.$dotColor.'; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' . $me['subject'] . '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div style="font-size:0.6rem; color:var(--text-muted); margin-top:4px;">' . ($isWeekend ? 'Weekend' : 'No Class') . '</div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
    </div>

</div>

<!-- View Switcher Script -->
<script>
    function switchTimetableMode(mode) {
        const weekView  = document.getElementById('timetableWeekView');
        const dayView   = document.getElementById('timetableDayView');
        const monthView = document.getElementById('timetableMonthView');
        
        const btnDay   = document.getElementById('btnViewDay');
        const btnWeek  = document.getElementById('btnViewWeek');
        const btnMonth = document.getElementById('btnViewMonth');

        // Reset all buttons style
        [btnDay, btnWeek, btnMonth].forEach(btn => {
            btn.style.background = 'transparent';
            btn.style.color = 'var(--text-secondary)';
            btn.style.boxShadow = 'none';
        });

        if (mode === 'day') {
            weekView.style.display  = 'none';
            dayView.style.display   = 'block';
            monthView.style.display = 'none';
            
            btnDay.style.background = 'var(--primary)';
            btnDay.style.color = '#fff';
            btnDay.style.boxShadow = '0 2px 8px rgba(37,99,235,0.3)';
        } else if (mode === 'month') {
            weekView.style.display  = 'none';
            dayView.style.display   = 'none';
            monthView.style.display = 'block';
            
            btnMonth.style.background = 'var(--primary)';
            btnMonth.style.color = '#fff';
            btnMonth.style.boxShadow = '0 2px 8px rgba(37,99,235,0.3)';
        } else {
            weekView.style.display  = 'block';
            dayView.style.display   = 'none';
            monthView.style.display = 'none';
            
            btnWeek.style.background = 'var(--primary)';
            btnWeek.style.color = '#fff';
            btnWeek.style.boxShadow = '0 2px 8px rgba(37,99,235,0.3)';
        }
    }

    function selectDayFilter(dayName, btnElem) {
        const panels = document.querySelectorAll('.day-schedule-panel');
        panels.forEach(p => p.style.display = 'none');
        
        const targetPanel = document.getElementById('panelDay_' + dayName);
        if (targetPanel) targetPanel.style.display = 'block';
        
        const pills = document.querySelectorAll('.day-pill-btn');
        pills.forEach(p => {
            p.style.background = 'var(--glass-bg)';
            p.style.color = 'var(--text-primary)';
        });
        
        btnElem.style.background = 'var(--primary)';
        btnElem.style.color = '#fff';
    }
</script>

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
