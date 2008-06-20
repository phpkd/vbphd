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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision$');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('phpkd_euni_phd_acp');
$specialtemplates = 
	array(
		'phpkdeuniphduniversity',
		'phpkdeuniphdcollege',
		'phpkdeuniphddepartment'
	);

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_template.php');
require_once(DIR . '/includes/adminfunctions_phpkd_euni_phd.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminuniversities'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################


$vbulletin->input->clean_array_gpc('r', array(
	'uid' => TYPE_UINT,
	'cid' => TYPE_UINT,
	'did' => TYPE_UINT,
	'pid' => TYPE_UINT,
	'iid' => TYPE_UINT,
	'sid' => TYPE_UINT
));

log_admin_action
(
	iif
	(
		$vbulletin->GPC['uid'] != 0, " university id = " . $vbulletin->GPC['uid'],
		iif($vbulletin->GPC['cid'] != 0, "college id = " . $vbulletin->GPC['cid']),
		iif($vbulletin->GPC['did'] != 0, "department id = " . $vbulletin->GPC['did']),
		iif($vbulletin->GPC['pid'] != 0, "professor id = " . $vbulletin->GPC['pid']),
		iif($vbulletin->GPC['iid'] != 0, "item id = " . $vbulletin->GPC['iid']),
		iif($vbulletin->GPC['sid'] != 0, "student id = " . $vbulletin->GPC['sid'])
	)
);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['phpkd_euni_phd_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'manage';
}

($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_start')) ? eval($hook) : false;

// ###################### Start add university #######################
if ($_REQUEST['do'] == 'addu' OR $_REQUEST['do'] == 'editu')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'uid'  => TYPE_UINT,
		'duid' => TYPE_UINT
	));

	if ($_REQUEST['do'] == 'addu')
	{
		// get a list of other universities to base this one off of
		print_form_header('phpkd_euni_phd', 'addu');
		print_description_row(construct_table_help_button('duid') . '<b>' . $vbphrase['phpkd_euni_phd_create_university_based_off_of_university'] . '</b> <select name="duid" tabindex="1" class="bginput">' . construct_university_chooser() . '</select> <input type="submit" class="button" value="' . $vbphrase['phpkd_euni_phd_go'] . '" tabindex="1" />', 0, 2, 'tfoot', 'center');
		print_table_footer();
		// Set Defaults;
		$university = array(
			'title' => '',
			'description' => '',
			'link' => '',
			'logo' => '',
			'displayorder' => 1,
			'showprivate' => 0,
			'styleid' => '',
			'styleoverride' => 0,
			'password' => '',
			'canhavepassword' => 1,
			'canhavecontent' => 1,
			'active' => 1,
			'countcontent' => 1,
			'showonuniversityjump' => 1
		);

		if (!empty($vbulletin->GPC['duid']))
		{
			$newuniversity = fetch_universityinfo($vbulletin->GPC['duid']);
			foreach (array_keys($university) AS $title)
			{
				$university["$title"] = $newuniversity["$title"];
			}
		}

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_add_default')) ? eval($hook) : false;

		print_form_header('phpkd_euni_phd', 'updateu');
		print_table_header($vbphrase['phpkd_euni_phd_add_new_university']);
	}
	else
	{
		if (!($university = fetch_universityinfo($vbulletin->GPC['uid'], false)))
		{
			print_stop_message('phpkd_euni_phd_invalid_university_specified');
		}
		print_form_header('phpkd_euni_phd', 'updateu');
		print_table_header(construct_phrase($vbphrase['phpkd_euni_phd_x_y_id_z'], $vbphrase['phpkd_euni_phd_university'], $university['title'], $university['uid']));
		construct_hidden_code('uid', $vbulletin->GPC['uid']);
	}

	$university['title'] = str_replace('&amp;', '&', $university['title']);
	$university['description'] = str_replace('&amp;', '&', $university['description']);

	print_input_row($vbphrase['phpkd_euni_phd_university_title'], 'university[title]', $university['title']);
	print_textarea_row($vbphrase['phpkd_euni_phd_university_description'], 'university[description]', $university['description']);
	print_input_row($vbphrase['phpkd_euni_phd_university_link'], 'university[link]', $university['link']);
	print_input_row($vbphrase['phpkd_euni_phd_university_logo'], 'university[logo]', $university['logo']);
	print_input_row("$vbphrase[phpkd_euni_phd_display_order]<dfn>$vbphrase[phpkd_euni_phd_zero_equals_no_display]</dfn>", 'university[displayorder]', $university['displayorder']);

	print_table_header($vbphrase['phpkd_euni_phd_style_options']);

	if ($university['styleid'] == 0)
	{
		$university['styleid'] = -1; // to get the "use default style" option selected
	}
	print_style_chooser_row('university[styleid]', $university['styleid'], $vbphrase['phpkd_euni_phd_use_default_style'], $vbphrase['phpkd_euni_phd_custom_university_style'], 1);
	print_yes_no_row($vbphrase['phpkd_euni_phd_university_override_style_choice'], 'university[options][styleoverride]', $university['styleoverride']);

	print_table_header($vbphrase['phpkd_euni_phd_access_options']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_university_is_active'], 'university[options][active]', $university['active']);
	print_select_row($vbphrase['phpkd_euni_phd_show_private_university'], 'university[showprivate]', array($vbphrase['phpkd_euni_phd_use_default'], $vbphrase['phpkd_euni_phd_no'], $vbphrase['phpkd_euni_phd_yes_hide_counters'], $vbphrase['phpkd_euni_phd_yes_display_counters']), $university['showprivate']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_university_can_have_content'], 'university[options][canhavecontent]', $university['canhavecontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_count_content_in_university'], 'university[options][countcontent]', $university['countcontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_show_university_on_university_jump'], 'university[options][showonuniversityjump]', $university['showonuniversityjump']);
	print_input_row($vbphrase['phpkd_euni_phd_university_password'], 'university[password]', $university['password']);
	if ($_REQUEST['do'] == 'editu')
	{
		print_yes_no_row($vbphrase['phpkd_euni_phd_apply_password_to_children_colleges'], 'applypwdtochild', 0);
	}
	print_yes_no_row($vbphrase['phpkd_euni_phd_university_can_have_password'], 'university[options][canhavepassword]', $university['canhavepassword']);

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_editu_form')) ? eval($hook) : false;

	print_submit_row($vbphrase['phpkd_euni_phd_save']);
}

// ###################### Start update university #######################
if ($_POST['do'] == 'updateu')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'uid'               => TYPE_UINT,
		'applypwdtochild'   => TYPE_BOOL,
		'university'        => TYPE_ARRAY
	));

	$universitydata =& datamanager_init('PHPKD_EUNI_PhD_University', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');

	if ($vbulletin->GPC['uid'])
	{
		$universitydata->set_existing($vbulletin->phpkdeuniphduniversity[$vbulletin->GPC['uid']]);
		$universitydata->set_info('applypwdtochild', $vbulletin->GPC['applypwdtochild']);
	}

	foreach ($vbulletin->GPC['university'] AS $varname => $value)
	{
		if ($varname == 'options')
		{
			foreach ($value AS $key => $val)
			{
				$universitydata->set_bitfield('options', $key, $val);
			}
		}
		else
		{
			$universitydata->set($varname, $value);
		}
	}

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_updateu_save')) ? eval($hook) : false;

	$uid = $universitydata->save();
	if (!$vbulletin->GPC['uid'])
	{
		$vbulletin->GPC['uid'] = $uid;
	}

	define('CP_REDIRECT', "phpkd_euni_phd.php?do=manage&amp;u=" . $vbulletin->GPC['uid'] . "#university" . $vbulletin->GPC['uid']);
	print_stop_message('phpkd_euni_phd_saved_university_x_successfully', $vbulletin->GPC['university']['title']);
}

