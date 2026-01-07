<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';

Auth::requireRole('admin');

adminLayoutStart('users', 'Teachers');
?>

<div style="max-width: 1400px; margin: 0 auto;">
  <div class="card">
    <div style="padding:.9rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
      <h3 style="margin:0; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:.5rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Teachers
      </h3>
      <div style="font-size:.8rem; color:#6b7280;">Manage teacher accounts and courses</div>
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
          <button type="submit" style="padding:.5rem .75rem; background:#3b82f6; color:#fff; border:none; border-radius:.5rem; font-weight:600;">Filter</button>
        </div>
      </form>

      <?php
      $clauses = ["role = 'teacher'"]; $params = [];
      $q = trim($_GET['q'] ?? ''); $status = $_GET['status'] ?? '';
      if ($status !== '') { $clauses[] = "status = ?"; $params[] = $status; }
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
              <th>Joined</th>
              <th>Active Courses</th>
              <th>Total Students</th>
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
              <td style="font-size:.82rem; color:#6b7280;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
              <td>
                <?php
                try {
                  $cstmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM courses WHERE teacher_id = ? AND status='active'");
                  $cstmt->execute([$user['id']]); $cnt = $cstmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
                  echo (int)$cnt;
                } catch (Exception $ex) { echo '0'; }
                ?>
              </td>
              <td>
                <?php
                try {
                  $estmt = $pdo->prepare("SELECT COUNT(DISTINCT e.student_id) AS cnt FROM enrollments e JOIN courses c ON e.course_id=c.id WHERE c.teacher_id = ? AND e.status='enrolled'");
                  $estmt->execute([$user['id']]); $cnt = $estmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
                  echo (int)$cnt;
                } catch (Exception $ex) { echo '0'; }
                ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p style="margin-top:.6rem; font-size:.8rem; color:#6b7280; text-align:right;">Total Teachers: <strong><?php echo count($users); ?></strong></p>
    </div>
  </div>
</div>

<?php adminLayoutEnd(); ?>
