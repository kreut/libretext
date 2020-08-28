<template>
  <div>
    <PageTitle v-if="canViewAssignments" title="Assignments"></PageTitle>
    <b-modal
      id="modal-upload-assignment-file"
      ref="modal"
      title="Upload File"
      @ok="handleOk"
      @hidden="resetModalForms"
      ok-title="Submit"

    >
      <b-form ref="form">
        <b-form-file
          ref="assignmentFileInput"
          v-model="form.assignmentFile"
          placeholder="Choose a .pdf file or drop it here..."
          drop-placeholder="Drop file here..."
          accept=".pdf"
        ></b-form-file>
        <div v-if="uploading">
          <b-spinner small type="grow"></b-spinner>
          Uploading file...
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
      title="Assignment Submission Feedback"
      @ok="closeAssignmentSubmissionFeedbackModal"
      ok-only
      ok-variant="primary"
      ok-title="Close"

    >
      <b-card title="Summary">
        <b-card-text>
          <p>
            Submitted File:
            <b-button variant="link" style="padding:0px; padding-bottom:3px"
                      v-on:click="downloadSubmission(assignmentFileInfo.assignment_id, assignmentFileInfo.submission, assignmentFileInfo.original_filename, $noty)">
              {{this.assignmentFileInfo.original_filename}}
            </b-button>
            <br>
            Score: {{this.assignmentFileInfo.score}}<br>
            Date submitted: {{this.assignmentFileInfo.date_submitted}}<br>
            Date graded: {{this.assignmentFileInfo.date_graded}}<br>
            Text feedback: {{this.assignmentFileInfo.text_feedback}}<br>
          <hr>

        </b-card-text>
      </b-card>
      <div v-if="assignmentFileInfo.file_feedback_url">
        <div class="d-flex justify-content-center mt-5">
          <iframe width="600" height="600" :src="this.assignmentFileInfo.file_feedback_url"></iframe>
        </div>
      </div>
    </b-modal>


    <div v-if="hasAssignments">
      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <div v-show="data.item.is_available">
              <a href="" v-on:click.prevent="getStudentView(data.item.id)">{{ data.item.name }}</a>
            </div>
            <div v-show="!data.item.is_available">
              {{ data.item.name }}
            </div>
          </div>
        </template>
        <template v-slot:cell(files)="data">
          <div v-if="data.item.submission_files === 'a'">
            <b-icon icon="cloud-upload" class="mr-2" v-on:click="openUploadAssignmentFileModal(data.item.id)"
                    v-b-modal.modal-upload-assignment-file></b-icon>
            <b-icon icon="pencil-square" v-on:click="getAssignmentFileInfo(data.item.id)"
            ></b-icon>
          </div>
          <div v-else>
            N/A
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
  import {downloadSubmission} from '~/helpers/SubmissionFiles'
  import {submitUploadFile} from '~/helpers/UploadFiles'

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
        assignmentId: null
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
            let dateAndTime = formatDateAndTime(value.due_date)
            let extension = value.is_extension ? '(Extension)' : ''

            return dateAndTime + ' ' + extension
          }
        },
        'credit_given_if_at_least',
        'files'
      ],
      hasAssignments: false,
      showNoAssignmentsAlert: false,
      canViewAssignments: false
    }),
    created() {
      this.downloadSubmission = downloadSubmission
      this.submitUploadFile = submitUploadFile
    },
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments()
    },
    methods: {

      closeAssignmentSubmissionFeedbackModal() {
        this.$nextTick(() => {
          this.$bvModal.hide('modal-assignment-submission-feedback')
        })
      },
      async getAssignmentFileInfo(assignmentId) {
        try {
          const {data} = await axios.get(`/api/assignment-files/assignment-file-info-by-student/${assignmentId}`)
          this.assignmentFileInfo = data.assignment_file_info
          if (!this.assignmentFileInfo) {
            this.$noty.info("You can't have any feedback if you haven't submitted a file!")
            return false
          }
          this.assignmentFileInfo = data.assignment_file_info

          this.$root.$emit('bv::show::modal', 'modal-assignment-submission-feedback');
          if (data.type === 'error') {
            this.$noty.error(data.message)
            this.$nextTick(() => {
              this.$bvModal.hide('modal-assignment-submission-feedback')
            })
            return false
          }
        } catch (error) {
          if (error.message.includes('status code 413')) {
            error.message = 'The maximum size allowed is 10MB.'
          }
          this.$noty.error(error.message)

        }
        //get the text comments
        //get the score
        //the the temporary url of the feedback
        //get the download url of your current submission


      },
      async handleOk(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        this.uploading = true
        await this.submitUploadFile('assignment',this.form, this.$noty, this.$refs, this.$nextTick, this.$bvModal)
        this.uploading = false
      },

      resetModalForms() {
        // alert('reset modal')
      },
      openUploadAssignmentFileModal(assignmentId) {
        this.form.errors.clear('assignmentFile')
        this.form.assignmentId = assignmentId
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
