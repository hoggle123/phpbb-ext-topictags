<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright (c) 2014 Robet Heim
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace robertheim\topictags\migrations;

use robertheim\topictags\permissions;
use robertheim\topictags\prefixes;

class release_3_0_3 extends \phpbb\db\migration\migration
{
	protected $version = '3.0.3';

	public function effectively_installed()
	{
		return version_compare($this->config[prefixes::CONFIG.'_version'], $this->version, '>=');
	}

	public static function depends_on()
	{
		return array(
			'\robertheim\topictags\migrations\release_3_0_2',
		);
	}

	public function update_data()
	{
		$re = array();

		// Already present on this board. Harmless if the row exists.
		$re[] = array('permission.add', array(permissions::USE_TAGS));
		$re[] = array('permission.add', array(permissions::ADMIN_EDIT_TAGS));
		$re[] = array('permission.add', array(permissions::MOD_EDIT_TAGS));

		$re = $this->grant_role($re, 'ROLE_USER_STANDARD', permissions::USE_TAGS);
		$re = $this->grant_role($re, 'ROLE_USER_FULL', permissions::USE_TAGS);
		$re = $this->grant_role($re, 'ROLE_USER_NEW_MEMBER', permissions::USE_TAGS);
		$re = $this->grant_role($re, 'ROLE_ADMIN_FULL', permissions::ADMIN_EDIT_TAGS);
		$re = $this->grant_role($re, 'ROLE_MOD_FULL', permissions::MOD_EDIT_TAGS);
		$re = $this->grant_role($re, 'ROLE_MOD_STANDARD', permissions::MOD_EDIT_TAGS);

		$re[] = array('permission.permission_set', array('REGISTERED', permissions::USE_TAGS, 'group'));
		$re[] = array('permission.permission_set', array('NEWLY_REGISTERED', permissions::USE_TAGS, 'group'));

		$re[] = array('config.update', array(prefixes::CONFIG.'_version', $this->version));

		return $re;
	}

	private function grant_role(array $re, $rolename, $permission)
	{
		$re[] = array('if', array(
			array('permission.role_exists', array($rolename)),
			array('permission.permission_set', array($rolename, $permission)),
		));
		return $re;
	}
}
