<?php
/**
 * $files in $base_path directory to $dest_path directory
 *
 * @return unpooled destination path
 */
function unpoolMergerFsDestPath($files, $base_path, $dest_path, $verbose=false)
{
	global $mergerfs_pool_path;
	global $mergerfs_disk_paths;
	$pool_path = FileUtil::addslash( $mergerfs_pool_path );
	$disk_paths = array_map( function ($p) {
		return FileUtil::addslash($p);
	}, $mergerfs_disk_paths );
	if ($verbose)
		FileUtil::toLog( "epfix-- Base path:".$base_path." Pool:".$pool_path." Disks: ".join(", ", $disk_paths) );
	if ( substr( $base_path, 0, strlen( $pool_path) ) !== $pool_path )
	{
		if ($verbose)
			FileUtil::toLog( "epfix-- Paths unchanged. base:'".$base_path."' dest:'".$dest_path."'" );
		return array($base_path, $dest_path);
	}
	// identify disk directory of pool $base_path directory
	// -> assume that the all $files of $base_path directory exist only on ONE disk
	if ($verbose)
		FileUtil::toLog( "epfix-- Looking for disk in pool with all files: ".$pool_path );
	foreach ( $disk_paths as $disk_path)
	{
		if ($verbose)
			FileUtil::toLog("epfix-- Looking at disk: ".$disk_path);
		$unpooled_base_path = str_replace( $pool_path, $disk_path, $base_path );
		if( $unpooled_base_path != $base_path && is_dir( $unpooled_base_path ) )
		{
			$filesExist = true;
			foreach ($files as $file)
			{
				if ($verbose)
					FileUtil::toLog("epfix-- checking for file: ".$unpooled_base_path.$file);
				if( !is_file( $unpooled_base_path.$file) )
				{
					$filesExist = false;
					break;// file is not on this disk
				}
			}
			if( $filesExist )
			{
				$unpooled_dest_path =  str_replace( $pool_path, $disk_path, $dest_path );
				$unpooled_base_path =  str_replace( $pool_path, $disk_path, $base_path );
				if ($verbose)
					FileUtil::toLog( "epfix-- Paths unpooled. base:'".$unpooled_base_path."' dest:'".$unpooled_dest_path."'" );
				return array($unpooled_base_path, $unpooled_dest_path);
			}
		}
	}
	if ($verbose)
		FileUtil::toLog( "epfix-- Failure: No disk has all files! ".$disk_paths );
	return array($base_path, $dest_path);
}

// Include our base configuration file
$rootPath = realpath(dirname(__FILE__)."/..");
// Avoid reusing $rootPath here becuase it calls realpath
// dirname is a more stable option becuase it's not file system aware
require_once( dirname(__FILE__).'/../conf/config.php' );

// Automatically include only the used utility classes
spl_autoload_register(function ($class) 
{
	// Remove namespaces from the classname string
	// Important for compatibility with 3rd party plugins
	$arr = explode('\\',$class);
	$class = end($arr);
	
	// Suppress include warnings if the user disables al_diagnostic
	// For compatibility with 3rd party plugins which use autoloaders
	global $al_diagnostic;
	if($al_diagnostic)
		include_once 'utility/'. strtolower($class). '.php';
	else
		@include_once 'utility/'. strtolower($class). '.php';
});

// Fixes quotations if php verison is less than 5.4
// Only include these methods if applicable
if(version_compare(phpversion(), '5.4', '<'))
	require_once ( 'utility/phpversionfix.php');

// Only allow "POST" or "GET" request methods
// Exit script and send 405 if anther method is tried
Requests::disableUnsupportedMethods();

// For "Cross-Site Request Forgery" checks if enabled
Requests::makeCSRFCheck();

@ini_set('precision',16);
@define('XMLRPC_MAX_I4', 2147483647);
@define('XMLRPC_MIN_I4', ~XMLRPC_MAX_I4);
@define('XMLRPC_MIN_I8', -9.999999999999999E+15);
@define('XMLRPC_MAX_I8', 9.999999999999999E+15);

if(function_exists('ini_set'))
{
	ini_set('display_errors',false);
	ini_set('log_errors',true);
}

if(!isset($_SERVER['REMOTE_USER']))
{
	if(isset($_SERVER['PHP_AUTH_USER']))
		$_SERVER['REMOTE_USER'] = $_SERVER['PHP_AUTH_USER'];
	else
	if(isset($_SERVER['REDIRECT_REMOTE_USER']))
		$_SERVER['REMOTE_USER'] = $_SERVER['REDIRECT_REMOTE_USER'];
}

FileUtil::getProfilePath();	// for creation profile, if it is absent
$conf = FileUtil::getConfFile('config.php');
if($conf)
	require_once($conf);

if(!isset($profileMask))
	$profileMask = 0777;
if(!isset($locale))	
	$locale = "UTF8";
setlocale(LC_CTYPE, $locale, "UTF-8", "en_US.UTF-8", "en_US.UTF8");
setlocale(LC_COLLATE, $locale, "UTF-8", "en_US.UTF-8", "en_US.UTF8");
