import _ from 'lodash'
import typesUtils from 'src/utils/types'

class HmailServerPluginSettings {
  constructor (appData) {
    const mailChangePasswordHmailserverPlugin = appData.MailChangePasswordHmailserverPlugin

    if (!_.isEmpty(mailChangePasswordHmailserverPlugin)) {
      this.supportedServers = typesUtils.pString(mailChangePasswordHmailserverPlugin.SupportedServers)
      this.adminUser = typesUtils.pString(mailChangePasswordHmailserverPlugin.AdminUser)
      this.hasAdminPass = typesUtils.pBool(mailChangePasswordHmailserverPlugin.HasAdminPass)
    }
  }

  saveHmailServerPluginSettings ({ supportedServers, adminUser, hasAdminPass }) {
    this.supportedServers = supportedServers
    this.adminUser = adminUser
    this.hasAdminPass = hasAdminPass
  }
}

let settings = null

export default {
  init (appData) {
    settings = new HmailServerPluginSettings(appData)
  },
  saveHmailServerPluginSettings (data) {
    settings.saveHmailServerPluginSettings(data)
  },
  getHmailServerPluginSettings () {
    return {
      supportedServers: settings.supportedServers,
      adminUser: settings.adminUser,
      hasAdminPass: settings.hasAdminPass
    }
  },
}
