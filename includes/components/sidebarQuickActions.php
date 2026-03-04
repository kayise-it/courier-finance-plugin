<?php
if (!defined('ABSPATH')) {
    exit;
}

class KIT_SidebarQuickActions
{
    /**
     * Render a compact right-sidebar quick actions card.
     *
     * @param array $actions Array of actions with keys: title, description, href, icon, color.
     * @param string $title Sidebar title.
     * @param string $subtitle Sidebar subtitle.
     * @param array $context Extra context for filters (e.g. page, mode, role, entity_id).
     * @return string
     */
    public static function render($actions = [], $title = 'Quick Actions', $subtitle = 'Suggested quick actions', $context = [])
    {
        if (empty($actions)) {
            $actions = [
                [
                    'title' => 'All Drivers',
                    'description' => 'Back to the drivers list',
                    'href' => admin_url('admin.php?page=manage-drivers'),
                    'icon' => 'M3 7h18M3 12h18M3 17h18',
                    'color' => 'blue',
                ],
            ];
        }

        $context = is_array($context) ? $context : [];

        /**
         * Filter all quick action items before render.
         *
         * @param array $actions
         * @param array $context
         */
        $actions = apply_filters('kit_sidebar_quick_actions_actions', $actions, $context);

        /**
         * Filter quick actions card title before render.
         *
         * @param string $title
         * @param array $context
         */
        $title = apply_filters('kit_sidebar_quick_actions_title', $title, $context);

        /**
         * Filter quick actions card subtitle before render.
         *
         * @param string $subtitle
         * @param array $context
         */
        $subtitle = apply_filters('kit_sidebar_quick_actions_subtitle', $subtitle, $context);

        $html = '<aside class="bg-white rounded-lg shadow-md border border-gray-200 p-5">';
        $html .= '<h3 class="text-lg font-semibold text-gray-900">' . esc_html($title) . '</h3>';
        $html .= '<p class="text-sm text-gray-500 mt-1 mb-4">' . esc_html($subtitle) . '</p>';
        $html .= '<div class="space-y-3">';

        foreach ($actions as $action) {
            if (!is_array($action)) {
                continue;
            }

            $color = $action['color'] ?? 'gray';
            $color_classes = self::getColorClasses($color);
            $href = isset($action['href']) ? $action['href'] : '#';
            $icon = isset($action['icon']) ? $action['icon'] : 'M12 4v16m8-8H4';
            $title_text = isset($action['title']) ? $action['title'] : 'Action';
            $description = isset($action['description']) ? $action['description'] : '';

            $html .= '<a href="' . esc_url($href) . '" class="block rounded-lg border ' . $color_classes['border'] . ' ' . $color_classes['bg'] . ' hover:' . $color_classes['hover'] . ' p-3 transition-colors">';
            $html .= '<div class="flex items-start">';
            $html .= '<div class="w-8 h-8 rounded-md ' . $color_classes['iconBg'] . ' flex items-center justify-center mr-3 flex-shrink-0">';
            $html .= '<svg class="w-4 h-4 ' . $color_classes['iconColor'] . '" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . esc_attr($icon) . '"></path>';
            $html .= '</svg>';
            $html .= '</div>';
            $html .= '<div class="min-w-0">';
            $html .= '<p class="text-sm font-medium text-gray-900">' . esc_html($title_text) . '</p>';
            if ($description !== '') {
                $html .= '<p class="text-xs text-gray-600 mt-0.5">' . esc_html($description) . '</p>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</aside>';

        return $html;
    }

    /**
     * Resolve color classes for sidebar action blocks.
     *
     * @param string $color
     * @return array
     */
    private static function getColorClasses($color)
    {
        $colors = [
            'blue' => [
                'bg' => 'bg-blue-50',
                'hover' => 'bg-blue-100',
                'border' => 'border-blue-200',
                'iconBg' => 'bg-blue-100',
                'iconColor' => 'text-blue-600',
            ],
            'green' => [
                'bg' => 'bg-green-50',
                'hover' => 'bg-green-100',
                'border' => 'border-green-200',
                'iconBg' => 'bg-green-100',
                'iconColor' => 'text-green-600',
            ],
            'purple' => [
                'bg' => 'bg-purple-50',
                'hover' => 'bg-purple-100',
                'border' => 'border-purple-200',
                'iconBg' => 'bg-purple-100',
                'iconColor' => 'text-purple-600',
            ],
            'orange' => [
                'bg' => 'bg-orange-50',
                'hover' => 'bg-orange-100',
                'border' => 'border-orange-200',
                'iconBg' => 'bg-orange-100',
                'iconColor' => 'text-orange-600',
            ],
            'red' => [
                'bg' => 'bg-red-50',
                'hover' => 'bg-red-100',
                'border' => 'border-red-200',
                'iconBg' => 'bg-red-100',
                'iconColor' => 'text-red-600',
            ],
            'gray' => [
                'bg' => 'bg-gray-50',
                'hover' => 'bg-gray-100',
                'border' => 'border-gray-200',
                'iconBg' => 'bg-gray-100',
                'iconColor' => 'text-gray-600',
            ],
        ];

        return $colors[$color] ?? $colors['gray'];
    }
}
