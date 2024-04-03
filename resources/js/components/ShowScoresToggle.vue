<template>
  <div>
    <b-modal :id="`modal-hide-scores-${assignment.id}`"
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
          @click="$bvModal.hide(`modal-hide-scores-${assignment.id}`)"
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
    <toggle-button
      tabindex="0"
      :width="84"
      :value="Boolean(assignment.show_scores)"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :aria-label="Boolean(assignment.show_scores) ? `Scores for ${assignment.name} are shown` : `Scores for ${assignment.name} are not shown`"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="initShowScores(assignment)"
    />
  </div>
</template>

<script>
import axios from 'axios'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
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
    async initShowScores (assignment) {
      this.assessmentType = assignment.assessment_type
      this.assignment = assignment
      this.assessmentType !== 'delayed' && Boolean(assignment.show_scores)
        ? this.$bvModal.show(`modal-hide-scores-${assignment.id}`)
        : await this.submitShowScores()
    },
    async submitShowScores () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/show-scores/${Number(this.assignment.show_scores)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.$bvModal.hide(`modal-hide-scores-${this.assignment.id}`)
          return false
        }
        this.assignment.show_scores = !this.assignment.show_scores
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(`modal-hide-scores-${this.assignment.id}`)
    }
  }
}

</script>

<style scoped>

</style>
