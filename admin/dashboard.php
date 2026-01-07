<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';

Auth::requireRole('admin');

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $users_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $total_courses = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'enrolled'");
    $total_enrollments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM academic_terms WHERE is_active = TRUE");
    $active_terms = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->query("
        SELECT t.name, COUNT(e.id) as enrollment_count
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        JOIN academic_terms t ON c.term_id = t.id
        WHERE e.status = 'enrolled'
        GROUP BY t.id, t.name
        ORDER BY t.start_date DESC
        LIMIT 8
    ");
    $enrollments_by_term = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $activity_logs = [];
    try {
        $logStmt = $pdo->query("SELECT user_name, role, action, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 8");
        $activity_logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $activity_logs = [];
    }
} catch (Exception $e) {
    $total_users = $total_courses = $total_enrollments = $active_terms = 0;
    $users_by_role = $enrollments_by_term = $activity_logs = [];
}

adminLayoutStart('dashboard', 'Dashboard');
?>

    <div style="max-width: 1400px; margin: 0 auto;">
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_courses; ?></div>
                <div class="stat-label">Active Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_enrollments; ?></div>
                <div class="stat-label">Active Enrollments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $active_terms; ?></div>
                <div class="stat-label">Active Terms</div>
            </div>
        </div>

        <div class="card" style="margin-top: 1.5rem; display:grid; grid-template-columns: repeat(auto-fit,minmax(320px,1fr)); gap:1rem;">
            <div style="padding:1rem; border-right:1px solid #e5e7eb; min-width:0;">
                <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; font-weight:700; color:#0f172a;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Role Mix
                </div>
                <?php if (!empty($users_by_role)): ?>
                    <?php
                        $maxRoleCount = max(array_column($users_by_role, 'count')) ?: 1;
                    ?>
                    <div style="display:grid; gap:.6rem;">
                        <?php foreach ($users_by_role as $role_data): 
                            $pct = ($role_data['count'] / $maxRoleCount) * 100;
                        ?>
                        <div>
                            <div style="display:flex; justify-content:space-between; font-size:.82rem; color:#475569; margin-bottom:.15rem;">
                                <span style="text-transform:capitalize; font-weight:600; color:#1f2937;"><?php echo htmlspecialchars($role_data['role']); ?></span>
                                <span style="color:#0f172a; font-weight:600;"><?php echo (int)$role_data['count']; ?></span>
                            </div>
                            <div style="background:#e5e7eb; border-radius:999px; height:10px; overflow:hidden;">
                                <div style="width: <?php echo $pct; ?>%; height:100%; background:#3b82f6;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#9ca3af; font-size:.88rem;">No role data available</p>
                <?php endif; ?>
            </div>

            <div style="padding:1rem; min-width:0;">
                <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; font-weight:700; color:#0f172a;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 17 9 11 13 15 21 7"/><polyline points="14 7 21 7 21 14"/></svg>
                    Enrollment Trend
                </div>
                <?php if (!empty($enrollments_by_term)): ?>
                    <?php
                        $maxEnroll = max(array_column($enrollments_by_term, 'enrollment_count')) ?: 1;
                        $points = [];
                        $xStep = count($enrollments_by_term) > 1 ? 180/(count($enrollments_by_term)-1) : 0;
                        foreach ($enrollments_by_term as $i => $row) {
                            $x = 10 + $i * $xStep;
                            $y = 90 - ( (int)$row['enrollment_count'] / $maxEnroll) * 60;
                            $points[] = $x . ',' . $y;
                        }
                    ?>
                    <svg viewBox="0 0 200 100" width="100%" height="140" preserveAspectRatio="xMidYMid meet" style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:.5rem; padding:6px;">
                        <?php if (count($points) > 1): ?>
                            <polyline fill="none" stroke="#10b981" stroke-width="3" points="<?php echo implode(' ', $points); ?>" />
                        <?php endif; ?>
                        <?php foreach ($points as $idx => $pt): list($px,$py)=explode(',', $pt); ?>
                            <circle cx="<?php echo $px; ?>" cy="<?php echo $py; ?>" r="4" fill="#10b981" />
                            <text x="<?php echo $px; ?>" y="95" font-size="10" fill="#334155" text-anchor="middle"><?php echo htmlspecialchars($enrollments_by_term[$idx]['name']); ?></text>
                        <?php endforeach; ?>
                    </svg>
                <?php else: ?>
                    <p style="color:#9ca3af; font-size:.88rem;">No enrollment data</p>
                <?php endif; ?>
            </div>

            <div style="padding:1rem; border-left:1px solid #e5e7eb; min-width:0;">
                <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; font-weight:700; color:#0f172a;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M9 4v16"/><path d="M4 9h16"/></svg>
                    Recent Activity
                </div>
                <?php if (!empty($activity_logs)): ?>
                    <ul style="list-style:none; padding:0; margin:0; display:grid; gap:.65rem;">
                        <?php foreach ($activity_logs as $log): ?>
                            <li style="display:grid; grid-template-columns: auto 1fr; gap:.75rem; align-items:center;">
                                <div style="width:34px; height:34px; border-radius:50%; background:#fef3c7; display:flex; align-items:center; justify-content:center; color:#b45309; font-weight:700; text-transform:uppercase;">
                                    <?php echo htmlspecialchars(substr($log['role'] ?? 'U',0,1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight:700; color:#111827;"><?php echo htmlspecialchars($log['user_name'] ?? 'User'); ?></div>
                                    <div style="font-size:.82rem; color:#4b5563;"><?php echo htmlspecialchars($log['action'] ?? ''); ?></div>
                                    <div style="font-size:.75rem; color:#9ca3af; margin-top:.1rem;">
                                        <?php echo isset($log['created_at']) ? date('M d, Y h:i A', strtotime($log['created_at'])) : ''; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color:#9ca3af; font-size:.88rem;">No recent activity logged</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php adminLayoutEnd(); ?>
