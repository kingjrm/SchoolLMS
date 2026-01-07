<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

$message = '';
$error = '';

// Create uploads directory if it doesn't exist
$uploads_dir = '../assets/uploads/announcements';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Handle post announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post') {
    $course_id = intval($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $external_link = trim($_POST['external_link'] ?? '');
    $pinned = isset($_POST['pinned']) ? 1 : 0;
    $image_path = null;

    if (empty($title) || empty($content) || $course_id === 0) {
        $error = 'Title and Content are required';
    } else {
        try {
            // Verify teacher owns this course
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$course_id, $teacher_id]);
            
            if (!$stmt->fetch()) {
                $error = 'Invalid course';
            } else {
                // Handle image upload
                if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['announcement_image'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($file['type'], $allowed_types)) {
                        $error = 'Invalid image type. Allowed: JPG, PNG, GIF, WebP';
                    } elseif ($file['size'] > $max_size) {
                        $error = 'Image too large. Maximum 5MB allowed';
                    } else {
                        // Generate unique filename
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'announcement_' . time() . '_' . uniqid() . '.' . $ext;
                        $filepath = $uploads_dir . '/' . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $image_path = 'assets/uploads/announcements/' . $filename;
                        } else {
                            $error = 'Failed to upload image';
                        }
                    }
                }
                
                // Validate external link if provided
                if (!empty($external_link)) {
                    if (!filter_var($external_link, FILTER_VALIDATE_URL)) {
                        $error = 'Invalid URL format';
                    }
                }
                
                // If no errors, insert announcement
                if (empty($error)) {
                    $stmt = $pdo->prepare("INSERT INTO announcements (course_id, posted_by, title, content, image_path, external_link, pinned) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$course_id, $teacher_id, $title, $content, $image_path, !empty($external_link) ? $external_link : null, $pinned]);
                    $message = 'Announcement posted successfully';
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = intval($_POST['delete_id'] ?? 0);
    try {
        // Get image path if exists
        $stmt = $pdo->prepare("SELECT a.image_path FROM announcements a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?");
        $stmt->execute([$delete_id, $teacher_id]);
        $ann = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete image file if exists
        if ($ann && $ann['image_path'] && file_exists('../' . $ann['image_path'])) {
            unlink('../' . $ann['image_path']);
        }
        
        // Delete announcement
        $stmt = $pdo->prepare("DELETE a FROM announcements a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?");
        $stmt->execute([$delete_id, $teacher_id]);
        $message = 'Announcement deleted';
    } catch (Exception $e) {
        $error = 'Error deleting announcement';
    }
}

teacherLayoutStart('announcements', 'Announcements');
?>


<div class="content-card">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card-header">
        <h2>Post Announcement</h2>
    </div>

    <form method="POST" class="form-container" style="margin-bottom:2rem;" enctype="multipart/form-data">
        <input type="hidden" name="action" value="post">

        <div class="form-group">
            <label>Course</label>
            <select name="course_id" required>
                <option value="">Select a course...</option>
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY title");
                    $stmt->execute([$teacher_id]);
                    while ($course = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                    <?php 
                    endwhile;
                } catch (Exception $e) {
                    echo '<option disabled>Error loading courses</option>';
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required>
        </div>

        <div class="form-group">
            <label>Content</label>
            <textarea name="content" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label>Attach Image (Optional)</label>
            <input type="file" name="announcement_image" accept="image/jpeg,image/png,image/gif,image/webp">
            <small style="color:#6b7280;font-size:0.75rem;display:block;margin-top:0.3rem;">Max 5MB. Formats: JPG, PNG, GIF, WebP</small>
        </div>

        <div class="form-group">
            <label>External Link (Optional)</label>
            <input type="url" name="external_link" placeholder="https://example.com">
            <small style="color:#6b7280;font-size:0.75rem;display:block;margin-top:0.3rem;">Include full URL starting with https://</small>
        </div>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:0.5rem">
                <input type="checkbox" name="pinned">
                <span>Pin this announcement</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Post Announcement</button>
    </form>

    <div class="card-header">
        <h2>Your Announcements</h2>
    </div>

    <div style="display:flex;flex-direction:column;gap:0.8rem;">
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, c.title as course_title 
                FROM announcements a 
                JOIN courses c ON a.course_id = c.id 
                WHERE a.posted_by = ? 
                ORDER BY a.pinned DESC, a.posted_at DESC
            ");
            $stmt->execute([$teacher_id]);
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($announcements)):
                foreach ($announcements as $ann):
            ?>
                <div style="background-color:#f9fafb;padding:1rem;border-radius:0.4rem;border-left:4px solid <?php echo $ann['pinned'] ? '#f97316' : '#d1d5db'; ?>;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:0.5rem;">
                        <div style="flex:1">
                            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;font-size:0.8rem">
                                <h4 style="margin:0;font-weight:600;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                <?php if ($ann['pinned']): ?>
                                    <span style="background-color:#f97316;color:white;padding:0.1rem 0.4rem;border-radius:0.2rem;font-size:0.65rem;font-weight:600;">Pinned</span>
                                <?php endif; ?>
                            </div>
                            <p style="color:#6b7280;font-size:0.75rem;margin:0;">
                                <?php echo htmlspecialchars($ann['course_title']); ?>
                            </p>
                            
                            <!-- Image Display -->
                            <?php if ($ann['image_path'] && file_exists('../' . $ann['image_path'])): ?>
                                <div style="margin:0.75rem 0;">
                                    <img src="<?php echo htmlspecialchars($ann['image_path']); ?>" alt="Announcement image" style="max-width:100%;max-height:300px;border-radius:0.4rem;">
                                </div>
                            <?php endif; ?>
                            
                            <p style="margin:0.5rem 0 0 0;color:#374151;line-height:1.4"><?php echo nl2br(htmlspecialchars(substr($ann['content'], 0, 500))); ?><?php echo strlen($ann['content']) > 500 ? '...' : ''; ?></p>
                            
                            <!-- External Link Display -->
                            <?php if ($ann['external_link']): ?>
                                <div style="margin-top:0.75rem;">
                                    <a href="<?php echo htmlspecialchars($ann['external_link']); ?>" target="_blank" rel="noopener noreferrer" style="color:#f97316;text-decoration:none;font-size:0.85rem;font-weight:500;">
                                        🔗 Visit Link
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <p style="color:#9ca3af;font-size:0.7rem;margin:0.5rem 0 0 0;">Posted: <?php echo date('M d, Y H:i', strtotime($ann['posted_at'])); ?></p>
                        </div>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="delete_id" value="<?php echo $ann['id']; ?>">
                            <button type="submit" class="btn-small btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php 
                endforeach;
            else: 
            ?>
                <p style="text-align:center;color:#9ca3af;padding:2rem;font-size:0.85rem">No announcements posted</p>
            <?php 
            endif;
        } catch (Exception $e) {
            echo '<p style="color:red;">Error loading announcements</p>';
        }
        ?>
    </div>
</div>

<?php teacherLayoutEnd(); ?>
