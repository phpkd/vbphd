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
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_template.php');
require_once(DIR . '/includes/adminfunctions_euni_phd.php');

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

// ###################### Start add #######################
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
		print_description_row(construct_table_help_button('duid') . '<b>' . $vbphrase['phpkd_euni_phd_create_university_based_off_of_university'] . '</b> <select name="duid" tabindex="1" class="bginput">' . construct_university_chooser() . '</select> <input type="submit" class="button" value="' . $vbphrase['go'] . '" tabindex="1" />', 0, 2, 'tfoot', 'center');
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
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['phpkd_euni_phd_university'], $university['title'], $university['uid']));
		construct_hidden_code('uid', $vbulletin->GPC['uid']);
	}

	$university['title'] = str_replace('&amp;', '&', $university['title']);
	$university['description'] = str_replace('&amp;', '&', $university['description']);

	print_input_row($vbphrase['phpkd_euni_phd_title'], 'university[title]', $university['title']);
	print_textarea_row($vbphrase['phpkd_euni_phd_description'], 'university[description]', $university['description']);
	print_input_row($vbphrase['phpkd_euni_phd_university_link'], 'university[link]', $university['link']);
	print_input_row($vbphrase['phpkd_euni_phd_university_logo'], 'university[logo]', $university['logo']);
	print_input_row("$vbphrase[phpkd_euni_phd_display_order]<dfn>$vbphrase[phpkd_euni_phd_zero_equals_no_display]</dfn>", 'university[displayorder]', $university['displayorder']);
	print_select_row($vbphrase['phpkd_euni_phd_show_private_university'], 'university[showprivate]', array($vbphrase['phpkd_euni_phd_use_default'], $vbphrase['no'], $vbphrase['phpkd_euni_phd_yes_hide_counters'], $vbphrase['phpkd_euni_phd_yes_display_counters']), $university['showprivate']);


	print_table_header($vbphrase['phpkd_euni_phd_style_options']);

	if ($university['styleid'] == 0)
	{
		$university['styleid'] = -1; // to get the "use default style" option selected
	}
	print_style_chooser_row('university[styleid]', $university['styleid'], $vbphrase['phpkd_euni_phd_use_default_style'], $vbphrase['phpkd_euni_phd_custom_university_style'], 1);
	print_yes_no_row($vbphrase['phpkd_euni_phd_override_style_choice'], 'university[options][styleoverride]', $university['styleoverride']);

	print_table_header($vbphrase['phpkd_euni_phd_access_options']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_university_is_active'], 'university[options][active]', $university['active']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_can_have_content'], 'university[options][canhavecontent]', $university['canhavecontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_count_content_in_university'], 'university[options][countcontent]', $university['countcontent']);
	print_yes_no_row($vbphrase['phpkd_euni_phd_show_university_on_university_jump'], 'university[options][showonuniversityjump]', $university['showonuniversityjump']);
	print_input_row($vbphrase['phpkd_euni_phd_university_password'], 'university[password]', $university['password']);
	if ($_REQUEST['do'] == 'editu')
	{
		print_yes_no_row($vbphrase['phpkd_euni_phd_apply_password_to_children'], 'applypwdtochild', 0);
	}
	print_yes_no_row($vbphrase['phpkd_euni_phd_can_have_password'], 'university[options][canhavepassword]', $university['canhavepassword']);

	($hook = vBulletinHook::fetch_hook('phpkd_euni_phd_admin_editu_form')) ? eval($hook) : false;

	print_submit_row($vbphrase['phpkd_euni_phd_save']);
}

