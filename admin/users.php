<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - School LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    require_once '../includes/config.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';
    require_once '../includes/helpers.php';

    Auth::requireRole('admin');
    $user = Auth::getCurrentUser();
    $db = new Database();

    $message = '';
    $action = $_GET['action'] ?? 'list';

    // Handle delete
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        
        // Don't allow deleting admin accounts
        $db->prepare("SELECT role FROM users WHERE id = ?")->bind('i', $delete_id)->execute();
        $user_role = $db->fetch();
        
        if ($user_role && $user_role['role'] !== 'admin') {
            $db->prepare("DELETE FROM users WHERE id = ?")->bind('i', $delete_id)->execute();
            $message = 'User deleted successfully';
        } else {
            $message = 'Cannot delete admin accounts';
        }
    }

    // Handle add/edit
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $role = $_POST['role'] ?? 'student';
        $status = $_POST['status'] ?? 'active';
        $user_id = $_POST['user_id'] ?? null;

        if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
            $message = 'All fields are required';
        } else {
            if ($user_id) {
                // Update user
                $query = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, status = ?";
                $params = 'ssssss';
                $values = [$username, $email, $first_name, $last_name, $role, $status];

                if (!empty($password)) {
                    $query .= ", password = ?";
                    $params .= 's';
                    $values[] = password_hash($password, PASSWORD_BCRYPT);
                }

                $query .= " WHERE id = ?";
                $params .= 'i';
                $values[] = $user_id;

                $stmt = $db->prepare($query);
                foreach ($values as $i => $value) {
                    $stmt->bind(chr(97 + $i), $value);
                }
                $stmt->execute();
                $message = 'User updated successfully';
            } else {
                // Add new user
                if (empty($password)) {
                    $message = 'Password is required for new users';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $query = "INSERT INTO users (username, email, password, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $db->prepare($query)
                        ->bind('sssssss', $username, $email, $hashed_password, $first_name, $last_name, $role, $status)
                        ->execute();
                    $message = 'User created successfully';
                }
            }
        }

        $action = 'list';
    }

    // Get user for edit
    $edit_user = null;
    if ($action == 'edit' && isset($_GET['id'])) {
        $edit_id = (int)$_GET['id'];
        $db->prepare("SELECT * FROM users WHERE id = ?")->bind('i', $edit_id)->execute();
        $edit_user = $db->fetch();
    }
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link active">Users</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="terms.php" class="nav-link">Academic Terms</a></li>
                <li class="nav-item"><a href="enrollments.php" class="nav-link">Enrollments</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link">Reports</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1><?php echo $action === 'edit' ? 'Edit User' : 'Users Management'; ?></h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleDropdown()">Menu</button>
                        <div class="dropdown-menu" id="dropdown">
                            <a href="../logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <?php echo showAlert(strpos($message, 'Error') === false ? 'success' : 'error', $message); ?>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Users</h3>
                    <a href="users.php?action=add" class="btn btn-primary btn-sm">Add User</a>
                </div>
                <div class="card-body">
                    <?php
                    $search = sanitize($_GET['search'] ?? '');
                    $role_filter = $_GET['role'] ?? '';

                    $query = "SELECT * FROM users WHERE 1=1";
                    
                    if (!empty($search)) {
                        $query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                    }
                    
                    if (!empty($role_filter)) {
                        $query .= " AND role = ?";
                    }

                    $query .= " ORDER BY created_at DESC";

                    $db->prepare($query);
                    
                    if (!empty($search)) {
                        $search_term = '%' . $search . '%';
                        $db->bind('ssss', $search_term, $search_term, $search_term, $search_term);
                    }
                    
                    if (!empty($role_filter)) {
                        $db->bind('s', $role_filter);
                    }

                    $db->execute();
                    $users = $db->fetchAll();
                    ?>

                    <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <input type="text" placeholder="Search users..." style="flex: 1; min-width: 200px;" 
                               onchange="window.location.href='users.php?search=' + encodeURIComponent(this.value)">
                        <select onchange="window.location.href='users.php?role=' + encodeURIComponent(this.value)">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo getRoleBadge($u['role']); ?></td>
                                <td><?php echo getStatusBadge($u['status']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($users)): ?>
                        <p style="text-align: center; color: #9ca3af;">No users found</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'edit' ? 'Edit User' : 'Add New User'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($edit_user['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($edit_user['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password <?php echo $edit_user ? '(Leave empty to keep current)' : '(Required)'; ?></label>
                                <input type="password" id="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select id="role" name="role" required>
                                    <option value="student" <?php echo (($edit_user['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                                    <option value="teacher" <?php echo (($edit_user['role'] ?? '') === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="admin" <?php echo (($edit_user['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo (($edit_user['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($edit_user['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="submit" class="btn btn-primary">Save</button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById('dropdown').classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdown');
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
