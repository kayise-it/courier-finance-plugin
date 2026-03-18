<?php
/**
 * Sync buttons component: Push to Sheet | Pull from Sheet
 *
 * Usage: kit_render_sync_buttons(['entity' => 'drivers', 'label' => 'Drivers']);
 *
 * @param array $args ['entity' => 'drivers'|'customers'|'deliveries'|'waybills', 'label' => optional]
 */
if (!defined('ABSPATH')) {
    exit;
}

function kit_render_sync_buttons($args = []) {
$entity = $args['entity'] ?? 'drivers';
$label = $args['label'] ?? ucfirst($entity);
$id = 'kit-sync-' . esc_attr($entity);

static $sync_script_printed = false;
if (!$sync_script_printed) {
    $sync_script_printed = true;
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('kit_google_sheet_sync');
    ?>
<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('click', function(e) {
            var trigger = e.target.closest('.kit-sync-trigger');
            var push = e.target.closest('.kit-sync-push');
            var pull = e.target.closest('.kit-sync-pull');
            var dd = e.target.closest('.kit-sync-buttons');
            if (dd) dd.querySelectorAll('.kit-sync-dropdown').forEach(function(d) { if (d !== e.target.closest('.kit-sync-dropdown')) d.classList.add('hidden'); });
            if (trigger) {
                e.preventDefault();
                var wrap = trigger.closest('.kit-sync-buttons');
                var dropdown = wrap ? wrap.querySelector('.kit-sync-dropdown') : null;
                if (dropdown) dropdown.classList.toggle('hidden');
                return;
            }
            if (push || pull) {
                e.preventDefault();
                var wrap = (push || pull).closest('.kit-sync-buttons');
                if (!wrap) return;
                var dropdown = wrap.querySelector('.kit-sync-dropdown');
                if (dropdown) dropdown.classList.add('hidden');
                var entity = wrap.getAttribute('data-entity');
                var direction = push ? 'push' : 'pull';
                var status = wrap.querySelector('.kit-sync-status');
                if (status) status.textContent = 'Syncing...';
                var fd = new FormData();
                fd.append('action', 'kit_google_sheet_sync');
                fd.append('nonce', '<?php echo esc_js($nonce); ?>');
                fd.append('entity', entity);
                fd.append('direction', direction);
                fetch('<?php echo esc_url($ajax_url); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (status) status.textContent = data.success ? (data.data && data.data.message ? data.data.message : 'Done') : (data.data && data.data.message ? data.data.message : 'Failed');
                        if (data.success && typeof location !== 'undefined') setTimeout(function() { location.reload(); }, 1500);
                    })
                    .catch(function(err) {
                        if (status) status.textContent = 'Error: ' + (err && err.message ? err.message : 'Request failed');
                    });
            }
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.kit-sync-buttons')) {
                document.querySelectorAll('.kit-sync-dropdown').forEach(function(d) { d.classList.add('hidden'); });
            }
        });
    });
})();
</script>
    <?php
}
?>
<div class="kit-sync-buttons inline-flex items-center gap-1" id="<?php echo $id; ?>" data-entity="<?php echo esc_attr($entity); ?>">
    <?php if (class_exists('Courier_Google_Sheets_Sync') && Courier_Google_Sheets_Sync::can_sync()): ?>
    <div class="relative" role="group" aria-label="Sync <?php echo esc_attr($label); ?> with Google Sheet">
        <button type="button" class="kit-sync-trigger px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 inline-flex items-center gap-1.5"
                aria-expanded="false" aria-haspopup="true" title="Sync with Google Sheet">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Sync</span>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div class="kit-sync-dropdown absolute right-0 mt-1 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-50" role="menu">
            <div class="py-1">
                <button type="button" class="kit-sync-push w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2" data-direction="push" role="menuitem">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Push to Sheet
                </button>
                <button type="button" class="kit-sync-pull w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2" data-direction="pull" role="menuitem">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Pull from Sheet
                </button>
            </div>
            <?php
            $err_bulk = get_option('courier_last_sync_error_bulk', null);
            $err_waybill = get_option('courier_last_sync_error', null);
            $has_logs = (is_array($err_bulk) && !empty($err_bulk['message'])) || (is_array($err_waybill) && !empty($err_waybill['message']));
            if ($has_logs):
                ?>
            <div class="border-t border-gray-200 px-4 py-2 bg-gray-50 rounded-b-md">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Last error / logs</p>
                <?php if (is_array($err_bulk) && !empty($err_bulk['message'])): ?>
                <p class="text-xs text-red-700 mb-1"><?php echo esc_html($err_bulk['message']); ?></p>
                <p class="text-xs text-gray-500"><?php echo esc_html($err_bulk['direction'] ?? ''); ?> <?php echo esc_html($err_bulk['entity'] ?? ''); ?></p>
                <?php endif; ?>
                <?php if (is_array($err_waybill) && !empty($err_waybill['message'])): ?>
                <p class="text-xs text-red-700 mt-1">Waybill <?php echo esc_html($err_waybill['waybill_no'] ?? ''); ?>: <?php echo esc_html($err_waybill['message']); ?></p>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-2">Check PHP error log (e.g. <code>wp-content/debug.log</code>) for details.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <span class="kit-sync-status text-xs text-gray-500 ml-1" aria-live="polite"></span>
    <?php endif; ?>
</div>
<?php
}
