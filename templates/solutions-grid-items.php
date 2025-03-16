<?php
/**
 * Template for AJAX solution items
 *
 * @package APW_Solutions_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<?php if ( ! empty( $solutions ) ) : ?>
    <div class="row">
        <?php
        // Use the helper function to render each solution card
        foreach ( $solutions as $solution ) {
            $plugin->render_solution_card( $solution );
        }
        ?>
    </div>
<?php else : ?>
    <p class="apw-solutions-empty">No solutions found.</p>
<?php endif; ?>