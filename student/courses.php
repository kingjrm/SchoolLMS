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
    
    try {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
        $stmt->execute([$course_id, $student_id]);
        
        if ($stmt->rowCount() > 0) {
            $enroll_error = "You are already enrolled in this course.";
        } else {
            // Enroll without section
            $stmt = $pdo->prepare("INSERT INTO enrollments (course_id, student_id, enrollment_date) VALUES (?, ?, CURDATE())");
            $stmt->execute([$course_id, $student_id]);
            $enroll_message = "Successfully enrolled in the course!";
        }
    } catch (Exception $e) {
        $enroll_error = "Enrollment failed. Please try again.";
    }
}

studentLayoutStart('courses', 'My Courses', false);

// Get filter values from query parameters
$search = $_GET['search'] ?? '';
$filter_teacher = $_GET['teacher'] ?? '';
$filter_term = $_GET['term'] ?? '';
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

        <!-- Filter Section -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title">🔍 Filter Courses</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search by Title or Code:</label>
                            <input type="text" id="search" name="search" placeholder="Course title or code..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="teacher">Filter by Instructor:</label>
                            <select id="teacher" name="teacher">
                                <option value="">All Instructors</option>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT DISTINCT u.id, u.first_name, u.last_name
                                        FROM enrollments e 
                                        JOIN courses c ON e.course_id = c.id 
                                        JOIN users u ON c.teacher_id = u.id 
                                        WHERE e.student_id = ?
                                        ORDER BY u.first_name, u.last_name
                                    ");
                                    $stmt->execute([$student_id]);
                                    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($teachers as $teacher):
                                        $teacher_name = $teacher['first_name'] . ' ' . $teacher['last_name'];
                                ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo $filter_teacher == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher_name); ?>
                                        </option>
                                <?php endforeach; ?>
                                <?php } catch (Exception $e) {} ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="term">Filter by Term:</label>
                            <select id="term" name="term">
                                <option value="">All Terms</option>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT DISTINCT t.id, t.name
                                        FROM enrollments e 
                                        JOIN courses c ON e.course_id = c.id 
                                        LEFT JOIN academic_terms t ON c.term_id = t.id 
                                        WHERE e.student_id = ?
                                        ORDER BY t.name DESC
                                    ");
                                    $stmt->execute([$student_id]);
                                    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($terms as $term):
                                ?>
                                        <option value="<?php echo $term['id']; ?>" <?php echo $filter_term == $term['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($term['name'] ?? 'Unassigned'); ?>
                                        </option>
                                <?php endforeach; ?>
                                <?php } catch (Exception $e) {} ?>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="courses.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Enrolled Courses Section -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title">📚 My Enrolled Courses</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    // Build query with filters
                    $where_conditions = ['e.student_id = ?'];
                    $params = [$student_id];
                    
                    if (!empty($search)) {
                        $where_conditions[] = "(c.title LIKE ? OR c.code LIKE ?)";
                        $search_param = '%' . $search . '%';
                        $params[] = $search_param;
                        $params[] = $search_param;
                    }
                    
                    if (!empty($filter_teacher)) {
                        $where_conditions[] = "c.teacher_id = ?";
                        $params[] = (int)$filter_teacher;
                    }
                    
                    if (!empty($filter_term)) {
                        $where_conditions[] = "c.term_id = ?";
                        $params[] = (int)$filter_term;
                    }
                    
                    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
                    
                    $stmt = $pdo->prepare("
                        SELECT c.*, t.name as term_name, u.first_name, u.last_name,
                        e.enrollment_date
                        FROM enrollments e 
                        JOIN courses c ON e.course_id = c.id 
                        LEFT JOIN academic_terms t ON c.term_id = t.id 
                        JOIN users u ON c.teacher_id = u.id 
                        $where_clause
                        ORDER BY e.enrollment_date DESC
                    ");
                    $stmt->execute($params);
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $courses = [];
                    echo '<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:0.5rem;padding:1rem;margin-bottom:1rem;color:#991b1b">';
                    echo 'Error loading courses: ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }

                if (!empty($courses)):
                ?>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-card-header">
                                <div>
                                    <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p class="course-code"><?php echo htmlspecialchars($course['code']); ?></p>
                                </div>
                            </div>
                            <div class="course-card-body">
                                <p class="course-info"><strong>Instructor:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
                                <p class="course-info"><strong>Term:</strong> <?php echo htmlspecialchars($course['term_name'] ?? 'N/A'); ?></p>
                                <p class="course-info"><strong>Credits:</strong> <?php echo $course['credits'] ?? 'N/A'; ?></p>
                                <p class="course-info"><strong>Enrolled:</strong> <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></p>
                                
                                <div class="course-actions">
                                    <a href="assignments.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">View Assignments</a>
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
    </div>

    <style>
        .courses-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .filter-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #1f2937;
        }

        .filter-group input,
        .filter-group select {
            padding: 0.55rem 0.75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.4rem;
            font-size: 0.85rem;
            font-family: inherit;
            color: #1f2937;
            transition: border-color 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #1f2937;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
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
            border-color: #f97316;
            transform: translateY(-2px);
        }

        .available-course-card {
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%);
        }

        .course-card-header {
            padding: 1rem;
            border-bottom: 2px solid #f97316;
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
            background-color: #f97316;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #ea580c;
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
        
        /* Hide global button, show courses button */
        .fab-join { display: none !important; }
        
        /* Courses-specific floating button */
        .fab-courses {
            position: fixed !important;
            right: 24px !important;
            bottom: 24px !important;
            width: 56px !important;
            height: 56px !important;
            border-radius: 50% !important;
            border: none !important;
            background: #3b82f6 !important;
            color: #fff !important;
            font-size: 28px !important;
            font-weight: 700 !important;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35) !important;
            cursor: pointer !important;
            z-index: 99999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease !important;
        }
        .fab-courses:hover {
            background: #2563eb !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.4) !important;
        }
    </style>

    <!-- Floating Join Button for Courses -->
    <button type="button" class="fab-courses" id="coursesFabBtn" onclick="document.getElementById('joinModalCourses').style.display='flex'" aria-label="Join course">+</button>

    <!-- Join Code Modal -->
    <div id="joinModalCourses" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:99998">
        <div style="width:100%;max-width:460px;background:#fff;border-radius:12px;padding:0;box-shadow:0 20px 45px rgba(0,0,0,0.2)">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0;font-size:1rem;font-weight:700;color:#1f2937">Join Course with Code</h3>
                <button type="button" onclick="document.getElementById('joinModalCourses').style.display='none'" style="background:transparent;border:none;font-size:1.5rem;cursor:pointer;color:#6b7280">×</button>
            </div>
            <form method="POST" action="courses.php">
                <div style="padding:1.25rem">
                    <label style="display:block;font-size:0.875rem;color:#374151;margin-bottom:0.5rem;font-weight:600">Course Code</label>
                    <input type="text" name="join_code" placeholder="e.g., ABC123" maxlength="10" required style="width:100%;padding:0.875rem 1rem;border:1.5px solid #e5e7eb;border-radius:0.5rem;font-size:1rem;text-transform:uppercase">
                    <div style="font-size:0.8125rem;color:#6b7280;margin-top:0.5rem">Ask your instructor for the join code.</div>
                </div>
                <div style="display:flex;gap:0.5rem;justify-content:flex-end;padding:0 1.25rem 1.25rem">
                    <button type="button" onclick="document.getElementById('joinModalCourses').style.display='none'" style="background:#f3f4f6;color:#374151;padding:0.5rem 1rem;border:none;border-radius:0.5rem;cursor:pointer">Cancel</button>
                    <button type="submit" name="join_by_code" value="1" style="background:#3b82f6;color:#fff;padding:0.5rem 1rem;border:none;border-radius:0.5rem;cursor:pointer;font-weight:600">Join</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Move the FAB button outside the content wrapper so fixed positioning works
    document.addEventListener('DOMContentLoaded', function() {
        var fab = document.getElementById('coursesFabBtn');
        var modal = document.getElementById('joinModalCourses');
        if (fab) {
            document.body.appendChild(fab);
            document.body.appendChild(modal);
            fab.style.display = 'flex';
        }
        
        // Close modal when clicking outside of it
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
    });
    </script>

</div>

<?php studentLayoutEnd(); ?>
