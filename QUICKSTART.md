# School LMS - Quick Start Guide

Get up and running in 5 minutes!

## ⚡ 5-Minute Setup

### Step 1: Start Services (1 minute)
```
1. Open XAMPP Control Panel
2. Click "Start" next to Apache
3. Click "Start" next to MySQL
4. Wait for both to show green status
```

### Step 2: Create Database (1 minute)
```
1. Go to: http://localhost/phpmyadmin
2. Left sidebar → "New"
3. Database name: school_lms
4. Click "Create"
```

### Step 3: Import Schema (1 minute)
```
1. Click on "school_lms" database
2. Go to "Import" tab
3. Click "Choose File"
4. Select: School-LMS/database/schema.sql
5. Click "Go" at bottom
```

### Step 4: Access System (1 minute)
```
1. Open browser
2. Go to: http://localhost/School-LMS/
3. Click "Login"
```

### Step 5: Login (1 minute)
```
Username: admin
Password: password123
Click "Sign In"
```

**Done!** You're now in the admin dashboard.

---

## 👤 Demo Accounts

Use these to explore different roles:

### Admin Account
```
Username: admin
Password: password123
Access: All admin features, user management, reports
```

### Teacher Account
```
Username: jsmith
Password: password123
Access: Course management, grading, materials
```

### Student Account
```
Username: astudent
Password: password123
Access: Course view, assignments, grades
```

---

## 🎯 Feature Walkthroughs

### Admin Workflow

**Create a New Course:**
1. Login as admin
2. Click "Courses" in sidebar
3. Click "Add Course" button
4. Fill in:
   - Course Code (e.g., CS101)
   - Course Title
   - Select Teacher (dropdown)
   - Select Term
5. Click "Add Course"

**Create Academic Term:**
1. Click "Academic Terms" in sidebar
2. Click "Add Term" button
3. Fill in:
   - Term Name (e.g., Fall 2024)
   - Start Date
   - End Date
4. Click "Add"

