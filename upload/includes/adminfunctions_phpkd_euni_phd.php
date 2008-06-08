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

	foreach ($vbulletin->phpkdeuniphduniversity AS $uid => $university)
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
* @param	boolean	Whether or not to return the result from the phpkdeuniphduniversity if it exists
*
* @return	mixed
*/
function fetch_universityinfo(&$uid, $usecache = true)
{
	global $vbulletin;

	$uid = intval($uid);
	if (!$usecache OR !isset($vbulletin->phpkdeuniphduniversity["$uid"]))
	{
		if (isset($vbulletin->phpkdeuniphduniversity["$uid"]['permissions']))
		{
			$perms = $vbulletin->phpkdeuniphduniversity["$uid"]['permissions'];
		}

		$vbulletin->phpkdeuniphduniversity["$uid"] = $vbulletin->db->query_first_slave("
			SELECT university.*
			FROM " . TABLE_PREFIX . "phpkd_euni_phd_university AS university
			WHERE university.uid = $uid
		");
	}

	if (!$vbulletin->phpkdeuniphduniversity["$uid"])
	{
		return false;
	}

	if (isset($perms))
	{
		$vbulletin->phpkdeuniphduniversity["$uid"]['permissions'] = $perms;
	}

	// decipher 'options' bitfield
	$vbulletin->phpkdeuniphduniversity["$uid"]['options'] = intval($vbulletin->phpkdeuniphduniversity["$uid"]['options']);
	foreach($vbulletin->bf_misc_phpkdeuniphduniversity AS $optionname => $optionval)
	{
		$vbulletin->phpkdeuniphduniversity["$uid"]["$optionname"] = (($vbulletin->phpkdeuniphduniversity["$uid"]['options'] & $optionval) ? 1 : 0);
	}

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_fetch_universityinfo')) ? eval($hook) : false;

	return $vbulletin->phpkdeuniphduniversity["$uid"];
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


// #############################################################################
/**
* Rebuilds the $vbulletin->usergroupcache and $vbulletin->phpkdeuniphduniversity from the university/usergroup tables
*
* @param	boolean	If true, force a recalculation of the university parent and child lists
*/
function build_university_permissions($rebuild_genealogy = true)
{
	global $vbulletin;
	$vbulletin->phpkdeuniphduniversity = array();
	$universitydata = array();

	$newuniversitycache = $vbulletin->db->query_read("
		SELECT university.*
		FROM " . TABLE_PREFIX . "phpkd_euni_phd_university AS university
		ORDER BY displayorder
	");
	while ($newuniversity = $vbulletin->db->fetch_array($newuniversitycache))
	{
		foreach ($newuniversity AS $key => $val)
		{
			/* values which begin with 0 and are greater than 1 character are strings, since 01 would be an octal number in PHP */
			if (is_numeric($val) AND !(substr($val, 0, 1) == '0' AND strlen($val) > 1))
			{
				$newuniversity["$key"] += 0;
			}
		}
		$vbulletin->phpkdeuniphduniversity["$newuniversity[uid]"] = $newuniversity;
	}
	$vbulletin->db->free_result($newuniversitycache);

	// rebuild college parent/child lists
	if ($rebuild_genealogy)
	{
		build_university_genealogy();
	}

	build_datastore('phpkdeuniphduniversity', serialize($vbulletin->phpkdeuniphduniversity), 1);

	DEVDEBUG('updatePHPKDEUNIPhDUniversity( ) - Updated caches, ' . $vbulletin->db->affected_rows() . ' rows affected.');
}


// #############################################################################
/**
* Recalculates college child list, then saves them back to the phpkd_euni_phd_university table
*/
function build_university_genealogy()
{
	global $vbulletin;

	if (empty($vbulletin->phpkdeuniphdcollege))
	{
		return;
	}

	// build child lists
	foreach ($vbulletin->phpkdeuniphdcollege AS $cid => $college)
	{
		// child list
		// $vbulletin->phpkdeuniphdcollege["$cid"]['childlist'] = $cid;

		if (is_array($vbulletin->phpkdeuniphdcollege["$cid"]))
		{
			foreach ($vbulletin->phpkdeuniphdcollege["$cid"] AS $collegeid => $collegeparentid)
			{
				$vbulletin->phpkdeuniphdcollege["$cid"]['childlist'] .= ',' . $collegeid;
			}
		}

		$vbulletin->phpkdeuniphdcollege["$cid"]['childlist'] .= ',-1';
	}

	$childsql = '';
	foreach ($vbulletin->phpkdeuniphdcollege AS $cid => $college)
	{
		$childsql .= "	WHEN $cid THEN '$college[childlist]'
		";
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_college SET
			childlist = CASE cid
				$childsql
				ELSE childlist
			END
	");
}





// #############################################################################
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
* | Operating Colleges 
* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
*/ ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// #############################################################################





// #############################################################################
/**
* Returns a list of <option> tags representing the list of colleges
*
* @param	integer	Selected College ID
* @param	boolean	Whether or not to display the 'Select College' option
* @param	string	If specified, name for the optional top element - no name, no display
*
* @return	string	List of <option> tags
*/
function construct_college_chooser($selectedid = -1, $displayselectcollege = false, $topname = null)
{
	return construct_select_options(construct_college_chooser_options($displayselectcollege, $topname), $selectedid);
}

// #############################################################################
/**
* Returns a list of <option> tags representing the list of colleges
*
* @param	boolean	Whether or not to display the 'Select College' option
* @param	string	If specified, name for the optional top element - no name, no display
*
* @return	string	List of <option> tags
*/
function construct_college_chooser_options($displayselectcollege = false, $topname = null)
{
	global $vbulletin, $vbphrase;

	$selectoptions = array();

	if ($displayselectcollege)
	{
		$selectoptions[0] = $vbphrase['phpkd_euni_phd_select_college'];
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

	foreach ($vbulletin->phpkdeuniphdcollege AS $cid => $college)
	{
		$selectoptions["$cid"] = construct_depth_mark($college['depth'], '--', $startdepth) . ' ' . $college['title'];
	}

	return $selectoptions;
}

// #############################################################################
/**
* Returns an array containing info for the specified college, or false if college is not found
*
* @param	integer	(ref) College ID
* @param	boolean	Whether or not to return the result from the phpkdeuniphdcollege if it exists
*
* @return	mixed
*/
function fetch_collegeinfo(&$cid, $usecache = true)
{
	global $vbulletin;

	$cid = intval($cid);
	if (!$usecache OR !isset($vbulletin->phpkdeuniphdcollege["$cid"]))
	{
		if (isset($vbulletin->phpkdeuniphdcollege["$cid"]['permissions']))
		{
			$perms = $vbulletin->phpkdeuniphdcollege["$cid"]['permissions'];
		}

		$vbulletin->phpkdeuniphdcollege["$cid"] = $vbulletin->db->query_first_slave("
			SELECT college.*
			FROM " . TABLE_PREFIX . "phpkd_euni_phd_college AS college
			WHERE college.cid = $cid
		");
	}

	if (!$vbulletin->phpkdeuniphdcollege["$cid"])
	{
		return false;
	}

	if (isset($perms))
	{
		$vbulletin->phpkdeuniphdcollege["$cid"]['permissions'] = $perms;
	}

	// decipher 'options' bitfield
	$vbulletin->phpkdeuniphdcollege["$cid"]['options'] = intval($vbulletin->phpkdeuniphdcollege["$cid"]['options']);
	foreach($vbulletin->bf_misc_phpkdeuniphdcollege AS $optionname => $optionval)
	{
		$vbulletin->phpkdeuniphdcollege["$cid"]["$optionname"] = (($vbulletin->phpkdeuniphdcollege["$cid"]['options'] & $optionval) ? 1 : 0);
	}

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_fetch_collegeinfo')) ? eval($hook) : false;

	return $vbulletin->phpkdeuniphdcollege["$cid"];
}

// #############################################################################
/**
* Prints a row containing a <select> list of colleges, complete with displayorder, parenting and depth information
*
* @param	string	text for the left cell of the table row
* @param	string	name of the <select>
* @param	mixed	selected <option>
* @param	string	name given to the -1 <option>
* @param	boolean	display the -1 <option> or not.
* @param	boolean	when true, allows multiple selections to be made. results will be stored in $name's array
*/
function print_college_chooser($title, $name, $selectedid = -1, $topname = null, $displayselectcollege = false, $multiple = false)
{
	if ($displayselectforum AND $selectedid <= 0)
	{
		$selectedid = 0;
	}

	print_select_row($title, $name, construct_college_chooser_options($displayselectcollege, $topname), $selectedid, 0, $multiple ? 10 : 0, $multiple);
}


// #############################################################################
/**
* Rebuilds the $vbulletin->usergroupcache and $vbulletin->phpkdeuniphdcollege from the college/usergroup tables
*
* @param	boolean	If true, force a recalculation of the college parent and child lists
*/
function build_college_permissions($rebuild_genealogy = true)
{
	global $vbulletin;
	$vbulletin->phpkdeuniphdcollege = array();
	$collegedata = array();

	$newcollegecache = $vbulletin->db->query_read("
		SELECT college.*
		FROM " . TABLE_PREFIX . "phpkd_euni_phd_college AS college
		ORDER BY displayorder
	");
	while ($newcollege = $vbulletin->db->fetch_array($newcollegecache))
	{
		foreach ($newcollege AS $key => $val)
		{
			/* values which begin with 0 and are greater than 1 character are strings, since 01 would be an octal number in PHP */
			if (is_numeric($val) AND !(substr($val, 0, 1) == '0' AND strlen($val) > 1))
			{
				$newcollege["$key"] += 0;
			}
		}
		$vbulletin->phpkdeuniphdcollege["$newcollege[cid]"] = $newcollege;
	}
	$vbulletin->db->free_result($newcollegecache);

	// rebuild college parent/child lists
	if ($rebuild_genealogy)
	{
		build_college_genealogy();
	}

	build_datastore('phpkdeuniphdcollege', serialize($vbulletin->phpkdeuniphdcollege), 1);

	DEVDEBUG('updatePHPKDEUNIPhDCollege( ) - Updated caches, ' . $vbulletin->db->affected_rows() . ' rows affected.');
}


// #############################################################################
/**
* Recalculates college parent and child lists, then saves them back to the phpkd_euni_phd_college table
*/
function build_college_genealogy()
{
	global $vbulletin;

	if (empty($vbulletin->phpkdeuniphdcollege))
	{
		return;
	}

	// build parent/child lists
	foreach ($vbulletin->phpkdeuniphdcollege AS $cid => $college)
	{
		// parent list
		$i = 0;
		$curid = $cid;

		$vbulletin->phpkdeuniphdcollege["$cid"]['parentlist'] = '';

		while ($curid != -1 AND $i++ < 1000)
		{
			if ($curid)
			{
				$vbulletin->phpkdeuniphdcollege["$cid"]['parentlist'] .= $curid . ',';
				$curid = $vbulletin->phpkdeuniphdcollege["$curid"]['parentid'];
			}
			else
			{
				global $vbphrase;
				if (!isset($vbphrase['phpkd_euni_phd_invalid_college_parenting']))
				{
					$vbphrase['phpkd_euni_phd_invalid_college_parenting'] = 'Invalid college parenting setup.';
				}
				trigger_error($vbphrase['phpkd_euni_phd_invalid_college_parenting'], E_USER_ERROR);
			}
		}

		$vbulletin->phpkdeuniphdcollege["$cid"]['parentlist'] .= '-1';

		// child list
		// $vbulletin->phpkdeuniphdcollege["$cid"]['childlist'] = $cid;

		if (is_array($vbulletin->phpkdeuniphdcollege["$cid"]))
		{
			foreach ($vbulletin->phpkdeuniphdcollege["$cid"] AS $collegeid => $collegeparentid)
			{
				$vbulletin->phpkdeuniphdcollege["$cid"]['childlist'] .= ',' . $collegeid;
			}
		}

		$vbulletin->phpkdeuniphdcollege["$cid"]['childlist'] .= ',-1';
	}

	$parentsql = '';
	$childsql = '';
	foreach ($vbulletin->phpkdeuniphdcollege AS $cid => $college)
	{
		$parentsql .= "	WHEN $cid THEN '$college[parentlist]'
		";
		$childsql .= "	WHEN $cid THEN '$college[childlist]'
		";
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_college SET
			parentlist = CASE cid
				$parentsql
				ELSE parentlist
			END,
			childlist = CASE cid
				$childsql
				ELSE childlist
			END
	");
}















// #############################################################################
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
* | Operating Departments 
* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
*/ ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// #############################################################################





// #############################################################################
/**
* Returns a list of <option> tags representing the list of departments
*
* @param	integer	Selected Department ID
* @param	boolean	Whether or not to display the 'Select Department' option
* @param	string	If specified, name for the optional top element - no name, no display
*
* @return	string	List of <option> tags
*/
function construct_department_chooser($selectedid = -1, $displayselectdepartment = false, $topname = null)
{
	return construct_select_options(construct_department_chooser_options($displayselectdepartment, $topname), $selectedid);
}


// #############################################################################
/**
* Returns a list of <option> tags representing the list of departments
*
* @param	boolean	Whether or not to display the 'Select Department' option
* @param	string	If specified, name for the optional top element - no name, no display
*
* @return	string	List of <option> tags
*/
function construct_department_chooser_options($displayselectdepartment = false, $topname = null)
{
	global $vbulletin, $vbphrase;

	$selectoptions = array();

	if ($displayselectdepartment)
	{
		$selectoptions[0] = $vbphrase['phpkd_euni_phd_select_department'];
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

	foreach ($vbulletin->phpkdeuniphddepartment AS $did => $department)
	{
		$selectoptions["$did"] = construct_depth_mark($department['depth'], '--', $startdepth) . ' ' . $department['title'];
	}

	return $selectoptions;
}


// #############################################################################
/**
* Returns an array containing info for the specified department, or false if department is not found
*
* @param	integer	(ref) Department ID
* @param	boolean	Whether or not to return the result from the phpkdeuniphddepartment if it exists
*
* @return	mixed
*/
function fetch_departmentinfo(&$did, $usecache = true)
{
	global $vbulletin;

	$did = intval($did);
	if (!$usecache OR !isset($vbulletin->phpkdeuniphddepartment["$did"]))
	{
		if (isset($vbulletin->phpkdeuniphddepartment["$did"]['permissions']))
		{
			$perms = $vbulletin->phpkdeuniphddepartment["$did"]['permissions'];
		}

		$vbulletin->phpkdeuniphddepartment["$did"] = $vbulletin->db->query_first_slave("
			SELECT department.*
			FROM " . TABLE_PREFIX . "phpkd_euni_phd_department AS department
			WHERE department.did = $did
		");
	}

	if (!$vbulletin->phpkdeuniphddepartment["$did"])
	{
		return false;
	}

	if (isset($perms))
	{
		$vbulletin->phpkdeuniphddepartment["$did"]['permissions'] = $perms;
	}

	// decipher 'options' bitfield
	$vbulletin->phpkdeuniphddepartment["$did"]['options'] = intval($vbulletin->phpkdeuniphddepartment["$did"]['options']);
	foreach($vbulletin->bf_misc_phpkdeuniphddepartment AS $optionname => $optionval)
	{
		$vbulletin->phpkdeuniphddepartment["$did"]["$optionname"] = (($vbulletin->phpkdeuniphddepartment["$did"]['options'] & $optionval) ? 1 : 0);
	}

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_fetch_departmentinfo')) ? eval($hook) : false;

	return $vbulletin->phpkdeuniphddepartment["$did"];
}


// #############################################################################
/**
* Rebuilds the $vbulletin->usergroupcache and $vbulletin->phpkdeuniphddepartment from the department/usergroup tables
*
* @param	boolean	If true, force a recalculation of the department parent list
*/
function build_department_permissions($rebuild_genealogy = true)
{
	global $vbulletin;
	$vbulletin->phpkdeuniphddepartment = array();
	$departmentdata = array();

	$newdepartmentcache = $vbulletin->db->query_read("
		SELECT department.*
		FROM " . TABLE_PREFIX . "phpkd_euni_phd_department AS department
		ORDER BY displayorder
	");
	while ($newdepartment = $vbulletin->db->fetch_array($newdepartmentcache))
	{
		foreach ($newdepartment AS $key => $val)
		{
			/* values which begin with 0 and are greater than 1 character are strings, since 01 would be an octal number in PHP */
			if (is_numeric($val) AND !(substr($val, 0, 1) == '0' AND strlen($val) > 1))
			{
				$newdepartment["$key"] += 0;
			}
		}
		$vbulletin->phpkdeuniphddepartment["$newdepartment[did]"] = $newdepartment;
	}
	$vbulletin->db->free_result($newdepartmentcache);

	// rebuild department parent list
	if ($rebuild_genealogy)
	{
		build_department_genealogy();
	}

	build_datastore('phpkdeuniphddepartment', serialize($vbulletin->phpkdeuniphddepartment), 1);

	DEVDEBUG('updatePHPKDEUNIPhDDepartment( ) - Updated caches, ' . $vbulletin->db->affected_rows() . ' rows affected.');
}


// #############################################################################
/**
* Recalculates department parent list, then saves them back to the phpkd_euni_phd_department table
*/
function build_department_genealogy()
{
	global $vbulletin;

	if (empty($vbulletin->phpkdeuniphddepartment))
	{
		return;
	}

	// build parent list
	foreach ($vbulletin->phpkdeuniphddepartment AS $did => $department)
	{
		// parent list
		$i = 0;
		$curid = $did;

		$vbulletin->phpkdeuniphddepartment["$did"]['parentlist'] = '';

		while ($curid != -1 AND $i++ < 1000)
		{
			if ($curid)
			{
				$vbulletin->phpkdeuniphddepartment["$did"]['parentlist'] .= $curid . ',';
				$curid = $vbulletin->phpkdeuniphddepartment["$curid"]['parentid'];
			}
			else
			{
				global $vbphrase;
				if (!isset($vbphrase['phpkd_euni_phd_invalid_department_parenting']))
				{
					$vbphrase['phpkd_euni_phd_invalid_department_parenting'] = 'Invalid department parenting setup.';
				}
				trigger_error($vbphrase['phpkd_euni_phd_invalid_department_parenting'], E_USER_ERROR);
			}
		}

		$vbulletin->phpkdeuniphddepartment["$did"]['parentlist'] .= '-1';
	}

	$parentsql = '';
	foreach ($vbulletin->phpkdeuniphddepartment AS $did => $department)
	{
		$parentsql .= "	WHEN $did THEN '$department[parentlist]'
		";
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_department SET
			parentlist = CASE did
				$parentsql
				ELSE parentlist
			END
	");
}














/*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
For Advanced Permissions editor!
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

// #############################################################################
// **
// * Rebuilds the $vbulletin->usergroupcache and $vbulletin->forumcache from the forum/usergroup tables
// *
// * @param	boolean	If true, force a recalculation of the forum parent and child lists
// 
function build_forum_permissions($rebuild_genealogy = true)
{
	global $vbulletin, $fpermcache;

	#echo "<h1>updateForumPermissions</h1>";

	$grouppermissions = array();
	$fpermcache = array();
	$vbulletin->forumcache = array();
	$vbulletin->usergroupcache = array();

	// query usergroups
	$usergroups = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY title");
	while ($usergroup = $vbulletin->db->fetch_array($usergroups))
	{
		foreach ($usergroup AS $key => $val)
		{
			if (is_numeric($val))
			{
				$usergroup["$key"] += 0;
			}
		}
		$vbulletin->usergroupcache["$usergroup[usergroupid]"] = $usergroup;
		// Profile pics disabled so don't inherit any of the profile pic settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['profilepicmaxwidth'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['profilepicmaxheight'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['profilepicmaxsize'] = -1;
		}
		// Avatars disabled so don't inherit any of the avatar settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['avatarmaxwidth'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['avatarmaxheight'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['avatarmaxsize'] = -1;
		}
		// Signature pics or signatures are disabled so don't inherit any of the signature pic settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic']) OR !($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigpicmaxwidth'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigpicmaxheight'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigpicmaxsize'] = -1;
		}

		// Signatures are disabled so don't inherit any of the signature settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxrawchars'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxchars'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxlines'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxsizebbcode'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaximages'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['signaturepermissions'] = 0;
		}

		($hook = vBulletinHook::fetch_hook('admin_build_forum_perms_group')) ? eval($hook) : false;

		$grouppermissions["$usergroup[usergroupid]"] = $usergroup['forumpermissions'];
	}
	unset($usergroup);
	$vbulletin->db->free_result($usergroups);
	DEVDEBUG('updateForumCache( ) - Queried Usergroups');

	$vbulletin->forumcache = array();
	$vbulletin->iforumcache = array();
	$forumdata = array();

	// get the vbulletin->iforumcache so we can traverse the forums in order within cache_forum_permissions
	$newforumcache = $vbulletin->db->query_read("
		SELECT forum.*" . (VB_AREA != 'Upgrade' ? ", NOT ISNULL(podcast.forumid) AS podcast" : "") . "
		FROM " . TABLE_PREFIX . "forum AS forum
		" . (VB_AREA != 'Upgrade' ? "LEFT JOIN " . TABLE_PREFIX . "podcast AS podcast ON (forum.forumid = podcast.forumid AND podcast.enabled = 1)" : "") . "
		ORDER BY displayorder
	");
	while ($newforum = $vbulletin->db->fetch_array($newforumcache))
	{
		foreach ($newforum AS $key => $val)
		{
			// values which begin with 0 and are greater than 1 character are strings, since 01 would be an octal number in PHP //
			if (is_numeric($val) AND !(substr($val, 0, 1) == '0' AND strlen($val) > 1))
			{
				$newforum["$key"] += 0;
			}
		}
		$vbulletin->iforumcache["$newforum[parentid]"]["$newforum[forumid]"] = $newforum['forumid'];
		$forumdata["$newforum[forumid]"] = $newforum;
	}
	$vbulletin->db->free_result($newforumcache);

	// get the forumcache into the order specified in $vbulletin->iforumcache
	$vbulletin->forumorder = array();
	fetch_forum_order();
	foreach ($vbulletin->forumorder AS $forumid => $depth)
	{
		$vbulletin->forumcache["$forumid"] =& $forumdata["$forumid"];
		$vbulletin->forumcache["$forumid"]['depth'] = $depth;
	}
	unset($vbulletin->forumorder);

	// rebuild forum parent/child lists
	if ($rebuild_genealogy)
	{
		build_forum_genealogy();
	}

	// query forum permissions
	$fperms = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "forumpermission");
	while ($fperm = $vbulletin->db->fetch_array($fperms))
	{
		$fpermcache["$fperm[forumid]"]["$fperm[usergroupid]"] = intval($fperm['forumpermissions']);

		($hook = vBulletinHook::fetch_hook('admin_build_forum_perms_forum')) ? eval($hook) : false;
	}
	unset($fperm);
	$vbulletin->db->free_result($fperms);
	DEVDEBUG('updateForumCache( ) - Queried Forum Pemissions');

	// call the function that will work out the forum permissions
	cache_forum_permissions($grouppermissions);

	// finally replace the existing cache templates
	build_datastore('usergroupcache', serialize($vbulletin->usergroupcache), 1);
	foreach(array_keys($vbulletin->forumcache) AS $forumid)
	{
		unset(
			$vbulletin->forumcache["$forumid"]['replycount'],
			$vbulletin->forumcache["$forumid"]['lastpost'],
			$vbulletin->forumcache["$forumid"]['lastposter'],
			$vbulletin->forumcache["$forumid"]['lastthread'],
			$vbulletin->forumcache["$forumid"]['lastthreadid'],
			$vbulletin->forumcache["$forumid"]['lasticonid'],
			$vbulletin->forumcache["$forumid"]['lastprefixid'],
			$vbulletin->forumcache["$forumid"]['threadcount']
		);
	}
	build_datastore('forumcache', serialize($vbulletin->forumcache), 1);

	DEVDEBUG('updateForumCache( ) - Updated caches, ' . $vbulletin->db->affected_rows() . ' rows affected.');
}

// #############################################################################
// **
// * Recursive function to build $vbulletin->forumorder - used to get the order of forums
// *
// * @param	integer	Initial parent forum ID to use
// * @param	integer	Initial depth of forums
// 
function fetch_forum_order($parentid = -1, $depth = 0)
{
	global $vbulletin;

	if (is_array($vbulletin->iforumcache["$parentid"]))
	{
		foreach ($vbulletin->iforumcache["$parentid"] AS $forumid)
		{
			$vbulletin->forumorder["$forumid"] = $depth;
			fetch_forum_order($forumid, $depth + 1);
		}
	}
}

// #############################################################################
// **
// * Recalculates forum parent and child lists, then saves them back to the forum table
// 
function build_forum_genealogy()
{
	global $vbulletin;

	if (empty($vbulletin->forumcache))
	{
		return;
	}

	// build parent/child lists
	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		// parent list
		$i = 0;
		$curid = $forumid;

		$vbulletin->forumcache["$forumid"]['parentlist'] = '';

		while ($curid != -1 AND $i++ < 1000)
		{
			if ($curid)
			{
				$vbulletin->forumcache["$forumid"]['parentlist'] .= $curid . ',';
				$curid = $vbulletin->forumcache["$curid"]['parentid'];
			}
			else
			{
				global $vbphrase;
				if (!isset($vbphrase['invalid_forum_parenting']))
				{
					$vbphrase['invalid_forum_parenting'] = 'Invalid forum parenting setup. Contact vBulletin support.';
				}
				trigger_error($vbphrase['invalid_forum_parenting'], E_USER_ERROR);
			}
		}

		$vbulletin->forumcache["$forumid"]['parentlist'] .= '-1';

		// child list
		$vbulletin->forumcache["$forumid"]['childlist'] = $forumid;
		fetch_forum_child_list($forumid, $forumid);
		$vbulletin->forumcache["$forumid"]['childlist'] .= ',-1';
	}

	$parentsql = '';
	$childsql = '';
	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		$parentsql .= "	WHEN $forumid THEN '$forum[parentlist]'
		";
		$childsql .= "	WHEN $forumid THEN '$forum[childlist]'
		";
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "forum SET
			parentlist = CASE forumid
				$parentsql
				ELSE parentlist
			END,
			childlist = CASE forumid
				$childsql
				ELSE childlist
			END
	");
}

// #############################################################################
// **
// * Recursive function to populate $vbulletin->forumcache with correct child list fields
// *
// * @param	integer	Forum ID to be updated
// * @param	integer	Parent forum ID
// 
function fetch_forum_child_list($mainforumid, $parentid)
{
	global $vbulletin;

	if (is_array($vbulletin->iforumcache["$parentid"]))
	{
		foreach ($vbulletin->iforumcache["$parentid"] AS $forumid => $forumparentid)
		{
			$vbulletin->forumcache["$mainforumid"]['childlist'] .= ',' . $forumid;
			fetch_forum_child_list($mainforumid, $forumid, $sql);
		}
	}
}

// #############################################################################
// **
// * Populates the $vbulletin->forumcache with calculated forum permissions for each usergroup
// *
// * NB: this function should only be called from build_forum_permissions()
// *
// * @param	integer	Initial permissions value
// * @param	integer	Parent forum id
// 
function cache_forum_permissions($permissions, $parentid = -1)
{
	global $vbulletin, $fpermcache;

	// abort if no child forums found
	if (!is_array($vbulletin->iforumcache["$parentid"]))
	{
		return;
	}

	// run through each child forum
	foreach($vbulletin->iforumcache["$parentid"] AS $forumid)
	{
		$forum =& $vbulletin->forumcache["$forumid"];

		// make a copy of the current permissions set up
		$perms = $permissions;

		// run through each usergroup
		foreach(array_keys($vbulletin->usergroupcache) AS $usergroupid)
		{
			// if there is a custom permission for the current usergroup, use it
			if (isset($fpermcache["$forumid"]["$usergroupid"]))
			{
				$perms["$usergroupid"] = $fpermcache["$forumid"]["$usergroupid"];
			}

			($hook = vBulletinHook::fetch_hook('admin_cache_forum_perms')) ? eval($hook) : false;

			// populate the current row of the forumcache permissions
			$forum['permissions']["$usergroupid"] = intval($perms["$usergroupid"]);
		}
		// recurse to child forums
		cache_forum_permissions($perms, $forum['forumid']);
	}
}

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
	$parentlist = substr($vbulletin->phpkdeuniphduniversity["$forumid"]['parentlist'], 0, -3);

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
				DEVDEBUG("FPerms: Custom - forum '" . $vbulletin->phpkdeuniphduniversity["$parentid"]['title'] . "': $perms");
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