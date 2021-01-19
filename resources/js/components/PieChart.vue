<script>
import { Pie } from 'vue-chartjs'

export default {
  extends: Pie,
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
      this.renderChart({
        labels: this.chartData.labels,
        datasets: [
          {
            backgroundColor: this.chartData.datasets.backgroundColor,
            data: this.chartData.datasets.data
          }
        ]
      }, {
        legend: {
          display: true
        },
        animation: false,
        responsive: true,
        maintainAspectRatio: false,
        tooltips: {
          callbacks: {
            label: function (tooltipItem, data) {
              return data['labels'][tooltipItem['index']] + ': ' + data['datasets'][0]['data'][tooltipItem['index']] + '%'
            }
          }
        }
      })
    },
    mounted () {
      this.renderChart({
        labels: ['Books', 'Magazines', 'Newspapers'],
        datasets: [
          {
            backgroundColor: ['red', 'black', '#00D8FF'],
            data: [40, 20, 10]
          }
        ]
      }, {
        legend: {
          display: true
        },
        responsive: true,
        maintainAspectRatio: false,
        tooltips: {
          callbacks: {
            label: function (tooltipItem, data) {
              return data['labels'][tooltipItem['index']] + ': ' + data['datasets'][0]['data'][tooltipItem['index']] + '%'
            }
          }
        }
      })
    }
  }
}
</script>
