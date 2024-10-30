<?php 

/*
Plugin Name: Contact Form 7 Referrer Add-on
Plugin URI: http://www.nicholastsim.co.uk/works/contact-form-7-referrer-plugin/
Description: Add useful referrer information to emails sent via any Contact Form 7 contact forms on your Wordpress website. Based on the Enhanced Wordpress Contact Form plugin by Yoast. Simply activate and it will automatically be added to your CF7 emails.
Author: Nicholas Tsim
Author URI: http://www.nicholastsim.co.uk
Version: 1.0.0
License: GPLv2 or later
*/


function wpcf7_referrer_promo_message () {
	global $pagenow;

	if(!current_user_can('install_plugins')) { return; }

	$hide = (int)get_option('wpcf7_referrer_hide_promo_message');
	$hide = 2;

	if(isset($_GET['hide_promo']) && $_GET['hide_promo'] == 'wpcf7_referrer_promo_message') {
		$hide = 2;
		update_option('wpcf7_referrer_hide_promo_message', $hide);
	}

	if($pagenow == 'admin.php' && isset($_REQUEST['page']) && $_REQUEST['page'] == 'wpcf7' && $hide !== 1) {

 		?>
 		<div class="updated">
        <h2>
        <?php 
        $title = 'Support Nicholas Tsim';
        _e( $title, 'default' )?>
   		<a class="button alignright" href="<?php echo "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'&hide_promo=wpcf7_referrer_promo_message' ?>">Hide permanently</a>        
        </h2><p>
        <?php 
        $promo_message = 'PHP developer and creator of the Contact Form 7 Referrer add-on. Donations greatly appreciated and will help support/motivate me to develop further plugins for Wordpress.';
        _e( $promo_message, 'default') ?>
        </p>
   		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="PMVZ8946CCMB8"><input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online."><img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1"></form>
		</div>
		<?php
	}
}

add_action('admin_notices', 'wpcf7_referrer_promo_message');


function wpcf7_referrer_get_query($query) {

	//Google keywords are deprecated, but will still be searched for in case they ever return
	if (strpos($query, "google.")) {
		$pattern = '/^.*\/search.*[\?&]q=(.*)$/';
	} else if (strpos($query, "bing.com")) {
		$pattern = '/^.*q=(.*)$/';
	} else if (strpos($query, "yahoo.")) {
		$pattern = '/^.*[\?&]p=(.*)$/';
	} else if (strpos($query, "ask.")) {
		$pattern = '/^.*[\?&]q=(.*)$/';
	} else {
		return false;
	}
	preg_match($pattern, $query, $matches);
	$querystr = substr($matches[1], 0, strpos($matches[1], '&'));
	return urldecode($querystr);
}

add_filter('wpcf7_referrer_keywords_query','wpcf7_referrer_get_query');

function wpcf7_referer_session() {
	$baseurl = get_bloginfo('url');
	if ( !isset($_SESSION) ) {
		session_start();
	}

	if ( !isset($_SESSION['wpcf7_pages']) || !is_array($_SESSION['wpcf7_pages']) ) {
		$_SESSION['wpcf7_pages'] = array();
	}

	if ( !isset($_SESSION['wpcf7_referer']) || !is_array($_SESSION['wpcf7_referer']) ) {
		$_SESSION['wpcf7_referer'] = array();
	}

	if ( !isset($_SERVER['HTTP_REFERER'])) {
		$_SESSION['wpcf7_referer'][] = "Type-in or bookmark";
	} elseif ( (strpos($_SERVER['HTTP_REFERER'], $baseurl) === false) && ! (in_array($_SERVER['HTTP_REFERER'], $_SESSION['wpcf7_referer']))) {
		$_SESSION['wpcf7_referer'][] = $_SERVER['HTTP_REFERER'];
	}
	if (end($_SESSION['wpcf7_pages']) != "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']) {
		$_SESSION['wpcf7_pages'][] = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];	
	}
}

add_action('init','wpcf7_referer_session');

function wpcf7_referrer_before_send_mail($array) {

	global $wpdb;

	if(wpautop($array['body']) == $array['body']) {// The email is of HTML type
		$lineBreak = "<br/>";
	} else {
		$lineBreak = "\n";
	}

	$referrerinfo = '--Referrer Info--'.$lineBreak.$lineBreak;

	$keywords = array();

	//Referrer 
	$i = 1;
	foreach ($_SESSION['wpcf7_referer'] as $referer) {
		$referrerinfo .= str_pad("Referer $i: ",20) . $referer . $lineBreak;
		$keywords_used = apply_filters('wpcf7_referrer_keywords_query',$referer);
		if ($keywords_used) {
			$keywords[] = $keywords_used;
		}
		$i++;
	}
	$referrerinfo .= $lineBreak;
	
	//Pages visited before contact form
	$i = 1;
	foreach ($_SESSION['wpcf7_pages'] as $page) {
		$referrerinfo .= str_pad("Page visited $i: ",20) . $page. $lineBreak;
		$i++;
	}
	$referrerinfo .= $lineBreak;

	//Keywords used
	$i = 1;
	if (count($keywords) > 0) {
		foreach ($keywords as $keyword) {
			$referrerinfo .= str_pad("Keyword $i: ",20) . $keyword. $lineBreak;
			$i++;
		}
	$referrerinfo .= $lineBreak;
	}

	if ( isset ($_SERVER["REMOTE_ADDR"]) )
		$referrerinfo .= 'User\'s IP: ' . $_SERVER["REMOTE_ADDR"] . $lineBreak;
	
	if ( isset ($_SERVER["HTTP_X_FORWARDED_FOR"]))
		$referrerinfo .= 'User\'s Proxy Server IP: ' . $_SERVER["HTTP_X_FORWARDED_FOR"] . $lineBreak ;

	if ( isset ($_SERVER["HTTP_USER_AGENT"]) )
		$referrerinfo .= 'User\'s browser is: ' . $_SERVER["HTTP_USER_AGENT"] . $lineBreak;

	//$array['body'] = str_replace('[referrer]', $referrerinfo, $array['body']);
	
	$array['body'] .= $lineBreak.$lineBreak.$referrerinfo;

	return $array;
	
}

add_filter('wpcf7_mail_components', 'wpcf7_referrer_before_send_mail');



