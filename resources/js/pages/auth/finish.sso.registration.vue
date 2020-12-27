<template>
  <div class="row">
    <div class="col-lg-8 m-auto">
      <card title="Complete Registration">
        <form>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">Time zone</label>
            <div class="col-md-7" @change="removeTimeZoneError()">
              <b-form-select v-model="form.time_zone"
                             :options="timeZones"
                             :class="{ 'is-invalid': form.errors.has('time_zone') }"
              />
              <has-error :form="form" field="time_zone" />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">Registration Type</label>
            <div class="col-md-7">
              <b-form-select v-model="form.registration_type"
                             :options="registrationTypes"
                             :class="{ 'is-invalid': form.errors.has('registration_type') }"
              />
              <has-error :form="form" field="registration_type" />
            </div>
          </div>
          <!-- Name -->
          <div v-if="![null,'student'].includes(form.registration_type)" class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">Access Code</label>
            <div class="col-md-7">
              <input v-model="form.access_code" :class="{ 'is-invalid': form.errors.has('access_code') }"
                     class="form-control" type="text" name="access_code"
              >
              <has-error :form="form" field="access_code" />
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

export default {
  data: () => ({
    isStudent: true,
    form: new Form({
      registration_type: null,
      access_code: '',
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
  },
  methods: {
    removeTimeZoneError () {
      this.form.errors.clear('time_zone')
    },
    async finishSSORegistration () {
      try {
        const { data } = await this.form.post('/api/finish-sso-registration')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        redirectOnSSOCompletion(this.form.registration_type, this.$router)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    }
  }
}
</script>
