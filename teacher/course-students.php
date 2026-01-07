<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];
$course_id = (int)($_GET['id'] ?? 0);

// Verify course ownership
try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $teacher_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        header('Location: courses.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: courses.php');
    exit;
}

teacherLayoutStart('students', 'Student Management');
?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="card" style="margin-bottom: 2rem;">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; font-weight: 700;">
                            👥 Manage Students: <?php echo htmlspecialchars($course['code']); ?>
                        </h3>
                        <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </p>
                    </div>
                    <a href="courses.php" style="color: #3b82f6; text-decoration: none; font-weight: 600;">← Back to Courses</a>
                </div>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT e.*, u.id as user_id, u.first_name, u.last_name, u.email, cs.section_code,
                        ROUND(AVG(CAST(g.score as DECIMAL(5,2))), 2) as avg_grade,
                        (SELECT COUNT(*) FROM assignment_submissions 
                         WHERE student_id = u.id AND assignment_id IN 
                         (SELECT id FROM assignments WHERE course_id = ?)) as submissions,
                        (SELECT COUNT(*) FROM assignment_submissions 
                         WHERE student_id = u.id AND assignment_id IN 
                         (SELECT id FROM assignments WHERE course_id = ?) AND status = 'graded') as graded
                        FROM enrollments e
                        JOIN users u ON e.student_id = u.id
                        LEFT JOIN course_sections cs ON e.section_id = cs.id
                        LEFT JOIN grades g ON g.student_id = u.id AND g.assignment_id IN 
                            (SELECT id FROM assignments WHERE course_id = ?)
                        WHERE e.course_id = ? AND e.status = 'enrolled'
                        GROUP BY e.id, u.id
                        ORDER BY u.last_name, u.first_name
                    ");
                    $stmt->execute([$course_id, $course_id, $course_id, $course_id]);
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $students = [];
                }

                if (!empty($students)):
                ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Section</th>
                                    <th>Enrolled</th>
                                    <th>Submissions</th>
                                    <th>Avg Grade</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td style="font-weight: 600;">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </td>
                                    <td style="font-size: 0.875rem; color: #6b7280;">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </td>
                                    <td>
                                        <?php echo $student['section_code'] ? htmlspecialchars($student['section_code']) : '<span style="color: #9ca3af;">—</span>'; ?>
                                    </td>
                                    <td style="font-size: 0.85rem; color: #6b7280;">
                                        <?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="display: inline-block; padding: 0.25rem 0.75rem; background: #eff6ff; color: #1e40af; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600;">
                                            <?php echo $student['graded']; ?>/<?php echo $student['submissions']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 700;">
                                        <?php if ($student['avg_grade']): ?>
                                            <span style="color: #10b981;"><?php echo $student['avg_grade']; ?>%</span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="student-progress.php?course=<?php echo $course_id; ?>&student=<?php echo $student['user_id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                            View →
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p style="margin-top: 1rem; font-size: 0.85rem; color: #6b7280; text-align: right;">
                        Total Enrolled: <strong><?php echo count($students); ?></strong> students
                    </p>
                <?php else: ?>
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">No students enrolled in this course yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php teacherLayoutEnd(); ?>
