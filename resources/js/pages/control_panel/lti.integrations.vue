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
        <b-modal id="modal-submit-lti-registration"
                 title="Save LTI Registration"
                 size="lg"
        >
          <LTIRegistration :form="ltiRegistrationForm" :show-campus-id="true" :show-schools="false"/>
          <template #modal-footer>
            <b-button
              variant="primary"
              size="sm"
              class="float-right"
              @click="saveLTIRegistration"
            >
              Submit
            </b-button>
          </template>
        </b-modal>
        <div class="float-right mb-2">
          <b-button variant="primary" size="sm" @click="$bvModal.show('modal-submit-lti-registration')">
            Add Registration
          </b-button>
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
import LTIRegistration from '~/components/LTIRegistration'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  components: {
    Loading,
    LTIRegistration,
    ToggleButton
  },
  data: () => ({
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
        key: 'client_id',
        label: 'Developer Key ID'
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
    },
    async saveLTIRegistration () {
      try {
        this.ltiRegistrationForm.errors.clear()
        const { data } = await this.ltiRegistrationForm.post('/api/lti-registration/save')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.ltiRegistrationForm = new Form({})
          this.$bvModal.hide('modal-submit-lti-registration')
          this.getLTIRegistrations()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          this.$bvModal.hide('modal-submit-lti-registration')
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
