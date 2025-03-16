<?php
/**
 * Shortcodes Class
 *
 * Handles registration and rendering of the solution shortcodes.
 *
 * @package APW_Solutions_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcodes Class
 */
class APW_Solutions_Shortcodes {
    /**
     * Class instance.
     *
     * @var APW_Solutions_Shortcodes
     */
    private static $instance = null;

    /**
     * Main plugin instance.
     *
     * @var APW_Solutions_Plugin
     */
    private $plugin;

    /**
     * Logger instance.
     *
     * @var APW_Solutions_Logger
     */
    private $logger;

    /**
     * Constructor.
     */
    private function __construct() {
        // Get main plugin instance
        $this->plugin = APW_Solutions_Plugin::get_instance();

        // Initialize logger if debugging is enabled
        if ( defined( 'APW_SOLUTIONS_DEBUG' ) && APW_SOLUTIONS_DEBUG && class_exists( 'APW_Solutions_Logger' ) ) {
            $this->logger = APW_Solutions_Logger::get_instance();
            $this->logger->info( 'Shortcodes class initialized' );
        }
    }

    /**
     * Get class instance.
     *
     * @return APW_Solutions_Shortcodes
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize shortcodes.
     */
    public function init() {
        $this->debug( 'Registering shortcodes' );

        // Register shortcodes
        add_shortcode( 'solutions', array( $this, 'solutions_shortcode' ) );
        add_shortcode( 'solutions_category', array( $this, 'solutions_category_shortcode' ) );

        // Initialize AJAX actions for frontend
        add_action( 'wp_ajax_apw_solutions_filter', array( $this, 'ajax_filter_solutions' ) );
        add_action( 'wp_ajax_nopriv_apw_solutions_filter', array( $this, 'ajax_filter_solutions' ) );
    }

