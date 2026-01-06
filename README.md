# School LMS - Learning Management System

A complete, professional Learning Management System built with PHP, MySQL, and modern web technologies.

## 🎯 Features

### For Administrators
- **User Management**: Create, edit, delete users with role assignment
- **Course Management**: Create courses and assign teachers
- **Term Management**: Manage academic terms with active term selection
- **Enrollment Management**: Enroll students in courses
- **Reports**: View comprehensive system statistics and analytics
- **Dashboard**: Overview of system statistics and recent activity

### For Teachers
- **Course Management**: View assigned courses and student enrollment
- **Materials Upload**: Upload course materials with file validation
- **Assignment Creation**: Create assignments with due dates
- **Grading System**: Grade student submissions with feedback
- **Announcements**: Post and manage course announcements
- **Quizzes**: Quiz framework for assessments
- **Dashboard**: Overview of pending submissions and courses

### For Students
- **Course Access**: View enrolled courses with materials
- **Assignment Submission**: Submit assignments and track status
- **Grade Tracking**: View grades by course and per assignment
- **Announcements**: Read course announcements
- **Progress Tracking**: Monitor overall academic performance
- **Dashboard**: Quick overview of courses and deadlines

## 🛠 Technology Stack

- **Frontend**: HTML5, CSS3, Responsive Design
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Authentication**: Session-based with bcrypt password hashing
- **Security**: Prepared statements, input sanitization, RBAC

## 📋 System Requirements

- Apache Web Server (or equivalent)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser
- 50MB disk space

## 🚀 Quick Start

### 1. Download/Setup Files
- Extract School-LMS folder to your web root (htdocs)

### 2. Create Database
```sql
CREATE DATABASE school_lms;
```

### 3. Import Schema
- Go to phpmyadmin
- Select school_lms database
- Click Import tab
- Select database/schema.sql
- Click Go

### 4. Access System
```
http://localhost/School-LMS/
```

### 5. Demo Login
- **Admin**: admin / password123
- **Teacher**: jsmith / password123
- **Student**: astudent / password123

## 📁 Project Structure

```
School-LMS/
├── admin/                    # Admin pages
│   ├── dashboard.php         # Admin overview
│   ├── users.php             # User management
│   ├── courses.php           # Course management
│   ├── terms.php             # Term management
│   ├── enrollments.php       # Student enrollment
│   └── reports.php           # System reports
│
├── teacher/                  # Teacher pages
│   ├── dashboard.php         # Teacher overview
│   ├── courses.php           # Assigned courses
│   ├── materials.php         # Course materials
│   ├── assignments.php       # Assignment management
│   ├── quizzes.php           # Quiz management
│   ├── grades.php            # Grading system
│   └── announcements.php     # Course announcements
│
├── student/                  # Student pages
│   ├── dashboard.php         # Student overview
│   ├── courses.php           # Enrolled courses
│   ├── assignments.php       # Assignment submissions
│   ├── grades.php            # Grade tracking
│   └── announcements.php     # Read announcements
│
├── includes/                 # Core PHP classes
│   ├── config.php            # Configuration
│   ├── Database.php          # Database wrapper
│   ├── Auth.php              # Authentication
│   └── helpers.php           # Utility functions
│
├── database/
│   └── schema.sql            # Database schema
│
├── assets/
│   ├── css/
│   │   └── style.css         # Global styling
│   ├── js/                   # Custom scripts
│   └── uploads/              # File uploads
│
├── logs/                     # Error logs
├── index.php                 # Home page
├── login.php                 # Login page
├── register.php              # Registration page
├── logout.php                # Logout handler
└── 401.php                   # Unauthorized page
```

## 🔐 Security Features

- **Password Hashing**: BCrypt algorithm for password security
- **SQL Injection Prevention**: All queries use prepared statements
- **Session Management**: Secure session handling with timeout
- **Role-Based Access Control**: Three-tier authorization system
- **Input Validation**: All user inputs validated and sanitized
- **File Upload Security**: File type validation and size limits
- **XSS Protection**: Output escaping and sanitization
- **CORS Headers**: Security headers for browser protection

## 📊 Database Schema

### Core Tables (13 total)

1. **users** - User accounts (admin, teacher, student)
2. **academic_terms** - School terms/semesters
3. **courses** - Course listings
4. **enrollments** - Student course enrollments
5. **course_materials** - Uploaded course files
6. **assignments** - Assignment details
7. **assignment_submissions** - Student submissions
8. **grades** - Assignment grades
9. **announcements** - Course announcements
10. **quizzes** - Quiz definitions
11. **quiz_questions** - Quiz questions
12. **quiz_answers** - Student quiz answers
13. **quiz_submissions** - Quiz attempt tracking

