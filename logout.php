<?php
/*
================================================================
EduFlow AI - Academic Planning & Monitoring System
Logout Screen - project/logout.php
================================================================
*/

session_start();
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
