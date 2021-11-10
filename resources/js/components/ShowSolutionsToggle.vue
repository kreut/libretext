<template>
  <div>
    <b-modal :id="`modal-hide-solutions-${assignment.id}`"
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
          @click="$bvModal.hide(`modal-hide-solutions-${assignment.id}`)"
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
        <toggle-button
          tabindex="0"
          :width="84"
          :value="Boolean(assignment.solutions_released)"
          :sync="true"
          :font-size="14"
          :color="toggleColors"
          :aria-label="Boolean(assignment.solutions_released) ? `Solutions are released for ${assignment.name}` : `Solutions are not released for ${assignment.name}`"
          :labels="{checked: 'Shown', unchecked: 'Hidden'}"
          @change="initSolutionsReleased(assignment)"
        />

  </div>
</template>

<script>
import 'vue-loading-overlay/dist/vue-loading.css'
import { ToggleButton } from 'vue-js-toggle-button'
import axios from 'axios'

export default {
  name: 'ShowSolutions',
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
    async initSolutionsReleased (assignment) {
      this.assessmentType = assignment.assessment_type
      this.assignment = assignment
      this.assessmentType !== 'delayed' && Boolean(assignment.solutions_released)
        ? this.$bvModal.show(`modal-hide-solutions-${assignment.id}`)
        : await this.submitSolutionsReleased()
    },
    async submitSolutionsReleased () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignment.id}/solutions-released/${Number(this.assignment.solutions_released)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.$bvModal.hide(`modal-hide-solutions-${this.assignment.id}`)
          return false
        }
        this.assignment.solutions_released = !this.assignment.solutions_released
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(`modal-hide-solutions-${this.assignment.id}`)
    }
  }

}
</script>

<style scoped>

</style>
