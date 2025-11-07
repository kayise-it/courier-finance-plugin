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
            'empty_message' => 'No data found',
            'table_class' => 'w-full table-fixed border-collapse',
            'header_base_class' => 'px-3 py-2 text-xs font-semibold text-left uppercase tracking-wide',
            'cell_base_class' => 'px-3 py-2 text-sm text-gray-900 whitespace-normal break-words align-top',
            'index_cell_class' => 'px-3 py-2 text-sm font-medium text-gray-600 whitespace-normal break-words align-top',
            'actions_cell_class' => 'px-3 py-2 text-sm font-medium text-gray-900 whitespace-normal break-words align-top flex flex-wrap items-center gap-2',
            'primary_action' => null,
            'items_per_page' => 100,
            'current_page' => 1,
            // Optional: callback to add attributes to each <tr>. Signature: function($row, $rowIndex): array
            'row_attrs_callback' => null
        ];

        $options = array_merge($defaults, $options);

        // Show ALL data initially - no pagination for infinite scroll
        $total_items = count($data);
        $display_data = $data; // Show all items

        // Ensure newest entries (by created_at or ID) appear first
        if (!empty($display_data)) {
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

        // Build unique IDs for infinite scroll
        $table_id = 'kit-infinite-table-' . uniqid();
        $container_id = 'kit-infinite-wrap-' . uniqid();

        ob_start();
?>
        <div id="<?php echo esc_attr($container_id); ?>" class="w-full">
            <?php if ($options['title'] || $options['subtitle']): ?>
                <div class="mb-3">
                    <?php if ($options['title']): ?>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo esc_html($options['title']); ?></h3>
                    <?php endif; ?>
                    <?php if ($options['subtitle']): ?>
                        <p class="text-sm text-gray-600"><?php echo esc_html($options['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($display_data)): ?>
                <div class="py-6 text-center text-sm text-gray-500">
                    <p><?php echo esc_html($options['empty_message']); ?></p>
                </div>
            <?php else: ?>
                <div class="w-full overflow-hidden">
                    <table id="<?php echo esc_attr($table_id); ?>" class="<?php echo esc_attr($options['table_class']); ?>" style="width:100%;">
                        <thead class="border-b border-gray-200">
                            <tr>
                                <!-- Index column header -->
                                <th class="<?php echo esc_attr($options['header_base_class']); ?> w-16 max-w-16">#</th>
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
                                    ?>
                                    <th<?php if ($columnSortable): ?> data-column="<?php echo esc_attr($key); ?>"<?php endif; ?> class="<?php echo esc_attr($headerClass); ?>">
                                        <?php if ($columnSortable): ?>
                                            <button type="button" class="sortable-header flex items-center gap-1 text-gray-700">
                                                <span class="whitespace-normal break-words"><?php echo esc_html($label); ?></span>
                                                <svg class="sort-icon w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="flex items-center gap-1 text-gray-700 whitespace-normal break-words"><?php echo esc_html($label); ?></span>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                                <?php if (!empty($options['actions'])): ?>
                                    <th class="<?php echo esc_attr($options['header_base_class']); ?> w-24 max-w-24">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($display_data as $rowIndex => $row): ?>
                                <?php
                                $rowAttrStr = '';
                                if (is_callable($options['row_attrs_callback'])) {
                                    $attrs = (array) call_user_func($options['row_attrs_callback'], $row, $rowIndex);
                                    foreach ($attrs as $attrKey => $attrVal) {
                                        $rowAttrStr .= ' ' . esc_attr($attrKey) . '="' . esc_attr((string) $attrVal) . '"';
                                    }
                                }
                                ?>
                                <tr<?php echo $rowAttrStr; ?> class="hover:bg-gray-50">
                                    <!-- Index column cell -->
                                    <td class="<?php echo esc_attr($options['index_cell_class']); ?>"><?php echo esc_html($rowIndex + 1); ?></td>
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
                                                echo $column['callback']($value, $row, $rowIndex);
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
                // Search functionality - client-side search for infinite scroll
                if (<?php echo $options['searchable'] ? 'true' : 'false'; ?>) {
                    const searchInput = document.getElementById('infinite-table-search');
                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            const searchTerm = this.value.toLowerCase().trim();
                            const table = document.getElementById('<?php echo esc_js($table_id); ?>');
                            const rows = table ? table.querySelectorAll('tbody tr') : [];
                            let visibleIndex = 1;

                            rows.forEach(row => {
                                const text = row.textContent.toLowerCase();
                                if (searchTerm === '' || text.includes(searchTerm)) {
                                    row.style.display = '';
                                    // Update index for visible rows
                                    const indexCell = row.children[0];
                                    if (indexCell && indexCell.tagName === 'TD') {
                                        indexCell.textContent = visibleIndex++;
                                    }
                                } else {
                                    row.style.display = 'none';
                                }
                            });
                        });

                        const clearSearchBtn = document.getElementById('clear-infinite-search');
                        if (clearSearchBtn) {
                            clearSearchBtn.addEventListener('click', function() {
                                searchInput.value = '';
                                // Re-index all rows when clearing search
                                const table = document.getElementById('<?php echo esc_js($table_id); ?>');
                                const rows = table ? table.querySelectorAll('tbody tr') : [];
                                rows.forEach((row, index) => {
                                    row.style.display = '';
                                    const indexCell = row.children[0];
                                    if (indexCell && indexCell.tagName === 'TD') {
                                        indexCell.textContent = index + 1;
                                    }
                                });
                                searchInput.dispatchEvent(new Event('input'));
                            });
                        }
                    }
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

                // Sorting functionality - client-side sorting for infinite scroll
                if (<?php echo $options['sortable'] ? 'true' : 'false'; ?>) {
                    const table = document.getElementById('<?php echo esc_js($table_id); ?>');
                    if (!table) return;
                    
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
                            
                            // Skip sorting if it's the index column (first column)
                            if (columnIndex === 0) {
                                return;
                            }

                            // Sort rows
                            const rows = Array.from(tbody.querySelectorAll('tr'));
                            
                            rows.sort((a, b) => {
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
                                // Update the index column (first cell) with new row number
                                const indexCell = row.children[0];
                                if (indexCell && indexCell.tagName === 'TD') {
                                    indexCell.textContent = index + 1;
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
                <table id="server-side-table" class="w-full table-fixed border-collapse" style="width:100%;">
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
