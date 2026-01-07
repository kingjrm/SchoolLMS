<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

// Handle create course request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_course') {
    $code = trim($_POST['course_code']);
    $title = trim($_POST['course_title']);
    $description = trim($_POST['course_description'] ?? '');
    $term_id = intval($_POST['term_id']);
    $credits = intval($_POST['credits'] ?? 3);
    $max_students = intval($_POST['max_students'] ?? 50);
    
    try {
        require_once '../includes/CourseInvite.php';
        $join_code = generateJoinCode($pdo);
        
        $stmt = $pdo->prepare(
            "INSERT INTO courses (code, title, description, teacher_id, term_id, credits, max_students, join_code, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')"
        );
        $stmt->execute([$code, $title, $description, $teacher_id, $term_id, $credits, $max_students, $join_code]);
        
        $_SESSION['success_message'] = 'Course created successfully! Join code: ' . $join_code;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_message'] = 'Course code already exists. Please use a different code.';
        } else {
            $_SESSION['error_message'] = 'Failed to create course: ' . $e->getMessage();
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: courses.php');
    exit;
}

// Handle generate new code request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_code') {
    $course_id = intval($_POST['course_id'] ?? 0);
    
    // Verify course belongs to current teacher
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $teacher_id]);
    
    if ($stmt->fetch()) {
        try {
            require_once '../includes/CourseInvite.php';
            $new_code = generateJoinCode($pdo);
            
            // Update course with new code
            $update = $pdo->prepare("UPDATE courses SET join_code = ? WHERE id = ?");
            $update->execute([$new_code, $course_id]);
            
            $_SESSION['success_message'] = 'New join code generated successfully!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Failed to generate new code. Please try again.';
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: courses.php');
    exit;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

teacherLayoutStart('courses', 'My Courses');
?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <?php if ($success_message): ?>
        <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:0.5rem;padding:1rem;margin-bottom:1rem;color:#065f46">
            ✓ <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:0.5rem;padding:1rem;margin-bottom:1rem;color:#991b1b">
            ✕ <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        <div class="card">
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 20px; height: 20px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h3 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1f2937;">My Courses</h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT c.*, t.name as term_name,
                        (SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE course_id = c.id) as student_count,
                        0 as assignment_count,
                        0 as material_count,
                        0 as section_count
                        FROM courses c
                        LEFT JOIN academic_terms t ON c.term_id = t.id
                        WHERE c.teacher_id = ? AND c.status = 'active'
                        ORDER BY c.created_at DESC
                    ");
                    $stmt->execute([$teacher_id]);
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $courses = [];
                    echo '<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:0.5rem;padding:1rem;margin-bottom:1rem;color:#991b1b">';
                    echo 'Error loading courses: ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }

                if (!empty($courses)):
                    foreach ($courses as $course):
                ?>
                    <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.25rem; margin-bottom: 1rem; background: #fff;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <h4 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem; color: #111827;">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </h4>
                                <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">
                                    <?php echo htmlspecialchars($course['code']); ?> • <?php echo htmlspecialchars($course['term_name']); ?>
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end; align-items: flex-start;">
                                <a href="course-students.php?id=<?php echo $course['id']; ?>" style="padding: 0.45rem 0.75rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; gap: 0.35rem;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Students
                                </a>
                                <a href="assignments.php?course_id=<?php echo $course['id']; ?>" style="padding: 0.45rem 0.75rem; background: #10b981; color: white; text-decoration: none; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; gap: 0.35rem;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Assignments
                                </a>
                                <button type="button" onclick="openCodeModal(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['join_code'] ?? ''); ?>')" style="padding: 0.45rem 0.75rem; background: #f59e0b; color: white; border: none; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; transition: all 0.2s; cursor: pointer; display: flex; align-items: center; gap: 0.35rem;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                    Code
                                </button>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f3f4f6;">
                            <div style="text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 600; color: #3b82f6;">
                                    <?php echo $course['student_count']; ?>
                                </div>
                                <div style="font-size: 0.7rem; color: #9ca3af;">
                                    Students
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 600; color: #10b981;">
                                    <?php echo $course['assignment_count']; ?>
                                </div>
                                <div style="font-size: 0.7rem; color: #9ca3af;">
                                    Assignments
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 600; color: #f59e0b;">
                                    <?php echo $course['material_count']; ?>
                                </div>
                                <div style="font-size: 0.7rem; color: #9ca3af;">
                                    Materials
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 600; color: #8b5cf6;">
                                    <?php echo $course['section_count']; ?>
                                </div>
                                <div style="font-size: 0.7rem; color: #9ca3af;">
                                    Sections
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:0.35rem;">
                                    <span style="font-size: 1.1rem; font-weight: 600; color: #ef4444; font-family: monospace; letter-spacing: 0.5px;">
                                        <?php echo htmlspecialchars($course['join_code'] ?? '—'); ?>
                                    </span>
                                    <?php if (!empty($course['join_code'])): ?>
                                    <button type="button"
                                        class="copy-code-btn"
                                        data-code="<?php echo htmlspecialchars($course['join_code']); ?>"
                                        title="Copy join code"
                                        style="padding: 0.2rem 0.4rem; font-size:0.65rem; border:1px solid #e5e7eb; background:#fff; color:#374151; border-radius:0.25rem; cursor:pointer;">
                                        Copy
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.7rem; color: #9ca3af; margin-top:0.15rem;">
                                    Code
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;
                else:
                ?>
                    <p style="text-align: center; color: #9ca3af; padding: 2rem; font-size: 0.85rem;">No active courses yet. Click the + button to create your first course.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

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

<!-- Manage Course Code Modal -->
<div id="codeModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9999">
    <div style="width:100%;max-width:480px;background:#fff;border-radius:10px;box-shadow:0 20px 45px rgba(0,0,0,0.2)">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:0.95rem;font-weight:600;color:#1f2937">Manage Join Code</h3>
            <button type="button" onclick="closeCodeModal()" style="background:transparent;border:none;font-size:1.5rem;cursor:pointer;color:#6b7280;line-height:1">×</button>
        </div>
        <form method="POST" action="courses.php">
            <div style="padding:1.25rem">
                <input type="hidden" id="modalCourseId" name="course_id">
                <div style="margin-bottom:1.25rem">
                    <label style="display:block;font-size:0.8rem;color:#374151;margin-bottom:0.5rem;font-weight:500">Current Join Code</label>
                    <div style="padding:0.875rem;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:0.5rem;display:flex;justify-content:space-between;align-items:center;gap:0.5rem">
                        <span id="currentCode" style="font-family:monospace;font-size:1.1rem;font-weight:600;color:#3b82f6;letter-spacing:0.5px;flex:1">—</span>
                        <button type="button" id="copyCodeBtn" onclick="copyCurrentCode()" style="background:#3b82f6;color:#fff;border:none;padding:0.45rem 0.875rem;border-radius:0.375rem;cursor:pointer;font-weight:500;font-size:0.75rem;white-space:nowrap">Copy</button>
                    </div>
                </div>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:0.875rem;margin-bottom:1rem">
                    <p style="margin:0;font-size:0.8rem;color:#1e40af">Share this code with students so they can join your course.</p>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;padding:0 1.25rem 1.25rem;border-top:1px solid #f3f4f6;padding-top:1rem">
                <button type="button" onclick="closeCodeModal()" style="background:#f3f4f6;color:#374151;padding:0.5rem 1rem;border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;font-size:0.8rem">Close</button>
                <button type="submit" name="action" value="generate_code" style="background:#f59e0b;color:#fff;padding:0.5rem 1.25rem;border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;font-size:0.8rem">Generate New</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCodeModal(courseId, currentCode) {
    document.getElementById('modalCourseId').value = courseId;
    document.getElementById('currentCode').textContent = currentCode || '—';
    document.getElementById('codeModal').style.display = 'flex';
}

function closeCodeModal() {
    document.getElementById('codeModal').style.display = 'none';
}

function copyCurrentCode() {
    const code = document.getElementById('currentCode').textContent;
    if (code === '—') return;
    const btn = document.getElementById('copyCodeBtn');
    const originalText = btn.textContent;
    navigator.clipboard.writeText(code).then(() => {
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = originalText; }, 1500);
    });
}

