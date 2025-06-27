<template>
  <div v-show="selectedClickerViewOption !== 'question'">
    <b-row class="justify-content-center">
      <b-col :cols="isPhone ? 12 : 6" class="text-center" v-if="selectedClickerViewOption === 'scores'">
        <HistogramAndTableView :key="`scores-${defaultSubmissionResultsIndex}-${defaultSubmissionResultsKey}`"
                               ref="ScoresHistogramAndTableView"
                               :chartdata="scoresChartData"
                               :height="300"
                               :width="300"
                               :x-label="'Score (points)'"
                               :x-key="'score'"
        />
      </b-col>
      <b-col :cols="isPhone ? 12 : 6" class="text-center" v-if="selectedClickerViewOption === 'submissions'">
        <HistogramAndTableView v-if="submissionsChartData.labelsWithCounts"
                               :key="`histogram-and-table-view-${defaultSubmissionResultsIndex}-${defaultSubmissionResultsKey}`"
                               ref="SubmissionsHistogramAndTableView"
                               :chartdata="submissionsChartData"
                               :height="300"
                               :width="300"
                               :x-label="'Submission'"
                               :x-key="'submission'"
                               :clicker-answer-shown="clickerAnswerShown"
                               :correct-ans="correctAns"
        />
        <PieChartAndTableViewer :key="`pie-chart-and-table-view-${defaultSubmissionResultsIndex}-${defaultSubmissionResultsKey}`"
                                :pie-chart-data="pieChartData"
                                :submissions-info="submissionsInfo"
                                :clicker-answer-shown="clickerAnswerShown"
                                :correct-ans="correctAns"
                                :technology="technology"
        />
      </b-col>
    </b-row>
    <b-row class="justify-content-center mt-4" v-show="defaultSubmissionResults.length > 1">
      <b-button id="forward-arrow"
                style="padding:10px;margin-right:10px"
                variant="info"
                :disabled="defaultSubmissionResultsIndex === 0"
                @click="defaultSubmissionResultsIndex--"
      >
        <font-awesome-icon style="font-size:20px"
                           :icon="arrowLeftIcon"
        />
      </b-button>
      <b-button id="back-arrow"
                style="padding:10px;width:50px"
                variant="info"
                :disabled="defaultSubmissionResultsIndex === defaultSubmissionResults.length-1"
                @click="defaultSubmissionResultsIndex++"
      >
        <font-awesome-icon style="font-size:20px"
                           :icon="arrowRightIcon"
        />
      </b-button>
    </b-row>
  </div>
</template>

<script>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faArrowRight } from '@fortawesome/free-solid-svg-icons'
import HistogramAndTableView from '../HistogramAndTableView.vue'
import PieChartAndTableViewer from '../PieChartAndTableViewer.vue'

export default {
  name: 'DefaultSubmissionResultsViewer',
  components: {
    PieChartAndTableViewer,
    HistogramAndTableView,
    FontAwesomeIcon
  },
  props: {
    defaultSubmissionResultsKey: {
      type: Number,
      default: 0
    },
    selectedClickerViewOption: {
      type: String,
      default: 'scores'
    },
    technology: {
      type: String,
      default: ''
    },
    isPhone: {
      type: Boolean,
      default: false
    },
    clickerAnswerShown: {
      type: Boolean,
      default: false
    },
    defaultSubmissionResults: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    useMathJax: false,
    correctAns: '',
    showSecondColumn: false,
    pieChartData: {},
    submissionsInfo: [],
    defaultSubmissionResultsIndex: 0,
    arrowLeftIcon: faArrowLeft,
    arrowRightIcon: faArrowRight,
    scoresChartData: {},
    submissionsChartData: {}
  }),
  watch: {
    defaultSubmissionResultsIndex () {
      this.submissionsChartData = {}
      this.pieChartData = {}
      this.submissionsInfo = []
      this.updateView()
    }
  },
  mounted () {
    this.updateView()
  },
  methods: {
    updateView () {
      this.scoresChartData = this.getChartDataForHistogram('scores', '#26A69A')
      switch (this.defaultSubmissionResults[this.defaultSubmissionResultsIndex].display) {
        case ('pie-chart'):
          this.pieChartData = this.defaultSubmissionResults[this.defaultSubmissionResultsIndex].pie_chart_data
          this.submissionsInfo = this.defaultSubmissionResults[this.defaultSubmissionResultsIndex].submissions
          this.pieChartData.use_mathjax = this.defaultSubmissionResults[this.defaultSubmissionResultsIndex].use_mathjax
          break
        case ('histogram'):
          this.submissionsChartData = this.getChartDataForHistogram('submissions', '#26A69A')
          break
        default:
        // for native, there won't even be any display for the submissions
      }
      this.correctAns = this.defaultSubmissionResults[this.defaultSubmissionResultsIndex].correct_ans
      this.showSecondColumn = this.defaultSubmissionResults[this.defaultSubmissionResultsIndex].pie_chart_data || this.submissionsChartData.labels
    },
    getChartDataForHistogram (items, color) {
      const value = items.slice(0, items.length - 1)
      let labels = []
      let counts = []
      const defaultSubmissionResults = this.defaultSubmissionResults[this.defaultSubmissionResultsIndex]
      if (!defaultSubmissionResults[items]) {
        return []
      }
      for (let i = 0; i < defaultSubmissionResults[items].length; i++) {
        labels.push(defaultSubmissionResults[items][i][value])
        counts.push(defaultSubmissionResults[items][i].number_of_students)
      }
      console.error(defaultSubmissionResults[items])
      return {
        labelsWithCounts: defaultSubmissionResults[items],
        labels: labels,
        datasets: [
          {
            backgroundColor: color,
            data: counts
          }
        ]
      }
    }
  }
}
</script>
