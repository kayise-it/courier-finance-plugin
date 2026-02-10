<?php
/**
 * QuickStats Component
 * Displays statistics in a single row with clean, modern styling
 */
class KIT_QuickStats {
    
    /**
     * Render quick stats component
     * 
     * @param array $stats Array of stats with 'title', 'value', 'icon', 'color' keys
     * @param string $title Optional section title
     * @return string HTML output
     */
    public static function render($stats = [], $title = '', $options = []) {
        $stats = is_array($stats) ? $stats : [];
        $grid_cols = $options['grid_cols'] ?? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4';
        $show_icons = $options['show_icons'] ?? true;
        $gap = $options['gap'] ?? 'gap-6';
        
        $html = '<div class="mb-8">';
        
        if ($title) {
            $html .= '<div class="flex items-center justify-between mb-4">';
            $html .= '<h3 class="text-lg font-semibold text-gray-900">' . esc_html($title) . '</h3>';
            $html .= '</div>';
        }
        
        $html .= '<div class="grid ' . esc_attr($grid_cols) . ' ' . esc_attr($gap) . '">';
        foreach ($stats as $stat) {
            $has_icon = $show_icons && isset($stat['icon']);
            $color = $stat['color'] ?? 'blue';
            $colorClasses = self::getColorClasses($color);
            
            // Check if stat is clickable
            $clickable = isset($stat['clickable']) && $stat['clickable'];
            $onclick = isset($stat['onclick']) ? ' onclick="' . esc_js($stat['onclick']) . '"' : '';
            $dataFilter = isset($stat['filter']) ? ' data-filter="' . esc_attr($stat['filter']) . '"' : '';
            $cardClasses = 'bg-white rounded-xl shadow-sm border border-gray-200 p-6 transition-all duration-200';
            if ($clickable) {
                $cardClasses .= ' hover:shadow-md hover:border-blue-300 cursor-pointer';
            } else {
                $cardClasses .= ' hover:shadow-md';
            }
            $html .= '<div class="' . $cardClasses . '"' . ($clickable ? $onclick . $dataFilter : '') . '>';
            
            if ($has_icon) {
                // Delivery Management layout: icon on left, text on right
                $html .= '<div class="flex items-center">';
                $html .= '<div class="flex-shrink-0">';
                $html .= '<div class="w-8 h-8 ' . $colorClasses['icon'] . ' rounded-lg flex items-center justify-center">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . esc_attr($stat['icon']) . '"></path>';
                $html .= '</svg>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="ml-4">';
                $html .= '<p class="text-sm font-medium text-gray-600">' . esc_html($stat['title']) . '</p>';
                $value_class = isset($stat['class']) ? esc_attr($stat['class']) : '';
                $html .= '<p class="text-2xl font-bold text-gray-900 ' . $value_class . '">' . esc_html($stat['value']) . '</p>';
                $html .= '</div>';
                $html .= '</div>';
            } else {
                // No icon layout: title on top, value below
                $html .= '<h3 class="text-sm font-medium text-gray-600 mb-2">' . esc_html($stat['title']) . '</h3>';
                $value_class = isset($stat['class']) ? esc_attr($stat['class']) : '';
                $html .= '<p class="text-2xl font-bold text-gray-900 ' . $value_class . '">' . esc_html($stat['value']) . '</p>';
            }
            
            if (isset($stat['subtitle'])) {
                $html .= '<p class="text-xs text-gray-500 mt-2">' . esc_html($stat['subtitle']) . '</p>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get color classes for different stat types
     * 
     * @param string $color Color name
     * @return array Color classes
     */
    private static function getColorClasses($color) {
        $colors = [
            'blue' => [
                'icon' => 'bg-blue-100 text-blue-600'
            ],
            'green' => [
                'icon' => 'bg-green-100 text-green-600'
            ],
            'yellow' => [
                'icon' => 'bg-yellow-100 text-yellow-600'
            ],
            'purple' => [
                'icon' => 'bg-purple-100 text-purple-600'
            ],
            'red' => [
                'icon' => 'bg-red-100 text-red-600'
            ],
            'gray' => [
                'icon' => 'bg-gray-100 text-gray-600'
            ],
            'orange' => [
                'icon' => 'bg-orange-100 text-orange-600'
            ]
        ];
        
        return $colors[$color] ?? $colors['blue'];
    }
}
