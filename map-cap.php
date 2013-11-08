<?php
/*
Plugin Name: Map Cap
Plugin URI: https://github.com/thenbrent/map-cap
Description: Control who can publish, edit and delete custom post types.  Silly name, useful code.
Author: Brent Shepherd
Version: 2.1
Author URI: http://find.brentshepherd.com/
*/

function mc_add_admin_menu() {
	$page_hook_suffix = add_users_page( 'Map Caps', 'Map Cap', 'manage_options', 'mapcap', 'mc_capabilities_settings_page' );
	add_action( "admin_print_scripts-{$page_hook_suffix}", 'mc_plugin_admin_scripts' );
}
add_action( 'admin_menu', 'mc_add_admin_menu' );

/**
 * Registers the CSS file for jQuery UI and the JavaScript file of the plugin
 */
function mc_plugin_admin_init() {
	wp_register_style( 'jquery-ui-smoothness', plugins_url( 'smoothness/jquery-ui.min.css', __FILE__ ), '1.10.3' );
	wp_register_script( 'mc-script', plugins_url( 'map-cap.js', __FILE__ ), array( 'jquery-ui-accordion', 'jquery-ui-tabs' ) );
}
add_action( 'admin_init', 'mc_plugin_admin_init' );

/**
 * Enqueues the already regstered CSS and JavaScript
*/
function mc_plugin_admin_scripts() {
	wp_enqueue_style( 'jquery-ui-smoothness' );
	wp_enqueue_script( 'mc-script' );
}

/** 
 * Site admins may want to allow or disallow users to create, edit and delete custom post type.
 * This function provides an admin menu for selecting which roles can do what with custom posts.
 **/
function mc_capabilities_settings_page() { 
	global $wp_roles;

	$role_names = $wp_roles->get_names();
	$roles = array();

	foreach ( $role_names as $key => $value ) {
		$roles[ $key ] = get_role( $key );
		$roles[ $key ]->display_name = $value;
	}

	$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
	$dont_touch = get_post_types( array( 'capability_type' => 'post' ) );
	$not_mapped = get_post_types( array( 'map_meta_cap' => false ) );

	// Don't edit capabilties for any custom post type with "post" as its capability type
	$post_types = array_diff( $post_types, $dont_touch );
	// For mapping capabilities, post type needs to have map_meta_cap set to true
	$mapped_post_types = array_diff( $post_types, $not_mapped );

?>
<div class="wrap map-cap-settings"> 
	<?php screen_icon(); ?>
	<h2><?php _e( 'Map Capabilities', 'map-cap' ); ?></h2>
<?php

	// Start of the Map Meta Cap settings form
	if ( ! empty( $post_types ) || ! empty( $mapped_post_types ) ) {

?>
	<form id="map-cap-form" method="post" action="">
<?php

		wp_nonce_field( 'mc_capabilities_settings', 'mc_nonce' );
	}

	if ( empty( $mapped_post_types ) ) {

?>		
		<h3><?php _e( 'Map Caps', 'map-cap' ); ?></h3>
		<p><?php _e( 'No custom post types have been registered with a custom capability, public argument set to true and the map_meta_cap argument set to true.', 'map-cap' ); ?></p>
		<p><?php printf( __( 'Try creating custom post types with the %sCustom Post Type UI plugin%s.', 'map-cap' ), '<a href="http://wordpress.org/extend/plugins/custom-post-type-ui/">', '</a>' ); ?></p>
<?php

	}
	else {

?>
		<div id="mc-tabs" class="form-table">
			<ul>	
<?php

		if( ! empty( $post_types ) ) {

?>
				<li><a href="#mc-force-mapping"><?php _e( 'Force Mapping', 'map-cap' ); ?></a></li>
<?php

		}
		foreach ( $mapped_post_types as $post_type ) {
			$post_type_details 	= get_post_type_object( $post_type );

?>
				<li><a href="#tabs-<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( $post_type_details->labels->name ); ?></a></li>
<?php

		}

?>
			</ul>
<?php

		if( ! empty( $post_types ) ) {

?>
			<div id="mc-force-mapping" class="mc-accordion">
				<h3><?php _e( 'Custom Post Types', 'map-cap' ); ?></h3>
				<div class="map-cap">
					<p><?php _e( 'Select a post type below to have Map Cap attempt to map its capabilities.', 'map-cap' ); ?></p>
					<p><?php printf( __( "If this does not work, please read the %splugin's FAQ%s for possible causes.", 'map-cap' ), '<a href="http://wordpress.org/extend/plugins/map-cap/faq/">', '</a>' ); ?></p>
<?php

			foreach( $post_types as $post_type ) {
				$post_type_details 	= get_post_type_object( $post_type );

?>
					<label for="<?php echo $post_type; ?>-map">
						<input type="checkbox" id="<?php echo esc_attr( $post_type ); ?>-map" name="<?php echo esc_attr( $post_type ); ?>-map"<?php checked( $post_type_details->map_meta_cap, 1 ); ?> />
						<?php echo esc_html( $post_type_details->labels->name ); ?>
					</label><br/>
<?php

			}

?>
				</div>
			</div>
<?php

		}				
		foreach( $mapped_post_types as $post_type ) {
			
			$post_type_details 	= get_post_type_object( $post_type );
			$post_type_cap 		= $post_type_details->capability_type;
			$post_type_caps		= $post_type_details->cap;

?>
			<div id="tabs-<?php echo esc_attr( $post_type ); ?>" class="mc-accordion">
<?php

			$post_type_arr_map = array(
				'publish'     => array( 'publish_posts', __( 'Publish %s', 'map-cap' ) ),           // Allow publish
				'edit'        => array( 'edit_published_posts', __( 'Edit Own %s', 'map-cap' ) ),   // Allow editing own posts
				'edit-others' => array( 'edit_others_posts', __( "Edit Others' %s", 'map-cap' ) ),  // Allow editing others posts
				'private'     => array( 'read_private_posts', __( 'View Private %s', 'map-cap' ) ), // Allow reading private posts
			);
			foreach ( $post_type_arr_map as $key => $value ) {
				$capability = $value[0];

?>
				<h3><?php printf( $value[1], esc_html( $post_type_details->labels->name ) ); ?></h3>
				<div class="map-cap">
<?php

				foreach ( $roles as $role ) {
					$id = esc_attr( $post_type . '-' . $role->name . '-' . $key );

?>
					<label for="<?php echo $id; ?>">
						<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $id; ?>" <?php checked( isset( $role->capabilities[ $post_type_caps->$capability ] ), 1 ); ?>/>
						<?php echo esc_html( $role->display_name ); ?>
					</label><br/>
<?php

				}

?>
				</div>
<?php

			}

?>
			</div>
<?php

		}

?>
		</div>
<?php

	}
	if ( ! empty( $post_types ) || ! empty( $not_mapped ) ) {

?>
		<p class="submit"><?php printf(
			'<input type="submit" name="submit" class="button button-primary" value="%s" />', 
			__( 'Save', 'map-cap' )
		); ?></p>
	</form>
<?php

	}

?>
</div>
<?php

}

