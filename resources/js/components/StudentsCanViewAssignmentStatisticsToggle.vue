<template>
  <div>
    <b-modal :id="`modal-show-assignment-statistics-${assignment.id}`"
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
          @click="$bvModal.hide(`modal-show-assignment-statistics-${assignment.id}`)"
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
    <toggle-button
      tabindex="0"
      :width="84"
      :value="Boolean(assignment.students_can_view_assignment_statistics)"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :aria-label="Boolean(assignment.shown) ? `Assignment statistics for ${assignment.name} are shown` : `Assignment statistics for ${assignment.name} not shown`"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="initShowAssignmentStatistics(assignment)"
    />
  </div>
</template>
<script>
import { ToggleButton } from 'vue-js-toggle-button'
import axios from 'axios'

export default {
  name: 'StudentsCanViewAssignmentStatisticsToggle',
  components: { ToggleButton },
  props: {
    assignment:
      {
        type: Object,
        default: function () {
        }
      }
  },
  data: () => ({
    assessmentType: '',
    toggleColors: window.config.toggleColors
  }),
  methods: {
    async initShowAssignmentStatistics (assignment) {
      this.assignment = assignment
      !assignment.students_can_view_assignment_statistics && !assignment.show_scores
        ? this.$bvModal.show(`modal-show-assignment-statistics-${assignment.id}`)
        : await this.submitShowAssignmentStatistics()
    },
    async submitShowAssignmentStatistics () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/show-assignment-statistics/${Number(this.assignment.students_can_view_assignment_statistics)}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide(`modal-show-assignment-statistics-${this.assignment.id}`)
        if (data.type === 'error') {
          return false
        }
        this.assignment.students_can_view_assignment_statistics = !this.assignment.students_can_view_assignment_statistics
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(`modal-show-assignment-statistics-${this.assignment.id}`)
    }
  }
}
</script>

<style scoped>

</style>
