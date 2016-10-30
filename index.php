<?php

require_once 'functions.php';

include 'config.php';

if (
	! isset( $config ) ||
	! isset( $config['api_root'] ) ||
	! isset( $config['auth_header'] )
) {
	error( 'Copy sample-config.php to config.php and fill in the values.' );
}

if ( isset( $_POST['action'] ) ) {
	// The user has requested to create or save a note.
	$note_id = normalize_note_id( $_POST['note_id'] );

	// Make sure the note ID is valid according to the current rules.
	if ( ! is_valid_note_id( $note_id ) ) {
		error( 'Invalid note ID.' );
	}

	// Either title or content must have a value.
	$title   = ( isset( $_POST['title'] )   ? $_POST['title']   : '' );
	$content = ( isset( $_POST['content'] ) ? $_POST['content'] : '' );
	if ( empty( $title ) && empty( $content ) ) {
		error( 'Title and content cannot both be empty.' );
	}

	switch ( $_POST['action'] ) {
		case 'create':
			$post = get_post_by_note_id( $note_id );
			if ( $post !== false ) {
				error( 'A note with this ID already exists.' );
			}
			$result = do_api_request(
				'POST',
				$config['api_root'],
				array(
					'status'  => 'private',
					'title'   => $title,
					'content' => $content,
					'slug'    => 'note-' . $note_id,
				)
			);
			http_response_code( 302 );
			header( 'Location: ' . $note_id );
			exit;

		case 'save':
			$post = get_post_by_note_id( $note_id );
			if ( $post === false ) {
				error( 'No note with this ID exists.' );
			}
			$result = do_api_request(
				'POST',
				$config['api_root'] . '/' . $post['id'],
				array(
					'title'   => $title,
					'content' => $content,
				)
			);
			http_response_code( 302 );
			header( 'Location: ' . $note_id );
			exit;

		default:
			error( 'That doesn\'t seem right...' );
	}
}

if ( ! isset( $_GET['note_id'] ) || empty( $_GET['note_id'] ) ) {
	// This is a new post; generate what its ID (slug) will be if saved.
	$note_id      = generate_note_id();
	$note_exists  = false;
	$note_title   = '';
	$note_content = '';
} else {
	// Try to get an existing post with the requested note ID.
	$note_id = normalize_note_id( $_GET['note_id'] );
	$post    = get_post_by_note_id( $note_id );
	if ( $post === false ) {
		render_404();
	}
	$note_exists  = true;
	$note_title   = htmlspecialchars( $post['title']['raw'] );
	$note_content = htmlspecialchars( $post['content']['raw'] );
}

render_note( array(
	'title'   => $note_title,
	'content' => $note_content,
	'id'      => $note_id,
	'exists'  => $note_exists,
) );
