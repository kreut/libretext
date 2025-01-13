<script>
import { Bar } from 'vue-chartjs'

export default {
  extends: Bar,
  props: {
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
    this.renderChart(this.chartdata, this.options)
  },
  watch: {
    chartdata: {
      handler (newData) {
        // Re-render the chart with updated data
        this.renderChart(newData, this.options)
      },
      deep: true
    }
  }
}
</script>
