<?php
/*==================================================================================*\
|| ################################################################################ ||
|| # Product Name: vB Username Change Manager               Version: 1.0.0 Beta.1 # ||
|| # Licence Number: {LicenceNumber}
|| # ---------------------------------------------------------------------------- # ||
|| # 																			  # ||
|| #          Copyright ©2005-2008 PHP KingDom, Ltd. All Rights Reserved.         # ||
|| #       This file may not be redistributed in whole or significant part.       # ||
|| # 																			  # ||
|| # ------------- vB Username Change Manager IS NOT FREE SOFTWARE -------------- # ||
|| #           http://www.phpkd.org | http://www.phpkd.org/license.html           # ||
|| ################################################################################ ||
\*==================================================================================*/


if (!defined('VB_AREA'))
{
	exit;
}

$hookobj =& vBulletinHook::init();
require_once(DIR . '/includes/phpkd/functions_euni_phd.php');

switch (strval($hookobj->last_called))
{
	case 'admin_permissions_form':
		{
			print_description_row($vbphrase['phpkd_euni_phd_adminperms'], false, 2, 'thead');
			foreach ($myobj->data['ugp']['phpkdeuniphdadminperms'] AS $title => $values)
			{
				// don't show settings that have a group for the usergroup page
				if (empty($values['group']))
				{
					$PHPKDEUNIPHDADMINPERMS["$title"] = $values['value'];
					$permsphrase["$title"] = $vbphrase["$values[phrase]"];
				}
			}

			foreach (convert_bits_to_array($user['phpkdeuniphdadminperms'], $PHPKDEUNIPHDADMINPERMS) AS $field => $value)
			{
				print_yes_no_row(($permsphrase["$field"] == '' ? $vbphrase['n_a'] : $permsphrase["$field"]), "phpkdeuniphdadminperms[$field]", $value);
			}
		}
		break;
	case 'admindata_start':
		{
			$this->validfields['phpkdeuniphdadminperms'] = array(TYPE_UINT,   REQ_NO);
			$this->bitfields['phpkdeuniphdadminperms'] = $this->registry->bf_ugp_phpkdeuniphdadminperms;
		}
		break;
	case 'admin_permissions_process':
		{
			$vbulletin->input->clean_array_gpc('p', array(
				'phpkdeuniphdadminperms' => TYPE_ARRAY_INT
			));

			foreach ($vbulletin->GPC['phpkdeuniphdadminperms'] AS $key => $value)
			{
				$admindm->set_bitfield('phpkdeuniphdadminperms', $key, $value);
			}
		}
		break;
	case 'can_administer':
		{
			// final bitfield check on each permission we are checking
			foreach($do AS $field)
			{
				if ($admin['phpkdeuniphdadminperms'] & $vbulletin->bf_ugp_phpkdeuniphdadminperms["$field"])
				{
					$return_value = true;
					return;
				}
			}
		}
		break;
	case 'admin_moderator_form':
		{
			if ($_REQUEST['do'] == 'editglobal')
			{
				print_description_row($vbphrase['phpkd_euni_phd_modperms'], false, 2, 'thead');
				foreach ($myobj->data['ugp']['phpkdeuniphdmodperms'] AS $title => $values)
				{
					// don't show settings that have a group for the usergroup page
					if (empty($values['group']))
					{
						$PHPKDEUNIPHDMODPERMS["$title"] = $values['value'];
						$permsphrase["$title"] = $vbphrase["$values[phrase]"];
					}
				}

				foreach (convert_bits_to_array($user['phpkdeuniphdmodperms'], $PHPKDEUNIPHDMODPERMS) AS $field => $value)
				{
					print_yes_no_row(($permsphrase["$field"] == '' ? $vbphrase['n_a'] : $permsphrase["$field"]), "phpkdeuniphdmodperms[$field]", $value);
				}
			}
		}
		break;
	case 'moderatordata_start':
		{
			$this->validfields['phpkdeuniphdmodperms'] = array(TYPE_ARRAY_BOOL, REQ_YES,  VF_METHOD);
			$this->bitfields['phpkdeuniphdmodperms'] = $this->registry->bf_ugp_phpkdeuniphdmodperms;
		}
		break;
	case 'admin_moderator_save':
		{
			$vbulletin->input->clean_array_gpc('p', array(
				'phpkdeuniphdmodperms' => TYPE_ARRAY_BOOL
			));

			foreach ($vbulletin->GPC['phpkdeuniphdmodperms'] AS $key => $val)
			{
				$moddata->set_bitfield('phpkdeuniphdmodperms', $key, $val);
			}
		}
		break;
	case 'can_moderate_forum':
		{
			foreach($do AS $field)
			{
				if ($vbulletin->userinfo['permissions']['phpkdeuniphdmodperms'] & $vbulletin->bf_ugp_phpkdeuniphdmodperms["$field"])
				{
					$return = true;
					return;
				}
			}
		}
		break;
	default:
		{
			$hookobj = new vBulletinHook_phpkd_euni_phd($hookobj->pluginlist, $hookobj->hookusage);
		}
		break;
}

/*=================================================================================*\
|| ############################################################################### ||
|| # Version.: 1.0.0
|| # Revision: $Revision$
|| # Released: $Date$
|| ############################################################################### ||
\*=================================================================================*/
?>