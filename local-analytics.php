<?php
/*
Plugin Name: Local Analytics
Plugin URI: http://www.joycebabu.com/downloads/local-analytics/
Description: Periodically downloads and serves ga.js from your server.
Version: 1.2.2
Author: Joyce Babu
Author URI: http://www.joycebabu.com/
*/
/*
Copyright 2007 Joyce Babu (email : contact@joycebabu.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

DEFINE('LOCAL_ANALYTICS_VERSION', '1.2.2');
DEFINE('LOCAL_ANALYTICS_PATH', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
DEFINE('LOCAL_ANALYTICS_ANCHOR_REGEX', '/<a (.*?)href=(["\'])(.*?)\\2(.*?)>(.*?)<\/a>/i');

register_activation_hook(LOCAL_ANALYTICS_PATH, 'locan_activate');

add_action('init', 'locan_init');
add_action('admin_menu', 'locan_admin_menu');
add_action('admin_print_scripts', 'locan_admin_print_scripts' );
add_filter('the_content', 'locan_parse_content');
add_action('admin_footer', 'locan_admin_footer');
add_filter('wp_footer', 'locan_wp_footer');

$locan_dirname = basename(dirname(__FILE__));
$locan_install_dir = ABSPATH . "wp-content/plugins/$locan_dirname/";
$locan_localfile = $locan_install_dir . 'local-urchin.js';
$locan_roles = array(array('Administrator', 10), array('Editor', 7), array('Author', 4), array('Contributor', 1), array('Subscriber', 0));

function locan_activate(){
	global $locan_localfile, $locan_install_dir, $locan_dirname;
	add_option('locan_localfile', $locan_localfile, 'Local Analytics: Local File Path');

	add_option('locan_uacct', 'UA-XXXXXXX-X', 'Local Analytics: Analytics Key');
	add_option('locan_disable_tracker', '0', 'Local Analytics: Disable Tracking');
	add_option('locan_enable_caching', '1', 'Local Analytics: Enable server side caching of ga.js');
	add_option('locan_use_gzip', '1', 'Local Analytics: User Gzip compression');
	add_option('locan_cache_time', '24', 'Local Analytics: Time to cache');

	add_option('locan_track_admin', '0', 'Local Analytics: Track visits to admin panel');
	add_option('locan_track_users', '1', 'Local Analytics: Track visits to admin panel');
	add_option('locan_track_userlevel', '4', 'Local Analytics: Users with higher userlevel will be ignored');
	add_option('locan_track_ads', '0', 'Local Analytics: Track clicks on ads');
	add_option('locan_ads_prefix', '/ads', 'Local Analytics: Prefix for tracked ads');
	add_option('locan_track_downloads', '1', 'Local Analytics: Track file downloads');
	add_option('locan_tracked_extensions', 'bmp,bz,doc,dmg,exe,gif,gz,jar,jpg,jpeg,js,mp3,pdf,phps,png,pps,ppt,rar,sit,tar,wav,xls,zip', 'Local Analytics: File extensionds to be tracked');
	add_option('locan_download_prefix', '/downloads', 'Local Analytics: Prefix for tracked downloads');
	add_option('locan_track_external', '1', 'Local Analytics: Track visits to external sites');
	preg_match('@http(s)?://(www\.)?(.*)(/.*)?@i', get_bloginfo('url'), $server);
	add_option('locan_internal_domains', "www.$server[3],$server[3]", 'Local Analytics: Domains considered as internal');
	add_option('locan_external_prefix', '/external', 'Local Analytics: Prefix for external sites');
	add_option('locan_track_mailto', '1', 'Local Analytics: Track clicks on email links');
	add_option('locan_mailto_prefix', '/mailto', 'Local Analytics: Prefix for email links');
	add_option('locan_extra_code', '', 'Local Analytics: Extra code for inserting');

	// Create the cached file if doesn't exist
	if(!is_writable($locan_install_dir)){
		chmod($locan_install_dir, 0666);
	}
	if(!file_exists($locan_localfile)){
		// Create the local file and set the last modified time to a past date
		if(!touch($locan_localfile, mktime(0, 0, 0, 0, 0, date('Y') - 1))){
			die("The current directory is not writable. You'll have to either change the permissions on the plugin directory or create the <em>$locan_localfile</em> file manually and chmod it 666.");
		}
	}
	if(!is_writable($locan_localfile)){
		chmod($locan_localfile, 0666);
	}
}

function locan_init(){
	global $locan_dirname, $user_level, $doing_rss, $locan_localfile;
	$uacct = get_option('locan_uacct');
	$is_feed = (is_feed() || $doing_rss);


	if(get_option('locan_disable_tracker') || $is_feed || ($uacct == 'UA-XXXXXXX-X') || (isset($user_level) &&  (!get_option('locan_track_users') || $user_level > get_option('locan_track_userlevel')))){
		DEFINE('LOCAL_ANALYTICS_TRACKING_ENABLED', false);
	}else{
		DEFINE('LOCAL_ANALYTICS_TRACKING_ENABLED', true);
		add_action ('wp_head', 'locan_insert_code');
	}
}

function locan_insert_code(){
	global $locan_dirname;
	if(LOCAL_ANALYTICS_TRACKING_ENABLED){
		$extra = get_option('locan_extra_code') ? get_option('locan_extra_code') . "\n" : '';
		echo "\n<!-- Begin Google Analytics Code by Local Analytics Plugin -->\n";
		if(get_option('locan_enable_caching')){
			$src = "/wp-content/plugins/$locan_dirname/local-urchin-js.php";
			wp_register_script('local-analytics-urchin', $src, false, '1.0');
		}else{
			//wp_register_script('local-analytics-urchin', ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://www.' : 'https://ssl.') . 'google-analytics.com/ga.js', false, '');
			echo "<script type=\"text/javascript\">\nvar gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");\ndocument.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));\n</script>";

		}
		wp_print_scripts(array('local-analytics-urchin'));
		echo "<script type=\"text/javascript\">\nvar pageTracker = _gat._getTracker(\"" . get_option('locan_uacct') . "\");\n{$extra}pageTracker._initData();\npageTracker._trackPageview();\n</script>\n<!-- End Google Analytics Code by Local Analytics Plugin -->\n";
	}
}

function locan_parse_link($matches){
	$extFilter = get_option('locan_tracked_extensions');
	$intDomains = get_option('locan_internal_domains');
	$url = (($pos = strpos($matches[3], '?')) === false) ? $matches[3] : substr($matches[3], 0, $pos);
	$url = parse_url($url);
	$url_no_scheme = $url['host'] . $url['path'] . /*(empty($url['query']) ? '' : "?{$url['query']}") . */(empty($url['fragment']) ? '' : "?{$url['fragment']}");//ltrim(str_replace('http://', '', $matches[3]), '/');
	if(!(empty($url['host']) || empty($intDomains)) && !eregi("^($intDomains)$", $url['host'])){
		if(get_option('locan_track_external')){
			$track_url = get_option('locan_external_prefix') . '/' . $url_no_scheme;
			$matches[4] .= " onclick=\"javascript:pageTracker._trackPageview('$track_url');\" ";	
		}
	}else{
		// Check for download link
		if(!empty($extFilter) && eregi("($extFilter)$", $matches[3]) && get_option('locan_track_downloads')){
			$track_url = get_option('locan_download_prefix') . $url['path'];
			$matches[4] .= " onclick=\"javascript:pageTracker._trackPageview('$track_url');\" ";
		}elseif(get_option('locan_track_mailto') && (substr($matches[3], 0, 7) == 'mailto:')){
			$track_url = get_option('locan_mailto_prefix') . '/'. substr($matches[3], 7);
			$matches[4] .= " onclick=\"javascript:pageTracker._trackPageview('$track_url');\" ";
		}
	}
	return "\n<a $matches[1] href=$matches[2]$matches[3]$matches[2]$matches[4]>$matches[5]</a>";
}

