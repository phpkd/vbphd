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
	case 'cache_templates':
		{
			
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