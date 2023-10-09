<?php
/**
 * Profile Cover plugin for MyBB
 * © 2023, mgXzyy
 */

$l['pfcover_info_name'] = "Profile Cover";
$l['pfcover_info_desc'] = "Allows users to upload a cover to display in their profile.";

// Admin CP
$l['profile_cover'] = "Profile Cover";
$l['can_use_profile_cover'] = "Can use profile cover?";
$l['can_upload_profile_cover'] = "Can upload profile cover?";
$l['profile_cover_size'] = "Maximum File Size:";
$l['profile_cover_size_desc'] = "Maximum file size of an uploaded profile cover in kilobytes. If set to 0, there is no limit.";
$l['profile_cover_dims'] = "Maximum Dimensions:";
$l['profile_cover_dims_desc'] = "Maximum dimensions a profile cover can be, in the format of width<strong>x</strong>height. If this is left blank then there will be no dimension restriction.";

$l['profile_cover_upload_dir'] = "Profile Cover Uploads Directory";

// Front end
$l['users_profile_cover'] = "{1}'s Profile Cover";
$l['changing_profile_cover'] = "Changing Profile Cover";
$l['remove_profile_cover'] = "Remove user's profile cover?";

$l['nav_usercp'] = "User Control Panel";
$l['ucp_nav_change_profile_cover'] = "Change Profile Cover";
$l['change_profile_cover'] = "Change Profile Cover";
$l['change_cover'] = "Change Cover";
$l['remove_cover'] = "Remove Cover";
$l['profile_cover_url'] = "Profile Cover URL:";
$l['profile_cover_url_note'] = "Enter the URL of a profile cover on the internet.";
$l['profile_cover_url_gravatar'] = "To use a <a href=\"http://gravatar.com\" target=\"_blank\">Gravatar</a> enter your Gravatar email.";
$l['profile_cover_description'] = "Profile Cover Description:";
$l['profile_cover_description_note'] = "(Optional) Add a brief description of your profile cover.";
$l['profile_cover_upload'] = "Upload Profile Cover:";
$l['profile_cover_upload_note'] = "Choose a profile cover on your local computer to upload.";
$l['profile_cover_note'] = "A profile cover is a small identifying image shown in a user's profile.";
$l['profile_cover_note_dimensions'] = "The maximum dimensions for profile covers are: {1}x{2} pixels.";
$l['profile_cover_note_size'] = "The maximum file size for profile covers is {1}.";
$l['custom_profile_cover'] = "Custom Profile Cover";
$l['already_uploaded_profile_cover'] = "You are currently using an uploaded profile cover. If you choose to use another one, your old profile cover will be deleted from the server.";
$l['profile_cover_auto_resize_note'] = "If your profile cover is too large, it will automatically be resized.";
$l['profile_cover_auto_resize_option'] = "Try to resize my profile cover if it is too large.";
$l['redirect_profile_cover_updated'] = "Your profile cover has been changed successfully.<br />You will now be returned to your User CP.";
$l['using_remote_profile_cover'] = "You are currently using a profile cover from a remote site. If you choose to use another one, your old profile cover URL will be emptied.";
$l['profile_cover_mine'] = "This is your Profile Cover";

$l['error_uploadfailed'] = "The file upload failed. Please choose a valid file and try again. ";
$l['error_profilecovertype'] = "Invalid file type. An uploaded profile cover must be in GIF, JPEG, BMP or PNG format.";
$l['error_invalidprofilecoverurl'] = "The URL you entered for your profile cover does not appear to be valid. Please ensure you enter a valid URL.";
$l['error_profilecovertoobig'] = "Sorry, but we cannot change your profile cover as the new cover you specified is too big. The maximum dimensions are {1}x{2} (width x height)";
$l['error_profilecoverresizefailed'] = "Your profile cover was unable to be resized so that it is within the required dimensions.";
$l['error_profilecoveruserresize'] = "You can also try checking the 'attempt to resize my profile cover' check box and uploading the same image again.";
$l['error_uploadsize'] = "The size of the uploaded file is too large.";
$l['error_remote_profile_cover_not_allowed'] = "Remote profile cover URLs have been disabled by the forum administrator.";
$l['error_descriptiontoobig'] = "Your profile cover description is too long. The maximum length for descriptions is 255 characters.";
$l['remote_profile_cover_disabled'] = "You are currently using a remote profile cover, which has been disabled. Your profile cover has been hidden.";

$l['current_profile_cover'] = "Current Profile Cover";
$l['remove_profile_cover_admin'] = "Remove current profile cover?";
$l['user_current_using_uploaded_profile_cover'] = "This user is currently using an uploaded profile cover.";
$l['user_current_using_remote_profile_cover'] = "This user is currently using a remotely linked profile cover.";
$l['specify_custom_profile_cover'] = "Specify Custom Profile Cover";
$l['upload_profile_cover'] = "Upload Profile Cover";
$l['profile_cover_auto_resize'] = "If the profile cover is too large, it will automatically be resized";
$l['or_specify_profile_cover_url'] = "or Specify Profile Cover/Gravatar URL";
$l['profile_cover_max_size'] = "Profile Covers can be a maximum of";
$l['profile_cover_desc'] = "Below you can manage the profile cover for this user. Profile Covers are small identifying images shown in a user's profile.";
$l['profile_cover_max_dimensions_are'] = "The maximum dimensions for profile covers are";
$l['attempt_to_auto_resize_profile_cover'] = "Attempt to resize this profile cover if it is too large?";
