<template>
  <div>
    <div class="row mb-4 float-right">
      <b-button v-b-modal.modal-assignment-details>Add Assigment</b-button>
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
          id="available_on"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Available on"
          label-for="Available on"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                v-model="form.available_on_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('available_on_date') }"
                v-on:shown="form.errors.clear('available_on_date')">
              </b-form-datepicker>
              <has-error :form="form" field="available_on_date"></has-error>
            </b-col>
            <b-col>
              <b-form-timepicker v-model="available_on_time" locale="en"></b-form-timepicker>
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          id="due_date"
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
              <b-form-timepicker v-model="due_time" locale="en"></b-form-timepicker>
              <has-error :form="form" field="due_time"></has-error>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-row>
          <b-col lg="5">Mark an assignment is completed if at least</b-col>
          <b-col lg="1">
            <b-form-select  v-model="numQuestions"
                          :options="numQuestionsOptions"
                          class="mb-3"
                          value-field="item"
                          text-field="name"
                          disabled-field="notEnabled">
          </b-form-select>
          </b-col>
      <b-col lg="2">
        questions are </b-col>
          <b-col lg="3">  <b-form-select  v-model="completedOrCorrect"
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
      title="Yes, delete Assignment!"
      @ok="handleDeleteAssignment"
      @hidden="resetModalForms"
      ok-title="Yes, delete assignment!"

    >
      <p>By deleting the assignment, you will also delete all student grades associated with the assignment</p>
      <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
    </b-modal>
    <div v-if="hasAssignments">
      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(name)="data">
          <a :href="`/assignments/${data.item.id}`">{{ data.item.name }}</a>
        </template>
      </b-table>
    </div>
    <div v-else>
      <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently has
        no assignments.</a></b-alert>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform";

  const now = new Date()
  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {key: 'name', label: 'Assignment'},
        'available_on',
        'due_date'
      ],
      assignments: [],
      available_on_date: '',
      available_on_time: '',
      due_date: '',
      due_time: '',
      numQuestions: '2',
      numQuestionsOptions: [
        { item: '2', name: '2' },
        { item: '3', name: '3' },
        { item: '4', name: '4' },
        { item: '5', name: '5' },
        { item: '6', name: '6' }],
      completedOrCorrect: 'completed',
      completedOrCorrectOptions: [
        { item: 'completed', name: 'completed' },
        { item: 'completed', name: 'cgiorrect' }
      ],
      hasAssignments: false,
      showNoAssignmentsAlert: false,
      assignmentId: false, //if there's a assignmentId it's an update
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      form: new Form({
        name: '',
        start_date: '',
        end_date: ''
      })
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments();

    },
    methods: {
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
        alert('delete assignment')
        /* try {
           const {data} = await axios.delete('/api/courses/' + this.courseId)
           this.$noty[data.type](data.message)
           this.resetAll('modal-delete-course')
         } catch (error) {
           console.log(error)
         }*/
      },
      submitAssignmentInfo(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.createAssignment()
      },
      async createAssignment() {
        alert('create assignment')

        /* try {
         let endpoint = (!this.courseId) ? '/api/courses' : '/api/courses/' + this.courseId
         const {data} = await this.form.post(endpoint)
         this.$noty[data.type](data.message)
         this.resetAll('modal-course-details')

       } catch (error) {
         console.log(error)
       }

     }*/
      },
      resetModalForms() {
        this.form.name = ''
        this.form.start_date = ''
        this.form.end_date = ''
        this.courseId = false
        this.form.errors.clear()
      },
      metaInfo() {
        return {title: this.$t('home')}
      }
    }
  }
</script>
