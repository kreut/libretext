<template>
  <div>
    <div v-if="activeTranscript && activeTranscript.length">
      <b-modal id="modal-confirm-re-process-transcript"
               title="Confirm Re-process Transcript"
      >
        Please confirm that you would like to re-process this transcript. If re-processed, then the current transcript
        will be deleted.
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm"
                    @click="$bvModal.hide('modal-confirm-re-process-transcript')"
          >
            Cancel
          </b-button>
          <b-button size="sm"
                    variant="danger"
                    @click="reProcessTranscript"
          >
            Re-process Transcript
          </b-button>
        </template>
      </b-modal>
      <b-form-group
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="transcript-timing"
        label-size="sm"
        label-align="center"
      >
        <template #label>
          Time Range <QuestionCircleTooltip :id="'discuss-it-description-tooltip'"/> <b-tooltip target="discuss-it-description-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            Select one of the time ranges to view/edit the transcript.
          </b-tooltip>
        </template>
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
        <span v-if="model === 'QuestionMediaUpload'">
          After saving this question, you will be notified by email when the editable transcript is ready for viewing.
        </span>
        <span v-if="model === 'DiscussionComment'">
          The transcript has not been processed yet.  Please give it a minute or so for the transcript to be completed.
        </span>
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
          <b-button size="sm" @click="$emit('hideMediaModal')">
            Cancel
          </b-button>
          <span v-show="model==='DiscussionComment'" class="pl-2">
            <span v-b-tooltip.hover="{ delay: { show: 500, hide: 0 } }"
                  :title="Boolean(activeMedia.re_processed_transcript)
                    ? 'Transcripts may only be re-processed once.'
                    : 'If there are major issues with the transcript you can re-process it for possibly improved results.'"
            >
              <b-button
                size="sm"
                variant="outline-danger"
                :disabled="Boolean(activeMedia.re_processed_transcript)"
                @click="$bvModal.show('modal-confirm-re-process-transcript')"
              >Re-process Full Transcript</b-button></span>
          </span>
        </div>
      </b-form-group>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Transcript',
  props: {
    activeMedia: {
      type: Object,
      default: () => {
      }
    },
    activeTranscript: {
      type: Array,
      default: () => {
      }
    },
    model: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    activeTranscriptTimingText: '',
    transcriptTimingOptions: [],
    transcriptTiming: null,
    activeTranscriptTiming: {}
  }),
  mounted () {
    this.transcriptTimingOptions = [{ value: null, text: 'Choose a time range' }]
    this.transcriptTiming = null
    if (this.activeTranscript) {
      for (let i = 0; i < this.activeTranscript.length; i++) {
        const timeRange = this.activeTranscript[i]
        const humanReadableTime = this.formatTimeRange(timeRange.start, timeRange.end)
        this.transcriptTimingOptions.push({ value: timeRange.start, text: humanReadableTime })
      }
    }
  },
  methods: {
    async reProcessTranscript () {
      try {
        const { data } = await axios.patch(`/api/question-media/${this.activeMedia.id}/re-process-transcript`, { model: 'DiscussionComment' })
        this.$bvModal.hide('modal-confirm-re-process-transcript')
        this.$noty[data.type](data.message)
        this.transcriptTiming = false
        this.$emit('removeCurrentTranscript')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateCaption () {
      try {
        const caption = this.activeMedia.transcript.findIndex(caption => caption.start === this.transcriptTiming)
        if (caption === -1) {
          this.$noty.error('We were unable to locate that caption.  Please contact support.')
          return
        }
        const { data } = await axios.patch(`/api/question-media/${this.activeMedia.id}/caption/${caption}`,
          {
            text: this.activeTranscriptTimingText,
            model: this.model
          })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$emit('updateTranscriptInMedia', caption, this.transcriptTiming, this.activeTranscriptTimingText)
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
    }
  }
}
</script>
