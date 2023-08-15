<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-finish-clicker-app-sso-registration"/>
    <b-modal id="modal-finish-clicker-app-sso-registration"
             title="Complete Registration"
             no-close-on-esc
             hide-header-close
             no-close-on-backdrop
    >
      <form>
        <RequiredText/>
        <div class="form-group row">
          <label class="col-md-3 col-form-label text-md-right">Time zone*
          </label>
          <div class="col-md-7"
          >
            <b-form-select id="time_zone"
                           v-model="form.time_zone"
                           :options="timeZones"
                           required
                           :class="{ 'is-invalid': form.errors.has('time_zone') }"
                           @change="form.errors.clear('time_zone')"
            />
            <has-error :form="form" field="time_zone"/>
          </div>
        </div>
        <div class="form-group row">
          <label class="col-md-3 col-form-label text-md-right" for="student_id">Student ID*
          </label>
          <div class="col-md-7">
            <input id="student_id"
                   v-model="form.student_id"
                   required
                   :class="{ 'is-invalid': form.errors.has('student_id') }"
                   class="form-control" type="text" name="student_id"
            >
            <has-error :form="form" field="student_id"/>
          </div>
        </div>
      </form>
      <template #modal-footer>
        <b-button variant="primary" class="float-right" @click="finishClickerAppSSORegistration()">
          Submit
        </b-button>
      </template>
    </b-modal>
    <div v-if="!isRegistration">
      <div>Token: {{ token }}</div>
      <div>Browser: {{ $browserDetect.meta.name }}</div>
      <a id="launch-url" :href="url">{{ url }}</a>
    </div>
  </div>
</template>

<script>
import AllFormErrors from '~/components/AllFormErrors'
import Form from 'vform'
import { getTimeZones } from '@vvo/tzdb'
import { populateTimeZoneSelect } from '~/helpers/TimeZones'

export default {
  name: 'LaunchClickerApp',
  components: {
    AllFormErrors
  },
  data: () => ({
    allFormErrors: [],
    token: '',
    url: '',
    isRegistration: false,
    form: new Form({
      student_id: '',
      time_zone: null
    }),
    timeZones: [
      { value: null, text: 'Please select a time zone' }
    ]
  }),
  created () {
    this.token = this.$route.params.token
    this.isRegistration = this.$route.params.isRegistration
    this.url = this.$browserDetect.isChrome
      ? `adaptclicker://adapt.libretexts.org/courses?token=${this.token}#adaptclicker;scheme=adaptclicker;package=edu.ualr.libretextTest;end`
      : `adaptclicker://adapt.libretexts.org/courses?token=${this.token}`
  },
  mounted () {
    if (this.isRegistration) {
      let timeZones = getTimeZones()
      populateTimeZoneSelect(timeZones, this)
      this.$bvModal.show('modal-finish-clicker-app-sso-registration')
    } else {
      document.getElementById('launch-url').click()
    }
  },
  methods: {
    async finishClickerAppSSORegistration () {
      try {
        await this.$store.dispatch('auth/saveToken', {
          token: this.token
        })
        const { data } = await this.form.post('/api/sso/finish-clicker-app-sso-registration')
        if (data.type === 'success') {
          this.$noty.success(data.message)
          this.$bvModal.hide('modal-finish-clicker-app-sso-registration')
          this.isRegistration = false
          document.getElementById('launch-url').click()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-finish-clicker-app-sso-registration')
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
