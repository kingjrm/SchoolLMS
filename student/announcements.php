<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';
require_once '../includes/student_layout.php';

Auth::requireRole('student');
$user = Auth::getCurrentUser();
$student_id = $user['id'];

studentLayoutStart('announcements', 'Announcements', false);
?>

<style>
    .announcements-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .announcement-card {
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: border-color 0.2s;
    }
    
    .announcement-card:hover {
        border-color: #cbd5e1;
    }
    
    .announcement-card.pinned {
        border-left: 3px solid #2563eb;
    }
    
    .announcement-header {
        padding: 1rem 1.25rem;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: start;
    }
    
    .announcement-header.pinned-header {
        background: #eff6ff;
        border-left: 3px solid #2563eb;
    }
    
    .announcement-title-section {
        flex: 1;
    }
    
    .announcement-title-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .announcement-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .pinned-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: #2563eb;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        font-weight: 600;
    }
    
    .pinned-badge svg {
        width: 0.7rem;
        height: 0.7rem;
        fill: currentColor;
    }
    
    .announcement-meta {
        font-size: 0.75rem;
        color: #6b7280;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }
    
    .announcement-meta-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    
    .announcement-meta-item svg {
        width: 0.85rem;
        height: 0.85rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
    }
    
    .course-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        background: #e5e7eb;
        color: #374151;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .announcement-body {
        padding: 1.25rem;
    }
    
    .announcement-content {
        font-size: 0.85rem;
        color: #374151;
        line-height: 1.7;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    .announcement-footer {
        padding: 0.75rem 1.25rem;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .announcement-author {
        font-size: 0.75rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .announcement-author svg {
        width: 0.9rem;
        height: 0.9rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
    }
    
    .announcement-date {
        font-size: 0.7rem;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    
    .announcement-date svg {
        width: 0.8rem;
        height: 0.8rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 1rem;
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }
    
    .empty-state-icon {
        width: 3rem;
        height: 3rem;
        margin: 0 auto 1rem;
        opacity: 0.4;
        fill: none;
        stroke: #9ca3af;
        stroke-width: 1.5;
    }
    
    .empty-state h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        color: #6b7280;
        font-weight: 600;
    }
    
    .empty-state p {
        margin: 0;
        font-size: 0.85rem;
        color: #9ca3af;
    }
</style>

<div class="announcements-container">
    <?php
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, c.code, c.title as course_title, u.first_name, u.last_name 
            FROM announcements a 
            JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.posted_by = u.id 
            JOIN enrollments e ON c.id = e.course_id 
            WHERE e.student_id = ? AND e.status = 'enrolled'
            ORDER BY a.pinned DESC, a.posted_at DESC
        ");
        $stmt->execute([$student_id]);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($announcements)):
            foreach ($announcements as $ann):
                $isPinned = !empty($ann['pinned']);
    ?>
                <div class="announcement-card <?php echo $isPinned ? 'pinned' : ''; ?>">
                    <div class="announcement-header <?php echo $isPinned ? 'pinned-header' : ''; ?>">
                        <div class="announcement-title-section">
                            <div class="announcement-title-row">
                                <h3 class="announcement-title"><?php echo htmlspecialchars($ann['title']); ?></h3>
                                <?php if ($isPinned): ?>
                                    <span class="pinned-badge">
                                        <svg viewBox="0 0 16 16">
                                            <path d="M8 0a1 1 0 0 1 1 1v5.268l4.562-2.634a1 1 0 1 1 1 1.732L10 8l4.562 2.634a1 1 0 1 1-1 1.732L9 9.732V15a1 1 0 1 1-2 0V9.732l-4.562 2.634a1 1 0 1 1-1-1.732L6 8 1.438 5.366a1 1 0 0 1 1-1.732L7 6.268V1a1 1 0 0 1 1-1z"/>
                                        </svg>
                                        Pinned
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="announcement-meta">
                                <span class="announcement-meta-item">
                                    <svg viewBox="0 0 16 16">
                                        <path d="M2.5 3A1.5 1.5 0 0 0 1 4.5v.793c.026.009.051.02.076.032L7.674 8.51c.206.1.446.1.652 0l6.598-3.185A.755.755 0 0 1 15 5.293V4.5A1.5 1.5 0 0 0 13.5 3h-11Z"/>
                                        <path d="M15 6.954 8.978 9.86a2.25 2.25 0 0 1-1.956 0L1 6.954V11.5A1.5 1.5 0 0 0 2.5 13h11a1.5 1.5 0 0 0 1.5-1.5V6.954Z"/>
                                    </svg>
                                    <span class="course-badge"><?php echo htmlspecialchars($ann['code']); ?></span>
                                </span>
                                <span class="announcement-meta-item"><?php echo htmlspecialchars($ann['course_title']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="announcement-body">
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                        </div>
                        
                        <!-- Image Display -->
                        <?php if ($ann['image_path'] && file_exists('../' . $ann['image_path'])): ?>
                            <div style="margin:0.75rem 0;">
                                <img src="<?php echo htmlspecialchars('../' . $ann['image_path']); ?>" alt="Announcement image" style="max-width:100%;max-height:300px;border-radius:0.4rem;">
                            </div>
                        <?php endif; ?>
                        
                        <!-- External Link Display -->
                        <?php if ($ann['external_link']): ?>
                            <div style="margin-top:0.75rem;">
                                <a href="<?php echo htmlspecialchars($ann['external_link']); ?>" target="_blank" rel="noopener noreferrer" style="color:#2563eb;text-decoration:none;font-size:0.85rem;font-weight:500;">
                                    🔗 Visit Link
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="announcement-footer">
                        <div class="announcement-author">
                            <svg viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                            </svg>
                            <?php echo htmlspecialchars($ann['first_name'] . ' ' . $ann['last_name']); ?>
                        </div>
                        <div class="announcement-date">
                            <svg viewBox="0 0 16 16">
                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                            </svg>
                            <?php echo formatDate($ann['posted_at']); ?>
                        </div>
                    </div>
                </div>
    <?php
            endforeach;
        else:
    ?>
            <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <h3>No Announcements</h3>
                <p>There are no announcements in your enrolled courses at this time.</p>
            </div>
    <?php
        endif;
    } catch (Exception $e) {
        echo '<div class="empty-state"><p style="color: #dc2626;">Error loading announcements: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
    ?>
</div>

<?php studentLayoutEnd(); ?>
