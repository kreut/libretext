<template>
  <b-container>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="`modal-form-errors-file-upload-${modalId}`" />
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

      <div v-if="activeTranscript.length">
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="1"
          label-for="transcript-timing"
          label-size="sm"
          label-align="center"
          label="Timing"
        >
          <b-form-row>
            <b-form-select v-model="transcriptTiming"
                           style="width:200px"
                           size="sm"
                           class="ml-2"
                           :options="transcriptTimingOptions"
                           @change="updateActiveTranscriptTiming"
            />
          </b-form-row>
        </b-form-group>
      </div>
      <div v-else>
        <b-alert info show>
          After saving this question, you will be notified by email when the editable transcript is ready for viewing.
        </b-alert>
      </div>
      <div v-if="transcriptTiming && activeTranscriptTiming.start">
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="1"
          label-for="caption"
          label="Caption"
          label-size="sm"
          label-align="center"
        >
          <b-form-textarea
            id="api_secret"
            v-model="activeTranscriptTimingText"
            type="text"
            rows="5"
            size="sm"
            required
          />
          <div class="mt-3">
            <b-button size="sm" variant="primary" @click="updateCaption">
              Update
            </b-button>
            <b-button size="sm" @click="$bvModal.hide('modal-question-media')">
              Cancel
            </b-button>
          </div>
        </b-form-group>
      </div>
    </b-modal>
    <b-row>
      <file-upload
        ref="questionMediaUpload"
        v-model="files"
        class="btn btn-primary btn-sm"
        accept=".mp3,.mp4"
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
            <b-spinner small type="grow" />
            Uploading File...
          </span>
          <span v-if="processingFile && !file.active" class="text-info">
            <b-spinner
              small
              type="grow"
            />
            {{ processingFileMessage }}
          </span>
          <b-button size="sm"
                    style="vertical-align: top"
                    :disabled="disableQuestionMediaStartUpload"
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
      <b-progress-bar :value="progress" :label="`${Number(progress).toFixed(0)}%`" show-progress animated />
    </b-progress>
    <b-row v-show="questionMediaErrorMessage" class="mb-3">
      <ErrorMessage :message="questionMediaErrorMessage" />
    </b-row>
    <div v-show="mediaUploads.length>0">
      <b-table
        aria-label="Media Uploads"
        striped
        hover
        :no-border-collapse="true"
        :fields="fields"
        :items="mediaUploads"
      >
        <template v-slot:cell(url)="data">
          <span :id="`copy-question-media-url-${data.item.s3_key}`">{{ data.item.url }}</span>
          <span class="text-info">
            <a :id="getTooltipTarget('copyURL',data.item.s3_key)"
               href=""
               aria-label="Copy URL"
               @click.prevent="doCopy(`copy-question-media-url-${data.item.s3_key}`, 'Successfully copied!  You may add this URL to a link in your question.')"
            >
              <font-awesome-icon :icon="copyIcon" />
            </a>
          </span>
          <b-tooltip :target="getTooltipTarget('copyURL',data.item.s3_key)"
                     triggers="hover"
                     delay="500"
          >
            Copy the URL, then create a link inside the question editor.
          </b-tooltip>
        </template>
        <template v-slot:cell(actions)="data">
          <b-icon :id="getTooltipTarget('editCaptions',data.item.s3_key)"
                  icon="pencil"
                  :aria-label="`Edit question transcription`"
                  style="cursor: pointer;"
                  @click="showQuestionMedia(data.item)"
          />
          <b-tooltip :target="getTooltipTarget('editCaptions',data.item.s3_key)"
                     triggers="hover"
                     delay="500"
          >
            Edit captions for {{ data.item.original_filename }}
          </b-tooltip>
          <b-icon :id="getTooltipTarget('deleteQuestionMedia',data.item.s3_key)"
                  icon="trash"
                  :aria-label="`Delete question media`"
                  style="cursor: pointer;"
                  @click="confirmDeleteQuestionMedia(data.item)"
          />
          <b-tooltip :target="getTooltipTarget('deleteQuestionMedia',data.item.s3_key)"
                     triggers="hover"
                     delay="500"
          >
            Delete {{ data.item.original_filename }}
          </b-tooltip>
        </template>
      </b-table>
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

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)
export default {
  name: 'QuestionMediaUpload',
  components: {
    ErrorMessage,
    AllFormErrors,
    FontAwesomeIcon
  },
  props: {
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
    copyIcon: faCopy,
    startTime: 0,
    questionMediaKey: 0,
    activeTranscriptTimingText: '',
    transcriptTiming: null,
    transcriptTimingOptions: [],
    fields: [
      {
        key: 'original_filename',
        label: 'Filename',
        isRowHeader: true
      },
      {
        key: 'url',
        label: 'URL',
        isRowHeader: true
      },
      {
        key: 'actions',
        isRowHeader: true
      }
    ],
    allFormErrors: [],
    questionMediaUpload: {},
    progress: 0,
    questionMediaErrorMessage: '',
    processingFile: false,
    processingFileMessage: '',
    disableQuestionMediaStartUpload: false,
    files: [],
    preSignedURL: '',
    modalId: '',
    activeMedia: {},
    activeTranscript: [],
    activeTranscriptTiming: {}
  }),
  mounted () {
    this.modalId = uuidv4()
    if (this.questionMediaUploadId) {
      this.autoOpenTranscript()
    }
  },
  methods: {
    getTooltipTarget,
    formatFileSize,
    fixInvalid,
    doCopy,
    confirmDeleteQuestionMedia (activeMedia) {
      this.activeMedia = activeMedia
      if (this.qtiJson.includes(activeMedia.s3_key)) {
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
    async updateCaption () {
      try {
        const caption = this.activeMedia.transcript.findIndex(caption => caption.start === this.transcriptTiming)
        if (caption === -1) {
          this.$noty.error('We were unable to locate that caption.  Please contact support.')
          return
        }
        const { data } = await axios.patch(`/api/question-media/${this.activeMedia.id}/caption/${caption}`,
          { text: this.activeTranscriptTimingText })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.activeMedia.transcript.find(caption => caption.start === this.transcriptTiming).text = this.activeTranscriptTimingText
          this.startTime = this.timeToSeconds(this.activeMedia.transcript[caption].start)
          this.questionMediaKey++
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateActiveTranscriptTiming () {
      this.activeTranscriptTimingText = ''
      if (this.transcriptTiming) {
        console.log(this.activeMedia.transcript)
        console.log(this.transcriptTiming)
        this.activeTranscriptTiming = this.activeMedia.transcript.find(caption => caption.start === this.transcriptTiming)
        this.activeTranscriptTimingText = this.activeTranscriptTiming.text
        console.log(this.activeTranscriptTiming)
      }
    },
    formatTime (timeStr) {
      const [hours, minutes, seconds] = timeStr.split(':').map(part => part.split('.')[0])
      const formattedMinutes = parseInt(minutes, 10).toString().padStart(1, '0')
      const formattedSeconds = parseInt(seconds, 10).toString().padStart(2, '0')

      return `${formattedMinutes}:${formattedSeconds}`
    },
    formatTimeRange (start, end) {
      // Format the start and end times
      const formattedStart = this.formatTime(start)
      const formattedEnd = this.formatTime(end)

      // Combine them into a single string
      return `${formattedStart} - ${formattedEnd}`
    },
    showQuestionMedia (activeMedia) {
      this.activeTranscriptTimingText = ''
      this.transcriptTiming = null
      this.activeMedia = activeMedia
      this.activeTranscript = activeMedia.transcript
      this.transcriptTimingOptions = [{ value: null, text: 'Choose a time range' }]
      this.transcriptTiming = null
      for (let i = 0; i < this.activeTranscript.length; i++) {
        const timeRange = this.activeTranscript[i]
        const humanReadableTime = this.formatTimeRange(timeRange.start, timeRange.end)
        this.transcriptTimingOptions.push({ value: timeRange.start, text: humanReadableTime })
      }
      this.$bvModal.show('modal-question-media')
    },
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
      this.$emit('updateQuestionMediaUploads', {
        original_filename: this.originalFilename,
        size: this.files[0].size,
        s3_key: this.questionMediaUpload.question_media_filename,
        url: this.questionMediaUpload.url,
        transcript: ''
      })
    }
  }
}
</script>
