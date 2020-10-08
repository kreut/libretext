<template>
  <div>
    <PageTitle v-if="canViewCourses" title="My Courses"></PageTitle>
    <div v-if="user.role === 2">
      <div class="row mb-4 float-right" v-if="canViewCourses">
        <b-button variant="primary" v-b-modal.modal-course-details>Add Course</b-button>
      </div>
    </div>
    <b-modal
      id="modal-manage-graders"
      ref="modal"
      title="Invite Grader"
      @ok="submitInviteGrader"
      @hidden="resetModalForms"
      ok-title="Submit"
    >
      <b-form ref="form">
        <div v-if="graders.length">
          Your current graders:<br>
          <ol id="graders">
            <li v-for="grader in graders" :key="grader.id">
              {{ grader.name }}
              <b-icon icon="trash" v-on:click="deleteGrader(grader.id)"></b-icon>
            </li>
          </ol>
        </div>

        <b-form-group
          id="email"
          label-cols-sm="4"
          label-cols-lg="3"
          label="New Grader"
          label-for="email"
        >
          <b-form-input
            id="email"
            v-model="graderForm.email"
            placeholder="Email Address"
            type="text"
            :class="{ 'is-invalid': graderForm.errors.has('email') }"
            @keydown="graderForm.errors.clear('email')"
          >
          </b-form-input>
          <has-error :form="graderForm" field="email"></has-error>
        </b-form-group>
        <div v-if="sendingEmail" class="float-right">
          <b-spinner small type="grow"></b-spinner>
          Sending Email..
        </div>

      </b-form>
    </b-modal>

    <b-modal
      id="modal-course-details"
      ref="modal"
      title="Course Details"
      @ok="submitCourseInfo"
      @hidden="resetModalForms"
      ok-title="Submit"

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
          >
          </b-form-input>
          <has-error :form="form" field="name"></has-error>
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
            v-on:shown="form.errors.clear('start_date')">
          </b-form-datepicker>
          <has-error :form="form" field="start_date"></has-error>
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
            v-on:shown="form.errors.clear('end_date')">
          </b-form-datepicker>
          <has-error :form="form" field="end_date"></has-error>
        </b-form-group>
      </b-form>
    </b-modal>

    <b-modal
      id="modal-delete-course"
      ref="modal"
      title="Confirm Delete Course"
      @ok="handleDeleteCourse"
      @hidden="resetModalForms"
      ok-title="Yes, delete course!"

    >
      <p>By deleting the course, you will also delete:</p>
      <ol>
        <li>All assignments associated with the course</li>
        <li>All submitted student responses</li>
        <li>All student scores</li>
      </ol>
      <p><strong>Once a course is deleted, it can not be retrieved!</strong></p>
    </b-modal>
    <b-modal
      id="modal-update-course-access-code"
      ref="modal"
      title="Confirm Refresh Access Code"
      @ok="handleUpdateAccessCode"
      @hidden="resetModalForms"
      ok-title="Yes, refresh the access code!"

    >
      <p>By refreshing your access code, students will no longer be able to sign up using the old access code.</p>
    </b-modal>

    <div v-if="hasCourses">
      <b-table striped hover :fields="fields" :items="courses">
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <a href="" v-on:click.prevent="showAssignments(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <span class="pr-1" v-on:click="showScores(data.item.id)"><b-icon icon="file-spreadsheet"></b-icon></span>
            <span v-if="user.role === 2">
              <span class="pr-1" v-on:click="editCourse(data.item)"><b-icon icon="pencil"></b-icon></span>
              <span class="pr-1" v-on:click="inviteGrader(data.item.id)"><b-icon icon="people"></b-icon></span>
              <span class="pr-1" v-on:click="updateAccessCode(data.item)"> <b-icon
                icon="arrow-repeat"></b-icon></span>
                <b-icon icon="trash" v-on:click="deleteCourse(data.item.id)"></b-icon>
            </span>
          </div>
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoCoursesAlert" variant="warning"><a href="#" class="alert-link">You currently have no
          courses.
        </a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from "vform"
