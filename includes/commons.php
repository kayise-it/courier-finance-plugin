<?php
class KIT_Commons
{
    public static function init()
    {
        add_shortcode('showheader', [self::class, 'showingHeader']);
        add_shortcode('kitbutton', [self::class, 'kitButton']);

        // Register AJAX handlers for status updates
        add_action('wp_ajax_update_delivery_status', [self::class, 'ajax_update_delivery_status']);
        add_action('wp_ajax_update_waybill_status', [self::class, 'ajax_update_waybill_status']);
    }

    /**
     * Safely perform string operations on potentially null values
     * 
     * @param string|null $value The string value to operate on
     * @param string $operation The operation to perform ('str_replace', 'strpos', etc.)
     * @param array $params Additional parameters for the operation
     * @return string|int|false The result of the operation or empty string if input is null
     */
    public static function safeStringOperation($value, $operation, $params = [])
    {
        // Ensure value is not null
        if ($value === null) {
            return '';
        }

        // Convert to string if it's not already
        $value = (string) $value;

        switch ($operation) {
            case 'str_replace':
                $search = $params[0] ?? '';
                $replace = $params[1] ?? '';
                // Ensure value is not null before using str_replace
                if ($value === null) {
                    $value = '';
                }
                return str_replace($search, $replace, $value);

            case 'strpos':
                $needle = $params[0] ?? '';
                $offset = $params[1] ?? 0;
                // Ensure value is not null before using strpos
                if ($value === null) {
                    $value = '';
                }
                return strpos($value, $needle, $offset);

            case 'ucfirst':
                return ucfirst($value);

            case 'strtolower':
                return strtolower($value);

            case 'strtoupper':
                return strtoupper($value);

            default:
                return $value;
        }
    }

