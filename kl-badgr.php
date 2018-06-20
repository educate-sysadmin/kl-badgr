<?php
/*
Plugin Name: KL Badgr
Plugin URI: https://github.org/educate-sysadmin/kl-badgr
Description: Wordpress plugin to provide backpack for Badgr-issued badges.
Version: 0.1
Author: b.cunningham@ucl.ac.uk
Author URI: https://educate.london
License: GPL2
*/

require_once('kl-badgr-options.php');

function kl_badgr_install() {
}

/* query Badgr token from username:password if required */
function kl_badgr_get_token() {
	if (null !== get_option('klbadgr_credentials') && get_option('klbadgr_credentials') == '') {
		return false;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.badgr.io/api-auth/token');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($ch,CURLOPT_POST, 2);
	curl_setopt($ch,CURLOPT_POSTFIELDS, get_option('klbadgr_credentials'));  
	$data = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	//echo 'Status:'.$httpcode; 
	//echo '<br/>';
	//echo 'Data:'.$data;   
	//echo '<br/>';
	curl_close($ch);  
	// parse token
	if ($httpcode == 200 && strlen($data) > 0) {
		$json = json_decode($data);
		$token = $json->token;
	}
	return $token;	
}

/* return array of recipients info for badge with entity_id (using authentication token) */
function kl_badgr_get_badgees($token, $entity_id) {
	$ch = curl_init();	
	curl_setopt($ch, CURLOPT_URL, 'https://api.badgr.io/v2/badgeclasses/'.$entity_id.'/assertions');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Authorization: Token '.$token,
	));
	//curl_setopt($ch,CURLOPT_POST, 1);
	//"recipient":{"identity":"sha256$b4c0eb68cea5f5d5b7520918a29e030fc111201ac7f1a09b1d993455fc3c886b"	 ... ??
	//      curl_setopt($ch,CURLOPT_POSTFIELDS, 'recipient={"identifier":"b.cunningham@ucl.ac.uk"}'); //?
	$data = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($httpcode !== 200 || !$data || strpos($data, '"success":true') === false) { //?
		return false;		
	}

	// parse recipients info
	$recipients = array(); $count = 0;
	$json = json_decode($data, true); // -> array
	$result = $json['result'];
	foreach ($result as $record) {
		foreach ($record as $key => $val) {
		  //echo $key.':'.$val.'</br/>';
		  if ($key == 'entityId') {			
			$recipients[$count] = array();
			$recipients[$count]['entityId'] = $val;
		  }
		  if ($key == 'image') {			
			$recipients[$count]['image'] = $val;
		  }
		  if ($key == 'issuedOn') {			
			$recipients[$count]['issuedOn'] = $val;
		  }		  
		  if ($key == 'recipient') {			
			$recipients[$count]['email'] = $val['plaintextIdentity'];			
		  }
		  if ($key == 'revoked') {			
			$recipients[$count]['revoked'] = $val;
			$count++;
		  }		  
		}
	}
	return $recipients;
}

/* get KL Badgr badges entity_ids */
// TODO: API, HACK uses options currently
function kl_badgr_get_badges() {
	return explode(",",get_option('klbadgr_badges'));
}

/* form to query by email if necessary */
function kl_badgr_form() {
	return "FORM";
}



function kl_badgr($atts, $content = null) {
	
	/* get authorisation token if necessary */
	$token = get_option('klbadgr_token');
	if (!isset($token) || $token == '' || $token == null) {
		$token = kl_badgr_get_token();
	}

	if (!isset($token) || !$token || strlen($token) == 0) {
		return "<p>Authentication error.</p>";
	}
	
	// resolve requests
	$valid = true;
	$badges = array(); // filter by badges or show all
	$badgee = null; // filter by recipient
	if (isset($_REQUEST['badges'])) { 
		if (preg_match("/[A-Za-z0-9,]+/",$_REQUEST['badges']) == 0) {
			$valid = false;	
		} 
	}
	if (isset($_REQUEST['badgee'])) { 
		if (preg_match("/[A-Za-z0-9@\.\_\+]+/",$_REQUEST['badgees']) == 0) {
			$valid = false;	
		} 
	}	
	$allbadges = kl_badgr_get_badges(); //array('ZaVRytPWT72xw3zNRz9xJg');
	var_dump($allbadges);
	if (isset($_REQUEST['badges'])) {
		$badges = explode(",",$_REQUEST['badges']);
		foreach ($badges as $badge) {
			if (!in_array($badge, $allbadges)) {
				$valid = false;
			}
		}
	} else {
		$badges = $allbadges;
	}
	
	if (!$valid) {
		return '<p>'.'Invalid request'.'</p>';
	}
	
	$badgee = (isset($_REQUEST['badgee']))?$_REQUEST['badgee']:null;
	
	if (!isset($badgee)) {
		// form ...
	}
    
	// query awards
	$entity_id = 'ZaVRytPWT72xw3zNRz9xJg';	
	$recipients = kl_badgr_get_badgees($token, $entity_id);
	if (!$recipients) {
		return "<p>Error querying Badgr.</p>";
	}
	
	print_r($recipients);

	return $output;
}

register_activation_hook( __FILE__, 'kl_badgr_install');
add_shortcode('kl_badgr','kl_badgr');
