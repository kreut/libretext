<template>
  <div>
    <b-modal
      id="modal-assignment-properties"
      ref="modal"
      title="Assignment Properties"
      ok-title="Submit"
      size="lg"
      @hidden="resetAssignmentForm(form, assignmentId)"
      @shown="updateModalToggleIndex"
    >
      <AssignmentProperties
        :key="assignmentId"
        :assignment-groups="assignmentGroups"
        :form="form"
        :course-id="parseInt(courseId)"
        :course-start-date="courseStartDate"
        :course-end-date="courseEndDate"
        :assignment-id="assignmentId"
        :is-beta-assignment="isBetaAssignment"
        :lms="false"
      />
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="$bvModal.hide('modal-assignment-properties')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary"
                  @click="handleSubmitAssignmentInfo()"
        >
          Submit
        </b-button>
      </template>

    </b-modal>
    <b-card header="default" header-html="<span class='font-weight-bold'>Import From The Commons</span>">
      <b-card-text>
        <p>Choose an assignment from one of your courses and then you can import assesessments below.</p>
        <b-form-group
          id="course"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Course"
          label-for="Course"
        >
          <b-form-row>
            <b-col lg="8">
              <b-form-select v-model="courseId"
                             :options="courses"
                             @change="initCourseAssignments()"
              />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="assignment"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Assignment"
          label-for="Assignment"
        >
          <b-form-row>
            <b-col lg="8">
              <b-form-select v-if="assignmentId !== 0"
                             v-model="assignmentId"
                             :options="courseAssignments"
              />
              <div class="pt-2">
                <span v-if="assignmentId === 0" class="font-weight-bold font-italic">This course has no available assignments.</span>
              </div>
            </b-col>
          </b-form-row>
        </b-form-group>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'
import {
  initAddAssignment,
  getAssignmentGroups,
  assignmentForm,
  prepareForm,
  resetAssignmentForm,
  updateModalToggleIndex
} from '~/helpers/AssignmentProperties'
import AssignmentProperties from '~/components/AssignmentProperties'

export default {
  name: 'ImportQuestionFromOpenCourse',
  components: { AssignmentProperties },
  props: {
    chosenCommonCourseAssignmentQuestion: {
      type: Object,
      default: function () {
      }
    },
    chosenCommonCourseAssignmentId: { type: Number, default: 0 }
  },
  data: () => ({
    isBetaAssignment: false,
    courses: [],
    courseAssignments: [{ value: null, text: 'Please choose an assignment.' }],
    allFormErrors: [],
    assignmentId: null,
    courseId: 0,
    assignmentGroups: [],
    form: assignmentForm,
    courseStartDate: '',
    courseEndDate: ''
  }),
  watch: {
    assignmentId: function (value) {
      if (value === -1) {
        this.initAddAssignment(this.form, this.courseId, this.assignmentGroups, this.$noty, this.$moment, this.courseStartDate, this.courseEndDate, this.$bvModal, 0)
      }
      this.$parent.updateAssignmentIdToImportTo(this.assignmentId)
    }
  },
  created () {
    this.initAddAssignment = initAddAssignment
    this.getAssignmentGroups = getAssignmentGroups
    this.prepareForm = prepareForm
    this.resetAssignmentForm = resetAssignmentForm
    this.updateModalToggleIndex = updateModalToggleIndex
  },
  mounted () {
    this.getCoursesAndAssignmentsByUser()
  },
  methods: {
    async handleSubmitAssignmentInfo () {
      this.prepareForm(this.form)
      try {
        this.form.course_id = this.courseId
        const { data } = await this.form.post(`/api/assignments`)
        let timeout = data.timeout ? data.timeout : 4000
        this.$noty[data.type](data.message, { timeout: timeout })
        if (data.type === 'success') {
          this.$bvModal.hide('modal-assignment-properties')
          await this.getCoursesAndAssignmentsByUser()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors')
        }
      }
    },
    async getCoursesAndAssignmentsByUser () {
      try {
        const { data } = await axios.get('/api/courses/assignments/non-beta')
        if (data.type === 'success') {
          this.courses = data.courses
          if (!this.courses.length) {
            this.showNoAssignments = true
            return false
          }
          this.courseId = this.courses[0]['value']
          this.courseStartDate = this.courses[0]['start_date']
          this.courseEndDate = this.courses[0]['end_date']
          this.assignmentGroups = await getAssignmentGroups(this.courseId, this.$noty)
          this.assignments = data.assignments
          this.initCourseAssignments()
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initCourseAssignments () {
      this.courseAssignments = this.assignments[this.courseId]
      this.courseAssignments.unshift({ value: null, text: 'Please choose an assignment.' })
      this.courseAssignments.push({ value: -1, text: 'Create new assignment' })
      this.assignmentId = this.courseAssignments.length ? this.courseAssignments[0]['value'] : 0
    }
  }
}

</script>

<style scoped>

</style>
