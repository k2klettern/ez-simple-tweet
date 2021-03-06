<?php
/*
Plugin Name: EZ Simple Tweet
Plugin URI: http://zeidan.info/linkedin_oauth-wordpress-plugin/
Description: Generates a Simple Last Tweet Text or more tweets you can Place anywhere on your site
Version: 0.1
Author: Eric Zeidan
Author URI: http://zeidan.es
License: GPL2
*/

/*  Copyright 2015 Eric Zeidan  (email : k2klettern@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

 
require_once 'class_eztweet.php';
require_once 'inc/vendor/twitteroauth/autoload.php';

//creamos la instancia para poder utilizarlo 
$eztweet = new eztweet_plugin();

/**
 * Functions for redirect on activation and include action on activation of plugin
 */
register_activation_hook(__FILE__, array($eztweet, "ezt_activate"));