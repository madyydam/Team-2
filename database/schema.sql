-- ============================================================
-- Academic Planning & Monitoring System
-- Module 3: Commencement of Classes
-- Database: academic_monitoring_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS academic_monitoring_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE academic_monitoring_db;

-- ------------------------------------------------------------
-- 1. ROLES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(50) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 2. DEPARTMENT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    hod_id        INT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 3. USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) UNIQUE NOT NULL,
    password      VARCHAR(255) NOT NULL,
    role_id       INT NOT NULL,
    department_id INT NULL,
    status        ENUM('Active','Inactive') DEFAULT 'Active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (department_id) REFERENCES department(department_id)
);

-- ------------------------------------------------------------
-- 4. FACULTY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS faculty (
    faculty_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    department_id INT NOT NULL,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL,
    employee_code VARCHAR(50),
    mobile        VARCHAR(15),
    qualification VARCHAR(100),
    designation   VARCHAR(100),
    experience    VARCHAR(100),
    expertise     VARCHAR(255),
    photo         VARCHAR(255),
    workload_hrs  INT DEFAULT 0,
    status        ENUM('Active','On Leave','Inactive') DEFAULT 'Active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (department_id) REFERENCES department(department_id)
);

-- ------------------------------------------------------------
-- 5. SEMESTER
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS semester (
    semester_id   INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(20) NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    start_date    DATE NOT NULL,
    end_date      DATE NOT NULL,
    status        ENUM('Planning','Active','Completed','Closed') DEFAULT 'Planning',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 6. SECTION
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS section (
    section_id    INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(20) NOT NULL,
    department_id INT NOT NULL,
    year          INT NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES department(department_id)
);

-- ------------------------------------------------------------
-- 7. STUDENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    student_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    roll_no       VARCHAR(20) UNIQUE NOT NULL,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL,
    mobile        VARCHAR(15),
    department_id INT NOT NULL,
    section_id    INT NOT NULL,
    semester_label VARCHAR(10) DEFAULT '5th',
    admission_year INT NOT NULL DEFAULT 2024,
    status        ENUM('Active','Inactive') DEFAULT 'Active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (department_id) REFERENCES department(department_id),
    FOREIGN KEY (section_id) REFERENCES section(section_id)
);

-- ------------------------------------------------------------
-- 8. SUBJECT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subject (
    subject_id   INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    semester_id  INT NOT NULL,
    department_id INT NOT NULL,
    credits      INT DEFAULT 3,
    type         ENUM('Theory','Practical','Lab') DEFAULT 'Theory',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (semester_id) REFERENCES semester(semester_id),
    FOREIGN KEY (department_id) REFERENCES department(department_id)
);

