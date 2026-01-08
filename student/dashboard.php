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
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) as count FROM assignments a JOIN enrollments e ON a.course_id = e.course_id LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ? WHERE e.student_id = ? AND e.status = 'enrolled' AND a.due_date > NOW() AND sub.id IS NULL");
    $stmt->execute([$student_id, $student_id]);
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
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#eef2ff;display:flex;align-items:center;justify-content:center;color:#4f46e5;">
                    <svg style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2" viewBox="0 0 24 24">
                        <path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo $progressPercent; ?>%</div>
                    <div class="stat-label" style="font-size:0.8rem">Student Progress</div>
                </div>
            </div>
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#ecfeff;display:flex;align-items:center;justify-content:center;color:#0891b2;">
                    <svg style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo $activityPercent; ?>%</div>
                    <div class="stat-label" style="font-size:0.8rem">Total Activity (7d)</div>
                </div>
            </div>
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#fef3c7;display:flex;align-items:center;justify-content:center;color:#b45309;">
                    <svg style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo htmlspecialchars($totalTimeText); ?></div>
                    <div class="stat-label" style="font-size:0.8rem">Total Time</div>
                </div>
            </div>
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#059669;">
                    <svg style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo $enrolled_courses; ?></div>
                    <div class="stat-label" style="font-size:0.8rem">Enrolled Courses</div>
                </div>
            </div>
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#dc2626;">
                    <svg style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo $pending_assignments; ?></div>
                    <div class="stat-label" style="font-size:0.8rem">Pending Assignments</div>
                </div>
            </div>
            <div class="stat-card" style="display:flex;align-items:center;gap:0.8rem;padding:1rem">
                <div style="width:40px;height:40px;border-radius:0.6rem;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;">
                    <svg style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2" viewBox="0 0 24 24">
                        <path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-value" style="margin:0;font-size:1.5rem"><?php echo $average_grade; ?></div>
                    <div class="stat-label" style="font-size:0.8rem">Average Grade</div>
                </div>
            </div>
        </div>

        <!-- Upcoming Assignments -->
        <?php if (!empty($upcoming)): ?>
        <div class="card">
            <h3 style="font-size:1rem;margin-bottom:1rem;font-weight:600;">Upcoming Assignments</h3>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <?php foreach ($upcoming as $up): ?>
                    <div style="padding:0.9rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-primary);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($up['title']); ?></strong>
                            <div class="stat-label" style="font-size:0.75rem;margin-top:0.25rem;">
                                <?php echo htmlspecialchars($up['code']); ?> • Due: <?php echo formatDate($up['due_date']); ?>
                            </div>
                        </div>
                        <a href="assignments.php" class="chip" style="text-decoration:none;">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Announcements -->
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, c.code, c.title as course_title 
                FROM announcements a 
                JOIN courses c ON a.course_id = c.id 
                JOIN enrollments e ON c.id = e.course_id 
                WHERE e.student_id = ? AND e.status = 'enrolled'
                ORDER BY a.posted_at DESC 
                LIMIT 3
            ");
            $stmt->execute([$student_id]);
            $recentAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $recentAnnouncements = [];
        }
        ?>
        <?php if (!empty($recentAnnouncements)): ?>
        <div class="card">
            <h3 style="font-size:1rem;margin-bottom:1rem;font-weight:600;">Recent Announcements</h3>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <?php foreach ($recentAnnouncements as $ann): ?>
                    <div style="padding:0.9rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-primary);">
                        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:0.5rem;">
                            <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($ann['title']); ?></strong>
                            <?php if ($ann['pinned']): ?>
                                <span style="font-size:0.65rem;color:#2563eb;font-weight:600;">PINNED</span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-label" style="font-size:0.75rem;">
                            <?php echo htmlspecialchars($ann['code']); ?> • <?php echo formatDate($ann['posted_at']); ?>
                        </div>
                        <p style="font-size:0.8rem;color:#6b7280;margin:0.5rem 0 0 0;line-height:1.5;">
                            <?php echo htmlspecialchars(substr($ann['content'], 0, 100)); ?><?php echo strlen($ann['content']) > 100 ? '...' : ''; ?>
                        </p>
                        <a href="announcements.php" style="font-size:0.75rem;color:#2563eb;text-decoration:none;margin-top:0.5rem;display:inline-block;">Read more →</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;">
            <div>
                <h3 style="font-size:1rem;margin-bottom:.5rem;font-weight:600;">Today&#39;s Activity</h3>
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT a.id,a.title,a.due_date,c.code,
                        (SELECT COUNT(*) FROM grades g WHERE g.assignment_id=a.id AND g.student_id=?) as has_grade,
                        (SELECT s.status FROM assignment_submissions s WHERE s.assignment_id=a.id AND s.student_id=? LIMIT 1) as sub_status
                        FROM assignments a 
                        JOIN enrollments e ON e.course_id=a.course_id 
                        JOIN courses c ON c.id=a.course_id 
                        WHERE e.student_id=? AND DATE(a.due_date)=CURDATE() 
                        ORDER BY a.due_date ASC LIMIT 4");
                    $stmt->execute([$student_id,$student_id,$student_id]);
                    $todayActs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) { $todayActs = []; }
                ?>
                <?php if ($todayActs): foreach ($todayActs as $it): ?>
                    <div style="padding:.9rem;border:1px solid var(--border-color);border-radius:.75rem;margin-bottom:.6rem;background:var(--bg-primary);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <strong><?php echo htmlspecialchars($it['title']); ?></strong>
                            <div class="stat-label"><?php echo htmlspecialchars($it['code']); ?> • Time: <?php echo htmlspecialchars(date('g:i A', strtotime($it['due_date']))); ?></div>
                        </div>
                        <?php
                            $isCompleted = (int)$it['has_grade'] > 0;
                            $isSubmitted = !$isCompleted && !empty($it['sub_status']);
                            $chipText = $isCompleted ? 'Completed' : ($isSubmitted ? 'Submitted' : 'Not Started');
                            $chipStyle = $isCompleted
                                ? 'background:#ecfdf5;border-color:#86efac;color:#166534'
                                : ($isSubmitted ? 'background:#dbeafe;border-color:#93c5fd;color:#0c2d6b' : '');
                        ?>
                        <span class="chip" style="<?php echo $chipStyle; ?>"><?php echo $chipText; ?></span>
                    </div>
                <?php endforeach; else: ?>
                    <div class="stat-label">No activities due today.</div>
                <?php endif; ?>
            </div>
            <div>
                <h3 style="font-size:1rem;margin-bottom:.5rem;font-weight:600;">My Tasks</h3>
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
