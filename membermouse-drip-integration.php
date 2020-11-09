<?php
/**
 * Plugin Name: MemberMouse Drip Integration
 * Version: 1.0.0
 * Plugin URI: https://membermouse.com
 * Description: This plugin allows you to integrate Drip with MemberMouse.
 * Author: MemberMouse, LLC
 * Author URI: https://membermouse.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: membermouse-drip-integration
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author MemberMouse, LLC
 * @since 1.0.0
 */
if (! defined('ABSPATH')) {
    exit();
}

// Load plugin class files.
require_once 'includes/class-membermouse-drip-integration.php';
require_once 'includes/class-membermouse-drip-integration-settings.php';

/**
 * Returns the main instance of MemberMouse_Drip_Integration to prevent the need to use globals.
 *
 * @since 1.0.0
 * @return object MemberMouse_Drip_Integration
 */
function membermouse_drip_integration()
{
    $instance = MemberMouse_Drip_Integration::instance(__FILE__, '1.0.0');

    if (is_null($instance->settings)) {
        $instance->settings = MemberMouse_Drip_Integration_Settings::instance($instance);
    }

    return $instance;
}

membermouse_drip_integration();
