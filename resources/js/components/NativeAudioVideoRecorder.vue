<template>
  <div>
    <b-container>
      <iframe v-if="!isRecording && audioVideoFilename"
              v-resize="{ log: false }"
              :src="getSrc()"
              width="100%"
              frameborder="0"
              allowfullscreen=""
      />
      <div v-if="(isRecording && recordingType == 'video') || !isRecording && !audioVideoFilename"
           class="recording-container"
      >
        <video v-if="isRecording && recordingType === 'video'" ref="video" class="w-100" autoplay/>
        <div v-if="!isRecording && !audioVideoFilename" class="placeholder">
          <p>
            Waiting for you to
            <b-button variant="success" size="sm" @click="startRecording">
              Start Recording
            </b-button>
          </p>
        </div>
      </div>
      <b-row class="justify-content-center pt-2">
        <b-button v-show="recordingType === 'video' && isRecording && !isProcessing"
                  size="sm"
                  variant="primary"
                  @click="stopRecording()"
        >
          Submit Video Recording
        </b-button>
        <span v-show="isProcessing && stream" class="text-muted">
          <b-spinner small type="grow"/>
          Processing...
        </span>
        <div v-show="isRecording && !isProcessing && recordingType === 'audio'">
          <div class="mb-3"><span class="text-muted">
          <b-spinner small type="grow"/>
          Recording...
        </span></div>
          <div>
            <b-button variant="primary" size="sm" @click="stopRecording">
              Save Recording
            </b-button>
          </div>
        </div>
      </b-row>
    </b-container>
  </div>
</template>

<script>
import axios from 'axios'
import { isPhone } from '../helpers/isPhone'

export default {
  props: {
    recordingType: {
      type: String,
      default: 'video'
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    uploadType: {
      type: String,
      default: 'discuss-it-comments'
    },
    previouslyUploadedFile: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    constraints: {},
    options: {},
    audioVideoFilename: '',
    isProcessing: false,
    mediaRecorder: null,
    recordedChunks: [],
    stream: null,
    isRecording: false
  }),
  mounted () {
    this.audioVideoFilename = this.previouslyUploadedFile
    switch (this.recordingType) {
      case ('video'):
        this.contentType = 'video/webm'
        this.constraints = { video: true, audio: true }
        if (MediaRecorder.isTypeSupported('video/webm; codecs=vp8,opus')) {
          this.options = { mimeType: 'video/webm; codecs=vp8,opus' }
        } else if (MediaRecorder.isTypeSupported('video/mp4; codecs=avc1')) {
          this.options = { mimeType: 'video/mp4; codecs=avc1,mp4a' } // MP4 with H.264 and AAC for Safari
        } else {
          alert('Neither WebM nor MP4 is supported by this browser.  Please try using Chrome.')
        }
        break
      case ('audio'):
        this.contentType = 'audio/webm'
        this.constraints = { audio: true }
        if (MediaRecorder.isTypeSupported('audio/webm')) {
          this.options = { mimeType: 'audio/webm' }
        } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
          this.options = { mimeType: 'audio/mp4' }
        } else {
          alert('Neither WebM nor MP4 audio is supported by this browser. Try Chrome or Safari.')
        }
        break
      default:
        alert('This is not a valid recording type.')
    }
  },
  methods: {
    isPhone,
    getSrc () {
      let src = ''
      switch (this.uploadType) {
        case ('discuss-it-comments'):
          src = `/discussion-comments/media-player/filename/${this.audioVideoFilename}/is-phone/${+this.isPhone()}`
          break
        case ('submission'):
          src = `/submission-audio/media-player/assignment/${this.assignmentId}/s3_key/${this.audioVideoFilename}/is-phone/${+this.isPhone()}`
          break
        default:
          alert(`${this.uploadType} is not a valid upload type.`)
          src = ''
      }
      return src
    },
    async startRecording () {
      this.resetPlayer()
      this.audioVideoFilename = ''
      this.isRecording = true
      this.isProcessing = false
      this.$nextTick(async () => {
        // Request both video and audio tracks
        this.stream = await navigator.mediaDevices.getUserMedia(this.constraints)
        if (this.recordingType === 'video') {
          this.$refs.video.srcObject = this.stream
        }

        this.recordedChunks = []
        // eslint-disable-next-line no-unused-vars
        this.mediaRecorder = new MediaRecorder(this.stream, this.options)
        // Mute the video element during recording to prevent feedback
        if (this.recordingType === 'video') {
          this.$refs.video.muted = true
        }
        this.mediaRecorder.ondataavailable = event => {
          if (event.data.size > 0) {
            this.recordedChunks.push(event.data)
          }
        }
        this.mediaRecorder.start()
      })
      this.$emit('startVideoRecording')
    },
    stopRecording () {
      this.isProcessing = true
      this.$emit('stopVideoRecording')
      this.mediaRecorder.stop()
      this.mediaRecorder.onstop = () => {
        const blob = new Blob(this.recordedChunks, { type: this.contentType })
        this.uploadRecording(blob)
      }
    },
    async uploadRecording (blob) {
      let uploadFileData = {
        upload_file_type: this.uploadType,
        file_name: 'temp.webm',
        assignment_id: this.assignmentId
      }
      const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)
      const result = await fetch(data.preSignedURL, {
        method: 'PUT',
        body: blob,
        headers: {
          'Content-Type': this.contentType
        }
      })
      console.error(data)
      switch (this.uploadType) {
        case ('discuss-it-comments'):
          this.audioVideoFilename = data.discuss_it_comments_filename
          break
        case ('submission'):
          this.audioVideoFilename = data.submission
      }
      this.resetPlayer()
      if (this.uploadType === 'discuss-it-comments') {
        this.$emit('updateDiscussionCommentVideo')
      }
      if (!result.ok) {
        this.$noty.error('Error uploading recording:', result.statusText)
      }
      this.isRecording = false
      this.isProcessing = false
      this.saveRecording()
    },
    resetPlayer () {
      if (this.stream) {
        this.stream.getTracks().forEach(track => track.stop())
        this.stream = null
        if (this.recordingType === 'video') {
          this.$refs.video.srcObject = null
        }
      }
    },
    saveRecording () {
      let methodToEmit
      switch (this.uploadType) {
        case ('discuss-it-comments'):
          methodToEmit = 'saveComment'
          break
        case ('submission'):
          methodToEmit = 'submitAudio'
      }
      this.$emit(methodToEmit, this.audioVideoFilename, this.recordingType)
    }
  }
}
</script>
<style scoped>
.recording-container {
  width: 100%;
  position: relative;
  padding-top: 75%; /* Aspect ratio 4:3 (640/480 = 0.75) */
}

.recording-container video,
.recording-container .placeholder {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder {
  background-color: #f8f9fa; /* Light gray background */
  color: #6c757d; /* Bootstrap text-muted color */
  font-size: 1.25rem; /* Slightly larger font size */
  text-align: center;
}
</style>
