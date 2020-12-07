<template>
  <div>
    <PageTitle v-if="canViewCourses" title="My Courses" />
    <div v-if="user.role === 2">
      <div v-if="canViewCourses" class="row mb-4 float-right">
        <b-button v-b-modal.modal-course-details variant="primary">
          Add Course
        </b-button>
      </div>
    </div>

    <b-modal
      id="modal-course-details"
      ref="modal"
      title="Course Details"
      ok-title="Submit"
      @ok="submitCourseInfo"
      @hidden="resetModalForms"
    >
      <b-form ref="form">
        <b-form-group
          id="name"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Name"
          label-for="name"
        >
          <b-form-input
            id="name"
            v-model="form.name"
            type="text"
            :class="{ 'is-invalid': form.errors.has('name') }"
            @keydown="form.errors.clear('name')"
          />
          <has-error :form="form" field="name" />
        </b-form-group>

        <b-form-group
          id="start_date"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Start Date"
          label-for="Start Date"
        >
          <b-form-datepicker
            v-model="form.start_date"
            :min="min"
            :class="{ 'is-invalid': form.errors.has('start_date') }"
            @shown="form.errors.clear('start_date')"
          />
          <has-error :form="form" field="start_date" />
        </b-form-group>

        <b-form-group
          id="end_date"
          label-cols-sm="4"
          label-cols-lg="3"
          label="End Date"
          label-for="End Date"
        >
          <b-form-datepicker
            v-model="form.end_date"
            :min="min"
            class="mb-2"
            :class="{ 'is-invalid': form.errors.has('end_date') }"
            @click="form.errors.clear('end_date')"
            @shown="form.errors.clear('end_date')"
          />
          <has-error :form="form" field="end_date" />
        </b-form-group>
      </b-form>
    </b-modal>

    <b-modal
      id="modal-delete-course"
      ref="modal"
      title="Confirm Delete Course"
      ok-title="Yes, delete course!"
      @ok="handleDeleteCourse"
      @hidden="resetModalForms"
    >
      <p>By deleting the course, you will also delete:</p>
      <ol>
        <li>All assignments associated with the course</li>
        <li>All submitted student responses</li>
        <li>All student scores</li>
      </ol>
      <p><strong>Once a course is deleted, it can not be retrieved!</strong></p>
    </b-modal>
    <div v-if="hasCourses">
      <b-table striped hover
               :fields="fields"
               :items="courses"
      >
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <a href="" @click.prevent="showAssignments(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
        <template v-slot:cell(start_date)="data">
          {{ $moment(data.item.start_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-slot:cell(end_date)="data">
          {{ $moment(data.item.end_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <span class="pr-1" @click="showScores(data.item.id)">
              <b-tooltip :target="getTooltipTarget('gradebook',data.item.id)"
                         delay="500"
              >
                Gradebook
              </b-tooltip>
              <b-icon :id="getTooltipTarget('gradebook',data.item.id)" icon="file-spreadsheet" /></span>
            <span v-if="user.role === 2">
              <b-tooltip ref="tooltip"
                         :target="getTooltipTarget('pencil',data.item.id)"
                         delay="500"
              >
                Edit Course
              </b-tooltip>
              <span class="pr-1" @click="editCourse(data.item)">
                <b-icon :id="getTooltipTarget('pencil',data.item.id)" icon="pencil" />
              </span>
              <span class="pr-1" @click="getProperties(data.item)">
                <b-tooltip :target="getTooltipTarget('properties',data.item.id)"
                           delay="500"
                >
                  Edit course properties
                </b-tooltip>
                <b-icon :id="getTooltipTarget(properties,data.item.id)" icon="gear" />
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
import Form from 'vform'
import { mapGetters } from 'vuex'
import { getTooltipTarget, initTooltips } from '../../helpers/Tooptips'

const now = new Date()
export default {
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    fields: [
      {
        key: 'name',
        label: 'Course',
        sortable: true
      },
      {
        key: 'start_date',
        sortable: true
      },
      {
        key: 'end_date',
        sortable: true
      },
      {
        key: 'access_code',
        label: 'Access Code'
      },
      'actions'
    ],
    courses: [],
    course: null,
    hasCourses: false,
    courseId: false, // if there's a courseId if it's an update
    min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
    form: new Form({
      name: '',
      start_date: '',
      end_date: ''
    }),
    showNoCoursesAlert: false,
    canViewCourses: false,
    modalHidden: false
  }),
  mounted () {
    this.getCourses()
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  },
  methods: {
    getProperties (course) {
      this.$router.push(`/instructors/courses/${course.id}/properties`)
    },
    showAssignments (courseId) {
      this.$router.push(`/instructors/courses/${courseId}/assignments`)
    },
    showScores (courseId) {
      this.$router.push(`/courses/${courseId}/scores`)
    },
    deleteCourse (courseId) {
      this.courseId = courseId
      this.$bvModal.show('modal-delete-course')
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
    updateAccessCode (course) {
      this.courseId = course.id
      this.course = course
      this.$bvModal.show('modal-update-course-access-code')
    },
    resetModalForms () {
      this.form.name = ''
      this.form.start_date = ''
      this.form.end_date = ''
      this.graderForm.email = ''
      this.courseId = false
      this.form.errors.clear()
    },
    resetAll (modalId) {
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
      !this.courseId ? this.createCourse() : this.updateCourse()
    },
    async createCourse () {
      try {
        const { data } = await this.form.post('/api/courses')
        this.$noty[data.type](data.message)
        this.resetAll('modal-course-details')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async updateCourse () {
      try {
        const { data } = await this.form.patch(`/api/courses/${this.courseId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-course-details')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
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
          this.courses = data.courses
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
