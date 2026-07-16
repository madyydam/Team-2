<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Header Include - project/header.php
================================================================
*/

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// 1. DYNAMIC MOCK DATA INITIALIZATION
// ---------------------------------------------------------
define('DB_VERSION', '5.0'); // Bump this to force a session refresh after data updates

if (!isset($_SESSION['academic_db']) || ($_SESSION['db_version'] ?? '') !== DB_VERSION) {
    session_unset();
    $_SESSION['db_version'] = DB_VERSION;

    $_SESSION['academic_db'] = [
        'semester_active' => false,
        'departments' => ['Computer Science', 'Information Technology', 'Electronics & Comm.', 'Mechanical Eng.'],
        
        'faculty' => [
            [
                'id' => 1,
                'name' => 'Prof. Balaji A. Chaugule',
                'email' => 'balaji.chaugule@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 12,
                'status' => 'Active',
                'qualification' => 'M.E. (Computer Engineering)',
                'designation' => 'Head of Information Technology',
                'experience' => 'Teaching - 12 Years',
                'expertise' => 'Cloud Computing, Mobile Security, Parallel Computing, Artificial Intelligence',
                'photo' => 'img/balaji_chaugule.jpg'
            ],
            [
                'id' => 2,
                'name' => 'Dr. Neeti Rathore',
                'email' => 'neeti.rathore@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 16,
                'status' => 'Active',
                'qualification' => 'Ph.D.',
                'designation' => 'Assistant Professor',
                'experience' => 'Teaching - 20 Years',
                'expertise' => 'Organic and Drug Chemistry, Education, Environmental Sciences',
                'photo' => 'img/neeti_rathore.jpg'
            ],
            [
                'id' => 3,
                'name' => 'Dr. Hemant R. Suryavanshi',
                'email' => 'hemant.suryavanshi@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 14,
                'status' => 'Active',
                'qualification' => 'Ph.D.',
                'designation' => 'Assistant Professor',
                'experience' => 'Teaching - 4 Years, Industrial - 18 Years',
                'expertise' => 'Research',
                'photo' => 'img/hemant_suryavanshi.jpg'
            ],
            [
                'id' => 4,
                'name' => 'Prof. Ashwini M. Agarwal',
                'email' => 'ashwini.agarwal@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 16,
                'status' => 'Active',
                'qualification' => 'M.E. / M.Tech',
                'designation' => 'Assistant Professor',
                'experience' => 'Industrial - 3 Years',
                'expertise' => 'Data Analyst, Data Science',
                'photo' => 'img/ashwini_agarwal.jpg'
            ],
            [
                'id' => 7,
                'name' => 'Prof. Rohini R. Dhere',
                'email' => 'rohini.dhere@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 12,
                'status' => 'Active',
                'qualification' => 'M.Sc.',
                'designation' => 'Assistant Professor',
                'experience' => 'Industrial - 2.3 Years',
                'expertise' => 'Mathematics',
                'photo' => 'img/rohini_dhere.jpg'
            ],
            [
                'id' => 8,
                'name' => 'Prof. Komal P. Rathod',
                'email' => 'komal.rathod@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 14,
                'status' => 'Active',
                'qualification' => 'M.E. / M.Tech',
                'experience' => '-',
                'expertise' => 'Computer Engineering',
                'photo' => 'img/komal_rathod.jpg'
            ],
            [
                'id' => 9,
                'name' => 'Prof. Vishal H. Bafana',
                'email' => 'vishal.bafana@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 16,
                'status' => 'Active',
                'qualification' => 'B.E. / B.Tech',
                'designation' => 'Teaching Assistant',
                'experience' => 'Teaching - 8 Years',
                'expertise' => 'Advance Web & Mobile Development, Influential Communication',
                'photo' => 'img/vishal_bafana.jpg'
            ],
            [
                'id' => 10,
                'name' => 'Prof. Karan U. Jadhav',
                'email' => 'karanumakant.jadhav@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 12,
                'status' => 'Active',
                'qualification' => 'M.Sc.',
                'designation' => 'Teaching Assistant',
                'experience' => 'Teaching - 0.3 Years, Industrial - 0.6 Years',
                'expertise' => 'Industrial Mathematics with Computer Application',
                'photo' => ''
            ],
            [
                'id' => 11,
                'name' => 'Prof. Sumesh S. Shinde',
                'email' => 'sumesh.shinde@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 18,
                'status' => 'Active',
                'qualification' => 'B.E. / B.Tech',
                'designation' => 'Teaching Assistant',
                'experience' => 'Teaching - 0.3 Years, Industrial - 0.6 Years',
                'expertise' => 'Full Stack Developer, Java, Html, Css, Js',
                'photo' => 'img/sumesh_shinde.jpg'
            ],
            [
                'id' => 12,
                'name' => 'Prof. Shubhangi Wadibhasme',
                'email' => 'shubhangi.wadibhasme@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 14,
                'status' => 'Active',
                'qualification' => 'B.E. / B.Tech',
                'designation' => 'Teaching Assistant',
                'experience' => '-',
                'expertise' => 'Java Developer',
                'photo' => ''
            ],
            [
                'id' => 13,
                'name' => 'Prof. Shruti S. Ghotane',
                'email' => 'shruti.ghotane@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 16,
                'status' => 'Active',
                'qualification' => 'B.E. / B.Tech',
                'designation' => 'Teaching Assistant',
                'experience' => '-',
                'expertise' => 'Full Stack Fullstack',
                'photo' => ''
            ],
            [
                'id' => 14,
                'name' => 'Prof. Prachi D. Gole',
                'email' => 'prachi.gole@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 14,
                'status' => 'Active',
                'qualification' => 'B.E. / B.Tech',
                'designation' => 'Teaching Assistant',
                'experience' => '-',
                'expertise' => 'Computer Engineering',
                'photo' => ''
            ],
            [
                'id' => 15,
                'name' => 'Mr. Tejas U. Jagtap',
                'email' => 'tejas.jagtap@zealeducation.com',
                'department' => 'Information Technology',
                'workload' => 8,
                'status' => 'Active',
                'qualification' => 'Diploma / BCA',
                'designation' => 'Lab Assistant',
                'experience' => '1 Year',
                'expertise' => 'Technical Support and Desktop Support Engineer and Software and Automation Tester',
                'photo' => ''
            ]
        ],
        
        'students' => [
            ['id' => 101, 'name' => 'ADE ISHWARI SHAMRAO', 'roll_no' => 'IT1101', 'email' => 'it1101.ishwari@student.edu', 'department' => 'Information Technology', 'semester' => '1st', 'attendance_pct' => 82],
            ['id' => 109, 'name' => 'BADAK ROHIT ASHOK', 'roll_no' => 'IT1102', 'email' => 'it1102.rohit@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 78],
            ['id' => 110, 'name' => 'BANKAR PRERANA SANDIP', 'roll_no' => 'IT1103', 'email' => 'it1103.prerana@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 91],
            ['id' => 111, 'name' => 'BARGAJE GAYATRI SANTOSH', 'roll_no' => 'IT1104', 'email' => 'it1104.gayatri@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 64],
            ['id' => 112, 'name' => 'BHAVSAR SOHAM ABHAY', 'roll_no' => 'IT1105', 'email' => 'it1105.soham@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 88],
            ['id' => 113, 'name' => 'BORKAR AYUSH RAMBHAU', 'roll_no' => 'IT1106', 'email' => 'it1106.ayush@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 73],
            ['id' => 114, 'name' => 'CHAITANYA JYOTIRAM BHOSALE', 'roll_no' => 'IT1107', 'email' => 'it1107.chaitanya@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 95],
            ['id' => 115, 'name' => 'CHOLE PRANAV SHARAD', 'roll_no' => 'IT1108', 'email' => 'it1108.pranav@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 80],
            ['id' => 116, 'name' => 'DALVE PRUTHVIRAJ AMAR', 'roll_no' => 'IT1109', 'email' => 'it1109.pruthviraj@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 83],
            ['id' => 117, 'name' => 'DAVHALE SUMIT KISHOR', 'roll_no' => 'IT1110', 'email' => 'it1110.sumit@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 69],
            ['id' => 118, 'name' => 'DHADVE MADHUR SANJAY', 'roll_no' => 'IT1111', 'email' => 'it1111.madhur@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 90],
            ['id' => 119, 'name' => 'DIVY ANIL KOKATE', 'roll_no' => 'IT1112', 'email' => 'it1112.divy@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 75],
            ['id' => 120, 'name' => 'DIXIT SHIVAMSINGH NARENDRASINGH', 'roll_no' => 'IT1113', 'email' => 'it1113.shivamsingh@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 87],
            ['id' => 121, 'name' => 'DOLASE PRANAV PRAVIN', 'roll_no' => 'IT1114', 'email' => 'it1114.pranav@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 81],
            ['id' => 122, 'name' => 'GADAKH RISHI ASHOK', 'roll_no' => 'IT1115', 'email' => 'it1115.rishi@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 76],
            ['id' => 123, 'name' => 'GARJE SUJIT ARUN', 'roll_no' => 'IT1116', 'email' => 'it1116.sujit@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 62],
            ['id' => 124, 'name' => 'GAURAV RAJENDRA NANVARE', 'roll_no' => 'IT1117', 'email' => 'it1117.gaurav@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 84],
            ['id' => 125, 'name' => 'GHARAT SWARA SANTOSH', 'roll_no' => 'IT1118', 'email' => 'it1118.swara@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 92],
            ['id' => 126, 'name' => 'GHULE ARJUN GAJANAN', 'roll_no' => 'IT1119', 'email' => 'it1119.arjun@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 89],
            ['id' => 127, 'name' => 'GODAMBE SWARAJ VISHNU', 'roll_no' => 'IT1120', 'email' => 'it1120.swaraj@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 70],
            ['id' => 128, 'name' => 'GOUR YUVRAJSINHA RAVINDRA', 'roll_no' => 'IT1121', 'email' => 'it1121.yuvrajsinha@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 86],
            ['id' => 129, 'name' => 'INGLE PRUTHVIRAJ PRABHU', 'roll_no' => 'IT1122', 'email' => 'it1122.pruthviraj@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 93],
            ['id' => 130, 'name' => 'KADAM PRANAV PREMNATH', 'roll_no' => 'IT1123', 'email' => 'it1123.pranav@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 79],
            ['id' => 131, 'name' => 'KALAMKAR RASIKA SUHAS', 'roll_no' => 'IT1124', 'email' => 'it1124.rasika@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 94],
            ['id' => 132, 'name' => 'KANHAIYA KISHOR MARATHE', 'roll_no' => 'IT1125', 'email' => 'it1125.kanhaiya@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 82],
            ['id' => 133, 'name' => 'KAWADE AYUSHI KIRAN', 'roll_no' => 'IT1126', 'email' => 'it1126.ayushi@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 88],
            ['id' => 134, 'name' => 'KHEDKAR SURAJ UDDHAV', 'roll_no' => 'IT1127', 'email' => 'it1127.suraj@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 74],
            ['id' => 135, 'name' => 'KHODADE SAMRUDDHI SHAILESH', 'roll_no' => 'IT1128', 'email' => 'it1128.samruddhi@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 91],
            ['id' => 136, 'name' => 'KHODE RIDDHI KRUSHNA', 'roll_no' => 'IT1129', 'email' => 'it1129.riddhi@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 85],
            ['id' => 137, 'name' => 'LAHAMGE SHUBHAM PRAVIN', 'roll_no' => 'IT1130', 'email' => 'it1130.shubham@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 67],
            ['id' => 138, 'name' => 'MACHAREKAR ARNAV RAJAN', 'roll_no' => 'IT1131', 'email' => 'it1131.arnav@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 83],
            ['id' => 139, 'name' => 'MAHURE ADITYA SUNIL', 'roll_no' => 'IT1132', 'email' => 'it1132.aditya@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 80],
            ['id' => 140, 'name' => 'MOMIN BUSHARA MEHMUD', 'roll_no' => 'IT1133', 'email' => 'it1133.bushara@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 96],
            ['id' => 141, 'name' => 'OROKAR MAHESHWARI SANDIP', 'roll_no' => 'IT1134', 'email' => 'it1134.maheshwari@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 78],
            ['id' => 142, 'name' => 'PADWAL SURAJ NARESH', 'roll_no' => 'IT1135', 'email' => 'it1135.suraj@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 72],
            ['id' => 143, 'name' => 'PANCHAL ROHAN RAMESH', 'roll_no' => 'IT1136', 'email' => 'it1136.rohan@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 89],
            ['id' => 144, 'name' => 'PATIL ROHINI GAUDAPPA', 'roll_no' => 'IT1137', 'email' => 'it1137.rohini@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 92],
            ['id' => 145, 'name' => 'PAWAR KARAN BABURAO', 'roll_no' => 'IT1138', 'email' => 'it1138.karan@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 81],
            ['id' => 146, 'name' => 'PAWAR SATYAM HIRAMAN', 'roll_no' => 'IT1139', 'email' => 'it1139.satyam@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 63],
            ['id' => 147, 'name' => 'PAYGUDE SHUBHANGI VIJAY', 'roll_no' => 'IT1140', 'email' => 'it1140.shubhangi@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 87],
            ['id' => 148, 'name' => 'POTDAR TUSHAR VIVEK', 'roll_no' => 'IT1141', 'email' => 'it1141.tushar@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 85],
            ['id' => 149, 'name' => 'POTRAJE RUCHIRA RAVI', 'roll_no' => 'IT1142', 'email' => 'it1142.ruchira@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 90],
            ['id' => 150, 'name' => 'PRASAD VIVEK KULKARNI', 'roll_no' => 'IT1143', 'email' => 'it1143.vivek@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 77],
            ['id' => 151, 'name' => 'RAMGUDE PAVAN AMAR', 'roll_no' => 'IT1144', 'email' => 'it1144.pavan@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 84],
            ['id' => 152, 'name' => 'RATHOD DISHA AVINASH', 'roll_no' => 'IT1145', 'email' => 'it1145.disha@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 94],
            ['id' => 153, 'name' => 'RATHOD RAJ NEPAL', 'roll_no' => 'IT1146', 'email' => 'it1146.raj@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 80],
            ['id' => 154, 'name' => 'RUTUJA SANJIVKUMAR PANCHAL', 'roll_no' => 'IT1147', 'email' => 'it1147.rutuja@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 86],
            ['id' => 155, 'name' => 'SADICHHA KALIDAS PAWAR', 'roll_no' => 'IT1148', 'email' => 'it1148.sadichha@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 68],
            ['id' => 156, 'name' => 'SATPUTE ADITYA PANDHARINATH', 'roll_no' => 'IT1149', 'email' => 'it1149.aditya@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 82],
            ['id' => 157, 'name' => 'SHEVARE AJAY VINOD', 'roll_no' => 'IT1150', 'email' => 'it1150.ajay@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 75],
            ['id' => 158, 'name' => 'SHINDE TEJASWINI PRAKASH', 'roll_no' => 'IT1151', 'email' => 'it1151.tejaswini@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 91],
            ['id' => 159, 'name' => 'SHRAWANI KRISHNA ROKDE', 'roll_no' => 'IT1152', 'email' => 'it1152.shrawani@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 88],
            ['id' => 160, 'name' => 'SHRINIDHI MADHAV SHINDE', 'roll_no' => 'IT1153', 'email' => 'it1153.shrinidhi@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 83],
            ['id' => 161, 'name' => 'SHUBHAM MACHHINDRA SURVASE', 'roll_no' => 'IT1154', 'email' => 'it1154.shubham@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 79],
            ['id' => 162, 'name' => 'SIDDHESH VINODKUMAR DHAVALE', 'roll_no' => 'IT1155', 'email' => 'it1155.siddhesh@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 71],
            ['id' => 163, 'name' => 'SINGH NAVYA RAJKUMAR', 'roll_no' => 'IT1156', 'email' => 'it1156.navya@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 95],
            ['id' => 164, 'name' => 'SOLANKAR SOHAM SIDDHESHWAR', 'roll_no' => 'IT1157', 'email' => 'it1157.soham@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 80],
            ['id' => 165, 'name' => 'SUCHITA RAMCHANDRA SIHORE', 'roll_no' => 'IT1159', 'email' => 'it1159.suchita@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 84],
            ['id' => 166, 'name' => 'SURVE VALLABH KSHITIJ', 'roll_no' => 'IT1160', 'email' => 'it1160.vallabh@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 89],
            ['id' => 167, 'name' => 'SWARANJALI OMPRAKASH GHODKE', 'roll_no' => 'IT1161', 'email' => 'it1161.swaranjali@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 92],
            ['id' => 168, 'name' => 'SWAROOP SATISH PARDESHI', 'roll_no' => 'IT1162', 'email' => 'it1162.swaroop@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 76],
            ['id' => 169, 'name' => 'TANTAK PARTH NITIN', 'roll_no' => 'IT1163', 'email' => 'it1163.parth@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 65],
            ['id' => 170, 'name' => 'UGLE MAYURI MAROTI', 'roll_no' => 'IT1164', 'email' => 'it1164.mayuri@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 87],
            ['id' => 171, 'name' => 'UNDALE SOHAM SHASHIKANT', 'roll_no' => 'IT1165', 'email' => 'it1165.soham@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 90],
            ['id' => 172, 'name' => 'VAISHNAVI AVINASH KANDHARE', 'roll_no' => 'IT1166', 'email' => 'it1166.vaishnavi@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 82],
            ['id' => 173, 'name' => 'WAGHMALE SRUSHTI SANTOSH', 'roll_no' => 'IT1167', 'email' => 'it1167.srushti@student.edu', 'department' => 'Information Technology', 'semester' => '5th', 'attendance_pct' => 88]
        ],
        
        'calendar' => [
            ['id' => 1, 'title' => 'Semester Commencement', 'type' => 'Academic Event', 'start_date' => '2026-08-03', 'end_date' => '2026-08-03'],
            ['id' => 2, 'title' => 'Independence Day Holiday', 'type' => 'Holiday', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15'],
            ['id' => 3, 'title' => 'Mid-Semester Examinations', 'type' => 'Academic Event', 'start_date' => '2026-10-12', 'end_date' => '2026-10-17'],
            ['id' => 4, 'title' => 'Winter Vacation Break', 'type' => 'Holiday', 'start_date' => '2026-12-21', 'end_date' => '2026-12-31'],
        ],
        
        'subjects' => [
            ['code' => 'CS501', 'name' => 'Artificial Intelligence', 'credits' => 4, 'semester' => '5th', 'department' => 'Information Technology'],
            ['code' => 'CS502', 'name' => 'Database Management Systems', 'credits' => 3, 'semester' => '5th', 'department' => 'Computer Science'],
            ['code' => 'CS503', 'name' => 'Web Application Development', 'credits' => 4, 'semester' => '5th', 'department' => 'Information Technology'],
            ['code' => 'IT501', 'name' => 'Software Engineering', 'credits' => 3, 'semester' => '5th', 'department' => 'Information Technology'],
            ['code' => 'EC501', 'name' => 'Microprocessors & Controllers', 'credits' => 4, 'semester' => '5th', 'department' => 'Electronics & Comm.'],
        ],

        'timetable' => [
            ['id' => 1, 'subject' => 'CS501', 'faculty_id' => 1, 'room' => 'Room 301', 'time_slot' => '09:00 AM - 10:00 AM', 'day_of_week' => 'Monday', 'department' => 'Information Technology', 'semester' => '5th'],
            ['id' => 2, 'subject' => 'CS502', 'faculty_id' => 2, 'room' => 'Room 302', 'time_slot' => '10:00 AM - 11:00 AM', 'day_of_week' => 'Monday', 'department' => 'Computer Science', 'semester' => '5th'],
            ['id' => 3, 'subject' => 'CS503', 'faculty_id' => 11, 'room' => 'Programming Lab 1', 'time_slot' => '11:15 AM - 01:15 PM', 'day_of_week' => 'Tuesday', 'department' => 'Information Technology', 'semester' => '5th'],
            ['id' => 4, 'subject' => 'IT501', 'faculty_id' => 2, 'room' => 'Room 303', 'time_slot' => '02:00 PM - 03:00 PM', 'day_of_week' => 'Wednesday', 'department' => 'Information Technology', 'semester' => '1st'],
            ['id' => 5, 'subject' => 'EC501', 'faculty_id' => 6, 'room' => 'Room 304', 'time_slot' => '09:00 AM - 10:00 AM', 'day_of_week' => 'Thursday', 'department' => 'Information Technology', 'semester' => '1st'],
        ],
        
        'labs' => [
            ['id' => 1, 'name' => 'Programming Lab 1', 'status' => 'Conducted', 'systems_working' => 58, 'total_systems' => 60, 'network_status' => 'Excellent', 'equipment_status' => 'Good'],
            ['id' => 2, 'name' => 'Data Science Lab', 'status' => 'Conducted', 'systems_working' => 45, 'total_systems' => 50, 'network_status' => 'Excellent', 'equipment_status' => 'Good'],
            ['id' => 3, 'name' => 'Hardware Lab 1', 'status' => 'Under Maintenance', 'systems_working' => 20, 'total_systems' => 30, 'network_status' => 'Fair', 'equipment_status' => 'Under Maintenance'],
            ['id' => 4, 'name' => 'Embedded Systems Lab', 'status' => 'Free', 'systems_working' => 25, 'total_systems' => 25, 'network_status' => 'Excellent', 'equipment_status' => 'Good'],
        ],
        
        'issues' => [
            ['id' => 1, 'title' => 'Projector Not Working', 'room' => 'Room 301', 'type' => 'Projector', 'status' => 'Pending', 'reported_by' => 'Dr. Balaji Chaugule', 'date' => '2026-07-15'],
            ['id' => 2, 'title' => 'Slow Fiber Network Connectivity', 'room' => 'Hardware Lab 1', 'type' => 'Internet', 'status' => 'In Progress', 'reported_by' => 'Dr. Hemant R. Suryavanshi', 'date' => '2026-07-14'],
        ],
        
        'announcements' => [
            ['id' => 1, 'title' => 'Syllabus Review Meeting scheduled for Friday', 'time' => '2 hours ago', 'type' => 'general'],
            ['id' => 2, 'title' => 'Midterm examination dates finalized', 'time' => '1 day ago', 'type' => 'exam'],
            ['id' => 3, 'title' => 'Network maintenance notice in block B', 'time' => '2 days ago', 'type' => 'warning'],
        ],
    ];
}

// ---------------------------------------------------------
// 2. ROUTING & ACCESS CONTROL
// ---------------------------------------------------------
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && $current_page != 'login.php' && $current_page != 'index.php') {
    header("Location: login.php");
    exit();
}

$theme_mode = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'light';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest User';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'HOD';
$user_avatar = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team 2 - <?= ucwords(str_replace('.php', '', str_replace('_', ' ', $current_page))) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body class="<?= $theme_mode === 'dark' ? 'dark-mode' : '' ?>">

    <!-- Toast container for live updates -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="app-container">
        <!-- Sidebar and Layout structure starts here -->
        <?php if ($current_page != 'login.php' && $current_page != 'index.php'): ?>
            <?php include 'sidebar.php'; ?>
            <div class="main-content" id="mainContent">
                <header class="navbar">
                    <div class="nav-left">
                        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                            </svg>
                        </button>
                        <div class="search-bar">
                            <svg viewBox="0 0 24 24">
                                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                            </svg>
                            <input type="text" placeholder="Search students, faculty, or logs..." id="navSearchInput" onkeyup="globalSearchFilter()">
                        </div>
                    </div>
                    
                    <div class="nav-right">
                        <!-- Dark Mode Toggle Switch -->
                        <div class="theme-toggle-wrapper" onclick="toggleTheme()" title="Toggle Dark Mode" aria-label="Toggle Dark Mode" role="button" id="themeToggleBtn">
                            <!-- Moon icon -->
                            <svg class="toggle-icon toggle-moon" viewBox="0 0 24 24">
                                <path d="M12.3 2.03c-1.3-.1-2.6.2-3.8.8-4.4 2.1-6.7 7-5.3 11.7 1.3 4.2 5.1 6.9 9.4 6.9 2.5 0 4.9-1 6.7-2.8 3.5-3.5 3.9-9.1 1-13-1.1-1.5-2.7-2.6-4.5-3.1-.5-.1-1 .2-1 .7s.3.9.8 1.1c4.5 1.5 6.6 6.5 4.6 10.8-1.9 4-6.6 5.8-10.6 3.9-3.4-1.6-5.3-5.4-4.5-9.1.7-3.2 3.4-5.6 6.7-5.9.5 0 .9-.4.9-.9s-.3-.9-.9-.9c-.1 0-.2 0-.3.1z"/>
                            </svg>
                            <div class="theme-toggle-track <?= $theme_mode === 'dark' ? 'active' : '' ?>" id="themeToggleTrack">
                                <div class="theme-toggle-thumb" id="themeToggleThumb"></div>
                            </div>
                            <!-- Sun icon -->
                            <svg class="toggle-icon toggle-sun" viewBox="0 0 24 24">
                                <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41l-1.06-1.06zm1.06-12.37c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06c.39-.39.39-1.03 0-1.41zm-12.37 12.37c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06c.39-.39.39-1.03 0-1.41z"/>
                            </svg>
                        </div>
                        
                        <!-- Notifications Center Trigger -->
                        <button class="nav-icon-btn" onclick="toggleDropdown('notificationMenu')" aria-label="Notifications">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
                            </svg>
                            <span class="badge-dot"></span>
                            
                            <div class="dropdown-menu" id="notificationMenu" style="width: 280px; padding: 0.75rem;">
                                <h4 style="margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 0.35rem;">Notifications</h4>
                                <?php foreach ($_SESSION['academic_db']['announcements'] as $announce): ?>
                                    <div style="font-size: 0.75rem; padding: 0.45rem 0; border-bottom: 1px dotted var(--border-color); line-height: 1.4;">
                                        <p style="color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($announce['title']) ?></p>
                                        <span style="color: var(--text-muted); font-size: 0.65rem;"><?= $announce['time'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <a href="reports.php" style="text-align: center; display: block; font-size: 0.75rem; color: var(--primary); font-weight: 600; margin-top: 0.5rem;">View Notification Center</a>
                            </div>
                        </button>
                        
                        <!-- User Profile Dropdown -->
                        <div class="profile-dropdown" onclick="toggleDropdown('profileMenu')">
                            <div class="profile-avatar">
                                <?= $user_avatar ?>
                            </div>
                            <div class="profile-info">
                                <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                                <div class="profile-role"><?= htmlspecialchars($user_role) ?> Dashboard</div>
                            </div>
                            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: var(--text-secondary);">
                                <path d="M7 10l5 5 5-5z"/>
                            </svg>
                            
                            <div class="dropdown-menu" id="profileMenu">
                                <a href="settings.php" class="dropdown-item">
                                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                                    System Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-item" style="color: var(--danger);">
                                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                                    Log Out
                                </a>
                            </div>
                        </div>
                    </div>
                </header>
        <?php endif; ?>
