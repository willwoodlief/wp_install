<?php
//require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/Input.php";
$issues         = [];
$messages       = [];
$debug_messages = [];

try {
	$secret = $key = $base_url = $username = $password = '';
    if ($_POST) {
        if (isset($_POST['submit-download'])) {
            //download the zip
            if (!isset($_POST['test-secret-in-plugin'])) {
                throw new Exception("Need to Put in a Test Secret. It can be any text");
            }

	        if (!isset($_POST['test-key-in-plugin'])) {
		        throw new Exception("Need to Put in a Test Key. It can be any text");
	        }
            $secret = $_POST['test-secret-in-plugin'];
            $key = $_POST['test-key-in-plugin'];


	        $file = modify_plugin_zip($key,$secret);

                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename=' . basename($file));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                ob_clean();
                flush();
                readfile( $file );
                exit;

        } else {

	        if (!isset($_POST['test-secret-in-plugin'])) {
		        throw new Exception("Need to Put in a Test Secret. It can be any text");
	        }

	        if (!isset($_POST['test-key-in-plugin'])) {
		        throw new Exception("Need to Put in a Test Key. It can be any text");
	        }
	        $secret = $_POST['test-secret-in-plugin'];
	        $key = $_POST['test-key-in-plugin'];

	        if (!isset($_POST['wp-admin-url'])) {
		        throw new Exception("Need to have the url to the Wordpress install");
	        }

	        $base_url = $_POST['wp-admin-url'];

	        $base_url = rtrim($base_url,"/").'/';  //make sure trailing slash

	        $login_url = $base_url.'wp-login.php';
	        $admin_url = $base_url.'wp-admin/';

	        if (!isset($_POST['wp-admin-user'])) {
		        throw new Exception("Need to have the LOGIN NAME to access the Wordpress");
	        }

	        if (!isset($_POST['wp-admin-password'])) {
		        throw new Exception("Need to have the PASSWORD to access the Wordpress ");
	        }

	        $username = $_POST['wp-admin-user'];
	        $password = $_POST['wp-admin-password'];
	        $ch = log_into_word($login_url,$admin_url,$username,$password);
	        $field_info = get_plugin_upload_inputs($ch,$admin_url);
	        $fields = $field_info['inputs'];
	        $url = $field_info['action'];
	        $file_input_name = $field_info['file_input'];


	        $zip_path = modify_plugin_zip($key,$secret);
	        $file_array = [$file_input_name=>$zip_path];

	        $uploaded_html =  upload_file_and_data($ch,$url,$file_array,$fields);

	        // print $uploaded_html;

	        $activation_part = htmlspecialchars_decode(find_activation_link($uploaded_html));
	        $activation_link = $admin_url . $activation_part;
	        //print "activation link is : ". $activation_link . "<hr>";
	        $ch->curl_helper($activation_link,$http_code,null,false,'text',false);
	        $messages[] = "Installed and Activated Plugin to the Wordpress Site";
        }

    }




} catch ( Exception $e ) {
	$issues[] = $e->getMessage() . "\n" . $e->getTraceAsString();
}

?>

<!DOCTYPE html>
<!--suppress JSCheckFunctionSignatures -->
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Test Auto Install for Wordpress</title>
	<style>
		span.wait-progress {
			display: none;
		}

		ul.unprocessed-emails li {
			color: red;
		}

		div.unprocessed-emails {
			display: none;
		}

		ul.processed-emails li {
			color: green;
		}

		div.processed-emails {
			display: none;
		}


		ul.ignored-emails li {
			color: black;
		}

		div.ignored-emails {
			display: none;
			margin-top: 2em;
		}


	</style>

	<script src="https://code.jquery.com/jquery-3.3.1.min.js"
	        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
	      integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"
	      integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
	        integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
	        crossorigin="anonymous"></script>

	<!--	fontawesome!-->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css"
	      integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">

	<script src="js/timestamp_to_locale.js"></script>
    <script src="js/talk.js"></script>
</head>
<body>
<?php print_alerts( $messages, 'success' ) ?>
<?php print_alerts( $issues, 'danger' ) ?>
<div class="container">
	<div class="row" style="margin-bottom: 2em">
		<div class="col-sm-12" style="text-align: center">
			<h1 class="display-2" >Wordpress Install Demo</h1>
            <span>This will customize a simple plugin called <b>Install Test</b><br> which will display a message on the top of the admin page</b></span>
            <br>
            <span>This script will automatically install the plugin, or you can download the customize plugin as a zip</span>
		</div>
	</div>

	<div class="row" style="">

		<div class="col-sm-4 col-sm-offset-4 ">
			<div class="form-group">
				<form action="install_demo.php" method="post" enctype="multipart/form-data">
					<div style="margin-bottom: 2em">

					</div>
                    <div class="form-group">
                        <label for="wp-admin-url">Wordpress URL</label>
                        <input  value="<?= $base_url ?>" type="url" class="form-control input-lg" id="wp-admin-url" name="wp-admin-url" autocomplete="url" placeholder="Full url to the wordpress root">
                        <span style="font-size: small">This assumes standard login and admin url schemes</span>
                    </div>
                    <div class="form-group">
                        <label for="wp-admin-user">User Name</label>
                        <input  value="<?= $username ?>" type="text" class="form-control input-lg" id="wp-admin-user" name="wp-admin-user" autocomplete="username" placeholder="Login Admin Name">
                    </div>
                    <div class="form-group">
                        <label for="wp-admin-password">Admin Password</label>
                        <input  value="<?= $password ?>" type="password" class="form-control input-lg" id="wp-admin-password" name="wp-admin-password" autocomplete="current-password" placeholder="Login Password">
                    </div>

                    <div class="form-group">
                        <label for="wp-admin-password">Test Key For Demo Plugin</label>
                        <input  value="<?= $key ?>" type="text" class="form-control input-lg" id="test-key-in-plugin" name="test-key-in-plugin" autocomplete="test-key" placeholder="This Will Be Echoed in the Plugin">
                    </div>

                    <div class="form-group">
                        <label for="wp-admin-password">Test Secret For Demo Plugin</label>
                        <input value="<?= $secret ?>" type="text" class="form-control input-lg" id="test-secret-in-plugin" name="test-secret-in-plugin" autocomplete="test-secret" placeholder="Also Echoed in the Plugin">
                    </div>

                    <div class="form-group">
                        <button type="submit" name="submit-wp-admin-info" class="btn btn-success btn-block full-width form-control" style="" value="1">
                            Start Plugin Install
                        </button>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="submit-download" class="btn btn-primary btn-block full-width form-control" style="" value="1">
                            Download the Zip Directly
                        </button>
                    </div>



				</form>
			</div>
		</div>
	</div>


</div>
</body>
</html>

