<?php
/**
 * @package Install_Test
 * @version 1.0
 */
/*
Plugin Name: Install Test
Plugin URI: http://gokabam.com/plugins/hello-dolly/
Description: This plugin just shows some fake credentials in the top admin screen. It reads it from an attached file
Author: Will Woodlief
Version: 1.0
*/



// This just echoes the chosen line, we'll position it later
function install_test() {

	// Read JSON file
	$da_file = plugin_dir_path( __FILE__ ) . 'test_data.json';
	$json = file_get_contents($da_file);

    //Decode JSON
	$json_data = json_decode($json,true);
	$install_test_key = $json_data['key'];
	$install_test_secret = $json_data['secret'];
	echo "<table id='install-test' style='display: inline-table;background-color: black;color:white'><tr><td colspan='2'>Install Test Data</td></tr><tr><td><b>Key:</b></td><td> $install_test_key</td></tr><tr><td><b>Secret:</b></td><td> $install_test_secret</td></tr></table>";
}

// Now we set that function up to execute when the admin_notices action is called
add_action( 'admin_notices', 'install_test' );

// We need some CSS to position the paragraph
function install_test_css() {
	// This makes sure that the positioning is also good for right-to-left languages
	$x = is_rtl() ? 'left' : 'right';

	echo "
	<style type='text/css'>
	#install-test {
		padding-$x: 15px;
		padding-top: 5px;		
		margin: 0;
		font-size: 16px;
	}
	</style>
	";
}

add_action( 'admin_head', 'install_test_css' );

