<template>
  <div>
    <div class="row mb-4 float-right">
      <b-button variant="primary" v-b-modal.modal-assignment-details>Add Assignment</b-button>
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
        <b-form-row>
          <b-col lg="5">Give students assignment credit if at least</b-col>
          <b-col lg="1">
            <b-form-select v-model="form.num_submissions_needed"
                           :options="numSubmissionsNeeded"
                           class="mb-3"
                           value-field="item"
                           text-field="name"
                           disabled-field="notEnabled">
            </b-form-select>
          </b-col>
          <b-col lg="4" class="d-flex justify-content-center">
            of the submitted responses are
          </b-col>
          <b-col lg="2">
            <b-form-select v-model="form.type_of_submission"
                           :options="completedOrCorrectOptions"
                           class="mb-3"
                           value-field="item"
                           text-field="name"
                           disabled-field="notEnabled">
            </b-form-select>
          </b-col>
        </b-form-row>
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
      <p>By deleting the assignment, you will also delete all student grades associated with the assignment.</p>
      <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
    </b-modal>
    <div v-if="hasAssignments">
      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <span class="pr-1" v-on:click="showAssignments(data.item.id)"><b-icon icon="question-circle"></b-icon></span>
            <span class="pr-1" v-on:click="showAssignments(data.item.id)"><b-icon icon="eye"></b-icon></span>
            <span class="pr-1" v-on:click="editAssignment(data.item)"><b-icon icon="pencil"></b-icon></span>
            <b-icon icon="trash" v-on:click="deleteAssignment(data.item.id)"></b-icon>
          </div>
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
      <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently has
        no assignments.</a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform";

  const now = new Date()
  let numSubmissionsNeeded = []
  for (let numSubmission of ['2', '3', '4', '5', '6', '7', '8', '9']) {
    numSubmissionsNeeded.push({item: numSubmission, name: numSubmission})
  }


  export default {
    middleware: 'auth',
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
        'available_from',
        'due',
        'credit_given_if_at_least',
        'actions'
      ],
      form: new Form({
        name: '',
        available_from_date: '',
        available_from_time: '09:00:00',
        due_date: '',
        due_time: '09:00:00',
        type_of_submission: 'correct',
        num_submissions_needed: '2'
      }),
      hasAssignments: false,
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      numSubmissionsNeeded: numSubmissionsNeeded,
      showNoAssignmentsAlert: false,
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments();

    },
    methods: {
    editAssignment(assignment) {

      this.assignmentId = assignment.id
      this.form.name = assignment.name
      this.form.available_from_date = assignment.available_from_date
      this.form.available_from_time = assignment.available_from_time
      this.form.due_date = assignment.due_date
      this.form.due_time = assignment.due_time
      this.form.type_of_submission = assignment.type_of_submission
      this.form.num_submissions_needed = assignment.num_submissions_needed
      this.$bvModal.show('modal-assignment-details')
    },
      getAssignments() {
        try {
          axios.get(`/api/courses/${this.courseId}/assignments`).then(
            response => {
              this.hasAssignments = response.data.length > 0
              this.showNoAssignmentsAlert = !this.hasAssignments;
              this.assignments = response.data
            }
          )
        } catch (error) {
          alert(error.response)
        }
      },
      async handleDeleteAssignment() {
         try {
           const {data} = await axios.delete(`/api/courses/${this.courseId}/assignments/${this.assignmentId}`)
           this.$noty[data.type](data.message)
           this.resetAll('modal-delete-assignment')
         } catch (error) {
           console.log(error)
         }
      },
      submitAssignmentInfo(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.createAssignment()
      },
      deleteAssignment(assignmentId) {
        this.assignmentId = assignmentId
        this.$bvModal.show('modal-delete-assignment')
      },
      async createAssignment() {

        try {
          let endpoint = `/api/courses/${this.courseId}/assignments`
          if (this.assignmentId) { //it's an update
            endpoint += `/${this.assignmentId}`
          }
          const {data} = await this.form.post(endpoint)

          console.log(data)
          this.$noty[data.type](data.message)
          this.resetAll('modal-assignment-details')

        } catch (error) {
          console.log(error)
        }
      },
      resetAll(modalId) {
        this.getAssignments()
        this.resetModalForms()
        // Hide the modal manually
        this.$nextTick(() => {
          this.$bvModal.hide(modalId)
        })
      },
      resetModalForms() {
        this.form.name = ''
        this.form.available_from_date = ''
        this.form.available_from_time = ''
        this.form.due_date = ''
        this.form.due_time = ''
        this.form.type_of_submission = 'correct'
        this.form.num_submissions_needed = '2'
        this.assignmentId = false
        this.form.errors.clear()
      },
      metaInfo() {
        return {title: this.$t('home')}
      }
    }
  }
</script>
