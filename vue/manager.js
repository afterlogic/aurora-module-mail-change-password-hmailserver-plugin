import settings from '../../MailChangePasswordHmailserverPlugin/vue/settings'

export default {
  moduleName: 'MailChangePasswordHmailserverPlugin',

  requiredModules: [],

  init (appData) {
    settings.init(appData)
  },
  getAdminSystemTabs () {
    return [
      {
        tabName: 'mail-hmailserver-plugin',
        title: 'MAILCHANGEPASSWORDHMAILSERVERPLUGIN.LABEL_HMAIL_SETTINGS_TAB',
        component () {
          return import('./components/HmailserverAdminSettings')
        },
      },
    ]
  },
}
