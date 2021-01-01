n<template>
  <div>
    <div v-if="loaded">
      <b-container>
        <b-row align-h="end">
          <b-button class="ml-3 mb-2" variant="primary" @click="getStudentView(assignmentId)">
            View Assessments
          </b-button>
        </b-row>
        <hr>

        <b-card v-if="instructions.length" class="mb-2" header="default" header-html="<h5>Instructions</h5>">
          {{ instructions }}
        </b-card>

        <AssignmentStatistics />
      </b-container>
    </div>
  </div>
</template>

<script>

import { mapGetters } from 'vuex'
import axios from 'axios'
import AssignmentStatistics from '../../components/AssignmentStatistics'

export default {
  components: { AssignmentStatistics },
  middleware: 'auth',
  data: () => ({
    assessmentUrlType: '',
    loaded: false,
    name: '',
    instructions: '',
    canViewAssignmentStatistics: false,
    assignmentInfo: {}
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),

  async mounted () {
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentSummary()
    this.loaded = true
  },
  methods: {
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.instructions = assignment.instructions
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
