<template>
  <b-container>
    <AllFormErrors :all-form-errors="allFormErrors"
                   :modal-id="`modal-form-errors-ckeditor-upload-${modalId}`"
    />
    <b-row>
      <b-modal id="modal-adding-files-to-ckeditor"
               size="xl"
               :title="modalTitle"
               no-close-on-backdrop
      >
        <div style="position: relative; padding-top: 65.26898734177216%;">
          <iframe
            :src="tutorialVideoSrc"
            loading="lazy"
            style="border: none; position: absolute; top: 0; left: 0; height: 100%; width: 100%;"
            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
            allowfullscreen="true"
          />
        </div>
        <template #modal-footer>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-adding-files-to-ckeditor')"
          >
            OK
          </b-button>
        </template>
      </b-modal>
      <div>{{buttonLabel}}
        <file-upload
          ref="CKEditorFileToLinkUploader"
          v-model="files"
          class="btn btn-primary btn-sm"
          :accept="'.pdf,.docx,.xlxs'"
          put-action="/put.method"
          @input-file="inputFile"
          @input-filter="inputUploadFileFilter"
        >
          Upload .pdf, .docx, or .xlxs file
        </file-upload>
        <QuestionCircleTooltipModal :aria-label="'Adding Files to Secondary Content'"
                                    :modal-id="'modal-adding-files-to-ckeditor'"
                                    :color-class="'font-bold'"
        />
      </div>
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
            v-if="(preSignedURL !== '') && (!$refs.CKEditorFileToLinkUploader || !$refs.CKEditorFileToLinkUploader.active)"
            variant="info"
            size="sm"
            style="vertical-align: top"
            :disabled="disableStartUpload"
            @click.prevent="initStartUpload('CKEditorFileToLinkUploader')"
          >
            Upload
          </b-button>
          <span v-else-if="file.active" class="ml-2 text-info">
            <b-spinner small type="grow"/>
            Uploading File...
          </span>
          <b-button size="sm"
                    style="vertical-align: top"
                    :disabled="disableStartUpload"
                    @click.prevent="cancelUpload"
          >
            Cancel
          </b-button>
          <div v-if="file.error" class="text-danger">
            Error: {{ file.error }}
          </div>
        </div>
      </div>
    </b-row>
    <b-progress v-if="preSignedURL" max="100" class="mt-2 mb-3">
      <b-progress-bar :value="progress" :label="`${Number(progress).toFixed(0)}%`" show-progress animated/>
    </b-progress>
    <b-row v-if="uploadFileErrorMessage" class="mb-3">
      <ErrorMessage :message="uploadFileErrorMessage"/>
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
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { getTooltipTarget } from '~/helpers/Tooptips'
import QuestionCircleTooltipModal from './QuestionCircleTooltipModal.vue'

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)
export default {
  name: 'QuestionMediaUpload',
  components: {
    QuestionCircleTooltipModal,
    ErrorMessage,
    AllFormErrors
  },
  props: {
    buttonLabel: {
      type: String,
      default: ''
    },
    uploadFileType: {
      type: String,
      default: ''
    },
    modalTitle: {
      type: String,
      default: ''
    },
    tutorialVideoSrc: {
      type: String,
      default: ''
    }
  },

  data: () => ({
    files2: [],
    copyIcon: faCopy,
    secondaryContentFileUploaderKey: 0,
    allFormErrors: [],
    CKEditorFileToLinkUploader: {},
    progress: 0,
    uploadFileErrorMessage: '',
    disableStartUpload: false,
    files: [],
    preSignedURL: '',
    modalId: ''
  }),
  mounted () {
    this.modalId = uuidv4()
  },
  methods: {
    getTooltipTarget,
    formatFileSize,
    fixInvalid,
    doCopy,
    cancelUpload () {
      this.files = []
      this.preSignedURL = ''
      this.progress = 0
      this.$noty.info('The upload has been canceled.')
    },
    timeToSeconds (time) {
      const [hours, minutes, seconds] = time.split(':').map(parseFloat)
      return (hours * 3600) + (minutes * 60) + seconds
    },
    initStartUpload (fileUploadRef) {
      this.allFormErrors = []
      this.$refs[fileUploadRef].active = true
    },
    async inputUploadFileFilter (newFile, oldFile, prevent) {
      this.uploadFileErrorMessage = ''
      this.errorMessages = []
      if (newFile && !oldFile) {
        if (parseInt(newFile.size) > 30000000) {
          this.uploadFileErrorMessage = '30 MB max allowed.  Your file is too large.'
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = [this.uploadFileErrorMessage]
          this.$bvModal.show(`modal-form-errors-ckeditor-upload-${this.modalId}`)
          return prevent()
        }
        let acceptedExtensionsRegex
        acceptedExtensionsRegex = /\.(pdf|xlxs|docx)$/i

        const validExtension = acceptedExtensionsRegex.test(newFile.name)
        if (!validExtension) {
          this.uploadFileErrorMessage = `${newFile.name} does not have a valid extension.`
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = [this.uploadFileErrorMessage]
          this.$bvModal.show(`modal-form-errors-ckeditor-upload-${this.modalId}`)
          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              upload_file_type: this.uploadFileType,
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
            this.temporary_url = data.temporary_url
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
              this.disableStartUpload = true
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
    handleOK () {
      navigator.clipboard.writeText(this.temporary_url)
      this.$noty.info(`The URL for ${this.originalFilename} has been saved to your clipboard and can be pasted as a link into your editor.`)
      this.preSignedURL = ''
      this.disableStartUpload = false
    }
  }
}
</script>
