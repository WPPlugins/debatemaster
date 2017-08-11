<?php
/*
Plugin Name: DebateMaster
Plugin URI: http://jeremybmerrill.com/wordpress/debatemaster.php
Description: Allows in-post "debate" by enabling a shortcode for tagging different parts of a post as by different authors.
Version: 1.0.0
Author: Jeremy B. Merrill
Author URI: http://jeremybmerrill.com
License: GPL2
*/
wp_enqueue_style('debatemaster_styles', WP_PLUGIN_URL . '/debatemaster/debatemaster_styles.css',false,'0.1','all');

$isleft = True;

function debatemaster_author($atts, $content = null) {
	global $isleft;
	global $wpdb;
	/* atts must be a username */
	if ($isleft){
		$leftclass = "debatemaster-left";
	}else{
		$leftclass = "debatemaster-right";
	}
	$isleft = !$isleft;
	$prelimoutput = '<div class="debatepoint ' . $leftclass . '"';	
	$colorarray = get_debatemaster_options();
	$bgcolor = $colorarray[$atts["id"]];
	if (substr($bgcolor,0,1) != "#")
		$bgcolor = "#".$bgcolor;
	$style = ' style="background-color: ' .$bgcolor. '";';

	$useridquery = 'SELECT * FROM ' . $wpdb->prefix. "users" . ' WHERE user_nicename = "'. $atts["id"] .'"';
	$userinfo = $wpdb->get_row($useridquery, ARRAY_A);
	$userid = $userinfo["ID"];//get user ID # from the nicename

	/*$userinfoquery = 'SELECT * FROM ' . $wpdb->prefix. "usermeta" .' WHERE user_id = "' .$userid.'"';
	$userinfo = $wpdb->get_results($userinfoquery, "ARRAY_A");
	print_r($userinfo);
	$usernicename = $userinfo["first_n"] . " " . $userinfo["last_n"];*/
	$userfullname = get_user_meta($userid, "nickname", True);

	$avatar = get_avatar($userid, 48);
	$prelimoutput .= $style;
	$prelimoutput .= '>';
	$prelimoutput .= $avatar . '<span class="usernicename">'.$userfullname.'</span> <span class"argues">&nbsp;argues:</span>';
	$endoutput = '</div><div style="clear: both;"></div> <!--end debatepoint-->';
	return $prelimoutput."<p>".$content."</p>".$endoutput;
}
add_shortcode("debate", "debatemaster_author");

// mt_settings_page() displays the page content for the Test settings submenu
function get_debatemaster_options() {
	//get a list of all the users
	$debatemaster_options = array();
	global $wpdb;
	$query = "SELECT user_nicename FROM " . $wpdb->prefix."users";
	$userarray = $wpdb->get_col($query);
	//create an array with user IDs as key, set default value to "none'
	foreach ($userarray as $usernicename){
		$debatemaster_options[$usernicename] = "none";
	}
	//$debatemaster_options = array();
  	$debatemaster_db_options = get_option("debatemaster_options");
	if (!empty($debatemaster_db_options)){
		foreach ($debatemaster_db_options as $key => $option)
			$debatemaster_options[$key] = $option;
	}
	update_option("debatemaster_options", $debatemaster_options);
	return $debatemaster_options;
}

add_action('admin_menu', 'debatemaster_plugin_menu');

function debatemaster_plugin_menu() {
  add_options_page('DebateMaster Plugin Options', 'DebateMaster', 'manage_options', 'debatemaster-menu', 'debatemaster_plugin_options');
}

function debatemaster_plugin_options() {
  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }
	$optionsarray = get_debatemaster_options();
	if (isset($_POST['dm_submit'])){ //if the submit button got clicked last time;
		//make an array, key = usernicenames and value = update value
		foreach($optionsarray as $key => $throwaway){
			if (isset($_POST[$key])){
				if (preg_match("/#?[0-9abcdefABCDEF]{6}|#?[0-9abcdefABCDEF]{3}|none/",$_POST[$key])){
					$optionsarray[$key] = $_POST[$key];
				}else{
					echo "One of your updates is invalid. Please try again. (Options were <strong>NOT</strong> updated!)";
				}
			}
		}

		update_option("debatemaster_options",$optionsarray);
	}
	
	echo '<div class="wrap">';
	//print the options page
	?>
	<h1>DebateMaster Options</h1>
	<h2>Color Options</h2>
	<h4>Set the background colors used for each author in a debate. Use "none" or HTML color codes like "#ffffff"</h4>
	<?php
	echo '<p> <form name="input" action="" method="POST">';
	foreach ($optionsarray as $key => $value){
		echo '<span id="dm_username">' . $key . '</span>';
		echo '<span id="dm_color"><input type="text" name="'.$key .'" value="'.$value.'"/></span>';
		echo "<br />";
	}
	echo '<input type="submit" name="dm_submit" value="Update DebateMaster Preferences" />
</form>';
	echo '</p>';
	echo '</div>';
}

add_action('deactivate_debatemaster.php','dm_removeoptions');

function dm_removeoptions(){
	delete_option("debatemaster_options");
}
/* css notes
	debatepoint: rounded corners, size
	*/
?>
