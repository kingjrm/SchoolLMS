<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';

Auth::requireRole('admin');

adminLayoutStart('reports', 'Reports & Analytics');
?>

    <div style="max-width: 1400px; margin: 0 auto;">
        <!-- Enrollment Statistics by Term -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700; display:flex; align-items:center; gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Enrollment Statistics by Term
                </h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->query("
                        SELECT 
                            t.id, t.name,
                            COUNT(DISTINCT c.id) as course_count,
                            COUNT(DISTINCT e.student_id) as student_count,
                            COUNT(DISTINCT e.id) as total_enrollments,
                            AVG(CAST(g.score as DECIMAL(5,2))) as avg_grade
                        FROM academic_terms t
                        LEFT JOIN courses c ON t.id = c.term_id
                        LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
                        LEFT JOIN grades g ON e.student_id = g.student_id
                        GROUP BY t.id, t.name
                        ORDER BY t.start_date DESC
                    ");
                    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $enrollments = [];
                }

                if (!empty($enrollments)):
                ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Term</th>
                                <th>Courses</th>
                                <th>Students</th>
                                <th>Total Enrollments</th>
                                <th>Avg Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $term): ?>
                            <tr>
                                <td style="font-weight: 600;">
                                    <?php echo htmlspecialchars($term['name']); ?>
                                </td>
                                <td>
                                    <span class="chip"><?php echo $term['course_count']; ?> courses</span>
                                </td>
                                <td>
                                    <span class="chip"><?php echo $term['student_count']; ?> students</span>
                                </td>
                                <td>
                                    <span style="display: inline-block; padding: 0.25rem 0.75rem; background: #eff6ff; color: #1e40af; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600;">
                                        <?php echo $term['total_enrollments']; ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: #3b82f6;">
                                    <?php echo $term['avg_grade'] ? round($term['avg_grade'], 2) . '%' : 'N/A'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Course Performance Report -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700; display:flex; align-items:center; gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 17 9 11 13 15 21 7"/><polyline points="14 7 21 7 21 14"/></svg>
                    Course Performance Report
                </h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->query("
                        SELECT 
                            c.id, c.code, c.title,
                            COUNT(DISTINCT e.student_id) as enrolled_students,
                            COUNT(DISTINCT a.id) as assignment_count,
                            ROUND(AVG(CAST(g.score as DECIMAL(5,2))), 2) as avg_student_grade,
                            (SELECT COUNT(*) FROM assignments WHERE course_id = c.id AND due_date < NOW()) as overdue_assignments
                        FROM courses c
                        LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
                        LEFT JOIN assignments a ON c.id = a.course_id
                        LEFT JOIN grades g ON a.id = g.assignment_id
                        WHERE c.status = 'active'
                        GROUP BY c.id, c.code, c.title
                        ORDER BY enrolled_students DESC
                    ");
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
                                <th>Course Title</th>
                                <th>Enrolled Students</th>
                                <th>Assignments</th>
                                <th>Avg Grade</th>
                                <th>Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td style="font-weight: 600;">
                                    <?php echo htmlspecialchars($course['code']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </td>
                                <td>
                                    <span class="chip"><?php echo $course['enrolled_students']; ?></span>
                                </td>
                                <td>
                                    <?php echo $course['assignment_count']; ?>
                                </td>
                                <td style="font-weight: 700; color: #3b82f6;">
                                    <?php echo $course['avg_student_grade'] ?? 'N/A'; ?>%
                                </td>
                                <td>
                                    <span style="display: inline-block; padding: 0.25rem 0.75rem; background: 
                                        <?php echo $course['overdue_assignments'] > 0 ? '#fef2f2;' : '#ecfdf5;'; ?>
                                        ; color: 
                                        <?php echo $course['overdue_assignments'] > 0 ? '#991b1b;' : '#065f46;'; ?>
                                        ; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600;">
                                        <?php echo $course['overdue_assignments']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Performance Summary -->
        <div class="card">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700; display:flex; align-items:center; gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Student Performance Summary
                </h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->query("
                        SELECT 
                            u.id, u.username, u.first_name, u.last_name, u.email,
                            COUNT(DISTINCT e.course_id) as enrolled_courses,
                            COUNT(DISTINCT asub.id) as submissions,
                            ROUND(AVG(CAST(g.score as DECIMAL(5,2))), 2) as avg_grade,
                            COUNT(DISTINCT CASE WHEN g.score < 60 THEN 1 END) as failing_count
                        FROM users u
                        LEFT JOIN enrollments e ON u.id = e.student_id AND e.status = 'enrolled'
                        LEFT JOIN assignments a ON e.course_id = a.course_id
                        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND u.id = asub.student_id
                        LEFT JOIN grades g ON asub.assignment_id = g.assignment_id AND u.id = g.student_id
                        WHERE u.role = 'student'
                        GROUP BY u.id, u.username, u.first_name, u.last_name, u.email
                        ORDER BY u.first_name, u.last_name
                        LIMIT 20
                    ");
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $students = [];
                }

                if (!empty($students)):
                ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Enrolled Courses</th>
                                <th>Submissions</th>
                                <th>Avg Grade</th>
                                <th>Failing Grades</th>
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
                                    <span class="chip"><?php echo $student['enrolled_courses']; ?></span>
                                </td>
                                <td>
                                    <?php echo $student['submissions']; ?>
                                </td>
                                <td style="font-weight: 700;">
                                    <span style="color: 
                                        <?php 
                                        if ($student['avg_grade'] >= 80) echo '#10b981;';
                                        elseif ($student['avg_grade'] >= 60) echo '#f59e0b;';
                                        else echo '#ef4444;';
                                        ?>
                                    ">
                                        <?php echo $student['avg_grade'] ?? 'N/A'; ?>%
                                    </span>
                                </td>
                                <td>
                                    <span style="display: inline-block; padding: 0.25rem 0.75rem; background: 
                                        <?php echo $student['failing_count'] > 0 ? '#fef2f2;' : '#ecfdf5;'; ?>
                                        ; color: 
                                        <?php echo $student['failing_count'] > 0 ? '#991b1b;' : '#065f46;'; ?>
                                        ; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600;">
                                        <?php echo $student['failing_count']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php adminLayoutEnd(); ?>
