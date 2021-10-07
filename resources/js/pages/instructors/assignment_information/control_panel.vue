<template>
  <div>
    <b-modal id="modal-show-assignment-statistics"
             title="Show Assignment Statistics"
    >
      <p>
        It looks like you want to show the assignment statistics but your students can't view their own scores.
        Are you sure that you want to show the assignment statistics?
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-show-assignment-statistics')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitShowAssignmentStatistics()"
        >
          Yes, I want to show the assignment statistics.
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-hide-solutions"
             title="Hide Solutions"
    >
      <p>
        This is a "<strong>{{ assessmentType }}</strong>" assignment. Typically
        for this type of assignment, students are shown the solutions immediately.
        Are you sure that you want to hide the solutions?
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-hide-solutions')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitSolutionsReleased()"
        >
          Yes, I want to hide the solutions!
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-hide-scores"
             title="Hide Scores"
    >
      <p>
        This is a "<strong>{{ assessmentType }}</strong>" assignment. Typically
        for this type of assignment, students get immediate feedback.
        Are you sure that you want to hide the scores?
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-hide-scores')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitShowScores()"
        >
          Yes, I want to hide the scores!
        </b-button>
      </template>
    </b-modal>
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
          label-cols-sm="4"
          label-cols-lg="3"
          label="Scores"
          label-for="scores"
        >
          <b-form-row class="mt-2">
            <toggle-button
              tabindex="0"
              :width="80"
              :value="Boolean(assignment.show_scores)"
              :sync="true"
              :font-size="14"
              :color="toggleColors"
              :labels="{checked: 'Shown', unchecked: 'Hidden'}"
              @change="initShowScores(assignment)"
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
            <toggle-button
              tabindex="0"
              :width="80"
              :value="Boolean(assignment.solutions_released)"
              :sync="true"
              :font-size="14"
              :color="toggleColors"
              :labels="{checked: 'Shown', unchecked: 'Hidden'}"
              @change="initSolutionsReleased(assignment)"
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
            <toggle-button
              tabindex="0"
              :width="80"
              :value="Boolean(assignment.students_can_view_assignment_statistics)"
              :sync="true"
              :font-size="14"
              :color="toggleColors"
              :labels="{checked: 'Shown', unchecked: 'Hidden'}"
              @change="initShowAssignmentStatistics(assignment)"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="points_per_question"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Points Per Question"
          label-for="points_per_question"
        >
          <b-form-row class="mt-2">
            <toggle-button
              tabindex="0"
              :width="80"
              :value="Boolean(assignment.show_points_per_question)"
              :sync="true"
              :font-size="14"
              :color="toggleColors"
              :labels="{checked: 'Shown', unchecked: 'Hidden'}"
              @change="submitShowPointsPerQuestion(assignment)"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="graders_can_see_student_names"
          label-cols-sm="5"
          label-cols-lg="4"
        >
          <template slot="label">
            Graders can see student names

            <a id="viewable-by-graders-tooltip"
               href="#"
            >
              <b-icon icon="question-circle" class="text-muted"/>
            </a>
            <b-tooltip target="viewable-by-graders-tooltip"
                       delay="500"
                       triggers="hover focus"
            >
              You can optionally hide your students' names from your graders to avoid any sort of
              conscious or subconscious bias.
            </b-tooltip>
          </template>
          <b-form-row v-if="user.role === 2" class="mt-2">
            <toggle-button
              tabindex="0"
              :width="60"
              :value="Boolean(assignment.graders_can_see_student_names)"
              :sync="true"
              :font-size="14"
              :color="toggleColors"
              :labels="{checked: 'Yes', unchecked: 'No'}"
              @change="submitGradersCanSeeStudentNames(assignment)"
            />
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
import { ToggleButton } from 'vue-js-toggle-button'
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  components: {
    ToggleButton,
    Loading
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
    async submitGradersCanSeeStudentNames () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/graders-can-see-student-names/${Number(this.assignment.graders_can_see_student_names)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.assignment.graders_can_see_student_names = !this.assignment.graders_can_see_student_names
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
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
    },
    async initShowAssignmentStatistics (assignment) {
      this.assignment = assignment
      !assignment.students_can_view_assignment_statistics && !assignment.show_scores
        ? this.$bvModal.show('modal-show-assignment-statistics')
        : await this.submitShowAssignmentStatistics()
    },
    async submitShowAssignmentStatistics () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/show-assignment-statistics/${Number(this.assignment.students_can_view_assignment_statistics)}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-show-assignment-statistics')
        if (data.type === 'error') {
          return false
        }
        this.assignment.students_can_view_assignment_statistics = !this.assignment.students_can_view_assignment_statistics
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-show-assignment-statistics')
    },

    async submitShowPointsPerQuestion (assignment) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-points-per-question/${Number(assignment.show_points_per_question)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.show_points_per_question = !assignment.show_points_per_question
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async initShowScores (assignment) {
      this.assessmentType = assignment.assessment_type
      this.assignment = assignment
      this.assessmentType !== 'delayed' && Boolean(assignment.show_scores)
        ? this.$bvModal.show('modal-hide-scores')
        : await this.submitShowScores()
    },
    async submitShowScores () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/show-scores/${Number(this.assignment.show_scores)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.$bvModal.hide('modal-hide-scores')
          return false
        }
        this.assignment.show_scores = !this.assignment.show_scores
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-hide-scores')
    },
    async initSolutionsReleased (assignment) {
      this.assessmentType = assignment.assessment_type
      this.assignment = assignment
      this.assessmentType !== 'delayed' && Boolean(assignment.solutions_released)
        ? this.$bvModal.show('modal-hide-solutions')
        : await this.submitSolutionsReleased()
    },
    async submitSolutionsReleased () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/solutions-released/${Number(this.assignment.solutions_released)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.$bvModal.hide('modal-hide-solutions')
          return false
        }
        this.assignment.solutions_released = !this.assignment.solutions_released
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-hide-solutions')
    },
    async handleReleaseSolutions (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.patch(`/api/assignments/${this.assignmentId}/release-solutions`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-release-solutions-show-scores')
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
