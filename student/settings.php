<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/student_layout.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();

$message = '';
$error = '';

// Get user profile data
$profile = ['profile_picture' => null, 'bio' => null, 'phone' => null, 'address' => null];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($profileData) {
        $profile = $profileData;
    }
} catch (Exception $e) {}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_picture') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
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
                    $profile['profile_picture'] = $filename;
                    $message = 'Profile picture updated successfully!';
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            } else {
                $error = 'Upload failed. Please try again.';
            }
        } else {
            $error = 'Invalid file. Max 5MB. Allowed: JPG, PNG, GIF, WebP.';
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    try {
        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $user['id']]);
        
        // Update user_profiles table
        $stmt = $pdo->prepare("
            INSERT INTO user_profiles (user_id, phone, bio, address) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE phone = ?, bio = ?, address = ?
        ");
        $stmt->execute([$user['id'], $phone, $bio, $address, $phone, $bio, $address]);
        
        $message = 'Profile updated successfully!';
        $user['first_name'] = $first_name;
        $user['last_name'] = $last_name;
        $profile['phone'] = $phone;
        $profile['bio'] = $bio;
        $profile['address'] = $address;
    } catch (Exception $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData && password_verify($current_password, $userData['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);
                $message = 'Password changed successfully!';
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (Exception $e) {
            $error = 'Error changing password: ' . $e->getMessage();
        }
    }
}

studentLayoutStart('settings', 'Settings', false);
?>

<style>
    .settings-container {
        max-width: 900px;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .settings-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    .settings-section h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .settings-section p {
        margin: 0 0 1.5rem 0;
        font-size: 0.85rem;
        color: #6b7280;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.65rem;
        border: 1.5px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.9rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .profile-picture-section {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }
    
    .profile-picture {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-weight: 700;
        font-size: 2rem;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .profile-picture img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-picture-upload {
        flex: 1;
    }
    
    .file-upload-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    
    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: #f9fafb;
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.85rem;
        color: #374151;
        font-weight: 500;
    }
    
    .file-upload-label:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    .file-upload-label svg {
        width: 1rem;
        height: 1rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
    }
    
    .profile-picture-upload input[type="file"] {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border-width: 0;
    }
    
    .file-name-display {
        margin-top: 0.5rem;
        font-size: 0.75rem;
        color: #6b7280;
        padding: 0.5rem;
        background: #f3f4f6;
        border-radius: 0.375rem;
        display: none;
    }
    
    .file-name-display.has-file {
        display: block;
    }
    
    .profile-picture-upload .file-info {
        font-size: 0.7rem;
        color: #9ca3af;
        margin-top: 0.5rem;
    }
    
    .btn {
        padding: 0.65rem 1.25rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: #2563eb;
        color: white;
    }
    
    .btn-primary:hover {
        background: #1d4ed8;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }
    
    .alert {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
    }
    
    .alert-success {
        background: #ecfdf5;
        border: 1px solid #86efac;
        color: #166534;
    }
    
    .alert-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }
    
    .readonly-field {
        background: #f9fafb;
        color: #6b7280;
        cursor: not-allowed;
    }
</style>

<div class="settings-container">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Profile Picture -->
    <div class="settings-section">
        <h3>Profile Picture</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_picture">
            <div class="profile-picture-section">
                <div class="profile-picture">
                    <?php if ($profile['profile_picture']): ?>
                        <img src="<?php echo htmlspecialchars('../assets/uploads/' . $profile['profile_picture']); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo htmlspecialchars(strtoupper(($user['first_name'][0] ?? 'S') . ($user['last_name'][0] ?? ''))); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-picture-upload">
                    <div class="file-upload-wrapper">
                        <label for="profile_picture_input" class="file-upload-label">
                            <svg viewBox="0 0 16 16">
                                <path d="M8.5 1.5A.5.5 0 0 0 8 2v5H3a.5.5 0 0 0-.35.15l-2 2a.5.5 0 0 0 .7.7L3 9.71V13a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9.5a.5.5 0 0 0-1 0V13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8.29l1.15 1.15a.5.5 0 0 0 .7-.7l-2-2A.5.5 0 0 0 3 6.5h5V2a.5.5 0 0 0-1 0v3H8a.5.5 0 0 0-.5-.5z"/>
                            </svg>
                            Choose File
                        </label>
                        <input type="file" id="profile_picture_input" name="profile_picture" accept="image/*" required>
                        <div class="file-name-display" id="file-name-display"></div>
                    </div>
                    <div class="file-info">Max 5MB. Allowed: JPG, PNG, GIF, WebP</div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 0.75rem;">Upload Picture</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Personal Information -->
    <div class="settings-section">
        <h3>Personal Information</h3>
        <p>Update your personal details and contact information.</p>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="readonly-field" readonly>
                <small style="color: #6b7280; font-size: 0.75rem;">Email cannot be changed</small>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="Enter your phone number">
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" placeholder="Enter your address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="settings-section">
        <h3>Change Password</h3>
        <p>Update your password to keep your account secure.</p>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6">
                <small style="color: #6b7280; font-size: 0.75rem;">Must be at least 6 characters</small>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>

    <!-- Account Information -->
    <div class="settings-section">
        <h3>Account Information</h3>
        <p>Your account details and role information.</p>
        <div class="form-group">
            <label>Username</label>
            <input type="text" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" class="readonly-field" readonly>
        </div>
        <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['role'] ?? '')); ?>" class="readonly-field" readonly>
        </div>
        <div class="form-group">
            <label>Account Status</label>
            <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['status'] ?? 'active')); ?>" class="readonly-field" readonly>
        </div>
    </div>
</div>

<script>
    document.getElementById('profile_picture_input')?.addEventListener('change', function(e) {
        const fileDisplay = document.getElementById('file-name-display');
        if (this.files && this.files[0]) {
            fileDisplay.textContent = 'Selected: ' + this.files[0].name;
            fileDisplay.classList.add('has-file');
        } else {
            fileDisplay.classList.remove('has-file');
        }
    });
</script>

<?php studentLayoutEnd(); ?>
