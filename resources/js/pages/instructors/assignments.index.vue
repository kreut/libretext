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
      id="modal-delete-assignment"
      ref="modal"
      title="Yes, delete course!"
      @ok="handleDeleteAssignment"
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
