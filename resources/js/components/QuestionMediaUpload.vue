<template>
  <b-container>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="`modal-form-errors-file-upload-${modalId}`" />
    <b-modal id="modal-upload-vtt"
             title="Upload Transcript"
             no-close-on-backdrop
             no-close-on-esc
             size="lg"
             hide-footer
             @shown="isVttUpload = true"
             @hidden="isVttUpload = false"
    >
      <p>
        If you would like to upload your own .vtt file to use as the transcript, please select it from your
        computer.
      </p>
      <div v-if="isVttUpload">
        <file-upload
          ref="vttUpload"
          v-model="files2"
          class="btn btn-primary btn-sm"
          accept=".vtt"
          put-action="/put.method"
          @input-file="inputFile"
          @input-filter="inputUploadFileFilter"
        >
          Select .vtt file
        </file-upload>
        <b-row class="upload mt-3 ml-1">
          <div v-if="files2.length && (preSignedURL !== '')">
            <div v-for="file in files2" :key="file.id">
              File to upload:
              <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
                file.name
              }}</span> -
              <span>{{ formatFileSize(file.size) }} </span>
              <b-button
                v-if="(preSignedURL !== '') && (!$refs.vttUpload || !$refs.vttUpload.active)"
                variant="info"
                size="sm"
                style="vertical-align: top"
                :disabled="disableStartUpload"
                @click.prevent="initStartUpload('vttUpload')"
              >
                Upload
              </b-button>
              <span v-else-if="file.active" class="ml-2 text-info">
                <b-spinner small type="grow" />
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
        <b-progress v-if="preSignedURL && isVttUpload" max="100" class="mt-2 mb-3">
          <b-progress-bar :value="progress" :label="`${Number(progress).toFixed(0)}%`" show-progress animated />
        </b-progress>
      </div>
    </b-modal>
    <b-modal id="modal-confirm-delete-question-media"
             :title="`Delete ${activeMedia.original_filename}`"
             no-close-on-esc
             no-close-on-backdrop
    >
      <p>Please confirm that you would like to delete:</p>
      <p class="text-center">
        {{ activeMedia.original_filename }}
      </p>
      <b-alert variant="danger" show>
        This action cannot be undone.
      </b-alert>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-delete-question-media')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="deleteQuestionMedia()"
        >
          Delete
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-question-media"
             title="Question Media"
             size="xl"
             no-close-on-esc
             no-close-on-backdrop
             hide-footer
    >
      <b-row>
        <div style="width:60%;margin:auto">
          <iframe
            :key="`question-media-${questionMediaKey}`"
            v-resize="{ log: false }"
            aria-label="Question Media"
            width="100%"
            allowtransparency="true"
            :src="`/question-media-player/${activeMedia.s3_key}/${startTime}`"
            frameborder="0"
            allowfullscreen
            class="mb-2"
          />
        </div>
      </b-row>
      <div v-if="needsTranscript(activeMedia)">
        <Transcript :model="'QuestionMediaUpload'"
                    :active-transcript="activeTranscript"
                    :active-media="activeMedia"
                    @hideMediaModal="hideMediaModal"
                    @updateTranscriptInMedia="updateTranscriptInMedia"
        />
      </div>
    </b-modal>
    <b-row>
      <div v-if="!isVttUpload" :class="isDiscussIt ? 'mt-3' : ''">
        <file-upload
          ref="questionMediaUpload"
          v-model="files"
          class="btn btn-primary btn-sm"
          :accept="isDiscussIt ? '.mp3,.mp4,.vtt,.pdf' : '.mp3,.mp4,.vtt'"
          put-action="/put.method"
          @input-file="inputFile"
          @input-filter="inputUploadFileFilter"
        >
          {{ isDiscussIt ? 'Upload Media' : 'Upload Audio or Video file' }}
        </file-upload>
        <QuestionCircleTooltipModal v-show="!isDiscussIt" :modal-id="'modal-upload-question-media'" />
        <b-modal id="modal-upload-question-media"
                 title="Upload Question Media and Edit Transcript"
                 size="lg"
                 no-close-on-backdrop
        >
          <div>
            <p>
              The following video explains how to have ADAPT automatically add a transcript to your audio/video media.
              This transcript can then be
              edited as needed.
            </p>
          </div>
          <b-embed
            type="iframe"
            aspect="16by9"
            src="https://www.youtube.com/embed/tvtGLadJ4u0?rel=0"
            allowfullscreen
          />
          <template #modal-footer>
            <b-button
              size="sm"
              variant="primary"
              class="float-right"
              @click="$bvModal.hide('modal-upload-question-media')"
            >
              OK
            </b-button>
          </template>
        </b-modal>
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
            v-if="(preSignedURL !== '') && (!$refs.questionMediaUpload || !$refs.questionMediaUpload.active)"
            variant="info"
            size="sm"
            style="vertical-align: top"
            :disabled="disableStartUpload"
            @click.prevent="initStartUpload('questionMediaUpload')"
          >
            Upload
          </b-button>
          <span v-else-if="file.active" class="ml-2 text-info">
            <b-spinner small type="grow" />
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
    <b-progress v-if="preSignedURL && !isVttUpload" max="100" class="mt-2 mb-3">
      <b-progress-bar :value="progress" :label="`${Number(progress).toFixed(0)}%`" show-progress animated />
    </b-progress>
    <b-row v-show="uploadFileErrorMessage" class="mb-3">
      <ErrorMessage :message="uploadFileErrorMessage" />
    </b-row>
    <div v-show="mediaUploads.length>0">
      <table class="table table-striped" aria-label="Media">
        <thead>
          <tr>
            <th v-if="isDiscussIt" scope="col">
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
               @end="updateQuestionMediaUploadsOrder"
        >
          <tr
            v-for="(mediaUpload, mediaUploadIndex) in mediaUploads"
            :key="`media-upload-${mediaUploadIndex}`"
          >
            <th v-if="isDiscussIt" scope="row">
              <b-icon icon="list" />
              {{ mediaUpload.order }}
            </th>
            <td style="width:80%">
              {{ mediaUpload.original_filename }}
            </td>

            <td>
              <span v-show="false" :id="`copy-question-media-url-${mediaUpload.s3_key}`">{{ mediaUpload.url }}</span>
              <a v-show="!isDiscussIt"
                 :id="getTooltipTarget('copyURL',mediaUpload.s3_key)"
                 href=""
                 aria-label="Copy URL"
                 style="cursor: pointer;color:#212529 !important"
                 class="mr-1"
                 @click.prevent="doCopy(`copy-question-media-url-${mediaUpload.s3_key}`, 'Successfully copied!  You may add this URL to a link in your question.')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
              <b-tooltip :target="getTooltipTarget('copyURL',mediaUpload.s3_key)"
                         triggers="hover"
                         delay="500"
              >
                Copy the URL, then create a link inside the question editor and ADAPT will convert the format the media
                for
                listening/viewing.
              </b-tooltip>
              <span v-show="needsTranscript(mediaUpload)">
                <b-icon :id="getTooltipTarget('editCaptions',mediaUpload.s3_key)"
                        icon="pencil"
                        :aria-label="`Edit question transcription`"
                        style="cursor: pointer;"
                        class="mr-1"
                        @click="showQuestionMedia(mediaUpload)"
                />
                <b-tooltip :target="getTooltipTarget('editCaptions',mediaUpload.s3_key)"
                           triggers="hover"
                           delay="500"
                >
                  Edit transcript for {{ mediaUpload.original_filename }}
                </b-tooltip>
                <span v-show="mediaUpload.id">
                  <a v-show="false"
                     :id="`download-transcript-${mediaUpload.id}`"
                     :href="`/api/question-media/${mediaUpload.id}/download-transcript`"
                  >Download Transcript</a>
                  <b-icon :id="getTooltipTarget('downloadTranscript',mediaUpload.s3_key)"
                          icon="download"
                          :aria-label="`Download Transcript`"
                          style="cursor: pointer;"
                          class="mr-1"
                          @click="downloadTranscript(mediaUpload)"
                  />
                  <b-tooltip :target="getTooltipTarget('downloadTranscript',mediaUpload.s3_key)"
                             triggers="hover"
                             delay="500"
                  >
                    Download transcript for {{ mediaUpload.original_filename }}
                  </b-tooltip>
                  <b-icon :id="getTooltipTarget('uploadTranscript',mediaUpload.s3_key)"
                          icon="upload"
                          :aria-label="`Upload Transcript`"
                          style="cursor: pointer;"
                          class="mr-1"
                          @click="uploadTranscript(mediaUpload)"
                  />
                  <b-tooltip :target="getTooltipTarget('uploadTranscript',mediaUpload.s3_key)"
                             triggers="hover"
                             delay="500"
                  >
                    Upload transcript (.vtt) file for {{ mediaUpload.original_filename }}
                  </b-tooltip>
                </span>
              </span>
              <b-icon :id="getTooltipTarget('deleteQuestionMedia',mediaUpload.s3_key)"
                      icon="trash"
                      :aria-label="`Delete question media`"
                      style="cursor: pointer;"
                      class="mr-1"
                      @click="confirmDeleteQuestionMedia(mediaUpload)"
              />
              <b-tooltip :target="getTooltipTarget('deleteQuestionMedia',mediaUpload.s3_key)"
                         triggers="hover"
                         delay="500"
              >
                Delete {{ mediaUpload.original_filename }}
              </b-tooltip>
            </td>
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
import { doCopy } from '~/helpers/Copy'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { getTooltipTarget } from '~/helpers/Tooptips'
import QuestionCircleTooltipModal from './QuestionCircleTooltipModal.vue'
import draggable from 'vuedraggable'
import Transcript from './Transcript.vue'

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)
export default {
  name: 'QuestionMediaUpload',
  components: {
    Transcript,
    QuestionCircleTooltipModal,
    ErrorMessage,
    AllFormErrors,
    FontAwesomeIcon,
    draggable
  },
  props: {
    isDiscussIt: {
      type: Boolean,
      default: false
    },
    qtiJson: {
      type: String,
      default: ''
    },
    questionMediaUploadId: {
      type: Number,
      default: 0
    },
    mediaUploads: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    isVttUpload: false,
    files2: [],
    copyIcon: faCopy,
    startTime: 0,
    questionMediaKey: 0,
    allFormErrors: [],
    questionMediaUpload: {},
    progress: 0,
    uploadFileErrorMessage: '',
    disableStartUpload: false,
    files: [],
    preSignedURL: '',
    modalId: '',
    activeMedia: {},
    activeTranscript: [],
    orderedMediaUploads: []
  }),
  mounted () {
    this.modalId = uuidv4()
    this.orderedMediaUploads = this.isDiscussIt
      ? this.mediaUploads.sort((a, b) => a.order - b.order)
      : this.mediaUploads

    if (this.questionMediaUploadId) {
      this.autoOpenTranscript()
    }
  },
  methods: {
    getTooltipTarget,
    formatFileSize,
    fixInvalid,
    doCopy,
    updateTranscriptInMedia (caption, transcriptTiming, activeTranscriptTimingText) {
      this.activeMedia.transcript.find(caption => caption.start === transcriptTiming).text = activeTranscriptTimingText
      this.startTime = this.timeToSeconds(this.activeMedia.transcript[caption].start)
      this.questionMediaKey++
    },
    hideMediaModal () {
      this.$bvModal.hide('modal-question-media')
    },
    updateQuestionMediaUploadsOrder () {
      for (let i = 0; i < this.orderedMediaUploads.length; i++) {
        this.orderedMediaUploads[i].order = i + 1
      }
      this.$emit('updateQuestionMediaUploadsOrder', this.orderedMediaUploads)
    },
    async downloadTranscript (activeMedia) {
      document.getElementById(`download-transcript-${activeMedia.id}`).click()
    },
    uploadTranscript (activeMedia) {
      this.activeMedia = activeMedia
      this.$bvModal.show('modal-upload-vtt')
    },
    confirmDeleteQuestionMedia (activeMedia) {
      this.activeMedia = activeMedia
      if (this.qtiJson.includes(activeMedia.s3_key) && !this.isDiscussIt) {
        this.$noty.error('Please remove the URL from the question before deleting this media.')
      } else {
        this.$bvModal.show('modal-confirm-delete-question-media')
      }
    },
    async deleteQuestionMedia () {
      try {
        if (this.activeMedia.id) {
          const { data } = await axios.delete(`/api/question-media/${this.activeMedia.id}`)
          this.$noty[data.type](data.message)
          if (data.type !== 'error') {
            this.$emit('deleteQuestionMediaUpload', this.activeMedia)
            this.$bvModal.hide('modal-confirm-delete-question-media')
          }
        } else {
          this.$noty.info(`${this.activeMedia.original_filename} has been deleted.`)
          this.$emit('deleteQuestionMediaUpload', this.activeMedia)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    cancelUpload () {
      this.files = []
      this.preSignedURL = ''
      this.progress = 0
      this.$noty.info('The upload has been canceled.')
    },
    autoOpenTranscript () {
      const activeMedia = this.mediaUploads.find(item => item.id === this.questionMediaUploadId)
      if (activeMedia) {
        this.activeMedia = activeMedia
        this.showQuestionMedia(activeMedia)
      }
    },
    timeToSeconds (time) {
      const [hours, minutes, seconds] = time.split(':').map(parseFloat)
      return (hours * 3600) + (minutes * 60) + seconds
    },
    needsTranscript (activeMedia) {
      return activeMedia.original_filename && !activeMedia.original_filename.endsWith('.pdf')
    },
    showQuestionMedia (activeMedia) {
      console.error(activeMedia)
      this.transcriptTiming = null
      this.activeMedia = activeMedia
      this.activeTranscript = activeMedia.transcript
      if (this.needsTranscript(activeMedia)) {
        this.$bvModal.show('modal-question-media')
      }
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
        let acceptedExtensionsRegex
        if (this.isVttUpload) {
          acceptedExtensionsRegex = /\.(vtt)$/i
        } else {
          acceptedExtensionsRegex = this.isDiscussIt ? /\.(pdf|mp3|mp4)$/i : /\.(mp3|mp4)$/i
        }
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
            let uploadFileData = this.isVttUpload
              ? {
                upload_file_type: 'vtt',
                s3_key: this.activeMedia.s3_key,
                file_name: newFile.name
              }
              : {
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
      if (!this.isVttUpload) {
        this.questionMediaUpload = newFile
        this.questionMediaUpload.url = window.location.origin + `/question-media-player/${this.questionMediaUpload.question_media_filename}`
        this.$emit('updateQuestionMediaUploads', {
          original_filename: this.originalFilename,
          size: this.files[0].size,
          s3_key: this.questionMediaUpload.question_media_filename,
          url: this.questionMediaUpload.url,
          transcript: ''
        })
      } else {
        this.validateVTTFile()
      }
    },
    async validateVTTFile () {
      try {
        const { data } = await axios.get(`/api/question-media/validate-vtt/${this.activeMedia.id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$emit('updateQuestionTranscript', this.activeMedia, data.transcript)
          this.$bvModal.hide('modal-upload-vtt')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
