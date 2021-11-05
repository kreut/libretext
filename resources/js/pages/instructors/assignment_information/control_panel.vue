<template>
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
      <PageTitle v-if="!isLoading" title="Control Panel"/>
      <div v-if="!isLoading">
        <b-form-group
          id="scores"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Scores"
          label-for="scores"
        >
          <b-form-row class="mt-2">
            <ShowScoresToggle :key="`show-scores-toggle-${assignment.id}`" :assignment="assignment"/>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="solutions"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Solutions"
          label-for="solutions"
        >
          <b-form-row class="mt-2">
            <ShowSolutionsToggle :key="`show-solutions-toggle-${assignment.id}`" :assignment="assignment"/>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="statistics"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Statistics"
          label-for="statistics"
        >
          <b-form-row class="mt-2">
            <StudentsCanViewAssignmentStatisticsToggle :key="`students-can-view-assignment-statistics-toggle-${assignment.id}`" :assignment="assignment"/>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="points_per_question"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Points Per Question"
          label-for="points_per_question"
        >
          <b-form-row class="mt-2">
            <ShowPointsPerQuestionToggle :key="`show-points-per-question-toggle-${assignment.id}`" :assignment="assignment"/>
          </b-form-row>
        </b-form-group>
        <b-form-group
          v-if="user.role === 2"
          id="graders_can_see_student_names"
          label-cols-sm="3"
          label-cols-lg="2"
          ab
        >
          <template slot="label">
            Student Names

            <QuestionCircleTooltip :id="'viewable-by-graders-tooltip'"/>
            <b-tooltip target="viewable-by-graders-tooltip"
                       delay="500"
                       triggers="hover focus"
            >
              You can optionally hide your students' names from your graders to avoid any sort of
              conscious or subconscious bias.
            </b-tooltip>
          </template>
          <b-form-row class="mt-2">
            <GradersCanSeeStudentNamesToggle :key="`graders-can-see-student-names-toggle-${assignment.id}`" :assignment="assignment"/>
          </b-form-row>
        </b-form-group>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import ShowScoresToggle from '~/components/ShowScoresToggle'
import ShowSolutionsToggle from '~/components/ShowSolutionsToggle'
import StudentsCanViewAssignmentStatisticsToggle from '~/components/StudentsCanViewAssignmentStatisticsToggle'
import ShowPointsPerQuestionToggle from '~/components/ShowPointsPerQuestionToggle'
import GradersCanSeeStudentNamesToggle from '~/components/GradersCanSeeStudentNamesToggle'
export default {
  middleware: 'auth',
  components: {
    Loading,
    ShowScoresToggle,
    ShowSolutionsToggle,
    StudentsCanViewAssignmentStatisticsToggle,
    ShowPointsPerQuestionToggle,
    GradersCanSeeStudentNamesToggle
  },
  data: () => ({
    toggleColors: window.config.toggleColors,
    isLoading: true,
    assignmentId: 0,
    assignment: {},
    assessmentType: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment control panel page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentSummary()
  },
  methods: {
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        if (data.type === 'error') {
          this.isLoading = false
          return false
        }
        this.assignment = data.assignment
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
