<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Landing Page - project/index.php
================================================================
*/

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$theme_mode = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team 2 - Academic Planning & Monitoring System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
        /* Landing-specific styles for a sleek SaaS landing page */
        .landing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
            padding: 0 4rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        .landing-hero {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
            text-align: center;
            position: relative;
            z-index: 5;
        }
        .landing-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--text-primary) 30%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        body.dark-mode .landing-title {
            background: linear-gradient(135deg, #FFFFFF 40%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .landing-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto 2.5rem;
            line-height: 1.6;
        }
        .landing-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            max-width: 1200px;
            margin: 2rem auto 6rem;
            padding: 0 2rem;
        }
        @media (max-width: 992px) {
            .landing-header { padding: 0 2rem; }
            .landing-title { font-size: 2.5rem; }
            .landing-features { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .landing-header { padding: 0 1.5rem; }
            .landing-title { font-size: 2rem; }
            .landing-features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="<?= $theme_mode === 'dark' ? 'dark-mode' : '' ?>">

    <!-- Interactive background blobs -->
    <div class="login-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2" style="top: 60%; left: 70%;"></div>
    </div>

    <!-- Navigation Header -->
    <header class="landing-header">
        <a href="index.php" class="sidebar-logo">
            <svg viewBox="0 0 24 24" style="width: 32px; height: 32px; fill: var(--primary);">
                <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9 12 3zm6.82 8.18L12 14.91 5.18 11.18 12 7.45l6.82 3.73zM12 18.72l-5-2.73v-3.72l5 2.73 5-2.73v3.72l-5 2.73z"/>
            </svg>
            <span>Team <span>2</span></span>
        </a>
        
        <div>
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Sign In to Portal</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="landing-hero">
        <h1 class="landing-title">Academic Planning &<br>Monitoring, Reimagined.</h1>
        <p class="landing-subtitle">
            Team 2 is a premium institutional resource planning portal built for modern colleges. Empower your departments with unified tools for automated conflict-free scheduling, live class metrics tracking, and equipment maintenance registers.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 4rem;">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-primary" style="padding: 0.85rem 2rem; font-size: 1rem;">Enter Console</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary" style="padding: 0.85rem 2rem; font-size: 1rem;">Access System</a>
            <?php endif; ?>
            <a href="#features" class="btn btn-secondary" style="padding: 0.85rem 2rem; font-size: 1rem;">Learn More</a>
        </div>
    </main>

    <!-- Features Section -->
    <section class="landing-features" id="features">
        <!-- Feature 1 -->
        <div class="glass-card">
            <div class="stat-icon-wrapper stat-blue" style="margin-bottom: 1.25rem;">
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
            </div>
            <h3 style="margin-bottom: 0.5rem; font-weight: 600;">Semester Setup & Calendar</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">
                Design semester dates, allocate holidays, and plan major institutional milestones on a clean chronological event manager.
            </p>
        </div>

        <!-- Feature 2 -->
        <div class="glass-card">
            <div class="stat-icon-wrapper stat-purple" style="margin-bottom: 1.25rem;">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            <h3 style="margin-bottom: 0.5rem; font-weight: 600;">Dynamic Workload Allocation</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">
                Assign subjects, adjust teaching workloads, and balance department resources dynamically with built-in allocation lists.
            </p>
        </div>

        <!-- Feature 3 -->
        <div class="glass-card">
            <div class="stat-icon-wrapper stat-green" style="margin-bottom: 1.25rem;">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H5v-2h4V7h2v4h4v2z"/></svg>
            </div>
            <h3 style="margin-bottom: 0.5rem; font-weight: 600;">Automated Lab Registry</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">
                Track hardware functionality, check network status, and record issues to generate immediate alerts for facility technicians.
            </p>
        </div>
    </section>

    <!-- Simple Landing Footer -->
    <footer class="app-footer" style="margin-top: auto; background: none; border: none; max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <div>
            &copy; 2026 <strong>Team 2</strong>. Next-Gen Academic Intelligence.
        </div>
        <div class="footer-links">
            <a href="#">Support Portal</a>
            <a href="#">Documentation</a>
            <a href="login.php">Console Login</a>
        </div>
    </footer>

</body>
</html>
