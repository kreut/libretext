<template>
  <span
    v-show="question.clone_source_id"
  >
    <span :id="`clone-tooltip-${question.question_id}`">
      <font-awesome-icon
        :icon="copyIcon"
        class="text-success"
      />
    </span>
    <b-tooltip :target="`clone-tooltip-${question.question_id}`"
               delay="250"
               triggers="hover focus"
    >
      This question was cloned from the question with ADAPT ID {{ question.clone_source_id }}.
    </b-tooltip>
  </span>
</template>

<script>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'

export default {
  name: 'CloneMessage',
  components: {
    FontAwesomeIcon
  },
  props: {
    question: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    copyIcon: faCopy
  })
}
</script>

<style scoped>

</style>
