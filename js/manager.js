'use strict';

module.exports = function (oAppData) {
	var App = require('%PathToCoreWebclientModule%/js/App.js');
	
	if (App.getUserRole() === Enums.UserRole.SuperAdmin)
	{
		var
			TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
			
			Settings = require('modules/%ModuleName%/js/Settings.js')
		;
		
		Settings.init(oAppData);

		return {
			start: function (ModulesManager) {
				ModulesManager.run('AdminPanelWebclient', 'registerAdminPanelTab', [
					function(resolve) {
						require.ensure(
							['modules/%ModuleName%/js/views/AdminSettingsView.js'],
							function() {
								resolve(require('modules/%ModuleName%/js/views/AdminSettingsView.js'));
							},
							'admin-bundle'
						);
					},
					Settings.HashModuleName,
					TextUtils.i18n('%MODULENAME%/LABEL_HMAIL_SETTINGS_TAB')
				]);
			}
		};
	}
	
	return null;
};
