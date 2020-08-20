<template>
  <div>
    <PageTitle v-if="canViewAssignments" title="Assignments"></PageTitle>
    <div v-if="hasAssignments">
      <b-modal
        id="modal-upload-assignment-file"
        ref="modal"
        title="Upload File"
        @ok="handleOk"
        @hidden="resetModalForms"
        ok-title="Submit"

      >
        <b-form ref="form" @submit.stop.prevent="submitUploadAssignmentFile">
          <b-form-file
            ref="assignmentFileInput"
            v-model="form.assignmentFile"
            placeholder="Choose a .pdf file or drop it here..."
            drop-placeholder="Drop file here..."
            accept=".pdf"
          ></b-form-file>
          <div v-if="uploading">
            <b-spinner small type="grow"></b-spinner> Uploading file...
          </div>
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">{{ form.errors.get('assignmentFile')}}
          </div>

        </b-form>
      </b-modal>
      <b-modal
        id="modal-assignment-submission-feedback"
        ref="modal"
        size="xl"
        title="Assignment submission feedback"
        @ok="closeAssignmentSubmissionFeedbackModal"
        ok-only
        ok-variant="primary"
        ok-title="Close"

      >

        Score:  {{this.assignmentFileInfo.score}}<br>
        Date graded: {{this.assignmentFileInfo.date_graded}}<br>
        Text feedback: {{this.assignmentFileInfo.text_feedback}}<br>
        <iframe width="600"  height="600" :src="this.assignmentFileInfo.file_feedback_url"></iframe>
      </b-modal>

      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <a href="" v-on:click.prevent="getStudentView(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
        <template v-slot:cell(files)="data">
       <b-icon icon="cloud-upload" class="mr-2" v-on:click="openUploadAssignmentFileModal(data.item.id)" v-b-modal.modal-upload-assignment-file></b-icon>
          <b-icon icon="pencil-square"  v-on:click="getAssignmentFileInfo(data.item.id)" v-b-modal.modal-assignment-submission-feedback></b-icon>
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

  const now = new Date()

  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  let formatDateAndTime = value => {
    let date = new Date(value)
    return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' ' + date.toLocaleTimeString()
  }


  export default {
    middleware: 'auth',
    data: () => ({
      form: new Form({
        assignmentFile: null,
      }),
      assignmentFileInfo: {},
      uploading: false,
      assignments: [],
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
        'credit_given_if_at_least',
        'files'
      ],
      hasAssignments: false,
      showNoAssignmentsAlert: false,
      canViewAssignments: false
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments()
    },
    methods: {
      closeAssignmentSubmissionFeedbackModal(){
        this.$nextTick(() => {
          this.$bvModal.hide('modal-assignment-submission-feedback')
        })
      },
      async getAssignmentFileInfo(assignmentId){
        try {
          const {data} = await axios.get(`/api/assignment-files/assignment-file-info-by-student/${assignmentId}`)
          console.log(data)
          this.assignmentFileInfo = data.assignment_file_info
          if (data.type === 'error') {
            this.$noty.error(data.message)
            this.$nextTick(() => {
              this.$bvModal.hide('modal-assignment-submission-feedback')
            })
            return false
          }
        } catch (error) {
          if (error.message.includes('status code 413')){
            error.message = 'The maximum size allowed is 10MB.'
          }
          this.$noty.error(error.message)

        }
        //get the text comments
        //get the score
        //the the temporary url of the feedback
        //get the download url of your current submission







      },
      handleOk(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.submitUploadAssignmentFile()
      },
      async submitUploadAssignmentFile() {
        try {
          console.log(this.form)
          this.form.errors.set('assignmentFile', null)
          this.uploading = true
          //https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
          let formData = new FormData();
          formData.append('assignmentFile', this.form.assignmentFile)
          formData.append('assignmentId', this.assignmentId)
          formData.append('_method', 'put'); // add this
          const {data} = await axios.post('/api/assignment-files', formData)
          if (data.type === 'error') {
            this.form.errors.set('assignmentFile', data.message)
          } else {
            this.$noty.success(data.message)
            this.$nextTick(() => {
              this.$bvModal.hide('modal-upload-assignment-file')
            })
          }
        } catch (error) {
          if (error.message.includes('status code 413')){
            error.message = 'The maximum size allowed is 10MB.'
          }
          this.$noty.error(error.message)

        }
        this.uploading = false
        this.$refs['assignmentFileInput'].reset()

      },
      resetModalForms() {
        // alert('reset modal')
      },
      openUploadAssignmentFileModal(assignmentId) {
        this.assignmentId = assignmentId
      },
      getStudentView(assignmentId) {
        this.$router.push(`/assignments/${assignmentId}/questions/view`)
      },
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
          alert(error.response)
        }
      },
      metaInfo() {
        return {title: this.$t('home')}
      }
    }
  }
</script>
