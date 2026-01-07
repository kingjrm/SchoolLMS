<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';

Auth::requireRole('admin');

$message = '';
$error = '';

// Filters
$tab = $_GET['tab'] ?? 'general';
$role = $_GET['role'] ?? '';
$user = trim($_GET['user'] ?? '');
$action = trim($_GET['action'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');

$logs = [];
if ($tab === 'activity') {
    try {
        $clauses = [];
        $params = [];
        if ($role !== '') { $clauses[] = 'role = ?'; $params[] = $role; }
        if ($user !== '') { $clauses[] = '(user_name LIKE ? OR user_email LIKE ?)'; $params[] = "%$user%"; $params[] = "%$user%"; }
        if ($action !== '') { $clauses[] = 'action LIKE ?'; $params[] = "%$action%"; }
        if ($from !== '') { $clauses[] = 'created_at >= ?'; $params[] = $from . ' 00:00:00'; }
        if ($to !== '') { $clauses[] = 'created_at <= ?'; $params[] = $to . ' 23:59:59'; }
        $sql = 'SELECT * FROM activity_logs';
        if (!empty($clauses)) { $sql .= ' WHERE ' . implode(' AND ', $clauses); }
        $sql .= ' ORDER BY created_at DESC LIMIT 100';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $logs = [];
        $error = 'Activity log table not found or could not be read.';
    }
}

adminLayoutStart('settings', 'Settings');
?>

<div style="max-width: 1200px; margin: 0 auto;">
  <?php if ($message): ?>
    <div style="padding:.75rem; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; border-radius:.5rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      <span><?php echo htmlspecialchars($message); ?></span>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="padding:.75rem; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:.5rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <span><?php echo htmlspecialchars($error); ?></span>
    </div>
  <?php endif; ?>

  <div class="card">
    <div style="padding:.9rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:.5rem;">
      <a href="?tab=general" style="padding:.45rem .7rem; border:1px solid #e5e7eb; border-radius:.6rem; text-decoration:none; font-weight:600; font-size:.86rem; color:<?php echo $tab==='general'?'#1d4ed8':'#475569'; ?>; background:<?php echo $tab==='general'?'#eef2ff':'#fff'; ?>;">General</a>
      <a href="?tab=activity" style="padding:.45rem .7rem; border:1px solid #e5e7eb; border-radius:.6rem; text-decoration:none; font-weight:600; font-size:.86rem; color:<?php echo $tab==='activity'?'#1d4ed8':'#475569'; ?>; background:<?php echo $tab==='activity'?'#eef2ff':'#fff'; ?>;">Activity Logs</a>
    </div>
    <div style="padding:1rem;">
      <?php if ($tab === 'general'): ?>
        <p style="color:#475569; margin:0;">General settings will go here.</p>
      <?php else: ?>
        <form class="filter-form" method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:.6rem; margin-bottom:.75rem; align-items:end;">
          <input type="hidden" name="tab" value="activity">
          <div>
            <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Role</label>
            <select name="role" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
              <option value="">All</option>
              <option value="admin" <?php echo $role==='admin'?'selected':''; ?>>Admin</option>
              <option value="teacher" <?php echo $role==='teacher'?'selected':''; ?>>Teacher</option>
              <option value="student" <?php echo $role==='student'?'selected':''; ?>>Student</option>
            </select>
          </div>
          <div>
            <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">User</label>
            <input type="text" name="user" value="<?php echo htmlspecialchars($user); ?>" placeholder="name or email" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
          </div>
          <div>
            <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Action</label>
            <input type="text" name="action" value="<?php echo htmlspecialchars($action); ?>" placeholder="contains..." style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
          </div>
          <div>
            <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">From</label>
            <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
          </div>
          <div>
            <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">To</label>
            <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
          </div>
          <div>
            <button type="submit" style="padding:.5rem .75rem; background:#3b82f6; color:#fff; border:none; border-radius:.5rem; font-weight:600;">Filter</button>
          </div>
        </form>

        <div style="overflow-x:auto;">
          <table class="table">
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Action</th>
                <th>When</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($log['user_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($log['role'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($log['action'] ?? ''); ?></td>
                    <td><?php echo isset($log['created_at']) ? date('M d, Y h:i A', strtotime($log['created_at'])) : ''; ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                  <tr><td colspan="4" style="text-align:center; color:#9ca3af;">No activity logs found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php adminLayoutEnd(); ?>
