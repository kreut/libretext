<template>
  <div>
    <b-form-radio-group
      v-if="radioButtons"
      v-model="assignment.show_points_per_question"
      stacked
      required
      @change="submitShowPointsPerQuestion(assignment)"
    >
      <b-form-radio name="showPointsPerQuestion" value="1">
        Shown
      </b-form-radio>
      <b-form-radio name="showPointsPerQuestion" value="0">
        Hidden
      </b-form-radio>
    </b-form-radio-group>
    <toggle-button
      v-if="!radioButtons"
      tabindex="0"
      :width="84"
      :value="Boolean(assignment.show_points_per_question)"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :aria-label="Boolean(assignment.show_points_per_question) ? `Points per question for ${assignment.name} are shown` : `Points per question for ${assignment.name} are not shown`"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="submitShowPointsPerQuestion(assignment)"
    />
  </div>
</template>

<script>
import { ToggleButton } from 'vue-js-toggle-button'
import axios from 'axios'

export default {
  components: { ToggleButton },
  props: {
    assignment:
      {
        type: Object,
        default: function () {
        }
      },
    radioButtons: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    assessmentType: '',
    toggleColors: window.config.toggleColors
  }),
  methods: {
    async submitShowPointsPerQuestion (assignment) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-points-per-question/${Number(assignment.show_points_per_question)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        if (!this.radioButtons) {
          assignment.show_points_per_question = !assignment.show_points_per_question
        }

      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
