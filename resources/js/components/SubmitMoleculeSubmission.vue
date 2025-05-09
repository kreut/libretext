<template>
  <div>
    <div v-show="loaded">
      <ul v-show="scoringType === 'p'" class="font-weight-bold p-0" style="list-style-type: none">
        <li>Total points: {{ question.submission_score }}</li>
        <li v-if="question.points > 0">
          Percent correct: {{ getPercent() }}%
        </li>
        <li v-if="question.points > 0">
          Result: <span :class="question.answered_correctly  ? 'text-success' : 'text-danger'"
        >{{ question.answered_correctly ? 'Correct' : 'Incorrect' }}</span>
        </li>
        <li v-for="(penalty, penaltyIndex) in penalties" v-show="penalty.points>0" :key="`penalties-${penaltyIndex}`">
          {{ penalty.text }} {{ penalty.points }} ({{ penalty.percent }}%)
        </li>
        <li>Submission:</li>
      </ul>
      <div v-if="responseStructure">
        <SketcherViewer
          :key="`sketcher-viewer-${structure}`"
          :qti-json="JSON.parse(question.qti_json)"
          :student-response="responseStructure"
          :read-only="true"
          :sketcher-viewer-id="'SubmitMoleculeSubmission'"
        />
      </div>
    </div>
  </div>
</template>

<script>
import SketcherViewer from './viewers/SketcherViewer.vue'

export default {
  name: 'SubmitMoleculeSubmission',
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
    loaded: false,
    result: '',
    responseStructure: ''
  }),
  mounted () {
    this.loaded = false
    this.responseStructure = JSON.stringify(JSON.parse(this.question.student_response).structure)
    this.$forceUpdate()
    this.loaded = true
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