// ###################### Start update #######################
if ($_POST['do'] == 'updateu')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'uid'               => TYPE_UINT,
		'applypwdtochild'   => TYPE_BOOL,
		'university'        => TYPE_ARRAY,
	));

	$universitydata =& datamanager_init('PHPKD_EUNI_PhD_University', $vbulletin, ERRTYPE_CP);

	if ($vbulletin->GPC['uid'])
	{
		$universitydata->set_existing($vbulletin->universitycache[$vbulletin->GPC['uid']]);
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
// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array('forumid' => TYPE_UINT));

	print_delete_confirmation('forum', $vbulletin->GPC['forumid'], 'forum', 'kill', 'forum', 0, $vbphrase['are_you_sure_you_want_to_delete_this_forum'], 'title_clean');
}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'forumid' => TYPE_UINT
	));

	$forumdata =& datamanager_init('Forum', $vbulletin, ERRTYPE_CP);
	$forumdata->set_condition("FIND_IN_SET(" . $vbulletin->GPC['forumid'] . ", parentlist)");
	$forumdata->delete();

	define('CP_REDIRECT', 'forum.php');
	print_stop_message('deleted_forum_successfully');
}

// ###################### Start do order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));

	if (is_array($vbulletin->GPC['order']))
	{
		$forums = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "forum");
		while ($university = $db->fetch_array($forums))
		{
			if (!isset($vbulletin->GPC['order']["$university[forumid]"]))
			{
				continue;
			}

			$displayorder = intval($vbulletin->GPC['order']["$university[forumid]"]);
			if ($university['displayorder'] != $displayorder)
			{
				$forumdm =& datamanager_init('Forum', $vbulletin, ERRTYPE_SILENT);
				$forumdm->set_existing($university);
				$forumdm->setr('displayorder', $displayorder);
				$forumdm->save();
				unset($forumdm);
			}
		}
	}

	build_forum_permissions();

	define('CP_REDIRECT', 'forum.php?do=manage');
	print_stop_message('saved_display_order_successfully');
}

// ###################### Start forum_is_related_to_forum #######################
function forum_is_related_to_forum($partial_list, $forumid, $full_list)
{
	// This function is only used below, only for expand/collapse of forums.
	// If the first forum's parent list is contained within the second,
	// then it is considered related (think of it as an aunt or uncle forum).

	$partial = explode(',', $partial_list);
	if ($partial[0] == $forumid)
	{
		array_shift($partial);
	}
	$full = explode(',', $full_list);

	foreach ($partial AS $fid)
	{
		if (!in_array($fid, $full))
		{
			return false;
		}
	}

	return true;
}

