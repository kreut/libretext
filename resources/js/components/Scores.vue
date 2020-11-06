<script>
import {Bar} from 'vue-chartjs'


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
  mounted() {
    this.renderChart(this.chartdata, {
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
    })
  }
}
</script>
