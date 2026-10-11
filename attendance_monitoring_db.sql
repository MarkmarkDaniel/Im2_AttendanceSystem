-- ============================================================================
-- ONLINE STUDENT ATTENDANCE MONITORING SYSTEM
-- COMPLETE DATABASE SETUP SCRIPT
-- ============================================================================
-- ITP104 - Information Management 2
-- Database: attendance_monitoring_db
-- Engine  : InnoDB   |   Charset: utf8mb4
--
-- ----------------------------------------------------------------------------
-- WHAT THIS FILE DOES
-- ----------------------------------------------------------------------------
--   1. DATABASE CREATION      Drops and recreates attendance_monitoring_db
--   2. TABLE CREATION         10 system tables + 8 Laravel framework tables
--   3. STORED PROCEDURES      Core lookup and reporting procedures
--   4. STORED PROCEDURES      CRUD and transaction procedures
--   5. INSERT SAMPLE DATA     251 rows of realistic demo data
--   6. VERIFICATION           Row counts, constraint checks, procedure tests
--   7. VIEW WHAT WAS ADDED    Live queries for the demo -- see new records
--
-- ----------------------------------------------------------------------------
-- HOW TO RUN
-- ----------------------------------------------------------------------------
--   1. Start MySQL in the XAMPP Control Panel
--   2. Open MySQL Workbench, connect to localhost:3306 as root
--   3. File -> Open SQL Script -> select this file
--   4. Click the lightning bolt, or press Ctrl+Shift+Enter
--   5. Right-click the schema list -> Refresh All
--
--   Then start the web application:
--       cd C:\xampp\htdocs\attendance-monitoring-system
--       php artisan serve
--
--   No php artisan migrate is needed. This script builds the identical
--   schema, and the migrations table is pre-filled so Laravel knows the
--   migrations have already run.
--
-- ----------------------------------------------------------------------------
-- WARNING
-- ----------------------------------------------------------------------------
--   Section 1 DROPS the database. Everything currently inside
--   attendance_monitoring_db will be permanently deleted and replaced
--   with the sample data below.
--
-- ----------------------------------------------------------------------------
-- DEMO ACCOUNTS  (all use the password: password)
-- ----------------------------------------------------------------------------
--   Admin       admin@example.com
--   Instructor  instructor@example.com
--   Student     student@example.com
--
--   Passwords are stored as bcrypt hashes, never as plain text.
--   Change them before using the system with real student data.
-- ============================================================================


-- ############################################################################
-- 1. DATABASE CREATION
-- ############################################################################

DROP DATABASE IF EXISTS attendance_monitoring_db;

CREATE DATABASE attendance_monitoring_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE attendance_monitoring_db;


-- ############################################################################
-- 2. TABLE CREATION
-- ############################################################################
-- Order matters. A foreign key can only point at a table that already
-- exists, so parents are created before children:
--
--   users, departments
--      -> students, instructors, subjects
--          -> classes
--              -> enrollments, attendance_sessions
--                  -> attendances
-- ############################################################################


-- ============================================================================
-- 2.1 - TABLE: users
-- ----------------------------------------------------------------------------
-- Login accounts for all three roles. Every student and instructor has
-- exactly one row here plus one row in their own profile table.
-- ============================================================================

