'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js'),
	
	ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('AdminPanelWebclient', 'getAbstractSettingsFormViewClass')
;

/**
* @constructor
*/
function CHmailserverAdminSettingsView()
{
	CAbstractSettingsFormView.call(this, '%ModuleName%');

	/* Editable fields */
//	this.enabled = ko.observable(!Settings.Disabled);
	this.supportedServers = ko.observable(Settings.SupportedServers);
	this.adminpass = ko.observable(Settings.AdminUser);
	this.adminuser = ko.observable(Settings.AdminPass);
	/*-- Editable fields */
}

_.extendOwn(CHmailserverAdminSettingsView.prototype, CAbstractSettingsFormView.prototype);

CHmailserverAdminSettingsView.prototype.ViewTemplate = '%ModuleName%_AdminSettingsView';

CHmailserverAdminSettingsView.prototype.getCurrentValues = function ()
{
	return [
//		!this.enabled(),
		Types.pString(this.supportedServers()),
		Types.pString(this.adminuser()),
		Types.pString(this.adminpass())
	];
};

CHmailserverAdminSettingsView.prototype.revertGlobalValues = function ()
{
//	this.enabled(!Settings.Disabled);
	this.supportedServers(Settings.SupportedServers);
	this.adminuser(Settings.AdminUser);
	this.adminpass(Settings.AdminPass);
};

CHmailserverAdminSettingsView.prototype.getParametersForSave = function ()
{
	return {
//		'Disabled': !this.enabled(),
		'SupportedServers': Types.pString(this.supportedServers()),
		'AdminUser': Types.pString(this.adminuser()),
		'AdminPass': Types.pString(this.adminpass())
	};
};

/**
 * @param {Object} oParameters
 */
CHmailserverAdminSettingsView.prototype.applySavedValues = function (oParameters)
{
//	Settings.updateAdmin(oParameters.Disabled, oParameters.SupportedServers, oParameters.Host, oParameters.Port);
	Settings.updateAdmin(oParameters.SupportedServers, oParameters.AdminUser, oParameters.AdminPass);
};

CHmailserverAdminSettingsView.prototype.setAccessLevel = function (sEntityType, iEntityId)
{
	this.visible(sEntityType === '');
};

module.exports = new CHmailserverAdminSettingsView();
