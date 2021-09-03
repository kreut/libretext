<template>
  <div>
    <Email id="modal-contact-us-for-instructor-account"
           ref="email"
           extra-email-modal-text="To obtain an instructor account, please provide your name, your email address, and an optional message."
           title="Contact Us For Instructor Account"
           type="instructor_account_request"
           subject="Instructor Account Request"
    />
    <b-modal
      id="modal-enter-course"
      title="Enter Course"
    >
      <p>
        To enter this course, you'll need to log in with your instructor account and then visit the Commons from the Dashboard.
        If you don't already have one, then you can contact us for an instructor account.
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$router.push({name: 'login'})"
        >
          Log In
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide('enter-course');$refs.email.openSendEmailModal()"
        >
          Contact Us For Instructor Account
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-import-course-as-beta"
      ref="modalImportCourseAsBeta"
      title="Import As Beta"
    >
      <ImportAsBetaText class="pb-2" />
      <b-form-group
        id="beta"
        label-cols-sm="7"
        label-cols-lg="6"
        label-for="beta"
        label="Import as a Beta Course"
      >
        <b-form-radio-group v-model="courseToImportForm.import_as_beta" class="mt-2">
          <b-form-radio name="beta" value="1">
            Yes
          </b-form-radio>
          <b-form-radio name="beta" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-import-course-as-beta')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleImportCourse"
        >
          Import
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-assignments"
      ref="modalAssignments"
      title="Assignments"
      hide-footer
    >
      <ul>
        <li v-for="assignment in assignments" :key="assignment.id">
          {{ assignment.name }}
        </li>
      </ul>
    </b-modal>
    <PageTitle title="The Commons" />
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-container>
        <b-row>
          <b-card-group v-for="commonsCourse in commonsCourses" :key="commonsCourse.id" class="col-6 pb-5">
            <b-card>
              <template #header>
                <h5 class="mb-0 font-italic">
                  {{ commonsCourse.name }}
                </h5>
              </template>
              <b-card-text>
                <span class="font-weight-bold font-italic">Description: </span>
                {{ commonsCourse.description ? commonsCourse.description : 'None provided' }}
              </b-card-text>

              <b-button variant="primary" size="sm" @click="openAssignmentsModal(commonsCourse.id)">
                View Assignments
              </b-button>
              <b-button variant="success" size="sm"
                        @click="initEnterCommonsCourseAsAnonymousUser(commonsCourse.id)"
              >
                Enter Course
              </b-button>
              <b-button v-if="user && user.role === 2" variant="outline-primary" size="sm"
                        @click="idOfCourseToImport = commonsCourse.id;commonsCourse.alpha ? openImportCourseAsBetaModal() : handleImportCourse()"
              >
                Import Course
              </b-button>
            </b-card>
          </b-card-group>
        </b-row>
      </b-container>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import Form from 'vform'
import Email from '~/components/Email'
import ImportAsBetaText from '~/components/ImportAsBetaText'

export default {
  components: {
    Loading,
    ImportAsBetaText,
    Email
  },
  data: () => ({
    loggingIn: true,
    idOfCourseToImport: 0,
    courseToImportForm: new Form({
      import_as_beta: 0
    }),
    isLoading: true,
    commonsCourses: [],
    assignments: [],
    openCourseId: 0,
    loginForm: new Form({
      username: '',
      password: ''
    }),
    openCourseName: '',
    fields: [
      'name',
      'description'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.getCommonsCourses()
  },
  methods: {
    async initEnterCommonsCourseAsAnonymousUser (courseId) {
      if (this.user && this.user.role === 2) {
        this.isLoading = true
        try {
          const { data } = await axios.post('/api/users/set-anonymous-user-session')
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
          await this.$router.push(`/students/courses/${courseId}/assignments/anonymous-user`)
        } catch (error) {
          this.$noty.error(error.message)
        }
      } else {
        this.$bvModal.show('modal-enter-course')
      }
    },
    openImportCourseAsBetaModal () {
      this.$bvModal.show('modal-import-course-as-beta')
    },
    async handleImportCourse () {
      try {
        const { data } = await this.courseToImportForm.post(`/api/courses/import/${this.idOfCourseToImport}`)
        this.$bvModal.hide('modal-import-course-as-beta')
        this.courseToImportForm.import_as_beta = 0 // reset
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-import-course-as-beta')
      this.courseToImportForm.import_as_beta = 0
    },
    async openAssignmentsModal (courseId) {
      try {
        const { data } = await axios.get(`/api/assignments/commons/${courseId}`)
        if (data.type !== 'success') {
          this.$noty[data.type](data.message)
          return false
        }
        this.assignments = data.assignments
        this.$bvModal.show('modal-assignments')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCommonsCourses () {
      try {
        const { data } = await axios.get(`/api/courses/commons`)
        if (data.type !== 'success') {
          this.isLoading = false
          this.$noty[data.type](data.message)
          return false
        }
        this.commonsCourses = data.commons_courses
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
