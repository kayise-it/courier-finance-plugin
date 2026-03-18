<?php
// ob_start(); // Start buffering the output - REMOVED FOR DEBUGGING

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . 'user-roles.php';

// Include unified table class if not already loaded
if (!class_exists('KIT_Unified_Table')) {
    $class_file = plugin_dir_path(__FILE__) . 'class-unified-table.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    } else {
        error_log('KIT_Unified_Table class file not found at: ' . $class_file);
        // Try alternative path
        $alt_class_file = dirname(plugin_dir_path(__FILE__)) . '/class-unified-table.php';
        if (file_exists($alt_class_file)) {
            require_once $alt_class_file;
        } else {
            error_log('KIT_Unified_Table class file not found at alternative path: ' . $alt_class_file);
        }
    }
}

// Include quick stats component
if (!class_exists('KIT_QuickStats')) {
    require_once plugin_dir_path(__FILE__) . 'components/quickStats.php';
}

function waybill_page()
{
    // Show success toast if waybill was created using KITToast
    if (isset($_GET['success']) && $_GET['success'] == '1') {
        $waybill_no = isset($_GET['waybill_no']) ? sanitize_text_field($_GET['waybill_no']) : '';
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : 'Waybill created successfully!';

        if ($waybill_no) {
            $message = $message . ' Waybill #: ' . $waybill_no;
        }

        // Load and show the toast using the component
        if (!class_exists('KIT_Toast')) {
            require_once plugin_dir_path(__FILE__) . 'components/toast.php';
        }

        KIT_Toast::ensure_toast_loads();
        echo KIT_Toast::show($message, 'success', 'Waybill Created');
    }

    echo do_shortcode('[kit_waybill_form]');
}
function plugin_Waybill_list_page()
{

    // Handle bulk delete from unified table action bar
    if (
        isset($_POST['bulk_action'], $_POST['bulk_ids'])
        && sanitize_text_field(wp_unslash($_POST['bulk_action'])) === 'delete'
    ) {
        if (!current_user_can('kit_update_data')) {
            wp_die('Unauthorized');
        }

        $bulk_nonce = isset($_POST['bulk_nonce']) ? sanitize_text_field(wp_unslash($_POST['bulk_nonce'])) : '';
        if (empty($bulk_nonce) || !wp_verify_nonce($bulk_nonce, 'bulk_action_nonce')) {
            wp_die('Invalid bulk action nonce.');
        }

        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';

        $raw_bulk_ids = sanitize_text_field(wp_unslash($_POST['bulk_ids']));
        $tokens = array_filter(array_map('trim', explode(',', $raw_bulk_ids)));
        $tokens = array_values(array_unique(array_map('sanitize_text_field', $tokens)));

        $numeric_tokens = array_values(array_filter($tokens, static function ($token) {
            return ctype_digit($token);
        }));

        $waybill_numbers = [];

        if (!empty($tokens)) {
            $or_clauses = [];
            $query_params = [];

            if (!empty($tokens)) {
                $string_placeholders = implode(',', array_fill(0, count($tokens), '%s'));
                $or_clauses[] = "waybill_no IN ($string_placeholders)";
                $query_params = array_merge($query_params, $tokens);
            }

            if (!empty($numeric_tokens)) {
                $int_placeholders = implode(',', array_fill(0, count($numeric_tokens), '%d'));
                $or_clauses[] = "id IN ($int_placeholders)";
                $query_params = array_merge($query_params, array_map('intval', $numeric_tokens));
            }

            if (!empty($or_clauses)) {
                $query = $wpdb->prepare(
                    "SELECT waybill_no FROM $waybills_table WHERE " . implode(' OR ', $or_clauses),
                    $query_params
                );
                $rows = $wpdb->get_col($query);

                if (!empty($rows)) {
                    $waybill_numbers = array_values(array_unique(array_map('strval', $rows)));
                }
            }
        }

        $deleted_count = 0;
        foreach ($waybill_numbers as $waybill_no) {
            KIT_Waybills::deleteWaybillItems($waybill_no);
            $deleted = $wpdb->delete($waybills_table, ['waybill_no' => $waybill_no], ['%s']);
            if ($deleted !== false && $deleted > 0) {
                $deleted_count += (int) $deleted;
            }
        }

        $redirect_url = remove_query_arg(['bulk_deleted', 'bulk_requested']);
        $redirect_url = add_query_arg([
            'bulk_deleted' => $deleted_count,
            'bulk_requested' => count($tokens),
        ], $redirect_url);
        wp_safe_redirect($redirect_url);
        exit;
    }

    // Handle bulk export to PDF
    if (isset($_GET['export_selected']) && !empty($_GET['export_selected'])) {
        if (!current_user_can('kit_view_waybills')) {
            wp_die('Unauthorized');
        }

        $selected_ids_string = sanitize_text_field(wp_unslash($_GET['export_selected']));
        $selected_ids_array = array_filter(array_map('trim', explode(',', $selected_ids_string)));
        $selected_ids_array = array_values(array_unique(array_map('sanitize_text_field', $selected_ids_array)));

        if (!empty($selected_ids_array)) {
            // Redirect to PDF generation with selected_ids parameter
            // Get plugin root URL (go up one level from includes/)
            $plugin_url = dirname(plugin_dir_url(__FILE__));
            $pdf_url = add_query_arg([
                'selected_ids' => implode(',', $selected_ids_array)
            ], $plugin_url . '/includes/customers/pdf-bulkinvoicing.php');

            wp_redirect($pdf_url);
            exit;
        }
    }
    // Debug: Check if class exists
    if (!class_exists('KIT_Unified_Table')) {
        error_log('KIT_Unified_Table class not found in plugin_Waybill_list_page');
        if (!class_exists('KIT_Toast')) {
            require_once plugin_dir_path(__FILE__) . 'components/toast.php';
        }
        KIT_Toast::ensure_toast_loads();
        echo KIT_Toast::error('KIT_Unified_Table class not found. Please check the logs.', 'Error');
        return;
    }

    // Warehouse database updates and simulations no longer needed - using kit_waybills table directly

    // Handle delete BEFORE any redirects so the action is not lost
    if (isset($_GET["delete_waybill"])) {
        KIT_Waybills::delete_waybill($_GET["delete_waybill"]);
        exit;
    }

    // Remove legacy per_page redirect to support infinite scroll and AJAX search

    if (isset($_GET['generate_quote'])) {
        echo "Generating quote...";
        KIT_Waybills::generate_Waybill_quote();
    }

    // Delete is handled earlier to avoid losing the action due to redirects
    // Include the modal component
    $customers   = KIT_Customers::tholaMaCustomer();
    $form_action = admin_url('admin-post.php?action=add_waybill_action');

    $modal_path = realpath(plugin_dir_path(__FILE__) . './components/modal.php');

    if ($modal_path && file_exists($modal_path)) {
        require_once $modal_path;
    } else {
        error_log("Modal.php not found at: " . ($modal_path ?: '(null)'));
        // Optional: Show a safe error or fallback content
    }



    // Handle search functionality
    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    // Handle sorting functionality
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'w.created_at';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

    // Map frontend column names to database fields
    $orderby_map = [
        'waybill_no' => 'w.waybill_no',
        'customer_name' => 'c.name',
        'description' => 'w.description',
        'created_by' => 'u.display_name',
        'destination' => 'dest_city.city_name',
        'approval' => 'w.approval',
        'total' => 'w.product_invoice_amount'
    ];
    $warehouse = "false";

    $db_orderby = isset($orderby_map[$orderby]) ? $orderby_map[$orderby] : 'w.created_at';

    $total_waybills = KIT_Waybills::get_waybill_count($search_term);
    $allWaybills = KIT_Waybills::getAllWaybills();

    // Convert to array format for unified table
    $waybillData = [];
    foreach ($allWaybills as $row) {
        $is_placeholder_text = static function ($value): bool {
            $v = strtolower(trim((string) $value));
            return $v === '' || in_array($v, ['0', 'null', 'n/a', 'na', 'none', '-', '--'], true);
        };
        // Get customer display: business => company name, individual => first + surname.
        $first_name = trim((string)($row->customer_name ?? ''));
        $surname = trim((string)($row->customer_surname ?? ''));
        $company_name = trim((string)($row->company_name ?? ''));
        if ($is_placeholder_text($first_name)) {
            $first_name = '';
        }
        if ($is_placeholder_text($surname)) {
            $surname = '';
        }
        if ($is_placeholder_text($company_name)) {
            $company_name = '';
        }
        // Treat "Private" as a placeholder company label, not a preferred business display.
        $is_business = $company_name !== '' && !in_array(strtolower($company_name), ['individual', '1ndividual', 'n/a', 'none', 'private'], true);
        $customer_name = $is_business ? $company_name : trim($first_name . ' ' . $surname);

        // If customer name is still empty and we have customer_id, try to fetch it
        if (empty($customer_name) && isset($row->customer_id) && $row->customer_id > 0) {
            global $wpdb;
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT id, cust_id, name, surname, company_name
                 FROM {$wpdb->prefix}kit_customers
                 WHERE cust_id = %d OR id = %d
                 ORDER BY CASE WHEN cust_id = %d THEN 0 ELSE 1 END
                 LIMIT 1",
                intval($row->customer_id),
                intval($row->customer_id),
                intval($row->customer_id)
            ));
            if ($customer) {
                $fallback_company = trim((string)($customer->company_name ?? ''));
                $fallback_first = trim((string)($customer->name ?? ''));
                $fallback_surname = trim((string)($customer->surname ?? ''));
                if ($is_placeholder_text($fallback_first)) {
                    $fallback_first = '';
                }
                if ($is_placeholder_text($fallback_surname)) {
                    $fallback_surname = '';
                }
                if ($is_placeholder_text($fallback_company)) {
                    $fallback_company = '';
                }
                $fallback_is_business = $fallback_company !== '' && !in_array(strtolower($fallback_company), ['individual', '1ndividual', 'n/a', 'none', 'private'], true);
                $customer_name = $fallback_is_business ? $fallback_company : trim($fallback_first . ' ' . $fallback_surname);
                $company_name = $fallback_company;
                $surname = $fallback_surname;
            }
        }

        if (empty($customer_name)) {
            $customer_name = 'No Customer Data';
        }

        // Check if waybill is in warehouse
        $is_in_warehouse = isset($row->warehouse) && (intval($row->warehouse) == 1 || $row->warehouse === true);

        // Build destination: prefer City, Country. Append route if meaningful (not "Warehoused items").
        $route_desc = isset($row->route_description) ? trim((string)$row->route_description) : '';
        $city_name  = isset($row->customer_city) ? trim((string)$row->customer_city) : '';
        $country    = isset($row->destination_country_name) ? trim((string)$row->destination_country_name) : '';


        $dest_parts = [];
        if ($city_name !== '') {
            $dest_parts[] = $city_name;
        }
        if ($country !== '') {
            $dest_parts[] = $country;
        }
        $dest_label = implode(', ', $dest_parts);

        if ($route_desc !== '' && strcasecmp($route_desc, 'Warehoused items') !== 0) {
            $dest_label = $route_desc . ($dest_label !== '' ? ' — ' . $dest_label : '');
        }
        $waybill_id = isset($row->id) ? intval($row->id) : 0;

        // Use waybill_id for the view link and as the id
        $view_id = $waybill_id;
        $view_page = '08600-Waybill-view';
        $view_param = 'waybill_id';
        $bulk_id = $waybill_id;

        // Get mass and volume
        $total_mass = isset($row->total_mass_kg) ? floatval($row->total_mass_kg) : 0;
        $total_volume = isset($row->total_volume) ? floatval($row->total_volume) : 0;
        $final_is_business = $company_name !== '' && !in_array(strtolower($company_name), ['individual', '1ndividual', 'n/a', 'none', 'private'], true);

        $waybillData[] = [
            'id' => $bulk_id, // Required for bulk management checkboxes
            'waybill_id' => $view_id, // For view links
            'waybill_no' => $row->waybill_no ?? 'N/A',
            'description' => $row->description ?? '',
            'customer_name' => $customer_name,
            'customer_surname' => $final_is_business ? '' : $surname,
            'customer_company' => $company_name,
            // Expose customer_id so table callbacks (e.g. customer_name column) can link to the customer detail page
            'customer_id' => isset($row->customer_id) ? intval($row->customer_id) : 0,
            'email' => $row->customer_email ?? '',
            'email_address' => $row->customer_email ?? '',
            'created_by' => $row->created_by ?? 'Unknown User',
            'destination' => $dest_label !== '' ? $dest_label : '—',
            'approval' => $row->approval ?? 'pending',
            'warehouse' => $row->warehouse ?? '',
            'status' => $row->status ?? 'created',
            'total_mass_kg' => $total_mass,
            'total_volume' => $total_volume,
            'view_page' => $view_page,
            'view_param' => $view_param,
            // Calculate total: product_invoice_amount + misc_total from miscellaneous JSON
            // Store as numeric value - column callback will handle formatting
            'total' => (function ($row) {
                $product_invoice_amount = floatval($row->product_invoice_amount ?? 0);
                $misc_total = 0;

                // Parse miscellaneous JSON to get misc_total
                $miscellaneous_raw = $row->miscellaneous ?? null;
                if (!empty($miscellaneous_raw)) {
                    $misc_data = json_decode($miscellaneous_raw, true);
                    if (is_array($misc_data) && isset($misc_data['misc_total'])) {
                        $misc_total = floatval($misc_data['misc_total']);
                    }
                }

                return $product_invoice_amount + $misc_total;
            })($row),
            'delivery_status' => $row->delivery_status ?? 'No Delivery',
            'truck_number' => $row->truck_number ?? '',
            'delivery_reference' => $row->delivery_reference ?? '',
            'dispatch_date' => $row->dispatch_date ?? '',
            'delivery_id' => $row->delivery_id ?? 0,
            'driver_name' => $row->driver_name ?? '',
            'waybill_id' => $view_id, // Use view_id
            'waybill_no_raw' => $row->waybill_no ?? '',
            'warehouse_action' => $row->warehouse_action ?? '',
            'warehouse_assigned_delivery_id' => $row->warehouse_assigned_delivery_id ?? '',
            'source_table' => $row->source_table ?? '',
            // Add mass and dimensions fields
            'item_length' => $row->item_length ?? 0,
            'item_width' => $row->item_width ?? 0,
            'item_height' => $row->item_height ?? 0,
            'total_mass_kg' => $row->total_mass_kg ?? 0,
            'total_volume' => $row->total_volume ?? 0,
            // Add city for grouping - if in warehouse, show as "Warehoused"
            // Otherwise use city_name if available, or "Unassigned City" if empty
            'city' => $is_in_warehouse ? 'Warehoused' : ($city_name !== '' ? $city_name : 'Unassigned City'),
            // Waybill type
            'waybill_type' => 'regular'
        ];
    }
    // Debug statement removed - was causing JSON parsing errors
