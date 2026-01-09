<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/admin_layout.php';

Auth::requireRole('admin');

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_news') {
            // Handle news addition
            $title = trim($_POST['title'] ?? '');
            $summary = trim($_POST['summary'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $status = $_POST['status'] ?? 'draft';

            // Handle image upload
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/uploads/news/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid('news_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_url = 'assets/uploads/news/' . $new_filename;
                    }
                }
            }

            if (empty($title) || empty($content)) {
                $error = 'Title and content are required.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO news (title, summary, content, image_url, author, posted_by, status, published_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;

                    $stmt->execute([
                        $title,
                        $summary,
                        $content,
                        $image_url,
                        $author,
                        $_SESSION['user_id'],
                        $status,
                        $published_at
                    ]);

                    $message = 'News article added successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to add news article: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'update_news') {
            // Handle news update
            $news_id = $_POST['news_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $summary = trim($_POST['summary'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $status = $_POST['status'] ?? 'draft';

            if (empty($title) || empty($content)) {
                $error = 'Title and content are required.';
            } else {
                try {
                    // Handle image upload for update
                    $image_url = $_POST['existing_image'] ?? null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../assets/uploads/news/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                        if (in_array($file_extension, $allowed_extensions)) {
                            $new_filename = uniqid('news_') . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;

                            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                                // Delete old image if exists
                                if ($image_url && file_exists('../' . $image_url)) {
                                    unlink('../' . $image_url);
                                }
                                $image_url = 'assets/uploads/news/' . $new_filename;
                            }
                        }
                    }

                    $stmt = $pdo->prepare("
                        UPDATE news
                        SET title = ?, summary = ?, content = ?, image_url = ?, author = ?, status = ?, published_at = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");

                    $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;

                    $stmt->execute([
                        $title,
                        $summary,
                        $content,
                        $image_url,
                        $author,
                        $status,
                        $published_at,
                        $news_id
                    ]);

                    $message = 'News article updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update news article: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_news') {
            $news_id = $_POST['news_id'] ?? 0;

            try {
                // Get image path before deleting
                $stmt = $pdo->prepare("SELECT image_url FROM news WHERE id = ?");
                $stmt->execute([$news_id]);
                $news = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($news && $news['image_url'] && file_exists('../' . $news['image_url'])) {
                    unlink('../' . $news['image_url']);
                }

                $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
                $stmt->execute([$news_id]);

                $message = 'News article deleted successfully!';
            } catch (Exception $e) {
                $error = 'Failed to delete news article: ' . $e->getMessage();
            }
        }
    }
}

// Handle filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "
    SELECT n.*, u.first_name, u.last_name
    FROM news n
    LEFT JOIN users u ON n.posted_by = u.id
    WHERE 1=1
";

$params = [];

if (!empty($status_filter)) {
    $query .= " AND n.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (n.title LIKE ? OR n.content LIKE ? OR n.author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($date_from)) {
    $query .= " AND DATE(n.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(n.created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY n.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $news_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $news_articles = [];
}

adminLayoutStart('news', 'News Management');
?>

