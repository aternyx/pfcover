<?php
/**
 * Profile Cover plugin for MyBB
 * © 2023, mgXzyy
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Neat trick for caching our custom template(s)
if(defined('THIS_SCRIPT'))
{
	if(THIS_SCRIPT == 'usercp.php')
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'usercp_pfcover,usercp_pfcover_auto_resize_auto,usercp_pfcover_auto_resize_user,usercp_pfcover_current,usercp_pfcover_description,usercp_pfcover_remote,usercp_pfcover_remove,usercp_pfcover_upload,usercp_nav_pfcover';
	}

	if(THIS_SCRIPT == 'private.php')
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'usercp_nav_pfcover';
	}

	if(THIS_SCRIPT == 'usercp2.php')
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'usercp_nav_pfcover';
	}

	if(THIS_SCRIPT == 'member.php')
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'member_profile_pfcover,member_profile_pfcover_description,member_profile_pfcover_pfcover';
	}

	if(THIS_SCRIPT == 'modcp.php')
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'modcp_editprofile_pfcover,modcp_editprofile_pfcover_description';
	}
}

// Tell MyBB when to run the hooks
$plugins->add_hook("global_start", "pfcover_header_cache");
$plugins->add_hook("global_intermediate", "pfcover_header");
$plugins->add_hook("usercp_start", "pfcover_run");
$plugins->add_hook("usercp_menu_built", "pfcover_nav");
$plugins->add_hook("member_profile_end", "pfcover_profile");
$plugins->add_hook("fetch_wol_activity_end", "pfcover_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "pfcover_online_location");
$plugins->add_hook("modcp_do_editprofile_start", "pfcover_removal");
$plugins->add_hook("modcp_editprofile_start", "pfcover_removal_lang");
$plugins->add_hook("datahandler_user_delete_content", "pfcover_user_delete");

$plugins->add_hook("admin_user_users_edit_graph_tabs", "pfcover_user_options");
$plugins->add_hook("admin_user_users_edit_graph", "pfcover_user_graph");
$plugins->add_hook("admin_user_users_edit_commit_start", "pfcover_user_commit");
$plugins->add_hook("admin_formcontainer_end", "pfcover_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "pfcover_usergroup_permission_commit");
$plugins->add_hook("admin_tools_system_health_output_chmod_list", "pfcover_chmod");

// The information that shows up on the plugin manager
function pfcover_info()
{
	global $lang;
	$lang->load("pfcover", true);

	return array(
		"name"				=> $lang->pfcover_info_name,
		"description"		=> $lang->pfcover_info_desc,
		"website"			=> "http://mgxweb.cf",
		"author"			=> "mgXzyy",
		"authorsite"		=> "http://mgxweb.cf",
		"version"			=> "1.0",
		"codename"			=> "pfcover",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is installed.
function pfcover_install()
{
	global $db, $cache;
	pfcover_uninstall();

	switch($db->type)
	{
		case "pgsql":
			$db->add_column("users", "pfcover", "varchar(200) NOT NULL default ''");
			$db->add_column("users", "pfcoverdimensions", "varchar(10) NOT NULL default ''");
			$db->add_column("users", "pfcovertype", "varchar(10) NOT NULL default ''");
			$db->add_column("users", "pfcoverdescription", "varchar(255) NOT NULL default ''");

			$db->add_column("usergroups", "canusepfcover", "smallint NOT NULL default '1'");
			$db->add_column("usergroups", "canuploadpfcover", "smallint NOT NULL default '1'");
			$db->add_column("usergroups", "pfcovermaxsize", "int NOT NULL default '40'");
			$db->add_column("usergroups", "pfcovermaxdimensions", "varchar(10) NOT NULL default '200x200'");
			break;
		case "sqlite":
			$db->add_column("users", "pfcover", "varchar(200) NOT NULL default ''");
			$db->add_column("users", "pfcoverdimensions", "varchar(10) NOT NULL default ''");
			$db->add_column("users", "pfcovertype", "varchar(10) NOT NULL default ''");
			$db->add_column("users", "pfcoverdescription", "varchar(255) NOT NULL default ''");

			$db->add_column("usergroups", "canusepfcover", "tinyint(1) NOT NULL default '1'");
			$db->add_column("usergroups", "canuploadpfcover", "tinyint(1) NOT NULL default '1'");
			$db->add_column("usergroups", "pfcovermaxsize", "int NOT NULL default '40'");
			$db->add_column("usergroups", "pfcovermaxdimensions", "varchar(10) NOT NULL default '200x200'");
			break;
		default:
			$db->add_column("users", "pfcover", "varchar(200) NOT NULL default ''");
			$db->add_column("users", "pfcoverdimensions", "varchar(10) NOT NULL default ''");
			$db->add_column("users", "pfcovertype", "varchar(10) NOT NULL default ''");
			$db->add_column("users", "pfcoverdescription", "varchar(255) NOT NULL default ''");

			$db->add_column("usergroups", "canusepfcover", "tinyint(1) NOT NULL default '1'");
			$db->add_column("usergroups", "canuploadpfcover", "tinyint(1) NOT NULL default '1'");
			$db->add_column("usergroups", "pfcovermaxsize", "int unsigned NOT NULL default '40'");
			$db->add_column("usergroups", "pfcovermaxdimensions", "varchar(10) NOT NULL default '200x200'");
			break;
	}

	$cache->update_usergroups();
}

// Checks to make sure plugin is installed
function pfcover_is_installed()
{
	global $db;
	if($db->field_exists("pfcover", "users"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function pfcover_uninstall()
{
	global $db, $cache;

	if($db->field_exists("pfcover", "users"))
	{
		$db->drop_column("users", "pfcover");
	}

	if($db->field_exists("pfcoverdimensions", "users"))
	{
		$db->drop_column("users", "pfcoverdimensions");
	}

	if($db->field_exists("pfcovertype", "users"))
	{
		$db->drop_column("users", "pfcovertype");
	}

	if($db->field_exists("pfcoverdescription", "users"))
	{
		$db->drop_column("users", "pfcoverdescription");
	}

	if($db->field_exists("canusepfcover", "usergroups"))
	{
		$db->drop_column("usergroups", "canusepfcover");
	}

	if($db->field_exists("canuploadpfcover", "usergroups"))
	{
		$db->drop_column("usergroups", "canuploadpfcover");
	}

	if($db->field_exists("pfcovermaxsize", "usergroups"))
	{
		$db->drop_column("usergroups", "pfcovermaxsize");
	}

	if($db->field_exists("pfcovermaxdimensions", "usergroups"))
	{
		$db->drop_column("usergroups", "pfcovermaxdimensions");
	}

	$cache->update_usergroups();
}

// This function runs when the plugin is activated.
function pfcover_activate()
{
	global $db;

	// Insert settings
	$insertarray = array(
		'name' => 'pfcover',
		'title' => 'Profile Cover Settings',
		'description' => 'Various option related to profile covers can be managed and set here.',
		'disporder' => 42,
		'isdefault' => 0,
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	$insertarray = array(
		'name' => 'pfcoveruploadpath',
		'title' => 'Profile Cover Upload Path',
		'description' => 'This is the path where profile covers will be uploaded to. This directory <strong>must be chmod 777</strong> (writable) for uploads to work.',
		'optionscode' => 'text',
		'value' => './uploads/pfcovers',
		'disporder' => 1,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'pfcoverresizing',
		'title' => 'Profile Cover Resizing Mode',
		'description' => 'If you wish to automatically resize all large profile covers, provide users the option of resizing their profile cover, or not resize profile covers at all you can change this setting.',
		'optionscode' => 'select
auto=Automatically resize large profile covers
user=Give users the choice of resizing large profile covers
disabled=Disable this feature',
		'value' => 'auto',
		'disporder' => 2,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'pfcoverdescription',
		'title' => 'Profile Cover Description',
		'description' => 'If you wish allow your users to enter an optional description for their profile cover, set this option to yes.',
		'optionscode' => 'yesno',
		'value' => 1,
		'disporder' => 3,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'userpfcoverrating',
		'title' => 'Gravatar Rating',
		'description' => 'Allows you to set the maximum rating for Gravatars if a user chooses to use one. If a user profile cover is higher than this rating no profile cover will be used. The ratings are:
<ul>
<li><strong>G</strong>: suitable for display on all websites with any audience type</li>
<li><strong>PG</strong>: may contain rude gestures, provocatively dressed individuals, the lesser swear words or mild violence</li>
<li><strong>R</strong>: may contain such things as harsh profanity, intense violence, nudity or hard drug use</li>
<li><strong>X</strong>: may contain hardcore sexual imagery or extremely disturbing violence</li>
</ul>',
		'optionscode' => 'select
g=G
pg=PG
r=R
x=X',
		'value' => 'g',
		'disporder' => 4,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'allowremotepfcovers',
		'title' => 'Allow Remote Profile Covers',
		'description' => $db->escape_string('Whether to allow the usage of profile covers from remote servers. Having this enabled can expose your server\'s IP address.'),
		'optionscode' => 'yesno',
		'value' => 1,
		'disporder' => 5,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	rebuild_settings();

	// Insert templates
	$insert_array = array(
		'title'		=> 'usercp_pfcover',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->change_profile_cover}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
	{$usercpnav}
	<td valign="top">
		{$pfcover_error}
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
			<tr>
				<td class="thead" colspan="2"><strong>{$lang->change_profile_cover}</strong></td>
			</tr>
			<tr>
				<td class="trow1" colspan="2">
					<table cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td>{$lang->profile_cover_note}{$pfcovermsg}
							{$currentpfcover}
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="tcat" colspan="2"><strong>{$lang->custom_profile_cover}</strong></td>
			</tr>
			<form enctype="multipart/form-data" action="usercp.php" method="post">
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			{$pfcoverupload}
			{$pfcover_remote}
			{$pfcoverdescription}
		</table>
		<br />
		<div align="center">
			<input type="hidden" name="action" value="do_pfcover" />
			<input type="submit" class="button" name="submit" value="{$lang->change_cover}" />
			{$removepfcover}
		</div>
	</td>
</tr>
</table>
</form>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_pfcover_auto_resize_auto',
		'template'	=> $db->escape_string('<br /><span class="smalltext">{$lang->profile_cover_auto_resize_note}</span>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_pfcover_auto_resize_user',
		'template'	=> $db->escape_string('<br /><span class="smalltext"><input type="checkbox" name="auto_resize" value="1" checked="checked" id="auto_resize" /> <label for="auto_resize">{$lang->profile_cover_auto_resize_option}</label></span>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_pfcover_current',
		'template'	=> $db->escape_string('<td width="150" align="right"><img src="{$userpfcover[\'image\']}" alt="{$lang->profile_cover_mine}" title="{$lang->profile_cover_mine}" {$userpfcover[\'width_height\']} /></td>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_pfcover_description',
		'template'	=> $db->escape_string('<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->profile_cover_description}</strong>
		<br /><span class="smalltext">{$lang->profile_cover_description_note}</span>
	</td>
	<td class="trow1" width="60%">
		<textarea name="pfcoverdescription" id="pfcoverdescription" rows="4" cols="80" maxlength="255">{$description}</textarea>
	</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_pfcover_remote',
		'template'	=> $db->escape_string('<tr>
	<td class="trow2" width="40%">
		<strong>{$lang->profile_cover_url}</strong>
		<br /><span class="smalltext">{$lang->profile_cover_url_note}</span>
	</td>
	<td class="trow2" width="60%">
		<input type="text" class="textbox" name="pfcoverurl" size="45" value="{$pfcoverurl}" />
		<br /><span class="smalltext">{$lang->profile_cover_url_gravatar}</span>
	</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_pfcover_remove',
		'template'	=> $db->escape_string('<input type="submit" class="button" name="remove" value="{$lang->remove_cover}" />'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_pfcover_upload',
		'template'	=> $db->escape_string('<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->profile_cover_upload}</strong>
		<br /><span class="smalltext">{$lang->profile_cover_upload_note}</span>
	</td>
	<td class="trow1" width="60%">
		<input type="file" name="pfcoverupload" size="25" class="fileupload" />
		{$auto_resize}
	</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'member_profile_pfcover',
		'template'	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead"><strong>{$lang->users_profile_cover}</strong></td>
	</tr>
	<tr>
		<td class="trow1" align="center">{$pfcover_img}<br />
		{$description}</td>
	</tr>
</table>
<br />'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'member_profile_pfcover_description',
		'template'	=> $db->escape_string('<span class="smalltext"><em>{$memprofile[\'pfcoverdescription\']}</em></span>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'member_profile_pfcover_pfcover',
		'template'	=> $db->escape_string('<img src="{$userpfcover[\'image\']}" alt="" {$userpfcover[\'width_height\']} />'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_nav_pfcover',
		'template'	=> $db->escape_string('<div><a href="usercp.php?action=pfcover" class="usercp_nav_item" style="padding-left:40px; background:url(\'images/pfcover.png\') no-repeat left center;">{$lang->ucp_nav_change_profile_cover}</a></div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'modcp_editprofile_pfcover',
		'template'	=> $db->escape_string('<tr><td colspan="3"><span class="smalltext"><label><input type="checkbox" class="checkbox" name="remove_pfcover" value="1" /> {$lang->remove_profile_cover}</label></span></td></tr>{$pfcoverdescription}'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'modcp_editprofile_pfcover_description',
		'template'	=> $db->escape_string('<tr>
	<td colspan="3"><span class="smalltext">{$lang->profile_cover_description}</span></td>
</tr>
<tr>
	<td colspan="3"><textarea name="pfcoverdescription" id="pfcoverdescription" rows="4" cols="40" maxlength="255">{$user[\'pfcoverdescription\']}</textarea></td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'global_remote_profile_cover_notice',
		'template'	=> $db->escape_string('<div class="red_alert"><a href="{$mybb->settings[\'bburl\']}/usercp.php?action=pfcover">{$lang->remote_profile_cover_disabled}</a></div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('{$bannedwarning}')."#i", '{$remote_profile_cover_notice}{$bannedwarning}');
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('{$changesigop}')."#i", '{$changesigop}<!-- pfcover -->');
	find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$pfcover}');
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$lang->remove_avatar}</label></span></td>
										</tr>')."#i", '{$lang->remove_avatar}</label></span></td>
										</tr>{$pfcover}');
}

// This function runs when the plugin is deactivated.
function pfcover_deactivate()
{
	global $db;
	$db->delete_query("settings", "name IN('pfcoveruploadpath','pfcoverresizing','pfcoverdescription','userpfcoverrating','allowremotepfcovers')");
	$db->delete_query("settinggroups", "name IN('pfcover')");
	$db->delete_query("templates", "title IN('usercp_pfcover','usercp_pfcover_auto_resize_auto','usercp_pfcover_auto_resize_user','usercp_pfcover_current','member_profile_pfcover','member_profile_pfcover_description','member_profile_pfcover_pfcover','usercp_pfcover_description','usercp_pfcover_remote','usercp_pfcover_remove','usercp_pfcover_upload','usercp_nav_pfcover','modcp_editprofile_pfcover','modcp_editprofile_pfcover_description','global_remote_profile_cover_notice')");
	rebuild_settings();

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('{$remote_profile_cover_notice}')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$pfcover}')."#i", '', 0);
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('<!-- pfcover -->')."#i", '', 0);
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$pfcover}')."#i", '', 0);
}

// User CP Nav link
function pfcover_nav()
{
	global $mybb, $lang, $templates, $usercpnav;
	$lang->load("pfcover");

	if($mybb->usergroup['canusepfcover'] == 1)
	{
		eval("\$pfcover_nav = \"".$templates->get("usercp_nav_pfcover")."\";");
		$usercpnav = str_replace("<!-- pfcover -->", $pfcover_nav, $usercpnav);
	}
}

// Cache the profile cover remote warning template
function pfcover_header_cache()
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'global_remote_profile_cover_notice';
}

// Profile cover remote warning
function pfcover_header()
{
	global $mybb, $templates, $lang, $remote_profile_cover_notice;
	$lang->load("pfcover");

	$remote_profile_cover_notice = '';
	if(($mybb->user['pfcovertype'] === 'remote' || $mybb->user['pfcovertype'] === 'gravatar') && !$mybb->settings['allowremotepfcovers'])
	{
		eval('$remote_profile_cover_notice = "'.$templates->get('global_remote_profile_cover_notice').'";');
	}
}

// The UserCP profile cover page
function pfcover_run()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $usercpnav, $header, $footer;
	$lang->load("pfcover");
	require_once MYBB_ROOT."inc/functions_pfcover.php";

	if($mybb->input['action'] == "do_pfcover" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		if($mybb->usergroup['canusepfcover'] == 0)
		{
			error_no_permission();
		}

		$pfcover_error = "";

		if(!empty($mybb->input['remove'])) // remove profile cover
		{
			$updated_pfcover = array(
				"pfcover" => "",
				"pfcoverdimensions" => "",
				"pfcovertype" => "",
				"pfcoverdescription" => ""
			);
			$db->update_query("users", $updated_pfcover, "uid='{$mybb->user['uid']}'");
			remove_pfcover($mybb->user['uid']);
		}
		elseif($_FILES['pfcoverupload']['name']) // upload profile cover
		{
			if($mybb->usergroup['canuploadpfcover'] == 0)
			{
				error_no_permission();
			}

			// See if profile cover description is too long
			if(my_strlen($mybb->input['pfcoverdescription']) > 255)
			{
				$pfcover_error = $lang->error_descriptiontoobig;
			}

			$pfcover = upload_pfcover();
			if(!empty($pfcover['error']))
			{
				$pfcover_error = $pfcover['error'];
			}
			else
			{
				if($pfcover['width'] > 0 && $pfcover['height'] > 0)
				{
					$pfcover_dimensions = $pfcover['width']."|".$pfcover['height'];
				}
				$updated_pfcover = array(
					"pfcover" => $pfcover['pfcover'].'?dateline='.TIME_NOW,
					"pfcoverdimensions" => $pfcover_dimensions,
					"pfcovertype" => "upload",
					"pfcoverdescription" => $db->escape_string($mybb->input['pfcoverdescription'])
				);
				$db->update_query("users", $updated_pfcover, "uid='{$mybb->user['uid']}'");
			}
		}
		elseif($mybb->input['pfcoverurl'] && $mybb->settings['allowremotepfcovers']) // remote profile cover
		{
			$mybb->input['pfcoverurl'] = trim($mybb->get_input('pfcoverurl'));
			if(validate_email_format($mybb->input['pfcoverurl']) != false)
			{
				// Gravatar
				$mybb->input['pfcoverurl'] = my_strtolower($mybb->input['pfcoverurl']);

				// If user image does not exist, or is a higher rating, use the mystery man
				$email = md5($mybb->input['pfcoverurl']);

				$s = '';
				if(!$mybb->usergroup['pfcovermaxdimensions'])
				{
					$mybb->usergroup['pfcovermaxdimensions'] = '200x200'; // Hard limit of 200 if there are no limits
				}

				// Because Gravatars are square, hijack the width
				list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['pfcovermaxdimensions']));
				$maxheight = (int)$maxwidth;

				// Rating?
				$types = array('g', 'pg', 'r', 'x');
				$rating = $mybb->settings['userpfcoverrating'];

				if(!in_array($rating, $types))
				{
					$rating = 'g';
				}

				$s = "?s={$maxheight}&r={$rating}&d=mm";

				// See if profile cover description is too long
				if(my_strlen($mybb->input['pfcoverdescription']) > 255)
				{
					$pfcover_error = $lang->error_descriptiontoobig;
				}

				$updated_pfcover = array(
					"pfcover" => "https://www.gravatar.com/avatar/{$email}{$s}",
					"pfcoverdimensions" => "{$maxheight}|{$maxheight}",
					"pfcovertype" => "gravatar",
					"pfcoverdescription" => $db->escape_string($mybb->input['pfcoverdescription'])
				);

				$db->update_query("users", $updated_pfcover, "uid = '{$mybb->user['uid']}'");
			}
			else
			{
				$mybb->input['pfcoverurl'] = preg_replace("#script:#i", "", $mybb->input['pfcoverurl']);
				$ext = get_extension($mybb->input['pfcoverurl']);

				// Copy the profile cover to the local server (work around remote URL access disabled for getimagesize)
				$file = fetch_remote_file($mybb->input['pfcoverurl']);
				if(!$file)
				{
					$pfcover_error = $lang->error_invalidpfcoverurl;
				}
				else
				{
					$tmp_name = $mybb->settings['pfcoveruploadpath']."/remote_".md5(random_str());
					$fp = @fopen($tmp_name, "wb");
					if(!$fp)
					{
						$pfcover_error = $lang->error_invalidpfcoverurl;
					}
					else
					{
						fwrite($fp, $file);
						fclose($fp);
						list($width, $height, $type) = @getimagesize($tmp_name);
						@unlink($tmp_name);
						if(!$type)
						{
							$pfcover_error = $lang->error_invalidpfcoverurl;
						}
					}
				}

				// See if profile cover description is too long
				if(my_strlen($mybb->input['pfcoverdescription']) > 255)
				{
					$pfcover_error = $lang->error_descriptiontoobig;
				}

				if(empty($pfcover_error))
				{
					if($width && $height && $mybb->usergroup['pfcovermaxdimensions'] != "")
					{
						list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['pfcovermaxdimensions']));
						if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
						{
							$lang->error_pfcovertoobig = $lang->sprintf($lang->error_pfcovertoobig, $maxwidth, $maxheight);
							$pfcover_error = $lang->error_pfcovertoobig;
						}
					}
				}

				if(empty($pfcover_error))
				{
					if($width > 0 && $height > 0)
					{
						$pfcover_dimensions = (int)$width."|".(int)$height;
					}
					$updated_pfcover = array(
						"pfcover" => $db->escape_string($mybb->input['pfcoverurl'].'?dateline='.TIME_NOW),
						"pfcoverdimensions" => $pfcover_dimensions,
						"pfcovertype" => "remote",
						"pfcoverdescription" => $db->escape_string($mybb->input['pfcoverdescription'])
					);
					$db->update_query("users", $updated_pfcover, "uid='{$mybb->user['uid']}'");
					remove_pfcover($mybb->user['uid']);
				}
			}
		}
		elseif(isset($mybb->input['pfcoverdescription']) && $mybb->input['pfcoverdescription'] != $mybb->user['pfcoverdescription']) // just updating profile cover description
		{
			// See if profile cover description is too long
			if(my_strlen($mybb->input['pfcoverdescription']) > 255)
			{
				$pfcover_error = $lang->error_descriptiontoobig;
			}

			if(empty($pfcover_error))
			{
				$updated_pfcover = array(
					"pfcoverdescription" => $db->escape_string($mybb->input['pfcoverdescription'])
				);
				$db->update_query("users", $updated_pfcover, "uid='{$mybb->user['uid']}'");
			}
		}
		else
		{
			$pfcover_error = $lang->error_remote_profile_cover_not_allowed;
		}

		if(empty($pfcover_error))
		{
			redirect("usercp.php?action=pfcover", $lang->redirect_profile_cover_updated);
		}
		else
		{
			$mybb->input['action'] = "pfcover";
			$pfcover_error = inline_error($pfcover_error);
		}
	}

	if($mybb->input['action'] == "pfcover")
	{
		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb($lang->change_profile_cover, "usercp.php?action=pfcover");

		// Show main profile cover page
		if($mybb->usergroup['canusepfcover'] == 0)
		{
			error_no_permission();
		}

		$pfcovermsg = $pfcoverurl = '';

		if($mybb->user['pfcovertype'] == "upload" || stristr($mybb->user['pfcover'], $mybb->settings['pfcoveruploadpath']))
		{
			$pfcovermsg = "<br /><strong>".$lang->already_uploaded_profile_cover."</strong>";
		}
		elseif($mybb->user['pfcovertype'] == "remote" || my_validate_url($mybb->user['pfcover']))
		{
			$pfcovermsg = "<br /><strong>".$lang->using_remote_profile_cover."</strong>";
			$pfcoverurl = htmlspecialchars_uni($mybb->user['pfcover']);
		}

		$currentpfcover = '';
		if(!empty($mybb->user['pfcover']) && ((($mybb->user['pfcovertype'] == 'remote' || $mybb->user['pfcovertype'] == 'gravatar') && $mybb->settings['allowremotepfcovers'] == 1) || $mybb->user['pfcovertype'] == "upload"))
		{
			$userpfcover = format_profile_cover(htmlspecialchars_uni($mybb->user['pfcover']), $mybb->user['pfcoverdimensions'], '200x200');
			eval("\$currentpfcover = \"".$templates->get("usercp_pfcover_current")."\";");
		}

		if($mybb->usergroup['pfcovermaxdimensions'] != "")
		{
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['pfcovermaxdimensions']));
			$lang->profile_cover_note .= "<br />".$lang->sprintf($lang->profile_cover_note_dimensions, $maxwidth, $maxheight);
		}
		if($mybb->usergroup['pfcovermaxsize'])
		{
			$maxsize = get_friendly_size($mybb->usergroup['pfcovermaxsize']*1024);
			$lang->profile_cover_note .= "<br />".$lang->sprintf($lang->profile_cover_note_size, $maxsize);
		}

		$auto_resize = '';
		if($mybb->settings['pfcoverresizing'] == "auto")
		{
			eval("\$auto_resize = \"".$templates->get("usercp_pfcover_auto_resize_auto")."\";");
		}
		else if($mybb->settings['pfcoverresizing'] == "user")
		{
			eval("\$auto_resize = \"".$templates->get("usercp_pfcover_auto_resize_user")."\";");
		}

		$pfcoverupload = '';
		if($mybb->usergroup['canuploadpfcover'] == 1)
		{
			eval("\$pfcoverupload = \"".$templates->get("usercp_pfcover_upload")."\";");
		}

		$pfcover_remote = '';
		if($mybb->settings['allowremotepfcovers'] == 1)
		{
			eval("\$pfcover_remote = \"".$templates->get("usercp_pfcover_remote")."\";");
		}

		$description = htmlspecialchars_uni($mybb->user['pfcoverdescription']);

		$pfcoverdescription = '';
		if($mybb->settings['pfcoverdescription'] == 1)
		{
			eval("\$pfcoverdescription = \"".$templates->get("usercp_pfcover_description")."\";");
		}

		$removepfcover = '';
		if(!empty($mybb->user['pfcover']))
		{
			eval("\$removepfcover = \"".$templates->get("usercp_pfcover_remove")."\";");
		}

		if(!isset($pfcover_error))
		{
			$pfcover_error = '';
		}

		eval("\$pfcover = \"".$templates->get("usercp_pfcover")."\";");
		output_page($pfcover);
	}
}

// Profile Cover display in profile
function pfcover_profile()
{
	global $mybb, $templates, $lang, $theme, $memprofile, $pfcover, $parser;
	$lang->load("pfcover");
	require_once MYBB_ROOT."inc/functions_pfcover.php";

	$lang->users_profile_cover = $lang->sprintf($lang->users_profile_cover, $memprofile['username']);

	$pfcover = $pfcover_img = '';
	if($memprofile['pfcover'] && ((($memprofile['pfcovertype'] == 'remote' || $memprofile['pfcovertype'] == 'gravatar') && $mybb->settings['allowremotepfcovers'] == 1) || $memprofile['pfcovertype'] == "upload"))
	{
		$memprofile['pfcover'] = htmlspecialchars_uni($memprofile['pfcover']);
		$userpfcover = format_profile_cover($memprofile['pfcover'], $memprofile['pfcoverdimensions']);
		eval("\$pfcover_img = \"".$templates->get("member_profile_pfcover_pfcover")."\";");

		$description = '';
		if(!empty($memprofile['pfcoverdescription']) && $mybb->settings['pfcoverdescription'] == 1)
		{
			$memprofile['pfcoverdescription'] = htmlspecialchars_uni($parser->parse_badwords($memprofile['pfcoverdescription']));
			eval("\$description = \"".$templates->get("member_profile_pfcover_description")."\";");
		}

		eval("\$pfcover = \"".$templates->get("member_profile_pfcover")."\";");
	}
}

// Online location support
function pfcover_online_activity($user_activity)
{
	global $user;
	if(my_strpos($user['location'], "usercp.php?action=pfcover") !== false)
	{
		$user_activity['activity'] = "usercp_pfcover";
	}

	return $user_activity;
}

function pfcover_online_location($plugin_array)
{
	global $lang;
	$lang->load("pfcover");

	if($plugin_array['user_activity']['activity'] == "usercp_pfcover")
	{
		$plugin_array['location_name'] = $lang->changing_profile_cover;
	}

	return $plugin_array;
}

// Mod CP removal function
function pfcover_removal()
{
	global $mybb, $db, $user;
	require_once MYBB_ROOT."inc/functions_pfcover.php";

	if(!empty($mybb->input['remove_pfcover']))
	{
		$updated_pfcover = array(
			"pfcover" => "",
			"pfcoverdimensions" => "",
			"pfcovertype" => ""
		);
		remove_pfcover($user['uid']);

		$db->update_query("users", $updated_pfcover, "uid='{$user['uid']}'");
	}

	// Update description if active
	if($mybb->settings['pfcoverdescription'] == 1)
	{
		$updated_pfcover = array(
			"pfcoverdescription" => $db->escape_string($mybb->input['pfcoverdescription'])
		);
		$db->update_query("users", $updated_pfcover, "uid='{$user['uid']}'");
	}
}

// Mod CP language
function pfcover_removal_lang()
{
	global $mybb, $lang, $user, $templates, $pfcover;
	$lang->load("pfcover");

	$user['pfcoverdescription'] = htmlspecialchars_uni($user['pfcoverdescription']);

	$pfcoverdescription = '';
	if($mybb->settings['pfcoverdescription'] == 1)
	{
		eval("\$pfcoverdescription = \"".$templates->get("modcp_editprofile_pfcover_description")."\";");
	}

	eval("\$pfcover = \"".$templates->get("modcp_editprofile_pfcover")."\";");
}

// Delete profile cover if user is deleted
function pfcover_user_delete($delete)
{
	global $db;

	// Remove any of the user(s) uploaded profile covers
	$query = $db->simple_select('users', 'pfcover', "uid IN({$delete->delete_uids}) AND pfcovertype='upload'");
	while($pfcover = $db->fetch_field($query, 'pfcover'))
	{
		$pfcover = substr($pfcover, 2, -20);
		@unlink(MYBB_ROOT.$pfcover);
	}

	return $delete;
}

// Edit user options in Admin CP
function pfcover_user_options($tabs)
{
	global $lang;
	$lang->load("pfcover", true);

	$tabs['pfcover'] = $lang->profile_cover;
	return $tabs;
}

function pfcover_user_graph()
{
	global $lang, $form, $mybb, $user, $errors;
	$lang->load("pfcover", true);

	$profile_cover_dimensions = explode("|", $user['pfcoverdimensions']);
	if($user['pfcover'] && (my_strpos($user['pfcover'], '://') === false || $mybb->settings['allowremotepfcovers']))
	{
		if($user['pfcoverdimensions'])
		{
			require_once MYBB_ROOT."inc/functions_image.php";
			list($width, $height) = explode("|", $user['pfcoverdimensions']);
			$scaled_dimensions = scale_image($width, $height, 200, 200);
		}
		else
		{
			$scaled_dimensions = array(
				"width" => 200,
				"height" => 200
			);
		}
		if(!my_validate_url($user['pfcover']))
		{
			$user['pfcover'] = "../{$user['pfcover']}\n";
		}
	}
	else
	{
		$user['pfcover'] = "../".$mybb->settings['useravatar'];
		$scaled_dimensions = array(
			"width" => 200,
			"height" => 200
		);
	}
	$profile_cover_top = ceil((206-$scaled_dimensions['height'])/2);

	echo "<div id=\"tab_pfcover\">\n";
	$table = new Table;
	$table->construct_header($lang->current_profile_cover, array('colspan' => 2));

	$table->construct_cell("<div style=\"width: 206px; height: 206px;\" class=\"user_avatar\"><img src=\"".htmlspecialchars_uni($user['pfcover'])."\" width=\"{$scaled_dimensions['width']}\" style=\"margin-top: {$profile_cover_top}px\" height=\"{$scaled_dimensions['height']}\" alt=\"\" /></div>", array('width' => 1));

	$pfcover_url = '';
	if($user['pfcovertype'] == "upload" || stristr($user['pfcover'], $mybb->settings['pfcoveruploadpath']))
	{
		$current_profile_cover_msg = "<br /><strong>{$lang->user_current_using_uploaded_profile_cover}</strong>";
	}
	elseif($user['pfcovertype'] == "remote" || my_validate_url($user['pfcover']))
	{
		$current_profile_cover_msg = "<br /><strong>{$lang->user_current_using_remote_profile_cover}</strong>";
		$pfcover_url = $user['pfcover'];
	}

	$pfcover_description = '';
	if(!empty($user['pfcoverdescription']))
	{
		$pfcover_description = htmlspecialchars_uni($user['pfcoverdescription']);
	}

	if($errors)
	{
		$pfcover_description = htmlspecialchars_uni($mybb->input['pfcover_description']);
		$pfcover_url = htmlspecialchars_uni($mybb->input['pfcover_url']);
	}

	$user_permissions = user_permissions($user['uid']);

	if($user_permissions['pfcovermaxdimensions'] != "")
	{
		list($max_width, $max_height) = explode("x", my_strtolower($user_permissions['pfcovermaxdimensions']));
		$max_size = "<br />{$lang->profile_cover_max_dimensions_are} {$max_width}x{$max_height}";
	}

	if($user_permissions['pfcovermaxsize'])
	{
		$maximum_size = get_friendly_size($user_permissions['pfcovermaxsize']*1024);
		$max_size .= "<br />{$lang->profile_cover_max_size} {$maximum_size}";
	}

	if($user['pfcover'])
	{
		$remove_pfcover = "<br /><br />".$form->generate_check_box("remove_pfcover", 1, "<strong>{$lang->remove_profile_cover_admin}</strong>");
	}

	$table->construct_cell($lang->profile_cover_desc."{$remove_pfcover}<br /><small>{$max_size}</small>");
	$table->construct_row();

	$table->output($lang->profile_cover.": ".htmlspecialchars_uni($user['username']));

	// Custom profile cover
	if($mybb->settings['pfcoverresizing'] == "auto")
	{
		$auto_resize = $lang->profile_cover_auto_resize;
	}
	else if($mybb->settings['pfcoverresizing'] == "user")
	{
		$auto_resize = "<input type=\"checkbox\" name=\"auto_resize\" value=\"1\" checked=\"checked\" id=\"auto_resize\" /> <label for=\"auto_resize\">{$lang->attempt_to_auto_resize_profile_cover}</label></span>";
	}
	$form_container = new FormContainer($lang->specify_custom_profile_cover);
	$form_container->output_row($lang->upload_profile_cover, $auto_resize, $form->generate_file_upload_box('pfcover_upload', array('id' => 'pfcover_upload')), 'pfcover_upload');
	if($mybb->settings['allowremotepfcovers'])
	{
		$form_container->output_row($lang->or_specify_profile_cover_url, "", $form->generate_text_box('pfcover_url', $pfcover_url, array('id' => 'pfcover_url')), 'pfcover_url');
	}
	$form_container->output_row($lang->profile_cover_description, "", $form->generate_text_area('pfcover_description', $pfcover_description, array('id' => 'pfcover_description', 'maxlength' => '255')), 'pfcover_description');
	$form_container->end();
	echo "</div>\n";
}

function pfcover_user_commit()
{
	global $db, $extra_user_updates, $mybb, $errors, $user;

	require_once MYBB_ROOT."inc/functions_pfcover.php";
	$user_permissions = user_permissions($user['uid']);

	// Are we removing a profile cover from this user?
	if($mybb->input['remove_pfcover'])
	{
		$extra_user_updates = array(
			"pfcover" => "",
			"pfcoverdimensions" => "",
			"pfcovertype" => ""
		);
		remove_pfcover($user['uid']);
	}

	// Are we uploading a new profile cover?
	if($_FILES['pfcover_upload']['name'])
	{
		$pfcover = upload_pfcover($_FILES['pfcover_upload'], $user['uid']);
		if($pfcover['error'])
		{
			$errors = array($pfcover['error']);
		}
		else
		{
			if($pfcover['width'] > 0 && $pfcover['height'] > 0)
			{
				$pfcover_dimensions = $pfcover['width']."|".$pfcover['height'];
			}
			$extra_user_updates = array(
				"pfcover" => $pfcover['pfcover'].'?dateline='.TIME_NOW,
				"pfcoverdimensions" => $pfcover_dimensions,
				"pfcovertype" => "upload"
			);
		}
	}
	// Are we setting a new profile cover from a URL?
	else if($mybb->input['pfcover_url'] && $mybb->input['pfcover_url'] != $user['pfcover'])
	{
		if(!$mybb->settings['allowremotepfcovers'])
		{
			$errors = array($lang->error_remote_profile_cover_not_allowed);
		}
		else
		{
			if(filter_var($mybb->input['pfcover_url'], FILTER_VALIDATE_EMAIL) !== false)
			{
				// Gravatar
				$email = md5(strtolower(trim($mybb->input['pfcover_url'])));

				$s = '';
				if(!$user_permissions['pfcovermaxdimensions'])
				{
					$user_permissions['pfcovermaxdimensions'] = '200x200'; // Hard limit of 200 if there are no limits
				}

				// Because Gravatars are square, hijack the width
				list($maxwidth, $maxheight) = explode("x", my_strtolower($user_permissions['pfcovermaxdimensions']));

				$s = "?s={$maxwidth}";
				$maxheight = (int)$maxwidth;

				$extra_user_updates = array(
					"pfcover" => "https://www.gravatar.com/avatar/{$email}{$s}",
					"pfcoverdimensions" => "{$maxheight}|{$maxheight}",
					"pfcovertype" => "gravatar"
				);
			}
			else
			{
				$mybb->input['pfcover_url'] = preg_replace("#script:#i", "", $mybb->input['pfcover_url']);
				$ext = get_extension($mybb->input['pfcover_url']);

				// Copy the profile cover to the local server (work around remote URL access disabled for getimagesize)
				$file = fetch_remote_file($mybb->input['pfcover_url']);
				if(!$file)
				{
					$pfcover_error = $lang->error_invalidpfcoverurl;
				}
				else
				{
					$tmp_name = "../".$mybb->settings['pfcoveruploadpath']."/remote_".md5(random_str());
					$fp = @fopen($tmp_name, "wb");
					if(!$fp)
					{
						$pfcover_error = $lang->error_invalidpfcoverurl;
					}
					else
					{
						fwrite($fp, $file);
						fclose($fp);
						list($width, $height, $type) = @getimagesize($tmp_name);
						@unlink($tmp_name);
						echo $type;
						if(!$type)
						{
							$pfcover_error = $lang->error_invalidpfcoverurl;
						}
					}
				}

				if(empty($pfcover_error))
				{
					if($width && $height && $user_permissions['pfcovermaxdimensions'] != "")
					{
						list($maxwidth, $maxheight) = explode("x", my_strtolower($user_permissions['pfcovermaxdimensions']));
						if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
						{
							$lang->error_pfcovertoobig = $lang->sprintf($lang->error_pfcovertoobig, $maxwidth, $maxheight);
							$pfcover_error = $lang->error_pfcovertoobig;
						}
					}
				}

				if(empty($pfcover_error))
				{
					if($width > 0 && $height > 0)
					{
						$pfcover_dimensions = (int)$width."|".(int)$height;
					}
					$extra_user_updates = array(
						"pfcover" => $db->escape_string($mybb->input['pfcover_url'].'?dateline='.TIME_NOW),
						"pfcoverdimensions" => $pfcover_dimensions,
						"pfcovertype" => "remote"
					);
					remove_pfcover($user['uid']);
				}
				else
				{
					$errors = array($pfcover_error);
				}
			}
		}
	}

	if($mybb->input['pfcover_description'] != $user['pfcoverdescription'])
	{
		$pfcover_description = my_substr($mybb->input['pfcover_description'], 0, 255);

		$extra_user_updates = array(
			"pfcoverdescription" => $db->escape_string($pfcover_description)
		);
	}
}

// Admin CP permission control
function pfcover_usergroup_permission()
{
	global $mybb, $lang, $form, $form_container, $run_module, $page;
	$lang->load("pfcover", true);

	if($run_module == 'user' && $page->active_action == 'groups' && !empty($form_container->_title) & !empty($lang->misc) & $form_container->_title == $lang->misc)
	{
		$pfcover_options = array(
			$form->generate_check_box('canusepfcover', 1, $lang->can_use_profile_cover, array("checked" => $mybb->input['canusepfcover'])),
			$form->generate_check_box('canuploadpfcover', 1, $lang->can_upload_profile_cover, array("checked" => $mybb->input['canuploadpfcover'])),
			"{$lang->profile_cover_size}<br /><small>{$lang->profile_cover_size_desc}</small><br />".$form->generate_numeric_field('pfcovermaxsize', $mybb->input['pfcovermaxsize'], array('id' => 'pfcovermaxsize', 'class' => 'field50', 'min' => 0)). "KB",
			"{$lang->profile_cover_dims}<br /><small>{$lang->profile_cover_dims_desc}</small><br />".$form->generate_text_box('pfcovermaxdimensions', $mybb->input['pfcovermaxdimensions'], array('id' => 'pfcovermaxdimensions', 'class' => 'field'))
		);
		$form_container->output_row($lang->profile_cover, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $pfcover_options)."</div>");
	}
}

function pfcover_usergroup_permission_commit()
{
	global $db, $mybb, $updated_group;
	$updated_group['canusepfcover'] = $mybb->get_input('canusepfcover', MyBB::INPUT_INT);
	$updated_group['canuploadpfcover'] = $mybb->get_input('canuploadpfcover', MyBB::INPUT_INT);
	$updated_group['pfcovermaxsize'] = $mybb->get_input('pfcovermaxsize', MyBB::INPUT_INT);
	$updated_group['pfcovermaxdimensions'] = $db->escape_string($mybb->input['pfcovermaxdimensions']);
}

// Check to see if CHMOD for profile covers is writable
function pfcover_chmod()
{
	global $mybb, $lang, $table;
	$lang->load("pfcover", true);

	if(is_writable('../'.$mybb->settings['pfcoveruploadpath']))
	{
		$message_profile_cover = "<span style=\"color: green;\">{$lang->writable}</span>";
	}
	else
	{
		$message_profile_cover = "<strong><span style=\"color: #C00\">{$lang->not_writable}</span></strong><br />{$lang->please_chmod_777}";
		++$errors;
	}

	$table->construct_cell("<strong>{$lang->profile_cover_upload_dir}</strong>");
	$table->construct_cell($mybb->settings['pfcoveruploadpath']);
	$table->construct_cell($message_profile_cover);
	$table->construct_row();
}
