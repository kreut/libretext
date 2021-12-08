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
          <span class="font-text-bold">OR</span>
        </div>
        <b-card sub-title="Login with ADAPT"
                sub-title-text-variant="dark"
                header-text-variant="white"
        >
          <hr>
          <RequiredText/>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="email">{{ $t('email') }}*</label>
            <div class="col-md-7">
              <input id="email"
                     v-model="form.email"
                     required
                     :class="{ 'is-invalid': form.errors.has('email') }"
                     class="form-control"
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
                     :class="{ 'is-invalid': form.errors.has('password') }"
                     type="password"
                     class="form-control"
              >
              <has-error :form="form" field="password"/>
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
          <hr>
        </b-card>
      </form>
      <b-card v-if="inIFrame">
        <div class="m-auto">
          <h5 class="font-italic" style="color:#0060bc">
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
import AllFormErrors from '~/components/AllFormErrors'
import LoginWithLibretexts from '~/components/LoginWithLibretexts'
import { redirectOnLogin } from '~/helpers/LoginRedirect'

export default {
  middleware: 'guest',
  components: {
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
        let landingPage = this.inIFrame && this.form.email === 'anonymous' ? data.landing_page : ''

        // Fetch the user.
        await this.$store.dispatch('auth/fetchUser')
        // Redirect to the correct home page
        redirectOnLogin(this.$store, this.$router, landingPage)
      } catch (error) {
        if (error.message.includes('status code 422')) {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-login')
        }
      }
    }
  }
}
</script>
