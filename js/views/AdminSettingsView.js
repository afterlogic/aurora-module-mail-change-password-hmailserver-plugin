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

	this.sFakePass = '      ';
	
	/* Editable fields */
	this.supportedServers = ko.observable(Settings.SupportedServers);
	this.adminuser = ko.observable(Settings.AdminUser);
	console.log('Settings.HasAdminUser', Settings.HasAdminPass);
	this.adminpass = ko.observable(Settings.HasAdminPass ? this.sFakePass : '');
	console.log('this.adminpass', this.adminpass());
	/*-- Editable fields */
}

_.extendOwn(CHmailserverAdminSettingsView.prototype, CAbstractSettingsFormView.prototype);

CHmailserverAdminSettingsView.prototype.ViewTemplate = '%ModuleName%_AdminSettingsView';

CHmailserverAdminSettingsView.prototype.getCurrentValues = function ()
{
	return [
		Types.pString(this.supportedServers()),
		Types.pString(this.adminuser()),
		Types.pString(this.adminpass())
	];
};

CHmailserverAdminSettingsView.prototype.revertGlobalValues = function ()
{
	this.supportedServers(Settings.SupportedServers);
	this.adminuser(Settings.AdminUser);
	this.adminpass(Settings.HasAdminPass ? this.sFakePass : '');
};

CHmailserverAdminSettingsView.prototype.getParametersForSave = function ()
{
	return {
		'SupportedServers': Types.pString(this.supportedServers()),
		'AdminUser': Types.pString(this.adminuser()),
		'AdminPass': this.adminpass() === this.sFakePass ? '' : Types.pString(this.adminpass())
	};
};

/**
 * @param {Object} oParameters
 */
CHmailserverAdminSettingsView.prototype.applySavedValues = function (oParameters)
{
	Settings.updateAdmin(oParameters.SupportedServers, oParameters.AdminUser, oParameters.AdminPass);
};

CHmailserverAdminSettingsView.prototype.setAccessLevel = function (sEntityType, iEntityId)
{
	this.visible(sEntityType === '');
};

module.exports = new CHmailserverAdminSettingsView();
