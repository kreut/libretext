<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-alert :show="errorMessage !==''" variant="danger">
        <span class="font-weight-bold">{{ errorMessage }}</span>
      </b-alert>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import axios from 'axios'

export default {
  components: {
    Loading
  },
  data: () => ({
    errorMessage: '',
    assignmentId: 0,
    isLoading: true
  }),
  async mounted () {
    this.assignmentId = this.$route.params.assignmentId
    let ltiToken = this.$route.params.ltiToken
    await this.$store.dispatch('auth/saveToken', {
      token: ltiToken,
      remember: false
    })
    await this.$store.dispatch('auth/fetchUser')
    await this.getAssignmentStartPageInfo(this.assignmentId)
    this.isLoading = false
  },
  methods: {
    async getAssignmentStartPageInfo (assignmentId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/start-page-info`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        data.adapt_launch
          ? await this.$router.push({
            name: 'questions.view',
            params: { assignmentId: this.assignmentId }
          })
          : window.location.href = (data.start_page_url)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }

  }
}
</script>
