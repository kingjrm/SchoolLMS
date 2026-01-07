<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../includes/student_layout.php';
require_once '../includes/TimeTracking.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();
$student_id = $user['id'];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'enrolled'");
    $stmt->execute([$student_id]);
    $enrolled_courses = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) as count FROM assignments a JOIN enrollments e ON a.course_id = e.course_id WHERE e.student_id = ? AND e.status = 'enrolled' AND a.due_date > NOW()");
    $stmt->execute([$student_id]);
    $pending_assignments = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT AVG(CAST(g.score as DECIMAL(5,2))) as avg_grade FROM grades g JOIN assignments a ON g.assignment_id = a.id JOIN enrollments e ON a.course_id = e.course_id WHERE g.student_id = ? AND e.status = 'enrolled'");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $average_grade = $result && $result['avg_grade'] ? round($result['avg_grade'], 2) : 'N/A';
} catch (Exception $e) {
    $enrolled_courses = $pending_assignments = 0;
    $average_grade = 'N/A';
}

// Derived metrics for dashboard
try {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) AS total FROM assignments a JOIN enrollments e ON a.course_id=e.course_id WHERE e.student_id=? AND e.status='enrolled'");
    $stmt->execute([$student_id]);
    $totalAssigned = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT g.assignment_id) AS done FROM grades g JOIN assignments a ON g.assignment_id=a.id JOIN enrollments e ON a.course_id=e.course_id WHERE g.student_id=? AND e.status='enrolled' AND g.score IS NOT NULL");
    $stmt->execute([$student_id]);
    $completedAssigned = (int)$stmt->fetch(PDO::FETCH_ASSOC)['done'];

    $progressPercent = $totalAssigned > 0 ? round(($completedAssigned/$totalAssigned)*100) : 0;

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) AS total7 FROM assignments a JOIN enrollments e ON a.course_id=e.course_id WHERE e.student_id=? AND e.status='enrolled' AND a.due_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$student_id]);
    $total7 = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total7'];

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT g.assignment_id) AS done7 FROM grades g JOIN assignments a ON g.assignment_id=a.id JOIN enrollments e ON a.course_id=e.course_id WHERE g.student_id=? AND e.status='enrolled' AND g.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$student_id]);
    $done7 = (int)$stmt->fetch(PDO::FETCH_ASSOC)['done7'];

    $activityPercent = $total7 > 0 ? round(($done7/$total7)*100) : 0;
} catch (Exception $e) {
    $progressPercent = 0; $activityPercent = 0; $totalAssigned = 0; $completedAssigned = 0;
}

// Get total time spent (from time tracking table)
try {
    $totalSeconds = TimeTracking::getTodayTotalSeconds($pdo, $student_id);
    $totalTimeText = TimeTracking::formatSeconds($totalSeconds);
} catch (Exception $e) {
    $totalTimeText = '0m';
}

studentLayoutStart('dashboard', 'Dashboard');

// Upcoming assignments (top 3)
try {
    $stmt = $pdo->prepare("SELECT a.title, a.due_date, c.code FROM assignments a JOIN courses c ON a.course_id=c.id JOIN enrollments e ON e.course_id=c.id WHERE e.student_id=? AND e.status='enrolled' AND a.due_date>=NOW() ORDER BY a.due_date ASC LIMIT 3");
    $stmt->execute([$student_id]);
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $upcoming = []; }

// Personal tasks (top 5 incomplete)
try {
    require_once '../includes/Tasks.php';
    Tasks::ensureTable($pdo);
    $tasks = Tasks::list($pdo, $student_id, 0, 5);
} catch (Exception $e) { $tasks = []; }
?>

        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#eef2ff;display:flex;align-items:center;justify-content:center;color:#4f46e5;font-weight:700;font-size:1.1rem">%</div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo $progressPercent; ?>%</div>
                    <div class="stat-label" style="font-size:0.8rem">Student Progress</div>
                </div>
            </div>
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#ecfeff;display:flex;align-items:center;justify-content:center;color:#0891b2;font-weight:700;font-size:1.1rem">%</div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo $activityPercent; ?>%</div>
                    <div class="stat-label" style="font-size:0.8rem">Total Activity (7d)</div>
                </div>
            </div>
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#fef3c7;display:flex;align-items:center;justify-content:center;color:#b45309;font-weight:700;font-size:1.1rem">⏱</div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo htmlspecialchars($totalTimeText); ?></div>
                    <div class="stat-label" style="font-size:0.8rem">Total Time</div>
                </div>
            </div>
        </div>

        <div class="card" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;">
            <div>
                <h3 style="font-size:1rem;margin-bottom:.5rem;">Today&#39;s Activity</h3>
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT a.id,a.title,a.due_date,c.code,(SELECT COUNT(*) FROM grades g WHERE g.assignment_id=a.id AND g.student_id=?) as has_grade FROM assignments a JOIN enrollments e ON e.course_id=a.course_id JOIN courses c ON c.id=a.course_id WHERE e.student_id=? AND DATE(a.due_date)=CURDATE() ORDER BY a.due_date ASC LIMIT 4");
                    $stmt->execute([$student_id,$student_id]);
                    $todayActs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) { $todayActs = []; }
                ?>
                <?php if ($todayActs): foreach ($todayActs as $it): ?>
                    <div style="padding:.9rem;border:1px solid var(--border-color);border-radius:.75rem;margin-bottom:.6rem;background:var(--bg-primary);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <strong><?php echo htmlspecialchars($it['title']); ?></strong>
                            <div class="stat-label"><?php echo htmlspecialchars($it['code']); ?> • Time: <?php echo htmlspecialchars(date('g:i A', strtotime($it['due_date']))); ?></div>
                        </div>
                        <span class="chip" style="<?php echo ((int)$it['has_grade']>0? 'background:#ecfdf5;border-color:#86efac;color:#166534' : ''); ?>"><?php echo ((int)$it['has_grade']>0? 'Completed' : 'Not Started'); ?></span>
                    </div>
                <?php endforeach; else: ?>
                    <div class="stat-label">No activities due today.</div>
                <?php endif; ?>
            </div>
            <div>
                <h3 style="font-size:1rem;margin-bottom:.5rem;">My Tasks</h3>
                <?php if ($tasks): foreach ($tasks as $t): ?>
                    <div style="padding:.9rem;border:1px solid var(--border-color);border-radius:.75rem;margin-bottom:.6rem;background:var(--bg-primary);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <strong><?php echo htmlspecialchars($t['title']); ?></strong>
                            <div class="stat-label">
                                <?php if (!empty($t['due_date'])): ?>Due <?php echo htmlspecialchars(date('M d, Y', strtotime($t['due_date']))); ?> · <?php endif; ?>
                                Priority: <?php echo htmlspecialchars(ucfirst($t['priority'])); ?>
                            </div>
                        </div>
                        <a class="chip" href="tasks.php">Open</a>
                    </div>
                <?php endforeach; else: ?>
                    <div class="stat-label">No tasks yet. <a href="tasks.php">Create one</a>.</div>
                <?php endif; ?>
            </div>
        </div>

<?php studentLayoutEnd(); ?>
