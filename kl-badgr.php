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
			// add URL
         	$recipients[$count]['URL'] = kl_badgr_get_badge_url($entity_id);
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

/* get badge criteria URL */
// TODO: API, HACK uses options currently
function kl_badgr_get_badge_url($badge) {
    $badge_urls = json_decode(get_option('klbadgr_urls'),true);
    return $badge_urls[$badge];
}

/* form to query by email if necessary */
function kl_badgr_form() {
    $return = '';
    $return .= '<div class="kl_badgr kl_badgr_search">'."\n";
    $return .= '<h2>Search</h2>';
    $return .= '<form method="post">'."\n";    
	$return .= '<label for = "email">Email:</label>&nbsp;<input type = "text" id="email" name = "email" value="" size="40"/>'."\n";
	$return .= '<br/> OR <br/>';
	$return .= '<label for = "entity">Award id (entity id)</label>:&nbsp;<input type = "text" id="entity" name = "entity" value="" size="40"/>'."\n";	    
    //$return .= wp_nonce_field('kl_badgr','kl_badgr');     // TODO
    $return .= '<br/>'."\n";
	$return .= '<p><input type = "submit" name = "kl_badgr" value="Search"></p>'."\n";
    $return .= '</form>'."\n";    
    $return .= '</div>'."\n";     
    
	return $return;
}

/* return html to output a badge award */
function kl_badge_award_display($badge_award) {
    $return = '';
    $return .= '<table class="kl_badgr">'."\n";
    $return .= '<thead>'."\n";    
    $return .= '<tr>'.'<th>'.'Badge'.'</th>'.'<th>'.'Details'.'</th>'.'</tr>'."\n";    
    $return .= '</thead>'."\n";    
    $return .= '<tbody>'."\n"; 
    $return .= '<tr>'."\n";
    $return .= '<td class="kl_badgr kl_badgr_badge">'."\n"; 
    $return .= '<a href = "'.$badge_award['image'].'">'."\n";
    $return .= '<img src = "'.$badge_award['image'].'" class="kl_badgr kl_badgr_img"/>'."\n";   
    $return .= '</a>'."\n";
    $return .= '</td>'."\n";    
    $return .= '<td class="kl_badgr kl_badgr_details" style="vertical-align: top;">'."\n";
    foreach ($badge_award as $key => $val) {    
        $return .= '<p class = "kl_badgr kl_badgr_'.$key.'">';
        $return .= '<strong>'.ucfirst($key).'</strong>'.': ';
        if ($key == 'image' || $key == 'URL') {
            $return .= '<a href = "'.$val.'">'."\n";        
        }        
        $return .= $val;
        if ($key == 'image' || $key == 'URL') {
            $return .= '</a>'."\n";
        }        
        $return .= "\n";
        $return .= '</p>';
    }
    $return .= '</td>'."\n";    
    $return .= '</tr>'."\n";        
    $return .= '</tbody>'."\n";        
    $return .= '</table>'."\n";    
    
    return $return;
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
	$badges_request = array(); // filter by badges or show all
	$email_request = null; // filter by recipient
	$entity_request = null; // show specific award
	// check nonce for form posting
	/*
	if (isset($_POST['kl_badgr'])) {
	    if (!wp_verify_nonce($_REQUEST['kl_badgr'],'kl_badgr')) {
	        $valid = false;
	        return '<p>'.'Invalid request: nonce'.'</p>';
	    }
	}
	
	*/
	if (isset($_REQUEST['badges'])) { 
		if (preg_match("/[A-Za-z0-9,]+/",$_REQUEST['badges']) == 0) {
			$valid = false;	
			return '<p>'.'Invalid request: badges'.'</p>';
		} 
	} else {
	    $badges_request = kl_badgr_get_badges(); 
	}
	if (isset($_REQUEST['email'])  && $_REQUEST['email'] != "") { 
		if (preg_match("/[A-Za-z0-9@._+]+/",$_REQUEST['email']) == 0) {
			$valid = false;	
			return '<p>'.'Invalid request: email'.'</p>';
		} 
	}
	if (isset($_REQUEST['entity']) && $_REQUEST['entity'] != "") { 
		if (preg_match("/[A-Za-z0-9,]+/",$_REQUEST['entity']) == 0) {
			$valid = false;	
            return '<p>'.'Invalid request: entity'.'</p>';			
		} 
	}			
	$allbadges = kl_badgr_get_badges(); //c;
	if (isset($_REQUEST['badges'])) {
		$badges_request = explode(",",$_REQUEST['badges']);
		foreach ($badges_request as $badge) {
			if (!in_array($badge, $allbadges)) {
				$valid = false;
			}
		}
	} else {
		$badges_request = $allbadges;
	}
	
	if (!$valid) {
		return '<p>'.'Invalid request'.'</p>';
	}	
	$entity_request = (isset($_REQUEST['entity']) && $_REQUEST['entity'] != "")?$_REQUEST['entity']:null;
	$email_request = (isset($_REQUEST['email']) && $_REQUEST['email'] != "")?$_REQUEST['email']:null;	
	
	//if (!isset($email_request) && !isset($entity_request)) {
        $output .= kl_badgr_form(); 
	//}

    $output .= '<h2>Awards</h2>';
    // query badges and awards	
    $awards = array();
	foreach ($badges_request as $badge) {
	    $awards[$badge] = kl_badgr_get_badgees($token, $badge);
	}
	
	// select and output
	foreach ($awards as $badge => $badge_awards) {
	    foreach ($badge_awards as $badge_award) {
            if ($entity_request) {
                if ((string) trim($badge_award['entityId']) != (string) trim($entity_request)) {
                    continue;
                }                
            }	        
            if ($email_request) {
                if ($badge_award['email'] !== $email_request) {
                    continue;
                }                
            }
            // else
            $output .= kl_badge_award_display($badge_award);
	    }	
    }
    
	return $output;
}

register_activation_hook( __FILE__, 'kl_badgr_install');
add_shortcode('kl_badgr','kl_badgr');
