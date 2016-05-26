<?php
/**
 * User: kylemaurer
 * Date: 5/26/16
 * Time: 6:55 AM
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get's the number of Help Scout conversations for a tag that matches the download's slug
 * 
 * @param $tag
 *
 * @return mixed
 */
function eddlc_get_conversation_count( $tag ) {
	$api = '2f6de042a3efb509593e72f0fe2743d900d7f407';
	$mailbox = '18425';
	$url     = 'https://api.helpscout.net/v1/mailboxes/' . $mailbox . '/conversations.json?tag=' . $tag;
	$args = array(
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $api . ':X' ),
			'Content-Type' => 'application/x-www-form-urlencoded'
		)
	);
	$request = wp_remote_request( $url, $args );
	$request = json_decode( wp_remote_retrieve_body( $request ) );
	return $request->count;
}

/**
 * Returns the percentage of sales that result in a ticket
 *
 * @param $sales
 * @param $conversations
 *
 * @return float|int
 */
function eddlc_get_conversation_rate( $sales, $conversations ) {

	$rate = ( $conversations !== 0 ) ? $sales / $conversations : 0;
	return $rate;
}