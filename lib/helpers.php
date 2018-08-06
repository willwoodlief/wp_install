<?php
require_once realpath( dirname( __FILE__ ) ) . "/../vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/../config/envs.php";
require_once realpath( dirname( __FILE__ ) ) . "/JsonHelper.php";
require_once realpath( dirname( __FILE__ ) ) . "/CurlHelper.php";

use Sunra\PhpSimple\HtmlDomParser;

/**
 * @param $login_url
 * @param $admin_url
 * @param $username
 * @param $password
 * @param bool $b_debug
 * @param bool $b_keep_cookies
 * @throws CurlHelperException
 * @return CurlHelper
 */
function log_into_word($login_url,$admin_url,$username,$password,$b_debug,$b_keep_cookies) {

	$ch = new CurlHelper(!$b_keep_cookies);
	$ch->b_debug = $b_debug;

	//set test cookie, WP will try to read it in the next one to see if the browser supports cookies
	$ch->curl_helper($login_url,$http_code,null,false,'text');



	$params = [
		'log'=>	$username,
		'pwd' => $password,
		'rememberme' => 'forever',
		'redirect_to' => $admin_url,
		'testcookie' => 1,
		'submit' => 'login'
	];

	 $ch->curl_helper($login_url,$http_code,$params,true,'text',false);
	 return $ch;
}

/**
 * Returns form info used to upload a zip
 * @param $ch CurlHelper
 * @param $admin_url string
 * @return array
 * @throws CurlHelperException
 */
function get_plugin_upload_inputs($ch,$admin_url) {
	$plugin_install_page = $admin_url . 'plugin-install.php';
	$http_code = 0;
	$str = $ch->curl_helper($plugin_install_page,$http_code,null,false,'text',false);
	$html = HtmlDomParser::str_get_html( $str );

	$ret = [];
	$file_name = '';
	// Get the form action
	$form = $html->find('form')[0]; //first form
	foreach ($form->find('input') as $element) {

		if ($element->type === 'file') {
			$file_name = $element->name;
		} else {
			$ret[$element->name] = $element->value;
		}
	}
	$action = $form->action;

	return ['action'=>$action,'inputs'=>$ret,'file_input'=>$file_name];
}

/**
 * @param $key
 * @param $secret
 *
 * @return string
 * @throws Exception
 */
function modify_plugin_zip($key,$secret) {
	$temp_dir = sys_get_temp_dir();
	$dest = $temp_dir.'/'.time() .'_install_test';
	`mkdir $dest`;
	$raw_path = dirname( __FILE__ )."/../plugins/install_test";
	$src = realpath($raw_path);
	`cp -r $src $dest`;

	//create new json string
	$np = ['secret'=>$secret,'key'=>$key];
	$json_string = JsonHelper::toString($np);
	//overwrite /tmp/1533531760_install_test/install_test/test_data.json
	$json_file = $dest.'/install_test/test_data.json';
	$b_what = file_put_contents($json_file,$json_string);
	if ($b_what === false) {
		throw new Exception("could not overwrite $json_file file with $json_string");
	}

	//zip up in parent directory

	$zipname =  $dest.'/install_test.zip';
	$target =  $dest.'/install_test';
	$zip = new ZipArchive;
	if ($zip->open($zipname, ZipArchive::CREATE)!==TRUE) {
		throw new Exception("cannot open <$zipname>\n");
	}

	if ($handle = opendir($target)) {
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != ".." ) {
				$full_entry = $target . '/' . $entry;
				$named_entry = 'install_test' . '/' . $entry;
				$b_ok = $zip->addFile($full_entry,$named_entry);
				if (!$b_ok) {
					throw new Exception("Could not add $entry to the zip file at $zipname");
				}
			}
		}
		closedir($handle);
	}

	$zip->close();

	//return zip path
	return $zipname;
}

