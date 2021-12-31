<template>
  <div>
    <p v-show="betaAssignmentsExist">
      Since this assessment is an Alpha course with tethered Beta courses, by removing this
      assessment, instructors in associated Beta courses will be prompted to approve the removal in their own courses.
    </p>
    <p>
      By removing <span v-html="getQuestionText()"/>, you will also delete all student submissions for this question. In addition,
      the scores will be re-computed.
    </p>
    <b-alert show variant="info">
      <span class="font-weight-bold">Once removed, scores cannot be reverted!</span>
    </b-alert>
  </div>
</template>

<script>
import _ from 'lodash'

export default {
  name: 'RemoveQuestion',
  props: {
    betaAssignmentsExist: { type: Boolean, default: false },
    questionToRemove: {
      type: Object,
      default: () => {
      }
    }
  },
  methods: {
    getQuestionText () {
      return _.isEmpty(this.questionToRemove) || !this.questionToRemove.title
        ? 'the question'
        : `<span class="font-weight-bold">${this.questionToRemove.title}</span>`
    }
  }
}
</script>

<style scoped>

</style>
