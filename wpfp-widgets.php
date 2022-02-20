<?php
/**
 * Version: 1.7.2
 * Author: Alto-Palo
 * Author URI: https://github.com/awvenezia
 * 
 * @package NlsnWPFP
 */

/**
 * Nlsn_widget_init
 *
 * @return void
 */
function nlsn_widget_init() {
		
	/**
	 * Nlsn_widget_view
	 *
	 * @param  mixed $args Arguments.
	 * @return void
	 */
	function nlsn_widget_view( $args ) {
		$before_title  = '';
		$before_widget = '';
		$after_title   = '';
		$after_widget  = '';
		$limit         = '';
		if ( ! empty( $args ) ) {
			foreach ( $args as $key => $val ) {
				${$key} = $val;
			}
		}
		$options = nlsn_get_options();
		if ( isset( $options['widget_limit'] ) ) {
			$limit = $options['widget_limit'];
		}
		$title = empty( $options['widget_title'] ) ? 'Most Favorited Posts' : $options['widget_title'];
		echo wp_kses_post( $before_widget );
		echo wp_kses_post( $before_title . $title . $after_title );
		nlsn_list_most_favorited( $limit );
		echo wp_kses_post( $after_widget );
	}
	
	/**
	 * Nlsn_widget_control
	 *
	 * @return void
	 */
	function nlsn_widget_control() {
		$options = nlsn_get_options();
		if ( ! wp_verify_nonce( 'nlsn-wpfp-widget-submit', 'nlsn-widget-submit' ) ) {
			return;
		}
		if ( isset( $_REQUEST['nlsn-widget-submit'] ) && isset( $_REQUEST['nlsn-title'] ) && isset( $_REQUEST['nlsn-limit'] ) ) :
			$options['widget_title'] = wp_strip_all_tags( stripslashes( sanitize_title( $_REQUEST['nlsn-title'] ) ) );
			$options['widget_limit'] = wp_strip_all_tags( stripslashes( sanitize_key( $_REQUEST['nlsn-limit'] ) ) );
			update_option( 'nlsn_options', $options );
		endif;
		$title = $options['widget_title'];
		$limit = $options['widget_limit'];
		?>
		<p>
			<label for="nlsn-title">
				<?php esc_html_e( 'Title:', 'nielsen' ); ?> <input type="text" value="<?php echo esc_attr( $title ); ?>" class="widefat" id="nlsn-title" name="nlsn-title" />
			</label>
		</p>
		<p>
			<label for="nlsn-limit">
				<?php esc_html_e( 'Number of posts to show:', 'nielsen' ); ?> <input type="text" value="<?php echo esc_attr( $limit ); ?>" style="width: 28px; text-align:center;" id="nlsn-limit" name="nlsn-limit" />
			</label>
		</p>
		<?php if ( ! $options['statistics'] ) { ?>
			<p>
				You must enable statistics from favorite posts <a href="plugins.php?page=wp-favorite-posts" title="Favorite Posts Configuration">configuration page</a>.
			</p>
			<?php } ?>
			<input type="hidden" name="nlsn-widget-submit" value="1" />
			<input type="hidden" name="nlsn-widget-nonce" value="<?= sanitize_key( wp_create_nonce( 'nlsn-wpfp-widget-submit' ) ); ?>
			<?php
	}
	wp_register_sidebar_widget( 'nlsn-most_favorited_posts', 'Most Favorited Posts', 'nlsn_widget_view' );
	wp_register_widget_control( 'nlsn-most_favorited_posts', 'Most Favorited Posts', 'nlsn_widget_control' );
	
	/**
	 * Nlsn_users_favorites_widget_view
	 *
	 * @param  mixed $args Arguments.
	 * @return void
	 */
	function nlsn_users_favorites_widget_view( $args ) {
		$before_title  = '';
		$before_widget = '';
		$after_title   = '';
		$after_widget  = '';
		if ( ! empty( $args ) ) {
			foreach ( $args as $key => $val ) {
				${$key} = $val;
			}
		}
		$options = nlsn_get_options();
		$title   = empty( $options['uf_widget_title'] ) ? 'Users Favorites' : $options['uf_widget_title'];
		echo wp_kses_post( $before_widget );
		echo wp_kses_post( $before_title . $title . $after_title );
		
		echo '<div class="user-favorite-list">Loading... </div>';
		echo wp_kses_post( $after_widget );
	}
	
	/**
	 * Nlsn_users_favorites_widget_control
	 *
	 * @return void
	 */
	function nlsn_users_favorites_widget_control() {
		$options = nlsn_get_options();
		if ( ! wp_verify_nonce( 'nlsn-wpfp-uf-widget-submit', 'nlsn-uf-widget-submit' ) ) {
			return;
		}
		if ( isset( $_REQUEST['nlsn-uf-widget-submit'] ) && isset( $_REQUEST['nlsn-uf-title'] ) && isset( $_REQUEST['nlsn-uf-limit'] ) ) :
			$options['uf_widget_title'] = wp_strip_all_tags( stripslashes( sanitize_title( $_REQUEST['nlsn-uf-title'] ) ) );
			$options['uf_widget_limit'] = wp_strip_all_tags( stripslashes( sanitize_key( $_REQUEST['nlsn-uf-limit'] ) ) );
			update_option( 'nlsn_options', $options );
		endif;
		$uf_title = $options['uf_widget_title'];
		$uf_limit = $options['uf_widget_limit'];
		?>
		<p>
			<label for="nlsn-uf-title">
				<?php esc_html_e( 'Title:', 'nielsen' ); ?> <input type="text" value="<?php echo esc_attr( $uf_title ); ?>" class="widefat" id="nlsn-uf-title" name="nlsn-uf-title" />
			</label>
		</p>
		<p>
			<label for="nlsn-uf-limit">
				<?php esc_html_e( 'Number of posts to show:', 'nielsen' ); ?> <input type="text" value="<?php echo esc_attr( $uf_limit ); ?>" style="width: 28px; text-align:center;" id="nlsn-uf-limit" name="nlsn-uf-limit" />
			</label>
		</p>

		<input type="hidden" name="nlsn-uf-widget-submit" value="1" />
		<input type="hidden" name="nlsn-widget-nonce" value="<?= sanitize_key( wp_create_nonce( 'nlsn-wpfp-uf-widget-submit' ) ); ?>
		<?php
	}
	wp_register_sidebar_widget( 'nlsn-users_favorites', 'User\'s Favorites', 'nlsn_users_favorites_widget_view' );
	wp_register_widget_control( 'nlsn-users_favorites', 'User\'s Favorites', 'nlsn_users_favorites_widget_control' );
}
add_action( 'widgets_init', 'nlsn_widget_init' );