?>
    <div class="wrap">
        <div class="<?php echo KIT_Commons::containerClasses(); ?>">
            <?php
            echo KIT_Commons::showingHeader([
                'title' => 'Waybill Dashboard',
                'icon' => KIT_Commons::icon('receipt'),
                'content' => KIT_Modal::render(
                    'create-waybill-modal',
                    'Create New Waybill',
                    kit_render_waybill_multiform([
                        'form_action'          => $form_action,
                        'waybill_id'           => '',
                        'is_edit_mode'         => '0',
                        'waybill'              => '{}',
                        'customer_id'          => '0',
                        'is_existing_customer' => '0',
                        'customer'             => $customers,
                        'is_modal' => true
                    ]),
                    '3xl'
                )
            ]);
            ?>
            <div class="ajaxReload">
                <?php
                $waybill_stats = [
                    [
                        'title' => 'Total Waybills',
                        'value' => number_format(KIT_Waybills::get_waybill_count()),
                        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        'color' => 'blue'
                    ],
                    [
                        'title' => 'Recent Waybills',
                        'value' => number_format(KIT_Waybills::get_recent_waybill_count()),
                        'subtitle' => 'Last 7 days',
                        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                        'color' => 'green'
                    ],
                    [
                        'title' => 'Pending',
                        'value' => number_format(KIT_Waybills::get_pending_waybill_count()),
                        'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        'color' => 'yellow'
                    ]
                ];
                echo KIT_QuickStats::render($waybill_stats, 'Waybill Overview', [
                    'grid_cols' => 'grid-cols-1 sm:grid-cols-3 md:grid-cols-3',
                    'gap' => 'gap-4'
                ]);
                ?>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var grid = document.querySelector('.kit-dashboard-kpis .grid') || document.querySelector('.wrap .grid');
                    if (!grid) return;
                });
                </script>
                <div class="space-y-4">
                    <?php
                    // Define actions
                    $summary_url = plugins_url('pdf-summary.php', dirname(__FILE__));
                    $actions = [
                        [
                            'label' => 'Download',
                            'title' => 'Download summary PDF',
                            'target' => '_blank',
                            'href' => $summary_url . '?waybill_no={waybill_no_raw}',
                            'class' => 'text-xs font-medium text-green-600 hover:text-green-800 hover:underline'
                        ],
                        [
                            'label' => 'View',
                            'title' => 'View waybill',
                            'target' => '_blank',
                            'href' => '?page=08600-Waybill-view&waybill_id={waybill_id}',
                            'class' => 'text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline',
                            'callback' => function ($value, $row, $rowIndex) {
                                // Use view_page and view_param from row data
                                $view_page = isset($row['view_page']) ? $row['view_page'] : '08600-Waybill-view';
                                $view_param = isset($row['view_param']) ? $row['view_param'] : 'waybill_id';
                                $waybill_id = isset($row['waybill_id']) ? $row['waybill_id'] : 0;

                                return '?page=' . esc_attr($view_page) . '&' . esc_attr($view_param) . '=' . intval($waybill_id);
                            }
                        ],
                        [
                            'label' => 'Delete',
                            'title' => 'Delete waybill',
                            'href' => '?page=08600-waybill-manage&delete_waybill={waybill_no_raw}',
                            'class' => 'text-xs font-medium text-red-600 hover:text-red-800 hover:underline',
                            'onclick' => 'return confirm("Are you sure you want to delete this waybill?")'
                        ]
                    ];
                    // Use standardized column definitions from KIT_Commons
                    // Waybill # automatically includes status badge below
                    $columns = KIT_Commons::getColumns([
                        'waybill_no',
                        'mass_and_dimensions',
                        'destination' => [
                            'label' => 'City',
                            'callback' => function ($value, $row, $rowIndex) {
                                $row = is_object($row) ? (array) $row : $row;
                                $city_name = $row['city'] ?? '';
                                if (empty($city_name) && !empty($value)) {
                                    if (strpos($value, ',') !== false) {
                                        list($country, $city) = array_map('trim', explode(',', $value, 2));
                                        $city_name = $city;
                                    } else {
                                        $city_name = $value;
                                    }
                                }

                                $delivery_reference = $row['delivery_reference'] ?? '';
                                $delivery_id = $row['delivery_id'] ?? 0;

                                $html = '<div class="flex flex-col">';
                                $html .= '<span class="font-bold text-gray-900">' . esc_html($city_name ?: '—') . '</span>';

                                if (!empty($delivery_reference)) {
                                    $delivery_url = $delivery_id > 0
                                        ? admin_url('admin.php?page=kit-deliveries&view_delivery=' . intval($delivery_id))
                                        : '#';
                                    $html .= '<a href="' . esc_url($delivery_url) . '" target="_blank" rel="noopener" class="text-[10px] font-bold text-blue-600 hover:text-blue-800 hover:underline">' . esc_html($delivery_reference) . '</a>';
                                }

                                $html .= '</div>';
                                return $html;
                            }
                        ],
                        'total' => ['cell_class' => 'text-right text-xs whitespace-nowrap']
                    ]);
                    // Insert Description column after Waybill # (so it appears as second data column)
                    $description_col = [
                        'label' => 'Description',
                        'sortable' => true,
                        'searchable' => true,
                        'header_class' => 'text-left whitespace-nowrap min-w-[220px]',
                        'cell_class' => 'text-left text-sm min-w-[220px] max-w-[420px] whitespace-normal break-words',
                        'callback' => function ($value, $row, $rowIndex) {
                            $row = is_object($row) ? (array) $row : $row;
                            $desc = $row['description'] ?? $value ?? '';
                            return esc_html($desc ?: '—');
                        }
                    ];
                    $columns = array_merge(
                        array_slice($columns, 0, 1, true),
                        ['description' => $description_col],
                        array_slice($columns, 1, null, true)
                    );

                    // Render table (infinite scroll) and pass actions so they appear in the dedicated Actions column
                    if (class_exists('KIT_Unified_Table')) {
                        // #region agent log
                        $main_table_options = [
                            'title' => 'All Waybills',
                            'sync_entity' => 'waybills',
                            'table_class' => 'w-full table-auto border-collapse',
                            'actions' => $actions,
                            'searchable' => true,
                            'sortable' => true,
                            'exportable' => true,
                            'bulk_management' => true,
                            'bulk_actions_list' => ['delete', 'export'],
                            'empty_message' => 'No waybills found',
                            'groupby' => 'city',
                            'group_heading_prefix' => '',
                            'preserve_order' => true,
                            'group_collapsible' => true,
                            'group_collapsed' => false,
                            'table_type' => true
                        ];

                        echo KIT_Unified_Table::infinite($waybillData, $columns, $main_table_options);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bulk invoice functionality
            const selectAllBtn = document.getElementById('select-all-waybills');
            const generateBulkInvoiceBtn = document.getElementById('generate-bulk-invoice');
            const selectedCountSpan = document.getElementById('selected-count');

            // Only proceed if the required elements exist
            if (!selectAllBtn || !generateBulkInvoiceBtn || !selectedCountSpan) {
                console.log('Bulk invoice elements not found, skipping bulk invoice functionality');
                return;
            }

            let selectedWaybills = new Set();

            // Select all functionality
            function updateSelectAllButton() {
                const checkboxes = document.querySelectorAll('.waybill-checkbox');
                const checkedCount = document.querySelectorAll('.waybill-checkbox:checked').length;

                if (checkedCount === 0) {
                    selectAllBtn.textContent = 'Select All';
                } else if (checkedCount === checkboxes.length) {
                    selectAllBtn.textContent = 'Deselect All';
                } else {
                    selectAllBtn.textContent = `Select All (${checkedCount}/${checkboxes.length})`;
                }

                // Update selected count
                selectedCountSpan.textContent = checkedCount;

                // Enable/disable generate button
                generateBulkInvoiceBtn.disabled = checkedCount === 0;
            }

            // Select all button click
            selectAllBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('.waybill-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);

                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                    if (!allChecked) {
                        selectedWaybills.add(checkbox.value);
                    } else {
                        selectedWaybills.delete(checkbox.value);
                    }
                });

                updateSelectAllButton();
            });

            // Individual checkbox change
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('waybill-checkbox')) {
                    if (e.target.checked) {
                        selectedWaybills.add(e.target.value);
                    } else {
                        selectedWaybills.delete(e.target.value);
                    }
                    updateSelectAllButton();
                }
            });

            // Generate bulk invoice
            generateBulkInvoiceBtn.addEventListener('click', function() {
                if (selectedWaybills.size === 0) {
                    alert('Please select at least one waybill to generate a bulk invoice.');
                    return;
                }

                const selectedIds = Array.from(selectedWaybills).join(',');
                const url = '<?php echo dirname(plugin_dir_url(__FILE__)); ?>/includes/customers/pdf-bulkinvoicing.php?selected_ids=' + selectedIds;

                // Open in new window/tab
                window.open(url, '_blank');
            });

            // Initialize
            updateSelectAllButton();
        });
    </script>

<?php
}
