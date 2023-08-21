<template>
  <div>
    <Email id="request-instructor-access-code-modal"
           ref="request_instructor_access_code_email"
           extra-email-modal-text="Please use this form to request an instructor access code."
           title="Instructor Access Code"
           type="contact_us"
           subject="Request Instructor Access Code"
    />
    <Email id="request-tester-access-code-modal"
           ref="request_tester_access_code_email"
           extra-email-modal-text="Please use this form to request a tester access code."
           title="Tester Access Code"
           type="contact_us"
           subject="Request Tester Access Code"
    />
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-register-form'"/>
    <PageTitle :title="registrationTitle"/>
    <div class="col-lg-8 m-auto">
      <card v-if="mustVerifyEmail" :title="registrationTitle">
        <div class="alert alert-success" role="alert">
          {{ $t('verify_email_address') }}
        </div>
      </card>
      <form @submit.prevent="register" @keydown="form.onKeydown($event)">
        <!-- GitHub Register Button -->
        <div v-if="isStudent">
          <div class="row mb-3">
            Instructions: You can either use your Campus Registration (SSO) to register with ADAPT or you
            can register directly with ADAPT. Before registering, please ask your instructor if they have a preference.
          </div>
          <div class="text-center mb-2">
            <login-with-libretexts action="Registration"/>
          </div>
          <div class="text-center mb-2">
            <span class="font-text-bold">or</span>
          </div>
        </div>
        <b-card h header-html="<h2 class=&quot;h5&quot;>Register With ADAPT</h2>">
          <!-- Name -->
          <p v-if="isTester">
            Tester accounts are special accounts used by those who work in campus testing centers to help administer exams on behalf of instructors.
          </p>
          <p v-if="(isTester || isInstructor) && !form.access_code">
            To register as an <span v-if="isInstructor">instructor</span><span v-if="isTester">tester</span>, please fill out the form below using your
            <span v-if="isTester">Tester</span><span v-if="isInstructor">Instructor</span> Access Code. If you
            don't have an access code, then please <a href="" @click.prevent="openSendEmailModal()">contact us</a>
            and we can provide one for you.
          </p>
          <RequiredText/>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="first_name">First Name*
            </label>
            <div class="col-md-7">
              <input id="first_name" v-model="form.first_name"
                     :class="{ 'is-invalid': form.errors.has('first_name') }"
                     class="form-control"
                     required
                     type="text"
                     name="first_name"
                     placeholder="First"
                     autocomplete="on"
              >
              <has-error :form="form" field="first_name"/>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="last_name">Last Name*
            </label>
            <div class="col-md-7">
              <input id="last_name"
                     v-model="form.last_name"
                     :class="{ 'is-invalid': form.errors.has('last_name') }"
                     required
                     class="form-control"
                     type="text"
                     name="last_name"
                     placeholder="Last"
                     autocomplete="on"
              >
              <has-error :form="form" field="last_name"/>
            </div>
          </div>
          <div v-if="isStudent" class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="student_id">Student ID*
            </label>
            <div class="col-md-7">
              <input id="student_id" v-model="form.student_id"
                     :class="{ 'is-invalid': form.errors.has('student_id') }"
                     required
                     class="form-control"
                     type="text"
                     name="student_id"
                     autocomplete="on"
              >
              <has-error :form="form" field="student_id"/>
            </div>
          </div>
          <!-- Email -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="email">Email*
            </label>
            <div class="col-md-7">
              <input id="email" v-model="form.email"
                     :class="{ 'is-invalid': form.errors.has('email') }"
                     class="form-control"
                     required
                     type="email"
                     name="email"
                     autocomplete="on"
              >
              <has-error :form="form" field="email"/>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="password">Password*
            </label>
            <div class="col-md-7">
              <input id="password" v-model="form.password"
                     :class="{ 'is-invalid': form.errors.has('password') }"
                     class="form-control"
                     required
                     type="password"
                     name="password"
                     autocomplete="on"
              >
              <has-error :form="form" field="password"/>
            </div>
          </div>

          <!-- Password Confirmation -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="password_confirmation">Confirm Password*
            </label>
            <div class="col-md-7">
              <input id="password_confirmation"
                     v-model="form.password_confirmation"
                     :class="{ 'is-invalid': form.errors.has('password_confirmation') }"
                     class="form-control"
                     required
                     type="password"
                     name="password_confirmation"
                     autocomplete="on"
              >
              <has-error :form="form" field="password_confirmation"/>
            </div>
          </div>

          <!-- Access Code -->
          <div v-if="isTester || isInstructor || isGrader || isQuestionEditor" class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="access_code">Access Code*
            </label>
            <div class="col-md-7">
              <input id="access_code" v-model="form.access_code"
                     :class="{ 'is-invalid': form.errors.has('access_code') }"
                     required
                     aria-describedby="access-code-help-block"
                     class="form-control"
                     type="text"
                     name="access_code"
              >
              <has-error :form="form" field="access_code"/>
              <b-form-text id="access-code-help-block">
                <span v-if="isGrader">Please contact your instructor for an access code.</span>
              </b-form-text>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="time_zone">Time zone*
            </label>
            <div class="col-md-7">
              <b-form-select id="time_zone"
                             v-model="form.time_zone"
                             title="time zone"
                             :options="timeZones"
                             :class="{ 'is-invalid': form.errors.has('time_zone') }"
                             required
                             @change="form.errors.clear('time_zone')"
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
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import LoginWithLibretexts from '~/components/LoginWithLibretexts'
import { redirectOnLogin } from '~/helpers/LoginRedirect'
import { getTimeZones } from '~/helpers/TimeZones'
import AllFormErrors from '~/components/AllFormErrors'
import Email from '~/components/Email'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'

