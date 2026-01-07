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

// Get filter parameters
$filterPriority = $_GET['priority'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

// Build query
$sql = "SELECT * FROM student_tasks WHERE student_id = :sid";
$params = ['sid' => $user['id']];

if ($filterPriority !== 'all') {
    $sql .= " AND priority = :priority";
    $params['priority'] = $filterPriority;
}

if ($filterStatus === 'completed') {
    $sql .= " AND is_completed = 1";
} elseif ($filterStatus === 'pending') {
    $sql .= " AND is_completed = 0";
}

if ($searchQuery !== '') {
    $sql .= " AND title LIKE :search";
    $params['search'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY is_completed ASC, COALESCE(due_date, '9999-12-31') ASC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

studentLayoutStart('tasks', 'Tasks', false);
?>

<style>
    .fab-join { display: none !important; }
    .task-filters {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .filter-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 0.5rem;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .filter-btn:hover {
        background: #f9fafb;
    }
    .filter-btn.active {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
    }
    .search-box {
        flex: 1;
        min-width: 250px;
    }
    .search-box input {
        width: 100%;
        padding: 0.6rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.9rem;
    }
</style>

<div class="card" style="padding:1.5rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <div>
            <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:.2rem">My Tasks</h3>
            <p style="color:#64748b;font-size:.9rem">Manage your personal tasks and stay organized</p>
        </div>
        <div style="color:#64748b;font-size:.9rem">Total: <?php echo count($tasks); ?></div>
    </div>

    <!-- Filters and Search -->
    <form method="GET" class="task-filters">
        <div class="search-box">
            <input type="text" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        
        <select name="priority" class="filter-btn" onchange="this.form.submit()">
            <option value="all" <?php echo $filterPriority === 'all' ? 'selected' : ''; ?>>All Priorities</option>
            <option value="high" <?php echo $filterPriority === 'high' ? 'selected' : ''; ?>>High</option>
            <option value="medium" <?php echo $filterPriority === 'medium' ? 'selected' : ''; ?>>Medium</option>
            <option value="low" <?php echo $filterPriority === 'low' ? 'selected' : ''; ?>>Low</option>
        </select>
        
        <select name="status" class="filter-btn" onchange="this.form.submit()">
            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
        </select>
        
        <button type="submit" class="filter-btn active">Search</button>
        
        <?php if ($filterPriority !== 'all' || $filterStatus !== 'all' || $searchQuery !== ''): ?>
            <a href="tasks.php" class="filter-btn" style="text-decoration:none;color:#64748b">Clear Filters</a>
        <?php endif; ?>
    </form>

    <!-- Task List -->
    <?php if (empty($tasks)): ?>
        <div style="text-align:center;padding:3rem;border:2px dashed #e5e7eb;background:#fafafa;border-radius:.8rem">
            <svg style="width:48px;height:48px;margin:0 auto 0.75rem;stroke:#94a3b8;fill:none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
            <div style="font-weight:700;margin-bottom:.5rem;font-size:1.1rem">No tasks found</div>
            <div style="color:#64748b;font-size:.95rem">Click the + button to create your first task</div>
        </div>
    <?php else: ?>
        <div style="display:grid;gap:.75rem">
            <?php foreach ($tasks as $t): ?>
                <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:0;padding:1rem;border-radius:.7rem">
                    <div style="display:flex;align-items:center;gap:1rem;flex:1">
                        <form method="post" style="margin:0">
                            <input type="hidden" name="toggle_id" value="<?php echo (int)$t['id']; ?>">
                            <button title="Toggle complete" style="background:#fff;border:2px solid #e5e7eb;width:32px;height:32px;border-radius:.6rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;<?php echo $t['is_completed']? 'background:#10b981;border-color:#10b981;color:#fff' : '' ?>">
                                <?php if ($t['is_completed']): ?>✓<?php endif; ?>
                            </button>
                        </form>
                        <div style="flex:1">
                            <div style="font-weight:600;font-size:0.85rem;<?php echo $t['is_completed']? 'text-decoration: line-through; color:#9ca3af' : 'color:#1f2937' ?>"><?php echo htmlspecialchars($t['title']); ?></div>
                            <div style="display:flex;gap:.5rem;align-items:center;margin-top:.35rem">
                                <?php if (!empty($t['due_date'])): ?>
                                    <span class="badge badge-medium" style="font-size:0.7rem;display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .45rem">
                                        <svg style="width:11px;height:11px;stroke:currentColor;fill:none" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <?php echo htmlspecialchars(date('M j, Y', strtotime($t['due_date']))); ?>
                                    </span>
                                <?php endif; ?>
                                <?php 
                                    $pri = strtolower($t['priority']);
                                    $cls = $pri === 'high' ? 'badge-high' : ($pri === 'low' ? 'badge-low' : 'badge-medium');
                                ?>
                                <span class="badge <?php echo $cls; ?>" style="font-size:0.7rem;padding:.15rem .45rem"><?php echo htmlspecialchars(ucfirst($t['priority'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <form method="post" onsubmit="return confirm('Delete this task?')" style="margin:0">
                        <input type="hidden" name="delete_id" value="<?php echo (int)$t['id']; ?>">
                        <button style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:.5rem .75rem;border-radius:.5rem;cursor:pointer;font-size:0.85rem;font-weight:500;transition:all 0.2s">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php studentLayoutEnd(); ?>

<!-- Custom FAB for Tasks (placed after layout to override global button) -->
<button type="button" class="fab-tasks" id="openTaskModal" aria-label="Add task" style="position:fixed;right:24px;bottom:24px;width:56px;height:56px;border-radius:50%;border:none;background:#3b82f6;color:#fff;font-size:28px;font-weight:700;box-shadow:0 10px 25px rgba(59,130,246,0.35);cursor:pointer;z-index:99999;display:flex;align-items:center;justify-content:center">+</button>

<!-- Add Task Modal -->
<div id="taskModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:99998">
    <div style="width:100%;max-width:500px;background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(0,0,0,0.2)">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:#1f2937">Add New Task</h3>
            <button type="button" onclick="document.getElementById('taskModal').style.display='none'" style="background:transparent;border:none;font-size:1.5rem;cursor:pointer;color:#6b7280">×</button>
        </div>
        <form method="POST">
            <div style="padding:1.5rem">
                <div style="margin-bottom:1.25rem">
                    <label style="display:block;font-size:0.9rem;color:#374151;margin-bottom:0.5rem;font-weight:600">Task Title *</label>
                    <input type="text" name="title" placeholder="e.g., Complete assignment" required style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:0.6rem;font-size:1rem">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
                    <div>
                        <label style="display:block;font-size:0.9rem;color:#374151;margin-bottom:0.5rem;font-weight:600">Due Date</label>
                        <input type="date" name="due_date" style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:0.6rem;font-size:0.95rem">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.9rem;color:#374151;margin-bottom:0.5rem;font-weight:600">Priority</label>
                        <select name="priority" style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:0.6rem;font-size:0.95rem">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;padding:0 1.5rem 1.5rem;border-top:1px solid #f3f4f6;padding-top:1rem">
                <button type="button" onclick="document.getElementById('taskModal').style.display='none'" style="background:#f3f4f6;color:#374151;padding:0.65rem 1.25rem;border:none;border-radius:0.6rem;cursor:pointer;font-weight:500">Cancel</button>
                <button type="submit" style="background:#3b82f6;color:#fff;padding:0.65rem 1.5rem;border:none;border-radius:0.6rem;cursor:pointer;font-weight:600">Add Task</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('openTaskModal').addEventListener('click', function() {
    document.getElementById('taskModal').style.display = 'flex';
});
document.getElementById('taskModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