-- ------------------------------------------------------------
-- 9. CLASSROOM
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS classroom (
    classroom_id INT AUTO_INCREMENT PRIMARY KEY,
    room_no      VARCHAR(20) UNIQUE NOT NULL,
    capacity     INT DEFAULT 60,
    projector    TINYINT(1) DEFAULT 0,
    internet     TINYINT(1) DEFAULT 0,
    whiteboard   TINYINT(1) DEFAULT 1,
    status       ENUM('Active','Maintenance','Unavailable') DEFAULT 'Active',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 10. TIMETABLE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS timetable (
    timetable_id INT AUTO_INCREMENT PRIMARY KEY,
    semester_id  INT NOT NULL,
    subject_id   INT NOT NULL,
    faculty_id   INT NOT NULL,
    classroom_id INT NOT NULL,
    section_id   INT NOT NULL,
    day          ENUM('Mon','Tue','Wed','Thu','Fri','Sat') NOT NULL,
    start_time   TIME NOT NULL,
    end_time     TIME NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (semester_id) REFERENCES semester(semester_id),
    FOREIGN KEY (subject_id) REFERENCES subject(subject_id),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (classroom_id) REFERENCES classroom(classroom_id),
    FOREIGN KEY (section_id) REFERENCES section(section_id)
);

-- ------------------------------------------------------------
-- 11. DAILY_CLASS_LOG
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS daily_class_log (
    classlog_id       INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id      INT NOT NULL,
    date              DATE NOT NULL,
    actual_start_time TIME NULL,
    actual_end_time   TIME NULL,
    status            ENUM('Conducted','Not Conducted','Cancelled') DEFAULT 'Not Conducted',
    remarks           TEXT,
    marked_by         INT NOT NULL,
    marked_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (timetable_id) REFERENCES timetable(timetable_id),
    FOREIGN KEY (marked_by) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 12. ATTENDANCE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    classlog_id   INT NOT NULL,
    student_id    INT NOT NULL,
    status        ENUM('Present','Absent') DEFAULT 'Absent',
    marked_by     INT NOT NULL,
    marked_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_student (classlog_id, student_id),
    FOREIGN KEY (classlog_id) REFERENCES daily_class_log(classlog_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (marked_by) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 13. FACULTY_ATTENDANCE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS faculty_attendance (
    faculty_attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id  INT NOT NULL,
    date        DATE NOT NULL,
    status      ENUM('Present','Absent','Half Day') DEFAULT 'Present',
    remarks     TEXT,
    marked_by   INT NOT NULL,
    marked_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_faculty_date (faculty_id, date),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (marked_by) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 14. LABORATORY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS laboratory (
    lab_id      INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    location    VARCHAR(100),
    capacity    INT DEFAULT 30,
    total_systems   INT DEFAULT 30,
    systems_working INT DEFAULT 30,
    network_status  ENUM('Excellent','Good','Fair','Poor') DEFAULT 'Good',
    status      ENUM('Active','Under Maintenance','Inactive') DEFAULT 'Active',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 15. LAB_SCHEDULE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lab_schedule (
    lab_schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    semester_id     INT NOT NULL,
    subject_id      INT NOT NULL,
    faculty_id      INT NOT NULL,
    lab_id          INT NOT NULL,
    section_id      INT NOT NULL,
    date            DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    cancelled_classes INT DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (semester_id) REFERENCES semester(semester_id),
    FOREIGN KEY (subject_id) REFERENCES subject(subject_id),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (lab_id) REFERENCES laboratory(lab_id),
    FOREIGN KEY (section_id) REFERENCES section(section_id)
);

-- ------------------------------------------------------------
-- 16. LAB_SESSION
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lab_session (
    session_id       INT AUTO_INCREMENT PRIMARY KEY,
    lab_schedule_id  INT NOT NULL,
    completed        ENUM('Yes','No') DEFAULT 'No',
    students_present INT DEFAULT 0,
    systems_working  INT DEFAULT 0,
    systems_repair   INT DEFAULT 0,
    remarks          TEXT,
    completed_by     INT NOT NULL,
    completed_at     DATETIME NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_schedule_id) REFERENCES lab_schedule(lab_schedule_id),
    FOREIGN KEY (completed_by) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 17. CLASSROOM_ISSUE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS classroom_issue (
    issue_id     INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    reported_by  INT NOT NULL,
    title        VARCHAR(255) NOT NULL,
    issue        TEXT NOT NULL,
    type         VARCHAR(50) DEFAULT 'General',
    status       ENUM('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at  DATETIME NULL,
    remarks      TEXT,
    FOREIGN KEY (classroom_id) REFERENCES classroom(classroom_id),
    FOREIGN KEY (reported_by) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 18. DAILY_REPORT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS daily_report (
    report_id            INT AUTO_INCREMENT PRIMARY KEY,
    date                 DATE UNIQUE NOT NULL,
    planned_classes      INT DEFAULT 0,
    conducted_classes    INT DEFAULT 0,
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    labs_completed       INT DEFAULT 0,
    cancelled_classes    INT DEFAULT 0,
    faculty_present      INT DEFAULT 0,
    issues               TEXT,
    generated_by         INT NOT NULL,
    generated_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 19. NOTIFICATION
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    title           VARCHAR(150) NOT NULL,
    message         TEXT NOT NULL,
    type            ENUM('Info','Warning','Alert','Reminder') DEFAULT 'Info',
    status          ENUM('Unread','Read') DEFAULT 'Unread',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 20. AUDIT_LOG
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    audit_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    module     VARCHAR(100) NOT NULL,
    action     VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ------------------------------------------------------------
-- 21. ACADEMIC_EVENT (Calendar events)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS academic_event (
    event_id    INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    type        ENUM('Academic Event','Holiday','Exam','Workshop','Other') DEFAULT 'Academic Event',
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    description TEXT,
    created_by  INT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Add HOD FK to department after users table is created
ALTER TABLE department
    ADD CONSTRAINT fk_dept_hod FOREIGN KEY (hod_id) REFERENCES users(user_id);

-- Indexes for performance
CREATE INDEX idx_timetable_day ON timetable(day);
CREATE INDEX idx_classlog_date ON daily_class_log(date);
CREATE INDEX idx_attendance_student ON attendance(student_id);
CREATE INDEX idx_faculty_att_date ON faculty_attendance(date);
CREATE INDEX idx_notification_user ON notification(user_id, status);
CREATE INDEX idx_issue_status ON classroom_issue(status);
