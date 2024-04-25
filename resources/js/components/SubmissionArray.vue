<template>
  <b-container>
    <b-row v-if="submissionArray && question.submission_array &&
      question.submission_array.length"
    >
      <ul v-show="scoringType === 'p'" class="font-weight-bold p-0" style="list-style-type: none">
        <li>Total points: {{ sumArrBy(question.submission_array, 'points', 4) }}</li>
        <li>Percent correct: {{ sumArrBy(question.submission_array, 'percent') }}%</li>
      </ul>
      <div class="table-responsive">
        <table class="table table-striped pb-3">
          <thead>
            <tr>
              <th scope="col">
                Submission
              </th>
              <th scope="col">
                Result
              </th>
              <th v-if="userRole === 2 && question.technology === 'webwork'" scope="col">
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
            <tr v-for="(item, itemIndex) in question.submission_array"
                :key="`submission-result-${itemIndex}`"
            >
              <td>
                <span :class="item.correct ? 'text-success' : 'text-danger'">
                  {{ item.submission ? item.submission : 'Nothing submitted' }}
                </span>
              </td>
              <td>
                <span v-show="item.correct" class="text-success">Correct</span>
                <span v-show="!item.correct" class="text-danger">
                  {{ item.partial_credit ? 'Partial Credit' : 'Incorrect' }}
                </span>
              </td>
              <td v-if="userRole === 2 && question.technology === 'webwork'">
                <span :class="item.correct ? 'text-success' : 'text-danger'">{{ item.correct_ans }}</span>
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
  </b-container>
</template>

<script>
export default {
  name: 'SubmissionArray',
  props: {
    submissionArray: {
      type: Array,
      default: () => {
      }
    },
    question: {
      type: Object,
      default: () => {
      }
    },
    scoringType: {
      type: String,
      default: ''
    },
    userRole: {
      type: Number,
      default: 3
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
