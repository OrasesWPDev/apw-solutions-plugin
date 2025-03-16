<?php
/**
 * Plugin Name: APW Solutions Plugin
 * Plugin URI:
 * Description: Creates shortcodes to display Solutions CPT in a grid layout with filtering options.
 * Version: 1.0.0
 * Author:
 * Author URI:
 * Text Domain: apw-solutions
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'APW_SOLUTIONS_VERSION', '1.0.0' );
define( 'APW_SOLUTIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APW_SOLUTIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APW_SOLUTIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Debug Mode Configuration
 *
 * - Set to true during development to enable detailed logging
 * - Set to false in production for optimal performance
 * - When false, no log files will be created or written to
 */
define( 'APW_SOLUTIONS_DEBUG', true ); // Set to false for production

// Enqueue all CSS and JS with cache busting
function apw_solutions_enqueue_assets() {
    // CSS - scan and enqueue all files in css directory
    $css_dir = APW_SOLUTIONS_PLUGIN_DIR . 'assets/css/';
    if (is_dir($css_dir)) {
        $css_files = glob($css_dir . '*.css');
        foreach ($css_files as $css_file) {
            $file_name = basename($css_file, '.css');
            $css_version = filemtime($css_file);
            wp_enqueue_style(
                'apw-solutions-' . $file_name,
                APW_SOLUTIONS_PLUGIN_URL . 'assets/css/' . basename($css_file),
                array(),
                $css_version
            );
        }
    }

    // JavaScript - scan and enqueue all files in js directory
    $js_dir = APW_SOLUTIONS_PLUGIN_DIR . 'assets/js/';
    if (is_dir($js_dir)) {
        $js_files = glob($js_dir . '*.js');
        foreach ($js_files as $js_file) {
            $file_name = basename($js_file, '.js');
            $js_version = filemtime($js_file);
            wp_enqueue_script(
                'apw-solutions-' . $file_name,
                APW_SOLUTIONS_PLUGIN_URL . 'assets/js/' . basename($js_file),
                array('jquery'),
                $js_version,
                true
            );
        }
    }

    // Add AJAX URL and debug configuration for JavaScript to use
    if (wp_script_is('apw-solutions-apw-solutions', 'enqueued')) {
        wp_localize_script(
            'apw-solutions-apw-solutions',  // Main script handle based on filename
            'apw_solutions_config',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('apw_solutions_ajax_nonce'),
                'debug'    => defined('APW_SOLUTIONS_DEBUG') && APW_SOLUTIONS_DEBUG
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'apw_solutions_enqueue_assets');

// Auto-include all PHP files in the includes directory
function apw_solutions_autoload_files() {
    $logger = null;

    // If debug is enabled, initialize the logger
    if ( defined( 'APW_SOLUTIONS_DEBUG' ) && APW_SOLUTIONS_DEBUG ) {
        require_once APW_SOLUTIONS_PLUGIN_DIR . 'includes/class-apw-solutions-logger.php';
        $logger = APW_Solutions_Logger::get_instance();
        $logger->info( 'Plugin initialization started' );
    }

    $includes_dir = APW_SOLUTIONS_PLUGIN_DIR . 'includes/';
    if ( is_dir( $includes_dir ) ) {
        $files = scandir( $includes_dir );

        // Sort files to ensure core files load first
        usort( $files, function( $a, $b ) {
            // Load main plugin class first
            if ( $a === 'class-apw-solutions-plugin.php' ) return -1;
            if ( $b === 'class-apw-solutions-plugin.php' ) return 1;

            // Load logger second (already loaded above)
            if ( $a === 'class-apw-solutions-logger.php' ) return -1;
            if ( $b === 'class-apw-solutions-logger.php' ) return 1;

            return strcmp( $a, $b );
        });

        foreach ( $files as $file ) {
            if ( preg_match( '/\.php$/', $file ) && $file !== 'class-apw-solutions-logger.php' ) {
                $full_path = $includes_dir . $file;
                if ( file_exists( $full_path ) ) {
                    require_once $full_path;
                    if ( $logger ) {
                        $logger->debug( 'Loaded file: ' . $file );
                    }
                }
            }
        }
    }
}

// Initialize everything
function apw_solutions_init() {
    apw_solutions_autoload_files();

    // Initialize main plugin class
    if ( class_exists( 'APW_Solutions_Plugin' ) ) {
        $plugin = APW_Solutions_Plugin::get_instance();
        $plugin->init();
    }
}
add_action( 'plugins_loaded', 'apw_solutions_init' );

// Add security index.php files on activation
register_activation_hook( __FILE__, 'apw_solutions_activate' );
function apw_solutions_activate() {
    // Add index.php files to existing directories for security
    $directories = array(
        '',  // Root directory
        'includes',
        'assets',
        'assets/css',
        'assets/js',
        'templates'
    );

    foreach ( $directories as $dir ) {
        $path = plugin_dir_path( __FILE__ ) . $dir;
        // Add index.php for security if it doesn't exist
        $index_file = trailingslashit($path) . 'index.php';
        if ( ! file_exists( $index_file ) ) {
            file_put_contents( $index_file, '<?php // Silence is golden' );
        }
    }
}

// Deactivation hook - clean up if needed
register_deactivation_hook(__FILE__, 'apw_solutions_deactivate');
function apw_solutions_deactivate() {
    // Clean up tasks when the plugin is deactivated
    delete_transient('apw_solutions_categories_cache');
}