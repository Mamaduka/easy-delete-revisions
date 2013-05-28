<?php
/**
 * Plugin Name: Easy Delete Revisions
 * Plugin URI: http://mamaduka.wordpress.com/easy-delete-revisions
 * Description: Delete all post revisions with a single click.
 * Version: 0.1
 * Author: George Mamadashvili
 * Author URI: http://mamaduka.wordpress.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Easy_Delete_Revisions {

	public $capability;

	public $action;

	private static $instance;

	/**
	 * Main Easy Delete Revision Instance.
	 *
	 * @since 0.1
	 * 
	 * @return Easy Delete Revision object
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Registers callback for init action.
	 *
	 * @since 0.1
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin.
	 *
	 * Registers default actions and sets required properties.
	 * 
	 * @since 0.1
	 */
	public function init() {
		$this->capability = 'manage_options';
		$this->action = 'edr_delete_revision';

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_post_' . $this->action, array( $this, 'delete_revisions' ) );
	}

	/**
	 * Register menu for our plugin.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {
		add_options_page( __( 'Easy Delete Revisions', 'edr' ), __( 'Delete Revisions', 'edr' ), $this->capability, 'easy-delete-revisions', array( $this, 'options_page' ) );
	}

	/**
	 * Render options page.
	 *
	 * @since 0.1
	 */
	public function options_page() {
		$revisions = wp_count_posts( 'revision' )->inherit;
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'Easy Delete Revisions', 'edr' ); ?></h2>
			<?php $this->display_message(); ?>

			<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<?php echo '<input type="hidden" name="action" value="' . esc_attr( $this->action ) . '" />'; ?>
				<?php wp_nonce_field( $this->action, $this->action . '_nonce' ); ?>

				<?php
					if ( $revisions ) {
						echo '<p>' . sprintf( _n( 'There is %d new post revisions to delete.', 'There are %d new post revisions to delete.', $revisions, 'edr' ), $revisions ) . '</p>';
					} else {
						echo '<p>' . __( 'No revisions.', 'edr' ) . '</p>';
					}
				?>

				<?php submit_button( __( 'Delete Revisions', 'edr' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display revision delete message.
	 *
	 * @since 0.1
	 */
	public function display_message() {
		if ( isset( $_GET['revisions-deleted'] ) )
			echo '<div class="updated"><p><strong>' . __( 'Revisions were deleted.', 'edr' ) . '</strong></p></div>';
	}

	/**
	 * Delete all post revisions and related meta-data.
	 * 
	 * @since 0.1
	 */
	public function delete_revisions() {
		if ( ! current_user_can( $this->capability ) )
			wp_die( __( 'Cheatin&#8217; uh?', 'edr' ) );

		check_admin_referer( $this->action, $this->action . '_nonce' );

		// Get revisions
		$revisions = get_posts( array(
			'post_type' => 'revision',
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'cache_results' => false
		) );

		if ( $revisions ) {
			foreach ( $revisions as $rev_id ) {
				wp_delete_post_revision( absint( $rev_id ) );
			}
		}

		$goback = add_query_arg( 'revisions-deleted', 'true', wp_get_referer() );
		wp_safe_redirect( $goback );
		exit;
	}
}

Easy_Delete_Revisions::get_instance();