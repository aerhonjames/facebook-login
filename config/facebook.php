<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by: Aerhon Oliveros
 * Developer account used: Aerhon Oliveros
 * Extend administrator users:
 */

$config['facebook']['credentials'] = [
	'app_id' => '',
	'app_secret' => ''
];

$config['facebook']['login'] = [
	'scopes' => [
		'email',
		'public_profile'
	],
	'redirect_uri' => 'facebook/callback'
];

$config['facebook']['page_id'] = ''; 
// $config['facebook']['page_id'] = ''; // For Testing