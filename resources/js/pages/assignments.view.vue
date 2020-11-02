
  <div>
    {assignmentInfo }
    <h2>{{ assignmentInfo.name }}</h2>
    This assignment is due on {{ assignmentInfo.due }}<br>
    The total number of points for this assignment is {{ assignmentInfo.total_points }}
    <div v-if="scores.length">
      For the {{ scores.length }} students who submitted answers to the questions, we have the following 5 number summary:

      Mean: {{ mean }}
      Standard Deviation: {{ stdev }}
      Max: {{ max }}
      Min:  {{ min }}
      Range: { range }}

    </div>
  </div>



<script>


import {mapGetters} from "vuex"
import axios from 'axios'
import { Bar } from 'vue-chartjs'


let stats = require("stats-lite")
export default {
  extends: Bar,
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
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
    this.renderChart(this.scores)

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
      this.mean = Math.round(stats.mean(this.scores)*100)/100
      this.stdev = Math.round(stats.stdev(this.scores)*100)/100
      this.range = this.max - this.min
    }
  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>

