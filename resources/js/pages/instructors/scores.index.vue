<template>
  <div>
    <PageTitle title="Scores"></PageTitle>
    <div v-if="hasAssignments">
      <b-table striped
               hover
               fixed
               :items="items"
               :fields="fields"
               :sort-by.sync="sortBy"
               primary-key="userId"
               :sort-desc.sync="sortDesc"
               sort-icon-left
               responsive="sm"

      >
        <template v-slot:[initStudentAssignmentCell(assignmentIndex+1)]="data"
                  v-for="assignmentIndex in assignmentsArray">
          <span v-html="data.value" v-on:click="openStudentAssignmentModal(data.value,data.item.userId, data.field.key)">{{ data.value}}</span>
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
        extension_time: '',
        score: null
      }),
      sortBy: 'name',
      sortDesc: false,
      courseId: '',
      fields: [],
      scores: [],
      items: [],
      hasAssignments: true,
      studentUserId: 0,
      assignmentId: 0,
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
      this.getScores();
    },
    methods: {
      submitUpdateAssignmentByStudent(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.updateAssignmentByStudent()
      },
      updateAssignmentByStudent() {
        let isUpdateScore = (this.form.score !== null)
        let isGiveExtension = (this.form.extension_date !== '')
        if (isUpdateScore && isGiveExtension) {
          this.$noty.error("Please either give an extension or provide an override score, but not both.")
          this.form.score = null
          this.form.extension_date = ''
          return false
        }
        if (!(isUpdateScore || isGiveExtension)) {

          this.$noty.error("Please either give an extension or provide an override score.")
        }
        if (isUpdateScore) {
          this.updateScore(this.studentUserId, this.assignmentId)
        }
        if (isGiveExtension) {
          this.giveExtension(this.studentUserId, this.assignmentId)
        }
      },
      async updateScore(studentUserId, assignmentId) {
        let updateInfo = {
          'course_id': this.courseId,
          'assignment_id': assignmentId,
          'student_user_id': studentUserId,
          'score': this.form.score
        }
        console.log(updateInfo)
        try {
          const {data} = await axios.patch(`/api/assignments/scores`, updateInfo)
          this.$noty[data.type](data.message)
          await this.getScores()
          if (data.type === 'success') {
            this.resetAll('modal-update-student-assignment')
          }
        } catch (error) {
          console.log(error)
        }
      },
      async giveExtension(studentUserId, assignmentId) {
        this.form.course_id = this.courseId
        this.form.assignment_id = assignmentId
        this.form.student_user_id = studentUserId
        try {
          const {data} = await this.form.patch(`/api/assignments/extensions`)
          console.log(data)
          this.$noty[data.type](data.message)
          await this.getScores()
          if (data.type === 'success') {
            this.resetAll('modal-update-student-assignment')
          }
        } catch (error) {
         console.log(error)
        }

      },
      resetAll(modalId) {
        this.resetModalForms()
        // Hide the modal manually
        this.$nextTick(() => {
          this.$bvModal.hide(modalId)
        })
      },
      resetModalForms() {
        this.form.extension_date = ''
        this.form.extension_time = ''
        this.form.score = null
        this.form.errors.clear()
      },
      initStudentAssignmentCell(key) {
        return `cell(${key})`; // simple string interpolation
      },
     async openStudentAssignmentModal(value, studentUserId, assignmentId) {
        this.studentUserId = studentUserId
        this.assignmentId = assignmentId
        if (value === 'Extension'){
          const {data} = await axios.get(`/api/assignments/extensions/${this.assignmentId}/${this.studentUserId}`)
          if (data.type === 'success'){
            this.form.extension_date = data.extension_date
            this.form.extension_time = data.extension_time
          }
        }
        //get the score and assignment info

        this.$bvModal.show('modal-update-student-assignment')

      },
      async getScores() {

        try {
          const {data} = await axios.get(`/api/courses/${this.courseId}/scores`)
          console.log(data)
          if (data.hasAssignments) {
            this.items = data.rows
            this.fields = data.fields  //Name
            //create an array 0 up through the top assignment number index
            this.assignmentsArray = [...Array(this.fields.length).keys()]
          } else {
            this.hasAssignments = false
          }


        } catch (error) {
          alert(error.message)
        }
      }

    }
  }
</script>
