<?php




require_once realpath( dirname( __FILE__ ) ) . "/JsonHelper.php";

class Input {

    /**
     * @const string input::MUST_BE_FILE
     * is used to force finding files in
     * @see get() for how to use
     */
    const MUST_BE_FILE = 'the param must be an uploaded file, it cannot be anything else ';


    /**
    * @const string input::THROW_IF_MISSING
    * is used to throw an exception if the param is missing from get post or file
    * @see get() for how to use
    */
    const THROW_IF_MISSING = 'the param must exist - Will v2.2@!unique';


    /**
     * @const string input::THROW_IF_EMPTY
     * is used to throw an exception if the param is missing from get post or file
     * @see get() for how to use
     */
    const THROW_IF_EMPTY = 'the param must exist and must not be empty - Will v2.2@!unique';

	public static function exists($param ){

	    $b_set = empty($_POST[$param]) && empty($_GET[$param]);
        return !$b_set;

	}

	private static $post_only = false;

    /**
     * if set to true, then only post and not get will be read
     * @param bool $b_what, default true
     * @return void
     */
	public static function onlyPost($b_what=true) {
	    self::$post_only = $b_what;
    }


    public static function isPost() {
	    if ($_POST) {return true;}
	    return false;
    }

    /**
     * @param $item <p>
     *   The key in the post or get
     * </p>
     * @param mixed $alternate <p>
     *   If the key value is missing then this is returned instead
     *   If cast is used, then this alternate is cast as well to the data type, it must be compatible
     *   If this is set to @see Input::MUST_BE_FILE then the behavior completely changes
     *    and an InvalidArgumentException is thrown if the name is not an uploaded file
     *    Also a check is made to see if the file is not 0 size
     *   If this is set to @see Input::THROW_IF_MISSING then
     *    an InvalidArgumentException is thrown if cannot find the item in get post or files
     *   if this is set to @see Input::THROW_IF_EMPTY then will throw if exists but is not filled
     * </p>
     * @param bool $b_sanitize <p>
     *   default false
     *   if true then the data is trimmed and has special chars converted
     * </p>
     * @param bool $cast <p>
     *   default this is not used
     *   else must be one of the following strings
     *    boolean,integer,float,text,json,date_time
     * </p>
     *
     * @return array|mixed|string
     * @throws Exception if cannot cast (try to cast array)
     * @throws InvalidArgumentException if it cannot find the name
     */
	public static function get($item,$alternate='',$b_sanitize=false,$cast = false){

        $b_throw_arg = false;
        if (($alternate === self::THROW_IF_MISSING) || ($alternate === self::THROW_IF_EMPTY)) {
            $b_throw_arg = true;
        }

	    if ($alternate === self::MUST_BE_FILE) {
            if(isset($_FILES[$item])) {
                //check not 0 size
                $filesize =  $_FILES[$item]['size'];
                if ($filesize <= 0) {
                    throw new InvalidArgumentException("The file uploaded is 0 size. Its uploaded as the name $item");
                }
                return $_FILES[$item];
            } else {
                throw new InvalidArgumentException("Cannot find $item in Files");
            }
        }

	    $unique_flag = 'a unique flag 0xFAFA';

        //we will test later to see if this value has changed from a value set here
        $pre_cast = $unique_flag; // have to use a value not normally set anywhere because pre cast can be the alternative in the params
	    if (isset($_POST[$item]) ) {
	        if ($alternate === self::THROW_IF_EMPTY) {
                $pre_cast =  self::sanitize($_POST[$item],$b_sanitize,null);
                if (is_null($pre_cast)) {
                    throw new InvalidArgumentException("Cannot find $item in post");
                }
            } else {
                $pre_cast =  self::sanitize($_POST[$item],$b_sanitize,$alternate);
            }

		} else if(isset($_GET[$item])) {

	        //if post only, and not in post above, then just set to alternate, else try to get value from get
	        if (self::$post_only) {
                $pre_cast = $alternate;
            } else {
                if ($alternate === self::THROW_IF_EMPTY) {
                    $pre_cast =  self::sanitize($_GET[$item],$b_sanitize,null);
                    if (is_null($pre_cast)) {
                        throw new InvalidArgumentException("Cannot find $item in get");
                    }
                } else {
                    $pre_cast = self::sanitize($_GET[$item], $b_sanitize, $alternate);
                }

            }

        } else if(isset($_FILES[$item])){
                return $_FILES[$item];
		} else {
	        if ($b_throw_arg) {
                throw new InvalidArgumentException("Cannot find $item in either get or post or files");
            }
        }

		if ($pre_cast === $unique_flag) {
            if ($cast) {
                $ret = JsonHelper::dataFromString($cast,$alternate);
            } else {
                $ret = $alternate;
            }
        } else {
            if ($cast) {
                $ret = JsonHelper::dataFromString($cast,$pre_cast);
            } else {
                $ret = $pre_cast;
            }

        }

        return $ret;

	}

	public static function sanitize($input,$b_sanitize,$alternate=''){
	   if (is_array($input)) {
	       if (empty($alternate)) {
               $alternate = [];
           }
	       $what = [];
	       foreach ($input as $key => $hmm) {
	           if (is_array($hmm)) {
	               $what[$key] = self::sanitize($hmm,$b_sanitize,[]);
               } else {
                   $strumm = strval($hmm);
                   if ($b_sanitize) {
                       $element =  trim(htmlentities($strumm, ENT_QUOTES, 'UTF-8'));
                   } else {
                       $element =  trim($strumm);
                   }
                   $what[$key] = $element;
               }



           }
       } else {
	       if (empty($input)) {
	           $input = null;
           }

	       $string = strval($input);
           if ($b_sanitize) {
               $what =  trim(htmlentities($string, ENT_QUOTES, 'UTF-8'));
           } else {
               $what =  trim($string);
           }
       }

        if (empty($what)) {
	        return $alternate;
        }
        return $what;

	}



}
