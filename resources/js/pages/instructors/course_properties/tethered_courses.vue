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
        <b-card header="default" header-html="Alpha Course Import Code" class="mb-5">
          <b-card-text>
            <p>
              Instructors who create Alpha courses control which assignments and assessments are created in their
              associated Beta Courses. If the Alpha course instructor removes an assignment or assessment, this will
              impact
              the scores of the students in the Beta courses. With great power comes great responsibility!
            </p>
            <p>
              To maintain control over who can tether Beta courses to your Alpha course, please provide potential
              instructors with the following import code:
            </p>
            <p class="text-center">
              <span class="font-weight-bold font-italic pr-2">{{ alphaCourseImportCode }}</span>
              <b-button variant="primary" size="sm" @click="refreshImportCode">
                Refresh
              </b-button>
            </p>
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
        <b-card header="default" header-html="Tethered Beta Courses">
          <b-card-text>
            <p>
              Here you can find a list of all of your tethered Beta courses. If this is an Alpha course,
              then every assignment/assessment that is created/removed will be automatically reflected in the
              tethered Beta courses.
            </p>
            <b-table
              v-if="betaCourses.length"
              striped
              hover
              :no-border-collapse="true"
              :fields="fields"
              :items="betaCourses"
            />
            <div v-if="!betaCourses.length">
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
    betaCourseToUntetherForm: new Form({
      name: ''
    }),
    betaCourse: '',
    tetheredToAlphaCourse: '',
    tetheredToAlphaCourseWithInstructorName: '',
    alphaCourseImportCode: '',
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
    this.getBetaCourses()
    this.getAlphaCourseImportCode()
    this.getTetheredToAlphaCourse()
  },
  methods: {
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
    async refreshImportCode () {
      try {
        const { data } = await axios.post(`/api/alpha-course-import-codes/refresh/${this.courseId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.alphaCourseImportCode = data.import_code
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getAlphaCourseImportCode () {
      try {
        const { data } = await axios.get(`/api/alpha-course-import-codes/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.alphaCourseImportCode = data.import_code
      } catch (error) {
        this.$noty.error(error.message)
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
