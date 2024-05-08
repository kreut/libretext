<template>
  <b-container>
    <b-row v-if="submissionArray && questionSubmissionArray &&
      questionSubmissionArray.length"
    >
      <ul v-show="scoringType === 'p'" class="font-weight-bold p-0" style="list-style-type: none">
        <li>Total points: {{ sumArrBy(questionSubmissionArray, 'points', 4) }}</li>
        <li>Percent correct: {{ sumArrBy(questionSubmissionArray, 'percent') }}%</li>
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
              <th v-if="userRole === 2 && technology === 'webwork'" scope="col">
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
            <tr v-for="(item, itemIndex) in questionSubmissionArray"
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
              <td v-if="userRole === 2 && technology === 'webwork'">
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
    penalties: {
      type: Array,
      default: () => {
      }
    },
    smallTable: {
      type: Boolean,
      default: false
    },
    submissionArray: {
      type: Array,
      default: () => {
      }
    },
    questionSubmissionArray: {
      type: Array,
      default: () => {
      }
    },
    technology: {
      type: String,
      default: ''
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
