<template>
  <span>
    <b-modal
      id="modal-show-audio-solution"
      ref="modal"
      title="Audio Solution"
      ok-title="OK"
      ok-only
      :size="question.solution_text ? 'lg' : 'sm'"
    >
      <b-row align-h="center">
        <b-card>
          <audio-player
            :src="question.solution_file_url"
          />
        </b-card>
      </b-row>
      <div v-if="question.solution_text" class="pt-3">
        <span v-html="question.solution_text" />
      </div>
    </b-modal>
    <span v-if="question.solution_type === 'audio'">
      <a
        href="" @click="openShowAudioSolutionModal"
      >{{ standardizeFilename(question.solution) }}</a>
    </span>
    <span v-if="question.solution_type === 'q'">
      <a
        :href="question.solution_file_url"
        target="_blank"
      >
        {{ standardizeFilename(question.solution) }}
      </a>
    </span>
    <span v-if="!question.solution">N/A</span>
  </span>
</template>

<script>
export default {
  props: {
    question: {
      type: Object,
      default: null
    },
    currentPage: {
      type: Number,
      default: null
    },
    assignmentName: {
      type: String,
      default: 'Assignment'
    }
  },
  methods: {
    standardizeFilename (filename) {
      if (!filename) {
        return ''
      }
      let ext = filename.slice((Math.max(0, filename.lastIndexOf('.')) || Infinity) + 1)

      let name = this.assignmentName.replace(/[/\\?%*:|"<>]/g, '-')
      return `${name}-${this.currentPage}.${ext}`
    },
    openShowAudioSolutionModal (event) {
      event.preventDefault()
      this.$bvModal.show('modal-show-audio-solution')
    }
  }
}
</script>

<style scoped>

</style>
