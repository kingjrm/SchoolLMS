<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/helpers.php';
require_once '../includes/student_layout.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();
$db = new Database();
$student_id = $user['id'];

// Handle enrollment by code
$enroll_message = '';
$enroll_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_by_code'])) {
    $join_code = strtoupper(trim($_POST['join_code']));
    
    try {
        // Find course by join code
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE join_code = ? AND status = 'active'");
        $stmt->execute([$join_code]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$course) {
            $enroll_error = "Invalid course code. Please check and try again.";
        } else {
            $course_id = $course['id'];
            
            // Check if already enrolled
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
            $stmt->execute([$course_id, $student_id]);
            
            if ($stmt->rowCount() > 0) {
                $enroll_error = "You are already enrolled in this course.";
            } else {
                // Enroll student
                $stmt = $pdo->prepare("INSERT INTO enrollments (course_id, student_id, enrollment_date) VALUES (?, ?, CURDATE())");
                $stmt->execute([$course_id, $student_id]);
                $enroll_message = "Successfully enrolled in the course!";
            }
        }
    } catch (Exception $e) {
        $enroll_error = "Enrollment failed. Please try again.";
    }
}

// Handle enrollment with section selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_course'])) {
    $course_id = (int)$_POST['course_id'];
    $section_id = (int)$_POST['section_id'] ?? null;
    
    try {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
        $stmt->execute([$course_id, $student_id]);
        
        if ($stmt->rowCount() > 0) {
            $enroll_error = "You are already enrolled in this course.";
        } else {
            // Check section capacity
            if ($section_id) {
                $stmt = $pdo->prepare("SELECT current_students, max_students FROM course_sections WHERE id = ?");
                $stmt->execute([$section_id]);
                $section = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($section['current_students'] >= $section['max_students']) {
                    $enroll_error = "This section is full. Please choose another section.";
                } else {
                    // Enroll student
                    $stmt = $pdo->prepare("INSERT INTO enrollments (course_id, section_id, student_id, enrollment_date) VALUES (?, ?, ?, CURDATE())");
                    $stmt->execute([$course_id, $section_id, $student_id]);
                    
                    // Update section count
                    $stmt = $pdo->prepare("UPDATE course_sections SET current_students = current_students + 1 WHERE id = ?");
                    $stmt->execute([$section_id]);
                    
                    $enroll_message = "Successfully enrolled in the course!";
                }
            } else {
                // Enroll without section
                $stmt = $pdo->prepare("INSERT INTO enrollments (course_id, student_id, enrollment_date) VALUES (?, ?, CURDATE())");
                $stmt->execute([$course_id, $student_id]);
                $enroll_message = "Successfully enrolled in the course!";
            }
        }
    } catch (Exception $e) {
        $enroll_error = "Enrollment failed. Please try again.";
    }
}