export default {
  middleware: 'guest',

  components: {
    LoginWithLibretexts,
    AllFormErrors,
    Email
  },

  metaInfo () {
    return { title: this.registrationTitle }
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
      time_zone: ''
    }),
    timeZones: [
      { value: '', text: 'Please select a time zone' }
    ],
    mustVerifyEmail: false,
    isGrader: false,
    isInstructor: false,
    isStudent: false,
    isQuestionEditor: false,
    isTester: false,
    registrationTitle: ''
  }),
  watch: {
    '$route' (to) {
      this.setRegistrationType(to.path)
    }
  },
  async mounted () {
    this.setRegistrationType(this.$route.path)
    this.timeZones = await getTimeZones()
    if (this.$route.params.accessCode) {
      this.form.access_code = this.$route.params.accessCode
    }
  },

  methods: {
    openSendEmailModal () {
      if (this.isTester) {
        this.$refs.request_tester_access_code_email.openSendEmailModal()
      } else if (this.isInstructor) {
        this.$refs.request_instructor_access_code_email.openSendEmailModal()
      } else {
        alert('Not a valid access code type request.')
      }
    },
    getRegistrationType (path) {
      if (path.includes('student')) {
        return 'student'
      }
      if (path.includes('instructor')) {
        return 'instructor'
      }
      if (path.includes('grader')) {
        return 'grader'
      }
      if (path.includes('question-editor')) {
        return 'question editor'
      }
      if (path.includes('tester')) {
        return 'tester'
      }
      alert('Not a valid registration type.')
      return false
    },
    setRegistrationType (path) {
      this.form.registration_type = this.getRegistrationType(path)
      switch (this.form.registration_type) {
        case 'instructor':
          this.registrationTitle = 'Instructor Registration'
          this.isInstructor = true
          this.isTester = this.isQuestionEditor = this.isStudent = this.isGrader = false
          break
        case 'student':
          this.registrationTitle = 'Student Registration'
          this.isStudent = true
          this.isTester = this.isQuestionEditor = this.isInstructor = this.isGrader = false
          break
        case 'grader':
          this.registrationTitle = 'Grader Registration'
          this.isGrader = true
          this.isTester = this.isQuestionEditor = this.isStudent = this.isInstructor = false
          break
        case 'question editor':
          this.registrationTitle = 'Non-Instructor Editor Registration'
          this.isQuestionEditor = true
          this.isTester = this.isGrader = this.isStudent = this.isInstructor = false
        case 'tester':
          this.registrationTitle = 'Tester Registration'
          this.isTester = true
          this.isQuestionEditor = this.isGrader = this.isStudent = this.isInstructor = false
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
          await this.$store.dispatch('auth/saveToken', { token })

          // Update the user.
          await this.$store.dispatch('auth/updateUser', { user: data })

          // Redirect to the correct home page
          redirectOnLogin(this.$store, this.$router)
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          fixInvalid()
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-register-form')
        }
      }
    }
  }
}
</script>
