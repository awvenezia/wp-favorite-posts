<?php
/**
 * Version: 1.7.5
 * Author: Alto-Palo
 * Author URI: https://github.com/awvenezia
 * 
 * @package NlsnWPFP
 */

$nlsn_options = get_option( 'nlsn_options' );
if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'submit_nonce' ) && isset( $_POST['submit'] ) ) {
	if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
		die();
	}

	if ( isset( $_REQUEST['show_remove_link'] ) && 'show_remove_link' === $_REQUEST['show_remove_link'] ) {
		$_REQUEST['added'] = 'show remove link';
	}

	if ( isset( $_REQUEST['show_add_link'] ) && 'show_add_link' === $_REQUEST['show_add_link'] ) {
		$_REQUEST['removed'] = 'show add link';
	}

	$nlsn_options['add_favorite']         = isset( $_REQUEST['add_favorite'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['add_favorite'] ) ) : null;
	$nlsn_options['added']                = isset( $_REQUEST['added'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['added'] ) ) : null;
	$nlsn_options['remove_favorite']      = isset( $_REQUEST['remove_favorite'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['remove_favorite'] ) ) : null;
	$nlsn_options['removed']              = isset( $_REQUEST['removed'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['removed'] ) ) : null;
	$nlsn_options['clear']                = isset( $_REQUEST['clear'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['clear'] ) ) : null;
	$nlsn_options['cleared']              = isset( $_REQUEST['cleared'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['cleared'] ) ) : null;
	$nlsn_options['favorites_empty']      = isset( $_REQUEST['favorites_empty'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['favorites_empty'] ) ) : null;
	$nlsn_options['rem']                  = isset( $_REQUEST['rem'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['rem'] ) ) : null;
	$nlsn_options['cookie_warning']       = isset( $_REQUEST['cookie_warning'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['cookie_warning'] ) ) : null;
	$nlsn_options['text_only_registered'] = isset( $_REQUEST['text_only_registered'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['text_only_registered'] ) ) : null;
	$nlsn_options['statistics']           = isset( $_REQUEST['statistics'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['statistics'] ) ) : null;
	$nlsn_options['before_image']         = isset( $_REQUEST['before_image'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['before_image'] ) ) : null;
	$nlsn_options['custom_before_image']  = isset( $_REQUEST['custom_before_image'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['custom_before_image'] ) ) : null;
	$nlsn_options['autoshow']             = isset( $_REQUEST['autoshow'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['autoshow'] ) ) : null;
	$nlsn_options['post_per_page']        = isset( $_REQUEST['post_per_page'] ) ? htmlspecialchars( sanitize_text_field( $_REQUEST['post_per_page'] ) ) : null;

	$nlsn_options['dont_load_js_file'] = '';
	if ( isset( $_REQUEST['dont_load_js_file'] ) ) {
		$nlsn_options['dont_load_js_file'] = htmlspecialchars( sanitize_text_field( $_REQUEST['dont_load_js_file'] ) );
	}

	$nlsn_options['dont_load_css_file'] = '';
	if ( isset( $_REQUEST['dont_load_css_file'] ) ) {
		$nlsn_options['dont_load_css_file'] = htmlspecialchars( sanitize_text_field( $_REQUEST['dont_load_css_file'] ) );
	}

	$nlsn_options['opt_only_registered'] = '';
	if ( isset( $_REQUEST['opt_only_registered'] ) ) {
		$nlsn_options['opt_only_registered'] = htmlspecialchars( sanitize_text_field( $_REQUEST['opt_only_registered'] ) );
	}

	update_option( 'nlsn_options', $nlsn_options );
}
$nlsn_message = '';
if ( isset( $_GET['action'] ) ) {
	if ( 'reset-statistics' === $_GET['action'] ) {
		global $wpdb;
		
		$nlsn_message = '<div class="updated below-h2" id="message"><p>';
		if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", 'nlsn_favorites' ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$nlsn_message .= 'All statistic data about wp favorite posts plugin have been <strong>deleted</strong>.';
		} else {
			$nlsn_message .= "Something gone <strong>wrong</strong>. Data couldn't delete. Maybe thre isn't any data to delete?";
		}   
		$nlsn_message .= '</p></div>';
	}
}
?>
<?php if ( ! empty( $_REQUEST ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php esc_html_e( 'Options saved.', 'nielsen' ); ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
<h2><?php esc_html_e( 'WP Favorite Posts Configuration', 'nielsen' ); ?></h2>

<div class="metabox-holder" id="poststuff">
<div class="meta-box-sortables">
<script>
jQuery(document).ready(function($) {
	$('.postbox').children('h3, .handlediv').click(function(){ $(this).siblings('.inside').toggle();});
	$('#nlsn-reset-statistics').click(function(){
		return confirm('All statistic data will be deleted, are you sure ?');
		});
});
</script>
<?php echo wp_kses_post( $nlsn_message ); ?>
<form action="" method="post">

<?php $nlsn_nonce = wp_create_nonce( 'submit_nonce' ); ?>
<input type="hidden" name="nonce" value="<?= esc_attr( $nlsn_nonce ) ?>" />
<div class="postbox">
	<div title="<?php esc_attr_e( 'Click to open/close', 'nielsen' ); ?>" class="handlediv">
		<br>
	</div>
	<h3 class="hndle"><span><?php esc_html_e( 'Options', 'nielsen' ); ?></span></h3>
	<div class="inside" style="display: block;">

		<table class="form-table">
			<tr>
				<th><?php wp_kses_post( 'Only <strong>registered users</strong> can favorite', 'nielsen' ); ?></th>
				<td><input type="checkbox" name="opt_only_registered" value="1" <?= ( '1' === stripslashes( $nlsn_options['opt_only_registered'] ) ? "checked='checked'" : '' ) ?>/></td>
			</tr>

			<tr>
				<th><?php esc_html_e( 'Auto show favorite link', 'nielsen' ); ?></th>
				<td>
					<select name="autoshow">
						<option value="custom" 
						<?php 
						if ( 'custom' === $nlsn_options['autoshow'] ) {
							echo "selected='selected'";} 
						?>
						>Custom</option>
						<option value="after" 
						<?php 
						if ( 'after' === $nlsn_options['autoshow'] ) {
							echo "selected='selected'";} 
						?>
						>After post</option>
						<option value="before" 
						<?php 
						if ( 'before' === $nlsn_options['autoshow'] ) {
							echo "selected='selected'";} 
						?>
						>Before post</option>
					</select>
					(Custom: insert <strong>&lt;?php nlsn_link() ?&gt;</strong> wherever you want to show favorite link)
				</td>
			</tr>

			<tr>
				<th><?php esc_html_e( 'Before Link Image', 'nielsen' ); ?></th>
				<td>
					<p>
					<?php
					$nlsn_images[] = 'star.png';
					$nlsn_images[] = 'heart.png';
					$nlsn_images[] = 'bullet_star.png';
					foreach ( $nlsn_images as $nlsn_img ) :
						?>
					<label for="<?php echo esc_attr( $nlsn_img ); ?>">
						<input type="radio" name="before_image" id="<?php echo esc_attr( $nlsn_img ); ?>" value="<?php echo esc_attr( $nlsn_img ); ?>" <?= $nlsn_options['before_image'] === $nlsn_img ? "checked='checked'" : '' ?>/>
						<img src="<?php echo esc_url( NLSN_PATH . '/img/' . $nlsn_img ); ?>" alt="<?php echo esc_attr( $nlsn_img ); ?>" title="<?php echo esc_attr( $nlsn_img ); ?>" class="nlsn-img" />
					</label><br/>
						<?php
					endforeach;
					?>
					<label for="custom">
						<input type="radio" name="before_image" id="custom" value="custom" <?= ( 'custom' === $nlsn_options['before_image'] ) ? "checked='checked'" : '' ?> />
						Custom Image URL :
					</label>
					<input type="custom_before_image" name="custom_before_image" value="<?php echo esc_attr( stripslashes( $nlsn_options['custom_before_image'] ) ); ?>" />
					<br />
					<label for="none">
						<input type="radio" name="before_image" id="none" value="" <?= ( '' === $nlsn_options['before_image'] ) ? "checked='checked'" : '' ?> />
						No Image
					</label>
				</td>
			</tr>

			<tr>
				<th><?php esc_html_e( 'Favorite post per page', 'nielsen' ); ?></th>
				<td>
					<input type="text" name="post_per_page" size="2" value="<?php echo esc_attr( stripslashes( $nlsn_options['post_per_page'] ) ); ?>" /> * This only works with default favorite post list page (nlsn-page-template.php).
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Most favorited posts statistics', 'nielsen' ); ?>*</th>
				<td>
					<label for="stats-enabled"><input type="radio" name="statistics" id="stats-enabled" value="1" <?= $nlsn_options['statistics'] ? "checked='checked'" : '' ?> />Enabled</label>
					<label for="stats-disabled"><input type="radio" name="statistics" id="stats-disabled" value="0" <?= ! $nlsn_options['statistics'] ? "checked='checked'" : '' ?> /> Disabled</label>
				</td>
			</tr>
			<tr><td></td>
				<td>
					<div class="submitbox">
						<div id="delete-action">
						<a href="?page=wp-favorite-posts&amp;action=reset-statistics" id="nlsn-reset-statistics" class="submitdelete deletion">Reset Statistic Data</a>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<p>* If statistics enabled plugin will count how much a post added to favorites.<br />
						You can show this statistics with <a href="widgets.php" title="Go to widgets">"Most Favorited Posts" widget</a>.</p>
				</td>
			</tr>

			<tr>
				<th></th>
				<td>
					<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Update options &raquo;', 'nielsen' ); ?>" />
				</td>
			</tr>
		</table>

	</div>
</div>

<div class="postbox">
	<div title="" class="handlediv"><br/></div>
	<h3 class="hndle"><span><?php esc_html_e( 'Label Settings', 'nielsen' ); ?></span></h3>
	<div class="inside" style="display: block;">


		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Text for add link', 'nielsen' ); ?></th>
				<td><input type="text" name="add_favorite" value="<?php echo wp_kses_post( stripslashes( $nlsn_options['add_favorite'] ) ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for added', 'nielsen' ); ?></th>
				<td>
					<input type="checkbox"  <?= ( 'show remove link' === $nlsn_options['added'] ) ? "checked='checked'" : '' ?> name="show_remove_link" onclick="jQuery('#added').val(''); jQuery('#added').toggle();" value="show_remove_link" id="show_remove_link" />
					<label for="show_remove_link">Show remove link</label><br/>
					<input id="added" type="text" name="added" <?= ( 'show remove link' === $nlsn_options['added'] ) ? "style='display:none;'" : '' ?> value="<?php echo wp_kses_post( stripslashes( $nlsn_options['added'] ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for remove link', 'nielsen' ); ?></th><td><input type="text" name="remove_favorite" value="<?php echo wp_kses_post( stripslashes( $nlsn_options['remove_favorite'] ) ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for removed', 'nielsen' ); ?></th>
				<td>
					<input type="checkbox" <?= ( 'show add link' === $nlsn_options['removed'] ) ? "checked='checked'" : '' ?> name="show_add_link" id="show_add_link" onclick="jQuery('#removed').val(''); jQuery('#removed').toggle();" value='show_add_link' />
					<label for="show_add_link">Show add link</label><br/>
					<input id="removed" type="text" name="removed" <?= ( 'show add link' === $nlsn_options['removed'] ) ? "style='display:none;'" : '' ?> value="<?php echo wp_kses_post( stripslashes( $nlsn_options['removed'] ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for clear link', 'nielsen' ); ?></th><td><input type="text" name="clear" value="<?php echo wp_kses_post( stripslashes( $nlsn_options['clear'] ) ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for cleared', 'nielsen' ); ?></th><td><input type="text" name="cleared" value="<?php echo wp_kses_post( stripslashes( $nlsn_options['cleared'] ) ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for favorites are empty', 'nielsen' ); ?></th><td><input type="text" name="favorites_empty" value="<?php echo wp_kses_post( stripslashes( $nlsn_options['favorites_empty'] ) ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for [remove] link', 'nielsen' ); ?></th><td><input type="text" name="rem" value="<?php echo wp_kses_post( stripslashes( $nlsn_options['rem'] ) ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for favorites saved to cookies', 'nielsen' ); ?></th><td><textarea name="cookie_warning" rows="3" cols="35"><?php echo wp_kses_post( stripslashes( $nlsn_options['cookie_warning'] ) ); ?></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Text for "only registered users can favorite" error message', 'nielsen' ); ?></th><td><textarea name="text_only_registered" rows="2" cols="35"><?php echo wp_kses_post( stripslashes( $nlsn_options['text_only_registered'] ) ); ?></textarea></td>
			</tr>

			<tr>
				<th></th>
				<td>
					<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Update options &raquo;', 'nielsen' ); ?>" />
				</td>
			</tr>

		</table>
	</div>
</div>
<div class="postbox">
	<div title="<?php esc_attr_e( 'Click to open/close', 'nielsen' ); ?>" class="handlediv"><br></div>
	<h3 class="hndle"><span><?php esc_html_e( 'Advanced Settings', 'nielsen' ); ?></span></h3>
	<div class="inside" style="display: block;">
		<table class="form-table">
			<tr>
				<td>
					<input type="checkbox" value="1" <?= ( '1' === $nlsn_options['dont_load_js_file'] ) ? "checked='checked'" : '' ?> name="dont_load_js_file" id="dont_load_js_file" />
					<label for="dont_load_js_file">Don't load js file</label>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" value="1" <?= ( '1' === $nlsn_options['dont_load_css_file'] ) ? "checked='checked'" : '' ?> name="dont_load_css_file" id="dont_load_css_file" />
					<label for="dont_load_css_file">Don't load css file</label>
				</td>
			</tr>			
			<tr>
				<td>
					<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Update options &raquo;', 'nielsen' ); ?>" />
				</td>
			</tr>
		</table>
	</div>
</div>
<div class="postbox">
	<div title="<?php esc_attr_e( 'Click to open/close', 'nielsen' ); ?>" class="handlediv"><br></div>
	<h3 class="hndle"><span><?php esc_html_e( 'Help', 'nielsen' ); ?></span></h3>
	<div class="inside" style="display: block;">
		If you need help about WP Favorite Posts plugin you can go <a href="http://wordpress.org/tags/wp-favorite-posts" target="_blank">plugin's wordpress support page</a>. I or someone else will help you.
	</div>
</div>
</form>
</div>
</div>
