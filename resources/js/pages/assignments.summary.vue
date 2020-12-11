<template>
  <div>
    <PageTitle v-if="loaded" :title="name" />
    <div v-if="loaded">
      <b-container>
        <b-row align-h="end">
          <b-button v-if="user.role === 2" class="ml-3 mb-2 " variant="primary" @click="getAssessmentsForAssignment(assignmentId)">
            Get Assessments
          </b-button>
          <b-button class="ml-3 mb-2" variant="primary" @click="getStudentView(assignmentId)">
            View Assessments
          </b-button>
        </b-row>
        <b-row>
          <b-card v-if="instructions.length" class="mb-2" header="default" header-html="<h5>Instructions</h5>">
            {{ instructions }}
          </b-card>
        </b-row>
        <b-row v-if="canViewAssignmentStatistics">
          <b-col>
            <b-card header="default" header-html="<h5>Assignment Statistics</h5>">
              <b-card-text>
                <ul>
                  <li>This assignment is out of {{ totalPoints }} points.</li>
                  <li v-if="this.scores.length">
                    {{ scores.length }} student submissions
                  </li>
                  <li v-if="this.scores.length">
                    Maximum score of {{ max }}
                  </li>
                  <li v-if="this.scores.length">
                    Minimum score of {{ min }}
                  </li>
                  <li v-if="this.scores.length">
                    Mean score of {{ mean }}
                  </li>
                  <li v-if="this.scores.length">
                    Standard deviation of {{ stdev }}
                  </li>
                  <li v-if="!this.scores.length">
                    Nothing has been scored yet.
                  </li>
                </ul>
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

import { mapGetters } from 'vuex'
import Scores from '~/components/Scores'
import { getScoresSummary } from '~/helpers/Scores'
import axios from 'axios'

export default {
  components: { Scores },
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    loaded: false,
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

  async mounted () {
    this.loaded = false
    this.getScoresSummary = getScoresSummary
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentSummary()
    if (this.canViewAssignmentStatistics) {
      try {
        const data = await this.getScoresSummary(this.assignmentId, `/api/assignments/${this.assignmentId}/scores-info`)
        console.log(data)
        if (data) {
          this.chartdata = data
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    this.loaded = true
  },
  methods: {
    getAssessmentsForAssignment (assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/get`)
    },
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.name = assignment.name
        this.instructions = assignment.instructions
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