function locan_parse_content($content){
	if(LOCAL_ANALYTICS_TRACKING_ENABLED){
		return preg_replace_callback(LOCAL_ANALYTICS_ANCHOR_REGEX, 'locan_parse_link', $content);
	}else{
		return $content;
	}
}

function locan_wp_footer(){
	global $locan_dirname;
	if(LOCAL_ANALYTICS_TRACKING_ENABLED && get_option('locan_track_ads')){
		$wp_address = get_option('siteurl');
		echo "\n<!-- Begin Ad Click Tracking Code by Local Analytics Plugin -->\n";
		echo "<script type='text/javascript' src='$wp_address/wp-content/plugins/$locan_dirname/local-astrack.php'></script>";
		echo "\n<!-- End Ad Click Tracking Code by Local Analytics Plugin -->\n";
	}
}

function locan_admin_menu(){
	add_options_page('Local Analytics Options', 'Local Analytics', 8, LOCAL_ANALYTICS_PATH, 'locan_admin_options_page');
}

function locan_admin_footer(){
	global $locan_dirname, $locan_localfile;
	$uacct = get_option('locan_uacct');
	$error = '';
	if($uacct == 'UA-XXXXXXX-X'){
		$error .= "<b>Local Analytics</b> is not active. You have to <a href='options-general.php?page=$locan_dirname/local-analytics.php'>enter</a> your Analytics Account ID.<br/>";
	}
	if(!file_exists($locan_localfile))$error .= "<em title='$locan_localfile'>local-urchin.js</em> does not exist. Please create it.";
	elseif(!is_writable($locan_localfile))$error .= "<em title='$locan_localfile'>local-urchin.js</em> is not writable. Please chmod it 666.";
	if(!empty($error))echo "<div id='locan-warning' class='updated fade-ff0000'><p><strong>$error</strong><a href='#' onclick='document.getElementById(\"locan-warning\").style.display=\"none\";return false;' style='float:right;'>Hide</a></p></div><script type='text/javascript'>
	function appendAfter(n1, n2){
		n1 = document.getElementById(n1);
		n2 = document.getElementById(n2);
		if(n1.parentNode){ 
			if(n1.nextSibling)n1.parentNode.insertBefore(n2, n1.nextSibling);
			else n1.parentNode.appendChild(n2);
		}
	}
	addLoadEvent(function(){
		appendAfter('submenu', 'locan-warning');
		document.getElementById('adminmenu').style['marginBottom'] = '0em';
		document.getElementById('locan-warning').style.top = '0em';
		document.getElementById('locan-warning').style.position = 'static';
	});
</script>
<style type='text/css'>
		#adminmenu { margin-bottom: 6em; }
		#locan-warning { position: absolute; top: 6em; }
		</style>
";
}

