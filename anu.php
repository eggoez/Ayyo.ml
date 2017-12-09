<?php
// To obtains requested URI
function get_uri(){
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		// IIS Mod-Rewrite
		if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		}else if(isset($_SERVER['HTTP_X_REWRITE_URL'])) {
			// IIS-Isapi_Rewrite
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		}else{
			// Use ORIG_PATH_INFO, if there is no PATH_INFO
			if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO'])){
				$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
			}
			// Check duplicated
			if (isset($_SERVER['PATH_INFO']) ) {
				if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']){
					$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
				}else{
					$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
				}
			}
			// Append the query string if it exists and isn't null
			if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
				$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
	}
	return $_SERVER['REQUEST_URI'];
}

/**
 * Translates a number to a short alhanumeric version
 *
 * Translated any number up to 9007199254740992
 * to a shorter version in letters e.g.:
 * 9007199254740989 --> PpQXn7COf
 *
 * specifiying the second argument true, it will
 * translate back e.g.:
 * PpQXn7COf --> 9007199254740989
 *
 * this function is based on any2dec && dec2any by
 * fragmer[at]mail[dot]ru
 * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
 *
 * If you want the alphaID to be at least 3 letter long, use the
 * $pad_up = 3 argument
 *
 * In most cases this is better than totally random ID generators
 * because this can easily avoid duplicate ID's.
 * For example if you correlate the alpha ID to an auto incrementing ID
 * in your database, you're done.
 *
 * The reverse is done because it makes it slightly more cryptic,
 * but it also makes it easier to spread lots of IDs in different
 * directories on your filesystem. Example:
 * $part1 = substr($alpha_id,0,1);
 * $part2 = substr($alpha_id,1,1);
 * $part3 = substr($alpha_id,2,strlen($alpha_id));
 * $destindir = "/".$part1."/".$part2."/".$part3;
 * // by reversing, directories are more evenly spread out. The
 * // first 26 directories already occupy 26 main levels
 *
 * more info on limitation:
 * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
 *
 * if you really need this for bigger numbers you probably have to look
 * at things like: http://theserverpages.com/php/manual/en/ref.bc.php
 * or: http://theserverpages.com/php/manual/en/ref.gmp.php
 * but I haven't really dugg into this. If you have more info on those
 * matters feel free to leave a comment.
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $number_in = 2188847690240;
 * $alpha_in  = "SpQXn7Cb";
 *
 * // Execute //
 * $alpha_out  = alphaID($number_in, false, 8);
 * $number_out = alphaID($alpha_in, true, 8);
 *
 * if ($number_in != $number_out) {
 *	 echo "Conversion failure, ".$alpha_in." returns ".$number_out." instead of the ";
 *	 echo "desired: ".$number_in."\n";
 * }
 * if ($alpha_in != $alpha_out) {
 *	 echo "Conversion failure, ".$number_in." returns ".$alpha_out." instead of the ";
 *	 echo "desired: ".$alpha_in."\n";
 * }
 *
 * // Show //
 * echo $number_out." => ".$alpha_out."\n";
 * echo $alpha_in." => ".$number_out."\n";
 * echo alphaID(238328, false)." => ".alphaID(alphaID(238328, false), true)."\n";
 *
 * // expects:
 * // 2188847690240 => SpQXn7Cb
 * // SpQXn7Cb => 2188847690240
 * // aaab => 238328
 *
 * </code>
 *
 * @author	Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author	Simon Franz
 * @author	Deadfish
 * @author  SK83RJOSH
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
 * @link	  http://kevin.vanzonneveld.net/
 *
 * @param mixed   $in	  String or long input to translate
 * @param boolean $to_num  Reverses translation when true
 * @param mixed   $pad_up  Number or boolean padds the result up to a specified length
 * @param string  $pass_key Supplying a password makes it harder to calculate the original ID
 *
 * @return mixed string or long
 */
