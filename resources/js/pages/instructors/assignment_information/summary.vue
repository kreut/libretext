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
        <PageTitle title="Assignment Summary"/>
        <b-modal
          id="modal-assign-tos-to-view"
          ref="modal"
          title="Assigned To"
          size="lg"
        >
          <AssignTosToView ref="assignTosModal" :assign-tos-to-view="assignTosToView"/>
        </b-modal>
        <b-container>
          <b-row align-h="end" class="pb-2">
            <b-button size="sm" variant="primary" @click="getPropertiesView">
              <b-icon icon="gear"/>
              Edit Assignment
            </b-button>
          </b-row>
          <b-card :header="assignment.name" class="h-100">
            <b-card-text>
              <p v-if="!lms">
                <span class="font-weight-bold">Instructions: </span>
                <span v-html="getInstructions(assignment)"/>
              </p>
              <p>
                <span class="font-weight-bold">Late Policy: </span>
                <span class="font-italic"
                >{{ assignment.formatted_late_policy ? assignment.formatted_late_policy : 'None.' }}</span>
              </p>
            </b-card-text>
          </b-card>
          <b-table
            striped
            hover
            :no-border-collapse="true"
            :items="items"
          >
            <template v-slot:cell(value)="data">
              <span v-if="data.item.property ==='Assigned To'">
                <span v-if="assignment.assign_tos.length ===1">{{ assignment.assign_tos[0].groups.toString() }}</span>
                <b-button v-if="assignment.assign_tos.length > 1" variant="primary" size="sm" @click="viewAssignTos">View Assigned To</b-button>
              </span>
              <span v-if="data.item.property !=='Assigned To'">
                {{ data.item.value }}
              </span>
              <span v-if="data.item.property ==='Libretexts URL'">
                <span v-if="assignment.libretexts_url">
                  <a :href="assignment.libretexts_url" target="_blank">{{
                      assignment.libretexts_url.slice(0, 40)
                    }}...</a>
                </span>
                  <span v-if="!assignment.libretexts_url">
                    N/A. This LMS assignment is served through Adapt.
                    </span>
              </span>
            </template>
          </b-table>
        </b-container>
      </div>
    </div>
  </div>
</template>

<script>
import _ from 'lodash'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import AssignTosToView from '~/components/AssignTosToView'

export default {
  middleware: 'auth',
  components: {
    Loading,
    AssignTosToView
  },
  data: () => ({
    lms: false,
    course: {},
    assignTosToView: [],
    assignmentId: 0,
    courseId: 0,
    isLoading: true,
    assignment: {},
    items: []
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

    getPropertiesView () {
      this.$router.push(`/instructors/assignments/${this.assignmentId}/information/properties`)
    },
    getInstructions (assignment) {
      return assignment.instructions ? assignment.instructions : 'None provided.'
    },
    viewAssignTos () {
      this.assignTosToView = this.assignment.assign_tos
      this.$bvModal.show('modal-assign-tos-to-view')
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
        this.courseEndDate = this.assignment.course_end_date
        this.courseStartDate = this.assignment.course_start_date
        this.lms = this.assignment.lms
        this.items = [{
          property: 'Assigned To',
          value: ''
        }]
        if (this.assignment.assign_tos.length === 1) {
          this.items.push(
            {
              property: 'Available On',
              value: this.$moment(this.assignment.assign_tos[0].available_from_date + this.assignment.assign_tos[0].available_from_time, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A')
            })
          this.items.push({
            property: 'Due',
            value: this.$moment(this.assignment.assign_tos[0].due_date + this.assignment.assign_tos[0].due_time, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A')
          })
          if (this.assignment.late_policy !== 'not accepted') {
            this.items.push({
              property: 'Final Submission Deadline',
              value: this.$moment(this.assignment.assign_tos[0].final_submission_deadline, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A')
            })
          }
        }

        this.items.push(
          { property: 'Assessment Type', value: _.startCase(this.assignment.assessment_type) },
          {
            property: 'Students Can View Assignment Statistics',
            value: this.students_can_view_assignment_statistics ? 'Yes' : 'No'
          },
          { property: 'Solutions Released', value: this.assignment.solutions_released ? 'Yes' : 'No' },
          { property: 'Scores Released', value: this.assignment.show_scores ? 'Yes' : 'No' },
          {
            property: 'Include In Final Weighted Average',
            value: this.assignment.include_in_weighted_average ? 'Yes' : 'No'
          },
          { property: 'Total Points', value: this.assignment.total_points },
          { property: 'Number of Questions', value: this.assignment.number_of_questions },
          {
            property: 'Number of Randomized Questions Chosen',
            value: this.assignment.number_of_randomized_questions_chosen
          }
        )
        if (this.assignment.assessment_type === 'clicker') {
          this.items.splice(2, 0, {
            property: 'Default Clicker Time To Submit',
            value: this.assignment.default_clicker_time_to_submit
          })
        }
        if (this.lms) {
          this.items.push({
            property: 'Libretexts URL',
            value: ''
          })
        }
        console.log(this.items)
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
