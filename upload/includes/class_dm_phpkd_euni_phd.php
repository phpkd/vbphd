<?php
/*==================================================================================*\
|| ################################################################################ ||
|| # Product Name: vB Username Change Manager               Version: 1.0.0 Beta.1 # ||
|| # Licence Number: {LicenceNumber}
|| # ---------------------------------------------------------------------------- # ||
|| # 																			  # ||
|| #          Copyright �2005-2008 PHP KingDom, Ltd. All Rights Reserved.         # ||
|| #       This file may not be redistributed in whole or significant part.       # ||
|| # 																			  # ||
|| # ------------- vB Username Change Manager IS NOT FREE SOFTWARE -------------- # ||
|| #           http://www.phpkd.org | http://www.phpkd.org/license.html           # ||
|| ################################################################################ ||
\*==================================================================================*/

if (!class_exists('vB_DataManager'))
{
	exit;
}

// required for convert_to_valid_html() and others
require_once(DIR . '/includes/adminfunctions.php');

/**
* Class to do data save/delete operations for Universities/Colleges/Departments/Professors/Items/Students)
*
* Example usage (updates eunixx with xid = 17):
*
* $exa = new vB_DataManager_PHPKD_EUNI_PhD();
* $exa->set_condition('xid = 17');
* $exa->set_info('xid', 17);
* $exa->set('title', 'Example');
* $exa->set('description', 'Detailed Description');
* $exa->save();
*
* @package	PHPKD
* @version	$Revision$
* @date		$Date$
*/
class vB_DataManager_PHPKD_EUNI_PhD extends vB_DataManager
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vB_DataManager_PHPKD_EUNI_PhD(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_data_start')) ? eval($hook) : false;
	}


	/**
	* Verifies that the given eunixx title is valid
	*
	* @param	string	Title
	*
	* @return	boolean
	*/
	function verify_title(&$title)
	{
		$this->set('title_clean', htmlspecialchars_uni(strip_tags($title), false));
		$title = convert_to_valid_html($title);


		if ($title == '')
		{
			$this->error('phpkd_euni_phd_invalid_title_specified');
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	* Converts & to &amp; and sets description_clean for use in meta tags
	*
	* @param	string	Title
	*
	* @return	boolean
	*/
	function verify_description(&$description)
	{
		$this->set('description_clean', htmlspecialchars_uni(strip_tags($description), false));
		$description = convert_to_valid_html($description);

		return true;
	}

	/**
	* Converts an array of 1/0 options into the options bitfield
	*
	* @param	array	Array of 1/0 values keyed with the bitfield names for the eunixx options bitfield
	*
	* @return	boolean	Returns true on success
	*/
	function verify_options(&$options)
	{
		#require_once(DIR . '/includes/functions_misc.php');
		#return $options = convert_array_to_bits($options, $this->registry->bf_misc_eunixxoptions);
		trigger_error("Can't set \$this->eunixx[options] directly - use \$this->set_bitfield('options', $bitname, $onoff) instead", E_USER_ERROR);
	}


	/**
	* Any checks to run immediately before saving. If returning false, the save will not take place.
	*
	* @param	boolean	Do the query?
	*
	* @return	boolean	True on success; false if an error occurred
	*/
	function pre_save($doquery = true)
	{
		if ($this->presave_called !== null)
		{
			return $this->presave_called;
		}

		$return_value = true;
		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_data_presave')) ? eval($hook) : false;

		$this->presave_called = $return_value;
		return $return_value;
	}
}


/**
* Class to do data save/delete operations for Universities
*
* @package	PHPKD
* @version	$Revision$
* @date		$Date$
*/
class vB_DataManager_PHPKD_EUNI_PhD_University extends vB_DataManager_PHPKD_EUNI_PhD
{
/**
	* Array of recognised and required fields for universities, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'uid'               => array(TYPE_UINT,       REQ_INCR, VF_METHOD, 'verify_nonzero'),
		'styleid'           => array(TYPE_INT,        REQ_NO,   'if ($data < 0) { $data = 0; } return true;'),
		'title'             => array(TYPE_STR,        REQ_YES,  VF_METHOD),
		'title_clean'       => array(TYPE_STR,        REQ_YES),
		'description'       => array(TYPE_STR,        REQ_NO,   VF_METHOD),
		'description_clean' => array(TYPE_STR,        REQ_NO),
		'options'           => array(TYPE_ARRAY_BOOL, REQ_AUTO),
		'displayorder'      => array(TYPE_UINT,       REQ_NO),
		'collegecount'      => array(TYPE_UINT,       REQ_NO),
		'departmentcount'   => array(TYPE_UINT,       REQ_NO),
		'professorcount'    => array(TYPE_UINT,       REQ_NO),
		'itemcount'         => array(TYPE_UINT,       REQ_NO),
		'studentcount'      => array(TYPE_UINT,       REQ_NO),
		'password'          => array(TYPE_NOTRIM,     REQ_NO),
		'link'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'logo'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'childlist'         => array(TYPE_STR,        REQ_AUTO),
		'showprivate'       => array(TYPE_UINT,       REQ_NO,   'if ($data > 3) { $data = 0; } return true;')
	);

	/**
	* Array of field names that are bitfields, together with the name of the variable in the registry with the definitions.
	* For example: var $bitfields = array('options' => 'bf_misc_useroptions', 'permissions' => 'bf_misc_moderatorpermissions')
	*
	* @var	array
	*/
	var $bitfields = array('options' => 'bf_misc_phpkdeuniphduniversity');

	/**
	* The main table this class deals with
	*
	* @var	string
	*/
	var $table = 'phpkd_euni_phd_university';

	/**
	* Array to store stuff to save to university table
	*
	* @var	array
	*/
	var $phpkd_euni_phd_university = array();

	/**
	* Condition template for update query
	*
	* @var	array
	*/
	var $condition_construct = array('uid = %1$d', 'uid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vB_DataManager_PHPKD_EUNI_PhD_University(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_university_data_start')) ? eval($hook) : false;
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed for each record updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_each($doquery = true)
	{
		if ($this->condition AND $this->info['applypwdtochild'] AND isset($this->phpkd_euni_phd_university['password']) AND $this->phpkd_euni_phd_university['password'] != $this->existing['password'])
		{
			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_college
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_university['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['uid'] . "', parentlist)
    		");

			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_department
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_university['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['uid'] . "', parentlist)
    		");

			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_professor
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_university['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['uid'] . "', parentlist)
    		");

			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_item
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_university['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['uid'] . "', parentlist)
    		");
		}

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_university_data_postsave')) ? eval($hook) : false;
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed once after all records are updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_once($doquery = true)
	{
		if (empty($this->info['disable_cache_rebuild']))
		{
			require_once(DIR . '/includes/adminfunctions_phpkd_euni_phd.php');
			build_university_permissions();
		}
	}


	/**
	* Deletes a university and its associated data from the database
	*/
	function delete()
	{
		// fetch list of universities and the child colleges and related departments to delete
		$universitylist = '';
		$collegelist = '';
		$departmentlist = '';

		$universities = $this->dbobject->query_read_slave("SELECT uid FROM " . TABLE_PREFIX . "phpkd_euni_phd_university WHERE " . $this->condition);
		while($thisuniversity = $this->dbobject->fetch_array($universities))
		{
			$universitylist .= ',' . $thisuniversity['uid'];
		}
		$this->dbobject->free_result($universities);

		$universitylist = substr($universitylist, 1);
		$collegelist = substr($collegelist, 1);
		$departmentlist = substr($departmentlist, 1);

		if ($universitylist == '')
		{
			// nothing to do
			$this->error('phpkd_euni_phd_invalid_university_specified');
		}
		else
		{
			/*
			 ~~~~~~~~~~~~~~~~~~~~~~~~~
			 * Deleting related Data
			 ~~~~~~~~~~~~~~~~~~~~~~~~~
			$condition = "uid IN ($universitylist)";

			// delete from extra data tables
			$this->db_delete(TABLE_PREFIX, 'forumpermission', $condition);
			$this->db_delete(TABLE_PREFIX, 'access',          $condition);
			$this->db_delete(TABLE_PREFIX, 'moderator',       $condition);
			$this->db_delete(TABLE_PREFIX, 'announcement',    $condition);
			$this->db_delete(TABLE_PREFIX, 'subscribeforum',  $condition);
			$this->db_delete(TABLE_PREFIX, 'tachyforumpost',  $condition);
			$this->db_delete(TABLE_PREFIX, 'podcast',         $condition);
			$this->db_delete(TABLE_PREFIX, 'forumprefixset',  $condition);

			require_once(DIR . '/includes/functions_databuild.php');

			// delete threads in specified forums
			$threads = $this->dbobject->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "thread WHERE $condition");
			while ($thread = $this->dbobject->fetch_array($threads))
			{
				$threadman =& datamanager_init('Thread', $this->registry, ERRTYPE_SILENT, 'threadpost');
				$threadman->set_existing($thread);
				$threadman->set_info('skip_moderator_log', true);
				$threadman->delete($this->registry->forumcache["$thread[forumid]"]['options'] & $this->registry->bf_misc_forumoptions['countposts']);
				unset($threadman);
			}
			$this->dbobject->free_result($threads);

			$this->db_delete(TABLE_PREFIX, 'forum', $condition);

			build_forum_permissions();

			($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_university_data_delete')) ? eval($hook) : false;
			*/
		}
	}
}


/**
* Class to do data save/delete operations for Universities
*
* @package	PHPKD
* @version	$Revision$
* @date		$Date$
*/
class vB_DataManager_PHPKD_EUNI_PhD_College extends vB_DataManager_PHPKD_EUNI_PhD
{
/**
	* Array of recognised and required fields for colleges, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'uid'               => array(TYPE_UINT,       REQ_INCR, VF_METHOD, 'verify_nonzero'),
		'styleid'           => array(TYPE_INT,        REQ_NO,   'if ($data < 0) { $data = 0; } return true;'),
		'title'             => array(TYPE_STR,        REQ_YES,  VF_METHOD),
		'title_clean'       => array(TYPE_STR,        REQ_YES),
		'description'       => array(TYPE_STR,        REQ_NO,   VF_METHOD),
		'description_clean' => array(TYPE_STR,        REQ_NO),
		'options'           => array(TYPE_ARRAY_BOOL, REQ_AUTO),
		'displayorder'      => array(TYPE_UINT,       REQ_NO),
		'departmentcount'   => array(TYPE_UINT,       REQ_NO),
		'professorcount'    => array(TYPE_UINT,       REQ_NO),
		'itemcount'         => array(TYPE_UINT,       REQ_NO),
		'studentcount'      => array(TYPE_UINT,       REQ_NO),
		'parentid'          => array(TYPE_INT,        REQ_YES,  VF_METHOD),
		'password'          => array(TYPE_NOTRIM,     REQ_NO),
		'link'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'logo'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'parentlist'        => array(TYPE_STR,        REQ_AUTO, 'return preg_match(\'#^(\d+,)*-1$#\', $data);'),
		'childlist'         => array(TYPE_STR,        REQ_AUTO),
		'showprivate'       => array(TYPE_UINT,       REQ_NO,   'if ($data > 3) { $data = 0; } return true;')
	);

	/**
	* Array of field names that are bitfields, together with the name of the variable in the registry with the definitions.
	* For example: var $bitfields = array('options' => 'bf_misc_useroptions', 'permissions' => 'bf_misc_moderatorpermissions')
	*
	* @var	array
	*/
	var $bitfields = array('options' => 'bf_misc_phpkdeuniphdcollege');

	/**
	* The main table this class deals with
	*
	* @var	string
	*/
	var $table = 'phpkd_euni_phd_college';

	/**
	* Array to store stuff to save to college table
	*
	* @var	array
	*/
	var $phpkd_euni_phd_college = array();

	/**
	* Condition template for update query
	*
	* @var	array
	*/
	var $condition_construct = array('cid = %1$d', 'cid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vB_DataManager_PHPKD_EUNI_PhD_College(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_college_data_start')) ? eval($hook) : false;
	}


	/**
	* Verifies that the parent college specified exists and is a valid parent for this college
	*
	* @param	integer	Parent College ID
	*
	* @return	boolean	Returns true if the parent id is valid, and the parent college specified exists
	*/
	function verify_parentid(&$parentid)
	{
		if ($parentid <= 0)
		{
			$this->error('phpkd_euni_phd_cant_parent_college_to_none');
			return false;
		}
		else if (!isset($this->registry->phpkdeuniphduniversity["$parentid"]))
		{
			$this->error('phpkd_euni_phd_invalid_university_specified');
			return false;
		}
		else
		{
			// no condition specified, so it's not an existing forum...
			return true;
		}
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed for each record updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_each($doquery = true)
	{
		if ($this->condition AND $this->info['applypwdtochild'] AND isset($this->phpkd_euni_phd_college['password']) AND $this->phpkd_euni_phd_college['password'] != $this->existing['password'])
		{
			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_college
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_college['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['cid'] . "', parentlist)
    		");

			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_department
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_college['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['cid'] . "', parentlist)
    		");

			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_professor
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_college['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['cid'] . "', parentlist)
    		");

			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "phpkd_euni_phd_item
         		SET password = '" . $this->dbobject->escape_string($this->phpkd_euni_phd_college['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['cid'] . "', parentlist)
    		");
		}

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_college_data_postsave')) ? eval($hook) : false;
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed once after all records are updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_once($doquery = true)
	{
		if (empty($this->info['disable_cache_rebuild']))
		{
			require_once(DIR . '/includes/adminfunctions_phpkd_euni_phd.php');
			build_college_permissions();
		}
	}


	/**
	* Deletes a college and its associated data from the database
	*/
	function delete()
	{
		// fetch list of colleges and the related departments to delete
		$collegelist = '';
		$departmentlist = '';

		$colleges = $this->dbobject->query_read_slave("SELECT cid FROM " . TABLE_PREFIX . "phpkd_euni_phd_college WHERE " . $this->condition);
		while($thiscollege = $this->dbobject->fetch_array($colleges))
		{
			$collegelist .= ',' . $thiscollege['cid'];
		}
		$this->dbobject->free_result($colleges);

		$collegelist = substr($collegelist, 1);
		$departmentlist = substr($departmentlist, 1);

		if ($collegelist == '')
		{
			// nothing to do
			$this->error('phpkd_euni_phd_invalid_college_specified');
		}
		else
		{
			/*
			 ~~~~~~~~~~~~~~~~~~~~~~~~~
			 * Deleting related Data
			 ~~~~~~~~~~~~~~~~~~~~~~~~~
			$condition = "cid IN ($collegelist)";

			// delete from extra data tables
			$this->db_delete(TABLE_PREFIX, 'forumpermission', $condition);
			$this->db_delete(TABLE_PREFIX, 'access',          $condition);
			$this->db_delete(TABLE_PREFIX, 'moderator',       $condition);
			$this->db_delete(TABLE_PREFIX, 'announcement',    $condition);
			$this->db_delete(TABLE_PREFIX, 'subscribeforum',  $condition);
			$this->db_delete(TABLE_PREFIX, 'tachyforumpost',  $condition);
			$this->db_delete(TABLE_PREFIX, 'podcast',         $condition);
			$this->db_delete(TABLE_PREFIX, 'forumprefixset',  $condition);

			require_once(DIR . '/includes/functions_databuild.php');

			// delete threads in specified forums
			$threads = $this->dbobject->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "thread WHERE $condition");
			while ($thread = $this->dbobject->fetch_array($threads))
			{
				$threadman =& datamanager_init('Thread', $this->registry, ERRTYPE_SILENT, 'threadpost');
				$threadman->set_existing($thread);
				$threadman->set_info('skip_moderator_log', true);
				$threadman->delete($this->registry->forumcache["$thread[forumid]"]['options'] & $this->registry->bf_misc_forumoptions['countposts']);
				unset($threadman);
			}
			$this->dbobject->free_result($threads);

			$this->db_delete(TABLE_PREFIX, 'forum', $condition);

			build_forum_permissions();

			($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_college_data_delete')) ? eval($hook) : false;
			*/
		}
	}
}


/**
* Class to do data save/delete operations for Departments
*
* @package	PHPKD
* @version	$Revision$
* @date		$Date$
*/
class vB_DataManager_PHPKD_EUNI_PhD_Department extends vB_DataManager_PHPKD_EUNI_PhD
{
/**
	* Array of recognised and required fields for departments, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'uid'               => array(TYPE_UINT,       REQ_INCR, VF_METHOD, 'verify_nonzero'),
		'styleid'           => array(TYPE_INT,        REQ_NO,   'if ($data < 0) { $data = 0; } return true;'),
		'title'             => array(TYPE_STR,        REQ_YES,  VF_METHOD),
		'title_clean'       => array(TYPE_STR,        REQ_YES),
		'description'       => array(TYPE_STR,        REQ_NO,   VF_METHOD),
		'description_clean' => array(TYPE_STR,        REQ_NO),
		'options'           => array(TYPE_ARRAY_BOOL, REQ_AUTO),
		'displayorder'      => array(TYPE_UINT,       REQ_NO),
		'professorcount'    => array(TYPE_UINT,       REQ_NO),
		'itemcount'         => array(TYPE_UINT,       REQ_NO),
		'studentcount'      => array(TYPE_UINT,       REQ_NO),
		'parentid'          => array(TYPE_INT,        REQ_YES,  VF_METHOD),
		'password'          => array(TYPE_NOTRIM,     REQ_NO),
		'link'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'logo'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'parentlist'        => array(TYPE_STR,        REQ_AUTO, 'return preg_match(\'#^(\d+,)*-1$#\', $data);'),
		'showprivate'       => array(TYPE_UINT,       REQ_NO,   'if ($data > 3) { $data = 0; } return true;')
	);

	/**
	* Array of field names that are bitfields, together with the name of the variable in the registry with the definitions.
	* For example: var $bitfields = array('options' => 'bf_misc_useroptions', 'permissions' => 'bf_misc_moderatorpermissions')
	*
	* @var	array
	*/
	var $bitfields = array('options' => 'bf_misc_phpkdeuniphddepartment');

	/**
	* The main table this class deals with
	*
	* @var	string
	*/
	var $table = 'phpkd_euni_phd_department';

	/**
	* Array to store stuff to save to department table
	*
	* @var	array
	*/
	var $phpkd_euni_phd_department = array();

	/**
	* Condition template for update query
	*
	* @var	array
	*/
	var $condition_construct = array('did = %1$d', 'did');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vB_DataManager_PHPKD_EUNI_PhD_Department(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_department_data_start')) ? eval($hook) : false;
	}


	/**
	* Verifies that the parent department specified exists and is a valid parent for this department
	*
	* @param	integer	Parent Department ID
	*
	* @return	boolean	Returns true if the parent id is valid, and the parent department specified exists
	*/
	function verify_parentid(&$parentid)
	{
		if ($parentid <= 0)
		{
			$this->error('phpkd_euni_phd_cant_parent_department_to_none');
			return false;
		}
		else if (!isset($this->registry->phpkdeuniphduniversity["$parentid"]))
		{
			$this->error('phpkd_euni_phd_invalid_college_specified');
			return false;
		}
		else
		{
			// no condition specified, so it's not an existing forum...
			return true;
		}
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed for each record updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_each($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_department_data_postsave')) ? eval($hook) : false;
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed once after all records are updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_once($doquery = true)
	{
		if (empty($this->info['disable_cache_rebuild']))
		{
			require_once(DIR . '/includes/adminfunctions_phpkd_euni_phd.php');
			build_department_permissions();
		}
	}


	/**
	* Deletes a department and its associated data from the database
	*/
	function delete()
	{
		// fetch list of departments and related data
		$departmentlist = '';

		$departments = $this->dbobject->query_read_slave("SELECT did FROM " . TABLE_PREFIX . "phpkd_euni_phd_department WHERE " . $this->condition);
		while($thisdepartment = $this->dbobject->fetch_array($departments))
		{
			$departmentlist .= ',' . $thisdepartment['did'];
		}
		$this->dbobject->free_result($departments);

		$departmentlist = substr($departmentlist, 1);

		if ($departmentlist == '')
		{
			// nothing to do
			$this->error('phpkd_euni_phd_invalid_department_specified');
		}
		else
		{
			/*
			 ~~~~~~~~~~~~~~~~~~~~~~~~~
			 * Deleting related Data
			 ~~~~~~~~~~~~~~~~~~~~~~~~~
			$condition = "did IN ($departmentlist)";

			// delete from extra data tables
			$this->db_delete(TABLE_PREFIX, 'forumpermission', $condition);
			$this->db_delete(TABLE_PREFIX, 'access',          $condition);
			$this->db_delete(TABLE_PREFIX, 'moderator',       $condition);
			$this->db_delete(TABLE_PREFIX, 'announcement',    $condition);
			$this->db_delete(TABLE_PREFIX, 'subscribeforum',  $condition);
			$this->db_delete(TABLE_PREFIX, 'tachyforumpost',  $condition);
			$this->db_delete(TABLE_PREFIX, 'podcast',         $condition);
			$this->db_delete(TABLE_PREFIX, 'forumprefixset',  $condition);

			require_once(DIR . '/includes/functions_databuild.php');

			// delete threads in specified forums
			$threads = $this->dbobject->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "thread WHERE $condition");
			while ($thread = $this->dbobject->fetch_array($threads))
			{
				$threadman =& datamanager_init('Thread', $this->registry, ERRTYPE_SILENT, 'threadpost');
				$threadman->set_existing($thread);
				$threadman->set_info('skip_moderator_log', true);
				$threadman->delete($this->registry->forumcache["$thread[forumid]"]['options'] & $this->registry->bf_misc_forumoptions['countposts']);
				unset($threadman);
			}
			$this->dbobject->free_result($threads);

			$this->db_delete(TABLE_PREFIX, 'forum', $condition);

			build_forum_permissions();

			($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_department_data_delete')) ? eval($hook) : false;
			*/
		}
	}
}


/**
* Class to do data save/delete operations for Universities
*
* @package	PHPKD
* @version	$Revision$
* @date		$Date$
*/
class vB_DataManager_PHPKD_EUNI_PhD_ASD extends vB_DataManager_PHPKD_EUNI_PhD
{
/**
	* Array of recognised and required fields for universities, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'uid'               => array(TYPE_UINT,       REQ_INCR, VF_METHOD, 'verify_nonzero'),
		'styleid'           => array(TYPE_INT,        REQ_NO,   'if ($data < 0) { $data = 0; } return true;'),
		'title'             => array(TYPE_STR,        REQ_YES,  VF_METHOD),
		'title_clean'       => array(TYPE_STR,        REQ_YES),
		'description'       => array(TYPE_STR,        REQ_NO,   VF_METHOD),
		'description_clean' => array(TYPE_STR,        REQ_NO),
		'options'           => array(TYPE_ARRAY_BOOL, REQ_AUTO),
		'displayorder'      => array(TYPE_UINT,       REQ_NO),
		'collegecount'      => array(TYPE_UINT,       REQ_NO),
		'departmentcount'   => array(TYPE_UINT,       REQ_NO),
		'professorcount'    => array(TYPE_UINT,       REQ_NO),
		'itemcount'         => array(TYPE_UINT,       REQ_NO),
		'studentcount'      => array(TYPE_UINT,       REQ_NO),
		'password'          => array(TYPE_NOTRIM,     REQ_NO),
		'link'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'logo'              => array(TYPE_STR,        REQ_NO), // do not use verify_link on this -- relative redirects are prefectly valid
		'childlist'         => array(TYPE_STR,        REQ_AUTO),
		'showprivate'       => array(TYPE_UINT,       REQ_NO,   'if ($data > 3) { $data = 0; } return true;')
	);

	/**
	* Array of field names that are bitfields, together with the name of the variable in the registry with the definitions.
	* For example: var $bitfields = array('options' => 'bf_misc_useroptions', 'permissions' => 'bf_misc_moderatorpermissions')
	*
	* @var	array
	*/
	var $bitfields = array('options' => 'bf_misc_phpkdeuniphduniversity');

	/**
	* The main table this class deals with
	*
	* @var	string
	*/
	var $table = 'phpkd_euni_phd_university';

	/**
	* Array to store stuff to save to university table
	*
	* @var	array
	*/
	var $phpkd_euni_phd_university = array();

	/**
	* Condition template for update query
	*
	* @var	array
	*/
	var $condition_construct = array('uid = %1$d', 'uid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vB_DataManager_PHPKD_EUNI_PhD(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_data_start')) ? eval($hook) : false;
	}

	/**
	* Verifies that the parent eunixx specified exists and is a valid parent for this eunixy
	*
	* @param	integer	Parent eunixx ID
	*
	* @return	boolean	Returns true if the parent id is valid, and the parent eunixx specified exists
	*/
	function verify_parentid(&$parentid)
	{
		if ($parentid == $this->fetch_field('uid'))
		{
			$this->error('cant_parent_forum_to_self');
			return false;
		}
		else if ($parentid <= 0)
		{
			$parentid = -1;
			return true;
		}
		else if (!isset($this->registry->forumcache["$parentid"]))
		{
			$this->error('invalid_forum_specified');
			return false;
		}
		else if ($this->condition !== null)
		{
			return $this->is_subforum_of($this->fetch_field('uid'), $parentid);
		}
		else
		{
			// no condition specified, so it's not an existing forum...
			return true;
		}
	}


	/**
	* Verifies that a given forum parent id is not one of its own children
	*
	* @param	integer	The ID of the current forum
	* @param	integer	The ID of the forum's proposed parentid
	*
	* @return	boolean	Returns true if the children of the given parent forum does not include the specified forum... or something
	*/
	function is_subforum_of($forumid, $parentid)
	{
		if (empty($this->registry->iforumcache))
		{
			cache_ordered_forums(0, 1);
		}

		if (is_array($this->registry->iforumcache["$forumid"]))
		{
			foreach ($this->registry->iforumcache["$forumid"] AS $curforumid)
			{
				if ($curforumid == $parentid OR !$this->is_subforum_of($curforumid, $parentid))
				{
					$this->error('cant_parent_forum_to_child');
					return false;
				}
			}
		}

		return true;
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed for each record updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_each($doquery = true)
	{
		if ($this->condition AND $this->info['applypwdtochild'] AND isset($this->forum['password']) AND $this->forum['password'] != $this->existing['password'])
		{
			$this->dbobject->query_write("
         		UPDATE " . TABLE_PREFIX . "forum
         		SET password = '" . $this->dbobject->escape_string($this->forum['password']) . "'
         		WHERE FIND_IN_SET('" . $this->existing['forumid'] . "', parentlist)
    		");
		}

		($hook = vBulletinHook::fetch_hook('forumdata_postsave')) ? eval($hook) : false;
	}


	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed once after all records are updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_once($doquery = true)
	{
		if (empty($this->info['disable_cache_rebuild']))
		{
			require_once(DIR . '/includes/adminfunctions.php');
			build_forum_permissions();
		}
	}


	/**
	* Deletes a eunixx and its associated data from the database
	*/
	function delete()
	{
		// fetch list of eunixxs to delete
		$forumlist = '';

		$forums = $this->dbobject->query_read_slave("SELECT forumid FROM " . TABLE_PREFIX . "forum WHERE " . $this->condition);
		while($thisforum = $this->dbobject->fetch_array($forums))
		{
			$forumlist .= ',' . $thisforum['forumid'];
		}
		$this->dbobject->free_result($forums);

		$forumlist = substr($forumlist, 1);

		if ($forumlist == '')
		{
			// nothing to do
			$this->error('invalid_forum_specified');
		}
		else
		{
			$condition = "forumid IN ($forumlist)";

			// delete from extra data tables
			$this->db_delete(TABLE_PREFIX, 'forumpermission', $condition);
			$this->db_delete(TABLE_PREFIX, 'access',          $condition);
			$this->db_delete(TABLE_PREFIX, 'moderator',       $condition);
			$this->db_delete(TABLE_PREFIX, 'announcement',    $condition);
			$this->db_delete(TABLE_PREFIX, 'subscribeforum',  $condition);
			$this->db_delete(TABLE_PREFIX, 'tachyforumpost',  $condition);
			$this->db_delete(TABLE_PREFIX, 'podcast',         $condition);
			$this->db_delete(TABLE_PREFIX, 'forumprefixset',  $condition);

			require_once(DIR . '/includes/functions_databuild.php');

			// delete threads in specified forums
			$threads = $this->dbobject->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "thread WHERE $condition");
			while ($thread = $this->dbobject->fetch_array($threads))
			{
				$threadman =& datamanager_init('Thread', $this->registry, ERRTYPE_SILENT, 'threadpost');
				$threadman->set_existing($thread);
				$threadman->set_info('skip_moderator_log', true);
				$threadman->delete($this->registry->forumcache["$thread[forumid]"]['options'] & $this->registry->bf_misc_forumoptions['countposts']);
				unset($threadman);
			}
			$this->dbobject->free_result($threads);

			$this->db_delete(TABLE_PREFIX, 'forum', $condition);

			build_forum_permissions();

			($hook = vBulletinHook::fetch_hook('forumdata_delete')) ? eval($hook) : false;
		}
	}
}
/*=================================================================================*\
|| ############################################################################### ||
|| # Version.: 1.0.0
|| # Revision: $Revision$
|| # Released: $Date$
|| ############################################################################### ||
\*=================================================================================*/
?>