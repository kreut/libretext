<template>
  <div>
    <b-form-radio-group
      v-if="radioButtons"
      v-model="assignment.question_titles_shown"
      stacked
      required
      @change="submitQuestionTitlesShown"
    >
      <b-form-radio :name="`question-titles-shown-${assignment.id}`" value="1">
        Shown
      </b-form-radio>
      <b-form-radio :name="`question-titles-shown-${assignment.id}`" value="0">
        Hidden
      </b-form-radio>
    </b-form-radio-group>
    <toggle-button
      v-if="!radioButtons"
      tabindex="0"
      :width="84"
      :value="Boolean(+assignment.question_titles_shown)"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :aria-label="Boolean(+assignment.question_titles_shown) ? `Question titles are shown for ${assignment.name}` : `Question titles are not shown for ${assignment.name}`"
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
      },
    radioButtons: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    toggleColors: window.config.toggleColors,
    assessmentType: ''
  }),
  methods: {
    async submitQuestionTitlesShown () {
      // In radio mode, v-model has already switched the value; the endpoint simply flips it on the server.
      // Only keep the new value if the server actually saved it.
      const revertRadio = () => {
        this.assignment.question_titles_shown = +this.assignment.question_titles_shown ? 0 : 1
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/show-question-titles`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          if (this.radioButtons) {
            revertRadio()
          }
          return false
        }
        if (!this.radioButtons) {
          this.assignment.question_titles_shown = +this.assignment.question_titles_shown ? 0 : 1
        }
      } catch (error) {
        this.$noty.error(error.message)
        if (this.radioButtons) {
          revertRadio()
        }
      }
    }
  }

}
</script>

<style scoped>

</style>
