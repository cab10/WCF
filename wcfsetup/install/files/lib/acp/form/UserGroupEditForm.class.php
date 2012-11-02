<?php
namespace wcf\acp\form;
use wcf\data\user\group\UserGroup;
use wcf\data\user\group\UserGroupAction;
use wcf\data\user\group\UserGroupEditor;
use wcf\form\AbstractForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\language\I18nHandler;
use wcf\system\WCF;

/**
 * Shows the group edit form.
 *
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	acp.form
 * @category	Community Framework
 */
class UserGroupEditForm extends UserGroupAddForm {
	/**
	 * @see	wcf\acp\form\UserGroupAddForm::$menuItemName
	 */
	public $menuItemName = 'wcf.acp.menu.link.group';
	
	/**
	 * @see	wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('admin.user.canEditGroup');
	
	/**
	 * id of the edited user group
	 * @var	integer
	 */
	public $groupID = 0;
	
	/**
	 * user group editor object
	 * @var	wcf\data\user\group\UserGroupEditor
	 */
	public $group = null;
	
	/**
	 * @see	wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		// get group
		if (isset($_REQUEST['id'])) $this->groupID = intval($_REQUEST['id']);
		$group = new UserGroup($this->groupID);
		if (!$group->groupID) {
			throw new IllegalLinkException();
		}
		if (!$group->isAccessible()) {
			throw new PermissionDeniedException();
		}
		
		$this->group = new UserGroupEditor($group);
		$this->optionHandler->setUserGroup($group);
	}
	
	/**
	 * @see	wcf\page\IPage::readData()
	 */
	public function readData() {
		if (empty($_POST)) {
			I18nHandler::getInstance()->setOptions('groupName', 1, $this->group->groupName, 'wcf.acp.group.group\d+');
			$this->groupName = $this->group->groupName;
			$options = $this->optionHandler->getCategoryOptions();
			
			// get default values
			if ($this->group->groupType != UserGroup::EVERYONE) {
				$defaultGroup = UserGroup::getGroupByType(UserGroup::EVERYONE);
				foreach ($options as $option) {
					$value = $defaultGroup->getGroupOption($option['object']->optionName);
					if ($value !== null) {
						$this->optionValues[$option['object']->optionName] = $value;
					}
				}
			}
			
			foreach ($options as $option) {
				$value = $this->group->getGroupOption($option['object']->optionName);
				if ($value !== null) {
					$this->optionValues[$option['object']->optionName] = $value;
				}
			}
		}
		
		parent::readData();
	}
	
	/**
	 * @see	wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		I18nHandler::getInstance()->assignVariables(!empty($_POST));
		
		WCF::getTPL()->assign(array(
			'groupID' => $this->group->groupID,
			'action' => 'edit'
		));
		
		// add warning when the initiator is in the group
		if ($this->group->isMember()) {
			WCF::getTPL()->assign('warningSelfEdit', true);
		}
	}
	
	/**
	 * @see	wcf\form\IForm::save()
	 */
	public function save() {
		AbstractForm::save();
		
		// save group
		$optionValues = $this->optionHandler->save();
		$saveOptions = array();
		if ($this->group->groupType == UserGroup::EVERYONE) {
			$saveOptions = $optionValues;
		}
		else {
			// get default group
			$defaultGroup = UserGroup::getGroupByType(UserGroup::EVERYONE);
			foreach ($this->optionHandler->getCategoryOptions() as $option) {
				$option = $option['object'];
				$defaultValue = $defaultGroup->getGroupOption($option->optionName);
				$typeObject = $this->optionHandler->getTypeObject($option->optionType);
					
				$newValue = $typeObject->merge($defaultValue, $optionValues[$option->optionID]);
				if ($newValue !== null) {
					$saveOptions[$option->optionID] = $newValue;
				}
			}
		}
		
		$this->groupName = 'wcf.acp.group.group'.$this->group->groupID;
		if (I18nHandler::getInstance()->isPlainValue('groupName')) {
			I18nHandler::getInstance()->remove($this->groupName, 1);
			$this->groupName = I18nHandler::getInstance()->getValue('groupName');
		}
		else {
			I18nHandler::getInstance()->save('groupName', $this->groupName, 'wcf.acp.group', 1);
		}
		
		$data = array(
			'data' => array_merge(array('groupName' => $this->groupName), $this->additionalFields),
			'options' => $saveOptions
		);
		$this->objectAction = new UserGroupAction(array($this->groupID), 'update', $data);
		$this->objectAction->executeAction();
		$this->saved();
		
		// show success message
		WCF::getTPL()->assign('success', true);
	}
}
