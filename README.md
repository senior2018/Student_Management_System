# Web-Based Student Management System

A PHP + MySQL student management system with admin login, full student CRUD,
search, and attendance tracking. Designed to run on macOS using the built-in
PHP CLI server and Homebrew MySQL — **no XAMPP needed**.

## Features

- Admin login / logout with session authentication
- Add, view, edit, delete students (CRUD)
- Search students by ID, name, email, or course
- Daily attendance marking (Present / Absent / Late) with date picker
- Attendance records with date range and per-student filters
- Dashboard with totals and today's attendance summary
- Security: bcrypt password hashing, prepared statements (PDO), CSRF tokens,
  output escaping, session regeneration on login
- Responsive layout (works on mobile)

## Project layout

```
Student_Management_System/
├── assets/style.css            Stylesheet
├── includes/
│   ├── config.example.php      Template — copy to config.php
│   ├── config.php              Your DB credentials (gitignored)
│   ├── db.php                  PDO connection factory
│   ├── auth.php                Session helpers
│   └── helpers.php             CSRF, validation, flash messages, escaping
├── partials/
│   ├── header.php              Shared top nav
│   └── footer.php              Shared footer
├── sql/
│   ├── schema.sql              Tables: users, students, attendance
│   └── seed_admin.sql          Default admin user
├── index.php                   Entry — redirects to dashboard/login
├── login.php / logout.php
├── dashboard.php
├── add_student.php
├── view_students.php           List + search
├── edit_student.php
├── delete_student.php
├── attendance.php              Mark daily attendance
└── view_attendance.php         Browse records
```

## Setup (macOS, no XAMPP)

You already have PHP 8.5 and MySQL 9 from Homebrew. If not:

```bash
brew install php mysql
brew services start mysql
```

### 1. Create the database

```bash
cd /Users/senior/Dev/PHP/Student_Management_System
mysql -u root -p < sql/schema.sql
mysql -u root -p < sql/seed_admin.sql
```

The seed creates an admin user:

- **Username:** `admin`
- **Password:** `admin123`

> Change this password immediately after your first login (you can do it
> directly in the DB: `UPDATE users SET password = ... WHERE username = 'admin';`
> using `password_hash()` to generate the new hash).

### 2. Configure DB credentials

```bash
cp includes/config.example.php includes/config.php
```

Edit `includes/config.php` and fill in your MySQL `user` and `password`.

### 3. Run the app

```bash
php -S localhost:8000
```

Then open <http://localhost:8000> in your browser. You'll be redirected to
the login page.

## Database schema

```sql
users        (id, username UNIQUE, password, created_at)
students     (id, fullname, gender, course, email UNIQUE, phone, created_at)
attendance   (id, student_id FK→students, attend_date, status, created_at,
              UNIQUE(student_id, attend_date))
```

The unique key on `attendance(student_id, attend_date)` plus
`INSERT ... ON DUPLICATE KEY UPDATE` means marking attendance for the same
student on the same day twice updates the status instead of duplicating.

`ON DELETE CASCADE` on `attendance.student_id` removes attendance records
automatically when a student is deleted.

## Security notes

- **Passwords** are hashed with `password_hash()` (bcrypt, cost 12) and
  verified with `password_verify()`.
- **SQL injection** is prevented by using PDO prepared statements with
  named parameters everywhere — no string concatenation.
- **CSRF**: every POST form includes a token verified against the session
  via `hash_equals()`.
- **XSS**: all output is escaped with `htmlspecialchars()` via the `e()`
  helper.
- **Session**: `session_regenerate_id(true)` runs on login to prevent
  session fixation; cookies are HttpOnly + SameSite=Lax.
- **Validation**: every input is validated server-side (length, format,
  enum) in `validate_student()`.

## Test it manually

1. Visit <http://localhost:8000> → redirected to login.
2. Log in with `admin` / `admin123`.
3. Add a few students from "Add Student".
4. Search them on "Students".
5. Edit one, delete one.
6. Go to "Attendance", pick a date, mark statuses, save.
7. Open "Records" to filter by date range and student.
