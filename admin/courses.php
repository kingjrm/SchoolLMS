<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/admin_layout.php';
require_once '../includes/CodeGenerator.php';
require_once '../includes/ActivityLogger.php';

Auth::requireRole('admin');
$db = new Database();
$message = '';
$action = $_GET['action'] ?? 'list';

// Regenerate join code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regen_join_code'])) {
    $cid = (int)($_POST['course_id'] ?? 0);
    if ($cid) {
        try {
            $newCode = CodeGenerator::generateUniqueJoinCode($pdo);
            $stmt = $pdo->prepare("UPDATE courses SET join_code = ? WHERE id = ?");
            $stmt->execute([$newCode, $cid]);
            $message = 'Join code regenerated';
            ActivityLogger::log($pdo, 'Regenerated join code', ['course_id'=>$cid, 'join_code'=>$newCode]);
            $action = 'edit';
            $_GET['id'] = (string)$cid;
        } catch (Exception $e) {
            $message = 'Error regenerating join code';
        }
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $term_id = (int)($_POST['term_id'] ?? 0);
    $credits = (int)($_POST['credits'] ?? 0);
    $max_students = (int)($_POST['max_students'] ?? 0);
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : null;

    if ($code === '' || $title === '' || !$teacher_id || !$term_id) {
        $message = 'Required fields are missing';
        $action = $course_id ? 'edit' : 'add';
    } else {
        try {
            if ($course_id) {
                $stmt = $pdo->prepare("UPDATE courses SET code=?, title=?, description=?, teacher_id=?, term_id=?, credits=?, max_students=? WHERE id=?");
                $stmt->execute([$code, $title, $description, $teacher_id, $term_id, $credits, $max_students, $course_id]);
                $message = 'Course updated successfully';
                ActivityLogger::log($pdo, 'Updated course', ['course_id'=>$course_id, 'code'=>$code]);
                $action = 'list';
            } else {
                // Generate unique join code
                $joinCode = CodeGenerator::generateUniqueJoinCode($pdo);
                $stmt = $pdo->prepare("INSERT INTO courses (code, title, description, teacher_id, term_id, credits, max_students, join_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$code, $title, $description, $teacher_id, $term_id, $credits, $max_students, $joinCode]);
                $message = 'Course created successfully';
                ActivityLogger::log($pdo, 'Created course', ['code'=>$code, 'title'=>$title]);
                $action = 'list';
            }
        } catch (Exception $e) {
            $message = 'Error saving course';
            $action = $course_id ? 'edit' : 'add';
        }
    }
}

// Prefetch edit course
$edit_course = null;
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $edit_course = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $edit_course = null;
    }
}

// Sections CRUD for a course
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    // Add section
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
        $section_code = strtoupper(trim($_POST['section_code'] ?? ''));
        $max_students = (int)($_POST['max_students'] ?? 30);
        if ($section_code !== '' && $max_students > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO course_sections (course_id, section_code, max_students, status) VALUES (?, ?, ?, 'active')");
                $stmt->execute([$editId, $section_code, $max_students]);
                $message = 'Section added';
                ActivityLogger::log($pdo, 'Added course section', ['course_id'=>$editId, 'section_code'=>$section_code, 'max_students'=>$max_students]);
            } catch (Exception $e) { $message = 'Error adding section'; }
        }
    }
    // Update section status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_section'])) {
        $sid = (int)($_POST['section_id'] ?? 0);
        $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
        try {
            $stmt = $pdo->prepare("UPDATE course_sections SET status = ? WHERE id = ? AND course_id = ?");
            $stmt->execute([$newStatus, $sid, $editId]);
            $message = 'Section status updated';
            ActivityLogger::log($pdo, 'Updated section status', ['course_id'=>$editId, 'section_id'=>$sid, 'status'=>$newStatus]);
        } catch (Exception $e) { $message = 'Error updating section status'; }
    }
    // Update max students
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_capacity'])) {
        $sid = (int)($_POST['section_id'] ?? 0);
        $max = (int)($_POST['max_students'] ?? 0);
        if ($max > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE course_sections SET max_students = ? WHERE id = ? AND course_id = ?");
                $stmt->execute([$max, $sid, $editId]);
                $message = 'Section capacity updated';
                ActivityLogger::log($pdo, 'Updated section capacity', ['course_id'=>$editId, 'section_id'=>$sid, 'max_students'=>$max]);
            } catch (Exception $e) { $message = 'Error updating capacity'; }
        }
    }
    // Delete section
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_section'])) {
        $sid = (int)($_POST['section_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM course_sections WHERE id = ? AND course_id = ?");
            $stmt->execute([$sid, $editId]);
            $message = 'Section deleted';
            ActivityLogger::log($pdo, 'Deleted section', ['course_id'=>$editId, 'section_id'=>$sid]);
        } catch (Exception $e) { $message = 'Error deleting section'; }
    }
}

