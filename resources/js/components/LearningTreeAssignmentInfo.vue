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
      The minimum number of non-root node assessments that students will have to answer correctly within a
      branch/tree before
      being able to retry the original assessment.
    </b-tooltip>
    <b-tooltip target="min_time_tooltip"
               delay="250"
               triggers="hover focus"
    >
      The minimum amount of time (in minutes) that a student will have to spend in either a branch or tree before being
      able to
      retry the original assessment.
    </b-tooltip>
    <b-tooltip target="free_pass_for_satisfying_learning_tree_criteria_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If you choose "Yes", then the student will be able to attempt the original question a second time without penalty
      after successfully
      exploring the Learning Tree. Otherwise, a penalty will be applied starting with the second submission.
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
                          @change="initNumberOfDoOvers($event)"
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
        Minimum Time (in minutes)*
        <QuestionCircleTooltip id="min_time_tooltip"/>
      </template>
      <b-form-row>
        <b-col :lg="inModal ? 3 : 2">
          <b-form-input
            id="min_time"
            v-model="form.min_time"
            required
            type="text"
            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
            :class="{ 'is-invalid': form.errors.has('min_time') }"
            @keydown="form.errors.clear('min_time')"
          />
          <has-error :form="form" field="min_time"/>
        </b-col>
      </b-form-row>
    </b-form-group>
    <div v-if="form.learning_tree_success_level === 'branch'">
      <b-form-group
        label-cols-sm="5"
        :label-cols-lg="inModal ? 4 : 3"
        label-for="number_of_successful_branches_for_a_reset"
      >
        <template slot="label">
          <b-icon
            icon="tree" variant="success"
          />
          Number of successful branches for a reset*
          <QuestionCircleTooltip id="number_of_successful_branches_for_a_reset_tooltip"/>
        </template>
        <b-tooltip target="number_of_successful_branches_for_a_reset_tooltip"
                   delay="250"
                   triggers="hover focus"
        >
          The number of successful branches a student must achieve in order to
          reset the
          original assessment.
        </b-tooltip>
        <b-form-row>
          <b-col :lg="inModal ? 3 : 2">
            <b-form-input
              id="number_of_successful_branches_for_a_reset"
              v-model="form.number_of_successful_branches_for_a_reset"
              required
              type="text"
              :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
              :class="{ 'is-invalid': form.errors.has('number_of_successful_branches_for_a_reset') }"
              @keydown="form.errors.clear('number_of_successful_branches_for_a_reset')"
            />
            <has-error :form="form" field="number_of_successful_branches_for_a_reset"/>
          </b-col>
        </b-form-row>
      </b-form-group>
      <b-form-group
        label-cols-sm="5"
        :label-cols-lg="inModal ? 4 : 3"
        label-for="number_of_allowed_resets"
      >
        <template slot="label">
          <b-icon
            icon="tree" variant="success"
          />
          Number of resets*
          <QuestionCircleTooltip id="number_of_resets_tooltip"/>
        </template>
        <b-tooltip target="number_of_resets_tooltip"
                   delay="250"
                   triggers="hover focus"
        >
          Each time a student satisfies the success criteria, the number of attempts will reset to 0, giving the student
          the option
          of a reset. If you choose "Maximum Possible", then ADAPT will compute this value based on the number of
          branches in your tree.
        </b-tooltip>
        <b-form-select id="number_of_resets"
                       v-model="form.number_of_resets"
                       required
                       :options="numberOfDoOversOptions"
                       style="width:75px"
        />
        <input type="hidden" class="form-control is-invalid">
        <div class="help-block invalid-feedback">
          {{ form.errors.get('number_of_resets') }}
        </div>
      </b-form-group>

    </div>
    <b-form-group
      label-cols-sm="5"
      :label-cols-lg="inModal ? 4 : 3"
      label-for="free_pass_for_satisfying_learning_tree_criteria"
    >
      <template slot="label">
        <b-icon
          icon="tree" variant="success"
        />
        Free pass for satisfying the success criteria*
        <QuestionCircleTooltip id="free_pass_for_satisfying_learning_tree_criteria_tooltip"/>
      </template>
      <b-form-radio-group v-model="form.free_pass_for_satisfying_learning_tree_criteria"
                          required
                          stacked
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
    numberOfDoOversOptions: [
      { text: '1', value: '1' },
      { text: '2', value: '2' },
      { text: '3', value: '3' },
      { text: '4', value: '4' },
      { text: '5', value: '5' }
    ],
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
    if (this.branchItems) {
      for (let i = 0; i < this.branchItems.length; i++) {
        numAssessments += this.branchItems[i].assessments
      }
      let errorMessage
      if (this.form.learning_tree_success_criteria === 'assessment based' && numAssessments < this.form.min_number_of_successful_assessments) {
        errorMessage = `The Learning Tree only has ${numAssessments} assessments but students need to complete a minimum of ${this.form.min_number_of_successful_assessments} before they can resubmit.`
        this.form.errors.set('min_number_of_successful_assessments', errorMessage)
      }

      if (this.branchItems.length < this.form.number_of_successful_branches_for_a_reset) {
        errorMessage = `The Learning Tree only has ${this.branchItems.length} branches but students need to successfully complete a minimum of ${this.form.number_of_successful_branches_for_a_reset} before they can resubmit.`
        this.form.errors.set('number_of_successful_branches_for_a_reset', errorMessage)
      }
    }
  },
  methods: {
    initNumberOfDoOvers (event) {
      if (event === 'branch') {
        this.form.number_of_resets = '1'
      }
    },
    updateShowMinAssessmentsOrTime (event) {
      this.showMinimumNumberOfSuccessfulAssessments = event === 'assessment based'
    }
  }
}
</script>

<style scoped>

</style>
