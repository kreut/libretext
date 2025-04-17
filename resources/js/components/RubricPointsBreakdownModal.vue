<template>
  <span>
  <b-modal id="modal-rubric-points-breakdown"
           size="lg"
           title="Rubric"
           hide-footer
  >
    <RubricPointsBreakdown
      :user-id="userId"
      :assignment-id="+assignmentId"
      :original-rubric="rubric"
      :question-id="questionId"
      :question-points="questionPoints"
      @setScoreInputType="setScoreInputType"
    />
  </b-modal>
  <b-button size="sm"
            variant="info"
            v-show="rubricPointsBreakdownExists && rubricShown"
            @click="$bvModal.show('modal-rubric-points-breakdown')"
  >View Rubric</b-button>
    </span>
</template>

<script>
import RubricPointsBreakdown from './RubricPointsBreakdown.vue'
import axios from 'axios'

export default {
  name: 'RubricPointsBreakdownModal',
  components: { RubricPointsBreakdown },
  props: {
    questionPoints: {
      type: Number,
      default: 0
    },
    userId: {
      type: Number,
      default: 0
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    },
    rubric: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    rubricShown: false,
    rubricPointsBreakdownExists: false,
    scoreInputType: null
  }),
  mounted () {
    this.getRubricPointsBreakdown()
  },
  methods: {
    setScoreInputType (scoreInputType) {
      this.scoreInputType = scoreInputType
    },
    async getRubricPointsBreakdown () {
      try {
        const { data } = await axios.get(`/api/rubric-points-breakdown/assignment/${this.assignmentId}/question/${this.questionId}/user/${this.userId}`)
        if (data.type === 'success') {
          this.rubricPointsBreakdownExists = data.rubric_points_breakdown_exists
          this.rubricShown = data.rubric_shown
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