function locan_admin_print_scripts(){
	global $locan_dirname;
	if(get_option('locan_track_admin')){
		locan_insert_code();
	}
	if(isset($_GET['page']) && ($_GET['page'] == LOCAL_ANALYTICS_PATH)){
		wp_register_script('local-analytics-js', "/wp-content/plugins/$locan_dirname/local-analytics-js.php", false, '1.0');
		wp_print_scripts(array('dbx', 'local-analytics-js'));
		echo '<style type="text/css">
#tipbox{display:none;position:absolute;border:2px solid black;background-color:gray;color:white;text-align:justify;font-style:italic;padding:10px;width:300px;font-family:arial, verdana, sans-serif;}
.hidden{display:none;}
.tbTitle{font-weight:bold;text-align:center;width:100%;display:block;}
.inputField{width:350px;}
</style>';
	}
}
function locan_list_2_array($lst){
	$arr1 = explode(',', trim($lst, ','));
	$arr2 = array();
	foreach($arr1 as $ar){
		$ar = trim($ar);
		if(!empty($ar))$arr2[] = $ar;
	}
	return $arr2;
}

function locan_admin_options_page(){
	global $locan_dirname, $locan_localfile;
	?>
	<div id="tipbox">&nbsp;</div>
	<div class="wrap">
	<h2>Local Analytics v<?php echo(LOCAL_ANALYTICS_VERSION);?></h2>
	<div id="advancedstuff" class="dbx-group" >
	<?php
	if($_POST['process'] == 1){
		update_option('locan_use_gzip', $_POST['use_gzip']);
		update_option('locan_disable_tracker', $_POST['disable_tracker']);
		update_option('locan_cache_time', $_POST['cache_time']);
		update_option('locan_uacct', $_POST['uacct']);
		update_option('locan_enable_caching', $_POST['enable_caching']);
		update_option('locan_track_admin', $_POST['track_admin']);
		update_option('locan_track_users', $_POST['track_users']);
		update_option('locan_track_userlevel', $_POST['track_userlevel']);
		update_option('locan_track_ads', $_POST['track_ads']);
		update_option('locan_ads_prefix', $_POST['ads_prefix']);
		$trackedExtensions = locan_list_2_array($_POST['tracked_extensions']);
		if(empty($trackedExtensions)){
			update_option('locan_track_downloads', '');
			update_option('locan_tracked_extensions', '');
		}else{
			array_walk($trackedExtensions, create_function('&$ext', '$ext = "\." . $ext;'));
			$trackedExtensions = implode('|', $trackedExtensions);

			update_option('locan_track_downloads', $_POST['track_downloads']);
			update_option('locan_tracked_extensions', $trackedExtensions);
		}
		update_option('locan_download_prefix', '/' . trim($_POST['download_prefix'], '/'));
		update_option('locan_track_external', $_POST['track_external']);
		$internalDomains = locan_list_2_array($_POST['internal_domains']);
		update_option('locan_internal_domains', implode('|', $internalDomains));
		update_option('locan_external_prefix', '/' . trim($_POST['external_prefix'], '/'));
		update_option('locan_track_mailto', $_POST['track_mailto']);
		update_option('locan_mailto_prefix', '/' . trim($_POST['mailto_prefix'], '/'));
		update_option('locan_extra_code', trim($_POST['extra_code']));
		echo "<div id='message' class='updated fade-00ff00'><p><strong>Options updated!</strong></p></div>";
	}elseif($_POST['process'] == 2){
		$ar_locan_option = array('locan_localfile', 'locan_use_gzip', 'locan_disable_tracker', 'locan_enable_caching', 'locan_cache_time', 'locan_uacct', 'locan_track_admin', 'locan_track_users', 'locan_track_userlevel', 'locan_track_ads', 'locan_ads_prefix','locan_track_downloads', 'locan_tracked_extensions', 'locan_download_prefix', 'locan_track_external', 'locan_internal_domains', 'locan_external_prefix', 'locan_track_mailto', 'locan_mailto_prefix', 'locan_extra_code');
		$delete_success = true;
		if($_POST['uninstall_accept'] == 1){
			foreach($ar_locan_option as $locan_option){
				$delete_success = $delete_success && delete_option($locan_option);
			}
			echo '<div id="message1" class="updated fade-00ff00"><p><strong>Local Analytics settings were removed successfully.</strong></p></div>';

			$deactivate_url = 'plugins.php?action=deactivate&amp;plugin=' . LOCAL_ANALYTICS_PATH;
			if(function_exists('wp_nonce_url')) { 
				$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-plugin_' . LOCAL_ANALYTICS_PATH);
			}
			?>
			<div class="wrap">
			<h2>Uninstall Local Analytics</h2>
			<p><a href="<?php echo $deactivate_url;?>">Click here</a> to deactivate and complete Local Analytics uninstallation.</p>
			</div>
			<?php
		}else{
			?>
			<div id="message1" class="updated  fade-ff0000"><p><strong>Error: You have to select the checkbox to uninstall Local Analytics.</strong></p></div>
			<?php
			locan_admin_uninstall_show();
		}
		echo '</div></div>';
		return;
	}
	locan_admin_options_show();
	echo '</div></div>';
}

