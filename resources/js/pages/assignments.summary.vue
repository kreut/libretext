<template>
  <div>
    <PageTitle v-bind:title="name" v-if="loaded"></PageTitle>
    <div v-if="loaded">
      <b-container>
        <b-row>
          <b-col>
            <b-card title="Assignment Statistics">
              <b-card-text>
                <ul>
                  <li>This assignment is out of {{ totalPoints }} points.</li>

                  <li v-if="this.scores.length">{{ scores.length }} student submissions</li>
                  <li v-if="this.scores.length">Maximum score of {{ max }}</li>
                  <li v-if="this.scores.length">Minimum score of {{ min }}</li>
                  <li v-if="this.scores.length">Mean score of {{ mean }}</li>
                  <li v-if="this.scores.length">Standard deviation of {{ stdev }}</li>
                  <li v-if="!this.scores.length">Nothing has been scored yet.</li>
                </ul>
                <hr>
                <b-button class="ml-3 mt-2 float-right" variant="primary" v-on:click="getStudentView(assignmentId)">View
                  Questions
                </b-button>
              </b-card-text>
            </b-card>
          </b-col>
          <b-col>
            <scores v-if="loaded && chartdata"
                    :chartdata="chartdata"
                    :height="300"
            />
          </b-col>
        </b-row>
      </b-container>
    </div>
  </div>
</template>

<script>


import {mapGetters} from "vuex"
import Scores from '~/components/Scores'
import {getScoresSummary} from '~/helpers/Scores'
import axios from "axios";

export default {
  components: {Scores},
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    loaded: false,
    name: '',
    totalPoints: '',
    chartdata: null,
    assignmentInfo: {},
    scores: [],
    mean: 0,
    stdev: 0,
    max: 0,
    min: 0,
    range: 0,
  }),

  async mounted() {
    this.loaded = false
    this.getScoresSummary = getScoresSummary
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentInfo()
    try {
      const data = await this.getScoresSummary(this.assignmentId, `/api/assignments/${this.assignmentId}/scores-info`)
     console.log(data)
      if (data) {
       this.chartdata = data
     }
    } catch (error) {
      this.$noty.error(error.message)
    }
    this.loaded = true
  },
  methods: {
    async getAssignmentInfo() {
      try {
        const {data} = await axios.get(`/api/assignments/${this.assignmentId}/total-points-info`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.name = assignment.name
        this.totalPoints = String(assignment.total_points).replace(/\.00$/, '')
      } catch (error) {
        this.$noty.error(error.message)
        this.title = 'Assignment Questions'
      }
    },
    getStudentView(assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/view`)
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
}
</script>