function alphaID($in, $to_num = false, $pad_up = false, $pass_key = null)
{
	$out   =   '';
	$index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$base  = strlen($index);

	if ($pass_key !== null) {
		// Although this function's purpose is to just make the
		// ID short - and not so much secure,
		// with this patch by Simon Franz (http://blog.snaky.org/)
		// you can optionally supply a password to make it harder
		// to calculate the corresponding numeric ID

		for ($n = 0; $n < strlen($index); $n++) {
			$i[] = substr($index, $n, 1);
		}

		$pass_hash = hash('sha256',$pass_key);
		$pass_hash = (strlen($pass_hash) < strlen($index) ? hash('sha512', $pass_key) : $pass_hash);

		for ($n = 0; $n < strlen($index); $n++) {
			$p[] =  substr($pass_hash, $n, 1);
		}

		array_multisort($p, SORT_DESC, $i);
		$index = implode($i);
	}

	if ($to_num) {
		// Digital number  <<--  alphabet letter code
		$len = strlen($in) - 1;

		for ($t = $len; $t >= 0; $t--) {
			$bcp = bcpow($base, $len - $t);
			$out = $out + strpos($index, substr($in, $t, 1)) * $bcp;
		}

		if (is_numeric($pad_up)) {
			$pad_up--;

			if ($pad_up > 0) {
				$out -= pow($base, $pad_up);
			}
		}
	} else {
		// Digital number  -->>  alphabet letter code
		if (is_numeric($pad_up)) {
			$pad_up--;

			if ($pad_up > 0) {
				$in += pow($base, $pad_up);
			}
		}

		for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; $t--) {
			$bcp = bcpow($base, $t);
			$a   = floor($in / $bcp) % $base;
			$out = $out . substr($index, $a, 1);
			$in  = $in - ($a * $bcp);
		}
	}

	return $out;
}

// Get self host name
function selfHost() {
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$pt = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($pt, 0, strpos($pt, "/")).$s;
	return $protocol."://".$_SERVER['SERVER_NAME'];
}

// DB connection
function db_connect(){
	mysql_connect(ML_DB_HOST, ML_DB_USER, ML_DB_PASS) or die();
	mysql_select_db(ML_DB_NAME) or die();
}

// single row query
function db_squery($sql){
	$res = mysql_query($sql);
	if(mysql_num_rows($res)>0){
		return mysql_fetch_array($res);
	}else{
		return false;
	}
}

// Check if is a domain set
function check_domain($url){
	$url_info = parse_url($url);
	if(empty($url_info['host'])){
		return false;
	}else{
		// to remove the WWW.
		if(substr($url_info['host'],0,4)=='www.'){
			return substr($url_info['host'],4);
		}else{
			return $url_info['host'];
		}
	}
}

// Check for banned domains
function check_banned_domain($host){
	$sql = 'SELECT * FROM '.ML_OT.' WHERE dom = "'.mysql_real_escape_string($host).'"';
	return db_squery($sql);
}

// Add the URL to the DB
function add_url_to_db($url){
	// Check if the address it's alredy compressed and return it if it is
	$url = trim($url);
	$sql = 'SELECT * FROM '.ML_CO.' WHERE url = "'.mysql_real_escape_string($url).'"';
	if($row = db_squery($sql)){
		return alphaID($row['id']);
	}else{
		// let's add that address
		$sql = 'INSERT INTO '.ML_CO.' (url) VALUES("'.mysql_real_escape_string($url).'")';
		mysql_query($sql);
		$url_nid = mysql_insert_id();
		return alphaID($url_nid);
	}
}

// Try to add the URL, return the ID or false
function add_url($url){
	// Check if the URL has a HOST
	if($host = check_domain($url)){			
		// Connect to the DB
		db_connect();
		// Check if the URL has a not banned Host
		if(check_banned_domain($host)){
			return false;
		}else{
			return add_url_to_db($url);
		}
		// Close the DB
		mysql_close();
	}else{
		return false;
	}
}

// check if is API or Book
function api_or_bkml($url){
	$aob = substr($url,1,7);
	if($aob=='longurl' || $aob=='bkmrklt'){
		return $aob;
	}else{
		return false;
	}
}

// give a HTTP 404 error and include any page
function error_404($r){
	header('HTTP/1.0 404 Not Found');
	die('<!-- Error: -->'.$r);  
}
?>
