<template>
  <div>
    <PageTitle v-if="canViewAssignments" title="Assignments"></PageTitle>
    <div v-if="user.role === 2">
      <div class="row mb-4 float-right" v-if="canViewAssignments">
        <b-button variant="primary" v-b-modal.modal-assignment-details>Add Assignment</b-button>
      </div>
    </div>
    <b-modal
      id="modal-assignment-details"
      ref="modal"
      title="Assignment Details"
      @ok="submitAssignmentInfo"
      @hidden="resetModalForms"
      ok-title="Submit"
      size="lg"
    >
      <b-form ref="form" @submit="createAssignment">
        <b-form-group
          id="name"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Name"
          label-for="name"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-input
                id="name"
                v-model="form.name"
                lg="7"
                type="text"
                :class="{ 'is-invalid': form.errors.has('name') }"
                @keydown="form.errors.clear('name')"
              >
              </b-form-input>
              <has-error :form="form" field="name"></has-error>
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          id="available_from"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Available on"
          label-for="Available on"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                v-model="form.available_from_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('available_from_date') }"
                v-on:shown="form.errors.clear('available_from_date')">
              </b-form-datepicker>
              <has-error :form="form" field="available_from_date"></has-error>
            </b-col>
            <b-col>
              <b-form-timepicker v-model="form.available_from_time"
                                 locale="en"
                                 :class="{ 'is-invalid': form.errors.has('available_from_time') }"
                                 v-on:shown="form.errors.clear('available_from_time')">

              </b-form-timepicker>
              <has-error :form="form" field="available_from_time"></has-error>
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          id="due"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Due Date"
          label-for="Due Date"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                v-model="form.due_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('due_date') }"
                v-on:shown="form.errors.clear('due_date')">
              </b-form-datepicker>
              <has-error :form="form" field="due_date"></has-error>
            </b-col>
            <b-col>
              <b-form-timepicker v-model="form.due_time"
                                 locale="en"
                                 :class="{ 'is-invalid': form.errors.has('due_time') }"
                                 v-on:shown="form.errors.clear('due_time')">
              </b-form-timepicker>
              <has-error :form="form" field="due_time"></has-error>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="submission_files"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Submission Files"
          label-for="Submission Files"
        >

          <b-form-radio-group v-model="form.submission_files" stacked>
            <b-form-radio name="submission_files" value="a">At the assignment level</b-form-radio>
            <b-form-radio name="submission_files" value="q">At the question level</b-form-radio>
            <b-form-radio name="submission_files" value="0">Students cannot upload files</b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <b-form-group
          id="default_points_per_question"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Points Per Question"
          label-for="number_of_points_per_question"
        >

          <b-form-row>
            <b-col lg="3">
              <b-form-input
                id="name"
                v-model="form.default_points_per_question"
                type="text"
                aria-describedby="number_of_points_per_question_help"
                placeholder=""
                :class="{ 'is-invalid': form.errors.has('default_points_per_question') }"
                @keydown="form.errors.clear('default_points_per_question')"
              >
              </b-form-input>
              <has-error :form="form" field="default_points_per_question"></has-error>
            </b-col>
          </b-form-row>

        </b-form-group>
      </b-form>
    </b-modal>

    <b-modal
      id="modal-delete-assignment"
      ref="modal"
      title="Confirm Delete Assignment"
      @ok="handleDeleteAssignment"
      @hidden="resetModalForms"
      ok-title="Yes, delete assignment!"

    >
      <p>By deleting the assignment, you will also delete all student scores associated with the assignment.</p>
      <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
    </b-modal>
    <div v-if="hasAssignments">
      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <span class="pr-1" v-on:click="getQuestions(data.item.id)"><b-icon icon="question-circle"></b-icon></span>
            <span class="pr-1" v-on:click="getStudentView(data.item.id)"><b-icon icon="eye"></b-icon></span>
            <span class="pr-1" v-on:click="getSubmissionFileView(data.item.id, data.item.submission_files)"> <b-icon
              icon="cloud-upload"></b-icon></span>
            <span v-if="user.role === 2">
            <span class="pr-1" v-on:click="editAssignment(data.item)"><b-icon icon="pencil"></b-icon></span>
            <b-icon icon="trash" v-on:click="deleteAssignment(data.item.id)"></b-icon>
              </span>
          </div>
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently
          has
          no assignments.</a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"
  import {mapGetters} from "vuex"


  const now = new Date()

  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  let formatDateAndTime = value => {
    let date = new Date(value)
    return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' ' + date.toLocaleTimeString()
  }


  export default {
    middleware: 'auth',
    computed: mapGetters({
      user: 'auth/user'
    }),
    data: () => ({
      assignmentId: false, //if there's a assignmentId it's an update
      assignments: [],
      completedOrCorrectOptions: [
        {item: 'correct', name: 'correct'},
        {item: 'completed', name: 'completed'}
      ],
      courseId: false,
      fields: [
        'name',
        {
          key: 'available_from',
          formatter: value => {
            return formatDateAndTime(value)
          }
        },
        {
          key: 'due',
          formatter: value => {
            return formatDateAndTime(value)
          }
        },
        {
          key: 'number_of_questions',
          tdClass: 'td-center'
        },
        'actions'
      ],
      form: new Form({
        name: '',
        available_from_date: '',
        available_from_time: '09:00:00',
        due_date: '',
        due_time: '09:00:00',
        submission_files: '0',
        type_of_submission: 'correct',
        num_submissions_needed: '2'
      }),
      hasAssignments: false,
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      defaultPointsPointsPerQuestion: '',
      canViewAssignments: false,
      showNoAssignmentsAlert: false,
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments();

    },
    methods: {
      editAssignment(assignment) {
console.log(assignment)
        this.assignmentId = assignment.id
        this.form.name = assignment.name
        this.form.available_from_date = assignment.available_from_date
        this.form.available_from_time = assignment.available_from_time
        this.form.due_date = assignment.due_date
        this.form.due_time = assignment.due_time
        this.form.type_of_submission = assignment.type_of_submission
        this.form.submission_files = assignment.submission_files
        this.form.num_submissions_needed = assignment.num_submissions_needed
        this.$bvModal.show('modal-assignment-details')
      }
      ,
      getQuestions(assignmentId) {
        this.$router.push(`/assignments/${assignmentId}/questions/get`)
      }
      ,
      getStudentView(assignmentId) {
        this.$router.push(`/assignments/${assignmentId}/questions/view`)
      }
      ,
      getSubmissionFileView(assignmentId, submissionFiles) {
        if (submissionFiles === 0) {
          this.$noty.info('If you would like students to upload files as part of the assignment, please edit this assignment.')
          return false
        }
        let type
        switch (submissionFiles) {
          case('q'):
            type = 'question'
            break
          case('a'):
            type = 'assignment'
            break
        }

        this.$router.push(`/assignments/${assignmentId}/${type}-files`)
      }
      ,
      async getAssignments() {
        try {
          const {data} = await axios.get(`/api/assignments/courses/${this.courseId}`)
          console.log(data)
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
          this.canViewAssignments = true
          this.hasAssignments = data.length > 0
          this.showNoAssignmentsAlert = !this.hasAssignments
          this.assignments = data

        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      ,
      async handleDeleteAssignment() {
        try {
          const {data} = await axios.delete(`/api/assignments/${this.assignmentId}`)
          this.$noty[data.type](data.message)
          this.resetAll('modal-delete-assignment')
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      ,
      submitAssignmentInfo(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        !this.assignmentId ? this.createAssignment() : this.updateAssignment()
      }
      ,
      deleteAssignment(assignmentId) {
        this.assignmentId = assignmentId
        this.$bvModal.show('modal-delete-assignment')
      }
      ,
      async updateAssignment() {

        try {

          const {data} = await this.form.patch(`/api/assignments/${this.assignmentId}`)

          console.log(data)
          this.$noty[data.type](data.message)
          this.resetAll('modal-assignment-details')

        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
        }
      }
      ,
      async createAssignment() {
        try {
          this.form.course_id = this.courseId
          const {data} = await this.form.post(`/api/assignments`)

          console.log(data)
          this.$noty[data.type](data.message)
          this.resetAll('modal-assignment-details')

        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
        }
      }
      ,
      resetAll(modalId) {
        this.getAssignments()
        this.resetModalForms()
        // Hide the modal manually
        this.$nextTick(() => {
          this.$bvModal.hide(modalId)
        })
      }
      ,
      resetModalForms() {
        this.form.name = ''
        this.form.available_from_date = ''
        this.form.available_from_time = '09:00:00'
        this.form.due_date = ''
        this.form.due_time = '09:00:00'
        this.form.type_of_submission = 'correct'
        this.form.num_submissions_needed = '2'
        this.form.submission_files = '0'
        this.assignmentId = false
        this.form.errors.clear()
      }
      ,
      metaInfo() {
        return {title: this.$t('home')}
      }
    }
  }
</script>
<style>
.td-center {
  text-align: center;
}
</style>
