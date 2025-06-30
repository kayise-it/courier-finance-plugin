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

        echo '<span class="inline-flex items-center rounded-md ' . $getStatusColors($status) . ' px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-500/10 ring-inset ' . $addClass . '">';
        echo esc_html(ucfirst($status));
        echo '</span>';
    }

    public static function QuotationStatus($waybillno, $waybillid, $status, $prevApproval)
    {
        if (!current_user_can('administrator')) {
            return self::statusBadge($prevApproval, 'px-6 py-2');
        }
        // Output a form with a dropdown to change status
        $statuses = [
            'pending'   => 'Pending',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'completed' => 'Completed'
        ];

        $select_id = 'quotation-status-' . esc_attr($waybillid);
        $current_status = $prevApproval;
        $current_label = $statuses[$current_status] ?? 'Unknown';

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
        <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" class="quotation-status-form">
            <input type="hidden" name="action" value="update_WaybillApproval">
            <input type="hidden" name="waybillno" value="<?= esc_attr($waybillno) ?>">
            <input type="hidden" name="waybillid" value="<?= esc_attr($waybillid) ?>">
            <?php wp_nonce_field('update_waybill_approval_nonce'); ?>

            <div class="relative inline-block text-left">
                <div>
                    <button type="button"
                        class="inline-flex justify-center w-full rounded-md border shadow-sm px-4 py-2 bg-white text-sm font-medium <?= $current_colors ?> hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        id="status-button-<?= esc_attr($waybillid) ?>"
                        onclick="toggleDropdown('<?= esc_attr($waybillid) ?>')">
                        <span class="flex items-center">
                            <span class="mr-2"><?= esc_html($current_label) ?></span>
                            <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                </div>

                <div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                    id="status-dropdown-<?= esc_attr($waybillid) ?>">
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
            function toggleDropdown(waybillId) {
                const dropdown = document.getElementById('status-dropdown-' + waybillId);
                const button = document.getElementById('status-button-' + waybillId);

                // Close all other dropdowns
                document.querySelectorAll('[id^="status-dropdown-"]').forEach(d => {
                    if (d.id !== 'status-dropdown-' + waybillId) {
                        d.classList.add('hidden');
                    }
                });

                dropdown.classList.toggle('hidden');

                // Update button text and colors when option is selected
                dropdown.addEventListener('click', function(e) {
                    if (e.target.tagName === 'BUTTON' && e.target.name === 'status') {
                        const selectedStatus = e.target.value;
                        const selectedLabel = e.target.textContent.trim();

                        // Update button text
                        button.querySelector('span:first-child').textContent = selectedLabel;

                        // Update button colors based on status
                        const statusColors = {
                            'pending': 'bg-yellow-100 text-yellow-800 border-yellow-300',
                            'approved': 'bg-green-100 text-green-800 border-green-300',
                            'rejected': 'bg-red-100 text-red-800 border-red-300',
                            'completed': 'bg-green-100 text-green-800 border-green-300'
                        };

                        // Remove existing color classes
                        button.classList.remove('bg-yellow-100', 'text-yellow-800', 'border-yellow-300',
                            'bg-green-100', 'text-green-800', 'border-green-300',
                            'bg-red-100', 'text-red-800', 'border-red-300',
                            'bg-gray-100', 'text-gray-800', 'border-gray-300');

                        // Add new color classes
                        const newColors = statusColors[selectedStatus] || 'bg-gray-100 text-gray-800 border-gray-300';
                        newColors.split(' ').forEach(cls => button.classList.add(cls));

                        // Hide dropdown
                        dropdown.classList.add('hidden');
                    }
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.relative')) {
                    document.querySelectorAll('[id^="status-dropdown-"]').forEach(d => {
                        d.classList.add('hidden');
                    });
                }
            });
        </script>
    <?php
        return ob_get_clean();
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
     *     'name' => 'include_waybill_fee',
     *     'value' => '1',
     *     'min_desc' => 'R50',
     *     'data_target' => 'include_waybill_fee',
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
    public static function ButtonBox($atts = [])
    {
        $atts = shortcode_atts([
            'name'        => '',
            'value'       => '1',
            'min_desc'    => '',
            'data_target' => '',
            'checked'     => false,
            'type'        => 'checkbox', // or 'radio'
            'class'       => 'fee-option',
            'id'          => '',
            'disabled'    => false,
        ], $atts);

        $input_id = $atts['id'] ? $atts['id'] : uniqid('btnbox_');
        $checked = $atts['checked'] ? 'checked' : '';
        $disabled = $atts['disabled'] ? 'disabled' : '';
        $data_target = $atts['data_target'] ? 'data-target="' . esc_attr($atts['data_target']) . '"' : '';

        $boxClass = trim($atts['class'] . ' text-gray-700 flex items-center justify-center p-3 border border-gray-300 rounded-lg shadow-sm hover:shadow-md hover:border-blue-400 transition-all w-24 h-24 cursor-pointer select-none');

        ob_start();
    ?>
        <label class="<?php echo esc_attr($boxClass); ?>" for="<?php echo esc_attr($input_id); ?>" <?php echo $data_target; ?>>
            <input
                type="<?php echo esc_attr($atts['type']); ?>"
                name="<?php echo esc_attr($atts['name']); ?>"
                value="<?php echo esc_attr($atts['value']); ?>"
                id="<?php echo esc_attr($input_id); ?>"
                class="sr-only"
                <?php echo $checked; ?>
                <?php echo $disabled; ?>>
            <div class="flex flex-col items-center justify-center h-full w-full pointer-events-none text-center">
                <span class="text-xs font-medium"><?php echo esc_html($atts['name']); ?></span>
                <?php if (!empty($atts['min_desc'])): ?>
                    <span class="text-xs text-gray-500 text-center"><?php echo esc_html($atts['min_desc']); ?></span>
                <?php endif; ?>
            </div>
        </label>
    <?php
        return ob_get_clean();
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
        ], $atts);

        $input_id = $atts['direction_id'] ? 'btnbox_' . $atts['direction_id'] : uniqid('btnbox_');

        ob_start();
    ?>
        <label for="<?php echo esc_attr($input_id); ?>" class="bg-white w-[100px] h-[100px] rounded-[5px] border-2 border-gray-300 cursor-pointer relative flex items-center justify-center text-center text-[11px] font-medium leading-tight hover:shadow-md transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-100 peer-checked:shadow-lg">
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
                '<input type="' . esc_attr($atts['type']) . '" name="' . esc_attr($atts['name']) .
                '" id="' . esc_attr($atts['id']) . '" value="' . esc_attr($atts['value']) .
                '" class="' . esc_attr($inputClass) . ' ' . esc_attr($atts['class']) . '" ' .
                esc_attr($atts['special']) . '/>';
        }

        // Dynamic input field (part of a group)
        return '<input type="' . esc_attr($atts['dynamic_type']) . '" name="' .
            esc_attr($atts['dynamic_group']) . '[' . esc_attr($atts['name']) . '][]" ' .
            'value="' . esc_attr($atts['value']) . '" class="' . esc_attr($inputClass) . ' ' .
            esc_attr($atts['class']) . '" ' . esc_attr($atts['special']) . '/>';
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
                            'special' => 'style="flex: 1;" placeholder="1" step="1"'
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
                           style="flex: 1;" placeholder="Amount" step="0.01">
                    <input type="number" name="<?php echo esc_js($options['group_name']); ?>[misc_quantity][]" 
                           class="<?php echo esc_js(self::inputClass() . ' ' . $options['input_class']); ?>" 
                           style="flex: 1;" placeholder="Qty" step="0.01">
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

    public static function simpleSelect($label, $name, $selectId, $arrayList)
    {
        //echo label
        echo '<label for="' . esc_attr($selectId) . '" class="' . self::labelClass() . '">' . esc_html($label) . '</label>';
        echo '<select name="' . $name . '" id="' . $selectId . '" class="' . self::selectClass() . '">';
        foreach ($arrayList as $key => $option) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($option) . '</option>';
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
        return 'block text-xs font-medium text-gray-700';
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
            <input type="hidden" name="action" value="delete_waybill909009">
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
        if(empty($waybill))
        {
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
                    <div class="flex flex-row gap-2 waybill-item border p-4 rounded-lg shadow-sm">
                        <!-- Description -->
                        <div class="w-full md:w-1/3">
                            <?php echo self::Linput([
                                'label' => 'Item Description',
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
                                'label' => 'Quantity',
                                'name' => $options['group_name'] . '[' . $index . '][quantity]',
                                'type' => 'number',
                                'value' => $item['quantity'] ?? 1,
                                'class' => $options['input_class'],
                                'label_class' => ($options['specialClass']) ?? '',
                            ]); ?>
                        </div>

                        <!-- Unit Price -->
                        <div class="w-full md:w-1/6">
                            <?php echo self::Linput([
                                'label' => 'Unit Price',
                                'name' => $options['group_name'] . '[' . $index . '][unit_price]',
                                'type' => 'number',
                                'value' => $item['unit_price'] ?? 0,
                                'class' => $options['input_class'],
                                'label_class' => ($options['specialClass']) ?? '',
                            ]); ?>
                        </div>

                        <!-- Remove Button -->
                        <div class="w-full md:w-auto flex items-end">
                            <button type="button" class="remove-item <?php echo esc_attr($options['remove_btn_class']); ?>">
                                Remove
                            </button>
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
                    newItem.className = 'flex flex-row gap-4 waybill-item border p-4 rounded-lg shadow-sm';
                    newItem.innerHTML = `
                    <div class="w-full md:w-1/3">
                        <label class="<?php echo esc_js($defaults['label_class']) . " " . $options['specialClass'] ?>">Item Description</label>
                        <input type="text" name="<?php echo esc_js($options['group_name']); ?>[${itemIndex}][item_name]" 
                               class="<?php echo esc_js($defaults['input_class']); ?>" 
                               placeholder="e.g. Laptop, Router, etc">
                    </div>
                    <div class="w-full md:w-1/6">
                        <label class="<?php echo esc_js($options['label_class']) . " " . $options['specialClass']; ?>">Quantity</label>
                        <input type="number" name="<?php echo esc_js($options['group_name']); ?>[${itemIndex}][quantity]" 
                               class="<?php echo esc_js($defaults['input_class']); ?> w-full" value="1">
                    </div>
                    <div class="w-full md:w-1/6">
                        <label class="<?php echo esc_js($options['label_class']) . " " . $options['specialClass']; ?>">Unit Price</label>
                        <input type="number" name="<?php echo esc_js($options['group_name']); ?>[${itemIndex}][unit_price]" 
                               class="<?php echo esc_js($defaults['input_class']); ?> w-full">
                    </div>

                    <div class="w-full md:w-auto flex items-end">
                        <button type="button" class="remove-item <?php echo esc_js($options['remove_btn_class']); ?>">
                            Remove
                        </button>
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
                            $html .= '>';
                            $html .= esc_html($customer->name . ' ' . $customer->surname); // Optional: visible name
                            $html .= '</option>';
                        }
                    }
                    $html .= '</select>';
                    return $html;
                }
            }

            KIT_Commons::init();
