<template>
  <div>
    <b-modal id="modal-error-message"
             title="Error"
             hide-footer
    >
      <b-alert variant="danger" show>
        {{ errorMessage }} Please contact support with this message.
      </b-alert>
    </b-modal>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
    </div>
  </div>
</template>

<script>
import { redirectOnLogin } from '../helpers/LoginRedirect'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: { Loading },
  layout: 'blank',
  data: () => ({
    errorMessage: '',
    isLoading: true
  }),
  mounted () {
    this.loginUserByJWT(this.$route.params.token)
  },
  methods: {
    showErrorMessage (message) {
      this.errorMessage = message
      this.$bvModal.show('modal-error-message')
    },
    async loginUserByJWT (token) {
      // Submit the form.
      try {
        const { data } = await axios.get(`/api/oidc/login-by-jwt/${token}`)
        const landingPage = data.landing_page ? data.landing_page : ''
        if (data.type === 'error') {
          this.isLoading = false
          this.showErrorMessage(data.message)
          return
        }
        // Save the token.
        await this.$store.dispatch('auth/saveToken', {
          token: data.token,
          remember: this.remember
        })

        await this.$store.dispatch('auth/fetchUser')
        Object.keys(localStorage).forEach((key) => {
          if (key !== ('appversion') && key !== ('libreOneTester')) {
            delete localStorage[key]
          }
        })
        redirectOnLogin(this.$store, this.$router, landingPage)
      } catch (error) {
        this.isLoading = false
        this.showErrorMessage(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
