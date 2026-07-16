<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Redesigned Landing Page - project/index.php
================================================================
*/

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check database count values for stats
$db = $_SESSION['academic_db'] ?? null;
$faculty_count = $db ? count($db['faculty']) : 14;
$student_count = $db ? count($db['students']) : 73;
$subject_count = $db ? count($db['subjects']) : 5;
$lab_count = $db ? count($db['labs']) : 4;
$issue_count = $db ? count(array_filter($db['issues'], function($i) { return $i['status'] === 'Pending' || $i['status'] === 'In Progress'; })) : 2;

$is_logged_in = isset($_SESSION['user_id']);
$theme_mode = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Planning & Monitoring System | Zeal IT Department</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }
        
        /* Interactive background blobs */
        .neon-blobs {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .neon-blob {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(140px);
            opacity: 0.15;
            animation: pulse-blob 12s infinite alternate;
        }
        .neon-blob-1 {
            background: var(--primary);
            top: -100px;
            left: -100px;
        }
        .neon-blob-2 {
            background: var(--accent);
            top: 30%;
            right: -200px;
            animation-delay: -4s;
        }
        .neon-blob-3 {
            background: var(--warning);
            bottom: -100px;
            left: 20%;
            animation-delay: -8s;
        }
        body.dark-mode .neon-blob {
            opacity: 0.22;
        }
        @keyframes pulse-blob {
            0% { transform: scale(1) translate(0, 0); }
            50% { transform: scale(1.15) translate(5%, 8%); }
            100% { transform: scale(0.9) translate(-3%, -5%); }
        }

        /* Glass Header navigation */
        .nav-glass {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(var(--bg-secondary-rgb), 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1300px;
            margin: 0 auto;
            padding: 1.25rem 2rem;
        }
        
        /* Hero Section Redesign */
        .hero-section {
            position: relative;
            padding: 8rem 2rem 4rem;
            max-width: 1300px;
            margin: 0 auto;
            z-index: 10;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 4rem;
            align-items: center;
        }
        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }
            .hero-actions {
                justify-content: center;
            }
        }
        
        .badge-pill-department {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border: 1px solid rgba(37, 99, 235, 0.2);
            padding: 0.35rem 0.9rem;
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        body.dark-mode .badge-pill-department {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
            color: var(--primary);
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.04em;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--text-primary) 30%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        @media (max-width: 768px) {
            .hero-title { font-size: 2.8rem; }
        }

        .hero-description {
            font-size: 1.15rem;
            line-height: 1.7;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            max-width: 650px;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-glow {
            box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(var(--primary-rgb), 0.4);
        }

        /* Stats dashboard card widget */
        .stat-card-widget {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.25rem;
            box-shadow: var(--shadow-card);
            position: relative;
        }
        .stat-card-widget::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05), transparent);
            pointer-events: none;
        }
        .stat-widget-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .stat-widget-item {
            background: rgba(var(--primary-rgb), 0.02);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            transition: all 0.2s ease;
        }
        .stat-widget-item:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            background: rgba(var(--primary-rgb), 0.05);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.25rem;
            font-family: 'Outfit', sans-serif;
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* Feature section style improvements */
        .section-title-wrapper {
            text-align: center;
            max-width: 700px;
            margin: 4rem auto 3rem;
            padding: 0 1rem;
        }
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .features-grid-custom {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            max-width: 1300px;
            margin: 0 auto 6rem;
            padding: 0 2rem;
        }
        @media (max-width: 992px) {
            .features-grid-custom { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .features-grid-custom { grid-template-columns: 1fr; }
        }

        .premium-feature-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-soft);
        }
        .premium-feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: var(--shadow-hover);
        }
    </style>
