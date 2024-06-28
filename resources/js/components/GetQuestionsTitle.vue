<template>
  <span>
    <a href="#"
       @click.prevent="$emit('initViewSingleQuestion', assignmentQuestion.question_id)"
    >
      <span v-if="assignmentQuestion.title"
            :class="{'text-danger' : assignmentQuestion.in_other_assignments}"
      >{{ assignmentQuestion.title }}</span>
      <span v-if="!assignmentQuestion.title">No title provided</span>
    </a>
    <span
      v-if="assignmentQuestion.in_other_assignments"
    >
      <QuestionCircleTooltip
        :id="`in-assignment-tooltip-${assignmentQuestion.question_id}`"
      />
      <b-tooltip :target="`in-assignment-tooltip-${assignmentQuestion.question_id}`"
                 delay="250"
                 triggers="hover focus"
      >
        This question is in your assignment<span
          v-if="assignmentQuestion.in_assignments_count>1"
        >s</span> "{{ assignmentQuestion.in_assignments_names }}".
      </b-tooltip>
    </span>
    <CloneMessage :question="assignmentQuestion" />
  </span>
</template>

<script>
import CloneMessage from './CloneMessage.vue'

export default {
  name: 'GetQuestionsTitle',
  components: {
    CloneMessage
  },
  props: {
    assignmentQuestion: {
      type: Object,
      default: () => {
      }
    }
  }
}
</script>

<style scoped>

</style>
