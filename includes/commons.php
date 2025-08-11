<?php
class KIT_Commons
{
    public static function init()
    {
        add_shortcode('showheader', [self::class, 'showingHeader']);
        add_shortcode('kitbutton', [self::class, 'kitButton']);
    }

    public static function statusBadge($status, $addClass = null)
    {

        $status = strtolower($status);
        $badge_classes = [
            'rejected' => 'border border-red-300 bg-red-50 text-red-800',
            'pending' => 'border border-yellow-300 bg-yellow-50 text-yellow-800',
            'approved' => 'border border-green-300 bg-green-50 text-green-800'
        ];

        $class = $badge_classes[$status] ?? 'bg-gray-100 text-gray-800';
        $display_text = ucfirst($status);

        return '<span class="inline-flex items-center px-5 py-2 rounded-lg text-xs font-medium ' . $class . '">'
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


    // Create a function to display a select box with the statuses for the waybill.status column with options "pending", "quoted", "rejected", "completed" and then update the waybill.status column with the selected status
    public static function waybillQuoteStatus($waybillno, $waybillid, $fontSize = 'text-xs')
    {
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
        ob_start(); ?>
        <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" class="quotation-status-form">
            <input type="hidden" name="action" value="waybillQuoteStatus_update">
            <input type="hidden" name="waybillno" value="<?= esc_attr($waybillno) ?>">
            <input type="hidden" name="waybillid" value="<?= esc_attr($waybillid) ?>">
            <?php wp_nonce_field('update_waybill_approval_nonce'); ?>
            <div class="relative inline-block text-left">
                <div>
                    <button type="button"
                        class="inline-flex  <?= $fontSize ?> justify-center w-full rounded-md border shadow-sm px-4 py-2 bg-white font-medium <?= $current_colors ?> hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        id="quote-button-<?= esc_attr($waybillid) ?>"
                        onclick="toggleDropdownQuote('<?= esc_attr($waybillid) ?>')">
                        <span class="flex items-center  <?= $fontSize ?>">
                            <span class="mr-2"><?= esc_html($current_label2) ?></span>
                            <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                </div>

                <div class="hidden <?= $fontSize ?> origin-top-left absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                    id="quote-dropdown-<?= esc_attr($waybillid) ?>">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        <?php foreach (
                            $statusesStatus as $key => $label
                        ): ?>
                            <button type="submit"
                                name="status"
                                value="<?= esc_attr($key) ?>"
                                class="block w-full text-left px-4 py-2 <?= $fontSize ?> text-gray-700 hover:bg-gray-100 hover:text-gray-900 <?= $key === $current_status2 ? 'bg-gray-100 text-gray-900' : '' ?>"
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

                // if current user is not administrator, return the status badge with the previous approval status

                $current_user_role = wp_get_current_user()->roles[0];
                if (!in_array('administrator', wp_get_current_user()->roles)) {
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
                        class="inline-flex  <?= $fontSize ?> justify-center w-full rounded-md border shadow-sm px-4 py-2 bg-white font-medium <?= $current_colors ?> hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        id="approval-button-<?= esc_attr($waybillid) ?>"
                        onclick="toggleDropdownApproval('<?= esc_attr($waybillid) ?>')">
                        <span class="flex items-center">
                            <span class="mr-2"><?= esc_html($current_label) ?></span>
                            <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                </div>

                <div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                    id="approval-dropdown-<?= esc_attr($waybillid) ?>">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        <?php foreach ($statuses as $key => $label): ?>
                            <button type="submit"
                                name="status"
                                value="<?= esc_attr($key) ?>"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 <?= $key === $current_status ? 'bg-gray-100 text-gray-900' : '' ?>"
                                role="menuitem">
                                <?= esc_html($label) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>

        <script>
            function toggleDropdownQuote(waybillId) {
                const dropdownQuote = document.getElementById('quote-dropdown-' + waybillId);
                const buttonQuote = document.getElementById('quote-button-' + waybillId);

                // Close all other dropdowns
                document.querySelectorAll('[id^="quote-dropdown-"]').forEach(d => {
                    if (d.id !== 'quote-dropdown-' + waybillId) {
                        d.classList.add('hidden');
                    }
                });

                dropdownQuote.classList.toggle('hidden');
            }

            function toggleDropdownApproval(waybillId) {
                const dropdownApproval = document.getElementById('approval-dropdown-' + waybillId);
                const buttonApproval = document.getElementById('approval-button-' + waybillId);

                // Close all other approval dropdowns
                document.querySelectorAll('[id^="approval-dropdown-"]').forEach(d => {
                    if (d.id !== 'approval-dropdown-' + waybillId) {
                        d.classList.add('hidden');
                    }
                });

                dropdownApproval.classList.toggle('hidden');

                // Update button text and colors when option is selected
                dropdownApproval.addEventListener('click', function handler(e) {
                    if (e.target.tagName === 'BUTTON' && e.target.name === 'approval') {
                        const selectedStatus = e.target.value;
                        const selectedLabel = e.target.textContent.trim();

                        // Update button text
                        button.querySelector('span:first-child').textContent = selectedLabel;

                        // Update button colors based on status
                        const statusColors = {
                            'pending': 'bg-yellow-100 text-yellow-800 border-yellow-300',
                            'approved': 'bg-green-100 text-green-800 border-green-300',
                            'rejected': 'bg-red-100 text-red-800 border-red-300'
                        };

                        // Remove existing color classes
                        button.classList.remove('bg-yellow-100', 'text-yellow-800', 'border-yellow-300',
                            'bg-green-100', 'text-green-800', 'border-green-300',
                            'bg-red-100', 'text-red-800', 'border-red-300',
                            'bg-gray-100', 'text-gray-800', 'border-gray-300');

                        // Add new color classes
                        const newColors = statusColors[selectedStatus] || 'bg-gray-100 text-gray-800 border-gray-300';
                        newColors.split(' ').forEach(cls => buttonApproval.classList.add(cls));

                        // Hide dropdown
                        dropdownApproval.classList.add('hidden');
                        // Remove this event listener after handling
                        dropdownApproval.removeEventListener('click', handler);
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
            public static function buttonBox($label, $highlight = '')
            {
                $base = 'rounded-md px-4 py-2 text-sm font-semibold border border-gray-300 transition hover:scale-105 hover:shadow-md hover:bg-blue-600 hover:text-white';

                if ($highlight === 'highlight') {
                    $base .= ' bg-yellow-400 text-black';
                } else {
                    $base .= ' bg-white text-gray-800';
                }

                return '<button class="' . $base . '">' . $label . '</button>';
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
        <label <?php echo $atts['onclick'] ? 'onclick="' . $atts['onclick'] . '"' : ''; ?> for="<?php echo esc_attr($input_id); ?>" class="<?= $atts['class'] ?>  bg-white w-[100px] h-[100px] rounded-[5px] border-2 border-gray-300 cursor-pointer relative flex items-center justify-center text-center text-[11px] font-medium leading-tight hover:shadow-md transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-100 peer-checked:shadow-lg">
            <input
                type="radio"
                name="<?php echo esc_attr($atts['name']); ?>"
                id="<?php echo esc_attr($input_id); ?>"
                value="<?php echo esc_attr($atts['direction_id']); ?>"
                class="sr-only peer"
                <?php echo $atts['checked'] ? 'checked' : ''; ?>>
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
                ], $atts);

                $labelClass = self::labelClass();

                // Standard input
                return '<label class="' . esc_attr($labelClass) . ' ' . $atts['classlabel'] . ' ">' .
                    esc_html($atts['label']) . '</label>' .
                    '<p class="m-0 p-0 ' . esc_attr($atts['classP']) . '">' . $atts['value'] . '</p>';
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
                        <button type="button" class="remove-misc-btn"
                            style="background-color: #ef4444; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer;">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <?=
                KIT_Commons::simpleBtn([
                    'type' => 'button',
                    'class' => 'bg-blue-500 text-xs text-white px-4 py-2 rounded-md hover:bg-blue-700',
                    'id' => esc_attr($options['button_id']),
                    'onclick' => '',
                    'text' => '+ Add Misc Item'
                ]);
            ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const addBtn = document.getElementById('<?php echo esc_js($options['button_id']); ?>');
                const container = document.getElementById('<?php echo esc_js($options['container_id']); ?>');

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
                    <button type="button" class="remove-misc-btn" 
                            style="background-color: #ef4444; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer;">×</button>
                `;
                    container.appendChild(newItem);
                });

                // Event delegation for remove buttons
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('remove-misc-btn')) {
                        e.target.closest('.misc-item').remove();
                    }
                });
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
                            if (strpos($link, 'current') !== false) {
                                $link = str_replace('page-numbers current', 'page-numbers current px-3 py-1 rounded border bg-blue-50 border-blue-500 text-blue-600', $link);
                            } else {
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
                        $html .= esc_html(ucfirst(str_replace('_', ' ', $field)));
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
                                    $waybillNo = str_replace(' ', '_', $field);
                                    $html .= self::waybillApprovalStatus($item->waybill_no, $item->waybill_id ?? 0, 'quoted', $item->approval ?? '', 'select');
                                } else {
                                    $html .= self::statusBadge($item->approval ?? '');
                                }
                            } elseif ($field === 'waybill no') {

                                $waybillNo = str_replace(' ', '_', $field);
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
                        $html .= '<a href="' . admin_url('admin-ajax.php?action=generate_pdf&quotation_id=' . ($item->waybill_id ?? '') . '&security_nonce=' . wp_create_nonce('pdf_nonce')) . '" target="_blank" class="text-indigo-600 hover:text-indigo-900" title="Print">';
                        $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />';
                        $html .= '</svg></a>';

                        // Create quotation button (for admins/managers)
                        if (($options['show_create_quotation']) && (current_user_can('administrator') || current_user_can('manager'))) {
                            $html .= '<button class="create-quotation text-green-600 hover:text-green-900" title="Create Quotation" data-waybill-id="' . esc_attr($item->waybill_id ?? '') . '">';
                            $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />';
                            $html .= '</svg>Quote</button>';
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
            public static function buttonClass()
            {
                return 'inline-flex items-center px-4 py-2 text-xs font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2';
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
                return 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
            }
            public static function tcolClasses()
            {
                //return 'px-6 py-4 text-sm text-gray-900';
                return 'px-4 py-2 text-sm text-gray-700 whitespace-nowrap';
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
                return current_user_can('manage_options') || current_user_can('administrator');
            }

            /**
             * Display waybill total with admin-only access
             * @param float $amount
             * @param string $fallback_text
             * @return string
             */
            public static function displayWaybillTotal($amount, $fallback_text = '***')
            {
                if (self::isAdmin()) {
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
            <button type="submit" class="delete-waybill text-red-600 hover:text-red-900">Delesste</button>
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
        <table class="<?= self::tableClasses(); ?> w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="<?= self::thClasses() ?> text-left">Item</th>
                    <th class="<?= self::thClasses() ?> text-center">Quantity</th>
                    <th class="<?= self::thClasses() ?> text-right">Unit Price</th>
                </tr>
            </thead>
            <tbody class="<?= self::tbodyClasses() ?>">
                <?php
                foreach ($waybill as $key => $value) {
                    ob_start();
                ?>
                    <tr class="border-t border-gray-100">
                        <td class="<?= self::tcolClasses() ?>"><?= $value['item_name']
                                                                ?></td>
                        <td class="<?= self::tcolClasses() ?> text-center"><?= $value['quantity'] ?></td>
                        <td class="<?= self::tcolClasses() ?> text-right"><?= $value['unit_price'] ?> </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    <?php
                return ob_get_clean();
            }

            /**
             * Renders a simple button that can handle actions (like submit, reset, button, or custom JS)
             * and custom classes based on passed attributes.
             * 
             * Usage:
             * KIT_Commons::simpleBtn([
             *     'type' => 'submit',
             *     'class' => 'my-custom-class',
             *     'onclick' => 'alert("Clicked!")',
             *     'text' => 'Click Me'
             * ]);
             */
            public static function simpleBtn($atts = [])
            {
                $atts = shortcode_atts([
                    'type'    => 'button', // button, submit, reset
                    'class'   => '',       // additional classes
                    'onclick' => '',       // JS action
                    'id'      => '',       // optional id
                    'name'    => '',       // optional name
                    'text'    => 'Button', // button text
                    'data-target' => '',
                    'disabled' => false,    // disabled attribute
                ], $atts);

                //px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2

                $btnClass = self::buttonClass();
                $btnColor = esc_attr($atts['class']);
                $type = esc_attr($atts['type']);
                $id = $atts['id'] ? 'id="' . esc_attr($atts['id']) . '"' : '';
                $name = $atts['name'] ? 'name="' . esc_attr($atts['name']) . '"' : '';
                $onclick = $atts['onclick'] ? 'onclick="' . esc_attr($atts['onclick']) . '"' : '';
                $disabled = $atts['disabled'] ? 'disabled' : '';
                $dataTarget = $atts['data-target'] ? 'data-target' : '';

                return '<button data-target="' . $dataTarget . '" type="' . $type . '" ' . $id . ' ' . $name . ' class="' . $btnColor . '" ' . $onclick . ' ' . $disabled . '>' .
                    esc_html($atts['text']) . '</button>';
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

            public static function kitButton($atts, $content = '')
            {
                $atts = shortcode_atts([
                    'color' => 'blue',
                    'href'  => '',
                    'modal' => '', // <-- support modal buttons
                    'icon'  => '', // optionally support icon injection
                    'type'  => 'button', // button, submit, reset
                    'name'  => '', // optional name
                    'id'    => '', // optional id
                    'onclick' => '', // optional onclick
                    'disabled' => false, // optional disabled
                    'data-target' => '', // optional data-target
                    'class' => '', // optional class
                ], $atts);


                $colorClasses = [
                    'blue' => 'bg-blue-600 hover:bg-blue-700 text-white',
                    'red' => 'bg-red-600 hover:bg-red-700 text-white',
                    'green' => 'bg-green-600 hover:bg-green-700 text-white',
                    'gray' => 'bg-gray-600 hover:bg-gray-700 text-white',
                ];

                $btnClass = $colorClasses[$atts['color']] ?? $colorClasses['blue'];

                // Start output buffering
                ob_start();

                $common_classes = 'inline-flex items-center px-4 py-2 text-xs font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2';

                // Include icon SVG if provided
                $icon = '';
                if ($atts['icon'] === 'plus') {
                    $icon = '<svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                 </svg>';
                }

                if (!empty($atts['modal'])) {
                    // Modal trigger button
        ?>
            <button data-modal="<?= esc_attr($atts['modal']) ?>"
                type="button"
                class="<?= esc_attr($common_classes . ' ' . $btnClass) ?>">
                <?= $icon ?><?= esc_html($content) ?>
            </button>
        <?php
                } elseif (!empty($atts['href'])) {
                    // Regular link button
        ?>
            <a href="<?= esc_url($atts['href']) ?>"
                class="<?= esc_attr($common_classes . ' ' . $btnClass) ?>">
                <?= $icon ?><?= esc_html($content) ?>
            </a>
        <?php
                } elseif (!empty($atts['type']) && $atts['type'] === 'submit') {
                    // Submit button
        ?>
            <button type="submit" name="<?= esc_attr($atts['name']) ?>" id="<?= esc_attr($atts['id']) ?>" class="<?= esc_attr($common_classes . ' ' . $btnClass) ?>">
                <?= $icon ?><?= esc_html($content) ?>
            </button>
        <?php
                }

                return ob_get_clean();
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
                    'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'
                ];
                $options = wp_parse_args($options, $defaults);

                ob_start();

        ?>
        <div class="form-step bg-gray-50 p-4 rounded-lg mb-8" id="step-waybill-items">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Waybill Items</h2>

            <div id="<?php echo esc_attr($options['container_id']); ?>" class="space-y-4">
                <?php foreach ($options['existing_items'] as $index => $item): ?>
                    <div class="flex flex-row gap-2 waybill-item p-1 rounded-lg shadow-sm">
                        <!-- Description -->
                        <div class="w-full md:w-1/3">
                            <?php echo self::Linput([
                                'label' => 'Item',
                                'name' => $options['group_name'] . '[' . $index . '][item_name]',
                                'value' => $item['item_name'] ?? '',
                                'class' => $options['input_class'],
                                'special' => 'placeholder="e.g. Laptop, Router, etc"',
                                'label_class' => ($options['specialClass']) ?? '',
                            ]); ?>
                        </div>


                        <!-- Quantity -->
                        <div class="w-full md:w-1/6">
                            <?php echo self::Linput([
                                'label' => 'Qty',
                                'name' => $options['group_name'] . '[' . $index . '][quantity]',
                                'type' => 'number',
                                'value' => $item['quantity'] ?? 1,
                                'class' => $options['input_class'],
                                'label_class' => ($options['specialClass']) ?? '',
                            ]); ?>
                        </div>

                        <!-- Unit Price -->
                        <div class="w-full md:w-1/3">
                            <?php echo self::Linput([
                                'label' => 'Unit Price',
                                'name' => $options['group_name'] . '[' . $index . '][unit_price]',
                                'type' => 'number',
                                'value' => $item['unit_price'] ?? 0,
                                'class' => $options['input_class'],
                                'label_class' => ($options['specialClass']) ?? '',
                            ]); ?>
                        </div>
                        <!-- Delete Icon -->
                        <div class="flex items-end">
                            <button type="button" class="remove-item <?php echo esc_attr($options['remove_btn_class']); ?>" title="Delete" style="background-color: #ef4444; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer;">×</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <button type="button" id="<?php echo esc_attr($options['button_id']); ?>"
                    class="<?php echo esc_attr($options['add_btn_class']); ?>">
                    + Add Another Item
                </button>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let itemIndex = <?php echo count($options['existing_items']); ?>;
                const container = document.getElementById('<?php echo esc_js($options['container_id']); ?>');
                const addBtn = document.getElementById('<?php echo esc_js($options['button_id']); ?>');

                addBtn.addEventListener('click', function() {
                    const newItem = document.createElement('div');
                    newItem.className = 'flex flex-row gap-2 waybill-item rounded-lg shadow-sm';
                    newItem.innerHTML = `
                    <div class="w-full md:w-1/3">
                        <label class="<?php echo esc_js($defaults['label_class']) . " " . $options['specialClass'] ?>">Item</label>
                        <input type="text" tabindex="1" name="<?php echo esc_js($options['group_name']); ?>[${itemIndex}][item_name]" 
                               class="<?php echo esc_js($defaults['input_class']); ?>" 
                               placeholder="e.g. Laptop, Router, etc">
                    </div>
                    <div class="w-full md:w-1/6">
                        <label class="<?php echo esc_js($options['label_class']) . " " . $options['specialClass']; ?>">Qty</label>
                        <input type="number" tabindex="2" name="<?php echo esc_js($options['group_name']); ?>[${itemIndex}][quantity]" 
                               class="<?php echo esc_js($defaults['input_class']); ?> w-full" value="1">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="<?php echo esc_js($options['label_class']) . " " . $options['specialClass']; ?>">Unit Price</label>
                        <input type="number" tabindex="3" name="<?php echo esc_js($options['group_name']); ?>[${itemIndex}][unit_price]" 
                               class="<?php echo esc_js($defaults['input_class']); ?> w-full">
                    </div>

                    <div class="w-full md:w-auto flex items-end">
                        
                        <button type="button" class="remove-item <?php echo esc_attr($options['remove_btn_class']); ?>" title="Delete" style="background-color: #ef4444; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer;">×</button>
                    </div>
                `;
                    container.appendChild(newItem);
                    itemIndex++;
                });

                // Event delegation for remove buttons
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-item') ||
                        e.target.closest('.remove-item')) {
                        const btn = e.target.classList.contains('remove-item') ?
                            e.target : e.target.closest('.remove-item');
                        btn.closest('.waybill-item').remove();
                    }
                });
            });
        </script><?php
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
                    if (isset($atts['buttonType']) && $atts['buttonType'] === 'icon-only') {
                        return '<div class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        </div>';
                    } else {
                        $downloadIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>';
                    }
                    $atts = shortcode_atts([
                        'icon' => $downloadIcon,
                        'text' => '',
                        'class' => 'bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2',
                    ], $atts);

                    return '<a href="' . admin_url('admin-ajax.php?action=generate_pdf&waybill_no=' . $waybillNo . '&pdf_nonce=' . wp_create_nonce("pdf_nonce")) . '"
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                        ' . $atts['icon'] . '
                                        ' . $atts['text'] . '
                                    </a>';
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

                    echo KIT_Commons::paginationSelect($itemsPerPage, $totalPages, $currentPage);

                    // ✅ Bulk Actions Form START (POST)
                    echo '<form method="POST" action="' . esc_url($_SERVER['REQUEST_URI']) . '&bulk_action=delete" id="bulkActionForm">';

                    // Table
                    echo '<div class="rounded-xl border border-gray-200 shadow-sm">';
                    echo '<table class="min-w-full divide-y divide-gray-200 text-sm bg-white">';
                    echo '<thead class="bg-gray-50 text-xs text-gray-700 uppercase tracking-wide">';
                    echo '<tr>';

                    // Select All Checkbox Header
                    echo '<th class="px-2 py-2 text-center w-5 ">';
                    echo '<input type="checkbox" class="selectAllRows w-4 h-4">';
                    echo '</th>';

                    foreach ($columns as $key => $header) {

                        $label = $header['label'];
                        $align = $header['align'];
                        echo '<th class="' . $align . '">' . htmlspecialchars($label) . '</th>';
                    }

                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody class="divide-y divide-gray-100 ">';

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

                        echo '<tr class="hover:bg-gray-50 transition" data-row-id="' . $rowId . '">';

                        // Checkbox with name and value for bulk actions
                        echo '<td class="px-2 py-2 text-center">';
                        echo '<input type="checkbox" name="selected_rows[]" value="' . $rowId . '" class="selectRow w-4 h-4">';
                        echo '</td>';

                        foreach ($columns as $key => $label) {
                            $alignment = $label['align'];
                            echo '<td class="' . $alignment . '">';
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

                    // Bulk Actions Buttons (still inside the same form)
                    echo '<div class="bg-slate-100 rounded p-3 flex flex-wrap items-center justify-start gap-2 text-sm text-gray-100">';
                    echo '<select name="bulk_action" class="rounded-md border-gray-300 bg-white px-5 py-1 text-sm text-gray-800 shadow-sm">';
                    echo '<option value="">Bulk actions</option>';
                    if ($invoicing = true) {
                        echo '<option value="bulkInvoice">Bulk Invoice</option>';
                    }
                    echo '<option value="delete">Delete</option>';
                    echo '<option value="export">Export</option>';
                    echo '</select>';
                    echo '<button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Apply</button>';
                    echo '</div>';

                    echo '</form>';  // ✅ Bulk Actions Form END


                    // JavaScript for Select All
                    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAll = document.querySelector('.selectAllRows');
                const rowCheckboxes = document.querySelectorAll('.selectRow');

                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
                    });
                }

                rowCheckboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        const allChecked = Array.from(rowCheckboxes).every(checkbox => checkbox.checked);
                        selectAll.checked = allChecked;
                    });
                });
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
            }
            KIT_Commons::init();

            // Fallback for esc_attr if not in WordPress
            if (!function_exists('esc_attr')) {
                function esc_attr($text)
                {
                    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                }
            }
