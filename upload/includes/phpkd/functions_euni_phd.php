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

class vBulletinHook_phpkd_euni_phd extends vBulletinHook
{
	var $last_called = '';

	function vBulletinHook_phpkd_euni_phd(&$pluginlist, &$hookusage)
	{
		$this->pluginlist =& $pluginlist;
		$this->hookusage =& $hookusage;
	}

	function &fetch_hook_object($hookname)
	{
		$this->last_called = $hookname;
		return parent::fetch_hook_object($hookname);
	}
}

/*=================================================================================*\
|| ############################################################################### ||
|| # Version.: 1.0.0
|| # Revision: $Revision: 8 $
|| # Released: $Date: 2008-05-16 10:29:42 +0300 (Fri, 16 May 2008) $
|| ############################################################################### ||
\*=================================================================================*/
?>