<?php
if (!defined('ABSPATH')) {
    exit;
}

class KIT_QuickActions {
    
    /**
     * Render Quick Actions component
     * 
     * @param array $actions Array of action items with keys: title, description, href, icon, color
     * @param string $title Section title (default: "Quick Actions")
     * @return string HTML output
     */
    public static function render($actions = [], $title = 'Quick Actions') {
        // Default actions if none provided
        if (empty($actions)) {
            $actions = [
                [
                    'title' => 'Create Waybill',
                    'description' => 'Generate new waybill',
                    'href' => '?page=08600-waybill-create',
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'color' => 'blue'
                ],
                [
                    'title' => 'Manage Customers',
                    'description' => 'View and edit customers',
                    'href' => '?page=08600-customers',
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'color' => 'green'
                ],
                [
                    'title' => 'Manage Routes',
                    'description' => 'Configure shipping routes',
                    'href' => '?page=route-management',
                    'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m-6 3l6-3',
                    'color' => 'purple'
                ],
                [
                    'title' => 'Warehouse',
                    'description' => 'Manage warehouse waybills',
                    'href' => '?page=warehouse-waybills',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'color' => 'orange'
                ]
            ];
        }

        $html = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">';
        $html .= '<h3 class="text-lg font-semibold text-gray-900 mb-4">' . esc_html($title) . '</h3>';
        $html .= '<div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-4">';

        foreach ($actions as $action) {
            $color = $action['color'] ?? 'gray';
            $colorClasses = self::getColorClasses($color);
            
            $onclick = isset($action['onclick']) ? ' onclick="' . esc_attr($action['onclick']) . '"' : '';
            $html .= '<a href="' . esc_url($action['href']) . '"' . $onclick . ' class="flex items-center p-4 ' . $colorClasses['bg'] . ' hover:' . $colorClasses['hover'] . ' rounded-lg border ' . $colorClasses['border'] . ' transition-colors group">';
            $html .= '<div class="w-10 h-10 ' . $colorClasses['iconBg'] . ' rounded-lg flex items-center justify-center mr-3 group-hover:' . $colorClasses['iconHover'] . '">';
            $html .= '<svg class="w-5 h-5 ' . $colorClasses['iconColor'] . '" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . esc_attr($action['icon']) . '"></path>';
            $html .= '</svg>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<h4 class="font-medium text-gray-900">' . esc_html($action['title']) . '</h4>';
            $html .= '<p class="text-[7px] text-gray-600">' . esc_html($action['description']) . '</p>';
            $html .= '</div>';
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
                'hover' => 'bg-blue-100',
                'border' => 'border-blue-200',
                'iconBg' => 'bg-blue-100',
                'iconHover' => 'bg-blue-200',
                'iconColor' => 'text-blue-600'
            ],
            'green' => [
                'bg' => 'bg-green-50',
                'hover' => 'bg-green-100',
                'border' => 'border-green-200',
                'iconBg' => 'bg-green-100',
                'iconHover' => 'bg-green-200',
                'iconColor' => 'text-green-600'
            ],
            'purple' => [
                'bg' => 'bg-purple-50',
                'hover' => 'bg-purple-100',
                'border' => 'border-purple-200',
                'iconBg' => 'bg-purple-100',
                'iconHover' => 'bg-purple-200',
                'iconColor' => 'text-purple-600'
            ],
            'orange' => [
                'bg' => 'bg-orange-50',
                'hover' => 'bg-orange-100',
                'border' => 'border-orange-200',
                'iconBg' => 'bg-orange-100',
                'iconHover' => 'bg-orange-200',
                'iconColor' => 'text-orange-600'
            ],
            'red' => [
                'bg' => 'bg-red-50',
                'hover' => 'bg-red-100',
                'border' => 'border-red-200',
                'iconBg' => 'bg-red-100',
                'iconHover' => 'bg-red-200',
                'iconColor' => 'text-red-600'
            ],
            'yellow' => [
                'bg' => 'bg-yellow-50',
                'hover' => 'bg-yellow-100',
                'border' => 'border-yellow-200',
                'iconBg' => 'bg-yellow-100',
                'iconHover' => 'bg-yellow-200',
                'iconColor' => 'text-yellow-600'
            ],
            'gray' => [
                'bg' => 'bg-gray-50',
                'hover' => 'bg-gray-100',
                'border' => 'border-gray-200',
                'iconBg' => 'bg-gray-100',
                'iconHover' => 'bg-gray-200',
                'iconColor' => 'text-gray-600'
            ]
        ];

        return $colors[$color] ?? $colors['gray'];
    }
}
