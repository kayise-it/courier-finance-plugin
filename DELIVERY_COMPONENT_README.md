# Delivery Card Component

A simple, reusable delivery card component for the KIT_Deliveries system.

## Files Created

1. **`includes/components/deliveryCard.php`** - The main component file
2. **`js/delivery-card.js`** - JavaScript functionality
3. **`includes/components/deliveryGrid.php`** - Example usage
4. **`DELIVERY_COMPONENT_README.md`** - This documentation

## How to Use

### 1. Basic Usage

```php
<?php
// Include the component
require_once __DIR__ . '/deliveryCard.php';

// Get your delivery data
$deliveries = KIT_Deliveries::getScheduledDeliveries();

// Render a single card
foreach ($deliveries as $delivery) {
    renderDeliveryCard($delivery, 'scheduled', true, 'handleDeliveryClick');
}
?>
```

### 2. Component Parameters

```php
renderDeliveryCard($delivery, $card_type, $clickable, $onclick_function, $radio_options);
```

- **`$delivery`** - Delivery object with properties:
  - `direction_id` - Required
  - `origin_country_id` OR `origin_country` - Required (supports both ID and name)
  - `destination_country_id` OR `destination_country` - Required (supports both ID and name)
  - `dispatch_date` - Required
  - `truck_number` - Optional
  - `description` - Optional

- **`$card_type`** - Status type (affects colors and text):
  - `'scheduled'` - Green status
  - `'in-transit'` - Blue status
  - `'delivered'` - Gray status
  - `'cancelled'` - Red status

- **`$clickable`** - Boolean, whether card is clickable
- **`$onclick_function`** - JavaScript function name to call on click
- **`$radio_options`** - Optional array for radio button functionality:
  - `type` - Input type (default: 'radio')
  - `name` - Input name attribute
  - `checked_id` - ID of checked option

### 3. Different Card Types

```php
// Scheduled delivery (clickable)
renderDeliveryCard($delivery, 'scheduled', true, 'handleDeliveryClick');

// In-transit delivery (read-only)
renderDeliveryCard($delivery, 'in-transit', false);

// Delivered delivery (clickable with custom function)
renderDeliveryCard($delivery, 'delivered', true, 'handleDeliveredClick');

// Delivery card with radio button selection
renderDeliveryCard($delivery, 'scheduled', true, 'handleDeliveryClick', [
    'type' => 'radio',
    'name' => 'delivery_id',
    'checked_id' => 1
]);
```

### 4. Grid Layout

```php
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
    <?php foreach ($deliveries as $delivery): ?>
        <?php renderDeliveryCard($delivery, 'scheduled', true, 'handleDeliveryClick'); ?>
    <?php endforeach; ?>
</div>
```

## JavaScript Functions

The component includes these JavaScript functions:

- **`handleDeliveryClick(directionId)`** - Handles card clicks
- **`clearDeliverySelection()`** - Clears selection
- **`handleGroupingChange(grouping)`** - Handles grouping changes

## Customization

### Status Colors
Edit the `getStatusConfig()` function in `deliveryCard.php` to change colors:

```php
function getStatusConfig($card_type) {
    switch ($card_type) {
        case 'scheduled':
            return ['color' => 'bg-green-500', 'text' => 'Scheduled'];
        case 'in-transit':
            return ['color' => 'bg-blue-500', 'text' => 'In Transit'];
        // Add more status types here
    }
}
```

### CSS Classes
The component uses Tailwind CSS classes. You can modify the classes in the `renderDeliveryCard()` function.

## Example Implementation

See `includes/components/deliveryGrid.php` for a complete example showing different ways to use the component.

## Benefits

1. **Reusable** - Use the same component across different pages
2. **Consistent** - All delivery cards look and behave the same
3. **Maintainable** - Changes in one place affect all instances
4. **Flexible** - Different card types and behaviors
5. **Simple** - Easy to implement and customize
