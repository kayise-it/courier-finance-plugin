<?php
/**
 * Inline status/message strip — wrapper around {@see KIT_Commons::displayHere()}.
 *
 * Before including, optionally set:
 *   $display_here_args = [ 'id' => 'my-region', 'variant' => 'error', ... ];
 */
if (! defined('ABSPATH')) {
    exit;
}

echo KIT_Commons::displayHere(isset($display_here_args) && is_array($display_here_args) ? $display_here_args : []);
