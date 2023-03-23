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
        <PageTitle title="Assignment Gradebook"/>
        <a
           class="float-right mb-2 btn-sm btn-primary link-outline-primary-btn"
           :href="`/api/scores/assignment/get-assignment-questions-scores-by-user/${assignmentId}/0/1`"
        >
          Download Scores
        </a>
        <TimeSpent @updateView="getAssignmentQuestionScoresByUser"/>
        <b-table
          v-show="items.length"
          aria-label="Assignment Gradebook"
          striped
          hover
          responsive
          sticky-header="800px"
          :no-border-collapse="true"
          :fields="fields"
          :items="items"
        />
        <b-alert :show="!items.length">
          <span class="font-weight-bold">
            This course has no enrolled users so there is no gradebook to show.
          </span>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import TimeSpent from '~/components/TimeSpent'
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  components: {
    Loading,
    TimeSpent
  },
  metaInfo () {
    return { title: 'Assignment Gradebook' }
  },
  data: () => ({
    assignmentId: 0,
    fields: [],
    items: [],
    isLoading: true,
    showTimeSpent: false,
    toggleColors: window.config.toggleColors
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentQuestionScoresByUser(0)
  },
  methods: {
    async getAssignmentQuestionScoresByUser (timeSpent) {
      try {
        const { data } = await axios.get(`/api/scores/assignment/get-assignment-questions-scores-by-user/${this.assignmentId}/${timeSpent}/0`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.rows
        this.fields = data.fields
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
