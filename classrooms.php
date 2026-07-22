<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Classroom Monitoring & Readiness - project/classrooms.php
================================================================
*/

include 'header.php';

$db = &$_SESSION['academic_db'];
$action_toast = '';

// Ensure classrooms array exists in the database session (prevent undefined session cache issues)
if (!isset($db['classrooms'])) {
    $db['classrooms'] = [
        ['id' => 1, 'room' => 'Room 301', 'projector' => 'Working', 'internet' => 'Working', 'whiteboard' => 'Available', 'capacity' => 60, 'status' => 'Available'],
        ['id' => 2, 'room' => 'Room 302', 'projector' => 'Working', 'internet' => 'Working', 'whiteboard' => 'Available', 'capacity' => 60, 'status' => 'Available'],
        ['id' => 3, 'room' => 'Room 303', 'projector' => 'Under Maintenance', 'internet' => 'Working', 'whiteboard' => 'Available', 'capacity' => 60, 'status' => 'Maintenance Requested'],
        ['id' => 4, 'room' => 'Room 304', 'projector' => 'Working', 'internet' => 'Not Working', 'whiteboard' => 'Available', 'capacity' => 60, 'status' => 'Maintenance Requested'],
    ];
}

// Handle Classroom readiness reporting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Log Classroom Maintenance Ticket
    if ($action === 'report_room_issue') {
        $room_number = $_POST['room_number'] ?? '';
        $issue_title = trim($_POST['title'] ?? '');
        $type        = $_POST['type'] ?? 'Projector';

        if (empty($issue_title) || empty($room_number)) {
            $action_toast = "showToast('Failed to log complaint: Room number and issue details are required.', 'danger');";
        } else {
            // Add issue to global issues list
            $new_id = count($db['issues']) > 0 ? max(array_column($db['issues'], 'id')) + 1 : 1;
            $db['issues'][] = [
                'id'          => $new_id,
                'title'       => $issue_title,
                'room'        => $room_number,
                'type'        => $type,
                'status'      => 'Pending',
                'reported_by' => $_SESSION['user_name'] ?? 'Faculty Member',
                'date'        => date('Y-m-d')
            ];

            // Change status of the room to Maintenance Requested
            foreach ($db['classrooms'] as &$cr) {
                if ($cr['room'] === $room_number) {
                    $cr['status'] = 'Maintenance Requested';
                    if ($type === 'Projector') $cr['projector'] = 'Under Maintenance';
                    if ($type === 'Internet')  $cr['internet']  = 'Not Working';
                    if ($type === 'Whiteboard') $cr['whiteboard'] = 'Not Available';
                    break;
                }
            }
            unset($cr);

            $action_toast = "showToast('Maintenance Ticket #{$new_id} generated. Room status updated to Maintenance Requested.', 'warning');";
        }
    }

    // 2. Clear Issue / Fix Readiness Status
    if ($action === 'resolve_room') {
        $room_number = $_POST['room_number'] ?? '';
        foreach ($db['classrooms'] as &$cr) {
            if ($cr['room'] === $room_number) {
                $cr['status'] = 'Available';
                $cr['projector'] = 'Working';
                $cr['internet']  = 'Working';
                $cr['whiteboard'] = 'Available';
                break;
            }
        }
        unset($cr);

        // Resolve associated pending issues for this room
        foreach ($db['issues'] as &$issue) {
            if ($issue['room'] === $room_number && $issue['status'] === 'Pending') {
                $issue['status'] = 'Resolved';
            }
        }
        unset($issue);

        $action_toast = "showToast('Classroom status restored to Available successfully.', 'success');";
    }
}
?>

<style>
.room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.room-readiness-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.65rem 0;
    border-bottom: 1px solid var(--border-color);
}
.room-readiness-row:last-child {
    border-bottom: none;
}
.readiness-check {
    font-weight: 700;
    font-size: 0.85rem;
}
.check-yes {
    color: #10b981;
}
.check-no {
    color: #ef4444;
}
.room-card-footer {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 0.5rem;
}
</style>

