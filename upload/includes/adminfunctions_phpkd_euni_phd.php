<?php
/*=================================================================================*\
|| ############################################################################### ||
|| # Product Name: Periodic Prune Pms                             Version: 1.0.0 # ||
|| # Licence Number: Free License                                                # ||
|| # --------------------------------------------------------------------------- # ||
|| #                                                                             # ||
|| #            Copyright ©2005-2008 PHP KingDom. Some Rights Reserved.          # ||
|| #      This file may be redistributed in whole or significant part under      # ||
|| #   "Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported"   # ||
|| # 																			 # ||
|| # ------------------ 'Periodic Prune Pms' IS FREE SOFTWARE ------------------ # ||
|| #        http://www.phpkd.net | http://www.phpkd.net/info/license/free        # ||
|| ############################################################################### ||
\*=================================================================================*/

error_reporting(E_ALL & ~E_NOTICE);

// #############################################################################
/**
* Returns a list of <option> tags representing the list of universities
*
* @param	integer	Selected University ID
* @param	boolean	Whether or not to display the 'Select University' option
* @param	string	If specified, name for the optional top element - no name, no display
*
* @return	string	List of <option> tags
*/
function construct_university_chooser($selectedid = -1, $displayselectuniversity = false, $topname = null)
{
	return construct_select_options(construct_university_chooser_options($displayselectuniversity, $topname), $selectedid);
}

// #############################################################################
/**
* Returns a list of <option> tags representing the list of universities
*
* @param	boolean	Whether or not to display the 'Select University' option
* @param	string	If specified, name for the optional top element - no name, no display
*
* @return	string	List of <option> tags
*/
function construct_university_chooser_options($displayselectuniversity = false, $topname = null)
{
	global $vbulletin, $vbphrase;

	$selectoptions = array();

	if ($displayselectuniversity)
	{
		$selectoptions[0] = $vbphrase['phpkd_euni_phd_select_university'];
	}

	if ($topname)
	{
		$selectoptions['-1'] = $topname;
		$startdepth = '--';
	}
	else
	{
		$startdepth = '';
	}

	foreach ($vbulletin->universitycache AS $uid => $university)
	{
		$selectoptions["$uid"] = construct_depth_mark($university['depth'], '--', $startdepth) . ' ' . $university['title'];
	}

	return $selectoptions;
}

