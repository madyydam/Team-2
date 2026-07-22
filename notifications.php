<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
SRS Module 3 - Feature 9: Notifications Center
notifications.php
================================================================
*/

include 'header.php';
$db = &$_SESSION['academic_db'];

// Build dynamic notifications from session state
$notifications = [];

// 1. Faculty reminders for upcoming classes
foreach ($db['class_sessions'] ?? [] as $cs) {
    if ($cs['status'] === 'Not Conducted') {
        $notifications[] = [
            'type'    => 'reminder',
            'icon'    => '⏰',
            'title'   => "Class Reminder — {$cs['subject']}",
            'message' => "Faculty {$cs['faculty']} has a class at {$cs['time']} in {$cs['room']}. Please ensure attendance is marked.",
            'time'    => 'Today',
            'badge'   => 'badge-warning',
            'label'   => 'Reminder',
        ];
    }
}

// 2. Attendance submission reminders
foreach ($db['class_sessions'] ?? [] as $cs) {
    if ($cs['status'] === 'Conducted') {
        $notifications[] = [
            'type'    => 'info',
            'icon'    => '📝',
            'title'   => "Attendance Marked — {$cs['subject']}",
            'message' => "Attendance has been submitted for {$cs['subject']} at {$cs['time']}. HOD dashboard updated.",
            'time'    => 'Today',
            'badge'   => 'badge-success',
            'label'   => 'Completed',
        ];
    }
}

// 3. Missed class alerts
foreach ($db['class_sessions'] ?? [] as $cs) {
    if ($cs['status'] === 'Cancelled') {
        $notifications[] = [
            'type'    => 'alert',
            'icon'    => '❌',
            'title'   => "Class Cancelled — {$cs['subject']}",
            'message' => "{$cs['subject']} at {$cs['time']} was cancelled. Reason: {$cs['reason']}. Added to makeup list.",
            'time'    => 'Today',
            'badge'   => 'badge-danger',
            'label'   => 'Action Needed',
        ];
    }
}

// 4. Lab issue notifications
foreach ($db['labs'] as $lab) {
    if (($lab['status'] ?? '') === 'Under Maintenance') {
        $notifications[] = [
            'type'    => 'alert',
            'icon'    => '🧪',
            'title'   => "Lab Maintenance — {$lab['name']}",
            'message' => "{$lab['name']} is under maintenance. Systems working: {$lab['systems_working']}/{$lab['total_systems']}. Maintenance team notified.",
            'time'    => 'Today',
            'badge'   => 'badge-danger',
            'label'   => 'Maintenance',
        ];
    }
}

// 5. Low attendance warnings for students
$low_att_students = array_filter($db['students'], fn($s) => ($s['attendance_pct']??80) < 75);
foreach (array_slice(array_values($low_att_students), 0, 5) as $ls) {
    $notifications[] = [
        'type'    => 'warning',
        'icon'    => '⚠',
        'title'   => "Low Attendance Alert — {$ls['name']}",
        'message' => "{$ls['name']} ({$ls['roll_no']}) has {$ls['attendance_pct']}% attendance. Below 75% threshold. Mentor and parent notified.",
        'time'    => 'Today',
        'badge'   => 'badge-warning',
        'label'   => 'Warning',
    ];
}

// 6. Maintenance request notifications
foreach ($db['issues'] ?? [] as $iss) {
    if ($iss['status'] === 'Pending') {
        $notifications[] = [
            'type'    => 'maintenance',
            'icon'    => '🔧',
            'title'   => "Maintenance Ticket — {$iss['room']}",
            'message' => "Issue: {$iss['title']}. Reported by {$iss['reported_by']}. Ticket auto-generated. Maintenance team assigned.",
            'time'    => $iss['date'],
            'badge'   => 'badge-warning',
            'label'   => 'Pending',
        ];
    }
}

// 7. System announcements
foreach ($db['announcements'] ?? [] as $ann) {
    $notifications[] = [
        'type'    => 'info',
        'icon'    => '📢',
        'title'   => $ann['title'],
        'message' => "System announcement: {$ann['title']}",
        'time'    => $ann['time'],
        'badge'   => 'badge-info',
        'label'   => 'Announcement',
    ];
}

// Group by type
$type_filter = $_GET['type'] ?? 'all';
$filtered = $type_filter === 'all' ? $notifications : array_filter($notifications, fn($n) => $n['type'] === $type_filter);

