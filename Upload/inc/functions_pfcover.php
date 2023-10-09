<?php
/**
 * Profile Cover plugin for MyBB
 * © 2023, mgXzyy
 */

/**
 * Remove any matching profile cover for a specific user ID
 *
 * @param int $uid The user ID
 * @param string $exclude A file name to be excluded from the removal
 */
function remove_profilecover($uid, $exclude="")
{
	global $mybb;

	if(defined('IN_ADMINCP'))
	{
		$pfcoverpath = '../'.$mybb->settings['pfcoveruploadpath'];
	}
	else
	{
		$pfcoverpath = $mybb->settings['pfcoveruploadpath'];
	}

	$dir = opendir($pfcoverpath);
	if($dir)
	{
		while($file = @readdir($dir))
		{
			if(preg_match("#pfcover_".$uid."\.#", $file) && is_file($pfcoverpath."/".$file) && $file != $exclude)
			{
				require_once MYBB_ROOT."inc/functions_upload.php";
				delete_uploaded_file($pfcoverpath."/".$file);
			}
		}

		@closedir($dir);
	}
}

/**
 * Upload a new profile cover in to the file system
 *
 * @param array $pfcover incoming FILE array, if we have one - otherwise takes $_FILES['pfcoverupload']
 * @param int $uid User ID this profile cover is being uploaded for, if not the current user
 * @return array Array of errors if any, otherwise filename if successful.
 */
function upload_pfcover($pfcover=array(), $uid=0)
{
	global $db, $mybb, $lang;

	$ret = array();
	require_once MYBB_ROOT."inc/functions_upload.php";

	if(!$uid)
	{
		$uid = $mybb->user['uid'];
	}

	if(empty($pfcover['name']) || !$pfcover['tmp_name'])
	{
		$pfcover = $_FILES['pfcoverupload'];
	}

	if(!is_uploaded_file($pfcover['tmp_name']))
	{
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check we have a valid extension
	$ext = get_extension(my_strtolower($pfcover['name']));
	if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext))
	{
		$ret['error'] = $lang->error_profilecovertype;
		return $ret;
	}

	if(defined('IN_ADMINCP'))
	{
		$pfcoverpath = '../'.$mybb->settings['pfcoveruploadpath'];
		$lang->load("messages", true);
	}
	else
	{
		$pfcoverpath = $mybb->settings['pfcoveruploadpath'];
	}

	$filename = "pfcover_".$uid.".".$ext;
	$file = upload_file($pfcover, $pfcoverpath, $filename);
	if(!empty($file['error']))
	{
		delete_uploaded_file($pfcoverpath."/".$filename);
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Lets just double check that it exists
	if(!file_exists($pfcoverpath."/".$filename))
	{
		$ret['error'] = $lang->error_uploadfailed;
		delete_uploaded_file($pfcoverpath."/".$filename);
		return $ret;
	}

	// Check if this is a valid image or not
	$img_dimensions = @getimagesize($pfcoverpath."/".$filename);
	if(!is_array($img_dimensions))
	{
		delete_uploaded_file($pfcoverpath."/".$filename);
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check profile cover dimensions
	if($mybb->usergroup['pfcovermaxdimensions'] != '')
	{
		list($maxwidth, $maxheight) = @explode("x", $mybb->usergroup['pfcovermaxdimensions']);
		if(($maxwidth && $img_dimensions[0] > $maxwidth) || ($maxheight && $img_dimensions[1] > $maxheight))
		{
			// Automatic resizing enabled?
			if($mybb->settings['pfcoverresizing'] == "auto" || ($mybb->settings['pfcoverresizing'] == "user" && $mybb->input['auto_resize'] == 1))
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$thumbnail = generate_thumbnail($pfcoverpath."/".$filename, $pfcoverpath, $filename, $maxheight, $maxwidth);
				if(!$thumbnail['filename'])
				{
					$ret['error'] = $lang->sprintf($lang->error_profilecovertoobig, $maxwidth, $maxheight);
					$ret['error'] .= "<br /><br />".$lang->error_profilecoverresizefailed;
					delete_uploaded_file($pfcoverpath."/".$filename);
					return $ret;
				}
				else
				{
					// Copy scaled image to CDN
					copy_file_to_cdn($pfcoverpath . '/' . $thumbnail['filename']);
					// Reset filesize
					$pfcover['size'] = filesize($pfcoverpath."/".$filename);
					// Reset dimensions
					$img_dimensions = @getimagesize($pfcoverpath."/".$filename);
				}
			}
			else
			{
				$ret['error'] = $lang->sprintf($lang->error_profilecovertoobig, $maxwidth, $maxheight);
				if($mybb->settings['pfcoverresizing'] == "user")
				{
					$ret['error'] .= "<br /><br />".$lang->error_profilecoveruserresize;
				}
				delete_uploaded_file($pfcoverpath."/".$filename);
				return $ret;
			}
		}
	}

	// Next check the file size
	if($pfcover['size'] > ($mybb->usergroup['pfcovermaxsize']*1024) && $mybb->usergroup['pfcovermaxsize'] > 0)
	{
		delete_uploaded_file($pfcoverpath."/".$filename);
		$ret['error'] = $lang->error_uploadsize;
		return $ret;
	}

	// Check a list of known MIME types to establish what kind of profile cover we're uploading
	switch(my_strtolower($pfcover['type']))
	{
		case "image/gif":
			$img_type =  1;
			break;
		case "image/jpeg":
		case "image/x-jpg":
		case "image/x-jpeg":
		case "image/pjpeg":
		case "image/jpg":
			$img_type = 2;
			break;
		case "image/png":
		case "image/x-png":
			$img_type = 3;
			break;
		case "image/bmp":
		case "image/x-bmp":
		case "image/x-windows-bmp":
			$img_type = 6;
			break;
		default:
			$img_type = 0;
	}

	// Check if the uploaded file type matches the correct image type (returned by getimagesize)
	if($img_dimensions[2] != $img_type || $img_type == 0)
	{
		$ret['error'] = $lang->error_uploadfailed;
		delete_uploaded_file($pfcoverpath."/".$filename);
		return $ret;
	}
	// Everything is okay so lets delete old profile covers for this user
	remove_profilecover($uid, $filename);

	$ret = array(
		"pfcover" => $mybb->settings['pfcoveruploadpath']."/".$filename,
		"width" => (int)$img_dimensions[0],
		"height" => (int)$img_dimensions[1]
	);

	return $ret;
}

