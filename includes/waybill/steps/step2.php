<div class="mb-6">
    <h2 class="text-lg font-medium text-gray-700 mb-3">Customer Information</h2>
    <?php $is_existing_customer = $atts['is_existing_customer'] ?>
    <!-- Hidden field to store customer ID -->
    <input type="hidden" id="cust_id" name="cust_id" value="<?php echo esc_attr($customer_id); ?>">

    <!-- Customer selection dropdown -->
    <?php $customers = KIT_Customers::tholaMaCustomer(); ?>
    <div class="mb-4">
        <?php
        echo KIT_Commons::customerSelect([
            'label' => 'Select Customer',
            'name' => 'customer_select',
            'id' => 'customer-select',
            'existing_customer' => $customer_id,
            'customer' => $customers,
        ]);
        ?>
    </div>

    <!-- Customer Details Form -->
    <div class="border rounded-md overflow-hidden mb-4">
        <button type="button"
            class="customer-accordion-toggle w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 font-medium">
            Customer Details
        </button>

        <div class="customer-details-content px-4 py-3 bg-white">
            <div class=" gap-4">
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Company Name',
                        'name'  => 'company_name',
                        'id'    => 'company_name',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->name : rand(1, 23)),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => ''
                    ]); ?>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Customer Name',
                        'name'  => 'customer_name',
                        'id'    => 'customer_name',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->name : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => ''
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Customer Surname',
                        'name'  => 'customer_surname',
                        'id'    => 'customer_surname',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->surname : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => ''
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Cell',
                        'name'  => 'cell',
                        'id'    => 'cell',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->cell : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => ''
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Email',
                        'name'  => 'email_address',
                        'id'    => 'email_address',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->email_address : 'me@me.com'),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => ''
                    ]); ?>
                </div>

            </div>
            <div>

                <?= KIT_Commons::Linput([
                    'label' => 'Address',
                    'name'  => 'address',
                    'id'    => 'address',
                    'type'  => 'text',
                    'value' => esc_attr($is_existing_customer ? $customer->address : ''),
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                    'special' => ''
                ]); ?>
            </div>
            <div>
                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
            </div>
        </div>
    </div>
</div>
<div class="flex justify-between mt-8">
    <button type="button" class="md:hidden prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
        Back
    </button>
    <button type="button" class="md:hidden next-step px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Next: Waybill Items
    </button>
    <button
        type="button"
        class="next-step px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        data-target="step-3">
        Next: Waybill Itemsiuio
    </button>

</div>