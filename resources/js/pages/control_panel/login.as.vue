<template>
  <div>
    <div v-if="hasAccess">
      <PageTitle title="Log In As Another User"/>
      <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :is-full-page="true"
                 :width="128"
                 :height="128"
                 color="#007BFF"
                 background="#FFFFFF"
        />
        <b-form ref="form">
          <p>Use the form below to login as another user:</p>
          <div class="col-7 pb-2">
            <vue-bootstrap-typeahead
              ref="queryTypeahead"
              v-model="form.user"
              :data="users"
              placeholder="Type a name"
              :class="{ 'is-invalid': form.errors.has('user') }"
              @keydown="form.errors.clear('user')"
            />
            <has-error :form="form" field="user"/>
            <span class="float-right">
          <b-button variant="primary" size="sm" class="mt-2" @click="submitLoginAs">
            Submit
          </b-button>
            </span>
          </div>
        </b-form>
      </div>
    </div>
  </div>
</template>
<script>
import Form from 'vform'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import { redirectOnLogin } from '~/helpers/LoginRedirect'
import axios from 'axios'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: {
    VueBootstrapTypeahead, Loading
  },
  data: () => ({
    form: new Form({
      user: ''
    }),
    users: [],
    isLoading: true,
    hasAccess: false
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
      this.$noty.error('You do not have access to the Login As page.')
      return false
    }
    this.getAllUsers()
  },
  methods: {
    async getAllUsers () {
      try {
        const { data } = await axios.get(`/api/user/all`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          this.users = data.users
          this.hasAccess = true
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
      }
    },
    async submitLoginAs () {
      try {
        const { data } = await this.form.post('/api/user/login-as')

        if (data.type === 'success') {
          // Save the token.
          await this.$store.dispatch('auth/saveToken', {
            token: data.token,
            remember: false
          })

          // Fetch the user.
          await this.$store.dispatch('auth/fetchUser')
          // Redirect to the correct home page
          redirectOnLogin(this.$store, this.$router)
        } else {
          this.$noty.error(data.message)// no access
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    }
  }
}
</script>
