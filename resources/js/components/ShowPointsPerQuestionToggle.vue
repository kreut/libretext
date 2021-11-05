<template>
  <div>
    <toggle-button
      tabindex="0"
      :width="84"
      :value="Boolean(assignment.show_points_per_question)"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
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
        assignment.show_points_per_question = !assignment.show_points_per_question
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