    public static function getPrimeColor(): string
    {
        $schemaPath = plugin_dir_path(__FILE__) . '../colorSchema.json';

        if (file_exists($schemaPath)) {
            $json = file_get_contents($schemaPath);
            $data = json_decode($json, true);

            if (is_array($data) && !empty($data['primary'])) {
                $hex = trim($data['primary']);
                // simple hex guard (#RGB, #RRGGBB)
                if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex)) {
                    return $hex;
                }
            }
        }

        // fallback
        return '#2563eb';
    }

    // Removed duplicate getSecondaryColor() definition; use canonical below

    // Removed duplicate getAccentColor() definition; use canonical below

    public static function statusBadge($status, $addClass = null, $dataAttributes = [])
    {
        $status = strtolower($status);
        $badge_classes = [
            'rejected' => 'border border-red-300 bg-red-50 text-red-800',
            'pending' => 'border border-yellow-300 bg-yellow-50 text-yellow-800',
            'approved' => 'border border-green-300 bg-green-50 text-green-800',
            'shipped' => 'border border-green-300 bg-green-50 text-green-800',
            'delivered' => 'border border-blue-300 bg-blue-50 text-blue-800',
            'cancelled' => 'border border-red-300 bg-red-50 text-red-800',
            'completed' => 'border border-blue-300 bg-blue-50 text-blue-800'
        ];

        $class = $badge_classes[$status] ?? 'bg-gray-100 text-gray-800';
        $display_text = ucfirst($status);

        // Build data attributes
        $dataAttrs = '';
        foreach ($dataAttributes as $key => $value) {
            $dataAttrs .= ' data-' . $key . '="' . esc_attr($value) . '"';
        }

        // Add status as data attribute for filtering
        $dataAttrs .= ' data-status="' . esc_attr($status) . '"';

        return '<span class="inline-flex items-center px-5 py-2 rounded-lg text-xs font-medium ' . $class . '"' . $dataAttrs . '>'
            . $display_text . '</span>';
    }

    public static function waybillGetStatus($waybillno, $waybillid)
    {
        global $wpdb;
        $waybill = $wpdb->get_var("SELECT `status` FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = '{$waybillno}'");
        $waybill = ($waybill) ?? NULL;
        return $waybill;
    }

    public static function get_countries()
    {
        global $wpdb;
        $countries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kit_operating_countries");
        return $countries;
    }

    public static function get_cities()
    {
        global $wpdb;
        $cities = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kit_operating_cities");
        return $cities;
    }

    /**
     * Get brand primary color from colorSchema.json; fallback if missing
     */
    public static function getPrimaryColor(): string
    {
        $schema_path = plugin_dir_path(__FILE__) . '../colorSchema.json';
        if (file_exists($schema_path)) {
            $data = json_decode(file_get_contents($schema_path), true);
            if (is_array($data) && !empty($data['primary'])) {
                return $data['primary'];
            }
        }
        return '#2563eb';
    }

    /**
     * Canonical secondary color reader used by earlier delegate to avoid duplicate definitions
     */
    public static function getSecondaryColorCanonical(): string
    {
        $schema_path = plugin_dir_path(__FILE__) . '../colorSchema.json';
        if (file_exists($schema_path)) {
            $data = json_decode(file_get_contents($schema_path), true);
            if (is_array($data) && !empty($data['secondary'])) {
                return $data['secondary'];
            }
        }
        return '#111827';
    }

    /**
     * Canonical accent color reader used by earlier delegate to avoid duplicate definitions
     */
    public static function getAccentColorCanonical(): string
    {
        $schema_path = plugin_dir_path(__FILE__) . '../colorSchema.json';
        if (file_exists($schema_path)) {
            $data = json_decode(file_get_contents($schema_path), true);
            if (is_array($data) && !empty($data['accent'])) {
                return $data['accent'];
            }
        }
        return '#10b981';
    }


    // Create a function to display a select box with the statuses for the waybill.status column with options "pending", "quoted", "rejected", "completed" and then update the waybill.status column with the selected status
    public static function waybillQuoteStatus($waybillno, $waybillid, $fontSize = 'text-xs')
    {
        // Check if current user can invoice (ONLY administrator can change invoice status)
        $can_invoice = KIT_User_Roles::can_see_prices();

        $statusesStatus = [
            'pending'   => 'Pending',
            'invoiced'    => 'Invoiced',
            'rejected'  => 'Rejected',
            'completed' => 'Completed',
        ];
        $fontSize = $fontSize ?? 'text-xs';

        $current_status2 = self::waybillGetStatus($waybillno, $waybillid);

        $current_label2 = $statusesStatus[$current_status2] ?? 'Not Invoiced';

        // Get color classes for current status
        $getStatusColors = function ($current_status2) {
            switch ($current_status2) {
                case 'pending':
                    return 'bg-yellow-100 text-yellow-800 border-yellow-300';
                case 'invoiced':
                    return 'bg-blue-100 text-blue-800 border-blue-300';
                case 'rejected':
                    return 'bg-red-100 text-red-800 border-red-300';
                case 'completed':
                    return 'bg-green-100 text-green-800 border-green-300';
                default:
                    return 'bg-gray-100 text-gray-800 border-gray-300';
            }
        };

        $current_colors = $getStatusColors($current_status2);

        // If user cannot invoice (only admins can), return status badge only
        if (!$can_invoice) {
            return self::statusBadge($current_status2, 'px-6 py-2 ' . $fontSize);
        }
        ob_start(); ?>
        <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" class="quotation-status-form">
            <input type="hidden" name="action" value="waybillQuoteStatus_update">
            <input type="hidden" name="waybillno" value="<?= esc_attr($waybillno) ?>">
            <input type="hidden" name="waybillid" value="<?= esc_attr($waybillid) ?>">
            <?php wp_nonce_field('update_waybill_approval_nonce'); ?>
            <div class="relative inline-block text-left">
                <div>
                    <button type="button" 
                            id="quote-button-<?= esc_attr($waybillid) ?>" 
                            onclick="toggleDropdownQuote('<?= esc_attr($waybillid) ?>')"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md border shadow-sm bg-white font-medium <?= esc_attr($current_colors) ?> hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span><?= esc_html($current_label2) ?></span>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>

                <div class="hidden origin-top-left absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                    id="quote-dropdown-<?= esc_attr($waybillid) ?>">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        <?php foreach ($statusesStatus as $key => $label): ?>
                            <button type="submit" 
                                    name="status" 
                                    value="<?= esc_attr($key) ?>"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 <?= ($key === $current_status2 ? 'bg-gray-100 text-gray-900' : '') ?>"
                                    role="menuitem">
                                <?= esc_html($label) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form><?php
                return ob_get_clean();
            }


            public static function getWaybillApprovalStatus($waybillno, $waybillid)
            {
                global $wpdb;
                $waybill = $wpdb->get_var("SELECT approval FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = '{$waybillno}' AND id = '{$waybillid}'");

                return $waybill;
            }
            public static function updateWaybillApprovalStatus($waybillno, $waybillid, $status)
            {
                global $wpdb;

                // Sanitize inputs
                $waybillno = sanitize_text_field($waybillno);
                $waybillid = intval($waybillid);
                $status    = sanitize_text_field($status);

                $table = $wpdb->prefix . 'kit_waybills';

                $updated = $wpdb->update(
                    $table,
                    [
                        'approval' => $status,
                        'approval_userid' => get_current_user_id(),
                    ],
                    [
                        'id' => $waybillid,
                        'waybill_no' => $waybillno,
                    ],
                    ['%s', '%d'],
                    ['%d', '%s']
                );

                if ($updated !== false) {
                    return ['success' => true, 'message' => 'Waybill approval updated.'];
                } else {
                    return ['success' => false, 'message' => 'Failed to update approval.'];
                }
            }

            public static function verifyint($value)
            {
                return empty($value) ? 0 : (int) $value;
            }

            public static function verifystring($value)
            {
                return empty($value) ? '' : trim((string) $value);
            }


            public static function waybillApprovalStatus($waybillno, $waybillid, $prevApproval, $fontSize = 'text-sm')
            {

                // Check if current user can approve (administrator OR manager can approve)
                $can_approve = KIT_User_Roles::can_approve();

                // if current user cannot approve, return the status badge with the previous approval status
                // Note: Managers CAN approve, only Data Capturers cannot
                if (!$can_approve) {
                    return self::statusBadge($prevApproval, 'px-6 py-2 ' . $fontSize);
                }

                // Output a form with a dropdown to change status
                $statuses = [
                    'pending'   => 'Not Approved',
                    'approved'  => 'Approved',
                    'rejected'  => 'Rejected',
                    'completed' => 'Completed'
                ];

                $current_status = self::getWaybillApprovalStatus($waybillno, $waybillid);

                $current_label = $statuses[$current_status] ?? 'Unkno23wn';


                // Get color classes for current status
                $getStatusColors = function ($status) {
                    switch ($status) {
                        case 'pending':
                            return 'bg-yellow-100 text-yellow-800 border-yellow-300';
                        case 'approved':
                            return 'bg-green-100 text-green-800 border-green-300';
                        case 'rejected':
                            return 'bg-red-100 text-red-800 border-red-300';
                        case 'completed':
                            return 'bg-green-100 text-green-800 border-green-300';
                        default:
                            return 'bg-gray-100 text-gray-800 border-gray-300';
                    }
                };

                $current_colors = $getStatusColors($current_status);

                ob_start(); ?>
        <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" id="waybill-approval-form">
            <input type="hidden" name="action" value="update_WaybillApproval">
            <input type="hidden" name="waybillno" value="<?= esc_attr($waybillno) ?>">
            <input type="hidden" name="waybillid" value="<?= esc_attr($waybillid) ?>">
            <?php wp_nonce_field('update_waybill_approval_nonce'); ?>

            <div class="relative inline-block text-left">
                <div>
                    <button type="button" 
                            id="approval-button-<?= esc_attr($waybillid) ?>" 
                            onclick="toggleDropdownApproval('<?= esc_attr($waybillid) ?>')"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md border shadow-sm bg-white font-medium <?= esc_attr($current_colors) ?> hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span><?= esc_html($current_label) ?></span>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>

                <div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                    id="approval-dropdown-<?= esc_attr($waybillid) ?>">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        <?php foreach ($statuses as $key => $label): ?>
                            <button type="submit" 
                                    name="status" 
                                    value="<?= esc_attr($key) ?>"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 <?= ($key === $current_status ? 'bg-gray-100 text-gray-900' : '') ?>"
                                    role="menuitem">
                                <?= esc_html($label) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>

        <script>
            if (typeof window.waybillDropdownsInitialized === 'undefined') {
                window.waybillDropdownsInitialized = true;
                
                function toggleDropdownQuote(waybillId) {
                    console.log('toggleDropdownQuote called with ID:', waybillId);
                    
                    const dropdownQuote = document.getElementById('quote-dropdown-' + waybillId);
                    const buttonQuote = document.getElementById('quote-button-' + waybillId);
                    
                    console.log('Quote dropdown element:', dropdownQuote);
                    console.log('Quote button element:', buttonQuote);

                    if (!dropdownQuote || !buttonQuote) {
                        console.error('Quote dropdown or button not found!');
                        return;
                    }

                    // Close all other quote dropdowns
                    document.querySelectorAll('[id^="quote-dropdown-"]').forEach(d => {
                        if (d.id !== 'quote-dropdown-' + waybillId) {
                            d.classList.add('hidden');
                        }
                    });

                    // Toggle current dropdown
                    dropdownQuote.classList.toggle('hidden');
                    console.log('Quote dropdown toggled, hidden class:', dropdownQuote.classList.contains('hidden'));
                }

                function toggleDropdownApproval(waybillId) {
                    console.log('toggleDropdownApproval called with ID:', waybillId);
                    
                    const dropdownApproval = document.getElementById('approval-dropdown-' + waybillId);
                    const buttonApproval = document.getElementById('approval-button-' + waybillId);
                    
                    console.log('Approval dropdown element:', dropdownApproval);
                    console.log('Approval button element:', buttonApproval);

                    if (!dropdownApproval || !buttonApproval) {
                        console.error('Approval dropdown or button not found!');
                        return;
                    }

                    // Close all other approval dropdowns
                    document.querySelectorAll('[id^="approval-dropdown-"]').forEach(d => {
                        if (d.id !== 'approval-dropdown-' + waybillId) {
                            d.classList.add('hidden');
                        }
                    });

                    // Toggle current dropdown
                    dropdownApproval.classList.toggle('hidden');
                    console.log('Approval dropdown toggled, hidden class:', dropdownApproval.classList.contains('hidden'));
                }
                
                // Make functions globally available
                window.toggleDropdownQuote = toggleDropdownQuote;
                window.toggleDropdownApproval = toggleDropdownApproval;
                
                // Close dropdowns when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('[id^="approval-dropdown-"]') && !e.target.closest('[id^="approval-button-"]') &&
                        !e.target.closest('[id^="quote-dropdown-"]') && !e.target.closest('[id^="quote-button-"]')) {
                        document.querySelectorAll('[id^="approval-dropdown-"], [id^="quote-dropdown-"]').forEach(d => {
                            d.classList.add('hidden');
                        });
                    }
                });
            }
        </script>
    <?php
                return ob_get_clean();
            }

            public static function tick()
            {
    ?>
        <span class="w-[13px] h-[13px] rounded-full bg-green-600 text-white flex items-center justify-center text-[10px]">
            ✓
        </span>

    <?php
            }
            public static function update_waybillApproval()
            {
                global $wpdb;

                $waybillid = intval($_POST['waybillid']);
                $waybillno = sanitize_text_field($_POST['waybillno']);
                $status = sanitize_text_field($_POST['status']);
                $userId = get_current_user_id();

                $table = $wpdb->prefix . 'kit_waybills';

                $updated = $wpdb->update(
                    $table,
                    [
                        'approval' => $status,
                        'approval_userid' => $userId,
                    ],
                    [
                        'id' => $waybillid,
                        'waybill_no' => $waybillno,
                    ],
                    ['%s', '%d'],
                    ['%d', '%s']
                );

                if ($updated !== false) {
                    wp_send_json_success(['message' => 'Waybill approval updated.']);
                } else {
                    wp_send_json_error(['message' => 'Failed to update approval.']);
                }
            }

            /**
             * Renders a square button box styled as a radio or checkbox input with info inside.
             *
             * Example usage:
             * KIT_Commons::ButtonBox([
             *     'name' => 'include_sadc',
             *     'value' => '1',
             *     'min_desc' => 'R50',
             *     'data_target' => 'include_sadc',
             *     'checked' => false,
             *     'type' => 'checkbox',
             *     'class' => 'fee-option',
             * ], 'Waybill Fee');
             *
             * Output HTML:
             * <label class="fee-option ...">
             *   <input type="checkbox" ...>
             *   <div class="flex flex-col items-center justify-center h-full w-full pointer-events-none">
             *     <span class="font-semibold text-sm mb-1">Waybill Fee</span>
             *     <span class="text-xs text-gray-500 text-center">R50</span>
             *   </div>
             * </label>
             */
            // DEPRECATED: Use renderButton() instead
            public static function buttonBox($label, $highlight = '')
            {
                $type = ($highlight === 'highlight') ? 'warning' : 'secondary';
                $classes = ($highlight === 'highlight') ? 'bg-yellow-400 text-black hover:bg-yellow-500' : '';
                
                return self::renderButton($label, $type, 'sm', [
                    'classes' => $classes
                ]);
            }

            public static function DestinationButtonBox($atts = [])
            {
                $atts = shortcode_atts([
                    'name'               => '',
                    'delivery_reference' => '',
                    'direction_id'       => 0,
                    'dispatch_date'      => '',
                    'status'             => false,
                    'description'        => '',
                    'checked'            => false, // Allow custom default selection
                    'class'              => '',
                    'onclick'            => '',
                ], $atts);

                $input_id = $atts['direction_id'] ? 'btnbox_' . $atts['direction_id'] : uniqid('btnbox_');

                ob_start();
    ?>
        <input
            type="radio"
            name="<?php echo esc_attr($atts['name']); ?>"
            id="<?php echo esc_attr($input_id); ?>"
            value="<?php echo esc_attr($atts['direction_id']); ?>"
            class="sr-only peer"
            <?php echo $atts['checked'] ? 'checked' : ''; ?>>
        <label <?php echo $atts['onclick'] ? 'onclick="' . $atts['onclick'] . '"' : ''; ?> for="<?php echo esc_attr($input_id); ?>" class="<?= $atts['class'] ?>  bg-white w-[100px] h-[100px] rounded-[5px] border-2 border-gray-300 cursor-pointer relative flex items-center justify-center text-center text-[11px] font-medium leading-tight hover:shadow-md transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-100 peer-checked:shadow-lg">
            <div>
                <h4><?= esc_html($atts['description']); ?></h4>
                <div class="font-bold text-[12px]"><?= esc_html(date('d M Y', strtotime($atts['dispatch_date']))); ?></div>
                <div class="text-gray-500"><?= esc_html(ucfirst($atts['status'])); ?></div>
            </div>
        </label>
    <?php
                return ob_get_clean();
            }


            public static function TextAreaField($atts = [])
            {
                $atts = shortcode_atts([
                    'label' => '',
                    'name'  => '',
                    'id'    => '',
                    'type'  => 'text',
                    'value' => '',
                    'class' => '',
                    'special' => '',
                    'is_dynamic' => false, // New parameter for dynamic fields
                    'dynamic_group' => '', // Group name for dynamic fields
                    'dynamic_type' => 'text', // Type for dynamic fields (when is_dynamic=true)
                    'label_class' => '', // Class for the label
                ], $atts);

                $labelClass = self::labelClass();
                $inputClass = self::inputClass();

                ob_start(); ?>

        <div class="<?php echo esc_attr($atts['class']); ?>">
            <?php if (!empty($atts['label'])): ?>
                <label for="<?php echo esc_attr($atts['id']); ?>" class="<?php echo esc_attr(trim($labelClass . ' ' . $atts['label_class'])); ?>">
                    <?php echo esc_html($atts['label']); ?>
                </label>
            <?php endif; ?>
            <textarea
                name="<?php echo esc_attr($atts['name']); ?>"
                id="<?php echo esc_attr($atts['id']); ?>"
                class="<?php echo esc_attr(trim($inputClass . ' ' . $atts['class'])); ?>"
                <?php echo $atts['special']; ?>><?php echo esc_attr($atts['value']); ?></textarea>
        </div>
    <?php
                return ob_get_clean();
            }

            public static function compareCharges($mass_charge, $volume_charge)
            {
                $mass = floatval($mass_charge);
                $volume = floatval($volume_charge);

                if (abs($mass - $volume) < 0.0001) {
                    return ['mass' => false, 'volume' => false, 'equal' => true];
                }

                return [
                    'mass' => $mass > $volume,
                    'volume' => $volume > $mass,
                    'equal' => false,
                ];
            }

            public static function LText($atts)
            {
                $atts = shortcode_atts([
                    'label' => '',
                    'value' => '',
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                    'is_dynamic' => false, // New parameter for dynamic fields
                    'allow_html' => false, // New parameter to allow HTML in value
                ], $atts);

                $labelClass = self::labelClass();

                // Standard input
                $value = $atts['allow_html'] ? ($atts['value'] ?? '') : htmlspecialchars($atts['value'] ?? '');
                return '<label class="' . esc_attr($labelClass) . ' ' . $atts['classlabel'] . ' ">' .
                    esc_html($atts['label']) . '</label>' .
                    '<p class="m-0 p-0 ' . esc_attr($atts['classP']) . '">' . $value . '</p>';
            }

            public static function Linput($atts)
            {
                $atts = shortcode_atts([
                    'label' => '',
                    'name'  => '',
                    'id'    => '',
                    'type'  => 'text',
                    'value' => '',
                    'class' => '',
                    'special' => '',
                    'onclick' => '',
                    'tabindex' => '',
                    'is_dynamic' => false, // New parameter for dynamic fields
                    'dynamic_group' => '', // Group name for dynamic fields
                    'dynamic_type' => 'text', // Type for dynamic fields (when is_dynamic=true)
                    'label_class' => '', // Class for the label
                ], $atts);

                $labelClass = self::labelClass();
                $inputClass = self::inputClass();

                // Standard input
                if (!$atts['is_dynamic']) {
                    return '<label for="' . esc_attr($atts['id']) . '" class="' . esc_attr($labelClass) . " " . esc_attr($atts['label_class']) . '">' .
                        esc_html($atts['label']) . '</label>' .
                        '<input step="0.01" type="' . esc_attr($atts['type']) . '" name="' . esc_attr($atts['name']) .
                        '" id="' . esc_attr($atts['id']) . '" value="' . esc_attr($atts['value']) .
                        '" class="' . esc_attr($inputClass) . ' ' . esc_attr($atts['class']) . '" ' . ($atts['onclick'] ? 'onclick="' . $atts['onclick'] . '" ' : '') .
                        esc_attr($atts['special']) . ' tabindex="' . esc_attr($atts['tabindex']) . '"/>';
                }

                // Dynamic input field (part of a group)
                return '<input type="' . esc_attr($atts['dynamic_type']) . '" name="' .
                    esc_attr($atts['dynamic_group']) . '[' . esc_attr($atts['name']) . '][]" ' .
                    'value="' . esc_attr($atts['value']) . '" class="' . esc_attr($inputClass) . ' ' .
                    esc_attr($atts['class']) .  '" ' . esc_attr($atts['special']) . ' ' . ($atts['onclick'] ? 'onclick="' . $atts['onclick'] . '" ' : '') . ' tabindex="' . esc_attr($atts['tabindex']) . '"/>';
            }

            public static function miscItemsControl($options = [])
            {
                $defaults = [
                    'container_id' => 'misc-items-container',
                    'button_id' => 'add-misc-btn',
                    'group_name' => 'misc_items',
                    'existing_items' => [],
                    'input_class' => '' // Additional classes for inputs
                ];
                $options = wp_parse_args($options, $defaults);


                ob_start();
    ?>
        <div class="misc-items-wrapper">
            <label class="<?php echo esc_attr(self::labelClass()); ?>">Miscellaneous Items</label>
            <div id="<?php echo esc_attr($options['container_id']); ?>" class="misc-items-list">
                <?php foreach ($options['existing_items'] as $index => $item): ?>
                    <div class="misc-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                        <?php
                        // Description input
                        echo self::Linput([
                            'is_dynamic' => true,
                            'dynamic_group' => $options['group_name'],
                            'name' => 'misc_item',
                            'value' => $item['misc_item'] ?? '',
                            'dynamic_type' => 'text',
                            'class' => $options['input_class'],
                            'special' => 'style="flex: 2;" placeholder="Item description"'
                        ]);

                        // Amount input
                        echo self::Linput([
                            'is_dynamic' => true,
                            'dynamic_group' => $options['group_name'],
                            'name' => 'misc_price',
                            'value' => $item['misc_price'] ?? '',
                            'dynamic_type' => 'number',
                            'class' => $options['input_class'],
                            'special' => 'style="flex: 1;" placeholder="Amount"'
                        ]);

                        // Qty input
                        echo self::Linput([
                            'is_dynamic' => true,
                            'dynamic_group' => $options['group_name'],
                            'name' => 'misc_quantity',
                            'value' => $item['misc_quantity'] ?? '',
                            'dynamic_type' => 'number',
                            'class' => $options['input_class'],
                            'special' => 'style="flex: 1;" placeholder="1"'
                        ]);
                        ?>
                        <?php echo KIT_Commons::renderButton('×', 'danger', 'sm', [
                            'type' => 'button',
                            'classes' => 'remove-misc-btn',
                            'gradient' => true
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php echo KIT_Commons::renderButton('+ Add Misc Item', 'primary', 'sm', [
                'id' => $options['button_id'],
                    'type' => 'button',
                'gradient' => true
            ]); ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const addBtn = document.getElementById('<?php echo esc_js($options['button_id']); ?>');
                const container = document.getElementById('<?php echo esc_js($options['container_id']); ?>');
                const totalTargetId = 'misc-total';

                function toNumber(value) {
                    const num = parseFloat(String(value).replace(/,/g, '.'));
                    return isNaN(num) ? 0 : num;
                }

                function recalcMiscTotal() {
                    let total = 0;
                    container.querySelectorAll('.misc-item').forEach((row) => {
                        const priceInput = row.querySelector('input[name^="<?php echo esc_js($options['group_name']); ?>[misc_price]"]');
                        const qtyInput = row.querySelector('input[name^="<?php echo esc_js($options['group_name']); ?>[misc_quantity]"]');
                        const price = toNumber(priceInput ? priceInput.value : 0);
                        const qty = parseInt(qtyInput ? qtyInput.value : 0, 10) || 0;
                        total += price * qty;
                    });

                    const target = document.getElementById(totalTargetId);
                    if (target) {
                        target.textContent = total.toFixed(2);
                    }
                }

                addBtn.addEventListener('click', () => {
                    const newItem = document.createElement('div');
                    newItem.className = 'misc-item';
                    newItem.style = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';
                    newItem.innerHTML = `
                    <input type="text" name="<?php echo esc_js($options['group_name']); ?>[misc_item][]" 
                           class="<?php echo esc_js(self::inputClass() . ' ' . $options['input_class']); ?>" 
                           style="flex: 2;" placeholder="Item name">
                    <input type="number" name="<?php echo esc_js($options['group_name']); ?>[misc_price][]" 
                           class="<?php echo esc_js(self::inputClass() . ' ' . $options['input_class']); ?>" 
                           style="flex: 1;" placeholder="Amount">
                    <input type="number" name="<?php echo esc_js($options['group_name']); ?>[misc_quantity][]" 
                           class="<?php echo esc_js(self::inputClass() . ' ' . $options['input_class']); ?>" 
                           style="flex: 1;" placeholder="Qty">
                    <button type="button" class="remove-misc-btn bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-200">×</button>
                `;
                    container.appendChild(newItem);
                    recalcMiscTotal();
                });

                // Event delegation for remove buttons
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('remove-misc-btn')) {
                        e.target.closest('.misc-item').remove();
                        recalcMiscTotal();
                    }
                });

                // Recalculate when user edits price or quantity
                container.addEventListener('input', (e) => {
                    if (
                        e.target.matches('input[name^="<?php echo esc_js($options['group_name']); ?>[misc_price]"]') ||
                        e.target.matches('input[name^="<?php echo esc_js($options['group_name']); ?>[misc_quantity]"]')
                    ) {
                        recalcMiscTotal();
                    }
                });

                // Initial calculation on load
                recalcMiscTotal();
            });
        </script>
    <?php
                return ob_get_clean();
            }




            //H2 Bold text
            public static function h2tag($atts)
            {
                $atts = shortcode_atts([
                    'title'   => '',
                    'class'    => '',
                    'content' => '', // HTML content like modals or buttons
                ], $atts);

                return '<h2 class="text-lg font-semibold text-gray-800 mb-2 ' . $atts['class'] . '">' . $atts['title'] . '</h2>';
            }
            //H4 Bold text
            public static function h4tag($atts)
            {
                $atts = shortcode_atts([
                    'title'   => '',
                    'class'    => '',
                    'content' => '', // HTML content like modals or buttons
                ], $atts);

                return '<h4 class="text-lg font-semibold text-gray-800 mb-2 ' . $atts['class'] . '">' . $atts['title'] . '</h4>';
            }

            public static function sumShowcase($atts)
            {

                echo '<pre>';
                print_r($atts);
                echo '</pre>';
                exit();

    ?>
        <div class="flex flex-col">
            <div class="relative"><span class="text-gray-600 font-bold"><?= $atts['label'] ?></span>
                <div class="floatingPrice">
                    <?php if ($atts['bigMass']): ?>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                            Highest
                        </span>
                    <?php elseif ($atts['bigVolume']): ?>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                            Lowest
                        </span>
                    <?php elseif ($atts['is_equal']): ?>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                            Equal
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="relative">
                <span class="font-medium flex items-center">
                    <?php if ($atts['charge'] === "mass"): ?>
                        <?= KIT_Commons::currency() . ($atts['theRate'] ?? 0) ?> x
                        <?= number_format($atts['howMuch'] ?? 0, 2) ?> kg (<?= KIT_Commons::currency() ?><?= number_format($atts['kolot'], 2) ?>)
                    <?php else: ?>
                        <?= number_format($atts['howMuch'] ?? 0, 2) ?> m³ (<?= KIT_Commons::currency() ?><?= number_format($atts['kolot'], 2) ?>)
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php

            }


            public static function simpleSelect($label, $name, $selectId, $arrayList, $isActive)
            {
                //echo label
                echo '<label for="' . esc_attr($selectId) . '" class="' . self::labelClass() . '">' . esc_html($label) . '</label>';
                echo '<select name="' . $name . '" id="' . $selectId . '" class="' . self::selectClass() . '">';
                foreach ($arrayList as $key => $option) {
                    $selected = ($isActive !== null && (string)$isActive === (string)$key) ? ' selected' : '';
                    echo '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($option) . '</option>';
                }
                echo '</select>';
            }

            //KIT_Commons::SelectInput('','', $shippingDirections);
            public static function SelectInput($label, $name, $selectId, $shippingDirections)
            {
                //echo label
                echo '<label for="' . esc_attr($selectId) . '" class="' . self::labelClass() . '">' . esc_html($label) . '</label>';
                echo '<select name="' . $name . '" id="' . $selectId . '" class="' . self::selectClass() . '">';
                foreach ($shippingDirections as $key => $shipDirection) {
                    echo '<option value="' . esc_attr($shipDirection->id) . '">' . esc_html($shipDirection->description) . '</option>';
                }
                echo '</select>';
            }

            /**
             * Generates a paginated table with the exact styling from the reference template
             * 
             * @param array $data Array of objects or arrays containing the table data
             * @param array $options Configuration options:
             *     - 'fields' (array): Fields to display (required)
             *     - 'items_per_page' (int): Items per page (default: 10)
             *     - 'current_page' (int): Current page number (default: 1)
             *     - 'table_class' (string): CSS class for the table (default: 'min-w-full divide-y divide-gray-200')
             *     - 'actions' (bool): Show actions column (default: false)
             *     - 'show_create_quotation' (bool): Show create quotation button (default: false)
             *     - 'id' (string): ID for the table container (default: 'ajaxtable')
             * @return string HTML output of the table with pagination controls
             */
            public static function paginatedTable($data, $options = [])
            {

                // Default options
                $defaults = [
                    'fields' => [],
                    'items_per_page' => 10,
                    'current_page' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1,
                    'table_class' => 'min-w-full divide-y divide-gray-200',
                    'actions' => false,
                    'show_create_quotation' => false,
                    'id' => 'ajaxtable'
                ];
                $options = array_merge($defaults, $options);

                // Convert data to array if it's an object
                if (is_object($data)) {
                    $data = json_decode(json_encode($data), true);
                }

                // Ensure data is an array
                if (!is_array($data)) {
                    return '<div class="error">Invalid data format provided</div>';
                }

                // Calculate pagination values
                $total_items = count($data);
                $total_pages = ceil($total_items / $options['items_per_page']);
                $options['current_page'] = min($options['current_page'], $total_pages);

                // Get current page data
                $offset = ($options['current_page'] - 1) * $options['items_per_page'];
                $paginated_data = array_slice($data, $offset, $options['items_per_page']);

                // Start building HTML
                $html = '';

                // Top pagination controls
                $html .= '<div class="tablenav top flex flex-wrap items-center justify-between gap-4 mb-4">';

                // Items per page dropdown - LEFT SIDE
                $html .= '<div class="flex items-center space-x-2">';
                $html .= '<label for="items-per-page" class="text-xs text-gray-600 whitespace-nowrap">Items per page:</label>';
                $html .= '<form method="get" id="items-per-page-form" style="display:inline;">';

                // Preserve other query params
                foreach ($_GET as $key => $val) {
                    if ($key !== 'items_per_page' && $key !== 'paged') {
                        $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '">';
                    }
                }

                $html .= '<select id="items-per-page" name="items_per_page" class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-1 pl-2 pr-8" onchange="this.form.submit()">';
                $per_page_options = [5, 10, 20, 50, 100];
                foreach ($per_page_options as $option) {
                    $selected = $options['items_per_page'] == $option ? ' selected' : '';
                    $html .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
                }
                $html .= '</select>';
                $html .= '</form>';

                // Item count
                $html .= '<span class="text-xs text-gray-600 whitespace-nowrap">';
                $html .= number_format($total_items) . ' item' . ($total_items !== 1 ? 's' : '');
                $html .= '</span>';
                $html .= '</div>'; // End left side

                // Pagination links - RIGHT SIDE
                $html .= '<div class="flex items-center space-x-1">';

                if ($total_pages > 1) {
                    $pagination_args = [
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $options['current_page'],
                        'total' => $total_pages,
                        'prev_next' => true,
                        'prev_text' => '<span class="px-3 py-1 rounded border-gray-300 bg-white text-gray-700 hover:bg-gray-50">&laquo; Previous</span>',
                        'next_text' => '<span class="px-3 py-1 rounded border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Next &raquo;</span>',
                        'add_args' => ['items_per_page' => $options['items_per_page']],
                        'type' => 'array'
                    ];

                    $pagination_links = paginate_links($pagination_args);

                    if ($pagination_links) {
                        foreach ($pagination_links as $link) {
                            // Add styling to current page
                            // Ensure link is not null before using string functions
                            $link = (string)($link ?? '');
                            if ($link !== '' && strpos($link, 'current') !== false) {
                                $link = str_replace('page-numbers current', 'page-numbers current px-3 py-1 rounded border bg-blue-50 border-blue-500 text-blue-600', $link);
                            } else if ($link !== '') {
                                $link = str_replace('page-numbers', 'page-numbers px-3 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50', $link);
                            }
                            $html .= $link;
                        }
                    }
                }

                $html .= '</div>'; // End right side
                $html .= '</div>'; // End tablenav

                // Table
                $html .= '<table id="' . esc_attr($options['id']) . '" class="' . esc_attr($options['table_class']) . '">';
                $html .= '<thead class="bg-gray-50"><tr>';

                                        // Headers
                        foreach ($options['headers'] as $field) {
                            if ($field !== 'customer_surname') {
                                $html .= '<th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">';
                                // Ensure field is not null before using string functions
                                $field = (string)($field ?? '');
                                if ($field !== '') {
                                    $html .= esc_html(ucfirst(str_replace('_', ' ', $field)));
                                }
                                $html .= '</th>';
                            }
                        }


                if ($options['actions']) {
                    $html .= '<th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
                }

                $html .= '</tr></thead>';
                $html .= '<tbody>';

                // Table rows
                foreach ($paginated_data as $item) {
                    // Convert to object if it's an array
                    if (is_array($item)) {
                        $item = (object) $item;
                    }

                    $html .= '<tr class="bg-white" data-waybill-id="' . esc_attr($item->waybill_id ?? '') . '">';

                    foreach ($options['headers'] as $field) {

                        /* If the $field is Waybill data and not customer data, then show the waybill data */
                        if ($field !== 'customer_surname') {
                            $html .= '<td class="px-4 py-3 whitespace-nowrap"><div class="text-xs">';
                            if ($field === 'approval') {
                                if (current_user_can('administrator')) {
                                    $waybillNo = $field !== '' ? str_replace(' ', '_', $field) : '';
                                    $html .= self::waybillApprovalStatus($item->waybill_no, $item->waybill_id ?? 0, 'quoted', $item->approval ?? '', 'select');
                                } else {
                                    $html .= self::statusBadge($item->approval ?? '');
                                }
                            } elseif ($field === 'waybill no') {

                                $waybillNo = $field !== '' ? str_replace(' ', '_', $field) : '';
                                $html .= '<a href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . ($item->waybill_id ?? '') . '&waybill_atts=view_waybill') . '" target="_blank" style="color:inherit; text-decoration:none;">';
                                $html .= '<span class="font-medium text-blue-600" style="color:inherit;">' . esc_html($item->$waybillNo ?? '') . '</span>';
                                if (!empty($item->created_at)) {
                                    $html .= '<div class="text-xs text-gray-500 mt-1">' . date('M d', strtotime($item->created_at)) . '</div>';
                                }
                                $html .= '</a>';
                            } elseif ($field === 'customer_name') {
                                $html .= '<a href="?page=all-customer-waybills&amp;cust_id=' . esc_attr($item->customer_id ?? '') . '" target="_blank" style="color:inherit; text-decoration:none;">';
                                $html .= '<span class="font-medium text-blue-600">' . esc_html($item->customer_name ?? '') . '</span>';
                                if (!empty($item->customer_surname)) {
                                    $html .= '<div class="text-xs text-gray-500 truncate max-w-xs" title="' . esc_attr($item->customer_surname) . '">';
                                    $html .= esc_html($item->customer_surname);
                                    $html .= '</div>';
                                }
                                $html .= '</a>';
                            } elseif ($field === 'total') {
                                $html .= '<span class="text-bold text-blue-600">' . self::currency() . '</span>';
                                $html .= '<span class="text-xs text-gray-500">' . (!empty($item->waybill_no) ? KIT_Waybills::get_total_cost_of_waybill($item->waybill_no) : '') . '</span>';
                            } else {
                                $html .= esc_html($item->$field ?? '');
                            }

                            $html .= '</div></td>';
                        }

                        /* If the $field is Customer data and not waybill data, then show the customer data */
                        if ($field === 'customer_name') {
                            $html .= '<td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">' . esc_html($item->customer_name ?? '') . '</td>';
                        }
                    }

                    if ($options['actions']) {
                        $html .= '<td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500"><div class="flex space-x-2">';

                        // View action
                        $html .= '<a href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . ($item->waybill_id ?? '') . '&waybill_atts=view_waybill') . '" class="text-blue-600 hover:text-blue-900" title="View">';
                        $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
                        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
                        $html .= '</svg></a>';

                        // Print action
                        $html .= '<a href="' . plugin_dir_url(__FILE__) . '../pdf-generator.php?waybill_no=' . ($item->waybill_id ?? '') . '&pdf_nonce=' . wp_create_nonce('pdf_nonce') . '" target="_blank" class="text-indigo-600 hover:text-indigo-900" title="Print">';
                        $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />';
                        $html .= '</svg></a>';

                        // Create quotation button (for admins/managers)
                        if (($options['show_create_quotation']) && (current_user_can('administrator') || current_user_can('manager'))) {
                            $html .= self::renderButton('Quote', 'success', 'sm', [
                                'title' => 'Create Quotation',
                                'data-waybill-id' => $item->waybill_id ?? '',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                                'iconPosition' => 'left',
                                'classes' => 'create-quotation',
                                'gradient' => true
                            ]);
                        }

                        // Delete button
                        $current_user = wp_get_current_user();
                        $html .= self::deleteWaybillGlobal($item->waybill_id ?? 0, $item->waybill_no ?? '', $item->delivery_id ?? 0, $current_user->ID);

                        $html .= '</div></td>';
                    }

                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';

                return $html;
            }

            public static function Ratebox()
            {
                //return a input that that uses $atts['name'], $atts['type'], $atts['label']
    ?>

    <?php
                return '<div class="asdasd"></div>';
            }

            public static function selectClass()
            {
                return 'text-xs w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded px-3 py-2 bg-white';
            }
            public static function inputClass()
            {
                return 'text-xs w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded px-3 py-2 bg-white';
            }
            /**
             * 08600 Button Theme System - Tailwind CSS Based
             * Following 60-30-10 color rule with Blue (#2563eb) as primary
             */

            // Base button classes
            public static function buttonClass()
            {
                return 'inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-medium text-sm transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
            }

            // Primary button (60% - Main Blue)
            public static function buttonPrimary($size = 'md', $fullWidth = false, $gradient = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                if ($gradient) {
                    return 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 active:from-blue-800 active:to-indigo-800 text-white font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5 focus:ring-blue-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
                }

                return 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:ring-blue-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            // Secondary button (30% - Gray)
            public static function buttonSecondary($size = 'md', $fullWidth = false, $gradient = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                if ($gradient) {
                    return 'bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 active:from-gray-300 active:to-gray-400 text-gray-700 border border-gray-300 hover:border-gray-400 focus:ring-gray-500 font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5 ' . $sizeClasses[$size] . ' ' . $widthClass;
                }

                return 'bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-700 border border-gray-300 hover:border-gray-400 focus:ring-gray-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            // Success button (10% - Green)
            public static function buttonSuccess($size = 'md', $fullWidth = false, $gradient = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                if ($gradient) {
                    return 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 active:from-green-800 active:to-emerald-800 text-white font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5 focus:ring-green-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
                }

                return 'bg-green-600 hover:bg-green-700 active:bg-green-800 text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:ring-green-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            // Danger button (10% - Red)
            public static function buttonDanger($size = 'md', $fullWidth = false, $gradient = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                if ($gradient) {
                    return 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 active:from-red-800 active:to-rose-800 text-white font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5 focus:ring-red-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
                }

                return 'bg-red-600 hover:bg-red-700 active:bg-red-800 text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:ring-red-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            // Warning button (10% - Orange)
            public static function buttonWarning($size = 'md', $fullWidth = false, $gradient = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                if ($gradient) {
                    return 'bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 active:from-orange-800 active:to-amber-800 text-white font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5 focus:ring-orange-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
                }

                return 'bg-orange-600 hover:bg-orange-700 active:bg-orange-800 text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:ring-orange-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            // Outline button variants
            public static function buttonOutlinePrimary($size = 'md', $fullWidth = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                return 'bg-transparent hover:bg-blue-50 text-blue-600 border-2 border-blue-600 hover:border-blue-700 focus:ring-blue-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            public static function buttonOutlineSecondary($size = 'md', $fullWidth = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                return 'bg-transparent hover:bg-gray-50 text-gray-600 border-2 border-gray-300 hover:border-gray-400 focus:ring-gray-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            // Ghost button variants
            public static function buttonGhost($size = 'md', $fullWidth = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                return 'bg-transparent hover:bg-gray-100 text-gray-600 hover:text-gray-800 focus:ring-gray-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            public static function buttonGhostPrimary($size = 'md', $fullWidth = false)
            {
                $sizeClasses = [
                    'sm' => 'px-4 py-2 text-xs',
                    'md' => 'px-6 py-3 text-sm',
                    'lg' => 'px-8 py-4 text-base',
                    'xl' => 'px-10 py-5 text-lg'
                ];

                $widthClass = $fullWidth ? 'w-full' : '';

                return 'bg-transparent hover:bg-blue-50 text-blue-600 hover:text-blue-700 focus:ring-gray-500 ' . $sizeClasses[$size] . ' ' . $widthClass;
            }

            // Tab button styles
            public static function buttonTab($active = false)
            {
                if ($active) {
                    return 'bg-white text-gray-900 shadow-sm border border-gray-200 px-6 py-3 rounded-lg font-medium text-sm transition-all duration-200';
                }
                return 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 px-6 py-3 rounded-lg font-medium text-sm transition-all duration-200';
            }

            // Toggle button styles
            public static function buttonToggle($active = false)
            {
                if ($active) {
                    return 'bg-white text-gray-900 shadow-sm border border-gray-200 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200';
                }
                return 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200';
            }

            // Link button styles
            public static function buttonLink($size = 'md')
            {
                $sizeClasses = [
                    'sm' => 'px-3 py-1 text-xs',
                    'md' => 'px-4 py-2 text-sm',
                    'lg' => 'px-6 py-3 text-base'
                ];

                return 'bg-transparent text-blue-600 hover:text-blue-700 hover:underline focus:ring-blue-500 ' . $sizeClasses[$size];
            }

            // Icon button styles
            public static function buttonIcon($size = 'md')
            {
                $sizeClasses = [
                    'sm' => 'p-2 w-8 h-8',
                    'md' => 'p-3 w-10 h-10',
                    'lg' => 'p-4 w-12 h-12'
                ];

                return 'inline-flex items-center justify-center rounded-lg transition-all duration-200 ' . $sizeClasses[$size];
            }

            // Loading state
            public static function buttonLoading()
            {
                return 'relative text-transparent';
            }

            // Disabled state
            public static function buttonDisabled()
            {
                return 'opacity-50 cursor-not-allowed pointer-events-none';
            }

            /**
             * Generate complete button HTML with consistent styling
             */
            public static function renderButton($text, $type = 'primary', $size = 'md', $options = [])
            {
                $defaults = [
                    'href' => null,
                    'onclick' => null,
                    'disabled' => false,
                    'loading' => false,
                    'fullWidth' => false,
                    'icon' => null,
                    'iconPosition' => 'left', // 'left' or 'right'
                    'classes' => '',
                    'id' => null,
                    'name' => null,
                    'value' => null,
                    'type' => 'button',
                    'gradient' => false, // Enable gradient for primary buttons
                    'modal' => null, // Modal trigger
                    'data-target' => null // Data target attribute
                ];

                $options = array_merge($defaults, $options);

                // Get base classes
                $baseClasses = self::buttonClass();

                // Get type-specific classes
                $typeClasses = '';
                switch ($type) {
                    case 'primary':
                        $typeClasses = self::buttonPrimary($size, $options['fullWidth'], $options['gradient']);
                        break;
                    case 'secondary':
                        $typeClasses = self::buttonSecondary($size, $options['fullWidth'], $options['gradient']);
                        break;
                    case 'success':
                        $typeClasses = self::buttonSuccess($size, $options['fullWidth'], $options['gradient']);
                        break;
                    case 'danger':
                        $typeClasses = self::buttonDanger($size, $options['fullWidth'], $options['gradient']);
                        break;
                    case 'warning':
                        $typeClasses = self::buttonWarning($size, $options['fullWidth'], $options['gradient']);
                        break;
                    case 'outline-primary':
                        $typeClasses = self::buttonOutlinePrimary($size, $options['fullWidth']);
                        break;
                    case 'outline-secondary':
                        $typeClasses = self::buttonOutlineSecondary($size, $options['fullWidth']);
                        break;
                    case 'ghost':
                        $typeClasses = self::buttonGhost($size, $options['fullWidth']);
                        break;
                    case 'ghost-primary':
                        $typeClasses = self::buttonGhostPrimary($size, $options['fullWidth']);
                        break;
                    case 'link':
                        $typeClasses = self::buttonLink($size);
                        break;
                    default:
                        $typeClasses = self::buttonPrimary($size, $options['fullWidth']);
                }

                // Add loading state
                if ($options['loading']) {
                    $typeClasses .= ' ' . self::buttonLoading();
                }

                // Add disabled state
                if ($options['disabled']) {
                    $typeClasses .= ' ' . self::buttonDisabled();
                }

                // Combine all classes
                $allClasses = trim($baseClasses . ' ' . $typeClasses . ' ' . $options['classes']);

                // Build attributes
                $attributes = [];
                if ($options['id']) $attributes[] = 'id="' . esc_attr($options['id']) . '"';
                if ($options['name']) $attributes[] = 'name="' . esc_attr($options['name']) . '"';
                if ($options['value']) $attributes[] = 'value="' . esc_attr($options['value']) . '"';
                if ($options['onclick']) $attributes[] = 'onclick="' . esc_js($options['onclick']) . '"';
                if ($options['disabled']) $attributes[] = 'disabled';
                if ($options['type']) $attributes[] = 'type="' . esc_attr($options['type']) . '"';
                if ($options['modal']) $attributes[] = 'data-modal="' . esc_attr($options['modal']) . '"';
                if ($options['data-target']) $attributes[] = 'data-target="' . esc_attr($options['data-target']) . '"';

                $attributesStr = implode(' ', $attributes);

                // Build content
                $content = '';

                // Add icon if specified
                if ($options['icon'] && $options['iconPosition'] === 'left') {
                    $content .= '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">' . $options['icon'] . '</svg>';
                }

                $content .= '<span>' . esc_html($text) . '</span>';

                // Add icon if specified (right position)
                if ($options['icon'] && $options['iconPosition'] === 'right') {
                    $content .= '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">' . $options['icon'] . '</svg>';
                }

                // Add loading spinner
                if ($options['loading']) {
                    $content .= '<svg class="absolute w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>';
                }

                // Return button HTML
                if ($options['href']) {
                    return '<a href="' . esc_url($options['href']) . '" class="' . $allClasses . '" ' . $attributesStr . '>' . $content . '</a>';
                } else {
                    return '<button class="' . $allClasses . '" ' . $attributesStr . '>' . $content . '</button>';
                }
            }
            public static function labelClass()
            {
                return 'block font-bold text-gray-700 mb-1';
            }

            public static function tbodyClasses()
            {
                return 'bg-white divide-y divide-gray-200'; // Default tbody classes
            }

            public static function tableClasses()
            {
                return 'min-w-full divide-y divide-gray-200';
            }

            public static function trowClasses()
            {
                return 'border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200';
            }
            public static function thClasses()
            {
                return 'px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
            }
            public static function tcolClasses()
            {
                //return 'px-6 py-4 text-sm text-gray-900';
                return 'px-3 py-2 text-sm text-gray-700 whitespace-nowrap';
            }

            public static function yspacingClass()
            {
                return 'space-y-3'; // Default spacing class
            }
            public static function currency()
            {
                return 'R'; // Rands
            }

            /**
             * Check if current user is an admin
             * @return bool
             */
            public static function isAdmin()
            {
                return KIT_User_Roles::is_admin();
            }

            /**
             * Check if current user can see prices
             * @return bool
             */
            public static function canSeePrices()
            {
                return KIT_User_Roles::can_see_prices();
            }

            /**
             * Display waybill total with price visibility control
             * @param float $amount
             * @param string $fallback_text
             * @return string
             */
            public static function displayWaybillTotal($amount, $fallback_text = '***')
            {
                if (self::canSeePrices()) {
                    return self::currency() . ' ' . number_format($amount, 2);
                } else {
                    return $fallback_text;
                }
            }
            public static function container()
            {
                return 'max-w-7xl mx-auto';
            }


            /**
             * Deletes data.
             * 
             * Example usage:
             * KIT_Commons::deleteWaybillGlobal($waybill->id, $waybill->no);
             */

            public static function deleteWaybillGlobal($waybillid, $waybillNo, $waybill_delivery_id, $customer_id)
            {
                ob_start();
    ?>
        <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <input type="hidden" name="action" value="delete_waybill">
            <?php wp_nonce_field('delete_waybill_nonce') ?>
            <input type="hidden" name="waybill_id" value="<?= esc_attr($waybillid) ?>">
            <input type="hidden" name="waybill_no" value="<?= esc_attr($waybillNo) ?>">
            <input type="hidden" name="delivery_id" value="<?= esc_attr($waybill_delivery_id) ?>">
            <input type="hidden" name="user_id" value="<?= esc_attr($customer_id) ?>">
            <?php echo self::renderButton('Delete', 'danger', 'sm', [
                'type' => 'submit',
                'classes' => 'delete-waybill',
                'gradient' => true
            ]); ?>
        </form>
    <?php

            }


            /**
             * Renders a table for waybill tracking and data.
             * 
             * Example usage:
             * KIT_Commons::waybillTrackAndData($waybill);
             * 
             * @param array $waybill Array of waybill data.
             * @return string HTML table for waybill tracking.
             */

            public static function waybillTrackAndData($waybill)
            {
                if (empty($waybill)) {
                    return "No Waybill items";
                }
    ?>
        <table class="<?= self::tableClasses(); ?> w-full" style="border-spacing: 0;">
            <thead>
                <tr class="bg-gray-100" style="font-size: 5px;">
                    <th class="<?= self::thClasses() ?> text-left">Item</th>
                    <th class="<?= self::thClasses() ?> text-center">Quantity</th>
                    <?php if (self::canSeePrices()): ?>
                        <th class="<?= self::thClasses() ?> text-right">Unit Price</th>
                        <th class="<?= self::thClasses() ?> text-right">Sub Total</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="<?= self::tbodyClasses() ?>">
                <?php
                $grand_total = 0;
                foreach ($waybill as $key => $value) {
                    $qty = isset($value['quantity']) ? (float)$value['quantity'] : 0;
                    $unit = isset($value['unit_price']) ? (float)$value['unit_price'] : 0;
                    $sub = $qty * $unit;
                    $grand_total += $sub;
                    ob_start();
                ?>
                    <tr class="border-t border-gray-100">
                        <td class="<?= self::tcolClasses() ?>"><?= $value['item_name'] ?></td>
                        <td class="<?= self::tcolClasses() ?> text-center"><?= number_format($qty, 0) ?></td>
                        <?php if (self::canSeePrices()): ?>
                            <td class="<?= self::tcolClasses() ?> text-right"><?= number_format($unit, 2) ?></td>
                            <td class="<?= self::tcolClasses() ?> text-right"><?= number_format($sub, 2) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="border-t">
                    <?php if (self::canSeePrices()): ?>
                        <td colspan="3" class="<?= self::tcolClasses() ?> text-right font-semibold">Total</td>
                        <td class="<?= self::tcolClasses() ?> text-right font-bold"><?= number_format($grand_total, 2) ?></td>
                    <?php else: ?>
                        <td colspan="2" class="<?= self::tcolClasses() ?> text-right font-semibold">Total</td>
                        <td class="<?= self::tcolClasses() ?> text-right font-bold">***</td>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
    <?php
                return ob_get_clean();
            }

            // DEPRECATED: Use renderButton() instead
            public static function simpleBtn($atts = [])
            {
                $atts = shortcode_atts([
                    'type'    => 'button',
                    'class'   => '',
                    'onclick' => '',
                    'id'      => '',
                    'name'    => '',
                    'text'    => 'Button',
                    'data-target' => '',
                    'disabled' => false,
                ], $atts);

                // Convert to renderButton format
                $type = 'primary';
                $options = [
                    'type' => $atts['type'],
                    'onclick' => $atts['onclick'],
                    'id' => $atts['id'],
                    'name' => $atts['name'],
                    'disabled' => $atts['disabled'],
                    'data-target' => $atts['data-target'],
                    'classes' => $atts['class']
                ];

                return self::renderButton($atts['text'], $type, 'md', $options);
            }


            public static function showingHeader($atts)
            {
                $atts = shortcode_atts([
                    'title'   => '',
                    'desc'    => '',
                    'content' => '', // HTML content like modals or buttons
                ], $atts);

                $desc = is_string($atts['desc']) ? $atts['desc'] : '';
                $gridCols = "";

                //if $atts['content'] not empty, set grid-cols-1 md:grid-cols-2
                if (is_array($atts["content"])) {
                    $gridCols = 'grid-cols-1 md:grid-cols-2';
                } elseif (is_string($atts["content"]) && !empty($atts["content"])) {
                    $gridCols = 'grid-cols-1 md:grid-cols-2';
                }

                ob_start(); ?>
        <header class="bg-white shadow mb-6 rounded-sm">
            <div class="<?php echo self::container(); ?> mx-auto py-3 px-6 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900"><?php echo esc_html($atts['title']); ?></h1>
                <div class="text-gray-600 relative overflow-hidden"><?php echo wp_kses_post($desc); ?></div>

                <?php
                if (!empty($atts['content'])) {
                    echo $atts['content']; // Accepts modal button etc.
                }
                ?>
            </div>
        </header>
        <?php
                return ob_get_clean();
            }

            // DEPRECATED: Use renderButton() instead
            public static function kitButton($atts, $content = '')
            {
                // Convert kitButton parameters to renderButton format
                $type = 'primary';
                if (isset($atts['color'])) {
                    switch ($atts['color']) {
                        case 'red': $type = 'danger'; break;
                        case 'green': $type = 'success'; break;
                        case 'gray': $type = 'secondary'; break;
                        case 'yellow': $type = 'warning'; break;
                        default: $type = 'primary'; break;
                    }
                }
                
                $options = [
                    'href' => $atts['href'] ?? null,
                    'onclick' => $atts['onclick'] ?? null,
                    'disabled' => $atts['disabled'] ?? false,
                    'id' => $atts['id'] ?? null,
                    'name' => $atts['name'] ?? null,
                    'type' => $atts['type'] ?? 'button',
                    'modal' => $atts['modal'] ?? null,
                    'data-target' => $atts['data-target'] ?? null,
                    'classes' => $atts['class'] ?? '',
                    'icon' => null,
                    'iconPosition' => 'left'
                ];
                
                // Convert icon names to SVG paths
                if (isset($atts['icon'])) {
                    switch ($atts['icon']) {
                        case 'plus':
                            $options['icon'] = '<path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />';
                            break;
                        case 'arrow-right':
                            $options['icon'] = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />';
                            break;
                        case 'arrow-left':
                            $options['icon'] = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />';
                            break;
                        case 'trash':
                            $options['icon'] = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />';
                            break;
                        case 'remove':
                            $options['icon'] = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
                            break;
                    }
                }
                
                return self::renderButton($atts['text'] ?? $content, $type, 'md', $options);
            }

            // Future: modal() method
            public static function waybillItemsControl($options = [])
            {
                $defaults = [
                    'container_id' => 'waybill-items-container',
                    'button_id' => 'add-waybill-item',
                    'group_name' => 'waybill_items',
                    'existing_items' => [],
                    'input_class' => self::inputClass(),
                    'label_class' => 'block text-xs font-medium mb-1',
                    'remove_btn_class' => 'bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600',
                    'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700',
                    'style' => 'modern' // 'modern' for step3.php, 'slim' for editWaybill.php
                ];
                $options = wp_parse_args($options, $defaults);

                ob_start();
            ?>
            <?php if ($options['style'] === 'slim'): ?>
                <!-- SLIM TABLE VERSION FOR EDITWAYBILL.PHP -->
                <div class="mb-6" id="step-waybill-items">
                    <!-- Slim Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Waybill Items</h2>
                        <?php echo self::renderButton('Add Item', 'primary', 'sm', [
                            'id' => $options['button_id'],
                            'type' => 'button',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>',
                            'iconPosition' => 'left',
                            'gradient' => true
                        ]); ?>
                    </div>

                    <style>
                        .waybillItemsTable {
                            border-collapse: collapse;
                            border: 1px solid #e2e8f0;
                            border-radius: 0.5rem;
                            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                            width: 100%;
                            table-layout: fixed;
                        }

                        .waybillItemsTable th,
                        .waybillItemsTable td {
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }

                        .th-1,
                        .th-2,
                        .th-3,
                        .th-4,
                        .th-5 {
                            padding: 0.9rem 0.5rem;
                            text-align: left;
                            font-size: 0.75rem;
                            font-weight: 500;
                            color: #6b7280;
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                            border-bottom: 1px solid #e5e7eb;
                        }

                        .th-1,
                        .td-1 {
                            width: 40%;
                        }

                        .th-2,
                        .td-2 {
                            width: 15%;
                        }

                        .th-3,
                        .td-3 {
                            width: 20%;
                        }

                        .th-4,
                        .td-4 {
                            width: 20%;
                        }

                        .th-5,
                        .td-5 {
                            width: 15%;
                        }

                        .td-1,
                        .td-2,
                        .td-3,
                        .td-4,
                        .td-5 {
                            padding-left: 0.5rem;
                            padding-right: 0.5rem;
                            padding-top: 0rem;
                            padding-bottom: 0rem;
                            text-align: left;
                            font-size: 0.75rem;
                            font-weight: 500;
                            color: #6b7280;
                            text-transform: capitalize;
                            letter-spacing: 0.05em;
                            border-bottom: 1px solid #e5e7eb;
                        }
                    </style>

                    <!-- Slim Table -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
                        <table class="waybillItemsTable w-full border-collapse table-fixed">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="th-1">Item Description</th>
                                    <th class="th-2">QTY</th>
                                    <th class="th-3">Unit Price (R)</th>
                                    <th class="th-4">Total</th>
                                    <th class="th-5">Action</th>
                                </tr>
                            </thead>
                            <tbody id="<?php echo esc_attr($options['container_id']); ?>" class="bg-white">
                                <?php foreach ($options['existing_items'] as $index => $item): ?>
                                    <tr class="waybill-item hover:bg-gray-50 border-b border-gray-100">
                                        <td class="td-1">
                                            <input type="text"
                                                name="<?php echo esc_attr($options['group_name']); ?>[<?php echo esc_attr($index); ?>][item_name]"
                                                value="<?php echo esc_attr($item['item_name'] ?? ''); ?>"
                                                placeholder="Item description"
                                                class="w-full px-1 py-0.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                                        </td>
                                        <td class="td-2">
                                            <input type="number"
                                                name="<?php echo esc_attr($options['group_name']); ?>[<?php echo esc_attr($index); ?>][quantity]"
                                                value="<?php echo esc_attr($item['quantity'] ?? 1); ?>"
                                                min="1"
                                                class="w-full px-1 py-0.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center" />
                                        </td>
                                        <td class="td-3">
                                            <input type="number"
                                                name="<?php echo esc_attr($options['group_name']); ?>[<?php echo esc_attr($index); ?>][unit_price]"
                                                value="<?php echo esc_attr($item['unit_price'] ?? 0); ?>"
                                                step="0.01"
                                                min="0"
                                                class="w-full px-1 py-0.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                                        </td>
                                        <td class="px-2 py-1 text-sm text-gray-900 text-center border-r border-gray-100">
                                            R <?php echo number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <?php echo self::renderButton('', 'danger', 'sm', [
                                                'type' => 'button',
                                                'classes' => 'remove-item p-5 rounded',
                                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                                                'gradient' => true
                                            ]); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- Empty State for Slim Version -->
                                <?php if (empty($options['existing_items'])): ?>
                                    <tr id="empty-state-slim" class="empty-state">
                                        <td colspan="5" class="text-center py-8 text-gray-500">
                                            <p class="text-sm">No items added yet. Click "Add Item" to start.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- COMPLETELY NEW MODERN WAYBILL ITEMS DESIGN -->
                <div class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 rounded-2xl shadow-lg border border-blue-100 p-6 mb-6" id="step-waybill-items">
                    <!-- Modern Header with Icon and Stats -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Waybill Items</h2>
                                <p class="text-sm text-gray-600">Add items being shipped with details and pricing</p>
                            </div>
                        </div>

                        <!-- Enhanced Add Item Button -->
                        <?php echo KIT_Commons::renderButton('Add New Item', 'primary', 'lg', [
                            'id' => $options['button_id'],
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>',
                            'iconPosition' => 'left',
                            'gradient' => true
                        ]); ?>
                    </div>

                    <!-- Items Container with Modern Card Design -->
                    <div id="<?php echo esc_attr($options['container_id']); ?>" class="space-y-4">
                        <?php foreach ($options['existing_items'] as $index => $item): ?>
                            <div class="waybill-item bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-all duration-200">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                    <!-- Item Description -->
                                    <div class="md:col-span-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Description</label>
                                        <input type="text"
                                            name="<?php echo esc_attr($options['group_name']); ?>[<?php echo esc_attr($index); ?>][item_name]"
                                            value="<?php echo esc_attr($item['item_name'] ?? ''); ?>"
                                            placeholder="Enter item description..."
                                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                                    </div>

                                    <!-- Quantity -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                        <input type="number"
                                            name="<?php echo esc_attr($options['group_name']); ?>[<?php echo esc_attr($index); ?>][quantity]"
                                            value="<?php echo esc_attr($item['quantity'] ?? 1); ?>"
                                            min="1"
                                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                                    </div>

                                    <!-- Unit Price -->
                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Price (R)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">R</span>
                                            <input type="number"
                                                name="<?php echo esc_attr($options['group_name']); ?>[<?php echo esc_attr($index); ?>][unit_price]"
                                                value="<?php echo esc_attr($item['unit_price'] ?? 0); ?>"
                                                step="0.01"
                                                min="0"
                                                class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                                        </div>
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="md:col-span-1 flex justify-end">
                                        <?php echo self::renderButton('', 'danger', 'sm', [
                                            'type' => 'button',
                                            'classes' => 'remove-item w-10 h-10 rounded-xl shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 flex items-center justify-center',
                                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                                            'gradient' => true
                                        ]); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Empty State -->
                    <div id="empty-state" class="<?php echo empty($options['existing_items']) ? 'block' : 'hidden'; ?> text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No items added yet</h3>
                        <p class="text-gray-600">Click "Add New Item" to start building your waybill</p>
                    </div>
                </div>
            <?php endif; ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    let itemIndex = <?php echo count($options['existing_items']); ?>;
                    const container = document.getElementById('<?php echo esc_js($options['container_id']); ?>');
                    const addBtn = document.getElementById('<?php echo esc_js($options['button_id']); ?>');

                    console.log('Container found:', container);
                    console.log('Add button found:', addBtn);
                    console.log('Initial item index:', itemIndex);

                    function rowTemplate(idx) {
                        <?php if ($options['style'] === 'slim'): ?>
                            // Slim table row template
                            return `
                        <tr class="waybill-item hover:bg-gray-50">
                            <td class="td-1">
                                <input type="text" 
                                       name="<?php echo esc_js($options['group_name']); ?>[${idx}][item_name]" 
                                       placeholder="Item description" 
                                       class="w-full px-1 py-0.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                            </td>
                            <td class="td-2">
                                <input type="number" 
                                       name="<?php echo esc_js($options['group_name']); ?>[${idx}][quantity]" 
                                       value="1" 
                                       min="1"
                                       class="w-full px-1 py-0.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center" />
                            </td>
                            <td class="td-3">
                                <input type="number" 
                                       name="<?php echo esc_js($options['group_name']); ?>[${idx}][unit_price]" 
                                       value="0" 
                                       step="0.01"
                                       min="0"
                                       class="w-full px-1 py-0.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                            </td>
                            <td class="td-4">
                                R 0.00
                            </td>
                            <td class="td-5">
                                <?php echo self::renderButton('', 'danger', 'sm', [
                                    'type' => 'button',
                                    'classes' => 'remove-item p-5 rounded',
                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                                    'gradient' => true
                                ]); ?>
                            </td>
                        </tr>`;
                        <?php else: ?>
                            // Modern card row template
                            return `
                        <div class="waybill-item bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-all duration-200">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                <!-- Item Description -->
                                <div class="md:col-span-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Description</label>
                                    <input type="text" 
                                           name="<?php echo esc_js($options['group_name']); ?>[${idx}][item_name]" 
                                           placeholder="Enter item description..." 
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                                </div>
                                
                                <!-- Quantity -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                    <input type="number" 
                                           name="<?php echo esc_js($options['group_name']); ?>[${idx}][quantity]" 
                                           value="1" 
                                           min="1"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                                </div>
                                
                                <!-- Unit Price -->
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit Price (R)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">R</span>
                                        <input type="number" 
                                               name="<?php echo esc_js($options['group_name']); ?>[${idx}][unit_price]" 
                                               value="0" 
                                               step="0.01"
                                               min="0"
                                               class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                                    </div>
                                </div>
                                
                                <!-- Remove Button -->
                                <div class="md:col-span-1 flex justify-end">
                                    <?php echo self::renderButton('', 'danger', 'sm', [
                                        'type' => 'button',
                                        'classes' => 'remove-item w-10 h-10 rounded-xl shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 flex items-center justify-center',
                                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                                        'gradient' => true
                                    ]); ?>
                                </div>
                            </div>
                        </div>`;
                        <?php endif; ?>
                    }

                    addBtn.addEventListener('click', function() {
                        console.log('Add button clicked, adding item...');
                        const newRow = rowTemplate(itemIndex++);
                        console.log('New row HTML:', newRow);
                        container.insertAdjacentHTML('beforeend', newRow);
                        console.log('Row added to container');
                        validateItems();
                        updateEmptyState();
                        <?php if ($options['style'] === 'slim'): ?>
                            updateSlimTableTotals();
                        <?php endif; ?>
                    });

                    container.addEventListener('click', function(e) {
                        if (e.target.classList.contains('remove-item')) {
                            const row = e.target.closest('.waybill-item');
                            if (row) {
                                row.remove();
                            }
                            validateItems();
                            updateEmptyState();
                            <?php if ($options['style'] === 'slim'): ?>
                                updateSlimTableTotals();
                            <?php endif; ?>
                        }
                    });

                    // Function to update empty state visibility
                    function updateEmptyState() {
                        const items = container.querySelectorAll('.waybill-item');
                        <?php if ($options['style'] === 'slim'): ?>
                            // For slim table, look for the empty state row
                            const emptyState = document.getElementById('empty-state-slim') || container.querySelector('.empty-state');
                        <?php else: ?>
                            // For modern version, look for the empty state div
                            const emptyState = document.getElementById('empty-state');
                        <?php endif; ?>

                        if (emptyState) {
                            if (items.length === 0) {
                                emptyState.classList.remove('hidden');
                                emptyState.style.display = '';
                            } else {
                                emptyState.classList.add('hidden');
                                emptyState.style.display = 'none';
                            }
                        }
                    }

                    // Initialize empty state
                    updateEmptyState();

                    // Toggle Next button active state when items change
                    function getNextBtn() {
                        // Support multiple markup options
                        return document.querySelector('#nextStepButton, .next-step, button[data-next-step], a[data-next-step]');
                    }

                    function validateItems() {
                        const rows = container.querySelectorAll('.waybill-item');
                        let valid = false;
                        rows.forEach(row => {
                            const name = row.querySelector('input[name*="[item_name]"]');
                            const qty = row.querySelector('input[name*="[quantity]"]');
                            const price = row.querySelector('input[name*="[unit_price]"]');
                            const q = parseFloat((qty && qty.value) || '0');
                            const p = parseFloat((price && price.value) || '0');
                            if (name && name.value.trim() !== '' && q > 0 && p >= 0) {
                                valid = true;
                            }
                        });

                        const btn = getNextBtn();
                        if (!btn) return; // nothing to toggle

                        if (valid) {
                            btn.classList.remove('opacity-50', 'opacity-40', 'cursor-not-allowed', 'pointer-events-none', 'btn-disabled', 'bg-gray-300', 'bg-blue-400');
                            btn.classList.add('bg-blue-600');
                            btn.removeAttribute('disabled');
                            btn.setAttribute('aria-disabled', 'false');
                        } else {
                            btn.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none', 'bg-blue-400');
                            btn.classList.remove('bg-blue-600');
                            btn.setAttribute('disabled', 'disabled');
                            btn.setAttribute('aria-disabled', 'true');
                        }
                    }

                    // Re-validate on any input change within the container
                    container.addEventListener('input', function(e) {
                        if (e.target.matches('input')) {
                            validateItems();
                            <?php if ($options['style'] === 'slim'): ?>
                                updateSlimTableTotals();
                            <?php endif; ?>
                        }
                    });

                    // Initial state
                    validateItems();
                    <?php if ($options['style'] === 'slim'): ?>
                        updateSlimTableTotals();

                        // Function to update totals in slim table
                        function updateSlimTableTotals() {
                            const rows = container.querySelectorAll('.waybill-item');
                            rows.forEach(row => {
                                const quantityInput = row.querySelector('input[name*="[quantity]"]');
                                const priceInput = row.querySelector('input[name*="[unit_price]"]');
                                const totalCell = row.querySelector('td:nth-child(4)');

                                if (quantityInput && priceInput && totalCell) {
                                    const quantity = parseFloat(quantityInput.value) || 0;
                                    const price = parseFloat(priceInput.value) || 0;
                                    const total = quantity * price;
                                    totalCell.textContent = `R ${total.toFixed(2)}`;
                                }
                            });
                        }
                    <?php endif; ?>
                });
            </script>
        <?php
                return ob_get_clean();
            }

            public static function customerSelect($customers)
            {
                $atts = shortcode_atts([
                    'label' => 'Select Customer',
                    'name' => 'customer_select',
                    'id' => 'customer-select',
                    'customer' => [],
                    'existing_customer' => "", // New parameter to check if customer is existing
                ], $customers);
                $customer_id = 0;
                if ($customers["existing_customer"]) {
                    $customer_id = $customers['existing_customer'];
                }

                $selectedCustomerId = (isset($customer_id) && $customer_id !== 0) ? $customer_id : 'new';


                $labelClass = self::labelClass();
                $selectClass = self::selectClass();
                $html = '<label for="' . esc_attr($atts['id']) . '" class="' . esc_attr($labelClass) . '">' . esc_html($atts['label']) . '</label>';
                $html .= '<select name="' . esc_attr($atts['name']) . '" id="' . esc_attr($atts['id']) . '" class="' . esc_attr($selectClass) . '">';
                $html .= '<option value="new"' . selected($selectedCustomerId, 'new', false) . '>Add New Customer</option>';
                if (!empty($atts['customer'])) {
                    foreach ($atts['customer'] as $customer) {
                        $html .= '<option value="' . esc_attr($customer->cust_id) . '"';
                        $html .= selected($selectedCustomerId, $customer->cust_id, false);
                        $html .= selected($atts['id'], $customer->cust_id, false); // Compare to cust_id and echo manually
                        $html .= ' data-name="' . esc_attr($customer->name) . '"';
                        $html .= ' data-surname="' . esc_attr($customer->surname) . '"';
                        $html .= ' data-cell="' . esc_attr($customer->cell) . '"';
                        $html .= ' data-address="' . esc_attr($customer->address) . '"';
                        $html .= ' data-email="' . esc_attr($customer->email_address) . '"';
                        $html .= ' data-company-name="' . esc_attr($customer->company_name) . '"';
                        $html .= '>';
                        $html .= esc_html($customer->name . ' ' . $customer->surname); // Optional: visible name
                        $html .= '</option>';
                    }
                }
                $html .= '</select>';
                return $html;
            }

            public static function download_pdf_button($waybillNo, $atts = [])
            {
                // Only administrators who can see prices can access PDFs
                if (!KIT_User_Roles::can_see_prices()) {
                    return '<span class="text-gray-500 text-sm">PDF access restricted to administrators</span>';
                }

                $atts = shortcode_atts([
                    'buttonType' => 'full', // 'icon-only' or 'full'
                    'text' => 'Download PDF',
                ], $atts);

                $downloadIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />';

                if ($atts['buttonType'] === 'icon-only') {
                    return self::renderButton('', 'primary', 'sm', [
                        'href' => admin_url('admin-ajax.php?action=generate_pdf&waybill_no=' . $waybillNo . '&pdf_nonce=' . wp_create_nonce("pdf_nonce")),
                        'icon' => $downloadIcon,
                        'classes' => 'p-2',
                        'gradient' => true
                    ]);
                } else {
                    return self::renderButton($atts['text'], 'primary', 'md', [
                        'href' => admin_url('admin-ajax.php?action=generate_pdf&waybill_no=' . $waybillNo . '&pdf_nonce=' . wp_create_nonce("pdf_nonce")),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />',
                        'iconPosition' => 'left',
                        'gradient' => true
                    ]);
                }
            }

            //selectAllCountries($name = '', $id = '', $country_id = null, $required = true, $type = 'origin')
            public static function originCC($selectCountryName, $id, $country_id, $selectCityName, $idCity, $city_id)
            {
                $html = '';
                $html .= '<div class="">';

                $html .= '<div class="">';
                $html .= KIT_Deliveries::selectAllCountries($selectCountryName, $id, $country_id, $required = "required", 'origin');
                $html .= '</div>';
                $html .= '<div class="">';
                $html .= KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', $country_id, $city_id);
                $html .= '</div>';

                $html .= '</div>';
                return $html;
            }

            public static function render_versatile_table($data, $columns, $cell_callback = null, $options = [])
            {
                // Generate unique table ID for search functionality
                $table_id = $options['id'] ?? 'versatile-table-' . uniqid();

                // Handle bulk actions
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action'])) {
                    $selectedRows = $_POST['selected_rows'];
                    $action = $_POST['bulk_action'];
                    if ($action === 'delete') {

                        // Perform deletion using $selectedRows
                        foreach ($selectedRows as $row) {
                            if ($options['role'] === 'delivery') {
                                KIT_Deliveries::delete_delivery($row);
                                wp_redirect(admin_url('admin.php?page=kit-deliveries&per_page=' . $_GET['per_page']));
                            }
                            if ($options['role'] === 'waybills') {
                                KIT_Waybills::delete_waybill($row);
                                wp_redirect(admin_url('admin.php?page=08600-Waybill&per_page=' . $_GET['per_page']));
                            }
                        }

                        exit();
                    } elseif ($action === 'bulkInvoice') {
                        $selectedRows = $_POST['selected_rows'];

                        // Redirect to the PDF generation page with selected rows via GET or POST
                        $url = admin_url('admin.php?page=all-customer-waybills&cust_id=' . $_GET['cust_id']);

                        // Encode selected rows as comma-separated string
                        $ids = implode(',', array_map('intval', $selectedRows)); // sanitize IDs

                        // Use GET or POST (GET for now)
                        wp_redirect(add_query_arg('selected_ids', $ids, $url));
                        exit();
                    } elseif ($action === 'export') {
                        // Export or download logic here
                        echo '<pre>';
                        print_r($selectedRows);
                        echo '</pre>';
                        exit();
                    }
                }


                $itemsPerPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : ($options['itemsPerPage'] ?? 10);
                $itemsPerPage = in_array($itemsPerPage, [5, 10, 20, 50]) ? $itemsPerPage : 10;
                $currentPage = max(1, intval($_GET['paged'] ?? 1));

                $totalItems = count($data);
                $totalPages = ceil($totalItems / $itemsPerPage);

                $offset = ($currentPage - 1) * $itemsPerPage;
                $pagedData = array_slice($data, $offset, $itemsPerPage);

                if ($totalItems === 0) {
                    echo '<div class="text-gray-500 py-4 text-center">No records found</div>';
                    return;
                }

                // Modern Search and Filter Controls (only if not disabled)
                if (!isset($options['disable_search']) || !$options['disable_search']) {
                    echo '<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">';
                    echo '<div class="flex flex-wrap items-center gap-4">';

                    // Search Bar
                    echo '<div class="flex-1 min-w-64">';
                    echo '<div class="relative">';
                    echo '<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">';
                    echo '<svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">';
                    echo '<path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />';
                    echo '</svg>';
                    echo '</div>';
                    echo '<input type="text" id="search-' . $table_id . '" placeholder="Search..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">';
                    echo '</div>';
                    echo '</div>';

                    // Filter Dropdown (Status by default, overridable)
                    if (!isset($options['disable_filters']) || !$options['disable_filters']) {
                        echo '<div class="flex-shrink-0">';
                        $filterLabel = $options['filterOverride'] ?? '';
                        if ($filterLabel === 'country') {
                            // Country filter replaces status
                            echo '<select id="status-filter-' . $table_id . '" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">';
                            echo '<option value="">All countries</option>';
                            global $wpdb;
                            $countries = $wpdb->get_results("SELECT id, country_name FROM {$wpdb->prefix}kit_operating_countries ORDER BY country_name");
                            if ($countries) {
                                foreach ($countries as $c) {
                                    echo '<option data-text="' . esc_attr(strtolower($c->country_name)) . '" value="' . intval($c->id) . '">' . esc_html($c->country_name) . '</option>';
                                }
                            }
                            echo '</select>';
                        } else {
                            // Default Status filter
                            echo '<select id="status-filter-' . $table_id . '" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">';
                            echo '<option value="">All Status</option>';
                            echo '<option value="pending">Pending</option>';
                            echo '<option value="shipped">Shipped</option>';
                            echo '<option value="delivered">Delivered</option>';
                            echo '<option value="cancelled">Cancelled</option>';
                            echo '</select>';
                        }
                        echo '</div>';

                        // Date Filter (keep for non-country mode)
                        echo '<div class="flex-shrink-0">';
                        echo '<div class="relative">';
                        echo '<input type="date" id="date-filter-' . $table_id . '" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">';
                        echo '</div>';
                        echo '</div>';
                    }

                    // Add New Button
                    if (isset($options['add_button'])) {
                        echo '<div class="flex-shrink-0">';
                        echo self::renderButton($options['add_button']['text'], 'primary', 'sm', [
                            'type' => 'button',
                            'onclick' => $options['add_button']['onclick'],
                            'icon' => '<path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />',
                            'iconPosition' => 'left',
                            'gradient' => true
                        ]);
                        echo '</div>';
                    }

                    echo '</div>';
                    echo '</div>';
                }

                echo KIT_Commons::paginationSelect($itemsPerPage, $totalPages, $currentPage);

                // ✅ Bulk Actions Form START (POST)
                echo '<form method="POST" action="' . esc_url($_SERVER['REQUEST_URI']) . '&bulk_action=delete" id="bulkActionForm">';

                // Modern Table Container
                echo '<div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">';
                echo '<table id="' . $table_id . '" class="min-w-full divide-y divide-gray-200">';
                echo '<thead class="bg-gray-50">';
                echo '<tr>';

                // Select All Checkbox Header
                echo '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">';
                echo '<input type="checkbox" class="selectAllRows w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">';
                echo '</th>';

                foreach ($columns as $key => $header) {
                    $label = $header['label'];
                    $align = $header['align'];
                    echo '<th scope="col" class="' . $align . ' px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">';
                    echo htmlspecialchars($label);
                    echo '</th>';
                }

                echo '</tr>';
                echo '</thead>';
                echo '<tbody class="bg-white divide-y divide-gray-200">';

                foreach ($pagedData as $row) {


                    if (isset($options['role']) && $options['role'] === 'customers') {
                        $rowId = $row->cust_id;
                    } elseif (isset($options['role']) && $options['role'] === 'deliveries') {
                        $rowId = $row->delivery_id;
                    } elseif (isset($options['role']) && $options['role'] === 'waybills') {
                        $rowId = $row->waybill_no;
                    } else {
                        $rowId = htmlspecialchars($row->id ?? '');
                    }

                    echo '<tr class="hover:bg-gray-50 transition-colors duration-200" data-row-id="' . $rowId . '">';

                    // Checkbox with name and value for bulk actions
                    echo '<td class="px-6 py-4 whitespace-nowrap">';
                    echo '<input type="checkbox" name="selected_rows[]" value="' . $rowId . '" class="selectRow w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">';
                    echo '</td>';

                    foreach ($columns as $key => $label) {
                        $alignment = $label['align'];
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">';
                        if ($cell_callback && is_callable($cell_callback)) {
                            echo $cell_callback($key, $row);
                        } else {
                            echo htmlspecialchars(($row->$key ?? '') ?: '');
                        }
                        echo '</td>';
                    }

                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
                echo '</div>';

                // Modern Bulk Actions
                echo '<div class="bg-gray-50 px-6 py-3 border-t border-gray-200">';
                echo '<div class="flex items-center justify-between">';
                echo '<div class="flex items-center space-x-3">';
                echo '<select name="bulk_action" class="rounded-md border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                echo '<option value="">Bulk Actions</option>';
                if (isset($options['bulk_actions'])) {
                    foreach ($options['bulk_actions'] as $action => $label) {
                        echo '<option value="' . $action . '">' . $label . '</option>';
                    }
                } else {
                    if (isset($invoicing) && $invoicing) {
                        echo '<option value="bulkInvoice">Bulk Invoice</option>';
                    }
                    echo '<option value="delete">Delete</option>';
                    echo '<option value="export">Export</option>';
                }
                echo '</select>';
                echo self::renderButton('Apply', 'primary', 'sm', [
                    'type' => 'submit',
                    'gradient' => true
                ]);
                echo '</div>';
                echo '<div class="text-sm text-gray-500">';
                echo '<span id="selected-count-' . $table_id . '">0</span> items selected';
                echo '</div>';
                echo '</div>';
                echo '</div>';

                echo '</form>';  // ✅ Bulk Actions Form END

                // Enhanced JavaScript for Select All and Dynamic Search
        ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const tableId = '<?php echo $table_id; ?>';
                    const table = document.getElementById(tableId);
                    const searchInput = document.getElementById('search-' + tableId);
                    const statusFilter = document.getElementById('status-filter-' + tableId);
                    const dateFilter = document.getElementById('date-filter-' + tableId);
                    const selectAll = table.querySelector('.selectAllRows');
                    const rowCheckboxes = table.querySelectorAll('.selectRow');
                    const selectedCountSpan = document.getElementById('selected-count-' + tableId);

                    // Select All functionality
                    if (selectAll) {
                        selectAll.addEventListener('change', function() {
                            rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
                            updateSelectedCount();
                        });
                    }

                    rowCheckboxes.forEach(cb => {
                        cb.addEventListener('change', function() {
                            const allChecked = Array.from(rowCheckboxes).every(checkbox => checkbox.checked);
                            selectAll.checked = allChecked;
                            updateSelectedCount();
                        });
                    });

                    // Update selected count
                    function updateSelectedCount() {
                        const selectedCount = table.querySelectorAll('.selectRow:checked').length;
                        if (selectedCountSpan) {
                            selectedCountSpan.textContent = selectedCount;
                        }
                    }

                    // Dynamic Search functionality (only if not disabled)
                    if (searchInput && (!<?php echo isset($options['disable_search']) && $options['disable_search'] ? 'true' : 'false'; ?>)) {
                        searchInput.addEventListener('input', function() {
                            filterTable();
                        });
                    }

                    // Status filter (only if not disabled)
                    if (statusFilter && (!<?php echo isset($options['disable_filters']) && $options['disable_filters'] ? 'true' : 'false'; ?>)) {
                        statusFilter.addEventListener('change', function() {
                            filterTable();
                        });
                    }

                    // Date filter (only if not disabled)
                    if (dateFilter && (!<?php echo isset($options['disable_filters']) && $options['disable_filters'] ? 'true' : 'false'; ?>)) {
                        dateFilter.addEventListener('change', function() {
                            filterTable();
                        });
                    }

                    // Immediate filter without animations (undo cascade)
                    function filterTable() {
                        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                        const statusValue = statusFilter ? statusFilter.value.toLowerCase() : '';
                        const dateValue = dateFilter ? dateFilter.value : '';
                        const rows = table.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            const statusCell = row.querySelector('[data-status]');
                            const dateCell = row.querySelector('[data-date]');
                            let showRow = true;
                            if (searchTerm && !text.includes(searchTerm)) showRow = false;
                            <?php if (isset($options['filterOverride']) && $options['filterOverride'] === 'country') { ?>
                                if (statusFilter && statusFilter.value) {
                                    const ths = table.querySelectorAll('thead th');
                                    let idx = -1;
                                    for (let i = 0; i < ths.length; i++) {
                                        if ((ths[i].textContent || '').trim().toLowerCase() === 'country') {
                                            idx = i;
                                            break;
                                        }
                                    }
                                    if (idx >= 0) {
                                        const cCell = row.querySelector('td:nth-child(' + (idx + 1) + ')');
                                        const cText = (cCell && cCell.textContent ? cCell.textContent : '').trim().toLowerCase();
                                        const opt = statusFilter.options[statusFilter.selectedIndex];
                                        const sel = (opt && (opt.getAttribute('data-text') || opt.textContent)).toLowerCase();
                                        if (cText !== sel) showRow = false;
                                    }
                                }
                            <?php } else { ?>
                                if (statusValue) {
                                    if (statusCell) {
                                        const rowStatus = statusCell.getAttribute('data-status').toLowerCase();
                                        if (rowStatus !== statusValue) showRow = false;
                                    } else {
                                        const ths = table.querySelectorAll('thead th');
                                        let idx = -1;
                                        for (let i = 0; i < ths.length; i++) {
                                            const l = (ths[i].textContent || '').trim().toLowerCase();
                                            if (l === 'status' || l === 'approval') {
                                                idx = i;
                                                break;
                                            }
                                        }
                                        if (idx >= 0) {
                                            const sCell = row.querySelector('td:nth-child(' + (idx + 1) + ')');
                                            const sText = (sCell && sCell.textContent ? sCell.textContent : '').trim().toLowerCase();
                                            if (sText !== statusValue) showRow = false;
                                        }
                                    }
                                }
                            <?php } ?>
                            if (dateValue && dateCell) {
                                const rowDate = dateCell.getAttribute('data-date');
                                if (rowDate !== dateValue) showRow = false;
                            }
                            row.style.display = showRow ? '' : 'none';
                        });
                    }

                    // Initialize
                    updateSelectedCount();
                });
            </script><?php
                    }

                    public static function customer_cell_callback($key, $row)
                    {
                        if ($key === 'actions') {
                            return '<a href="?page=edit-customer&edit_customer=' . urlencode($row->id) . '" class="text-blue-600 hover:underline">Edit</a>';
                        }
                        // Default: just show the value
                        return htmlspecialchars(property_exists($row, $key) ? $row->$key : '');
                    }



                    public static function paginationSelect($itemsPerPage, $totalPages, $currentPage)
                    {
                        // ✅ Separate Rows-Per-Page + Pagination Form (GET) - OUTSIDE the previous form
                        echo '<div class="bg-slate-100 rounded p-3 mt-4 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-100">';

                        // Rows per page form (GET)
                        echo '<form method="GET" class="flex items-center gap-2">';
                        foreach ($_GET as $key => $value) {
                            if ($key !== 'per_page') {
                                echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                            }
                        }
                        echo '<label for="per_page" class="text-black">Rows per page:</label>';
                        echo '<select name="per_page" id="per_page" onchange="this.form.submit()" class="rounded-md border-gray-300 bg-white px-3 py-1 text-sm text-gray-800 shadow-sm">';
                        foreach ([5, 10, 20, 50] as $option) {
                            $selected = ($option == $itemsPerPage) ? 'selected' : '';
                            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                        }
                        echo '</select>';
                        echo '</form>';

                        // Pagination links (GET)
                        echo '<div class="flex items-center gap-1">';
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $isActive = $i === $currentPage;
                            $url = add_query_arg(['paged' => $i, 'per_page' => $itemsPerPage]);
                            echo '<a href="' . esc_url($url) . '" class="px-3 py-1 rounded-md border text-sm ' .
                                ($isActive
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-blue-50') .
                                '">' . $i . '</a>';
                        }
                        echo '</div>';

                        echo '</div>'; // End rows-per-page + pagination container

                    }

                    /**
                     * Creates an AJAX-based status change select that works within versatile tables
                     * 
                     * @param string $current_status Current status value
                     * @param int $item_id ID of the item to update
                     * @param string $item_type Type of item (delivery, waybill, etc.)
                     * @param array $status_options Array of status options ['value' => 'label']
                     * @return string HTML for the status select
                     */
                    public static function create_ajax_status_select($current_status, $item_id, $item_type, $status_options = [])
                    {
                        // Default status options if none provided
                        if (empty($status_options)) {
                            $status_options = [
                                'pending' => 'Pending',
                                'scheduled' => 'Scheduled',
                                'in_transit' => 'In Transit',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled'
                            ];
                        }

                        $select_id = 'status-select-' . $item_type . '-' . $item_id;
                        $nonce = wp_create_nonce('update_status_' . $item_type);

                        $html = '<select id="' . $select_id . '" class="text-xs border border-gray-300 rounded px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" data-item-id="' . $item_id . '" data-item-type="' . $item_type . '" data-nonce="' . $nonce . '">';

                        foreach ($status_options as $value => $label) {
                            $selected = ($current_status === $value) ? ' selected' : '';
                            $html .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
                        }

                        $html .= '</select>';

                        // Add JavaScript for AJAX handling
                        $html .= '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const select = document.getElementById("' . $select_id . '");
                        if (select) {
                            select.addEventListener("change", function() {
                                const itemId = this.dataset.itemId;
                                const itemType = this.dataset.itemType;
                                const newStatus = this.value;
                                const nonce = this.dataset.nonce;
                                const originalValue = this.getAttribute("data-original-value") || "' . $current_status . '";
                                
                                // Show loading state
                                this.disabled = true;
                                this.style.opacity = "0.5";
                                
                                // Make AJAX request
                                fetch(ajaxurl, {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/x-www-form-urlencoded",
                                    },
                                    body: new URLSearchParams({
                                        action: "update_" + itemType + "_status",
                                        item_id: itemId,
                                        new_status: newStatus,
                                        nonce: nonce
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Update original value
                                        this.setAttribute("data-original-value", newStatus);
                                        
                                        // Show success indicator
                                        this.style.borderColor = "#10b981";
                                        setTimeout(() => {
                                            this.style.borderColor = "";
                                        }, 2000);
                                        
                                        // Optional: Show success message
                                        if (data.data && data.data.message) {
                                            const successMsg = document.createElement("div");
                                            successMsg.className = "fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 text-sm";
                                            successMsg.textContent = data.data.message;
                                            document.body.appendChild(successMsg);
                                            setTimeout(() => successMsg.remove(), 3000);
                                        }
                                    } else {
                                        // Revert to original value
                                        this.value = originalValue;
                                        alert("Error: " + (data.data || "Failed to update status"));
                                    }
                                })
                                .catch(error => {
                                    // Revert to original value
                                    this.value = originalValue;
                                    alert("Network error: " + error.message);
                                })
                                .finally(() => {
                                    // Re-enable select
                                    this.disabled = false;
                                    this.style.opacity = "1";
                                });
                            });
                            
                            // Store original value
                            select.setAttribute("data-original-value", "' . $current_status . '");
                        }
                    });
                    </script>';

                        return $html;
                    }

                    /**
                     * AJAX handler for updating delivery status
                     */
                    public static function ajax_update_delivery_status()
                    {
                        // Verify nonce
                        if (!wp_verify_nonce($_POST['nonce'], 'update_status_delivery')) {
                            wp_send_json_error('Invalid nonce');
                        }

                        // Check permissions
                        if (!current_user_can('edit_posts')) {
                            wp_send_json_error('Insufficient permissions');
                        }

                        $delivery_id = intval($_POST['item_id']);
                        $new_status = sanitize_text_field($_POST['new_status']);

                        // Validate status
                        $valid_statuses = ['scheduled', 'in_transit', 'delivered', 'cancelled'];
                        if (!in_array($new_status, $valid_statuses)) {
                            wp_send_json_error('Invalid status');
                        }

                        global $wpdb;
                        $result = $wpdb->update(
                            $wpdb->prefix . 'kit_deliveries',
                            [
                                'status' => $new_status,
                                'last_updated_at' => current_time('mysql')
                            ],
                            ['id' => $delivery_id],
                            ['%s', '%s'],
                            ['%d']
                        );

                        if ($result !== false) {
                            wp_send_json_success(['message' => 'Delivery status updated successfully']);
                        } else {
                            wp_send_json_error('Failed to update delivery status');
                        }
                    }

                    /**
                     * AJAX handler for updating waybill status
                     */
                    public static function ajax_update_waybill_status()
                    {
                        // Verify nonce
                        if (!wp_verify_nonce($_POST['nonce'], 'update_status_waybill')) {
                            wp_send_json_error('Invalid nonce');
                        }

                        // Check permissions
                        if (!current_user_can('edit_posts')) {
                            wp_send_json_error('Insufficient permissions');
                        }

                        $waybill_id = intval($_POST['item_id']);
                        $new_status = sanitize_text_field($_POST['new_status']);

                        // Validate status
                        $valid_statuses = ['pending', 'quoted', 'paid', 'completed', 'invoiced', 'rejected', 'warehoused', 'assigned'];
                        if (!in_array($new_status, $valid_statuses)) {
                            wp_send_json_error('Invalid status');
                        }

                        global $wpdb;
                        $result = $wpdb->update(
                            $wpdb->prefix . 'kit_waybills',
                            [
                                'status' => $new_status,
                                'last_updated_at' => current_time('mysql'),
                                'last_updated_by' => get_current_user_id()
                            ],
                            ['id' => $waybill_id],
                            ['%s', '%s', '%d'],
                            ['%d']
                        );

                        if ($result !== false) {
                            wp_send_json_success(['message' => 'Waybill status updated successfully']);
                        } else {
                            wp_send_json_error('Failed to update waybill status');
                        }
                    }
                }
                KIT_Commons::init();

                // Fallback for esc_attr if not in WordPress
                if (!function_exists('esc_attr')) {
                    function esc_attr($text)
                    {
                        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                    }
                }
