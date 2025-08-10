// Simple Waybill Items Script for Table Layout
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('add-waybill-item');
    const container = document.getElementById('custom-waybill-items');
    let itemIndex = document.querySelectorAll('.waybill-item').length;
    
    if (!addBtn || !container) return;
    
    // Add new item
    addBtn.addEventListener('click', function() {
        const newTable = document.createElement('table');
        newTable.className = 'w-full text-sm waybill-item border-b border-gray-100';
        newTable.innerHTML = `
            <tbody>
                <tr class="hover:bg-gray-25">
                    <td class="px-3 py-2 w-8">
                        <div class="w-6 h-6 bg-blue-100 text-blue-800 text-xs font-bold rounded-full flex items-center justify-center">
                            ${itemIndex + 1}
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" 
                               name="custom_items[${itemIndex}][item_name]" 
                               class="w-full px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                               placeholder="e.g. MacBook Pro, Documents...">
                    </td>
                    <td class="px-3 py-2 w-20">
                        <input type="number" 
                               name="custom_items[${itemIndex}][quantity]" 
                               value="1"
                               class="w-full px-2 py-1 text-sm border border-gray-200 rounded text-center focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                               min="1">
                    </td>
                    <td class="px-3 py-2 w-24">
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">R</span>
                            <input type="number" 
                                   name="custom_items[${itemIndex}][unit_price]" 
                                   class="w-full pl-5 pr-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                    </td>
                    <td class="px-3 py-2 w-24">
                        <span class="text-sm font-medium text-gray-800 item-total">R 0.00</span>
                    </td>
                    <td class="px-3 py-2 w-16 text-center">
                        <button type="button" class="remove-item inline-flex items-center justify-center w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded text-xs transition-colors" title="Remove">
                            ×
                        </button>
                    </td>
                </tr>
            </tbody>
        `;
        
        container.appendChild(newTable);
        itemIndex++;
        
        // Add remove functionality
        const removeBtn = newTable.querySelector('.remove-item');
        removeBtn.addEventListener('click', function() {
            newTable.remove();
            updateItemNumbers();
        });
        
        // Add calculation functionality
        setupItemCalculation(newTable);
    });
    
    // Setup calculation for existing items
    document.querySelectorAll('.waybill-item').forEach(setupItemCalculation);
    
    // Setup remove buttons for existing items
    document.querySelectorAll('.remove-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            btn.closest('.waybill-item').remove();
            updateItemNumbers();
        });
    });
    
    function setupItemCalculation(itemElement) {
        const qtyInput = itemElement.querySelector('input[name*="[quantity]"]');
        const priceInput = itemElement.querySelector('input[name*="[unit_price]"]');
        const totalSpan = itemElement.querySelector('.item-total');
        
        function updateTotal() {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const total = qty * price;
            totalSpan.textContent = 'R ' + total.toFixed(2);
        }
        
        if (qtyInput) qtyInput.addEventListener('input', updateTotal);
        if (priceInput) priceInput.addEventListener('input', updateTotal);
        
        // Initial calculation
        updateTotal();
    }
    
    function updateItemNumbers() {
        document.querySelectorAll('.waybill-item').forEach(function(item, index) {
            const numberDiv = item.querySelector('.w-6.h-6');
            if (numberDiv) {
                numberDiv.textContent = index + 1;
            }
        });
    }
});
