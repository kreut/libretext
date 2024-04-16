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
    <b-modal id="modal-lti-linked-assignments"
             title="Linked Assignments"
             size="lg"
             @hidden="getCoursesAndAssignmentsByUser"
    >
      <div v-show="ltiLinkedAssignments.length">
        <p>Below you can find your currently linked assignments for {{ activeCourse.text }}.</p>
        <p>
          If you've accidentally linked an ADAPT assignment to the wrong assignment in your LMS, you can always
          unlink it here and then relink it to the correct one.
        </p>
        <b-table
          aria-label="'Linked assignments"
          striped
          hover
          :no-border-collapse="true"
          :fields="ltiLinkedAssignmentfields"
          :items="ltiLinkedAssignments"
        >
          <template v-slot:cell(actions)="data">
            <b-button size="sm" variant="danger" @click="unlinkLtiAssignment(data.item.value)">
              Unlink Assignment
            </b-button>
          </template>
        </b-table>
      </div>
      <div v-show="!ltiLinkedAssignments.length">
        <b-alert show variant="info">
          You have no assignments in this course that are currently linked to your LMS.
        </b-alert>
      </div>
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-lti-linked-assignments')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
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
        scores automatically passed back from ADAPT to your LMS. If your LMS provides an option for the number of
        submissions, please choose "unlimited" within the LMS. Failure
        to do so may mean that some scores will not be passed back. You can control the number of student submissions
        allowed
        using ADAPT's Assignment Properties.
      </p>
      <b-form-group
        id="course"
        label-cols-sm="3"
        label-cols-lg="2"
        label="Course"
        label-for="course"
      >
        <b-form-row>
          <b-col lg="10">
            <b-form-select id="course"
                           v-model="courseId"
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
        label-for="assignment"
      >
        <b-form-row>
          <b-col lg="10">
            <b-form-select v-if="assignmentId !== 0"
                           id="assignment"
                           v-model="assignmentId"
                           :options="courseAssignments"
            />
            <div class="pt-2">
              <span v-if="assignmentId === 0" class="font-weight-bold">This course has no available assignments to link.</span>
            </div>
          </b-col>
        </b-form-row>
        <a v-show="ltiLinkedAssignments.length"
           href="#"
           class="small ml-auto"
           @click.prevent="viewLTILinkedAssignments()"
        >View Linked Assignments</a>
      </b-form-group>
      <b-alert :show="courseHasApiKey" variant="info">

        This course is integrated with your LMS via its API.  Instead of manually linking assignments, please just create them in ADAPT
        and they will automatically be sent back to your LMS.
      </b-alert>
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
  metaInfo () {
    return { title: 'Link Assignment To LMS' }
  },
  data: () => ({
    ltiLinkedAssignmentfields: [
      {
        key: 'text',
        label: 'name'
      },
      'lms_resource_link_id',
      'actions'
    ],
    activeCourse: { name: '' },
    courseHasApiKey: false,
    ltiLinkedAssignments: [],
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
    if (!localStorage.launchInNewWindow) {
      // they haven't been logged in yet.  Using the window session
      let success = await this.getLTIUser()
      if (success) {
        await this.getCoursesAndAssignmentsByUser()
      }
    } else {
      await this.getCoursesAndAssignmentsByUser()
    }
  },
  methods: {
    async unlinkLtiAssignment (assignmentId) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignmentId}/unlink-lti`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.initCourseAssignments()
          this.ltiLinkedAssignments = this.ltiLinkedAssignments.filter(assignment => assignment.value !== assignmentId)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    viewLTILinkedAssignments () {
      try {
        this.activeCourse = this.courses.find(item => item.value === this.courseId)
        this.$bvModal.show('modal-lti-linked-assignments')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initCourseAssignments () {
      this.courseHasApiKey = this.courses.find(course => course.value === this.courseId).has_api_key
      this.courseAssignments = this.assignments[this.courseId].filter(assignment => assignment.lms_resource_link_id === null)
      this.ltiLinkedAssignments = this.assignments[this.courseId].filter(assignment => assignment.lms_resource_link_id !== null)
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
          this.courseHasApiKey = this.courses[0]['has_api_key']
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