/** 
 * Save capabilities settings when the admin page is submitted page by adding the capabilitiy
 * to the appropriate roles.
 **/
function mc_save_capabilities() {
	global $wp_roles;

    if ( ! isset( $_POST['mc_nonce'] ) || ! check_admin_referer( 'mc_capabilities_settings', 'mc_nonce' ) || ! current_user_can( 'manage_options' ) )
		return;

	$role_names = $wp_roles->get_names();
	$roles = array();

	foreach ( $role_names as $key=>$value ) {
		$roles[ $key ] = get_role( $key );
		$roles[ $key ]->display_name = $value;
	}

	$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
	$dont_touch = get_post_types( array( 'capability_type' => 'post' ) );
	$not_mapped = get_post_types( array( 'map_meta_cap' => false ) );

	// Don't edit capabilties for any custom post type with "post" as its capability type
	$post_types = array_diff( $post_types, $dont_touch );

	$force_map_types = get_option( 'mc_force_map_meta_cap', array() );

	foreach( $post_types as $post_type ) {
		// Shared capability required to see post's menu & publish posts
		if ( ( isset( $_POST[ $post_type.'-map' ] ) && $_POST[ $post_type.'-map' ] == 'on' ) ) {
			$force_map_types[$post_type] = true;
		} else {
			$force_map_types[$post_type] = false;
		}
	}
	update_option( 'mc_force_map_meta_cap', $force_map_types );

	// For mapping capabilities, post type needs to have map_meta_cap set to true
	$mapped_post_types = array_diff( $post_types, $not_mapped );

	foreach ( $roles as $key => $role ) {

		foreach( $mapped_post_types as $post_type ) {

			$post_type_details = get_post_type_object( $post_type );
			$post_type_cap 	= $post_type_details->capability_type;
			$post_type_caps	= $post_type_details->cap;
			$post_role		= $post_type.'-'.$key;

			// Shared capability required to see post's menu & publish posts
			if ( ( isset( $_POST[ $post_role.'-publish' ] ) && $_POST[ $post_role.'-publish' ] == 'on' ) || ( isset( $_POST[ $post_role.'-edit' ] ) && $_POST[ $post_role.'-edit' ] == 'on' ) || ( isset( $_POST[ $post_role.'-edit-others' ] ) && $_POST[ $post_role.'-edit-others' ] == 'on' ) ) {
				$role->add_cap( $post_type_caps->edit_posts );
			} else {
				$role->remove_cap( $post_type_caps->edit_posts );
			}

			// Shared capability required to delete posts
			if ( ( isset( $_POST[ $post_role.'-publish' ] ) && $_POST[ $post_role.'-publish' ] == 'on' ) || ( isset( $_POST[ $post_role.'-edit' ] ) && $_POST[ $post_role.'-edit' ] == 'on' ) ) {
				$role->add_cap( $post_type_caps->delete_posts );
			} else {
				$role->remove_cap( $post_type_caps->delete_posts );
			}

			// Allow publish
			if ( isset( $_POST[ $post_role.'-publish' ] ) && $_POST[ $post_role.'-publish' ] == 'on' ) {
				$role->add_cap( $post_type_caps->publish_posts );
			} else {
				$role->remove_cap( $post_type_caps->publish_posts );
			}

			// Allow editing own posts
			if ( ( isset( $_POST[ $post_role.'-edit' ] ) && $_POST[ $post_role.'-edit' ] == 'on' ) || ( isset( $_POST[ $post_role.'-edit-others' ] ) && $_POST[ $post_role.'-edit-others' ] == 'on' ) ) {
				$role->add_cap( $post_type_caps->edit_published_posts );
				$role->add_cap( $post_type_caps->edit_private_posts );
				$role->add_cap( $post_type_caps->delete_published_posts );
				$role->add_cap( $post_type_caps->delete_private_posts );
			} else {
				$role->remove_cap( $post_type_caps->edit_published_posts );
				$role->remove_cap( $post_type_caps->edit_private_posts );
				$role->remove_cap( $post_type_caps->delete_published_posts );
				$role->remove_cap( $post_type_caps->delete_private_posts );
			}

			// Allow editing other's posts
			if ( isset( $_POST[ $post_role.'-edit-others' ] ) && $_POST[ $post_role.'-edit-others' ] == 'on' ) {
				$role->add_cap( $post_type_caps->edit_others_posts );
				$role->add_cap( $post_type_caps->delete_others_posts );
			} else {
				$role->remove_cap( $post_type_caps->edit_others_posts );
				$role->remove_cap( $post_type_caps->delete_others_posts );
			}

			// Allow reading private
			if ( isset( $_POST[ $post_role.'-private' ] ) && $_POST[ $post_role.'-private' ] == 'on' )
				$role->add_cap( $post_type_caps->read_private_posts);
			else
				$role->remove_cap( $post_type_caps->read_private_posts );
		}
	}

	$location = admin_url( 'users.php?page=mapcap&updated=1' );
	wp_safe_redirect( $location );
	exit;
}
add_action( 'admin_init', 'mc_save_capabilities' );


