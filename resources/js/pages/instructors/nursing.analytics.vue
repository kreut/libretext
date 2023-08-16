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
        <b-table
          :aria-label="Analytics"
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
    analytics: {},
    isLoading: true
  }),
  mounted () {
    this.getNursingAnalytics()
  },
  methods: {
    convertToTime (time) {
      let message = ''

      let seconds = Math.floor(time) % 60
      let minutes = Math.floor(time / 60)
      let pluralSec = seconds > 1 ? 's' : ''
      if (time > 60) {
        let pluralMin = minutes > 1 ? 's' : ''
        message += `${minutes} minute${pluralMin}, ${seconds} second${pluralSec}`
      } else {
        message += `${seconds} second${pluralSec}`
      }

      return message
    },
    async getNursingAnalytics () {
      try {
        const { data } = await axios.get('/api/analytics/nursing')
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        data.analytics.formative_avg_time_on_task = this.convertToTime(data.analytics.formative_avg_time_on_task)
        data.analytics.summative_avg_time_on_task = this.convertToTime(data.analytics.summative_avg_time_on_task)
        this.analytics = [data.analytics]
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
