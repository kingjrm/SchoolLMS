<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/student_layout.php';

Auth::requireRole('student');

studentLayoutStart('help', 'Help');
?>
    <div class="help-container">
        <div class="help-header">
            <h1>Help & Support</h1>
            <p class="help-subtitle">Find answers, guides, and get support.</p>
        </div>

        <!-- Guides Section -->
        <div class="help-guides-grid">
            <div class="help-card">
                <h3 class="help-card-title">📚 Getting Started</h3>
                <p class="help-card-description">New to the LMS? Learn the basics.</p>
                <ul class="help-card-links">
                    <li><a href="#">How to enroll in a course</a></li>
                    <li><a href="#">Understanding your dashboard</a></li>
                    <li><a href="#">Setting up your profile</a></li>
                </ul>
            </div>

            <div class="help-card">
                <h3 class="help-card-title">✏️ Courses & Assignments</h3>
                <p class="help-card-description">Manage your coursework efficiently.</p>
                <ul class="help-card-links">
                    <li><a href="#">How to submit assignments</a></li>
                    <li><a href="#">Understanding grading</a></li>
                    <li><a href="#">Accessing course materials</a></li>
                </ul>
            </div>

            <div class="help-card">
                <h3 class="help-card-title">📋 Tasks & Organization</h3>
                <p class="help-card-description">Stay organized and on track.</p>
                <ul class="help-card-links">
                    <li><a href="#">Creating and managing tasks</a></li>
                    <li><a href="#">Setting task priorities</a></li>
                    <li><a href="#">Tracking your progress</a></li>
                </ul>
            </div>

            <div class="help-card">
                <h3 class="help-card-title">👤 Account & Profile</h3>
                <p class="help-card-description">Manage your account settings.</p>
                <ul class="help-card-links">
                    <li><a href="#">Updating your profile picture</a></li>
                    <li><a href="#">Changing your password</a></li>
                    <li><a href="#">Account preferences</a></li>
                </ul>
            </div>

            <div class="help-card">
                <h3 class="help-card-title">💬 Communication</h3>
                <p class="help-card-description">Connect with instructors and peers.</p>
                <ul class="help-card-links">
                    <li><a href="#">Reading announcements</a></li>
                    <li><a href="#">Contacting your instructor</a></li>
                    <li><a href="#">Course discussions</a></li>
                </ul>
            </div>

            <div class="help-card">
                <h3 class="help-card-title">⚙️ Troubleshooting</h3>
                <p class="help-card-description">Common issues and solutions.</p>
                <ul class="help-card-links">
                    <li><a href="#">Can't access a course?</a></li>
                    <li><a href="#">Grade not showing up</a></li>
                    <li><a href="#">Browser compatibility</a></li>
                </ul>
            </div>
        </div>

        <!-- FAQ Section -->
        <section class="help-faq-section">
            <h2 class="help-section-title">Frequently Asked Questions</h2>
            <div class="help-faq-list">
                <div class="help-faq-item">
                    <h4 class="help-faq-question">What's my learning timeline?</h4>
                    <p class="help-faq-answer">Your courses have specific start and end dates. Check your dashboard and course pages for deadlines.</p>
                </div>
                <div class="help-faq-item">
                    <h4 class="help-faq-question">How do I check my grades?</h4>
                    <p class="help-faq-answer">Go to the <strong>Grades</strong> page to see all your assignments and scores. Grades update once instructors mark your work.</p>
                </div>
                <div class="help-faq-item">
                    <h4 class="help-faq-question">Can I resubmit assignments?</h4>
                    <p class="help-faq-answer">Resubmission depends on your course policy. Contact your instructor if you need to resubmit.</p>
                </div>
                <div class="help-faq-item">
                    <h4 class="help-faq-question">How do I report a technical issue?</h4>
                    <p class="help-faq-answer">Email support@example.com with a description and screenshot. Our team will help within 24 hours.</p>
                </div>
            </div>
        </section>

        <!-- Contact Support -->
        <section class="help-support-section">
            <h2 class="help-support-title">Need More Help?</h2>
            <p class="help-support-intro">We're here to help! Reach out to our support team:</p>
            <div class="help-support-info">
                <div class="help-support-item"><strong>Email:</strong> <a href="mailto:support@example.com">support@example.com</a></div>
                <div class="help-support-item"><strong>Response time:</strong> Within 24 hours</div>
                <div class="help-support-item"><strong>Available:</strong> Monday to Friday, 9 AM – 5 PM</div>
            </div>
        </section>
    </div>
<?php studentLayoutEnd(); ?>
