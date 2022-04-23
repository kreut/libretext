<template>
  <div>
    <div v-show="h5pNonAdaptQuestions">
    <b-alert show>
      <p>This assignment has some questions which may not function well with ADAPT:</p>
      <p>{{ h5pNonAdaptQuestions }}</p>
      For H5P
      questions to be compatible with
      performance based assignments, each question should have a single submission. Questions may work with completion
      based assignments. However, only the first submission will be counted. Please review any potentially problematic questions in Student View.
    </b-alert>
    </div>
    <b-alert :show="h5pQuestionsWithAnonymousUsers">
      This assignment has H5P assessments in a course which allows anonymous users.
      Because of the nature of H5P, any user will be able to view both the questions and solutions when logged in as
      Anonymous.
    </b-alert>
    <b-alert :show="h5pQuestionsWithRealTimeAndMultipleAttempts">
      This assignment has H5P assessments in an assignment that provides real time feedback and allows multiple
      attempts. Due to the nature of
      H5P, students will see the correct responses after their first attempt.
    </b-alert>

    <b-alert :show="betaAssignmentsExist">
      This is an assignment that is part of an Alpha course which has Beta assignments which
      are tethered to it. If you add/remove assessments from this assignment Beta instructors will have to approve the
      changes
      if they would like to be reflected in their assignments.
    </b-alert>
    <b-alert :show="assessmentType === 'clicker'">
      Important: clicker assignments can only be used in conjunction with H5P true-false and multiple choice
      assessments.
    </b-alert>
    <b-alert :show="openEndedQuestionsInRealTime !== ''">
      It looks like the following assessments have an open-ended component: {{ openEndedQuestionsInRealTime }}. These
      types of assessments are appropriate for
      delayed assessments. Please either change the assessment type to Delayed or remove these assessments.
    </b-alert>
    <b-alert :show="learningTreeQuestionsInNonLearningTree !== ''">
      It looks like the following assessments are Learning Tree assessments: {{
        learningTreeQuestionsInNonLearningTree
      }}. However, this is not a
      Learning Tree assignment. Please either change the assessment type to Learning Tree or remove these assessments.
    </b-alert>
    <b-alert :show="nonLearningTreeQuestions !== ''">
      It looks like the following assessments are non-Learning Tree assessments: {{ nonLearningTreeQuestions }}.
      However, this is a
      Learning Tree assignment. Please either change the assessment type to a non-Learning Tree type or remove these
      assessments.
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
    nonLearningTreeQuestions: { type: String, default: '' },
    h5pNonAdaptQuestions: { type: String, default: '' },
    betaAssignmentsExist: { type: Boolean, default: false },
    h5pQuestionsWithAnonymousUsers: { type: Boolean, default: false },
    h5pQuestionsWithRealTimeAndMultipleAttempts: { type: Boolean, default: false }
  }
}
</script>

<style scoped>

</style>
