<?php
	/**
	 * Thumbnailr for Wordpress; thumbnailr.com
	 */


	function thumbnailr_admin_clearCache(){
		write_log("thumbnailr_clearCache");

		cozyuni_clearCached("recentimages");

		cozyuni_setAdminMsg("Cache cleared");
	}