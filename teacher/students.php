<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

// Get filter values
$search = $_GET['search'] ?? '';
$filter_course = $_GET['course'] ?? '';

teacherLayoutStart('students', 'Students');
?>

<!-- Compact Filter Bar -->
<div style="display:flex;gap:0.5rem;margin-bottom:0.75rem;align-items:center;">
    <form method="GET" style="display:flex;gap:0.5rem;flex:1;align-items:center;">
        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="padding:0.35rem 0.5rem;border:1.5px solid #e5e7eb;border-radius:0.3rem;font-size:0.75rem;flex:1;">
        <select name="course" style="padding:0.35rem 0.5rem;border:1.5px solid #e5e7eb;border-radius:0.3rem;font-size:0.75rem;min-width:140px;">
            <option value="">All Courses</option>
            <?php
            try {
                $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY title");
                $stmt->execute([$teacher_id]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c):
            ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($_GET['course'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['title']); ?>
                    </option>
            <?php endforeach; } catch (Exception $e) {} ?>
        </select>
        <button type="submit" class="btn btn-primary" style="padding:0.35rem 0.7rem;font-size:0.75rem;">Filter</button>
        <a href="students.php" class="btn btn-secondary" style="padding:0.35rem 0.7rem;text-decoration:none;font-size:0.75rem;">Reset</a>
    </form>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Enrolled Students</h2>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Enrollment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $search = $_GET['search'] ?? '';
                    $filter_course = $_GET['course'] ?? '';
                    
                    $where = ['c.teacher_id = ?', 'e.status = ?'];
                    $params = [$teacher_id, 'enrolled'];
                    
                    if ($search) {
                        $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
                        $s = '%' . $search . '%';
                        $params[] = $s;
                        $params[] = $s;
                        $params[] = $s;
                    }
                    
                    if ($filter_course) {
                        $where[] = "c.id = ?";
                        $params[] = (int)$filter_course;
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, c.title as course_title, e.enrollment_date
                        FROM enrollments e
                        JOIN users u ON e.student_id = u.id
                        JOIN courses c ON e.course_id = c.id
                        WHERE " . implode(" AND ", $where) . "
                        ORDER BY c.title, u.first_name, u.last_name
                    ");
                    $stmt->execute($params);
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($students)):
                        foreach ($students as $student):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['course_title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                        </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <tr><td colspan="4" style="text-align:center;color:#9ca3af;">No students found</td></tr>
                    <?php 
                    endif;
                } catch (Exception $e) {
                    echo '<tr><td colspan="4" style="color:red;">Error loading students</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php teacherLayoutEnd(); ?>
