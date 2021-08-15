<template>
  <div>
    <b-modal
      id="modal-import-course"
      ref="modal"
      title="Import Course"
    >
      <vue-bootstrap-typeahead
        ref="queryTypeahead"
        v-model="courseToImport"
        class="mb-2"
        :data="formattedImportableCourses"
        placeholder="Enter a course or instructor name"
        @hit="checkIfAlpha($event)"
      />
      <b-form-group
        v-if="showImportAsBeta"
        id="beta"
        label-cols-sm="7"
        label-cols-lg="6"
        label-for="beta"
      >
        <template slot="label">
          Import as a Beta Course
          <span id="beta_course_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
          <b-tooltip target="beta_course_tooltip"
                     delay="250"
          >
            <ImportAsBetaText/>
          </b-tooltip>
        </template>
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
          @click="$bvModal.hide('modal-import-course')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          :disabled="disableYesImportCourse"
          @click="handleImportCourse"
        >
          Yes, import course!
        </b-button>
      </template>
    </b-modal>
    <PageTitle v-if="canViewCourses" title="My Courses"/>
    <b-container v-if="canViewCourses && user && user.role === 2">
      <b-row align-h="end" class="mb-4">
        <b-button v-b-modal.modal-course-details variant="primary" class="mr-1"
                  size="sm"
        >
          Add Course
        </b-button>
        <b-button variant="outline-primary" size="sm" class="mr-1" @click="initImportCourse">
          Import Course
        </b-button>
        <b-button variant="outline-info" size="sm" class="mr-1" @click="$router.push({name: 'commons'})">
          Visit Commons
        </b-button>
      </b-row>
    </b-container>

    <b-modal
      id="modal-course-details"
      ref="modal"
      title="Course Details"
      @hidden="resetModalForms"
    >
      <CourseForm :form="newCourseForm"/>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-course-details')"
        >
          Cancel
        </b-button>
        <b-button
          size="sm"
          variant="primary"
          class="float-right"
          @click="submitCourseInfo"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-cannot-delete-course-with-at-least-one-tethered-beta-course"
      ref="cannotDeleteCourseWithTetheredBetaCourse"
      title="Cannot Delete Course"
      size="sm"
      hide-footer
    >
      <p>
        This is an Alpha course with at least one Beta course so it cannot be deleted. You can always hide this
        course from your students.
      </p>
    </b-modal>
    <b-modal
      id="modal-delete-course"
      ref="modal"
      title="Confirm Delete Course"
      @hidden="resetModalForms"
    >
      <p>By deleting the course, you will also delete:</p>
      <ol>
        <li>All assignments associated with the course</li>
        <li>All submitted student responses</li>
        <li>All student scores</li>
      </ol>
      <p><strong>Once a course is deleted, it can not be retrieved!</strong></p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-delete-course')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleDeleteCourse"
        >
          Yes, delete course!
        </b-button>
      </template>
    </b-modal>

    <div v-if="user && user.role === 4">
      <div v-if="canViewCourses" class="row mb-4 float-right">
        <b-button v-b-modal.modal-course-grader-access-code variant="primary">
          Add Course
        </b-button>
      </div>
    </div>
    <b-modal
      id="modal-show-course-warning"
      ref="modal"
      ok-title="I understand"
      title="Verify your course start and end dates"
    >
      <p>
        You are about to unhide this course. Please verify the start and end dates of this course as
        being accurate.
      </p>
      <p class="font-weight-bold">
        Students will not be able to enroll in the course outside of the course dates.
      </p>
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitShowCourse();$bvModal.hide('modal-show-course-warning');"
        >
          I understand
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-course-grader-access-code"
      ref="modal"
      ok-title="Submit"
      title="Enroll as Grader"
      @ok="submitAddGraderToCourse"
    >
      <b-form ref="form">
        <p>To become a course grader, please provide the course access code given to you by your instructor.</p>
        <b-form-group
          id="access_code"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Access Code"
          label-for="access_code"
        >
          <b-form-input
            id="access_code"
            v-model="graderForm.access_code"
            type="text"
            :class="{ 'is-invalid': graderForm.errors.has('access_code') }"
            @keydown="graderForm.errors.clear('access_code')"
          />
          <has-error :form="graderForm" field="access_code"/>
        </b-form-group>
      </b-form>
    </b-modal>

    <div v-if="hasCourses">
      <div v-if="user.role === 2 && hasBetaCourses && showBetaCourseDatesWarning">
        <b-alert variant="info" :show="true">
          <p>
            <span class="font-weight-bold">
              You currently have at least one Beta course.  Double check that the course
              dates are accurate as Adapt uses this information to tether the Alpha assignments to your Beta assignments.
            </span>
          </p>
          <b-button size="sm" variant="info" @click="doNotShowBetaCourseDatesWarnings()">
            Don't Show This Again
          </b-button>
        </b-alert>
      </div>
      <b-table striped hover
               :fields="fields"
               :items="courses"
      >
        <template v-slot:head(shown)="data">
          Shown <span v-b-tooltip="showCourseShownTooltip"><b-icon class="text-muted"
                                                                   icon="question-circle"
        /></span>
        </template>
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <span v-show="parseInt(data.item.alpha) === 1"
                  :id="getTooltipTarget('alphaCourse',data.item.id)"
                  class="text-muted"
            >&alpha; </span>
            <b-tooltip :target="getTooltipTarget('alphaCourse',data.item.id)"
                       delay="500"
            >
              This course is an Alpha course. Adding/removing assignments or assessments from this
              course will be directly reflected in the associated Beta courses.
            </b-tooltip>
            <span v-show="parseInt(data.item.is_beta_course) === 1"
                  :id="getTooltipTarget('betaCourse',data.item.id)"
                  class="text-muted"
            >&beta; </span>
            <b-tooltip :target="getTooltipTarget('betaCourse',data.item.id)"
                       delay="500"
            >
              This course is a Beta course. Since it is tethered to an Alpha course, assignments/assessments which are
              added/removed in the Alpha course will be directly reflected in this course.
            </b-tooltip>
            <a href="" @click.prevent="showAssignments(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
        <template v-slot:cell(shown)="data">
          <toggle-button
            :width="57"
            :value="Boolean(data.item.shown)"
            :sync="true"
            :font-size="14"
            :margin="4"
            :color="{checked: '#28a745', unchecked: '#6c757d'}"
            :labels="{checked: 'Yes', unchecked: 'No'}"
            @change="showCourseWarning(data.item)"
          />
        </template>
        <template v-slot:cell(access_code)="data">
          {{ data.item.access_code ? data.item.access_code : 'None' }}
        </template>
        <template v-slot:cell(start_date)="data">
          {{ $moment(data.item.start_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-slot:cell(end_date)="data">
          {{ $moment(data.item.end_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <span class="pr-1" @click="showGradebook(data.item.id)">
              <b-tooltip :target="getTooltipTarget('gradebook',data.item.id)"
                         delay="500"
              >
                Gradebook
              </b-tooltip>
              <b-icon :id="getTooltipTarget('gradebook',data.item.id)" icon="file-spreadsheet"/></span>
            <span v-if="user && user.role === 2">

              <span class="pr-1" @click="getProperties(data.item)">
                <b-tooltip :target="getTooltipTarget('properties',data.item.id)"
                           delay="500"
                >
                  Course Properties
                </b-tooltip>
                <b-icon :id="getTooltipTarget('properties',data.item.id)" icon="gear"/>
              </span>
              <b-tooltip :target="getTooltipTarget('deleteCourse',data.item.id)"
                         delay="500"
              >
                Delete Course
              </b-tooltip>
              <b-icon :id="getTooltipTarget('deleteCourse',data.item.id)" icon="trash"
                      @click="deleteCourse(data.item.id)"
              />
            </span>
          </div>
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoCoursesAlert" variant="warning">
          <a href="#" class="alert-link">You currently have no
            courses.
          </a>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import CourseForm from '~/components/CourseForm'
import Form from 'vform'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import { ToggleButton } from 'vue-js-toggle-button'
import ImportAsBetaText from '~/components/ImportAsBetaText'

export default {
  components: {
    CourseForm,
    ToggleButton,
    VueBootstrapTypeahead,
    ImportAsBetaText
  },
  middleware: 'auth',
  data: () => ({
    showBetaCourseDatesWarning: true,
    hasBetaCourses: false,
    disableYesImportCourse: true,
    importAsBeta: 0,
    showImportAsBeta: false,
    formattedImportableCourses: [],
    importableCourses: [],
    courseToImport: '',
    showCourseShownTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: 'Show or hide a course on the student\'s homepage.  If you are embedding assignments, please show/hide individual assignments; hiding the course won\'t hide the individually embedded assignments.'
    },
    fields: [],
    courses: [],
    course: null,
    hasCourses: false,
    courseId: false, // if there's a courseId if it's an update
    showNoCoursesAlert: false,
    canViewCourses: false,
    modalHidden: false,
    graderForm: new Form({
      access_code: ''
    }),
    courseToImportForm: new Form({
      import_as_beta: 0
    }),
    newCourseForm: new Form({
      school: '',
      name: '',
      beta: '0',
      alpha: '0',
      lms: '0',
      public_description: '',
      private_description: '',
      section: '',
      start_date: '',
      end_date: '',
      public: '1'
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  watch: {
    courseToImport (newValue, oldValue) {
      if (newValue !== oldValue) {
        this.courseToImportForm.import_as_beta = 0
        this.showImportAsBeta = false
        this.disableYesImportCourse = true
      }
    }
  },
  mounted () {
    this.getCourses()
    this.getLastCourseSchool()
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.fields = (this.user.role === 2)
      ? [{
        key: 'name',
        label: 'Course',
        sortable: true
      },
        'shown',
        {
          key: 'start_date',
          sortable: true
        },
        {
          key: 'end_date',
          sortable: true
        },
        'actions'
      ]
      : [{
        key: 'name',
        label: 'Course',
        sortable: true
      },
        'sections',
        {
          key: 'start_date',
          sortable: true
        },
        {
          key: 'end_date',
          sortable: true
        },
        'actions'
      ]
  },
  methods: {
    async doNotShowBetaCourseDatesWarnings () {
      try {
        const { data } = await axios.post(`/api/beta-courses/do-not-show-beta-course-dates-warning`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.showBetaCourseDatesWarning = false
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async checkIfAlpha (courseToImport) {
      this.importAsBeta = 0
      let courseId = this.getIdOfCourseToImport(courseToImport)
      try {
        const { data } = await axios.get(`/api/courses/is-alpha/${courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        if (data.alpha === 1 && this.user.email !== 'commons@libertexts.org') {
          this.showImportAsBeta = true
        }
        this.disableYesImportCourse = false
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getLastCourseSchool () {
      try {
        const { data } = await axios.get(`/api/courses/last-school`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.newCourseForm.school = data.last_school_name
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async initImportCourse () {
      this.disableYesImportCourse = true
      this.importAsBeta = 0
      this.showImportAsBeta = false
      try {
        const { data } = await axios.get(`/api/courses/importable`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.importableCourses = data.importable_courses
        this.formattedImportableCourses = []
        for (let i = 0; i < data.importable_courses.length; i++) {
          this.formattedImportableCourses.push(data.importable_courses[i].formatted_course)
        }
        this.$bvModal.show('modal-import-course')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getIdOfCourseToImport (courseToImport) {
      for (let i = 0; i < this.importableCourses.length; i++) {
        if (this.importableCourses[i]['formatted_course'] === courseToImport) {
          return this.importableCourses[i]['course_id']
        }
      }
      return 0
    },
    async handleImportCourse () {
      try {
        let IdOfCourseToImport = this.getIdOfCourseToImport(this.courseToImport)
        const { data } = await this.courseToImportForm.post(`/api/courses/import/${IdOfCourseToImport}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.$bvModal.hide('modal-import-course')
        await this.getCourses()
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    showCourseWarning (course) {
      this.course = course
      this.course.shown || this.user.email === 'commons@libretexts.org'
        ? this.submitShowCourse()
        : this.$bvModal.show('modal-show-course-warning')
    },
    async submitShowCourse () {
      try {
        const { data } = await axios.patch(`/api/courses/${this.course.id}/show-course/${Number(this.course.shown)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.course.shown = !this.course.shown
        this.course.access_code = data.course_access_code
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitAddGraderToCourse (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.graderForm.post('/api/graders')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.resetAll('modal-course-grader-access-code')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async createCourse () {
      try {
        const { data } = await this.newCourseForm.post('/api/courses')
        this.$noty[data.type](data.message)
        this.resetAll('modal-course-details')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    getProperties (course) {
      this.$router.push(`/instructors/courses/${course.id}/properties`)
    },
    showAssignments (courseId) {
      this.$router.push(`/instructors/courses/${courseId}/assignments`)
    },
    showGradebook (courseId) {
      this.$router.push(`/courses/${courseId}/gradebook`)
    },
    async deleteCourse (courseId) {
      this.courseId = courseId
      try {
        const { data } = await axios.get(`/api/beta-courses/get-from-alpha-course/${courseId}`)
        if (data.type !== 'success') {
          return false
        }
        data.beta_courses.length
          ? this.$bvModal.show('modal-cannot-delete-course-with-at-least-one-tethered-beta-course')
          : this.$bvModal.show('modal-delete-course')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleDeleteCourse () {
      try {
        const { data } = await axios.delete('/api/courses/' + this.courseId)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-course')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    editCourse (course) {
      this.$refs.tooltip.$emit('close')
      this.courseId = course.id
      this.form.name = course.name
      this.form.start_date = course.start_date
      this.form.end_date = course.end_date
      this.$bvModal.show('modal-course-details')
    },
    resetModalForms () {
      this.newCourseForm.name = ''
      this.newCourseForm.start_date = ''
      this.newCourseForm.end_date = ''
      this.courseId = false
      this.newCourseForm.errors.clear()
    },
    resetAll (modalId) {
      this.courseId = ''
      this.getCourses()
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    submitCourseInfo (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.createCourse()
    },
    async getCourses () {
      try {
        const { data } = await axios.get('/api/courses')
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.canViewCourses = true
          this.hasCourses = data.courses.length > 0
          this.showNoCoursesAlert = !this.hasCourses
          this.showBetaCourseDatesWarning = data.showBetaCourseDatesWarning
          this.courses = data.courses
          this.hasBetaCourses = this.courses.filter(course => course.is_beta_course).length
          console.log(data.courses)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  },
  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
<style>
body, html {
  overflow: visible;

}

svg:focus, svg:active:focus {
  outline: none !important;
}
</style>
