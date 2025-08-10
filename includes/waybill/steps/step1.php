<!-- Waybill Basic Information -->
<div class="max-w-2xl space-y-6">
    <!-- Waybill Number -->
    <div>
        <?php
        echo KIT_Commons::Linput([
            'label' => 'Waybill Number',
            'name'  => 'waybill_no',
            'id'    => 'waybill_no',
            'type'  => 'text',
            'value' => esc_attr($waybill->waybill_no ?? KIT_Waybills::generate_waybill_number()),
            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm',
            'special' => 'readonly',
        ]);
        ?>
    </div>

    <!-- Description -->
    <div>
        <?php
        echo KIT_Commons::TextAreaField([
            'label' => 'Description (Optional)',
            'name'  => 'waybill_description',
            'id'    => 'waybill_description',
            'type'  => 'textarea',
            'value' => esc_attr($waybill->waybill_description ?? ''),
            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm',
            'special' => 'rows="4" placeholder="Brief description of the shipment contents, special handling instructions, or any relevant notes..."',
        ]);
        ?>
    </div>
</div>

