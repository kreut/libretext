<template>
  <div>

    <scores :data="scoresData"/>

    {assignmentInfo }
    <h2>{{ assignmentInfo.name }}</h2>
    This assignment is due on {{ assignmentInfo.due }}<br>
    The total number of points for this assignment is {{ assignmentInfo.total_points }}
    <div v-if="scores.length">
      For the {{ scores.length }} students who submitted answers to the questions, we have the following 5 number
      summary:

      Mean: {{ mean }}
      Standard Deviation: {{ stdev }}
      Max: {{ max }}
      Min: {{ min }}
      Range: { range }}

    </div>
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
    scoresData: {
      labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      datasets: [
        {
          label: 'GitHub Commits',
          backgroundColor: '#f87979',
          data: [40, 20, 12, 39, 10, 40, 39, 80, 40, 20, 12, 11]
        }
      ]
    },
    assignmentInfo: {},
    scores: {},
    mean: 0,
    stdev: 0,
    max: 0,
    min: 0,
    range: 0,
  }),

  async mounted() {
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentSummary(this.assignmentId)
  },
  methods: {
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

      let labels = []
      let counts = []
      for (let i = 0; i < this.scores.length; i++) {
        if (!labels.includes(this.scores[i])) {
          labels.push(this.scores[i])
          counts.push(0)
        }
      }
      console.log(counts)

      labels = labels.sort((a, b) => a - b)
      console.log(labels)
        for (let i = 0; i < this.scores.length; i++) {
          for (let j = 0; j < labels.length; j++) {
            if (parseFloat(this.scores[i]) === parseFloat(labels[j])) {
              counts[j]++
              break
            }
          }
        }
      this.scoresData.labels = labels
      this.scoresData.datasets.data = counts
      console.log(counts)
    }
  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>

