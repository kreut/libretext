<template>
  <div class="row pb-5">
    <b-modal id="modal-log-in-as-anonymous-user"
             title="Log In As Anonymous User"
             size="lg"
    >
      <p>
        Anonymous users can view all of the course content and submit responses.
        <span class="font-weight-bold">
          Students should never log in as Anonymous since no responses will be saved.
        </span>
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-log-in-as-anonymous-user')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="loginAsAnonymous"
        >
          Log me in as Anonymous
        </b-button>
      </template>
    </b-modal>
    <div class="col-lg-8 m-auto">
      <form v-if="!inIFrame" @submit.prevent="login" @keydown="form.onKeydown($event)">
        <!-- Email -->

        <div class="text-center mb-2">
          <login-with-libretexts action="Login"/>
        </div>
        <div class="text-center mb-2">
          <span class="font-text-bold">OR</span>
        </div>
        <b-card sub-title="Login with Adapt"
                sub-title-text-variant="dark"
                header-text-variant="white"
        >
          <hr>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('email') }}</label>
            <div class="col-md-7">
              <input v-model="form.email" :class="{ 'is-invalid': form.errors.has('email') }" class="form-control"
                     type="email" name="email"
              >
              <has-error :form="form" field="email"/>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('password') }}</label>
            <div class="col-md-7">
              <input v-model="form.password" :class="{ 'is-invalid': form.errors.has('password') }"
                     class="form-control"
                     type="password" name="password"
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
          <span class="font-italic">To access our open courses, you can <a href="#" @click.prevent="loginAsAnonymous">log in here</a>.</span>
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
        <div v-if="canLogInAsAnonymousUser">
          <hr>
          Since this is one of our open courses. You can optionally <a href=""
                                                                       @click.prevent="openLogInAsAnonymousUserModal"
        >log in</a> as an Anonymous User.
        </div>
      </b-card>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import LoginWithLibretexts from '~/components/LoginWithLibretexts'
import { redirectOnLogin } from '~/helpers/LoginRedirect'
import axios from 'axios'

export default {
  middleware: 'guest',

  components: {
    LoginWithLibretexts
  },

  metaInfo () {
    return { title: this.$t('login') }
  },

  data: () => ({
    form: new Form({
      email: '',
      password: ''
    }),
    remember: false,
    inIFrame: false,
    canLogInAsAnonymousUser: false
  }),
  created () {
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },
  mounted () {
    if (this.inIFrame) {
      this.checkIfcanLogInAsAnonymousUser()
    }
  },
  methods: {
    openLogInAsAnonymousUserModal () {
      this.$bvModal.show('modal-log-in-as-anonymous-user')
    },
    async checkIfcanLogInAsAnonymousUser () {
      try {
        const { data } = await axios.get('/api/courses/anonymous-user/can-log-in')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.canLogInAsAnonymousUser = data.anonymous_users
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    loginAsAnonymous () {
      this.form.email = 'anonymous'
      this.form.password = 'anonymous'
      this.login()
    },
    async login () {
      // Submit the form.
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
    }
  }
}
</script>
