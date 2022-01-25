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
        <PageTitle title="Auto-graded Submissions"/>
        <div v-if="items.length">
          <download-excel
            class="float-right mb-2"
            :data="downloadRows"
            :fetch="downloadAutoGradedSubmissions"
            :fields="downloadFields"
            worksheet="My Worksheet"
            type="csv"
            name="auto-graded submissions.csv"
          >
            <b-button variant="primary"
                      size="sm"
            >
              Download Submissions
            </b-button>
          </download-excel>

          <b-table

            aria-label="Auto-graded Submissions"
            striped
            hover
            responsive
            sticky-header="800px"
            :fields="fields"
            :no-border-collapse="true"
            :items="items"
          />
        </div>
        <div v-else>
          <b-alert show>
          <span class="font-weight-bold">
           There are no auto-graded submissions for this assignment.
          </span>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  metaInfo () {
    return { title: 'Assignment Gradebook' }
  },
  data: () => ({
    downloadFields: {},
    downloadRows: [],
    fields: [],
    items: [],
    isLoading: true
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the auto-graded-submissions page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    await this.getAutoGradedSubmissions()
    this.isLoading = false
  },
  methods: {
    async getAutoGradedSubmissions () {
      try {
        const { data } = await axios.get(`/api/auto-graded-submissions/${this.assignmentId}/get-auto-graded-submissions-by-assignment`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.items
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async downloadAutoGradedSubmissions () {
      try {
        const { data } = await axios.get(`/api/auto-graded-submissions/${this.assignmentId}/get-auto-graded-submissions-by-assignment`)
        this.downloadFields = data.download_fields
        this.downloadRows = data.download_rows
        return data.download_rows
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
