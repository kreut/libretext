<template>
  <div class="row">
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-register-form'"/>
    <div class="col-lg-8 m-auto">
      <card v-if="mustVerifyEmail" :title="registrationTitle">
        <div class="alert alert-success" role="alert">
          {{ $t('verify_email_address') }}
        </div>
      </card>
      <card v-else :title="registrationTitle">
        <form @submit.prevent="register" @keydown="form.onKeydown($event)">
          <!-- GitHub Register Button -->
          <div v-if="isStudent">
            <div class="text-center mb-2">
              <login-with-libretexts action="Registration"/>
            </div>
            <div class="text-center mb-2">
              <span class="font-text-bold">OR</span>
            </div>
          </div>
          <b-card sub-title="Register with Adapt"
                  sub-title-text-variant="primary"
                  header-text-variant="white"
          >
            <hr>
            <!-- Name -->
            <RequiredText />
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">First Name<Asterisk /></label>
              <div class="col-md-7">
                <input id="first_name" v-model="form.first_name"
                       :class="{ 'is-invalid': form.errors.has('first_name') }" class="form-control" type="text"
                       name="first_name" placeholder="First"
                >
                <has-error :form="form" field="first_name"/>
              </div>
              </div>
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">Last Name<Asterisk /></label>
              <div class="col-md-7">
                <input id="last_name" v-model="form.last_name"
                       :class="{ 'is-invalid': form.errors.has('last_name') }"
                       class="form-control" type="text" name="last_name" placeholder="Last"
                >
                <has-error :form="form" field="last_name"/>
              </div>
            </div>
            <div v-if="isStudent" class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">Student ID<Asterisk /></label>
              <div class="col-md-7">
                <input id="student_id" v-model="form.student_id"
                       :class="{ 'is-invalid': form.errors.has('student_id') }"
                       class="form-control" type="text" name="student_id"
                >
                <has-error :form="form" field="student_id"/>
              </div>
            </div>
            <!-- Email -->
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">Email<Asterisk /></label>
              <div class="col-md-7">
                <input id="email" v-model="form.email"
                       :class="{ 'is-invalid': form.errors.has('email') }"
                       class="form-control"
                       type="email" name="email"
                >
                <has-error :form="form" field="email"/>
              </div>
            </div>

            <!-- Password -->
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">Password<Asterisk /></label>
              <div class="col-md-7">
                <input id="password" v-model="form.password"
                       :class="{ 'is-invalid': form.errors.has('password') }"
                       class="form-control"
                       type="password" name="password"
                >
                <has-error :form="form" field="password"/>
              </div>
            </div>

            <!-- Password Confirmation -->
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">Confirm Password<Asterisk /></label>
              <div class="col-md-7">
                <input id="password_confirmation"
                       v-model="form.password_confirmation"
                       :class="{ 'is-invalid': form.errors.has('password_confirmation') }" class="form-control"
                       type="password" name="password_confirmation"
                >
                <has-error :form="form" field="password_confirmation"/>
              </div>
            </div>

            <!-- Access Code -->
            <div v-if="isInstructor || isGrader" class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">Access Code<Asterisk /></label>
              <div class="col-md-7">
                <input id="access_code" v-model="form.access_code"
                       :class="{ 'is-invalid': form.errors.has('access_code') }"
                       class="form-control" type="text" name="access_code"
                >
                <has-error :form="form" field="access_code"/>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">Time zone<Asterisk /></label>
              <div class="col-md-7" @change="removeTimeZoneError()">
                <b-form-select id="time_zone" v-model="form.time_zone"
                               :options="timeZones"
                               :class="{ 'is-invalid': form.errors.has('time_zone') }"
                />
                <has-error :form="form" field="time_zone"/>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-7 offset-md-8 d-flex">
                <!-- Submit Button -->
                <v-button :loading="form.busy">
                  Submit
                </v-button>
              </div>
            </div>
          </b-card>
        </form>
      </card>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import LoginWithLibretexts from '~/components/LoginWithLibretexts'
import { redirectOnLogin } from '~/helpers/LoginRedirect'
import { getTimeZones } from '@vvo/tzdb'
import { populateTimeZoneSelect } from '~/helpers/TimeZones'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  middleware: 'guest',

  components: {
    LoginWithLibretexts,
    AllFormErrors
  },

  metaInfo () {
    return { title: this.$t('register') }
  },
  data: () => ({
    allFormErrors: [],
    form: new Form({
      first_name: '',
      last_name: '',
      email: '',
      student_id: '',
      password: '',
      password_confirmation: '',
      access_code: '',
      registration_type: '',
      time_zone: null
    }),
    timeZones: [
      { value: null, text: 'Please select a time zone' }
    ],
    mustVerifyEmail: false,
    isGrader: false,
    isInstructor: false,
    isStudent: false,
    registrationTitle: ''
  }),
  watch: {
    '$route' (to) {
      this.setRegistrationType(to.path)
    }
  },
  mounted () {
    this.setRegistrationType(this.$route.path)
    let timeZones = getTimeZones()
    populateTimeZoneSelect(timeZones, this)
  },

  methods: {
    removeTimeZoneError () {
      this.form.errors.clear('time_zone')
    },
    setRegistrationType (path) {
      this.form.registration_type = path.replace('/register/', '')
      switch (this.form.registration_type) {
        case 'instructor':
          this.registrationTitle = 'Instructor Registration'
          this.isInstructor = true
          this.isStudent = this.isGrader = false
          break
        case 'student':
          this.registrationTitle = 'Student Registration'
          this.isStudent = true
          this.isInstructor = this.isGrader = false
          break
        case 'grader':
          this.registrationTitle = 'Grader Registration'
          this.isGrader = true
          this.isStudent = this.isInstructor = false
          break
      }
    },
    async register () {
      try {
        // Register the user.
        const { data } = await this.form.post('/api/register')
        // Must verify email fist.
        if (data.status) {
          this.mustVerifyEmail = true
        } else {
          // Log in the user.
          const { data: { token } } = await this.form.post('/api/login')

          // Save the token.
          this.$store.dispatch('auth/saveToken', { token })

          // Update the user.
          await this.$store.dispatch('auth/updateUser', { user: data })

          // Redirect to the correct home page
          redirectOnLogin(this.$store, this.$router)
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-register-form')
        }
      }
    }
  }
}
</script>
