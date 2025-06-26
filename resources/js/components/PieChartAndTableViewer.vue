<template>
  <div v-if="pieChartData.datasets">
    {{ useMathJax }}
    <toggle-button
      :width="105"
      class="mt-2"
      :value="showPieChartView"
      :sync="true"
      size="lg"
      :font-size="14"
      :margin="4"
      :color="toggleColors"
      :labels="{checked: 'Pie Chart', unchecked: 'Table View'}"
      :aria-label="showPieChartView ? 'Pie Chart' : 'Table View'"
      @change="updatePieChartView()"
    />
    <div v-if="showPieChartView">
      <pie-chart
        :chartdata="pieChartData"
      />
      <div
        v-for="(label, pieChartDataIndex) in pieChartData.labels"
        :key="`pie-chart-data-${pieChartDataIndex}`"
        class="pt-2 text-center"
        style="margin-bottom: 10px; font-size: large;"
      >
        <b-icon-square-fill
          :style="`color:${pieChartData.datasets.backgroundColor[pieChartDataIndex]}; margin-right: 8px;`"
        />
        <span
          style="
    margin-right: 4px;
    max-width: 100%; /* adjust based on your layout */
    word-break: break-word;
    white-space: normal;
    overflow-wrap: break-word;
    display: inline-block;
  "
          v-html="cleanLabel(label)"
        />
        <span
          v-show="clickerAnswerShown && pieChartData.correct_answer_index === pieChartDataIndex"
        >
          <img
            alt="Checkmark for correct answer"
            :src="asset('assets/img/check-mark.png')"
            style="height: 20px; margin-left: 4px;"
          >
        </span>
      </div>
    </div>
    <div v-else>
      <table class="table table-striped table-responsive">
        <thead>
        <tr>
          <th scope="col">
            Submission
          </th>
          <th scope="col">
            Number of students
          </th>
          <th scope="col">
            Percent
          </th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(submissionInfo,index) in submissionsInfo" :key="`submission-${index}`"
            :class="clickerAnswerShown && pieChartData.correct_answer_index === index ? 'text-success' : ''"
        >
          <td>
            <div v-show="technology === 'qti'" v-html="cleanLabel(submissionInfo.submission)"/>
            <span v-show="technology !== 'qti'">{{ submissionInfo.submission }}</span>
          </td>
          <td>{{ submissionInfo.number_of_students }}</td>
          <td>{{ getPercent(pieChartData.labels[index]) }}</td>
        </tr>
        </tbody>
      </table>
    </div>
    <div v-show="clickerAnswerShown
      && pieChartData.correct_answer_index === -1
      && correctAns"
    >
      <p><span class="text-success font-weight-bold">Answer: </span>{{ correctAns }}</p>
    </div>
  </div>
</template>

<script>

import PieChart from './PieChart.vue'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  name: 'PieChartAndTableViewer',
  components: {
    PieChart,
    ToggleButton
  },
  props: {
    useMathJax: {
      type: Boolean,
      default: false
    },
    technology: {
      type: String,
      default: ''
    },
    pieChartData: {
      type: Object,
      default: () => {
      }
    },
    correctAns: {
      type: String,
      default: ''
    },
    clickerAnswerShown: {
      type: Boolean,
      default: false
    },
    submissionsInfo: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    showPieChartView: true,
    toggleColors: window.config.toggleColors
  }),
  watch: {
    clickerAnswerShown (newVal) {
      if (newVal && this.pieChartData.correct_answer_index === -1 &&
        this.correctAns) {
        this.rerenderMathaJax()
      }
    }
  },
  mounted () {
    this.rerenderMathaJax()
  },
  methods: {
    rerenderMathaJax () {
      if (this.useMathJax) {
        this.$nextTick(() => {
          MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        })
      }
    },
    updatePieChartView () {
      this.showPieChartView = !this.showPieChartView
      this.rerenderMathaJax()
    },
    cleanLabel (label) {
      if (this.technology === 'qti') {
        label = label.replace('<p>', '').replace('</p>', '')
      }
      return label
    },
    getPercent (label) {
      const parts = label.split('&mdash;')
      return parts[1]?.trim() || ''
    }
  }
}
</script>
