<template>
  <b-container>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="`modal-form-errors-file-upload-${modalId}`"/>
    <b-modal id="modal-question-media-url"
             :title="`Copy URL for ${questionMediaUpload.name}`"
             size="lg"
    >
      <div v-if="questionMediaUpload.url">
        <p>
          Please copy the following URL, then create a link inside the question editor.
          When viewing the question outside of the editor, the audio-video player will stream the contents:
        </p>

        <p class="text-center">
          <span id="copy-question-media-url">{{ questionMediaUpload.url }}</span>
        </p>
      </div>
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="doCopy('copy-question-media-url', 'Successfully copied!  You may add this URL to a link in your question.');$bvModal.hide('modal-question-media-url')"
        >
          Copy URL
        </b-button>
      </template>
    </b-modal>
    <b-row>
      <file-upload
        ref="questionMediaUpload"
        v-model="files"
        class="btn btn-primary btn-sm"
        accept=".mp3,.mp4,.ogg,.vtt,.webm"
        put-action="/put.method"
        @input-file="inputFile"
        @input-filter="inputQuestionMediaFilter"
      >
        Select Audio or Video file
      </file-upload>
    </b-row>
    <b-row class="upload mt-3 ml-1">
      <div v-if="files.length && (preSignedURL !== '')">
        <div v-for="file in files" :key="file.id">
          File to upload:
          <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
              file.name
            }}</span> -
          <span>{{ formatFileSize(file.size) }} </span>
          <b-button
            v-if="!processingFile && (preSignedURL !== '') && (!$refs.questionMediaUpload || !$refs.questionMediaUpload.active)"
            variant="info"
            size="sm"
            style="vertical-align: top"
            :disabled="disableQuestionMediaStartUpload"
            @click.prevent="initStartUpload"
          >
            Upload
          </b-button>
          <span v-else-if="file.active" class="ml-2 text-info">
            <b-spinner small type="grow"/>
            Uploading File...
          </span>
          <span v-if="processingFile && !file.active" class="text-info">
            <b-spinner
              small
              type="grow"
            />
            {{ processingFileMessage }}
          </span>
          <div v-if="file.error" class="text-danger">
            Error: {{ file.error }}
          </div>
        </div>
      </div>
    </b-row>
    <b-progress v-if="preSignedURL" max="100" class="mt-2 mb-3">
      <b-progress-bar :value="progress" :label="`${Number(progress).toFixed(0)}%`" show-progress animated/>
    </b-progress>
    <b-row v-show="questionMediaErrorMessage" class="mb-3">
      <ErrorMessage :message="questionMediaErrorMessage"/>
    </b-row>
  </b-container>
</template>
<script>
import axios from 'axios'
import { formatFileSize } from '~/helpers/UploadFiles'
import ErrorMessage from './ErrorMessage.vue'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import AllFormErrors from './AllFormErrors.vue'
import { v4 as uuidv4 } from 'uuid'
import Vue from 'vue'
import { doCopy } from '~/helpers/Copy'

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)
export default {
  name: 'QuestionMediaUpload',
  components: {
    ErrorMessage,
    AllFormErrors
  },
  data: () => ({
    allFormErrors: [],
    questionMediaUpload: {},
    progress: 0,
    questionMediaErrorMessage: '',
    processingFile: false,
    processingFileMessage: '',
    disableQuestionMediaStartUpload: false,
    files: [],
    preSignedURL: '',
    modalId: ''
  }),
  mounted () {
    this.modalId = uuidv4()
  },
  methods: {
    formatFileSize,
    fixInvalid,
    doCopy,
    initStartUpload () {
      this.processingFileMessage = ''
      this.allFormErrors = []
      this.$refs.questionMediaUpload.active = true
    },
    async inputQuestionMediaFilter (newFile, oldFile, prevent) {
      this.questionMediaErrorMessage = ''
      this.errorMessages = []
      if (newFile && !oldFile) {
        if (parseInt(newFile.size) > 30000000) {
          this.questionMediaErrorMessage = '30 MB max allowed.  Your file is too large.'
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = [this.questionMediaErrorMessage]
          this.$bvModal.show(`modal-form-errors-file-upload-${this.modalId}`)
          return prevent()
        }
        const acceptedExtensionsRegex = /\.(mp3|mp4|ogg|vtt|webm)$/i
        const validExtension = acceptedExtensionsRegex.test(newFile.name)
        if (!validExtension) {
          this.questionMediaErrorMessage = `${newFile.name} does not have a valid extension.`
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = [this.questionMediaErrorMessage]
          this.$bvModal.show(`modal-form-errors-file-upload-${this.modalId}`)
          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              upload_file_type: 'question-media',
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
            newFile.question_media_filename = data.question_media_filename
            newFile.temporary_url = data.temporary_url
            this.handledOK = false
          } catch (error) {
            this.$noty.error(error.message)
            return false
          }
        }
      }

      // Create a blob field
      this.progress = 0
      newFile.blob = ''
      let URL = window.URL || window.webkitURL
      if (URL && URL.createObjectURL) {
        newFile.blob = URL.createObjectURL(newFile.file)
      }
      console.log(newFile.blob)
    },
    inputFile (newFile, oldFile) {
      console.log(newFile)
      this.progress = newFile ? newFile.progress : 0

      if (newFile && oldFile && !newFile.active && oldFile.active) {
        // Get response data
        if (newFile.xhr) {
          //  Get the response status code
          console.log('status', newFile.xhr.status)
          if (newFile.xhr.status === 200) {
            if (!this.handledOK) {
              this.handledOK = true
              console.log(this.handledOK)
              console.log(newFile)
              this.disableQuestionMediaStartUpload = true
              this.handleOK(newFile)
            }
          } else {
            this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
          }
        } else {
          this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
        }
      }
    },
    handleOK (newFile) {
      this.preSignedURL = ''
      this.disableQuestionMediaStartUpload = false
      this.questionMediaUpload = newFile
      this.questionMediaUpload.url = window.location.origin + `/question-media-player/${this.questionMediaUpload.question_media_filename}`
      this.$bvModal.show('modal-question-media-url')
    }
  }
}
</script>