// ###################### Start manage #######################
if ($_REQUEST['do'] == 'manage')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'forumid' 	=> TYPE_UINT,
		'expandid'	=> TYPE_INT,
	));

	if (!$vbulletin->GPC['expandid'])
	{
		$vbulletin->GPC['expandid'] = -1;
	}
	else if ($vbulletin->GPC['expandid'] == -2)
	{
		// expand all -- easiest to just turn off collapsing
		$vbulletin->options['cp_collapse_forums'] = false;
	}

	// a little javascript for the options menus
	?>
	<script type="text/javascript">
	<!--
	function js_forum_jump(foruminfo)
	{
		var cp_collapse_forums = <?php echo intval($vbulletin->options['cp_collapse_forums']); ?>;
		if (foruminfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['please_select_forum']); ?>');
			return;
		}
		else if (typeof(document.cpform.forumid) != 'undefined')
		{
			action = document.cpform.controls.options[document.cpform.controls.selectedIndex].value;
		}
		else
		{
			action = eval("document.cpform.f" + foruminfo + ".options[document.cpform.f" + foruminfo + ".selectedIndex].value");
		}
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "forum.php?do=edit&f="; break;
				case 'remove': page = "forum.php?do=remove&f="; break;
				case 'add': page = "forum.php?do=add&parentid="; break;
				case 'addmod': page = "moderator.php?do=add&f="; break;
				case 'listmod': page = "moderator.php?do=showmods&f=";break;
				case 'annc': page = "announcement.php?do=add&f="; break;
				case 'view': page = "../forumdisplay.php?f="; break;
				case 'perms':
					if (cp_collapse_forums > 0)
					{
						page = "forumpermission.php?do=manage&f=";
					}
					else
					{
						page = "forumpermission.php?do=manage&devnull=";
					}
					break;
				case 'podcast': page = "forum.php?do=podcast&f="; break;
				case 'empty': page = "forum.php?do=empty&f="; break;
			}
			document.cpform.reset();
			jumptopage = page + foruminfo + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			if (action == 'perms')
			{
				window.location = jumptopage + '#forum' + foruminfo;
			}
			else
			{
				window.location = jumptopage;
			}
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['invalid_action_specified']); ?>');
		}
	}

	function js_moderator_jump(foruminfo)
	{
		if (foruminfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['please_select_forum']); ?>');
			return;
		}
		else if (typeof(document.cpform.forumid) != 'undefined')
		{
			modinfo = document.cpform.moderator[document.cpform.moderator.selectedIndex].value;
		}
		else
		{
			modinfo = eval("document.cpform.m" + foruminfo + ".options[document.cpform.m" + foruminfo + ".selectedIndex].value");
			document.cpform.reset();
		}

		switch (modinfo)
		{
			case 'add': window.location = "moderator.php?s=<?php echo $vbulletin->session->vars['sessionhash']; ?>&do=add&f=" + foruminfo; break;
			case 'show': window.location = "moderator.php?s=<?php echo $vbulletin->session->vars['sessionhash']; ?>&do=showmods&f=" + foruminfo; break;
			case '': return false; break;
			default: window.location = "moderator.php?s=<?php echo $vbulletin->session->vars['sessionhash']; ?>&do=edit&moderatorid=" + modinfo; break;
		}
	}

	function js_returnid()
	{
		return document.cpform.forumid.value;
	}
	//-->
	</script>
	<?php

	$forumoptions1 = array(
		'edit'    => $vbphrase['edit_forum'],
		'view'    => $vbphrase['view_forum'],
		'remove'  => $vbphrase['delete_forum'],
		'add'     => $vbphrase['add_child_forum'],
		'addmod'  => $vbphrase['add_moderator'],
		'listmod' => $vbphrase['list_moderators'],
		'annc'    => $vbphrase['add_announcement'],
		'perms'   => $vbphrase['view_permissions'],
		'podcast' => $vbphrase['podcast_settings'],
	);

	$forumoptions2 = array(
		'edit'    => $vbphrase['edit_forum'],
		'view'    => $vbphrase['view_forum'],
		'remove'  => $vbphrase['delete_forum'],
		'add'     => $vbphrase['add_child_forum'],
		'addmod'  => $vbphrase['add_moderator'],
		'annc'    => $vbphrase['add_announcement'],
		'perms'   => $vbphrase['view_permissions'],
		'podcast' => $vbphrase['podcast_settings'],
	);

	require_once(DIR . '/includes/functions_databuild.php');

	if ($vbulletin->options['cp_collapse_forums'] != 2)
	{
		print_form_header('phpkd_euni_phd', 'doorder');
		print_table_header($vbphrase['phpkd_euni_phd_manager'], 4);
		print_description_row($vbphrase['if_you_change_display_order'], 0, 4);

		require_once(DIR . '/includes/functions_forumlist.php');
		cache_moderators();

		$forums = array();
		$expanddata = array('forumid' => -1, 'parentlist' => '');
		if (is_array($vbulletin->universitycache))
		{
			foreach($vbulletin->universitycache AS $forumid => $university)
			{
				$forums["$university[forumid]"] = construct_depth_mark($university['depth'], '--') . ' ' . $university['title'];
				if ($university['forumid'] == $vbulletin->GPC['expandid'])
				{
					$expanddata = $university;
				}
			}
		}
		$expanddata['parentids'] = explode(',', $expanddata['parentlist']);

		if ($vbulletin->options['cp_collapse_forums'])
		{
			$expandtext = '[-] ';
		}
		else
		{
			$expandtext = '';
		}

		if (is_array($vbulletin->universitycache))
		{
			foreach($vbulletin->universitycache AS $key => $university)
			{
				$modcount = sizeof($imodcache["$university[forumid]"]);
				if ($modcount)
				{
					$mainoptions =& $forumoptions1;
					$mainoptions['listmod'] = $vbphrase['list_moderators'] . " ($modcount)";
				}
				else
				{
					$mainoptions =& $forumoptions2;
				}

				$cell = array();
				if (!$vbulletin->options['cp_collapse_forums'] OR $university['forumid'] == $expanddata['forumid'] OR in_array($university['forumid'], $expanddata['parentids']))
				{
					$cell[] = "<a name=\"forum$university[forumid]\">&nbsp;</a> $expandtext<b>" . construct_depth_mark($university['depth'],'- - ') . "<a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;f=$university[forumid]\">$university[title]</a>" . iif(!empty($university['password']),'*') . " " . iif($university['link'], "(<a href=\"" . htmlspecialchars_uni($university['link']) . "\">" . $vbphrase['link'] . "</a>)") . "</b>";
					$cell[] = "\n\t<select name=\"f$university[forumid]\" onchange=\"js_forum_jump($university[forumid]);\" class=\"bginput\">\n" . construct_select_options($mainoptions) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_forum_jump($university[forumid]);\" />\n\t";
					$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$university[forumid]]\" value=\"$university[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";

					$mods = array('no_value' => $vbphrase['moderators'].' (' . sizeof($imodcache["$university[forumid]"]) . ')');
					if (is_array($imodcache["$university[forumid]"]))
					{
						foreach ($imodcache["$university[forumid]"] AS $moderator)
						{
							$mods['']["$moderator[moderatorid]"] = $moderator['username'];
						}
					}
					$mods['add'] = $vbphrase['add_moderator'];
					$cell[] = "\n\t<select name=\"m$university[forumid]\" onchange=\"js_moderator_jump($university[forumid]);\" class=\"bginput\">\n" . construct_select_options($mods) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_moderator_jump($university[forumid]);\" />\n\t";
				}
				else if (
					$vbulletin->options['cp_collapse_forums'] AND
						(
						$university['parentid'] == $expanddata['forumid'] OR
						$university['parentid'] == -1 OR
						forum_is_related_to_forum($university['parentlist'], $university['forumid'], $expanddata['parentlist'])
						)
					)
				{
					$cell[] = "<a name=\"forum$university[forumid]\">&nbsp;</a> <a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=manage&amp;expandid=$university[forumid]\">[+]</a>  <b>" . construct_depth_mark($university['depth'],'- - ') . "<a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;f=$university[forumid]\">$university[title]</a>" . iif(!empty($university['password']),'*') . " " . iif($university['link'], "(<a href=\"$university[link]\">" . $vbphrase['link'] . "</a>)") . "</b>";
					$cell[] = construct_link_code($vbphrase['expand'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=manage&amp;expandid=$university[forumid]");
					$cell[] = "&nbsp;";
					$cell[] = "&nbsp;";
				}
				else
				{
					continue;
				}

				if ($university['parentid'] == -1)
				{
					print_cells_row(array($vbphrase['forum'], $vbphrase['controls'], $vbphrase['phpkd_euni_phd_display_order'], $vbphrase['moderators']), 1, 'tcat');
				}
				print_cells_row($cell);
			}
		}

		print_table_footer(4, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save_display_order'] . "\" accesskey=\"s\" />" . construct_button_code($vbphrase['phpkd_euni_phd_add_new_university'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=add"));

		if ($vbulletin->options['cp_collapse_forums'])
		{
			echo '<p class="smallfont" align="center">' . construct_link_code($vbphrase['expand_all'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=manage&amp;expandid=-2") . '</p>';
		}

		echo '<p class="smallfont" align="center">' . $vbphrase['forums_marked_asterisk_are_password_protected'] . '</p>';
	}
	else
	{
		print_form_header('phpkd_euni_phd', 'doorder');
		print_table_header($vbphrase['phpkd_euni_phd_manager'], 2);

		print_cells_row(array($vbphrase['forum'], $vbphrase['controls']), 1, 'tcat');
		$cell = array();

		$select = '<select name="forumid" id="sel_foruid" tabindex="1" class="bginput">';
		$select .= construct_university_chooser($vbulletin->GPC['forumid'], true);
		$select .= "</select>\n";

		$cell[] = $select;
		$cell[] = "\n\t<select name=\"controls\" class=\"bginput\">\n" . construct_select_options($forumoptions1) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_forum_jump(js_returnid());\" />\n\t";
		print_cells_row($cell);
		print_table_footer(2, construct_button_code($vbphrase['phpkd_euni_phd_add_new_university'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=add"));
	}
}

// ###################### Start add podcast #######################
if ($_REQUEST['do'] == 'podcast')
{
	if (!($university = fetch_universityinfo($vbulletin->GPC['forumid'], false)))
	{
		print_stop_message('phpkd_euni_phd_invalid_university_specified');
	}
	require_once(DIR . '/includes/adminfunctions_misc.php');

	$university['title'] = str_replace('&amp;', '&', $university['title']);

	$podcast = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "podcast
		WHERE forumid = $university[forumid]"
	);

	print_form_header('phpkd_euni_phd', 'updatepodcast');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['podcast_settings'], $university['title'], $university['forumid']));
	construct_hidden_code('forumid', $university['forumid']);

	print_yes_no_row($vbphrase['enabled'], 'enabled', $podcast['enabled']);
	print_podcast_chooser($vbphrase['category'], 'categoryid', $podcast['categoryid']);
	print_input_row($vbphrase['media_author'] . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255) . '</dfn>', 'author', $podcast['author']);
	print_input_row($vbphrase['owner_name']  . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255), 'ownername', $podcast['ownername']);
	print_input_row($vbphrase['owner_email']  . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255), 'owneremail', $podcast['owneremail']);
	print_input_row($vbphrase['image_url'], 'image', $podcast['image']);
	print_input_row($vbphrase['subtitle']  . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255) . '</dfn>', 'subtitle', $podcast['subtitle']);
	print_textarea_row($vbphrase['keywords'] . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255) . '</dfn>', 'keywords', $podcast['keywords'], 2, 40);
	print_textarea_row($vbphrase['summary'] . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 4000) . '</dfn>', 'summary', $podcast['summary'], 4, 40);
	print_yes_no_row($vbphrase['explicit'], 'explicit', $podcast['explicit']);

	print_submit_row($vbphrase['save']);
}

