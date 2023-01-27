<template>
  <div>
    <PageTitle title="Metrics"/>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <p>
        Number of courses will be low since I currently save by course id. Since some people (like yourself) are
        re-using course it looks like
        I'll have to do this using additional identifiers, including the term and crn</p>


      <div v-if="!isLoading && Object.keys(metrics).length">
        <h4 class="text-info">General Metrics</h4>
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
            <td>{{ formatKey(key) }}</td>
            <td>{{ metrics[key].toLocaleString() }}</td>
          </tr>
        </table>
      </div>
      <div v-if="!isLoading && Object.keys(metrics).length">
        <h4 class="text-info">Cell Data</h4>
        <b-table
          class="table table-striped"
          :items="cellData"
        />
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
    cellData: [],
    metrics: [],
    isLoading: true
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  async mounted () {
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      await this.$router.push({ name: 'no.access' })
      return false
    }
    await this.getMetrics()
    await this.getCellData()
    this.isLoading = false
  },
  methods: {
    formatKey (key) {
      const words = key.split('_')
      for (let i = 0; i < words.length; i++) {
        words[i] = words[i][0].toUpperCase() + words[i].substring(1)
      }
      return words.join(' ')
    },
    async getMetrics () {
      try {
        const { data } = await axios.get('/api/metrics')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.metrics = data.metrics
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCellData () {
      try {
        const { data } = await axios.get('/api/metrics/cell-data')
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
