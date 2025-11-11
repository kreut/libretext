<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-submit-work'"/>
    <b-modal title="Confirm Delete Work"
             id="modal-confirm-delete-submitted-work"
             size="md"
    >
      Are you sure that you would like to delete your submitted work? Note that your auto-graded submission will remain
      unaffected
      by this action.
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-delete-submitted-work')"
        >
          Cancel
        </b-button>
        <b-button
          size="sm"
          variant="danger"
          class="float-right"
          @click="deleteSubmittedWork"
        >
          Delete
        </b-button>
      </template>
    </b-modal>
    <b-tooltip target="submit-work"
               triggers="hover"
               delay="500"
    >
      <span v-if="!currentSubmittedWorkAt">
       Optionally, upload supporting work after submitting your answer. Your instructor may review it when grading this question.
      </span>
      <span v-if="currentSubmittedWorkAt">
        Your instructor may take your supporting work into account while grading
      this question.  You last submitted supporting work on<br>{{ currentSubmittedWorkAt }}.
      </span>
    </b-tooltip>
    <span id="submit-work" class="d-inline-block" tabindex="0">
    <b-button
              size="sm"
              :disabled="disabled"
              :variant="submittedWorkUrl ? 'success' : 'info'"
              @click="initShowSubmitWorkModal()"
    >Submit<span v-show="submittedWorkUrl">ted</span> Work
    </b-button>
    </span>
    <b-modal id="modal-submit-work"
             title="Submit Work"
             size="lg"
             @hidden="updateSubmittedWork"
    >
      <b-alert variant="info" :show="!submitButtonActive">You may no longer submit supporting work.</b-alert>
      <div v-if="submitButtonActive">
      <file-upload
        ref="submittedWorkFiles"
        v-model="submittedWorkFiles"
        class="btn btn-outline-primary btn-sm"
        accept=".pdf,.jpeg,.jpg,.png,.heic,.gif,.webp"
        put-action="/put.method"
        @input-file="inputFile"
        @input-filter="inputFilter"
      >
        Select a PDF or image
      </file-upload>
      <b-button v-if="submittedWorkUrl"
                variant="danger"
                size="sm"
                @click="$bvModal.show('modal-confirm-delete-submitted-work')"
      >Delete
      </b-button>
      </div>
      <b-row class="upload mt-3 ml-1">
        <div v-if="submittedWorkFiles.length && (preSignedURL !== '')">
          <div v-for="file in submittedWorkFiles" :key="file.id">
            File to upload:
            <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
                file.name
              }}</span> -
            <span>{{ formatFileSize(file.size) }} </span>
            <b-button
              v-if="(preSignedURL !== '')"
              variant="info"
              size="sm"
              style="vertical-align: top"
              @click.prevent="$refs.submittedWorkFiles.active = true"
            >
              Upload
            </b-button>
            <span v-else-if="file.active" class="ml-2 text-info">
                <b-spinner small type="grow"/>
                Uploading File...
              </span>
            <div v-if="file.error" class="text-danger">
              Error: {{ file.error }}
            </div>
          </div>
        </div>
      </b-row>
      <b-embed
        v-if="submittedWorkUrl"
        :key="submittedWorkUrl"
        v-resize="{ log: false, checkOrigin: false }"
        width="100%"
        :src="submittedWorkUrl"
        allowfullscreen
      />
      <template #modal-footer>
        <b-button
          size="sm"
          variant="primary"
          class="float-right"
          @click="$bvModal.hide('modal-submit-work')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import { getAcceptedFileTypes, submitUploadFile, formatFileSize } from '~/helpers/UploadFiles'