document.getElementById('codeModal').addEventListener('click', function(e) {
    if (e.target === this) closeCodeModal();
});
</script>

<!-- Floating Add Course Button -->
<button type="button" class="fab-create-course" onclick="document.getElementById('createCourseModal').style.display='flex'" aria-label="Create course">+</button>

<!-- Create Course Modal -->
<div id="createCourseModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9999">
    <div style="width:100%;max-width:550px;background:#fff;border-radius:10px;box-shadow:0 20px 45px rgba(0,0,0,0.2);max-height:90vh;overflow-y:auto">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;z-index:1">
            <h3 style="margin:0;font-size:0.95rem;font-weight:600;color:#1f2937">Create New Course</h3>
            <button type="button" onclick="document.getElementById('createCourseModal').style.display='none'" style="background:transparent;border:none;font-size:1.5rem;cursor:pointer;color:#6b7280;line-height:1">×</button>
        </div>
        <form method="POST" action="courses.php">
            <input type="hidden" name="action" value="create_course">
            <div style="padding:1.25rem">
                <div style="margin-bottom:1rem">
                    <label style="display:block;font-size:0.8rem;color:#374151;margin-bottom:0.4rem;font-weight:500">Course Code <span style="color:#ef4444">*</span></label>
                    <input type="text" name="course_code" placeholder="e.g., CS101" required style="width:100%;padding:0.625rem 0.75rem;border:1.5px solid #e5e7eb;border-radius:0.375rem;font-size:0.85rem">
                    <div style="font-size:0.7rem;color:#6b7280;margin-top:0.25rem">Unique identifier for the course</div>
                </div>
                
                <div style="margin-bottom:1rem">
                    <label style="display:block;font-size:0.8rem;color:#374151;margin-bottom:0.4rem;font-weight:500">Course Title <span style="color:#ef4444">*</span></label>
                    <input type="text" name="course_title" placeholder="e.g., Introduction to Computer Science" required style="width:100%;padding:0.625rem 0.75rem;border:1.5px solid #e5e7eb;border-radius:0.375rem;font-size:0.85rem">
                </div>
                
                <div style="margin-bottom:1rem">
                    <label style="display:block;font-size:0.8rem;color:#374151;margin-bottom:0.4rem;font-weight:500">Description</label>
                    <textarea name="course_description" rows="3" placeholder="Course description and objectives..." style="width:100%;padding:0.625rem 0.75rem;border:1.5px solid #e5e7eb;border-radius:0.375rem;font-size:0.85rem;resize:vertical"></textarea>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:0.8rem;color:#374151;margin-bottom:0.4rem;font-weight:500">Credits</label>
                        <input type="number" name="credits" value="3" min="1" max="10" style="width:100%;padding:0.625rem 0.75rem;border:1.5px solid #e5e7eb;border-radius:0.375rem;font-size:0.85rem">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8rem;color:#374151;margin-bottom:0.4rem;font-weight:500">Max Students</label>
                        <input type="number" name="max_students" value="50" min="1" max="500" style="width:100%;padding:0.625rem 0.75rem;border:1.5px solid #e5e7eb;border-radius:0.375rem;font-size:0.85rem">
                    </div>
                </div>
                
                <div style="margin-bottom:1rem">
                    <label style="display:block;font-size:0.8rem;color:#374151;margin-bottom:0.4rem;font-weight:500">Academic Term <span style="color:#ef4444">*</span></label>
                    <select name="term_id" required style="width:100%;padding:0.625rem 0.75rem;border:1.5px solid #e5e7eb;border-radius:0.375rem;font-size:0.85rem">
                        <option value="">Select a term...</option>
                        <?php
                        try {
                            $terms_stmt = $pdo->query("SELECT id, name, start_date, end_date FROM academic_terms WHERE is_active = 1 ORDER BY start_date DESC");
                            while ($term = $terms_stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $term['id'] . '">' . htmlspecialchars($term['name']) . '</option>';
                            }
                        } catch (Exception $e) {
                            echo '<option value="">Error loading terms</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:0.875rem">
                    <p style="margin:0;font-size:0.8rem;color:#1e40af">A unique join code will be automatically generated for students to join your course.</p>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;padding:0 1.25rem 1.25rem;border-top:1px solid #f3f4f6;padding-top:1rem">
                <button type="button" onclick="document.getElementById('createCourseModal').style.display='none'" style="background:#f3f4f6;color:#374151;padding:0.5rem 1rem;border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;font-size:0.8rem">Cancel</button>
                <button type="submit" style="background:#10b981;color:#fff;padding:0.5rem 1.25rem;border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;font-size:0.8rem">Create Course</button>
            </div>
        </form>
    </div>
</div>

<style>
.fab-create-course {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 60px;
    height: 60px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 2rem;
    font-weight: 300;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9998;
    line-height: 1;
}

.fab-create-course:hover {
    background: #059669;
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}

.fab-create-course:active {
    transform: scale(0.95) rotate(90deg);
}
</style>

<script>
// Close modal when clicking outside
document.getElementById('createCourseModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>

<?php teacherLayoutEnd(); ?>
