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

// Configure your GitHub repository details
// Define these constants in wp-config.php or set them below.
if (!defined('KIT_GITHUB_PLUGIN_REPO')) {
    // Example: owner/repo
    define('KIT_GITHUB_PLUGIN_REPO', 'your-github-user/your-plugin-repo');
}

// Optional: Personal Access Token to increase API limits or access private repo
if (!defined('KIT_GITHUB_ACCESS_TOKEN')) {
    define('KIT_GITHUB_ACCESS_TOKEN', '');
}

$repoUrl = 'https://github.com/' . constant('KIT_GITHUB_PLUGIN_REPO');

$updateChecker = PucFactory::buildUpdateChecker(
    $repoUrl,
    $plugin_file,
    'courier-finance-plugin'
);

// Ask PUC to look for releases. Set the release asset if needed.
// If your plugin main file header has Version matching a Git tag (e.g. v2.0.1), ensure tag format aligns.
$updateChecker->setBranch('main');

// Support private repos or higher rate limits
$token = constant('KIT_GITHUB_ACCESS_TOKEN');
if (is_string($token) && $token !== '') {
    $updateChecker->setAuthentication($token);
}

// Optional: Cache TTL tweak (default is fine). Example to check every 12 hours:
// $updateChecker->setCacheDuration(12 * HOUR_IN_SECONDS);


