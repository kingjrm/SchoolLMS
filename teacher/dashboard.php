<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

try {
    // Total courses
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses WHERE teacher_id = ? AND status = 'active'");
    $stmt->execute([$teacher_id]);
    $total_courses = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total students enrolled
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.student_id) as count 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.teacher_id = ? AND e.status = 'enrolled'
    ");
    $stmt->execute([$teacher_id]);
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Pending assignments
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT a.id) as count 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE c.teacher_id = ? AND a.due_date >= NOW()
    ");
    $stmt->execute([$teacher_id]);
    $pending_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Ungraded submissions
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT asub.id) as count 
        FROM assignment_submissions asub 
        JOIN assignments a ON asub.assignment_id = a.id 
        JOIN courses c ON a.course_id = c.id 
        WHERE c.teacher_id = ? AND asub.status = 'submitted'
    ");
    $stmt->execute([$teacher_id]);
    $ungraded = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $total_courses = $total_students = $pending_assignments = $ungraded = 0;
}

teacherLayoutStart('dashboard', 'Dashboard');
?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_courses; ?></div>
                <div class="stat-label">Active Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $pending_assignments; ?></div>
                <div class="stat-label">Upcoming Assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f97316;"><?php echo $ungraded; ?></div>
                <div class="stat-label">Ungraded Submissions</div>
            </div>
        </div>

        <!-- Recent Courses -->
        <div class="card" style="margin-top: 2rem;">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700;">📚 My Courses</h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT c.*, t.name as term_name,
                        (SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE course_id = c.id AND status = 'enrolled') as student_count,
                        (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
                        FROM courses c
                        JOIN academic_terms t ON c.term_id = t.id
                        WHERE c.teacher_id = ? AND c.status = 'active'
                        ORDER BY c.created_at DESC
                    ");
                    $stmt->execute([$teacher_id]);
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $courses = [];
                }

                if (!empty($courses)):
                ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Title</th>
                                <th>Term</th>
                                <th>Students</th>
                                <th>Assignments</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($course['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo htmlspecialchars($course['term_name']); ?></td>
                                <td><span class="chip"><?php echo $course['student_count']; ?> students</span></td>
                                <td><?php echo $course['assignment_count']; ?></td>
                                <td>
                                    <a href="course-students.php?id=<?php echo $course['id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                        Manage →
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">You have no active courses</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ungraded Submissions -->
        <div class="card" style="margin-top: 2rem;">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700;">⏳ Ungraded Submissions</h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT asub.*, a.title as assignment_title, c.code as course_code, u.first_name, u.last_name
                        FROM assignment_submissions asub
                        JOIN assignments a ON asub.assignment_id = a.id
                        JOIN courses c ON a.course_id = c.id
                        JOIN users u ON asub.student_id = u.id
                        WHERE c.teacher_id = ? AND asub.status = 'submitted'
                        ORDER BY asub.submitted_at DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$teacher_id]);
                    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $submissions = [];
                }

                if (!empty($submissions)):
                ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Assignment</th>
                                <th>Course</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($sub['assignment_title']); ?></td>
                                <td><?php echo htmlspecialchars($sub['course_code']); ?></td>
                                <td style="font-size: 0.85rem; color: #6b7280;"><?php echo date('M d, Y', strtotime($sub['submitted_at'])); ?></td>
                                <td>
                                    <a href="grade-submission.php?id=<?php echo $sub['id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                        Grade →
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">All submissions graded!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php teacherLayoutEnd(); ?>