studentLayoutStart('courses', 'My Courses');
?>

    <div class="courses-container">
        <?php if ($enroll_message): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                ✓ <?php echo htmlspecialchars($enroll_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($enroll_error): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                ✗ <?php echo htmlspecialchars($enroll_error); ?>
            </div>
        <?php endif; ?>

        <!-- Enrolled Courses Section -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title">📚 My Enrolled Courses</h3>
            </div>
            <div class="card-body">
                <?php
                $db->prepare("
                    SELECT c.*, t.name as term_name, u.first_name, u.last_name,
                    cs.section_code,
                    (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count,
                    (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id) as material_count,
                    (SELECT ROUND(AVG(CAST(g.score as DECIMAL(5,2))), 2) FROM grades g 
                     JOIN assignments a ON g.assignment_id = a.id 
                     WHERE a.course_id = c.id AND g.student_id = ?) as avg_grade
                    FROM enrollments e 
                    JOIN courses c ON e.course_id = c.id 
                    JOIN academic_terms t ON c.term_id = t.id 
                    JOIN users u ON c.teacher_id = u.id 
                    LEFT JOIN course_sections cs ON e.section_id = cs.id
                    WHERE e.student_id = ? AND e.status = 'enrolled' 
                    ORDER BY c.created_at DESC
                ")->bind('ii', $student_id, $student_id)->execute();
                $courses = $db->fetchAll();

                if (!empty($courses)):
                ?>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-card-header">
                                <div>
                                    <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p class="course-code"><?php echo htmlspecialchars($course['code']); ?></p>
                                    <?php if ($course['section_code']): ?>
                                        <p class="course-section">Section: <?php echo htmlspecialchars($course['section_code']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="course-card-body">
                                <p class="course-info"><strong>Instructor:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
                                <p class="course-info"><strong>Term:</strong> <?php echo htmlspecialchars($course['term_name']); ?></p>
                                <p class="course-info"><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                
                                <?php if ($course['avg_grade']): ?>
                                    <div class="course-grade">
                                        <span class="grade-label">Average Grade:</span>
                                        <span class="grade-value"><?php echo $course['avg_grade']; ?>%</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="course-stats">
                                    <span><?php echo $course['material_count']; ?> Materials</span>
                                    <span>•</span>
                                    <span><?php echo $course['assignment_count']; ?> Assignments</span>
                                </div>
                                <div class="course-actions">
                                    <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Course</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">You are not enrolled in any courses yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Courses Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🎓 Available Courses to Enroll</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    // Get active courses not yet enrolled
                    $query = "
                        SELECT c.*, t.name as term_name, u.first_name, u.last_name,
                        (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'enrolled') as enrolled_count,
                        (SELECT COUNT(DISTINCT cs.id) FROM course_sections WHERE course_id = c.id AND status = 'active') as section_count
                        FROM courses c 
                        JOIN academic_terms t ON c.term_id = t.id 
                        JOIN users u ON c.teacher_id = u.id 
                        WHERE c.status = 'active' AND t.is_active = TRUE
                        AND c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ? AND status IN ('enrolled', 'completed'))
                        ORDER BY c.created_at DESC
                    ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$student_id]);
                    $available = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $available = [];
                }

                if (!empty($available)):
                ?>
                    <div class="courses-grid">
                        <?php foreach ($available as $course): ?>
                        <div class="course-card available-course-card">
                            <div class="course-card-header">
                                <div>
                                    <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p class="course-code"><?php echo htmlspecialchars($course['code']); ?></p>
                                </div>
                            </div>
                            <div class="course-card-body">
                                <p class="course-info"><strong>Instructor:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
                                <p class="course-info"><strong>Term:</strong> <?php echo htmlspecialchars($course['term_name']); ?></p>
                                <p class="course-info"><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                <p class="course-info"><strong>Enrolled:</strong> <?php echo $course['enrolled_count']; ?>/<?php echo $course['max_students']; ?></p>
                                
                                <div class="course-description">
                                    <?php if ($course['description']): ?>
                                        <p><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($course['section_count'] > 0): ?>
                                    <form method="POST" class="enroll-form">
                                        <input type="hidden" name="enroll_course" value="1">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        
                                        <div class="form-group" style="margin-bottom: 0.75rem;">
                                            <label for="section_<?php echo $course['id']; ?>" style="display: block; margin-bottom: 0.25rem; font-size: 0.85rem; font-weight: 600;">Select Section:</label>
                                            <select name="section_id" id="section_<?php echo $course['id']; ?>" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.85rem;">
                                                <option value="">-- Choose a section --</option>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT id, section_code, current_students, max_students FROM course_sections WHERE course_id = ? AND status = 'active' ORDER BY section_code");
                                                $stmt->execute([$course['id']]);
                                                $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($sections as $section):
                                                    $available_spots = $section['max_students'] - $section['current_students'];
                                                    $disabled = $available_spots <= 0 ? 'disabled' : '';
                                                ?>
                                                    <option value="<?php echo $section['id']; ?>" <?php echo $disabled; ?>>
                                                        <?php echo htmlspecialchars($section['section_code']); ?> 
                                                        (<?php echo $section['current_students']; ?>/<?php echo $section['max_students']; ?>)
                                                        <?php echo $disabled ? ' [FULL]' : ''; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary" style="width: 100%;">Enroll Now</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="enroll-form">
                                        <input type="hidden" name="enroll_course" value="1">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-primary" style="width: 100%;">Enroll Now</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">No available courses at this time.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .courses-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .course-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            overflow: hidden;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .course-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .available-course-card {
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%);
        }

        .course-card-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fafbfc;
        }

        .course-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #1f2937;
        }

        .course-code {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0;
            font-weight: 600;
        }

        .course-section {
            font-size: 0.8rem;
            color: #3b82f6;
            margin: 0.25rem 0 0 0;
            font-weight: 500;
        }

        .course-card-body {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .course-info {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #4b5563;
        }

        .course-grade {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 0.75rem;
            margin: 0.75rem 0;
            border-radius: 0.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grade-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e40af;
        }

        .grade-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2563eb;
        }

        .course-stats {
            font-size: 0.8rem;
            color: #9ca3af;
            margin: 0.75rem 0;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }

        .course-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0.75rem 0;
            flex: 1;
        }

        .course-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .enroll-form {
            margin-top: auto;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .no-data {
            text-align: center;
            color: #9ca3af;
            padding: 2rem;
            font-style: italic;
        }

        .card-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fafbfc;
        }

        .card-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }

        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Floating Join Button */
        .fab-join {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            background: #3b82f6;
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
            cursor: pointer;
            transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease;
            z-index: 1000;
        }
        .fab-join:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(37, 99, 235, 0.4); }
        .fab-join:active { transform: translateY(0); }

        /* Modal styles */
        .join-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17,24,39,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .join-modal {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(0,0,0,0.2);
            transform: translateY(8px);
            opacity: 0;
            transition: all 0.2s ease;
        }
        .join-modal.show { transform: translateY(0); opacity: 1; }
        .join-modal-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .join-modal-title { margin: 0; font-size: 1rem; font-weight: 700; color: #1f2937; }
        .join-close { background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280; }
        .join-modal-body { padding: 1.25rem; }
        .join-input { width: 100%; padding: 0.875rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem; outline: none; }
        .join-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        .join-help { font-size: 0.8125rem; color: #6b7280; margin-top: 0.5rem; }
        .join-actions { display: flex; gap: 0.5rem; justify-content: flex-end; padding: 0 1.25rem 1.25rem; }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
    </style>

    <!-- Floating Join Button -->
    <button type="button" class="fab-join" id="openJoinModal" aria-label="Join course">+</button>

    <!-- Join Code Modal -->
    <div class="join-modal-backdrop" id="joinModalBackdrop" aria-hidden="true">
        <div class="join-modal" id="joinModal">
            <div class="join-modal-header">
                <h3 class="join-modal-title">Join Course with Code</h3>
                <button type="button" class="join-close" id="closeJoinModal" aria-label="Close">×</button>
            </div>
            <form method="POST">
                <div class="join-modal-body">
                    <label style="display:block; font-size: 0.875rem; color:#374151; margin-bottom:0.5rem; font-weight:600;">Course Code</label>
                    <input class="join-input" type="text" name="join_code" id="joinCodeInput" placeholder="e.g., ABC123" maxlength="10" required>
                    <div class="join-help">Ask your instructor for the join code.</div>
                </div>
                <div class="join-actions">
                    <button type="button" class="btn btn-secondary" id="cancelJoin">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="join_by_code" value="1">Join</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function(){
        const fab = document.getElementById('openJoinModal');
        const backdrop = document.getElementById('joinModalBackdrop');
        const modal = document.getElementById('joinModal');
        const closeBtn = document.getElementById('closeJoinModal');
        const cancelBtn = document.getElementById('cancelJoin');
        const codeInput = document.getElementById('joinCodeInput');

        function openModal(){
            backdrop.style.display = 'flex';
            requestAnimationFrame(() => modal.classList.add('show'));
            setTimeout(() => codeInput && codeInput.focus(), 150);
        }
        function closeModal(){
            modal.classList.remove('show');
            setTimeout(() => { backdrop.style.display = 'none'; }, 150);
        }
        function clickOutside(e){ if(e.target === backdrop) closeModal(); }

        if (fab) fab.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', clickOutside);
        if (codeInput) codeInput.addEventListener('input', () => { codeInput.value = codeInput.value.toUpperCase(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && backdrop.style.display === 'flex') closeModal(); });
    })();
    </script>

<?php studentLayoutEnd(); ?>
