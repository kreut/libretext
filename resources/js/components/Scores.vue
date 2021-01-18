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
      default: null
    }
  },
  computed: {
    chartData: function () {
      return this.chartdata
    }
  },
  watch: {
    chartdata: function () {
      this.renderChart(this.chartdata, this.options)
    },
    mounted () {
      if (!this.options) {
        this.options = {
          legend: {
            display: false
          },
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
              }
            }]
          }
        }
      }
    }

  }
}
</script>
