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

    // Get course enrollment data for chart
    $stmt = $pdo->prepare("
        SELECT c.title, COUNT(DISTINCT e.student_id) as count
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
        WHERE c.teacher_id = ? AND c.status = 'active'
        GROUP BY c.id, c.title
        ORDER BY count DESC
        LIMIT 6
    ");
    $stmt->execute([$teacher_id]);
    $course_enrollment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get assignment submission stats
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN asub.status = 'submitted' THEN 1 ELSE 0 END) as submitted,
            SUM(CASE WHEN asub.status = 'graded' THEN 1 ELSE 0 END) as graded,
            SUM(CASE WHEN asub.status IN ('submitted', 'graded') THEN 1 ELSE 0 END) as total
        FROM assignment_submissions asub
        JOIN assignments a ON asub.assignment_id = a.id
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = ?
    ");
    $stmt->execute([$teacher_id]);
    $submission_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $total_courses = $total_students = $pending_assignments = $ungraded = 0;
    $course_enrollment = [];
    $submission_stats = ['submitted' => 0, 'graded' => 0, 'total' => 0];
}

teacherLayoutStart('dashboard', 'Dashboard');
?>

    <div style="max-width:1400px;margin:0 auto;padding:1rem;">
        <!-- Stats Grid -->
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin-bottom:2rem;">
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
                <div class="stat-value" style="color:#f97316"><?php echo $ungraded; ?></div>
                <div class="stat-label">Ungraded Submissions</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
            <!-- Enrollment Chart -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin:0;font-size:0.95rem;font-weight:700;">Students per Course</h3>
                </div>
                <div style="padding:1.5rem;">
                    <div style="display:flex;flex-direction:column;gap:1rem;">
                        <?php 
                        $max = max(array_column($course_enrollment, 'count') ?: [1]);
                        foreach ($course_enrollment as $course):
                            $pct = ($course['count'] / $max) * 100;
                        ?>
                            <div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem;font-size:0.8rem;">
                                    <span title="<?php echo htmlspecialchars($course['title']); ?>"><?php echo htmlspecialchars(substr($course['title'], 0, 18)); ?></span>
                                    <strong><?php echo $course['count']; ?></strong>
                                </div>
                                <div style="background:#e5e7eb;height:20px;border-radius:0.3rem;overflow:hidden;">
                                    <div style="background:#f97316;height:100%;width:<?php echo $pct; ?>%;transition:all 0.3s;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Submission Status -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin:0;font-size:0.95rem;font-weight:700;">Assignment Submissions</h3>
                </div>
                <div style="padding:2rem;display:flex;justify-content:space-around;align-items:center;">
                    <?php 
                    $submitted = $submission_stats['submitted'] ?? 0;
                    $graded = $submission_stats['graded'] ?? 0;
                    $total = $submission_stats['total'] ?? 0;
                    ?>
                    <div style="text-align:center;">
                        <div style="font-size:2.5rem;font-weight:700;color:#f97316;margin-bottom:0.5rem;"><?php echo $submitted; ?></div>
                        <div style="font-size:0.8rem;color:#6b7280;">Pending Review</div>
                    </div>
                    <div style="width:1px;height:80px;background:#e5e7eb;"></div>
                    <div style="text-align:center;">
                        <div style="font-size:2.5rem;font-weight:700;color:#10b981;margin-bottom:0.5rem;"><?php echo $graded; ?></div>
                        <div style="font-size:0.8rem;color:#6b7280;">Graded</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Courses Table -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <h2 style="margin:0;font-weight:700;font-size:0.95rem;">My Courses</h2>
            </div>
            <div style="padding:0;">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT c.*, t.name as term_name,
                        (SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE course_id = c.id AND status = 'enrolled') as student_count,
                        (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
                        FROM courses c
                        LEFT JOIN academic_terms t ON c.term_id = t.id
                        WHERE c.teacher_id = ? AND c.status = 'active'
                        ORDER BY c.created_at DESC
                        LIMIT 8
                    ");
                    $stmt->execute([$teacher_id]);
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $courses = [];
                }

                if (!empty($courses)):
                ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Term</th>
                                    <th>Students</th>
                                    <th>Assignments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo htmlspecialchars($course['term_name'] ?? 'N/A'); ?></td>
                                    <td><span class="badge"><?php echo $course['student_count']; ?></span></td>
                                    <td><?php echo $course['assignment_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align:center;color:#9ca3af;padding:2rem;">No active courses</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ungraded Submissions -->
        <div class="card">
            <div class="card-header">
                <h2 style="margin:0;font-weight:700;font-size:0.95rem;">Recent Ungraded Submissions</h2>
            </div>
            <div style="padding:0;">
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
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Assignment</th>
                                    <th>Course</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['assignment_title']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($sub['course_code']); ?></span></td>
                                    <td style="font-size:0.85rem;color:#6b7280;"><?php echo date('M d', strtotime($sub['submitted_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align:center;color:#9ca3af;padding:2rem;">All submissions graded! ✓</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php teacherLayoutEnd(); ?>
