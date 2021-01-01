<template>
  <div>
    <b-card header="default" header-html="Login As user">
      <b-form ref="form">
        <p>To access this assignment, please provide the course access code given to you by your instructor.</p>
        <div class="col-7 pb-2">
          <vue-bootstrap-typeahead
            ref="queryTypeahead"
            v-model="form.user"
            :data="users"
            placeholder="Type a name"
          />
        </div>
        <b-button variant="primary" @click="submitLoginAs">
          Submit
        </b-button>
      </b-form>
    </b-card>
  </div>
</template>
<script>
import Form from 'vform'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import axios from 'axios'
import { mapGetters } from 'vuex'
import { redirectOnLogin } from '~/helpers/LoginRedirect'

export default {
  components: {
    VueBootstrapTypeahead
  },
  data: () => ({
    form: new Form({
      user: ''
    }),
    users: []
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (this.user === null) {
      this.$router.go(-1)
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
          console.log(this.users)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitLoginAs () {
      try {
        const { data } = await this.form.post('/api/user/login-as')

        if (data.type === 'success') {
          // Save the token.
          this.$store.dispatch('auth/saveToken', {
            token: data.token,
            remember: this.remember
          })

          // Fetch the user.
          await this.$store.dispatch('auth/fetchUser')
          // Redirect to the correct home page
          redirectOnLogin(this.$store, this.$router)
        } else {
          this.$noty.error(data.message)// no access
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
