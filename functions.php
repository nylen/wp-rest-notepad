<?php

function error( $message, $code = 500 ) {
	http_response_code( $code );
	header( 'Content-Type: text/plain' );
	die( "Error: $message" );
}

// Letters that don't look like any numbers, plus numbers
$note_id_chars = 'abcdefghkmnpqrstuvwxyz0123456789';
// Desired number of characters (creates/saves are invalid otherwise)
$note_id_length = 10;

function normalize_note_id( $note_id ) {
	global $note_id_chars;

	$note_id = strtolower( $note_id );

	// Replace letters that might look like 1 with 1
	$note_id = str_replace( 'i', '1', $note_id );
	$note_id = str_replace( 'j', '1', $note_id );
	$note_id = str_replace( 'l', '1', $note_id );

	// Replace letters that might look like 0 with 0
	$note_id = str_replace( 'o', '0', $note_id );

	// Get rid of anything that's not in the character list
	$note_id = preg_replace( '@[^' . $note_id_chars . ']@', '', $note_id );

	return $note_id;
}

function generate_note_id() {
	global $note_id_chars, $note_id_length;

	$note_id = '';
	for ( $i = 0; $i < $note_id_length; $i++ ) {
		$note_id .= $note_id_chars[ rand( 0, strlen( $note_id_chars ) - 1 ) ];
	}
	return $note_id;
}

function is_valid_note_id( $note_id ) {
	global $note_id_length;

	$note_id = normalize_note_id( $note_id );
	return ( strlen( $note_id ) === $note_id_length );
}

function render_note( $note ) {
	$action      = ( $note['exists'] ? 'save' : 'create' );
	$action_text = ( $note['exists'] ? 'Save note' : 'Create note' );
	$readonly    = '';

	if ( isset( $note['is_404'] ) && $note['is_404'] ) {
		$readonly = 'readonly onfocus="this.blur()"';
		http_response_code( 404 );
	}

	echo <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, user-scalable=no">
		<title>WP REST Notepad</title>
		<link rel="stylesheet" href="style.css">
	</head>
	<body>
		<form method="POST" action=".">
			<input
				type="text"
				id="title"
				name="title"
				placeholder="Enter note title"
				value="$note[title]"
				$readonly
			>
			<textarea
				id="content"
				name="content"
				placeholder="Enter note content"
				rows="3"
				$readonly
			>$note[content]</textarea>

HTML;

	if ( ! isset( $note['is_404'] ) || ! $note['is_404'] ) {
	echo <<<HTML
			<input type="hidden" name="note_id" value="$note[id]">
			<input type="hidden" name="action" value="$action">
			<input type="submit" id="submit" value="$action_text">

HTML;
	}

	echo <<<HTML
		</form>
	</body>
</html>

HTML;
	exit;
}

function render_404() {
	render_note( array(
		'title'   => '404 Not Found',
		'content' => "This is an error\nThere is nothing to see here\nTry again later?",
		'exists'  => false,
		'is_404'  => true,
	) );
}

function do_api_request( $method, $url, $params ) {
	global $config;

	$options = array(
		'http' => array(
			'ignore_errors' => true,
			'method'        => $method,
			'header'        => array(
				'Authorization: ' . $config['auth_header'],
			),
		)
	);

	if ( $method === 'GET' ) {
		$url .= '?' . http_build_query( $params );
	} else {
		$options['http']['content'] = http_build_query( $params );
		array_push(
			$options['http']['header'],
			'Content-Type: application/x-www-form-urlencoded'
		);
	}

	$context  = stream_context_create( $options );
	$response = @file_get_contents( $url, false, $context );
	if ( $response === false ) {
		error( 'API request failed' );
	}
	if ( ! preg_match( '@^HTTP/[0-9.]+ (200 OK|201 Created)$@', $http_response_header[0] ) ) {
		error( 'Bad API response: ' . $http_response_header[0] );
	}
	$response = json_decode( $response, true );
	if ( $response === null ) {
		error( 'Non-JSON API response' );
	}

	return $response;
}

function get_post_by_note_id( $note_id ) {
	global $config;

	if ( empty( $note_id ) || strlen( $note_id ) < 5 ) {
		return false;
	}
	$response = do_api_request(
		'GET',
		$config['api_root'],
		array(
			'context' => 'edit',
			'status'  => 'private',
			'slug'    => 'note-' . $note_id,
		)
	);
	if ( ! is_array( $response ) || count( $response ) !== 1 ) {
		return false;
	}
	return $response[0];
}

if ( ! function_exists( 'http_response_code' ) ) {
	function http_response_code( $code = NULL ) {
		// For PHP < 5.4.0, adapted from:
		// http://php.net/manual/en/function.http-response-code.php#107261
		// NOTE: unlike the real function, this only *sets* the response code
		switch ( $code ) {
			case 100: $text = 'Continue'; break;
			case 101: $text = 'Switching Protocols'; break;
			case 200: $text = 'OK'; break;
			case 201: $text = 'Created'; break;
			case 202: $text = 'Accepted'; break;
			case 203: $text = 'Non-Authoritative Information'; break;
			case 204: $text = 'No Content'; break;
			case 205: $text = 'Reset Content'; break;
			case 206: $text = 'Partial Content'; break;
			case 300: $text = 'Multiple Choices'; break;
			case 301: $text = 'Moved Permanently'; break;
			case 302: $text = 'Moved Temporarily'; break;
			case 303: $text = 'See Other'; break;
			case 304: $text = 'Not Modified'; break;
			case 305: $text = 'Use Proxy'; break;
			case 400: $text = 'Bad Request'; break;
			case 401: $text = 'Unauthorized'; break;
			case 402: $text = 'Payment Required'; break;
			case 403: $text = 'Forbidden'; break;
			case 404: $text = 'Not Found'; break;
			case 405: $text = 'Method Not Allowed'; break;
			case 406: $text = 'Not Acceptable'; break;
			case 407: $text = 'Proxy Authentication Required'; break;
			case 408: $text = 'Request Time-out'; break;
			case 409: $text = 'Conflict'; break;
			case 410: $text = 'Gone'; break;
			case 411: $text = 'Length Required'; break;
			case 412: $text = 'Precondition Failed'; break;
			case 413: $text = 'Request Entity Too Large'; break;
			case 414: $text = 'Request-URI Too Large'; break;
			case 415: $text = 'Unsupported Media Type'; break;
			case 500: $text = 'Internal Server Error'; break;
			case 501: $text = 'Not Implemented'; break;
			case 502: $text = 'Bad Gateway'; break;
			case 503: $text = 'Service Unavailable'; break;
			case 504: $text = 'Gateway Time-out'; break;
			case 505: $text = 'HTTP Version not supported'; break;
			default:
				exit( 'Unknown http status code "' . htmlentities( $code ) . '"' );
				break;
		}

		$protocol = ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' );
		header( $protocol . ' ' . $code . ' ' . $text );
	}
}