// ###################### Start add college #######################
if ($_REQUEST['do'] == 'addc' OR $_REQUEST['do'] == 'editc')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cid'      => TYPE_UINT,
		'dcid'     => TYPE_UINT,
		'parentid' => TYPE_UINT
	));

	if ($_REQUEST['do'] == 'addc')
	{
		// get a list of other colleges to base this one off of
		print_form_header('phpkd_euni_phd', 'addc');
		print_description_row(construct_table_help_button('dcid') . '<b>' . $vbphrase['phpkd_euni_phd_create_college_based_off_of_college'] . '</b> <select name="dcid" tabindex="1" class="bginput">' . construct_college_chooser() . '</select> <input type="submit" class="button" value="' . $vbphrase['phpkd_euni_phd_go'] . '" tabindex="1" />', 0, 2, 'tfoot', 'center');
		print_table_footer();
		// Set Defaults;
		$college = array(
			'title' => '',
			'description' => '',
			'link' => '',
			'logo' => '',
			'displayorder' => 1,
			'parentid' => $vbulletin->GPC['parentid'],
			'showprivate' => 0,
			'styleid' => '',
			'styleoverride' => 0,
			'password' => '',
			'canhavepassword' => 1,
			'canhavecontent' => 1,
			'active' => 1,
			'countcontent' => 1,
			'showoncollegejump' => 1
		);

		if (!empty($vbulletin->GPC['dcid']))
		{
			$newcollege = fetch_collegeinfo($vbulletin->GPC['dcid']);
			foreach (array_keys($college) AS $title)
			{
				$college["$title"] = $newcollege["$title"];
			}
		}

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_add_default')) ? eval($hook) : false;

		print_form_header('phpkd_euni_phd', 'updatec');
		print_table_header($vbphrase['phpkd_euni_phd_add_new_college']);
	}
	else
	{
		if (!($college = fetch_collegeinfo($vbulletin->GPC['cid'], false)))
		{
			print_stop_message('phpkd_euni_phd_invalid_college_specified');
		}
		print_form_header('phpkd_euni_phd', 'updatec');
		print_table_header(construct_phrase($vbphrase['phpkd_euni_phd_x_y_id_z'], $vbphrase['phpkd_euni_phd_college'], $college['title'], $college['cid']));
		construct_hidden_code('cid', $vbulletin->GPC['cid']);
	}

	$college['title'] = str_replace('&amp;', '&', $college['title']);
	$college['description'] = str_replace('&amp;', '&', $college['description']);

	print_input_row($vbphrase['phpkd_euni_phd_college_title'], 'college[title]', $college['title']);
	print_textarea_row($vbphrase['phpkd_euni_phd_college_description'], 'college[description]', $college['description']);
	print_input_row($vbphrase['phpkd_euni_phd_college_link'], 'college[link]', $college['link']);
	print_input_row($vbphrase['phpkd_euni_phd_college_logo'], 'college[logo]', $college['logo']);
	print_input_row("$vbphrase[phpkd_euni_phd_display_order]<dfn>$vbphrase[phpkd_euni_phd_zero_equals_no_display]</dfn>", 'college[displayorder]', $college['displayorder']);

	if ($vbulletin->GPC['uid'] != -1)
	{
		print_university_chooser($vbphrase['phpkd_euni_phd_parent_university'], 'college[parentid]', $college['parentid'], $vbphrase['phpkd_euni_phd_no_one']);
	}
	else
	{
		construct_hidden_code('parentid', 0);
	}

	print_table_header($vbphrase['phpkd_euni_phd_style_options']);

	if ($college['styleid'] == 0)
	{
		$college['styleid'] = -1; // to get the "use default style" option selected
	}
	print_style_chooser_row('college[styleid]', $college['styleid'], $vbphrase['phpkd_euni_phd_use_default_style'], $vbphrase['phpkd_euni_phd_custom_college_style'], 1);
	print_yes_no_row($vbphrase['phpkd_euni_phd_college_override_style_choice'], 'college[options][styleoverride]', $college['styleoverride']);

	print_table_header($vbphrase['phpkd_euni_phd_access_options']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_college_is_active'], 'college[options][active]', $college['active']);
	print_select_row($vbphrase['phpkd_euni_phd_show_private_college'], 'college[showprivate]', array($vbphrase['phpkd_euni_phd_use_default'], $vbphrase['phpkd_euni_phd_no'], $vbphrase['phpkd_euni_phd_yes_hide_counters'], $vbphrase['phpkd_euni_phd_yes_display_counters']), $college['showprivate']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_college_can_have_content'], 'college[options][canhavecontent]', $college['canhavecontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_count_content_in_college'], 'college[options][countcontent]', $college['countcontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_show_college_on_college_jump'], 'college[options][showoncollegejump]', $college['showoncollegejump']);
	print_input_row($vbphrase['phpkd_euni_phd_college_password'], 'college[password]', $college['password']);
	if ($_REQUEST['do'] == 'editc')
	{
		print_yes_no_row($vbphrase['phpkd_euni_phd_apply_password_to_children_departments'], 'applypwdtochild', 0);
	}
	print_yes_no_row($vbphrase['phpkd_euni_phd_college_can_have_password'], 'college[options][canhavepassword]', $college['canhavepassword']);

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_editc_form')) ? eval($hook) : false;

	print_submit_row($vbphrase['phpkd_euni_phd_save']);
}

// ###################### Start update college #######################
if ($_POST['do'] == 'updatec')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'cid'               => TYPE_UINT,
		'applypwdtochild'   => TYPE_BOOL,
		'college'           => TYPE_ARRAY
	));

	$collegedata =& datamanager_init('PHPKD_EUNI_PhD_College', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');

	if ($vbulletin->GPC['cid'])
	{
		$collegedata->set_existing($vbulletin->phpkdeuniphdcollege[$vbulletin->GPC['cid']]);
		$collegedata->set_info('applypwdtochild', $vbulletin->GPC['applypwdtochild']);
	}

	foreach ($vbulletin->GPC['college'] AS $varname => $value)
	{
		if ($varname == 'options')
		{
			foreach ($value AS $key => $val)
			{
				$collegedata->set_bitfield('options', $key, $val);
			}
		}
		else
		{
			$collegedata->set($varname, $value);
		}
	}

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_updatec_save')) ? eval($hook) : false;

	$cid = $collegedata->save();
	if (!$vbulletin->GPC['cid'])
	{
		$vbulletin->GPC['cid'] = $cid;
	}

	define('CP_REDIRECT', "phpkd_euni_phd.php?do=manage&amp;c=" . $vbulletin->GPC['cid'] . "#college" . $vbulletin->GPC['cid']);
	print_stop_message('phpkd_euni_phd_saved_college_x_successfully', $vbulletin->GPC['college']['title']);
}


