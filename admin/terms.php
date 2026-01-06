<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Terms - School LMS</title>
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

    // Handle add/edit
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $name = sanitize($_POST['name'] ?? '');
        $start_date = sanitize($_POST['start_date'] ?? '');
        $end_date = sanitize($_POST['end_date'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $term_id = $_POST['term_id'] ?? null;

        if (empty($name) || empty($start_date) || empty($end_date)) {
            $message = 'All fields are required';
        } else {
            // If setting this term as active, deactivate others
            if ($is_active) {
                $db->prepare("UPDATE academic_terms SET is_active = 0")->execute();
            }

            if ($term_id) {
                // Update term
                $query = "UPDATE academic_terms SET name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?";
                $db->prepare($query)
                    ->bind('sssii', $name, $start_date, $end_date, $is_active, $term_id)
                    ->execute();
                $message = 'Term updated successfully';
            } else {
                // Add new term
                $query = "INSERT INTO academic_terms (name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)";
                $db->prepare($query)
                    ->bind('sssi', $name, $start_date, $end_date, $is_active)
                    ->execute();
                $message = 'Term created successfully';
            }
        }

        $action = 'list';
    }

    // Get term for edit
    $edit_term = null;
    if ($action == 'edit' && isset($_GET['id'])) {
        $edit_id = (int)$_GET['id'];
        $db->prepare("SELECT * FROM academic_terms WHERE id = ?")->bind('i', $edit_id)->execute();
        $edit_term = $db->fetch();
    }
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link">Users</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="terms.php" class="nav-link active">Academic Terms</a></li>
                <li class="nav-item"><a href="enrollments.php" class="nav-link">Enrollments</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link">Reports</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1><?php echo $action === 'edit' ? 'Edit Term' : 'Academic Terms'; ?></h1>
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
                    <h3 class="card-title">All Terms</h3>
                    <a href="terms.php?action=add" class="btn btn-primary btn-sm">Add Term</a>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("SELECT * FROM academic_terms ORDER BY start_date DESC")->execute();
                    $terms = $db->fetchAll();
                    ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($terms as $term): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($term['name']); ?></td>
                                <td><?php echo formatDate($term['start_date'], 'Y-m-d'); ?></td>
                                <td><?php echo formatDate($term['end_date'], 'Y-m-d'); ?></td>
                                <td><?php echo $term['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-info">Inactive</span>'; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="terms.php?action=edit&id=<?php echo $term['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($terms)): ?>
                        <p style="text-align: center; color: #9ca3af;">No terms found</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'edit' ? 'Edit Term' : 'Add New Term'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_term): ?>
                            <input type="hidden" name="term_id" value="<?php echo $edit_term['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name">Term Name</label>
                            <input type="text" id="name" name="name" placeholder="e.g., Spring 2026"
                                   value="<?php echo htmlspecialchars($edit_term['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" 
                                       value="<?php echo htmlspecialchars($edit_term['start_date'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" 
                                       value="<?php echo htmlspecialchars($edit_term['end_date'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" <?php echo (($edit_term['is_active'] ?? 0) ? 'checked' : ''); ?>>
                                Set as Active Term
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="submit" class="btn btn-primary">Save</button>
                            <a href="terms.php" class="btn btn-secondary">Cancel</a>
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
