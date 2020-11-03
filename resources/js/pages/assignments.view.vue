<template>
  <div>
      <PageTitle v-bind:title="name" v-if="loaded"></PageTitle>
      <div v-if="loaded">
        <b-container>
          <b-row>
            <b-col>
            <b-card title="Summary Statistics">
              <b-card-text>
            <ul>
            <li>This assignment is out of {{ totalPoints }} points.</li>
              <li>{{ scores.length}} student submissions</li>
              <li v-if="this.scores.length">Maximum score of {{ max }}</li>
              <li v-if="this.scores.length">Minimum score of {{ min }}</li>
              <li v-if="this.scores.length">Mean score of {{ mean }}</li>
              <li v-if="this.scores.length">Standard deviation of {{stdev }}</li>
            </ul>
                <hr>
                <b-button class="ml-3 mt-2 float-right" variant="primary" v-on:click="getStudentView(assignmentId)">View Questions</b-button>
              </b-card-text>
            </b-card>
            </b-col>
            <b-col>
              <scores v-if="loaded"
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
    name:'',
    totalPoints:'',
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
    this.getScoresSummary = getScoresSummary
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentInfo()
    try {
      const scoresData = await this.getScoresSummary(this.assignmentId,`/api/assignments/${this.assignmentId}`)
      console.log(scoresData)
      this.chartdata = scoresData
      this.loaded = true
    } catch (error) {
      alert(error.message)
    }
  },
  methods: {
    async getAssignmentInfo() {
      try {
        const {data} = await axios.get(`/api/assignments/${this.assignmentId}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        this.name = data.name
        this.totalPoints = String(data.total_points).replace(/\.00$/, '')
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

