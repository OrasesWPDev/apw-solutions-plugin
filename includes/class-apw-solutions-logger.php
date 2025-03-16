<?php
/**
 * Logger Class
 *
 * Handles debug logging for the plugin.
 * Creates log directory dynamically only when debugging is enabled.
 *
 * @package APW_Solutions_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logger Class
 */
class APW_Solutions_Logger {
    /**
     * Class instance.
     *
     * @var APW_Solutions_Logger
     */
    private static $instance = null;

    /**
     * Debug enabled flag.
     *
     * @var bool
     */
    private $debug_enabled;

    /**
     * Log directory path.
     *
     * @var string
     */
    private $log_dir;

    /**
     * Constructor.
     */
    private function __construct() {
        // Check if debugging is enabled globally
        $this->debug_enabled = defined('APW_SOLUTIONS_DEBUG') && APW_SOLUTIONS_DEBUG;

        // Set log directory path
        $this->log_dir = APW_SOLUTIONS_PLUGIN_DIR . 'logs/';

        // Create log directory if debugging is enabled
        if ($this->debug_enabled && !file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);

            // Add protection files
            file_put_contents($this->log_dir . '.htaccess', 'Deny from all');
            file_put_contents($this->log_dir . 'index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Get class instance.
     *
     * @return APW_Solutions_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log message.
     *
     * @param string $message Message to log.
     * @param string $type    Log type.
     * @param array  $context Additional context.
     * @return bool|int
     */
    public function log($message, $type = 'info', $context = array()) {
        // Early return if debugging is disabled
        if (!$this->debug_enabled) {
            return false;
        }

        // Ensure log directory exists
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);

            // Add protection files if they don't exist
            if (!file_exists($this->log_dir . '.htaccess')) {
                file_put_contents($this->log_dir . '.htaccess', 'Deny from all');
            }
            if (!file_exists($this->log_dir . 'index.php')) {
                file_put_contents($this->log_dir . 'index.php', '<?php // Silence is golden');
            }
        }

        // Log implementation with timestamp, type, and context
        $timestamp = current_time('Y-m-d H:i:s');
        $context_string = !empty($context) ? ' Context: ' . json_encode($context) : '';
        $log_entry = "[{$timestamp}] [{$type}] {$message}{$context_string}\n";

        $filename = $this->log_dir . 'debug-' . current_time('Y-m-d') . '.log';
        return file_put_contents($filename, $log_entry, FILE_APPEND);
    }

    /**
     * Log info message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool|int
     */
    public function info($message, $context = []) {
        return $this->log($message, 'INFO', $context);
    }

    /**
     * Log error message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool|int
     */
    public function error($message, $context = []) {
        return $this->log($message, 'ERROR', $context);
    }

    /**
     * Log warning message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool|int
     */
    public function warning($message, $context = []) {
        return $this->log($message, 'WARNING', $context);
    }

    /**
     * Log debug message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool|int
     */
    public function debug($message, $context = []) {
        return $this->log($message, 'DEBUG', $context);
    }
}