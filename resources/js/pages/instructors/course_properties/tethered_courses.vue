<template>
  <div>
    <b-modal
      id="modal-confirm-untether-from-alpha-course"
      ref="modal"
      :title="`Confirm Untether ${betaCourse} From ${tetheredToAlphaCourse}`"
      size="lg"
    >
      <p>
        By untethering <span class="font-italic font-weight-bold">{{ betaCourse }}</span> from
        <span class="font-italic font-weight-bold">
          {{ tetheredToAlphaCourse }}</span>, you will regain complete control over adding/removing assignments and
        assessments.
      </p>
      <p>
        In addition <span class="font-italic font-weight-bold">{{ tetheredToAlphaCourse }}</span> assignments will no
        longer be
        redirected to the associated
        assignments in <span class="font-italic font-weight-bold">{{ betaCourse }}</span>. If your course is part of
        a Libretext book, this will mean that your students will no longer be able to access the assignments from
        the book.
      </p>
      <p>
        However, if you are serving your assignments through the Adapt platform, your students will be unaffected by
        this change.
      </p>
      <b-form-group
        id="beta_course_to_untether"
        label-cols-sm="6"
        label-cols-lg="5"
        label="Confirm by entering the Beta Course name"
        label-for="beta_course_to_untether"
      >
        <b-form-row>
          <b-col lg="7">
            <b-form-input
              id="name"
              v-model="betaCourseToUntetherForm.name"
              :placeholder="betaCourse"
              lg="7"
              type="text"
              :class="{ 'is-invalid': betaCourseToUntetherForm.errors.has('name') }"
              @keydown="betaCourseToUntetherForm.errors.clear('name')"
            />
            <has-error :form="betaCourseToUntetherForm" field="name" />
          </b-col>
        </b-form-row>
      </b-form-group>
      <b-alert :show="true" variant="danger">
        <span class="font-weight-bold">
          Important: This action cannot be undone.
        </span>
      </b-alert>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-untether-from-alpha-course')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleUntetherFromAlphaCourse"
        >
          Yes, untether this course!
        </b-button>
      </template>
    </b-modal>

    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && user.role === 2">
        <b-card header="default" header-html="Tethered Courses" class="mb-5">
          <b-card-text>
            <p>
              Tethered courses are courses that remain in sync. An instructor in the Alpha course will create
              assignments/assessments and these will automatically be reflected in the tethered Beta courses. To create an
              Alpha course, you can use <router-link :to="{ name: 'course_properties.general_info'}">
                this form
              </router-link> and select Alpha course. Beta courses, can be created at the
              time of import when you import a new course.
            </p>
          </b-card-text>
        </b-card>

        <b-card header="default" header-html="Pending Approvals" class="mb-5">
          <b-card-text>
            <div v-if="tetheredToAlphaCourseWithInstructorName.length">
              <b-form-group
                id="notify_of_approvals"
                label-cols-sm="6"
                label-cols-lg="5"
                label-for="notify_of_approvals"
              >
                <template slot="label">
                  Notify when there are pending approvals <span id="pending_approvals_tooltip">
                    <b-icon class="text-muted" icon="question-circle" /></span>
                  <b-tooltip target="pending_approvals_tooltip"
                             delay="250"
                  >
                    Optionally receive an email notification when you need to approve actions made by the Alpha
                    instructor.
                  </b-tooltip>
                </template>
                <b-form-radio-group v-model="betaApprovalNotifications"
                                    class="mt-2"
                                    @change="updateBetaApprovalNotifications()"
                >
                  <b-form-radio name="notify_of_approvals" value="1">
                    Yes
                  </b-form-radio>
                  <b-form-radio name="notify_of_approvals" value="0">
                    No
                  </b-form-radio>
                </b-form-radio-group>
              </b-form-group>
              <b-table
                v-if="pendingBetaCourseApprovals.length"
                striped
                hover
                :no-border-collapse="true"
                :fields="pendingBetaCourseApprovalFields"
                :items="pendingBetaCourseApprovals"
              >
                <template v-slot:cell(name)="data">
                  <router-link
                    :to="{ name: 'instructors.assignments.questions' , params: {assignmentId: data.item.id}}"
                  >
                    {{ data.item.name }}
                  </router-link>
                </template>
              </b-table>
            </div>
            <div v-else>
              <b-alert :show="true">
                <span class="font-weight-bold">
                  For Beta courses, you will will approve additions/removals of assessments from your tethered Alpha course.  This course is not a Beta course
                  so this panel is not applicable.
                </span>
              </b-alert>
            </div>
          </b-card-text>
        </b-card>
        <b-card header="default" header-html="Tethered Alpha Course" class="mb-5">
          <b-card-text>
            <div v-if="tetheredToAlphaCourseWithInstructorName.length">
              <p>This Beta course is currently tethered to:</p>
              <p class="text-center">
                <span class="font-weight-bold font-italic pr-2">{{ tetheredToAlphaCourseWithInstructorName }}</span>
                <b-button variant="primary" size="sm" @click="confirmUntetherFromAlphaCourse">
                  Untether
                </b-button>
              </p>
            </div>
            <div v-else>
              <b-alert :show="true" variant="info">
                <span class="font-weight-bold">This course is not tethered to any Alpha course.</span>
              </b-alert>
            </div>
          </b-card-text>
        </b-card>
        <b-card header="default" header-html="Tethered Beta Courses" class="mb-5">
          <b-card-text>
            <div v-if="betaCourses.length">
              <p>
                Here you can find a list of all of your tethered Beta courses. If this is an Alpha course,
                then every assignment/assessment that is created/removed will be automatically reflected in the
                tethered Beta courses.
              </p>
              <b-table
                striped
                hover
                :no-border-collapse="true"
                :fields="fields"
                :items="betaCourses"
              />
            </div>
            <div v-else>
              <b-alert :show="true" variant="info">
                <span class="font-weight-bold">This course has no tethered Beta courses.</span>
              </b-alert>
            </div>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import Form from 'vform'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: { Loading },
  data: () => ({
    pendingBetaCourseApprovalFields: ['name', 'total_pending'],
    pendingBetaCourseApprovals: [],
    betaApprovalNotifications: 0,
    betaCourseToUntetherForm: new Form({
      name: ''
    }),
    alphaCourse: false,
    betaCourse: '',
    tetheredToAlphaCourse: '',
    tetheredToAlphaCourseWithInstructorName: '',
    isLoading: true,
    courseId: 0,
    betaCourses: [],
    fields: [
      {
        key: 'name',
        label: 'Course Name'
      },
      {
        key: 'user_name',
        label: 'Instructor Name'
      },
      'email'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getBetaApprovalNotifications()
    this.getBetaCourses()
    this.getPendingBetaCourseApprovals()
    this.getTetheredToAlphaCourse()
  },
  methods: {
    async getBetaApprovalNotifications () {
      try {
        const { data } = await axios.get(`/api/courses/beta-approval-notifications/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty[data.type](data.message)
          return false
        }
        this.betaApprovalNotifications = data.beta_approval_notifications
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateBetaApprovalNotifications () {
      try {
        const { data } = await axios.patch(`/api/courses/beta-approval-notifications/${this.courseId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.betaApprovalNotifications = !this.betaApprovalNotifications
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.betaApprovalNotifications = !this.betaApprovalNotifications
      }
    },
    async getPendingBetaCourseApprovals () {
      try {
        const { data } = await axios.get(`/api/beta-course-approvals/course/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty[data.type](data.message)
          return false
        }
        this.pendingBetaCourseApprovals = data.pending_beta_course_approvals
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getTetheredToAlphaCourse () {
      try {
        const { data } = await axios.get(`/api/beta-courses/get-tethered-to-alpha-course/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty[data.type](data.message)
          this.isLoading = false
          return false
        }
        this.tetheredToAlphaCourseWithInstructorName = data.tethered_to_alpha_course_with_instructor_name
        this.tetheredToAlphaCourse = data.tethered_to_alpha_course
        this.betaCourse = data.beta_course
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    confirmUntetherFromAlphaCourse () {
      this.$bvModal.show('modal-confirm-untether-from-alpha-course')
    },
    async handleUntetherFromAlphaCourse () {
      try {
        this.betaCourseToUntetherForm.course_id = this.courseId
        const { data } = await this.betaCourseToUntetherForm.delete(`/api/beta-courses/untether/${this.courseId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.tetheredToAlphaCourseWithInstructorName = ''
        this.tetheredToAlphaCourse = ''
        this.$bvModal.hide('modal-confirm-untether-from-alpha-course')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async getBetaCourses () {
      try {
        const { data } = await axios.get(`/api/beta-courses/get-from-alpha-course/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.betaCourses = data.beta_courses
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
