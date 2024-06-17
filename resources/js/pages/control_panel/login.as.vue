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
        <b-container>
          <b-row>
            <autocomplete
              ref="userSearch"
              class="pr-2"
              style="width:650px"
              :search="searchByUser"
              inline
              @submit="selectUser"
            />
            <span class="float-right">
              <b-button variant="primary" class="mt-2" @click="submitLoginAs">
                Submit
              </b-button>
            </span>
          </b-row>
          <ErrorMessage :message="form.errors.get('user')"/>
        </b-container>
      </div>
    </div>
  </div>
</template>
<script>
import Form from 'vform'
import ErrorMessage from '~/components/ErrorMessage'
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
import { redirectOnLogin } from '~/helpers/LoginRedirect'
import axios from 'axios'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: {
    Loading,
    Autocomplete,
    ErrorMessage
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
    this.hasAccess = (this.user !== null) && (this.isMe || this.user.id === 7665)
    if (!this.hasAccess) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.getAllUsers()
  },
  methods: {
    selectUser (selectedUser) {
      if (selectedUser) {
        this.form.user = selectedUser
        this.form.errors.set('user', '')
      }
    },
    searchByUser (input) {
      if (input.includes('https://')) {
        return [input]
      }
      if (input.length < 1) {
        return []
      }
      let matches = this.users.filter(user => user.toLowerCase().includes(input.toLowerCase()))
      let items = []
      if (matches) {
        for (let i = 0; i < matches.length; i++) {
          items.push(matches[i])
        }
        items.sort()
      }
      return items
    },
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
      if (!this.form.user) {
        this.form.errors.set('user', 'You have not selected a user from the dropdown list.')
        return false
      }
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
          if (this.form.user.includes('https://')) {
            window.location.href = this.form.user
          } else {
            redirectOnLogin(this.$store, this.$router)
          }
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
