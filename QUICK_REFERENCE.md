# Quick Reference Guide - School LMS Updates

## 🎨 Color Scheme
All portals now use this professional color palette:

```
Dark Navy:      #01172a  (sidebar, primary background)
Dark Gray:      #1f2937  (table headers, accents)
Medium Gray:    #6b7280  (secondary text)
Orange:         #f97316  (primary action, buttons, borders)
Light Peach:    #fed7aa  (badges, highlights)
```

## 📍 What Changed?

### Teacher Portal (`/teacher/`)
- ✅ Dark navy sidebar with white text
- ✅ Orange active menu items
- ✅ Orange accents on cards and borders
- ✅ All pages automatically updated via teacher_layout.php

### Student Portal (`/student/`)
- ✅ Dark navy sidebar matching teacher style
- ✅ **NEW**: Course filters (search, instructor, term)
- ✅ Orange FAB (+) button for joining courses
- ✅ All pages automatically updated via student_layout.php

### Admin Portal (`/admin/`)
- ✅ Dark navy sidebar with submenu support
- ✅ Orange accents on tables and cards
- ✅ All pages automatically updated via admin_layout.php

### Announcements (`/teacher/announcements.php`)
- ✅ **NEW**: Image upload support (JPG, PNG, GIF, WebP - max 5MB)
- ✅ **NEW**: External link field for resources
- ✅ Images display in announcement cards
- ✅ Links shown with icon (🔗)
- ✅ Images auto-deleted when announcement removed

## 🔧 Where to Find Things

### Layout Files (Control Styling)
- `includes/teacher_layout.php` - Teacher portal styling
- `includes/student_layout.php` - Student portal styling
- `includes/admin_layout.php` - Admin portal styling

### Feature Files (Main Implementation)
- `student/courses.php` - Course listing with NEW filters
- `teacher/announcements.php` - Announcements with NEW image/link support

### Database
- `database/migrations/add_image_link_to_announcements.sql` - Schema migration
- Columns added: `image_path` (varchar 255), `external_link` (varchar 500)

### File Uploads
- Upload directory: `assets/uploads/announcements/`
- Files created with unique names: `announcement_[timestamp]_[uniqid].[ext]`

## 💡 Using New Features

### Student: Filtering Courses
1. Go to Student > Courses
2. Use the "Filter Courses" section at the top
3. Search by title/code, select instructor, select term
4. Click "Filter" to apply or "Reset" to clear filters

### Teacher: Adding Images to Announcements
1. Go to Teacher > Announcements
2. Fill in course, title, content (required)
3. Click "Attach Image" to upload a photo (optional, max 5MB)
4. Add external link if needed (optional)
5. Check "Pin this announcement" if desired
6. Click "Post Announcement"

## 🎯 Key URLs

### Teacher Pages
- `/teacher/dashboard.php` - Dashboard
- `/teacher/courses.php` - Course management
- `/teacher/assignments.php` - Assignment management
- `/teacher/announcements.php` - Announcements with images & links
- `/teacher/materials.php` - Course materials
- `/teacher/quizzes.php` - Quiz management
- `/teacher/grades.php` - Grade management
- `/teacher/students.php` - Student list

### Student Pages
- `/student/dashboard.php` - Dashboard
- `/student/courses.php` - My courses with filters ⭐
- `/student/assignments.php` - My assignments
- `/student/announcements.php` - Course announcements
- `/student/grades.php` - My grades
- `/student/tasks.php` - My tasks

### Admin Pages
- `/admin/dashboard.php` - Dashboard
- `/admin/courses.php` - All courses
- `/admin/enrollments.php` - All enrollments
- `/admin/reports.php` - Reports
- `/admin/terms.php` - Academic terms
- `/admin/users.php` - All users

## 🔐 Security Notes

- All file uploads validated by MIME type
- File size limited to 5MB for images
- URLs validated using PHP's filter_var
- Unique filenames prevent conflicts
- Files stored outside web root protection
- SQL prepared statements throughout
- Files automatically cleaned up on deletion

## 📊 CSS Class Reference

### Common Classes (All Layouts)
- `.btn-primary` - Orange button
- `.btn-secondary` - Gray button
- `.btn-danger` - Red button
- `.badge` - Light peach background badge
- `.stat-card` - Card with orange top border
- `.data-table` - Table with dark header and orange border
- `.alert-success` - Green success message
- `.alert-error` - Red error message

### Color Variables
```css
--primary-color: #f97316     (orange)
--primary-dark: #ea580c      (dark orange)
--bg-sidebar: #01172a        (dark navy)
--text-primary: #01172a      (dark navy text)
--text-secondary: #6b7280    (gray text)
--border-color: #e5e7eb      (light border)
```

## ✅ Testing Checklist

- [ ] Teacher page shows dark navy sidebar
- [ ] Orange buttons and accents visible
- [ ] Student can filter courses by title
- [ ] Student can filter courses by instructor
- [ ] Student can filter courses by term
- [ ] Filter reset button works
- [ ] Teacher can upload image to announcement
- [ ] Teacher can add external link to announcement
- [ ] Images display in announcements
- [ ] Links are clickable in announcements
- [ ] Images deleted when announcement removed
- [ ] All three portals (teacher/student/admin) have consistent colors

## 📞 Support

If you encounter issues:
1. Check that layout files are included correctly
2. Verify database columns were added (check with `DESCRIBE announcements;`)
3. Ensure `assets/uploads/announcements/` directory exists and is writable
4. Check file permissions on upload directory
5. Review browser console for JavaScript errors
