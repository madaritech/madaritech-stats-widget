<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://www.madaritech.com
 * @since      1.0.0
 *
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 * @author     Madaritech <freelance@madaritech.com>
 */
class Md_Site_Stats_Widget_Deactivator
{

    /**
     * Delete transients.
     *
     * Delete all the transients created to cacche the statistics indexes.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        $transients = ['post_statistics','comments_statistics'];
        foreach ($transients as $transient) {
            if (get_transient($transient)) {
                delete_transient($transient);
            }
        }
    }
}
