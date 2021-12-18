<template>
  <div>
    <PageTitle title="Sitemap"/>
    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>General Information</h2>" class="mb-3">
      <b-card-text>
        <ul>
          <li>
            <router-link :to="{name: 'settings.profile'}">
              Settings - Profile
            </router-link>
          </li>
          <li>
            <router-link :to="{name: 'settings.password'}">
              Settings - Password
            </router-link>
          </li>
          <li>
            <router-link :to="{name: 'settings.notifications'}">
              Settings - Notifications
            </router-link>
          </li>
        </ul>
      </b-card-text>
    </b-card>
    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Courses And Assignments</h2>">
      <b-card-text>
        You can directly view all of <router-link :to="{name: 'students.courses.index'}">
        your courses
      </router-link>
        or drill down to the course-assignment details below.
        <ol>
          <li v-for="enrolledInCoursesAndAssignment in enrolledInCoursesAndAssignments"
              :key="`enrolled-in-course-and-assignment-${enrolledInCoursesAndAssignment.course.id}`"
          >
            {{ enrolledInCoursesAndAssignment.course.course_section_name }}
            <ul>
              <li v-for="assignment in enrolledInCoursesAndAssignment.assignments"
                  :key="`assignment-${assignment.assignment_id}`"
              >
                {{ assignment.name }} <span v-if="enrolledInCoursesAndAssignment.course.is_lms">Please enter through your LMS.</span>
                <span v-if="!enrolledInCoursesAndAssignment.course.is_lms">
                   <router-link :to="{name: 'students.assignments.summary', params: {assignmentId: assignment.assignment_id}}">
                     Summary </router-link>
                  -  <router-link :to="{name: 'questions.view', params: {assignmentId: assignment.assignment_id}}">
                  Questions </router-link>
                </span>
              </li>
            </ul>
          </li>
        </ol>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  metaInfo () {
    return { title: 'Sitemap' }
  },
  data: () => ({
    enrolledInCoursesAndAssignments: []
  }),
  mounted () {
    this.getEnrolledInCoursesAndAssignments()
  },
  methods: {
    async getEnrolledInCoursesAndAssignments () {
      const { data } = await axios.get('/api/courses/enrolled-in-courses-and-assignments')
      if (data.type === 'error') {
        this.$noty.error(data.message)
        return false
      }
      this.enrolledInCoursesAndAssignments = data.enrolled_in_courses_and_assignments
    }
  }
}
</script>

<style scoped>

</style>
