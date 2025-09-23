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

class KIT_Unified_Table {
    
    /**
     * Render a simple table
     */
    public static function simple($data, $columns, $options = []) {
        $defaults = [
            'title' => '',
            'subtitle' => '',
            'actions' => [],
            'empty_message' => 'No data found',
            'class' => 'min-w-full divide-y divide-gray-200'
        ];
        
        $options = array_merge($defaults, $options);
        
        ob_start();
        ?>
        <div class="bg-white shadow rounded-lg overflow-hidden">
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
                <div class="overflow-x-auto">
                    <table class="<?php echo esc_attr($options['class']); ?>">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php foreach ($columns as $key => $column): ?>
                                    <?php
                                    $label = is_array($column) ? $column['label'] : $column;
                                    ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?php echo esc_html($label); ?>
                                    </th>
                                <?php endforeach; ?>
                                <?php if (!empty($options['actions'])): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($data as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <?php foreach ($columns as $key => $column): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php
                                            // Handle both arrays and objects
                                            $value = '';
                                            if (is_array($row)) {
                                                $value = $row[$key] ?? '';
                                            } elseif (is_object($row)) {
                                                $value = $row->$key ?? '';
                                            }
                                            
                                            if (is_array($column) && isset($column['callback'])) {
                                                echo $column['callback']($value, $row);
                                            } else {
                                                echo esc_html($value);
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <?php if (!empty($options['actions'])): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php foreach ($options['actions'] as $action): ?>
                                                <?php
                                                $href = $action['href'] ?? '#';
                                                $class = $action['class'] ?? 'text-blue-600 hover:text-blue-800';
                                                $onclick = isset($action['onclick']) ? 'onclick="' . esc_attr($action['onclick']) . '"' : '';
                                                $target = isset($action['target']) ? 'target="' . esc_attr($action['target']) . '" rel="noopener"' : '';
                                                
                                                // Replace placeholders in href
                                                if (is_array($row)) {
                                                    foreach ($row as $placeholder => $value) {
                                                        $href = str_replace('{' . $placeholder . '}', $value, $href);
                                                    }
                                                } elseif (is_object($row)) {
                                                    foreach ($row as $placeholder => $value) {
                                                        $href = str_replace('{' . $placeholder . '}', $value, $href);
                                                    }
                                                }
                                                ?>
                                                <a href="<?php echo esc_url($href); ?>" 
                                                   class="<?php echo esc_attr($class); ?>" 
                                                   <?php echo $onclick; ?> <?php echo $target; ?>>
                                                    <?php echo esc_html($action['label']); ?>
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
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render an advanced table with all features
     */
    public static function advanced($data, $columns, $options = []) {
        $defaults = [
            'title' => '',
            'subtitle' => '',
            'actions' => [],
            'searchable' => false,
            'sortable' => false,
            'pagination' => false,
            'items_per_page' => 10,
            'current_page' => 1,
            'show_items_per_page' => false,
            'exportable' => false,
            'bulk_actions' => false,
            'selectable' => false,
            'empty_message' => 'No data found',
            'class' => 'min-w-full divide-y divide-gray-200',
            'primary_action' => null
        ];
        
        $options = array_merge($defaults, $options);
        
        // Handle pagination
        $total_items = count($data);
        $total_pages = ceil($total_items / $options['items_per_page']);
        $start_index = ($options['current_page'] - 1) * $options['items_per_page'];
        $paginated_data = array_slice($data, $start_index, $options['items_per_page']);
        
        ob_start();
        ?>
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <!-- Header -->
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
                    <?php if ($options['primary_action']): ?>
                        <a href="<?php echo esc_url($options['primary_action']['href']); ?>" 
                           class="<?php echo esc_attr($options['primary_action']['class']); ?>">
                            <?php echo esc_html($options['primary_action']['label']); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Search and Controls -->
                <div class="mt-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
                    <?php if ($options['searchable']): ?>
                        <div class="flex-1 max-w-lg">
                            <input type="text" 
                                   id="table-search" 
                                   placeholder="Search..." 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center space-x-4">
                        <?php if ($options['show_items_per_page']): ?>
                            <div class="flex items-center">
                                <label for="items-per-page" class="text-sm text-gray-700 mr-2">Show:</label>
                                <select id="items-per-page" class="block w-20 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="5" <?php selected($options['items_per_page'], 5); ?>>5</option>
                                    <option value="10" <?php selected($options['items_per_page'], 10); ?>>10</option>
                                    <option value="20" <?php selected($options['items_per_page'], 20); ?>>20</option>
                                    <option value="50" <?php selected($options['items_per_page'], 50); ?>>50</option>
                                    <option value="100" <?php selected($options['items_per_page'], 100); ?>>100</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($options['exportable']): ?>
                            <button type="button" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Export
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Table -->
            <?php if (empty($paginated_data)): ?>
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-500"><?php echo esc_html($options['empty_message']); ?></p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="<?php echo esc_attr($options['class']); ?>">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php if ($options['selectable']): ?>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </th>
                                <?php endif; ?>
                                <?php foreach ($columns as $key => $column): ?>
                                    <?php
                                    $label = is_array($column) ? $column['label'] : $column;
                                    $sortable = is_array($column) && isset($column['sortable']) ? $column['sortable'] : $options['sortable'];
                                    ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?php if ($sortable): ?>
                                            <button class="group inline-flex items-center">
                                                <?php echo esc_html($label); ?>
                                                <svg class="ml-2 h-3 w-3 text-gray-400 group-hover:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <?php echo esc_html($label); ?>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                                <?php if (!empty($options['actions'])): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($paginated_data as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <?php if ($options['selectable']): ?>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </td>
                                    <?php endif; ?>
                                    <?php foreach ($columns as $key => $column): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php
                                            // Handle both arrays and objects
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
                                                echo $column['callback']($value, $row);
                                            } else {
                                                echo esc_html($value);
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <?php if (!empty($options['actions'])): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php foreach ($options['actions'] as $action): ?>
                                                <?php
                                                $href = $action['href'] ?? '#';
                                                $class = $action['class'] ?? 'text-blue-600 hover:text-blue-800';
                                                $onclick = isset($action['onclick']) ? 'onclick="' . esc_attr($action['onclick']) . '"' : '';
                                                
                                                // Replace placeholders in href
                                                if (is_array($row)) {
                                                    foreach ($row as $placeholder => $value) {
                                                        $href = str_replace('{' . $placeholder . '}', $value, $href);
                                                    }
                                                } elseif (is_object($row)) {
                                                    foreach ($row as $placeholder => $value) {
                                                        $href = str_replace('{' . $placeholder . '}', $value, $href);
                                                    }
                                                }
                                                ?>
                                                <a href="<?php echo esc_url($href); ?>" 
                                                   class="<?php echo esc_attr($class); ?>" 
                                                   <?php echo $onclick; ?>>
                                                    <?php echo esc_html($action['label']); ?>
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
                
                <!-- Pagination -->
                <?php if ($options['pagination'] && $total_pages > 1): ?>
                    <div class="px-6 py-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo $start_index + 1; ?></span> to 
                                <span class="font-medium"><?php echo min($start_index + $options['items_per_page'], $total_items); ?></span> of 
                                <span class="font-medium"><?php echo $total_items; ?></span> results
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($options['current_page'] > 1): ?>
                                    <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>&paged=<?php echo $options['current_page'] - 1; ?>&per_page=<?php echo $options['items_per_page']; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $options['current_page'] - 2); $i <= min($total_pages, $options['current_page'] + 2); $i++): ?>
                                    <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>&paged=<?php echo $i; ?>&per_page=<?php echo $options['items_per_page']; ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php echo $i === $options['current_page'] ? 'text-blue-600 bg-blue-50 border-blue-300' : 'text-gray-500 bg-white border-gray-300'; ?> border rounded-md hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($options['current_page'] < $total_pages): ?>
                                    <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>&paged=<?php echo $options['current_page'] + 1; ?>&per_page=<?php echo $options['items_per_page']; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($options['searchable']): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('table-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        });
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a server-side table (DataTables)
     */
    public static function server_side($columns, $options = []) {
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
                        <tr>
                            <?php foreach ($columns as $key => $column): ?>
                                <?php
                                $label = is_array($column) ? $column['label'] : $column;
                                ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo esc_html($label); ?>
                                </th>
                            <?php endforeach; ?>
                            <?php if (!empty($options['actions'])): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                    <?php foreach ($columns as $key => $column): ?>
                        { data: '<?php echo esc_js($key); ?>' },
                    <?php endforeach; ?>
                    <?php if (!empty($options['actions'])): ?>
                        { 
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
                                    actions += '<a href="<?php echo esc_url($href); ?>" class="<?php echo esc_attr($class); ?>" <?php echo $onclick; ?>><?php echo esc_html($action['label']); ?></a>';
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
