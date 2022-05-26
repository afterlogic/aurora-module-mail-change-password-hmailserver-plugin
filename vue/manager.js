import settings from '../../MailChangePasswordHmailserverPlugin/vue/settings'

export default {
  moduleName: 'MailChangePasswordHmailserverPlugin',

  requiredModules: ['MailWebclient'],

  init (appData) {
    settings.init(appData)
  },

  getAdminSystemTabs () {
    return [
      {
        tabName: 'hmailserver',
        tabTitle: 'MAILCHANGEPASSWORDHMAILSERVERPLUGIN.LABEL_HMAIL_SETTINGS_TAB',
        tabRouteChildren: [
          { path: 'hmailserver', component: () => import('./components/HmailserverAdminSettings') },
        ],
      },
    ]
  },
}
