<template>
  <b-row v-if="currentQuestionSubmissionArray &&
      currentQuestionSubmissionArray.length"
  >
    <ul v-show="scoringType === 'p'" class="font-weight-bold p-0" style="list-style-type: none">
      <li v-if="createdAt">Date Submitted: {{ createdAt }}</li>
      <li>Total points: {{ sumArrBy(currentQuestionSubmissionArray, 'points', 4) }}</li>
      <li>Percent correct: {{ sumArrBy(currentQuestionSubmissionArray, 'percent') }}%</li>
      <li v-for="(penalty, penaltyIndex) in penalties" v-show="penalty.points>0" :key="`penalties-${penaltyIndex}`">
        {{ penalty.text }} {{ penalty.points }} ({{ penalty.percent }}%)
      </li>
    </ul>
    <div class="table-responsive">
      <table class="table table-striped pb-3" :class="smallTable ? 'table-sm' : ''">
        <thead>
        <tr>
          <th scope="col">
            Submission
          </th>
          <th scope="col">
            Result
          </th>
          <th v-if="(userRole === 2 || showCorrectAnswer) && technology === 'webwork'" scope="col">
            Correct Answer
          </th>
          <th v-if="scoringType === 'p'" scope="col">
            Points
          </th>
          <th v-if="scoringType === 'p'" scope="col">
            Percent
          </th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(item, itemIndex) in currentQuestionSubmissionArray"
            :key="`submission-result-${itemIndex}`"
        >
          <td>
                <span v-show="!item.submission_has_html"
                      :class="item.correct ? 'text-success' : 'text-danger'"
                >
                  {{ item.submission ? item.submission : 'Nothing submitted' }}
                </span>
            <div v-show="item.submission_has_html"
                 :class="item.correct ? 'text-success' : 'text-danger'"
                 v-html="item.submission ? item.submission : 'Nothing submitted'"
            >
            </div>
          </td>
          <td>
            <span v-show="item.correct" class="text-success">Correct</span>
            <span v-show="!item.correct" class="text-danger">
                  {{ item.partial_credit ? 'Partial Credit' : 'Incorrect' }}
                </span>
          </td>
          <td v-if="(userRole === 2 || showCorrectAnswer) && technology === 'webwork'">
            <span v-show="!item.correct_ans_has_html" :class="item.correct ? 'text-success' : 'text-danger'">{{
                item.correct_ans
              }}</span>
            <div v-show="item.correct_ans_has_html" :class="item.correct ? 'text-success' : 'text-danger'"
                 v-html="item.correct_ans"
            />
          </td>
          <td v-if="scoringType === 'p'">
                <span :class="item.correct ? 'text-success' : 'text-danger'">
                  {{ item.points }}</span>
          </td>
          <td v-if="scoringType === 'p'">
            <span :class="item.correct ? 'text-success' : 'text-danger'">{{ item.percent }}%</span>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </b-row>
</template>

<script>
export default {
  name: 'SubmissionArrayTable',
  props: {
    showCorrectAnswer: {
      type: Boolean,
      default: false
    },
    createdAt: {
      type: String,
      default: ''
    },
    currentQuestionSubmissionArray: {
      type: Array,
      default: () => {
      }
    },
    scoringType: {
      type: String,
      default: ''
    },
    smallTable: {
      type: Boolean,
      default: false
    },
    penalties: {
      type: Array,
      default: () => {
      }
    },
    userRole: {
      type: Number,
      default: 0
    },
    technology: {
      type: String,
      default: ''
    }
  },
  methods: {
    sumArrBy (arr, key, places = 0) {
      let sum
      let factor
      sum = arr.reduce((sum, item) => {
        return sum + Number(item[key])
      }, 0)
      factor = 10 ** places
      return Math.round(sum * factor) / factor
    }
  }
}
</script>

<style scoped>

</style>
