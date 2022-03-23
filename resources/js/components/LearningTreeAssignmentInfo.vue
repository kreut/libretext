<template>
  <div>
    <b-tooltip target="learning_tree_success_level_tooltip"
               delay="250"
               triggers="hover focus"
    >
      A student will be able to try the root node assessment again after they satisfy a set of criteria defined at
      the branch level or at the
      tree level.
    </b-tooltip>
    <b-tooltip target="learning_tree_success_criteria_tooltip"
               delay="250"
               triggers="hover focus"
    >
      An assessment based criteria will ensure that students correctly answer assessments which support underlying
      concepts. A time based criteria will
      ensure that they have spent a sufficient amount of time exploring the remediation material.
    </b-tooltip>
    <b-tooltip target="min_number_of_successful_assessments_tooltip"
               delay="250"
               triggers="hover focus"
    >
      The minimum number of non-root node assessments that students will have to answer within correctly within a
      branch or the tree before
      being able to retry the original assessment.
    </b-tooltip>
    <b-tooltip target="min_time_tooltip"
               delay="250"
               triggers="hover focus"
    >
      The minimum amount of time that a student will have to spend in either a branch or tree before being able to
      retry the original assessment.
    </b-tooltip>
    <b-tooltip target="free_pass_for_satisfying_learning_tree_criteria_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If you choose "Yes", then the student will be able to attempt the original question one more time without penalty.
      Otherwise, a penalty will be applied starting with the second submission.
    </b-tooltip>
    <b-form-group
      label-cols-sm="5"
      :label-cols-lg="inModal ? 4 : 3"
      label-for="learning_tree_success_level"
    >
      <template slot="label">
        <b-icon
          icon="tree" variant="success"
        />
        Success determined at the*
        <QuestionCircleTooltip id="learning_tree_success_level_tooltip"/>
      </template>
      <b-form-radio-group id="learning_tree_success_level"
                          v-model="form.learning_tree_success_level"
                          class="pt-2"
                          required
                          name="learning_tree_success_level"
                          :class="{ 'is-invalid': form.errors.has('learning_tree_success_level') }"
                          @keydown="form.errors.clear('learning_tree_success_level')"
      >
        <b-form-radio value="branch">
          Branch Level
        </b-form-radio>
        <b-form-radio value="tree">
          Tree Level
        </b-form-radio>
      </b-form-radio-group>
    </b-form-group>
    <b-form-group
      label-cols-sm="5"
      :label-cols-lg="inModal ? 4 : 3"
      class="pt-2"
      label-for="learning_tree_success_criteria"
    >
      <template slot="label">
        <b-icon
          icon="tree" variant="success"
        />
        Success Criteria*
        <QuestionCircleTooltip id="learning_tree_success_criteria_tooltip"/>
      </template>
      <b-form-radio-group v-model="form.learning_tree_success_criteria"
                          required
                          class="pt-2"
                          :disabled="isLocked(hasSubmissionsOrFileSubmissions)"
                          @change="updateShowMinAssessmentsOrTime($event)"
      >
        <b-form-radio value="assessment based">
          Assessment Based
        </b-form-radio>
        <b-form-radio value="time based">
          Time Based
        </b-form-radio>
      </b-form-radio-group>
    </b-form-group>
    <b-form-group
      v-show="showMinimumNumberOfSuccessfulAssessments"
      label-cols-sm="5"
      :label-cols-lg="inModal ? 4 : 3"
      label-for="min_number_of_successful_assessments"
    >
      <template slot="label">
        <b-icon
          icon="tree" variant="success"
        />
        Minimum number of successful assessments*
        <QuestionCircleTooltip id="min_number_of_successful_assessments_tooltip"/>
      </template>
      <b-form-row>
        <b-col lg="3">
          <b-form-input
            id="min_number_of_successful_assessments"
            v-model="form.min_number_of_successful_assessments"
            required
            type="text"
            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
            :class="{ 'is-invalid': form.errors.has('min_number_of_successful_assessments') }"
            @keydown="form.errors.clear('min_number_of_successful_assessments')"
          />
          <has-error :form="form" field="min_number_of_successful_assessments"/>
        </b-col>
      </b-form-row>
    </b-form-group>
    <b-form-group
      v-show="form.learning_tree_success_criteria === 'time based'"
      label-cols-sm="5"
      :label-cols-lg="inModal ? 4 : 3"
      label-for="min_time"
    >
      <template slot="label">
        <b-icon
          icon="tree" variant="success"
        />
        Minimum Time*
        <QuestionCircleTooltip id="min_time_tooltip"/>
      </template>
      <b-form-row>
        <b-col lg="3">
          <b-form-input
            id="min_time"
            v-model="form.min_time"
            required
            type="text"
            placeholder="In Minutes"
            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
            :class="{ 'is-invalid': form.errors.has('min_time') }"
            @keydown="form.errors.clear('min_time')"
          />
          <has-error :form="form" field="min_time"/>
        </b-col>
      </b-form-row>
    </b-form-group>
    <b-form-group
      v-if="form.learning_tree_success_level === 'branch'"
      label-cols-sm="5"
      :label-cols-lg="inModal ? 4 : 3"
      label-for="min_number_of_successful_branches"
    >
      <template slot="label">
        <b-icon
          icon="tree" variant="success"
        />
        Minimum number of successful branches*
        <QuestionCircleTooltip id="min_number_of_successful_branches_tooltip"/>
      </template>
      <b-tooltip target="min_number_of_successful_branches_tooltip"
                 delay="250"
                 triggers="hover focus"
      >
        If this value is set, then the student will have to be successful in this number of branches to be allowed to
        retry the
        original assessment.
      </b-tooltip>
      <b-form-row>
        <b-col lg="3">
          <b-form-input
            id="min_number_of_successful_branches"
            v-model="form.min_number_of_successful_branches"
            required
            type="text"
            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
            :class="{ 'is-invalid': form.errors.has('min_number_of_successful_branches') }"
            @keydown="form.errors.clear('min_number_of_successful_branches__required')"
          />
          <has-error :form="form" field="min_number_of_successful_branches"/>
        </b-col>
      </b-form-row>
    </b-form-group>
    <b-form-group
      label-cols-sm="5"
      :label-cols-lg="inModal ? 4 : 3"
      label-for="reset_points"
    >
      <template slot="label">
        <b-icon
          icon="tree" variant="success"
        />
        Free pass for satisfying the success criteria*
        <QuestionCircleTooltip id="free_pass_for_satisfying_learning_tree_criteria_tooltip"/>
      </template>
      <b-form-radio-group v-model="form.reset_points"
                          required
                          class="pt-2"
                          :disabled="isLocked(hasSubmissionsOrFileSubmissions)"
      >
        <b-form-radio value="1">
          Yes
        </b-form-radio>
        <b-form-radio value="0">
          No
        </b-form-radio>
      </b-form-radio-group>
    </b-form-group>
  </div>
