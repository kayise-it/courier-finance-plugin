<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__, 4) . '/'); // Adjust the path as needed
}

// Load DOMPDF setup
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once ABSPATH . 'wp-load.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (isset($_GET['selected_ids'])) {
    $selected_ids_string = $_GET['selected_ids']; // e.g. "4000,4003,4004"
    $selected_ids_array = array_map('intval', explode(',', $selected_ids_string));

    if (!empty($selected_ids_array)) {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        ob_start(); // Start buffering HTML content

    ?>
<h1>adsad</h1>
    <?php 
        $html = ob_get_clean(); // Get the full buffered HTML
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream("bulk-invoice.pdf", [
            "Attachment" => true // Set to false if you want inline preview in browser
        ]);
    }
}