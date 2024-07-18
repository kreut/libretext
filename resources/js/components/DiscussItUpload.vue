<template>
  <b-container class="pt-3">
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="`modal-form-errors-file-upload-${modalId}`"/>
    <b-row>
      <file-upload
        ref="discussItMediaUpload"
        v-model="files"
        class="btn btn-primary btn-sm"
        accept=".pdf,.mp3,.mp4"
        put-action="/put.method"
        @input-file="inputFile"
        @input-filter="inputUploadFileFilter"
      >
        Add Media
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
            v-if="(preSignedURL !== '') && (!$refs.discussItMediaUpload || !$refs.discussItMediaUpload.active)"
            variant="info"
            size="sm"
            style="vertical-align: top"
            :disabled="disableStartUpload"
            @click.prevent="initStartUpload('discussItMediaUpload')"
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
    <b-row v-show="uploadFileErrorMessage" class="mb-3">
      <ErrorMessage :message="uploadFileErrorMessage"/>
    </b-row>
    <ErrorMessage v-if="questionFormErrorsMediaUpload" :message="questionFormErrorsMediaUpload"/>
    <div v-if="mediaUploads.length">
      <table class="table table-striped" aria-label="Media">
        <thead>
        <tr>
          <th scope="col">
            Order
          </th>
          <th scope="col">
            Filename
          </th>
          <th scope="col">
            Actions
          </th>
        </tr>
        </thead>
        <tbody is="draggable" :key="mediaUploads.length" v-model="orderedMediaUploads" tag="tbody"
               @end="saveNewOrder"
        >
        <tr
          v-for="mediaUpload in mediaUploads"
          :key="`media-upload-${mediaUpload.order}`"
        >
          <th scope="row">
            <b-icon icon="list"/>
            {{ mediaUpload.order }}
          </th>
          <td>{{ mediaUpload.original_filename }}</td>
          <td>Delete or View</td>
        </tr>
        </tbody>
      </table>
    </div>
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
import { getTooltipTarget } from '~/helpers/Tooptips'
import draggable from 'vuedraggable'

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)
export default {
  name: 'DiscussItMediaUpload',
  components: {
    AllFormErrors,
    ErrorMessage,
    draggable
  },
  props: {
    qtiJson: {
      type: String,
      default: ''
    },
    discussItMediaUploadId: {
      type: Number,
      default: 0
    },
    questionFormErrorsMediaUpload: {
      type: String,
      default: ''
    },
    mediaUploads: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    src: '',
    currentPage: 1,
    modalId: '',
    allFormErrors: [],
    discussItMediaUpload: {},
    progress: 0,
    uploadFileErrorMessage: '',
    disableStartUpload: false,
    files: [],
    preSignedURL: '',
    orderedMediaUploads: []
  }),
  mounted () {
    this.modalId = uuidv4()
    this.orderedMediaUploads = this.mediaUploads
  },
  methods: {
    getTooltipTarget,
    formatFileSize,
    fixInvalid,
    saveNewOrder () {
      for (let i = 0; i < this.orderedMediaUploads.length; i++) {
        this.orderedMediaUploads[i].order = i + 1
      }
      this.$emit('updateDiscussItMediaUploadsOrder', this.orderedMediaUploads)
    },
    cancelUpload () {
      this.files = []
      this.preSignedURL = ''
      this.progress = 0
      this.$noty.info('The upload has been canceled.')
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
          this.$bvModal.show(`modal-form-errors-file-upload-${this.modalId}`)
          return prevent()
        }
        const acceptedExtensionsRegex = /\.(pdf|mp3|mp4)$/i
        const validExtension = acceptedExtensionsRegex.test(newFile.name)
        if (!validExtension) {
          this.uploadFileErrorMessage = `${newFile.name} does not have a valid extension.`
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = [this.uploadFileErrorMessage]
          this.$bvModal.show(`modal-form-errors-file-upload-${this.modalId}`)
          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              upload_file_type: 'discuss-it-media',
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
            newFile.discuss_it_media_filename = data.discuss_it_media_filename
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
    handleOK (newFile) {
      this.preSignedURL = ''
      this.disableStartUpload = false
      this.discussItMediaUpload = newFile
      this.src = this.discussItMediaUpload.temporary_url
      console.log(this.discussItMediaUpload)

      this.$emit('updateDiscussItMediaUploads', {
        id: uuidv4(),
        original_filename: this.discussItMediaUpload.name,
        s3_key: this.discussItMediaUpload.discuss_it_media_filename,
        temporary_url: this.src
      })
    }
  }
}
</script>
