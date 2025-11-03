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
            'class' => 'min-w-full divide-y divide-gray-200',
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

        // Build unique IDs for infinite scroll
        $table_id = 'kit-infinite-table-' . uniqid();
        $container_id = 'kit-infinite-wrap-' . uniqid();

        ob_start();
?>
        <div class="bg-white shadow rounded-lg overflow-hidden max-w-full" style="max-width: 100vw; box-sizing: border-box;" id="<?php echo esc_attr($container_id); ?>">
            <?php if ($options['title'] || $options['subtitle']): ?>
                <div class="px-6 py-4 border-b border-gray-200">
                    <?php if ($options['title']): ?>
                        <h3 class="text-lg font-medium text-gray-900"><?php echo esc_html($options['title']); ?></h3>
                    <?php endif; ?>
                    <?php if ($options['subtitle']): ?>
                        <p class="mt-1 text-sm text-gray-500"><?php echo esc_html($options['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($data)): ?>
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-500"><?php echo esc_html($options['empty_message']); ?></p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto max-w-full" style="max-width: 100vw; box-sizing: border-box;">
                    <table id="<?php echo esc_attr($table_id); ?>" class="<?php echo esc_attr($options['class']); ?>" style="table-layout: fixed; width: 100%; max-width: 100%;">
                        <thead class="bg-gray-50">
                            <tr class="px-6 py-3">
                                <!-- Index column header -->
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16 max-w-16">
                                    #
                                </th>
                                <?php foreach ($columns as $key => $column): ?>
                                    <?php
                                    $label = is_array($column) ? $column['label'] : $column;
                                    $columnSortable = is_array($column) && isset($column['sortable']) ? $column['sortable'] : $options['sortable'];
                                    // Skip sorting for checkbox column
                                    if ($key === 'checkbox') {
                                        $columnSortable = false;
                                    }
                                    $padding = is_array($column) && isset($column['padding']) ? $column['padding'] : 'px-2 py-2';
                                    $headerClass = $padding . ' text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
                                    if (is_array($column) && isset($column['header_class'])) {
                                        $headerClass = trim($headerClass . ' ' . $column['header_class']);
                                    }
                                    ?>
                                    <th class="<?php echo esc_attr($headerClass); ?>" <?php if ($columnSortable): ?>data-column="<?php echo esc_attr($key); ?>"<?php endif; ?>>
                                        <?php if ($columnSortable): ?>
                                            <button type="button" class="group inline-flex items-center sortable-header cursor-pointer hover:bg-gray-100 px-2 py-1 rounded -ml-2" style="cursor: pointer;">
                                        <?php echo esc_html($label); ?>
                                                <svg class="ml-2 h-3 w-3 text-gray-400 group-hover:text-gray-500 sort-icon" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <?php echo esc_html($label); ?>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                                <?php if (!empty($options['actions'])): ?>
                                    <th class="<?php echo esc_attr($padding); ?> text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24 max-w-24">
                                        Actions
                                    </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 7878">
                            <?php foreach ($data as $rowIndex => $row): ?>
                                <?php
                                $rowAttrStr = '';
                                if (is_callable($options['row_attrs_callback'])) {
                                    $attrs = (array) call_user_func($options['row_attrs_callback'], $row, $rowIndex);
                                    foreach ($attrs as $attrKey => $attrVal) {
                                        $rowAttrStr .= ' ' . esc_attr($attrKey) . '="' . esc_attr((string) $attrVal) . '"';
                                    }
                                }
                                ?>
                                <tr class="hover:bg-gray-50"<?php echo $rowAttrStr; ?>>
                                    <!-- Index column cell -->
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-500 font-medium">
                                        <?php echo esc_html($rowIndex + 1); ?>
                                    </td>
                                    <?php foreach ($columns as $key => $column): ?>
                                        <?php
                                        $padding = is_array($column) && isset($column['padding']) ? $column['padding'] : 'px-2 py-2';
                                        $cellClass = $padding . ' whitespace-nowrap text-sm text-gray-900';
                                        if (is_array($column) && isset($column['cell_class'])) {
                                            $cellClass = $column['cell_class'] . ' ' . $padding;
                                        }
                                        ?>
                                        <td class="7878 <?php echo esc_attr($cellClass); ?>">
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
                                        <td class="px-2 py-2 whitespace-nowrap text-sm font-medium 7878">
                                            <?php foreach ($options['actions'] as $action): ?>
                                                <?php
                                                $href = $action['href'] ?? '#';
                                                $class = $action['class'] ?? 'text-blue-600 hover:text-blue-800';
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
                                                    class="<?php echo esc_attr($class); ?>"
                                                    <?php echo $onclick; ?> <?php echo $target; ?>>
                                                    <?php 
                                                    if (isset($action['is_html']) && $action['is_html']) {
                                                        echo $action['label'];
                                                    } else {
                                                        echo esc_html($action['label']);
                                                    }
                                                    ?>
                                                </a>
                                                <?php if ($action !== end($options['actions'])): ?>
                                                    <span class="mx-2">|</span>
                                                <?php endif; ?>
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
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <?php if ($options['title']): ?>
                            <h3 class="text-lg font-medium text-gray-900"><?php echo esc_html($options['title']); ?></h3>
                        <?php endif; ?>
                        <?php if ($options['subtitle']): ?>
                            <p class="mt-1 text-sm text-gray-500"><?php echo esc_html($options['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="server-side-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="dd3s1234">
                            <?php foreach ($columns as $key => $column): ?>
                                <?php
                                $label = is_array($column) ? $column['label'] : $column;
                                ?>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo esc_html($label); ?>
                                </th>
                            <?php endforeach; ?>
                            <?php if (!empty($options['actions'])): ?>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
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
                                        actions += '<a href="<?php echo esc_url($href); ?>" class="<?php echo esc_attr($class); ?>" <?php echo $onclick; ?>><?php echo $label_output; ?></a>';
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