All tables include:
- Proper foreign key relationships
- Indexes on frequently queried columns
- Timestamps for audit trails
- Status fields for workflow management

## 👥 User Roles

### Administrator
- Full system access
- Manage all users
- Create courses and terms
- View all reports

### Teacher
- Access assigned courses
- Upload materials
- Create assignments and quizzes
- Grade submissions
- Post announcements

### Student
- Access enrolled courses
- Submit assignments
- View grades and feedback
- Read announcements
- Track progress

## 🎨 User Interface

- **Responsive Design**: Works on desktop, tablet, and mobile
- **Professional Layout**: Clean sidebar navigation with main content area
- **Consistent Styling**: Poppins font throughout, neutral color palette
- **Accessible Forms**: Clear labels and helpful error messages
- **Data Tables**: Sortable tables with pagination-ready structure
- **Status Indicators**: Visual badges for different statuses

## 🔧 Configuration

Edit `includes/config.php` to customize:

```php
// Database Connection
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_lms');

// Application
define('APP_NAME', 'School LMS');
define('APP_URL', 'http://localhost/School-LMS');

// Session
define('SESSION_TIMEOUT', 3600); // 1 hour
```

## 📝 Database Queries

### Example: Get Student Grades

```php
$db->prepare("
    SELECT a.title, g.score, g.max_score, g.feedback
    FROM grades g
    JOIN assignments a ON g.assignment_id = a.id
    WHERE g.student_id = ?
    ORDER BY g.graded_at DESC
")->bind('i', $student_id)->execute();
$grades = $db->fetchAll();
```

### Example: Get Course Enrollment

```php
$db->prepare("
    SELECT c.id, c.title, u.first_name, u.last_name
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    JOIN users u ON e.student_id = u.id
    WHERE c.teacher_id = ? AND e.status = 'enrolled'
")->bind('i', $teacher_id)->execute();
$students = $db->fetchAll();
```

## 🐛 Troubleshooting

### Database Connection Failed
1. Verify MySQL is running
2. Check credentials in config.php
3. Ensure database 'school_lms' exists

### Login Not Working
1. Verify users table is populated
2. Check demo credentials (admin/password123)
3. Clear browser cookies

### File Upload Errors
1. Ensure assets/uploads/ exists
2. Check folder permissions (755)
3. Verify upload file size limit

### Styling Issues
1. Clear browser cache (Ctrl+Shift+Delete)
2. Hard refresh (Ctrl+Shift+R)
3. Check assets/css/style.css exists

## 📚 Best Practices

### For Development
- Always use prepared statements for queries
- Validate all user inputs
- Use role-based access checks
- Keep error logs for debugging
- Test with different user roles

### For Deployment
- Change all demo passwords
- Set `display_errors` to 0
- Enable `log_errors` to file
- Use HTTPS in production
- Regular database backups
- Monitor error logs

## 🔄 Workflow Examples

### Creating an Assignment (Teacher)
1. Login as teacher
2. Go to Courses → Select course
3. Click Assignments → Add new
4. Fill details and due date
5. Click Submit
6. Assignment appears for enrolled students

### Submitting Assignment (Student)
1. Login as student
2. Go to Courses → Select course
3. Click Assignments
4. Select pending assignment
5. Enter submission
6. Click Submit
7. Status changes to "submitted"

### Grading (Teacher)
1. Login as teacher
2. Go to Grades
3. See pending submissions
4. Click to grade
5. Enter score and feedback
6. Click Submit
7. Student sees grade and feedback

## 🚀 Performance

- Optimized SQL queries with proper indexes
- Efficient database design (3NF normalization)
- Prepared statements prevent SQL injection
- Caching-friendly HTML structure
- Minimal CSS file (~30KB minified)
- Fast page load times

## 🤝 Contributing

To enhance the system:
1. Follow existing code style
2. Use prepared statements for queries
3. Add validation for all inputs
4. Test with different roles
5. Update documentation

## 📄 License

Open source - modify as needed for your institution

## 🎓 Educational Use

Perfect for:
- K-12 schools
- Universities
- Online courses
- Training programs
- Educational institutions

## 📞 Support

- Check README.md for technical details
- See QUICKSTART.md for feature walkthroughs
- Visit SETUP-GUIDE.md for troubleshooting
- Run test-connection.php for diagnostics

## 🎉 Getting Started

1. **Setup**: Follow SETUP-GUIDE.md
2. **Login**: Use demo credentials
3. **Explore**: Test each role's features
4. **Customize**: Modify as needed
5. **Deploy**: Follow production checklist

---

**Version**: 1.0
**Status**: Production Ready
**Last Updated**: January 6, 2026

Built with ❤️ for educators and students
