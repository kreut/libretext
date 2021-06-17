<template>
  <div>
  <b-alert :show="assessmentType === 'clicker'">
          <span class="font-italic font-weight-bold">
            Important: clicker assignments can only be used in conjunction with H5P true-false and multiple choice assessments.
          </span>
  </b-alert>
  <b-alert :show="openEndedQuestionsInRealTime !== ''">
          <span class="font-italic font-weight-bold">
            It looks like the following assessments have an open-ended component: {{ openEndedQuestionsInRealTime }}.  These types of assessments are appropriate for
            delayed assessments.  Please either change the assessment type to Delayed or remove these assessments.
          </span>
  </b-alert>
  <b-alert :show="learningTreeQuestionsInNonLearningTree !== ''">
          <span class="font-italic font-weight-bold">
            It looks like the following assessments are Learning Tree assessments: {{
              learningTreeQuestionsInNonLearningTree
            }}.  However, this is not a
            Learning Tree assignment.  Please either change the assessment type to Learning Tree or remove these assessments.
          </span>
  </b-alert>
  <b-alert :show="nonLearningTreeQuestions !== ''">
          <span class="font-italic font-weight-bold">
            It looks like the following assessments are non-Learning Tree assessments: {{ nonLearningTreeQuestions }}.  However, this is a
            Learning Tree assignment.  Please either change the assessment type to a non-Learning Tree type or remove these assessments.
          </span>
  </b-alert>
  </div>
</template>

<script>
export default {
  name: 'AssessmentTypeWarnings',
  props: {
    assessmentType: { type: String, default: '' },
    openEndedQuestionsInRealTime: { type: String, default: '' },
    learningTreeQuestionsInNonLearningTree: { type: String, default: '' },
    nonLearningTreeQuestions: { type: String, default: '' }

  }
}
</script>

<style scoped>

</style>
