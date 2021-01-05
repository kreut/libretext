<template>
  <div>
    <b-modal
      id="modal-assignment-properties"
      ref="modal"
      title="Assignment Properties"
      ok-title="Submit"
      size="lg"
      @ok="submitAssignmentInfo"
      @hidden="resetModalForms"
    >
      <b-tooltip target="internal"
                 delay="250"
      >
        Get questions from the Adapt database or from the Query library
      </b-tooltip>
      <b-tooltip target="late_deduction_application_period_tooltip"
                 delay="250"
      >
        Enter a timeframe such as 5 minutes, 3 hours, or 1 day. As a concrete example, if the Late Deduction percent
        is 20%
        and the timeframe is 1 hour, then if a student uploads the file 1 hour and 40 minutes late, then the percent
        is applied twice
        and they'll have a 40% deduction when computing the score.
      </b-tooltip>
      <b-tooltip target="external"
                 delay="250"
      >
        Use questions outside of Adapt and manually input scores into the grade book
      </b-tooltip>
      <b-tooltip target="delayed"
                 delay="250"
      >
        Scores and solutions are not automatically released. This type of assessment works well
        for open-ended questions.
      </b-tooltip>

      <b-tooltip target="real_time"
                 delay="250"
      >
        Scores and solutions are released in real time, providing students with immediate feedback.
      </b-tooltip>
      <b-tooltip target="learning_tree"
                 delay="250"
      >
        Students are provided with Learning Trees which consist of a root question node and remediation nodes.
        The remediation nodes provide the student with supplementary material to help them answer the initial
        question.
      </b-tooltip>
      <b-tooltip target="min_time_needed_in_learning_tree_tooltip"
                 delay="250"
      >
        The minimum time a student must be in a Learning Tree before they can earn a percent of the
        original question points.
      </b-tooltip>
      <b-tooltip target="percent_earned_for_exploring_learning_tree_tooltip"
                 delay="250"
      >
        The percent of the question points that a student earns for entering the Learning Tree for at least the
        minimum time as described above.
      </b-tooltip>

      <b-tooltip target="submission_count_percent_decrease_tooltip"
                 delay="250"
      >
        For each new attempt after their first free attempt, students will be awarded the total number of new
        attempts multiplied by the percent decrease of the total score in addition to the percent awarded for entering
        the Learning Tree.
      </b-tooltip>

      <b-form ref="form" @submit="createAssignment">
        <div v-if="isLocked()">
          <b-alert variant="info" show>
            <strong>This assignment is locked. Since students have submitted responses, the only
              items that you can update are the assignment's name, the assignment's available/due dates,
              the assignment's group, the instructions, and whether to include the assignment in the final score.
            </strong>
          </b-alert>
        </div>

        <b-form-group
          id="name"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Name"
          label-for="name"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-input
                id="name"
                v-model="form.name"
                lg="7"
                type="text"
                :class="{ 'is-invalid': form.errors.has('name') }"
                @keydown="form.errors.clear('name')"
              />
              <has-error :form="form" field="name" />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="available_from"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Available on"
          label-for="Available on"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                v-model="form.available_from_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('available_from_date') }"
                @shown="form.errors.clear('available_from_date')"
              />
              <has-error :form="form" field="available_from_date" />
            </b-col>
            <b-col>
              <b-form-timepicker v-model="form.available_from_time"
                                 locale="en"
                                 :class="{ 'is-invalid': form.errors.has('available_from_time') }"
                                 @shown="form.errors.clear('available_from_time')"
              />
              <has-error :form="form" field="available_from_time" />
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          id="due"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Due Date"
          label-for="Due Date"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                v-model="form.due_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('due') }"
                @shown="form.errors.clear('due')"
              />
              <has-error :form="form" field="due" />
            </b-col>
            <b-col>
              <b-form-timepicker v-model="form.due_time"
                                 locale="en"
                                 :class="{ 'is-invalid': form.errors.has('due_time') }"
                                 @shown="form.errors.clear('due_time')"
              />
              <has-error :form="form" field="due_time" />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="assignment_group"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Assignment Group"
          label-for="Assignment Group"
        >
          <b-form-row>
            <b-col lg="5">
              <b-form-select v-model="form.assignment_group_id"
                             :options="assignmentGroups"
                             :class="{ 'is-invalid': form.errors.has('assignment_group_id') }"
                             @change="checkGroupId(form.assignment_group_id)"
              />
              <has-error :form="form" field="assignment_group_id" />
            </b-col>
            <b-modal
              id="modal-create-assignment-group"
              ref="modal"
              title="Create Assignment Group"
              ok-title="Submit"
              @ok="handleCreateAssignmentGroup"
              @hidden="resetAssignmentGroupForm"
            >
              <b-form-row>
                <b-form-group
                  id="create_assignment_group"
                  label-cols-sm="4"
                  label-cols-lg="5"
                  label="Assignment Group"
                  label-for="Assignment Group"
                >
                  <b-form-input
                    id="assignment_group"
                    v-model="assignmentGroupForm.assignment_group"
                    type="text"
                    placeholder=""
                    :class="{ 'is-invalid': assignmentGroupForm.errors.has('assignment_group') }"
                    @keydown="assignmentGroupForm.errors.clear('assignment_group')"
                  />
                  <has-error :form="assignmentGroupForm" field="assignment_group" />
                </b-form-group>
              </b-form-row>
            </b-modal>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="source"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Source"
          label-for="Source"
        >
          <b-form-radio-group v-model="form.source" stacked
                              :disabled="isLocked()"
          >
            <span @click="resetSubmissionFilesAndPointsPerQuestion">

              <b-form-radio name="source" value="a">Internal <span id="internal" class="text-muted"><b-icon
                icon="question-circle"
              /></span></b-form-radio>
            </span>
            <b-form-radio name="source" value="x">
              External <span id="external" class="text-muted"><b-icon
                icon="question-circle"
              /></span>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <b-form-group
          id="scoring_type"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Scoring Type"
          label-for="Scoring Type"
        >
          <b-form-radio-group v-model="form.scoring_type" stacked
                              :disabled="isLocked()"
          >
            <span @click="form.students_can_view_assignment_statistics = 1">
              <b-form-radio value="p">Points</b-form-radio></span>
            <span @click="canSwitchToCompleteIncomplete">
              <span @click="resetSubmissionFilesAndPointsPerQuestion">
                <b-form-radio value="c">Complete/Incomplete</b-form-radio>
              </span>
            </span>
          </b-form-radio-group>
        </b-form-group>
        <div v-show="form.source === 'a'">
          <b-form-group
            v-show="form.scoring_type === 'p'"
            id="default_points_per_question"
            label-cols-sm="4"
            label-cols-lg="3"
            label="Default Points/Question"
            label-for="default_points_per_question"
          >
            <b-form-row>
              <b-col lg="3">
                <b-form-input
                  id="default_points_per_question"
                  v-model="form.default_points_per_question"
                  type="text"
                  placeholder=""
                  :class="{ 'is-invalid': form.errors.has('default_points_per_question') }"
                  :disabled="isLocked()"
                  @keydown="form.errors.clear('default_points_per_question')"
                />
                <has-error :form="form" field="default_points_per_question" />
              </b-col>
            </b-form-row>
          </b-form-group>
        </div>
      </b-form>

      <b-form-group
        v-show="form.source === 'a'"
        id="assessment_type"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Assessment Type"
        label-for="Assessment Type"
      >
        <b-form-radio-group v-model="form.assessment_type"
                            stacked
                            :disabled="isLocked()"
        >
          <span @click="!!isLocked() && showRealTimeOptions">
            <b-form-radio name="assessment_type" value="real time">
              Real Time Graded Assessments <span id="real_time" class="text-muted"><b-icon
                icon="question-circle"
              />
              </span></b-form-radio>
          </span>
          <span @click="!!isLocked() && showDelayedOptions">
            <b-form-radio name="assessment_type" value="delayed">
              Delayed Graded Assessments <span id="delayed" class="text-muted"><b-icon
                icon="question-circle"
              /></span>
            </b-form-radio>
          </span>
          <span @click="checkIfScoringTypeOfPoints">
            <b-form-radio name="assessment_type" value="learning tree">
              Learning Tree Assessments <span id="learning_tree" class="text-muted"><b-icon
                icon="question-circle"
              />
              </span>
            </b-form-radio>
          </span>
        </b-form-radio-group>
      </b-form-group>
      <div v-show="form.assessment_type === 'learning tree'">
        <b-form-group
          id="min_time_needed_in_learning_tree"
          label-cols-sm="8"
          label-cols-lg="7"
          label-for="min_time_needed_in_learning_tree"
        >
          <template slot="label">
            <b-icon
              icon="tree" variant="success"
            />
            Minimum Number of Minutes Exploring Learning Tree <span id="min_time_needed_in_learning_tree_tooltip"
                                                                    class="text-muted"
            ><b-icon
              icon="question-circle"
            /></span>
          </template>
          <b-form-row>
            <b-col lg="5">
              <b-form-input
                id="min_time_needed_in_learning_tree"
                v-model="form.min_time_needed_in_learning_tree"
                type="text"
                placeholder="In Minutes"
                :disabled="isLocked()"
                :class="{ 'is-invalid': form.errors.has('min_time_needed_in_learning_tree') }"
                @keydown="form.errors.clear('min_time_needed_in_learning_tree')"
              />
              <has-error :form="form" field="min_time_needed_in_learning_tree" />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="percent_earned_for_exploring_learning_tree"
          label-cols-sm="7"
          label-cols-lg="6"
          label="Percent Earned For Exploring Learning Tree"
          label-for="percent_earned_for_exploring_learning_tree"
        >
          <template slot="label">
            <b-icon
              icon="tree" variant="success"
            />
            Percent Earned For Exploring Learning Tree <span id="percent_earned_for_exploring_learning_tree_tooltip"
                                                             class="text-muted"
            ><b-icon
              icon="question-circle"
            /></span>
          </template>
          <b-form-row>
            <b-col lg="5">
              <b-form-input
                id="percent_earned_for_exploring_learning_tree"
                v-model="form.percent_earned_for_exploring_learning_tree"
                type="text"
                placeholder="Out of 100"
                :disabled="isLocked()"
                :class="{ 'is-invalid': form.errors.has('percent_earned_for_exploring_learning_tree') }"
                @keydown="form.errors.clear('percent_earned_for_exploring_learning_tree')"
              />
              <has-error :form="form" field="percent_earned_for_exploring_learning_tree" />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="submission_count_percent_decrease"
          label-cols-sm="7"
          label-cols-lg="6"
          label-for="submission_count_percent_decrease"
        >
          <template slot="label">
            <b-icon
              icon="tree" variant="success"
            />
            Submission Count Percent Decrease <span id="submission_count_percent_decrease_tooltip" class="text-muted"><b-icon
              icon="question-circle"
            /></span>
          </template>
          <b-form-row>
            <b-col lg="5">
              <b-form-input
                id="submission_count_percent_decrease"
                v-model="form.submission_count_percent_decrease"
                type="text"
                placeholder="Out of 100"
                :disabled="isLocked()"
                :class="{ 'is-invalid': form.errors.has('submission_count_percent_decrease') }"
                @keydown="form.errors.clear('submission_count_percent_decrease')"
              />
              <has-error :form="form" field="submission_count_percent_decrease" />
            </b-col>
          </b-form-row>
        </b-form-group>
      </div>
      <b-form-group
        v-show="form.scoring_type === 'p' && form.assessment_type === 'delayed' && form.source === 'a'"
        id="submission_files"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Submission Files"
        label-for="Submission Files"
      >
        <b-form-radio-group v-model="form.submission_files" stacked
                            :disabled="isLocked()"
        >
          <!-- <b-form-radio name="submission_files" value="a">At the assignment level</b-form-radio>-->
          <b-form-radio name="submission_files" value="q">
            At the question level
          </b-form-radio>
          <b-form-radio name="submission_files" value="0">
            Students cannot upload files
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-show="form.source === 'a'"
        id="late_policy"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Late Policy"
        label-for="Late Policy"
      >
        <b-form-radio-group v-model="form.late_policy" stacked
                            :disabled="isLocked()"
        >
          <!-- <b-form-radio name="submission_files" value="a">At the assignment level</b-form-radio>-->
          <b-form-radio value="not accepted">
            Do not accept late
          </b-form-radio>
          <span @click="initLateValues">
            <b-form-radio value="marked late">
              Accept but mark late
            </b-form-radio>
            <b-form-radio value="deduction">
              Accept late with a deduction
            </b-form-radio>
          </span>
        </b-form-radio-group>
      </b-form-group>
      <div v-show="form.late_policy === 'deduction'">
        <b-form-group
          id="late_deduction_percent"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Late Deduction Percent"
          label-for="late_deduction_percent"
        >
          <b-form-row>
            <b-col lg="4">
              <b-form-input
                id="late_deduction_percent"
                v-model="form.late_deduction_percent"
                type="text"
                placeholder="Out of 100"
                :class="{ 'is-invalid': form.errors.has('late_deduction_percent') }"
                @keydown="form.errors.clear('late_deduction_percent')"
              />
              <has-error :form="form" field="late_deduction_percent" />
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          id="late_deduction_application_period"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Late Deduction Applied"
          label-for="late_deduction_application_period"
        >
          <b-form-radio-group v-model="form.late_deduction_applied_once"
                              stacked
                              :disabled="isLocked()"
          >
            <span @click="form.late_deduction_application_period = ''">
              <b-form-radio value="1">
                Just once
              </b-form-radio>
            </span>
            <b-form-radio class="mt-2" value="0">
              <b-row>
                <b-col lg="2" class="mt-1">
                  Every
                </b-col>
                <b-col lg="6">
                  <b-form-input
                    id="late_deduction_application_period"
                    v-model="form.late_deduction_application_period"
                    :disabled="parseInt(form.late_deduction_applied_once) === 1"
                    type="text"
                    :class="{ 'is-invalid': form.errors.has('late_deduction_application_period') }"
                    @keydown="form.errors.clear('late_deduction_application_period')"
                  />
                  <has-error :form="form" field="late_deduction_application_period" />
                </b-col>
                <span id="late_deduction_application_period_tooltip">
                  <b-icon class="text-muted" icon="question-circle" /></span>
              </b-row>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
      <b-form-group
        v-if="form.late_policy !== 'not accepted'"
        id="last"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Late Policy Deadline"
        label-for="Late Policy Deadline"
      >
        <b-form-row>
          <b-col lg="7">
            <b-form-datepicker
              v-model="form.late_policy_deadline_date"
              :min="min"
              :class="{ 'is-invalid': form.errors.has('late_policy_deadline') }"
              :disabled="Boolean(solutionsReleased) && assessmentType !== 'real time'"
              @shown="form.errors.clear('late_policy_deadline')"
            />
            <has-error :form="form" field="late_policy_deadline" />
          </b-col>
          <b-col>
            <b-form-timepicker v-model="form.late_policy_deadline_time"
                               locale="en"
                               :class="{ 'is-invalid': form.errors.has('late_policy_deadline_time') }"
                               :disabled="Boolean(solutionsReleased) && assessmentType !== 'real time'"
                               @shown="form.errors.clear('late_policy_deadline_time')"
            />
            <has-error :form="form" field="late_policy_deadline_time" />
          </b-col>
        </b-form-row>
      </b-form-group>
      <b-form-group
        id="include_in_weighted_average"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Include In Final Score"
        label-for="Include In Final Score"
      >
        <b-form-radio-group v-model="form.include_in_weighted_average" stacked>
          <b-form-radio name="include_in_weighted_average" value="1">
            Include the assignment in computing a final
            weighted score
          </b-form-radio>
          <b-form-radio name="include_in_weighted_average" value="0">
            Do not include the assignment in computing a
            final weighted score
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-show="form.source === 'x' && form.scoring_type === 'p'"
        id="total_points"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Total Points"
        label-for="Total Points"
      >
        <b-form-row>
          <b-col lg="3">
            <b-form-input
              id="external_source_points"
              v-model="form.external_source_points"
              type="text"
              placeholder=""
              :class="{ 'is-invalid': form.errors.has('external_source_points') }"
              @keydown="form.errors.clear('external_source_points')"
            />
            <has-error :form="form" field="external_source_points" />
          </b-col>
        </b-form-row>
      </b-form-group>

      <b-form-group
        v-show="form.source === 'a'"
        id="instructions"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Instructions"
        label-for="instructions"
      >
        <b-form-row>
          <b-form-textarea
            id="instructions"
            v-model="form.instructions"
            type="text"
            placeholder="(Optional)"
            rows="3"
          />
        </b-form-row>
      </b-form-group>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'

