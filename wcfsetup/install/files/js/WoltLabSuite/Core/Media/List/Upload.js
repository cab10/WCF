/**
 * Uploads media files.
 *
 * @author	Matthias Schmidt
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module	WoltLabSuite/Core/Media/List/Upload
 */
define(
	[
		'Core', 'Dom/Util', '../Upload'
	],
	function(
		Core, DomUtil, MediaUpload
	)
{
	"use strict";
	
	if (!COMPILER_TARGET_DEFAULT) {
		var Fake = function() {};
		Fake.prototype = {
			_createButton: function() {},
			_success: function() {},
			_upload: function() {},
			_createFileElement: function() {},
			_getParameters: function() {},
			_uploadFiles: function() {},
			_createFileElements: function() {},
			_failure: function() {},
			_insertButton: function() {},
			_progress: function() {},
			_removeButton: function() {}
		};
		return Fake;
	}
	
	/**
	 * @constructor
	 */
	function MediaListUpload(buttonContainerId, targetId, options) {
		MediaUpload.call(this, buttonContainerId, targetId, options);
	}
	Core.inherit(MediaListUpload, MediaUpload, {
		/**
		 * Creates the upload button.
		 */
		_createButton: function() {
			MediaListUpload._super.prototype._createButton.call(this);
			
			var icon = elCreate('span');
			icon.classList = 'icon icon16 fa-upload';
			DomUtil.prepend(icon, elBySel('span', this._button));
		}
	});
	
	return MediaListUpload;
});
