<template>
  <div>
    <PageTitle title="Grades"></PageTitle>
    <div v-if="hasAssignments">
      <b-table striped
               hover
               fixed
               :items="items"
               :fields="fields"
               :sort-by.sync="sortBy"
               :sort-desc.sync="sortDesc"
               sort-icon-left
               responsive="sm"

      >
        <template v-slot:[initStudentAssignmentCell(assignmentIndex+1)]="data"
                  v-for="assignmentIndex in assignmentsArray">
          <span v-on:click="openStudentAssignmentModal(data.index, data.field.key)">{{ data.value}}</span>
        </template>

      </b-table>
    </div>
    <div v-else>
      <b-alert show variant="warning"><a href="#" class="alert-link">Once you create your first assignment, you'll be
        able to view your gradebook.</a></b-alert>
    </div>
    <b-modal
      id="modal-update-student-assignment"
      ref="modal"
      title="Update Student Assignment"
      @ok="submitUpdateAssignmentByStudent"
      @hidden="resetModalForms"
      ok-title="Submit"
      size="lg"
    >
      <p>Please use this form to either provide an extension for your student or given an override.</p>
      <b-form ref="form" @submit="updateAssignmentByStudent">
        <b-form-group
          id="extension"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Extension"
          label-for="Extension"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                v-model="form.extension_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('extension_date') }"
                v-on:shown="form.errors.clear('extension_date')">
              </b-form-datepicker>
              <has-error :form="form" field="extension_date"></has-error>
            </b-col>
            <b-col>
              <b-form-timepicker v-model="form.extension_time"
                                 locale="en"
                                 :class="{ 'is-invalid': form.errors.has('extension_time') }"
                                 v-on:shown="form.errors.clear('extension_time')">

              </b-form-timepicker>
              <has-error :form="form" field="extension_time"></has-error>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="score"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Override Score"
          label-for="Override Score"
        >
          <b-form-row>
            <div>
              <b-form-select v-model="form.score" :options="options"></b-form-select>
            </div>
          </b-form-row>

        </b-form-group>
      </b-form>
    </b-modal>

  </div>
</template>
<script>
  import axios from 'axios'
  import Form from "vform"


  const now = new Date()

  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  let formatDateAndTime = value => {
    let date = new Date(value)
    return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' ' + date.toLocaleTimeString()
  }

  // get all students enrolled in the course: course_enrollment
  // get all assignments for the course
  //
  export default {
    middleware: 'auth',
    data: () => ({
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      form: new Form({
        extension_date: '',
        extension_time: '09:00:00',
        score: null
      }),
      sortBy: 'name',
      sortDesc: false,
      courseId: '',
      fields: [],
      grades: [],
      items: [],
      hasAssignments: true,
      studentIndex: 0,
      assignmentIndex: 0,
      assignmentsArray: [],
      options: [
        {value: null, text: 'Please select an option'},
        {value: 'C', text: 'Completed'},
        {value: '1', text: '1'},
        {value: '2', text: '2'},
        {value: '3', text: '3'},
        {value: '4', text: '4'},
        {value: '5', text: '5'},
      ]
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getGrades();
    },
    methods: {
      submitUpdateAssignmentByStudent(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.updateAssignmentByStudent()
      },
      updateAssignmentByStudent() {
        if ((this.form.score !== null) && (this.form.extension_date !== '')) {
          this.$noty.error("Please either give an extension or provide an override score, but not both.")
          this.form.score = null
          this.form.extension_date = ''
        }

      },
      resetModalForms() {

        this.form.extension_date = ''
        this.form.extension_time = '09:00:00'
        this.form.errors.clear()
      },
      initStudentAssignmentCell(key) {
        return `cell(${key})`; // simple string interpolation
      },
      openStudentAssignmentModal(studentIndex, assignmentIndex) {
        this.studentIndex = studentIndex
        this.assignmentIndex = assignmentIndex
        console.log(studentIndex + ', ' + assignmentIndex)
        this.$bvModal.show('modal-update-student-assignment')

      },
      getGrades() {

        try {
          axios.get(`/api/courses/${this.courseId}/grades`).then(
            response => {
              console.log(response)
              if (response.data.hasAssignments) {
                this.items = response.data.rows
                this.fields = response.data.fields
                //create an array 0 up through the top assignment number index
                this.assignmentsArray = [...Array(this.fields.length).keys()]
              } else {
                this.hasAssignments = false
              }
            }
          )
        } catch (error) {
          alert(error.message)
        }
      }

    }
  }
</script>
