<template>
  <div>
    <toggle-button
      tabindex="0"
      :width="84"
      :value="toggleValue"
      :sync="true"
      :font-size="14"
      :color="toggleColors"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="submitToggleChange()"
    />

  </div>
</template>

<script>
import 'vue-loading-overlay/dist/vue-loading.css'
import { ToggleButton } from 'vue-js-toggle-button'
import axios from 'axios'

export default {
  name: 'ReportToggle',
  components: { ToggleButton },
  props: {
    assignmentId:
      {
        type: Number,
        default: 0
      },
    questionId:
      {
        type: Number,
        default: 0
      },
    item: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    toggleColors: window.config.toggleColors,
    toggleValue: false
  }),
  mounted () {
    this.getToggleValue()
  },
  methods: {
    async getToggleValue () {
      try {
        const { data } = await axios.get(`/api/report-toggles/assignment/${this.assignmentId}/question/${this.questionId}/${this.item}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.toggleValue = Boolean(data.toggle_value)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitToggleChange () {
      try {
        const { data } = await axios.patch(`/api/report-toggles/assignment/${this.assignmentId}/question/${this.questionId}/${this.item}`)
        this.$noty[data.type](data.message)
        this.toggleValue = !this.toggleValue
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }

}
</script>

<style scoped>

</style>