</head>
<body class="<?= $theme_mode === 'dark' ? 'dark-mode' : '' ?>">

    <!-- Neon Animated Background Blobs -->
    <div class="neon-blobs">
        <div class="neon-blob neon-blob-1"></div>
        <div class="neon-blob neon-blob-2"></div>
        <div class="neon-blob neon-blob-3"></div>
    </div>

    <!-- Navigation Header -->
    <header class="nav-glass">
        <div class="nav-container">
            <a href="index.php" class="sidebar-logo" style="font-size: 1.4rem;">
                <svg viewBox="0 0 24 24" style="width: 34px; height: 34px; fill: var(--primary);">
                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9 12 3zm6.82 8.18L12 14.91 5.18 11.18 12 7.45l6.82 3.73zM12 18.72l-5-2.73v-3.72l5 2.73 5-2.73v3.72l-5 2.73z"/>
                </svg>
                <span>Zeal <span>IT Portal</span></span>
            </a>
            
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-primary btn-glow">Console Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-glow">Portal Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Content -->
    <main class="hero-section">
        <div class="hero-grid">
            <div>
                <span class="badge-pill-department">🎓 Zeal College of Engineering</span>
                <h1 class="hero-title">Academic planning,<br>re-invented for IT.</h1>
                <p class="hero-description">
                    A gorgeous, dynamic web workspace for Information Technology departments. Experience seamless conflict-free timetables, track lab health metrics, and manage detailed faculty records instantly.
                </p>
                <div class="hero-actions">
                    <?php if ($is_logged_in): ?>
                        <a href="dashboard.php" class="btn btn-primary btn-glow" style="padding: 1rem 2.5rem; font-size: 1rem; border-radius: 12px;">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-glow" style="padding: 1rem 2.5rem; font-size: 1rem; border-radius: 12px;">Access System Console</a>
                    <?php endif; ?>
                    <a href="#features" class="btn btn-secondary" style="padding: 1rem 2.5rem; font-size: 1rem; border-radius: 12px; background: rgba(var(--primary-rgb), 0.05);">Learn Features</a>
                </div>
            </div>

            <!-- Stats dashboard card widget -->
            <div>
                <div class="stat-card-widget">
                    <h3 style="margin-bottom: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                        <span style="display:inline-block; width:10px; height:10px; background:var(--accent); border-radius:50%;"></span>
                        Live Department Register
                    </h3>
                    <div class="stat-widget-grid">
                        <div class="stat-widget-item">
                            <div class="stat-number"><?= $faculty_count ?></div>
                            <div class="stat-label">Allocated Faculty</div>
                        </div>
                        <div class="stat-widget-item">
                            <div class="stat-number"><?= $student_count ?></div>
                            <div class="stat-label">Active IT Students</div>
                        </div>
                        <div class="stat-widget-item">
                            <div class="stat-number"><?= $lab_count ?></div>
                            <div class="stat-label">Active Hardware Labs</div>
                        </div>
                        <div class="stat-widget-item" style="border-color: <?= $issue_count > 0 ? 'rgba(var(--warning-rgb), 0.3)' : 'var(--border-color)' ?>;">
                            <div class="stat-number" style="color: <?= $issue_count > 0 ? 'var(--warning)' : 'var(--accent)' ?>;"><?= $issue_count ?></div>
                            <div class="stat-label">Active Lab Issues</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Features Heading -->
    <div class="section-title-wrapper" id="features">
        <h2 class="section-title">Engineered for Academic Success</h2>
        <p style="color: var(--text-secondary); font-size: 1.05rem;">Manage core schedules, workload lists, and infrastructure logs inside a unified workspace.</p>
    </div>

    <!-- Feature Grid Section -->
    <section class="features-grid-custom">
        <!-- Feature 1 -->
        <div class="premium-feature-card">
            <div class="stat-icon-wrapper stat-blue" style="margin-bottom: 1.5rem; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" style="width: 24px; height: 24px;"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
            </div>
            <h3 style="margin-bottom: 0.75rem; font-weight: 700; font-size: 1.2rem;">Schedules & Calendar</h3>
            <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.6;">
                Design semester dates, allocate national holidays, and plan major institutional milestones on a clean chronological event manager.
            </p>
        </div>

        <!-- Feature 2 -->
        <div class="premium-feature-card">
            <div class="stat-icon-wrapper stat-purple" style="margin-bottom: 1.5rem; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" style="width: 24px; height: 24px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            <h3 style="margin-bottom: 0.75rem; font-weight: 700; font-size: 1.2rem;">Workload Allocation</h3>
            <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.6;">
                Balance teaching responsibilities dynamically, view credentials, and trace core competence with rich skill badges and square avatars.
            </p>
        </div>

        <!-- Feature 3 -->
        <div class="premium-feature-card">
            <div class="stat-icon-wrapper stat-green" style="margin-bottom: 1.5rem; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" style="width: 24px; height: 24px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H5v-2h4V7h2v4h4v2z"/></svg>
            </div>
            <h3 style="margin-bottom: 0.75rem; font-weight: 700; font-size: 1.2rem;">Infrastructure Health</h3>
            <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.6;">
                Report lab projector faults, log hardware errors, check internet speeds, and track real-time issue tickets through the administrator dashboard.
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="app-footer" style="margin-top: auto; background: none; border-top: 1px solid var(--border-color); max-width: 1300px; margin: 0 auto; padding: 2.5rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.88rem; color: var(--text-secondary);">
            &copy; 2026 <strong>Zeal College of Engineering & Research</strong>. Department of IT Portal.
        </div>
        <div class="footer-links" style="display: flex; gap: 1.5rem; font-size: 0.88rem;">
            <a href="login.php" style="font-weight: 600; color: var(--primary);">Console Login</a>
            <a href="dashboard.php" style="color: var(--text-secondary);">Dashboard</a>
            <a href="students.php" style="color: var(--text-secondary);">Students Directory</a>
        </div>
    </footer>

</body>
</html>
