<template>
  <div>
    <b-form inline>
      <span v-if="prompt" v-html="strippedPrompt" class="mr-2"/>
      <b-input
        v-model="numericalResponse"
        style="width:100px"
        size="sm"
      />
      <span v-if="showResponseFeedback && qtiJson.studentResponse && qtiJson.studentResponse.answeredCorrectly != null" class="pl-2">
        <b-icon-check-circle-fill
          v-if="qtiJson.studentResponse.answeredCorrectly"
          class="text-success"
          scale="1.1"
        />
        <b-icon-x-circle-fill
          v-if="!qtiJson.studentResponse.answeredCorrectly"
          class="text-danger"
          scale="1.1"
        />
      </span>
      <span v-if="qtiJson.jsonType === 'answer_json' && rangeHint" class="text-muted small pl-2">
        {{ rangeHint }}
      </span>
    </b-form>
    <GeneralFeedback
      v-if="qtiJson.jsonType === 'question_json' && qtiJson.feedback && !Array.isArray(qtiJson.feedback)"
      :feedback="qtiJson.feedback"
      :feedback-type="feedbackType"
    />
  </div>
</template>

<script>
import GeneralFeedback from '../feedback/GeneralFeedback'
import { mapGetters } from 'vuex'

export default {
  name: 'SingleNumericalViewer',
  components: {
    GeneralFeedback
  },
  props: {
    prompt: {
      type: String,
      default: ''
    },
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    numericalResponse: '',
    feedbackType: 'incorrect'
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    strippedPrompt () {
      return this.prompt.replace(/^<p>/, '').replace(/<\/p>\s*$/, '')
    },
    rangeHint () {
      const cr = this.qtiJson.correctResponse
      if (!cr || !cr.value) return null
      const val = parseFloat(cr.value)
      if (isNaN(val)) return null

      if (cr.toleranceType === 'relative') {
        const pct = parseFloat(cr.relativeTolerance ?? 0)
        if (isNaN(pct) || pct <= 0) return null
        const tol = Math.abs(val) * pct / 100
        return `(${+(val - tol).toFixed(4)} to ${+(val + tol).toFixed(4)}, ±${pct}%)`
      }

      const tol = parseFloat(cr.marginOfError ?? 0)
      if (isNaN(tol) || tol <= 0) return null
      return `(${+(val - tol).toFixed(4)} to ${+(val + tol).toFixed(4)})`
    }
  },
  mounted () {
    if (this.qtiJson.studentResponse) {
      this.numericalResponse = this.qtiJson.studentResponse.response ?? ''
      if (this.qtiJson.studentResponse.answeredCorrectly) {
        this.feedbackType = 'correct'
      }
    }
  }
}
</script>
