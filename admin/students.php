<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';

Auth::requireRole('admin');

adminLayoutStart('users', 'Students');
?>

<div style="max-width: 1400px; margin: 0 auto;">
  <div class="card">
    <div style="padding:.9rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
      <h3 style="margin:0; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:.5rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Students
      </h3>
      <div style="font-size:.8rem; color:#6b7280;">Manage student accounts and enrollments</div>
    </div>
    <div style="padding:1rem;">
      <form method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:.5rem; margin-bottom: .75rem; align-items:end;">
        <div>
          <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Search</label>
          <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="username, email, name" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
        </div>
        <div>
          <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Status</label>
          <select name="status" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
            <option value="">All</option>
            <option value="active" <?php echo (($_GET['status'] ?? '')==='active')?'selected':''; ?>>Active</option>
            <option value="inactive" <?php echo (($_GET['status'] ?? '')==='inactive')?'selected':''; ?>>Inactive</option>
          </select>
        </div>
        <div>
          <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Verified</label>
          <select name="verified" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
            <option value="">All</option>
            <option value="1" <?php echo (($_GET['verified'] ?? '')==='1')?'selected':''; ?>>Yes</option>
            <option value="0" <?php echo (($_GET['verified'] ?? '')==='0')?'selected':''; ?>>No</option>
          </select>
        </div>
        <div>
          <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Section</label>
          <input type="text" name="section" value="<?php echo htmlspecialchars($_GET['section'] ?? ''); ?>" placeholder="e.g., CS101-A" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
        </div>
        <div>
          <button type="submit" style="padding:.5rem .75rem; background:#3b82f6; color:#fff; border:none; border-radius:.5rem; font-weight:600;">Filter</button>
        </div>
      </form>

      <?php
      $clauses = ["role = 'student'"]; $params = [];
      $q = trim($_GET['q'] ?? ''); $status = $_GET['status'] ?? ''; $verified = $_GET['verified'] ?? '';
      $section = trim($_GET['section'] ?? '');
      if ($status !== '') { $clauses[] = "status = ?"; $params[] = $status; }
      if ($verified !== '') { $clauses[] = "is_verified = ?"; $params[] = (int)$verified; }
      if ($q !== '') { $clauses[] = "(username LIKE ? OR email LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }

      $query = "SELECT * FROM users"; if (!empty($clauses)) { $query .= " WHERE " . implode(' AND ', $clauses); }
      $query .= " ORDER BY created_at DESC";
      $stmt = $pdo->prepare($query); $stmt->execute($params); $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <div style="overflow-x:auto;">
        <table class="table">
          <thead>
            <tr>
              <th>Username</th>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
              <th>Verified</th>
              <th>Joined</th>
              <th>Section(s)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
              <td style="font-weight:600;"><?php echo htmlspecialchars($user['username']); ?></td>
              <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
              <td style="font-size:.85rem; color:#6b7280;"><?php echo htmlspecialchars($user['email']); ?></td>
              <td>
                <span class="badge" style="background: <?php echo $user['status']==='active'?'#ecfdf5':'#fef2f2'; ?>; color: <?php echo $user['status']==='active'?'#065f46':'#991b1b'; ?>;"><?php echo $user['status']; ?></span>
              </td>
              <td>
                <?php if ($user['is_verified']): ?>
                  <span style="display:inline-flex;align-items:center;gap:.3rem;color:#10b981;font-weight:600;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Yes
                  </span>
                <?php else: ?>
                  <span style="color:#6b7280;">No</span>
                <?php endif; ?>
              </td>
              <td style="font-size:.82rem; color:#6b7280;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
              <td style="font-size:.8rem; color:#374151;">
                <?php
                try {
                  $sstmt = $pdo->prepare("SELECT c.code, cs.section_code FROM enrollments e LEFT JOIN courses c ON e.course_id=c.id LEFT JOIN course_sections cs ON e.section_id=cs.id WHERE e.student_id = ? AND e.status='enrolled' ORDER BY c.code");
                  $sstmt->execute([$user['id']]);
                  $rows = $sstmt->fetchAll(PDO::FETCH_ASSOC);
                  $labels = [];
                  foreach ($rows as $r) { $labels[] = htmlspecialchars(($r['code'] ?? '—') . (isset($r['section_code']) && $r['section_code']!==null ? ('-' . $r['section_code']) : '')); }
                  echo !empty($labels) ? implode(', ', $labels) : '<span style="color:#9ca3af">—</span>';
                } catch (Exception $ex) { echo '<span style="color:#9ca3af">—</span>'; }
                ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p style="margin-top:.6rem; font-size:.8rem; color:#6b7280; text-align:right;">Total Students: <strong><?php echo count($users); ?></strong></p>
    </div>
  </div>
</div>

<?php adminLayoutEnd(); ?>