<style>
.news-form { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem; }
.news-form h3 { margin-bottom: 1rem; font-size: 1.125rem; font-weight: 600; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; font-family: inherit; }
.form-group textarea { min-height: 120px; resize: vertical; }
.image-preview { max-width: 200px; max-height: 150px; border-radius: 0.5rem; margin-top: 0.5rem; }
.filters { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; }
.filters .form-row { margin-bottom: 0; }
.news-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem; }
.news-card h4 { margin-bottom: 0.5rem; font-size: 1rem; font-weight: 600; }
.news-card .meta { color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.75rem; }
.news-card .actions { display: flex; gap: 0.5rem; }
.status-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.7rem; font-weight: 600; }
.status-published { background: #dcfce7; color: #166534; }
.status-draft { background: #fef3c7; color: #92400e; }
.status-archived { background: #f3f4f6; color: #374151; }
</style>

<div style="max-width: 1400px; margin: 0 auto;">
    <?php if ($message): ?>
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #bbf7d0;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Add News Form -->
    <div class="card news-form">
        <h3>Add New News Article</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_news">
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" placeholder="e.g., Admin Team">
                </div>
            </div>
            <div class="form-group">
                <label for="summary">Summary</label>
                <textarea id="summary" name="summary" placeholder="Brief summary of the news article"></textarea>
            </div>
            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content" required placeholder="Full news article content"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="image">Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small style="color: var(--text-secondary); font-size: 0.8rem;">Supported formats: JPG, PNG, GIF, WebP</small>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>
            <button type="submit" style="background: #f97316; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Add News Article</button>
        </form>
    </div>

    <!-- Filters -->
    <div class="card filters">
        <h3 style="margin-bottom: 1rem; font-size: 1.125rem; font-weight: 600;">Filter News Articles</h3>
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, content, or author">
                </div>
                <div class="form-group">
                    <label for="status_filter">Status</label>
                    <select id="status_filter" name="status">
                        <option value="">All Status</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" style="background: #f97316; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">Apply Filters</button>
                <a href="news.php" style="background: #6b7280; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">Clear Filters</a>
            </div>
        </form>
    </div>

    <!-- News Articles List -->
    <div class="card">
        <div style="padding: 1.25rem; border-bottom: 1px solid var(--border-color);">
            <h3 style="margin: 0; font-size: 1.125rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14,2 14,8 20,8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10,9 9,9 8,9"></polyline>
                </svg>
                News Articles (<?php echo count($news_articles); ?>)
            </h3>
        </div>
        <div style="padding: 1.5rem;">
            <?php if (empty($news_articles)): ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">No news articles found.</p>
            <?php else: ?>
                <?php foreach ($news_articles as $article): ?>
                    <div class="news-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <h4><?php echo htmlspecialchars($article['title']); ?></h4>
                                <div class="meta">
                                    <span>By <?php echo htmlspecialchars($article['author'] ?: $article['first_name'] . ' ' . $article['last_name']); ?></span>
                                    <span>•</span>
                                    <span><?php echo date('M j, Y', strtotime($article['created_at'])); ?></span>
                                    <span>•</span>
                                    <span><?php echo $article['views']; ?> views</span>
                                    <span>•</span>
                                    <span class="status-badge status-<?php echo $article['status']; ?>"><?php echo ucfirst($article['status']); ?></span>
                                </div>
                                <?php if ($article['summary']): ?>
                                    <p style="color: var(--text-secondary); margin-bottom: 0.75rem;"><?php echo htmlspecialchars(substr($article['summary'], 0, 150)) . (strlen($article['summary']) > 150 ? '...' : ''); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($article['image_url']): ?>
                                <img src="../<?php echo htmlspecialchars($article['image_url']); ?>" alt="News image" style="width: 80px; height: 60px; object-fit: cover; border-radius: 0.5rem; margin-left: 1rem;">
                            <?php endif; ?>
                        </div>
                        <div class="actions">
                            <button onclick="editNews(<?php echo $article['id']; ?>)" style="background: #3b82f6; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.8rem;">Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this news article?')">
                                <input type="hidden" name="action" value="delete_news">
                                <input type="hidden" name="news_id" value="<?php echo $article['id']; ?>">
                                <button type="submit" style="background: #dc2626; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.8rem;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 0.75rem; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-bottom: 1rem;">Edit News Article</h3>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_news">
            <input type="hidden" name="news_id" id="edit_news_id">
            <input type="hidden" name="existing_image" id="edit_existing_image">

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_title">Title *</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_author">Author</label>
                    <input type="text" id="edit_author" name="author">
                </div>
            </div>
            <div class="form-group">
                <label for="edit_summary">Summary</label>
                <textarea id="edit_summary" name="summary"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_content">Content *</label>
                <textarea id="edit_content" name="content" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_image">New Image (leave empty to keep current)</label>
                    <input type="file" id="edit_image" name="image" accept="image/*">
                    <div id="current_image_preview"></div>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">Cancel</button>
                <button type="submit" style="background: #f97316; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">Update Article</button>
            </div>
        </form>
    </div>
</div>

<script>
function editNews(newsId) {
    // Fetch news data and populate the edit form
    fetch(`news.php?ajax=get_news&id=${newsId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_news_id').value = data.news.id;
                document.getElementById('edit_title').value = data.news.title;
                document.getElementById('edit_summary').value = data.news.summary || '';
                document.getElementById('edit_content').value = data.news.content;
                document.getElementById('edit_author').value = data.news.author || '';
                document.getElementById('edit_status').value = data.news.status;
                document.getElementById('edit_existing_image').value = data.news.image_url || '';

                const preview = document.getElementById('current_image_preview');
                if (data.news.image_url) {
                    preview.innerHTML = `<img src="../${data.news.image_url}" alt="Current image" class="image-preview">`;
                } else {
                    preview.innerHTML = '';
                }

                document.getElementById('editModal').style.display = 'block';
            } else {
                alert('Error loading news article: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading news article');
        });
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php adminLayoutEnd(); ?>