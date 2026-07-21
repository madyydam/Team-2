<?php
/*
================================================================
EduFlow AI - Academic Planning & Monitoring System
Academic Calendar & Holiday Management - project/calendar.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$action_toast = '';

// Handle add event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_event') {
        $title = trim($_POST['title'] ?? '');
        $type = $_POST['type'] ?? 'Academic Event';
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        
        if (empty($title) || empty($start)) {
            $action_toast = "showToast('Failed to add event: Title and Start Date are required.', 'danger');";
        } else {
            $new_id = count($db['calendar']) > 0 ? max(array_column($db['calendar'], 'id')) + 1 : 1;
            $db['calendar'][] = [
                'id' => $new_id,
                'title' => $title,
                'type' => $type,
                'start_date' => $start,
                'end_date' => empty($end) ? $start : $end,
            ];
            $action_toast = "showToast('New event \"".htmlspecialchars($title)."\" has been scheduled!', 'success');";
        }
    }
    
    // Handle delete event
    if (isset($_POST['action']) && $_POST['action'] === 'delete_event') {
        $event_id = intval($_POST['event_id'] ?? 0);
        foreach ($db['calendar'] as $index => $ev) {
            if ($ev['id'] === $event_id) {
                unset($db['calendar'][$index]);
                // Reindex array
                $db['calendar'] = array_values($db['calendar']);
                $action_toast = "showToast('Event has been deleted from the calendar.', 'warning');";
                break;
            }
        }
    }
}
?>

<div class="container-fluid">
    
    <!-- Header & View Switcher -->
    <div class="glass-card-header" style="margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700;">Academic Calendar</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Schedule semester start, midterms, project deadlines, and holidays.</p>
        </div>
        
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <!-- Day / Week / Month View Switcher -->
            <div style="display: flex; background: rgba(15,23,42,0.06); padding: 4px; border-radius: 8px; gap: 4px;">
                <button type="button" class="cal-view-btn" id="btnCalDay" onclick="switchCalMode('day')" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600; border: none; background: transparent; border-radius: 6px; cursor: pointer; color: var(--text-secondary); transition: all 0.2s;">
                    📅 Day View
                </button>
                <button type="button" class="cal-view-btn" id="btnCalWeek" onclick="switchCalMode('week')" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600; border: none; background: transparent; border-radius: 6px; cursor: pointer; color: var(--text-secondary); transition: all 0.2s;">
                    📆 Week View
                </button>
                <button type="button" class="cal-view-btn active" id="btnCalMonth" onclick="switchCalMode('month')" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600; border: none; background: var(--primary); color: #fff; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(37,99,235,0.3);">
                    🗓️ Month View
                </button>
            </div>
            
            <button class="btn btn-primary" onclick="toggleDropdown('addEventModal')">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Add Milestone
            </button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
        
        <!-- List of Calendar Milestones -->
        <div class="glass-card">
            <h3 class="glass-card-title" style="margin-bottom: 1.25rem;">Upcoming Dates & Holidays</h3>
            
            <div class="table-responsive">
                <table class="modern-table modern-table-zebra">
                    <thead>
                        <tr>
                            <th>Milestone / Event</th>
                            <th>Category</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($db['calendar'])): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem 0; color: var(--text-muted);">
                                    No semester events scheduled yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($db['calendar'] as $ev): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($ev['title']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $ev['type'] === 'Holiday' ? 'badge-danger' : ($ev['type'] === 'Exam' ? 'badge-warning' : 'badge-success') ?>">
                                            <?= $ev['type'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('F d, Y', strtotime($ev['start_date'])) ?></td>
                                    <td><?= !empty($ev['end_date']) && $ev['end_date'] !== $ev['start_date'] ? date('F d, Y', strtotime($ev['end_date'])) : '-' ?></td>
                                    <td style="text-align: right;">
                                        <form action="calendar.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this event?');">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
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
        
        <!-- Side Widgets: Today's Schedule Overview -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Today Status Widget -->
            <div class="glass-card" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);">
                <h3 class="glass-card-title" style="margin-bottom: 0.75rem;">Today's Details</h3>
                <div style="font-size: 2.25rem; font-weight: 700; color: var(--primary);"><?= date('d') ?></div>
                <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;"><?= date('l, F Y') ?></div>
                <div class="badge badge-success">No Holidays Today</div>
            </div>
            
            <!-- Quick Add Event Modal-in-Sidebar -->
            <div class="glass-card" id="quickAddSidebar">
                <h3 class="glass-card-title" style="margin-bottom: 1rem;">Quick Add Milestone</h3>
                <form action="calendar.php" method="POST">
                    <input type="hidden" name="action" value="add_event">
                    
                    <div class="form-group">
                        <label class="form-label">Event Title</label>
                        <input class="form-control" type="text" name="title" placeholder="e.g. Midterm Exams" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="type">
                            <option value="Academic Event">Academic Event</option>
                            <option value="Holiday">Holiday</option>
                            <option value="Exam">Exam / Test</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input class="form-control" type="date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">End Date (Optional)</label>
                        <input class="form-control" type="date" name="end_date">
                    </div>
                    
                    <button class="btn btn-primary" type="submit" style="width: 100%; margin-top: 0.5rem;">Save Milestone</button>
                </form>
            </div>
        </div>
        
    </div>

</div>

<!-- Pop-up Backdrop/Modal Dialog (Fallback overlay layout) -->
<div class="modal-backdrop" id="addEventModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Create Calendar Milestone</h3>
            <button class="modal-close" onclick="document.getElementById('addEventModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="calendar.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_event">
                
                <div class="form-group">
                    <label class="form-label">Event Title</label>
                    <input class="form-control" type="text" name="title" placeholder="e.g. Winter Break, Semester Commencement" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Event Type</label>
                    <select class="form-control" name="type">
                        <option value="Academic Event">Academic Event</option>
                        <option value="Holiday">Holiday</option>
                        <option value="Exam">Exam / Test</option>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input class="form-control" type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input class="form-control" type="date" name="end_date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addEventModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Trigger Toast on completion -->

<script>
    function switchCalMode(mode) {
        const btnDay   = document.getElementById('btnCalDay');
        const btnWeek  = document.getElementById('btnCalWeek');
        const btnMonth = document.getElementById('btnCalMonth');

        [btnDay, btnWeek, btnMonth].forEach(btn => {
            btn.style.background = 'transparent';
            btn.style.color = 'var(--text-secondary)';
            btn.style.boxShadow = 'none';
        });

        const activeBtn = (mode === 'day') ? btnDay : ((mode === 'week') ? btnWeek : btnMonth);
        activeBtn.style.background = 'var(--primary)';
        activeBtn.style.color = '#fff';
        activeBtn.style.boxShadow = '0 2px 8px rgba(37,99,235,0.3)';
        
        if (typeof showToast === 'function') {
            showToast('Calendar view switched to ' + mode.toUpperCase() + ' Mode', 'info');
        }
    }

    window.addEventListener('DOMContentLoaded', (event) => {
        <?= $action_toast ?>
    });
</script>

<?php include 'footer.php'; ?>
