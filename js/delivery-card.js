/**
 * Delivery Card JavaScript Functions
 * Simple and reusable functions for delivery cards
 */

// Handle delivery card click
function handleDeliveryClick(directionId) {
    // Remove active state from all delivery cards
    document.querySelectorAll('.delivery-card').forEach(card function {
        card.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
    });
    
    // Add active state to clicked card
    var selectedCard = document.querySelector(`[data-direction-id="${directionId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
    }
    
    // Trigger custom event for other components to listen to
    var event = new CustomEvent('deliverySelected', {
        detail: { directionId: directionId }
    });
    document.dispatchEvent(event);
}

// Alias for backward compatibility
function handleDeliveryChange(directionId) {
    handleDeliveryClick(directionId);
}

// Clear delivery selection
function clearDeliverySelection() {
    // Remove active state from all cards
    document.querySelectorAll('.delivery-card').forEach(card function {
        card.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
    });
    
    // Trigger custom event
    var event = new CustomEvent('deliveryCleared');
    document.dispatchEvent(event);
}

// Handle grouping changes
function handleGroupingChange(grouping) {
    console.log('Grouping changed to:', grouping);
    // Implement grouping logic here
    // This function can be customized based on needs
}

// Initialize delivery cards when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Set up grouping select if it exists
    var groupingSelect = document.getElementById('grouping-option');
    if (groupingSelect) {
        groupingSelect.addEventListener('change', function() {
            handleGroupingChange(this.value);
        });
    }
    
    // Listen for delivery selection events
    document.addEventListener('deliverySelected', function(e) {
        console.log('Delivery selected:', e.detail.directionId);
        // Handle delivery selection - customize as needed
    });
    
    document.addEventListener('deliveryCleared', function() {
        console.log('Delivery selection cleared');
        // Handle delivery clearing - customize as needed
    });
});
