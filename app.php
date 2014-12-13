<?php

	## ---------------------------------------------------
	#  ADDICTIVE COMMUNITY
	## ---------------------------------------------------
	#  Developed by Brunno Pleffken Hosti
	#  File: app.php
	#  Release: v1.0.0
	#  Copyright: (c) 2014 - Addictive Software
	## ---------------------------------------------------

	// --------------------------------------------
	// Application specific constants
	// --------------------------------------------

	define("VERSION", "v0.1.0");
	define("CHANNEL", "Alpha"); // e.g.: Alpha, Beta, Release Candidate, Final

	// --------------------------------------------
	// Are we in development or production env.?
	// --------------------------------------------

	define("DEV", true);

	// --------------------------------------------
	// Timing definition constants
	// --------------------------------------------

	define("SECONDS", 1);
	define("MINUTE", 60);
	define("HOUR", 3600);
	define("DAY", 86400);
	define("WEEK", 604800);
	define("MONTH", 2592000);
	define("YEAR", 31536000);

	// ---------------------------------------------------
	// List of mobile browsers user agent fragments
	// ---------------------------------------------------

	$mobileBrowser = array(
		"Android", "Windows Phone", "iPhone",
		"MeeGo", "Symbian", "SymbianOS", "Opera Mini"
		);

	// ---------------------------------------------------
	// Shortcut for echo
	// ---------------------------------------------------

	function __($string)
	{
		echo $string;
	}

?>
