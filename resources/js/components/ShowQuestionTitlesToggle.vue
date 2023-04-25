<template>
  <div>
    <toggle-button
      tabindex="0"
      :width="84"
      :value="Boolean(assignment.question_titles_shown)"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :aria-label="Boolean(assignment.question_titles_shown) ? `Question titles are shown for ${assignment.name}` : `Question titles are not shown for ${assignment.name}`"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="submitQuestionTitlesShown()"
    />

  </div>
</template>

<script>
import 'vue-loading-overlay/dist/vue-loading.css'
import { ToggleButton } from 'vue-js-toggle-button'
import axios from 'axios'

export default {
  name: 'QuestionTitlesShown',
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
    async submitQuestionTitlesShown () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/show-question-titles`)
        this.$noty[data.type](data.message)
        this.assignment.question_titles_shown = !this.assignment.question_titles_shown
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }

}
</script>

<style scoped>

</style>
