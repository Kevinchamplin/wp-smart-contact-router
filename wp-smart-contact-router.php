<?php
/**
 * Plugin Name:       WP Smart Contact Router
 * Plugin URI:        https://github.com/Kevinchamplin/wp-smart-contact-router
 * Description:       Routes contact form submissions to different recipients based on topic and priority.
 * Version:           0.1.0
 * Author:            Kevin Champlin
 * Author URI:        https://github.com/Kevinchamplin
 * Text Domain:       wp-smart-contact-router
 * Requires PHP:      8.0
 */

declare(strict_types=1);

namespace KevinChamplin\WPSmartContactRouter;

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/Plugin.php';

Plugin::bootstrap();
