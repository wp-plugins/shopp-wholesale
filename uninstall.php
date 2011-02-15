<?php
	
	if(!defined('WP_UNINSTALL_PLUGIN')) {
		wp_die("Do not access this file directly.");
	}
	
	//TODO: check delete on uninstall option
	//TODO: drop tables
	//TODO: drop columns
	
	//delete options
	delete_option('sws-settings');
	 
?>