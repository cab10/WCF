<?php
namespace wcf\data\user\group\option;
use wcf\data\option\Option;

/**
 * Represents a usergroup option.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Data\User\Group\Option
 *
 * @property-read	string		$defaultValue		default value of the user option
 * @property-read	integer		$usersOnly	is `1` if the option only applies to user groups for registered users, otherwise `1`
 */
class UserGroupOption extends Option {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'user_group_option';
}