import axios from 'axios'
import { fixInvalid } from '../helpers/accessibility/FixInvalid'
import Vue from 'vue'
import AllFormErrors from './AllFormErrors.vue'
import Form from 'vform'

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)
export default {
  name: 'SubmitWork',
  components: { AllFormErrors, FileUpload: VueUploadComponent },
  props: {
    disabled: {
      type: Boolean,
      default: false
    },
    submittedWork: {
      type: String,
      default: null
    },
    submittedWorkAt: {
      type: String,
      default: null
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    },
    userId: {
      type: Number,
      default: 0
    },
    submitButtonActive: {
      type: Boolean,
      default: false
    },
  },
  data: () => ({
    submittedWorkForm: new Form({
      assignment_id: null,
      question_id: null
    }),
    submittedWorkFiles: [],
    progress: 0,
    preSignedURL: '',
    allFormErrors: [],
    handledOK: false,
    submittedWorkUrl: '',
    currentSubmittedWorkAt: null
  }),
  mounted () {
    this.submittedWorkForm.assignmentId = this.assignmentId
    this.submittedWorkForm.questionId = this.questionId
    this.submittedWorkUrl = this.submittedWork
    this.currentSubmittedWorkAt = this.submittedWorkAt
  },
  methods: {
    getAcceptedFileTypes,
    submitUploadFile,
    formatFileSize,
    updateSubmittedWork () {
      this.$emit('updateSubmittedWork', {
        submittedWorkUrl: this.submittedWorkUrl,
        submittedWorkAt: this.currentSubmittedWorkAt
      })
    },
    async deleteSubmittedWork () {
      try {
        const { data } = await axios.delete(`/api/submissions/assignments/${this.assignmentId}/questions/${this.questionId}/submitted-work`)
        this.$noty[data.type](data.message)
        this.submittedWorkUrl = null
        this.currentSubmittedWorkAt = null
        if (data.type === 'info') {
          this.$bvModal.hide('modal-confirm-delete-submitted-work')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initSubmitWork () {
      this.submittedWorkFiles = []
      this.preSignedURL = ''
      this.handledOK = false
    },
    initShowSubmitWorkModal () {
      this.initSubmitWork()
      this.submittedWorkFiles = []
      this.preSignedURL = ''
      this.handledOK = false
      this.$bvModal.show('modal-submit-work')
    },
    inputFile (newFile, oldFile) {
      if (newFile && oldFile && !newFile.active && oldFile.active) {
        // Get response data
        this.progress = newFile ? newFile.progress : 0
        if (newFile.xhr) {
          //  Get the response status code
          // console.log('status', newFile.xhr.status)
          if (newFile.xhr.status === 200) {
            if (!this.handledOK) {
              this.handledOK = true
              // console.log(this.handledOK)
              this.handleOK()
            }
          } else {
            this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
          }
        } else {
          this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
        }
      }
    },
    async handleOK () {
      try {
        const { data } = await axios.patch(`/api/submissions/assignments/${this.assignmentId}/questions/${this.questionId}/submit-work`,
          { submitted_work: this.s3Key })
        this.$noty[data.type](data.message)

        if (data.type === 'success') {
          this.submittedWorkUrl = data.submitted_work_url
          this.currentSubmittedWorkAt = data.submitted_work_at
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.initSubmitWork()
    },
    async inputFilter (newFile, oldFile, prevent) {
      this.submittedWorkForm.errors.clear()
      if (newFile && !oldFile) {
        // Filter non-image file
        if (parseInt(newFile.size) > 20000000) {
          let message = '20 MB max allowed.  Your file is too large.  '
          this.submittedWorkForm.errors.set(this.uploadFileType, message)

          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.submittedWorkForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-file-upload')

          return prevent()
        }
        let validUploadTypesMessage = `The valid upload types are: .pdf,.jpeg,.jpg,.png,.heic,.gif,.webp`
        const validExtension = /\.(pdf|jpeg|jpg|png|heic|gif|webp)$/i.test(newFile.name)

        if (!validExtension) {
          this.submittedWorkForm.errors.set(this.uploadFileType, validUploadTypesMessage)
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.submittedWorkForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-submit-work')
          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              assignment_id: this.assignmentId,
              question_id: this.questionId,
              upload_file_type: 'submitted-work',
              file_name: newFile.name
            }
            const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)
            if (data.type === 'error') {
              this.$noty.error(data.message)
              return false
            }
            this.preSignedURL = data.preSignedURL
            newFile.putAction = this.preSignedURL

            this.s3Key = data.s3_key
            this.originalFilename = newFile.name
            this.handledOK = false
          } catch (error) {
            this.$noty.error(error.message)
            return false
          }
        }
      }

      // Create a blob field
      newFile.blob = ''
      let URL = window.URL || window.webkitURL
      if (URL && URL.createObjectURL) {
        newFile.blob = URL.createObjectURL(newFile.file)
      }

    }
  }
}
</script>