import {mapGetters} from "vuex"
import moment from 'moment'


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
        label: 'Course'
      },
      {
        key: 'start_date',
        formatter: value => {
          return moment(value, 'YYYY-MM-DD').format('MMMM DD, YYYY')
        }
      },
      {
        key: 'end_date',
        formatter: value => {
          return moment(value, 'YYYY-MM-DD').format('MMMM DD, YYYY')
        }
      },
      {
        key: 'access_code',
        label: 'Access Code'
      },
      'actions'
    ],
    sendingEmail: false,
    courses: [],
    course: null,
    hasCourses: false,
    courseId: false, //if there's a courseId if it's an update
    min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
    form: new Form({
      name: '',
      start_date: '',
      end_date: ''
    }),
    graders: {},
    graderForm: new Form({
      email: ''
    }),
    showNoCoursesAlert: false,
    canViewCourses: false
  }),
  mounted() {
    this.getCourses();
  },
  methods: {
    async deleteGrader(userId) {
      try {
        const {data} = await axios.delete(`/api/grader/${this.courseId}/${userId}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error('We were not able to remove the grader from the course.  Please try again or contact us for assistance.')
          return false
        }
        this.$noty.success(data.message)
        //remove the grad
        this.graders = this.graders.filter(grader => parseFloat(grader.id) !== parseFloat(userId))


      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleUpdateAccessCode(bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const {data} = await axios.patch('/api/course-access-codes', {course_id: this.courseId})
        if (data.type === 'error') {
          this.$noty.error('We were not able to update your access code.')
          return false
        }
        this.$noty.success(data.message)
        this.course.access_code = data.access_code
        this.$nextTick(() => {
          this.$bvModal.hide('modal-update-course-access-code')
        })
      } catch (error) {
        this.$noty.error(error.message)
      }


    },
    async inviteGrader(courseId) {
      this.courseId = courseId
      try {
        const {data} = await axios.get(`/api/grader/${this.courseId}`)
        this.graders = data.graders
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error('We were not able to retrieve your graders.')
          return false
        }
        this.$bvModal.show('modal-manage-graders')

      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    async submitInviteGrader(bvModalEvt) {
      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }
      bvModalEvt.preventDefault()
      try {
        this.sendingEmail = true
        const {data} = await this.graderForm.post(`/api/invitations/${this.courseId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-manage-graders')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
      this.sendingEmail = false
    },
    showAssignments(courseId) {
      this.$router.push(`/instructors/courses/${courseId}/assignments`)
    }
    ,
    showScores(courseId) {
      window.location.href = `/courses/${courseId}/scores`
      this.$router.push(`/courses/${courseId}/scores`)
    }
    ,
    deleteCourse(courseId) {
      this.courseId = courseId
      this.$bvModal.show('modal-delete-course')
    }
    ,
    async handleDeleteCourse() {
      try {
        const {data} = await axios.delete('/api/courses/' + this.courseId)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-course')
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    ,
    editCourse(course) {
      this.courseId = course.id;
      this.form.name = course.name
      this.form.start_date = course.start_date
      this.form.end_date = course.end_date
      this.$bvModal.show('modal-course-details')
    },
    updateAccessCode(course) {
      this.courseId = course.id
      this.course = course
      this.$bvModal.show('modal-update-course-access-code')
    }
    ,
    resetModalForms() {
      this.form.name = ''
      this.form.start_date = ''
      this.form.end_date = ''
      this.graderForm.email = ''
      this.courseId = false
      this.form.errors.clear()
    }
    ,
    resetAll(modalId) {
      this.getCourses()
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    }
    ,
    submitCourseInfo(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      !this.courseId ? this.createCourse() : this.updateCourse()
    }
    ,
    async createCourse() {
      try {
        const {data} = await this.form.post('/api/courses')
        this.$noty[data.type](data.message)
        this.resetAll('modal-course-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }

    },
    async updateCourse() {
      try {
        const {data} = await this.form.patch(`/api/courses/${this.courseId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-course-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }

      }

    }
    ,
    async getCourses() {
      try {
        const {data} = await axios.get('/api/courses')
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
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>
<style>
body, html {
  overflow: visible;

}

</style>
