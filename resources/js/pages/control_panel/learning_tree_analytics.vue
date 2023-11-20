<template>
  <div>
    <PageTitle title="Learning Tree Anayltics"/>
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
                @click="downloadAnalytics"
                size="sm">
        Download Analytics
      </b-button>
      <a id="download-analytics" href="/api/learning-tree-analytics" v-show="false">Download Analytics</a>
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
    return { title: this.$t('Instructor Access Codes') }
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
    downloadAnalytics () {
      document.getElementById('download-analytics').click()
    }
  }
}
</script>
