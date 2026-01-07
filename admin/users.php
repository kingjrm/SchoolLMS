<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';
require_once '../includes/ActivityLogger.php';

Auth::requireRole('admin');

// Handle actions
$message = '';
$error = '';
$isAjax = isset($_GET['ajax']);

function renderUserRows(array $users, PDO $pdo): string {
    ob_start();
    foreach ($users as $user): ?>
        <tr>
            <td style="font-weight: 600;">
                <?php echo htmlspecialchars($user['username']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
            </td>
            <td style="font-size: 0.875rem; color: #6b7280;">
                <?php echo htmlspecialchars($user['email']); ?>
            </td>
            <td>
                <span style="display: inline-block; padding: 0.25rem 0.75rem; background: 
                    <?php 
                    if ($user['role'] === 'admin') echo '#fee2e2;';
                    elseif ($user['role'] === 'teacher') echo '#eff6ff;';
                    else echo '#ecfdf5;';
                    ?>
                    ; color: 
                    <?php 
                    if ($user['role'] === 'admin') echo '#991b1b;';
                    elseif ($user['role'] === 'teacher') echo '#1e40af;';
                    else echo '#065f46;';
                    ?>
                    ; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600; text-transform: capitalize;">
                    <?php echo $user['role']; ?>
                </span>
            </td>
            <td>
                <span style="display: inline-block; padding: 0.25rem 0.75rem; background: 
                    <?php echo $user['status'] === 'active' ? '#ecfdf5;' : '#fef2f2;'; ?>
                    ; color: 
                    <?php echo $user['status'] === 'active' ? '#065f46;' : '#991b1b;'; ?>
                    ; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600; text-transform: capitalize;">
                    <?php echo $user['status']; ?>
                </span>
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
            <td style="font-size: 0.85rem; color: #6b7280;">
                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
            </td>
            <td style="font-size:.8rem; color:#374151;">
                <?php
                if ($user['role'] === 'student') {
                    try {
                        $sstmt = $pdo->prepare("SELECT c.code, cs.section_code FROM enrollments e LEFT JOIN courses c ON e.course_id=c.id LEFT JOIN course_sections cs ON e.section_id=cs.id WHERE e.student_id = ? AND e.status='enrolled' ORDER BY c.code");
                        $sstmt->execute([$user['id']]);
                        $rows = $sstmt->fetchAll(PDO::FETCH_ASSOC);
                        if (!empty($rows)) {
                            $labels = array_map(function($r){ return htmlspecialchars(($r['code'] ?? '—') . (isset($r['section_code']) && $r['section_code']!==null ? ('-' . $r['section_code']) : '')); }, $rows);
                            echo implode(', ', $labels);
                        } else { echo '<span style="color:#9ca3af">—</span>'; }
                    } catch (Exception $ex) { echo '<span style="color:#9ca3af">—</span>'; }
                } else {
                    echo '<span style="color:#9ca3af">—</span>';
                }
                ?>
            </td>
            <td>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                    <button type="submit" style="background: none; border: none; color: #3b82f6; cursor: pointer; font-weight: 600; font-size: 0.85rem; text-decoration: underline;">
                        <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach;
    return ob_get_clean();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $user_id = (int)$_POST['user_id'];
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $user_id]);
            $message = "User status updated successfully.";
            ActivityLogger::log($pdo, 'Updated user status', ['target_user_id'=>$user_id, 'new_status'=>$status]);
        } catch (Exception $e) {
            $error = "Failed to update user status.";
        }
    }
}

$view = $_GET['view'] ?? 'all';
$sectionOptions = [];
try {
    $secStmt = $pdo->query("SELECT DISTINCT cs.section_code, c.code FROM course_sections cs JOIN courses c ON cs.course_id=c.id WHERE cs.section_code IS NOT NULL AND cs.section_code<>'' ORDER BY c.code, cs.section_code");
    $sectionOptions = $secStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $sectionOptions = []; }

$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$verified = $_GET['verified'] ?? '';
$q = trim($_GET['q'] ?? '');
$section = trim($_GET['section'] ?? '');

try {
    $clauses = [];
    $params = [];

    if ($view === 'students') { $clauses[] = "u.role = 'student'"; }
    elseif ($view === 'teachers') { $clauses[] = "u.role = 'teacher'"; }

    if ($role !== '') { $clauses[] = "u.role = ?"; $params[] = $role; }
    if ($status !== '') { $clauses[] = "u.status = ?"; $params[] = $status; }
    if ($verified !== '') { $clauses[] = "u.is_verified = ?"; $params[] = (int)$verified; }
    if ($q !== '') {
        $clauses[] = "(u.username LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)";
        $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
    }
    if ($section !== '') {
        $clauses[] = "u.id IN (SELECT e.student_id FROM enrollments e LEFT JOIN course_sections cs ON e.section_id=cs.id WHERE cs.section_code = ? AND e.status='enrolled')";
        $params[] = $section;
    }

    $query = "SELECT u.* FROM users u";
    if (!empty($clauses)) { $query .= " WHERE " . implode(' AND ', $clauses); }
    $query .= " ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}

$rowsHtml = renderUserRows($users, $pdo);
$totalUsers = count($users);

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rowsHtml,
        'count' => $totalUsers,
        'hasData' => $totalUsers > 0,
        'empty' => '<p style="text-align: center; color: #9ca3af; padding: 2rem;">No users found</p>'
    ]);
    exit;
}