// ###################### Start add podcast #######################
if ($_POST['do'] == 'updatepodcast')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'categoryid' => TYPE_UINT,
		'explicit'   => TYPE_BOOL,
		'enabled'    => TYPE_BOOL,
		'author'     => TYPE_STR,
		'owneremail' => TYPE_STR,
		'ownername'  => TYPE_STR,
		'image'      => TYPE_STR,
		'subtitle'   => TYPE_STR,
		'keywords'   => TYPE_STR,
		'summary'    => TYPE_STR,
	));

	if (!($university = fetch_universityinfo($vbulletin->GPC['forumid'], false)))
	{
		print_stop_message('phpkd_euni_phd_invalid_university_specified');
	}
	require_once(DIR . '/includes/adminfunctions_misc.php');

	$category = fetch_podcast_categoryarray($vbulletin->GPC['categoryid']);

	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "podcast (forumid, enabled, categoryid, category, author, image, explicit, keywords, owneremail, ownername, subtitle, summary)
		VALUES (
			$university[forumid],
			" . intval($vbulletin->GPC['enabled']) . ",
			" . $vbulletin->GPC['categoryid'] . ",
			'" . $db->escape_string(serialize($category)) . "',
			'" . $db->escape_string($vbulletin->GPC['author']) . "',
			'" . $db->escape_string($vbulletin->GPC['image']) . "',
			" . intval($vbulletin->GPC['explicit']) . ",
			'" . $db->escape_string($vbulletin->GPC['keywords']) . "',
			'" . $db->escape_string($vbulletin->GPC['owneremail']) . "',
			'" . $db->escape_string($vbulletin->GPC['ownername']) . "',
			'" . $db->escape_string($vbulletin->GPC['subtitle']) . "',
			'" . $db->escape_string($vbulletin->GPC['summary']) . "'
		)
	");

	build_forum_permissions();

	define('CP_REDIRECT', 'forum.php?do=manage');
	print_stop_message('updated_podcast_settings_successfully');
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