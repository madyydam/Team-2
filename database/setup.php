<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Database Seed Script - database/setup.php
Run once via browser: http://localhost/Team 2/database/setup.php
================================================================
*/

// DB connection (direct, not through includes since this is a one-time setup)
$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'academic_monitoring_db';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("<h2 style='color:red'>DB Connection Failed: " . $conn->connect_error . "</h2>");
}
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($dbname);
$conn->set_charset('utf8mb4');

// Load and execute schema.sql if tables do not exist
$schema_path = __DIR__ . '/schema.sql';
if (file_exists($schema_path)) {
    $schema_content = file_get_contents($schema_path);
    // Remove SQL comments
    $schema_content = preg_replace('/--.*$/m', '', $schema_content);
    // Split into queries
    $queries = explode(';', $schema_content);
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query !== '') {
            $conn->query($query);
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}

$log = [];
$errors = [];

function run_sql($conn, $sql, $label, &$log, &$errors) {
    if ($conn->query($sql)) {
        $log[] = "✅ $label";
    } else {
        $errors[] = "❌ $label — " . $conn->error;
    }
}

// ============================================================
// CLEAR EXISTING DATA (in reverse FK order)
// ============================================================
$clear_tables = [
    'academic_event', 'audit_log', 'notification', 'daily_report',
    'classroom_issue', 'lab_session', 'lab_schedule',
    'faculty_attendance', 'attendance', 'daily_class_log',
    'timetable', 'subject', 'students', 'faculty',
    'section', 'semester', 'classroom', 'laboratory',
    'users', 'department', 'roles'
];

