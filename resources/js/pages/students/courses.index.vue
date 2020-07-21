<template>
  <div>
    <PageTitle title="My Courses"></PageTitle>
    Start:
    1. Students can enroll in a course (using the course access code)
    2. Students can view assignments for the course

    <div class="row mb-4 float-right">
      <b-button variant="primary" v-b-modal.modal-course-details>Enroll In Course</b-button>
    </div>
    <b-modal
      id="modal-enroll-course"
      ref="modal"
      title="Enroll In Course"
      @ok="submitEnrollInCourse"
      @hidden="resetModalForms"
      ok-title="Submit"

    >
      <b-form ref="form" @submit="submitEnrollInCourse">
        <b-form-group
          id="course_access_code"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Course Access Code"
          label-for="course_access_code"
        >
          <b-form-input
            id="course_access_code"
            v-model="form.course_access_code"
            type="text"
            :class="{ 'is-invalid': form.errors.has('course_access_code') }"
            @keydown="form.errors.clear('course_access_code')"
          >
          </b-form-input>
          <has-error :form="form" field="course_access_code"></has-error>
        </b-form-group>

      </b-form>
    </b-modal>

    <div v-if="hasEnrolledInCourses">
      <b-table striped hover :fields="fields" :items="enrolledInCourses">
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoEnrolledInCoursesAlert" variant="warning"><a href="#" class="alert-link">You currently are not enrolled in any courses.
        </a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"
  import { formatDate } from '~/helpers/Date'


  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {
          key: 'name',
          label: 'Course'
        },
        'instructor',
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
        }
      ],
      enrolledInCourses: [],
      hasEnrolledInCourses: false,
      form: new Form({
        course_access_code: ''
      }),
      showNoEnrolledInCoursesAlert: false,
    }),
    mounted() {
      this.getEnrolledInCourses();

    },
    methods: {

      resetModalForms() {
        this.form.course_access_code = ''
        this.form.errors.clear()
      }
      ,
      resetAll(modalId) {
        this.getEnrolledInCourses()
        this.resetModalForms()
        // Hide the modal manually
        this.$nextTick(() => {
          this.$bvModal.hide(modalId)
        })
      }
      ,
      submitEnrollInCourse(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.enrollInCourse()
      }
      ,
      enrollInCourse() {

      },
      getEnrolledInCourses() {
        try {
          axios.get('/api/enrollments').then(
            response => {
              this.hasEnrolledInCourses = response.data.length > 0
              this.showNoEnrolledInCoursesAlert = !this.hasEnrolledInCourses
              this.enrolledInCourses = response.data
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
