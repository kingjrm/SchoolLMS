<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/teacher_layout.php';

Auth::requireRole('teacher');

teacherLayoutStart('help', 'Help');
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
        <p>Find answers and guides for managing your courses and students.</p>
    </div>

    <!-- Search Bar -->
    <div class="help-search">
        <div class="help-search-icon">🔍</div>
        <input type="text" id="helpSearch" placeholder="Search for help topics, questions, or guides...">
    </div>

    <!-- Course Management -->
    <div class="help-section">
        <h2 class="help-section-title">Course Management</h2>
        <div class="help-item" data-category="courses">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Creating a new course</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Learn how to create and set up a new course.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Courses</strong> page</li>
                        <li>Click the "Create Course" or "Add New Course" button</li>
                        <li>Fill in course details: title, code, description</li>
                        <li>Set the course status (active/inactive)</li>
                        <li>Add course materials and resources</li>
                        <li>Save the course</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="courses">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Managing course materials</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Upload and organize materials for your students.</p>
                    <ol class="help-item-steps">
                        <li>Go to your course page</li>
                        <li>Navigate to the <strong>Materials</strong> section</li>
                        <li>Click "Upload Material" or "Add Material"</li>
                        <li>Choose to upload a file or add a link</li>
                        <li>Enter a title and description</li>
                        <li>Save the material</li>
                        <li>Students can now access it from their course page</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments & Grading -->
    <div class="help-section">
        <h2 class="help-section-title">Assignments & Grading</h2>
        <div class="help-item" data-category="grading">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Creating assignments</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Create assignments for your students.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Assignments</strong> page</li>
                        <li>Click "Create Assignment" or "Add New"</li>
                        <li>Select the course</li>
                        <li>Enter assignment title, description, and due date</li>
                        <li>Set maximum score</li>
                        <li>Add resources if needed (files or links)</li>
                        <li>Save the assignment</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="grading">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Grading student submissions</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Grade assignments and provide feedback to students.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Grades</strong> page</li>
                        <li>Use filters to find specific submissions (by course, assignment, or status)</li>
                        <li>Click on a submission to view it</li>
                        <li>Review the student's work and any attached files</li>
                        <li>Enter the score (out of maximum points)</li>
                        <li>Add feedback in the feedback textarea</li>
                        <li>Click "Submit Grade" or "Update Grade"</li>
                        <li>Students will see the grade immediately</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="grading">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Managing assignment resources</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Add files and links to assignments for student reference.</p>
                    <ol class="help-item-steps">
                        <li>When creating or editing an assignment</li>
                        <li>Scroll to the "Resources" section</li>
                        <li>Click "Add Resource"</li>
                        <li>Choose "File" to upload or "Link" for a URL</li>
                        <li>Enter a title and upload/enter the resource</li>
                        <li>Save the resource</li>
                        <li>Students can view and download resources when viewing the assignment</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Management -->
    <div class="help-section">
        <h2 class="help-section-title">Student Management</h2>
        <div class="help-item" data-category="students">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Viewing enrolled students</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">See who is enrolled in your courses.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Students</strong> page or <strong>Course Students</strong></li>
                        <li>Select a course from the dropdown or filter</li>
                        <li>View the list of enrolled students</li>
                        <li>See student names, enrollment dates, and status</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="help-item" data-category="students">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Communicating with students</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Stay in touch with your students.</p>
                    <ul class="help-item-steps">
                        <li><strong>Announcements:</strong> Post course-wide messages that all students see</li>
                        <li><strong>Private Messages:</strong> Respond to messages students send on assignment pages</li>
                        <li><strong>Feedback:</strong> Include detailed feedback when grading assignments</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcements -->
    <div class="help-section">
        <h2 class="help-section-title">Announcements</h2>
        <div class="help-item" data-category="announcements">
            <div class="help-item-header" onclick="toggleHelpItem(this)">
                <h3 class="help-item-title">Creating announcements</h3>
                <span class="help-item-icon">▼</span>
            </div>
            <div class="help-item-content">
                <div class="help-item-body">
                    <p class="help-item-description">Keep students informed with announcements.</p>
                    <ol class="help-item-steps">
                        <li>Go to the <strong>Announcements</strong> page</li>
                        <li>Click "Create Announcement" or "New Announcement"</li>
                        <li>Select the course</li>
                        <li>Enter a title and content</li>
                        <li>Check "Pin" if you want it to appear at the top</li>
                        <li>Click "Post" to publish</li>
                        <li>Students will see it on their announcements page</li>
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
                    <span>Can students see their grades immediately?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    Yes, once you submit a grade, students can immediately see it on their Grades page along with any feedback you provide.
                </div>
            </div>
            
            <div class="help-faq-item" onclick="toggleFAQ(this)">
                <div class="help-faq-question">
                    <span>Can I edit an assignment after students submit?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    You can edit assignment details, but be aware that students may have already started working on it. Consider creating a new assignment for major changes or clearly communicate any updates.
                </div>
            </div>
            
            <div class="help-faq-item" onclick="toggleFAQ(this)">
                <div class="help-faq-question">
                    <span>What file types can students upload?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    Students can upload PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP, PNG, JPG, and TXT files with a maximum size of 20MB per file.
                </div>
            </div>
            
            <div class="help-faq-item" onclick="toggleFAQ(this)">
                <div class="help-faq-question">
                    <span>How do I organize submissions for grading?</span>
                    <span class="help-faq-icon">▼</span>
                </div>
                <div class="help-faq-answer">
                    Use the filters on the Grades page to group by assignment or course, filter by status (ungraded/graded), and search by student name. This helps you manage large numbers of submissions efficiently.
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

<?php teacherLayoutEnd(); ?>
