<template>
  <div class="row pb-5">
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-login'"/>
    <div class="col-lg-8 m-auto">
      <form v-if="!inIFrame" @submit.prevent="login" @keydown="form.onKeydown($event)">
        <!-- Email -->

        <div class="text-center mb-2">
          <login-with-libretexts action="Login"/>
        </div>
        <div class="text-center mb-2">
          <span class="font-text-bold">or</span>
        </div>
        <b-card header-html="<h1 class=&quot;h7&quot;>Login With ADAPT</h1>">
          <RequiredText/>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="email">{{ $t('email') }}*</label>
            <div class="col-md-7">
              <input id="email"
                     v-model="form.email"
                     required
                     :aria-invalid="form.errors.has('email')"
                     :class="{ 'is-invalid': form.errors.has('email') }"
                     class="form-control"
                     autocomplete="on"
              >
              <has-error :form="form" field="email"/>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="password">{{ $t('password') }}*</label>
            <div class="col-md-7">
              <input id="password"
                     v-model="form.password"
                     required
                     :aria-invalid="form.errors.has('password')"
                     :class="{ 'is-invalid': form.errors.has('password') }"
                     type="password"
                     class="form-control"
                     autocomplete="on"
              >
              <has-error :form="form" field="password"/>
              <ErrorMessage
                v-if="form.errors.has('email') && form.errors.get('email') === 'These credentials do not match our records.'"
                message="These credentials do not match our records."
              />
            </div>
          </div>

          <!-- Remember Me -->
          <div class="form-group row">
            <div class="col-md-3"/>
            <div class="col-md-7 d-flex">
              <checkbox v-model="remember" name="remember">
                {{ $t('remember_me') }}
              </checkbox>

              <router-link :to="{ name: 'password.request' }" class="small ml-auto my-auto">
                {{ $t('forgot_password') }}
              </router-link>
            </div>
          </div>

          <div class="form-group row">
            <div class="col-md-7 offset-md-8 d-flex">
              <!-- Submit Button -->
              <v-button :loading="form.busy" class="primary">
                Submit
              </v-button>
            </div>
          </div>
        </b-card>
      </form>
      <b-card v-if="inIFrame">
        <div class="m-auto">
          <h5 style="color:#0060bc">
            You are not logged in!
          </h5>
        </div>
        <h6>Our SSO supports Google, Microsoft, and your Libretext login.</h6>
        <div class="form-group row">
          <div class="col-md-7 offset-md-8 d-flex">
            <login-with-libretexts action="Login"/>
          </div>
        </div>
      </b-card>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import AllFormErrors from '~/components/AllFormErrors'
import LoginWithLibretexts from '~/components/LoginWithLibretexts'
import { redirectOnLogin } from '~/helpers/LoginRedirect'
import ErrorMessage from '../../components/ErrorMessage.vue'

export default {
  middleware: 'guest',
  components: {
    ErrorMessage,
    LoginWithLibretexts,
    AllFormErrors
  },

  metaInfo () {
    return { title: 'Login' }
  },

  data: () => ({
    allFormErrors: [],
    form: new Form({
      email: '',
      password: ''
    }),
    remember: false,
    inIFrame: false
  }),
  created () {
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },
  methods: {
    async login () {
      // Submit the form.
      try {
        const { data } = await this.form.post('/api/login')

        // Save the token.
        await this.$store.dispatch('auth/saveToken', {
          token: data.token,
          remember: this.remember
        })

        // mimic the behavior of SSO
        let assignmentOrQuestionLaunch = data.landing_page && (data.landing_page.includes('questions/view') || data.landing_page.includes('students/assignments'))
        let landingPage = (!this.inIFrame && assignmentOrQuestionLaunch) || (this.inIFrame && this.form.email === 'anonymous') ? data.landing_page : ''
        // Fetch the user.
        await this.$store.dispatch('auth/fetchUser')
        // Redirect to the correct home page
        Object.keys(localStorage).forEach((key) => {
          if (key !== ('appversion')) {
            delete localStorage[key]
          }
        })
        redirectOnLogin(this.$store, this.$router, landingPage)
      } catch (error) {
        if (error.message.includes('status code 422')) {
          fixInvalid()
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-login')
        }
      }
    }
  }
}
</script>
