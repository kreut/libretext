<template>
  <div v-if="showPage">
    <PageTitle title="My Courses"/>
    <RedirectToClickerModal :key="`redirect-to-clicker-modal-${clickerAssignmentId}-${clickerQuestionId}`"
                            :assignment-id="clickerAssignmentId"
                            :question-id="clickerQuestionId"
    />
    <div class="row mb-4 float-right">
      <EnrollInCourse :get-enrolled-in-courses="getEnrolledInCourses"/>
      <b-button v-if="!isAnonymousUser" v-b-modal.modal-enroll-in-course variant="primary" size="sm">
        Enroll In Course
      </b-button>
    </div>
    <div v-if="hasEnrolledInCourses">
      <b-table striped
               hover
               :fields="fields"
               :items="enrolledInCourses"
               aria-label="Courses"
      >
        <template v-slot:cell(course_section_name)="data">
          <div class="mb-0">
            <a href="" @click.prevent="getAssignments(data.item.id)">{{ data.item.course_section_name }}</a>
          </div>
        </template>
        <template v-slot:cell(public_description)="data">
          {{ data.item.public_description ? data.item.public_description : 'None provided' }}
        </template>
        <template v-if="!isAnonymousUser" v-slot:cell(start_date)="data">
          {{ $moment(data.item.start_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-if="!isAnonymousUser" v-slot:cell(end_date)="data">
          {{ $moment(data.item.end_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoEnrolledInCoursesAlert" variant="warning">
          <a href="#" class="alert-link">Either you are currently not enrolled in any courses or
            the only courses for which you are enrolled are currently unpublished.
          </a>
        </b-alert>
      </div>
      <div class="mt-4">
        <b-alert :show="showNoAnonymousUserCoursesAlert" variant="warning">
          <a href="#" class="alert-link">We currently have no courses which have Open Access.
          </a>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import EnrollInCourse from '~/components/EnrollInCourse'
import { mapGetters } from 'vuex'
import { initCentrifuge } from '~/helpers/Centrifuge'
import RedirectToClickerModal from '../../components/RedirectToClickerModal.vue'

export default {
  components: { RedirectToClickerModal, EnrollInCourse },
  middleware: 'auth',
  data: () => ({
    clickerAssignmentId: 0,
    clickerQuestionId: 0,
    isAnonymousUser: false,
    showNoAnonymousUserCoursesAlert: false,
    fields: [],
    enrolledInCourses: [],
    hasEnrolledInCourses: false,
    form: new Form({
      access_code: ''
    }),
    showNoEnrolledInCoursesAlert: false,
    showPage: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  beforeDestroy () {
    try {
      if (this.centrifuge) {
        this.centrifuge.disconnect()
      }
    } catch (error) {
      // won't be a function for all the other ones that haven't been defined on the page
    }
  },
  mounted () {
    if (this.user.is_tester_student) {
      this.$router.push({ name: 'cannot.view.as.testing.student' })
    }
    this.isAnonymousUser = this.user.email === 'anonymous'
    if (this.isAnonymousUser) {
      this.fields = [{
        key: 'course_section_name',
        label: 'Course',
        isRowHeader: true
      },
        {
          key: 'public_description',
          label: 'Course Description'
        }
      ]
      this.getAnonymousUserCourses()
    } else {
      this.fields = [{
        key: 'course_section_name',
        label: 'Course - Section',
        isRowHeader: true
      },
        {
          key: 'public_description',
          label: 'Course Description'
        },
        'instructor',
        'start_date',
        'end_date'
      ]
      this.getEnrolledInCourses()
      this.initClickerAssignmentsForEnrolledAndOpenCourses()
    }
  },
  methods: {
    async initClickerAssignmentsForEnrolledAndOpenCourses () {
      try {
        const { data } = await axios.get('/api/assignments/clicker/enrolled-open-courses')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return
        }
        const clickerAssignments = data.clicker_assignments
        if (clickerAssignments.length) {
          this.centrifuge = await initCentrifuge()
          for (let i = 0; i < clickerAssignments.length; i++) {
            let assignment = clickerAssignments[i]
            let sub = this.centrifuge.newSubscription(`set-current-page-${assignment.id}`)
            sub.on('publication', async (ctx) => {
              console.error(ctx)
              const data = ctx.data
              this.clickerAssignmentId = +assignment.id
              this.clickerQuestionId = +data.question_id
            }).subscribe()
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getAssignments (courseId) {
      const course = this.enrolledInCourses.find(item => item.id === courseId)
      if (course.lms && course.lms_only_entry && !this.user.is_instructor_logged_in_as_student) {
        this.$noty.info('All assignments are served through your LMS such as Canvas, Blackboard, or Brightspace. Please log in to your LMS to access your assignments.')
        return false
      }
      this.isAnonymousUser
        ? this.$router.push(`/students/courses/${courseId}/assignments/anonymous-user`)
        : this.$router.push(`/students/courses/${courseId}/assignments`)
    },
    async getAnonymousUserCourses () {
      try {
        const { data } = await axios.get('/api/courses/anonymous-user')
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.showPage = true
          this.hasEnrolledInCourses = data.enrollments.length > 0
          this.showNoAnonymousUserCoursesAlert = !this.hasEnrolledInCourses
          this.enrolledInCourses = data.enrollments
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getEnrolledInCourses () {
      try {
        const { data } = await axios.get('/api/enrollments')
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.showPage = true
          this.hasEnrolledInCourses = data.enrollments.length > 0
          this.showNoEnrolledInCoursesAlert = !this.hasEnrolledInCourses
          this.enrolledInCourses = data.enrollments
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  },
  metaInfo () {
    return { title: 'My Courses' }
  }
}
</script>
