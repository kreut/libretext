<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-course"/>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-delete-course"/>
    <b-modal
      id="modal-copy-beta"
      ref="modal"
      title="Copy Beta Course"
    >
      <p>
        This course is a Beta course. You can copy this as another tethered Beta course, using the current state of the
        associated Alpha course
        or you can copy this as an untethered course.
      </p>
      <b-form-group label="Copy the course"
                    label-cols-sm="5"
                    label-cols-lg="4"
                    label-for="copy-the-course-options"
      >
        <b-form-radio-group id="copy-the-course-options"
                            v-model="copyCourseOption"
                            class="mt-2"
        >
          <b-form-radio name="copy-course-options" value="as-beta">
            as another Beta course
          </b-form-radio>
          <b-form-radio name="copy-course-options" value="untethered">
            an an untethered course
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <template #modal-footer>
        <span v-if="processingImportCourse">
          <b-spinner small type="grow"/>
          Copying course...
        </span>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-copy-beta')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="copy(courseToCopy)"
        >
          Submit
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-import-course"
      ref="modal"
      title="Import Course"
      size="lg"
    >
      <div id="course_to_import">
        <v-select
          id="course-to-import"
          v-model="courseToImport"
          class="mb-2"
          :options="formattedImportableCourses"
          placeholder="Enter a course or instructor name"
          @input="checkIfAlpha($event)"
        />
      </div>
      <b-form-group
        v-if="showImportAsBeta"
        id="beta"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="beta"
      >
        <template v-slot:label>
          Import as Beta Course
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
        <span v-if="processingImportCourse">
          <b-spinner small type="grow"/>
          Importing course...
        </span>
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
          :disabled="disableYesImportCourse || processingImportCourse"
          @click="handleImportCourse"
        >
          Yes, import course!
        </b-button>
      </template>
    </b-modal>
    <PageTitle v-if="canViewCourses" title="My Courses"/>
    <b-container v-if="canViewCourses && user && [2,5].includes(user.role)">
      <b-row align-h="end" class="mb-4">
        <b-button v-b-modal.modal-course-details variant="primary" class="mr-1"
                  size="sm"
        >
          New Course
        </b-button>
        <b-button v-if="user.role === 2"
                  variant="outline-primary"
                  size="sm"
                  class="mr-1"
                  @click="initImportCourse"
        >
          Import Course
        </b-button>
      </b-row>
    </b-container>

    <b-modal
      id="modal-course-details"
      ref="modal"
      title="Course Details"
      size="lg"
      :no-close-on-esc="true"
      :no-close-on-backdrop="true"
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
          @click="createCourse"
        >
          Save
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
      :title="`Confirm Delete ${courseName}`"
      size="lg"
      @hidden="resetModalForms"
    >
      <b-form ref="form">
        <b-alert show variant="danger">
          <span class="font-weight-bold">Once a course is deleted, it can not be retrieved!</span>
        </b-alert>
        <p>By deleting the <strong>{{ courseName }}</strong>, you will also delete:</p>
        <ol>
          <li>All assignments associated with the course</li>
          <li>All submitted student responses</li>
          <li>All student scores</li>
        </ol>
        <RequiredText :plural="false"/>
        <b-form-group
          label-cols-sm="1"
          label-cols-lg="2"
          label-for="Confirmation"
        >
          <template v-slot:label>
            Confirmation*
          </template>
          <b-form-input
            id="confirmation"
            v-model="deleteCourseForm.confirmation"
            class="col-8"
            required
            placeholder="Please enter the name of the course."
            type="text"
            :class="{ 'is-invalid': deleteCourseForm.errors.has('confirmation') }"
            @keydown="deleteCourseForm.errors.clear('confirmation')"
          />
          <has-error :form="deleteCourseForm" field="confirmation"/>
        </b-form-group>
      </b-form>
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
          :disabled="processingDeletingCourse"
          @click="handleDeleteCourse"
        >
          <span v-if="!processingDeletingCourse">Yes, delete course!</span>
          <span v-if="processingDeletingCourse"><b-spinner small type="grow"/>
            Deleting Course...
          </span>
        </b-button>
      </template>
    </b-modal>

    <div v-if="user && user.role === 4">
      <div v-if="canViewCourses" class="row mb-4 float-right">
        <b-button v-b-modal.modal-course-grader-access-code variant="primary">
          New Course
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
              dates are accurate as ADAPT uses this information to tether the Alpha assignments to your Beta assignments.
            </span>
          </p>
          <b-button size="sm" variant="info" @click="doNotShowBetaCourseDatesWarnings()">
            Don't Show This Again
          </b-button>
        </b-alert>
      </div>
      <div class="table-responsive">
        <table class="table table-striped" aria-label="Course List">
          <thead>
          <tr>
            <th scope="col">
              Course
            </th>
            <th v-if="[2,4].includes(user.role)" style="width:100px">
                <span v-show="user.role === 2">
                  Shown <a id="course_shown"
                           v-b-tooltip="showCourseShownTooltip"
                           href="#"
                           aria-label="Toggle courses shown"
                ><b-icon class="text-muted"
                         icon="question-circle"
                /></a></span>
              <span v-show="user.role === 4">
                  Sections
                </span>
            </th>
            <th v-if="[2,4].includes(user.role)">
              Term
            </th>
            <th :style="[2,4].includes(user.role) ? 'width:120px' : ''">
              Actions
            </th>
          </tr>
          </thead>
          <tbody is="draggable" v-model="courses" tag="tbody" :options="{disabled : user.role === 4}"
                 @end="saveNewOrder"
          >
          <tr v-for="course in courses"
              :key="course.id"
          >
            <th scope="row">
              <div class="mb-0">
                <b-icon v-if="user.role === 2" icon="list"/>
                <span v-show="parseInt(course.alpha) === 1"
                      :id="getTooltipTarget('alphaCourse',course.id)"
                      class="text-muted"
                >&alpha; </span>
                <b-tooltip :target="getTooltipTarget('alphaCourse',course.id)"
                           delay="500"
                >
                  This course is an Alpha course. Adding/removing assignments or assessments from this
                  course will be directly reflected in the associated Beta courses.
                </b-tooltip>
                <span v-show="parseInt(course.is_beta_course) === 1"
                      :id="getTooltipTarget('betaCourse',course.id)"
                      class="text-muted"
                >&beta; </span>
                <b-tooltip :target="getTooltipTarget('betaCourse',course.id)"
                           delay="500"
                >
                  This course is a Beta course. Since it is tethered to an Alpha course, assignments/assessments which
                  are
                  added/removed in the Alpha course will be directly reflected in this course.
                </b-tooltip>
                <a href="" @click.prevent="showAssignments(course.id)">{{ course.name }}</a>
              </div>
            </th>

            <td v-if="[2,4].includes(user.role)">
                <span v-if="user.role === 2">
                  <toggle-button
                    tabindex="0"
                    :width="57"
                    :value="Boolean(course.shown)"
                    :aria-checked="Boolean(course.shown)"
                    :aria-label="Boolean(course.shown) ? `${course.name} shown` : `${course.name} not shown`"
                    :sync="true"
                    :font-size="14"
                    :margin="4"
                    :color="toggleColors"
                    :labels="{checked: 'Yes', unchecked: 'No'}"
                    @change="showCourseWarning(course)"
                  />
                </span>
              <span v-if="user.role === 4">
                  {{ course.sections }}
                </span>
            </td>
            <td v-if="[2,4].includes(user.role)">{{ course.term }}</td>
            <td>
              <div class="mb-0">
                  <span v-if="[2,4].includes(user.role)" class="pr-1">
                    <b-tooltip :target="getTooltipTarget('gradebook',course.id)"
                               delay="500"
                               triggers="hover focus"
                    >
                      Gradebook
                    </b-tooltip>
                    <a :id="getTooltipTarget('gradebook',course.id)"
                       href=""
                       @click.prevent="showGradebook(course.id)"
                    >
                      <b-icon class="text-muted"
                              icon="file-spreadsheet"
                              :aria-label="`Gradebook for ${course.name}`"
                      />
                    </a>
                  </span>
                <span v-if="user && [2,5].includes(user.role)">
                    <span class="pr-1">
                      <b-tooltip :target="getTooltipTarget('properties',course.id)"
                                 delay="500"
                                 triggers="hover focus"
                      >
                        Course Properties
                      </b-tooltip>
                      <a :id="getTooltipTarget('properties',course.id)"
                         href=""
                         @click.prevent="getProperties(course)"
                      >
                        <b-icon class="text-muted"
                                icon="gear"
                                :aria-label="`Course properties for ${course.name}`"
                        />
                      </a>
                    </span>
                    <span v-if="!course.copying_course" class="pr-1">
                      <a :id="getTooltipTarget('copy',course.id)"
                         href=""
                         @click.prevent="initCopyCourse(course)"
                      >
                        <font-awesome-icon
                          class="text-muted"
                          :icon="copyIcon"
                          :aria-label="`Copy ${course.name}`"
                        />
                      </a>
                      <b-tooltip :target="getTooltipTarget('copy',course.id)"
                                 delay="500"
                      >
                        Copy {{ course.name }}
                      </b-tooltip>
                    </span>
                    <span v-if="course.copying_course">
                      <b-spinner small type="grow"/>
                    </span>
                    <b-tooltip :target="getTooltipTarget('deleteCourse',course.id)"
                               delay="500"
                               triggers="hover focus"
                    >
                      Delete Course
                    </b-tooltip>
                    <a :id="getTooltipTarget('deleteCourse',course.id)"
                       href=""
                       @click.prevent="deleteCourse(course)"
                    >
                      <b-icon class="text-muted"
                              icon="trash"
                              :aria-label="`Delete ${course.name}`"
                      />
                    </a>

                  </span>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
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
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { ToggleButton } from 'vue-js-toggle-button'
import ImportAsBetaText from '~/components/ImportAsBetaText'
import AllFormErrors from '~/components/AllFormErrors'
import draggable from 'vuedraggable'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  components: {
    CourseForm,
    ToggleButton,
    FontAwesomeIcon,
    ImportAsBetaText,
    AllFormErrors,
    draggable
  },
  middleware: 'auth',
  data: () => ({
    copyCourseOption: null,
    courseToCopy: {},
    copyIcon: faCopy,
    processingDeletingCourse: false,
    deleteCourseForm: new Form({
      confirmation: ''
    }),
    courseName: '',
    processingImportCourse: false,
    toggleColors: window.config.toggleColors,
    currentOrderedCourses: [],
    allFormErrors: [],
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
      term: 'N/A',
      lms: '0',
      public_description: '',
      private_description: '',
      section: '',
      start_date: '',
      end_date: '',
      public: '0',
      anonymous_users: '0'
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
  beforeDestroy () {
    window.removeEventListener('keydown', this.quickSave)
  },
  mounted () {
    window.addEventListener('keydown', this.quickSave)
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
    quickSave (event) {
      if (event.ctrlKey && event.key === 's') {
        this.createCourse()
      }
    },
    updateCopyingCourse (course, value) {
      for (let i = 0; i < this.courses.length; i++) {
        if (this.courses[i].id === course.id) {
          this.courses[i].copying_course = value
          this.$forceUpdate()
          return
        }
      }
    },
    initCopyCourse (course) {
      this.courseToCopy = course
      this.copyCourseOption = null
      if (course.is_beta_course) {
        this.copyCourseOption = 'as-beta'
        this.$bvModal.show('modal-copy-beta')
      } else {
        this.copy(this.courseToCopy)
      }
    },
    async getAlphaCourseFromBetaCourse (course) {
      try {
        const { data } = await axios.get(`/api/beta-courses/get-alpha-course-from-beta-course/${course.id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return data.alpha_course
      } catch (error) {
        this.$noty.error(error.message)
      }
      return false
    },
    async copy (course) {
      this.courseToImportForm.action = 'copy'
      this.updateCopyingCourse(course, true)
      if (this.copyCourseOption === 'as-beta') {
        course = await this.getAlphaCourseFromBetaCourse(course)
        this.$bvModal.hide('modal-copy-beta')
        if (!course) {
          this.updateCopyingCourse(course, false)
          return false
        }
        this.courseToImportForm.import_as_beta = true
      }
      try {
        const { data } = await this.courseToImportForm.post(`/api/courses/import/${course.id}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-copy-beta')
        if (data.type === 'error') {
          this.updateCopyingCourse(course, false)
          return false
        }
        await this.getCourses()
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-copy-beta')
      this.updateCopyingCourse(course, false)
    },
    async saveNewOrder () {
      let orderedCourses = []
      for (let i = 0; i < this.courses.length; i++) {
        orderedCourses.push(this.courses[i].id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedCourses.length; i++) {
        if (this.currentOrderedCourses[i] !== this.courses[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/courses/order`, { ordered_courses: orderedCourses })
        this.$noty[data.type](data.message)

        if (data.type === 'success') {
          for (let i = 0; i < this.courses.length; i++) {
            this.courses[i].order = i + 1
          }
          this.currentOrderedCourses = this.courses
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
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
      this.processingImportCourse = true
      try {
        let IdOfCourseToImport = this.getIdOfCourseToImport(this.courseToImport)
        this.courseToImportForm.action = 'import'
        const { data } = await this.courseToImportForm.post(`/api/courses/import/${IdOfCourseToImport}`)
        this.$noty[data.type](data.message)
        this.courseToImport = ''
        if (data.type === 'error') {
          this.processingImportCourse = false
          return false
        }
        this.$bvModal.hide('modal-import-course')
        await this.getCourses()
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
      this.processingImportCourse = false
      this.courseToImport = ''
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
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.newCourseForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-course')
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
    async deleteCourse (course) {
      this.courseId = course.id
      this.courseName = course.name
      try {
        const { data } = await axios.get(`/api/beta-courses/get-from-alpha-course/${this.courseId}`)
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
      this.processingDeletingCourse = true
      try {
        const { data } = await this.deleteCourseForm.delete(`/api/courses/${this.courseId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-course')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.deleteCourseForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-delete-course')
        }
      }
      this.processingDeletingCourse = false
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
      this.courseName = ''
      this.deleteCourseForm.confirmation = ''
      this.getCourses()
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    async getCourses () {
      try {
        const { data } = await axios.get('/api/courses')
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.canViewCourses = true
          this.hasCourses = data.courses && data.courses.length > 0
          this.showNoCoursesAlert = !this.hasCourses
          this.showBetaCourseDatesWarning = data.showBetaCourseDatesWarning
          this.courses = data.courses
          for (let i = 0; i < this.courses.length; i++) {
            this.courses[i].copying_course = false
          }
          this.hasBetaCourses = this.courses.filter(course => course.is_beta_course).length
          this.currentOrderedCourses = this.courses
          console.log(data.courses)
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
<style>
body, html {
  overflow: visible;

}

svg:focus, svg:active:focus {
  outline: none !important;
}
</style>