// ###################### Start add department #######################
if ($_REQUEST['do'] == 'addd' OR $_REQUEST['do'] == 'editd')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'did'      => TYPE_UINT,
		'ddid'     => TYPE_UINT,
		'parentid' => TYPE_UINT
	));

	if ($_REQUEST['do'] == 'addd')
	{
		// get a list of other departments to base this one off of
		print_form_header('phpkd_euni_phd', 'addd');
		print_description_row(construct_table_help_button('ddid') . '<b>' . $vbphrase['phpkd_euni_phd_create_department_based_off_of_department'] . '</b> <select name="ddid" tabindex="1" class="bginput">' . construct_department_chooser() . '</select> <input type="submit" class="button" value="' . $vbphrase['phpkd_euni_phd_go'] . '" tabindex="1" />', 0, 2, 'tfoot', 'center');
		print_table_footer();
		// Set Defaults;
		$department = array(
			'title' => '',
			'description' => '',
			'link' => '',
			'logo' => '',
			'displayorder' => 1,
			'parentid' => $vbulletin->GPC['parentid'],
			'showprivate' => 0,
			'styleid' => '',
			'styleoverride' => 0,
			'password' => '',
			'canhavepassword' => 1,
			'canhavecontent' => 1,
			'active' => 1,
			'countcontent' => 1,
			'showondepartmentjump' => 1
		);

		if (!empty($vbulletin->GPC['ddid']))
		{
			$newdepartment = fetch_departmentinfo($vbulletin->GPC['ddid']);
			foreach (array_keys($department) AS $title)
			{
				$department["$title"] = $newdepartment["$title"];
			}
		}

		($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_add_default')) ? eval($hook) : false;

		print_form_header('phpkd_euni_phd', 'updated');
		print_table_header($vbphrase['phpkd_euni_phd_add_new_department']);
	}
	else
	{
		if (!($department = fetch_departmentinfo($vbulletin->GPC['did'], false)))
		{
			print_stop_message('phpkd_euni_phd_invalid_department_specified');
		}
		print_form_header('phpkd_euni_phd', 'updated');
		print_table_header(construct_phrase($vbphrase['phpkd_euni_phd_x_y_id_z'], $vbphrase['phpkd_euni_phd_department'], $department['title'], $department['did']));
		construct_hidden_code('did', $vbulletin->GPC['did']);
	}

	$department['title'] = str_replace('&amp;', '&', $department['title']);
	$department['description'] = str_replace('&amp;', '&', $department['description']);

	print_input_row($vbphrase['phpkd_euni_phd_department_title'], 'department[title]', $department['title']);
	print_textarea_row($vbphrase['phpkd_euni_phd_department_description'], 'department[description]', $department['description']);
	print_input_row($vbphrase['phpkd_euni_phd_department_link'], 'department[link]', $department['link']);
	print_input_row($vbphrase['phpkd_euni_phd_department_logo'], 'department[logo]', $department['logo']);
	print_input_row("$vbphrase[phpkd_euni_phd_display_order]<dfn>$vbphrase[phpkd_euni_phd_zero_equals_no_display]</dfn>", 'department[displayorder]', $department['displayorder']);

	if ($vbulletin->GPC['uid'] != -1)
	{
		print_college_chooser($vbphrase['phpkd_euni_phd_parent_college'], 'department[parentid]', $department['parentid'], $vbphrase['phpkd_euni_phd_no_one']);
	}
	else
	{
		construct_hidden_code('parentid', 0);
	}

	print_table_header($vbphrase['phpkd_euni_phd_style_options']);

	if ($department['styleid'] == 0)
	{
		$department['styleid'] = -1; // to get the "use default style" option selected
	}
	print_style_chooser_row('department[styleid]', $department['styleid'], $vbphrase['phpkd_euni_phd_use_default_style'], $vbphrase['phpkd_euni_phd_custom_department_style'], 1);
	print_yes_no_row($vbphrase['phpkd_euni_phd_department_override_style_choice'], 'department[options][styleoverride]', $department['styleoverride']);

	print_table_header($vbphrase['phpkd_euni_phd_access_options']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_department_is_active'], 'department[options][active]', $department['active']);
	print_select_row($vbphrase['phpkd_euni_phd_show_private_department'], 'department[showprivate]', array($vbphrase['phpkd_euni_phd_use_default'], $vbphrase['phpkd_euni_phd_no'], $vbphrase['phpkd_euni_phd_yes_hide_counters'], $vbphrase['phpkd_euni_phd_yes_display_counters']), $department['showprivate']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_department_can_have_content'], 'department[options][canhavecontent]', $department['canhavecontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_count_content_in_department'], 'department[options][countcontent]', $department['countcontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_show_department_on_department_jump'], 'department[options][showondepartmentjump]', $department['showondepartmentjump']);
	print_input_row($vbphrase['phpkd_euni_phd_department_password'], 'department[password]', $department['password']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_department_can_have_password'], 'department[options][canhavepassword]', $department['canhavepassword']);

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_editd_form')) ? eval($hook) : false;

	print_submit_row($vbphrase['phpkd_euni_phd_save']);
}

// ###################### Start update department #######################
if ($_POST['do'] == 'updated')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'did'               => TYPE_UINT,
		'department'        => TYPE_ARRAY
	));

	$departmentdata =& datamanager_init('PHPKD_EUNI_PhD_Department', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');

	if ($vbulletin->GPC['did'])
	{
		$departmentdata->set_existing($vbulletin->phpkdeuniphddepartment[$vbulletin->GPC['did']]);
	}

	foreach ($vbulletin->GPC['department'] AS $varname => $value)
	{
		if ($varname == 'options')
		{
			foreach ($value AS $key => $val)
			{
				$departmentdata->set_bitfield('options', $key, $val);
			}
		}
		else
		{
			$departmentdata->set($varname, $value);
		}
	}

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_updated_save')) ? eval($hook) : false;

	$did = $departmentdata->save();
	if (!$vbulletin->GPC['did'])
	{
		$vbulletin->GPC['did'] = $did;
	}

	define('CP_REDIRECT', "phpkd_euni_phd.php?do=manage&amp;d=" . $vbulletin->GPC['did'] . "#department" . $vbulletin->GPC['did']);
	print_stop_message('phpkd_euni_phd_saved_department_x_successfully', $vbulletin->GPC['department']['title']);
}


// ###################### Start Remove University #######################

if ($_REQUEST['do'] == 'removeu')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'uid' => TYPE_UINT
	));

	print_delete_confirmation('phpkd_euni_phd_university', $vbulletin->GPC['uid'], 'phpkd_euni_phd', 'killu', 'university', 0, $vbphrase['phpkd_euni_phd_are_you_sure_you_want_to_delete_this_university'], 'title_clean');
}

