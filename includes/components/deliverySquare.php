<?php
if (!defined('ABSPATH')) {
    exit;
}

class KIT_DeliverySquare
{
    /**
     * Render a single delivery square
     * @param array $d expects: title, date, status, subtitle
     * @return string
     */
    public static function render(array $d): string
    {
        $title    = esc_html($d['title'] ?? '');
        $subtitle = esc_html($d['subtitle'] ?? '');
        $date     = esc_html($d['date'] ?? '');
        $status   = esc_html(ucfirst($d['status'] ?? ''));

        $html  = '<div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm w-full">';
        if ($title) {
            $html .= '<div class="text-center text-sm text-gray-700 leading-tight">' . $title . '</div>';
        }
        if ($subtitle) {
            $html .= '<div class="text-center text-xs text-gray-500">' . $subtitle . '</div>';
        }
        if ($date) {
            $html .= '<div class="text-center font-semibold text-gray-900 mt-2">' . $date . '</div>';
        }
        if ($status) {
            $html .= '<div class="text-center text-xs text-gray-500 mt-1">' . $status . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a list of squares
     * @param array $items array of arrays accepted by render()
     * @return string
     */
    public static function renderList(array $items): string
    {
        $html = '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">';
        foreach ($items as $item) {
            $html .= self::render($item);
        }
        $html .= '</div>';
        return $html;
    }
}


