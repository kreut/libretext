<template>
  <span>
    <span class="h5 mb-0" v-show="formativeQuestion">
      <a :id="`formative-question-${questionId}`"
         href=""
         aria-label="Used in other assignment"
         @click.prevent
      ><b-icon icon="exclamation-circle-fill"
               variant="warning"
      />
      </a></span>
    <b-tooltip :target="`formative-question-${questionId}`"
               delay="250"
               placement="rightbottom"
               triggers="hover focus"
    >
      This question is used in another instructor's formative assignment which means that the assignment is not locked down and students
      have access to solutions immediately after submission.
    </b-tooltip>
  </span>
</template>

<script>
export default {
  name: 'FormativeWarning',
  props: {
    questionId: {
      type: Number,
      default: 0
    },
    formativeQuestion: {
      type: Boolean,
      default: false
    }
  }
}
</script>

<style scoped>

</style>
