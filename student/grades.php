<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../includes/student_layout.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();
$student_id = $user['id'];

// Get filter parameters
$filter_course = $_GET['course'] ?? '';
$filter_search = trim($_GET['search'] ?? '');

studentLayoutStart('grades', 'Grades', false);
?>

<style>
    .grades-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .filter-section {
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        margin-bottom: 1rem;
    }
    
    .filter-inline {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: end;
    }
    
    .filter-inline input,
    .filter-inline select {
        background: #fff;
        padding: 0.55rem 0.75rem;
        border: 1.5px solid #e5e7eb;
        border-radius: 0.4rem;
        font-size: 0.85rem;
    }
    
    .filter-inline input {
        flex: 1 1 260px;
    }
    
    .filter-inline select {
        flex: 1 1 200px;
    }
    
    .filter-inline .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        padding: 0.48rem 0.85rem;
        border-radius: 0.4rem;
        font-size: 0.82rem;
        border: 1.5px solid #f97316;
        background: #f97316;
        color: #fff;
        cursor: pointer;
        font-weight: 600;
    }
    
    .filter-inline .btn:hover {
        background: #ea580c;
    }
    
    .reset-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        padding: 0 0.85rem;
        border: 1.5px solid #e5e7eb;
        border-radius: 0.4rem;
        background: #fff;
        color: #1f2937;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.82rem;
        transition: all 0.2s;
    }
    
    .reset-link:hover {
        background: #f8fafc;
        border-color: #d1d5db;
    }
    
    .course-grades-section {
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    .course-header {
        background: #1f2937;
        padding: 1.25rem 1.5rem;
        color: white;
        border-bottom: 1px solid #374151;
    }
    
    .course-header h3 {
        margin: 0 0 0.35rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: white;
    }
    
    .course-header p {
        margin: 0;
        font-size: 0.85rem;
        color: #d1d5db;
    }
    
    .course-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .stat-item {
        text-align: left;
    }
    
    .stat-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-value.grade-excellent {
        color: #059669;
    }
    
    .stat-value.grade-good {
        color: #2563eb;
    }
    
    .stat-value.grade-average {
        color: #d97706;
    }
    
    .stat-value.grade-poor {
        color: #dc2626;
    }
    
    .assignments-list {
        padding: 1.5rem;
    }
    
    .assignment-grade-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: border-color 0.2s;
    }
    
    .assignment-grade-card:hover {
        border-color: #cbd5e1;
    }
    
    .assignment-grade-card:last-child {
        margin-bottom: 0;
    }
    
    .assignment-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .assignment-info {
        flex: 1;
    }
    
    .assignment-info h4 {
        margin: 0 0 0.35rem 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .assignment-info p {
        margin: 0;
        font-size: 0.75rem;
        color: #6b7280;
    }
    
    .grade-display {
        text-align: right;
        min-width: 120px;
    }
    
    .grade-score {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.25rem;
    }
    
    .grade-score.excellent {
        color: #059669;
    }
    
    .grade-score.good {
        color: #2563eb;
    }
    
    .grade-score.average {
        color: #d97706;
    }
    
    .grade-score.poor {
        color: #dc2626;
    }
    
    .grade-max {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-bottom: 0.25rem;
    }
    
    .grade-percentage {
        font-size: 0.8rem;
        font-weight: 600;
        color: #6b7280;
        padding: 0.2rem 0.5rem;
        background: #f3f4f6;
        border-radius: 0.25rem;
        display: inline-block;
    }
    
    .feedback-section {
        background: #f9fafb;
        border-left: 3px solid #4b5563;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-top: 1rem;
    }
    
    .feedback-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #4b5563;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }
    
    .feedback-text {
        font-size: 0.85rem;
        color: #374151;
        line-height: 1.6;
        white-space: pre-wrap;
    }
    
    .graded-date {
        font-size: 0.7rem;
        color: #9ca3af;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f3f4f6;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 1rem;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }
    
    .empty-state-icon {
        width: 3rem;
        height: 3rem;
        margin: 0 auto 1rem;
        opacity: 0.4;
        fill: none;
        stroke: #9ca3af;
        stroke-width: 1.5;
    }
    
    .empty-state h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
        color: #6b7280;
        font-weight: 600;
    }
    
    .empty-state p {
        margin: 0;
        font-size: 0.85rem;
        color: #9ca3af;
    }
</style>

<div class="grades-container">
    <?php
    // Get courses for filter
    try {
        $coursesStmt = $pdo->prepare("
            SELECT DISTINCT c.id, c.code, c.title
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            JOIN assignments a ON c.id = a.course_id 
            JOIN grades g ON a.id = g.assignment_id AND g.student_id = ?
            WHERE e.student_id = ? AND e.status = 'enrolled'
            ORDER BY c.title
        ");
        $coursesStmt->execute([$student_id, $student_id]);
        $filterCourses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $filterCourses = [];
    }
    ?>
    
    <!-- Filters -->
    <?php if (!empty($filterCourses)): ?>
    <div class="filter-section">
        <form method="GET" class="filter-inline">
            <input type="text" name="search" placeholder="Search assignment..." value="<?php echo htmlspecialchars($filter_search); ?>">
            <select name="course">
                <option value="">All Courses</option>
                <?php foreach ($filterCourses as $courseOption): ?>
                    <option value="<?php echo $courseOption['id']; ?>" <?php echo $filter_course == $courseOption['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($courseOption['code'] . ' - ' . $courseOption['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Filter</button>
            <?php if ($filter_course || $filter_search): ?>
                <a href="grades.php" class="reset-link">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

    <?php
    try {
        // Build query with filters
        $where_conditions = ["e.student_id = ?", "e.status = 'enrolled'", "g.score IS NOT NULL"];
        $params = [$student_id, $student_id];

        if ($filter_course) {
            $where_conditions[] = "c.id = ?";
            $params[] = (int)$filter_course;
        }

        if ($filter_search) {
            $where_conditions[] = "(a.title LIKE ? OR c.title LIKE ? OR c.code LIKE ?)";
            $search_param = '%' . $filter_search . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get all courses with grades
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.id, c.code, c.title, u.first_name, u.last_name
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            JOIN users u ON c.teacher_id = u.id 
            JOIN assignments a ON c.id = a.course_id 
            JOIN grades g ON a.id = g.assignment_id AND g.student_id = ?
            $where_clause
            ORDER BY c.title
        ");
        $stmt->execute($params);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($courses)):
            foreach ($courses as $course):
                // Get assignments and grades for this course
                $courseWhere = ["a.course_id = ?", "g.student_id = ?", "g.score IS NOT NULL"];
                $courseParams = [$course['id'], $student_id];
                
                if ($filter_search) {
                    $courseWhere[] = "a.title LIKE ?";
                    $courseParams[] = '%' . $filter_search . '%';
                }
                
                $gradesStmt = $pdo->prepare("
                    SELECT a.id, a.title as assignment_title, a.max_score, a.due_date,
                           g.score, g.feedback, g.graded_at
                    FROM assignments a
                    LEFT JOIN grades g ON a.id = g.assignment_id
                    WHERE " . implode(' AND ', $courseWhere) . "
                    ORDER BY g.graded_at DESC, a.due_date DESC
                ");
                $gradesStmt->execute($courseParams);
                $grades = $gradesStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($grades)):
                    // Calculate course statistics
                    $totalAssignments = count($grades);
                    $totalScore = 0;
                    $totalMax = 0;
                    foreach ($grades as $grade) {
                        $totalScore += $grade['score'];
                        $totalMax += $grade['max_score'];
                    }
                    $averageGrade = $totalMax > 0 ? ($totalScore / $totalMax) * 100 : 0;
                    
                    // Determine grade color
                    $gradeClass = 'grade-excellent';
                    if ($averageGrade < 90) $gradeClass = 'grade-good';
                    if ($averageGrade < 80) $gradeClass = 'grade-average';
                    if ($averageGrade < 70) $gradeClass = 'grade-poor';
    ?>
                    <div class="course-grades-section">
                        <div class="course-header">
                            <h3><?php echo htmlspecialchars($course['code']); ?></h3>
                            <p><?php echo htmlspecialchars($course['title']); ?> • <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
                        </div>
                        
                        <div class="course-stats">
                            <div class="stat-item">
                                <div class="stat-label">Assignments</div>
                                <div class="stat-value"><?php echo $totalAssignments; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Average Grade</div>
                                <div class="stat-value <?php echo $gradeClass; ?>"><?php echo round($averageGrade, 1); ?>%</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total Points</div>
                                <div class="stat-value"><?php echo round($totalScore, 1); ?> / <?php echo $totalMax; ?></div>
                            </div>
                        </div>
                        
                        <div class="assignments-list">
                            <?php foreach ($grades as $grade): 
                                $percentage = ($grade['score'] / $grade['max_score']) * 100;
                                $scoreClass = 'excellent';
                                if ($percentage < 90) $scoreClass = 'good';
                                if ($percentage < 80) $scoreClass = 'average';
                                if ($percentage < 70) $scoreClass = 'poor';
                            ?>
                                <div class="assignment-grade-card">
                                    <div class="assignment-header">
                                        <div class="assignment-info">
                                            <h4><?php echo htmlspecialchars($grade['assignment_title']); ?></h4>
                                            <p>Due: <?php echo formatDate($grade['due_date']); ?></p>
                                        </div>
                                        <div class="grade-display">
                                            <div class="grade-score <?php echo $scoreClass; ?>">
                                                <?php echo round($grade['score'], 2); ?>
                                            </div>
                                            <div class="grade-max">out of <?php echo $grade['max_score']; ?></div>
                                            <div class="grade-percentage"><?php echo round($percentage, 1); ?>%</div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($grade['feedback']): ?>
                                        <div class="feedback-section">
                                            <div class="feedback-label">Teacher Feedback</div>
                                            <div class="feedback-text"><?php echo nl2br(htmlspecialchars($grade['feedback'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($grade['graded_at']): ?>
                                        <div class="graded-date">
                                            Graded on <?php echo formatDate($grade['graded_at']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
    <?php
                endif;
            endforeach;
        else:
    ?>
            <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                <h3>No Grades Yet</h3>
                <p>Your graded assignments will appear here once your teacher has graded them.</p>
            </div>
    <?php
        endif;
    } catch (Exception $e) {
        echo '<div class="empty-state"><p style="color: #dc2626;">Error loading grades: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
    ?>
</div>

<script>
    (function() {
        const form = document.querySelector('.filter-inline');
        if (!form) return;

        const searchInput = form.querySelector('input[name="search"]');
        const selects = form.querySelectorAll('select');
        let debounceTimer = null;

        const submitForm = () => {
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitForm, 350);
            });
        }

        selects.forEach(select => {
            select.addEventListener('change', submitForm);
        });
    })();
</script>

<?php studentLayoutEnd(); ?>
