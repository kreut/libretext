<template>
  <div>
    <b-alert :show="errorMessage !==''" variant="danger">
      <span class="font-weight-bold">{{ errorMessage }}</span>
    </b-alert>
    <b-alert :show="showNoAssignments" variant="info">
      <span class="font-weight-bold">
        You can link assignments from courses where you have indicated that they
        are LMS courses under Course Properties in your ADAPT Account.
        Currently, you have no assignments that you can link to your LMS.</span>
    </b-alert>
    <b-modal
      id="link-assignment"
      ref="modal"
      title="Link Assignment To LMS"
      size="lg"
    >
      <p>
        Below you can find the list of courses which you have enabled as LMS courses in the Course Properties panel
        within ADAPT.
      </p>
      <p>
        Once your assignment is linked, your students will be able to complete the assignment within your LMS with
        scores automatically passed back from ADAPT to your LMS.
      </p>
      <b-form-group
        id="course"
        label-cols-sm="3"
        label-cols-lg="2"
        label="Course"
        label-for="Course"
      >
        <b-form-row>
          <b-col lg="10">
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
          <b-col lg="10">
            <b-form-select v-if="assignmentId !== 0"
                           v-model="assignmentId"
                           :options="courseAssignments"
            />
            <div class="pt-2">
              <span v-if="assignmentId === 0" class="font-weight-bold font-italic">This course has no available assignments to link.</span>
            </div>
          </b-col>
        </b-form-row>
      </b-form-group>

      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="linkAssignmentToLMS"
        >
          Link Assignment
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import { getLTIUser } from '~/helpers/lti'

export default {
  data: () => ({
    errorMessage: '',
    courseId: 0,
    assignmentId: 0,
    courseAssignments: [],
    courses: [],
    assignments: [],
    showNoAssignments: false,
    lmsResourceLinkId: 0
  }),
  created () {
    this.getLTIUser = getLTIUser
  },
  async mounted () {
    this.lmsResourceLinkId = this.$route.params.lmsResourceLinkId
    let success = await this.getLTIUser()
    if (success) {
      await this.getCoursesAndAssignmentsByUser()
    }
  },
  methods: {
    initCourseAssignments () {
      this.courseAssignments = this.assignments[this.courseId].filter(assignment => assignment.lms_resource_link_id === null)
      this.assignmentId = this.courseAssignments.length ? this.courseAssignments[0]['value'] : 0
    },
    async getCoursesAndAssignmentsByUser () {
      try {
        const { data } = await axios.get('/api/courses/assignments')
        if (data.type === 'success') {
          this.courses = data.courses.filter(course => parseInt(course.lms) === 1)
          console.log(this.courses)
          if (!this.courses.length) {
            this.showNoAssignments = true
            return false
          }
          this.courseId = this.courses[0]['value']
          this.assignments = data.assignments
          this.initCourseAssignments()
          this.$bvModal.show('link-assignment')
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async linkAssignmentToLMS () {
      try {
        const { data } = await axios.post(`/api/lti/link-assignment-to-lms/${this.assignmentId}`,
          { 'lms_resource_link_id': this.lmsResourceLinkId })
        if (data.type === 'success') {
          await this.$router.push({
            name: 'questions.view',
            params: { assignmentId: data.assignment_id }
          })
        } else {
          this.$bvModal.hide('link-assignment')
          this.errorMessage = data.message
        }
      } catch (error) {
        this.errorMessage = error.message
      }
    }
  }
}
</script>
