'use strict';

var
	_ = require('underscore'),
	
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js')
;

module.exports = {
	ServerModuleName: 'MailChangePasswordHmailserverPlugin',
	HashModuleName: 'mail-hmailserver-plugin',
	
	SupportedServers: '',
	AdminUser: '',
	HasAdminPass: false,
	
	/**
	 * Initializes settings of the module.
	 * 
	 * @param {Object} oAppData Object contained modules settings.
	 */
	init: function (oAppData)
	{
		var oAppDataSection = oAppData['%ModuleName%'];
		
		if (!_.isEmpty(oAppDataSection))
		{
			this.SupportedServers = Types.pString(oAppDataSection.SupportedServers);
			this.AdminUser = Types.pString(oAppDataSection.AdminUser);
			this.HasAdminPass = Types.pBool(oAppDataSection.HasAdminPass);
		}
	},
	
	/**
	 * Updates new settings values after saving on server.
	 * 
	 * @param {string} sSupportedServers
	 * @param {string} sAdminUser
	 * @param {string} sAdminPass
	 */
	updateAdmin: function (sSupportedServers, sAdminUser, sAdminPass)
	{
		this.SupportedServers = Types.pString(sSupportedServers);
		this.AdminUser = Types.pString(sAdminUser);
		this.HasAdminPass = sAdminPass !== '';
	}
};