/**
 * @param $ch CurlHelper
 * @param $url string
 * @param array $file_names
 * @param array $fields
 * @throws CurlHelperException
 * @return string
 */
function upload_file_and_data($ch,$url,array $file_names,array $fields) {
// data fields for POST request

	$files = array();
	foreach ($file_names as $input_name => $file_path){
		$content = file_get_contents($file_path);
		$pretty_file_name = basename($file_path);
		$files[$input_name] = ['content'=>$content,'file_name'=>$pretty_file_name];
	}


	$boundary = uniqid();
	$delimiter = '-------------' . $boundary;

	$post_data = build_data_files($boundary, $fields, $files);

	$headers = array(
		//"Authorization: Bearer $TOKEN",
		"Content-Type: multipart/form-data; boundary=" . $delimiter,
		"Content-Length: " . strlen($post_data)

	);

	$body =  $ch->curl_helper($url,$http_code,$post_data,true,'text',false,false,false,$headers);

	if ($http_code >= 400 ) {
		throw new Exception($body);
	}
	return $body;
}


function build_data_files($boundary, $fields, $files){
	$data = '';
	$eol = "\r\n";

	$delimiter = '-------------' . $boundary;

	foreach ($fields as $name => $content) {
		$data .= "--" . $delimiter . $eol
		         . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
		         . $content . $eol;
	}


	foreach ($files as $name => $details) {
		$content = $details['content'];
		$file_name = $details['file_name'];
		$data .= "--" . $delimiter . $eol
		         . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file_name . '"' . $eol
		         //. 'Content-Type: image/png'.$eol
		         . 'Content-Transfer-Encoding: binary'.$eol
		;

		$data .= $eol;
		$data .= $content . $eol;
	}
	$data .= "--" . $delimiter . "--".$eol;


	return $data;
}

/**
 * @param $uploaded_html
 *
 * @return string
 * @throws Exception
 */
function find_activation_link($uploaded_html) {
	$html = HtmlDomParser::str_get_html( $uploaded_html );


	$link = $html->find('a.button-primary');
	if (empty($link)) {
		throw new Exception("Cannot find activation link. This can happen if the plugin was already installed or uploaded");
	}

	return $link[0]->href;
}

function normalizePath($path) {
	/** @noinspection PhpDeprecationInspection */
	return array_reduce(explode('/', $path), create_function('$a, $b', '
			if($a === 0)
				$a = "/";

			if($b === "" || $b === ".")
				return $a;

			if($b === "..")
				return dirname($a);

			return preg_replace("/\/+/", "/", "$a/$b");
		'), 0);
}



/**
 * Prints out html for errors
 *
 * Allows messages to be put on screen in a nice way
 * Requires bootstrap js and css to in the page
 * Also, assumes inside a bootstrap container or container-fluid  div
 * @param array $alerts <p>
 *  array of things that will be converted to strings
 * </p>
 * @param string $style <p>
 *   one of danger,warning,info, success
 * </p>
 *
 * @return void
 */
function print_alerts(array $alerts,$style='danger') {


	switch ($style) {
		case 'danger':
			{
				$title = "Error!";
				break;
			}
		case 'warning':
			{
				$title = "Warning";
				break;
			}
		case 'info':
			{
				$title = "Notice";
				break;
			}

		case 'success':
			{
				$title = "Success";
				break;
			}

		default: {
			$style = 'info';
			$title = "Message";
		}
	}

	print "<!-- Generated by print_alerts -->\n";
	print "<div class='row'>\n";
	print "  <div class='col-sm-12 col-md-12 col-lg-12 '>\n";
	foreach ($alerts as $alert) {
		$alert = strval($alert);
		print "    <div class='alert alert-$style alert-dismissible' role='alert'>\n";
		print '      <a href="#" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>'."\n";

		print "      <strong>$title</strong><pre> $alert"."</pre>\n";
		print "    </div>\n";

	}
	print "  </div>\n";
	print "</div>\n";
}