CREATE TABLE users (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name              VARCHAR(150)    NOT NULL,
    email             VARCHAR(150)    NOT NULL,
    email_verified_at TIMESTAMP       NULL DEFAULT NULL,
    password          VARCHAR(255)    NOT NULL,
    role              ENUM('admin', 'instructor', 'student') NOT NULL DEFAULT 'student',
    profile_image     VARCHAR(255)    NULL DEFAULT NULL,
    remember_token    VARCHAR(100)    NULL DEFAULT NULL,
    created_at        TIMESTAMP       NULL DEFAULT NULL,
    updated_at        TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    KEY users_role_index (role)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.2 - TABLE: departments
-- ----------------------------------------------------------------------------
-- The parent table of the whole design. Students, instructors, and
-- subjects all belong to a department.
-- ============================================================================

CREATE TABLE departments (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_code VARCHAR(20)     NOT NULL,
    department_name VARCHAR(150)    NOT NULL,
    description     TEXT            NULL DEFAULT NULL,
    created_at      TIMESTAMP       NULL DEFAULT NULL,
    updated_at      TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY departments_department_code_unique (department_code)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.3 - TABLE: students
-- ----------------------------------------------------------------------------
-- Student profile. user_id links to the login account (One-to-One).
-- Deleting the user deletes the profile, but a department that still has
-- students cannot be deleted.
-- ============================================================================

CREATE TABLE students (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        BIGINT UNSIGNED NOT NULL,
    student_number VARCHAR(30)     NOT NULL,
    first_name     VARCHAR(100)    NOT NULL,
    middle_name    VARCHAR(100)    NULL DEFAULT NULL,
    last_name      VARCHAR(100)    NOT NULL,
    email          VARCHAR(150)    NOT NULL,
    phone          VARCHAR(30)     NULL DEFAULT NULL,
    department_id  BIGINT UNSIGNED NOT NULL,
    year_level     ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NOT NULL,
    section        VARCHAR(20)     NOT NULL,
    status         ENUM('Active', 'Inactive', 'Dropped', 'Unofficially Dropped') NOT NULL DEFAULT 'Active',
    created_at     TIMESTAMP       NULL DEFAULT NULL,
    updated_at     TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY students_student_number_unique (student_number),
    UNIQUE KEY students_email_unique (email),
    KEY students_filter_index (department_id, year_level, section),
    KEY students_last_name_index (last_name),
    KEY students_user_id_foreign (user_id),

    CONSTRAINT students_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,

    CONSTRAINT students_department_id_foreign
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.3.1 - TABLE: student_masterlist
-- ----------------------------------------------------------------------------
-- Official list of enrolled students, encoded by the admin. A student can
-- only self-register when the student number, last name and birth date
-- typed on the registration form match a row here. A row counts as
-- "registered" once a students row with the same student_number exists.
-- ============================================================================

CREATE TABLE student_masterlist (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_number VARCHAR(30)     NOT NULL,
    first_name     VARCHAR(100)    NOT NULL,
    middle_name    VARCHAR(100)    NULL DEFAULT NULL,
    last_name      VARCHAR(100)    NOT NULL,
    birth_date     DATE            NOT NULL,
    department_id  BIGINT UNSIGNED NOT NULL,
    year_level     ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NOT NULL,
    section        VARCHAR(20)     NOT NULL,
    created_at     TIMESTAMP       NULL DEFAULT NULL,
    updated_at     TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY student_masterlist_student_number_unique (student_number),
    KEY student_masterlist_last_name_index (last_name),
    KEY student_masterlist_department_id_foreign (department_id),

    CONSTRAINT student_masterlist_department_id_foreign
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.4 - TABLE: instructors
-- ----------------------------------------------------------------------------
-- Instructor profile. Same One-to-One pattern as students.
-- ============================================================================

CREATE TABLE instructors (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    employee_number VARCHAR(30)     NOT NULL,
    first_name      VARCHAR(100)    NOT NULL,
    middle_name     VARCHAR(100)    NULL DEFAULT NULL,
    last_name       VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    department_id   BIGINT UNSIGNED NOT NULL,
    status          ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at      TIMESTAMP       NULL DEFAULT NULL,
    updated_at      TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY instructors_employee_number_unique (employee_number),
    UNIQUE KEY instructors_email_unique (email),
    KEY instructors_department_index (department_id),
    KEY instructors_user_id_foreign (user_id),

    CONSTRAINT instructors_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,

    CONSTRAINT instructors_department_id_foreign
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.5 - TABLE: subjects
-- ----------------------------------------------------------------------------
-- The course catalog. A subject is the idea (ITP104); a class is one
-- actual offering of it with a schedule and an instructor.
-- ============================================================================

CREATE TABLE subjects (
    id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    subject_code  VARCHAR(30)      NOT NULL,
    subject_name  VARCHAR(150)     NOT NULL,
    description   TEXT             NULL DEFAULT NULL,
    units         TINYINT UNSIGNED NOT NULL DEFAULT 3,
    department_id BIGINT UNSIGNED  NOT NULL,
    created_at    TIMESTAMP        NULL DEFAULT NULL,
    updated_at    TIMESTAMP        NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY subjects_subject_code_unique (subject_code),
    KEY subjects_department_index (department_id),

    CONSTRAINT subjects_department_id_foreign
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.6 - TABLE: classes
-- ----------------------------------------------------------------------------
-- One offering of a subject: a section, a room, a schedule, an instructor.
-- start_time is what the late-detection logic compares against.
-- ============================================================================

CREATE TABLE classes (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_id    BIGINT UNSIGNED NOT NULL,
    instructor_id BIGINT UNSIGNED NOT NULL,
    school_year   VARCHAR(20)     NOT NULL,
    semester      ENUM('1st Semester', '2nd Semester', 'Summer') NOT NULL,
    section       VARCHAR(20)     NOT NULL,
    room          VARCHAR(50)     NULL DEFAULT NULL,
    schedule_day  ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday',
                       'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time    TIME            NOT NULL,
    end_time      TIME            NOT NULL,
    status        ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at    TIMESTAMP       NULL DEFAULT NULL,
    updated_at    TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY classes_offering_unique (subject_id, section, school_year, semester),
    KEY classes_instructor_index (instructor_id),
    KEY classes_day_index (schedule_day),

    CONSTRAINT classes_subject_id_foreign
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON DELETE CASCADE,

    CONSTRAINT classes_instructor_id_foreign
        FOREIGN KEY (instructor_id) REFERENCES instructors (id)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.7 - TABLE: enrollments
-- ----------------------------------------------------------------------------
-- The junction (bridge) table that resolves the Many-to-Many relationship
-- between students and classes. The UNIQUE key stops double enrollment.
-- ============================================================================

CREATE TABLE enrollments (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id          BIGINT UNSIGNED NOT NULL,
    student_id        BIGINT UNSIGNED NOT NULL,
    enrollment_status ENUM('Enrolled', 'Dropped', 'Completed') NOT NULL DEFAULT 'Enrolled',
    created_at        TIMESTAMP       NULL DEFAULT NULL,
    updated_at        TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY enrollments_unique (class_id, student_id),
    KEY enrollments_student_id_foreign (student_id),

    CONSTRAINT enrollments_class_id_foreign
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE,

    CONSTRAINT enrollments_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students (id)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.8 - TABLE: attendance_sessions
-- ----------------------------------------------------------------------------
-- One class meeting on one day. qr_token holds the random QR value while
-- the session is Open, and is set to NULL when it closes - that single
-- rule is what makes a scanned QR code expire.
-- ============================================================================

CREATE TABLE attendance_sessions (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id     BIGINT UNSIGNED NOT NULL,
    qr_token     VARCHAR(64)     NULL DEFAULT NULL,
    session_date DATE            NOT NULL,
    start_time   TIME            NOT NULL,
    end_time     TIME            NULL DEFAULT NULL,
    status       ENUM('Open', 'Closed') NOT NULL DEFAULT 'Open',
    created_at   TIMESTAMP       NULL DEFAULT NULL,
    updated_at   TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY attendance_sessions_qr_token_unique (qr_token),
    UNIQUE KEY attendance_sessions_unique (class_id, session_date),

    CONSTRAINT attendance_sessions_class_id_foreign
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.9 - TABLE: attendances
-- ----------------------------------------------------------------------------
-- The core record of the system. attendances_unique_record is the most
-- important constraint here: one student can only have ONE record per
-- class per day, which makes duplicate QR scans impossible.
-- ============================================================================

CREATE TABLE attendances (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attendance_session_id BIGINT UNSIGNED NULL DEFAULT NULL,
    class_id              BIGINT UNSIGNED NOT NULL,
    student_id            BIGINT UNSIGNED NOT NULL,
    attendance_date       DATE            NOT NULL,
    time_in               TIME            NULL DEFAULT NULL,
    time_out              TIME            NULL DEFAULT NULL,
    status                ENUM('Present', 'Late', 'Absent') NOT NULL DEFAULT 'Absent',
    remarks               VARCHAR(255)    NULL DEFAULT NULL,
    recorded_by           BIGINT UNSIGNED NULL DEFAULT NULL,
    created_at            TIMESTAMP       NULL DEFAULT NULL,
    updated_at            TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY attendances_unique_record (class_id, student_id, attendance_date),
    KEY attendances_date_status_index (attendance_date, status),
    KEY attendances_student_index (student_id),
    KEY attendances_session_foreign (attendance_session_id),
    KEY attendances_recorded_by_foreign (recorded_by),

    CONSTRAINT attendances_attendance_session_id_foreign
        FOREIGN KEY (attendance_session_id) REFERENCES attendance_sessions (id)
        ON DELETE CASCADE,

    CONSTRAINT attendances_class_id_foreign
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE,

    CONSTRAINT attendances_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students (id)
        ON DELETE CASCADE,

    CONSTRAINT attendances_recorded_by_foreign
        FOREIGN KEY (recorded_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.10 - TABLE: notifications
-- ----------------------------------------------------------------------------
-- In-app messages. type maps directly to a Bootstrap colour class.
-- ============================================================================

CREATE TABLE notifications (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT UNSIGNED NOT NULL,
    title      VARCHAR(150)    NOT NULL,
    message    TEXT            NOT NULL,
    type       ENUM('success', 'warning', 'danger', 'info') NOT NULL DEFAULT 'info',
    is_read    TINYINT(1)      NOT NULL DEFAULT 0,
    read_at    TIMESTAMP       NULL DEFAULT NULL,
    created_at TIMESTAMP       NULL DEFAULT NULL,
    updated_at TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    KEY notifications_user_read_index (user_id, is_read),

    CONSTRAINT notifications_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- ============================================================================
-- 2.11 - LARAVEL FRAMEWORK TABLES
-- ----------------------------------------------------------------------------
-- These are NOT part of the attendance system design. Laravel needs them
-- because .env sets SESSION_DRIVER=database, CACHE_STORE=database, and
-- QUEUE_CONNECTION=database.
--
-- EXCLUDE THESE FROM YOUR EER DIAGRAM. They have no relationship to the
-- attendance tables and will only make the diagram confusing.
-- ============================================================================

CREATE TABLE password_reset_tokens (
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE sessions (
    id            VARCHAR(255)    NOT NULL,
    user_id       BIGINT UNSIGNED NULL DEFAULT NULL,
    ip_address    VARCHAR(45)     NULL DEFAULT NULL,
    user_agent    TEXT            NULL DEFAULT NULL,
    payload       LONGTEXT        NOT NULL,
    last_activity INT             NOT NULL,
    PRIMARY KEY (id),
    KEY sessions_user_id_index (user_id),
    KEY sessions_last_activity_index (last_activity)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE cache (
    `key`      VARCHAR(255) NOT NULL,
    value      MEDIUMTEXT   NOT NULL,
    expiration INT          NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE cache_locks (
    `key`      VARCHAR(255) NOT NULL,
    owner      VARCHAR(255) NOT NULL,
    expiration INT          NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE jobs (
    id           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    queue        VARCHAR(255)     NOT NULL,
    payload      LONGTEXT         NOT NULL,
    attempts     TINYINT UNSIGNED NOT NULL,
    reserved_at  INT UNSIGNED     NULL DEFAULT NULL,
    available_at INT UNSIGNED     NOT NULL,
    created_at   INT UNSIGNED     NOT NULL,
    PRIMARY KEY (id),
    KEY jobs_queue_index (queue)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE job_batches (
    id             VARCHAR(255) NOT NULL,
    name           VARCHAR(255) NOT NULL,
    total_jobs     INT          NOT NULL,
    pending_jobs   INT          NOT NULL,
    failed_jobs    INT          NOT NULL,
    failed_job_ids LONGTEXT     NOT NULL,
    options        MEDIUMTEXT   NULL DEFAULT NULL,
    cancelled_at   INT          NULL DEFAULT NULL,
    created_at     INT          NOT NULL,
    finished_at    INT          NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid       VARCHAR(255)    NOT NULL,
    connection TEXT            NOT NULL,
    queue      TEXT            NOT NULL,
    payload    LONGTEXT        NOT NULL,
    exception  LONGTEXT        NOT NULL,
    failed_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY failed_jobs_uuid_unique (uuid)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE migrations (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    migration VARCHAR(255) NOT NULL,
    batch     INT          NOT NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Marking every migration as already run, so that a later
-- "php artisan migrate" does not try to re-create these tables.
INSERT INTO migrations (migration, batch) VALUES
('0001_01_01_000000_create_users_table',                1),
('0001_01_01_000001_create_cache_table',                1),
('0001_01_01_000002_create_jobs_table',                 1),
('2026_01_10_000001_create_departments_table',          1),
('2026_01_10_000002_create_students_table',             1),
('2026_01_10_000003_create_instructors_table',          1),
('2026_01_10_000004_create_subjects_table',             1),
('2026_01_10_000005_create_classes_table',              1),
('2026_01_10_000006_create_enrollments_table',          1),
('2026_01_10_000007_create_attendance_sessions_table',  1),
('2026_01_10_000008_create_attendances_table',          1),
('2026_01_10_000009_create_notifications_table',        1);



-- ############################################################################
-- 3. STORED PROCEDURES (CORE)
-- ############################################################################
-- WHY DELIMITER:
--   MySQL normally ends a statement at a semicolon. A procedure body is full
--   of semicolons, so MySQL would stop reading halfway through. DELIMITER $$
--   temporarily changes the end-of-statement marker to $$ so the whole
--   BEGIN...END block is sent as one piece. DELIMITER ; switches it back.
--
--   DELIMITER is a CLIENT command, not SQL. It works in MySQL Workbench and
--   the mysql command line, but NOT through PHP, PDO, or Laravel.
-- ############################################################################

-- ----------------------------------------------------------------------------
-- Drop any existing procedures so this script can be re-run safely
-- ----------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_GetUserByEmail;
DROP PROCEDURE IF EXISTS sp_GetUserByEmail;
DROP PROCEDURE IF EXISTS sp_GetStudentByNumber;
DROP PROCEDURE IF EXISTS sp_SearchStudents;
DROP PROCEDURE IF EXISTS sp_GetDashboardStats;

DROP PROCEDURE IF EXISTS sp_AddStudent;
DROP PROCEDURE IF EXISTS sp_UpdateStudent;
DROP PROCEDURE IF EXISTS sp_DeleteStudent;

DROP PROCEDURE IF EXISTS sp_EnrollStudent;
DROP PROCEDURE IF EXISTS sp_DropStudent;
DROP PROCEDURE IF EXISTS sp_GetClassRoster;

DROP PROCEDURE IF EXISTS sp_OpenSession;
DROP PROCEDURE IF EXISTS sp_CloseSession;
DROP PROCEDURE IF EXISTS sp_RecordAttendance;
DROP PROCEDURE IF EXISTS sp_MarkAbsentees;

DROP PROCEDURE IF EXISTS sp_GetDailyReport;
DROP PROCEDURE IF EXISTS sp_GetStudentReport;
DROP PROCEDURE IF EXISTS sp_GetStudentRate;
DROP PROCEDURE IF EXISTS sp_GetClassReport;
DROP PROCEDURE IF EXISTS sp_GetDepartmentReport;
DROP PROCEDURE IF EXISTS sp_GetMonthlyReport;
DROP PROCEDURE IF EXISTS sp_GetLowAttendance;

DROP PROCEDURE IF EXISTS sp_AddNotification;
DROP PROCEDURE IF EXISTS sp_MarkAllRead;


-- ============================================================================
-- 3.1 - LOOKUP AND SEARCH PROCEDURES
-- ============================================================================

DELIMITER $$

-- ----------------------------------------------------------------------------
-- PROCEDURE 1: Find a user account by email
-- ----------------------------------------------------------------------------
-- Note: the password is stored as a bcrypt hash, so it CANNOT be compared
-- inside SQL. This procedure returns the hash, and the application checks it
-- with Hash::check(). Never compare passwords with = in SQL.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetUserByEmail(
    IN p_email VARCHAR(150)
)
BEGIN
    SELECT id, name, email, password, role
    FROM users
    WHERE email = p_email;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 2: Find a student by student number
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetStudentByNumber(
    IN p_student_number VARCHAR(30)
)
BEGIN
    SELECT
        s.id,
        s.student_number,
        CONCAT(s.first_name, ' ', s.last_name) AS full_name,
        s.email,
        s.phone,
        d.department_code,
        d.department_name,
        s.year_level,
        s.section,
        s.status
    FROM students AS s
    JOIN departments AS d ON d.id = s.department_id
    WHERE s.student_number = p_student_number;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 3: Search students by name or student number
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_SearchStudents(
    IN p_search VARCHAR(100)
)
BEGIN
    SELECT
        s.id,
        s.student_number,
        CONCAT(s.last_name, ', ', s.first_name) AS student_name,
        d.department_code,
        s.year_level,
        s.section,
        s.status
    FROM students AS s
    JOIN departments AS d ON d.id = s.department_id
    WHERE s.first_name     LIKE CONCAT('%', p_search, '%')
       OR s.last_name      LIKE CONCAT('%', p_search, '%')
       OR s.student_number LIKE CONCAT('%', p_search, '%')
       OR s.email          LIKE CONCAT('%', p_search, '%')
    ORDER BY s.last_name;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 4: Dashboard counts
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetDashboardStats()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM students)    AS total_students,
        (SELECT COUNT(*) FROM instructors) AS total_instructors,
        (SELECT COUNT(*) FROM departments) AS total_departments,
        (SELECT COUNT(*) FROM subjects)    AS total_subjects,
        (SELECT COUNT(*) FROM classes)     AS total_classes,
        (SELECT COUNT(*) FROM attendances
            WHERE attendance_date = CURDATE() AND status = 'Present') AS present_today,
        (SELECT COUNT(*) FROM attendances
            WHERE attendance_date = CURDATE() AND status = 'Late')    AS late_today,
        (SELECT COUNT(*) FROM attendances
            WHERE attendance_date = CURDATE() AND status = 'Absent')  AS absent_today;
END $$

DELIMITER ;


-- ============================================================================
-- 3.2 - REPORT PROCEDURES
-- ============================================================================

DELIMITER $$

-- ----------------------------------------------------------------------------
-- PROCEDURE 15: Daily attendance report
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetDailyReport(
    IN p_date DATE
)
BEGIN
    SELECT
        s.student_number,
        CONCAT(s.last_name, ', ', s.first_name) AS student_name,
        d.department_code,
        sub.subject_code,
        c.section,
        a.time_in,
        a.status,
        a.remarks
    FROM attendances  AS a
    JOIN students     AS s   ON s.id   = a.student_id
    JOIN departments  AS d   ON d.id   = s.department_id
    JOIN classes      AS c   ON c.id   = a.class_id
    JOIN subjects     AS sub ON sub.id = c.subject_id
    WHERE a.attendance_date = p_date
    ORDER BY sub.subject_code, s.last_name;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 16: One student's full attendance history
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetStudentReport(
    IN p_student_id BIGINT,
    IN p_date_from  DATE,
    IN p_date_to    DATE
)
BEGIN
    SELECT
        a.attendance_date,
        sub.subject_code,
        sub.subject_name,
        c.section,
        a.time_in,
        a.status,
        a.remarks
    FROM attendances AS a
    JOIN classes     AS c   ON c.id   = a.class_id
    JOIN subjects    AS sub ON sub.id = c.subject_id
    WHERE a.student_id = p_student_id
      AND a.attendance_date >= p_date_from
      AND a.attendance_date <= p_date_to
    ORDER BY a.attendance_date DESC;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 17: One student's attendance rate in one class
-- ----------------------------------------------------------------------------
-- Formula: ((Present + Late) / Total Sessions) * 100
-- The IF guard prevents a division-by-zero error.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetStudentRate(
    IN p_student_id BIGINT,
    IN p_class_id   BIGINT
)
BEGIN
    DECLARE v_total   INT DEFAULT 0;
    DECLARE v_present INT DEFAULT 0;
    DECLARE v_late    INT DEFAULT 0;
    DECLARE v_absent  INT DEFAULT 0;
    DECLARE v_rate    DECIMAL(5,2) DEFAULT 0;

    SELECT
        COUNT(*),
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END),
        SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END),
        SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END)
    INTO v_total, v_present, v_late, v_absent
    FROM attendances
    WHERE student_id = p_student_id
      AND class_id   = p_class_id;

    IF v_total > 0 THEN
        SET v_rate = ROUND(((v_present + v_late) / v_total) * 100, 2);
    END IF;

    SELECT
        v_total   AS total_sessions,
        v_present AS present_count,
        v_late    AS late_count,
        v_absent  AS absent_count,
        v_rate    AS attendance_rate;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 18: Class attendance report
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetClassReport()
BEGIN
    SELECT
        sub.subject_code,
        sub.subject_name,
        c.section,
        CONCAT(i.first_name, ' ', i.last_name) AS instructor_name,
        COUNT(DISTINCT a.student_id)      AS student_count,
        COUNT(DISTINCT a.attendance_date) AS session_count,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
        ROUND(
            SUM(CASE WHEN a.status IN ('Present', 'Late') THEN 1 ELSE 0 END)
            / COUNT(*) * 100
        , 2) AS attendance_rate
    FROM attendances  AS a
    JOIN classes      AS c   ON c.id   = a.class_id
    JOIN subjects     AS sub ON sub.id = c.subject_id
    JOIN instructors  AS i   ON i.id   = c.instructor_id
    GROUP BY c.id, sub.subject_code, sub.subject_name, c.section,
             i.first_name, i.last_name
    ORDER BY sub.subject_code;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 19: Department attendance report
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetDepartmentReport()
BEGIN
    SELECT
        d.department_code,
        d.department_name,
        COUNT(DISTINCT s.id) AS student_count,
        COUNT(*)             AS total_records,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
        ROUND(
            SUM(CASE WHEN a.status IN ('Present', 'Late') THEN 1 ELSE 0 END)
            / COUNT(*) * 100
        , 2) AS attendance_rate
    FROM attendances AS a
    JOIN students    AS s ON s.id = a.student_id
    JOIN departments AS d ON d.id = s.department_id
    GROUP BY d.id, d.department_code, d.department_name
    ORDER BY d.department_code;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 20: Monthly attendance report
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetMonthlyReport(
    IN p_month INT,
    IN p_year  INT
)
BEGIN
    SELECT
        a.attendance_date,
        DAYNAME(a.attendance_date) AS day_name,
        COUNT(*) AS total_records,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
        ROUND(
            SUM(CASE WHEN a.status IN ('Present', 'Late') THEN 1 ELSE 0 END)
            / COUNT(*) * 100
        , 2) AS attendance_rate
    FROM attendances AS a
    WHERE MONTH(a.attendance_date) = p_month
      AND YEAR(a.attendance_date)  = p_year
    GROUP BY a.attendance_date
    ORDER BY a.attendance_date;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 21: Students below the minimum attendance rate
-- ----------------------------------------------------------------------------
-- HAVING filters on the computed rate. WHERE cannot do that, because WHERE
-- runs before GROUP BY and the rate does not exist yet at that point.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetLowAttendance(
    IN p_minimum_rate DECIMAL(5,2)
)
BEGIN
    SELECT
        s.student_number,
        CONCAT(s.last_name, ', ', s.first_name) AS student_name,
        sub.subject_code,
        c.section,
        COUNT(*) AS total_sessions,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
        ROUND(
            SUM(CASE WHEN a.status IN ('Present', 'Late') THEN 1 ELSE 0 END)
            / COUNT(*) * 100
        , 2) AS attendance_rate
    FROM attendances AS a
    JOIN students    AS s   ON s.id   = a.student_id
    JOIN classes     AS c   ON c.id   = a.class_id
    JOIN subjects    AS sub ON sub.id = c.subject_id
    GROUP BY s.id, s.student_number, s.last_name, s.first_name,
             sub.subject_code, c.section
    HAVING COUNT(*) >= 3
       AND attendance_rate < p_minimum_rate
    ORDER BY attendance_rate;
END $$

DELIMITER ;



-- ############################################################################
-- 4. STORED PROCEDURES (CRUD AND TRANSACTIONS)
-- ############################################################################


-- ============================================================================
-- 4.1 - STUDENT CRUD PROCEDURES
-- ============================================================================

DELIMITER $$

-- ----------------------------------------------------------------------------
-- PROCEDURE 5: Add a new student (creates the login account too)
-- ----------------------------------------------------------------------------
-- p_password must ALREADY be a bcrypt hash. Generate it in PHP with
-- Hash::make('password'), then pass the result here. SQL cannot bcrypt.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_AddStudent(
    IN p_student_number VARCHAR(30),
    IN p_first_name     VARCHAR(100),
    IN p_middle_name    VARCHAR(100),
    IN p_last_name      VARCHAR(100),
    IN p_email          VARCHAR(150),
    IN p_phone          VARCHAR(30),
    IN p_department_id  BIGINT,
    IN p_year_level     VARCHAR(20),
    IN p_section        VARCHAR(20),
    IN p_password       VARCHAR(255)
)
BEGIN
    DECLARE v_user_id BIGINT;

    -- Step 1: create the login account
    INSERT INTO users (name, email, password, role, created_at, updated_at)
    VALUES (
        CONCAT(p_first_name, ' ', p_last_name),
        p_email,
        p_password,
        'student',
        NOW(),
        NOW()
    );

    -- LAST_INSERT_ID() returns the id of the row we just inserted
    SET v_user_id = LAST_INSERT_ID();

    -- Step 2: create the student profile linked to that account
    INSERT INTO students (
        user_id, student_number, first_name, middle_name, last_name,
        email, phone, department_id, year_level, section, status,
        created_at, updated_at
    )
    VALUES (
        v_user_id, p_student_number, p_first_name, p_middle_name, p_last_name,
        p_email, p_phone, p_department_id, p_year_level, p_section, 'Active',
        NOW(), NOW()
    );

    -- Show what was created
    SELECT
        v_user_id       AS new_user_id,
        LAST_INSERT_ID() AS new_student_id,
        p_student_number AS student_number,
        'Student added successfully' AS message;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 6: Update an existing student
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_UpdateStudent(
    IN p_student_id     BIGINT,
    IN p_first_name     VARCHAR(100),
    IN p_middle_name    VARCHAR(100),
    IN p_last_name      VARCHAR(100),
    IN p_email          VARCHAR(150),
    IN p_phone          VARCHAR(30),
    IN p_department_id  BIGINT,
    IN p_year_level     VARCHAR(20),
    IN p_section        VARCHAR(20),
    IN p_status         VARCHAR(20)
)
BEGIN
    DECLARE v_user_id BIGINT;

    -- Find the linked login account
    SELECT user_id INTO v_user_id
    FROM students
    WHERE id = p_student_id;

    -- Update the student profile
    UPDATE students
    SET first_name    = p_first_name,
        middle_name   = p_middle_name,
        last_name     = p_last_name,
        email         = p_email,
        phone         = p_phone,
        department_id = p_department_id,
        year_level    = p_year_level,
        section       = p_section,
        status        = p_status,
        updated_at    = NOW()
    WHERE id = p_student_id;

    -- Keep the login account in sync
    UPDATE users
    SET name       = CONCAT(p_first_name, ' ', p_last_name),
        email      = p_email,
        updated_at = NOW()
    WHERE id = v_user_id;

    SELECT 'Student updated successfully' AS message;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 7: Delete a student
-- ----------------------------------------------------------------------------
-- Deleting the user cascades to the student row, and from there to the
-- student's enrollments and attendances. We delete them explicitly first
-- so the order is clear and easy to explain.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_DeleteStudent(
    IN p_student_id BIGINT
)
BEGIN
    DECLARE v_user_id BIGINT;

    SELECT user_id INTO v_user_id
    FROM students
    WHERE id = p_student_id;

    -- Child rows first, to avoid foreign key errors
    DELETE FROM attendances  WHERE student_id = p_student_id;
    DELETE FROM enrollments  WHERE student_id = p_student_id;
    DELETE FROM students     WHERE id         = p_student_id;
    DELETE FROM users        WHERE id         = v_user_id;

    SELECT 'Student deleted successfully' AS message;
END $$

DELIMITER ;


-- ============================================================================
-- 4.2 - ENROLLMENT PROCEDURES
-- ============================================================================

DELIMITER $$

-- ----------------------------------------------------------------------------
-- PROCEDURE 8: Enroll a student in a class
-- ----------------------------------------------------------------------------
-- Checks for an existing enrollment first, because the table has a
-- UNIQUE (class_id, student_id) key that would reject a duplicate.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_EnrollStudent(
    IN p_class_id   BIGINT,
    IN p_student_id BIGINT
)
BEGIN
    DECLARE v_existing INT DEFAULT 0;

    SELECT COUNT(*) INTO v_existing
    FROM enrollments
    WHERE class_id = p_class_id AND student_id = p_student_id;

    IF v_existing > 0 THEN
        SELECT 'Student is already enrolled in this class' AS message;
    ELSE
        INSERT INTO enrollments (class_id, student_id, enrollment_status, created_at, updated_at)
        VALUES (p_class_id, p_student_id, 'Enrolled', NOW(), NOW());

        SELECT 'Student enrolled successfully' AS message;
    END IF;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 9: Drop a student from a class
-- ----------------------------------------------------------------------------
-- Sets the status to Dropped instead of deleting, so the attendance
-- history stays intact.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_DropStudent(
    IN p_class_id   BIGINT,
    IN p_student_id BIGINT
)
BEGIN
    UPDATE enrollments
    SET enrollment_status = 'Dropped',
        updated_at        = NOW()
    WHERE class_id = p_class_id AND student_id = p_student_id;

    SELECT 'Student dropped from the class' AS message;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 10: List everyone enrolled in a class
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_GetClassRoster(
    IN p_class_id BIGINT
)
BEGIN
    SELECT
        s.student_number,
        CONCAT(s.last_name, ', ', s.first_name) AS student_name,
        d.department_code,
        s.year_level,
        s.section,
        e.enrollment_status
    FROM enrollments AS e
    JOIN students    AS s ON s.id = e.student_id
    JOIN departments AS d ON d.id = s.department_id
    WHERE e.class_id = p_class_id
    ORDER BY s.last_name;
END $$

DELIMITER ;


-- ============================================================================
-- 4.3 - ATTENDANCE AND TRANSACTION PROCEDURES
-- ============================================================================

DELIMITER $$

-- ----------------------------------------------------------------------------
-- PROCEDURE 11: Open an attendance session
-- ----------------------------------------------------------------------------
-- p_qr_token must be generated by the application (a 5-character
-- uppercase/digit code -- see AttendanceService::generateToken() in Laravel).
-- SQL should not generate security tokens.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_OpenSession(
    IN p_class_id     BIGINT,
    IN p_session_date DATE,
    IN p_qr_token     VARCHAR(64)
)
BEGIN
    DECLARE v_existing   INT  DEFAULT 0;
    DECLARE v_start_time TIME;

    -- Take the start time from the class schedule
    SELECT start_time INTO v_start_time
    FROM classes
    WHERE id = p_class_id;

    SELECT COUNT(*) INTO v_existing
    FROM attendance_sessions
    WHERE class_id = p_class_id AND session_date = p_session_date;

    IF v_existing > 0 THEN
        -- Reopen the existing session with a fresh token
        UPDATE attendance_sessions
        SET qr_token   = p_qr_token,
            end_time   = NULL,
            status     = 'Open',
            updated_at = NOW()
        WHERE class_id = p_class_id AND session_date = p_session_date;

        SELECT 'Existing session reopened with a new QR token' AS message;
    ELSE
        INSERT INTO attendance_sessions
            (class_id, qr_token, session_date, start_time, status, created_at, updated_at)
        VALUES
            (p_class_id, p_qr_token, p_session_date, v_start_time, 'Open', NOW(), NOW());

        SELECT 'New session opened' AS message;
    END IF;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 12: Close an attendance session
-- ----------------------------------------------------------------------------
-- Setting qr_token to NULL is what makes the QR code expire. Because MySQL
-- allows many NULLs in a UNIQUE column, every closed session can hold NULL.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_CloseSession(
    IN p_session_id BIGINT
)
BEGIN
    UPDATE attendance_sessions
    SET status     = 'Closed',
        qr_token   = NULL,
        end_time   = CURTIME(),
        updated_at = NOW()
    WHERE id = p_session_id;

    SELECT 'Session closed and QR token expired' AS message;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 13: Record one attendance entry
-- ----------------------------------------------------------------------------
-- Works out Present or Late by comparing the check-in time against the
-- class start time plus the grace period. This is the same rule the
-- application uses in ClassModel::determineStatus().
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_RecordAttendance(
    IN p_session_id     BIGINT,
    IN p_student_id     BIGINT,
    IN p_time_in        TIME,
    IN p_grace_minutes  INT,
    IN p_recorded_by    BIGINT
)
BEGIN
    DECLARE v_class_id     BIGINT;
    DECLARE v_date         DATE;
    DECLARE v_start_time   TIME;
    DECLARE v_cutoff       TIME;
    DECLARE v_status       VARCHAR(10);
    DECLARE v_remarks      VARCHAR(255);
    DECLARE v_existing     INT DEFAULT 0;
    DECLARE v_enrolled     INT DEFAULT 0;

    -- Get the session details
    SELECT class_id, session_date
    INTO v_class_id, v_date
    FROM attendance_sessions
    WHERE id = p_session_id;

    -- Get the class start time
    SELECT start_time INTO v_start_time
    FROM classes
    WHERE id = v_class_id;

    -- Check enrollment first
    SELECT COUNT(*) INTO v_enrolled
    FROM enrollments
    WHERE class_id = v_class_id
      AND student_id = p_student_id
      AND enrollment_status = 'Enrolled';

    IF v_enrolled = 0 THEN
        SELECT 'This student is not enrolled in the class' AS message;
    ELSE
        -- Check for an existing record (the UNIQUE key would reject a duplicate)
        SELECT COUNT(*) INTO v_existing
        FROM attendances
        WHERE class_id = v_class_id
          AND student_id = p_student_id
          AND attendance_date = v_date;

        -- Work out Present or Late
        SET v_cutoff = ADDTIME(v_start_time, SEC_TO_TIME(p_grace_minutes * 60));

        IF p_time_in <= v_cutoff THEN
            SET v_status  = 'Present';
            SET v_remarks = NULL;
        ELSE
            SET v_status  = 'Late';
            SET v_remarks = 'Arrived after class start time.';
        END IF;

        IF v_existing > 0 THEN
            UPDATE attendances
            SET attendance_session_id = p_session_id,
                time_in     = p_time_in,
                status      = v_status,
                remarks     = v_remarks,
                recorded_by = p_recorded_by,
                updated_at  = NOW()
            WHERE class_id = v_class_id
              AND student_id = p_student_id
              AND attendance_date = v_date;
        ELSE
            INSERT INTO attendances (
                attendance_session_id, class_id, student_id, attendance_date,
                time_in, status, remarks, recorded_by, created_at, updated_at
            )
            VALUES (
                p_session_id, v_class_id, p_student_id, v_date,
                p_time_in, v_status, v_remarks, p_recorded_by, NOW(), NOW()
            );
        END IF;

        SELECT
            v_status     AS attendance_status,
            p_time_in    AS time_in,
            v_start_time AS class_start_time,
            v_cutoff     AS late_cutoff,
            'Attendance recorded' AS message;
    END IF;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 14: Mark absentees for a session
-- ----------------------------------------------------------------------------
-- Every enrolled student with no record yet becomes Absent. The NOT IN
-- subquery is what prevents duplicate absent records.
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_MarkAbsentees(
    IN p_session_id  BIGINT,
    IN p_recorded_by BIGINT
)
BEGIN
    DECLARE v_class_id BIGINT;
    DECLARE v_date     DATE;
    DECLARE v_created  INT DEFAULT 0;

    SELECT class_id, session_date
    INTO v_class_id, v_date
    FROM attendance_sessions
    WHERE id = p_session_id;

    INSERT INTO attendances (
        attendance_session_id, class_id, student_id, attendance_date,
        time_in, status, remarks, recorded_by, created_at, updated_at
    )
    SELECT
        p_session_id,
        v_class_id,
        e.student_id,
        v_date,
        NULL,
        'Absent',
        'No check-in recorded.',
        p_recorded_by,
        NOW(),
        NOW()
    FROM enrollments AS e
    WHERE e.class_id = v_class_id
      AND e.enrollment_status = 'Enrolled'
      AND e.student_id NOT IN (
            SELECT student_id
            FROM attendances
            WHERE class_id = v_class_id
              AND attendance_date = v_date
      );

    SET v_created = ROW_COUNT();

    SELECT
        v_created AS absent_records_created,
        'Absentees marked' AS message;
END $$

DELIMITER ;


-- ============================================================================
-- 4.4 - NOTIFICATION PROCEDURES
-- ============================================================================

DELIMITER $$

-- ----------------------------------------------------------------------------
-- PROCEDURE 22: Add a notification
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_AddNotification(
    IN p_user_id BIGINT,
    IN p_title   VARCHAR(150),
    IN p_message TEXT,
    IN p_type    VARCHAR(20)
)
BEGIN
    INSERT INTO notifications
        (user_id, title, message, type, is_read, created_at, updated_at)
    VALUES
        (p_user_id, p_title, p_message, p_type, 0, NOW(), NOW());

    SELECT LAST_INSERT_ID() AS notification_id,
           'Notification created' AS message;
END $$


-- ----------------------------------------------------------------------------
-- PROCEDURE 23: Mark all of a user's notifications as read
-- ----------------------------------------------------------------------------
CREATE PROCEDURE sp_MarkAllRead(
    IN p_user_id BIGINT
)
BEGIN
    DECLARE v_updated INT DEFAULT 0;

    UPDATE notifications
    SET is_read    = 1,
        read_at    = NOW(),
        updated_at = NOW()
    WHERE user_id = p_user_id
      AND is_read = 0;

    SET v_updated = ROW_COUNT();

    SELECT v_updated AS notifications_marked_read;
END $$

DELIMITER ;



-- ############################################################################
-- 5. INSERT SAMPLE DATA
-- ############################################################################
-- 251 rows across 10 tables. Every date is fixed, never generated with
-- CURDATE(), so the reports produce identical numbers on any machine and
-- on any day.
-- ############################################################################


-- ============================================================================
-- 5.1 - SAMPLE DATA: departments
-- ============================================================================

INSERT INTO departments (id, department_code, department_name, description, created_at, updated_at) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 'Focuses on application development, networking, database management, and IT service management.', '2026-06-01 08:00:00', '2026-06-01 08:00:00'),
(2, 'BSCS', 'Bachelor of Science in Computer Science', 'Focuses on algorithms, computing theory, software engineering, and intelligent systems.', '2026-06-01 08:00:00', '2026-06-01 08:00:00'),
(3, 'BSCE', 'Bachelor of Science in Computer Engineering', 'Focuses on hardware, embedded systems, digital logic, and computer architecture.', '2026-06-01 08:00:00', '2026-06-01 08:00:00'),
(4, 'BSBA', 'Bachelor of Science in Business Administration', 'Focuses on management, marketing, finance, and business operations.', '2026-06-01 08:00:00', '2026-06-01 08:00:00');


-- ============================================================================
-- 5.2 - SAMPLE DATA: users
-- ----------------------------------------------------------------------------
-- DEVELOPMENT / DEMO ACCOUNTS ONLY.
-- Every account below uses the password:  password
-- The hash is bcrypt. Change these before storing any real student data.
--
--   Admin      -> admin@example.com      / password
--   Instructor -> instructor@example.com / password
--   Student    -> student@example.com    / password
-- ============================================================================

INSERT INTO users (id, name, email, password, role, created_at, updated_at) VALUES
-- Administrator
(1,  'System Administrator', 'admin@example.com',              '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',      '2026-06-01 08:00:00', '2026-06-01 08:00:00'),
-- Instructors
(2,  'Juan Dela Cruz',       'instructor@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', '2026-06-01 08:10:00', '2026-06-01 08:10:00'),
(3,  'Maria Santos',         'maria.santos@example.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', '2026-06-01 08:11:00', '2026-06-01 08:11:00'),
(4,  'Roberto Reyes',        'roberto.reyes@example.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', '2026-06-01 08:12:00', '2026-06-01 08:12:00'),
(5,  'Ana Bautista',         'ana.bautista@example.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', '2026-06-01 08:13:00', '2026-06-01 08:13:00'),
-- Students
(6,  'Juan Miguel Ramos',    'student@example.com',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:00:00', '2026-06-05 09:00:00'),
(7,  'Andrea Nicole Villanueva', 'andrea.villanueva@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2026-06-05 09:01:00', '2026-06-05 09:01:00'),
(8,  'Mark Anthony Delos Reyes', 'mark.delosreyes@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2026-06-05 09:02:00', '2026-06-05 09:02:00'),
(9,  'Patricia Mae Bautista','patricia.bautista@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:03:00', '2026-06-05 09:03:00'),
(10, 'John Carlo Mendoza',   'johncarlo.mendoza@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:04:00', '2026-06-05 09:04:00'),
(11, 'Kristine Joy Aguilar', 'kristine.aguilar@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:05:00', '2026-06-05 09:05:00'),
(12, 'Paolo Miguel Cruz',    'paolo.cruz@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:06:00', '2026-06-05 09:06:00'),
(13, 'Angelica Marie Torres','angelica.torres@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:07:00', '2026-06-05 09:07:00'),
(14, 'Rafael Antonio Garcia','rafael.garcia@example.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:08:00', '2026-06-05 09:08:00'),
(15, 'Bea Katrina Domingo',  'bea.domingo@example.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:09:00', '2026-06-05 09:09:00'),
(16, 'Christian Dave Navarro','christian.navarro@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:10:00', '2026-06-05 09:10:00'),
(17, 'Jasmine Rose Fernandez','jasmine.fernandez@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:11:00', '2026-06-05 09:11:00'),
(18, 'Emmanuel Jose Pascual','emmanuel.pascual@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:12:00', '2026-06-05 09:12:00'),
(19, 'Sofia Isabel Lim',     'sofia.lim@example.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:13:00', '2026-06-05 09:13:00'),
(20, 'Nathaniel Ryan Ocampo','nathaniel.ocampo@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    '2026-06-05 09:14:00', '2026-06-05 09:14:00');


-- ============================================================================
-- 5.3 - SAMPLE DATA: instructors
-- ============================================================================

INSERT INTO instructors (id, user_id, employee_number, first_name, middle_name, last_name, email, department_id, status, created_at, updated_at) VALUES
(1, 2, 'EMP-2026-001', 'Juan',    'Cruz',   'Dela Cruz', 'instructor@example.com',    1, 'Active', '2026-06-01 08:10:00', '2026-06-01 08:10:00'),
(2, 3, 'EMP-2026-002', 'Maria',   'Lopez',  'Santos',    'maria.santos@example.com',  1, 'Active', '2026-06-01 08:11:00', '2026-06-01 08:11:00'),
(3, 4, 'EMP-2026-003', 'Roberto', NULL,     'Reyes',     'roberto.reyes@example.com', 2, 'Active', '2026-06-01 08:12:00', '2026-06-01 08:12:00'),
(4, 5, 'EMP-2026-004', 'Ana',     'Rivera', 'Bautista',  'ana.bautista@example.com',  3, 'Active', '2026-06-01 08:13:00', '2026-06-01 08:13:00');


-- ============================================================================
-- 5.4 - SAMPLE DATA: students
-- ============================================================================

INSERT INTO students (id, user_id, student_number, first_name, middle_name, last_name, email, phone, department_id, year_level, section, status, created_at, updated_at) VALUES
(1,  6,  '2026-00001', 'Juan Miguel',     'Reyes', 'Ramos',       'student@example.com',           '09171234001', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:00:00', '2026-06-05 09:00:00'),
(2,  7,  '2026-00002', 'Andrea Nicole',   'Cruz',  'Villanueva',  'andrea.villanueva@example.com', '09171234002', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:01:00', '2026-06-05 09:01:00'),
(3,  8,  '2026-00003', 'Mark Anthony',    NULL,    'Delos Reyes', 'mark.delosreyes@example.com',   '09171234003', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:02:00', '2026-06-05 09:02:00'),
(4,  9,  '2026-00004', 'Patricia Mae',    'Lim',   'Bautista',    'patricia.bautista@example.com', '09171234004', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:03:00', '2026-06-05 09:03:00'),
(5,  10, '2026-00005', 'John Carlo',      'Santos','Mendoza',     'johncarlo.mendoza@example.com', '09171234005', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:04:00', '2026-06-05 09:04:00'),
(6,  11, '2026-00006', 'Kristine Joy',    NULL,    'Aguilar',     'kristine.aguilar@example.com',  '09171234006', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:05:00', '2026-06-05 09:05:00'),
(7,  12, '2026-00007', 'Paolo Miguel',    'Tan',   'Cruz',        'paolo.cruz@example.com',        '09171234007', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:06:00', '2026-06-05 09:06:00'),
(8,  13, '2026-00008', 'Angelica Marie',  'Reyes', 'Torres',      'angelica.torres@example.com',   '09171234008', 1, '3rd Year', 'A', 'Active', '2026-06-05 09:07:00', '2026-06-05 09:07:00'),
(9,  14, '2026-00009', 'Rafael Antonio',  NULL,    'Garcia',      'rafael.garcia@example.com',     '09171234009', 1, '2nd Year', 'A', 'Active', '2026-06-05 09:08:00', '2026-06-05 09:08:00'),
(10, 15, '2026-00010', 'Bea Katrina',     'Ong',   'Domingo',     'bea.domingo@example.com',       '09171234010', 1, '2nd Year', 'A', 'Active', '2026-06-05 09:09:00', '2026-06-05 09:09:00'),
(11, 16, '2026-00011', 'Christian Dave',  'Uy',    'Navarro',     'christian.navarro@example.com', '09171234011', 1, '2nd Year', 'B', 'Active', '2026-06-05 09:10:00', '2026-06-05 09:10:00'),
(12, 17, '2026-00012', 'Jasmine Rose',    NULL,    'Fernandez',   'jasmine.fernandez@example.com', '09171234012', 1, '2nd Year', 'B', 'Active', '2026-06-05 09:11:00', '2026-06-05 09:11:00'),
(13, 18, '2026-00013', 'Emmanuel Jose',   'Ramos', 'Pascual',     'emmanuel.pascual@example.com',  '09171234013', 2, '3rd Year', 'A', 'Active', '2026-06-05 09:12:00', '2026-06-05 09:12:00'),
(14, 19, '2026-00014', 'Sofia Isabel',    'Chua',  'Lim',         'sofia.lim@example.com',         '09171234014', 2, '3rd Year', 'A', 'Active', '2026-06-05 09:13:00', '2026-06-05 09:13:00'),
(15, 20, '2026-00015', 'Nathaniel Ryan',  NULL,    'Ocampo',      'nathaniel.ocampo@example.com',  '09171234015', 3, '1st Year', 'A', 'Active', '2026-06-05 09:14:00', '2026-06-05 09:14:00');


-- ============================================================================
-- 5.5 - SAMPLE DATA: subjects
-- ============================================================================

INSERT INTO subjects (id, subject_code, subject_name, description, units, department_id, created_at, updated_at) VALUES
(1, 'ITP104', 'Information Management 2',             'Advanced database design, SQL, stored procedures, and data administration.', 3, 1, '2026-06-02 08:00:00', '2026-06-02 08:00:00'),
(2, 'ITP111', 'System Administration and Maintenance','Server setup, user management, backup and recovery, and system monitoring.', 3, 1, '2026-06-02 08:00:00', '2026-06-02 08:00:00'),
(3, 'ITP102', 'Web Systems and Technologies',         'Front-end and back-end web development using modern frameworks.',            3, 1, '2026-06-02 08:00:00', '2026-06-02 08:00:00'),
(4, 'ITP105', 'Object Oriented Programming',          'Classes, inheritance, polymorphism, and design patterns.',                   3, 1, '2026-06-02 08:00:00', '2026-06-02 08:00:00'),
(5, 'CS201',  'Data Structures and Algorithms',       'Arrays, linked lists, trees, graphs, sorting, and searching.',               3, 2, '2026-06-02 08:00:00', '2026-06-02 08:00:00'),
(6, 'CS202',  'Discrete Structures',                  'Logic, set theory, combinatorics, and graph theory.',                        3, 2, '2026-06-02 08:00:00', '2026-06-02 08:00:00'),
(7, 'CPE101', 'Digital Logic Design',                 'Boolean algebra, logic gates, flip-flops, and sequential circuits.',         3, 3, '2026-06-02 08:00:00', '2026-06-02 08:00:00'),
(8, 'BA101',  'Principles of Management',             'Planning, organizing, leading, and controlling in organizations.',           3, 4, '2026-06-02 08:00:00', '2026-06-02 08:00:00');


-- ============================================================================
-- 5.6 - SAMPLE DATA: classes
-- ============================================================================

INSERT INTO classes (id, subject_id, instructor_id, school_year, semester, section, room, schedule_day, start_time, end_time, status, created_at, updated_at) VALUES
(1, 1, 1, '2026-2027', '1st Semester', '3A', 'Room 301', 'Monday',    '08:00:00', '11:00:00', 'Active', '2026-06-10 08:00:00', '2026-06-10 08:00:00'),
(2, 2, 1, '2026-2027', '1st Semester', '3A', 'Room 302', 'Tuesday',   '13:00:00', '16:00:00', 'Active', '2026-06-10 08:00:00', '2026-06-10 08:00:00'),
(3, 3, 2, '2026-2027', '1st Semester', '2A', 'Room 303', 'Wednesday', '08:00:00', '11:00:00', 'Active', '2026-06-10 08:00:00', '2026-06-10 08:00:00'),
(4, 4, 2, '2026-2027', '1st Semester', '2B', 'Lab 1',    'Thursday',  '10:00:00', '13:00:00', 'Active', '2026-06-10 08:00:00', '2026-06-10 08:00:00'),
(5, 5, 3, '2026-2027', '1st Semester', '3A', 'Room 401', 'Monday',    '13:00:00', '16:00:00', 'Active', '2026-06-10 08:00:00', '2026-06-10 08:00:00'),
(6, 7, 4, '2026-2027', '1st Semester', '1A', 'Lab 2',    'Friday',    '08:00:00', '11:00:00', 'Active', '2026-06-10 08:00:00', '2026-06-10 08:00:00');


-- ============================================================================
-- 5.7 - SAMPLE DATA: enrollments
-- ----------------------------------------------------------------------------
-- 23 enrollments total across 6 classes.
-- ============================================================================

INSERT INTO enrollments (class_id, student_id, enrollment_status, created_at, updated_at) VALUES
-- Class 1: ITP104 - 3A (8 students)
(1, 1, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
(1, 2, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
(1, 3, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
(1, 4, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
(1, 5, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
(1, 6, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
(1, 7, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
(1, 8, 'Enrolled', '2026-06-15 09:00:00', '2026-06-15 09:00:00'),
-- Class 2: ITP111 - 3A (8 students)
(2, 1, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
(2, 2, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
(2, 3, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
(2, 4, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
(2, 5, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
(2, 6, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
(2, 7, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
(2, 8, 'Enrolled', '2026-06-15 09:05:00', '2026-06-15 09:05:00'),
-- Class 3: ITP102 - 2A (2 students)
(3, 9,  'Enrolled', '2026-06-15 09:10:00', '2026-06-15 09:10:00'),
(3, 10, 'Enrolled', '2026-06-15 09:10:00', '2026-06-15 09:10:00'),
-- Class 4: ITP105 - 2B (2 students)
(4, 11, 'Enrolled', '2026-06-15 09:15:00', '2026-06-15 09:15:00'),
(4, 12, 'Enrolled', '2026-06-15 09:15:00', '2026-06-15 09:15:00'),
-- Class 5: CS201 - 3A (2 students)
(5, 13, 'Enrolled', '2026-06-15 09:20:00', '2026-06-15 09:20:00'),
(5, 14, 'Enrolled', '2026-06-15 09:20:00', '2026-06-15 09:20:00'),
-- Class 6: CPE101 - 1A (1 student)
(6, 15, 'Enrolled', '2026-06-15 09:25:00', '2026-06-15 09:25:00');


-- ============================================================================
-- 5.8 - SAMPLE DATA: attendance_sessions
-- ----------------------------------------------------------------------------
-- 24 CLOSED sessions (qr_token is NULL - the QR codes have expired)
--  1 OPEN session on 2026-08-17 (has a live qr_token)
--
-- All session dates fall on the correct schedule_day of the class:
--   Class 1 and 5 -> Mondays     Class 2 -> Tuesdays    Class 3 -> Wednesdays
-- ============================================================================

INSERT INTO attendance_sessions (id, class_id, qr_token, session_date, start_time, end_time, status, created_at, updated_at) VALUES
-- Class 1: ITP104 - 3A - Mondays 8:00 AM
(1,  1, NULL, '2026-07-06', '08:00:00', '11:00:00', 'Closed', '2026-07-06 07:50:00', '2026-07-06 11:00:00'),
(2,  1, NULL, '2026-07-13', '08:00:00', '11:00:00', 'Closed', '2026-07-13 07:50:00', '2026-07-13 11:00:00'),
(3,  1, NULL, '2026-07-20', '08:00:00', '11:00:00', 'Closed', '2026-07-20 07:50:00', '2026-07-20 11:00:00'),
(4,  1, NULL, '2026-07-27', '08:00:00', '11:00:00', 'Closed', '2026-07-27 07:50:00', '2026-07-27 11:00:00'),
(5,  1, NULL, '2026-08-03', '08:00:00', '11:00:00', 'Closed', '2026-08-03 07:50:00', '2026-08-03 11:00:00'),
(6,  1, NULL, '2026-08-10', '08:00:00', '11:00:00', 'Closed', '2026-08-10 07:50:00', '2026-08-10 11:00:00'),
-- Class 1: OPEN session - this one still has a live QR token
(7,  1, 'K7X3M', '2026-08-17', '08:00:00', NULL, 'Open', '2026-08-17 07:48:00', '2026-08-17 07:48:00'),
-- Class 2: ITP111 - 3A - Tuesdays 1:00 PM
(8,  2, NULL, '2026-07-07', '13:00:00', '16:00:00', 'Closed', '2026-07-07 12:50:00', '2026-07-07 16:00:00'),
(9,  2, NULL, '2026-07-14', '13:00:00', '16:00:00', 'Closed', '2026-07-14 12:50:00', '2026-07-14 16:00:00'),
(10, 2, NULL, '2026-07-21', '13:00:00', '16:00:00', 'Closed', '2026-07-21 12:50:00', '2026-07-21 16:00:00'),
(11, 2, NULL, '2026-07-28', '13:00:00', '16:00:00', 'Closed', '2026-07-28 12:50:00', '2026-07-28 16:00:00'),
(12, 2, NULL, '2026-08-04', '13:00:00', '16:00:00', 'Closed', '2026-08-04 12:50:00', '2026-08-04 16:00:00'),
(13, 2, NULL, '2026-08-11', '13:00:00', '16:00:00', 'Closed', '2026-08-11 12:50:00', '2026-08-11 16:00:00'),
-- Class 3: ITP102 - 2A - Wednesdays 8:00 AM
(14, 3, NULL, '2026-07-08', '08:00:00', '11:00:00', 'Closed', '2026-07-08 07:45:00', '2026-07-08 11:00:00'),
(15, 3, NULL, '2026-07-15', '08:00:00', '11:00:00', 'Closed', '2026-07-15 07:45:00', '2026-07-15 11:00:00'),
(16, 3, NULL, '2026-07-22', '08:00:00', '11:00:00', 'Closed', '2026-07-22 07:45:00', '2026-07-22 11:00:00'),
(17, 3, NULL, '2026-07-29', '08:00:00', '11:00:00', 'Closed', '2026-07-29 07:45:00', '2026-07-29 11:00:00'),
(18, 3, NULL, '2026-08-05', '08:00:00', '11:00:00', 'Closed', '2026-08-05 07:45:00', '2026-08-05 11:00:00'),
(19, 3, NULL, '2026-08-12', '08:00:00', '11:00:00', 'Closed', '2026-08-12 07:45:00', '2026-08-12 11:00:00'),
-- Class 5: CS201 - 3A - Mondays 1:00 PM
(20, 5, NULL, '2026-07-06', '13:00:00', '16:00:00', 'Closed', '2026-07-06 12:50:00', '2026-07-06 16:00:00'),
(21, 5, NULL, '2026-07-13', '13:00:00', '16:00:00', 'Closed', '2026-07-13 12:50:00', '2026-07-13 16:00:00'),
(22, 5, NULL, '2026-07-20', '13:00:00', '16:00:00', 'Closed', '2026-07-20 12:50:00', '2026-07-20 16:00:00'),
(23, 5, NULL, '2026-07-27', '13:00:00', '16:00:00', 'Closed', '2026-07-27 12:50:00', '2026-07-27 16:00:00'),
(24, 5, NULL, '2026-08-03', '13:00:00', '16:00:00', 'Closed', '2026-08-03 12:50:00', '2026-08-03 16:00:00'),
(25, 5, NULL, '2026-08-10', '13:00:00', '16:00:00', 'Closed', '2026-08-10 12:50:00', '2026-08-10 16:00:00');


-- ============================================================================
-- 5.9 - SAMPLE DATA: attendances (Class 1 - ITP104)
-- ----------------------------------------------------------------------------
-- Class starts 08:00. Present check-in = 07:52. Late check-in = 08:14.
-- Absent rows have NULL time_in because the student never checked in.
-- recorded_by = 2 (Juan Dela Cruz, the instructor of this class)
-- ============================================================================

INSERT INTO attendances (attendance_session_id, class_id, student_id, attendance_date, time_in, status, remarks, recorded_by, created_at, updated_at) VALUES
-- 2026-07-06
(1, 1, 1, '2026-07-06', '07:52:00', 'Present', NULL,                                2, '2026-07-06 07:52:00', '2026-07-06 07:52:00'),
(1, 1, 2, '2026-07-06', '07:52:00', 'Present', NULL,                                2, '2026-07-06 07:52:00', '2026-07-06 07:52:00'),
(1, 1, 3, '2026-07-06', '07:52:00', 'Present', NULL,                                2, '2026-07-06 07:52:00', '2026-07-06 07:52:00'),
(1, 1, 4, '2026-07-06', '08:14:00', 'Late',    'Arrived after class start time.',   2, '2026-07-06 08:14:00', '2026-07-06 08:14:00'),
(1, 1, 5, '2026-07-06', '07:52:00', 'Present', NULL,                                2, '2026-07-06 07:52:00', '2026-07-06 07:52:00'),
(1, 1, 6, '2026-07-06', NULL,       'Absent',  'No check-in recorded.',             2, '2026-07-06 11:00:00', '2026-07-06 11:00:00'),
(1, 1, 7, '2026-07-06', '07:52:00', 'Present', NULL,                                2, '2026-07-06 07:52:00', '2026-07-06 07:52:00'),
(1, 1, 8, '2026-07-06', '07:52:00', 'Present', NULL,                                2, '2026-07-06 07:52:00', '2026-07-06 07:52:00'),
-- 2026-07-13
(2, 1, 1, '2026-07-13', '07:52:00', 'Present', NULL,                              2, '2026-07-13 07:52:00', '2026-07-13 07:52:00'),
(2, 1, 2, '2026-07-13', '07:52:00', 'Present', NULL,                              2, '2026-07-13 07:52:00', '2026-07-13 07:52:00'),
(2, 1, 3, '2026-07-13', '08:14:00', 'Late',    'Arrived after class start time.', 2, '2026-07-13 08:14:00', '2026-07-13 08:14:00'),
(2, 1, 4, '2026-07-13', '07:52:00', 'Present', NULL,                              2, '2026-07-13 07:52:00', '2026-07-13 07:52:00'),
(2, 1, 5, '2026-07-13', '07:52:00', 'Present', NULL,                              2, '2026-07-13 07:52:00', '2026-07-13 07:52:00'),
(2, 1, 6, '2026-07-13', '07:52:00', 'Present', NULL,                              2, '2026-07-13 07:52:00', '2026-07-13 07:52:00'),
(2, 1, 7, '2026-07-13', NULL,       'Absent',  'No check-in recorded.',           2, '2026-07-13 11:00:00', '2026-07-13 11:00:00'),
(2, 1, 8, '2026-07-13', '07:52:00', 'Present', NULL,                              2, '2026-07-13 07:52:00', '2026-07-13 07:52:00'),
-- 2026-07-20
(3, 1, 1, '2026-07-20', '07:52:00', 'Present', NULL,                              2, '2026-07-20 07:52:00', '2026-07-20 07:52:00'),
(3, 1, 2, '2026-07-20', NULL,       'Absent',  'No check-in recorded.',           2, '2026-07-20 11:00:00', '2026-07-20 11:00:00'),
(3, 1, 3, '2026-07-20', '07:52:00', 'Present', NULL,                              2, '2026-07-20 07:52:00', '2026-07-20 07:52:00'),
(3, 1, 4, '2026-07-20', '07:52:00', 'Present', NULL,                              2, '2026-07-20 07:52:00', '2026-07-20 07:52:00'),
(3, 1, 5, '2026-07-20', '08:14:00', 'Late',    'Arrived after class start time.', 2, '2026-07-20 08:14:00', '2026-07-20 08:14:00'),
(3, 1, 6, '2026-07-20', '07:52:00', 'Present', NULL,                              2, '2026-07-20 07:52:00', '2026-07-20 07:52:00'),
(3, 1, 7, '2026-07-20', '07:52:00', 'Present', NULL,                              2, '2026-07-20 07:52:00', '2026-07-20 07:52:00'),
(3, 1, 8, '2026-07-20', '07:52:00', 'Present', NULL,                              2, '2026-07-20 07:52:00', '2026-07-20 07:52:00'),
-- 2026-07-27
(4, 1, 1, '2026-07-27', '07:52:00', 'Present', NULL,                              2, '2026-07-27 07:52:00', '2026-07-27 07:52:00'),
(4, 1, 2, '2026-07-27', '07:52:00', 'Present', NULL,                              2, '2026-07-27 07:52:00', '2026-07-27 07:52:00'),
(4, 1, 3, '2026-07-27', '07:52:00', 'Present', NULL,                              2, '2026-07-27 07:52:00', '2026-07-27 07:52:00'),
(4, 1, 4, '2026-07-27', '07:52:00', 'Present', NULL,                              2, '2026-07-27 07:52:00', '2026-07-27 07:52:00'),
(4, 1, 5, '2026-07-27', '07:52:00', 'Present', NULL,                              2, '2026-07-27 07:52:00', '2026-07-27 07:52:00'),
(4, 1, 6, '2026-07-27', '08:14:00', 'Late',    'Arrived after class start time.', 2, '2026-07-27 08:14:00', '2026-07-27 08:14:00'),
(4, 1, 7, '2026-07-27', '07:52:00', 'Present', NULL,                              2, '2026-07-27 07:52:00', '2026-07-27 07:52:00'),
(4, 1, 8, '2026-07-27', NULL,       'Absent',  'No check-in recorded.',           2, '2026-07-27 11:00:00', '2026-07-27 11:00:00'),
-- 2026-08-03
(5, 1, 1, '2026-08-03', '08:14:00', 'Late',    'Arrived after class start time.', 2, '2026-08-03 08:14:00', '2026-08-03 08:14:00'),
(5, 1, 2, '2026-08-03', '07:52:00', 'Present', NULL,                              2, '2026-08-03 07:52:00', '2026-08-03 07:52:00'),
(5, 1, 3, '2026-08-03', '07:52:00', 'Present', NULL,                              2, '2026-08-03 07:52:00', '2026-08-03 07:52:00'),
(5, 1, 4, '2026-08-03', NULL,       'Absent',  'No check-in recorded.',           2, '2026-08-03 11:00:00', '2026-08-03 11:00:00'),
(5, 1, 5, '2026-08-03', '07:52:00', 'Present', NULL,                              2, '2026-08-03 07:52:00', '2026-08-03 07:52:00'),
(5, 1, 6, '2026-08-03', '07:52:00', 'Present', NULL,                              2, '2026-08-03 07:52:00', '2026-08-03 07:52:00'),
(5, 1, 7, '2026-08-03', '07:52:00', 'Present', NULL,                              2, '2026-08-03 07:52:00', '2026-08-03 07:52:00'),
(5, 1, 8, '2026-08-03', '07:52:00', 'Present', NULL,                              2, '2026-08-03 07:52:00', '2026-08-03 07:52:00'),
-- 2026-08-10
(6, 1, 1, '2026-08-10', '07:52:00', 'Present', NULL,                              2, '2026-08-10 07:52:00', '2026-08-10 07:52:00'),
(6, 1, 2, '2026-08-10', '07:52:00', 'Present', NULL,                              2, '2026-08-10 07:52:00', '2026-08-10 07:52:00'),
(6, 1, 3, '2026-08-10', NULL,       'Absent',  'No check-in recorded.',           2, '2026-08-10 11:00:00', '2026-08-10 11:00:00'),
(6, 1, 4, '2026-08-10', '07:52:00', 'Present', NULL,                              2, '2026-08-10 07:52:00', '2026-08-10 07:52:00'),
(6, 1, 5, '2026-08-10', '07:52:00', 'Present', NULL,                              2, '2026-08-10 07:52:00', '2026-08-10 07:52:00'),
(6, 1, 6, '2026-08-10', '07:52:00', 'Present', NULL,                              2, '2026-08-10 07:52:00', '2026-08-10 07:52:00'),
(6, 1, 7, '2026-08-10', '08:14:00', 'Late',    'Arrived after class start time.', 2, '2026-08-10 08:14:00', '2026-08-10 08:14:00'),
(6, 1, 8, '2026-08-10', '07:52:00', 'Present', NULL,                              2, '2026-08-10 07:52:00', '2026-08-10 07:52:00');


-- ============================================================================
-- 5.10 - SAMPLE DATA: attendances (Class 1 - OPEN session 2026-08-17)
-- ----------------------------------------------------------------------------
-- Only 4 of the 8 students have checked in so far. Student 1 is
-- deliberately NOT checked in, so a live QR scan demo produces a real
-- first-time check-in instead of a duplicate error.
-- recorded_by is NULL here because these came from QR self-scans.
-- ============================================================================

INSERT INTO attendances (attendance_session_id, class_id, student_id, attendance_date, time_in, status, remarks, recorded_by, created_at, updated_at) VALUES
(7, 1, 2, '2026-08-17', '07:54:00', 'Present', NULL,                              NULL, '2026-08-17 07:54:00', '2026-08-17 07:54:00'),
(7, 1, 3, '2026-08-17', '07:57:00', 'Present', NULL,                              NULL, '2026-08-17 07:57:00', '2026-08-17 07:57:00'),
(7, 1, 4, '2026-08-17', '08:06:00', 'Late',    'Arrived after class start time.', NULL, '2026-08-17 08:06:00', '2026-08-17 08:06:00'),
(7, 1, 5, '2026-08-17', '07:59:00', 'Present', NULL,                              NULL, '2026-08-17 07:59:00', '2026-08-17 07:59:00');


-- ============================================================================
-- 5.11 - SAMPLE DATA: attendances (Class 2 - ITP111)
-- ----------------------------------------------------------------------------
-- Class starts 13:00. Present = 12:55. Late = 13:18.
-- ============================================================================

INSERT INTO attendances (attendance_session_id, class_id, student_id, attendance_date, time_in, status, remarks, recorded_by, created_at, updated_at) VALUES
-- 2026-07-07
(8,  2, 1, '2026-07-07', '12:55:00', 'Present', NULL,                              2, '2026-07-07 12:55:00', '2026-07-07 12:55:00'),
(8,  2, 2, '2026-07-07', '12:55:00', 'Present', NULL,                              2, '2026-07-07 12:55:00', '2026-07-07 12:55:00'),
(8,  2, 3, '2026-07-07', NULL,       'Absent',  'No check-in recorded.',           2, '2026-07-07 16:00:00', '2026-07-07 16:00:00'),
(8,  2, 4, '2026-07-07', '12:55:00', 'Present', NULL,                              2, '2026-07-07 12:55:00', '2026-07-07 12:55:00'),
(8,  2, 5, '2026-07-07', '12:55:00', 'Present', NULL,                              2, '2026-07-07 12:55:00', '2026-07-07 12:55:00'),
(8,  2, 6, '2026-07-07', '12:55:00', 'Present', NULL,                              2, '2026-07-07 12:55:00', '2026-07-07 12:55:00'),
(8,  2, 7, '2026-07-07', '12:55:00', 'Present', NULL,                              2, '2026-07-07 12:55:00', '2026-07-07 12:55:00'),
(8,  2, 8, '2026-07-07', '13:18:00', 'Late',    'Arrived after class start time.', 2, '2026-07-07 13:18:00', '2026-07-07 13:18:00'),
-- 2026-07-14
(9,  2, 1, '2026-07-14', '12:55:00', 'Present', NULL,                              2, '2026-07-14 12:55:00', '2026-07-14 12:55:00'),
(9,  2, 2, '2026-07-14', '13:18:00', 'Late',    'Arrived after class start time.', 2, '2026-07-14 13:18:00', '2026-07-14 13:18:00'),
(9,  2, 3, '2026-07-14', '12:55:00', 'Present', NULL,                              2, '2026-07-14 12:55:00', '2026-07-14 12:55:00'),
(9,  2, 4, '2026-07-14', '12:55:00', 'Present', NULL,                              2, '2026-07-14 12:55:00', '2026-07-14 12:55:00'),
(9,  2, 5, '2026-07-14', NULL,       'Absent',  'No check-in recorded.',           2, '2026-07-14 16:00:00', '2026-07-14 16:00:00'),
(9,  2, 6, '2026-07-14', '12:55:00', 'Present', NULL,                              2, '2026-07-14 12:55:00', '2026-07-14 12:55:00'),
(9,  2, 7, '2026-07-14', '12:55:00', 'Present', NULL,                              2, '2026-07-14 12:55:00', '2026-07-14 12:55:00'),
(9,  2, 8, '2026-07-14', '12:55:00', 'Present', NULL,                              2, '2026-07-14 12:55:00', '2026-07-14 12:55:00'),
-- 2026-07-21
(10, 2, 1, '2026-07-21', '12:55:00', 'Present', NULL,                              2, '2026-07-21 12:55:00', '2026-07-21 12:55:00'),
(10, 2, 2, '2026-07-21', '12:55:00', 'Present', NULL,                              2, '2026-07-21 12:55:00', '2026-07-21 12:55:00'),
(10, 2, 3, '2026-07-21', '12:55:00', 'Present', NULL,                              2, '2026-07-21 12:55:00', '2026-07-21 12:55:00'),
(10, 2, 4, '2026-07-21', '13:18:00', 'Late',    'Arrived after class start time.', 2, '2026-07-21 13:18:00', '2026-07-21 13:18:00'),
(10, 2, 5, '2026-07-21', '12:55:00', 'Present', NULL,                              2, '2026-07-21 12:55:00', '2026-07-21 12:55:00'),
(10, 2, 6, '2026-07-21', '12:55:00', 'Present', NULL,                              2, '2026-07-21 12:55:00', '2026-07-21 12:55:00'),
(10, 2, 7, '2026-07-21', NULL,       'Absent',  'No check-in recorded.',           2, '2026-07-21 16:00:00', '2026-07-21 16:00:00'),
(10, 2, 8, '2026-07-21', '12:55:00', 'Present', NULL,                              2, '2026-07-21 12:55:00', '2026-07-21 12:55:00'),
-- 2026-07-28
(11, 2, 1, '2026-07-28', NULL,       'Absent',  'No check-in recorded.',           2, '2026-07-28 16:00:00', '2026-07-28 16:00:00'),
(11, 2, 2, '2026-07-28', '12:55:00', 'Present', NULL,                              2, '2026-07-28 12:55:00', '2026-07-28 12:55:00'),
(11, 2, 3, '2026-07-28', '12:55:00', 'Present', NULL,                              2, '2026-07-28 12:55:00', '2026-07-28 12:55:00'),
(11, 2, 4, '2026-07-28', '12:55:00', 'Present', NULL,                              2, '2026-07-28 12:55:00', '2026-07-28 12:55:00'),
(11, 2, 5, '2026-07-28', '12:55:00', 'Present', NULL,                              2, '2026-07-28 12:55:00', '2026-07-28 12:55:00'),
(11, 2, 6, '2026-07-28', '13:18:00', 'Late',    'Arrived after class start time.', 2, '2026-07-28 13:18:00', '2026-07-28 13:18:00'),
(11, 2, 7, '2026-07-28', '12:55:00', 'Present', NULL,                              2, '2026-07-28 12:55:00', '2026-07-28 12:55:00'),
(11, 2, 8, '2026-07-28', '12:55:00', 'Present', NULL,                              2, '2026-07-28 12:55:00', '2026-07-28 12:55:00'),
-- 2026-08-04
(12, 2, 1, '2026-08-04', '12:55:00', 'Present', NULL,                              2, '2026-08-04 12:55:00', '2026-08-04 12:55:00'),
(12, 2, 2, '2026-08-04', '12:55:00', 'Present', NULL,                              2, '2026-08-04 12:55:00', '2026-08-04 12:55:00'),
(12, 2, 3, '2026-08-04', '13:18:00', 'Late',    'Arrived after class start time.', 2, '2026-08-04 13:18:00', '2026-08-04 13:18:00'),
(12, 2, 4, '2026-08-04', '12:55:00', 'Present', NULL,                              2, '2026-08-04 12:55:00', '2026-08-04 12:55:00'),
(12, 2, 5, '2026-08-04', '12:55:00', 'Present', NULL,                              2, '2026-08-04 12:55:00', '2026-08-04 12:55:00'),
(12, 2, 6, '2026-08-04', '12:55:00', 'Present', NULL,                              2, '2026-08-04 12:55:00', '2026-08-04 12:55:00'),
(12, 2, 7, '2026-08-04', '12:55:00', 'Present', NULL,                              2, '2026-08-04 12:55:00', '2026-08-04 12:55:00'),
(12, 2, 8, '2026-08-04', NULL,       'Absent',  'No check-in recorded.',           2, '2026-08-04 16:00:00', '2026-08-04 16:00:00'),
-- 2026-08-11
(13, 2, 1, '2026-08-11', '12:55:00', 'Present', NULL,                              2, '2026-08-11 12:55:00', '2026-08-11 12:55:00'),
(13, 2, 2, '2026-08-11', NULL,       'Absent',  'No check-in recorded.',           2, '2026-08-11 16:00:00', '2026-08-11 16:00:00'),
(13, 2, 3, '2026-08-11', '12:55:00', 'Present', NULL,                              2, '2026-08-11 12:55:00', '2026-08-11 12:55:00'),
(13, 2, 4, '2026-08-11', '12:55:00', 'Present', NULL,                              2, '2026-08-11 12:55:00', '2026-08-11 12:55:00'),
(13, 2, 5, '2026-08-11', '13:18:00', 'Late',    'Arrived after class start time.', 2, '2026-08-11 13:18:00', '2026-08-11 13:18:00'),
(13, 2, 6, '2026-08-11', '12:55:00', 'Present', NULL,                              2, '2026-08-11 12:55:00', '2026-08-11 12:55:00'),
(13, 2, 7, '2026-08-11', '12:55:00', 'Present', NULL,                              2, '2026-08-11 12:55:00', '2026-08-11 12:55:00'),
(13, 2, 8, '2026-08-11', '12:55:00', 'Present', NULL,                              2, '2026-08-11 12:55:00', '2026-08-11 12:55:00');


-- ============================================================================
-- 5.12 - SAMPLE DATA: attendances (Class 3 - ITP102)
-- ----------------------------------------------------------------------------
-- Class starts 08:00. Present = 07:50. Late = 08:09.
-- recorded_by = 3 (Maria Santos)
-- ============================================================================

INSERT INTO attendances (attendance_session_id, class_id, student_id, attendance_date, time_in, status, remarks, recorded_by, created_at, updated_at) VALUES
(14, 3, 9,  '2026-07-08', '07:50:00', 'Present', NULL,                              3, '2026-07-08 07:50:00', '2026-07-08 07:50:00'),
(14, 3, 10, '2026-07-08', '07:50:00', 'Present', NULL,                              3, '2026-07-08 07:50:00', '2026-07-08 07:50:00'),
(15, 3, 9,  '2026-07-15', '07:50:00', 'Present', NULL,                              3, '2026-07-15 07:50:00', '2026-07-15 07:50:00'),
(15, 3, 10, '2026-07-15', '08:09:00', 'Late',    'Arrived after class start time.', 3, '2026-07-15 08:09:00', '2026-07-15 08:09:00'),
(16, 3, 9,  '2026-07-22', NULL,       'Absent',  'No check-in recorded.',           3, '2026-07-22 11:00:00', '2026-07-22 11:00:00'),
(16, 3, 10, '2026-07-22', '07:50:00', 'Present', NULL,                              3, '2026-07-22 07:50:00', '2026-07-22 07:50:00'),
(17, 3, 9,  '2026-07-29', '07:50:00', 'Present', NULL,                              3, '2026-07-29 07:50:00', '2026-07-29 07:50:00'),
(17, 3, 10, '2026-07-29', '07:50:00', 'Present', NULL,                              3, '2026-07-29 07:50:00', '2026-07-29 07:50:00'),
(18, 3, 9,  '2026-08-05', '08:09:00', 'Late',    'Arrived after class start time.', 3, '2026-08-05 08:09:00', '2026-08-05 08:09:00'),
(18, 3, 10, '2026-08-05', '07:50:00', 'Present', NULL,                              3, '2026-08-05 07:50:00', '2026-08-05 07:50:00'),
(19, 3, 9,  '2026-08-12', '07:50:00', 'Present', NULL,                              3, '2026-08-12 07:50:00', '2026-08-12 07:50:00'),
(19, 3, 10, '2026-08-12', '07:50:00', 'Present', NULL,                              3, '2026-08-12 07:50:00', '2026-08-12 07:50:00');


-- ============================================================================
-- 5.13 - SAMPLE DATA: attendances (Class 5 - CS201)
-- ----------------------------------------------------------------------------
-- Class starts 13:00. Present = 12:58. Late = 13:22.
-- recorded_by = 4 (Roberto Reyes)
-- ============================================================================

INSERT INTO attendances (attendance_session_id, class_id, student_id, attendance_date, time_in, status, remarks, recorded_by, created_at, updated_at) VALUES
(20, 5, 13, '2026-07-06', '12:58:00', 'Present', NULL,                              4, '2026-07-06 12:58:00', '2026-07-06 12:58:00'),
(20, 5, 14, '2026-07-06', '12:58:00', 'Present', NULL,                              4, '2026-07-06 12:58:00', '2026-07-06 12:58:00'),
(21, 5, 13, '2026-07-13', '13:22:00', 'Late',    'Arrived after class start time.', 4, '2026-07-13 13:22:00', '2026-07-13 13:22:00'),
(21, 5, 14, '2026-07-13', '12:58:00', 'Present', NULL,                              4, '2026-07-13 12:58:00', '2026-07-13 12:58:00'),
(22, 5, 13, '2026-07-20', '12:58:00', 'Present', NULL,                              4, '2026-07-20 12:58:00', '2026-07-20 12:58:00'),
(22, 5, 14, '2026-07-20', NULL,       'Absent',  'No check-in recorded.',           4, '2026-07-20 16:00:00', '2026-07-20 16:00:00'),
(23, 5, 13, '2026-07-27', '12:58:00', 'Present', NULL,                              4, '2026-07-27 12:58:00', '2026-07-27 12:58:00'),
(23, 5, 14, '2026-07-27', '12:58:00', 'Present', NULL,                              4, '2026-07-27 12:58:00', '2026-07-27 12:58:00'),
(24, 5, 13, '2026-08-03', '12:58:00', 'Present', NULL,                              4, '2026-08-03 12:58:00', '2026-08-03 12:58:00'),
(24, 5, 14, '2026-08-03', '13:22:00', 'Late',    'Arrived after class start time.', 4, '2026-08-03 13:22:00', '2026-08-03 13:22:00'),
(25, 5, 13, '2026-08-10', '12:58:00', 'Present', NULL,                              4, '2026-08-10 12:58:00', '2026-08-10 12:58:00'),
(25, 5, 14, '2026-08-10', '12:58:00', 'Present', NULL,                              4, '2026-08-10 12:58:00', '2026-08-10 12:58:00');


-- ============================================================================
-- 5.14 - SAMPLE DATA: notifications
-- ============================================================================

INSERT INTO notifications (user_id, title, message, type, is_read, read_at, created_at, updated_at) VALUES
-- Student notifications (user 6 = student@example.com)
(6, 'Attendance Recorded',    'You were marked PRESENT in ITP104 - Information Management 2 on August 10, 2026 at 7:52 AM.',                   'success', 1, '2026-08-10 08:05:00', '2026-08-10 08:05:00', '2026-08-10 08:05:00'),
(6, 'Late Attendance',        'You were marked LATE in ITP104 - Information Management 2 on August 3, 2026 at 8:14 AM.',                       'warning', 1, '2026-08-03 08:20:00', '2026-08-03 08:20:00', '2026-08-03 08:20:00'),
(6, 'Absence Recorded',       'You were marked ABSENT in ITP111 - System Administration and Maintenance on July 28, 2026.',                    'danger',  0, NULL,                  '2026-07-28 16:10:00', '2026-07-28 16:10:00'),
(6, 'Attendance Session Open','ITP104 - Information Management 2 has an open attendance session. Scan the QR code to check in.',               'info',    0, NULL,                  '2026-08-17 07:48:00', '2026-08-17 07:48:00'),
-- Instructor notifications (user 2 = instructor@example.com)
(2, 'Session Closed',         'Attendance session for ITP104 - 3A on August 10, 2026 was closed. 6 Present, 1 Late, 1 Absent.',                'info',    1, '2026-08-10 11:05:00', '2026-08-10 11:05:00', '2026-08-10 11:05:00'),
(2, 'Low Attendance Warning', 'Mark Anthony Delos Reyes has fallen below the 75% attendance rate in ITP104 - 3A.',                             'warning', 0, NULL,                  '2026-08-11 09:30:00', '2026-08-11 09:30:00'),
(2, 'New Enrollment',         'Angelica Marie Torres was enrolled in ITP111 - 3A.',                                                            'success', 0, NULL,                  '2026-08-12 10:15:00', '2026-08-12 10:15:00'),
-- Admin notifications (user 1 = admin@example.com)
(1, 'New Student Registration','Nathaniel Ryan Ocampo (2026-00015) completed registration under BSCE.',                                        'success', 1, '2026-08-05 14:20:00', '2026-08-05 14:20:00', '2026-08-05 14:20:00'),
(1, 'Department Attendance Report','BSIT department attendance rate for August 2026 is 87.5%.',                                                'info',    0, NULL,                  '2026-08-12 17:00:00', '2026-08-12 17:00:00'),
(1, 'Class Without Sessions', 'CPE101 - 1A has no recorded attendance sessions this semester.',                                                'warning', 0, NULL,                  '2026-08-13 08:00:00', '2026-08-13 08:00:00');



-- ############################################################################
-- 6. VERIFICATION
-- ############################################################################
-- Run these after the script to confirm everything was created correctly.
-- Highlight one statement and press Ctrl+Enter to run just that one.
-- ############################################################################

-- ----------------------------------------------------------------------------
-- 6.1  All 18 tables should be listed
-- ----------------------------------------------------------------------------
SHOW TABLES;


-- ----------------------------------------------------------------------------
-- 6.2  Row count per table
-- Expected: 4 departments, 20 users, 4 instructors, 15 students, 8 subjects,
--           6 classes, 23 enrollments, 25 sessions, 124 attendances,
--           10 notifications
-- ----------------------------------------------------------------------------
SELECT 'departments'         AS table_name, COUNT(*) AS total_rows FROM departments
UNION ALL SELECT 'users',               COUNT(*) FROM users
UNION ALL SELECT 'instructors',         COUNT(*) FROM instructors
UNION ALL SELECT 'students',            COUNT(*) FROM students
UNION ALL SELECT 'subjects',            COUNT(*) FROM subjects
UNION ALL SELECT 'classes',             COUNT(*) FROM classes
UNION ALL SELECT 'enrollments',         COUNT(*) FROM enrollments
UNION ALL SELECT 'attendance_sessions', COUNT(*) FROM attendance_sessions
UNION ALL SELECT 'attendances',         COUNT(*) FROM attendances
UNION ALL SELECT 'notifications',       COUNT(*) FROM notifications;


-- ----------------------------------------------------------------------------
-- 6.3  All 23 stored procedures should be listed
-- ----------------------------------------------------------------------------
SHOW PROCEDURE STATUS WHERE Db = 'attendance_monitoring_db';

-- ----------------------------------------------------------------------------
-- 6.4  All 15 foreign keys, with their delete rules
-- ----------------------------------------------------------------------------
SELECT
    kcu.TABLE_NAME             AS child_table,
    kcu.COLUMN_NAME            AS foreign_key_column,
    kcu.REFERENCED_TABLE_NAME  AS parent_table,
    rc.DELETE_RULE             AS on_delete
FROM information_schema.KEY_COLUMN_USAGE AS kcu
JOIN information_schema.REFERENTIAL_CONSTRAINTS AS rc
    ON rc.CONSTRAINT_NAME   = kcu.CONSTRAINT_NAME
   AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
WHERE kcu.TABLE_SCHEMA = 'attendance_monitoring_db'
  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME;


-- ----------------------------------------------------------------------------
-- 6.5  Passwords are hashed, never plain text
-- Every row must read "bcrypt hashed"
-- ----------------------------------------------------------------------------
SELECT
    email,
    role,
    LEFT(password, 7) AS hash_prefix,
    CASE
        WHEN password LIKE '$2y$%' THEN 'bcrypt hashed'
        ELSE 'PLAIN TEXT -- SECURITY PROBLEM'
    END AS password_storage
FROM users
ORDER BY role, email;


-- ----------------------------------------------------------------------------
-- 6.6  Duplicate attendance check
-- Must return ZERO rows. The UNIQUE key on
-- (class_id, student_id, attendance_date) makes duplicates impossible.
-- ----------------------------------------------------------------------------
SELECT class_id, student_id, attendance_date, COUNT(*) AS duplicate_count
FROM attendances
GROUP BY class_id, student_id, attendance_date
HAVING COUNT(*) > 1;


-- ----------------------------------------------------------------------------
-- 6.7  Attendance summary per class
-- Formula: ((Present + Late) / Total) * 100
-- ----------------------------------------------------------------------------
SELECT
    sub.subject_code,
    c.section,
    COUNT(*) AS total_records,
    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
    SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
    ROUND(
        SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END)
        / COUNT(*) * 100
    , 2) AS attendance_rate
FROM attendances AS a
JOIN classes     AS c   ON c.id   = a.class_id
JOIN subjects    AS sub ON sub.id = c.subject_id
GROUP BY c.id, sub.subject_code, c.section
ORDER BY sub.subject_code;


-- ----------------------------------------------------------------------------
-- 6.8  TEST THE STORED PROCEDURES
-- Run these one at a time
-- ----------------------------------------------------------------------------

CALL sp_GetDashboardStats();
CALL sp_GetStudentByNumber('2026-00001');
CALL sp_SearchStudents('Ramos');
CALL sp_GetUserByEmail('student@example.com');
CALL sp_GetClassRoster(1);
CALL sp_GetDailyReport('2026-08-10');
CALL sp_GetStudentReport(1, '2026-07-01', '2026-08-31');
CALL sp_GetStudentRate(3, 1);
CALL sp_GetClassReport();
CALL sp_GetDepartmentReport();
CALL sp_GetMonthlyReport(8, 2026);
CALL sp_GetLowAttendance(75.00);


-- ----------------------------------------------------------------------------
-- 6.9  View the code of any procedure
-- ----------------------------------------------------------------------------
-- SHOW CREATE PROCEDURE sp_GetStudentRate;
-- SHOW CREATE PROCEDURE sp_RecordAttendance;


-- ############################################################################
-- 7. VIEW WHAT THE SYSTEM ADDED
-- ############################################################################
-- Use these DURING the demo. Add something in the browser, then run the
-- matching query here -- the new row appears at the top.
--
-- ORDER BY id DESC puts the newest record first.
-- LIMIT keeps the result grid short and readable.
--
-- Highlight ONE query and press Ctrl+Enter to run just that one.
-- ############################################################################

-- ----------------------------------------------------------------------------
-- 7.1  NEWEST CLASSES
-- Browser: Admin -> Classes -> Add Class
-- ----------------------------------------------------------------------------
SELECT
    c.id AS class_id,
    sub.subject_code,
    sub.subject_name,
    c.section,
    CONCAT(i.first_name, ' ', i.last_name) AS instructor_name,
    c.schedule_day,
    TIME_FORMAT(c.start_time, '%h:%i %p') AS start_time,
    TIME_FORMAT(c.end_time,   '%h:%i %p') AS end_time,
    c.room,
    c.school_year,
    c.semester,
    c.status,
    c.created_at
FROM classes     AS c
JOIN subjects    AS sub ON sub.id = c.subject_id
JOIN instructors AS i   ON i.id   = c.instructor_id
ORDER BY c.id DESC
LIMIT 5;


-- ----------------------------------------------------------------------------
-- 7.2  NEWEST STUDENTS
-- Browser: Admin -> Students -> Add Student
-- Shows BOTH rows created by one add: the login account and the profile
-- ----------------------------------------------------------------------------
SELECT
    u.id AS user_id,
    s.id AS student_id,
    s.student_number,
    CONCAT(s.last_name, ', ', s.first_name) AS student_name,
    s.email,
    d.department_code,
    s.year_level,
    s.section,
    s.status,
    LEFT(u.password, 7) AS password_prefix,
    s.created_at
FROM students    AS s
JOIN users       AS u ON u.id = s.user_id
JOIN departments AS d ON d.id = s.department_id
ORDER BY s.id DESC
LIMIT 5;


-- ----------------------------------------------------------------------------
-- 7.3  NEWEST INSTRUCTORS
-- Browser: Admin -> Instructors -> Add Instructor
-- ----------------------------------------------------------------------------
SELECT
    u.id AS user_id,
    i.id AS instructor_id,
    i.employee_number,
    CONCAT(i.first_name, ' ', i.last_name) AS instructor_name,
    i.email,
    d.department_code,
    i.status,
    i.created_at
FROM instructors AS i
JOIN users       AS u ON u.id = i.user_id
JOIN departments AS d ON d.id = i.department_id
ORDER BY i.id DESC
LIMIT 5;


-- ----------------------------------------------------------------------------
-- 7.4  NEWEST DEPARTMENTS
-- Browser: Admin -> Departments -> Add Department
-- ----------------------------------------------------------------------------
SELECT
    id,
    department_code,
    department_name,
    description,
    created_at
FROM departments
ORDER BY id DESC
LIMIT 5;


-- ----------------------------------------------------------------------------
-- 7.5  NEWEST SUBJECTS
-- Browser: Admin -> Subjects -> Add Subject
-- ----------------------------------------------------------------------------
SELECT
    sub.id,
    sub.subject_code,
    sub.subject_name,
    sub.units,
    d.department_code,
    sub.created_at
FROM subjects    AS sub
JOIN departments AS d ON d.id = sub.department_id
ORDER BY sub.id DESC
LIMIT 5;


-- ----------------------------------------------------------------------------
-- 7.6  NEWEST ENROLLMENTS
-- Browser: Admin -> Enrollments -> Manage Roster -> Enroll Selected Students
-- ----------------------------------------------------------------------------
SELECT
    e.id AS enrollment_id,
    s.student_number,
    CONCAT(s.last_name, ', ', s.first_name) AS student_name,
    sub.subject_code,
    c.section,
    e.enrollment_status,
    e.created_at
FROM enrollments AS e
JOIN students    AS s   ON s.id   = e.student_id
JOIN classes     AS c   ON c.id   = e.class_id
JOIN subjects    AS sub ON sub.id = c.subject_id
ORDER BY e.id DESC
LIMIT 10;


-- ----------------------------------------------------------------------------
-- 7.7  NEWEST ATTENDANCE RECORDS
-- Browser: Instructor -> Record Attendance -> Save
--      or  Student    -> Scan QR Attendance
-- recorded_by tells you which one it was
-- ----------------------------------------------------------------------------
SELECT
    a.id AS attendance_id,
    s.student_number,
    CONCAT(s.last_name, ', ', s.first_name) AS student_name,
    sub.subject_code,
    c.section,
    a.attendance_date,
    TIME_FORMAT(a.time_in, '%h:%i %p') AS time_in,
    a.status,
    CASE
        WHEN a.recorded_by IS NULL THEN 'QR self-scan by student'
        ELSE CONCAT('Manual entry by ', u.name)
    END AS recorded_how,
    a.remarks,
    a.created_at
FROM attendances AS a
JOIN students    AS s   ON s.id   = a.student_id
JOIN classes     AS c   ON c.id   = a.class_id
JOIN subjects    AS sub ON sub.id = c.subject_id
LEFT JOIN users  AS u   ON u.id   = a.recorded_by
ORDER BY a.id DESC
LIMIT 10;


-- ----------------------------------------------------------------------------
-- 7.8  NEWEST ATTENDANCE SESSIONS
-- Browser: Instructor -> QR Attendance -> Start Attendance
-- A live session has a token. A closed one has NULL -- that is the expiry.
-- ----------------------------------------------------------------------------
SELECT
    ses.id AS session_id,
    sub.subject_code,
    c.section,
    ses.session_date,
    TIME_FORMAT(ses.start_time, '%h:%i %p') AS start_time,
    ses.status,
    CASE WHEN ses.qr_token IS NULL THEN 'EXPIRED' ELSE 'ACTIVE' END AS qr_status,
    ses.qr_token,
    ses.created_at
FROM attendance_sessions AS ses
JOIN classes  AS c   ON c.id   = ses.class_id
JOIN subjects AS sub ON sub.id = c.subject_id
ORDER BY ses.id DESC
LIMIT 5;


-- ----------------------------------------------------------------------------
-- 7.9  NEWEST NOTIFICATIONS
-- The system creates these automatically after attendance and enrollment
-- ----------------------------------------------------------------------------
SELECT
    n.id,
    u.name AS sent_to,
    u.role,
    n.title,
    n.message,
    n.type,
    CASE WHEN n.is_read = 1 THEN 'Read' ELSE 'Unread' END AS read_status,
    n.created_at
FROM notifications AS n
JOIN users AS u ON u.id = n.user_id
ORDER BY n.id DESC
LIMIT 10;


-- ----------------------------------------------------------------------------
-- 7.10  BEFORE AND AFTER COUNTER
-- Run this BEFORE adding anything in the browser, note the numbers,
-- add something, then run it AGAIN. The count goes up.
-- ----------------------------------------------------------------------------
SELECT
    (SELECT COUNT(*) FROM departments)         AS departments,
    (SELECT COUNT(*) FROM subjects)            AS subjects,
    (SELECT COUNT(*) FROM users)               AS users,
    (SELECT COUNT(*) FROM instructors)         AS instructors,
    (SELECT COUNT(*) FROM students)            AS students,
    (SELECT COUNT(*) FROM classes)             AS classes,
    (SELECT COUNT(*) FROM enrollments)         AS enrollments,
    (SELECT COUNT(*) FROM attendance_sessions) AS sessions,
    (SELECT COUNT(*) FROM attendances)         AS attendances,
    (SELECT COUNT(*) FROM notifications)       AS notifications,
    NOW() AS checked_at;


-- ----------------------------------------------------------------------------
-- 7.11  EVERYTHING ADDED TODAY
-- One query that catches any record created today, from any module
-- ----------------------------------------------------------------------------
SELECT 'Department' AS record_type, id, department_code AS identifier,
       department_name AS description, created_at
FROM departments WHERE DATE(created_at) = CURDATE()

UNION ALL
SELECT 'Subject', id, subject_code, subject_name, created_at
FROM subjects WHERE DATE(created_at) = CURDATE()

UNION ALL
SELECT 'Student', id, student_number, CONCAT(first_name, ' ', last_name), created_at
FROM students WHERE DATE(created_at) = CURDATE()

UNION ALL
SELECT 'Instructor', id, employee_number, CONCAT(first_name, ' ', last_name), created_at
FROM instructors WHERE DATE(created_at) = CURDATE()

UNION ALL
SELECT 'Class', c.id, sub.subject_code, CONCAT('Section ', c.section), c.created_at
FROM classes AS c JOIN subjects AS sub ON sub.id = c.subject_id
WHERE DATE(c.created_at) = CURDATE()

UNION ALL
SELECT 'Enrollment', e.id, s.student_number, sub.subject_code, e.created_at
FROM enrollments AS e
JOIN students AS s   ON s.id = e.student_id
JOIN classes  AS c   ON c.id = e.class_id
JOIN subjects AS sub ON sub.id = c.subject_id
WHERE DATE(e.created_at) = CURDATE()

UNION ALL
SELECT 'Attendance', a.id, s.student_number, a.status, a.created_at
FROM attendances AS a JOIN students AS s ON s.id = a.student_id
WHERE DATE(a.created_at) = CURDATE()

ORDER BY created_at DESC;

SELECT * FROM users;

-- ############################################################################
-- RELATIONSHIP SUMMARY  (for the EER diagram)
-- ############################################################################
--
-- ONE-TO-ONE (1:1) -- 2 relationships
--   users        1 --- 1  students        via students.user_id
--   users        1 --- 1  instructors     via instructors.user_id
--
-- ONE-TO-MANY (1:N) -- 12 relationships
--   departments  1 --- N  students        via students.department_id
--   departments  1 --- N  instructors     via instructors.department_id
--   departments  1 --- N  subjects        via subjects.department_id
--   subjects     1 --- N  classes         via classes.subject_id
--   instructors  1 --- N  classes         via classes.instructor_id
--   classes      1 --- N  enrollments     via enrollments.class_id
--   students     1 --- N  enrollments     via enrollments.student_id
--   classes      1 --- N  attendance_sessions
--   attendance_sessions 1 --- N attendances
--   classes      1 --- N  attendances     via attendances.class_id
--   students     1 --- N  attendances     via attendances.student_id
--   users        1 --- N  notifications   via notifications.user_id
--   users        1 --- N  attendances     via attendances.recorded_by
--
-- MANY-TO-MANY (M:N) -- 1 relationship
--   students  M --- N  classes
--   Resolved by the junction table "enrollments", which turns one M:N
--   relationship into two 1:N relationships. A relational database cannot
--   store M:N directly -- this is the standard solution.
--
-- TO BUILD THE EER DIAGRAM:
--   Database -> Reverse Engineer -> attendance_monitoring_db
--   At "Select Objects", UNTICK these 8 Laravel framework tables:
--     cache, cache_locks, failed_jobs, job_batches, jobs,
--     migrations, password_reset_tokens, sessions
--   Keep only the 10 system tables.
-- ############################################################################


-- ============================================================================
-- END OF SCRIPT
-- ============================================================================