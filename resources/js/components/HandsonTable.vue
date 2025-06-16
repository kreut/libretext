<template>
  <div>
    <hot-table
      ref="hotTableComponent"
      :data="tableData"
      :rowHeaders="true"
      :colHeaders="true"
      :contextMenu="true"
      :allowInsertRow="true"
      :allowInsertColumn="true"
      :colWidths="88"
      :rowHeights="'auto'"
      :autoRowSize="true"
      :manualColumnResize="true"
      :fillHandle="{ autoInsertRow: false }"
      :formulas="{ engine: HyperFormula() }"
      :beforeChange="beforeChange"
      :afterChange="afterChange"
      licenseKey="non-commercial-and-evaluation"
    />

    <canvas class="chartJSContainer" width="600" height="400"></canvas>

    <button id="export-file" @click="exportToCSV">Export CSV</button>
  </div>
</template>

<script>
import { HotTable } from '@handsontable/vue'
import Handsontable from 'handsontable'
import { Chart } from 'chart.js'
import 'handsontable/dist/handsontable.full.css'
import HyperFormula from 'hyperformula/commonjs'

export default {
  name: 'HandsontableChart',
  components: {
    HotTable,
  },
  data () {
    return {
      tableData: [
        ['Category', 'Date', 'Account', 'F', 'Debit', 'Credit'],
        ['Mortgage', '1/22/96', 'Home', 20, 20, 100],
        ['Utilities', '1/22/22', 'Home', 10, 200, 10],
        ['Stocks', '1/22/22', 'IRA', 10, 100, 10],
        ['Total', 'NA', 'NA', 'NA', '=SUM(E2:E4)', '=SUM(F2:F4)'],
      ],
      hotSettings: {
        rowHeaders: true,
        colHeaders: true,
        contextMenu: true,
        allowInsertRow: true,
        allowInsertColumn: true,
        colWidths: 88,
        rowHeights: 'auto',
        autoRowSize: true,
        manualColumnResize: true,
        fillHandle: {
          autoInsertRow: false,
        },
        formulas: {
          engine: Handsontable.formulas?.engine || undefined,
        },
        beforeChange: function (changes, src) {
          if (src !== 'loadData') {
            this.instance.vueParent.updateChartFromTable()
          }
        },
        afterChange: function (changes, src) {
          MathJax.Hub.Queue(['Typeset', MathJax.Hub])
          if (src !== 'loadData') {
            this.instance.vueParent.updateChartFromTable()
          }
        },
      },
      chart: null,
    }
  },
  mounted () {
    this.initChart()
    this.updateChartFromTable()
  },
  methods: {
    HyperFormula () {
      return HyperFormula
    },
    getHotInstance () {
      return this.$refs.hotTableComponent.hotInstance
    },
    updateChartFromTable () {
      const hot = this.getHotInstance()
      const data = hot.getData()

      // Rebuild datasets based on table data
      const datasets = data.slice(1).map((row, i) => ({
        label: row[0],
        data: row.map((val, idx) =>
          idx > 2 && !isNaN(parseFloat(val)) ? parseFloat(val) : 0
        ),
        backgroundColor: this.getColor(i),
        borderWidth: 1,
      }))

      this.chart.data.labels = data[0]
      this.chart.data.datasets = datasets
      this.chart.update()
    },
    initChart () {
      const ctx = document.querySelector('.chartJSContainer').getContext('2d')
      this.chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: this.tableData[0],
          datasets: [],
        },
        options: {
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      })
    },
    exportToCSV () {
      const hot = this.getHotInstance()
      const exportPlugin = hot.getPlugin('exportFile')
      exportPlugin.downloadFile('csv', {
        bom: false,
        columnDelimiter: ',',
        columnHeaders: true,
        exportHiddenColumns: true,
        exportHiddenRows: true,
        fileExtension: 'csv',
        filename: 'test-CSV-file_[YYYY]-[MM]-[DD]',
        mimeType: 'text/csv',
        rowDelimiter: '\r\n',
        rowHeaders: true,
      })
    },
    getColor (index) {
      const colors = [
        'rgb(255, 99, 132)',
        'rgb(54, 162, 235)',
        'rgb(75, 192, 192)',
        'rgb(255, 205, 86)',
        'rgb(153, 102, 255)',
      ]
      return colors[index % colors.length]
    },
    beforeChange (changes, src) {
      if (src !== 'loadData') {
        this.updateChartFromTable()
      }
    },
    afterChange (changes, src) {
      if (src !== 'loadData') {
        setTimeout(() => {
          this.updateChartFromTable()
          MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        }, 50)
      }
    }
  }
}
</script>

<style>
.chartJSContainer {
  margin-top: 20px;
}
</style>
