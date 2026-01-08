<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

Auth::requireRole('admin');

adminLayoutStart('help', 'Help');
?>

<style>
    .help-container {
        max-width: 1000px;
    }
    
    .help-header {
        margin-bottom: 2rem;
    }
    
    .help-header h1 {
        margin: 0 0 0.5rem 0;
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .help-header p {
        margin: 0;
        font-size: 1rem;
        color: #6b7280;
    }
    
    .help-search {
        margin-bottom: 2rem;
        position: relative;
    }
    
    .help-search input {
        width: 100%;
        padding: 0.875rem 1rem 0.875rem 2.75rem;
        border: 1.5px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }
    
    .help-search input:focus {
        outline: none;
        border-color: #3b82f6;
    }
    
    .help-search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        pointer-events: none;
    }
    
    .help-section {
        margin-bottom: 2rem;
    }
    
    .help-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .help-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        overflow: hidden;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .help-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .help-item.hidden {
        display: none;
    }
    
    .help-item-header {
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
    }
    
    .help-item-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        flex: 1;
    }
    
    .help-item-icon {
        color: #6b7280;
        font-size: 0.875rem;
        transition: transform 0.2s;
    }
    
    .help-item.expanded .help-item-icon {
        transform: rotate(180deg);
    }
    
    .help-item-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .help-item.expanded .help-item-content {
        max-height: 1000px;
    }
    
    .help-item-body {
        padding: 1.25rem;
        border-top: 1px solid #e5e7eb;
    }
    
    .help-item-description {
        font-size: 0.85rem;
        color: #4b5563;
        line-height: 1.6;
        margin: 0 0 1rem 0;
    }
    
    .help-item-steps {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .help-item-steps li {
        padding: 0.5rem 0;
        padding-left: 1.5rem;
        position: relative;
        font-size: 0.85rem;
        color: #374151;
        line-height: 1.6;
    }
    
    .help-item-steps li::before {
        content: "•";
        position: absolute;
        left: 0;
        color: #3b82f6;
        font-weight: bold;
    }
    
    .help-item-steps li + li {
        border-top: 1px solid #f3f4f6;
    }
    
    .help-faq-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .help-faq-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .help-faq-item:hover {
        border-color: #cbd5e1;
    }
    
    .help-faq-item.hidden {
        display: none;
    }
    
    .help-faq-question {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        padding: 1rem 1.25rem;
        background: #f9fafb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .help-faq-answer {
        font-size: 0.85rem;
        color: #4b5563;
        line-height: 1.6;
        margin: 0;
        padding: 1rem 1.25rem;
        border-top: 1px solid #e5e7eb;
        display: none;
    }
    
    .help-faq-item.expanded .help-faq-answer {
        display: block;
    }
    
    .help-faq-icon {
        color: #6b7280;
        font-size: 0.875rem;
        transition: transform 0.2s;
    }
    
    .help-faq-item.expanded .help-faq-icon {
        transform: rotate(180deg);
    }
    
    .help-support-section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        margin-top: 2rem;
    }
    
    .help-support-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 0.5rem 0;
    }
    
    .help-support-intro {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0 0 1.5rem 0;
    }
    
    .help-support-info {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        text-align: left;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .help-support-item {
        font-size: 0.9rem;
        color: #374151;
    }
    
    .help-support-item a {
        color: #2563eb;
        text-decoration: none;
    }
    
    .help-support-item a:hover {
        text-decoration: underline;
    }
    
    .no-results {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }
    
    .no-results-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>

<div class="help-container">
    <div class="help-header">
        <h1>Help & Support</h1>
        <p>Find answers and guides for system administration.</p>
    </div>

    <!-- Search Bar -->
    <div class="help-search">
        <div class="help-search-icon">🔍</div>
        <input type="text" id="helpSearch" placeholder="Search for help topics, questions, or guides...">
    </div>

    <!-- User Management -->
    <div class="help-section">
        <h2 class="help-section-title">User Management</h2>
        <div class="help-item" data-category="users">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Creating user accounts</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Create accounts for students, teachers, or administrators.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Users</strong> page</li>
                        <li>Click "Add New User" or "Create User"</li>
                        <li>Fill in required information: username, email, password</li>
                        <li>Enter first name and last name</li>
                        <li>Select the role (Student, Teacher, or Admin)</li>
                        <li>Set account status (Active/Inactive)</li>
                        <li>Save the user account</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="users">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Managing user roles</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Change user roles and permissions.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Users</strong> page</li>
                        <li>Find the user you want to modify</li>
                        <li>Click "Edit" or the user's name</li>
                        <li>Change the role dropdown</li>
                        <li>Save changes</li>
                        <li>Note: Changing roles may affect access to certain features</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="users">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Activating/deactivating users</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Control user access to the system.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Users</strong> page</li>
                        <li>Find the user account</li>
                        <li>Change the status from Active to Inactive (or vice versa)</li>
                        <li>Inactive users cannot log in but their data is preserved</li>
                        <li>Save the changes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Administration -->
    <div class="help-section">
        <h2 class="help-section-title">Course Administration</h2>
        <div class="help-item" data-category="courses">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Viewing all courses</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Monitor and manage all courses in the system.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Courses</strong> page</li>
                        <li>View all courses with their details</li>
                        <li>Use filters to find specific courses</li>
                        <li>See course status, teacher, and enrollment numbers</li>
                        <li>Click on a course to view detailed information</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="courses">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Assigning teachers to courses</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Assign teachers to manage courses.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Courses</strong> page</li>
                        <li>Find the course you want to assign</li>
                        <li>Click "Edit" or the course name</li>
                        <li>Select a teacher from the teacher dropdown</li>
                        <li>Save the changes</li>
                        <li>The teacher will now have access to manage that course</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollment Management -->
    <div class="help-section">
        <h2 class="help-section-title">Enrollment Management</h2>
        <div class="help-item" data-category="enrollments">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Viewing enrollments</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Monitor student enrollments across all courses.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Enrollments</strong> page</li>
                        <li>View all student-course enrollments</li>
                        <li>Filter by course, student, or status</li>
                        <li>See enrollment dates and current status</li>
                        <li>Use search to find specific enrollments</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="enrollments">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Manual enrollment</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Manually enroll students in courses.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Enrollments</strong> page</li>
                        <li>Click "Add Enrollment" or "Enroll Student"</li>
                        <li>Select a student from the dropdown</li>
                        <li>Select a course</li>
                        <li>Set enrollment status (usually "Enrolled")</li>
                        <li>Save the enrollment</li>
                        <li>The student will now have access to the course</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Terms -->
    <div class="help-section">
        <h2 class="help-section-title">Academic Terms</h2>
        <div class="help-item" data-category="terms">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Creating academic terms</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Set up academic terms or semesters.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Terms</strong> page</li>
                        <li>Click "Create Term" or "Add New Term"</li>
                        <li>Enter term name (e.g., "Fall 2024", "Spring Semester")</li>
                        <li>Set start date and end date</li>
                        <li>Choose whether to set as active</li>
                        <li>Save the term</li>
                        <li>Note: Only one term can be active at a time</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="terms">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Setting active terms</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Activate a term for the current academic period.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Terms</strong> page</li>
                        <li>Find the term you want to activate</li>
                        <li>Click "Set Active" or toggle the active status</li>
                        <li>The previous active term will be deactivated automatically</li>
                        <li>Only one term can be active at a time</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports -->
    <div class="help-section">
        <h2 class="help-section-title">Reports & Analytics</h2>
        <div class="help-item" data-category="reports">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Accessing system reports</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">View comprehensive system-wide reports.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Reports</strong> page</li>
                        <li>Select the type of report you want to view</li>
                        <li>Apply filters (date range, course, user, etc.)</li>
                        <li>View statistics and analytics</li>
                        <li>Export reports if needed</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="help-section">
        <h2 class="help-section-title">Frequently Asked Questions</h2>
        <div class="help-faq-list">
            <div class="help-faq-item" onclick="toggleFAQ(this)">
                <div class="help-faq-question">
                    <span>Can I bulk import users?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    Currently, users must be created individually through the interface. For bulk operations, contact the development team for assistance with CSV imports or database operations.
                </div>
            </div>
            
            <div class="help-faq-item" onclick="toggleFAQ(this)">
                <div class="help-faq-question">
                    <span>How do I deactivate a user account?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    Go to the Users page, find the user, and change their status from "Active" to "Inactive". Inactive users cannot log in, but all their data (grades, submissions, etc.) is preserved in the system.
                </div>
            </div>
            
            <div class="help-faq-item" onclick="toggleFAQ(this)">
                <div class="help-faq-question">
                    <span>What reports are available?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    The Reports page provides user activity reports, course statistics, enrollment data, grade distributions, and system usage metrics. Reports can be filtered by date range, course, user role, and other criteria.
                </div>
            </div>
            
            <div class="help-faq-item" onclick="toggleFAQ(this)">
                <div class="help-faq-question">
                    <span>How do I manage academic terms?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    Go to the Terms page to create, edit, and activate academic terms. Only one term can be active at a time. When you activate a new term, the previous active term is automatically deactivated.
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="help-support-section">
        <h2 class="help-support-title">Need More Help?</h2>
        <p class="help-support-intro">We're here to help! Reach out to our support team:</p>
        <div class="help-support-info">
            <div class="help-support-item"><strong>Email:</strong> <a href="mailto:support@schoollms.com">support@schoollms.com</a></div>
            <div class="help-support-item"><strong>Response time:</strong> Within 24 hours</div>
            <div class="help-support-item"><strong>Available:</strong> Monday to Friday, 9 AM – 5 PM</div>
        </div>
    </div>
    
    <div class="no-results" id="noResults" style="display: none;">
        <div class="no-results-icon">🔍</div>
        <h3>No results found</h3>
        <p>Try different keywords or browse the sections above</p>
    </div>
</div>

<script>
    function toggleHelpItem(header) {
        const item = header.closest('.help-item');
        item.classList.toggle('expanded');
    }
    
    function toggleFAQ(item) {
        item.classList.toggle('expanded');
    }
    
    // Search functionality
    const searchInput = document.getElementById('helpSearch');
    const helpItems = document.querySelectorAll('.help-item, .help-faq-item');
    const noResults = document.getElementById('noResults');
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        let visibleCount = 0;
        
        helpItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matches = text.includes(searchTerm);
            
            if (matches) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });
        
        if (searchTerm && visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
        
        if (searchTerm) {
            helpItems.forEach(item => {
                if (!item.classList.contains('hidden')) {
                    item.classList.add('expanded');
                }
            });
        }
    });
</script>

<?php adminLayoutEnd(); ?>