</template>

<script>
import { isLocked } from '~/helpers/Assignments'
import { mapGetters } from 'vuex'

export default {
  name: 'LearningTreeAssignmentInfo',
  props: {
    inModal: {
      type: Boolean,
      default: true
    },
    branchItems: {
      type: Array,
      default: () => {
      }
    },
    form: {
      type: Object,
      default: () => {
      }
    },
    hasSubmissionsOrFileSubmissions: {
      type: Boolean,
      default: false
    },
    isBetaAssignment: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    showMinimumNumberOfSuccessfulAssessments: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.isLocked = isLocked
  },
  mounted () {
    this.showMinimumNumberOfSuccessfulAssessments = this.form.learning_tree_success_criteria === 'assessment based'
    let numAssessments = 0
    for (let i = 0; i < this.branchItems.length; i++) {
      numAssessments += this.branchItems[i].assessments
    }
    let errorMessage
    if (this.form.learning_tree_success_criteria === 'assessment based' && numAssessments < this.form.min_number_of_successful_assessments) {
      errorMessage = `The Learning Tree only has ${numAssessments} assessments but students need to complete a minimum of ${this.form.min_number_of_successful_assessments} before they can resubmit.`
      this.form.errors.set('min_number_of_successful_assessments', errorMessage)
    }

    if (this.branchItems.length < this.form.min_number_of_successful_branches) {
      errorMessage = `The Learning Tree only has ${this.branchItems.length} branches but students need to successfully complete a minimum of ${this.form.min_number_of_successful_branches} before they can resubmit.`
      this.form.errors.set('min_number_of_successful_branches', errorMessage)
    }

  },
  methods: {
    updateShowMinAssessmentsOrTime (event) {
      this.showMinimumNumberOfSuccessfulAssessments = event === 'assessment based'
    }
  }
}
</script>

<style scoped>

</style>
