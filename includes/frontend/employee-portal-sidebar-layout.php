<?php
/**
 * Portal layout with sidebar. Expects: $dashboard_url, $sidebar_menu, $current_section, $is_dashboard_page, $main_content.
 *
 * @package CourierFinancePlugin
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<style type="text/css">
/* No top navbar space on portal; theme can override with body { --kit-employee-header-height: 56px; } if needed */
:root { --kit-employee-header-height: 0; }
.kit-employee-dashboard-with-sidebar {
    display: flex !important;
    flex-direction: row !important;
    min-height: 100vh !important;
    width: 100vw !important;
    max-width: 100vw !important;
    margin-left: calc(-50vw + 50%) !important;
    margin-right: 0 !important;
    margin-top: 0 !important;
    padding: 0 !important;
    padding-top: var(--kit-employee-header-height, 0) !important;
    background: #f1f5f9 !important;
    box-sizing: border-box !important;
}
.kit-employee-dashboard-sidebar {
    width: 260px !important;
    min-width: 260px !important;
    max-width: 260px !important;
    display: flex !important;
    flex-shrink: 0 !important;
    flex-direction: column !important;
    position: sticky !important;
    top: 0 !important;
    height: 100vh !important;
    min-height: 100vh !important;
    background: #ffffff !important;
    border-right: 1px solid #e2e8f0 !important;
    box-sizing: border-box !important;
    visibility: visible !important;
    opacity: 1 !important;
}
.kit-employee-dashboard-main {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    display: block !important;
    padding-left: 0 !important;
}
.entry-content-page .kit-employee-dashboard-with-sidebar,
#content .kit-employee-dashboard-with-sidebar,
#container .kit-employee-dashboard-with-sidebar {
    display: flex !important;
    width: 100vw !important;
    margin-left: calc(-50vw + 50%) !important;
}
@media (max-width: 1023px) {
    .kit-employee-dashboard-sidebar {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        transform: translateX(-100%) !important;
        width: 260px !important;
        min-width: 260px !important;
        height: 100vh !important;
        min-height: 100vh !important;
        z-index: 100 !important;
    }
    .kit-employee-dashboard-with-sidebar.kit-employee-sidebar-open .kit-employee-dashboard-sidebar {
        transform: translateX(0) !important;
    }
    .kit-employee-dashboard-main {
        width: 100% !important;
        max-width: 100% !important;
        flex: 1 1 100% !important;
        min-width: 0 !important;
    }
}
</style>
<div class="kit-employee-dashboard-wrap kit-employee-dashboard-with-sidebar" id="kit-employee-dashboard-wrap">
    <button type="button" class="kit-employee-dashboard-menu-toggle" id="kit-employee-sidebar-toggle" aria-label="<?php esc_attr_e('Open menu', '08600-services-quotations'); ?>">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <aside class="kit-employee-dashboard-sidebar" id="kit-employee-dashboard-sidebar" aria-label="<?php esc_attr_e('Dashboard navigation', '08600-services-quotations'); ?>">
        <div class="kit-employee-dashboard-sidebar-inner">
            <div class="kit-employee-dashboard-sidebar-brand">
                <a href="<?php echo esc_url($dashboard_url); ?>" class="kit-employee-dashboard-sidebar-logo">08600</a>
                <span class="kit-employee-dashboard-sidebar-user"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
            </div>
            <nav class="kit-employee-dashboard-sidebar-nav">
                <?php
                foreach ($sidebar_menu as $item) {
                    if (!empty($item['children'])) {
                        $active = false;
                        foreach ($item['children'] as $child) {
                            if (!empty($child['url']) && strpos($child['url'], 'section=') !== false && $current_section !== '' && strpos($child['url'], 'section=' . $current_section) !== false) {
                                $active = true;
                                break;
                            }
                        }
                        ?>
                        <div class="kit-employee-dashboard-sidebar-group">
                            <span class="kit-employee-dashboard-sidebar-group-title"><?php echo esc_html($item['label']); ?></span>
                            <?php foreach ($item['children'] as $child) :
                                $url = !empty($child['url']) ? $child['url'] : '#';
                                $is_active = $current_section !== '' && strpos($url, 'section=' . $current_section) !== false;
                                ?>
                                <a href="<?php echo esc_url($url); ?>" class="kit-employee-dashboard-sidebar-link<?php echo $is_active ? ' is-active' : ''; ?>"><?php echo esc_html($child['label']); ?></a>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        continue;
                    }
                    $url = !empty($item['url']) ? $item['url'] : '#';
                    $is_active = ($item['label'] === __('Dashboard', '08600-services-quotations') && $is_dashboard_page) || ($current_section !== '' && strpos($url, 'section=' . $current_section) !== false);
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="kit-employee-dashboard-sidebar-link<?php echo $is_active ? ' is-active' : ''; ?>">
                        <?php echo esc_html($item['label']); ?>
                    </a>
                <?php } ?>
            </nav>
            <div class="kit-employee-dashboard-sidebar-footer">
                <button type="button" class="kit-employee-dashboard-sidebar-link kit-employee-dashboard-close-sidebar" id="kit-employee-close-sidebar" style="width:100%;text-align:left;border:none;background:transparent;cursor:pointer;font:inherit;"><?php esc_html_e('Close menu', '08600-services-quotations'); ?></button>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="kit-employee-dashboard-sidebar-link kit-employee-dashboard-sidebar-logout"><?php esc_html_e('Log out', '08600-services-quotations'); ?></a>
            </div>
        </div>
    </aside>
    <div class="kit-employee-dashboard-main">
        <div class="wrap kit-dashboard-wrap kit-dashboard-modern kit-employee-portal-section-wrap">
            <?php echo $main_content; ?>
        </div>
    </div>
</div>
<script>
(function() {
    var wrap = document.getElementById('kit-employee-dashboard-wrap');
    var toggle = document.getElementById('kit-employee-sidebar-toggle');
    var closeBtn = document.getElementById('kit-employee-close-sidebar');
    var sidebar = document.getElementById('kit-employee-dashboard-sidebar');
    function openSidebar() { if (wrap) wrap.classList.add('kit-employee-sidebar-open'); }
    function closeSidebar() { if (wrap) wrap.classList.remove('kit-employee-sidebar-open'); }
    if (toggle) toggle.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function(a) { a.addEventListener('click', function() { closeSidebar(); }); });
    }
})();
</script>