import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'

import { isLocked, getAssignments } from '~/helpers/Assignments'

import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  data: () => ({
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    title: '',
    assessmentType: '',
    assignmentGroups: [{ value: null, text: 'Please choose one' }],
    isLoading: false,
    solutionsReleased: 0,
    assignmentId: false, // if there's an assignmentId it's an update
    assignments: [],
    showPointsPerQuestionTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: 'In case you only grade a random subset of questions, you can hide the number of points per question so that your students won\'t know which questions you\'ll be grading.'
    },
    completedOrCorrectOptions: [
      { item: 'correct', name: 'correct' },
      { item: 'completed', name: 'completed' }
    ],
    courseId: false,
    form: new Form({
      name: '',
      available_from: '',
      due: '',
      assessment_type: 'real time',
      min_time_needed_in_learning_tree: null,
      percent_earned_for_exploring_learning_tree: null,
      submission_count_percent_decrease: null,
      available_from_date: '',
      assignment_group_id: null,
      available_from_time: '09:00:00',
      due_date: '',
      due_time: '09:00:00',
      submission_files: '0',
      late_policy: 'not accepted',
      late_deduction_percent: null,
      late_deduction_applied_once: 1,
      late_policy_deadline_date: '',
      late_policy_deadline_time: '09:00:00',
      late_deduction_application_period: null,
      type_of_submission: 'correct',
      source: 'a',
      scoring_type: 'p',
      include_in_weighted_average: 1,
      num_submissions_needed: '2',
      default_points_per_question: 10,
      external_source_points: 100,
      instructions: ''
    }),
    hasAssignments: false,
    has_submissions_or_file_submissions: false,
    min: '',
    canViewAssignments: false,
    showNoAssignmentsAlert: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.getAssignments = getAssignments
    this.isLocked = isLocked
  },
  mounted () {
    this.courseId = this.$route.params.courseId

    this.getAssignmentGroups(this.courseId)
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  },
  methods: {
    checkIfScoringTypeOfPoints (event) {
      if (this.form.scoring_type === 'c') {
        event.preventDefault()
        this.$noty.info('Learning Tree assessments types must have a Scoring Type of points.')
        return false
      }
    },
    canSwitchToCompleteIncomplete (event) {
      if (this.form.late_policy !== 'not accepted') {
        event.preventDefault()
        this.$noty.info('If you would like a Complete/Incomplete Scoring Type, please choose "Do not accept late" as your Late Policy.  You will still be able to grant individual extensions.', {
          timeout: 10000 })
        return false
      }
      if (this.form.assessment_type === 'learning tree') {
        event.preventDefault()
        this.$noty.info('Learning Tree assessments types must have a Scoring Type of points.')
        return false
      }
    },
    initLateValues (event) {
      if (this.form.scoring_type === 'c') {
        event.preventDefault()
        this.$noty.info('If you would like a Late Policy other than "Do not accept late, please change your Scoring Type to points.')
        return false
      }
      this.form.late_deduction_percent = null
      this.form.late_deduction_applied_once = 1
      this.form.late_deduction_application_period = null
      this.form.late_policy_deadline_date = this.form.due_date
      let start = this.$moment(this.$moment(this.form.due_date + ' ' + this.form.due_time), 'YYYY-MM-DD HH:mm:SS')
      start = start.add(this.$moment.duration(20, 'minutes'))
      this.form.late_policy_deadline_time = this.$moment(start, 'YYYY-MM-DD HH:mm:SS').format('HH:mm:00')
    },
    showDelayedOptions () {
      this.form.submission_files = 'q'
      this.form.min_time_needed_in_learning_tree = null
      this.form.percent_earned_for_exploring_learning_tree = null
      this.form.submission_count_percent_decrease = null
    },
    showRealTimeOptions () {
      this.form.min_time_needed_in_learning_tree = null
      this.form.percent_earned_for_exploring_learning_tree = null
      this.form.submission_count_percent_decrease = null
    },
    getGradeBook () {
      this.$router.push(`/courses/${this.courseId}/gradebook`)
    },
    getLockedQuestionsMessage (assignment) {
      if ((Number(assignment.has_submissions_or_file_submissions))) {
        return 'Since students have already submitted responses to this assignment, you won\'t be able to add or remove questions.'
      }
      if ((Number(assignment.solutions_released))) {
        return 'You have already released the solutions to this assignment, so you won\'t be able to add or remove questions.'
      }
    },

    async handleCreateAssignmentGroup (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.assignmentGroupForm.post(`/api/assignmentGroups/${this.courseId}`)
        console.log(data)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        let newAssignmentGroup = {
          value: data.assignment_group_info.assignment_group_id,
          text: data.assignment_group_info.assignment_group
        }

        this.assignmentGroups.splice(this.assignmentGroups.length - 1, 0, newAssignmentGroup)
        this.form.assignment_group_id = data.assignment_group_info.assignment_group_id
        this.$bvModal.hide('modal-create-assignment-group')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    checkGroupId (groupId) {
      if (groupId === -1) {
        this.$bvModal.show('modal-create-assignment-group')
      }
    },
    async getCourseInfo () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        this.title = `${data.course.name} Assignments`
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getAssignmentGroups (courseId) {
      try {
        const { data } = await axios.get(`/api/assignmentGroups/${courseId}`)
        if (data.error) {
          this.$noty.error(data.message)
          return false
        }
        for (let i = 0; i < data.assignment_groups.length; i++) {
          this.assignmentGroups.push({
            value: data.assignment_groups[i]['id'],
            text: data.assignment_groups[i]['assignment_group']
          })
        }
        this.assignmentGroups.push({
          value: -1,
          text: 'Create new group'
        })
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initAddAssignment () {
      this.has_submissions_or_file_submissions = 0
      this.solutionsReleased = 0
      this.form.assignment_group_id = null
      this.form.available_from_date = this.form.due_date = this.form.late_policy_deadline_date = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
      this.form.available_from_time = this.form.due_time = this.form.late_policy_deadline_time = this.$moment(this.$moment(), 'YYYY-MM-DD HH:mm:SS').format('HH:mm:00')

      this.form.late_policy = 'not accepted'
      this.form.late_deduction_percent = null
      this.form.late_deduction_applied_once = 1
      this.form.late_deduction_application_period = null
      this.form.source = 'a'
      this.form.default_points_per_question = 10
      this.form.instructions = ''
      this.form.assessment_type = 'real time'
      this.form.min_time_needed_in_learning_tree = null
      this.form.percent_earned_for_exploring_learning_tree = null
      this.form.submission_count_percent_decrease = null
    },
    async submitShowAssignment (assignment) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-assignment/${Number(assignment.shown)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.shown = !assignment.shown
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitShowAssignmentStatistics (assignment) {
      if (!assignment.students_can_view_assignment_statistics && !assignment.show_scores) {
        this.$noty.info('If you would like students to view the assignment statistics, please first allow them to view the scores.')
        return false
      }

      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-assignment-statistics/${Number(assignment.students_can_view_assignment_statistics)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.students_can_view_assignment_statistics = !assignment.students_can_view_assignment_statistics
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitShowPointsPerQuestion (assignment) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-points-per-question/${Number(assignment.show_points_per_question)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.show_points_per_question = !assignment.show_points_per_question
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitShowScores (assignment) {
      if (assignment.students_can_view_assignment_statistics && assignment.show_scores) {
        this.$noty.info('If you would like students to view the scores, please first hide the assignment statistics.')
        return false
      }
      console.log(assignment)
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-scores/${Number(assignment.show_scores)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.show_scores = !assignment.show_scores
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitSolutionsReleased (assignment) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/solutions-released/${Number(assignment.solutions_released)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.solutions_released = !assignment.solutions_released
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleReleaseSolutions (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.patch(`/api/assignments/${this.assignmentId}/release-solutions`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-release-solutions-show-scores')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    resetSubmissionFilesAndPointsPerQuestion () {
      this.form.default_points_per_question = 10
      this.form.submission_files = 0
      this.form.assessment_type = 'real time'
      this.form.students_can_view_assignment_statistics = 0
      this.form.external_source_points = 100
      this.form.errors.clear('default_points_per_question')
      this.form.errors.clear('external_source_points')
    },
    editAssignment (assignment) {
      console.log(assignment)

      this.has_submissions_or_file_submissions = (assignment.has_submissions_or_file_submissions === 1)
      this.solutionsReleased = assignment.solutions_released
      this.assignmentId = assignment.id
      this.number_of_questions = assignment.number_of_questions

      this.form.name = assignment.name
      this.form.assessment_type = this.assessmentType = assignment.assessment_type
      this.form.available_from_date = assignment.available_from_date
      this.form.available_from_time = assignment.available_from_time
      this.form.due_date = assignment.due_date
      this.form.min_time_needed_in_learning_tree = assignment.min_time_needed_in_learning_tree
      this.form.percent_earned_for_exploring_learning_tree = assignment.percent_earned_for_exploring_learning_tree
      this.form.submission_count_percent_decrease = assignment.submission_count_percent_decrease
      this.form.due_time = assignment.due_time
      this.form.late_policy = assignment.late_policy
      this.form.late_deduction_applied_once = +(assignment.late_deduction_application_period === 'once')
      this.form.late_deduction_application_period = !this.form.late_deduction_applied_once ? assignment.late_deduction_application_period : ''
      this.form.late_policy_deadline_time = assignment.late_policy_deadline_time
      this.form.late_policy_deadline_date = assignment.late_policy_deadline_date
      this.form.late_deduction_percent = assignment.late_deduction_percent
      this.form.assignment_group_id = assignment.assignment_group_id
      this.form.include_in_weighted_average = assignment.include_in_weighted_average
      this.form.source = assignment.source
      this.form.instructions = assignment.instructions
      this.form.type_of_submission = assignment.type_of_submission
      this.form.submission_files = assignment.submission_files
      this.form.num_submissions_needed = assignment.num_submissions_needed
      this.form.default_points_per_question = assignment.default_points_per_question
      this.form.scoring_type = assignment.scoring_type
      this.form.students_can_view_assignment_statistics = assignment.students_can_view_assignment_statistics
      this.form.external_source_points = (assignment.source === 'x' && assignment.scoring_type === 'p')
        ? assignment.external_source_points
        : ''
      this.$bvModal.show('modal-assignment-properties')
    },
    getAssignmentView (role, assignment) {
      if (assignment.source === 'x') {
        this.$noty.info('This assignment has no questions to view because it is an external assignment.  To add questions, please edit the assignment and change the Source to Adapt.')
        return false
      }

      this.$router.push(`/instructors/assignments/${assignment.id}/information`)
    },
    getSubmissionFileView (assignmentId, submissionFiles) {
      if (submissionFiles === 0) {
        this.$noty.info('If you would like students to upload files as part of the assignment, please edit this assignment.')
        return false
      }
      let type
      switch (submissionFiles) {
        case ('q'):
          type = 'question'
          break
        case ('a'):
          type = 'assignment'
          break
      }

      this.$router.push(`/assignments/${assignmentId}/${type}-files`)
    },
    submitAssignmentInfo (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.form.available_from = this.form.available_from_date + ' ' + this.form.available_from_time
      this.form.due = this.form.due_date + ' ' + this.form.due_time
      this.form.late_policy_deadline = this.form.late_policy_deadline_date + ' ' + this.form.late_policy_deadline_time
      !this.assignmentId ? this.createAssignment() : this.updateAssignment()
    },
    async updateAssignment () {
      try {
        const { data } = await this.form.patch(`/api/assignments/${this.assignmentId}`)

        console.log(data)
        this.$noty[data.type](data.message)
        this.resetAll('modal-assignment-properties')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async createAssignment () {
      try {
        this.form.course_id = this.courseId
        const { data } = await this.form.post(`/api/assignments`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-assignment-properties')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async resetAll (modalId) {
      await this.$parent.getAssignments()
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    resetAssignmentGroupForm () {
      this.assignmentGroupForm.errors.clear()
      this.assignmentGroupForm.assignment_group = ''
    },
    resetModalForms () {
      this.form.name = ''
      this.form.available_from_date = ''
      this.form.available_from_time = '09:00:00'
      this.form.due_date = ''
      this.form.due_time = '09:00:00'
      this.form.type_of_submission = 'correct'
      this.form.num_submissions_needed = '2'
      this.form.submission_files = 'q'
      this.form.default_points_per_question = '10'
      this.form.scoring_type = 'p'

      this.assignmentId = false
      this.form.errors.clear()
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
