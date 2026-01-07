<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/student_layout.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();

$profilePic = '';
$uploadMsg = '';

// Get current profile picture
try {
    $stmt = $pdo->prepare("SELECT profile_picture FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($profile && $profile['profile_picture']) {
        $profilePic = $profile['profile_picture'];
    }
} catch (Exception $e) {}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($file['type'], $allowed) && $file['size'] <= 5242880) { // 5MB max
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user['id'] . '_' . time() . '.' . $ext;
        $uploadPath = __DIR__ . '/../assets/uploads/' . $filename;
        
        if (!is_dir(__DIR__ . '/../assets/uploads/')) {
            mkdir(__DIR__ . '/../assets/uploads/', 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, profile_picture) VALUES (?, ?) ON DUPLICATE KEY UPDATE profile_picture = ?");
                $stmt->execute([$user['id'], $filename, $filename]);
                $profilePic = $filename;
                $uploadMsg = '<div style="background:#ecfdf5;border:1px solid #86efac;color:#166534;padding:.75rem;border-radius:.6rem;margin-bottom:1rem">Profile picture updated successfully!</div>';
            } catch (Exception $e) {
                $uploadMsg = '<div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.75rem;border-radius:.6rem;margin-bottom:1rem">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            $uploadMsg = '<div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.75rem;border-radius:.6rem;margin-bottom:1rem">Upload failed. Please try again.</div>';
        }
    } else {
        $uploadMsg = '<div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.75rem;border-radius:.6rem;margin-bottom:1rem">Invalid file. Max 5MB. Allowed: JPG, PNG, GIF, WebP.</div>';
    }
    header('Location: settings.php');
    exit;
}

studentLayoutStart('settings', 'Settings');
?>
    <div class="card" style="max-width:800px">
        <?php echo $uploadMsg; ?>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.75rem">Account Settings</h3>
        <p style="color:#475569;font-size:.9rem;margin-bottom:1.5rem">Manage your profile and preferences.</p>
        
        <div style="display:grid;gap:1.5rem">
            <!-- Profile Picture -->
            <div style="border:1px solid var(--border-color);border-radius:.75rem;padding:1.25rem;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.06)">
                <div style="font-weight:600;margin-bottom:.75rem">Profile Picture</div>
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem">
                    <div style="width:80px;height:80px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:700;overflow:hidden">
                        <?php if ($profilePic): ?>
                            <img src="<?php echo htmlspecialchars('../assets/uploads/' . $profilePic); ?>" style="width:100%;height:100%;object-fit:cover">
                        <?php else: ?>
                            <?php echo htmlspecialchars(strtoupper($user['first_name'][0] ?? 'S')); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <form method="post" enctype="multipart/form-data" style="display:flex;gap:.5rem;align-items:center">
                            <input type="file" name="profile_picture" accept="image/*" required style="flex:1">
                            <button type="submit" style="padding:.65rem 1rem;background:#2563eb;color:#fff;border:none;border-radius:.6rem;cursor:pointer;font-weight:600">Upload</button>
                        </form>
                        <div style="color:#64748b;font-size:.8rem;margin-top:.5rem">Max 5MB. JPG, PNG, GIF, WebP.</div>
                    </div>
                </div>
            </div>

            <!-- Name -->
            <div style="display:flex;align-items:center;justify-content:space-between;border:1px solid var(--border-color);border-radius:.75rem;padding:1rem;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.06)">
                <div>
                    <div style="font-weight:600">Name</div>
                    <div style="color:#64748b;font-size:.9rem"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></div>
                </div>
                <a href="#" style="color:#2563eb;text-decoration:none;font-weight:600">Edit</a>
            </div>

            <!-- Email -->
            <div style="display:flex;align-items:center;justify-content:space-between;border:1px solid var(--border-color);border-radius:.75rem;padding:1rem;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.06)">
                <div>
                    <div style="font-weight:600">Email</div>
                    <div style="color:#64748b;font-size:.9rem"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                </div>
                <a href="#" style="color:#2563eb;text-decoration:none;font-weight:600">Change</a>
            </div>
        </div>
    </div>
<?php studentLayoutEnd(); ?>