/**
 * If a post type has been registered without setting map meta cap to true, Map Cap offers
 * the end user a check box to attempt to force the post type to allow meta capability mapping.
 *
 * To force meta capability mapping, this function sets the map_meta_cap flag to true and
 * updates the post type's capabilities to use all primitive capabilities.
 **/
function mc_force_map_meta_cap() {
	global $wp_post_types;
	
	$force_map_types = get_option( 'mc_force_map_meta_cap', array() );

	foreach( $force_map_types as $post_type => $force_mapping ) {

		if ( ! isset( $wp_post_types[$post_type] ) )
			continue;

		// Set the post type to map meta capabilities
		$wp_post_types[$post_type]->map_meta_cap = $force_mapping;

		// Spoof a capabilities array for the get_post_type_capabilities function
		$wp_post_types[$post_type]->capabilities = array();
		foreach( $wp_post_types[$post_type]->cap as $key => $cap )
			$wp_post_types[$post_type]->capabilities[$key] = $cap;

		// Update the post type's capabilities to include the primitive capabilities required by map_meta_cap
		$wp_post_types[$post_type]->cap = get_post_type_capabilities( $wp_post_types[$post_type] );

		// Remove the spoofed capabilities array
		unset($wp_post_types[$post_type]->capabilities);
	}
}
add_action( 'init', 'mc_force_map_meta_cap', 10000 );


/** 
 * If a post author doesn't have permission to edit their own custom post types, they are redirected
 * to the post.php page, but what if they don't have permission to edit vanilla posts? WP Breaks. 
 *
 * This is a bit cludgy, so this function redirects them to that post type's admin index page and adds 
 * a message to show post was published.
 */
function mc_post_access_denied_redirect() {
	global $pagenow;

	if( $pagenow == 'edit.php' ) { // @TODO find a way to determine this with better specificity
		wp_redirect( add_query_arg( array( 'updated' => 1 ), admin_url( 'index.php' ) ) );
		exit;
	}
}
add_action( 'admin_page_access_denied', 'mc_post_access_denied_redirect', 20 ); //run after other functions

/**
 * Loads textdomain
 */
function mc_i18n_support() {
	load_plugin_textdomain(
		'map-cap',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}
add_action( 'plugins_loaded', 'mc_i18n_support' );