// Temporarily disable FK checks for clean truncation
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
foreach ($clear_tables as $tbl) {
    $conn->query("TRUNCATE TABLE $tbl");
    $log[] = "🗑 Cleared: $tbl";
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// ============================================================
// 1. ROLES
// ============================================================
$roles_sql = "INSERT INTO roles (name, description) VALUES
('Admin',   'System administrator with full access'),
('HOD',     'Head of Department - monitors academic activities'),
('Faculty', 'Faculty member - conducts classes and marks attendance'),
('Student', 'Student - views timetable and attendance')";
run_sql($conn, $roles_sql, "Roles seeded", $log, $errors);

// ============================================================
// 2. DEPARTMENTS
// ============================================================
$dept_sql = "INSERT INTO department (name) VALUES
('Computer Science'),
('Information Technology'),
('Electronics & Communication'),
('Mechanical Engineering')";
run_sql($conn, $dept_sql, "Departments seeded", $log, $errors);

// ============================================================
// 3. USERS & 4. FACULTY (linked together)
// ============================================================
$faculty_data = [
    // [email, name, plaintext_password, role_id, dept_id, emp_code, qual, designation, workload, status, experience, expertise, photo]
    ['admin@team2.edu', 'Prof. Balaji A. Chaugule', 'admin123', 1, 2, 'EMP001', 'M.E. (Computer Engineering)', 'Head of Information Technology', 12, 'Active', 'Teaching - 12 Years', 'Cloud Computing, Mobile Security, Parallel Computing, Artificial Intelligence', 'img/balaji_chaugule.jpg'],
    ['hod@team2.edu', 'Prof. Balaji A. Chaugule', 'hod123', 2, 2, 'EMP001', 'M.E. (Computer Engineering)', 'Head of Information Technology', 12, 'Active', 'Teaching - 12 Years', 'Cloud Computing, Mobile Security, Parallel Computing, Artificial Intelligence', 'img/balaji_chaugule.jpg'],
    ['neeti.rathore@zealeducation.com', 'Dr. Neeti Rathore', 'faculty123', 3, 2, 'EMP003', 'Ph.D.', 'Assistant Professor', 16, 'Active', 'Teaching - 20 Years', 'Organic and Drug Chemistry, Education, Environmental Sciences', 'img/neeti_rathore.jpg'],
    ['hemant.suryavanshi@zealeducation.com', 'Dr. Hemant R. Suryavanshi', 'faculty123', 3, 2, 'EMP005', 'Ph.D.', 'Assistant Professor', 14, 'Active', 'Teaching - 4 Years, Industrial - 18 Years', 'Research', 'img/hemant_suryavanshi.jpg'],
    ['ashwini.agarwal@zealeducation.com', 'Prof. Ashwini M. Agarwal', 'faculty123', 3, 2, 'EMP006', 'M.E. / M.Tech', 'Assistant Professor', 16, 'Active', 'Industrial - 3 Years', 'Data Analyst, Data Science', 'img/ashwini_agarwal.jpg'],
    ['rohini.dhere@zealeducation.com', 'Prof. Rohini R. Dhere', 'faculty123', 3, 2, 'EMP007', 'M.Sc.', 'Assistant Professor', 12, 'Active', 'Industrial - 2.3 Years', 'Mathematics', 'img/rohini_dhere.jpg'],
    ['komal.rathod@zealeducation.com', 'Prof. Komal P. Rathod', 'faculty123', 3, 2, 'EMP008', 'M.E. / M.Tech', 'Assistant Professor', 14, 'Active', '-', 'Computer Engineering', 'img/komal_rathod.jpg'],
    ['vishal.bafana@zealeducation.com', 'Prof. Vishal H. Bafana', 'faculty123', 3, 2, 'EMP009', 'B.E. / B.Tech', 'Teaching Assistant', 16, 'Active', 'Teaching - 8 Years', 'Advance Web & Mobile Development, Influential Communication', 'img/vishal_bafana.jpg'],
    ['karanumakant.jadhav@zealeducation.com', 'Prof. Karan U. Jadhav', 'faculty123', 3, 2, 'EMP010', 'M.Sc.', 'Teaching Assistant', 12, 'Active', 'Teaching - 0.3 Years, Industrial - 0.6 Years', 'Industrial Mathematics with Computer Application', ''],
    ['sumesh.shinde@zealeducation.com', 'Prof. Sumesh S. Shinde', 'faculty123', 3, 2, 'EMP011', 'B.E. / B.Tech', 'Teaching Assistant', 18, 'Active', 'Teaching - 0.3 Years, Industrial - 0.6 Years', 'Full Stack Developer, Java, Html, Css, Js', 'img/sumesh_shinde.jpg'],
    ['shubhangi.wadibhasme@zealeducation.com', 'Prof. Shubhangi Wadibhasme', 'faculty123', 3, 2, 'EMP012', 'B.E. / B.Tech', 'Teaching Assistant', 14, 'Active', '-', 'Java Developer', ''],
    ['shruti.ghotane@zealeducation.com', 'Prof. Shruti S. Ghotane', 'faculty123', 3, 2, 'EMP013', 'B.E. / B.Tech', 'Teaching Assistant', 16, 'Active', '-', 'Full Stack Fullstack', ''],
    ['prachi.gole@zealeducation.com', 'Prof. Prachi D. Gole', 'faculty123', 3, 2, 'EMP014', 'B.E. / B.Tech', 'Teaching Assistant', 14, 'Active', '-', 'Computer Engineering', ''],
    ['tejas.jagtap@zealeducation.com', 'Mr. Tejas U. Jagtap', 'faculty123', 3, 2, 'EMP015', 'Diploma / BCA', 'Lab Assistant', 8, 'Active', '1 Year', 'Technical Support and Desktop Support Engineer and Software and Automation Tester', '']
];

$stmt_user = $conn->prepare("INSERT INTO users (name, email, password, role_id, department_id, status) VALUES (?,?,?,?,?,'Active')");
$stmt_fac = $conn->prepare("INSERT INTO faculty (user_id, department_id, name, email, employee_code, qualification, designation, experience, expertise, photo, workload_hrs, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");

foreach ($faculty_data as $fd) {
    $hashed = password_hash($fd[2], PASSWORD_BCRYPT);
    $stmt_user->bind_param('sssii', $fd[1], $fd[0], $hashed, $fd[3], $fd[4]);
    $stmt_user->execute();
    $user_id = $conn->insert_id;
    
    $stmt_fac->bind_param('iissssssssis', $user_id, $fd[4], $fd[1], $fd[0], $fd[5], $fd[6], $fd[7], $fd[10], $fd[11], $fd[12], $fd[8], $fd[9]);
    $stmt_fac->execute();
}
$stmt_user->close();
$stmt_fac->close();
$log[] = "✅ Users & Faculty seeded successfully (16 records)";

// ============================================================
// 5. SEMESTER
// ============================================================
$sem_sql = "INSERT INTO semester (name, academic_year, start_date, end_date, status) VALUES
('Fall Semester', '2026-27', '2026-08-03', '2026-12-20', 'Active')";
run_sql($conn, $sem_sql, "Semester seeded", $log, $errors);

// ============================================================
// 6. SECTIONS
// ============================================================
$sec_sql = "INSERT INTO section (name, department_id, year) VALUES
('CS-A', 1, 3),
('IT-A', 2, 3),
('EC-A', 3, 3),
('ME-A', 4, 3)";
run_sql($conn, $sec_sql, "Sections seeded", $log, $errors);

// ============================================================
// 7. STUDENTS (linked to user accounts)
// ============================================================
$student_list = [
    ['roll_no' => 'IT1101', 'name' => 'ADE ISHWARI SHAMRAO', 'email' => 'it1101.ishwari@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 82],
    ['roll_no' => 'IT1102', 'name' => 'BADAK ROHIT ASHOK', 'email' => 'it1102.rohit@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 78],
    ['roll_no' => 'IT1103', 'name' => 'BANKAR PRERANA SANDIP', 'email' => 'it1103.prerana@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 91],
    ['roll_no' => 'IT1104', 'name' => 'BARGAJE GAYATRI SANTOSH', 'email' => 'it1104.gayatri@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 64],
    ['roll_no' => 'IT1105', 'name' => 'BHAVSAR SOHAM ABHAY', 'email' => 'it1105.soham@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 88],
    ['roll_no' => 'IT1106', 'name' => 'BORKAR AYUSH RAMBHAU', 'email' => 'it1106.ayush@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 73],
    ['roll_no' => 'IT1107', 'name' => 'CHAITANYA JYOTIRAM BHOSALE', 'email' => 'it1107.chaitanya@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 95],
    ['roll_no' => 'IT1108', 'name' => 'CHOLE PRANAV SHARAD', 'email' => 'it1108.pranav@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 80],
    ['roll_no' => 'IT1109', 'name' => 'DALVE PRUTHVIRAJ AMAR', 'email' => 'it1109.pruthviraj@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 83],
    ['roll_no' => 'IT1110', 'name' => 'DAVHALE SUMIT KISHOR', 'email' => 'it1110.sumit@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 69],
    ['roll_no' => 'IT1111', 'name' => 'DHADVE MADHUR SANJAY', 'email' => 'it1111.madhur@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 90],
    ['roll_no' => 'IT1112', 'name' => 'DIVY ANIL KOKATE', 'email' => 'it1112.divy@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 75],
    ['roll_no' => 'IT1113', 'name' => 'DIXIT SHIVAMSINGH NARENDRASINGH', 'email' => 'it1113.shivamsingh@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 87],
    ['roll_no' => 'IT1114', 'name' => 'DOLASE PRANAV PRAVIN', 'email' => 'it1114.pranav@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 81],
    ['roll_no' => 'IT1115', 'name' => 'GADAKH RISHI ASHOK', 'email' => 'it1115.rishi@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 76],
    ['roll_no' => 'IT1116', 'name' => 'GARJE SUJIT ARUN', 'email' => 'it1116.sujit@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 62],
    ['roll_no' => 'IT1117', 'name' => 'GAURAV RAJENDRA NANVARE', 'email' => 'it1117.gaurav@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 84],
    ['roll_no' => 'IT1118', 'name' => 'GHARAT SWARA SANTOSH', 'email' => 'it1118.swara@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 92],
    ['roll_no' => 'IT1119', 'name' => 'GHULE ARJUN GAJANAN', 'email' => 'it1119.arjun@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 89],
    ['roll_no' => 'IT1120', 'name' => 'GODAMBE SWARAJ VISHNU', 'email' => 'it1120.swaraj@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 70],
    ['roll_no' => 'IT1121', 'name' => 'GOUR YUVRAJSINHA RAVINDRA', 'email' => 'it1121.yuvrajsinha@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 86],
    ['roll_no' => 'IT1122', 'name' => 'INGLE PRUTHVIRAJ PRABHU', 'email' => 'it1122.pruthviraj@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 93],
    ['roll_no' => 'IT1123', 'name' => 'KADAM PRANAV PREMNATH', 'email' => 'it1123.pranav@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 79],
    ['roll_no' => 'IT1124', 'name' => 'KALAMKAR RASIKA SUHAS', 'email' => 'it1124.rasika@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 94],
    ['roll_no' => 'IT1125', 'name' => 'KANHAIYA KISHOR MARATHE', 'email' => 'it1125.kanhaiya@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 82],
    ['roll_no' => 'IT1126', 'name' => 'KAWADE AYUSHI KIRAN', 'email' => 'it1126.ayushi@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 88],
    ['roll_no' => 'IT1127', 'name' => 'KHEDKAR SURAJ UDDHAV', 'email' => 'it1127.suraj@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 74],
    ['roll_no' => 'IT1128', 'name' => 'KHODADE SAMRUDDHI SHAILESH', 'email' => 'it1128.samruddhi@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 91],
    ['roll_no' => 'IT1129', 'name' => 'KHODE RIDDHI KRUSHNA', 'email' => 'it1129.riddhi@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 85],
    ['roll_no' => 'IT1130', 'name' => 'LAHAMGE SHUBHAM PRAVIN', 'email' => 'it1130.shubham@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 67],
    ['roll_no' => 'IT1131', 'name' => 'MACHAREKAR ARNAV RAJAN', 'email' => 'it1131.arnav@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 83],
    ['roll_no' => 'IT1132', 'name' => 'MAHURE ADITYA SUNIL', 'email' => 'it1132.aditya@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 80],
    ['roll_no' => 'IT1133', 'name' => 'MOMIN BUSHARA MEHMUD', 'email' => 'it1133.bushara@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 96],
    ['roll_no' => 'IT1134', 'name' => 'OROKAR MAHESHWARI SANDIP', 'email' => 'it1134.maheshwari@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 78],
    ['roll_no' => 'IT1135', 'name' => 'PADWAL SURAJ NARESH', 'email' => 'it1135.suraj@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 72],
    ['roll_no' => 'IT1136', 'name' => 'PANCHAL ROHAN RAMESH', 'email' => 'it1136.rohan@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 89],
    ['roll_no' => 'IT1137', 'name' => 'PATIL ROHINI GAUDAPPA', 'email' => 'it1137.rohini@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 92],
    ['roll_no' => 'IT1138', 'name' => 'PAWAR KARAN BABURAO', 'email' => 'it1138.karan@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 81],
    ['roll_no' => 'IT1139', 'name' => 'PAWAR SATYAM HIRAMAN', 'email' => 'it1139.satyam@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 63],
    ['roll_no' => 'IT1140', 'name' => 'PAYGUDE SHUBHANGI VIJAY', 'email' => 'it1140.shubhangi@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 87],
    ['roll_no' => 'IT1141', 'name' => 'POTDAR TUSHAR VIVEK', 'email' => 'it1141.tushar@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 85],
    ['roll_no' => 'IT1142', 'name' => 'POTRAJE RUCHIRA RAVI', 'email' => 'it1142.ruchira@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 90],
    ['roll_no' => 'IT1143', 'name' => 'PRASAD VIVEK KULKARNI', 'email' => 'it1143.vivek@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 77],
    ['roll_no' => 'IT1144', 'name' => 'RAMGUDE PAVAN AMAR', 'email' => 'it1144.pavan@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 84],
    ['roll_no' => 'IT1145', 'name' => 'RATHOD DISHA AVINASH', 'email' => 'it1145.disha@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 94],
    ['roll_no' => 'IT1146', 'name' => 'RATHOD RAJ NEPAL', 'email' => 'it1146.raj@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 80],
    ['roll_no' => 'IT1147', 'name' => 'RUTUJA SANJIVKUMAR PANCHAL', 'email' => 'it1147.rutuja@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 86],
    ['roll_no' => 'IT1148', 'name' => 'SADICHHA KALIDAS PAWAR', 'email' => 'it1148.sadichha@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 68],
    ['roll_no' => 'IT1149', 'name' => 'SATPUTE ADITYA PANDHARINATH', 'email' => 'it1149.aditya@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 82],
    ['roll_no' => 'IT1150', 'name' => 'SHEVARE AJAY VINOD', 'email' => 'it1150.ajay@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 75],
    ['roll_no' => 'IT1151', 'name' => 'SHINDE TEJASWINI PRAKASH', 'email' => 'it1151.tejaswini@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 91],
    ['roll_no' => 'IT1152', 'name' => 'SHRAWANI KRISHNA ROKDE', 'email' => 'it1152.shrawani@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 88],
    ['roll_no' => 'IT1153', 'name' => 'SHRINIDHI MADHAV SHINDE', 'email' => 'it1153.shrinidhi@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 83],
    ['roll_no' => 'IT1154', 'name' => 'SHUBHAM MACHHINDRA SURVASE', 'email' => 'it1154.shubham@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 79],
    ['roll_no' => 'IT1155', 'name' => 'SIDDHESH VINODKUMAR DHAVALE', 'email' => 'it1155.siddhesh@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 71],
    ['roll_no' => 'IT1156', 'name' => 'SINGH NAVYA RAJKUMAR', 'email' => 'it1156.navya@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 95],
    ['roll_no' => 'IT1157', 'name' => 'SOLANKAR SOHAM SIDDHESHWAR', 'email' => 'it1157.soham@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 80],
    ['roll_no' => 'IT1159', 'name' => 'SUCHITA RAMCHANDRA SIHORE', 'email' => 'it1159.suchita@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 84],
    ['roll_no' => 'IT1160', 'name' => 'SURVE VALLABH KSHITIJ', 'email' => 'it1160.vallabh@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 89],
    ['roll_no' => 'IT1161', 'name' => 'SWARANJALI OMPRAKASH GHODKE', 'email' => 'it1161.swaranjali@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 92],
    ['roll_no' => 'IT1162', 'name' => 'SWAROOP SATISH PARDESHI', 'email' => 'it1162.swaroop@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 76],
    ['roll_no' => 'IT1163', 'name' => 'TANTAK PARTH NITIN', 'email' => 'it1163.parth@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 65],
    ['roll_no' => 'IT1164', 'name' => 'UGLE MAYURI MAROTI', 'email' => 'it1164.mayuri@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 87],
    ['roll_no' => 'IT1165', 'name' => 'UNDALE SOHAM SHASHIKANT', 'email' => 'it1165.soham@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 90],
    ['roll_no' => 'IT1166', 'name' => 'VAISHNAVI AVINASH KANDHARE', 'email' => 'it1166.vaishnavi@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 82],
    ['roll_no' => 'IT1167', 'name' => 'WAGHMALE SRUSHTI SANTOSH', 'email' => 'it1167.srushti@student.edu', 'dept_id' => 2, 'section_id' => 2, 'attendance_pct' => 88]
];

$stmt_user = $conn->prepare("INSERT INTO users (name, email, password, role_id, department_id, status) VALUES (?,?,?,?,?,'Active')");
$stmt_student = $conn->prepare("INSERT INTO students (user_id, roll_no, name, email, department_id, section_id, semester_label, admission_year) VALUES (?,?,?,?,?,?,?,?)");

foreach ($student_list as $sl) {
    $hashed_pass = password_hash('student123', PASSWORD_BCRYPT);
    $role_id = 4;
    $stmt_user->bind_param('sssii', $sl['name'], $sl['email'], $hashed_pass, $role_id, $sl['dept_id']);
    $stmt_user->execute();
    $user_id = $conn->insert_id;
    
    $seml = '5th';
    $admy = 2024;
    $stmt_student->bind_param('isssiisi', $user_id, $sl['roll_no'], $sl['name'], $sl['email'], $sl['dept_id'], $sl['section_id'], $seml, $admy);
    $stmt_student->execute();
}
$stmt_user->close();
$stmt_student->close();
$log[] = "✅ Students seeded successfully (73 students)";

// ============================================================
// 8. SUBJECTS
// ============================================================
$subj_sql = "INSERT INTO subject (name, subject_code, semester_id, department_id, credits, type) VALUES
('Artificial Intelligence',         'CS501', 1, 2, 4, 'Theory'),
('Database Management Systems',     'CS502', 1, 1, 3, 'Theory'),
('Web Application Development',     'CS503', 1, 2, 4, 'Lab'),
('Software Engineering',            'IT501', 1, 2, 3, 'Theory'),
('Microprocessors & Controllers',   'EC501', 1, 3, 4, 'Theory')";
run_sql($conn, $subj_sql, "Subjects seeded", $log, $errors);

// ============================================================
// 9. CLASSROOMS
// ============================================================
$room_sql = "INSERT INTO classroom (room_no, capacity, projector, internet, whiteboard, status) VALUES
('Room 301',         60, 1, 1, 1, 'Active'),
('Room 302',         60, 1, 1, 1, 'Active'),
('Room 303',         55, 0, 1, 1, 'Active'),
('Room 304',         55, 1, 0, 1, 'Active'),
('Programming Lab 1',60, 1, 1, 1, 'Active')";
run_sql($conn, $room_sql, "Classrooms seeded", $log, $errors);

// ============================================================
// 10. TIMETABLE
// ============================================================
$tt_sql = "INSERT INTO timetable (semester_id, subject_id, faculty_id, classroom_id, section_id, day, start_time, end_time) VALUES
(1, 1, 1, 1, 2, 'Mon', '09:00:00', '10:00:00'),
(1, 2, 3, 2, 1, 'Mon', '10:00:00', '11:00:00'),
(1, 3, 10, 5, 2, 'Tue', '11:15:00', '13:15:00'),
(1, 4, 4, 3, 2, 'Wed', '14:00:00', '15:00:00'),
(1, 5, 6, 4, 3, 'Thu', '09:00:00', '10:00:00')";
run_sql($conn, $tt_sql, "Timetable seeded", $log, $errors);

// ============================================================
// 11. LABORATORIES
// ============================================================
$lab_sql = "INSERT INTO laboratory (name, location, capacity, total_systems, systems_working, network_status, status) VALUES
('Programming Lab 1',   'Block A, Floor 2', 60, 60, 58, 'Excellent', 'Active'),
('Data Science Lab',    'Block B, Floor 1', 50, 50, 45, 'Excellent', 'Active'),
('Hardware Lab 1',      'Block C, Floor 1', 30, 30, 20, 'Fair',      'Under Maintenance'),
('Embedded Systems Lab','Block C, Floor 2', 25, 25, 25, 'Excellent', 'Active')";
run_sql($conn, $lab_sql, "Laboratories seeded", $log, $errors);

// ============================================================
// 12. DAILY CLASS LOG (today's classes)
// ============================================================
$today = date('Y-m-d');
$dcl_sql = "INSERT INTO daily_class_log (timetable_id, date, actual_start_time, actual_end_time, status, remarks, marked_by) VALUES
(1, '$today', '09:02:00', '10:00:00', 'Conducted',     'Class completed on time', 1),
(2, '$today', '10:05:00', '11:00:00', 'Conducted',     'Extra problems solved',   3),
(3, '$today', NULL,       NULL,        'Not Conducted', 'Upcoming class',          10),
(4, '$today', NULL,       NULL,        'Cancelled',     'Faculty on Leave',        4),
(5, '$today', NULL,       NULL,        'Not Conducted', 'Upcoming class',          6)";
run_sql($conn, $dcl_sql, "Daily class log seeded", $log, $errors);

// ============================================================
// 13. ATTENDANCE (for conducted classes)
// ============================================================
$att_sql = "INSERT INTO attendance (classlog_id, student_id, status, marked_by) VALUES
(1,1,'Present',1),(1,2,'Absent',1),(1,3,'Present',1),
(2,1,'Present',3),(2,2,'Present',3),(2,3,'Absent',3)";
run_sql($conn, $att_sql, "Attendance seeded", $log, $errors);

// ============================================================
// 14. FACULTY ATTENDANCE (today)
// ============================================================
$fa_sql = "INSERT INTO faculty_attendance (faculty_id, date, status, marked_by) VALUES
(1, '$today', 'Present',  1),
(3, '$today', 'Present',  1),
(10, '$today', 'Present',  1),
(4, '$today', 'Absent',   1),
(6, '$today', 'Present',  1)";
run_sql($conn, $fa_sql, "Faculty attendance seeded", $log, $errors);

// ============================================================
// 15. CLASSROOM ISSUES
// ============================================================
$issue_sql = "INSERT INTO classroom_issue (classroom_id, reported_by, title, issue, type, status) VALUES
(1, 1, 'Projector Not Working',           'Projector lamp is dead, cannot display slides', 'Projector', 'Open'),
(3, 4, 'Slow Fiber Network Connectivity', 'Internet speed is below 10Mbps in this room',    'Internet',  'In Progress')";
run_sql($conn, $issue_sql, "Classroom issues seeded", $log, $errors);

// ============================================================
// 16. NOTIFICATIONS
// ============================================================
$notif_sql = "INSERT INTO notification (user_id, title, message, type, status) VALUES
(1, 'Syllabus Review Meeting', 'Syllabus Review Meeting scheduled for this Friday at 3 PM', 'Reminder', 'Unread'),
(1, 'Midterm Dates Finalized', 'Midterm examination dates have been finalized: Oct 12-17',   'Info',     'Unread'),
(1, 'Network Maintenance',     'Network maintenance scheduled in Block B this weekend',       'Warning',  'Unread'),
(2, 'Low Attendance Alert',    'Students BADAK ROHIT ASHOK and BHAVSAR SOHAM ABHAY have attendance below 75%','Alert',   'Unread'),
(3, 'Attendance Reminder',     'Please mark today attendance before 5 PM',                    'Reminder', 'Unread')";
run_sql($conn, $notif_sql, "Notifications seeded", $log, $errors);

// ============================================================
// 17. ACADEMIC EVENTS (Calendar)
// ============================================================
$evt_sql = "INSERT INTO academic_event (title, type, start_date, end_date, description, created_by) VALUES
('Semester Commencement',        'Academic Event', '2026-08-03', '2026-08-03', 'Fall Semester 2026-27 begins',          1),
('Independence Day Holiday',     'Holiday',        '2026-08-15', '2026-08-15', 'National Holiday - College Closed',     1),
('Mid-Semester Examinations',    'Exam',           '2026-10-12', '2026-10-17', 'Mid-sem exams for all departments',     1),
('Winter Vacation Break',        'Holiday',        '2026-12-21', '2026-12-31', 'End of semester break',                 1),
('Semester End Examinations',    'Exam',           '2026-11-25', '2026-12-10', 'End-of-semester theory examinations',   1)";
run_sql($conn, $evt_sql, "Academic events seeded", $log, $errors);

// ============================================================
// 18. UPDATE DEPARTMENT HOD
// ============================================================
$hod_sql = "UPDATE department SET hod_id = 2 WHERE department_id = 2";
run_sql($conn, $hod_sql, "Department HOD updated", $log, $errors);

// ============================================================
// Done — Display Results
// ============================================================
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team 2 — Database Setup</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 800px; margin: 40px auto; background: #0A0D1A; color: #F3F4F6; padding: 2rem; border-radius: 12px; }
        h1 { color: #6366F1; }
        .log { background: #12182D; padding: 1rem; border-radius: 8px; margin: 1rem 0; max-height: 400px; overflow-y: auto; }
        .error { background: rgba(244,63,94,0.15); padding: 1rem; border-radius: 8px; border-left: 4px solid #F43F5E; margin: 1rem 0; }
        .success { background: rgba(6,182,212,0.1); padding: 1rem; border-radius: 8px; border-left: 4px solid #06B6D4; }
        p { margin: 0.3rem 0; font-size: 0.9rem; }
        a { color: #6366F1; }
    </style>
</head>
<body>
    <h1>🎓 Team 2 — Database Setup Complete</h1>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>❌ Errors occurred:</strong>
            <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="success"><strong>✅ All operations completed successfully!</strong></div>
    <?php endif; ?>

    <div class="log">
        <?php foreach ($log as $l): ?><p><?= htmlspecialchars($l) ?></p><?php endforeach; ?>
    </div>

    <div style="margin-top:2rem; background:#12182D; padding:1rem; border-radius:8px;">
        <strong>🔑 Login Credentials:</strong><br><br>
        <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
            <tr style="color:#6366F1"><th style="text-align:left;padding:0.3rem">Role</th><th style="text-align:left;padding:0.3rem">Email / Username</th><th style="text-align:left;padding:0.3rem">Password</th></tr>
            <tr><td style="padding:0.3rem">Admin</td><td>admin</td><td>admin123</td></tr>
            <tr><td style="padding:0.3rem">HOD</td><td>hod</td><td>hod123</td></tr>
            <tr><td style="padding:0.3rem">Faculty</td><td>faculty</td><td>faculty123</td></tr>
            <tr><td style="padding:0.3rem">Student</td><td>student</td><td>student123</td></tr>
        </table>
    </div>

    <p style="margin-top:1.5rem"><a href="/Team 2/">→ Go to Application</a></p>
</body>
</html>
