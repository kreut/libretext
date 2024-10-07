<template>
  <div>
    <b-form-radio-group
      v-if="radioButtons"
      v-model="assignment.question_url_view"
      stacked
      required
      @change="submitQuestionUrlView()"
    >
      <b-form-radio name="showPointsPerQuestion" value="assignment">
        Assignment
      </b-form-radio>
      <b-form-radio name="showPointsPerQuestion" value="question">
        Question
      </b-form-radio>
    </b-form-radio-group>
    <toggle-button
      v-if="!radioButtons"
      tabindex="0"
      :width="115"
      :value="assignment.question_url_view==='question'"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :aria-label="assignment.question_url_view==='question' ? `Just the question for ${assignment.name} are shown` : `The entire assignment for ${assignment.name} is shown`"
      :labels="{checked: 'Question', unchecked: 'Assignment'}"
      @change="submitQuestionUrlView()"
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
    async submitQuestionUrlView () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/question-url-view`)
        this.$noty[data.type](data.message)
        if (data.type === 'info') {
          this.assignment.question_url_view = data.question_url_view
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
