<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Tasks.php';
require_once __DIR__ . '/../includes/student_layout.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();

Tasks::ensureTable($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title'])) {
        $title = trim($_POST['title'] ?? '');
        $due = trim($_POST['due_date'] ?? '');
        $priority = strtolower(trim($_POST['priority'] ?? 'medium'));
        if (!in_array($priority, ['low','medium','high'], true)) { $priority = 'medium'; }
        $dueDate = $due !== '' ? ($due . ' 00:00:00') : null;
        if ($title !== '') {
            Tasks::add($pdo, $user['id'], $title, null, $dueDate, $priority);
        }
    } elseif (isset($_POST['toggle_id'])) {
        $id = (int)($_POST['toggle_id'] ?? 0);
        if ($id) { Tasks::toggleComplete($pdo, $user['id'], $id); }
    } elseif (isset($_POST['delete_id'])) {
        $id = (int)($_POST['delete_id'] ?? 0);
        if ($id) { Tasks::delete($pdo, $user['id'], $id); }
    }
    header('Location: tasks.php');
    exit;
}

$tasks = Tasks::list($pdo, $user['id']);

studentLayoutStart('tasks', 'Tasks');
?>

<div class="card" style="padding:1.25rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <div>
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:.2rem">My Tasks</h3>
            <p style="color:#64748b;font-size:.9rem">Stay organized with a simple, clean task list.</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1.1fr .9fr;gap:1rem;align-items:start">
        <div class="card" style="margin:0;box-shadow:0 1px 3px rgba(0,0,0,.06)">
            <div style="font-weight:700;margin-bottom:.5rem">Add Task</div>
            <form method="post" style="display:grid;grid-template-columns:1.5fr 1fr 1fr auto;gap:.6rem;align-items:end">
                <div>
                    <label style="display:block;font-size:.8rem;color:#64748b;margin-bottom:.3rem">Task</label>
                    <input type="text" name="title" placeholder="e.g. Read Chapter 3" required style="width:100%;padding:.7rem .8rem;border:1px solid #e5e7eb;border-radius:.6rem">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;color:#64748b;margin-bottom:.3rem">Due Date</label>
                    <input type="date" name="due_date" style="width:100%;padding:.7rem .8rem;border:1px solid #e5e7eb;border-radius:.6rem">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;color:#64748b;margin-bottom:.3rem">Priority</label>
                    <select name="priority" style="width:100%;padding:.7rem .8rem;border:1px solid #e5e7eb;border-radius:.6rem">
                        <option>Low</option>
                        <option selected>Medium</option>
                        <option>High</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="padding:.75rem 1rem;border:none;border-radius:.6rem;background:#2563eb;color:#fff;cursor:pointer">Add</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin:0;box-shadow:0 1px 3px rgba(0,0,0,.06)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.25rem">
                <div style="font-weight:700">Your Tasks</div>
                <div style="color:#64748b;font-size:.85rem">Total: <?php echo count($tasks); ?></div>
            </div>
            <?php if (empty($tasks)): ?>
                <div style="text-align:center;padding:1.75rem;border:1px dashed #e5e7eb;background:#fafafa;border-radius:.6rem">
                    <div style="font-weight:700;margin-bottom:.25rem">No tasks yet</div>
                    <div style="color:#64748b;font-size:.9rem">Add your first task to get started.</div>
                </div>
            <?php else: ?>
                <div style="display:grid;gap:.6rem">
                    <?php foreach ($tasks as $t): ?>
                        <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:0;border-radius:.8rem">
                            <div style="display:flex;align-items:center;gap:.75rem">
                                <form method="post" style="margin:0">
                                    <input type="hidden" name="toggle_id" value="<?php echo (int)$t['id']; ?>">
                                    <button title="Toggle" style="background:#fff;border:1px solid #e5e7eb;width:28px;height:28px;border-radius:.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;<?php echo $t['is_completed']? 'background:#ecfdf5;border-color:#34d399;color:#047857' : '' ?>">
                                        <?php if ($t['is_completed']): ?>
                                            ✓
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <div>
                                    <div style="font-weight:600;<?php echo $t['is_completed']? 'text-decoration: line-through; color:#64748b' : '' ?>"><?php echo htmlspecialchars($t['title']); ?></div>
                                    <div style="font-size:.8rem;color:#64748b;display:flex;gap:.5rem;align-items:center;margin-top:.15rem">
                                        <?php if (!empty($t['due_date'])): ?>
                                            <span class="badge badge-medium">Due <?php echo htmlspecialchars(date('M j, Y', strtotime($t['due_date']))); ?></span>
                                        <?php endif; ?>
                                        <?php 
                                            $pri = strtolower($t['priority']);
                                            $cls = $pri === 'high' ? 'badge-high' : ($pri === 'low' ? 'badge-low' : 'badge-medium');
                                        ?>
                                        <span class="badge <?php echo $cls; ?>">Priority: <?php echo htmlspecialchars(ucfirst($t['priority'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <form method="post" onsubmit="return confirm('Delete this task?')" style="margin:0">
                                <input type="hidden" name="delete_id" value="<?php echo (int)$t['id']; ?>">
                                <button style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:.45rem .65rem;border-radius:.55rem;cursor:pointer">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php studentLayoutEnd(); ?>
