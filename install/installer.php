<?php

	## -------------------------------------------------------
	#  ADDICTIVE COMMUNITY
	## -------------------------------------------------------
	#  Created by Brunno Pleffken Hosti
	#  http://github.com/brunnopleffken/addictive-community
	#
	#  File: installer.php
	#  Release: v1.0.0
	#  Copyright: (c) 2015 - Addictive Software
	## -------------------------------------------------------

	/**
	 * --------------------------------------------------------------------
	 * INITIALIZE
	 * --------------------------------------------------------------------
	 */

	// Load kernel modules
	require_once("../kernel/Core.php");
	require_once("../kernel/Html.php");
	require_once("../kernel/String.php");
	require_once("../kernel/Database.php");

	// Get step number
	$step = Html::Request("step");

	// Get user data
	$data = $_POST;


	/**
	 * --------------------------------------------------------------------
	 * OK, LET'S DO IT
	 * --------------------------------------------------------------------
	 */

	switch($step) {

		/**
		 * --------------------------------------------------------------------
		 * SAVE CONFIGURATION FILE
		 * --------------------------------------------------------------------
		 */

		case 1:
			// Check if config.php is writable (CHMOD 777)
			if(is_writable("../config.php")) {
				$handle = fopen("../config.php", "w");

				$file_content = "<?php
	// Addictive Community configuration file for MySQL
	\$config['db_server']   = \"{$data['db_server']}\";
	\$config['db_username'] = \"{$data['db_username']}\";
	\$config['db_password'] = \"{$data['db_password']}\";
	\$config['db_database'] = \"{$data['db_database']}\";
	\$config['db_prefix']   = \"c_\";
?>";

				if(fwrite($handle, $file_content)) {
					$status = 1;
					fclose($handle);
				}
				else {
					$status = 0;
				}
			}
			else {
				$status = 0; // Error
			}

			$description = "Save configuration file";
			break;


		/**
		 * --------------------------------------------------------------------
		 * CHECK SAVED INFORMATION AND TRY TO CONNECT TO DATABASE
		 * --------------------------------------------------------------------
		 */

		case 2:
			// Get brand new config.php file
			require("../config.php");
			// Try to connect to database using config.php data
			$database = new Database($config);

			$status      = ($database) ? 1 : 0;
			$description = "Check information and connect to database";
			break;


		/**
		 * --------------------------------------------------------------------
		 * EXTRACT TABLES
		 * --------------------------------------------------------------------
		 */

		case 3:
			// Get config file and connect to Database
			require("../config.php");
			$database = new Database($config);

			// Avoid PHP timeout
			set_time_limit(0);

			// Get SQL file and its content
			$file = "sql/tables.sql";
			$handle = fopen($file, "r");

			if($handle) {
				$sql_query = fread($handle, filesize($file));
				$queries = explode("\n", $sql_query);

				foreach($queries as $value) {
					$query = $database->Query($value);
				}

				$status = ($query) ? 1 : 0;
			}
			else {
				$status = 0;
			}

			$description = "Extract table structure";
			break;


		/**
		 * --------------------------------------------------------------------
		 * INSERT INITIAL DATA AND SETTINGS
		 * --------------------------------------------------------------------
		 */

		case 4:
			// Get config file and connect to Database
			require("../config.php");
			$database = new Database($config);
			$errors = false;

			// Generate a random security hash and key
			$salt_hash = hash("sha1", mt_rand() . microtime());
			$salt_key  = mt_rand(1,99);

			// Insert static data from data.sql

			$file = "sql/data.sql";
			$handle = fopen($file, "r");

			if($handle) {
				$sql_query = fread($handle, filesize($file));
				$queries = explode("\n", $sql_query);

				foreach($queries as $value) {
					$query = $database->Query($value);
					if(!$query) {
						$errors = true;
					}
				}
			}

			$community_info = array(
				'timestamp'      => time(),
				'community_name' => String::Sanitize($data['community_name']),
				'community_url'  => String::Sanitize($data['community_url'])
			);

			// Insert sample room, thread and post

			$sql[] = "INSERT INTO `c_rooms` (`r_id`, `name`, `description`, `url`, `order_n`, `threads`, `lastpost_date`, `lastpost_thread`, `lastpost_member`, `invisible`, `rules_title`, `rules_text`, `rules_visible`, `read_only`, `password`, `upload`, `perm_view`, `perm_post`, `perm_reply`) VALUES (1, 'A Test Room', 'You can edit or remove this room at any time.', NULL, 1, 1, {$community_info['timestamp']}, 1, 1, 0, '', '', 0, 0, '', 1, 'a:5:{i:0;s:3:\"V_1\";i:1;s:3:\"V_2\";i:2;s:3:\"V_3\";i:3;s:3:\"V_4\";i:4;s:3:\"V_5\";}', 'a:3:{i:0;s:3:\"V_1\";i:1;s:3:\"V_2\";i:2;s:3:\"V_3\";}', 'a:3:{i:0;s:3:\"V_1\";i:1;s:3:\"V_2\";i:2;s:3:\"V_3\";}');";
			$sql[] = "INSERT INTO `c_threads` (`t_id`, `title`, `slug`, `author_member_id`, `replies`, `views`, `start_date`, `room_id`, `tags`, `announcement`, `lastpost_date`, `lastpost_member_id`, `moved_to`, `locked`, `approved`, `with_bestanswer`) VALUES (1, 'Welcome', 'welcome', 1, 1, 0, {$community_info['timestamp']}, 1, NULL, 0, {$community_info['timestamp']}, 1, NULL, 0, 1, 0);";
			$sql[] = "INSERT INTO `c_posts` (`p_id`, `author_id`, `thread_id`, `post_date`, `attach_id`, `attach_clicks`, `ip_address`, `post`, `edit_time`, `edit_author`, `best_answer`, `first_post`) VALUES (1, 1, 1, {$community_info['timestamp']}, NULL, NULL, '127.0.0.1', '<p>Welcome to your new Addictive Community.</p><p>This is simply a test message confirming that the installation was successful.</p>', NULL, NULL, 0, 1);";

			// Insert configuration file

			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_community_name', '{$community_info['community_name']}');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_community_url', '{$community_info['community_url']}');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_website_name', 'My Website');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_website_url', 'http://');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_community_logo', 'logo.png');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_sidebar_online', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_sidebar_stats', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('template_default_set', 'default');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('language_default_set', 'en_US');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('theme_default_set', 'default-light');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('emoticon_default_set', 'default');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('seo_description', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('seo_keywords', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('date_long_format', 'd M Y, H:i');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('date_short_format', 'd M Y');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('date_default_offset', '0');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_session_expiration', '900');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_member_enable_signature', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_member_enable_avatar_upload', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('threads_per_page', '10');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('thread_posts_per_page', '10');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('thread_posts_hot', '15');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('thread_best_answer_all_pages', 'false');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('thread_obsolete', 'false');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('thread_obsolete_value', '60');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('thread_allow_emoticons', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_allow_guest_post', 'false');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_offline', 'false');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_disable_registrations', 'false');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_bread_separator', '&rang;');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('member_pm_enable', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('member_pm_storage', '100');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_calendar_enable', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_smtp', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_username', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_password', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_port', '587');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_authentication', 'true');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_auth_method', 'tls');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_from', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_email_from_name', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_security_validation', 'false');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('general_warning_max', '5');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('security_salt_hash', '{$salt_hash}');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('security_salt_key', '{$salt_key}');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('language_bad_words', '');";
			$sql[] = "INSERT INTO `c_config` (`index`, `value`) VALUES ('language_bad_words_replacement', '#####');";

			foreach($sql as $value) {
				$query = $database->Query($value);
				if(!$query) {
					$errors = true;
				}
			}

			$status      = ($errors) ? 0 : 1;
			$description = "Insert initial data and settings";
			break;


		/**
		 * --------------------------------------------------------------------
		 * SAVE USER INFORMATION
		 * --------------------------------------------------------------------
		 */

		case 5:
			// Get config file and connect to Database
			require("../config.php");
			$database = new Database($config);

			// Get security hash key
			$Db->Query("SELECT * FROM c_config c WHERE `index` = 'security_salt_hash' OR `index` = 'security_salt_key';");
			$_salt = $Db->FetchToArray();

			$salt = array(
				"hash" => $_salt[0]['value'],
				"key"  => $_salt[1]['value']
			);

			// Get administrator account data
			$admin_info = array(
				'username' => String::Sanitize($data['admin_username']),
				'password' => String::PasswordEncrypt($data['admin_password'], $salt),
				'email'    => String::Sanitize($data['admin_email']),
				'joined'   => time()
			);

			// Build SQL

			$insert_admin_query = "INSERT INTO `c_members` (`username`, `password`, `email`, `hide_email`, `ip_address`, `joined`, `usergroup`, `member_title`, `location`, `profile`, `gender`, `b_day`, `b_month`, `b_year`, `photo`, `photo_type`, `website`, `im_windowslive`, `im_skype`, `im_facebook`, `im_twitter`, `im_yim`, `im_aol`, `posts`, `lastpost_date`, `signature`, `template`, `language`, `warn_level`, `warn_date`, `last_activity`, `time_offset`, `dst`, `show_birthday`, `show_gender`, `token`) VALUES ('{$admin_info['username']}', '{$admin_info['password']}', '{$admin_info['email']}', 1, '', {$admin_info['joined']}, 1, '', '', '', '', NULL, NULL, NULL, '', 'gravatar', '', '', '', '', '', '', '', 1, 1367848084, '', 'default', 'en_US', NULL, NULL, 0, '0', 0, 1, 1, '')";

			$insert_admin = $database->Query($insert_admin_query);

			$status      = ($insert_admin) ? 1 : 0;
			$description = "Save user information";
			break;


		/**
		 * --------------------------------------------------------------------
		 * LOCK INSTALLER
		 * --------------------------------------------------------------------
		 */

		case 6:
			$status = (fopen(".lock", "w")) ? 1 : 0;
			$description = "Lock installer";
			break;

		default:
			$status = 0;
	}

	$data = array(
		'status' => $status,
		'step'   => $step,
		'time'   => microtime(),
		'desc'   => $description
	);

	echo json_encode($data);
