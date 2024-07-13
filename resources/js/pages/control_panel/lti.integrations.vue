<template>
  <div>
    <div v-if="hasAccess">
      <PageTitle title="LTI Integrations"/>
      <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :is-full-page="true"
                 :width="128"
                 :height="128"
                 color="#007BFF"
                 background="#FFFFFF"
        />
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="1"
          label="Campus*"
          label-size="sm"
        >
          <b-form-input
            id="campus"
            v-model="ltiPendingRegistrationForm.campus"
            type="text"
            size="sm"
            required
            :class="{ 'is-invalid': ltiPendingRegistrationForm.errors.has('campus') }"
            @keydown="ltiPendingRegistrationForm.errors.clear('campus')"
          />
          <has-error :form="ltiPendingRegistrationForm" field="campus"/>
          <b-button variant="primary" size="sm" class="mt-2" @click="saveLTIPendingRegistration">
            Submit
          </b-button>
        </b-form-group>
        <div v-if="campusId">
          <div class="mb-2">
            Canvas: <span id="canvas-url">{{ origin }}/lti/canvas/config/{{ campusId }}</span>

            <span style="cursor: pointer;" @click="doCopy('canvas-url')"><font-awesome-icon :icon="copyIcon"/></span>
          </div>
          <div class="mb-2">
            Moodle: <span id="moodle-url">{{ origin }}/lti/moodle/config/{{ campusId }}</span>
            <span style="cursor: pointer;" @click="doCopy('moodle-url')"><font-awesome-icon :icon="copyIcon"/></span>
          </div>
          <div class="mb-2">
            Blackboard: <span id="blackboard-url">{{ origin }}/lti/blackboard/config/{{
              campusId
            }}</span>
            <span style="cursor: pointer;" @click="doCopy('blackboard-url')"><font-awesome-icon :icon="copyIcon"/></span>
          </div>
        </div>
        <b-table v-show="ltiRegistrations.length"
                 striped
                 hover
                 :fields="fields"
                 :items="ltiRegistrations"
                 class="border border-1 rounded"
        >
          <template v-slot:cell(active)="data">
            <toggle-button
              class="mt-1"
              :width="57"
              :value=" parseInt(data.item.active) === 1"
              :sync="true"
              :font-size="14"
              :margin="4"
              :color="toggleColors"
              :labels="{checked: 'Yes', unchecked: 'No'}"
              @change="toggleActive(data.item.id)"
            />
          </template>
        </b-table>
      </div>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import { mapGetters } from 'vuex'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { ToggleButton } from 'vue-js-toggle-button'
import { doCopy } from '~/helpers/Copy'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  components: {
    Loading,
    ToggleButton,
    FontAwesomeIcon
  },
  data: () => ({
    copyIcon: faCopy,
    origin: window.location.origin,
    campusId: '',
    ltiPendingRegistrationForm: new Form({
      campus: ''
    }),
    ltiRegistrationForm: new Form({
      admin_name: '',
      admin_email: '',
      url: '',
      developer_key_id: ''
    }),
    toggleColors: window.config.toggleColors,
    hasAccess: false,
    isLoading: true,
    ltiRegistrations: [],
    fields: [{
      key: 'auth_server',
      label: 'URL'
    },
      'campus_id',
      'admin_email',
      {
        key: 'api',
        label: 'API'
      },
      'active']
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.getLTIRegistrations()
  },
  methods: {
    doCopy,
    async saveLTIPendingRegistration () {
      try {
        const { data } = await this.ltiPendingRegistrationForm.post(`/api/lti-pending-registration`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.campusId = data.campus_id
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async toggleActive (registrationId) {
      try {
        const { data } = await axios.patch(`/api/lti-registration/active/${registrationId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getLTIRegistrations()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getLTIRegistrations () {
      try {
        const { data } = await axios.get(`/api/lti-registration`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.ltiRegistrations = data.lti_registrations
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

<style scoped>

</style>
