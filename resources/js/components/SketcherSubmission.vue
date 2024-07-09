<template>
  <div>
    <ul v-show="scoringType === 'p'" class="font-weight-bold p-0" style="list-style-type: none">
      <li>Total points: {{ question.submission_score }}</li>
      <li v-if="question.points > 0">
        Percent correct: {{ getPercent() }}%
      </li>
      <li v-if="question.points > 0">
        Result: <span :class="result === 'Correct' ? 'text-success' : 'text-danger'">{{ result }}</span>
      </li>
      <li v-for="(penalty, penaltyIndex) in penalties" v-show="penalty.points>0" :key="`penalties-${penaltyIndex}`">
        {{ penalty.text }} {{ penalty.points }} ({{ penalty.percent }}%)
      </li>
      <li>Submission:</li>
    </ul>
    <div v-if="structure">
      <SketcherViewer
        :qti-json="JSON.parse(question.qti_json)"
        :student-response="structure"
        :read-only="true"
        :sketcher-viewer-id="'sketcherSubmission'"
      />
    </div>
  </div>
</template>

<script>
import SketcherViewer from './viewers/SketcherViewer.vue'

export default {
  name: 'SketcherSubmission',
  components: { SketcherViewer },
  props: {
    question: {
      type: Object,
      default: () => {
      }
    },
    structure: {
      type: String,
      default: ''
    },
    scoringType: {
      type: String,
      default: ''
    },
    penalties: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    result: ''
  }),
  mounted () {
    this.result = +this.question.submission_score === +this.question.points ? 'Correct' : 'Incorrect'
    this.structure = JSON.stringify(JSON.parse(this.question.student_response).structure)
  },
  methods: {
    getPercent () {
      return Math.round(100 * this.question.submission_score / this.question.points)
    }
  }
}
</script>

<style scoped>

</style>
