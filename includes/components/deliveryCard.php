<?php
/**
 * DeliveryCard Component
 * Displays delivery information in a clean, modern card format
 */
class KIT_DeliveryCard {
    
    /**
     * Render delivery card component
     * 
     * @param object $delivery Delivery object with properties
     * @param array $options Additional options for customization
     * @return string HTML output
     */
    public static function render($delivery, $options = []) {
        $defaults = [
            'show_actions' => true,
            'show_truck' => true,
            'show_waybill_count' => false,
            'card_class' => 'bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200',
            'actions' => [
                'view' => true,
                'edit' => true,
                'delete' => true
            ]
        ];
        
        $options = array_merge($defaults, $options);
        
        // Get status colors
        $status_colors = [
            'scheduled' => 'bg-blue-100 text-blue-800',
            'in_transit' => 'bg-yellow-100 text-yellow-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800'
        ];
        
        $status_color = $status_colors[$delivery->status] ?? 'bg-gray-100 text-gray-800';
        $status_text = ucfirst(str_replace('_', ' ', $delivery->status));
        
        $html = '<div class="' . $options['card_class'] . '">';
        
        // Header Section
        $html .= '<div class="flex items-start justify-between mb-4">';
        
        // Left side - Tracking ID and Truck
        $html .= '<div class="flex-1">';
        $html .= '<div class="text-lg font-bold text-gray-900 mb-1">' . esc_html($delivery->delivery_reference) . '</div>';
        
        if ($options['show_truck'] && !empty($delivery->truck_number)) {
            $html .= '<div class="text-sm text-gray-600">Truck: ' . esc_html($delivery->truck_number) . '</div>';
        }
        
        $html .= '</div>';
        
        // Right side - Status Badge
        $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $status_color . '">';
        $html .= $status_text;
        $html .= '</span>';
        
        $html .= '</div>';
        
        // Details Section
        $html .= '<div class="space-y-3 mb-4">';
        
        // Origin
        if (!empty($delivery->origin_country)) {
            $html .= '<div class="flex items-center text-sm">';
            $html .= '<svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>';
            $html .= '</svg>';
            $html .= '<span class="text-gray-600">From: <span class="font-medium text-gray-900">' . esc_html($delivery->origin_country) . '</span></span>';
            $html .= '</div>';
        }
        
        // Destination
        if (!empty($delivery->destination_country)) {
            $html .= '<div class="flex items-center text-sm">';
            $html .= '<svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>';
            $html .= '</svg>';
            $html .= '<span class="text-gray-600">To: <span class="font-medium text-gray-900">' . esc_html($delivery->destination_country) . '</span></span>';
            $html .= '</div>';
        }
        
        // Dispatch Date
        if (!empty($delivery->dispatch_date)) {
            $html .= '<div class="flex items-center text-sm">';
            $html .= '<svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>';
            $html .= '</svg>';
            $html .= '<span class="text-gray-600">Dispatch: <span class="font-medium text-gray-900">' . date('M j, Y', strtotime($delivery->dispatch_date)) . '</span></span>';
            $html .= '</div>';
        }
        
        // Waybill Count (optional)
        if ($options['show_waybill_count'] && isset($delivery->waybill_count)) {
            $html .= '<div class="flex items-center text-sm">';
            $html .= '<svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>';
            $html .= '</svg>';
            $html .= '<span class="text-gray-600">Waybills: <span class="font-medium text-gray-900">' . intval($delivery->waybill_count) . '</span></span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Action Buttons Section
        if ($options['show_actions']) {
            $html .= '<div class="flex items-center gap-2">';
            
            if ($options['actions']['view']) {
                $html .= '<a href="?page=kit-deliveries&view_delivery=' . $delivery->id . '" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">';
                $html .= 'View';
                $html .= '</a>';
            }
            
            if ($options['actions']['edit']) {
                $html .= '<a href="?page=kit-deliveries&edit_delivery=' . $delivery->id . '" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">';
                $html .= 'Edit';
                $html .= '</a>';
            }
            
            if ($options['actions']['delete']) {
                $html .= '<button onclick="deleteDelivery(' . $delivery->id . ')" class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 transition-colors">';
                $html .= 'Delete';
                $html .= '</button>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render multiple delivery cards in a grid
     * 
     * @param array $deliveries Array of delivery objects
     * @param array $options Options for the cards
     * @return string HTML output
     */
    public static function renderGrid($deliveries, $options = []) {
        $defaults = [
            'grid_class' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6',
            'empty_message' => 'No deliveries found'
        ];
        
        $options = array_merge($defaults, $options);
        
        if (empty($deliveries)) {
            return '<div class="text-center py-12 text-gray-500">' . esc_html($options['empty_message']) . '</div>';
        }
        
        $html = '<div class="' . $options['grid_class'] . '">';
        
        foreach ($deliveries as $delivery) {
            $html .= self::render($delivery, $options);
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
