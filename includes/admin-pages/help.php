<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Help & Support</h1>
    <hr class="wp-header-end">

    <div class="help-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <!-- User Manual -->
        <div class="help-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #2563eb;">User Manual</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Getting Started Guide', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Complete User Manual', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Feature Documentation', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- FAQ -->
        <div class="help-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #059669;">Frequently Asked Questions</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('General Questions', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Technical Issues', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Billing Questions', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Video Tutorials -->
        <div class="help-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #dc2626;">Video Tutorials</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Basic Tutorials', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Advanced Features', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Tips & Tricks', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Contact Support -->
        <div class="help-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #7c3aed;">Contact Support</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Email Support', 'secondary', 'md', ['href' => 'mailto:info@kayiseit.co.za', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Phone Support', 'secondary', 'md', ['href' => 'tel:0877022625', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Live Chat', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- System Status -->
        <div class="help-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #ea580c;">System Status</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('System Health', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Maintenance Schedule', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Update History', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Community -->
        <div class="help-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #0891b2;">Community</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('User Forum', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Feature Requests', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Feedback', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>
    </div>

    <!-- Quick Support Info -->
    <div class="support-info" style="margin-top: 30px; background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #2563eb;">
        <h3 style="margin: 0 0 15px 0; color: #2563eb;">Need Immediate Help?</h3>
                    <p style="margin: 0 0 10px 0; color: #64748b;">
                <strong>Email:</strong> info@kayiseit.co.za<br>
                <strong>Phone:</strong> 0877022625<br>
                <strong>Hours:</strong> Monday - Friday, 8:00 AM - 6:00 PM (SAST)
            </p>
        <p style="margin: 0; color: #64748b;">
            For urgent issues outside business hours, please email us and we'll respond as soon as possible.
        </p>
    </div>
</div>