    /**
     * Solutions shortcode callback.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function solutions_shortcode( $atts ) {
        $this->debug( 'Processing [solutions] shortcode' );

        // Start output buffering
        ob_start();

        // Get categories
        $categories = $this->plugin->get_solution_categories();

        // Filter out the Uncategorized category
        $filtered_categories = array();
        foreach ( $categories as $category ) {
            if ( $category->slug !== 'uncategorized' ) {
                $filtered_categories[] = $category;
            }
        }
        $categories = $filtered_categories;

        if ( empty( $categories ) ) {
            $this->debug( 'No categories found for solutions', 'WARNING' );
            return '<p class="apw-solutions-error">No solution categories found.</p>';
        }

        // Default to first category
        $default_category = !empty( $categories ) ? $categories[0] : null;

        // Look for "use-case" slug specifically, as this should be initial display
        foreach ( $categories as $category ) {
            if ( $category->slug === 'use-case' ) {
                $default_category = $category;
                break;
            }
        }

        // Get solutions for default category
        $solutions = $default_category ? $this->plugin->get_solutions_by_category( $default_category->term_id ) : array();

        // Add unique identifier for AJAX targeting
        $container_id = 'apw-solutions-container-' . uniqid();

        try {
            // Load template
            $this->plugin->get_template( 'solutions-grid.php', array(
                'categories' => $categories,
                'default_category' => $default_category,
                'solutions' => $solutions,
                'container_id' => $container_id
            ) );
        } catch ( Exception $e ) {
            $this->debug( 'Error loading template: ' . $e->getMessage(), 'ERROR' );
            return '<p class="apw-solutions-error">Error displaying solutions.</p>';
        }

        // Return buffered output
        return ob_get_clean();
    }

    /**
     * Solutions category shortcode callback.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function solutions_category_shortcode( $atts ) {
        // Extract and parse attributes
        $atts = shortcode_atts( array(
            'category' => '',
        ), $atts, 'solutions_category' );

        $this->debug( 'Processing [solutions_category] shortcode for category: ' . $atts['category'] );

        if ( empty( $atts['category'] ) ) {
            $this->debug( 'No category specified for solutions_category shortcode', 'WARNING' );
            return '<p class="apw-solutions-error">No category specified for solutions.</p>';
        }

        // Check if the category is 'uncategorized' and return error if it is
        if ( $atts['category'] === 'uncategorized' || $atts['category'] == get_option('default_category') ) {
            $this->debug( 'Uncategorized category requested in shortcode', 'WARNING' );
            return '<p class="apw-solutions-error">Invalid category specified.</p>';
        }

        // Start output buffering
        ob_start();

        // Get solutions for specified category
        $solutions = $this->plugin->get_solutions_by_category( $atts['category'] );

        if ( empty( $solutions ) ) {
            $this->debug( 'No solutions found for category: ' . $atts['category'], 'WARNING' );
        }

        try {
            // Load template
            $this->plugin->get_template( 'solutions-category.php', array(
                'category' => $atts['category'],
                'category_name' => $this->get_category_name( $atts['category'] ),
                'solutions' => $solutions,
            ) );
        } catch ( Exception $e ) {
            $this->debug( 'Error loading template: ' . $e->getMessage(), 'ERROR' );
            return '<p class="apw-solutions-error">Error displaying solutions.</p>';
        }

        // Return buffered output
        return ob_get_clean();
    }

    /**
     * AJAX handler for filtering solutions by category.
     */
    public function ajax_filter_solutions() {
        $this->debug( 'Processing AJAX request for solutions filtering' );

        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'apw_solutions_ajax_nonce' ) ) {
            $this->debug( 'AJAX nonce verification failed', 'ERROR' );
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Get category ID
        $category_id = isset( $_POST['category'] ) ? intval( $_POST['category'] ) : 0;

        if ( ! $category_id ) {
            $this->debug( 'No category ID provided in AJAX request', 'ERROR' );
            wp_send_json_error( array( 'message' => 'No category selected' ) );
        }

        // Check if the category is 'uncategorized' and return error if it is
        $category = get_term( $category_id, 'category' );
        if ( $category && $category->slug === 'uncategorized' ) {
            $this->debug( 'Uncategorized category requested, returning error', 'WARNING' );
            wp_send_json_error( array( 'message' => 'Invalid category' ) );
        }

        // Get solutions for category
        $solutions = $this->plugin->get_solutions_by_category( $category_id );

        // Get category name
        $category_name = $this->get_category_name( $category_id );

        // Start output buffering to capture template
        ob_start();

        try {
            // Load solutions grid template (partial for AJAX)
            $this->plugin->get_template( 'solutions-grid-items.php', array(
                'solutions' => $solutions,
                'category_name' => $category_name
            ) );

            $html = ob_get_clean();

            // Send success response
            wp_send_json_success( array(
                'html' => $html,
                'count' => count( $solutions ),
                'category_name' => $category_name
            ) );
        } catch ( Exception $e ) {
            ob_end_clean();
            $this->debug( 'Error loading template for AJAX: ' . $e->getMessage(), 'ERROR' );
            wp_send_json_error( array( 'message' => 'Error loading solutions' ) );
        }
    }

    /**
     * Get category name from ID or slug.
     *
     * @param int|string $category Category ID or slug.
     * @return string Category name.
     */
    private function get_category_name( $category ) {
        if ( is_numeric( $category ) ) {
            $term = get_term( $category, 'category' );
        } else {
            $term = get_term_by( 'slug', $category, 'category' );
        }

        return $term && ! is_wp_error( $term ) && $term->slug !== 'uncategorized' ? $term->name : '';
    }

    /**
     * Debug helper.
     *
     * @param string $message Message to log.
     * @param string $type    Log type.
     * @param array  $context Additional context.
     */
    private function debug( $message, $type = 'DEBUG', $context = array() ) {
        if ( $this->logger ) {
            switch ( strtoupper( $type ) ) {
                case 'ERROR':
                    $this->logger->error( $message, $context );
                    break;
                case 'WARNING':
                    $this->logger->warning( $message, $context );
                    break;
                case 'INFO':
                    $this->logger->info( $message, $context );
                    break;
                case 'DEBUG':
                default:
                    $this->logger->debug( $message, $context );
                    break;
            }
        }
    }
}