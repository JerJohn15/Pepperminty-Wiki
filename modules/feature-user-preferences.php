<?php
register_module([
	"name" => "User Preferences",
	"version" => "0.1",
	"author" => "Starbeamrainbowlabs",
	"description" => "Adds a user preferences page, letting pople do things like change their email address and password.",
	"id" => "feature-user-preferences",
	"code" => function() {
		global $settings;
		/**
		 * @api {get} ?action=user-preferences Get a user preferences configuration page.
		 * @apiName UserPreferences
		 * @apiGroup Settings
		 * @apiPermission User
		 */
		
		 /*
 		 * ██    ██ ███████ ███████ ██████
 		 * ██    ██ ██      ██      ██   ██
 		 * ██    ██ ███████ █████   ██████  █████
 		 * ██    ██      ██ ██      ██   ██
 		 *  ██████  ███████ ███████ ██   ██
 		 * 
 		 * ██████  ██████  ███████ ███████ ███████
 		 * ██   ██ ██   ██ ██      ██      ██
 		 * ██████  ██████  █████   █████   ███████
 		 * ██      ██   ██ ██      ██           ██
 		 * ██      ██   ██ ███████ ██      ███████
 		 */
		add_action("user-preferences", function() {
			global $env;
			
			if(!$env->is_logged_in)
			{
				exit(page_renderer::render_main("Error  - $settings->sitename", "<p>Since you aren't logged in, you can't change your preferences. This is because stored preferences are tied to each registered user account. You can login <a href='?action=login&returnto=" . rawurlencode("?action=user-preferences") . "'>here</a>.</p>"));
			}
			
			$content = "<h2>User Preferences</h2>\n";
			$content .= "<label for='username'>Username:</label>\n";
			$content .= "<input type='text' name='username' value='$env->user' readonly />\n";
			$content .= "<h3>Change Password</h3\n>";
			$content .= "<form method='post' action='?action=change-password'>\n";
			$content .= "<label for='old-pass'>Current Password:</label>\n";
			$content .= "<input type='password' name='current-pass'  />\n";
			$content .= "<br />\n";
			$content .= "<label for='new-pass'>New Password:</label>\n";
			$content .= "<input type='password' name='new-pass' />\n";
			$content .= "<br />\n";
			$content .= "<label for='new-pass-confirm'>Confirm New Password:</label>\n";
			$content .= "<input type='password' name='new-pass-confirm' />\n";
			$content .= "<br />\n";
			$content .= "<input type='submit' value='Change Password' />\n";
			$content .= "</form>\n";
			
			exit(page_renderer::render_main("User Preferences - $settings->sitename", $content));
		});
		
		add_action("change-password", function() {
		    global $env;
			// Make sure the new password was typed correctly
			// This comes before the current password check since that's more intensive
			if($_POST["new-pass"] !== $_POST["new-pass-confirm"]) {
				exit(page_renderer::render_main("Password mismatch - $settings->sitename", "<p>The new password you typed twice didn't match. <a href='javascript:history.back();'>Go back</a>.</p>"));
			}
			// Check the current password
			if(hash_password($_POST["current-pass"]) !== $env->user_data->password) {
				exit(page_renderer::render_main("Password mismatch - $settings->sitename", "<p>Error: You typed your current password incorrectly. <a href='javascript:history.back();'>Go back</a>.</p>"));
			}
			
			// All's good! Go ahead and change the password.
			$env->user_data->password = hash_password($_POST["current-pass"]);
			// Save the userdata back to disk
			save_userdata();
		});
		
		/**
		 * @api	{post}	?action=change-password	Change your password
		 * @apiName			ChangePassword
		 * @apiGroup		Settings
		 * @apiPermission	User
		 *
		 * @apiParam	{string}	current-pass		Your current password.
		 * @apiParam	{string}	new-pass			Your new password.
		 * @apiParam	{string}	new-pass-confirm	Your new password again, to make sure you've typed it correctly.
		 *
		 * @apiError	PasswordMismatchError	The new password fields don't match.
		 */
		
		add_help_section("910-user-preferences", "User Preferences", "<p>(help text coming soon)</p>");
	}
]);

?>