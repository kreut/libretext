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
        <p>
          Showing the solutions, scores, and statistics can be manally changed here. In addition if you go to your
          Assignment Properties,
          you set up an "auto-release" so that these events are automatically activated at specified times.
        </p>
        <b-form-group
          id="scores"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Scores"
          label-for="scores"
        >
          <b-form-row class="mt-2">
            <AutoReleaseToggles :key="`show-scores-toggle-${assignment.id}`"
                                :assignment="assignment"
                                :property="'show_scores'"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="solutions"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Solutions"
          label-for="solutions"
        >
          <b-form-row class="mt-2">
            <AutoReleaseToggles :key="`show-solutions-toggle-${assignment.id}`"
                                :assignment="assignment"
                                :property="'solutions_released'"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="statistics"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Statistics"
          label-for="statistics"
        >
          <b-form-row class="mt-2">
            <AutoReleaseToggles
              :key="`students-can-view-assignment-statistics-toggle-${assignment.id}`"
              :assignment="assignment"
              :property="'students_can_view_assignment_statistics'"
            />
          </b-form-row>
        </b-form-group>
        <hr>
        <b-form-group
          id="points_per_question"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Points Per Question"
          label-for="points_per_question"
        >
          <b-form-row class="mt-2">
            <ShowPointsPerQuestionToggle :key="`show-points-per-question-toggle-${assignment.id}`"
                                         :assignment="assignment"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="question_titles"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="question_titles"
        >
          <template v-slot:label>
            Question Titles
            <QuestionCircleTooltip id="question-titles-tooltip"/>
            <b-tooltip target="question-titles-tooltip"
                       delay="500"
                       triggers="hover focus"
            >
              You can optionally allow your students to see the titles of the questions.
            </b-tooltip>
          </template>
          <b-form-row class="mt-2">
            <ShowQuestionTitlesToggle :key="`show-question-titles-toggle-${assignment.id}`"
                                      :assignment="assignment"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          v-if="user.role === 2"
          id="graders_can_see_student_names"
          label-cols-sm="4"
          label-cols-lg="3"
        >
          <template v-slot:label>
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
            <GradersCanSeeStudentNamesToggle :key="`graders-can-see-student-names-toggle-${assignment.id}`"
                                             :assignment="assignment"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="question_url_view"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="question_url_view"
        >
          <template v-slot:label>
            Question URL View
            <QuestionCircleTooltip id="question-url-view-tooltip"/>
            <b-tooltip target="question-url-view-tooltip"
                       delay="500"
                       triggers="hover focus"
            >
              You can provide your students with a URL taking them directly to any question in the assignment, found
              within a given question's properties. From this question you can either show the entire assignment
              or just limit the view to that specific question.
            </b-tooltip>
          </template>
          <b-form-row class="mt-2">
            <QuestionUrlViewToggle :key="`question-url-view-toggle-${assignment.id}`" :assignment="assignment"/>
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
import ShowPointsPerQuestionToggle from '~/components/ShowPointsPerQuestionToggle'
import GradersCanSeeStudentNamesToggle from '~/components/GradersCanSeeStudentNamesToggle'
import QuestionUrlViewToggle from '~/components/QuestionUrlViewToggle.vue'
import ShowQuestionTitlesToggle from '~/components/ShowQuestionTitlesToggle.vue'
import AutoReleaseToggles from '../../../components/AutoReleaseToggles.vue'

export default {
  middleware: 'auth',
  components: {
    AutoReleaseToggles,
    Loading,
    ShowPointsPerQuestionToggle,
    GradersCanSeeStudentNamesToggle,
    QuestionUrlViewToggle,
    ShowQuestionTitlesToggle
  },
  metaInfo () {
    return { title: 'Assignment Control Panel' }
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
      await this.$router.push({ name: 'no.access' })
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
