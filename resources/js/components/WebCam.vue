<template>
  <div>
    <b-container>
      <iframe v-if="!isRecording && discussItCommentsFilename"
              v-resize="{ log: false }"
              :src="`/discussion-comments/media-player/filename/${discussItCommentsFilename}`"
              width="100%"
              frameborder="0"
              allowfullscreen=""
      />
      <div v-if="isRecording || !isRecording && !discussItCommentsFilename" class="video-container">
        <video v-if="isRecording" ref="video" class="w-100" autoplay />
        <div v-if="!isRecording && !discussItCommentsFilename" class="placeholder">
          <p>
            Waiting for you to
            <b-button variant="success" size="sm" @click="startRecording">
              Start Recording
            </b-button>
          </p>
        </div>
      </div>
      <b-row class="justify-content-center pt-2">
        <b-button v-show="isRecording && !isProcessing" size="sm" variant="danger" @click="stopRecording">
          Stop Recording
        </b-button>
        <span v-show="isProcessing && stream" class="text-muted">
          <b-spinner small type="grow" />
          Processing...
        </span>
        <span v-show="!isRecording && discussItCommentsFilename">
          <b-button variant="primary" size="sm" @click="saveRecording">
            Save Recording
          </b-button>
          <b-button variant="danger" size="sm"
                    @click="startRecording"
          >
            Re-record
          </b-button>
        </span>
      </b-row>
    </b-container>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  props: {
    assignmentId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    discussItCommentsFilename: '',
    isProcessing: false,
    mediaRecorder: null,
    recordedChunks: [],
    stream: null,
    isRecording: false
  }),
  methods: {
    async startRecording () {
      this.resetPlayer()
      this.discussItCommentsFilename = ''
      this.isRecording = true
      this.$nextTick(async () => {
        // Request both video and audio tracks
        this.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true })
        this.$refs.video.srcObject = this.stream

        this.recordedChunks = []
        this.mediaRecorder = new MediaRecorder(this.stream, { mimeType: 'video/webm; codecs=vp8,opus' })
        // Mute the video element during recording to prevent feedback
        this.$refs.video.muted = true
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
        const blob = new Blob(this.recordedChunks, { type: 'video/webm' })
        this.uploadVideo(blob)
      }
    },
    async uploadVideo (blob) {
      let uploadFileData = {
        upload_file_type: 'discuss-it-comments',
        file_name: 'temp.webm',
        assignment_id: this.assignmentId
      }
      const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)

      const result = await fetch(data.preSignedURL, {
        method: 'PUT',
        body: blob,
        headers: {
          'Content-Type': 'video/webm'
        }
      })
      this.discussItCommentsFilename = data.discuss_it_comments_filename

      this.resetPlayer()
      this.$emit('updateDiscussionCommentVideo')
      if (!result.ok) {
        this.$noty.error('Error uploading video:', result.statusText)
      }
      this.isRecording = false
      this.isProcessing = false
    },
    resetPlayer () {
      if (this.stream) {
        this.stream.getTracks().forEach(track => track.stop())
        this.stream = null
        this.$refs.video.srcObject = null
      }
    },
    saveRecording () {
      this.$emit('saveComment', this.discussItCommentsFilename)
    }
  }
}
</script>
<style scoped>
.video-container {
  width: 100%;
  position: relative;
  padding-top: 75%; /* Aspect ratio 4:3 (640/480 = 0.75) */
}

.video-container video,
.video-container .placeholder {
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

