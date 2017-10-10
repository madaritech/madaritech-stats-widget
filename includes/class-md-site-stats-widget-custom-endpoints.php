<?php 

/**
 * The file that defines the custom endpoints class
 *
 * A class definition that includes attributes and functions used to build the custom endpoints.
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
class Md_Site_Stats_Widget_Custom_Endpoints
{

    /**
     * The ID of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $version The current version of this plugin.
     */
    private $version;

    /*
    * A {@link Md_Site_Stats_Widget_Log_Service} instance.
    *
    * @since 1.0.0
    * @access private
    * @var \Md_Site_Stats_Widget_Log_Service $log A {@link Md_Site_Stats_Widget_Log_Service_Log_Service} instance.
    */
    private $log;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        $this->log = Md_Site_Stats_Widget_Log_Service::create('Md_Site_Stats_Widget_Custom_Endpoints');
    }

    /**
     * Register the custom REST route
     *
     * @since 1.0.0
     *
     */
    public function register_route()
    {
        register_rest_route(
            'md-site-stats-widget/v1',
            '/stats',
            array(
                'methods' => 'GET',
                'callback' => array(&$this, 'statistics'),
            )
        );
    }

    /**
     * Calculates the statistics for single or multi site. Return the statistics in JSON format or an error messagge if it is impossible to get the statistics.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request The request object.
     * @return string|null The response or null if none.
     */
    public function statistics(WP_REST_Request $request)
    {
        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Retrieving statistics [ request :: " .var_export($request, true)." ]...");
        }
        
        global $wpdb;
        $blog_count = 0;
        $sites_stats = array();

        if (is_multisite()) {
            $sites = get_sites();
            foreach ($sites as $key => $value) {
                $url        = $value->domain.$value->path;
                $id         = $value->id;
                $prefix     = $wpdb->get_blog_prefix($id);
                $blog_users = count(get_users("blog_id=$id"));
                $blogname   = get_blog_details($id)->blogname;
                $blog_count = get_blog_count();

                $sites_stats[$id] = $this->stats_index($url, $id, $prefix, $blog_users, $blogname, $blog_count);
            }
        } else {
            $id         = get_current_blog_id();
            $url        = site_url();
            $prefix     = $wpdb->get_blog_prefix();
            $blog_users = count_users();
            $blogname   = get_bloginfo('name');
            $blog_count = 1;

            $sites_stats[] = $this->stats_index($url, $id, $prefix, $blog_users['total_users'], $blogname, $blog_count);
        }

        foreach ($sites_stats as $site_stats) {
            if (empty($site_stats)) {
                $err = new WP_Error('error', 'Statistics not available', array( 'status' => 404 ));
                $response = rest_ensure_response($err);
                    
                if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
                    $this->log->warn("Error in retrieving statistics [ response :: " .var_export($response, true)." ]...");
                }
                return $response;
            }
        }
        

        $stats = array( 'blog_count' => $blog_count, 'sites' => $sites_stats);
        $response = rest_ensure_response($stats);
        
        if (Md_Site_Stats_Widget_Log_Service::is_enabled()) {
            $this->log->trace("Statistics retrieved [ response :: " .var_export($response, true)." ]...");
        }
        
        return $response;
    }

    /**
     * Calculates the statistic indexes.
     *
     * @since 1.0.0
     *
     * @access private
     * @param string $url The site url.
     * @param int $id The site id.
     * @param string $prefix prefix to build site's table name.
     * @param int $blog_users The sites' user number.
     * @param string $blogname The site name.
     * @param int $blog_count The number of sites in the network (1 for single site).
     * @return array The statistics about posts and comments.
     */
    private function stats_index($url, $id, $prefix, $blog_users, $blogname, $blog_count)
    {
        $posts = $this->get_post_statistics($id, $prefix);

        $post_count['publish'] = 0;
        $post_count['future'] = 0;
        $post_count['draft'] = 0;
        $post_count['pending'] = 0;
        $post_count['private'] = 0;
        $post_count['trash'] = 0;
        $post_count['auto-draft'] = 0;
        $post_count['inherit'] = 0;
        $post_total = 0;

        foreach ($posts as $post) {
            $post_total = $post_total + intVal($post->post_number);
            $post_count[$post->post_status] = $post->post_number;
        }

        //Blog comments
        $comments = $this->get_comment_statistics($id, $prefix);

        //Recording site stats
        $site_stats = [
            'url' => $url,
            'users' => $blog_users,
            'blogname' => $blogname,
            'posts' => $post_total,
            'post_publish' => $post_count['publish'],
            'post_future' => $post_count['future'],
            'post_draft' => $post_count['draft'],
            'post_pending' => $post_count['pending'],
            'post_private' => $post_count['private'],
            'post_trash' => $post_count['trash'],
            'post_auto_draft' => $post_count['auto-draft'],
            'post_inherit' => $post_count['inherit'],
            'comments' => $comments
        ];

        return $site_stats;
    }

    /**
     * Retrieve the post statistics. The result are cached using transient: first get results from transient, but if transient doesn't exists, sets the transient.
     *
     * @since 1.0.0
     *
     * @access private
     * @param int $id The site id
     * @param string $prefix The prefix of the current site
     * @return object The posts statistics.
     */
    private function get_post_statistics($id, $prefix)
    {
        global $wpdb;

        // Check for transient. If none, then execute WP_Query
        $posts = (is_multisite()) ? get_site_transient('post_statistics'.$id) : get_transient('post_statistics'.$id);
        if (false === $posts) {
            $posts = $wpdb->get_results("SELECT count(*) as post_number, post_status FROM {$prefix}posts where post_type='post' group by post_status");

            // Put the results in a transient. No expiration time.
            if (is_multisite()) {
                set_site_transient('post_statistics'.$id, $posts);
            } else {
                set_transient('post_statistics'.$id, $posts);
            }
        }
        return $posts;
    }

    /**
     * Retrieve the transient comment statistics. The result are cached using transient: first get results from transient, but if transient doesn't exists, sets the transient.
     *
     * @since 1.0.0
     *
     * @access private
     * @param int $id The site id
     * @param string $prefix The prefix of the current site
     * @return object The comments number.
     */
    private function get_comment_statistics($id, $prefix)
    {
        global $wpdb;

        // Check for transient. If none, then execute WP_Query
        $comments = (is_multisite()) ? get_site_transient('comments_statistics'.$id) : get_transient('comments_statistics'.$id);
        if (false === $comments) {
            $comments = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}comments");

            // Put the results in a transient. No expiration time.
            if (is_multisite()) {
                set_site_transient('comments_statistics'.$id, $comments);
            } else {
                set_transient('comments_statistics'.$id, $comments);
            }
        }
        return $comments;
    }

    /**
     * Delete the transient on post statistics refresh.
     *
     * @since 1.0.0
     *
     * @access private
     * @param string $key The key used to identify the transient.
     */
    private function refresh_statistics($key)
    {
        $id = get_current_blog_id();
        if (is_multisite()) {
            delete_site_transient($key.$id);
        } else {
            delete_transient($key.$id);
        }
    }

    /**
     * Delete the transient on post statistics refresh.
     *
     * @since 1.0.0
     *
     * @access private
     * @param int $post_id The post id.
     */
    public function refresh_post_statistics($post_id)
    {
        $this->refresh_statistics('post_statistics');
    }

    /**
     * Delete the transient on comments statistics refresh
     *
     * @since 1.0.0
     *
     * @access private
     * @param int $post_id The post id.
     */
    public function refresh_comment_statistics()
    {
        $this->refresh_statistics('comments_statistics');
    }
}
