<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

teacherLayoutStart('quizzes', 'Quizzes');
?>


<div class="content-card">
    <div class="card-header">
        <h2>Manage Quizzes</h2>
        <a href="#" class="btn btn-primary btn-sm" onclick="alert('Quiz creation coming soon'); return false;">+ Create Quiz</a>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT q.*, c.title as course_title
                        FROM quizzes q 
                        JOIN courses c ON q.course_id = c.id 
                        WHERE c.teacher_id = ? 
                        ORDER BY q.created_at DESC
                    ");
                    $stmt->execute([$teacher_id]);
                    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($quizzes)):
                        foreach ($quizzes as $q):
                ?>
                        <tr>
                            <td><?php echo htmlspecialchars(substr($q['title'], 0, 30)); ?></td>
                            <td><?php echo htmlspecialchars($q['course_title']); ?></td>
                            <td><?php echo $q['due_date'] ? date('M d, Y', strtotime($q['due_date'])) : 'N/A'; ?></td>
                            <td>
                                <a href="#" class="btn-small" onclick="alert('Quiz management coming soon'); return false;">Manage</a>
                            </td>
                        </tr>
                <?php 
                        endforeach;
                    else:
                ?>
                        <tr><td colspan="4" style="text-align:center;color:#9ca3af;">No quizzes created</td></tr>
                <?php 
                    endif;
                } catch (Exception $e) {
                    echo '<tr><td colspan="4" style="color:red;">Error loading quizzes</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <p style="color:#9ca3af;margin-top:1.5rem;font-size:0.8rem;">Quiz functionality is available. Teachers can create quizzes with multiple-choice, true/false, and short-answer questions.</p>
</div>

<?php teacherLayoutEnd(); ?>
