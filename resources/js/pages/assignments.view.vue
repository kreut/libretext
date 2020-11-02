<template>
  <div>
    <b-container>
      <PageTitle v-bind:title="assignmentInfo.name" v-if="loaded"></PageTitle>
      <div v-if="loaded">
      This assignment is out of {{ assignmentInfo.total_points }} points.  For the {{ scores.length}} students
      that submitted, we have a maximum score of {{ max }}, a minimum score of {{ min }}, a mean score of {{ mean }}, and
      a standard deviation of {{stdev }}.
      </div>
      <scores v-if="loaded"
              :chartdata="chartdata"
              :height="300"
      />


    </b-container>
  </div>
</template>

<script>


import {mapGetters} from "vuex"
import axios from 'axios'
import Scores from '~/components/Scores'

let stats = require("stats-lite")
export default {
  components: {Scores},
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({

    loaded: false,
    chartdata: null,
    assignmentInfo: {},
    scores: {},
    mean: 0,
    stdev: 0,
    max: 0,
    min: 0,
    range: 0,
  }),

  async mounted() {
    this.loaded = false
    this.assignmentId = this.$route.params.assignmentId
    try {
      const scoresData = await this.getAssignmentSummary(this.assignmentId)
      console.log(scoresData)
      this.chartdata = scoresData
      this.loaded = true
    } catch (error) {
      alert(error.message)
    }
  },
  methods: {
    round(num, precision) {
      num = parseFloat(num)
      if (!precision) return num
      return (Math.round(num / precision) * precision)
    },
    async getAssignmentSummary(assignmentId) {
      const {data} = await axios.get(`/api/assignments/${assignmentId}`)
      console.log(data)
      this.assignmentInfo = data

      this.scores = this.assignmentInfo.scores.map(user => parseFloat(user.score))
      console.log(this.scores)
      this.max = Math.max(...this.scores) //https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/max
      this.min = Math.min(...this.scores)
      this.mean = Math.round(stats.mean(this.scores) * 100) / 100
      this.stdev = Math.round(stats.stdev(this.scores) * 100) / 100
      this.range = this.max - this.min
      let precision
      if (this.max < 20) {
        precision = 1
      } else if (this.max < 50) {
        precision = 5
      } else {
        precision = 10
      }

      let labels = []
      let counts = []
      for (let i = 0; i < this.scores.length; i++) {
        let score = this.round(this.scores[i], precision)
        if (!labels.includes(score)) {
          labels.push(score)
          counts.push(0)
        }
      }
      console.log(counts)

      labels = labels.sort((a, b) => a - b)
      console.log(labels)
      for (let i = 0; i < this.scores.length; i++) {
        for (let j = 0; j < labels.length; j++) {
          let score = this.round(this.scores[i], precision)
          if (parseFloat(score) === parseFloat(labels[j])) {
            counts[j]++
            break
          }
        }
      }

      return {
        labels: labels,
        datasets: [
          {
            label: 'Distribution of Scores',
            backgroundColor: 'green',
            data: counts
          }
        ]
      }
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
}
</script>

