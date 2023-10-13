<template>
  <div>
    <Email id="modal-contact-us-for-instructor-account"
           ref="email"
           extra-email-modal-text="To obtain an instructor account, please provide your name, your email address, and an optional message."
           title="Contact Us For Instructor Account"
           type="contact_us"
           subject="Request Instructor Access Code"
    />
    <b-modal
      id="modal-enter-course"
      title="Enter Course"
    >
      <p>
        To enter this course, you'll need to log in with your instructor account and then visit the {{ title }} from the
        Dashboard.
        If you don't already have one, then you can contact us for an instructor account.
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="user ? logout(): $router.push({name: 'login'})"
        >
          Log In
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-enter-course');$refs.email.openSendEmailModal()"
        >
          Contact Us For Instructor Account
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
    <PageTitle :title="title"/>
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
        <b-table
          :aria-label="title"
          striped
          hover
          :no-border-collapse="true"
          :fields="fields"
          :items="openCourses"
        >
          <template v-slot:cell(name)="data">
            <a
              href=""
              @click.prevent="initEnterOpenCourseAsAnonymousUser(data.item.id)"
            >
              {{ data.item.name }}
            </a>
          </template>
          <template v-slot:cell(actions)="data">
            <div class="mb-0">
              <b-tooltip :target="getTooltipTarget('viewAssignments',data.item.id)"
                         triggers="hover"
                         delay="500"
              >
                View assignments for {{ data.item.name }}
              </b-tooltip>
              <a :id="getTooltipTarget('viewAssignments',data.item.id)"
                 href="#"
                 class="pr-1"
                 @click="openAssignmentsModal(data.item.id)"
              >
                <b-icon class="text-muted"
                        icon="eye"
                        :aria-label="`View ${data.item.name} Assignments`"
                />
              </a>
              <ImportCourse v-if="user && user.role === 2"
                            :one-button-per-row="oneButtonPerRow"
                            :open-course="data.item"
                            :icon="true"
              />
            </div>
          </template>
        </b-table>
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
import ImportCourse from '~/components/ImportCourse'
import { logout } from '~/helpers/Logout'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'

export default {
  components: {
    Loading,
    Email,
    ImportCourse
  },
  metaInfo () {
    return { title: 'Commons' }
  },
  data: () => ({
    type: '',
    title: '',
    oneButtonPerRow: false,
    oneCoursePerRow: false,
    loggingIn: true,
    isLoading: true,
    openCourses: [],
    assignments: [],
    openCourseId: 0,
    loginForm: new Form({
      username: '',
      password: ''
    }),
    openCourseName: '',
    fields: []
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.logout = logout
  },
  mounted () {
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.resizeHandler()
    window.addEventListener('resize', this.resizeHandler)
    this.type = this.$route.params.type
    if (!['commons', 'public'].includes(this.type)) {
      this.$noty.error(`The type should be either commons or public: ${this.type} is not a valid type.`)
      return false
    }
    this.title = this.type === 'commons' ? 'Commons' : 'Public Courses'
    this.getOpenCourses()
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.resizeHandler)
  },
  methods: {
    resizeHandler () {
      this.oneCoursePerRow = this.zoomGreaterThan(1.2)
      this.oneButtonPerRow = this.zoomGreaterThan(1)
    },
    async initEnterOpenCourseAsAnonymousUser (courseId) {
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
    async openAssignmentsModal (courseId) {
      try {
        const { data } = await axios.get(`/api/assignments/open/${this.type}/${courseId}`)
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
    async getOpenCourses () {
      try {
        const { data } = await axios.get(`/api/courses/${this.type}`)
        if (data.type !== 'success') {
          this.isLoading = false
          this.$noty[data.type](data.message)
          return false
        }
        this.openCourses = this.type === 'commons' ? data.commons_courses : data.public_courses
        this.fields = [{
          key: 'name',
          isRowHeader: true,
          sortable: true
        }
        ]
        if (this.type === 'commons') {
          this.fields.push('description')
        } else {
          this.fields.push({
            key: 'instructor',
            label: 'Authored By',
            sortable: true
          })
          this.fields.push(
            {
              key: 'school',
              sortable: true
            })
        }
        this.fields.push('actions')
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
