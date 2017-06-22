'use strict';

var Types = require('%PathToCoreWebclientModule%/js/utils/Types.js');

module.exports = {
	ServerModuleName: 'MailChangePasswordHmailserverPlugin',
	HashModuleName: 'mail-hmailserver-plugin',
	
	Disabled: false,
	SupportedServers: '',
	AdminUser: '',
	AdminPass: '',
	
	/**
	 * Initializes settings of the module.
	 * 
	 * @param {Object} oAppDataSection module section in AppData.
	 */
	init: function (oAppDataSection)
	{
		if (oAppDataSection)
		{
			this.Disabled = !!oAppDataSection.Disabled;
			this.SupportedServers = Types.pString(oAppDataSection.SupportedServers);
			this.AdminUser = Types.pString(oAppDataSection.AdminUser);
			this.AdminPass = Types.pString(oAppDataSection.AdminPass);
		}
	},
	
	updateAdmin: function (bDisabled, aSupportedServers, sAdminUser, sAdminPass)
	{
		this.Disabled = !!bDisabled;
		this.SupportedServers = Types.pString(aSupportedServers);
		this.AdminUser = Types.pString(sAdminUser);
		this.AdminPass = Types.pString(sAdminPass);
	}
};
