<template>
  <div>
    <b-card-text>
      <table v-show="showTable" class="table table-striped small" aria-label="Rubric Points Breakdown">
        <thead>
        <tr><span class="text-muted">Rubric {{ scoreInputType }} breakdown</span></tr>
        <tr>
          <th scope="col">
            <span style="font-size:12px">Criteria</span>
          </th>
          <th scope="col" style="width:110px;font-size:12px">
            Max<br>Possible
          </th>
          <th scope="col" style="width:100px;'font-size:12px">
            <b-form-radio
              v-model="scoreInputType"
              name="score_input_type"
              value="points"
              @change="$emit('setRubricPointsBreakdown', rubricPointsBreakdown, scoreInputType)"
            >
              Points
            </b-form-radio>
            <b-form-radio
              v-model="scoreInputType"
              name="score_input_type"
              value="percentage"
              @change="$emit('setRubricPointsBreakdown', rubricPointsBreakdown, scoreInputType)"
            >
              Percentage
            </b-form-radio>
          </th>
        </tr>
        </thead>
        <tbody>
        <tr
          v-for="( rubricItem,rubricItemIndex) in rubricPointsBreakdown"
          :key="`rubric-criterion-${rubricItemIndex}`"
        >
          <td>
            {{ rubricItem.criterion }}
            <QuestionCircleTooltip v-show="rubricItem.description"
                                   :id="`rubric-item-tooltip-${rubricItemIndex}`"
            />
            <b-tooltip :target="`rubric-item-tooltip-${rubricItemIndex}`"
                       delay="250"
                       triggers="hover focus"
            >
              {{ rubricItem.description }}
            </b-tooltip>
          </td>
          <td>{{
              scoreInputType === 'percentage' ? originalRubricPercentages[rubricItemIndex]
                : originalRubricPoints[rubricItemIndex]
            }}<span v-if="scoreInputType === 'percentage'">%</span></td>
          <td>
            <div v-if="scoreInputType === 'points'">
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
              <ErrorMessage
                v-if="rubricItem.points !== '' && +rubricItem.points > originalRubricPoints[rubricItemIndex]"
                :message="`Max of ${originalRubricPoints[rubricItemIndex] }`"
              />
            </div>
            <div v-if="scoreInputType === 'percentage'">
              <div class="d-inline-flex">
                <b-form-input
                  v-model="rubricItem.percentage"
                  type="text"
                  size="sm"
                  style="width:80px"
                  placeholder=""
                  required
                  :class="getClass(rubricItem, rubricItemIndex)"
                  @input="recomputeOpenEndedPoints"
                />
                <span class="pl-1 pt-1">%</span></div>
              <ErrorMessage
                v-if="rubricItem.percentage !== '' && +rubricItem.percentage >  originalRubricPercentages[rubricItemIndex]"
                :message="`Max of ${originalRubricPercentages[rubricItemIndex]}`"
              />
            </div>
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
    rubricPointsBreakdownScoreInputType: false,
    originalInputType: '',
    scoreInputType: '',
    showTable: false,
    errors: [],
    rubricPointsBreakdown: [],
    originalRubricPoints: [],
    originalRubricPercentages: [],
    originalMaxPoints: 0
  }),
  async mounted () {
    await this.getRubricPointsBreakdown()
    const originalRubric = JSON.parse(this.originalRubric)
    const rubricItems = originalRubric.rubric_items
    this.originalInputType = originalRubric.score_input_type
    this.scoreInputType = originalRubric.score_input_type
    this.originalMaxPoints = 0

    for (let i = 0; i < rubricItems.length; i++) {
      const points = +rubricItems[i].points
      this.originalMaxPoints += points
    }
    switch (this.scoreInputType) {
      case ('points'):
        for (let i = 0; i < rubricItems.length; i++) {
          const points = +rubricItems[i].points
          const percentage = 100 * +points / this.questionPoints
          this.originalRubricPercentages.push(percentage)
          this.originalRubricPoints.push(points)
        }
        break
      case ('percentage'):
        for (let i = 0; i < rubricItems.length; i++) {
          const percentage = +rubricItems[i].percentage
          const points = this.questionPoints * percentage / 100
          this.originalRubricPercentages.push(percentage)
          this.originalRubricPoints.push(points)
        }
        break
    }
    let rubricWithMaxes = []
    for (let i = 0; i < rubricItems.length; i++) {
      switch (this.scoreInputType) {
        case ('percentage'):
          let pointsItem = rubricItems[i]
          pointsItem.points = this.originalRubricPoints[i]
          rubricWithMaxes.push(pointsItem)
          break
        case ('points'):
          for (let i = 0; i < rubricItems.length; i++) {
            let percentagesItem = rubricItems[i]
            percentagesItem.percentage = +this.originalRubricPercentages[i]
            rubricWithMaxes.push(percentagesItem)
          }
          break
      }
    }
    this.$emit('setOriginalRubricWithMaxes', rubricWithMaxes)
    setTimeout(() => {
      this.showTable = true
    }, 750)
    if (this.rubricPointsBreakdownScoreInputType) {
      this.scoreInputType = this.rubricPointsBreakdownScoreInputType
      localStorage.scoreInputType = this.scoreInputType
    } else {
      if (localStorage.getItem('scoreInputType')) {
        this.scoreInputType = localStorage.getItem('scoreInputType')
      }
    }
    this.$emit('setScoreInputType', this.scoreInputType)
  },
  methods: {
    getClass (rubricItem, rubricItemIndex) {
      switch (this.scoreInputType) {
        case ('points'):
          if (rubricItem.points !== '' && rubricItem.points !== null) {
            if (+rubricItem.points < 0) {
              return 'is-invalid'
            }
            return +rubricItem.points <= this.getMax(rubricItemIndex) ? 'is-valid' : 'is-invalid'
          }
          break
        case ('percentage'):
          if (rubricItem.percentage !== '' && rubricItem.percentage !== null) {
            if (+rubricItem.percentage < 0) {
              return 'is-invalid'
            }
            return +rubricItem.percentage <= this.getMax(rubricItemIndex) ? 'is-valid' : 'is-invalid'
          }
      }
    },
    recomputeOpenEndedPoints () {
      let points = 0
      switch (this.scoreInputType) {
        case ('points'):
          for (let i = 0; i <= this.rubricPointsBreakdown.length - 1; i++) {
            if (this.rubricPointsBreakdown[i].points !== '') {
              if (isNaN(this.rubricPointsBreakdown[i].points)) {
                return false
              }
              if (+this.rubricPointsBreakdown[i].points < 0) return false
              points = points + +this.rubricPointsBreakdown[i].points
              if (+this.rubricPointsBreakdown[i].points > this.getMax(i)) {
                return
              }
            }
          }
          break
        case ('percentage'):
          for (let i = 0; i <= this.rubricPointsBreakdown.length - 1; i++) {
            console.error(this.rubricPointsBreakdown[i].percentage)
            if (this.rubricPointsBreakdown[i].percentage !== '') {
              if (isNaN(this.rubricPointsBreakdown[i].percentage)) {
                return false
              }
              if (+this.rubricPointsBreakdown[i].percentage < 0) return false
              points = points + (this.questionPoints * this.rubricPointsBreakdown[i].percentage / 100)
            }
          }
          break
        default:
          this.$noty('No score input type available.')
      }

      this.$emit('updateOpenEndedSubmissionScore', this.rubricPointsBreakdown, this.scoreInputType, points)
    },
    getMax (rubricItemIndex) {
      switch (this.scoreInputType) {
        case ('points'):
          const points = this.originalRubricPoints[rubricItemIndex]
          if (isNaN(points)) return false
          if (+points < 0) return false
          return +this.questionPoints * points / this.originalMaxPoints
        case ('percentage'):
          const percentage = this.originalRubricPercentages[rubricItemIndex]
          if (isNaN(percentage)) return false
          if (+percentage < 0) return false
          return this.originalRubricPercentages[rubricItemIndex]
      }
    },
    async getRubricPointsBreakdown () {
      try {
        const { data } = await axios.get(`/api/rubric-points-breakdown/assignment/${this.assignmentId}/question/${this.questionId}/user/${this.userId}`)
        if (data.type === 'success') {
          this.rubricPointsBreakdown = JSON.parse(data.rubric_points_breakdown).rubric_items
          this.rubricPointsBreakdownScoreInputType = JSON.parse(data.rubric_points_breakdown).score_input_type
          if (data.rubric_points_breakdown_exists) {
            this.$emit('setRubricPointsBreakdown', this.rubricPointsBreakdown, this.scoreInputType)
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
