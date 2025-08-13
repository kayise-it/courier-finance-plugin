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
    public static function render($stats = [], $title = '') {
        $html = '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">';
        
        if ($title) {
            $html .= '<h3 class="text-sm font-medium text-gray-700 mb-4">' . esc_html($title) . '</h3>';
        }
        
        $html .= '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
        foreach ($stats as $stat) {
            $color = $stat['color'] ?? 'blue';
            $colorClasses = self::getColorClasses($color);
            
            $html .= '<div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">';
            $html .= '<div class="' . $colorClasses['icon'] . ' p-2 rounded-lg">';
            $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $stat['icon'] . '"></path>';
            $html .= '</svg>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="text-xs font-medium text-gray-500">' . esc_html($stat['title']) . '</div>';
            $html .= '<div class="text-lg font-bold text-gray-900">' . esc_html($stat['value']) . '</div>';
            $html .= '</div>';
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
            ]
        ];
        
        return $colors[$color] ?? $colors['blue'];
    }
}