// ###################### Start Kill University #######################

if ($_POST['do'] == 'killu')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'uid' => TYPE_UINT
	));

	$universitydata =& datamanager_init('PHPKD_EUNI_PhD_University', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');
	$universitydata->set_condition("FIND_IN_SET(" . $vbulletin->GPC['uid'] . ", parentlist)");
	$universitydata->delete();

	define('CP_REDIRECT', 'phpkd_euni_phd.php');
	print_stop_message('phpkd_euni_phd_deleted_university_successfully');
}

// ###################### Start Remove College #######################

if ($_REQUEST['do'] == 'removec')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cid' => TYPE_UINT
	));

	print_delete_confirmation('phpkd_euni_phd_college', $vbulletin->GPC['cid'], 'phpkd_euni_phd', 'killc', 'college', 0, $vbphrase['phpkd_euni_phd_are_you_sure_you_want_to_delete_this_college'], 'title_clean');
}

// ###################### Start Kill College #######################

if ($_POST['do'] == 'killc')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'cid' => TYPE_UINT
	));

	$universitydata =& datamanager_init('PHPKD_EUNI_PhD_College', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');
	$universitydata->set_condition("FIND_IN_SET(" . $vbulletin->GPC['cid'] . ", parentlist)");
	$universitydata->delete();

	define('CP_REDIRECT', 'phpkd_euni_phd.php');
	print_stop_message('phpkd_euni_phd_deleted_college_successfully');
}