// Archive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    try {
        $db->prepare("UPDATE courses SET status = 'archived' WHERE id = ?")->bind('i', (int)$_POST['archive_id'])->execute();
        $message = 'Course archived successfully';
        ActivityLogger::log($pdo, 'Archived course', ['course_id'=>(int)$_POST['archive_id']]);
    } catch (Exception $e) {
        $message = 'Error archiving course';
    }
}

adminLayoutStart('courses', 'Courses');
?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <?php if ($message): ?>
            <div style="padding:.75rem; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; border-radius:.5rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
        <div class="card">
            <div style="padding:.9rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M20 22H6.5a2.5 2.5 0 0 1 0-5H20z"/><path d="M14 17V2H6a2 2 0 0 0-2 2v13"/></svg>
                    Active Courses
                </h3>
                <a href="?action=add" style="padding:.45rem .7rem; background:#3b82f6; color:#fff; border-radius:.5rem; text-decoration:none; font-size:.8rem;">Add Course</a>
            </div>
            <div style="padding:1rem;">
                <?php $db->prepare("SELECT c.*, u.first_name, u.last_name, t.name as term_name FROM courses c JOIN users u ON c.teacher_id=u.id JOIN academic_terms t ON c.term_id=t.id WHERE c.status='active' ORDER BY c.created_at DESC")->execute();
                $courses = $db->fetchAll(); ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Teacher</th>
                                <th>Term</th>
                                <th>Credits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['code']); ?></td>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['term_name']); ?></td>
                                <td><?php echo (int)$course['credits']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Archive this course?');">
                                        <input type="hidden" name="archive_id" value="<?php echo (int)$course['id']; ?>">
                                        <button type="submit" style="padding:.35rem .6rem; border:1px solid #e5e7eb; background:#fff; border-radius:.4rem; font-size:.78rem; cursor:pointer;">Archive</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($courses)): ?>
                    <p style="text-align:center; color:#9ca3af; padding:1rem;">No active courses</p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div style="padding:.9rem; border-bottom:1px solid #e5e7eb;">
                <h3 style="margin:0; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:.5rem;">
                    <?php if ($action==='edit'): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Edit Course
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Course
                    <?php endif; ?>
                </h3>
            </div>
            <div style="padding:1rem;">
                <form method="POST">
                    <?php if ($edit_course): ?>
                        <input type="hidden" name="course_id" value="<?php echo (int)$edit_course['id']; ?>">
                    <?php endif; ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:.75rem;">
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Course Code</label>
                            <input type="text" name="code" value="<?php echo htmlspecialchars($edit_course['code'] ?? ''); ?>" required style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                        </div>
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Course Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($edit_course['title'] ?? ''); ?>" required style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                        </div>
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Teacher</label>
                            <select name="teacher_id" required style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                                <option value="">Select Teacher</option>
                                <?php
                                try { $tstmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role='teacher' AND status='active' ORDER BY first_name");
                                      $teachers = $tstmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (Exception $e) { $teachers = []; }
                                foreach ($teachers as $t): ?>
                                    <option value="<?php echo (int)$t['id']; ?>" <?php echo (($edit_course['teacher_id'] ?? 0)==$t['id'])?'selected':''; ?>><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Term</label>
                            <select name="term_id" required style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                                <option value="">Select Term</option>
                                <?php
                                try { $tstmt = $pdo->query("SELECT id, name FROM academic_terms ORDER BY start_date DESC");
                                      $terms = $tstmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (Exception $e) { $terms = []; }
                                foreach ($terms as $term): ?>
                                    <option value="<?php echo (int)$term['id']; ?>" <?php echo (($edit_course['term_id'] ?? 0)==$term['id'])?'selected':''; ?>><?php echo htmlspecialchars($term['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Credits</label>
                            <input type="number" name="credits" value="<?php echo htmlspecialchars((string)($edit_course['credits'] ?? '')); ?>" min="0" style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                        </div>
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Max Students</label>
                            <input type="number" name="max_students" value="<?php echo htmlspecialchars((string)($edit_course['max_students'] ?? '')); ?>" min="0" style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                        </div>
                    </div>
                    <div style="margin-top:.75rem;">
                        <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Description</label>
                        <textarea name="description" rows="4" style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem; resize:vertical;"><?php echo htmlspecialchars($edit_course['description'] ?? ''); ?></textarea>
                    </div>
                    <div style="display:flex; gap:.5rem; margin-top:.9rem;">
                        <button type="submit" name="save_course" class="btn btn-primary" style="padding:.5rem .8rem;">Save</button>
                        <a href="courses.php" class="btn btn-secondary" style="padding:.5rem .8rem;">Cancel</a>
                    </div>
                </form>
                <?php if ($edit_course): ?>
                <hr style="border:none; border-top:1px solid #e5e7eb; margin:1rem 0;">
                <div style="display:grid; grid-template-columns: 1fr auto; align-items:center; gap:.75rem; margin-bottom:.5rem;">
                    <div>
                        <div style="font-size:.75rem; color:#6b7280;">Join Code</div>
                        <div style="font-family:monospace; font-weight:700; color:#ef4444; letter-spacing:1px;"><?php echo htmlspecialchars($edit_course['join_code'] ?? '—'); ?></div>
                    </div>
                    <form method="POST" style="display:flex; gap:.5rem;">
                        <input type="hidden" name="course_id" value="<?php echo (int)$edit_course['id']; ?>">
                        <button type="submit" name="regen_join_code" style="padding:.4rem .6rem; border:1px solid #e5e7eb; background:#fff; border-radius:.5rem; font-size:.78rem; cursor:pointer;">Regenerate</button>
                    </form>
                </div>
                <div style="margin-top:.75rem;">
                    <h4 style="margin:0 0 .5rem 0; font-size:.95rem;">Sections</h4>
                    <form method="POST" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:.5rem; align-items:end; margin-bottom:.75rem;">
                        <input type="hidden" name="course_id" value="<?php echo (int)$edit_course['id']; ?>">
                        <div>
                            <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Section Code</label>
                            <input type="text" name="section_code" maxlength="10" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;" required>
                        </div>
                        <div>
                            <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Max Students</label>
                            <input type="number" name="max_students" min="1" value="30" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;" required>
                        </div>
                        <div>
                            <button type="submit" name="add_section" style="padding:.5rem .75rem; background:#3b82f6; color:#fff; border:none; border-radius:.5rem; font-weight:600;">Add Section</button>
                        </div>
                    </form>
                    <?php
                    try {
                        $sstmt = $pdo->prepare("SELECT * FROM course_sections WHERE course_id = ? ORDER BY section_code");
                        $sstmt->execute([(int)$edit_course['id']]);
                        $sections = $sstmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) { $sections = []; }
                    ?>
                    <div style="overflow-x:auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sections as $sec): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sec['section_code']); ?></td>
                                    <td><?php echo (int)$sec['current_students']; ?> / <?php echo (int)$sec['max_students']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $sec['status']==='active' ? 'badge-medium' : 'badge-low'; ?>"><?php echo htmlspecialchars($sec['status']); ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline; margin-right:.25rem;">
                                            <input type="hidden" name="course_id" value="<?php echo (int)$edit_course['id']; ?>">
                                            <input type="hidden" name="section_id" value="<?php echo (int)$sec['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $sec['status']==='active'?'inactive':'active'; ?>">
                                            <button type="submit" name="toggle_section" style="padding:.35rem .6rem; border:1px solid #e5e7eb; background:#fff; border-radius:.4rem; font-size:.78rem; cursor:pointer;">
                                                <?php echo $sec['status']==='active'?'Deactivate':'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline; margin-right:.25rem;">
                                            <input type="hidden" name="course_id" value="<?php echo (int)$edit_course['id']; ?>">
                                            <input type="hidden" name="section_id" value="<?php echo (int)$sec['id']; ?>">
                                            <input type="number" name="max_students" min="1" value="<?php echo (int)$sec['max_students']; ?>" style="width:80px; padding:.3rem .4rem; border:1px solid #e5e7eb; border-radius:.4rem;">
                                            <button type="submit" name="update_capacity" style="padding:.35rem .6rem; border:1px solid #e5e7eb; background:#fff; border-radius:.4rem; font-size:.78rem; cursor:pointer;">Update</button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this section?');">
                                            <input type="hidden" name="course_id" value="<?php echo (int)$edit_course['id']; ?>">
                                            <input type="hidden" name="section_id" value="<?php echo (int)$sec['id']; ?>">
                                            <button type="submit" name="delete_section" style="padding:.35rem .6rem; border:1px solid #e5e7eb; background:#fff; border-radius:.4rem; font-size:.78rem; cursor:pointer;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

<?php adminLayoutEnd(); ?>
 
