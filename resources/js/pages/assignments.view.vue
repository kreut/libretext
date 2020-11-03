<template>
  <div>
      <PageTitle v-bind:title="assignmentInfo.name" v-if="loaded"></PageTitle>
      <div v-if="loaded">
        <b-container>
          <b-row>
            <b-col>
            <b-card title="Summary Statistics">
              <b-card-text>
            <ul>
            <li>This assignment is out of {{ assignmentInfo.total_points }} points.</li>
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
    this.getScoresSummary = getScoresSummary
    this.assignmentId = this.$route.params.assignmentId
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
    getStudentView(assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/view`)
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
}
</script>

