<template>
  <div class="row pb-5">
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-finish-sso-registration'"/>
    <div class="col-lg-8 m-auto">
      <card title="Complete Registration">
        <form>
          <RequiredText/>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">Time zone*
            </label>
            <div class="col-md-7" @change="removeTimeZoneError()">
              <b-form-select id="time_zone"
                             v-model="form.time_zone"
                             :options="timeZones"
                             required
                             :class="{ 'is-invalid': form.errors.has('time_zone') }"
              />
              <has-error :form="form" field="time_zone"/>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">Registration Type*
            </label>
            <div class="col-md-7">
              <b-form-select v-model="form.registration_type"
                             required
                             :options="registrationTypes"
                             :class="{ 'is-invalid': form.errors.has('registration_type') }"
              />
              <has-error :form="form" field="registration_type"/>
            </div>
          </div>
          <!-- Name -->
          <div v-if="![null,'student'].includes(form.registration_type)" class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="access_code">Access Code*
            </label>
            <div class="col-md-7">
              <input id="access_code"
                     v-model="form.access_code"
                     required
                     :class="{ 'is-invalid': form.errors.has('access_code') }"
                     class="form-control"
                     type="text"
                     name="access_code"
              >
              <has-error :form="form" field="access_code"/>
            </div>
          </div>
          <div v-if="form.registration_type === 'student'" class="form-group row">
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
          <b-row align-h="end" class="col-md-10">
            <b-button variant="primary" @click="finishSSORegistration()">
              Submit
            </b-button>
          </b-row>
        </form>
      </card>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import { getTimeZones } from '@vvo/tzdb'
import { populateTimeZoneSelect } from '~/helpers/TimeZones'
import { redirectOnSSOCompletion } from '../../helpers/LoginRedirect'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  components: {
    AllFormErrors
  },
  data: () => ({
    allFormErrors: [],
    inIFrame: false,
    isStudent: true,
    form: new Form({
      registration_type: null,
      access_code: '',
      student_id: '',
      time_zone: null
    }),
    timeZones: [
      { value: null, text: 'Please select a time zone' }
    ],
    registrationTypes: [{ value: null, text: 'Please select your registration type' },
      { value: 'student', text: 'Student' },
      { value: 'instructor', text: 'Instructor' },
      { value: 'grader', text: 'Grader' }
    ]
  }),
  watch: {
    '$route' (to) {
      this.setRegistrationType(to.path)
    }
  },
  mounted () {
    let timeZones = getTimeZones()
    populateTimeZoneSelect(timeZones, this)
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },
  methods: {
    async finishSSORegistration () {
      try {
        const { data } = await this.form.post('/api/sso/finish-registration')
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        // go to the page before the attempted login
        if (this.inIFrame) {
          window.location = data.landing_page // instead of router push so that I can get the refreshed user
          return false
        } else {
          redirectOnSSOCompletion(this.form.registration_type)
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-finish-sso-registration')
        }
      }
    }
  }
}
</script>
