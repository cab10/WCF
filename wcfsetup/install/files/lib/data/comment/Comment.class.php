<?php
namespace wcf\data\comment;
use wcf\data\DatabaseObject;
use wcf\data\IMessage;
use wcf\data\TUserContent;
use wcf\system\comment\CommentHandler;
use wcf\system\html\output\HtmlOutputProcessor;
use wcf\util\StringUtil;

/**
 * Represents a comment.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Data\Comment
 *
 * @property-read	integer		$commentID		unique id of the comment
 * @property-read	integer		$objectTypeID		id of the `com.woltlab.wcf.comment.commentableContent` object type
 * @property-read	integer		$objectID		id of the commented object of the object type represented by `$objectTypeID`
 * @property-read	integer		$time			timestamp at which the comment has been written
 * @property-read	integer|null	$userID			id of the user who wrote the comment or `null` if the user does not exist anymore or if the comment has been written by a guest
 * @property-read	string		$username		name of the user or guest who wrote the comment
 * @property-read	string		$message		comment message
 * @property-read	integer		$responses		number of responses on the comment
 * @property-read	string		$responseIDs		serialized array with the ids of the five latest comment responses
 * @property-read	integer		$unfilteredResponses	number of all responses on the comment, including disabled ones
 * @property-read	string		$unfilteredResponseIDs	serialized array with the ids of the five latest comment responses, including disabled ones
 * @property-read       integer         $enableHtml             is 1 if HTML will rendered in the comment, otherwise 0
 * @property-read	integer		$isDisabled		is 1 if the comment is disabled, otherwise 0
 */
class Comment extends DatabaseObject implements IMessage {
	use TUserContent;
	
	/**
	 * Returns a list of response ids.
	 * 
	 * @return	integer[]
	 */
	public function getResponseIDs() {
		if ($this->responseIDs === null || $this->responseIDs == '') {
			return [];
		}
		
		$responseIDs = @unserialize($this->responseIDs);
		if ($responseIDs === false) {
			return [];
		}
		
		return $responseIDs;
	}
	
	/**
	 * Returns a list of unfiltered response ids, including those that are still disabled.
	 *
	 * @return	integer[]
	 */
	public function getUnfilteredResponseIDs() {
		if ($this->unfilteredResponseIDs === null || $this->unfilteredResponseIDs == '') {
			return [];
		}
		
		$responseIDs = @unserialize($this->unfilteredResponseIDs);
		if ($responseIDs === false) {
			return [];
		}
		
		return $responseIDs;
	} 
	
	/**
	 * @inheritDoc
	 */
	public function getFormattedMessage() {
		$processor = new HtmlOutputProcessor();
		$processor->process($this->message, 'com.woltlab.wcf.comment', $this->commentID);
		
		return $processor->getHtml();
	}
	
	/**
	 * Returns a simplified version of the formatted message.
	 * 
	 * @return	string
	 */
	public function getSimplifiedFormattedMessage() {
		$processor = new HtmlOutputProcessor();
		$processor->setOutputType('text/simplified-html');
		$processor->process($this->message, 'com.woltlab.wcf.comment', $this->commentID);
		
		return $processor->getHtml();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getExcerpt($maxLength = 255) {
		return StringUtil::truncateHTML($this->getSimplifiedFormattedMessage(), $maxLength);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getMessage() {
		return $this->message;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return CommentHandler::getInstance()->getObjectType($this->objectTypeID)->getProcessor()->getLink($this->objectTypeID, $this->objectID);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return CommentHandler::getInstance()->getObjectType($this->objectTypeID)->getProcessor()->getTitle($this->objectTypeID, $this->objectID);
	}
	
	/**
	 * @inheritDoc
	 */
	public function isVisible() {
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function __toString() {
		return $this->getFormattedMessage();
	}
}
