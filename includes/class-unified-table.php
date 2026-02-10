<?php

/**
 * KIT Unified Table Class
 * 
 * A unified table system for displaying data with advanced features like
 * pagination, sorting, searching, and actions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Unified_Table
{
    /**
     * Render a table with infinite scroll enabled.
     * Simple implementation that shows all data.
     */
    public static function infinite($data, $columns, $options = [])
    {
        $defaults = [
            'title' => '',
            'subtitle' => '',
            'actions' => [],
            'searchable' => false,
            'sortable' => true,
            'exportable' => false,
            'bulk_actions' => false,
            'selectable' => false,
            'bulk_management' => false,
            'bulk_actions_list' => [], // Array of bulk actions: ['delete', 'export', 'status_active', 'status_inactive']
            'bulk_action_handler' => null, // Callback function to handle bulk actions
            'empty_message' => 'No data found',
            'table_class' => 'w-full table-auto border-collapse',
            'header_base_class' => 'px-3 py-2 text-xs font-semibold text-left uppercase tracking-wide',
            'cell_base_class' => 'px-3 py-2 text-sm text-gray-900 whitespace-normal break-words align-top',
            'index_cell_class' => 'px-3 py-2 text-sm font-medium text-gray-600 whitespace-nowrap text-center',
            'actions_cell_class' => 'px-3 py-2 text-sm font-medium text-gray-900 whitespace-normal break-words align-top flex flex-wrap items-center gap-2',
            'primary_action' => null,
            'items_per_page' => 100,
            'current_page' => 1,
            // Optional: callback to add attributes to each <tr>. Signature: function($row, $rowIndex): array
            'row_attrs_callback' => null,
            'groupby' => null,
            'group_heading_prefix' => '',
            'group_heading_cell_class' => 'px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 bg-gray-100',
            'group_heading_row_class' => 'bg-gray-50 border-0',
            'preserve_order' => false,
            'group_collapsible' => false,
            'group_collapsed' => false,
            'group_toggle_button_class' => 'w-full text-left flex items-center justify-between gap-2',
            'group_toggle_icon_class' => 'transition-transform duration-150 ease-in-out',
            'group_toggle_icon_collapsed' => 'rotate-0',
            'group_toggle_icon_expanded' => 'rotate-90',
            'dropdowns' => false, // Enable dropdown components
            'dropdown_config' => [], // Configuration for dropdown components (e.g., customer bulk invoice)
            'table_type' => false, // Enable view toggle buttons (grouped vs infinite scroll)
            'search_placeholder' => 'Search...', // Custom search placeholder text
            'search_filters' => [], // Custom search filter options: [['value' => 'field', 'label' => 'Label'], ...]
            'search_default_filter' => null, // Default filter value
        ];

        $options = array_merge($defaults, $options);

        // Store original groupby value for view toggling
        $original_groupby = $options['groupby'];

        // Ensure bulk_management is boolean - explicitly convert to boolean
        // Handle string 'true', boolean true, integer 1, etc.
        if (isset($options['bulk_management'])) {
            $options['bulk_management'] = filter_var($options['bulk_management'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $options['bulk_management'] = false;
        }

        // Show ALL data initially - no pagination for infinite scroll
        $total_items = count($data);
        $display_data = $data; // Show all items

        // Ensure newest entries (by created_at or ID) appear first unless caller preserves order
        if (!empty($display_data) && !$options['preserve_order']) {
            $sample = reset($display_data);
            $sampleArray = is_object($sample) ? get_object_vars($sample) : (array) $sample;

            $sortKey = null;
            if (array_key_exists('created_at', $sampleArray)) {
                $sortKey = 'created_at';
            } elseif (array_key_exists('updated_at', $sampleArray)) {
                $sortKey = 'updated_at';
            } elseif (array_key_exists('waybill_id', $sampleArray)) {
                $sortKey = 'waybill_id';
            } elseif (array_key_exists('id', $sampleArray)) {
                $sortKey = 'id';
            }

            if ($sortKey) {
                usort($display_data, function ($a, $b) use ($sortKey) {
                    $aVal = is_object($a) ? get_object_vars($a) : (array) $a;
                    $bVal = is_object($b) ? get_object_vars($b) : (array) $b;

                    $aValue = $aVal[$sortKey] ?? null;
                    $bValue = $bVal[$sortKey] ?? null;

                    if ($aValue === $bValue) {
                        return 0;
                    }

                    // Attempt to compare as timestamps/numbers first
                    $aNumeric = is_numeric($aValue) ? (float) $aValue : strtotime((string) $aValue);
                    $bNumeric = is_numeric($bValue) ? (float) $bValue : strtotime((string) $bValue);

                    if ($aNumeric !== false && $bNumeric !== false) {
                        return $bNumeric <=> $aNumeric; // Descending
                    }

                    return strcasecmp((string) $bValue, (string) $aValue);
                });
            }
        }

        // Check URL parameter for view preference
        $view_param = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : null;
        if ($options['table_type'] === true && $view_param === 'infinite') {
            // Override groupby when infinite view is requested
            $options['groupby'] = null;
        }

        $groupField = !empty($options['groupby']) ? $options['groupby'] : null;
        if ($groupField) {
            $groupedDisplayData = [];
            $currentGroupLabel = null;
            $currentGroupId = '';
            $groupIndex = 0;
            $groupCounts = []; // Track counts per group
            $groupHeadersCreated = []; // Track which group headers we've already created

            // First pass: count items per group
            foreach ($display_data as $row) {
                $rowArray = is_object($row) ? get_object_vars($row) : (array) $row;
                $groupValue = $rowArray[$groupField] ?? '';

                if (is_scalar($groupValue)) {
                    $groupLabel = (string) $groupValue;
                } elseif (is_object($groupValue) && method_exists($groupValue, '__toString')) {
                    $groupLabel = (string) $groupValue;
                } else {
                    $groupLabel = '';
                }

                if ($groupLabel === '') {
                    $groupLabel = 'Unassigned City';
                }

                if (!isset($groupCounts[$groupLabel])) {
                    $groupCounts[$groupLabel] = 0;
                }
                $groupCounts[$groupLabel]++;
            }

            // Always sort by group field when grouping to ensure all items of the same group are together
            // This is necessary for proper grouping even if preserve_order is true
            // Also sort by customer_name within each group
            usort($display_data, function ($a, $b) use ($groupField) {
                $aArray = is_object($a) ? get_object_vars($a) : (array) $a;
                $bArray = is_object($b) ? get_object_vars($b) : (array) $b;

                $aValue = $aArray[$groupField] ?? '';
                $bValue = $bArray[$groupField] ?? '';

                if (is_scalar($aValue)) {
                    $aLabel = (string) $aValue;
                } elseif (is_object($aValue) && method_exists($aValue, '__toString')) {
                    $aLabel = (string) $aValue;
                } else {
                    $aLabel = '';
                }

                if (is_scalar($bValue)) {
                    $bLabel = (string) $bValue;
                } elseif (is_object($bValue) && method_exists($bValue, '__toString')) {
                    $bLabel = (string) $bValue;
                } else {
                    $bLabel = '';
                }

                if ($aLabel === '') $aLabel = 'Unassigned City';
                if ($bLabel === '') $bLabel = 'Unassigned City';

                // First sort by group field (city)
                $groupCompare = strcasecmp($aLabel, $bLabel);
                if ($groupCompare !== 0) {
                    return $groupCompare;
                }

                // Sort by customer_name

                // If both same type, sort by customer_name
                $aCustomerName = $aArray['customer_name'] ?? '';
                $bCustomerName = $bArray['customer_name'] ?? '';

                if (is_scalar($aCustomerName)) {
                    $aCustomerLabel = (string) $aCustomerName;
                } elseif (is_object($aCustomerName) && method_exists($aCustomerName, '__toString')) {
                    $aCustomerLabel = (string) $aCustomerName;
                } else {
                    $aCustomerLabel = '';
                }

                if (is_scalar($bCustomerName)) {
                    $bCustomerLabel = (string) $bCustomerName;
                } elseif (is_object($bCustomerName) && method_exists($bCustomerName, '__toString')) {
                    $bCustomerLabel = (string) $bCustomerName;
                } else {
                    $bCustomerLabel = '';
                }

                return strcasecmp($aCustomerLabel, $bCustomerLabel);
            });

            // Second pass: build grouped data with counts
            foreach ($display_data as $row) {
                $rowArray = is_object($row) ? get_object_vars($row) : (array) $row;
                $groupValue = $rowArray[$groupField] ?? '';

                if (is_scalar($groupValue)) {
                    $groupLabel = (string) $groupValue;
                } elseif (is_object($groupValue) && method_exists($groupValue, '__toString')) {
                    $groupLabel = (string) $groupValue;
                } else {
                    $groupLabel = '';
                }

                if ($groupLabel === '') {
                    $groupLabel = 'Unassigned City';
                }

                // Only create a new group header if we haven't seen this group label yet
                if (!isset($groupHeadersCreated[$groupLabel])) {
                    $groupIndex++;
                    $currentGroupId = 'group-' . $groupIndex;
                    $groupCount = $groupCounts[$groupLabel] ?? 0;
                    $groupedDisplayData[] = [
                        '__group_row'   => true,
                        '__group_label' => $groupLabel,
                        '__group_id'    => $currentGroupId,
                        '__group_collapsed' => !empty($options['group_collapsed']),
                        '__group_count' => $groupCount,
                    ];
                    $groupHeadersCreated[$groupLabel] = $currentGroupId;
                    $currentGroupLabel = $groupLabel;
                } else {
                    // Use the existing group ID for this label
                    $currentGroupId = $groupHeadersCreated[$groupLabel];
                }

                if (is_array($row)) {
                    $row['__group_id'] = $currentGroupId;
                } elseif (is_object($row)) {
                    $row->__group_id = $currentGroupId;
                }

                $groupedDisplayData[] = $row;
            }

            $display_data = $groupedDisplayData;
        }

        $hasGroupRows = !empty($groupField);

        // Build unique IDs for infinite scroll
        $table_id = 'kit-infinite-table-' . uniqid();
        $container_id = 'kit-infinite-wrap-' . uniqid();

        ob_start();
?>
        <div id="<?php echo esc_attr($container_id); ?>" class="w-full bg-white rounded-xl shadow-sm border border-gray-200" style="box-sizing: content-box;">
            <?php if ($options['title'] || $options['subtitle'] || $options['table_type'] || !empty($options['primary_action'])): ?>
                <div class="px-6 pt-6 pb-4 mb-3 grid grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <?php if ($options['title']): ?>
                            <h3 class="text-lg font-semibold text-gray-900 truncate"><?php echo esc_html($options['title']); ?></h3>
                        <?php endif; ?>
                        <?php if ($options['subtitle']): ?>
                            <p class="text-sm text-gray-600 ml-2"><?php echo esc_html($options['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <?php if (!empty($options['primary_action'])): ?>
                            <!-- Primary Action Button -->
                            <a href="<?php echo esc_url($options['primary_action']['href'] ?? '#'); ?>"
                                class="<?php echo esc_attr($options['primary_action']['class'] ?? 'px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition'); ?>">
                                <?php echo esc_html($options['primary_action']['label'] ?? 'Add New'); ?>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($options['dropdowns']) && $options['dropdowns'] === true): ?>
                            <?php
                            // Include customer bulk invoice dropdown component if configured
                            if (!empty($options['dropdown_config']['customer_bulk_invoice'])) {
                                $dropdown_path = dirname(__FILE__) . '/components/customerBulkInvoiceDropdown.php';
                                if (file_exists($dropdown_path)) {
                                    require_once $dropdown_path;

                                    $dropdown_config = $options['dropdown_config']['customer_bulk_invoice'];
                                    // Merge with default data from table
                                    if (!isset($dropdown_config['data'])) {
                                        $dropdown_config['data'] = $data;
                                    }

                                    echo render_customer_bulk_invoice_dropdown($dropdown_config);
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($display_data)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-sm text-gray-500"><?php echo wp_kses_post($options['empty_message']); ?></p>
                </div>
            <?php else: ?>
                <!-- Header Row: View Toggles and Search -->
                <div class="flex items-center justify-between gap-4 pt-6 pb-4 px-6 border-t border-gray-100">
                    <div class="flex-shrink-0">
                        <?php if ($options['table_type'] === true): ?>
                            <!-- View Toggle Buttons -->
                            <div class="inline-flex gap-4" role="group" aria-label="Table view toggle">
                                <?php
                                // Grouped view button
                                $grouped_icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>';
                                echo KIT_Commons::renderButton('Grouped by City', 'primary', 'lg', [
                                    'id' => 'view-toggle-grouped-' . esc_attr($table_id),
                                    'type' => 'button',
                                    'icon' => $grouped_icon,
                                    'iconPosition' => 'left',
                                    'classes' => 'view-toggle-btn rounded-none border-r-0 !rounded-none',
                                    'data-view' => 'grouped',
                                    'data-table-id' => $table_id,
                                    'ariaLabel' => 'Group by City',

                                ]);

                                // Infinite scroll button
                                $infinite_icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
                                echo KIT_Commons::renderButton('All Waybills', 'secondary', 'lg', [
                                    'id' => 'view-toggle-infinite-' . esc_attr($table_id),
                                    'type' => 'button',
                                    'icon' => $infinite_icon,
                                    'iconPosition' => 'left',
                                    'classes' => 'view-toggle-btn rounded-none !rounded-none',
                                    'data-view' => 'infinite',
                                    'data-table-id' => $table_id,
                                    'ariaLabel' => 'Infinite Scroll (Newest First)'
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($options['searchable'] === true): ?>
                        <!-- Search with Filter Dropdown -->
                        <div class="flex items-center gap-3 flex-1 max-w-2xl ml-auto">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" style="z-index: 10;">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text"
                                    id="infinite-table-search-<?php echo esc_attr($table_id); ?>"
                                    class="block w-full pr-3 py-2.5 h-10 text-sm border border-gray-300 rounded-md bg-white placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow"
                                    style="padding-left: 2.75rem;"
                                    placeholder="<?php echo esc_attr($options['search_placeholder']); ?>"
                                    autocomplete="off"
                                    aria-label="<?php echo esc_attr($options['search_placeholder']); ?>">
                            </div>
                            <?php if (!empty($options['search_filters'])): ?>
                                <div class="relative flex-shrink-0">
                                    <?php
                                    $select_class = class_exists('KIT_Commons') ? KIT_Commons::selectClass() : 'text-xs w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white';
                                    $default_filter = $options['search_default_filter'] ?? ($options['search_filters'][0]['value'] ?? '');
                                    ?>
                                    <select id="search-filter-type-<?php echo esc_attr($table_id); ?>"
                                        class="<?php echo esc_attr($select_class); ?> w-40 h-10 text-sm cursor-pointer pr-9"
                                        style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none;">
                                        <?php foreach ($options['search_filters'] as $filter): ?>
                                            <option value="<?php echo esc_attr($filter['value']); ?>" <?php echo ($filter['value'] === $default_filter) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($filter['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none" style="z-index: 10;">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php echo KIT_Commons::renderButton('', 'ghost', 'sm', [
                                'type' => 'button',
                                'id' => 'clear-infinite-search-' . esc_attr($table_id),
                                'classes' => 'inline-flex items-center justify-center w-10 h-10 border border-gray-300 rounded-md bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors flex-shrink-0',
                                'title' => 'Clear search',
                                'ariaLabel' => 'Clear search',
                                'iconOnly' => true,
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                            ]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bulk Actions Bar -->
                <?php if ($options['bulk_management'] === true): ?>
                    <div id="bulk-actions-bar-<?php echo esc_attr($table_id); ?>" class="mb-4 mx-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg shadow-sm transition-all duration-300" style="display: none;">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span id="bulk-selected-count-<?php echo esc_attr($table_id); ?>" class="text-sm font-semibold text-gray-900">0 selected</span>
                                </div>
                                <div class="h-6 w-px bg-gray-300"></div>
                                <div class="flex gap-2">
                                    <?php
                                    $bulk_actions = $options['bulk_actions_list'] ?? [];
                                    if (empty($bulk_actions)) {
                                        // Default actions if none specified
                                        $bulk_actions = ['delete', 'export'];
                                    }
                                    foreach ($bulk_actions as $action):
                                        $action_label = '';
                                        $action_class = '';
                                        $action_id = '';
                                        switch ($action) {
                                            case 'delete':
                                                $action_label = 'Delete';
                                                $action_class = 'bg-red-600 hover:bg-red-700 text-white';
                                                $action_id = 'bulk-delete-' . $table_id;
                                                break;
                                            case 'export':
                                                $action_label = 'Export to PDF';
                                                $action_class = 'bg-blue-600 hover:bg-blue-700 text-white';
                                                $action_id = 'bulk-export-' . $table_id;
                                                break;
                                            case 'status_active':
                                                $action_label = 'Set Active';
                                                $action_class = 'bg-green-600 hover:bg-green-700 text-white';
                                                $action_id = 'bulk-active-' . $table_id;
                                                break;
                                            case 'status_inactive':
                                                $action_label = 'Set Inactive';
                                                $action_class = 'bg-gray-600 hover:bg-gray-700 text-white';
                                                $action_id = 'bulk-inactive-' . $table_id;
                                                break;
                                        }
                                        if ($action_label):
                                            $bulk_icon = '';
                                            if ($action === 'delete') {
                                                $bulk_icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>';
                                            } elseif ($action === 'export') {
                                                $bulk_icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>';
                                            } elseif ($action === 'status_active') {
                                                $bulk_icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
                                            } elseif ($action === 'status_inactive') {
                                                $bulk_icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
                                            }
                                            echo KIT_Commons::renderButton($action_label, 'primary', 'sm', [
                                                'type' => 'button',
                                                'id' => $action_id,
                                                'classes' => 'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all duration-200 ' . $action_class . ' disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-md active:scale-95',
                                                'data-bulk-action' => $action,
                                                'disabled' => true,
                                                'icon' => $bulk_icon,
                                                'iconPosition' => 'left',
                                            ]);
                                    ?>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </div>
                            <?php echo KIT_Commons::renderButton('Clear Selection', 'ghost', 'sm', [
                                'type' => 'button',
                                'id' => 'bulk-clear-selection-' . esc_attr($table_id),
                                'classes' => 'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white rounded-md transition-colors',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                                'iconPosition' => 'left',
                            ]); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Table Container -->
                <div class="px-6 overflow-x-auto">
                            <table id="<?php echo esc_attr($table_id); ?>" class="<?php echo esc_attr($options['table_class']); ?>" style="width:100%;" <?php if ($hasGroupRows): ?> data-has-group-rows="1" <?php endif; ?> data-original-groupby="<?php echo esc_attr($original_groupby ?? ''); ?>" data-current-view="<?php echo ($view_param === 'infinite' || !$hasGroupRows) ? 'infinite' : 'grouped'; ?>">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <?php if ($options['bulk_management'] === true): ?>
                                            <!-- Bulk selection checkbox header -->
                                            <th class="<?php echo esc_attr($options['header_base_class']); ?> w-12 text-center" style="width: 48px;">
                                                <input type="checkbox"
                                                    id="bulk-select-all-<?php echo esc_attr($table_id); ?>"
                                                    class="bulk-select-all-checkbox w-4 h-4 rounded border-0 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer transition-all"
                                                    title="Select all"
                                                    aria-label="Select all rows"
                                                    style="display: inline-block; margin: 0; cursor: pointer; pointer-events: auto;">
                                            </th>
                                        <?php endif; ?>
                                        <!-- Index column header -->
                                        <th class="<?php echo esc_attr($options['header_base_class']); ?> w-12 min-w-12 text-center">#</th>
                                        <?php foreach ($columns as $key => $column): ?>
                                            <?php
                                            $label = is_array($column) ? $column['label'] : $column;
                                            $columnSortable = is_array($column) && isset($column['sortable']) ? $column['sortable'] : $options['sortable'];
                                            // Skip sorting for checkbox column
                                            if ($key === 'checkbox') {
                                                $columnSortable = false;
                                            }
                                            $headerClass = $options['header_base_class'];
                                            if (is_array($column) && !empty($column['header_class'])) {
                                                $headerClass = trim($headerClass . ' ' . $column['header_class']);
                                            }
                                            // Check if header should be right-aligned
                                            $isRightAligned = strpos($headerClass, 'text-right') !== false;
                                            $flexJustify = $isRightAligned ? 'justify-end' : '';
                                            ?>
                                            <th<?php if ($columnSortable): ?> data-column="<?php echo esc_attr($key); ?>" <?php endif; ?> class="<?php echo esc_attr($headerClass); ?>">
                                                <?php if ($columnSortable): ?>
                                                    <?php echo KIT_Commons::renderButton($label, 'ghost', 'sm', [
                                                        'type' => 'button',
                                                        'classes' => 'sortable-header flex items-center gap-2 border-0 shadow-none ' . esc_attr($flexJustify) . ' text-gray-700 hover:text-gray-900 font-semibold transition-colors group w-full',
                                                        'icon' => '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />',
                                                        'iconPosition' => 'right',
                                                    ]); ?>
                                                <?php else: ?>
                                                    <span class="flex items-center gap-1 <?php echo esc_attr($flexJustify); ?> text-gray-700 font-semibold whitespace-normal break-words"><?php echo esc_html($label); ?></span>
                                                <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                            <?php if (!empty($options['actions'])): ?>
                                                <?php
                                                // Get actions header class from options if provided
                                                if (isset($options['actions_header_class'])) {
                                                    $actions_header_class = $options['actions_header_class'];
                                                } else {
                                                    $actions_header_class = $options['header_base_class'];
                                                }
                                                ?>
                                                <th class="<?php echo esc_attr($actions_header_class); ?>">Actions</th>
                                            <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php
                                    $visibleRowCounter = 0;
                                    $totalColumns = (($options['bulk_management'] === true) ? 1 : 0) + 1 + count($columns) + (!empty($options['actions']) ? 1 : 0);
                                    ?> 
                                    <?php foreach ($display_data as $rowIndex => $row): ?>
                                        <?php
                                        if ((is_array($row) && !empty($row['__group_row'])) || (is_object($row) && !empty($row->__group_row))) {
                                            $groupLabel = is_array($row) ? ($row['__group_label'] ?? 'Unassigned City') : ($row->__group_label ?? 'Unassigned City');
                                            $groupId = is_array($row) ? ($row['__group_id'] ?? '') : ($row->__group_id ?? '');
                                            $groupCollapsed = is_array($row) ? (!empty($row['__group_collapsed'])) : (!empty($row->__group_collapsed));
                                            $groupCount = is_array($row) ? ($row['__group_count'] ?? 0) : ($row->__group_count ?? 0);
                                            $isCollapsible = !empty($options['group_collapsible']);
                                            $headingContent = trim(($options['group_heading_prefix'] ?? '') . $groupLabel);
                                            $headingCellClass = $options['group_heading_cell_class'];
                                            if ($isCollapsible) {
                                                $headingCellClass = 'p-0 align-middle bg-transparent border-0';
                                            }

                                            $gradientButtonClasses = 'inline-flex w-full items-center justify-between text-left font-semibold px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 active:from-blue-800 active:to-indigo-800 text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2';
                                            if (class_exists('KIT_Commons') && method_exists('KIT_Commons', 'buttonClass') && method_exists('KIT_Commons', 'buttonPrimary')) {
                                                $baseButton = KIT_Commons::buttonClass();
                                                $baseButton = str_replace('justify-center', 'justify-between', $baseButton);
                                                $baseButton = str_replace('gap-2', 'gap-3', $baseButton);
                                                $baseButton .= ' text-left w-full';
                                                $typeButton = KIT_Commons::buttonPrimary('sm', true, true);
                                                $gradientButtonClasses = trim($baseButton . ' ' . $typeButton . ' group-toggle');
                                            } else {
                                                $gradientButtonClasses .= ' group-toggle';
                                            }

                                            $iconClasses = 'group-toggle-icon w-4 h-4 text-white transition-transform duration-200';
                                        ?>
                                            <tr class="<?php echo esc_attr($options['group_heading_row_class']); ?>" data-group-row="1" <?php if ($groupId): ?> data-group-id="<?php echo esc_attr($groupId); ?>" <?php endif; ?> data-collapsed="<?php echo $groupCollapsed ? '1' : '0'; ?>">
                                                <td colspan="<?php echo esc_attr($totalColumns); ?>" class="<?php echo esc_attr($headingCellClass); ?>">
                                                    <?php if ($isCollapsible): ?>
                                                        <?php
                                                        $groupBtnLabel = $groupCount > 0 ? '<span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 text-xs font-semibold text-white bg-red-600 rounded-full leading-none">' . esc_html($groupCount) . '</span> ' . esc_html($headingContent) : 'Group';
                                                        echo KIT_Commons::renderButton($groupBtnLabel, 'primary', 'sm', [
                                                            'type' => 'button',
                                                            'classes' => $gradientButtonClasses,
                                                            'data-group-toggle' => $groupId,
                                                            'ariaExpanded' => $groupCollapsed ? 'false' : 'true',
                                                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />',
                                                            'iconPosition' => 'right',
                                                            'rawHtml' => $groupCount > 0,
                                                        ]);
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center font-semibold text-gray-700 gap-2">
                                                            <?php if ($groupCount > 0): ?>
                                                                <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 text-xs font-semibold text-white bg-red-600 rounded-full">
                                                                    <?php echo esc_html($groupCount); ?>
                                                                </span>
                                                                <?php echo esc_html($headingContent); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php
                                            continue;
                                        }

                                        $visibleRowCounter++;

                                        $rowAttrStr = '';
                                        if (is_callable($options['row_attrs_callback'])) {
                                            $attrs = (array) call_user_func($options['row_attrs_callback'], $row, $visibleRowCounter - 1);
                                            foreach ($attrs as $attrKey => $attrVal) {
                                                $rowAttrStr .= ' ' . esc_attr($attrKey) . '="' . esc_attr((string) $attrVal) . '"';
                                            }
                                        }
                                        $rowGroupId = is_array($row) ? ($row['__group_id'] ?? '') : (is_object($row) ? ($row->__group_id ?? '') : '');
                                        if (!empty($rowGroupId)) {
                                            $rowAttrStr .= ' data-group-id="' . esc_attr($rowGroupId) . '"';
                                        }


                                        // Add data attributes for search functionality
                                        $waybillNo = is_array($row) ? ($row['waybill_no'] ?? $row['waybill_no_raw'] ?? '') : ($row->waybill_no ?? '');
                                        $customerName = is_array($row) ? ($row['customer_name'] ?? '') : ($row->customer_name ?? '');
                                        $email = is_array($row) ? ($row['email'] ?? $row['email_address'] ?? '') : ($row->email ?? $row->email_address ?? '');
                                        $destination = is_array($row) ? ($row['destination'] ?? $row['city'] ?? '') : ($row->destination ?? $row->city ?? '');

                                        // Driver-specific fields
                                        $driverName = is_array($row) ? ($row['name'] ?? '') : ($row->name ?? '');
                                        $phone = is_array($row) ? ($row['phone'] ?? '') : ($row->phone ?? '');
                                        $licenseNumber = is_array($row) ? ($row['license_number'] ?? '') : ($row->license_number ?? '');

                                        if ($waybillNo) {
                                            $rowAttrStr .= ' data-waybill-no="' . esc_attr($waybillNo) . '"';
                                        }
                                        if ($customerName) {
                                            $rowAttrStr .= ' data-customer-name="' . esc_attr($customerName) . '"';
                                        }
                                        if ($email) {
                                            $rowAttrStr .= ' data-email="' . esc_attr($email) . '"';
                                        }
                                        if ($destination) {
                                            $rowAttrStr .= ' data-destination="' . esc_attr($destination) . '"';
                                        }
                                        if ($driverName) {
                                            $rowAttrStr .= ' data-name="' . esc_attr($driverName) . '"';
                                        }
                                        if ($phone && $phone !== 'N/A') {
                                            $rowAttrStr .= ' data-phone="' . esc_attr($phone) . '"';
                                        }
                                        if ($licenseNumber && $licenseNumber !== 'N/A') {
                                            $rowAttrStr .= ' data-license-number="' . esc_attr($licenseNumber) . '"';
                                        }

                                        // Row styling
                                        $rowClass = 'hover:bg-blue-50/50 transition-colors duration-150';
                                        ?>
                                        <tr<?php echo $rowAttrStr; ?> class="<?php echo esc_attr($rowClass); ?>" data-row-id="<?php echo esc_attr(is_array($row) ? ($row['id'] ?? '') : (is_object($row) ? ($row->id ?? '') : '')); ?>">
                                            <?php if ($options['bulk_management'] === true): ?>
                                                <!-- Bulk selection checkbox cell -->
                                                <td class="<?php echo esc_attr($options['cell_base_class']); ?> w-12 text-center" style="width: 48px;">
                                                    <?php
                                                    // Use waybill_no (waybill number) as the checkbox value, not the database ID
                                                    if (is_array($row)) {
                                                        $row_id = $row['waybill_no'] ?? $row['waybill_no_raw'] ?? '';
                                                    } else {
                                                        $row_id = $row->waybill_no ?? '';
                                                    }
                                                    ?>
                                                    <input type="checkbox"
                                                        class="bulk-row-checkbox w-4 h-4 rounded border-0 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer transition-all"
                                                        value="<?php echo esc_attr($row_id); ?>"
                                                        data-row-id="<?php echo esc_attr($row_id); ?>"
                                                        aria-label="Select row"
                                                        style="display: inline-block; margin: 0; cursor: pointer; pointer-events: auto;">
                                                </td>
                                            <?php endif; ?>
                                            <!-- Index column cell -->
                                            <td class="<?php echo esc_attr($options['index_cell_class']); ?>"><?php echo esc_html($visibleRowCounter); ?></td>
                                            <?php foreach ($columns as $key => $column): ?>
                                                <?php
                                                $cellClass = $options['cell_base_class'];
                                                if (is_array($column) && isset($column['cell_class'])) {
                                                    $cellClass = trim($cellClass . ' ' . $column['cell_class']);
                                                }
                                                ?>
                                                <td class="<?php echo esc_attr($cellClass); ?>">
                                                    <?php
                                                    // Simple, robust value extraction
                                                    $value = '';

                                                    if (is_array($row)) {
                                                        $value = $row[$key] ?? '';
                                                    } elseif (is_object($row)) {
                                                        $value = $row->$key ?? '';
                                                    }

                                                    // Enforce price visibility for 'total' column
                                                    if ($key === 'total' && class_exists('KIT_User_Roles') && !KIT_User_Roles::can_see_prices()) {
                                                        $value = '***';
                                                    }

                                                    if (is_array($column) && isset($column['callback'])) {
                                                        echo $column['callback']($value, $row, $visibleRowCounter - 1);
                                                    } else {
                                                        echo esc_html($value);
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <?php if (!empty($options['actions'])): ?>
                                                <td class="<?php echo esc_attr($options['actions_cell_class']); ?>">
                                                    <?php foreach ($options['actions'] as $action): ?>
                                                        <?php
                                                        $href = $action['href'] ?? '#';
                                                        $class = $action['class'] ?? '';
                                                        $onclick = isset($action['onclick']) ? 'onclick="' . esc_attr($action['onclick']) . '"' : '';
                                                        $titleAttr = isset($action['title']) ? 'title="' . esc_attr($action['title']) . '" aria-label="' . esc_attr($action['title']) . '"' : '';
                                                        $target = isset($action['target']) ? 'target="' . esc_attr($action['target']) . '" rel="noopener"' : '';

                                                        // Use callback if provided, otherwise replace placeholders in href
                                                        if (isset($action['callback']) && is_callable($action['callback'])) {
                                                            $href = call_user_func($action['callback'], $href, $row, $visibleRowCounter - 1);
                                                        } else {
                                                            // Replace placeholders in href
                                                            if (is_array($row)) {
                                                                foreach ($row as $placeholder => $value) {
                                                                    $href = str_replace('{' . $placeholder . '}', $value, $href);
                                                                }
                                                            } elseif (is_object($row)) {
                                                                foreach ($row as $placeholder => $value) {
                                                                    $value = $value ?? ''; // Handle null values
                                                                    $href = str_replace('{' . $placeholder . '}', $value, $href);
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <a href="<?php echo esc_url($href); ?>"
                                                            <?php echo $class ? 'class="' . esc_attr($class) . '"' : ''; ?>
                                                            <?php echo $onclick; ?> <?php echo $titleAttr; ?> <?php echo $target; ?>>
                                                            <?php
                                                            if (isset($action['is_html']) && $action['is_html']) {
                                                                echo $action['label'];
                                                            } else {
                                                                echo esc_html($action['label']);
                                                            }
                                                            ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </td>
                                            <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
            <?php endif; ?>
        </div>

        <!-- Infinite scroll specific JavaScript -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const table = document.getElementById('<?php echo esc_js($table_id); ?>');
                if (!table) {
                    return;
                }

                const collapsedGroups = new Set();
                const searchInput = <?php echo $options['searchable'] ? "document.getElementById('infinite-table-search-" . esc_js($table_id) . "')" : 'null'; ?>;
                const searchFilterType = <?php echo $options['searchable'] ? "document.getElementById('search-filter-type-" . esc_js($table_id) . "')" : 'null'; ?>;
                const clearSearchBtn = <?php echo $options['searchable'] ? "document.getElementById('clear-infinite-search-" . esc_js($table_id) . "')" : 'null'; ?>;
                const hasBulkManagement = <?php echo ($options['bulk_management'] === true) ? 'true' : 'false'; ?>;
                const enableViewToggle = <?php echo ($options['table_type'] === true) ? 'true' : 'false'; ?>;
                const originalGroupby = table.dataset.originalGroupby || '';
                const tableData = <?php echo json_encode($data); ?>;
                const tableColumns = <?php echo json_encode($columns); ?>;
                const tableOptions = <?php echo json_encode($options); ?>;

                const reindexRows = () => {
                    let index = 1;
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        if (row.dataset.groupRow === '1') {
                            return;
                        }
                        if (row.style.display === 'none') {
                            return;
                        }
                        // Skip checkbox cell if bulk_management is enabled (first cell), index is second cell
                        const indexCellIndex = hasBulkManagement ? 1 : 0;
                        const indexCell = row.children[indexCellIndex];
                        if (indexCell && indexCell.tagName === 'TD') {
                            // Only update if it's not the checkbox cell (check for checkbox input)
                            if (!indexCell.querySelector('input[type="checkbox"]')) {
                                indexCell.textContent = index++;
                            }
                        }
                    });
                };

                // Function to get cell value by column key
                function getCellValueByColumn(row, columnKey) {
                    // Get column index from header
                    const headerRow = table.querySelector('thead tr');
                    if (!headerRow) return '';

                    const headers = Array.from(headerRow.querySelectorAll('th'));
                    let columnIndex = -1;

                    headers.forEach((th, index) => {
                        if (th.getAttribute('data-column') === columnKey) {
                            columnIndex = index;
                        }
                    });

                    if (columnIndex === -1) {
                        // Fallback: try to find by text content
                        headers.forEach((th, index) => {
                            const label = th.textContent.trim().toLowerCase();
                            const keyMap = {
                                'waybill': 'waybill_no',
                                'waybill #': 'waybill_no',
                                'customer': 'customer_name',
                                'customer name': 'customer_name',
                                'name': 'customer_name',
                                'email': 'email',
                                'destination': 'destination',
                                'city': 'destination'
                            };
                            for (let key in keyMap) {
                                if (label.includes(key) && keyMap[key] === columnKey) {
                                    columnIndex = index;
                                    break;
                                }
                            }
                        });
                    }

                    if (columnIndex === -1) return '';

                    // Adjust for bulk management checkbox (first column) and index column (second column)
                    const dataCellIndex = hasBulkManagement ? columnIndex - 1 : columnIndex - 1;
                    if (dataCellIndex < 0) return '';

                    const cell = row.children[dataCellIndex];
                    return cell ? (cell.textContent || '').trim() : '';
                }

                // Function to get search value from row based on filter type
                function getSearchValueFromRow(row, filterType) {
                    let searchValue = '';

                    switch (filterType) {
                        case 'waybill_no':
                            // Priority: data attribute > cell value > row ID
                            searchValue = (row.dataset.waybillNo || '') +
                                (row.dataset.rowId || '') +
                                getCellValueByColumn(row, 'waybill_no');
                            // Also check all cells for waybill number pattern
                            if (!searchValue || searchValue.trim() === '') {
                                Array.from(row.children).forEach(cell => {
                                    const text = cell.textContent || '';
                                    // Match waybill number patterns (numbers or alphanumeric like 4000a)
                                    const waybillMatch = text.match(/\b\d{3,}[a-z]?\b/);
                                    if (waybillMatch) {
                                        searchValue += waybillMatch[0] + ' ';
                                    }
                                });
                            }
                            break;
                        case 'name':
                            // Driver name search
                            searchValue = (row.dataset.name || '') +
                                getCellValueByColumn(row, 'name');
                            if (!searchValue || searchValue.trim() === '') {
                                Array.from(row.children).forEach(cell => {
                                    const text = cell.textContent || '';
                                    if (text.match(/^[A-Z][a-z]+\s+[A-Z]/) || text.match(/[A-Z][a-z]+\s+[A-Z][a-z]+/) || text.match(/^[A-Z][a-z]+/)) {
                                        searchValue += text + ' ';
                                    }
                                });
                            }
                            break;
                        case 'phone':
                            // Phone search
                            searchValue = (row.dataset.phone || '') +
                                getCellValueByColumn(row, 'phone');
                            if (!searchValue || searchValue.trim() === '') {
                                Array.from(row.children).forEach(cell => {
                                    const text = cell.textContent || '';
                                    // Match phone patterns
                                    if (text.match(/[\d\s\-\+\(\)]+/) && text.length > 5) {
                                        searchValue += text + ' ';
                                    }
                                });
                            }
                            break;
                        case 'license_number':
                            // License number search
                            searchValue = (row.dataset.licenseNumber || row.dataset.license_number || '') +
                                getCellValueByColumn(row, 'license_number');
                            break;
                        case 'customer_name':
                            // Priority: data attribute > cell value
                            searchValue = (row.dataset.customerName || '') +
                                getCellValueByColumn(row, 'customer_name');
                            // Fallback: search in all cells for name patterns
                            if (!searchValue || searchValue.trim() === '') {
                                Array.from(row.children).forEach(cell => {
                                    const text = cell.textContent || '';
                                    // Look for name-like patterns (two words, capitalized)
                                    if (text.match(/^[A-Z][a-z]+\s+[A-Z]/) || text.match(/[A-Z][a-z]+\s+[A-Z][a-z]+/)) {
                                        searchValue += text + ' ';
                                    }
                                });
                            }
                            break;
                        case 'email':
                            // Priority: data attribute > cell value
                            searchValue = (row.dataset.email || '') +
                                getCellValueByColumn(row, 'email');
                            // Fallback: search for email pattern in all cells
                            if (!searchValue || searchValue.trim() === '') {
                                Array.from(row.children).forEach(cell => {
                                    const text = cell.textContent || '';
                                    if (text.match(/@/)) {
                                        searchValue += text + ' ';
                                    }
                                });
                            }
                            break;
                        case 'destination':
                            // Priority: data attribute > cell value
                            searchValue = (row.dataset.destination || '') +
                                (row.dataset.city || '') +
                                getCellValueByColumn(row, 'destination') +
                                getCellValueByColumn(row, 'city');
                            break;
                        default:
                            searchValue = row.textContent || '';
                    }

                    return searchValue.toLowerCase();
                }

                let applyFilters = () => {
                    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
                    const filterType = searchFilterType ? searchFilterType.value : 'waybill_no';
                    const rows = table.querySelectorAll('tbody tr');
                    const groupRowsMap = new Map(); // Track which groups have matching rows

                    // First pass: identify group headers and check if they have matching rows
                    rows.forEach(row => {
                        const isGroupRow = row.dataset.groupRow === '1';
                        if (isGroupRow) {
                            const groupId = row.dataset.groupId || '';
                            groupRowsMap.set(groupId, {
                                headerRow: row,
                                hasMatch: false
                            });
                            row.style.display = '';
                            return;
                        }

                        const groupId = row.dataset.groupId || '';
                        const isCollapsed = groupId !== '' && collapsedGroups.has(groupId);

                        // Get search value based on filter type
                        const searchValue = getSearchValueFromRow(row, filterType);
                        const matchesSearch = searchTerm === '' || searchValue.includes(searchTerm);

                        if (matchesSearch) {
                            const groupInfo = groupRowsMap.get(groupId);
                            if (groupInfo) {
                                groupInfo.hasMatch = true;
                            }
                            // Show/hide based on collapsed state
                            if (isCollapsed) {
                                row.style.display = 'none';
                            } else {
                                row.style.display = '';
                            }
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Second pass: hide group headers that have no matching rows
                    groupRowsMap.forEach((info, groupId) => {
                        if (!info.hasMatch) {
                            info.headerRow.style.display = 'none';
                        }
                    });

                    reindexRows();
                };

                const updateGroupToggleVisual = (headerRow, isCollapsed) => {
                    if (!headerRow) {
                        return;
                    }
                    headerRow.dataset.collapsed = isCollapsed ? '1' : '0';
                    const toggleBtn = headerRow.querySelector('[data-group-toggle]');
                    if (toggleBtn) {
                        toggleBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                        const icon = toggleBtn.querySelector('.group-toggle-icon');
                        if (icon) {
                            icon.style.transform = isCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
                        }
                    }
                };

                const groupHeaders = table.querySelectorAll('tbody tr[data-group-row="1"]');
                groupHeaders.forEach(headerRow => {
                    const groupId = headerRow.dataset.groupId;
                    if (!groupId) {
                        return;
                    }
                    const defaultCollapsed = headerRow.dataset.collapsed === '1';
                    if (defaultCollapsed) {
                        collapsedGroups.add(groupId);
                    }
                    updateGroupToggleVisual(headerRow, collapsedGroups.has(groupId));

                    const toggleBtn = headerRow.querySelector('[data-group-toggle]');
                    if (toggleBtn) {
                        toggleBtn.addEventListener('click', function(event) {
                            event.preventDefault();
                            event.stopPropagation();

                            // Get the specific group ID from this header row
                            const clickedGroupId = headerRow.dataset.groupId;
                            if (!clickedGroupId) {
                                return;
                            }

                            const currentlyCollapsed = collapsedGroups.has(clickedGroupId);
                            if (currentlyCollapsed) {
                                collapsedGroups.delete(clickedGroupId);
                            } else {
                                collapsedGroups.add(clickedGroupId);
                            }

                            // Update only this specific group header
                            updateGroupToggleVisual(headerRow, collapsedGroups.has(clickedGroupId));

                            // Toggle only rows with this specific group ID
                            const allRowsWithGroupId = table.querySelectorAll(`tbody tr[data-group-id="${clickedGroupId}"]:not([data-group-row="1"])`);
                            allRowsWithGroupId.forEach(row => {
                                if (collapsedGroups.has(clickedGroupId)) {
                                    row.style.display = 'none';
                                } else {
                                    // Only show if it matches search
                                    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
                                    const filterType = searchFilterType ? searchFilterType.value : 'waybill_no';
                                    const searchValue = getSearchValueFromRow(row, filterType);
                                    const matchesSearch = searchTerm === '' || searchValue.includes(searchTerm);
                                    row.style.display = matchesSearch ? '' : 'none';
                                }
                            });

                            reindexRows();
                        });
                    }
                });

                if (searchInput) {
                    searchInput.addEventListener('input', applyFilters);
                }

                if (searchFilterType) {
                    const defaultPlaceholder = <?php echo json_encode($options['search_placeholder']); ?>;
                    const defaultFilter = <?php echo json_encode($options['search_default_filter'] ?? ($options['search_filters'][0]['value'] ?? '')); ?>;

                    searchFilterType.addEventListener('change', function() {
                        // Update placeholder text based on filter type if configured
                        const filterOptions = <?php echo json_encode($options['search_filters'] ?? []); ?>;
                        const selectedFilter = filterOptions.find(f => f.value === this.value);
                        if (searchInput && selectedFilter && selectedFilter.placeholder) {
                            searchInput.placeholder = selectedFilter.placeholder;
                        } else if (searchInput) {
                            searchInput.placeholder = defaultPlaceholder;
                        }
                        applyFilters();
                    });
                }

                if (clearSearchBtn) {
                    const defaultPlaceholder = <?php echo json_encode($options['search_placeholder']); ?>;
                    const defaultFilter = <?php echo json_encode($options['search_default_filter'] ?? ($options['search_filters'][0]['value'] ?? '')); ?>;

                    clearSearchBtn.addEventListener('click', function() {
                        if (searchInput) {
                            searchInput.value = '';
                            searchInput.placeholder = defaultPlaceholder;
                        }
                        if (searchFilterType) {
                            searchFilterType.value = defaultFilter;
                        }
                        applyFilters();
                    });
                }

                applyFilters();

                // View Toggle Functionality
                if (enableViewToggle) {
                    const groupedBtn = document.getElementById('view-toggle-grouped-<?php echo esc_js($table_id); ?>');
                    const infiniteBtn = document.getElementById('view-toggle-infinite-<?php echo esc_js($table_id); ?>');

                    // Set data attributes for buttons (since renderButton doesn't support arbitrary data attributes)
                    if (groupedBtn) {
                        groupedBtn.setAttribute('data-view', 'grouped');
                        groupedBtn.setAttribute('data-table-id', '<?php echo esc_js($table_id); ?>');
                    }
                    if (infiniteBtn) {
                        infiniteBtn.setAttribute('data-view', 'infinite');
                        infiniteBtn.setAttribute('data-table-id', '<?php echo esc_js($table_id); ?>');
                    }

                    const tbody = table.querySelector('tbody');
                    // Check URL parameter to determine current view
                    const urlParams = new URLSearchParams(window.location.search);
                    const urlView = urlParams.get('view');
                    const currentView = urlView === 'infinite' ? 'infinite' : (originalGroupby ? 'grouped' : 'infinite');

                    // Update table dataset to match current view
                    table.dataset.currentView = currentView;

                    // Helper function to update button active state
                    function setButtonActive(btn, isActive) {
                        if (isActive) {
                            // Remove plain/inactive classes
                            btn.classList.remove('bg-white', 'bg-gray-100', 'text-gray-700', 'text-black', 'border-gray-300', 'hover:bg-gray-50', 'hover:bg-gray-200');
                            // Add gradient active classes
                            btn.classList.add('bg-gradient-to-r', 'from-blue-600', 'to-indigo-600', 'text-white', 'border-blue-600', 'hover:from-blue-700', 'hover:to-indigo-700');
                        } else {
                            // Remove gradient/active classes
                            btn.classList.remove('bg-gradient-to-r', 'from-blue-600', 'to-indigo-600', 'text-white', 'border-blue-600', 'hover:from-blue-700', 'hover:to-indigo-700', 'bg-blue-600', 'hover:bg-blue-700');
                            // Add plain inactive classes
                            btn.classList.add('bg-white', 'text-black', 'border-gray-300', 'hover:bg-gray-50');
                        }
                    }

                    // Set initial button states
                    setButtonActive(groupedBtn, currentView === 'grouped');
                    setButtonActive(infiniteBtn, currentView === 'infinite');

                    // Function to switch to grouped view
                    function switchToGroupedView() {
                        if (!originalGroupby) return;

                        // Update button states
                        setButtonActive(groupedBtn, true);
                        setButtonActive(infiniteBtn, false);

                        // Update URL without reload
                        const url = new URL(window.location.href);
                        url.searchParams.set('view', 'grouped');
                        window.history.pushState({
                            view: 'grouped'
                        }, '', url);

                        // Reload the page to rebuild with grouping (server-side grouping is needed)
                        window.location.reload();
                    }

                    // Function to switch to infinite scroll view
                    function switchToInfiniteView() {
                        // Update button states
                        setButtonActive(infiniteBtn, true);
                        setButtonActive(groupedBtn, false);

                        // Update URL and reload to rebuild without grouping
                        const url = new URL(window.location.href);
                        url.searchParams.set('view', 'infinite');
                        window.location.href = url.toString();
                    }

                    // Event listeners
                    groupedBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (table.dataset.currentView !== 'grouped') {
                            switchToGroupedView();
                        }
                    });

                    infiniteBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (table.dataset.currentView !== 'infinite') {
                            switchToInfiniteView();
                        }
                    });
                }

                // Select All functionality for infinite scroll
                if (<?php echo $options['selectable'] ? 'true' : 'false'; ?>) {
                    const selectAllCheckbox = document.getElementById('infinite-select-all-checkbox');
                    const rowCheckboxes = document.querySelectorAll('.infinite-row-checkbox');

                    if (selectAllCheckbox) {
                        selectAllCheckbox.addEventListener('change', function() {
                            const checked = this.checked;
                            rowCheckboxes.forEach(checkbox => {
                                const row = checkbox.closest('tr');
                                if (row && row.style.display !== 'none') {
                                    checkbox.checked = checked;
                                }
                            });
                        });
                    }

                    rowCheckboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const visibleCheckboxes = Array.from(rowCheckboxes).filter(cb => {
                                const row = cb.closest('tr');
                                return row && row.style.display !== 'none';
                            });

                            const checkedVisibleBoxes = visibleCheckboxes.filter(cb => cb.checked);

                            if (selectAllCheckbox) {
                                if (checkedVisibleBoxes.length === visibleCheckboxes.length && visibleCheckboxes.length > 0) {
                                    selectAllCheckbox.checked = true;
                                    selectAllCheckbox.indeterminate = false;
                                } else if (checkedVisibleBoxes.length === 0) {
                                    selectAllCheckbox.checked = false;
                                    selectAllCheckbox.indeterminate = false;
                                } else {
                                    selectAllCheckbox.checked = false;
                                    selectAllCheckbox.indeterminate = true;
                                }
                            }
                        });
                    });
                }

                // Bulk Management functionality
                if (<?php echo ($options['bulk_management'] === true) ? 'true' : 'false'; ?>) {
                    // Initialize bulk management using function from kitscript.js
                    if (typeof initBulkManagement === 'function') {
                        <?php if (function_exists('wp_create_nonce')): ?>
                            initBulkManagement('<?php echo esc_js($table_id); ?>', '<?php echo wp_create_nonce('bulk_action_nonce'); ?>');
                        <?php else: ?>
                            initBulkManagement('<?php echo esc_js($table_id); ?>', null);
                        <?php endif; ?>

                        // Update UI when filters change
                        if (typeof applyFilters !== 'undefined') {
                            const originalApplyFilters = applyFilters;
                            applyFilters = function() {
                                originalApplyFilters();
                                if (window.kitBulkManagement && window.kitBulkManagement['<?php echo esc_js($table_id); ?>']) {
                                    window.kitBulkManagement['<?php echo esc_js($table_id); ?>'].updateUI();
                                }
                            };
                        }
                    } else {
                        console.error('initBulkManagement function not found. Make sure kitscript.js is loaded.');
                    }
                }

                // Sorting functionality - client-side sorting for infinite scroll
                if (<?php echo $options['sortable'] ? 'true' : 'false'; ?>) {
                    if (table.dataset && table.dataset.hasGroupRows === '1') return;

                    const sortHeaders = table.querySelectorAll('.sortable-header');
                    let currentSortColumn = '';
                    let currentSortDirection = 'asc';

                    sortHeaders.forEach(header => {
                        header.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            const th = this.closest('th');
                            const column = th ? th.getAttribute('data-column') : null;
                            const icon = this.querySelector('.sort-icon');
                            const tbody = table.querySelector('tbody');

                            if (!tbody || !column) return;

                            // Determine sort direction
                            if (currentSortColumn === column) {
                                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
                            } else {
                                currentSortDirection = 'asc';
                            }
                            currentSortColumn = column;

                            // Reset all icons
                            sortHeaders.forEach(h => {
                                const i = h.querySelector('.sort-icon');
                                if (i) i.style.transform = 'rotate(0deg)';
                            });

                            // Set current icon
                            if (icon) {
                                icon.style.transform = currentSortDirection === 'asc' ? 'rotate(180deg)' : 'rotate(0deg)';
                            }

                            // Get column index from the table header row
                            const headerRow = table.querySelector('thead tr');
                            const columnIndex = Array.from(headerRow.children).indexOf(th);

                            // Skip sorting if it's the checkbox column (first column when bulk_management is enabled) or index column
                            if (hasBulkManagement && columnIndex === 0) {
                                return; // Skip checkbox column
                            }
                            if (!hasBulkManagement && columnIndex === 0) {
                                return; // Skip index column when no bulk management
                            }
                            if (hasBulkManagement && columnIndex === 1) {
                                return; // Skip index column when bulk management is enabled
                            }

                            // Sort rows
                            const rows = Array.from(tbody.querySelectorAll('tr'));

                            rows.sort((a, b) => {
                                // Skip group rows
                                if (a.dataset.groupRow === '1' || b.dataset.groupRow === '1') {
                                    return 0;
                                }

                                const aCell = a.children[columnIndex];
                                const bCell = b.children[columnIndex];

                                if (!aCell || !bCell) return 0;

                                let aVal = aCell.textContent?.trim() || '';
                                let bVal = bCell.textContent?.trim() || '';

                                // Try to parse as numbers for numeric sorting
                                const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                                const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));

                                if (!isNaN(aNum) && !isNaN(bNum)) {
                                    // Numeric comparison
                                    aVal = aNum;
                                    bVal = bNum;
                                } else {
                                    // String comparison
                                    aVal = aVal.toLowerCase();
                                    bVal = bVal.toLowerCase();
                                }

                                if (currentSortDirection === 'asc') {
                                    return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                                } else {
                                    return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
                                }
                            });

                            // Re-append sorted rows and update index numbers
                            rows.forEach((row, index) => {
                                // Skip checkbox cell if bulk_management is enabled (first cell), index is second cell
                                const indexCellIndex = hasBulkManagement ? 1 : 0;
                                const indexCell = row.children[indexCellIndex];
                                if (indexCell && indexCell.tagName === 'TD') {
                                    // Only update if it's not the checkbox cell (check for checkbox input)
                                    if (!indexCell.querySelector('input[type="checkbox"]')) {
                                        indexCell.textContent = index + 1;
                                    }
                                }
                                tbody.appendChild(row);
                            });
                        });
                    });
                }
            });
        </script>

    <?php
        return ob_get_clean();
    }

    /**
     * Render a server-side table (DataTables)
     */
    public static function server_side($columns, $options = [])
    {
        $defaults = [
            'title' => '',
            'subtitle' => '',
            'ajax_url' => '',
            'ajax_action' => '',
            'actions' => []
        ];

        $options = array_merge($defaults, $options);

        ob_start();
    ?>
        <div class="w-full">
            <div class="mb-3">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <?php if ($options['title']): ?>
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo esc_html($options['title']); ?></h3>
                        <?php endif; ?>
                        <?php if ($options['subtitle']): ?>
                            <p class="text-sm text-gray-600"><?php echo esc_html($options['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="w-full overflow-hidden">
                <table id="server-side-table" class="w-full table-auto border-collapse" style="width:100%;">
                    <thead class="border-b border-gray-200">
                        <tr>
                            <?php foreach ($columns as $key => $column): ?>
                                <?php
                                $label = is_array($column) ? $column['label'] : $column;
                                ?>
                                <th class="px-3 py-2 text-xs font-semibold text-left uppercase tracking-wide text-gray-700">
                                    <span class="flex items-center gap-1 text-gray-700 whitespace-normal break-words"><?php echo esc_html($label); ?></span>
                                </th>
                            <?php endforeach; ?>
                            <?php if (!empty($options['actions'])): ?>
                                <th class="px-3 py-2 text-xs font-semibold text-left uppercase tracking-wide text-gray-700">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#server-side-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '<?php echo esc_url($options['ajax_url']); ?>',
                        type: 'POST',
                        data: {
                            action: '<?php echo esc_attr($options['ajax_action']); ?>'
                        }
                    },
                    columns: [
                        <?php foreach ($columns as $key => $column): ?> {
                                data: '<?php echo esc_js($key); ?>'
                            },
                        <?php endforeach; ?>
                        <?php if (!empty($options['actions'])): ?> {
                                data: null,
                                orderable: false,
                                render: function(data, type, row) {
                                    let actions = '';
                                    <?php foreach ($options['actions'] as $action): ?>
                                        <?php
                                        $href = $action['href'] ?? '#';
                                        $class = $action['class'] ?? 'text-blue-600 hover:text-blue-800';
                                        $onclick = isset($action['onclick']) ? 'onclick="' . esc_attr($action['onclick']) . '"' : '';
                                        ?>
                                        <?php
                                        $label_output = (isset($action['is_html']) && $action['is_html']) ? $action['label'] : esc_html($action['label']);
                                        ?>
                                        actions += '<a href="<?php echo esc_url($href); ?>"<?php echo $class ? ' class="' . esc_attr($class) . '"' : ''; ?> <?php echo $onclick; ?>><?php echo $label_output; ?></a>';
                                        <?php if ($action !== end($options['actions'])): ?>
                                            actions += ' | ';
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    return actions;
                                }
                            }
                        <?php endif; ?>
                    ]
                });
            });
        </script>
<?php
        return ob_get_clean();
    }
}
