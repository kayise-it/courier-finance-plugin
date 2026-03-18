<?php
/**
 * Google Sheets connection test page.
 *
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Capability check
if (!current_user_can('kit_view_waybills')) {
    wp_die(__('You do not have sufficient permissions.'));
}

$configured = Courier_Google_Sheets::is_configured();
$error = null;
$rows = [];
$row_count = 0;

if ($configured) {
    try {
        $rows = Courier_Google_Sheets::get_values('', 'A1:AR10');
        $row_count = count($rows);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<div class="wrap">
    <h1>Google Sheets Test</h1>

    <?php if (!$configured): ?>
        <div class="notice notice-error">
            <p><strong>Not configured.</strong> Credentials file not found or not readable at:<br>
            <code><?php echo esc_html(Courier_Google_Sheets::get_credentials_path()); ?></code></p>
            <p>See <code>credentials/README.txt</code> in the plugin for setup instructions.</p>
            <hr style="margin:1em 0;">
            <p><strong>On production (e.g. www.08600africa.com):</strong></p>
            <ol style="list-style:decimal; margin-left:1.5em;">
                <li>The file <code>credentials/google-service-account.json</code> is not in the plugin zip (it is in .gitignore). You must upload it separately or place it on the server.</li>
                <li>In <code>wp-config.php</code> (above “That’s all, stop editing!”) add:<br>
                    <code>define('COURIER_GOOGLE_CREDENTIALS_PATH', '/absolute/path/on/server/to/google-service-account.json');</code><br>
                    Use the real path where you uploaded the JSON (e.g. in the plugin’s <code>credentials/</code> folder or outside the web root).</li>
                <li>Ensure the file is readable by the web server (e.g. <code>chmod 640</code> and correct owner).</li>
                <li>Share the Google Sheet with the service account email (e.g. <code>xxx@your-project.iam.gserviceaccount.com</code> from the JSON).</li>
            </ol>
        </div>
    <?php elseif ($error): ?>
        <div class="notice notice-error">
            <p><strong>API Error:</strong> <?php echo esc_html($error); ?></p>
            <p>Check that: (1) the sheet is shared with the service account email, (2) Google Sheets API is enabled.</p>
        </div>
    <?php else: ?>
        <div class="notice notice-success">
            <p><strong>Connected.</strong> Fetched <?php echo (int) $row_count; ?> rows from the spreadsheet.</p>
        </div>

        <?php if ($row_count > 0): ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <?php foreach ($rows[0] as $cell): ?>
                            <th><?php echo esc_html($cell); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 1; $i < $row_count; $i++): ?>
                        <tr>
                            <?php foreach ($rows[$i] as $cell): ?>
                                <td><?php echo esc_html($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
