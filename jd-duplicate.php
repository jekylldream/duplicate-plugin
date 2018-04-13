<?php

/*
Plugin Name: Duplicate Posts And Pages
Description: Plugin Enabling Easy Duplication of Posts and Pages
Version:     0.1
Author:      Jekyll Dream
Author URI:  http://jekylldream.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

define('plugin_url', plugin_dir_url( __FILE__ ));
//Add duplicate button to post & page row lists
add_filter( 'post_row_actions', 'jd_row_actions', 10, 2 );
add_filter( 'page_row_actions', 'jd_row_actions', 10, 2 );
//Add duplicate button to bulk action drop downs for posts & pages
add_filter( 'bulk_actions-edit-post', 'jd_bulk_actions' );
add_filter( 'handle_bulk_actions-edit-post', 'jd_bulk_actions_handler', 10, 3 );
add_filter( 'bulk_actions-edit-page', 'jd_bulk_actions' );
add_filter( 'handle_bulk_actions-edit-page', 'jd_bulk_actions_handler', 10, 3 );
//Add deplicate button to submit box when editing posts & pages
add_action( 'post_submitbox_misc_actions', 'jd_submitbox_button' );
//Enqueue init function to call .js and .css files
add_action( 'admin_enqueue_scripts', 'jd_init');
//Ajax handler for ajax duplicate calls
add_action( 'wp_ajax_jd_duplicate_handler', 'jd_duplicate_handler');

function jd_init() {

	wp_register_script('jd-duplicate', plugin_dir_url( __FILE__ ) . 'jd-duplicate.js', array('jquery'));
	wp_enqueue_script('jd-duplicate');
	wp_enqueue_style( 'jd-duplicate', plugin_dir_url( __FILE__ ) . 'jd-duplicate-style.css' );

}

function jd_row_actions( $actions, $post ) {

	jd_init();

	if ( $post->post_type == "post" or "page") {
        //Properly retrieving the POST ID using $post->ID breaks the AJAX call... it never reaches the "done" state. 
		//putting it back to the (broken) $post->post_id causes it to send the call but the ID is blank.
		$actions['Duplicate'] = '<span class="jd-duplicate" name="' . $post->post_title . '" value="' . $post->ID . '">Duplicate</span>';
		return $actions;

	}

}

//Build our submit box button that shows when editing posts & pages
function jd_submitbox_button ( $post ) {

	jd_init();

	if ( $post->post_status != 'auto-draft' and $post->post_status != 'trash') {
	?>

	<div class="misc-pub-section" id="jd-duplicate-misc-pub-section">
	<span name='redirect-to-edit' value='<?php echo $post->ID; ?>' class="jd-duplicate jd-duplicate-submitbox-button button button-primary button-large"> Duplicate </span>
	</div>

	<?php
	}

}

//Bulk action list modification for posts & pages list view
function jd_bulk_actions ( $actions ) {

	$actions['Duplicate'] = __('Duplicate', 'duplicate');
	return $actions;

}

//Handler for the duplicate bulk action on posts & pages list view
function jd_bulk_actions_handler ( $redirect_to, $doaction, $post_ids ) {
	
	//jd_debug( 'reached this return 3' );

	if ( $doaction == "Duplicate" ) {

		foreach ( $post_ids as $post_id ) {

			jd_duplicate_handler( $post_id );

		}

		$redirect_to = add_query_arg( 'jd_bulk_duplicate', count( $post_ids ), $redirect_to );

	}
	
	return $redirect_to;

}

function jd_duplicate_handler( $sourceid ) {
	
	if ( empty( $sourceid ) ) {

		$sourceid = $_POST['postid'];
	
	}

	//Determine $output as proper type of ARRAY for return of get_post()
	$output = ARRAY_A;

	$source = get_post( $sourceid, $output );

	//Setup our destination post
	$destination = $source;

	$destination['post_title'] = $destination['post_title'] . ' copy';

	//Retrieve the original post comments
	
	$args = array(
		'post_id' => $source['ID']
	);

	$comments = get_comments( $args );

	//Retrieve the original post meta

	$sourcemeta = get_post_meta( $source['ID'] );

	//Prevent overwrite of original post by unsetting the ID of new post
	unset( $destination['ID'] );

	//Insert the post now
	$newpost = wp_insert_post( $destination );

	//Insert comments
	//$comments returns OBJECTs, whereas wp_new_comment expects an array. Let's convert.

	foreach ( $comments as $comment ) {

		$dupecomment = array(
			'comment_post_ID' => $newpost,
			'comment_author' => $comment->comment_author,
			'comment_author_email' => $comment->comment_author_email,
			'comment_author_url' => $comment->comment_author_url,
			'comment_content' => $comment->comment_content,
			'comment_type' => $comment->comment_type,
			'comment_parent' => $comment->comment_parent,
			'comment_date' => $comment->comment_date,
			'user_id' => $comment->user_id	
		);

		wp_new_comment( $dupecomment );

	}

	//Insert metadata

	foreach ( $sourcemeta as $key => $value ) {

		update_post_meta( $newpost, $key, $value[0]);

	}

	if ( $_POST['postname'] == 'redirect-to-edit' ) {

		// This is not working... it won't redirect to the new edit page yet.
		
		$editurl = get_edit_post_link( $newpost, '' );

		echo $editurl;

		wp_die();

	}

}

//---+---START DEBUG---+---
function jd_debug( $info ) {

	$debugpostid = 185;

	if ( !empty($info) ) {
	$postdata = array(
		'ID' => $debugpostid,
		'post_content' => 'Debug: ' . $info
	);

	wp_update_post( $postdata );
	}
	else {
		$debugpost = get_post( 185, ARRAY_A );
		$info = $debugpost['post_content'];
		return $info;
	}

}

function jd_admin_alert( $info ) {

	?>

	<div class="notice notice-success is-dismissable">
		<p><?php // echo jd_debug(); ?></p>
	</div>

	<?php

}

add_action('admin_notices', 'jd_admin_alert');
//---+---END DEBUG---+---
?>