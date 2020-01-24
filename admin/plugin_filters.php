<?php 

// Query for all post statuses so attachments are returned
function variant_page_builder_modify_link_query_args( $query ) {
	$query['post_status'] = 'any';
	return $query;
}
add_filter( 'wp_link_query_args', 'variant_page_builder_modify_link_query_args' );

// Link to media file URL instead of attachment page
function variant_page_builder_modify_link_query_results( $results ) {
	foreach ( $results as &$result ) {
		if ( 'Media' === $result['info'] ) {
			$result['permalink'] = wp_get_attachment_url( $result['ID'] );
		}
	}
	return $results;
}
add_filter( 'wp_link_query', 'variant_page_builder_modify_link_query_results', 10, 1 );

function variant_page_builder_filter_media( $query ) {
	// admins get to see everything
	if ( ! current_user_can( 'manage_options' ) ){
		$query['author'] = get_current_user_id();
	}
	
	return $query;
}
add_filter( 'ajax_query_attachments_args', 'variant_page_builder_filter_media' );

/**
 * variant_page_builder_display_template
 * 
 * If variant page builder HTML meta key exists, change the page template to the same one we use for Visual Composer
 * Page template is essentially a header & footer with a blank fullwidth content space in the middle
 * 
 * @blame tommus
 * @since 1.0.0
 */
function variant_page_builder_display_template( $template ){
	global $post;
	
	if( is_archive() || is_404() || is_home() || !( isset($post->post_content) ) || is_search() )
		return $template;

	if( variant_page_builder_check_html($post->ID) ){
		$template = locate_template( array( 'page_visual_composer.php' ) );
	}
	 
	return $template;
	
}

/**
 * variant_page_builder_content_filter
 * 
 * Filters the_content() on a page and replaces it with _variant_page_builder_html meta key if found.
 * Used to replace the regular content with content created by variant
 * 
 * @blame tommus
 * @since v1.0.0
 */
function variant_page_builder_content_filter( $content ) {
	global $post;

    if( !( post_password_required() ) && variant_page_builder_check_html($post->ID) ){
    	
    	//Remove ?lang= query arg in WPML, should not be appended here
    	$home_src = $home = remove_query_arg('lang', home_url('/'));
    	
    	if( has_filter('wpml_object_id') ){
    		$home_src = trailingslashit(get_option('home'));	
    	}
    	
    	$search = array(
    		'src="//',
    		'href="//',
    		'src="/', 
    		'href="/', 
    		'[[', 
    		']]',
    		'src="doNotModify//',
    		'href="doNotModify//',
    		'data-src-webm="/',
    		'data-src-mp4="/'
    	);
    	
    	$replace = array(
    		'src="doNotModify//',
    		'href="doNotModify//',
    		'src="'. $home_src, 
    		'href="'. $home, 
    		'[', 
    		']',
    		'src="//',
    		'href="//',
    		'data-src-webm="'. $home_src,
    		'data-src-mp4="'. $home_src
    	);
    	
    	$html    = get_post_meta( $post->ID, '_variant_page_builder_html', 1 );
    	$content = do_shortcode( str_replace( $search, $replace, $html ) );
    	
	}

    return $content;
}

function variant_page_builder_front_end_body_classes($classes) {
	global $post;
	
	if( isset($post->ID) && variant_page_builder_check_html($post->ID) ){
    	$classes[] = 'variant-content';
	}
	
	$classes[] = 'variant-v' . VARIANT_PAGE_BUILDER_VERSION;
	
    return $classes;
}


/**
 * variant_page_builder_body_classes
 * 
 * Add .variant-active class to body when variant is active. Plugin hook called in init
 * 
 * @blame tommus
 * @since 1.0.0
 */
function variant_page_builder_body_classes($classes) {
    $classes[] = 'variant-active';
    return $classes;
}