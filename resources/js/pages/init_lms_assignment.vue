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
import { getLTIUser } from '~/helpers/lti'

export default {
  components: {
    Loading
  },
  data: () => ({
    errorMessage: '',
    assignmentId: 0,
    isLoading: true
  }),
  created () {
    this.getLTIUser = getLTIUser
  },
  async mounted () {
    this.assignmentId = this.$route.params.assignmentId
    let success = await this.getLTIUser()
    if (success) {
      await this.$router.push({
        name: 'questions.view',
        params: { assignmentId: this.assignmentId }
      })
    }
    this.isLoading = false
  }
}
</script>
