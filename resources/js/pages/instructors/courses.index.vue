<template>
  <div>
    <div class="row mb-4 float-right">
      <b-button variant="primary" v-b-modal.modal-course-details>Add Course</b-button>
    </div>
    <b-modal
      id="modal-course-details"
      ref="modal"
      title="Course Details"
      @ok="submitCourseInfo"
      @hidden="resetModalForms"
      ok-title="Submit"

    >
      <b-form ref="form" @submit="createCourse">
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
        <li>All student grades</li>
      </ol>
      <p><strong>Once a course is deleted, it can not be retrieved!</strong></p>
    </b-modal>
    <div v-if="hasCourses">
      <b-table striped hover :fields="fields" :items="courses">
        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <span class="pr-1" v-on:click="showAssignments(data.item.id)"><b-icon
              icon="file-earmark-text"></b-icon></span>
            <span class="pr-1" v-on:click="showGrades(data.item.id)"><b-icon icon="file-spreadsheet"></b-icon></span>
            <span class="pr-1" v-on:click="editCourse(data.item)"><b-icon icon="pencil"></b-icon></span>
            <b-icon icon="trash" v-on:click="deleteCourse(data.item.id)"></b-icon>
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

  let formatDate = value => {
    let date_pieces = value.split('-')
    let month = date_pieces[1]
    let day = date_pieces[2].split(' ')[0]//get rid of the time piece 2020-06-21 00:00:00
    let year = date_pieces[0]
    return month + '-' + day + '-' + year
  }

  const now = new Date()
  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {
          key: 'name',
          label: 'Course'
        },
        {
          key: 'start_date',
          formatter: value => {
            return formatDate(value)
          }
        },
        {
          key: 'end_date',
          formatter: value => {
            return formatDate(value)
          }
        },
        {
          key: 'access_code',
          label: 'Access Code'
        },
        'actions'
      ],
      courses: [],
      hasCourses: false,
      courseId: false, //if there's a courseId if it's an update
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      form: new Form({
        name: '',
        start_date: '',
        end_date: ''
      }),
      showNoCoursesAlert: false,
    }),
    mounted() {
      this.getCourses();

    },
    methods: {
      showAssignments(courseId) {
        window.location.href = `/courses/${courseId}/assignments`
      }
      ,
      showGrades(courseId) {
        window.location.href = `/courses/${courseId}/grades`
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
          console.log(error)
        }
      }
      ,
      editCourse(course) {
        this.courseId = course.id;
        this.form.name = course.name
        this.form.start_date = course.start_date
        this.form.end_date = course.end_date
        this.$bvModal.show('modal-course-details')
      }
      ,
      resetModalForms() {
        this.form.name = ''
        this.form.start_date = ''
        this.form.end_date = ''
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
        this.createCourse()
      }
      ,
      async createCourse() {
        try {
          let endpoint = (!this.courseId) ? '/api/courses' : '/api/courses/' + this.courseId
          const {data} = await this.form.post(endpoint)
          this.$noty[data.type](data.message)
          this.resetAll('modal-course-details')

        } catch (error) {
          console.log(error)
        }

      }
      ,
      getCourses() {
        try {
          axios.get('/api/courses').then(
            response => {
              this.hasCourses = response.data.length > 0
              this.showNoCoursesAlert = !this.hasCourses
              this.courses = response.data
            }
          )
        } catch (error) {
          alert(error.message)
        }
      }
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