// ###################### Start Remove Department #######################

if ($_REQUEST['do'] == 'removed')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'did' => TYPE_UINT
	));

	print_delete_confirmation('phpkd_euni_phd_department', $vbulletin->GPC['did'], 'phpkd_euni_phd', 'killd', 'department', 0, $vbphrase['phpkd_euni_phd_are_you_sure_you_want_to_delete_this_department'], 'title_clean');
}

// ###################### Start Kill Department #######################

if ($_POST['do'] == 'killd')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'did' => TYPE_UINT
	));

	$universitydata =& datamanager_init('PHPKD_EUNI_PhD_Department', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');
	$universitydata->set_condition("FIND_IN_SET(" . $vbulletin->GPC['did'] . ", parentlist)");
	$universitydata->delete();

	define('CP_REDIRECT', 'phpkd_euni_phd.php');
	print_stop_message('phpkd_euni_phd_deleted_department_successfully');
}

// ###################### Start do order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));

	if (is_array($vbulletin->GPC['order']))
	{
		$universities = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "phpkd_euni_phd_university");
		while ($university = $db->fetch_array($universities))
		{
			if (!isset($vbulletin->GPC['order']["$university[uid]"]))
			{
				continue;
			}

			$udisplayorder = intval($vbulletin->GPC['order']["$university[uid]"]);
			if ($university['displayorder'] != $udisplayorder)
			{
				$universitydm =& datamanager_init('PHPKD_EUNI_PhD_University', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');
				$universitydm->set_existing($university);
				$universitydm->setr('displayorder', $udisplayorder);
				$universitydm->save();
				unset($universitydm);
			}
		}


		$colleges = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "phpkd_euni_phd_college");
		while ($college = $db->fetch_array($colleges))
		{
			if (!isset($vbulletin->GPC['order']["$college[cid]"]))
			{
				continue;
			}

			$cdisplayorder = intval($vbulletin->GPC['order']["$college[cid]"]);
			if ($college['displayorder'] != $cdisplayorder)
			{
				$collegedm =& datamanager_init('PHPKD_EUNI_PhD_College', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');
				$collegedm->set_existing($college);
				$collegedm->setr('displayorder', $cdisplayorder);
				$collegedm->save();
				unset($collegedm);
			}
		}

		$departments = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "phpkd_euni_phd_department");
		while ($department = $db->fetch_array($departments))
		{
			if (!isset($vbulletin->GPC['order']["$department[did]"]))
			{
				continue;
			}

			$ddisplayorder = intval($vbulletin->GPC['order']["$department[did]"]);
			if ($department['displayorder'] != $ddisplayorder)
			{
				$departmentdm =& datamanager_init('PHPKD_EUNI_PhD_Department', $vbulletin, ERRTYPE_CP, 'PHPKD_EUNI_PhD');
				$departmentdm->set_existing($department);
				$departmentdm->setr('displayorder', $ddisplayorder);
				$departmentdm->save();
				unset($departmentdm);
			}
		}
	}

	build_university_permissions();
	build_college_permissions();
	build_department_permissions();

	define('CP_REDIRECT', 'phpkd_euni_phd.php?do=manage');
	print_stop_message('phpkd_euni_phd_saved_display_order_successfully');
}


