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
        <PageTitle title="Assignment Properties" />
        <b-card :header="assignment.name" class="h-100">
          <b-card-text>
            <span class="font-weight-bold">Instructions: </span><span class="font-italic">{{ assignment.instructions ? assignment.instructions : 'None provided.' }}</span><br>
            <span class="font-weight-bold">Late Policy: </span><span class="font-italic">{{ assignment.late_policy }}</span><br>
          </b-card-text>
        </b-card>
      </div>
      <b-table
        striped
        hover
        :no-border-collapse="true"
        :items="items"
      />
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
  data: () => ({
    isLoading: true,
    assignment: {},
    items: [
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to the assignment properties page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentSummary()
  },
  methods: {
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        if (data.type === 'error') {
          this.isLoading = false
          return false
        }
        this.assignment = data.assignment
        this.items = [
          { property: 'Available On', value: this.$moment(this.assignment.available_on, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') },
          { property: 'Due', value: this.$moment(this.assignment.due, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') },
          { property: 'Assessment Type', value: this.assignment.assessment_type },
          { property: 'Students Can View Assignment Statistics', value: this.students_can_view_assignment_statistics ? 'Yes' : 'No' },
          { property: 'Solutions Released', value: this.assignment.solutions_released ? 'Yes' : 'No' },
          { property: 'Scores Released', value: this.assignment.scores_released ? 'Yes' : 'No' },
          { property: 'Include In Final Weighted Average', value: this.assignment.include_in_weighted_average ? 'Yes' : 'No' },
          { property: 'Total Points', value: this.assignment.total_points }
        ]
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

<style scoped>

</style>
