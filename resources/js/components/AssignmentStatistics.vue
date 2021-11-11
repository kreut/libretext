n
<template>
  <div>
    <div v-if="!isLoading">
      <b-container>
        <b-row v-if="canViewAssignmentStatistics">
          <b-col>
            <b-card header="default" header-html="<h5>5 Number Summary</h5>">
              <b-card-text>
                <ul>
                  <li v-if="scores.length">
                    {{ scores.length }} student submissions
                  </li>
                  <li v-if="scores.length">
                    Maximum score of {{ max }}
                  </li>
                  <li v-if="scores.length">
                    Minimum score of {{ min }}
                  </li>
                  <li v-if="scores.length">
                    Mean score of {{ mean }}
                  </li>
                  <li v-if="scores.length">
                    Standard deviation of {{ stdev }}
                  </li>
                  <li v-if="!scores.length">
                    Nothing has been scored yet.
                  </li>
                </ul>
              </b-card-text>
            </b-card>
          </b-col>
          <b-col>
            <HistogramAndTableView :chartdata="chartdata"
                                   :height="400"
            />
          </b-col>
        </b-row>
      </b-container>
    </div>
  </div>
</template>

<script>

import { mapGetters } from 'vuex'

import { getScoresSummary } from '~/helpers/Scores'
import axios from 'axios'
import HistogramAndTableView from './HistogramAndTableView'

export default {
  components: { HistogramAndTableView },
  middleware: 'auth',
  data: () => ({
    assessmentUrlType: '',
    isLoading: true,
    name: '',
    instructions: '',
    totalPoints: '',
    canViewAssignmentStatistics: false,
    chartdata: null,
    assignmentInfo: {},
    scores: [],
    mean: 0,
    stdev: 0,
    max: 0,
    min: 0,
    range: 0
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),

  async mounted () {
    this.getScoresSummary = getScoresSummary
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentSummary()
    if (this.canViewAssignmentStatistics) {
      try {
        const data = await this.getScoresSummary(this.assignmentId, `/api/assignments/${this.assignmentId}/scores-info`)
        // console.log(data)
        if (data) {
          this.chartdata = data
        }
        console.log(this.chartdata)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    this.isLoading = false
    this.$emit('loaded-statistics')
  },
  methods: {
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        // console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.totalPoints = String(assignment.total_points).replace(/\.00$/, '')
        this.canViewAssignmentStatistics = assignment.can_view_assignment_statistics
      } catch (error) {
        this.$noty.error(error.message)
        this.title = 'Assignment Summary'
      }
    },
    getStudentView (assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/view`)
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
