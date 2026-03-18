<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <div class="<?php echo KIT_Commons::containerClasses(); ?>">
        <h1 class="wp-heading-inline">Help & Support</h1>
        <hr class="wp-header-end">

        <div class="help-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- User Manual -->
            <div class="help-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">User Manual</h3>
                <ul class="list-none p-0 m-0 space-y-2.5">
                    <li>
                        <?php echo KIT_Commons::renderButton('Getting Started Guide', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Complete User Manual', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Feature Documentation', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                </ul>
            </div>

            <!-- FAQ -->
            <div class="help-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Frequently Asked Questions</h3>
                <ul class="list-none p-0 m-0 space-y-2.5">
                    <li>
                        <?php echo KIT_Commons::renderButton('General Questions', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Technical Issues', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Billing Questions', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                </ul>
            </div>

            <!-- Video Tutorials -->
            <div class="help-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Video Tutorials</h3>
                <ul class="list-none p-0 m-0 space-y-2.5">
                    <li>
                        <?php echo KIT_Commons::renderButton('Basic Tutorials', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Advanced Features', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Tips & Tricks', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                </ul>
            </div>

            <!-- Contact Support -->
            <div class="help-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Support</h3>
                <ul class="list-none p-0 m-0 space-y-2.5">
                    <li>
                        <?php echo KIT_Commons::renderButton('Email Support', 'secondary', 'lg', ['href' => 'mailto:info@kayiseit.co.za', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Phone Support', 'secondary', 'lg', ['href' => 'tel:0877022625', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Live Chat', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                </ul>
            </div>

            <!-- System Status -->
            <div class="help-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">System Status</h3>
                <ul class="list-none p-0 m-0 space-y-2.5">
                    <li>
                        <?php echo KIT_Commons::renderButton('System Health', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Maintenance Schedule', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Update History', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                </ul>
            </div>

            <!-- Community -->
            <div class="help-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Community</h3>
                <ul class="list-none p-0 m-0 space-y-2.5">
                    <li>
                        <?php echo KIT_Commons::renderButton('User Forum', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Feature Requests', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                    <li>
                        <?php echo KIT_Commons::renderButton('Feedback', 'secondary', 'lg', ['href' => '#', 'fullWidth' => true]); ?>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Quick Support Info -->
        <div class="support-info mt-8 bg-slate-50 p-5 rounded-lg border-l-4 border-blue-600">
            <h3 class="text-lg font-semibold text-blue-600 mb-4">Need Immediate Help?</h3>
            <p class="m-0 mb-2.5 text-slate-600">
                <strong>Email:</strong> info@kayiseit.co.za<br>
                <strong>Phone:</strong> 0877022625<br>
                <strong>Hours:</strong> Monday - Friday, 8:00 AM - 6:00 PM (SAST)
            </p>
            <p class="m-0 text-slate-600">
                For urgent issues outside business hours, please email us and we'll respond as soon as possible.
            </p>
        </div>
    </div>
</div>