// #############################################################################
/**
* Returns an array containing info for the specified university, or false if university is not found
*
* @param	integer	(ref) University ID
* @param	boolean	Whether or not to return the result from the universitycache if it exists
*
* @return	mixed
*/
function fetch_universityinfo(&$uid, $usecache = true)
{
	global $vbulletin;

	$uid = intval($uid);
	if (!$usecache OR !isset($vbulletin->universitycache["$uid"]))
	{
		if (isset($vbulletin->universitycache["$uid"]['permissions']))
		{
			$perms = $vbulletin->universitycache["$uid"]['permissions'];
		}

		$vbulletin->universitycache["$uid"] = $vbulletin->db->query_first_slave("
			SELECT university.*
			FROM " . TABLE_PREFIX . "phpkd_euni_phd_university AS university
			WHERE university.uid = $uid
		");
	}

	if (!$vbulletin->universitycache["$uid"])
	{
		return false;
	}

	if (isset($perms))
	{
		$vbulletin->universitycache["$uid"]['permissions'] = $perms;
	}

	// decipher 'options' bitfield
	$vbulletin->universitycache["$uid"]['options'] = intval($vbulletin->universitycache["$uid"]['options']);
	foreach($vbulletin->bf_misc_universityoptions AS $optionname => $optionval)
	{
		$vbulletin->universitycache["$uid"]["$optionname"] = (($vbulletin->universitycache["$uid"]['options'] & $optionval) ? 1 : 0);
	}

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_fetch_universityinfo')) ? eval($hook) : false;

	return $vbulletin->universitycache["$uid"];
}

// #############################################################################
/**
* Prints a row containing a <select> list of universities, complete with displayorder, parenting and depth information
*
* @param	string	text for the left cell of the table row
* @param	string	name of the <select>
* @param	mixed	selected <option>
* @param	string	name given to the -1 <option>
* @param	boolean	display the -1 <option> or not.
* @param	boolean	when true, allows multiple selections to be made. results will be stored in $name's array
*/
function print_university_chooser($title, $name, $selectedid = -1, $topname = null, $displayselectuniversity = false, $multiple = false)
{
	if ($displayselectforum AND $selectedid <= 0)
	{
		$selectedid = 0;
	}

	print_select_row($title, $name, construct_university_chooser_options($displayselectuniversity, $topname), $selectedid, 0, $multiple ? 10 : 0, $multiple);
}















/*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
For Advanced Permissions editor!
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// ###################### Start getforumpermissions #######################
// queries forumpermissions for a single forum and either returns the forumpermissions,
// or the usergroup default.
function fetch_forum_permissions($usergroupid, $forumid)
{
	global $vbulletin;

	// assign the permissions to the usergroup defaults
	$perms = $vbulletin->usergroupcache["$usergroupid"]['forumpermissions'];
	DEVDEBUG("FPerms: Usergroup Defaults: $perms");

	// get the parent list of the forum we are interested in, excluding -1
	$parentlist = substr($vbulletin->universitycache["$forumid"]['parentlist'], 0, -3);

	// query forum permissions for the forums in the parent list of the current one
	$fperms = $vbulletin->db->query_read("
		SELECT uid, uperms
		FROM " . TABLE_PREFIX . "phpkd_euni_phd_uperms as uperms
		WHERE grpid = $usergroupid
		AND uid IN($parentlist)
	");
	// no custom permissions found, return usergroup defaults
	if ($vbulletin->db->num_rows($fperms) == 0)
	{
		return array('forumpermissions' => $perms);
	}
	else
	{
		// assign custom permissions to forums
		$fp = array();
		while ($fperm = $vbulletin->db->fetch_array($fperms))
		{
			$fp["$fperm[forumid]"] = $fperm['forumpermissions'];
		}
		unset($fperm);
		$vbulletin->db->free_result($fperms);

		// run through each forum in the forum's parent list in order
		foreach(array_reverse(explode(',', $parentlist)) AS $parentid)
		{
			// if the current parent forum has a custom permission, use it
			if (isset($fp["$parentid"]))
			{
				$perms = $fp["$parentid"];
				DEVDEBUG("FPerms: Custom - forum '" . $vbulletin->universitycache["$parentid"]['title'] . "': $perms");
			}
		}

		// return the permissions, whatever they may be now.
		return array('forumpermissions' => $perms);
	}
}

// ###################### Start permboxes #######################
function print_forum_permission_rows($customword, $forumpermission = array(), $extra = '')
{
	global $vbphrase;

	print_label_row(
		"<b>$customword</b>",'
		<input type="button" class="button" value="' . $vbphrase['all_yes'] . '" onclick="' . iif($extra != '', 'if (js_set_custom()) { ') . ' js_check_all_option(this.form, 1);' . iif($extra != '', ' }') . '" class="button" />
		<input type="button" class="button" value=" ' . $vbphrase['all_no'] . ' " onclick="' . iif($extra != '', 'if (js_set_custom()) { ') . ' js_check_all_option(this.form, 0);' . iif($extra != '', ' }') . '" class="button" />
		<!--<input type="submit" class="button" value="Okay" class="button" />-->
	', 'tcat', 'middle');

	// Load permissions
	require_once(DIR . '/includes/class_bitfield_builder.php');

	$groupinfo = vB_Bitfield_Builder::fetch_permission_group('forumpermissions');

	foreach($groupinfo AS $grouptitle => $group)
	{
		print_table_header($vbphrase["$grouptitle"]);

		foreach ($group AS $permtitle => $permvalue)
		{
			print_yes_no_row($vbphrase["{$permvalue['phrase']}"], "forumpermission[$permtitle]", $forumpermission["$permtitle"], $extra);
		}

		//print_table_break();
		//print_column_style_code(array('width: 70%', 'width: 30%'));
	}

	($hook = vBulletinHook::fetch_hook('admin_fperms_form')) ? eval($hook) : false;
}

*/

/*=================================================================================*\
|| ############################################################################### ||
|| # Version.: 1.0.0
|| # Revision: $Revision$
|| # Released: $Date$
|| ############################################################################### ||
\*=================================================================================*/
?>