/**
 * Formats a profile cover to a certain dimension
 *
 * @param string $pfcover The profile cover file name
 * @param string $dimensions Dimensions of the profile cover, width x height (e.g. 44|44)
 * @param string $max_dimensions The maximum dimensions of the formatted profile cover
 * @return array Information for the formatted profile cover
 */
function format_profile_cover($pfcover, $dimensions = '', $max_dimensions = '')
{
	global $mybb;
	static $pfcovers;

	if(!isset($pfcovers))
	{
		$pfcovers = array();
	}

	if(my_strpos($pfcover, '://') !== false && !$mybb->settings['allowremoteprofilecovers'])
	{
		// Remote profile cover, but remote profile covers are disallowed.
		$pfcover = null;
	}

	if(!$pfcover)
	{
		// Default profile cover
		$pfcover = '';
		$dimensions = '';
	}

	// An empty key wouldn't work so we need to add a fall back
	$key = $dimensions;
	if(empty($key))
	{
		$key = 'default';
	}
	$key2 = $max_dimensions;
	if(empty($key2))
	{
		$key2 = 'default';
	}

	if(isset($pfcovers[$pfcover][$key][$key2]))
	{
		return $pfcovers[$pfcover][$key][$key2];
	}

	$pfcover_width_height = '';

	if($dimensions)
	{
		$dimensions = explode("|", $dimensions);

		if($dimensions[0] && $dimensions[1])
		{
			list($max_width, $max_height) = explode('x', $max_dimensions);

			if(!empty($max_dimensions) && ($dimensions[0] > $max_width || $dimensions[1] > $max_height))
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$scaled_dimensions = scale_image($dimensions[0], $dimensions[1], $max_width, $max_height);
				$pfcover_width_height = "width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\"";
			}
			else
			{
				$pfcover_width_height = "width=\"{$dimensions[0]}\" height=\"{$dimensions[1]}\"";
			}
		}
	}

	$pfcovers[$pfcover][$key][$key2] = array(
		'image' => htmlspecialchars_uni($mybb->get_asset_url($pfcover)),
		'width_height' => $pfcover_width_height
	);

	return $pfcovers[$pfcover][$key][$key2];
}
