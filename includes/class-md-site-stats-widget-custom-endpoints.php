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

        /*$sites = get_sites();
        var_dump($sites);
        exit();*/
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
     * Calculates the statistics
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request The request object.
     * @return string|null The response or null if none.
     */
    public function statistics(WP_REST_Request $request)
    {
        global $wpdb;
        $site_stats = array();
        $sites = get_sites();

        foreach ($sites as $key => $value) {
            
            //Blog url
            $url = $value->domain.$value->path;
            
            //Blog id
            $id = $value->id;
            $ids = ($id == 1) ? '' : $id.'_';

            // Blog users
            $blog_users = get_users("blog_id=$id");
            $users = count($blog_users);
            
            //Blog name
            $blog_details = get_blog_details($id);
            $blogname = $blog_details->blogname; ///SErve???

            //Blog posts
            /*$posts = $wpdb->get_var("SELECT COUNT(*) FROM wp_{$ids}posts WHERE post_status='publish' and post_type='post'");
            $posts = (!isset($posts) || empty($posts)) ? '0' : $posts;*/

            $posts= $wpdb->get_results("SELECT count(*) as post_number, post_status FROM wp_{$ids}posts where post_type='post' group by post_status");

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
            $comments = $wpdb->get_var("SELECT COUNT(*) FROM wp_{$ids}comments");
            $comments = (!isset($comments) || empty($comments)) ? '0' : $comments;

            //Blog terms
            $terms = $wpdb->get_var("SELECT COUNT(*) FROM wp_{$ids}terms");
            $terms = (!isset($terms) || empty($terms)) ? '0' : $terms;

            //Blog links
            $links = $wpdb->get_var("SELECT COUNT(*) FROM wp_{$ids}links");
            $links = (!isset($links) || empty($links)) ? '0' : $links;

            //Recording site stats
            $site_stats[$id] = [
                'url' => $url, 
                'users' => $users, 
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
                'comments' => $comments,
                'terms' => $terms,
                'links' => $links
            ];
        }

        //$blog_users = get_users( 'blog_id=2&orderby=nicename' );
        
        $blog_count = get_blog_count(); //The number of active sites on your installation

        $stats = array( 'blog_count' => $blog_count, 'sites' => $site_stats);

        /*if (empty($stats)) {
            return new WP_Error( 'awesome_no_author', 'Invalid author', array( 'status' => 404 ) );
        }*/

        return $stats;

        /*if (empty($stats)) {
            return null;
        }*/
        //.$posts_stats['publish']
        //return $stats;
        //return str_shuffle('pollo'.$posts_stats->publish);

        //return $sites;
    }
}
