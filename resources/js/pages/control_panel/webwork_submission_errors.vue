<template>
  <div>
    <PageTitle title="Webwork Submission Errors"/>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-button variant="primary"
                @click="downloadWebworkSubmissionErrors"
                size="sm">
        Download Webwork Submission Errors
      </b-button>
      <a id="download-webwork-submission-errors" href="/api/webwork/submission-errors" v-show="false">Download Webwork Submission Errors</a>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'

export default {
  name: 'LearningTreeAnalytics',
  components: {
    Loading
  },
  metaInfo () {
    return { title: this.$t('Webwork Submission Errors') }
  },
  data: () => ({
    hasAccess: false,
    isLoading: true
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
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.isLoading = false
  },
  methods: {
    downloadWebworkSubmissionErrors () {
      document.getElementById('download-webwork-submission-errors').click()
    }
  }
}
</script>
