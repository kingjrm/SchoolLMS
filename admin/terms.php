<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/admin_layout.php';
require_once '../includes/ActivityLogger.php';

Auth::requireRole('admin');
$db = new Database();
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_term'])) {
    $name = trim($_POST['name'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $term_id = isset($_POST['term_id']) ? (int)$_POST['term_id'] : null;

    if ($name === '' || $start_date === '' || $end_date === '') {
        $message = 'All fields are required';
        $action = $term_id ? 'edit' : 'add';
    } else {
        try {
            if ($is_active) { $pdo->exec("UPDATE academic_terms SET is_active = 0"); }
            if ($term_id) {
                $stmt = $pdo->prepare("UPDATE academic_terms SET name=?, start_date=?, end_date=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $start_date, $end_date, $is_active, $term_id]);
                $message = 'Term updated successfully';
                ActivityLogger::log($pdo, 'Updated term', ['term_id'=>$term_id, 'name'=>$name, 'is_active'=>$is_active ? 1 : 0]);
                $action = 'list';
            } else {
                $stmt = $pdo->prepare("INSERT INTO academic_terms (name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $start_date, $end_date, $is_active]);
                $message = 'Term created successfully';
                ActivityLogger::log($pdo, 'Created term', ['name'=>$name, 'is_active'=>$is_active ? 1 : 0]);
                $action = 'list';
            }
        } catch (Exception $e) {
            $message = 'Error saving term';
            $action = $term_id ? 'edit' : 'add';
        }
    }
}

// Prefetch edit term
$edit_term = null;
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM academic_terms WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $edit_term = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $edit_term = null; }
}

adminLayoutStart('terms', 'Terms');
?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <?php if ($message): ?>
            <div style="padding:.75rem; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; border-radius:.5rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
        <div class="card">
            <div style="padding:.9rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Academic Terms
                </h3>
                <a href="?action=add" style="padding:.45rem .7rem; background:#3b82f6; color:#fff; border-radius:.5rem; text-decoration:none; font-size:.8rem;">Add Term</a>
            </div>
            <div style="padding:1rem;">
                <?php $db->prepare("SELECT * FROM academic_terms ORDER BY start_date DESC")->execute(); $terms = $db->fetchAll(); ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($terms as $term): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($term['name']); ?></td>
                                <td><?php echo htmlspecialchars($term['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($term['end_date']); ?></td>
                                <td>
                                    <span class="badge <?php echo $term['is_active'] ? 'badge-medium' : 'badge-low'; ?>">
                                        <?php echo $term['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($terms)): ?>
                    <p style="text-align:center; color:#9ca3af; padding:1rem;">No terms found</p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div style="padding:.9rem; border-bottom:1px solid #e5e7eb;">
                <h3 style="margin:0; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:.5rem;">
                    <?php if ($action==='edit'): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Edit Term
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Term
                    <?php endif; ?>
                </h3>
            </div>
            <div style="padding:1rem;">
                <form method="POST">
                    <?php if ($edit_term): ?>
                        <input type="hidden" name="term_id" value="<?php echo (int)$edit_term['id']; ?>">
                    <?php endif; ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:.75rem;">
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Term Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_term['name'] ?? ''); ?>" required style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                        </div>
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">Start Date</label>
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($edit_term['start_date'] ?? ''); ?>" required style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                        </div>
                        <div>
                            <label style="display:block; font-size:.75rem; color:#6b7280; margin-bottom:.25rem;">End Date</label>
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($edit_term['end_date'] ?? ''); ?>" required style="width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem;">
                        </div>
                    </div>
                    <div style="margin-top:.75rem;">
                        <label style="display:flex; gap:.5rem; align-items:center; font-size:.85rem; color:#374151;">
                            <input type="checkbox" name="is_active" <?php echo (($edit_term['is_active'] ?? 0) ? 'checked' : ''); ?>> Set as Active Term
                        </label>
                    </div>
                    <div style="display:flex; gap:.5rem; margin-top:.9rem;">
                        <button type="submit" name="save_term" class="btn btn-primary" style="padding:.5rem .8rem;">Save</button>
                        <a href="terms.php" class="btn btn-secondary" style="padding:.5rem .8rem;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

<?php adminLayoutEnd(); ?>
