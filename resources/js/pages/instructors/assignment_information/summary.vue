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
        <PageTitle title="Assignment Summary" />

        <AssignmentProperties ref="assignmentProperties" :course-id="Number(courseId)" />
        <b-container>
          <b-row align-h="end" class="pb-2">
            <b-button size="sm" variant="primary" @click="initEditAssignment()">
              <b-icon icon="gear" /> Edit Assignment
            </b-button>
          </b-row>

          <b-card :header="assignment.name" class="h-100">
            <b-card-text>
              <span class="font-weight-bold">Instructions: </span><span class="font-italic">{{ assignment.instructions ? assignment.instructions : 'None provided.' }}</span><br>
              <span class="font-weight-bold">Late Policy: </span><span class="font-italic">{{ assignment.formatted_late_policy }}</span><br>
            </b-card-text>
          </b-card>
          <b-table
            striped
            hover
            :no-border-collapse="true"
            :items="items"
          />
        </b-container>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import AssignmentProperties from '~/components/AssignmentProperties'

export default {
  middleware: 'auth',
  components: {
    Loading,
    AssignmentProperties
  },
  data: () => ({
    assignmentId: 0,
    courseId: 0,
    isLoading: true,
    assignment: {},
    items: [
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.assignmentId = this.$route.params.assignmentId
  },
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment summary page.')
      return false
    }

    this.getAssignmentSummary()
  },
  methods: {
    initEditAssignment () {
      this.$refs.assignmentProperties.editAssignment(this.assignment)
    },
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        if (data.type === 'error') {
          this.isLoading = false
          return false
        }
        this.assignment = data.assignment
        this.courseId = this.assignment.course_id
        this.items = [
          { property: 'Available On', value: this.$moment(this.assignment.available_on, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') },
          { property: 'Due', value: this.$moment(this.assignment.due, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') },
          { property: 'Assessment Type', value: this.assignment.assessment_type },
          { property: 'Students Can View Assignment Statistics', value: this.students_can_view_assignment_statistics ? 'Yes' : 'No' },
          { property: 'Solutions Released', value: this.assignment.solutions_released ? 'Yes' : 'No' },
          { property: 'Scores Released', value: this.assignment.show_scores ? 'Yes' : 'No' },
          { property: 'Include In Final Weighted Average', value: this.assignment.include_in_weighted_average ? 'Yes' : 'No' },
          { property: 'Total Points', value: this.assignment.total_points },
          { property: 'Number Of Questions', value: this.assignment.number_of_questions }
        ]
        if (this.assignment.assessment_type === 'clicker') {
          this.items.splice(2, 0, { property: 'Default Clicker Time To Submit', value: this.assignment.default_clicker_time_to_submit })
        }
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
