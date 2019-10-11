<?php
function activarTareas(){
	if( !wp_next_scheduled('miEventoCadaHora' ) ){
		wp_schedule_event( time(), 'hourly', 'miEventoCadaHora' );
	}
}
add_action('wp', 'activarTareas');
function ejecutarTareaCadaHora(){
	//user id test = 3750
	$user_list = mailchimp_delete_user_expiry( 3750 );
	return $user_list;
}
add_action('miEventoCadaHora', 'ejecutarTareaCadaHora');
function mailchimp_delete_user_expiry( $user_id ){
	$mailchimp     = get_option('mailchimp-woocommerce', false );
	$email         = get_userdata( $user_id )->user_email;
	//https://docs.woocommerce.com/document/woocommerce-memberships-function-reference/
	$member_active = wc_memberships_is_user_active_member( $email , 'member' ) ;
	$user_list     = mailchimp_get_user( $email, $mailchimp );

	if ( $member_active !== true && $user_list === true ) {
		$api_key = $mailchimp['mailchimp_api_key'];
		$list_id = $mailchimp['mailchimp_list'];
		$email   = $email;
		$args    = array(
			'method' => 'DELETE',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $api_key )
			)
		);
		$response = wp_remote_post( 'https://' . substr($api_key,strpos($api_key,'-')+1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email)), $args );
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			//TODO -> refactor here put this in a log file
			$users_data = "Something went wrong: $error_message";
		} else {
			$users_data = "User Delete";
		}
		return $users_data;
	}
	return false;
}
function mailchimp_get_user( $user_email, $mailchimp_list_array ){
		$mailchimp_list_array = get_option('mailchimp-woocommerce', false);
		$api_key              = $mailchimp_list_array['mailchimp_api_key'];
		$list_id              = $mailchimp_list_array['mailchimp_list'];
		$email                = $user_email ;
		$args                 = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $api_key )
			)
		);
		$response = wp_remote_post( 'https://' . substr($api_key,strpos($api_key,'-')+1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email)), $args );
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			//TODO -> refactor here put this in a log file
			$users_data = "Something went wrong: $error_message";
		} else {
			$users_data = true;
		}
		return $users_data;
}