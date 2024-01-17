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
import { getLTIUser } from '~/helpers/lti'
import { mapGetters } from 'vuex'

export default {
  components: {
    Loading
  },
  data: () => ({
    errorMessage: '',
    assignmentId: 0,
    isLoading: true,
    url: '',
    assignmentName: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.getLTIUser = getLTIUser
  },
  async mounted () {
    this.assignmentId = this.$route.params.assignmentId
    if (!localStorage.launchInNewWindow) {
      // they haven't been logged in yet.  Using the window session
      let success = await this.getLTIUser()
      if (success) {
        await this.getAssignmentStartPageInfo(this.assignmentId)
      }
    } else {
      await this.getAssignmentStartPageInfo(this.assignmentId)
    }
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
        this.assignmentName = data.name
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
