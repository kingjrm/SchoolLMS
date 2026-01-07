<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';

Auth::requireRole('admin');

adminLayoutStart('enrollments', 'Enrollment Management');
?>

    <div style="max-width: 1400px; margin: 0 auto;">
        <div class="card">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700; display:flex; align-items:center; gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5h12"/><path d="M9 12h12"/><path d="M9 19h12"/><path d="M5 5h.01"/><path d="M5 12h.01"/><path d="M5 19h.01"/></svg>
                    Student Enrollments by Course & Section
                </h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT c.id, c.code, c.title, t.name as term_name, u.first_name, u.last_name,
                        (SELECT COUNT(DISTINCT e.student_id) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'enrolled') as total_enrolled,
                        (SELECT COUNT(DISTINCT cs.id) FROM course_sections cs WHERE cs.course_id = c.id AND cs.status = 'active') as total_sections
                        FROM courses c
                        JOIN academic_terms t ON c.term_id = t.id
                        JOIN users u ON c.teacher_id = u.id
                        WHERE c.status = 'active' AND t.is_active = TRUE
                        ORDER BY t.name DESC, c.code ASC
                    ");
                    $stmt->execute();
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $courses = [];
                }

                if (!empty($courses)):
                    foreach ($courses as $course):
                ?>
                    <div style="margin-bottom: 2rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fafbfc;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0 0 0.25rem 0;">
                                    <?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?>
                                </h4>
                                <p style="margin: 0; font-size: 0.85rem; color: #6b7280;">
                                    Instructor: <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?> | 
                                    Term: <?php echo htmlspecialchars($course['term_name']); ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: #3b82f6;">
                                    <?php echo $course['total_enrolled']; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #6b7280;">
                                    Enrolled students
                                </div>
                            </div>
                        </div>

                        <?php if ($course['total_sections'] > 0): ?>
                            <table class="table" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th>Capacity</th>
                                        <th>Enrolled</th>
                                        <th>Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT id, section_code, max_students, current_students
                                        FROM course_sections
                                        WHERE course_id = ? AND status = 'active'
                                        ORDER BY section_code
                                    ");
                                    $stmt->execute([$course['id']]);
                                    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($sections as $section):
                                        $available = $section['max_students'] - $section['current_students'];
                                        $capacity_percent = ($section['current_students'] / $section['max_students']) * 100;
                                    ?>
                                    <tr>
                                        <td style="font-weight: 600;">
                                            <?php echo htmlspecialchars($section['section_code']); ?>
                                        </td>
                                        <td>
                                            <?php echo $section['max_students']; ?>
                                        </td>
                                        <td>
                                            <div style="position: relative; height: 20px; background: #e5e7eb; border-radius: 0.25rem; overflow: hidden;">
                                                <div style="position: absolute; height: 100%; background: #3b82f6; width: <?php echo $capacity_percent; ?>%; top: 0; left: 0;"></div>
                                                <div style="position: absolute; height: 100%; display: flex; align-items: center; justify-content: center; width: 100%; font-size: 0.75rem; font-weight: 600; color: #1f2937;">
                                                    <?php echo $section['current_students']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="display: inline-block; padding: 0.25rem 0.75rem; background: 
                                                <?php echo $available > 0 ? '#ecfdf5;' : '#fef2f2;'; ?>
                                                ; color: 
                                                <?php echo $available > 0 ? '#065f46;' : '#991b1b;'; ?>
                                                ; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600;">
                                                <?php echo $available; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <?php endforeach;
                else:
                ?>
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">No active courses found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php adminLayoutEnd(); ?>
