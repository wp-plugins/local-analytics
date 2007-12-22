<?php
/*
+-------------------------------------------------------------------+
|																	|
|	WordPress Plugin : Local Analytics								|
|	Copyright (c) 2007 Joyce Babu (email : contact@joycebabu.com)	|
|																	|
|	Copyright														|
|	- Joyce Babu													|
|	- http://www.joycebabu.com/										|
|	- You are free to do anything with this script. I will			|
|		always appreciate if you CAN give a link back to 			|
|		http://www.joycebabu.com/downloads/local-analytics/			|
|																	|
|	File Information:												|
|	- Javascript Functions used in Local Analytics					|
|	- /wp-content/plugins/local-analytics/local-analytics-js.php	|
|																	|
+-------------------------------------------------------------------+
/*
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
$useGzip = get_option('locan_cache_time') ? true : false;

if($useGzip){
	ob_start('ob_gzhandler');
}
cache_javascript_headers();

?>
//<![CDATA[
addLoadEvent( function() {
	//create new docking boxes manager
	var manager = new dbxManager("local-analytics");
	//create new docking boxes group
	var locan_options = new dbxGroup("locan-uninstall", "vertical", "10", "no", "10", "yes", "closed", "open", "close", "click-down and drag to move this box", "click to %toggle% this box", "use the arrow keys to move this box", ", or press the enter key to %toggle% it", "%mytitle% [%dbxtitle%]");
	var locan_uninstall = new dbxGroup("locan-ads", "vertical", "10", "no", "10", "yes", "open", "open", "close", "click-down and drag to move this box", "click to %toggle% this box", "use the arrow keys to move this box", ", or press the enter key to %toggle% it", "%mytitle% [%dbxtitle%]");
	var locan_uninstall = new dbxGroup("locan-options", "vertical", "10", "no", "10", "yes", "open", "open", "close", "click-down and drag to move this box", "click to %toggle% this box", "use the arrow keys to move this box", ", or press the enter key to %toggle% it", "%mytitle% [%dbxtitle%]");
	var el = document.getElementsByTagName("a");
	var current;
	var length = el.length;
	tipbox = document.getElementById("tipbox");
	tipbox.onmouseover = fadeUp;
	tipbox.onmouseout = fadeDown;
	for(var i=0; i < length; i++){
		current = el[i];
		if(current.className == "locanHelpTip"){
			current.onmouseover = showTip;
			current.onmouseout = fadeDown;
			current.onclick = new Function("return false;");
		}
	}
});

var tipbox, timer;
function showTip(evt){
	//hideTip();
	e = getEvent(evt);
	span = e.src.nextSibling;
	while(!span.className || span.className.indexOf("hidden") == -1)span = span.nextSibling;
	if(span){
		tipbox.style.left = e.x + 5 + "px";
		tipbox.style.top = e.y + "px";
		tipbox.style.zIndex = 5000;
		tipbox.innerHTML = span.innerHTML;
		tipbox.style.display = "block";
		fadeUp();
	}
}
function fadeUp(){
	op = getOpacity(tipbox);
	clearTimeout(timer);
	fadeTip(op, +10);
}
function fadeDown(){
	op = getOpacity(tipbox);
	clearTimeout(timer);
	fadeTip(op, -10);
}
function fadeTip(op, a){
    if(op >= 100 && a > 0) {
		setOpacity(tipbox, 100);
		clearTimeout(timer);
	}else if(op <= 0 && a < 0){
		setOpacity(tipbox, 0);
		tipbox.style.display = "none";
		clearTimeout(timer);
	}else if(a != 0){
		setOpacity(tipbox, op);
		op += a;
		timer = setTimeout("fadeTip(" + op + ", " + a + ")", 30);
	}
}
function getOpacity(el){
	if(window.ActiveXObject && typeof(el.style.filter) == "string"){
		try{v = el.filters.item("DXImageTransform.Microsoft.Alpha").opacity / 100;}catch(e){
			try{v = el.filters.item("alpha").opacity;}catch(e){}
		}
	}else{s = el.style;v = (s.opacity ? s.opacity : s.MozOpacity ? s.MozOpacity : s.KhtmlOpacity) * 100;}
	v = v ? v : 100;
	return v;
}
function setOpacity(el, v){
    if(window.ActiveXObject && typeof(el.style.filter) == "string"){
		el.style.filter = "alpha(opacity=" + v + ")";
		if (!el.currentStyle || !el.currentStyle.hasLayout)el.style.zoom = 1;
	}else{
		v /= 100;
		/*if(v == 1)v = 0.9999;
		else */if(v < 0.00001) v = 0;
		el.style.opacity = v;
		el.style["-moz-opacity"] = v;
		el.style["-khtml-opacity"] = v;
	}
}
function getEvent(evt){
	var e = [];
	evt = evt || window.event;
	e.src = evt.srcElement || evt.target || evt.currentTarget || null;
	e.x = evt.pageX || evt.clientX || 0;
	e.y = evt.pageY || evt.clientY || 0;
	if(e.stopPropagation)e.stopPropagation();else e.cancelBubble = true;
	return e;
}
//]]>