// ###################### Start modify #######################
if ($_REQUEST['do'] == 'manage')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'uid'      => TYPE_UINT,
		'cid'      => TYPE_UINT,
		'did'      => TYPE_UINT,
		'pid'      => TYPE_UINT,
		'iid'      => TYPE_UINT,
		'sid'      => TYPE_UINT
	));

	// a little javascript for the options menus
	?>
	<script type="text/javascript">
	<!--
	function js_university_jump(universityinfo)
	{
		if (universityinfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['phpkd_euni_phd_please_select_university']); ?>');
			return;
		}
		else if (typeof(document.cpform.uid) != 'undefined')
		{
			action = document.cpform.controls.options[document.cpform.controls.selectedIndex].value;
		}
		else
		{
			action = eval("document.cpform.u" + universityinfo + ".options[document.cpform.u" + universityinfo + ".selectedIndex].value");
		}
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "phpkd_euni_phd.php?do=editu&u="; break;
				case 'remove': page = "phpkd_euni_phd.php?do=removeu&u="; break;
				case 'add': page = "phpkd_euni_phd.php?do=addc&parentid="; break;
				case 'addprof': page = "phpkd_euni_phd.php?do=addprof&u="; break;
				case 'listprof': page = "phpkd_euni_phd.php?do=showprofs&u=";break;
				case 'view': page = "../phpkd_euni_phd.php?u="; break;
				case 'perms': page = "phpkd_euni_phd_perms.php?do=modify&devnull="; break;
				case 'empty': page = "phpkd_euni_phd.php?do=emptyu&u="; break;
			}
			document.cpform.reset();
			jumptopage = page + universityinfo + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			if (action == 'perms')
			{
				window.location = jumptopage + '#university' + universityinfo;
			}
			else
			{
				window.location = jumptopage;
			}
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['phpkd_euni_phd_invalid_action_specified']); ?>');
		}
	}

	function js_college_jump(collegeinfo)
	{
		if (collegeinfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['phpkd_euni_phd_please_select_college']); ?>');
			return;
		}
		else if (typeof(document.cpform.cid) != 'undefined')
		{
			action = document.cpform.controls.options[document.cpform.controls.selectedIndex].value;
		}
		else
		{
			action = eval("document.cpform.c" + collegeinfo + ".options[document.cpform.c" + collegeinfo + ".selectedIndex].value");
		}
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "phpkd_euni_phd.php?do=editc&c="; break;
				case 'remove': page = "phpkd_euni_phd.php?do=removec&c="; break;
				case 'add': page = "phpkd_euni_phd.php?do=addd&parentid="; break;
				case 'addprof': page = "phpkd_euni_phd.php?do=addprof&c="; break;
				case 'listprof': page = "phpkd_euni_phd.php?do=showprofs&c=";break;
				case 'view': page = "../phpkd_euni_phd.php?c="; break;
				case 'perms': page = "phpkd_euni_phd_perms.php?do=modify&devnull="; break;
				case 'empty': page = "phpkd_euni_phd.php?do=emptyc&c="; break;
			}
			document.cpform.reset();
			jumptopage = page + collegeinfo + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			if (action == 'perms')
			{
				window.location = jumptopage + '#college' + collegeinfo;
			}
			else
			{
				window.location = jumptopage;
			}
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['phpkd_euni_phd_invalid_action_specified']); ?>');
		}
	}

	function js_department_jump(departmentinfo)
	{
		if (departmentinfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['phpkd_euni_phd_please_select_department']); ?>');
			return;
		}
		else if (typeof(document.cpform.did) != 'undefined')
		{
			action = document.cpform.controls.options[document.cpform.controls.selectedIndex].value;
		}
		else
		{
			action = eval("document.cpform.d" + departmentinfo + ".options[document.cpform.d" + departmentinfo + ".selectedIndex].value");
		}
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "phpkd_euni_phd.php?do=editd&d="; break;
				case 'remove': page = "phpkd_euni_phd.php?do=removed&d="; break;
				case 'addprof': page = "phpkd_euni_phd.php?do=addprof&d="; break;
				case 'listprof': page = "phpkd_euni_phd.php?do=showprofs&d=";break;
				case 'view': page = "../phpkd_euni_phd.php?d="; break;
				case 'perms': page = "phpkd_euni_phd_perms.php?do=modify&devnull="; break;
				case 'empty': page = "phpkd_euni_phd.php?do=emptyd&d="; break;
			}
			document.cpform.reset();
			jumptopage = page + departmentinfo + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			if (action == 'perms')
			{
				window.location = jumptopage + '#department' + departmentinfo;
			}
			else
			{
				window.location = jumptopage;
			}
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['phpkd_euni_phd_invalid_action_specified']); ?>');
		}
	}

	function js_returnid()
	{
		return document.cpform.uid.value;
	}
	//-->
	</script>
	<?php

	$universityoptions = array(
		'edit'     => $vbphrase['phpkd_euni_phd_edit_university'],
		'view'     => $vbphrase['phpkd_euni_phd_view_university'],
		'remove'   => $vbphrase['phpkd_euni_phd_remove_university'],
		'add'      => $vbphrase['phpkd_euni_phd_add_child_college'],
		'addprof'  => $vbphrase['phpkd_euni_phd_add_professor'],
		'listprof' => $vbphrase['phpkd_euni_phd_list_professors'],
		'perms'    => $vbphrase['phpkd_euni_phd_view_permissions'],
		'empty'    => $vbphrase['phpkd_euni_phd_empty_university'],
	);

	$collegeoptions = array(
		'edit'     => $vbphrase['phpkd_euni_phd_edit_college'],
		'view'     => $vbphrase['phpkd_euni_phd_view_college'],
		'remove'   => $vbphrase['phpkd_euni_phd_remove_college'],
		'add'      => $vbphrase['phpkd_euni_phd_add_child_department'],
		'addprof'  => $vbphrase['phpkd_euni_phd_add_professor'],
		'listprof' => $vbphrase['phpkd_euni_phd_list_professors'],
		'perms'    => $vbphrase['phpkd_euni_phd_view_permissions'],
		'empty'    => $vbphrase['phpkd_euni_phd_empty_college'],
	);

	$departmentoptions = array(
		'edit'     => $vbphrase['phpkd_euni_phd_edit_department'],
		'view'     => $vbphrase['phpkd_euni_phd_view_department'],
		'remove'   => $vbphrase['phpkd_euni_phd_remove_department'],
		'addprof'  => $vbphrase['phpkd_euni_phd_add_professor'],
		'listprof' => $vbphrase['phpkd_euni_phd_list_professors'],
		'perms'    => $vbphrase['phpkd_euni_phd_view_permissions'],
		'empty'    => $vbphrase['phpkd_euni_phd_empty_department'],
	);

		print_form_header('phpkd_euni_phd', 'doorder');
		print_table_header($vbphrase['phpkd_euni_phd_manager'], 4);
		print_description_row($vbphrase['phpkd_euni_phd_if_you_change_display_order'], 0, 4);

		if (is_array($vbulletin->forumcache))
		{
			foreach($vbulletin->forumcache AS $key => $forum)
			{
				$mainoptions =& $forumoptions1;

				$cell = array();
					$cell[] = "<a name=\"forum$forum[forumid]\">&nbsp;</a><b>" . construct_depth_mark($forum['depth'],'- - ') . "<a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;f=$forum[forumid]\">$forum[title]</a>" . iif(!empty($forum['password']),'*') . " " . iif($forum['link'], "(<a href=\"" . htmlspecialchars_uni($forum['link']) . "\">" . $vbphrase['link'] . "</a>)") . "</b>";
					$cell[] = "\n\t<select name=\"f$forum[forumid]\" onchange=\"js_university_jump($forum[forumid]);\" class=\"bginput\">\n" . construct_select_options($mainoptions) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_university_jump($forum[forumid]);\" />\n\t";
					$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$forum[forumid]]\" value=\"$forum[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";
					$cell[] = "\n\t<select name=\"m$forum[forumid]\" onchange=\"js_moderator_jump($forum[forumid]);\" class=\"bginput\">\n" . construct_select_options($mods) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_moderator_jump($forum[forumid]);\" />\n\t";

				if (!$forum['parentid'])
				{
					print_cells_row(array($vbphrase['forum'], $vbphrase['controls'], $vbphrase['display_order'], $vbphrase['moderators']), 1, 'tcat');
				}
				print_cells_row($cell);
			}
		}

		print_table_footer(4, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save_display_order'] . "\" accesskey=\"s\" />" . construct_button_code($vbphrase['add_new_forum'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=add"));

		echo '<p class="smallfont" align="center">' . $vbphrase['forums_marked_asterisk_are_password_protected'] . '</p>';
}

print_cp_footer();

/*=================================================================================*\
|| ############################################################################### ||
|| # Version.: 1.0.0
|| # Revision: $Revision$
|| # Released: $Date$
|| ############################################################################### ||
\*=================================================================================*/
?>