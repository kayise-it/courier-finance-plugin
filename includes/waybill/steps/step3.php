<!-- Waybill Items Step -->
<div class="" id="">
<!-- Waybill Items Step -->
<?=
 KIT_Commons::waybillItemsControl([
    'container_id' => 'custom-waybill-items',
    'button_id' => 'add-waybill-item',
    'group_name' => 'custom_items',
    'existing_items' => [
        [
            'item_name' => 'Laptop Dell XPS',
            'quantity' => 1,
            'unit_price' => 1200,
            'total_price' => 2 * 25
        ],
        [
            'item_name' => 'Wireless Mouse',
            'quantity' => 2,
            'unit_price' => 25,
            'total_price' => 2 * 25
        ]
    ],
    'input_class' => 'border border-gray-300 rounded px-3 py-2 bg-white',
    'remove_btn_class' => 'bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600',
    'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700',
    'specialClass' => '!text-[10px]',
]);
?>

    <div class="flex justify-between mt-8">
        <button type="button" class="md:hidden prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" data-target="step-1">
            Back
        </button>
        <button type="button" class="hidden md:block prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" data-target="step-1">
            Back
        </button>
        <button type="button" class="md:hidden next-step px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700" data-target="step-3">
            Next: Charges & Fees
        </button>
        <button type="button" class="hidden md:block next-step px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700" data-target="step-4">
            Next: Charges & Fees
        </button>
    </div>
</div>