**Manage Users:**
1. Click "Users" in sidebar
2. See list of all users
3. Click "Edit" to modify user
4. Click "Delete" to remove (admin accounts can't be deleted)

**Enroll Students:**
1. Click "Enrollments" in sidebar
2. Click "Enroll Student" button
3. Select Student and Course
4. Click "Enroll"

**View Reports:**
1. Click "Reports" in sidebar
2. See system statistics
3. View enrollment breakdown
4. Check submission status

### Teacher Workflow

**Upload Course Materials:**
1. Login as teacher
2. Click "Materials" in sidebar
3. Click "Add Material" button
4. Select Course (dropdown)
5. Upload File (any type, up to 50MB)
6. Click "Upload"
7. Materials appear in the list

**Create Assignment:**
1. Click "Assignments" in sidebar
2. Click "Create Assignment" button
3. Fill in:
   - Assignment Title
   - Description
   - Course (dropdown)
   - Due Date & Time
   - Max Score
4. Click "Create Assignment"
5. Students can now see and submit

**Grade Submissions:**
1. Click "Grades" in sidebar
2. See list of pending submissions
3. Click on student name
4. Enter Score (out of max)
5. Add Feedback (optional)
6. Click "Submit Grade"
7. Student sees grade immediately

**Post Announcement:**
1. Click "Announcements" in sidebar
2. Click "Add Announcement" button
3. Enter message
4. Optionally check "Pin" to feature it
5. Click "Post"
6. Appears in student feeds

### Student Workflow

**View Enrolled Courses:**
1. Login as student
2. Click "Courses" in sidebar
3. See all enrolled courses with:
   - Course code and title
   - Teacher name
   - Number of materials
   - Number of assignments

**Submit Assignment:**
1. Click "Assignments" in sidebar
2. Find pending assignment
3. Click on assignment
4. Paste or type your answer
5. Click "Submit Assignment"
6. Status changes to "Submitted"
7. Wait for teacher to grade

**Check Grades:**
1. Click "Grades" in sidebar
2. View grades by course
3. See individual assignment scores
4. Read teacher feedback
5. Track progress (shows percentage complete)

**Read Announcements:**
1. Click "Announcements" in sidebar
2. See all course announcements
3. Pinned announcements appear first
4. Read important course updates

---

## 🎨 User Interface Overview

### Sidebar Navigation
- Always visible on left (desktop/tablet)
- Shows current page highlighted in blue
- Different menu items per role

### Top Bar
- School LMS logo/title
- User info and name
- Menu dropdown (hover or click)
- Logout option

### Main Content Area
- Dashboard with statistics
- List pages with tables
- Forms for adding/editing
- Status indicators (badges)

### Colors Used
- Blue buttons and links
- Green for success messages
- Red for errors
- Gray for inactive items

---

## 🔍 Admin Dashboard Stats

See at a glance:
- **Total Students**: Count of all student accounts
- **Total Teachers**: Count of all teacher accounts
- **Total Courses**: Count of all courses
- **Total Enrollments**: Count of students enrolled

Plus:
- Recent enrollments table
- Recent courses created

---

## 🔍 Teacher Dashboard Stats

See at a glance:
- **Assigned Courses**: Number of courses you teach
- **Total Students**: Number of students across all courses
- **Pending Submissions**: Count of ungraded work

Plus:
- List of your courses
- List of pending submissions to grade

---

## 🔍 Student Dashboard Stats

See at a glance:
- **Enrolled Courses**: Number of courses you're in
- **Pending Assignments**: Work due soon
- **Average Grade**: Your overall performance

Plus:
- List of enrolled courses with progress
- List of upcoming deadlines

---

## 📝 Common Tasks

### Add a New Student (Admin)
1. Click "Users" → "Add User"
2. Enter: First Name, Last Name, Username, Email
3. Set Role to "Student"
4. Click "Create Account"

### Assign Teacher to Course (Admin)
1. Click "Courses"
2. Click "Edit" on a course
3. Select different teacher in dropdown
4. Click "Update Course"

### Drop Student from Course (Admin)
1. Click "Enrollments"
2. Find the enrollment
3. Click "Drop" button
4. Confirmation dialog appears
5. Status changes to "dropped"

### Enable/Disable User (Admin)
1. Click "Users"
2. Click "Edit" on user
3. Set Status to "active" or "inactive"
4. Inactive users can't login

### Create New Academic Term (Admin)
1. Click "Academic Terms"
2. Click "Add Term"
3. Only ONE term can be "active"
4. When you set a term active, others become inactive automatically

---

## ⚙️ Settings & Configuration

Most settings are in `includes/config.php`:

```php
// Change these to customize:
define('APP_NAME', 'School LMS');        // Site name
define('SESSION_TIMEOUT', 3600);         // Logout after 1 hour
define('APP_URL', 'http://...');         // Base URL
```

---

## 🆘 Quick Troubleshooting

**"Can't login"**
- Ensure MySQL is running
- Try demo credentials: admin / password123
- Check if database imported correctly

**"Blank page or errors"**
- Hard refresh: Ctrl+Shift+R
- Check browser console: F12 → Console
- Check error log: logs/error.log

**"Files won't upload"**
- Check assets/uploads/ folder exists
- Verify folder is writable
- Ensure file is under 50MB

**"Database connection failed"**
- Run: http://localhost/School-LMS/test-connection.php
- Verify MySQL is running
- Check credentials in config.php

---

## 📚 Documentation

For more details:
- **README.md** - Full technical documentation
- **SETUP-GUIDE.md** - Detailed troubleshooting
- **INSTALLATION.md** - Installation checklist
- **UPDATES.md** - What's new and what changed

---

## 🚀 Advanced Features

The system also includes:
- Quiz framework (ready to expand)
- File upload management
- Announcement pinning
- Grade feedback system
- Progress tracking
- Role-based access control

---

## 💡 Tips & Tricks

### For Admins
- Pin important announcements so students see them first
- Use Academic Terms to organize by semester
- Archive old courses instead of deleting
- Review Reports monthly for trends

### For Teachers
- Upload materials early in term
- Set assignment due dates strategically
- Leave feedback on grades for guidance
- Pin important announcements

### For Students
- Check Announcements for updates
- Submit early to avoid last-minute issues
- Review feedback on graded work
- Monitor deadlines on dashboard

---

## 🔐 Security Notes

- All passwords are hashed (bcrypt)
- Never share your login credentials
- Logout after use, especially on shared computers
- Report suspicious activity to admin

---

## 📞 Need Help?

1. **Check SETUP-GUIDE.md** for troubleshooting
2. **Run test-connection.php** to diagnose database issues
3. **Review error logs** in logs/error.log
4. **Check browser console** (F12) for JavaScript errors

---

## ✅ Verification Checklist

After setup, verify:
- [ ] Both Apache and MySQL are running
- [ ] Can access http://localhost/phpmyadmin
- [ ] Database "school_lms" exists
- [ ] Can login with admin / password123
- [ ] Admin dashboard loads with stats
- [ ] Can see Users, Courses, etc. in menu

---

**You're all set!** 🎉

Explore the system, test all roles, and customize as needed.

For production use, see SETUP-GUIDE.md for security checklist.

---

**Version**: 1.0
**Last Updated**: January 6, 2026
