<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// avoid broken avatar icons if anon comments are allowed
function pp_pre_get_avatar( $avatar, $id_or_email, $args ) {

	if ( isset( $id_or_email->user_id ) ) {

		if ( $id_or_email->user_id === '0' ) {

			$default = get_option('avatar_default');

			if ( $default == 'identicon_local' )	{

				$mystery_man = '<img src="' . site_url() . '/wp-content/plugins/buddypress/bp-core/images/Xmystery-man.jpg" class="avatar user-28-avatar avatar-74 photo" width="74" height="74">';

				$avatar = apply_filters( 'pp_local_avatars_anon', $mystery_man );

			}
		}
	}

	return $avatar;
}
add_filter( 'pre_get_avatar', 'pp_pre_get_avatar', 15, 3 );



// settings in wp-admin
function pp_lc_add_settings() {

	$show = get_option('show_avatars');

	if ( $show ) {

		add_filter( 'avatar_defaults', 'pp_lc_add_avatar_default_option', 11, 1 );

		add_filter( 'default_avatar_select', 'pp_lc_add_avatar_default_option_img', 11, 1 );

		$default_avatar = get_option('avatar_default');

		if ( $default_avatar == 'identicon_local' )
			pp_lc_add_settings_section();

	}

}
add_action('admin_init', 'pp_lc_add_settings');

// add an option in Settings > Discussion > Avatars
function pp_lc_add_avatar_default_option( $avatar_defaults ) {

	$avatar_defaults['identicon_local'] =  __('BuddyPress Identicon (Generated and Stored Locally)');

	return $avatar_defaults;
}

// add an icon to the option in Settings > Discussion > Avatars
function pp_lc_add_avatar_default_option_img( $avatar_list ) {

	//var_dump( $avatar_list );

	$str_array = array( //'http://0.gravatar.com/avatar/ffd294ab5833ba14aaf175f9acc71cc4?s=64&amp;d=identicon_local&amp;r=g&amp;forcedefault=1 2x',
	//'http://0.gravatar.com/avatar/ffd294ab5833ba14aaf175f9acc71cc4?s=32&amp;d=identicon_local&amp;r=g&amp;forcedefault=1',
	//'http://1.gravatar.com/avatar/1ea18284b39b7e184779ea1ddc5f4ee2?s=64&amp;d=identicon_local&amp;r=g&amp;forcedefault=1 2x',
	//'http://1.gravatar.com/avatar/1ea18284b39b7e184779ea1ddc5f4ee2?s=32&amp;d=identicon_local&amp;r=G&amp;forcedefault=1',
	//'http://1.gravatar.com/avatar/1ea18284b39b7e184779ea1ddc5f4ee2?s=32&#038;d=identicon_local&#038;f=y&#038;r=g',
	//'http://1.gravatar.com/avatar/1ea18284b39b7e184779ea1ddc5f4ee2?s=64&amp;d=identicon_local&amp;f=y&amp;r=g 2x'
	);

	$icon = plugins_url( 'icon.png', __FILE__ );

	$avatar_list = str_ireplace($str_array, $icon, $avatar_list);

	return $avatar_list;

}

// Add Bulk Generation section to Settings > Discussion > Avatars
function pp_lc_add_settings_section() {
	add_settings_section(
		'generate_avatars',
		'Bulk Generate',
		'pp_lc_generate_avatars_callback',
		'discussion'
	);
}

function pp_lc_generate_avatars_callback( $arg ) {
?>
	<table class="form-table">
	<tr>
	<th scope="row"><?php _e('BuddyPress Avatars'); ?></th>
	<td><fieldset>
		<label for="gen_avatars">
			Generate Identicons and store them locally for all BP Members and Groups without an Avatar. <br/><br/>
			If you have a large number of members without Avatars, <em>this may take too long</em>. <br/><br/>

		    <a href="<?php print wp_nonce_url(admin_url('options-discussion.php?task=bulk-generate'), 'bulk_gen', 'pp_nonce');?>">Generate Identicons</a>

		</label>
	</fieldset></td>
	</tr>
	</table>
<?php
}



/**
 * create class instance
 * maybe bulk generate avatars
 * uses the bp_core_set_avatar_globals hook via bp_setup_globals
 */

function pp_lc_load_class() {
	global $wpdb;

	$default = get_option('avatar_default');

	if ( $default == 'identicon_local' ) {
		$instance = PP_Local_Avatars::get_instance();
	}


	if ( is_admin() ) {

		if ( isset( $_GET['task'] ) && $_GET['task'] == 'bulk-generate' ) {

			if ( ! wp_verify_nonce($_GET['pp_nonce'], 'bulk_gen') ) {

				die( 'Local Avatars - Security Check Fail' );

			} else {

				$users = get_users( array( 'fields' => 'ID' ) );

				foreach ( $users as $user ) {
					$instance->create( $user );
				}


				$group_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}bp_groups" );

				foreach ( $group_ids as $group_id ) {
					$instance->group_create( $group_id );
				}


				wp_redirect( admin_url( '/options-discussion.php?avs_gen=1' ) );
				exit;

			}
		}
	}
}
add_action( 'bp_core_set_avatar_globals', 'pp_lc_load_class', 100 );


function pp_lc_avatars_admin_notice() {

    if ( ! empty( $_GET['avs_gen'] ) ) {
        echo '<div class="updated"><p>Avatars have been generated.</p></div>';
    }
}
add_action('admin_notices', 'pp_lc_avatars_admin_notice');


