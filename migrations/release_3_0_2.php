<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright © 2015 Robert Heim
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace robertheim\topictags\migrations;

use robertheim\topictags\prefixes;

class release_3_0_2 extends \phpbb\db\migration\migration
{
	protected $version = '3.0.2';

	public function effectively_installed()
	{
		return version_compare($this->config[prefixes::CONFIG.'_version'], $this->version, '>=');
	}

	public static function depends_on()
	{
		return array(
			'\robertheim\topictags\migrations\release_3_0_1',
		);
	}


	public function update_data()
	{
		$re = array();

		$re[] = array('config.remove', array(prefixes::CONFIG.'_convert_space_to_minus', 1));
		$re[] = array('config.add', array(prefixes::CONFIG.'_convert_space_to_hyphen', 1));
		$re[] = array('config.update', array(prefixes::CONFIG.'_version', $this->version));

		return $re;
	}
}
