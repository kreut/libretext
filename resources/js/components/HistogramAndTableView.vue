<template>
  <div v-if="chartdata.labelsWithCounts && chartdata.labelsWithCounts.length">
    <toggle-button
      :width="105"
      class="mt-2"
      :value="showHistogramView"
      :sync="true"
      size="lg"
      :font-size="14"
      :margin="4"
      :color="toggleColors"
      :labels="{checked: 'Histogram', unchecked: 'Table View'}"
      :aria-label="showHistogramView ? 'Histogram' : 'Table View'"
      @change="showHistogramView = !showHistogramView"
    />
    <div v-if="showHistogramView">
      <HistogramView
        class="border-1 border-info"
        :chartdata="chartdata"
        :height="height"
        :x-label="xLabel"
      />
    </div>
    <div v-else>
      <b-table
        striped
        hover
        :no-border-collapse="true"
        :items="chartdata.labelsWithCounts"
        :fields="labelsWithCountsFields"
        responsive
      />
    </div>
    <div v-show="clickerAnswerShown
        && correctAns"
    >
      <p><span class="text-success font-weight-bold">Answer:</span> {{ correctAns }}</p>
    </div>
  </div>
</template>

<script>
import { ToggleButton } from 'vue-js-toggle-button'
import HistogramView from './HistogramView.vue'

export default {
  components: { HistogramView, ToggleButton },
  props: {
    xLabel: {
      type: String,
      default: 'Score'
    },
    xKey: {
      type: String,
      default: 'score'
    },
    clickerAnswerShown: {
      type: Boolean,
      default: false
    },
    correctAns: {
      type: String,
      default: ''
    },
    chartdata: {
      type: Object,
      default: function () {
        return {}
      }
    },
    height: {
      type: Number,
      default: 0
    },
    width: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    showHistogramView: true,
    toggleColors: window.config.toggleColors,
    labelsWithCountsFields: []
  }),
  mounted () {
    this.labelsWithCountsFields = [
      {
        key: this.xKey,
        thClass: 'text-center',
        tdClass: 'text-center'
      },
      {
        key: 'number_of_students',
        label: 'Number of Students',
        thClass: 'text-center',
        tdClass: 'text-center'
      }
    ]
    this.$forceUpdate()
  }
}
</script>
