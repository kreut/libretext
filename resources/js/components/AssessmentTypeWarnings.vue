<template>
  <div>
    <div v-show="h5pNonAdaptQuestions">
      <b-alert show>
        <p>This assignment has some questions which may not function well with ADAPT:</p>
        <p>{{ h5pNonAdaptQuestions }}</p>
        Since these questions are not on the <a
        href="https://chem.libretexts.org/Courses/Remixer_University/Mastering_ADAPT%3A_A_User%27s_Guide/07%3A_Building_H5P_Assessments/H5P-ADAPT_Assessment_Status"
        target="blank"
      >list of Adapt ready H5P questions</a> they may not work as expected.
        Please attempt these question in Student View before assigning them to your students.
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
    <b-alert :show="openEndedQuestionsInRealTime !== ''">
      It looks like the following assessments have an open-ended component: {{ openEndedQuestionsInRealTime }}. These
      types of assessments are appropriate for
      delayed assessments. Please either change the assessment type to Delayed or remove these assessments.
    </b-alert>
    <b-alert :show="pendingQuestionRevisions !== ''">
      The following questions have pending revisions: {{ pendingQuestionRevisions }}. If you would like to update the
      question to the most current revision, please go to that question.
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
    h5pQuestionsWithRealTimeAndMultipleAttempts: { type: Boolean, default: false },
    pendingQuestionRevisions: { type: String, default: '' }
  }
}
</script>

<style scoped>

</style>