function locan_admin_options_show(){
	global $locan_roles;
	$trackedExtensions = str_replace('\.', '', get_option('locan_tracked_extensions'));
	$trackedExtensions = str_replace('|', ',', $trackedExtensions);
	$internalDomains = str_replace('|', ',', get_option('locan_internal_domains'));
	preg_match('/www\.(.*)/i', $_SERVER['SERVER_NAME'], $server);
	?>
	<form id="wp_form" name="frmLA" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<div id="locan-options" class="dbx-group" >
		<fieldset class="dbx-box">
		<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">General Options</h3></div>
		<div class="dbx-c-ontent-wrapper">
		<div class="dbx-content">
			<table cellspacing="0" cellpadding="5" style="width:100%;" class="editform">
			<tr><th scope="row" style="width:220px;">Analytics Account ID [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Your Analytics Account ID is a unique identifier in the format <strong>UA-XXXXXXX-X</strong>. You can get your Account ID from your <a href="https://www.google.com/support/googleanalytics/bin/answer.py?answer=55603">tracking code</a>. In your tracking code, look for a line similar to <br />_uacct = "<span style="color:maroon;">UA-11111-1</span>";<br/> In the above code <span style="color:maroon;">UA-11111-1</span> is the Account ID.</span></th><td><input type="text" name="uacct" value="<?php echo get_option('locan_uacct');?>" size="15" maxlength="15"/></td></tr>
			<tr><th scope="row">Disable Tracking [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Use this option to disable tracking temporarily.</span></th><td><input type="checkbox" id="disable" name="disable_tracker" <?php echo (get_option('locan_disable_tracker') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="disable">Disable Local Analytics tracking.</label></td></tr>
			<tr><th scope="row">Enable Caching [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">If caching is enabled, <span style="color:maroon;">ga.js</span> will be periodically downloaded to your server and served. This speeds up your page loading and also prevents most of the adblockers from blocking the file. <br /><strong>Enabling this option is recommended</strong>.</span></th><td><input type="checkbox" id="enable_caching" name="enable_caching" <?php echo (get_option('locan_enable_caching') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="enable_caching">Enable local caching of <em>ga.js</em>.</label></td></tr>
			<tr><th scope="row">Cache time [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">How long should the local file be cached (in hours).</span></th><td><input type="text" name="cache_time" value="<?php echo get_option('locan_cache_time');?>" size="15" maxlength="4"/></td></tr>
			<tr><th scope="row">Enable Gzip [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">This option should be disabled if your server is using Zlib Compression for Javascript files.</span></th><td><input type="checkbox" id="use_gzip" name="use_gzip" <?php echo (get_option('locan_use_gzip') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="use_gzip">Use mod_gzip to compress the Javascript file.</label></td></tr></table>
		</div></div></fieldset>

		<fieldset class="dbx-box">
		<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">Tracking Options</h3></div>
		<div class="dbx-c-ontent-wrapper">
		<div class="dbx-content">
			<table border="0" cellspacing="0" cellpadding="5" style="width:100%;" class="editform">
			<tr><th scope="row" style="width:220px;">Track Logged In Users</th><td><input type="checkbox" id="track_users" name="track_users" <?php echo (get_option('locan_track_users') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="track_users">Track logged in users with User level less than the ignore value below.<br /></label></td></tr>
			<tr><th scope="row">Max. Tracked User Level [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Logged in users with higher user levels will not be tracked, if <strong>Track Logged In Users</strong> is enabled.<br />Select <span style="color:maroon;">Administrator</span> to track all users.</span></th><td><select name="track_userlevel">
			<?php
			$track_userlevel = get_option('locan_track_userlevel');
			foreach($locan_roles as $role){
				if($role[1] == $track_userlevel){
					echo "<option selected='selected' value='$role[1]'>$role[0]</option>";
				}else{
					echo "<option value='$role[1]'>$role[0]</option>";
				}
			}
			?></select></td></tr>
			<tr><th scope="row">Track Admin Panel [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">This option requires <strong>Track Logged In Users</strong> to be enabled.</span></th><td><input type="checkbox" id="track_admin" name="track_admin" <?php echo (get_option('locan_track_admin') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="track_admin">Track visits to the Administration Panels.</label></td></tr>
			<tr><th scope="row">Track Ads[<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Local Analytics can track clicks on Adsense and YPN ads. Advanced details are not available, just the number of clicks are recorded.</span></th><td><input type="checkbox" id="track_ads" name="track_ads" <?php echo (get_option('locan_track_ads') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="track_ads">Track clicks on Adsense and YPN ads.</label></td></tr>
			<tr><th scope="row">Ad Click Prefix [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Adds this prefix to all clicks on ads for easy analysis.</span></th><td><input type="text" name="ads_prefix" class="inputField" value="<?php echo get_option('locan_ads_prefix');?>" size="40"/></td></tr>
			<tr><th scope="row">Track Outgoing Clicks [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Enable this option to track clicks on external links.</span></th><td><input type="checkbox" id="track_external" name="track_external" <?php echo (get_option('locan_track_external') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="track_external">Track visits to external sites.<br /></label></td></tr>
			<tr><th scope="row">Internal Domains [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Links to these domains won't be considered as external.</span></th><td><input type="text" name="internal_domains" class="inputField" value="<?php echo $internalDomains;?>" size="40"/></td></tr>
			<tr><th scope="row">External Prefix [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Adds this prefix to all outgoing clicks for easy analysis.</span></th><td><input type="text" name="external_prefix" class="inputField" value="<?php echo get_option('locan_external_prefix');?>" size="40"/></td></tr>
			<tr><th scope="row">Track Downloads [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Enable this option to track internal clicks on filetypes specified below.</span></th><td><input type="checkbox" id="track_downloads" name="track_downloads" <?php echo (get_option('locan_track_downloads') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="track_downloads">Track internal file downloads<br /></label></td></tr>
			<tr><th scope="row">Download Extensions [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Outgoing clicks on these these filetypes will be tracked as downloads. Separate extensions using comma ( , ).</span></th><td><input type="text" name="tracked_extensions" class="inputField" value="<?php echo $trackedExtensions;?>" size="40"/></td></tr>
			<tr><th scope="row">Download Prefix [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Adds this prefix to all tracked downloads for easy analysis.</span></th><td><input type="text" name="download_prefix" class="inputField" value="<?php echo get_option('locan_download_prefix');?>" size="40"/></td></tr>
			<tr><th scope="row">Track MailTo</th><td><input type="checkbox" id="track_mailto" name="track_mailto" <?php echo (get_option('locan_track_mailto') == 1) ? 'checked="checked"' : '';?> value="1" /> <label for="track_mailto">Track clicks on email(mailto:) links.</label></td></tr>
			<tr><th scope="row">MailTo Prefix [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Adds this prefix to all clicks on email links for easy analysis.</span></th><td><input type="text" name="mailto_prefix" class="inputField" value="<?php echo get_option('locan_mailto_prefix');?>" size="40"/></td></tr>
			<tr><th scope="row" valign="top">Additional Tracking Code [<a class="locanHelpTip" href="#">?</a>]<span class="hidden">Enter any additional tracking code which you wish to insert within the script. <br />For example to <a href="http://www.google.com/support/analytics/bin/answer.py?hl=en&amp;answer=27268">track all sub domains</a> of <?php echo $server[1];?> in a single profile, enter <br /><code style="color:maroon;">pageTracker._setDomainName("<?php echo $server[1];?>");</code></span></th><td><textarea class="inputField" name="extra_code" rows="3" cols="40"><?php echo get_option('locan_extra_code');?></textarea></td></tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="5" style="width:100%;">
			<tr><td><center>
			<input type="hidden" name="process" value="1" />
			<input type="submit" value="Save Options" style="width:150px;" /> &nbsp; <input type="button" style="width:150px;" name="cancel" value="Cancel" class="button" onclick="javascript:history.go(-1)" /> </center></td></tr>
			</table>
		</div></div></fieldset>
	</div>
	</form>
		<div id="locan-ads" class="dbx-group" >
		<fieldset class="dbx-box">
		<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">Promotions and Donations</h3></div>
		<div class="dbx-c-ontent-wrapper">
		<div class="dbx-content"><center>
			<script type="text/javascript"><!--
			google_ad_client = "pub-0875376866576215";
			//Local Analytics Referral
			google_ad_slot = "4918999280";
			google_ad_width = 110;
			google_ad_height = 32;
			google_cpa_choice = ""; // on file
			//--></script>
			<script type="text/javascript"
			src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>
			<script type="text/javascript"><!--
			google_ad_client = "pub-0875376866576215";
			//Local Analytics Referral - Firefox
			google_ad_slot = "3394844436";
			google_ad_width = 110;
			google_ad_height = 32;
			google_cpa_choice = ""; // on file
			//--></script>
			<script type="text/javascript"
			src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>
			<form style="display:inline;" name="_xclick" action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_xclick">
			<input type="hidden" name="business" value="joycekbabu@gmail.com">
			<input type="hidden" name="item_name" value="Local Analytics Donation">
			<input type="hidden" name="currency_code" value="USD">
			<input type="image" src="http://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
			</form>
		</center></div></div></fieldset></div>
	<?php
	locan_admin_uninstall_show();
}
function locan_admin_uninstall_show(){
?>

<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
<div id="locan-uninstall" class="dbx-group" >
<fieldset class="dbx-box">
<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">Uninstall Local Analytics</h3></div>
<div class="dbx-c-ontent-wrapper">
<div class="dbx-content">
	<p>Deactivating <strong>Local Analytics</strong> does not remove any of the options saved by the plugin in the database. To completely remove <strong>Local Analytics</strong>, use the <em>Uninstall</em> button below.</p>
	<p style="color:red;"><strong>WARNING:</strong> Uninstallation is non reversible. Please backup your WordPress database, if you wish to revert back later.</p>
		<input type="checkbox" id="uninstall_accept" name="uninstall_accept" value="1" /> <label for="uninstall_accept">I know what I am doing. Please uninstall the plugin.</label>
		<input type="hidden" name="process" value="2" />
		<center><input type="submit" value="Uninstall" style="width:150px;" /></center>
</div></div></fieldset>
</div></form>
<?php
}
