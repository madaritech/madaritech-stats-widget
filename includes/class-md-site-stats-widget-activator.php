<?php
/**
 * Fired during plugin activation
 *
 * @link       http://www.madaritech.com
 * @since      1.0.0
 *
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 * @author     Madaritech <freelance@madaritech.com>
 */
class Md_Site_Stats_Widget_Activator {


	/**
	 * Delete transients.
	 *
	 * Delete all the transients created to cacche the statistics indexes.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$transients = [ 'post_statistics', 'comment_statistics' ];

		if ( is_multisite() ) {
			$sites = get_sites();
			foreach ( $sites as $key => $value ) {
				$id = $value->id;
				foreach ( $transients as $transient ) {
					if ( get_site_transient( $transient . $id ) ) {
						delete_site_transient( $transient . $id );
					}
				}
			}
		} else {
			$id = get_current_blog_id();
			foreach ( $transients as $transient ) {
				if ( get_transient( $transient . $id ) ) {
					delete_transient( $transient . $id );
				}
			}
		}
	}
}
