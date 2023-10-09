# profilecovers
Ability for users on MyBB to upload their cover on their profile.

## Installation
1. Upload all files above, keeping the file structure intact.
2. Make sure you CHMOD the upload/pfcovers folder to 777.
3. Go to Configuration > Plugins
4. Click "Install & Activate"

## Variables
user pov cover image

      {$mybb->user['pfcover']} 
get cover url of user in member_profile template

      {$memprofile['pfcover']}
get cover url of user in postbit template 

      {$post['pfcover']} 
