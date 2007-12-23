<?php
/*
+-------------------------------------------------------------------------------+
|																				|
|	WordPress Plugin : Local Analytics											|
|	Copyright (c) 2007 Joyce Babu (email : contact@joycebabu.com)				|
|																				|
|	Copyright																	|
|	- Joyce Babu																|
|	- http://www.joycebabu.com/													|
|	- You are free to do anything with this script. I will						|
|		always appreciate if you CAN give a link back to 						|
|		http://www.joycebabu.com/blog/speed-up-google-analytics-using-simple-php-script.html					|
|																				|
|	File Information:															|
|	- Update and return the local ga.js											|
|	- /wp-content/plugins/local-analytics/local-urchin-js.php					|
|																				|
+-------------------------------------------------------------------------------+

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

# Include wp-config.php
@require('../../../wp-config.php');

// Remote file to download
$remoteFile = ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://www.' : 'https://ssl.') . 'google-analytics.com/ga.js';
// Local File name. Must be made writable
$localFile = get_option('locan_localfile');
// Time to cache in hours
$cacheTime = ($a = intval(get_option('locan_cache_time'))) ? $a : 24;
// Connection time out
$connTimeout = 10;
// Use Gzip compression
$useGzip = get_option('locan_use_gzip') ? true : false;

if($useGzip){
	ob_start('ob_gzhandler');
}
cache_javascript_headers();
if(file_exists($localFile) && (time() - ($cacheTime * 3600) < filemtime($localFile))){
	readfile($localFile);
}else{
	$url = parse_url($remoteFile);
	$host = $url['host'];
	$path = isset($url['path']) ? $url['path'] : '/';

	if (isset($url['query'])) {
		$path .= '?' . $url['query'];
	} 

	$port = isset($url['port']) ? $url['port'] : '80';

	$fp = @fsockopen($host, '80', $errno, $errstr, $connTimeout ); 

	if(!$fp){
		// On connection failure return the cached file (if it exist)
		if(file_exists($localFile)){
			readfile($localFile);
		}
	}else{
		// Send the header information
		$header = "GET $path HTTP/1.0\r\n";
		$header .= "Host: $host\r\n";
		$header .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6\r\n";
		$header .= "Accept: */*\r\n";
		$header .= "Accept-Language: en-us,en;q=0.5\r\n";
		$header .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
		$header .= "Keep-Alive: 300\r\n";
		$header .= "Connection: keep-alive\r\n";
		$header .= "Referer: http://$host\r\n\r\n";

		fputs($fp, $header);

		$response = ''; 
		// Get the response from the remote server
		while($line = fread($fp, 4096)){ 
			$response .= $line;
		} 

		// Close the connection
		fclose( $fp );

		// Remove the headers
		$pos = strpos($response, "\r\n\r\n");
		$response = substr($response, $pos + 4);

		// Return the processed response
		echo $response;

		// Save the response to the local file
		if(!file_exists($localFile)){
			// Try to create the file, if doesn't exist
			fopen($localFile, 'w');
		}
		if(is_writable($localFile)) {
			if($fp = @fopen($localFile, 'w')){
				fwrite($fp, $response);
				fclose($fp);
			}
		}
	}
}
?>