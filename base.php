<?php

	/*
		Plugin Name: Thumbnailr
		Plugin URI: http://www.thumbnailr.com/
		Description: Recent images from your Thumbnailr site on your Wordpress blog
		Version: 0.10
		Author: Twan
	 */

	if (!defined('COZYUNI_DIR')) {
		$cozyUniDir="/usr/local/apache/htdocs/cozyuniwp/";
		if(!file_exists($cozyUniDir)){
			$cozyUniDir=plugin_dir_path(__FILE__) ;
		}
		define('COZYUNI_DIR', $cozyUniDir );
	}


	//const
	if (!defined('COZYUNI_RESTURL')) {
		define('COZYUNI_RESTURL',  "http://rest.thumbnailr.com/dash/rest");
	}


	//widgets
	include_once "thumbnailr-recentimages-widget.php";
	add_action('widgets_init', 'thumbnailr_widgets_init');
	function thumbnailr_widgets_init() {
		register_widget('Thumbnailr_RecentImages_Widget');
	}

	//base cozyuni functionality
	require_once COZYUNI_DIR ."cozyuni-data.php";
	require_once COZYUNI_DIR ."cozyuni-func.php";
	require_once COZYUNI_DIR ."cozyuni-misc.php";
	require_once COZYUNI_DIR ."cozyuni-cache.php";

	//other
	if (is_admin()) {
		include_once(plugin_dir_path(__FILE__) . "admin.php");
	}

	//plugin links
	add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'thumbnailr_plugin_action_links');
	function thumbnailr_plugin_action_links($links) {
		write_log("thumbnailr_plugin_action_links");
		$links[] = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=thumbnailr-options')) . '">Settings</a>';
		$links[] = '<a href="http://www.thumbnailr.com" target="_blank">Thumbnailr</a>';
		return $links;
	}
