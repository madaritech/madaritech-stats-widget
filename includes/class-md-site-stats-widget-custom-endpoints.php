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
class Md_Site_Stats_Widget_Custom_Endpoints {


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
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->log = Md_Site_Stats_Widget_Log_Service::create( 'Md_Site_Stats_Widget_Custom_Endpoints' );
	}

	/**
	 * Register the custom REST route
	 *
	 * @since 1.0.0
	 */
	public function register_route() {
		register_rest_route(
			'md-site-stats-widget/v1',
			'/stats',
			array(
				'methods' => 'GET',
				'callback' => array( &$this, 'statistics' ),
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
	public function statistics( WP_REST_Request $request ) {
		$this->log->trace( 'Retrieving statistics [ request :: ' . var_export( $request, true ) . ' ]...' );

		global $wpdb;
		$blog_count = 0;
		$sites_stats = array();

		if ( is_multisite() ) {
			// Multisite.
			// Get info about all sites of the network.
			$sites = get_sites();

			// Cycle each site to get id and other informations.
			foreach ( $sites as $key => $value ) {
				// Get necessary site data.
				$url        = $value->domain . $value->path;
				$id         = $value->id;
				$prefix     = $wpdb->get_blog_prefix( $id );
				$blog_users = count( get_users( "blog_id=$id" ) );
				$blogname   = get_blog_details( $id )->blogname;
				$blog_count = get_blog_count();

								// Get the blog posts for the site.
				$posts = $this->get_multisite_post_statistics( $id );

				if ( is_wp_error( $posts ) ) {
					$response = rest_ensure_response( $posts );
					$this->log->warn( 'Error in retrieving statistics [ response :: ' . var_export( $response, true ) . ' ]...' );
					return $response;
				}

				// Get the blog comments for the site.
				$comments = $this->get_multisite_comment_statistics( $id );

				if ( is_wp_error( $comments ) ) {
					$response = rest_ensure_response( $comments );
					$this->log->warn( 'Error in retrieving statistics [ response :: ' . var_export( $response, true ) . ' ]...' );
					return $response;
				}

				// Elaborate data in the proper structure.
				$sites_stats[ $id ] = $this->stats_index( $url, $posts, $comments->total_comments, $blog_users, $blogname, $blog_count );
			}
		} else {
			// Single site.
			// Get necessary current site data.
			$id         = get_current_blog_id();
			$url        = site_url();
			$prefix     = $wpdb->get_blog_prefix();
			$blog_users = count_users();
			$blogname   = get_bloginfo( 'name' );
			$blog_count = 1;

			// Get the blog posts for the current site.
			$posts = $this->get_singlesite_post_statistics( $id );
			if ( is_wp_error( $posts ) ) {
				$response = rest_ensure_response( $posts );
				$this->log->warn( 'Error in retrieving statistics [ response :: ' . var_export( $response, true ) . ' ]...' );
				return $response;
			}

			// Get the blog comments for the current site.
			$comments = $this->get_singlesite_comment_statistics( $id );
			if ( is_wp_error( $comments ) ) {
				$response = rest_ensure_response( $err );
				$this->log->warn( 'Error in retrieving statistics [ response :: ' . var_export( $response, true ) . ' ]...' );
				return $response;
			}

			// Elaborate data in the proper structure.
			$sites_stats[ $id ] = $this->stats_index( $url, $posts, $comments->total_comments, $blog_users['total_users'], $blogname, $blog_count );
		}

		// Building final object to send response to client.
		$stats = array(
			'blog_count' => $blog_count,
			'sites' => $sites_stats,
		);
		$response = rest_ensure_response( $stats );

				$this->log->trace( 'Statistics retrieved [ response :: ' . var_export( $response, true ) . ' ]...' );

				return $response;
	}

	/**
	 * Calculates the statistic indexes.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @param string $url The site url.
	 * @param array  $posts The posts statistics.
	 * @param object $comments The comments object returned from wp_count_comments function.
	 * @param int    $blog_users The sites' user number.
	 * @param string $blogname The site name.
	 * @param int    $blog_count The number of sites in the network (1 for single site).
	 */
	private function stats_index( $url, $posts, $comments, $blog_users, $blogname, $blog_count ) {
		// Recording site stats.
		$site_stats = [
			'url' => $url,
			'users' => $blog_users,
			'blogname' => $blogname,
			'posts' => $posts['total'],
			'post_publish' => $posts['publish'],
			'post_future' => $posts['future'],
			'post_draft' => $posts['draft'],
			'post_pending' => $posts['pending'],
			'post_private' => $posts['private'],
			'post_trash' => $posts['trash'],
			'post_auto_draft' => $posts['auto-draft'],
			'post_inherit' => $posts['inherit'],
			'comments' => $comments,
		];

		return $site_stats;
	}

	/**
	 * Retrieve the post statistics in multisite context.
	 *
	 * Use the transient to retrieve the posts statistics for each site of the network using the correct blog id. If transient is unkwown or expired it calculates the statistics, swithcing to the right blog site using the switch_to_blog function. Transient has been choosen to keep cache persistent.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @param int $id The site id.
	 * @return array|Wp_error The posts statistics.
	 */
	private function get_multisite_post_statistics( $id ) {

		// Check for transient. If none, then execute query.
		$posts = get_site_transient( 'post_statistics' . $id );

		if ( false === $posts ) {
			$posts = array();

			// Switch to the blog we need to get posts.
			switch_to_blog( $id );

						$posts = $this->get_posts_statistics_info();

			// Switch back to current blog.
			restore_current_blog();

			if ( ! is_wp_error( $posts ) ) {
				// Put the results in a transient. No expiration time.
				set_site_transient( 'post_statistics' . $id, $posts );
			} else {
				return $err;
			}
		}
		return $posts;
	}

	/**
	 * Retrieve the post statistics in singlesite context.
	 *
	 * Use the transient to retrieve the posts statistics for the current site (the id come from get_current_blog_id function. If transient is unkwown or expired it calculates the statistics, switching to the right blog site using the switch_to_blog function. Transient has been choosen to keep cache persistent.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @param int $id The current site id.
	 * @return array|Wp_error The posts statistics.
	 */
	private function get_singlesite_post_statistics( $id ) {
		// Check for transient. If none, then execute WP_Query.
		$posts = get_transient( 'post_statistics' . $id );

		if ( false === $posts ) {
			$posts = $this->get_posts_statistics_info();

			if ( ! is_wp_error( $posts ) ) {
				// Put the results in a transient. No expiration time.
				set_transient( 'post_statistics' . $id, $posts );
			} else {
				return $err;
			}
		}
		return $posts;
	}

	/**
	 *
	 * Retrieve the transient comments statistics in multisite context.
	 *
	 * Use the transient to retrieve the comments statistics for each site of the network using the correct blog id. If transient is unkwown or expired it calculates the statistics with wp_count_comments function, switching to the right blog site using the switch_to_blog function. Transient has been choosen to keep cache persistent.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @param int $id The site id.
	 * @return array|Wp_error The comments number for site in mulsite network.
	 */
	private function get_multisite_comment_statistics( $id ) {
		// Check for transient. If none, then execute WP_Query.
		$comments = get_site_transient( 'comment_statistics' . $id );

		$this->log->debug( 'Statistics comments [ comments :: ' . var_export( $comments, true ) . ' ]...' );

		if ( false === $comments ) {
			// Switch to the blog we need to get posts.
			switch_to_blog( $id );

				$comments = wp_count_comments();

			// Switch back to current blog.
			restore_current_blog();

			if ( ! empty( $comments ) ) {
				// Put the results in a transient. No expiration time.
				set_site_transient( 'comment_statistics' . $id, $comments );
			} else {
				$err = new WP_Error( 'error', __( 'Retrieving comments failure', 'md_site_stats_widget' ) );
				return $err;
			}
		}
		return $comments;
	}

	/**
	 *
	 * Retrieve the transient comment statistics in singlesite context.
	 *
	 * Use the transient to retrieve the comments statistics for the current site using the correct blog id. If transient is unkwown or expired it calculates the statistics with wp_count_comments function. Transient has been choosen to keep cache persistent.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @param int $id The site id.
	 * @return array|Wp_error The comments number.
	 */
	private function get_singlesite_comment_statistics( $id ) {
		// Check for transient. If none, then execute WP_Query.
		$comments = get_transient( 'comment_statistics' . $id );

		if ( false === $comments ) {
			$comments = wp_count_comments();

			if ( ! empty( $comments ) ) {
				// Put the results in a transient. No expiration time.
				set_transient( 'comment_statistics' . $id, $comments );
			} else {
				$err = new WP_Error( 'error', __( 'Retrieving comments failure', 'md_site_stats_widget' ) );
				return $err;
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
	private function refresh_statistics( $key ) {
		$id = get_current_blog_id();
		if ( is_multisite() ) {
			delete_site_transient( $key . $id );
		} else {
			delete_transient( $key . $id );
		}
	}

	/**
	 * Triggers the posts statistics refresh on insertion of new post (hook).
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @param int $post_id The id of the post inserted.
	 */
	public function refresh_post_statistics( $post_id ) {
		$this->refresh_statistics( 'post_statistics' );
	}

	/**
	 * Triggers the comments statistics refresh on insertion of new comment (hook).
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	public function refresh_comment_statistics() {
		$this->refresh_statistics( 'comment_statistics' );
	}

	/**
	 *
	 * Retrieves the posts information with WP_Query.
	 *
	 * WP_Query is used to get all post of interest; the manipulation of post is made via php: we cout the different types of posts. The result is limited to 100. This can be set for optimization reasons. Using WP_Query is less expensive than using only one query wpdb; WP_Query is also configured to be more performant.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @return WP_Error|array The posts statistics
	 */
	private function get_posts_statistics_info() {
		$args = array(
			'post_type' => 'post',
			'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit' ),
			'posts_per_page' => 100,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows' => true,
		);

				$wpq_posts = new WP_Query( $args );

		if ( empty( $wpq_posts ) ) {
			$err = new WP_Error( 'error', __( 'Retrieving posts failure', 'md_site_stats_widget' ) );
			return $err;
		}

		$posts['total']      = $wpq_posts->post_count;
		$posts['publish']    = 0;
		$posts['future']     = 0;
		$posts['draft']      = 0;
		$posts['pending']    = 0;
		$posts['private']    = 0;
		$posts['trash']      = 0;
		$posts['auto-draft'] = 0;
		$posts['inherit']    = 0;

		while ( $wpq_posts->have_posts() ) {
			$wpq_posts->next_post();

			switch ( $wpq_posts->post->post_status ) {
				case 'publish':
					$posts['publish'] += 1;
					break;
				case 'future':
					$posts[''] += 1;
					break;
				case 'draft':
					$posts['future'] += 1;
					break;
				case 'pending':
					$posts['pending'] += 1;
					break;
				case 'private':
					$posts['private'] += 1;
					break;
				case 'trash':
					$posts['trash'] += 1;
					break;
				case 'auto-draft':
					$posts['auto-draft'] += 1;
					break;
				case 'inherit':
					$posts['inherit'] += 1;
					break;
				default:
					break;
			}
		}

		// Reset Query & Post Data.
		wp_reset_query();
		wp_reset_postdata();

		return $posts;
	}
}
