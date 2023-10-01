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
      <div v-if="!isLoading">
        <PageTitle title="Analytics"/>
        <ul>
          <li>
            Total number of formative submissions: {{ totalNumberOfSubmissions.formative }}
          </li>
          <li>
            Total number of summative submissions: {{ totalNumberOfSubmissions.summative }}
          </li>
          <li>
            Total number of submissions: {{ totalNumberOfSubmissions.summative + totalNumberOfSubmissions.formative }}
          </li>
        </ul>
        <div class="pb-2">
          <a
            class="mb-2 btn-sm btn-primary link-outline-primary-btn"
            href="/api/analytics/nursing/1"
          >
            Download
          </a>
        </div>
        <b-table
          aria-label="Analytics"
          striped
          hover
          :no-border-collapse="true"
          :items="analytics"
        />
      </div>
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
    totalNumberOfSubmissions: {},
    analytics: [],
    isLoading: true
  }),
  mounted () {
    this.getNursingAnalytics()
  },
  methods: {
    async getNursingAnalytics () {
      try {
        const { data } = await axios.get('/api/analytics/nursing/0')
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.totalNumberOfSubmissions = { formative: 0, summative: 0 }
        for (let i = 0; i < data.analytics.length; i++) {
          const analytics = data.analytics[i]
          this.analytics.push(analytics)
          this.totalNumberOfSubmissions[analytics.type] += analytics.number_of_submissions
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
