<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.madaritech.com
 * @since             1.0.0
 * @package           Md_Site_Stats_Widget
 *
 * @wordpress-plugin
 * Plugin Name:       Madaritech Site Stats Widget
 * Plugin URI:        http://www.madaritech.com/md-site-stats-widget
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Madaritech
 * Author URI:        http://www.madaritech.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       md-site-stats-widget
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-md-site-stats-widget-activator.php
 */
function activate_md_site_stats_widget() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-md-site-stats-widget-activator.php';
	Md_Site_Stats_Widget_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-md-site-stats-widget-deactivator.php
 */
function deactivate_md_site_stats_widget() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-md-site-stats-widget-deactivator.php';
	Md_Site_Stats_Widget_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_md_site_stats_widget' );
register_deactivation_hook( __FILE__, 'deactivate_md_site_stats_widget' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-md-site-stats-widget.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_md_site_stats_widget() {

	$plugin = new Md_Site_Stats_Widget();
	$plugin->run();

}
run_md_site_stats_widget();
