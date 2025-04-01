<template>
  <div>
    <b-card-text>
      <table class="table table-striped" aria-label="Rubric Points Breakdown" v-show="showTable">
        <thead>
        <tr><span class="text-muted">Rubric points breakdown</span></tr>
        <tr>
          <th scope="col">
            Criteria
          </th>
          <th scope="col" style="width:110px">
            Max Points<br>Possible
          </th>
          <th scope="col" style="width:100px">
            Points Awarded
          </th>
        </tr>
        </thead>
        <tbody>
        <tr
          v-for="( rubricItem,rubricItemIndex) in rubricPointsBreakdown"
          :key="`rubric-criterion-${rubricItemIndex}`"
        >
          <td>{{ rubricItem.criterion }}</td>
          <td>{{ getMaxPoints(rubricItemIndex) }}</td>
          <td>
            <b-form-input
              v-model="rubricItem.points"
              type="text"
              size="sm"
              style="width:80px"
              placeholder=""
              required
              :class="getClass(rubricItem, rubricItemIndex)"
              @input="recomputeOpenEndedPoints"
            />
            <ErrorMessage v-if="rubricItem.points !== '' && +rubricItem.points > getMaxPoints(rubricItemIndex)"
                          :message="`Max of ${getMaxPoints(rubricItemIndex)}`"
            />
          </td>
        </tr>
        </tbody>
      </table>
    </b-card-text>
  </div>
</template>

<script>
import axios from 'axios'
import ErrorMessage from './ErrorMessage.vue'

export default {
  name: 'RubricPointsBreakdown',
  components: { ErrorMessage },
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
    originalRubric: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    showTable: false,
    errors: [],
    rubricPointsBreakdown: [],
    originalRubricPoints: [],
    originalMaxPoints: 0
  }),
  mounted () {
    this.getRubricPointsBreakdown()
    const originalRubric = JSON.parse(this.originalRubric)
    this.originalMaxPoints = 0
    for (let i = 0; i < originalRubric.length; i++) {
      const points = +originalRubric[i].points
      this.originalRubricPoints.push(points)
      this.originalMaxPoints += points
    }
    setTimeout(() => {
      this.showTable = true
    }, 750)
  },
  methods: {
    getClass (rubricItem, rubricItemIndex) {
      if (rubricItem.points !== '') {
        if (+rubricItem.points < 0) {
          return 'is-invalid'
        }
        return +rubricItem.points <= this.getMaxPoints(rubricItemIndex) ? 'is-valid' : 'is-invalid'
      }
      return ''
    },
    recomputeOpenEndedPoints () {
      let points = 0
      for (let i = 0; i <= this.rubricPointsBreakdown.length - 1; i++) {
        if (this.rubricPointsBreakdown[i].points !== '') {
          if (isNaN(this.rubricPointsBreakdown[i].points)) {
            return false
          }
          if (+this.rubricPointsBreakdown[i].points < 0) return false
          points = points + +this.rubricPointsBreakdown[i].points
          if (+this.rubricPointsBreakdown[i].points > this.getMaxPoints(i)) {
            return
          }
        }
      }
      this.$emit('updateOpenEndedSubmissionScore', this.rubricPointsBreakdown, points)
    },
    getMaxPoints (rubricItemIndex) {
      const points = this.originalRubricPoints[rubricItemIndex]
      if (isNaN(points)) return false
      if (+points < 0) return false
      return +this.questionPoints * points / this.originalMaxPoints
    },
    async getRubricPointsBreakdown () {
      try {
        const { data } = await axios.get(`/api/rubric-points-breakdown/assignment/${this.assignmentId}/question/${this.questionId}/user/${this.userId}`)
        if (data.type === 'success') {
          this.rubricPointsBreakdown = JSON.parse(data.rubric_points_breakdown)
          if (data.rubric_points_breakdown_exists) {
            this.$emit('setRubricPointsBreakdown', this.rubricPointsBreakdown)
          }
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
