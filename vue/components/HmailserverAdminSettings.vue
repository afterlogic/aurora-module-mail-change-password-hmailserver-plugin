<template>
  <q-scroll-area class="full-height full-width">
    <div class="q-pa-lg ">
      <div class="row q-mb-md">
        <div class="col text-h5" v-t="'MAILCHANGEPASSWORDHMAILSERVERPLUGIN.HEADING_SETTINGS_TAB'"></div>
      </div>
      <q-card flat bordered class="card-edit-settings">
        <q-card-section>
          <div class="row q-mb-md">
            <div class="col-2 q-my-sm" v-t="'MAILCHANGEPASSWORDHMAILSERVERPLUGIN.LABEL_MAIL_SERVERS'"></div>
            <div class="col-5">
              <q-input outlined dense class="bg-white" type="textarea" v-model="supportedServers" @keyup.enter="save"/>
            </div>
          </div>
          <div class="row q-mb-md">
            <div class="col-2 q-my-sm" />
            <div class="col-8">
              <q-item-label caption>
                {{ $t('MAILCHANGEPASSWORDPOPPASSDPLUGIN.LABEL_HINT_MAIL_SERVERS') }}
              </q-item-label>
            </div>
          </div>
          <div class="row q-mb-md">
            <div class="col-2 q-my-sm" v-t="'MAILCHANGEPASSWORDHMAILSERVERPLUGIN.LABEL_ADMINUSER'"></div>
            <div class="col-5">
              <q-input outlined dense class="bg-white" v-model="adminUser" @keyup.enter="save"/>
            </div>
          </div>
          <div class="row q-mb-md">
            <div class="col-2 q-my-sm" v-t="'MAILCHANGEPASSWORDHMAILSERVERPLUGIN.LABEL_ADMINPASS'"></div>
            <div class="col-5">
              <q-input outlined dense class="bg-white" type="password" autocomplete="new-password" v-model="password" @keyup.enter="save"/>
            </div>
          </div>
        </q-card-section>
      </q-card>
      <div class="q-pt-md text-right">
        <q-btn unelevated no-caps dense class="q-px-sm" :ripple="false" color="primary" @click="save"
               :label="saving ? $t('COREWEBCLIENT.ACTION_SAVE_IN_PROGRESS') : $t('COREWEBCLIENT.ACTION_SAVE')">
        </q-btn>
      </div>
    </div>
    <UnsavedChangesDialog ref="unsavedChangesDialog"/>
  </q-scroll-area>
</template>

<script>
import UnsavedChangesDialog from 'src/components/UnsavedChangesDialog'
import webApi from 'src/utils/web-api'
import settings from '../../../MailChangePasswordHmailserverPlugin/vue/settings'
import notification from 'src/utils/notification'
import errors from 'src/utils/errors'
import _ from 'lodash'

const FAKE_PASS = '     '

export default {
  name: 'HmailserverAdminSettings',
  components: {
    UnsavedChangesDialog
  },
  data () {
    return {
      adminUser: '',
      hasAdminPass: false,
      supportedServers: '',
      savedPass: FAKE_PASS,
      password: FAKE_PASS,
      saving: false
    }
  },
  mounted () {
    this.populate()
  },
  beforeRouteLeave(to, from, next) {
    if (this.hasChanges() && _.isFunction(this?.$refs?.unsavedChangesDialog?.openConfirmDiscardChangesDialog)) {
      this.$refs.unsavedChangesDialog.openConfirmDiscardChangesDialog(next)
    } else {
      next()
    }
  },
  methods: {
    hasChanges() {
      const data = settings.getHmailServerPluginSettings()
      return this.supportedServers !== data.supportedServers ||
          this.adminUser !== data.adminUser ||
          this.port !== data.port ||
          this.password !== this.savedPass
    },
    save () {
      if (!this.saving) {
        this.saving = true
        const parameters = {
          SupportedServers: this.supportedServers,
          AdminUser: this.adminUser,
        }
        if (this.password !== FAKE_PASS) {
          parameters.AdminPass = this.password
        }
        webApi.sendRequest({
          moduleName: 'MailChangePasswordHmailserverPlugin',
          methodName: 'UpdateSettings',
          parameters,
        }).then(result => {
          this.saving = false
          if (result === true) {
            settings.saveHmailServerPluginSettings({
              supportedServers: this.supportedServers,
              adminUser: this.adminUser,
              hasAdminPass: this.password !== ''
            })
            this.savedPass = this.password
            notification.showReport(this.$t('COREWEBCLIENT.REPORT_SETTINGS_UPDATE_SUCCESS'))
          } else {
            notification.showError(this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED'))
          }
        }, response => {
          this.saving = false
          notification.showError(errors.getTextFromResponse(response, this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED')))
        })
      }
    },
    populate () {
      const data = settings.getHmailServerPluginSettings()
      this.adminUser = data.adminUser
      this.hasAdminPass = data.hasAdminPass
      this.supportedServers = data.supportedServers
      this.savedPass = data.hasAdminPass ? FAKE_PASS : ''
      this.password = data.hasAdminPass ? FAKE_PASS : ''
    }
  }
}
</script>

<style scoped>

</style>
