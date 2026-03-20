<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure main plugin file path is defined
$plugin_file = __DIR__ . '/../08600-services-quotations.php';
if (!file_exists($plugin_file)) {
    return;
}

// Try to load Plugin Update Checker (PUC) via Composer first
if (!class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
    // Fallback to bundled library path if present
    $bundledPucPath = __DIR__ . '/../vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
    if (file_exists($bundledPucPath)) {
        require_once $bundledPucPath;
    }
}

if (!class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
    // As a last resort, try the library if manually placed under includes/lib
    $legacyPucPath = __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';
    if (file_exists($legacyPucPath)) {
        require_once $legacyPucPath;
    }
}

if (!class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
    // PUC not available; bail silently
    return;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p5\Vcs\Api as PucVcsApi;

// Configure your GitHub repository details
// Define these constants in wp-config.php or set them below.
if (!defined('KIT_GITHUB_PLUGIN_REPO')) {
    // owner/repo — override in wp-config.php if you fork or rename the repo
    define('KIT_GITHUB_PLUGIN_REPO', 'kayise-it/courier-finance-plugin');
}

// Optional: Personal Access Token to increase API limits or access private repo
if (!defined('KIT_GITHUB_ACCESS_TOKEN')) {
    define('KIT_GITHUB_ACCESS_TOKEN', '');
}

// Optional: Branch to track for the updater (defaults to main)
if (!defined('KIT_GITHUB_BRANCH')) {
    define('KIT_GITHUB_BRANCH', 'main');
}

$repoUrl = 'https://github.com/' . constant('KIT_GITHUB_PLUGIN_REPO');

$updateChecker = PucFactory::buildUpdateChecker(
    $repoUrl,
    $plugin_file,
    'courier-finance-plugin'
);

// Ask PUC to look for releases. Set the release asset if needed.
// If your plugin main file header has Version matching a Git tag (e.g. v2.0.1), ensure tag format aligns.
$updateChecker->setBranch(constant('KIT_GITHUB_BRANCH'));

// Prefer the tracked branch over GitHub Releases/tags so "push to main" updates apply
// even when no release exists or an old release would win.
add_filter(
    'puc_vcs_update_detection_strategies-courier-finance-plugin',
    static function ($strategies) {
        if (!isset($strategies[PucVcsApi::STRATEGY_BRANCH])) {
            return $strategies;
        }
        $branchStrategy = $strategies[PucVcsApi::STRATEGY_BRANCH];
        unset($strategies[PucVcsApi::STRATEGY_BRANCH]);
        return array_merge([PucVcsApi::STRATEGY_BRANCH => $branchStrategy], $strategies);
    },
    10,
    1
);

// Support private repos or higher rate limits
$token = constant('KIT_GITHUB_ACCESS_TOKEN');
if (is_string($token) && $token !== '') {
    $updateChecker->setAuthentication($token);
}

if (defined('WP_DEBUG') && WP_DEBUG) {
    $updateChecker->setCacheDuration(5 * MINUTE_IN_SECONDS);
} else {
    $updateChecker->setCacheDuration(6 * HOUR_IN_SECONDS);
}


