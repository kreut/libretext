<template>
  <div>
    <PageTitle title="Metrics: Updated Every 24 Hours"/>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && Object.keys(metrics).length">
        <div class="mb-2">
          <span class="h4 text-info">Metrics</span>
          <a
            class="float-right mb-2 btn-sm btn-primary link-outline-primary-btn"
            href="/api/metrics/1"
          >
            Download
          </a>
        </div>
        <table class="table table-striped">
          <thead>
          <tr>
            <th scope="col">
              Metric
            </th>
            <th scope="col">
              Number
            </th>
          </tr>
          </thead>
          <tr v-for="(key,index) in Object.keys(metrics)" :key="`metric-${index}`">
            <td>
              {{ formatKey(key) }} <span v-if="key === 'real_courses'">
                <QuestionCircleTooltip :id="'real-courses-tooltip'"/>
                <b-tooltip target="real-courses-tooltip"
                           delay="500"
                           triggers="hover focus"
                >
                  At least 3 submissions have been made in the course.
                </b-tooltip>
              </span>
              <span v-if="key === 'live_courses'">
                <QuestionCircleTooltip :id="'live-courses-tooltip'"/>
                <b-tooltip target="live-courses-tooltip"
                           delay="500"
                           triggers="hover focus"
                >
                  At least one student currently enrolled.
                </b-tooltip>
              </span>
            </td>
            <td>{{ metrics[key] ? metrics[key].toLocaleString() : 0 }}</td>
          </tr>
        </table>
      </div>
      <div v-if="!isLoading && Object.keys(metrics).length">
        <div class="mb-2">
          <span class="h4 text-info">Cell Data</span>
          <a
            class="float-right mb-2 btn-sm btn-primary link-outline-primary-btn"
            href="/api/metrics/cell-data/1"
          >
            Download
          </a>
          <p>The following courses have at least 3 student submissions.</p>
        </div>
        <b-table
          class="table table-striped"
          :items="cellData"
          :fields="cellDataFields"
        >
        </b-table>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
  components: { Loading },
  metaInfo () {
    return { title: this.$t('Metrics') }
  },
  data: () => ({
    cellDataFields: ['course_name', 'discipline', 'school_name', 'term', 'instructor_name', 'number_of_enrolled_students'],
    cellData: [],
    metrics: [],
    isLoading: true
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isAdmin: () => window.config.isAdmin
  },
  async mounted () {
    this.hasAccess = this.isAdmin && (this.user !== null)
    if (!this.hasAccess) {
      await this.$router.push({ name: 'no.access' })
      return false
    }
    await this.getMetrics(0)
    await this.getCellData(0)
    this.isLoading = false
  },
  methods: {
    formatKey (key) {
      if (key === 'grade_passbacks') {
        return 'Assignment Grades Passed Back'
      }
      if (key === 'live_lms_courses') {
        return 'Live LMS courses'
      }
      if (key === 'live_non_lms_courses') {
        return 'Live Non-LMS courses'
      }
      const words = key.split('_')
      for (let i = 0; i < words.length; i++) {
        words[i] = words[i][0].toUpperCase() + words[i].substring(1)
      }
      return words.join(' ')
    },
    async getMetrics (download = 0) {
      try {
        const { data } = await axios.get(`/api/metrics/${download}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.metrics = data.metrics
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCellData (download = 0) {
      try {
        const { data } = await axios.get(`/api/metrics/cell-data/${download}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.cellData = data.cell_data
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
