n<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle :title="name" />
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
  </div>
</template>

<script>

import { mapGetters } from 'vuex'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import AssignmentStatistics from '../../components/AssignmentStatistics'

export default {
  components: { AssignmentStatistics, Loading },
  middleware: 'auth',
  data: () => ({
    assessmentUrlType: '',
    isLoading: true,
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
    this.isLoading = false
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
        this.name = assignment.name
        this.canViewAssignmentStatistics = assignment.can_view_assignment_statistics
      } catch (error) {
        this.$noty.error(error.message)
        this.name = 'Assignment Summary'
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