if (!$isAjax) {
    adminLayoutStart('users', 'User Management');
}
?>

    <div style="max-width: 1400px; margin: 0 auto;">
        <?php if ($message): ?>
            <div style="padding: 1rem; background: #ecfdf5; color: #065f46; border-radius: 0.5rem; border: 1px solid #a7f3d0; margin-bottom: 1.5rem; display:flex; align-items:center; gap:.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="padding: 1rem; background: #fef2f2; color: #991b1b; border-radius: 0.5rem; border: 1px solid #fecaca; margin-bottom: 1.5rem; display:flex; align-items:center; gap:.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <div style="padding: .9rem; border-bottom: 1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; gap:.75rem;">
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700; display:flex; align-items:center; gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    User Management
                </h3>
                <div style="display:flex; gap:.5rem;">
                    <a href="?view=all" style="padding:.4rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem; text-decoration:none; font-size:.78rem; <?php echo $view==='all'?'background:#eef2ff;color:#1e3a8a;':'color:#374151;background:#fff;'; ?>">All</a>
                    <a href="?view=students" style="padding:.4rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem; text-decoration:none; font-size:.78rem; <?php echo $view==='students'?'background:#ecfdf5;color:#065f46;':'color:#374151;background:#fff;'; ?>">Students</a>
                    <a href="?view=teachers" style="padding:.4rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem; text-decoration:none; font-size:.78rem; <?php echo $view==='teachers'?'background:#eff6ff;color:#1e40af;':'color:#374151;background:#fff;'; ?>">Teachers</a>
                </div>
            </div>
            <div style="padding: 1rem;">
                <form id="filter-form" class="filter-form" method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:.5rem; margin-bottom: .75rem; align-items:end;">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <div>
                        <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Search</label>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="username, email, name" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                    </div>
                    <div>
                        <label style="display:block; font-size:.72rem; color:#6b7280; margin-bottom:.25rem;">Role</label>
                        <select name="role" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                            <option value="">All</option>
                            <option value="admin" <?php echo (($_GET['role'] ?? '')==='admin')?'selected':''; ?>>Admin</option>
                            <option value="teacher" <?php echo (($_GET['role'] ?? '')==='teacher')?'selected':''; ?>>Teacher</option>
                            <option value="student" <?php echo (($_GET['role'] ?? '')==='student')?'selected':''; ?>>Student</option>
                        </select>
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
                        <select name="section" style="width:100%; padding:.5rem .6rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                            <option value="">All</option>
                            <?php foreach ($sectionOptions as $sec): ?>
                                <option value="<?php echo htmlspecialchars($sec['section_code']); ?>" <?php echo (($section ?? '') === $sec['section_code']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(($sec['code'] ?? '') . ' - ' . $sec['section_code']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" style="padding:.5rem .75rem; background:#3b82f6; color:#fff; border:none; border-radius:.5rem; font-weight:600;">Filter</button>
                    </div>
                </form>
                <div id="users-table-wrapper" style="overflow-x: auto; <?php echo empty($users) ? 'display:none;' : ''; ?>">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Verified</th>
                                <th>Joined</th>
                                <th>Section(s)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body"><?php echo $rowsHtml; ?></tbody>
                    </table>
                </div>
                <p id="empty-state" style="text-align: center; color: #9ca3af; padding: 2rem; <?php echo empty($users) ? '' : 'display:none;'; ?>">No users found</p>
                <p style="margin-top: .6rem; font-size: 0.8rem; color: #6b7280; text-align: right;">
                    Total Users: <strong id="users-count"><?php echo $totalUsers; ?></strong>
                </p>
            </div>
        </div>
    </div>

    <script>
    (function(){
        const form = document.getElementById('filter-form');
        const tbody = document.getElementById('users-table-body');
        const wrapper = document.getElementById('users-table-wrapper');
        const empty = document.getElementById('empty-state');
        const count = document.getElementById('users-count');
        if (!form || !tbody) return;

        let timer = null;
        const runFilter = () => {
            const fd = new FormData(form);
            const params = new URLSearchParams(fd);
            params.append('ajax', '1');
            fetch('users.php?' + params.toString(), { headers: { 'X-Requested-With': 'fetch' } })
                .then(r => r.json())
                .then(data => {
                    if (data.hasData) {
                        wrapper.style.display = '';
                        tbody.innerHTML = data.rows;
                        empty.style.display = 'none';
                    } else {
                        wrapper.style.display = 'none';
                        empty.innerHTML = 'No users found';
                        empty.style.display = 'block';
                    }
                    if (count) { count.textContent = data.count; }
                })
                .catch(() => {});
        };

        const onChange = () => {
            clearTimeout(timer);
            timer = setTimeout(runFilter, 250);
        };

        form.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', onChange);
            el.addEventListener('change', onChange);
        });

        form.addEventListener('submit', function(e){
            e.preventDefault();
            runFilter();
        });
    })();
    </script>

    <?php adminLayoutEnd(); ?>
