<template>
  <div style="position: relative; height: 300px;">
    <canvas ref="canvas"></canvas>
  </div>
</template>

<script>
import Chart from 'chart.js'

export default {
  name: 'PieChart',
  props: {
    chartdata: {
      type: Object,
      required: true
    },
    options: {
      type: Object,
      default: () => ({
        legend: { display: false },
        animation: false,
        responsive: true,
        maintainAspectRatio: false,
        tooltips: false
      })
    }
  },
  data () {
    return {
      chart: null
    }
  },
  mounted () {
    this.renderChart()
    this.$emit('pieChartLoaded')
  },
  watch: {
    chartdata: {
      handler () {
        this.renderChart()
        this.$emit('pieChartLoaded')
      },
      deep: true
    }
  },
  methods: {
    renderChart () {
      if (this.chart) {
        this.chart.destroy()
      }

      const ctx = this.$refs.canvas.getContext('2d')
      this.chart = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: this.chartdata.labels,
          datasets: [
            {
              backgroundColor: this.chartdata.datasets.backgroundColor,
              data: this.chartdata.datasets.data
            }
          ]
        },
        options: this.options
      })
    }
  },
  beforeDestroy () {
    if (this.chart) {
      this.chart.destroy()
    }
  }
}
</script>

<style scoped>
canvas {
  width: 100% !important;
  height: auto !important;
}
</style>
