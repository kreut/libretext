<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-assignment-form'"/>
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
        <PageTitle title="Assignment Properties"/>
        <b-card class="mb-4">
          <AssignmentProperties
            :key="assignment.course_start_date"
            :assignment-groups="assignmentGroups"
            :form="form"
            :course-id="parseInt(courseId)"
            :course-start-date="courseStartDate"
            :course-end-date="assignment.course_end_date"
            :assignment-id="parseInt(assignmentId)"
            :is-beta-assignment="assignment.is_beta_assignment"
            :lms="Boolean(lms)"
          />
          <hr>
          <span class="float-right">
            <b-button size="sm" variant="primary" @click="handleSubmitAssignmentInfo">
              Submit
            </b-button>
          </span>
        </b-card>
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
import AllFormErrors from '~/components/AllFormErrors'

import {
  editAssignment,
  getAssignmentGroups,
  prepareForm,
  assignmentForm
} from '~/helpers/AssignmentProperties'
import {
  isLocked,
  getAssignments,
  isLockedMessage,
  initAssignmentGroupOptions,
  updateAssignmentGroupFilter
} from '~/helpers/Assignments'

export default {
  middleware: 'auth',
  components: {
    Loading,
    AssignmentProperties,
    AllFormErrors
  },
  data: () => ({
    lms: false,
    courseStartDate: '',
    assignmentGroups: [],
    allFormErrors: [],
    form: assignmentForm,
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
    this.getAssignments = getAssignments
    this.isLocked = isLocked
    this.isLockedMessage = isLockedMessage
    this.initAssignmentGroupOptions = initAssignmentGroupOptions
    this.updateAssignmentGroupFilter = updateAssignmentGroupFilter
  },
  async mounted () {
    this.editAssignment = editAssignment
    this.getAssignmentGroups = getAssignmentGroups
    this.prepareForm = prepareForm
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment summary page.')
      return false
    }
    const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
    this.courseId = data.assignment.course_id
    this.courseStartDate = data.assignment.course_start_date
    this.lms = data.assignment.lms
    this.assignmentGroups = await getAssignmentGroups(this.courseId, this.$noty)
    await this.getAssignments()

    this.assignment = this.assignments.find(assignment => parseInt(assignment.id) === parseInt(this.assignmentId))
    console.log(this.assignment)
    this.editAssignment(this.assignment)
  },
  methods: {
    async handleSubmitAssignmentInfo () {
      this.prepareForm(this.form)
      try {
        this.form.course_id = this.courseId
        const { data } = await this.form.patch(`/api/assignments/${this.assignmentId}`)
        let timeout = data.timeout ? data.timeout : 4000
        this.$noty[data.type](data.message, { timeout: timeout })
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-assignment-form')
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