$counts = [
    'all'         => count($notifications),
    'reminder'    => count(array_filter($notifications, fn($n)=>$n['type']==='reminder')),
    'alert'       => count(array_filter($notifications, fn($n)=>$n['type']==='alert')),
    'warning'     => count(array_filter($notifications, fn($n)=>$n['type']==='warning')),
    'maintenance' => count(array_filter($notifications, fn($n)=>$n['type']==='maintenance')),
    'info'        => count(array_filter($notifications, fn($n)=>$n['type']==='info')),
];
?>
<style>
.notif-item { display: flex; gap: 1rem; align-items: flex-start; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); transition: background .15s; }
.notif-item:hover { background: rgba(37,99,235,.03); }
.notif-item:last-child { border-bottom: none; }
.notif-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
.notif-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); flex-shrink: 0; margin-top: 6px; }
.notif-read { opacity: .6; }
.filter-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .35rem .85rem; border-radius: 99px; font-size: .75rem; font-weight: 700; border: 1px solid var(--border-color); background: var(--glass-bg); color: var(--text-secondary); cursor: pointer; transition: all .2s; text-decoration: none; }
.filter-pill.active { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<div class="container-fluid">

    <!-- Header -->
    <div class="glass-card-header" style="margin-bottom:2rem;">
        <div>
            <h1 style="font-size:1.75rem; font-weight:700;">🛎 Notifications Center</h1>
            <p style="color:var(--text-secondary); font-size:.9rem; margin-top:.25rem;">
                SRS Module 3 — Feature 9. Automatic alerts for classes, attendance, labs, and maintenance.
            </p>
        </div>
        <div style="display:flex; gap:.6rem; align-items:center;">
            <span class="badge badge-danger" style="font-size:.75rem; padding:.35rem .7rem;"><?= count($notifications) ?> Total</span>
            <button onclick="showToast('All notifications marked as read.', 'success')" class="btn btn-secondary" style="font-size:.8rem; padding:.5rem 1rem;">Mark All Read</button>
        </div>
    </div>

    <!-- Filter Pills -->
    <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
        <a href="notifications.php?type=all" class="filter-pill <?= $type_filter==='all'?'active':'' ?>">All <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;"><?= $counts['all'] ?></span></a>
        <a href="notifications.php?type=alert" class="filter-pill <?= $type_filter==='alert'?'active':'' ?>">❌ Alerts <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;"><?= $counts['alert'] ?></span></a>
        <a href="notifications.php?type=warning" class="filter-pill <?= $type_filter==='warning'?'active':'' ?>">⚠ Warnings <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;"><?= $counts['warning'] ?></span></a>
        <a href="notifications.php?type=reminder" class="filter-pill <?= $type_filter==='reminder'?'active':'' ?>">⏰ Reminders <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;"><?= $counts['reminder'] ?></span></a>
        <a href="notifications.php?type=maintenance" class="filter-pill <?= $type_filter==='maintenance'?'active':'' ?>">🔧 Maintenance <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;"><?= $counts['maintenance'] ?></span></a>
        <a href="notifications.php?type=info" class="filter-pill <?= $type_filter==='info'?'active':'' ?>">📢 Announcements <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;"><?= $counts['info'] ?></span></a>
    </div>

    <!-- Notifications List -->
    <div class="glass-card">
        <?php if (empty($filtered)): ?>
        <div style="text-align:center; padding:3rem 0; color:var(--text-muted); font-size:.9rem;">
            ✅ No notifications in this category.
        </div>
        <?php else: ?>
        <?php
        $type_colors = [
            'alert'       => ['bg'=>'rgba(239,68,68,.12)',   'dot'=>'#ef4444'],
            'warning'     => ['bg'=>'rgba(245,158,11,.12)',  'dot'=>'#f59e0b'],
            'reminder'    => ['bg'=>'rgba(59,130,246,.12)',  'dot'=>'#3b82f6'],
            'maintenance' => ['bg'=>'rgba(139,92,246,.12)',  'dot'=>'#8b5cf6'],
            'info'        => ['bg'=>'rgba(16,185,129,.12)',  'dot'=>'#10b981'],
        ];
        foreach (array_values($filtered) as $i => $n):
            $colors = $type_colors[$n['type']] ?? ['bg'=>'rgba(100,116,139,.12)','dot'=>'#64748b'];
        ?>
        <div class="notif-item">
            <div class="notif-icon" style="background:<?= $colors['bg'] ?>;"><?= $n['icon'] ?></div>
            <div style="flex:1; min-width:0;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem; flex-wrap:wrap;">
                    <div style="font-weight:700; font-size:.87rem; color:var(--text-primary);"><?= htmlspecialchars($n['title']) ?></div>
                    <div style="display:flex; align-items:center; gap:.5rem; flex-shrink:0;">
                        <span class="badge <?= $n['badge'] ?>" style="font-size:.62rem;"><?= $n['label'] ?></span>
                        <span style="font-size:.72rem; color:var(--text-muted); white-space:nowrap;"><?= $n['time'] ?></span>
                    </div>
                </div>
                <div style="font-size:.8rem; color:var(--text-secondary); margin-top:.3rem; line-height:1.5;"><?= htmlspecialchars($n['message']) ?></div>
                <div style="margin-top:.5rem; display:flex; gap:.4rem;">
                    <button onclick="this.parentElement.parentElement.parentElement.classList.add('notif-read'); showToast('Notification dismissed.','info')" class="btn btn-secondary" style="font-size:.68rem; padding:.2rem .6rem;">Dismiss</button>
                    <?php if ($n['type']==='warning' || $n['type']==='alert'): ?>
                    <button onclick="showToast('Action taken for this notification.','success')" class="btn btn-primary" style="font-size:.68rem; padding:.2rem .6rem;">Take Action</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="notif-dot" style="background:<?= $colors['dot'] ?>;"></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>
