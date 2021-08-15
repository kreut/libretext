<template>
  <div>
    <b-alert :show="errorMessage !==''" variant="danger">
      <span class="font-weight-bold">{{ errorMessage }}</span>
    </b-alert>
    <b-alert :show="showNoAssignments" variant="info">
      <span class="font-weight-bold">You have no assignments that you can link to your LMS.</span>
    </b-alert>
    <b-modal
      id="link-assignment"
      ref="modal"
      title="Link Assignment"
      size="lg"
      @ok="linkAssignmentToLMS"
    >
      <p>
        Once your assignment is linked, your students will be able to complete the assignment within your LMS with
        scores automatically passed back from Adapt to your LMS.
      </p>
      <b-form-group
        id="course"
        label-cols-sm="4"
        label-cols-lg="3"
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
        label-cols-sm="4"
        label-cols-lg="3"
        label="Assignment"
        label-for="Assignment "
      >
        <b-form-row>
          <b-col lg="10">
            <b-form-select v-model="assignmentId"
                           :options="courseAssignments"
            />
          </b-col>
        </b-form-row>
      </b-form-group>
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
    showNoAssignments: false
  }),
  created () {
    this.getLTIUser = getLTIUser
  },
  async mounted () {
    this.resourceLinkId = this.$route.params.resourceLinkId
    let success = await this.getLTIUser()
    if (success) {
      await this.getCoursesAndAssignmentsByUser()
    }
  },
  methods: {
    initCourseAssignments () {
      this.courseAssignments = this.assignments[this.courseId]
      this.assignmentId = this.courseAssignments[0]['value']
    },
    async getCoursesAndAssignmentsByUser () {
      try {
        const { data } = await axios.get('/api/courses/assignments')
        if (data.type === 'success') {
          this.courses = data.courses
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
          { 'resource_link_id': this.resourceLinkId })
        if (data.type === 'success') {
          await this.$router.push({
            name: 'questions.view',
            params: { assignmentId: data.assignment_id }
          })
        } else {
          this.errorMessage = data.message
        }
      } catch (error) {
        this.errorMessage = error.message
      }
    }
  }
}
</script>
