<template>
  <div>
    <PageTitle title="My Courses"></PageTitle>
    <div class="row mb-4 float-right">
      <b-button variant="primary" v-b-modal.modal-enroll-in-course>Enroll In Course</b-button>
    </div>
    <b-modal
      id="modal-enroll-in-course"
      ref="modal"
      title="Enroll In Course"
      @ok="submitEnrollInCourse"
      @hidden="resetModalForms"
      ok-title="Submit"

    >
      <b-form ref="form" @submit="submitEnrollInCourse">
        <p>To enroll in the course, please provide the access code given to you by your instructor.</p>
        <b-form-group
          id="access_code"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Access Code"
          label-for="access_code"
        >
          <b-form-input
            id="access_code"
            v-model="form.access_code"
            type="text"
            :class="{ 'is-invalid': form.errors.has('access_code') }"
            @keydown="form.errors.clear('access_code')"
          >
          </b-form-input>
          <has-error :form="form" field="access_code"></has-error>
        </b-form-group>

      </b-form>
    </b-modal>

    <div v-if="hasEnrolledInCourses">
      <b-table striped hover :fields="fields" :items="enrolledInCourses">
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <a href="" v-on:click.prevent="getAssignments(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
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
        access_code: ''
      }),
      showNoEnrolledInCoursesAlert: false,
    }),
    mounted() {
      this.getEnrolledInCourses();

    },
    methods: {
    getAssignments(courseId){
      this.$router.push(`/students/courses/${courseId}/assignments`)
    },
      resetModalForms() {
        this.form.access_code = ''
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
      async enrollInCourse() {
        try {
          const {data} = await this.form.post('/api/enrollments')
          if (data.validated) {
            this.$noty[data.type](data.message)
            if (data.type === 'success') {
              this.resetAll('modal-enroll-in-course')
            }
          }
        } catch (error) {
          console.log(error)
        }
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
