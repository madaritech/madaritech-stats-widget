<?php
/**
 * Services: Log Service.
 *
 * A service which provides functions for logging.
 *
 * @link       http://www.madaritech.com
 * @since      1.0.0
 *
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 */

/**
 * Define the {@link Md_Site_Stats_Widget_Log_Service} class.
 *
 * @since      1.0.0
 * @package    Md_Site_Stats_Widget
 * @subpackage Md_Site_Stats_Widget/includes
 * @author     Madaritech <freelance@madaritech.com>
 */
class Md_Site_Stats_Widget_Log_Service
{

    /**
     * The class name source of logging events.
     *
     * @since  1.0.0
     * @access private
     * @var    string $class_name The class name source of logging events.
     */
    private $class_name;

    /**
     * Create a {@link Md_Site_Stats_Widget_Log_Service} instance.
     *
     * @since 1.0.0
     *
     * @param string $class_name The class name for the logger.
     */
    public function __construct($class_name)
    {
        $this->class_name = $class_name;
    }

    /**
     * Check whether debugging is enabled.
     *
     * @since 1.0.0
     *
     * @return bool True if debugging is enabled otherwise false.
     */
    public static function is_enabled()
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Create a {@link Md_Site_Stats_Widget_Log_Service} instance with the provided class name.
     *
     * @since 1.0.0
     *
     * @param string $class_name The class name source of logging events.
     *
     * @return \Md_Site_Stats_Widget_Log_Service A {@link Md_Site_Stats_Widget_Log_Service} instance.
     */
    public static function create($class_name)
    {
        return new Md_Site_Stats_Widget_Log_Service($class_name);
    }

    /**
     * Debug log.
     *
     * @since 1.0.0
     *
     * @param string $message The message to log.
     */
    public function debug($message)
    {
        $this->log('DEBUG', $message);
    }

    /**
     * Information log.
     *
     * @since 1.0.0
     *
     * @param string $message The message to log.
     */
    public function info($message)
    {
        $this->log('INFO', $message);
    }

    /**
     * Warning log.
     *
     * @since 1.0.0
     *
     * @param string $message The message to log.
     */
    public function warn($message)
    {
        $this->log('WARN', $message);
    }

    /**
     * Trace log.
     *
     * @since 1.0.0
     *
     * @param string $message The message to log.
     */
    public function trace($message)
    {
        $this->log('TRACE', $message);
    }

    /**
     * Internal log function.
     *
     * @since 1.0.0
     *
     * @param string $level   The debug level.
     * @param string $message The message.
     * @param bool $override True if the logger print the message without check if the debug is enabled on wp-config.
     */
    private function log($level, $message, $override=false)
    {
        if ($override) {
            error_log(sprintf('%-6s [%-40s] %s', $level, $this->class_name, $message));
        } elseif ($this->is_enabled()) {
            error_log(sprintf('%-6s [%-40s] %s', $level, $this->class_name, $message));
        }
    }
}
