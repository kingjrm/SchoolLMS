<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

teacherLayoutStart('courses', 'My Courses');
?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="card">
            <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700;">📚 Courses & Student Management</h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT c.*, t.name as term_name,
                        (SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE course_id = c.id AND status = 'enrolled') as student_count,
                        (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count,
                        (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id) as material_count,
                        (SELECT COUNT(DISTINCT cs.id) FROM course_sections WHERE course_id = c.id AND status = 'active') as section_count
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
                    foreach ($courses as $course):
                ?>
                    <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; color: #1f2937;">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </h4>
                                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0;">
                                    <?php echo htmlspecialchars($course['code']); ?> • <?php echo htmlspecialchars($course['term_name']); ?>
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                                <a href="course-students.php?id=<?php echo $course['id']; ?>" style="padding: 0.5rem 1rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 0.375rem; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                    👥 Manage Students
                                </a>
                                <a href="course-assignments.php?id=<?php echo $course['id']; ?>" style="padding: 0.5rem 1rem; background: #10b981; color: white; text-decoration: none; border-radius: 0.375rem; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                    ✏️ Assignments
                                </a>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; padding-top: 1rem; border-top: 1px solid #f3f4f6;">
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;">
                                    <?php echo $course['student_count']; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #6b7280;">
                                    Enrolled Students
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;">
                                    <?php echo $course['assignment_count']; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #6b7280;">
                                    Assignments
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b;">
                                    <?php echo $course['material_count']; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #6b7280;">
                                    Materials
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: #8b5cf6;">
                                    <?php echo $course['section_count']; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #6b7280;">
                                    Sections
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:0.5rem;">
                                    <span style="font-size: 1.25rem; font-weight: 700; color: #ef4444; font-family: monospace; letter-spacing: 1px;">
                                        <?php echo htmlspecialchars($course['join_code'] ?? '—'); ?>
                                    </span>
                                    <?php if (!empty($course['join_code'])): ?>
                                    <button type="button"
                                        class="copy-code-btn"
                                        data-code="<?php echo htmlspecialchars($course['join_code']); ?>"
                                        title="Copy join code"
                                        style="padding: 0.25rem 0.5rem; font-size:0.75rem; border:1px solid #e5e7eb; background:#fff; color:#374151; border-radius:0.375rem; cursor:pointer;">
                                        Copy
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #6b7280; margin-top:0.25rem;">
                                    Join Code
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;
                else:
                ?>
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">You have no active courses</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php teacherLayoutEnd(); ?>
<script>
(function(){
    function showTemp(el, text, ms){
        const original = el.textContent;
        el.textContent = text;
        el.disabled = true;
        setTimeout(()=>{ el.textContent = original; el.disabled = false; }, ms);
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.copy-code-btn');
        if (!btn) return;
        const code = btn.getAttribute('data-code');
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(code);
            } else {
                const ta = document.createElement('textarea');
                ta.value = code; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
            }
            showTemp(btn, 'Copied!', 1200);
        } catch (err) {
            showTemp(btn, 'Failed', 1200);
        }
    });
})();
</script>
