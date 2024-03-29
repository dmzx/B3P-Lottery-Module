<?php
/**
*
* @package Board3 Portal Lottery Module
* @copyright (c) 2019 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\b3plottery;

class ext extends \phpbb\extension\base
{
	public function is_enableable()
	{
		$ext_manager = $this->container->get('ext.manager');

		return phpbb_version_compare(PHPBB_VERSION, '3.2.0', '>=') && phpbb_version_compare(PHP_VERSION, '5.4.7', '>=') && $ext_manager->is_enabled('dmzx/ultimatepoints');
	}
}
