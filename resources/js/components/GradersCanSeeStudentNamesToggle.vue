<template>
  <div>
    <toggle-button
      tabindex="0"
      :width="84"
      :value="Boolean(assignment.graders_can_see_student_names)"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      :aria-label="Boolean(assignment.graders_can_see_student_names) ? `Graders can student names for ${assignment.name}` : `Graders cannot see student names for ${assignment.name}`"
      @change="submitGradersCanSeeStudentNames(assignment)"
    />
  </div>
</template>

<script>
import 'vue-loading-overlay/dist/vue-loading.css'
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
    toggleColors: window.config.toggleColors,
    assessmentType: ''
  }),
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
    }
  }
}
</script>

<style scoped>

</style>
