<?php
if (!defined('ABSPATH')) {
    exit;
}

class KIT_DashboardQuickies {
    
    /**
     * Render Dashboard Quickies component
     * 
     * @param array $actions Array of action items with keys: title, href, icon, color
     * @param string $title Section title (optional)
     * @return string HTML output
     */
    public static function render($actions = [], $title = '') {
        // Default actions if none provided
        if (empty($actions)) {
            $actions = [
                [
                    'title' => 'Create Waybill',
                    'href' => '?page=08600-waybill-create',
                    'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
                    'color' => 'blue'
                ],
                [
                    'title' => 'Manage Customers',
                    'href' => '?page=08600-customers',
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'color' => 'green'
                ],
                [
                    'title' => 'Manage Routes',
                    'href' => '?page=route-management',
                    'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m-6 3l6-3',
                    'color' => 'purple'
                ],
                [
                    'title' => 'Warehouse',
                    'href' => '?page=warehouse-waybills',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'color' => 'orange'
                ]
            ];
        }

        $html = '<div class="dashboard-quickies bg-white rounded-lg border-gray-200 p-4">';
        
        if (!empty($title)) {
            $html .= '<h3 class="text-sm font-medium text-gray-700 mb-3">' . esc_html($title) . '</h3>';
        }
        
        $html .= '<div class="flex flex-wrap gap-2">';

        foreach ($actions as $action) {
            $color = $action['color'] ?? 'gray';
            $colorClasses = self::getColorClasses($color);
            $onclick = isset($action['onclick']) ? ' onclick="' . esc_attr($action['onclick']) . '"' : '';
            
            $html .= '<a href="' . esc_url($action['href']) . '"' . $onclick . ' class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-md ' . $colorClasses['bg'] . ' ' . $colorClasses['text'] . ' ' . $colorClasses['border'] . ' hover:' . $colorClasses['hover'] . ' transition-colors duration-200 group">';
            $html .= '<svg class="w-3 h-3 mr-1.5 ' . $colorClasses['iconColor'] . '" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . esc_attr($action['icon']) . '"></path>';
            $html .= '</svg>';
            $html .= esc_html($action['title']);
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get color classes for different action types
     * 
     * @param string $color Color name
     * @return array Color classes
     */
    private static function getColorClasses($color) {
        $colors = [
            'blue' => [
                'bg' => 'bg-blue-50',
                'text' => 'text-blue-700',
                'border' => 'border-blue-200',
                'hover' => 'bg-blue-100',
                'iconColor' => 'text-blue-500'
            ],
            'green' => [
                'bg' => 'bg-green-50',
                'text' => 'text-green-700',
                'border' => 'border-green-200',
                'hover' => 'bg-green-100',
                'iconColor' => 'text-green-500'
            ],
            'purple' => [
                'bg' => 'bg-purple-50',
                'text' => 'text-purple-700',
                'border' => 'border-purple-200',
                'hover' => 'bg-purple-100',
                'iconColor' => 'text-purple-500'
            ],
            'orange' => [
                'bg' => 'bg-orange-50',
                'text' => 'text-orange-700',
                'border' => 'border-orange-200',
                'hover' => 'bg-orange-100',
                'iconColor' => 'text-orange-500'
            ],
            'red' => [
                'bg' => 'bg-red-50',
                'text' => 'text-red-700',
                'border' => 'border-red-200',
                'hover' => 'bg-red-100',
                'iconColor' => 'text-red-500'
            ],
            'yellow' => [
                'bg' => 'bg-yellow-50',
                'text' => 'text-yellow-700',
                'border' => 'border-yellow-200',
                'hover' => 'bg-yellow-100',
                'iconColor' => 'text-yellow-500'
            ],
            'gray' => [
                'bg' => 'bg-gray-50',
                'text' => 'text-gray-700',
                'border' => 'border-gray-200',
                'hover' => 'bg-gray-100',
                'iconColor' => 'text-gray-500'
            ]
        ];

        return $colors[$color] ?? $colors['gray'];
    }
}
