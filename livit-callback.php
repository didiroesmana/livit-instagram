<?php
require_once('../../../wp-config.php');
require_once('class.instagram.php');
use MetzWeb\Instagram\Instagram;

$client_id = get_option('livit_client_id');
$secret_key = get_option('livit_secret_key'); 
$call_back = get_option('livit_call_back_url');
if (isset($_GET['code'])) {
	$code = $_GET['code'];
}

if (isset($code)){
	$instagram = new Instagram(array(
		'apiKey' => $client_id,
		'apiSecret' => $secret_key,
		'apiCallback' => $call_back
	));
	$data = $instagram->getOAuthToken($code);
	update_option('access_token',$data->access_token);
	header('Location: '.admin_url('admin.php?page=livit-instagram-settings'));
} else {
	die('wazzup bro?');
}

?>