<script>
import { Bar } from 'vue-chartjs'

export default {
  name: 'HistogramView',
  extends: Bar,
  props: {
    xLabel: {
      type: String,
      default: 'Score'
    },
    chartdata: {
      type: Object,
      default: null
    },
    options: {
      type: Object,
      default: () => ({
        legend: {
          display: false
        },
        animation: false,
        scales: {
          xAxes: [{
            display: true,
            scaleLabel: {
              display: true,
              labelString: 'Score'
            }
          }],
          yAxes: [{
            display: true,
            scaleLabel: {
              display: true,
              labelString: 'Number of Students'
            },
            ticks: {
              beginAtZero: true // Ensures the y-scale starts at 0
            }
          }]
        }
      })
    }
  },
  mounted () {
    let options = JSON.parse(JSON.stringify(this.options))
    options.scales.xAxes[0].scaleLabel.labelString = this.xLabel
    this.renderChart(this.chartdata, options)
  },
  watch: {
    chartdata: {
      handler (newData) {
        // Re-render the chart with updated data
        this.renderChart(newData, options)
      },
      deep: true
    }
  }
}
</script>