<div class="container-fluid">
    
    <!-- Header Section -->
    <div class="glass-card-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <span>🏫 Classroom Status & Monitoring</span>
            </h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Daily classroom readiness audit, projector checks, internet operational status, and automated maintenance tickets.</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('reportIssueModal').classList.add('active')">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;margin-right: 4px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            Report Room Issue
        </button>
    </div>

    <!-- Classroom Status Grid -->
    <div class="room-grid">
        <?php foreach ($db['classrooms'] as $room): ?>
            <?php 
            $status = $room['status'] ?? 'Available';
            $is_available = ($status === 'Available');
            $border_color = $is_available ? '#10b981' : '#f59e0b';
            $badge_class = $is_available ? 'badge-success' : 'badge-warning';
            ?>
            <div class="glass-card" style="padding: 1.5rem; border-top: 4px solid <?= $border_color ?>; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <!-- Header -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($room['room']) ?></h3>
                            <span style="font-size: 0.72rem; color: var(--text-muted);">Classroom Capacity: <strong><?= $room['capacity'] ?? 60 ?></strong></span>
                        </div>
                        <span class="badge <?= $badge_class ?>" style="font-size: 0.7rem; padding: 0.25rem 0.6rem;">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </div>

                    <!-- Readiness items -->
                    <div style="background: rgba(15,23,42,0.02); border-radius: var(--radius-md); padding: 0.25rem 1rem; border: 1px solid var(--border-color);">
                        <div class="room-readiness-row">
                            <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;">Projector Status</span>
                            <span class="readiness-check <?= $room['projector'] === 'Working' ? 'check-yes' : 'check-no' ?>">
                                <?= $room['projector'] === 'Working' ? '✔ Working' : '❌ ' . htmlspecialchars($room['projector']) ?>
                            </span>
                        </div>
                        <div class="room-readiness-row">
                            <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;">Internet / Network</span>
                            <span class="readiness-check <?= $room['internet'] === 'Working' ? 'check-yes' : 'check-no' ?>">
                                <?= $room['internet'] === 'Working' ? '✔ Working' : '❌ ' . htmlspecialchars($room['internet']) ?>
                            </span>
                        </div>
                        <div class="room-readiness-row">
                            <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;">Whiteboard readiness</span>
                            <span class="readiness-check <?= $room['whiteboard'] === 'Available' ? 'check-yes' : 'check-no' ?>">
                                <?= $room['whiteboard'] === 'Available' ? '✔ Available' : '❌ ' . htmlspecialchars($room['whiteboard']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer buttons -->
                <div class="room-card-footer">
                    <?php if (!$is_available): ?>
                        <form action="classrooms.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="action" value="resolve_room">
                            <input type="hidden" name="room_number" value="<?= htmlspecialchars($room['room']) ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 0.78rem; padding: 0.5rem 0.75rem;">
                                Restore readiness
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" onclick="openReportModal('<?= htmlspecialchars($room['room']) ?>')" style="width: 100%; font-size: 0.78rem; padding: 0.5rem 0.75rem;">
                            Report Issue
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Active classroom tickets log -->
    <div class="glass-card" style="margin-top: 2rem;">
        <div class="glass-card-header" style="margin-bottom: 1.25rem;">
            <div>
                <h3 class="glass-card-title">Classroom Maintenance Ticket Registry</h3>
                <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.15rem;">Live complaints tracking and audit history logs.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Classroom</th>
                        <th>Category</th>
                        <th>Description / Problem</th>
                        <th>Reported By</th>
                        <th>Date Logging</th>
                        <th>Ticket Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $room_issues = array_filter($db['issues'], function($issue) {
                        return strpos($issue['room'], 'Room') !== false;
                    });
                    ?>
                    <?php if (empty($room_issues)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem 0;">No active classroom issues logged. All systems working!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_reverse($room_issues) as $issue): ?>
                            <tr>
                                <td><code>#TKT-00<?= $issue['id'] ?></code></td>
                                <td><strong><?= htmlspecialchars($issue['room']) ?></strong></td>
                                <td><span class="badge badge-info" style="font-size: 0.65rem;"><?= htmlspecialchars($issue['type']) ?></span></td>
                                <td><?= htmlspecialchars($issue['title']) ?></td>
                                <td><?= htmlspecialchars($issue['reported_by']) ?></td>
                                <td><?= htmlspecialchars($issue['date']) ?></td>
                                <td>
                                    <?php if ($issue['status'] === 'Pending'): ?>
                                        <span class="badge badge-danger">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Resolved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Dialog: Report Issue -->
<div class="modal-backdrop" id="reportIssueModal" onclick="this.classList.remove('active')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Log Classroom Complaint</h3>
            <button class="modal-close" onclick="document.getElementById('reportIssueModal').classList.remove('active')">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form action="classrooms.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="report_room_issue">
                
                <div class="form-group">
                    <label class="form-label">Select Classroom</label>
                    <select class="form-control" name="room_number" id="modal_room_number">
                        <?php foreach ($db['classrooms'] as $cr): ?>
                            <option value="<?= htmlspecialchars($cr['room']) ?>"><?= htmlspecialchars($cr['room']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Issue Category</label>
                    <select class="form-control" name="type">
                        <option value="Projector">Projector Status</option>
                        <option value="Internet">Internet Connection</option>
                        <option value="Whiteboard">Whiteboard readiness</option>
                        <option value="Furniture">Furniture Maintenance</option>
                        <option value="Other">Other / Electricity</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Complaint details / Description</label>
                    <textarea class="form-control" name="title" rows="3" placeholder="Explain the details of the problem..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('reportIssueModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReportModal(room) {
    document.getElementById('modal_room_number').value = room;
    document.getElementById('reportIssueModal').classList.add('active');
}

window.addEventListener('DOMContentLoaded', (event) => {
    <?= $action_toast ?>
});
</script>

<?php include 'footer.php'; ?>
