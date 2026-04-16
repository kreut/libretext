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
        label-for="transcript-timing"
        label-size="sm"
      >
        <b-form-row>
          <span class="col-form-label-sm">
            Time Range
            <QuestionCircleTooltip :id="'discuss-it-description-tooltip'"/>
            <b-tooltip target="discuss-it-description-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Select one of the time ranges to view/edit the transcript.
            </b-tooltip>
          </span>
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

    <div v-if="transcriptTiming && activeTranscriptTiming.start">
      <b-form-group
        :label-cols-sm="inModal ? 2 : 3"
        :label-cols-lg="inModal ? 1 : 2"
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
              >Re-process Full Transcript</b-button>
            </span>
          </span>
        </div>
      </b-form-group>
    </div>

    <!-- Full transcript display -->
    <div v-if="activeTranscript && activeTranscript.length" class="mt-3">
      <h6>Transcript</h6>
      <div class="transcript-container">
    <span
      v-for="caption in activeTranscript"
      :key="caption.start"
      :ref="'caption-' + caption.start"
      class="transcript-segment"
      :class="{ 'transcript-segment--active': caption.start === currentPlaybackStart }"
      :aria-current="caption.start === currentPlaybackStart ? 'true' : null"
    >{{ caption.text }} </span>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Transcript',
  props: {
    inModal: {
      type: Boolean,
      default: true
    },
    activeMedia: {
      type: Object,
      default: () => {}
    },
    activeTranscript: {
      type: Array,
      default: () => []
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
    activeTranscriptTiming: {},
    currentPlaybackStart: null
  }),
  beforeDestroy () {
    window.removeEventListener('message', this.handlePlayerTimeUpdate)
  },
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
    window.addEventListener('message', this.handlePlayerTimeUpdate)
  },
  methods: {
    handlePlayerTimeUpdate (event) {
      console.error(this.activeMedia.id)
      console.error(event)
      if (event.data?.type !== 'videoTimeUpdate') return
      if (event.data?.mediaId !== `media_id-${this.activeMedia.id}`) return
      console.error(event)
      const currentTime = event.data.currentTime

      const match = this.activeMedia.transcript?.find(caption => {
        const start = this.parseTimeToSeconds(caption.start)
        const end = this.parseTimeToSeconds(caption.end)
        return currentTime >= start && currentTime < end
      })

      if (match && match.start !== this.currentPlaybackStart) {
        this.currentPlaybackStart = match.start
        this.$nextTick(() => {
          const ref = this.$refs['caption-' + match.start]
          const el = Array.isArray(ref) ? ref[0] : ref
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
        })
      }
    },
    parseTimeToSeconds (timeStr) {
      const [hours, minutes, seconds] = timeStr.split(':').map(Number)
      return hours * 3600 + minutes * 60 + seconds
    },
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
      this.$emit('setActiveMedia', this.activeMedia)
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
        this.activeTranscriptTiming = this.activeMedia.transcript.find(caption => caption.start === this.transcriptTiming)
        this.activeTranscriptTimingText = this.activeTranscriptTiming.text
      }
    },
    formatTime (timeStr) {
      const [hours, minutes, seconds] = timeStr.split(':').map(part => part.split('.')[0])
      const formattedMinutes = parseInt(minutes, 10).toString().padStart(1, '0')
      const formattedSeconds = parseInt(seconds, 10).toString().padStart(2, '0')
      return `${formattedMinutes}:${formattedSeconds}`
    },
    formatTimeRange (start, end) {
      const formattedStart = this.formatTime(start)
      const formattedEnd = this.formatTime(end)
      return `${formattedStart} - ${formattedEnd}`
    }
  }
}
</script>

<style scoped>
.transcript-container {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 12px;
  line-height: 1.8;
}

.transcript-segment {
  display: inline;
}

.transcript-segment--active {
  background-color: #fff3cd;
  outline: 2px solid #ffc107;
  border-radius: 3px;
  padding: 1px 0;
}

.transcript-timestamp {
  color: #6c757d;
  font-size: 0.8rem;
  white-space: nowrap;
  min-width: 80px;
}

.transcript-text {
  font-size: 0.9rem;
}
</style>
