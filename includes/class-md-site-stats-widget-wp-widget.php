<?php
/**
 * The file that defines the core wp widget class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  http://www.madaritech.com
 * @since 1.0.0
 *
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 */

/**
 * The core wp widget class.
 *
 * This is used to define the widget. This widget let shows in the frontend statisctics about single site or multisite WordPress installation. User can select a refresh time (some minutes to 1 hour) by wich the statistics data will be retrieved from the server. The statistisc data shows, for each site, the number of users, comments and posts. About posts, a detailed table shows the amount of posts for each type (publish, draft, etc.)
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
     * Sets up
     *
     * Sets up the widgets classname and description, creates instance for the widget and creates instance for the logger.
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
     * @param array $args     Widget standard args
     * @param array $instance Widget options
     */
    public function widget($args, $instance)
    {
        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace('Building widget [ args :: ' . var_export($args, true) . ' ][ instance :: ' . var_export($instance, true) . ' ]...');
        }

        echo $args['before_widget'];

        if (! empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        if (empty($instance['refresh'])) {
            $instance['refresh'] = '1';
        }

        wp_enqueue_script('stats_widget', plugin_dir_url(__FILE__) . '../public/js/md-site-stats-widget-ajax.js', array( 'jquery', 'jquery-ui-accordion' ), '', true);

        wp_localize_script(
            'stats_widget',
            'wpApiSettings',
            array(
                'root' => esc_url_raw(rest_url()),
                'refresh_time' => $instance['refresh'],
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );

        echo '<script id="accordion"></script><div id="stats-widget"></div>';

        echo $args['after_widget'];

        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace('Widget built...');
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
            $this->log->trace('Building widget form [ instance :: ' . var_export($instance, true) . ' ]...');
        }

        // outputs the options form on admin
        $title = ! empty($instance['title']) ? $instance['title'] : __('Madaritech Stats', 'md_site_stats_widget');
        $refresh = ! empty($instance['refresh']) ? $instance['refresh'] : __('1', 'md_site_stats_widget'); ?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'md_site_stats_widget'); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">

		<label for="<?php echo $this->get_field_id('refresh'); ?>"><?php _e('Refresh time:', 'md_site_stats_widget'); ?></label> 
		<select class="widefat" id="<?php echo $this->get_field_id('refresh'); ?>" name="<?php echo $this->get_field_name('refresh'); ?>">
			<option value="1" <?php selected($refresh, 1); ?>>1 Minute</option>
			<option value="5" <?php selected($refresh, 5); ?>>5 Minutes</option>
			<option value="15" <?php selected($refresh, 15); ?>>15 Minutes</option>
			<option value="30" <?php selected($refresh, 30); ?>>30 Minutes</option>
			<option value="60" <?php selected($refresh, 60); ?>>60 Minutes</option>
		</select>
		</p>
		<?php

        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace('Widget form built...');
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
            $this->log->trace('Building widget form [ new instance :: ' . var_export($new_instance, true) . ' ][ old instance :: ' . var_export($old_instance, true) . ' ]...');
        }

        // processes widget options to be saved
        $instance = array();
        $instance['title'] = (! empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['refresh'] = (! empty($new_instance['refresh'])) ? strip_tags($new_instance['refresh']) : '';

        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace('Widget form built...');
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
