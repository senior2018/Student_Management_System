# Student Management System — User Manual

A short guide for admins on how to use the system.

---

## 1. Logging in

1. Open the login page in your browser:
   <http://localhost:8080/login.php>
2. Type the username and password.
   - Default admin: **`admin`** / **`admin123`**
3. Click **Sign in**.

After a successful login, you go to the **Dashboard**.

**Tip:** if you forget your password, the easiest fix is to re-run `setup.php` to recreate the admin (you'll have to manually delete the existing admin row from MySQL first), or change the password in the database directly.

---

## 2. The Dashboard

The dashboard shows:

- **Total Students** — how many students are in the system
- **Present / Absent / Late Today** — how many students have each status for today's date
- **Quick actions** — buttons that go straight to the most common pages
- **System Status** — shows whether email notifications are turned on

---

## 3. Adding a Student

1. From the top nav, click **Add Student** (or use the dashboard button).
2. Fill in:
   - **Full Name** (2–100 letters)
   - **Gender** (Male / Female / Other)
   - **Course** (e.g. "BSc Computer Science")
   - **Email** (must be a valid email format)
   - **Phone** (7–20 characters, digits / spaces / `+` / `-` / `()` allowed)
   - **Photo** (optional — JPG, PNG, GIF, or WebP, up to 2 MB)
3. Click **Save Student**.

After saving:
- You land on the student list with a green "Student added" notice.
- If email is set up, a welcome email is sent automatically.

**If you see errors:** the messages appear next to the wrong fields. Fix them and click Save again — the rest of your input stays in the form.

---

## 4. Viewing & Searching Students

Top nav → **Students**.

- The list shows every student, newest first, with their photo, name, GPA (once they have grades), course, email, phone, and action buttons.
- **Search box**: type any ID, full name, email, or course and press **Search**. Click **Clear** to reset.

Each row has three buttons:
- **Edit** — change the student's info
- **Grades** — add/remove grades and see GPA
- **Delete** — remove the student (asks for confirmation first)

---

## 5. Editing a Student

1. On the student list, click **Edit** next to a row.
2. Change whatever fields you need.
3. (Optional) Upload a new photo to replace the old one. The current photo is shown above the file picker.
4. Click **Save Changes**.

---

## 6. Deleting a Student

1. On the student list, click **Delete** next to a row.
2. Confirm the prompt.

This also removes the student's attendance records and grades.

---

## 7. Marking Attendance

1. Top nav → **Attendance**.
2. Pick a date (defaults to today). You can't pick a future date.
3. For each student, choose **Present**, **Absent**, or **Late**.
4. Click **Save Attendance**.

You can change today's attendance any number of times — the system updates the existing record instead of making duplicates.

---

## 8. Viewing Attendance Records

1. Top nav → **Records**.
2. Filter by date range and (optionally) by one specific student.
3. Click **Filter** to apply.

The list shows date, student, course, and a colour badge for status (green=Present, red=Absent, yellow=Late).

---

## 9. Managing Grades / GPA

1. On the student list, click **Grades** on a student's row.
2. The top of the page shows their **Overall GPA** (out of 4.00).
3. To add a grade:
   - Fill in **Subject** (e.g. "Math 101"), **Score** (0–100), and **Credits** (1–10).
   - Click **Add Grade**.
4. The grade appears in the list with its grade point. The GPA updates immediately.
5. To remove a grade, click **Delete** on that row.

**GPA scale used: TCU 5.0-point scale (Tanzania)**

| Score | Letter | Grade Point | Meaning |
|---|---|---|---|
| 70 – 100 | A  | 5.0 | Excellent |
| 60 – 69  | B+ | 4.0 | Very Good |
| 50 – 59  | B  | 3.0 | Good (pass mark) |
| 40 – 49  | C  | 2.0 | Satisfactory |
| 35 – 39  | D  | 1.0 | Marginal Fail |
| 0 – 34   | F  | 0.0 | Absolute Fail |

**Formula:** `GPA = sum(Grade Point × Credits) / sum(Credits)`

**Degree classification (shown beside the GPA):**

| GPA | Classification |
|---|---|
| 4.4 – 5.0 | First Class Honours |
| 3.5 – 4.3 | Upper Second Class Honours |
| 2.7 – 3.4 | Lower Second Class Honours |
| 2.0 – 2.6 | Pass |
| Below 2.0 | Fail |

---

## 10. PDF Report

Top nav → **PDF Report**.

Generates a PDF list of all students (with their GPA) and opens it in a new tab. From there your browser can save or print it.

---

## 11. Email Notifications

Welcome emails are sent automatically when a new student is added — **but only if email is set up**. On the dashboard, look at "System Status":
- **Active** → emails are being sent
- **Off** → emails are skipped (the app still works fine)

To turn email on, see [DOCUMENTATION.md](DOCUMENTATION.md#email-setup).

---

## 12. Logging Out

Top nav → **Logout**. This wipes your session and sends you back to the login page.

---

## 13. Using on a Phone

The system is responsive — open the same URL in your phone's browser. The nav bar stacks, tables scroll horizontally, and forms expand to full width. You can do everything from a phone.

---

## 14. Common Problems

| Problem | What to try |
|---|---|
| "This site can't be reached" | The PHP server isn't running. In Terminal, `cd` to the project folder and run `php -S localhost:8080` |
| "Invalid username or password" | Default is `admin` / `admin123`. If you changed it and forgot, ask the developer (see DOCUMENTATION.md) |
| "Invalid CSRF token" | Your session probably timed out. Refresh the page and try again |
| Photo upload fails | Make sure the file is JPG / PNG / GIF / WebP and under 2 MB |
| No PDF appears | Check that Composer dependencies are installed (`composer install` in the project folder) |
| GPA stays "—" | The student has no grades yet. Click **Grades** on their row and add some |
| Email status says "Off" | Set up Gmail credentials in `includes/config.php` (see DOCUMENTATION.md) |
