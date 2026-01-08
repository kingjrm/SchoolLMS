<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
// require_once '../includes/Database.php'; // Not needed; using PDO
require_once '../includes/helpers.php';
require_once '../includes/student_layout.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();
$db = null; // use $pdo from config.php
$student_id = $user['id'];

$message = '';

// Simple filters (search, course, status)
$search = trim($_GET['search'] ?? '');
$filter_course = $_GET['course'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_teacher = $_GET['teacher_id'] ?? '';

// Course options for filter dropdown
try {
    $courseFilterStmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.title, c.code
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        JOIN assignments a ON a.course_id = c.id
        WHERE e.student_id = ? AND e.status = 'enrolled'
        ORDER BY c.title
    ");
    $courseFilterStmt->execute([$student_id]);
    $filterCourses = $courseFilterStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $filterCourses = [];
}

// Teacher options for filter dropdown
try {
    $teacherFilterStmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.first_name, u.last_name
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        JOIN users u ON c.teacher_id = u.id
        JOIN assignments a ON a.course_id = c.id
        WHERE e.student_id = ? AND e.status = 'enrolled'
        ORDER BY u.first_name, u.last_name
    ");
    $teacherFilterStmt->execute([$student_id]);
    $filterTeachers = $teacherFilterStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $filterTeachers = [];
}

    // Handle mark as done
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_done'])) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT a.id FROM assignments a JOIN courses c ON a.course_id = c.id JOIN enrollments e ON c.id = e.course_id WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'");
        $stmt->execute([$assignment_id, $student_id]);
        
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = 'Invalid assignment';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
            $stmt->execute([$assignment_id, $student_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE assignment_submissions SET is_completed = 1 WHERE assignment_id = ? AND student_id = ?");
                $stmt->execute([$assignment_id, $student_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, is_completed) VALUES (?, ?, 1)");
                $stmt->execute([$assignment_id, $student_id]);
            }
            $message = 'Assignment marked as done';
        }
    }

    // Handle save student comment
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_comment'])) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $student_comment = trim(sanitize($_POST['student_comment'] ?? ''));
        
        if (empty($student_comment)) {
            $message = 'Comment cannot be empty';
        } else {
            $stmt = $pdo->prepare("SELECT a.id FROM assignments a JOIN courses c ON a.course_id = c.id JOIN enrollments e ON c.id = e.course_id WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'");
            $stmt->execute([$assignment_id, $student_id]);
            
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $message = 'Invalid assignment';
            } else {
                // Insert new comment into comments table
                $stmt = $pdo->prepare("INSERT INTO assignment_comments (assignment_id, user_id, user_type, comment) VALUES (?, ?, 'student', ?)");
                $stmt->execute([$assignment_id, $student_id, $student_comment]);
                $message = 'Comment sent';
            }
        }
    }

    // Handle submit assignment (text + required file or existing file)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_assignment'])) {
        try {
            $assignment_id = (int)($_POST['assignment_id'] ?? 0);
            $submission_text = sanitize($_POST['submission_text'] ?? '');

            // Verify student is enrolled in course
            $stmt = $pdo->prepare("
                SELECT a.id FROM assignments a 
                JOIN courses c ON a.course_id = c.id 
                JOIN enrollments e ON c.id = e.course_id 
                WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'
            ");
            $stmt->execute([$assignment_id, $student_id]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $message = 'Invalid assignment';
            } else {
                // Handle file upload
                $submittedFileRel = null;
                $fileError = '';
                if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
                    $submissionsDir = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . 'submissions' . DIRECTORY_SEPARATOR;
                    if (!is_dir($submissionsDir)) {
                        @mkdir($submissionsDir, 0755, true);
                    }

                    $allowedExtensions = ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','png','jpg','jpeg','txt'];
                    $maxFileSize = 20 * 1024 * 1024; // 20MB
                    $originalName = $_FILES['submission_file']['name'];
                    $size = (int)$_FILES['submission_file']['size'];
                    $tmp = $_FILES['submission_file']['tmp_name'];
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    if ($size > $maxFileSize) {
                        $fileError = 'File too large (max 20MB)';
                    } elseif (!in_array($ext, $allowedExtensions, true)) {
                        $fileError = 'Invalid file type';
                    } else {
                        $safeBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                        $unique = $safeBase . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                        $targetPath = $submissionsDir . $unique;
                        if (move_uploaded_file($tmp, $targetPath)) {
                            $submittedFileRel = 'submissions/' . $unique;
                        } else {
                            $fileError = 'Failed to save file';
                        }
                    }
                } elseif (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    // File was attempted but had an error
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];
                    $fileError = $uploadErrors[$_FILES['submission_file']['error']] ?? 'Unknown upload error';
                }

                // Check if already submitted and whether an existing file is present
                $stmt = $pdo->prepare("SELECT id, submission_file FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
                $stmt->execute([$assignment_id, $student_id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                $existingFile = $existing && !empty($existing['submission_file']) ? $existing['submission_file'] : null;

                if ($fileError) {
                    $message = 'Error: ' . $fileError;
                } elseif (!$submittedFileRel && !$existingFile) {
                    $message = 'Error: File attachment required';
                } else {
                    if ($existing) {
                        if ($submittedFileRel) {
                            // Replace with new file
                            $query = "UPDATE assignment_submissions SET submission_text = ?, submission_file = ?, submitted_at = NOW(), status = 'submitted' WHERE assignment_id = ? AND student_id = ?";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$submission_text, $submittedFileRel, $assignment_id, $student_id]);
                            if ($stmt->rowCount() === 0) {
                                throw new Exception('Failed to update assignment submission - no rows affected');
                            }
                        } else {
                            // Keep existing file
                            $query = "UPDATE assignment_submissions SET submission_text = ?, submitted_at = NOW(), status = 'submitted' WHERE assignment_id = ? AND student_id = ?";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$submission_text, $assignment_id, $student_id]);
                            if ($stmt->rowCount() === 0) {
                                throw new Exception('Failed to update assignment submission - no rows affected');
                            }
                        }
                        $message = 'Assignment updated';
                    } else {
                        if ($submittedFileRel) {
                            $query = "INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, submission_file, status) VALUES (?, ?, ?, ?, 'submitted')";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$assignment_id, $student_id, $submission_text, $submittedFileRel]);
                            if ($stmt->rowCount() === 0) {
                                throw new Exception('Failed to insert assignment submission - no rows affected');
                            }
                            $message = 'Assignment submitted successfully';
                        } else {
                            $message = 'Error: File attachment required';
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $message = 'Error: Database error occurred. Please try again.';
            error_log('Assignment submission PDO error: ' . $e->getMessage());
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            error_log('Assignment submission error: ' . $e->getMessage());
        }
        
        // Return JSON response for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => strpos($message, 'Error') === false,
                'message' => $message
            ]);
            exit;
        }
    }

    // Handle unsubmit assignment (delete submission and all comments)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unsubmit_assignment'])) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT a.id FROM assignments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'
        ");
        $stmt->execute([$assignment_id, $student_id]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = 'Invalid assignment';
        } else {
            // Delete all comments for this assignment and student
            $stmt = $pdo->prepare("DELETE FROM assignment_comments WHERE assignment_id = ? AND user_id = ? AND user_type = 'student'");
            $stmt->execute([$assignment_id, $student_id]);
            // Delete the submission record entirely
            $stmt = $pdo->prepare("DELETE FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
            $stmt->execute([$assignment_id, $student_id]);
            $message = 'Submission and comments deleted';
        }
    }

    // Handle remove submitted attachment only
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_submission_file'])) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT a.id FROM assignments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'
        ");
        $stmt->execute([$assignment_id, $student_id]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = 'Invalid assignment';
        } else {
            $stmt = $pdo->prepare("SELECT submission_file FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
            $stmt->execute([$assignment_id, $student_id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($sub['submission_file'])) {
                $fullPath = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $sub['submission_file'];
                if (is_file($fullPath)) { @unlink($fullPath); }
            }
            $stmt = $pdo->prepare("UPDATE assignment_submissions SET submission_file = NULL WHERE assignment_id = ? AND student_id = ?");
            $stmt->execute([$assignment_id, $student_id]);
            $message = 'Attachment removed';
        }
    }
?>
<?php studentLayoutStart('assignments', 'Assignments'); ?>

            <style>
                .hidden { display: none !important; }

                .filter-inline input,
                .filter-inline select {
                    background: #fff;
                }

                .filter-inline {
                    gap: 0.75rem;
                }

                .filter-inline .btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    height: 40px;
                    padding: 0.48rem 0.85rem;
                    border-radius: 0.4rem;
                    font-size: 0.82rem;
                    border: 1.5px solid #f97316;
                    background: #f97316;
                    color: #fff;
                    outline: none;
                    box-shadow: none;
                    font-family: inherit;
                }

                .filter-inline .btn:focus,
                .filter-inline .btn:active {
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.25);
                }

                .reset-link {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    height: 40px;
                    padding: 0 0.85rem;
                    border: 1.5px solid #e5e7eb;
                    border-radius: 0.4rem;
                    background: #fff;
                    color: #1f2937;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 0.82rem;
                    font-family: inherit;
                    transition: background 0.2s, border-color 0.2s, color 0.2s;
                }

                .reset-link:hover {
                    background: #f8fafc;
                    border-color: #d1d5db;
                }
                
                [id^="expand-"] {
                    animation: slideDown 0.3s ease-out;
                }
                
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        max-height: 0;
                    }
                    to {
                        opacity: 1;
                        max-height: 1000px;
                    }
                }
                
                .modal-overlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 1000;
                    justify-content: center;
                    align-items: center;
                    padding: 1rem;
                }
                
                .modal-overlay.active {
                    display: flex;
                }
                
                .modal-content {
                    background: white;
                    border-radius: 0.5rem;
                    padding: 1rem;
                    max-width: 80vw;
                    width: 100%;
                    max-height: 85vh;
                    height: auto;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                    overflow-y: auto;
                }
                
                .modal-header {
                    margin-bottom: 0.75rem;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                
                .modal-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6b7280;
                }
                
                .modal-close:hover {
                    color: #1f2937;
                }

                .toast {
                    position: fixed;
                    top: 4.5rem;
                    right: 1.25rem;
                    background: white;
                    border-left: 4px solid #3b82f6;
                    padding: 1rem 1.5rem;
                    border-radius: 0.375rem;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    z-index: 2000;
                    animation: slideInRight 0.3s ease-out;
                    max-width: 350px;
                }

                .toast.success {
                    border-left-color: #10b981;
                    background: #f0fdf4;
                }

                .toast.success .toast-icon {
                    color: #10b981;
                }

                .toast.error {
                    border-left-color: #ef4444;
                    background: #fef2f2;
                }

                .toast.error .toast-icon {
                    color: #ef4444;
                }

                .toast-message {
                    font-size: 0.875rem;
                    color: #1f2937;
                    margin: 0;
                }

                @keyframes slideInRight {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }

                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }

                .toast.removing {
                    animation: slideOutRight 0.3s ease-in forwards;
                }
            </style>
            
            <script>
                function updateFileDisplay(input) {
                    const assignmentId = input.id.split('-')[1];
                    const filePreview = document.getElementById('filePreview-' + assignmentId);
                    const fileName = document.getElementById('fileName-' + assignmentId);
                    const fileSize = document.getElementById('fileSize-' + assignmentId);
                    
                    if (input.files && input.files[0]) {
                        const file = input.files[0];
                        fileName.textContent = file.name;
                        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                        fileSize.textContent = sizeMB + ' MB';
                        filePreview.style.display = 'flex';
                        // Enable Turn In if not disabled by status
                        const form = input.closest('form');
                        const btn = form ? form.querySelector('button[type="submit"][data-turnin-id]') : null;
                        if (btn && !btn.hasAttribute('disabled')) {
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            btn.style.cursor = 'pointer';
                        }
                    } else {
                        filePreview.style.display = 'none';
                        const form = input.closest('form');
                        const btn = form ? form.querySelector('button[type="submit"][data-turnin-id]') : null;
                        const hasExisting = form ? (form.querySelector('input[name="has_existing_file"]')?.value === '1') : false;
                        if (btn && !btn.hasAttribute('disabled') && !hasExisting) {
                            btn.disabled = true;
                            btn.style.opacity = '0.65';
                            btn.style.cursor = 'not-allowed';
                        }
                    }
                }
                
                function clearFileUpload(assignmentId) {
                    const input = document.getElementById('submissionFileInput-' + assignmentId);
                    input.value = '';
                    document.getElementById('filePreview-' + assignmentId).style.display = 'none';
                    
                    // Trigger file display update to handle button states
                    updateFileDisplay(input);
                }
                
                function showToast(message, type = 'success') {
                    const toast = document.createElement('div');
                    toast.className = 'toast ' + type;
                    toast.innerHTML = `
                        <p class="toast-message">${message}</p>
                    `;
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.classList.add('removing');
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                }
                
                function submitAssignmentForm(event, assignmentId) {
                    event.preventDefault();
                    
                    const form = event.target;
                    const formData = new FormData(form);
                    const fileInput = form.querySelector('input[name="submission_file"]');
                    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
                    const hasExisting = (form.querySelector('input[name="has_existing_file"]')?.value === '1');

                    // Require: new file or existing server file
                    if (!(hasFile || hasExisting)) {
                        showToast('Attach a file before turning in.', 'error');
                        return;
                    }
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        }
                        return response.text();
                    })
                    .then(data => {
                        // Handle JSON response
                        if (typeof data === 'object' && data !== null) {
                            if (data.success) {
                                showToast(data.message || 'Assignment submitted successfully!', 'success');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                showToast(data.message || 'Error submitting assignment', 'error');
                            }
                            return;
                        }
                        
                        // Handle HTML response (fallback)
                        if (typeof data === 'string' && data.includes('Error:')) {
                            const errorMatch = data.match(/Error:[^<]*/);
                            if (errorMatch) {
                                showToast(errorMatch[0], 'error');
                                return;
                            }
                        }
                        
                        // Success - reload page to ensure data is saved and displayed correctly
                        showToast('Assignment submitted successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    })
                    .catch(error => {
                        showToast('Error submitting assignment. Please try again.', 'error');
                        console.error('Error:', error);
                    });
                }
                
                function submitCommentForm(event, assignmentId) {
                    event.preventDefault();
                    
                    const form = event.target;
                    const textarea = document.getElementById('commentTextarea-' + assignmentId);
                    const commentText = textarea.value.trim();
                    
                    if (!commentText) {
                        showToast('Message cannot be empty', 'error');
                        return;
                    }
                    
                    const formData = new FormData(form);
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        showToast('Message sent!', 'success');
                        
                        // Add message to thread instantly
                        const thread = document.getElementById('commentsThread-' + assignmentId);
                        const now = new Date();
                        const timeStr = now.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
                        
                        // Remove "No messages yet" text if present
                        const noMsgText = thread.querySelector('p');
                        if (noMsgText && noMsgText.textContent.includes('No messages yet')) {
                            noMsgText.remove();
                        }
                        
                        const messageDiv = document.createElement('div');
                        messageDiv.style.cssText = 'margin-bottom: 0.5rem; display: flex; flex-direction: column; align-items: flex-end;';
                        messageDiv.innerHTML = `
                            <div style="max-width: 75%; background: #dbeafe; padding: 0.5rem 0.75rem; border-radius: 0.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div style="font-size: 0.65rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem;">
                                    You <span style="font-weight: 400; color: #6b7280;">(Student)</span>
                                </div>
                                <div style="font-size: 0.7rem; color: #1f2937; word-wrap: break-word;">${commentText.replace(/\n/g, '<br>')}</div>
                                <div style="font-size: 0.6rem; color: #9ca3af; margin-top: 0.25rem;">${timeStr}</div>
                            </div>
                        `;
                        thread.appendChild(messageDiv);
                        thread.scrollTop = thread.scrollHeight;
                        
                        // Clear textarea
                        textarea.value = '';
                    })
                    .catch(error => {
                        showToast('Error sending message. Please try again.', 'error');
                        console.error('Error:', error);
                    });
                }

                function submitUnsubmitForm(event, assignmentId) {
                    event.preventDefault();

                    const form = event.target;
                    const formData = new FormData(form);

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(() => {
                        showToast('Submission reverted', 'success');

                        const statusBadge = document.querySelector(`[data-assignment-id="${assignmentId}"] span:nth-child(2)`);
                        if (statusBadge) {
                            const wrapper = statusBadge.closest('[data-due-date]');
                            const dueStr = wrapper?.dataset?.dueDate;
                            const isLate = dueStr ? new Date(dueStr) < new Date() : false;

                            if (isLate) {
                                statusBadge.innerHTML = `
                                    <svg style="width: 0.5rem; height: 0.5rem; fill: currentColor;" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 2.5a4 4 0 110-8 4 4 0 010 8zM.458 8C1.732 4.36 4.97 1.5 8 1.5c3.03 0 6.268 2.86 7.542 6.5-1.274 3.64-4.512 6.5-7.542 6.5-3.03 0-6.268-2.86-7.542-6.5z"/>
                                    </svg>
                                    <span>Overdue</span>
                                `;
                                statusBadge.style.backgroundColor = '#fee2e2';
                                statusBadge.style.color = '#991b1b';
                            } else {
                                statusBadge.innerHTML = `
                                    <svg style="width: 0.5rem; height: 0.5rem; fill: currentColor;" viewBox="0 0 16 16">
                                        <circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="8" cy="8" r="2.5"/>
                                    </svg>
                                    <span>Pending</span>
                                `;
                                statusBadge.style.backgroundColor = '#fef3c7';
                                statusBadge.style.color = '#92400e';
                            }
                        }

                        // Hide unsubmit button and re-enable turn in
                        form.closest('.unsubmit-row')?.classList.add('hidden');
                        const unsubmitRow = document.querySelector(`[data-unsubmit-id="${assignmentId}"]`);
                        if (unsubmitRow) {
                            unsubmitRow.style.display = 'none';
                        }
                        // Keep attached file visible after unsubmit
                        const turnInBtn = document.querySelector(`[data-turnin-id="${assignmentId}"]`);
                        if (turnInBtn) {
                            turnInBtn.disabled = false;
                            turnInBtn.removeAttribute('disabled');
                            turnInBtn.style.opacity = '1';
                            turnInBtn.style.cursor = 'pointer';
                        }
                    })
                    .catch(error => {
                        showToast('Error reverting submission. Please try again.', 'error');
                        console.error('Error:', error);
                    });
                }

                function submitRemoveFileForm(event, assignmentId) {
                    event.preventDefault();

                    const form = event.target;
                    const formData = new FormData(form);

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(() => {
                        showToast('Attachment removed', 'success');
                        const submittedRow = document.getElementById('submittedFileRow-' + assignmentId);
                        if (submittedRow) submittedRow.style.display = 'none';
                        const hasExistingInput = document.getElementById('hasExistingFile-' + assignmentId);
                        if (hasExistingInput) hasExistingInput.value = '0';
                        const formEl = document.querySelector(`form[onsubmit*="submitAssignmentForm"][onsubmit*="${assignmentId}"]`);
                        const fileInput = formEl ? formEl.querySelector('input[name="submission_file"]') : null;
                        const turnInBtn = document.querySelector(`[data-turnin-id="${assignmentId}"]`);
                        const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
                        if (turnInBtn && !turnInBtn.hasAttribute('disabled') && !hasFile) {
                            turnInBtn.disabled = true;
                            turnInBtn.style.opacity = '0.65';
                            turnInBtn.style.cursor = 'not-allowed';
                        }
                    })
                    .catch(error => {
                        showToast('Error removing attachment. Please try again.', 'error');
                        console.error('Error:', error);
                    });
                }
                
                function submitMarkDoneForm(event, assignmentId) {
                    event.preventDefault();
                    
                    const form = event.target;
                    const formData = new FormData(form);
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        showToast('Assignment marked as done!', 'success');
                        
                        // Update status badge
                        const statusBadge = document.querySelector(`[data-assignment-id="${assignmentId}"] span:nth-child(2)`);
                        if (statusBadge) {
                            statusBadge.innerHTML = `
                                <svg style="width: 0.5rem; height: 0.5rem; fill: currentColor;" viewBox="0 0 16 16">
                                    <path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/>
                                </svg>
                                <span>Marked Done</span>
                            `;
                            statusBadge.style.backgroundColor = '#d1d5db';
                            statusBadge.style.color = '#374151';
                        }
                        
                        // Hide mark as done button
                        const button = form.querySelector('button[name="mark_done"]');
                        if (button) button.closest('div').style.display = 'none';
                    })
                    .catch(error => {
                        showToast('Error marking as done. Please try again.', 'error');
                        console.error('Error:', error);
                    });
                }
                
                function toggleAssignment(id) {
                    const el = document.getElementById('expand-' + id);
                    el.classList.toggle('hidden');
                }
                
                function openResourceModal(type, name, url, size) {
                    const modal = document.getElementById('resourceModal');
                    const content = document.getElementById('modalResourceContent');
                    
                    if (type === 'file') {
                        const ext = url.split('.').pop().toLowerCase();
                        
                        if (['pdf'].includes(ext)) {
                            content.innerHTML = `
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937; word-break: break-word;">${name}</h3>
                                <p style="color: #6b7280; font-size: 0.75rem; margin: 0 0 1rem 0;">${size}</p>
                                <div style="width: 100%; height: 600px; border: 1px solid #e5e7eb; border-radius: 0.375rem; margin-bottom: 1rem; background: #f9fafb;">
                                    <iframe src="${url}" style="width: 100%; height: 100%; border: none; border-radius: 0.375rem;"></iframe>
                                </div>
                                <a href="${url}" download style="display: block; width: 100%; background: #3b82f6; color: white; padding: 0.6rem; border: none; border-radius: 0.375rem; text-align: center; cursor: pointer; font-weight: 600; font-size: 0.8rem; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">Download File</a>
                            `;
                        } else if (['png', 'jpg', 'jpeg'].includes(ext)) {
                            content.innerHTML = `
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937; word-break: break-word;">${name}</h3>
                                <p style="color: #6b7280; font-size: 0.75rem; margin: 0 0 1rem 0;">${size}</p>
                                <div style="width: 100%; text-align: center; margin-bottom: 1rem; max-height: 600px; overflow: auto;">
                                    <img src="${url}" style="max-width: 100%; max-height: 600px; border-radius: 0.375rem; border: 1px solid #e5e7eb;">
                                </div>
                                <a href="${url}" download style="display: block; width: 100%; background: #3b82f6; color: white; padding: 0.6rem; border: none; border-radius: 0.375rem; text-align: center; cursor: pointer; font-weight: 600; font-size: 0.8rem; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">Download File</a>
                            `;
                        } else if (['txt'].includes(ext)) {
                            fetch('${url}')
                                .then(r => r.text())
                                .then(text => {
                                    content.innerHTML = `
                                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937; word-break: break-word;">${name}</h3>
                                        <p style="color: #6b7280; font-size: 0.75rem; margin: 0 0 1rem 0;">${size}</p>
                                        <pre style="width: 100%; height: 500px; padding: 1rem; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 0.375rem; overflow: auto; font-size: 0.75rem; margin-bottom: 1rem; white-space: pre-wrap; word-wrap: break-word; font-family: monospace;">${text}</pre>
                                        <a href="${url}" download style="display: block; width: 100%; background: #3b82f6; color: white; padding: 0.6rem; border: none; border-radius: 0.375rem; text-align: center; cursor: pointer; font-weight: 600; font-size: 0.8rem; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">Download File</a>
                                    `;
                                })
                                .catch(err => {
                                    content.innerHTML = `
                                        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; font-weight: 600; color: #1f2937;">${name}</h3>
                                        <p style="color: #6b7280; font-size: 0.75rem; margin-bottom: 1rem;">${size}</p>
                                        <p style="color: #ef4444; font-size: 0.8rem; margin-bottom: 1rem;">Could not preview file</p>
                                        <a href="${url}" download style="display: block; width: 100%; background: #3b82f6; color: white; padding: 0.6rem; border: none; border-radius: 0.375rem; text-align: center; cursor: pointer; font-weight: 600; font-size: 0.8rem; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">Download File</a>
                                    `;
                                });
                        } else {
                            content.innerHTML = `
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937; word-break: break-word;">${name}</h3>
                                <p style="color: #6b7280; font-size: 0.75rem; margin: 0 0 1rem 0;">${size}</p>
                                <div style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; margin-bottom: 1rem;">
                                    <p style="color: #6b7280; font-size: 0.8rem; margin: 0;">Preview not available for this file type.</p>
                                </div>
                                <a href="${url}" download style="display: block; width: 100%; background: #3b82f6; color: white; padding: 0.6rem; border: none; border-radius: 0.375rem; text-align: center; cursor: pointer; font-weight: 600; font-size: 0.8rem; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">Download File</a>
                            `;
                        }
                    } else {
                        content.innerHTML = `
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937; word-break: break-word;">${name}</h3>
                            <p style="color: #6b7280; font-size: 0.75rem; margin: 0 0 1rem 0; word-break: break-all;">${url}</p>
                            <a href="${url}" target="_blank" style="display: block; width: 100%; background: #d97706; color: white; padding: 0.6rem; border: none; border-radius: 0.375rem; text-align: center; cursor: pointer; font-weight: 600; font-size: 0.8rem; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#b45309';" onmouseout="this.style.background='#d97706';">Open Link</a>
                        `;
                    }
                    
                    modal.classList.add('active');
                }
                
                function closeResourceModal() {
                    const modal = document.getElementById('resourceModal');
                    modal.classList.remove('active');
                }
                
                document.getElementById('resourceModal')?.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeResourceModal();
                    }
                });
            </script>

            <?php if ($message): ?>
                <?php echo showAlert(strpos($message, 'Error') === false ? 'success' : 'error', $message); ?>
            <?php endif; ?>

            <!-- Filters -->
            <form method="GET" class="filter-inline" style="margin-bottom: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" placeholder="Search assignment or course" value="<?php echo htmlspecialchars($search); ?>" style="flex: 1 1 260px; padding: 0.55rem 0.75rem; border: 1.5px solid #e5e7eb; border-radius: 0.4rem; font-size: 0.85rem;">
                <select name="course" style="flex: 1 1 200px; padding: 0.55rem 0.75rem; border: 1.5px solid #e5e7eb; border-radius: 0.4rem; font-size: 0.85rem;">
                    <option value="">All courses</option>
                    <?php foreach ($filterCourses as $courseOption): ?>
                        <option value="<?php echo $courseOption['id']; ?>" <?php echo $filter_course == $courseOption['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($courseOption['title'] . ' (' . $courseOption['code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="teacher" style="flex: 1 1 180px; padding: 0.55rem 0.75rem; border: 1.5px solid #e5e7eb; border-radius: 0.4rem; font-size: 0.85rem;">
                    <option value="">All teachers</option>
                    <?php foreach ($filterTeachers as $teacherOption): ?>
                        <option value="<?php echo $teacherOption['id']; ?>" <?php echo $filter_teacher == $teacherOption['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($teacherOption['first_name'] . ' ' . $teacherOption['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" style="flex: 1 1 160px; padding: 0.55rem 0.75rem; border: 1.5px solid #e5e7eb; border-radius: 0.4rem; font-size: 0.85rem;">
                    <option value="">All statuses</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="submitted" <?php echo $filter_status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="graded" <?php echo $filter_status === 'graded' ? 'selected' : ''; ?>>Graded</option>
                    <option value="done" <?php echo $filter_status === 'done' ? 'selected' : ''; ?>>Marked Done</option>
                    <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="assignments.php" class="reset-link">Reset</a>
            </form>

            <script>
                (function() {
                    const form = document.querySelector('.filter-inline');
                    if (!form) return;

                    const searchInput = form.querySelector('input[name="search"]');
                    const selects = form.querySelectorAll('select');
                    let debounceTimer = null;

                    const submitForm = () => {
                        if (form.requestSubmit) {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    };

                    if (searchInput) {
                        searchInput.addEventListener('input', () => {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(submitForm, 350);
                        });
                    }

                    selects.forEach(select => {
                        select.addEventListener('change', submitForm);
                    });
                })();
            </script>

            <div class="card">
                <div class="card-body">
                    <?php
                    $where_conditions = ["e.student_id = ?", "e.status = 'enrolled'"];
$params = [$student_id, $student_id]; // for submission + grade joins
                    $params[] = $student_id; // for e.student_id

                    if ($search !== '') {
                        $where_conditions[] = "(a.title LIKE ? OR a.code LIKE ? OR c.title LIKE ? OR c.code LIKE ? )";
                        $search_param = '%' . $search . '%';
                        $params[] = $search_param;
                        $params[] = $search_param;
                        $params[] = $search_param;
                        $params[] = $search_param;
                    }

                    if ($filter_course !== '') {
                        $where_conditions[] = "c.id = ?";
                        $params[] = (int)$filter_course;
                    }

                    if ($filter_teacher !== '') {
                        $where_conditions[] = "c.teacher_id = ?";
                        $params[] = (int)$filter_teacher;
                    }

                    switch ($filter_status) {
                        case 'graded':
                            $where_conditions[] = "(s.status = 'graded' OR g.score IS NOT NULL)";
                            break;
                        case 'submitted':
                            $where_conditions[] = "s.status = 'submitted'";
                            break;
                        case 'done':
                            $where_conditions[] = "s.is_completed = 1";
                            break;
                        case 'pending':
                            $where_conditions[] = "( (s.status IS NULL OR s.status = '') AND (s.is_completed IS NULL OR s.is_completed = 0) AND a.due_date >= CURDATE() )";
                            break;
                        case 'overdue':
                            $where_conditions[] = "( a.due_date < CURDATE() AND (s.status IS NULL OR s.status = '') AND (s.is_completed IS NULL OR s.is_completed = 0) )";
                            break;
                        default:
                            break;
                    }

                    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

                    $stmt = $pdo->prepare("
                        SELECT a.*, c.code, c.title as course_title, 
                        s.id as submission_id, s.status as submission_status, s.submitted_at, s.submission_text, s.submission_file, s.is_completed, s.student_comment,
                        g.score, g.feedback
                        FROM assignments a 
                        JOIN courses c ON a.course_id = c.id 
                        JOIN enrollments e ON c.id = e.course_id 
                        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
                        LEFT JOIN grades g ON a.id = g.assignment_id AND g.student_id = ?
                        $where_clause
                        ORDER BY a.due_date DESC
                    ");
                    $stmt->execute($params);
                    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $assignment): ?>
                        <?php
                        $status_style = '';
                        if ($assignment['submission_status'] === 'graded') {
                            $status_style = 'background-color: #d1fae5; color: #065f46;';
                        } elseif ($assignment['submission_status'] === 'submitted') {
                            $status_style = 'background-color: #dbeafe; color: #0c2d6b;';
                        } elseif ($assignment['is_completed']) {
                            $status_style = 'background-color: #d1d5db; color: #374151;';
                        } elseif (isDeadlinePassed($assignment['due_date'])) {
                            $status_style = 'background-color: #fee2e2; color: #991b1b;';
                        } else {
                            $status_style = 'background-color: #fef3c7; color: #92400e;';
                        }
                        $status_text = '';
                        if ($assignment['submission_status'] === 'graded') {
                            $status_text = 'Graded';
                        } elseif ($assignment['submission_status'] === 'submitted') {
                            $status_text = 'Submitted';
                        } elseif ($assignment['is_completed']) {
                            $status_text = 'Marked Done';
                        } elseif (isDeadlinePassed($assignment['due_date'])) {
                            $status_text = 'Overdue';
                        } else {
                            $status_text = 'Pending';
                        }
                        $icon_path = '';
                        if ($assignment['submission_status'] === 'graded' || $assignment['submission_status'] === 'submitted' || $assignment['is_completed']) {
                            $icon_path = '<path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/>';
                        } elseif (isDeadlinePassed($assignment['due_date'])) {
                            $icon_path = '<path d="M8 9.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 2.5a4 4 0 110-8 4 4 0 010 8zM.458 8C1.732 4.36 4.97 1.5 8 1.5c3.03 0 6.268 2.86 7.542 6.5-1.274 3.64-4.512 6.5-7.542 6.5-3.03 0-6.268-2.86-7.542-6.5z"/>';
                        } else {
                            $icon_path = '<circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="8" r="2.5"/>';
                        }
                        ?>
                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.375rem; margin-bottom: 1.25rem; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <!-- Assignment Header (Clickable) -->
                            <div style="background: #f8f9fa; color: #1f2937; padding: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;" onclick="toggleAssignment(<?php echo $assignment['id']; ?>); this.style.background = this.style.background === 'rgb(248, 249, 250)' ? '#eef2ff' : '#f8f9fa';" data-assignment-id="<?php echo $assignment['id']; ?>" data-due-date="<?php echo htmlspecialchars($assignment['due_date']); ?>">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 0.08rem 0; font-size: 0.95rem; font-weight: 600;"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <p style="margin: 0; opacity: 0.75; font-size: 0.7rem;"><?php echo htmlspecialchars($assignment['code']); ?> • Due: <?php echo formatDate($assignment['due_date']); ?></p>
                                </div>
                                <span style="display: inline-flex; align-items: center; gap: 0.4rem;" data-due-date="<?php echo htmlspecialchars($assignment['due_date']); ?>">
                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.15rem 0.5rem; border-radius: 0.2rem; font-size: 0.65rem; font-weight: 600;
                                        <?php echo $status_style; ?>">
                                        <svg style="width: 0.5rem; height: 0.5rem; fill: currentColor;" viewBox="0 0 16 16">
                                            <?php echo $icon_path; ?>
                                        </svg>
                                        <span>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </span>
                                    <svg style="width: 0.65rem; height: 0.65rem; fill: currentColor; transition: transform 0.2s;" viewBox="0 0 16 16">
                                        <path d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </span>
                            </div>
                            
                            <!-- Assignment Details (Hidden by default) -->
                            <div id="expand-<?php echo $assignment['id']; ?>" class="hidden" style="padding: 1rem; border-top: 1px solid #e5e7eb;">
                                
                                <!-- Description -->
                                <div style="margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                                    <p style="margin: 0; color: #4b5563; font-size: 0.75rem; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                </div>
                                
                                <!-- Resources from Teacher -->
                                <?php
                                $resStmt = $pdo->prepare("SELECT * FROM assignment_resources WHERE assignment_id = ? ORDER BY created_at DESC");
                                $resStmt->execute([$assignment['id']]);
                                $resources = $resStmt->fetchAll(PDO::FETCH_ASSOC);
                                if ($resources): ?>
                                    <div style="margin-bottom: 0.75rem; padding: 0.75rem; background: #f0f4ff; border-left: 4px solid #4f46e5; border-radius: 0.375rem;">
                                        <h4 style="margin: 0 0 0.5rem 0; font-size: 0.7rem; font-weight: 600; color: #1f2937; text-transform: uppercase; letter-spacing: 0.05em;">Resources</h4>
                                        <div style="display: grid; gap: 0.35rem;">
                                        <?php foreach ($resources as $res): ?>
                                            <?php if ($res['type'] === 'file' && !empty($res['file_path'])): 
                                                $url = UPLOAD_URL . $res['file_path']; ?>
                                                <a style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: white; border: 1px solid #dbeafe; border-radius: 0.25rem; text-decoration: none; color: inherit; transition: all 0.2s; cursor: pointer;" onclick="openResourceModal('file', '<?php echo htmlspecialchars($res['title'] ?: $res['file_name']); ?>', '<?php echo htmlspecialchars($url); ?>', '<?php echo round($res['file_size'] / 1024, 1); ?> KB');" onmouseover="this.style.background='#eff6ff'; this.style.borderColor='#93c5fd';" onmouseout="this.style.background='white'; this.style.borderColor='#dbeafe';">
                                                    <svg style="width: 0.8rem; height: 0.8rem; flex-shrink: 0; color: #2563eb;" viewBox="0 0 16 16"><path fill="currentColor" d="M4 2a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V2zm5 3a1 1 0 1 0-2 0 1 1 0 0 0 2 0z"/></svg>
                                                    <div style="flex: 1; min-width: 0;">
                                                        <div style="font-size: 0.7rem; font-weight: 600; color: #1f2937; word-break: break-word;"><?php echo htmlspecialchars($res['title'] ?: $res['file_name']); ?></div>
                                                        <div style="font-size: 0.65rem; color: #6b7280;"><?php echo round($res['file_size'] / 1024, 1); ?> KB</div>
                                                    </div>
                                                    <svg style="width: 0.65rem; height: 0.65rem; flex-shrink: 0; color: #6b7280;" viewBox="0 0 16 16"><path fill="currentColor" d="M8.5 1.5A.5.5 0 0 0 8 2v5H3a.5.5 0 0 0-.35.15l-2 2a.5.5 0 0 0 .7.7L3 9.71V13a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9.5a.5.5 0 0 0-1 0V13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8.29l1.15 1.15a.5.5 0 0 0 .7-.7l-2-2A.5.5 0 0 0 3 6.5h5V2a.5.5 0 0 0-1 0v3H8a.5.5 0 0 0-.5-.5z"/></svg>
                                                </a>
                                            <?php elseif ($res['type'] === 'link' && !empty($res['url'])): ?>
                                                <a style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: white; border: 1px solid #fef08a; border-radius: 0.25rem; text-decoration: none; color: inherit; transition: all 0.2s; cursor: pointer;" onclick="openResourceModal('link', '<?php echo htmlspecialchars($res['title'] ?: $res['url']); ?>', '<?php echo htmlspecialchars($res['url']); ?>');" onmouseover="this.style.background='#fffbeb'; this.style.borderColor='#fcd34d';" onmouseout="this.style.background='white'; this.style.borderColor='#fef08a';">
                                                    <svg style="width: 0.8rem; height: 0.8rem; flex-shrink: 0; color: #d97706;" viewBox="0 0 16 16"><path fill="currentColor" d="M6.5 1a1 1 0 0 0-1 1v2H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.5V2a1 1 0 0 0-1-1h-3zM4 5h8a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/></svg>
                                                    <div style="flex: 1; min-width: 0;">
                                                        <div style="font-size: 0.7rem; font-weight: 600; color: #1f2937; word-break: break-word;"><?php echo htmlspecialchars($res['title'] ?: $res['url']); ?></div>
                                                        <div style="font-size: 0.65rem; color: #6b7280; word-break: break-all;"><?php echo htmlspecialchars($res['url']); ?></div>
                                                    </div>
                                                    <svg style="width: 0.65rem; height: 0.65rem; flex-shrink: 0; color: #6b7280;" viewBox="0 0 16 16"><path fill="currentColor" d="M8.5 1.5A.5.5 0 0 0 8 2v5H3a.5.5 0 0 0-.35.15l-2 2a.5.5 0 0 0 .7.7L3 9.71V13a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9.5a.5.5 0 0 0-1 0V13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8.29l1.15 1.15a.5.5 0 0 0 .7-.7l-2-2A.5.5 0 0 0 3 6.5h5V2a.5.5 0 0 0-1 0v3H8a.5.5 0 0 0-.5-.5z"/></svg>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Grades -->
                                <?php if ($assignment['submission_status'] === 'graded' && $assignment['score'] !== null): ?>
                                <div style="margin-bottom: 0.75rem; padding: 0.5rem; background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <svg style="width: 0.9rem; height: 0.9rem; flex-shrink: 0; color: #16a34a;" viewBox="0 0 16 16"><path fill="currentColor" d="M9.5 2a.5.5 0 0 1 1 0v6a.5.5 0 0 1-1 0V2zm-3 6a.5.5 0 0 1 1 0v6a.5.5 0 0 1-1 0V8zm-3-2a.5.5 0 0 1 1 0v8a.5.5 0 0 1-1 0V6z"/></svg>
                                    <div>
                                        <div style="font-size: 0.6rem; color: #6b7280; font-weight: 600; text-transform: uppercase;">Your Grade</div>
                                        <div style="font-size: 0.8rem; font-weight: 700; color: #16a34a;"><?php echo $assignment['score']; ?>/<?php echo $assignment['max_score']; ?></div>
                                        <?php if ($assignment['feedback']): ?>
                                        <div style="margin-top: 0.3rem; font-size: 0.65rem; color: #165e40; border-top: 1px solid #bbf7d0; padding-top: 0.3rem;">
                                            <strong>Feedback:</strong> <?php echo htmlspecialchars($assignment['feedback']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Submission Form -->
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;">
                                    <form method="POST" enctype="multipart/form-data" onsubmit="submitAssignmentForm(event, <?php echo $assignment['id']; ?>)">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                        <input type="hidden" name="submit_assignment" value="1">
                                        <input type="hidden" name="has_existing_file" id="hasExistingFile-<?php echo $assignment['id']; ?>" value="<?php echo !empty($assignment['submission_file']) ? '1' : '0'; ?>">
                                        
                                        <!-- Submission Section -->
                                        <?php $isSubmitted = ($assignment['submission_status'] === 'submitted' || $assignment['submission_status'] === 'graded'); ?>
                                        <div style="margin-bottom: 1rem;">
                                            <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Turn In Assignment</h4>
                                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.7rem; color: #374151;">Your Work</label>
                                                <textarea name="submission_text" style="width: 100%; min-height: 70px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-family: inherit; font-size: 0.7rem; transition: border 0.2s; <?php echo $isSubmitted ? 'background: #f3f4f6; cursor: not-allowed;' : ''; ?>" placeholder="Write or paste your submission here..." <?php echo $isSubmitted ? 'readonly' : ''; ?> onmouseover="<?php echo !$isSubmitted ? "this.style.borderColor='#9ca3af';" : ''; ?>" onmouseout="<?php echo !$isSubmitted ? "this.style.borderColor='#d1d5db';" : ''; ?>" onfocus="<?php echo !$isSubmitted ? "this.style.borderColor='#3b82f6'; this.style.outline='none';" : ''; ?>" onblur="<?php echo !$isSubmitted ? "this.style.borderColor='#d1d5db';" : ''; ?>"><?php echo htmlspecialchars($assignment['submission_text'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <!-- File Upload Area (Enhanced Design) -->
                                            <?php if (!$isSubmitted): ?>
                                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                                <label style="display: block; margin-bottom: 0.35rem; font-weight: 500; font-size: 0.7rem; color: #374151;">Attach File (optional)</label>
                                                <div style="position: relative; border: 2px dashed #d1d5db; border-radius: 0.5rem; padding: 1.5rem; text-align: center; background: #f9fafb; transition: all 0.2s; cursor: pointer;" 
                                                     onmouseover="this.style.borderColor='#3b82f6'; this.style.background='#eff6ff';"
                                                     onmouseout="this.style.borderColor='#d1d5db'; this.style.background='#f9fafb';"
                                                     onclick="document.getElementById('submissionFileInput-<?php echo $assignment['id']; ?>').click();" id="fileUploadArea-<?php echo $assignment['id']; ?>">
                                                    <svg style="width: 1.5rem; height: 1.5rem; margin: 0 auto 0.35rem; color: #6b7280;" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                                                    <div style="font-size: 0.75rem; font-weight: 600; color: #1f2937; margin-bottom: 0.2rem;">Click to upload or drag and drop</div>
                                                    <p style="color: #6b7280; font-size: 0.65rem; margin: 0;">PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP, PNG, JPG, TXT (Max 20MB)</p>
                                                    <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.png,.jpg,.jpeg,.txt" style="display: none;" id="submissionFileInput-<?php echo $assignment['id']; ?>" onchange="updateFileDisplay(this)">
                                                </div>
                                                
                                                <!-- File Preview -->
                                                <div id="filePreview-<?php echo $assignment['id']; ?>" style="display: none; margin-top: 0.5rem; padding: 0.5rem; background: #eff6ff; border-left: 3px solid #3b82f6; border-radius: 0.25rem; align-items: center; gap: 0.4rem;">
                                                    <svg style="width: 0.85rem; height: 0.85rem; color: #3b82f6; flex-shrink: 0;" viewBox="0 0 16 16"><path fill="currentColor" d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/></svg>
                                                    <div>
                                                        <div style="color: #0c4a6e; font-size: 0.7rem; text-decoration: none; font-weight: 500;" id="fileName-<?php echo $assignment['id']; ?>"></div>
                                                        <div style="color: #0c7da8; font-size: 0.65rem;" id="fileSize-<?php echo $assignment['id']; ?>"></div>
                                                    </div>
                                                    <button type="button" style="margin-left: auto; background: none; border: none; color: #6b7280; cursor: pointer; font-size: 0.9rem; padding: 0;" onclick="clearFileUpload(<?php echo $assignment['id']; ?>);">✕</button>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                                
                                                <?php if (!empty($assignment['submission_file'])): ?>
                                                    <?php 
                                                        $submittedPath = UPLOAD_DIR . $assignment['submission_file'];
                                                        $submittedSizeKB = (is_file($submittedPath)) ? round(filesize($submittedPath) / 1024, 1) . ' KB' : '';
                                                        $submittedName = basename($assignment['submission_file']);
                                                        $submittedUrl = UPLOAD_URL . $assignment['submission_file'];
                                                    ?>
                                                    <div id="submittedFileRow-<?php echo $assignment['id']; ?>" style="margin-top: 0.5rem; padding: 0.5rem; background: #f0fdf4; border-left: 3px solid #16a34a; border-radius: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                                                        <svg style="width: 0.85rem; height: 0.85rem; color: #16a34a; flex-shrink: 0;" viewBox="0 0 16 16"><path fill="currentColor" d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/></svg>
                                                        <div style="flex: 1; min-width: 0;">
                                                            <div style="font-size: 0.7rem; font-weight: 600; color: #166534; word-break: break-word;"><?php echo htmlspecialchars($submittedName); ?></div>
                                                            <?php if ($submittedSizeKB): ?>
                                                                <div style="font-size: 0.65rem; color: #16a34a;"><?php echo $submittedSizeKB; ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button type="button" style="background: #dcfce7; color: #166534; padding: 0.3rem 0.6rem; border: 1px solid #bbf7d0; border-radius: 0.35rem; font-size: 0.68rem; font-weight: 600; cursor: pointer;" 
                                                            onclick="openResourceModal('file', '<?php echo htmlspecialchars($submittedName); ?>', '<?php echo htmlspecialchars($submittedUrl); ?>', '<?php echo htmlspecialchars($submittedSizeKB); ?>');"
                                                            onmouseover="this.style.background='#bbf7d0';" onmouseout="this.style.background='#dcfce7';">
                                                            View
                                                        </button>
                                                        <?php if ($assignment['submission_status'] !== 'submitted' && $assignment['submission_status'] !== 'graded'): ?>
                                                        <form method="POST" onsubmit="submitRemoveFileForm(event, <?php echo $assignment['id']; ?>)" style="margin: 0;">
                                                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                            <input type="hidden" name="remove_submission_file" value="1">
                                                            <button type="submit" style="background: #fee2e2; color: #7f1d1d; padding: 0.3rem 0.6rem; border: 1px solid #fecaca; border-radius: 0.35rem; font-size: 0.68rem; font-weight: 600; cursor: pointer;" onmouseover="this.style.background='#fecaca';" onmouseout="this.style.background='#fee2e2';">Remove</button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php $canUnsubmit = ($assignment['submission_status'] === 'submitted' && $assignment['score'] === null); ?>
                                            <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
                                                <?php $turnInDisabled = ($assignment['submission_status'] === 'submitted' || $assignment['submission_status'] === 'graded'); ?>
                                                <button type="submit" data-turnin-id="<?php echo $assignment['id']; ?>" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 0.4rem 0.9rem; border: none; border-radius: 0.375rem; cursor: <?php echo $turnInDisabled ? 'not-allowed' : 'pointer'; ?>; font-weight: 600; font-size: 0.7rem; transition: background 0.2s; opacity: <?php echo $turnInDisabled ? '0.65' : '1'; ?>;" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';" <?php echo $turnInDisabled ? 'disabled' : ''; ?>>Turn In</button>

                                                <div class="unsubmit-row" data-unsubmit-id="<?php echo $assignment['id']; ?>" style="<?php echo $canUnsubmit ? '' : 'display:none;'; ?>">
                                                    <form method="POST" onsubmit="submitUnsubmitForm(event, <?php echo $assignment['id']; ?>)" style="display: inline-flex; gap: 0.4rem; align-items: center;">
                                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                        <input type="hidden" name="unsubmit_assignment" value="1">
                                                        <button type="submit" class="btn" style="background: #f1f5f9; color: #1f2937; padding: 0.32rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.35rem; font-size: 0.68rem; font-weight: 600; cursor: pointer; transition: background 0.2s, border-color 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';">
                                                            Unsubmit
                                                        </button>
                                                        <span style="font-size: 0.65rem; color: #6b7280;">Revert your submission to make changes.</span>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Private Comment Section -->
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Private Messages</h4>
                                    
                                    <?php
                                    // Fetch all comments for this assignment
                                    $commentsStmt = $pdo->prepare("
                                        SELECT ac.*, u.first_name, u.last_name, u.role 
                                        FROM assignment_comments ac
                                        JOIN users u ON ac.user_id = u.id
                                        WHERE ac.assignment_id = ?
                                        ORDER BY ac.created_at ASC
                                    ");
                                    $commentsStmt->execute([$assignment['id']]);
                                    $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <!-- Messages Thread -->
                                    <div id="commentsThread-<?php echo $assignment['id']; ?>" style="max-height: 300px; overflow-y: auto; margin-bottom: 0.75rem; padding: 0.5rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem;">
                                        <?php if (empty($comments)): ?>
                                            <p style="color: #9ca3af; font-size: 0.7rem; text-align: center; margin: 0.5rem 0;">No messages yet</p>
                                        <?php else: ?>
                                            <?php foreach ($comments as $comment): ?>
                                                <?php $isStudent = ($comment['user_type'] === 'student'); ?>
                                                <div style="margin-bottom: 0.5rem; display: flex; flex-direction: column; align-items: <?php echo $isStudent ? 'flex-end' : 'flex-start'; ?>;">
                                                    <div style="max-width: 75%; background: <?php echo $isStudent ? '#dbeafe' : '#f3f4f6'; ?>; padding: 0.5rem 0.75rem; border-radius: 0.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                        <div style="font-size: 0.65rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem;">
                                                            <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                                            <span style="font-weight: 400; color: #6b7280;">(<?php echo ucfirst($comment['user_type']); ?>)</span>
                                                        </div>
                                                        <div style="font-size: 0.7rem; color: #1f2937; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                                                        <div style="font-size: 0.6rem; color: #9ca3af; margin-top: 0.25rem;"><?php echo date('M j, g:i A', strtotime($comment['created_at'])); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- New Comment Form -->
                                    <form method="POST" onsubmit="submitCommentForm(event, <?php echo $assignment['id']; ?>)">
                                        <input type="hidden" name="save_comment" value="1">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                        <div class="form-group" style="margin-bottom: 0.5rem;">
                                            <textarea id="commentTextarea-<?php echo $assignment['id']; ?>" name="student_comment" style="width: 100%; min-height: 50px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-family: inherit; font-size: 0.7rem; transition: border 0.2s;" placeholder="Type your message..." onmouseover="this.style.borderColor='#9ca3af';" onmouseout="this.style.borderColor='#d1d5db';" onfocus="this.style.borderColor='#3b82f6'; this.style.outline='none';" onblur="this.style.borderColor='#d1d5db';"></textarea>
                                        </div>
                                        <button type="submit" class="btn" style="background: #10b981; color: white; padding: 0.4rem 0.9rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.7rem; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#059669';" onmouseout="this.style.background='#10b981';">Send Message</button>
                                    </form>
                                </div>
                                
                                <!-- Mark as Done -->
                                <?php if (!$assignment['submission_status'] && !$assignment['is_completed']): ?>
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;">
                                    <form method="POST" style="display: inline;" onsubmit="submitMarkDoneForm(event, <?php echo $assignment['id']; ?>)">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                        <input type="hidden" name="mark_done" value="1">
                                        <button type="submit" class="btn" style="background: #8b5cf6; color: white; padding: 0.4rem 0.9rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.7rem; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#7c3aed';" onmouseout="this.style.background='#8b5cf6';">Mark as Done</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php 
                        endforeach;
                    else: ?>
                        <p style="text-align: center; color: #9ca3af; font-size: 0.85rem;">No assignments in your enrolled courses</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Resource Modal -->
            <div id="resourceModal" class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <div></div>
                        <button class="modal-close" onclick="closeResourceModal();">×</button>
                    </div>
                    <div id="modalResourceContent"></div>
                </div>
            </div>
<?php studentLayoutEnd(); ?>

