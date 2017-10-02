<?php 

/**
 * The file that defines the core wp widget class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.madaritech.com
 * @since      1.0.0
 *
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 */

/**
 * The core wp widget class.
 *
 * This is used to define the widget.
 *
 * @since      1.0.0
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 * @author     Madaritech <freelance@madaritech.com>
 */
class Md_Site_Stats_Widget_Wp_Widget extends WP_Widget
{
    /*
    * A {@link Md_Site_Stats_Widget_Log_Service} instance.
    *
    * @since 1.0.0
    * @access private
    * @var \Md_Site_Stats_Widget_Log_Service $log A {@link Md_Site_Stats_Widget_Log_Service_Log_Service} instance.
    */
    private $log;

    /**
     * Sets up the widgets name etc
     */
    public function __construct()
    {
        $widget_ops = array(
            'classname' => 'md_site_stats_widget_wp_widget',
            'description' => 'Madaritech Stats Widget',
        );
        parent::__construct('md_site_stats_widget_wp_widget', 'Madaritech Stats Widget', $widget_ops);

        $this->log = Md_Site_Stats_Widget_Log_Service::create('Md_Site_Stats_Widget_Wp_Widget');
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Building widget [ args :: " .var_export($args, true)." ][ instance :: " .var_export($instance, true)." ]...");
        }

        echo $args['before_widget'];

        if (! empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        if (empty($instance['refresh'])) {
            $instance['refresh'] = '1';
        }

        wp_enqueue_script('stats_widget', plugin_dir_url(__FILE__) . '../public/js/md-site-stats-widget-ajax.js', array( 'jquery', 'jquery-ui-accordion' ), '', true);

        wp_localize_script('stats_widget', 'wpApiSettings', array(
                                                                'root' => esc_url_raw(rest_url()),
                                                                'refresh_time' => $instance['refresh'],
                                                                'nonce' => wp_create_nonce('wp_rest')
                                                            ));

        echo '<script id="accordion"></script><div id="stats-widget"></div>';

        echo $args['after_widget'];

        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Widget built...");
        }
    }

    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form($instance)
    {
        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Building widget form [ instance :: " .var_export($instance, true). " ]...");
        }

        // outputs the options form on admin
        $title = ! empty($instance['title']) ? $instance['title'] : esc_html__('Madaritech Stats', 'md_site_stats_widget');
        $refresh = ! empty($instance['refresh']) ? $instance['refresh'] : esc_html__('1', 'md_site_stats_widget'); ?>
        <p>
        <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:', 'md_site_stats_widget'); ?></label> 
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">

        <label for="<?php echo esc_attr($this->get_field_id('refresh')); ?>"><?php esc_attr_e('Refresh time:', 'md_site_stats_widget'); ?></label> 
        <select class="widefat" id="<?php echo esc_attr($this->get_field_id('refresh')); ?>" name="<?php echo esc_attr($this->get_field_name('refresh')); ?>">
        <?php $refresh=esc_attr($refresh); ?>
            <option value="1" <?php echo ($refresh=='1')?'selected':''; ?>>1 Minute</option>
            <option value="5" <?php echo ($refresh=='5')?'selected':''; ?>>5 Minutes</option>
            <option value="15" <?php echo ($refresh=='15')?'selected':''; ?>>15 Minutes</option>
            <option value="30" <?php echo ($refresh=='30')?'selected':''; ?>>30 Minutes</option>
            <option value="60" <?php echo ($refresh=='60')?'selected':''; ?>>60 Minutes</option>
        </select>
        </p>
        <?php

        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Widget form built...");
        }
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     *
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Building widget form [ new instance :: " .var_export($new_instance, true). " ][ old instance :: " .var_export($old_instance, true). " ]...");
        }

        // processes widget options to be saved
        $instance = array();
        $instance['title'] = (! empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['refresh'] = (! empty($new_instance['refresh'])) ? strip_tags($new_instance['refresh']) : '';

        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Widget form built...");
        }

        return $instance;
    }

    /**
     * Register the Widget
     */
    public function register_widget()
    {
        register_widget('Md_Site_Stats_Widget_Wp_Widget');
    }
}
