<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-create-assignment-group'"/>
    <b-tooltip target="default-completion-scoring-mode-tooltip"
               delay="250"
               triggers="hover focus"
    >
      For assessments with both an auto-graded and open-ended component, students can receive full credit
      for submitting either piece, or you can apportion a percentage of the points to each piece. This can
      be customized for each question.
    </b-tooltip>
    <b-tooltip target="private-description-tooltip"
               delay="250"
               triggers="hover focus"
    >
      An optional description for the assignment. This description will only be viewable by you.
    </b-tooltip>
    <b-tooltip target="public-description-tooltip"
               delay="250"
               triggers="hover focus"
    >
      An optional description for the assignment. This description will be viewable by your students.
    </b-tooltip>
    <b-tooltip target="compiled_pdf_tooltip"
               delay="250"
               triggers="hover focus"
    >
      <p>
        If you choose this option, your students will upload a single compiled PDF and let ADAPT know which pages
        are associated with which questions.
      </p>
    </b-tooltip>
    <b-tooltip target="individual_assessment_upload_tooltip"
               delay="250"
               triggers="hover focus"
    >
      <p>
        If you choose this option, your students will upload individual submissions at the question level.
        Use this option if you don't plan on having non-PDF uploads such as text, images, or audio or if
        there is only one PDF submission.
      </p>
    </b-tooltip>
    <b-tooltip target="both_upload_tooltip"
               delay="250"
               triggers="hover focus"
    >
      <p>
        If you choose this option, your students will be able to upload either a compiled PDF or individual
        assessment uploads. Use this option if you have both several assessments which require a PDF submission
        and you also have non-PDF assessments such as text, images, or audio.
      </p>
    </b-tooltip>

    <b-tooltip target="internal"
               delay="250"
               triggers="hover focus"
    >
      Get questions from the ADAPT database or from the Query library
    </b-tooltip>
    <b-tooltip target="notifications_tooltip"
               delay="250"
               triggers="hover focus"
    >
      Students can optionally request to receive notifications for upcoming due dates. You may want to turn this
      option
      off for Exams and Clicker assignments so your students don't receive unnecessary notifications.
    </b-tooltip>
    <b-tooltip target="late_deduction_application_period_tooltip"
               delay="250"
               triggers="hover focus"
    >
      Enter a timeframe such as 5 minutes, 3 hours, or 1 day. As a concrete example, if the Late Deduction percent
      is 20%
      and the timeframe is 1 hour, then if a student uploads the file 1 hour and 40 minutes late, then the percent
      is applied twice
      and they'll have a 40% deduction when computing the score.
    </b-tooltip>
    <b-tooltip target="external"
               delay="250"
               triggers="hover focus"
    >
      Use questions outside of ADAPT and manually input scores into the grade book
    </b-tooltip>
    <b-tooltip target="delayed"
               delay="250"
               triggers="hover focus"
    >
      Scores and solutions are not automatically released. This type of assessment works well
      for open-ended questions.
    </b-tooltip>
    <b-tooltip target="performance"
               delay="250"
               triggers="hover focus"
    >
      Students are given credit for providing correct answers.
    </b-tooltip>
    <b-tooltip target="completion"
               delay="250"
               triggers="hover focus"
    >
      Students are given full credit for automatically graded submissions as long as they submit something.
      Open-ended submissions are manually graded. For questions with both automatically
      graded and open-ended submissions, students are awarded half of the points as long as they submit something
      for the automatically graded piece, with the remaining points awarded at the discretion of the grader.
    </b-tooltip>

    <b-tooltip target="real_time"
               delay="250"
               triggers="hover focus"
    >
      Scores and solutions are released in real time, providing students with immediate feedback.
    </b-tooltip>

    <b-tooltip target="add_assign_to_tooltip"
               delay="250"
               triggers="hover focus"
    >
      When adding new "assign tos", we first assign at the user level, then section level, and finally at the course
      level. So, if you
      assign one set of dates to everybody and another to a specific user, that user's dates will override those at
      the course level.
    </b-tooltip>

    <b-tooltip target="assign_to_tooltip"
               delay="250"
               triggers="hover focus"
    >
      You can assign to Everybody, a particular section (search by name) or student (search by name or email).
    </b-tooltip>
    <b-tooltip target="default_clicker_time_to_submit_tooltip"
               delay="250"
               triggers="hover focus"
    >
      The default amount of time (30 seconds, 2 minutes) your students will have to answer clicker questions.
      This can be changed at the individual question level.
    </b-tooltip>
    <b-tooltip target="learning_tree"
               delay="250"
               triggers="hover focus"
    >
      Students are provided with Learning Trees which consist of a root question node and remediation nodes.
      The remediation nodes provide the student with supplementary material to help them answer the initial
      question.
    </b-tooltip>
    <b-tooltip target="clicker"
               delay="250"
               triggers="hover focus"
    >
      Instructors manually open and close these real-time graded assessments.
    </b-tooltip>
    <b-tooltip target="number_of_randomized_assessments_tooltip"
               delay="250"
               triggers="hover focus"
    >
      ADAPT will randomly choose a subset of assessments from the total that you provide
    </b-tooltip>

    <b-tooltip target="final_submission_deadline_tooltip"
               delay="250"
               triggers="hover focus"
    >
      For assessments where you allow late submissions (either marked late or with penalty), this is the latest
      possible date for which you'll accept a submission. If your solutions are released, you will not be able to
      change this field.
    </b-tooltip>

    <b-tooltip target="libretexts_url_tooltip"
               delay="250"
               triggers="hover focus"
    >
      If your assignment is integrated into a Libretexts, provide the URL for the start of the assignment. When your
      students open the assignment in your LMS, they will be re-directed to this URL.
    </b-tooltip>

    <b-tooltip target="percent_earned_for_exploring_learning_tree_tooltip"
               delay="250"
               triggers="hover focus"
    >
      The percent of the question points that a student earns for entering the Learning Tree for at least the
      minimum time as described above.
    </b-tooltip>

    <b-tooltip target="default_open_ended_submission_type_tooltip"
               delay="250"
               triggers="hover focus"
    >
      Adjust this option if your assignment consists of open-ended questions. This option can be changed on a per
      question basis once you start adding questions to the assignment.
    </b-tooltip>

    <b-tooltip target="submission_count_percent_decrease_tooltip"
               delay="250"
               triggers="hover focus"
    >
      For each new attempt after their first free attempt, students will be awarded the total number of new
      attempts multiplied by the percent decrease of the total score in addition to the percent awarded for entering
      the Learning Tree.
    </b-tooltip>

    <b-form ref="form">
      <div v-if="isLocked(hasSubmissionsOrFileSubmissions)">
        <b-alert variant="info" show>
          <span class="font-weight-bold" v-html="isLockedMessage()"></span>
        </b-alert>
      </div>
      <div v-if="isBetaAssignment">
        <b-alert variant="info" show>
          <span class="font-weight-bold">This is a Beta assignment which is tethered to an Alpha assignment.
            You will only be able to change items specific to your course such as the assignment group,
            the late policy (if appropriate), whether you include this in the final score, student notifications, and the assign to information.</span>
        </b-alert>
      </div>
      <RequiredText/>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="name"
        label="Name*"
      >
        <b-form-row>
          <b-col lg="10">
            <b-form-input
              id="name"
              v-model="form.name"
              :disabled="isBetaAssignment"
              required
              type="text"
              :class="{ 'is-invalid': form.errors.has('name') }"
              @keydown="form.errors.clear('name')"
            />
            <has-error :form="form" field="name"/>
          </b-col>
        </b-form-row>
      </b-form-group>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="public_description"
      >
        <template slot="label">
          Public Description
          <QuestionCircleTooltip :id="'public-description-tooltip'"/>
        </template>
        <b-form-textarea
          id="public_description"
          v-model="form.public_description"
          style="margin-bottom: 23px"
          rows="2"
          max-rows="2"
          :disabled="isBetaAssignment"
        />
      </b-form-group>
      <b-form-group
        label-for="private_description"
        label-cols-sm="4"
        label-cols-lg="3"
      >
        <template slot="label">
          Private Description
          <QuestionCircleTooltip :id="'private-description-tooltip'"/>
        </template>
        <b-form-textarea
          id="private_description"
          v-model="form.private_description"
          style="margin-bottom: 23px"
          rows="2"
          max-rows="2"
          :disabled="isBetaAssignment"
        />
      </b-form-group>
      <b-form-group
        label-for="assignment_group"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Assignment Group*"
      >
        <b-form-row>
          <b-col lg="5">
            <b-form-select id="assignment_group"
                           v-model="form.assignment_group_id"
                           :options="assignmentGroups"
                           required
                           :class="{ 'is-invalid': form.errors.has('assignment_group_id') }"
                           @change="checkGroupId(form.assignment_group_id)"
            />
            <has-error :form="form" field="assignment_group_id"/>
          </b-col>
          <b-modal
            id="modal-per-question-solutions-availability"
            title="Solutions Availability"
            size="lg"
            hide-footer
          >
            <p>You can either choose to make solutions available on an automatic or manual basis.</p>
            <p><span class="font-weight-bold">Automatic:</span></p>
            <p>
              If you choose a finite number of attempts for your students, then students will see the solution if
              either
            </p>
            <ul>
              <li>They get the question completely correct.</li>
              <li>They cannot make any more attempts.</li>
            </ul>
            <p>If you choose an unlimited number of attempts, then students will see the solution if either</p>
            <ul>
              <li>They get the question completely correct.</li>
              <li>
                They request to see the solution. After requesting to see the solution, they will not be allowed to
                resubmit.
              </li>
            </ul>
            <p>
              If at any point you would like all of your students to see all of the solutions, you can always override
              this option by releasing the solutions in the Control Panel.
            </p>
            <p><span class="font-weight-bold">Manual:</span></p>
            <p>
              If you choose the manual option, then students will not see the solutions until you release the solutions
              in the Control Panel for this assignment.
            </p>
          </b-modal>
          <b-modal
            id="modal-create-assignment-group"
            ref="modal"
            title="Create Assignment Group"
          >
            <RequiredText/>
            <b-form-row>
              <b-form-group
                label-cols-sm="5"
                label-cols-lg="6"
                label-for="create_assignment_group"
                label="Assignment Group*"
              >
                <b-form-input
                  id="create_assignment_group"
                  v-model="assignmentGroupForm.assignment_group"
                  required
                  type="text"
                  placeholder=""
                  :class="{ 'is-invalid': assignmentGroupForm.errors.has('assignment_group') }"
                  @keydown="assignmentGroupForm.errors.clear('assignment_group')"
                />
                <has-error :form="assignmentGroupForm" field="assignment_group"/>
              </b-form-group>
            </b-form-row>
            <template #modal-footer>
              <b-button
                size="sm"
                class="float-right"
                @click="resetAssignmentGroupForm;$bvModal.hide('modal-create-assignment-group')"
              >
                Cancel
              </b-button>
              <b-button
                variant="primary"
                size="sm"
                class="float-right"
                @click="handleCreateAssignmentGroup"
              >
                Submit
              </b-button>
            </template>
          </b-modal>
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-if="!lms"
        id="source"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="source"
      >
        <template slot="label">
          Source*
        </template>
        <b-form-radio-group
          id="source"
          v-model="form.source"
          stacked
          required
          :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
          @change="initInternalExternalSwitch()"
        >
          <b-form-radio name="source" value="a">
            Internal
            <QuestionCircleTooltip :id="'internal'"/>
          </b-form-radio>

          <b-form-radio name="source" value="x">
            External
            <QuestionCircleTooltip :id="'external'"/>
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="scoring_type"
      >
        <template slot="label">
          Scoring Type*
        </template>
        <b-form-radio-group id="scoring_type" v-model="form.scoring_type" stacked
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                            required
        >
          <span @click="form.number_of_allowed_attempts=1;form.students_can_view_assignment_statistics = 1">
            <b-form-radio value="p">Performance <QuestionCircleTooltip :id="'performance'"/></b-form-radio>
          </span>
          <span @click="canSwitchToCompleteIncomplete">
            <span @click="resetOpenEndedResponsesAndPointsPerQuestion">
              <b-form-radio value="c">Completion <QuestionCircleTooltip :id="'completion'"/>
              </b-form-radio>
            </span>
          </span>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-show="form.scoring_type === 'c'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="completion_scoring_mode"
      >
        <template slot="label">
          Default Completion Scoring Mode*
          <QuestionCircleTooltip :id="'default-completion-scoring-mode-tooltip'"/>
        </template>
        <b-form-radio-group id="default_completion_scoring_mode"
                            v-model="form.default_completion_scoring_mode"
                            stacked
                            required
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                            :class="{ 'is-invalid': form.errors.has('default_completion_scoring_mode') }"
                            @keydown="form.errors.clear('default_completion_scoring_mode')"
        >
          <b-form-radio value="100% for either">
            100% of points for either auto-graded or open-ended submission
          </b-form-radio>
          <b-form-radio value="split">
            <input v-model="form.completion_split_auto_graded_percentage"
                   class="percent-input"
                   aria-label="completion split auto-graded percentage"
                   @keyup="completionSplitOpenEndedPercentage = updateCompletionSplitOpenEndedSubmissionPercentage(form)"
                   @click="form.default_completion_scoring_mode = 'split'"
                   @keydown="form.default_completion_scoring_mode = 'split'"
            >% of points awarded for an auto-graded
            submission<br>
            <span v-if="!isNaN(parseFloat(completionSplitOpenEndedPercentage))">
              <input v-model="completionSplitOpenEndedPercentage"
                     class="percent-input percent-input-disabled"
                     aria-label="completion split open-ended percentage"
                     :aria-disabled="true"
                     @click="false"
              >%
              of the points awarded for an open-ended submission
            </span>
          </b-form-radio>
        </b-form-radio-group>
        <has-error :form="form" field="default_completion_scoring_mode"/>
      </b-form-group>
      <!-- Must be number of points for alpha courses because changing weights or the total points with beta courses would be chaos -->
      <b-form-group
        v-show="!isAlphaCourse"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="scoring_type"
      >
        <template slot="label">
          Points Per Question*
        </template>
        <b-form-radio-group id="points_per_question"
                            v-model="form.points_per_question"
                            stacked
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                            required
                            @change="initPointsPerQuestionSwitch($event)"
        >
          <b-form-radio
            name="points_per_question"
            value="number of points"
            :class="{ 'is-invalid': form.errors.has('points_per_question') }"
            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
            @keydown="form.errors.clear('points_per_question')"

          >
            Specify number of points for each question
            <QuestionCircleTooltip :id="'points-per-question-specify-actual-values-tooltip'"/>
            <b-tooltip target="points-per-question-specify-actual-values-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Specify the number of points at the question level.
            </b-tooltip>
          </b-form-radio>
          <b-form-radio name="points_per_question" value="question weight">
            Determine
            by question weight using the total assignment points
            <QuestionCircleTooltip
              :id="'points-per-question-determine-by-weights-tooltip'"
            />
            <b-tooltip target="points-per-question-determine-by-weights-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Specify the total number of points for the assignment and ADAPT will compute the points per question based
              on the assigned weights.
              The weights can be customized at the question level.
            </b-tooltip>
          </b-form-radio>
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">
            {{ form.errors.get('points_per_question') }}
          </div>
        </b-form-radio-group>
      </b-form-group>
      <div v-show="form.source === 'a'">
        <b-form-group
          v-show="showDefaultPointsPerQuestion"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="default_points_per_question"
        >
          <template slot="label">
            Default Points/Question*
          </template>
          <b-form-row>
            <b-col lg="3">
              <b-form-input
                id="default_points_per_question"
                v-model="form.default_points_per_question"
                type="text"
                placeholder=""
                required
                :class="{ 'is-invalid': form.errors.has('default_points_per_question') }"
                :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                @keydown="form.errors.clear('default_points_per_question')"
              />
              <has-error :form="form" field="default_points_per_question"/>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          v-show="!showDefaultPointsPerQuestion"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="total_points"
          label="Total Assignment Points*"
        >
          <b-form-row>
            <b-col lg="3">
              <b-form-input
                id="total_points"
                v-model="form.total_points"
                type="text"
                required
                :class="{ 'is-invalid': form.errors.has('total_points') }"
                :disabled="(isLocked(hasSubmissionsOrFileSubmissions) && !overallStatusIsNotOpen) || isBetaAssignment"
                @keydown="form.errors.clear('total_points')"
              />
              <has-error :form="form" field="total_points"/>
            </b-col>
          </b-form-row>
        </b-form-group>
      </div>


      <b-form-group
        v-show="form.source === 'a'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="assessment_type"
      >
        <template slot="label">
          Assessment Type*
        </template>
        <b-form-radio-group id="assessment_type"
                            v-model="form.assessment_type"
                            required
                            stacked
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                            @change="initAssessmentTypeSwitch($event)"
        >
          <b-form-radio name="assessment_type" value="real time">
            Real Time Graded Assessments
            <QuestionCircleTooltip :id="'real_time'"/>
          </b-form-radio>

          <b-form-radio name="assessment_type" value="delayed">
            Delayed Graded Assessments
            <QuestionCircleTooltip :id="'delayed'"/>
          </b-form-radio>

          <b-form-radio name="assessment_type" value="learning tree">
            Learning Tree Assessments
            <QuestionCircleTooltip :id="'learning_tree'"/>
          </b-form-radio>

          <b-form-radio name="assessment_type" value="clicker">
            Clicker Assessments
            <QuestionCircleTooltip :id="'clicker'"/>
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <div v-if="['real time','learning tree'].includes(form.assessment_type) && form.scoring_type === 'p'">
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="number_of_allowed_attempts"
        >
          <template slot="label">
            Number of Allowed Attempts*

            <QuestionCircleTooltip :id="'number-of-allowed-attempts-tooltip'"/>
            <b-tooltip target="number-of-allowed-attempts-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Optionally, you can let your students attempt real time assessments multiple times. Please note that due
              to
              the nature of H5P, your students will see the answer
              after the first attempt regardless of how many attempts you allow.
            </b-tooltip>
          </template>
          <b-form-select id="number_of_allowed_attempts"
                         v-model="form.number_of_allowed_attempts"
                         required
                         class="mt-2"
                         :options="form.assessment_type === 'real time'
                         ? numberOfAllowedAttemptsOptions
                         : numberOfAllowedAttemptsOptions.filter(numberOfAttempts => parseInt(numberOfAttempts.value) !== 1)"
                         :style="[form.number_of_allowed_attempts !== 'unlimited' ? {'width':'60px'} : {'width':'120px'}]"
                         :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                         :class="{ 'is-invalid': form.errors.has('number_of_allowed_attempts') }"
                         @change="form.errors.clear('number_of_allowed_attempts');$forceUpdate()"
          />
          <has-error :form="form" field="number_of_allowed_attempts"/>
        </b-form-group>
        <b-form-group
          v-if="form.number_of_allowed_attempts !== '1'"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="attempts_penalty"
        >
          <template slot="label">
            Attempts Penalty*
            <QuestionCircleTooltip :id="'attempts-penalty-tooltip'"/>
            <b-tooltip target="attempts-penalty-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              If you allow your students to attempt a question multiple times, you may provide a penalty to be applied
              for
              each attempt
              after the first. As an example, a
              correct answer on the second attempt with a penalty of 10% means that a student will receive 90% of the
              total score for the question.
            </b-tooltip>
          </template>
          <b-form-row>
            <b-col>
              <b-form-input
                id="number_of_allowed_attempts_penalty"
                v-model="form.number_of_allowed_attempts_penalty"
                type="text"
                required
                placeholder="0-100"
                style="width:100px"
                :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                :class="{ 'is-invalid': form.errors.has('number_of_allowed_attempts_penalty') }"
                @keydown="form.errors.clear('number_of_allowed_attempts_penalty')"
              />
              <has-error :form="form" field="number_of_allowed_attempts_penalty"/>
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          v-show="form.assessmentType !== 'delayed'"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="hint"
        >
          <template slot="label">
            Hint*
            <QuestionCircleTooltip :id="'hint-tooltip'"/>
            <b-tooltip target="hint-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Allow your students to see a hint for the solution if a hint exists.
            </b-tooltip>
          </template>
          <b-form-radio-group id="hint"
                              v-model="form.hint"
                              required
                              stacked
                              @change="updateHintPenaltyView($event)"
          >
            <b-form-radio value="0">
              No
            </b-form-radio>

            <b-form-radio value="1">
              Yes
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>

        <b-form-group
          v-if="showHintPenalty"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="hint_penalty"
        >
          <template slot="label">
            Hint Penalty*
            <QuestionCircleTooltip :id="'hint-penalty-tooltip'"/>
            <b-tooltip target="hint-penalty-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Penalty applied if a student decides to chooses to view the hint.
            </b-tooltip>
          </template>
          <b-form-row>
            <b-col>
              <b-form-input
                id="hint_penalty"
                v-model="form.hint_penalty"
                type="text"
                required
                placeholder="0-100"
                style="width:100px"
                :class="{ 'is-invalid': form.errors.has('hint_penalty') }"
                @keydown="form.errors.clear('hint_penalty')"
              />
              <has-error :form="form" field="hint_penalty"/>
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          v-if="assessmentType === 'real time'"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="solutions_availability"
        >
          <template slot="label">
            Solutions Availability*
            <b-icon icon="question-circle"
                    class="text-muted"
                    @mouseover="$bvModal.show('modal-per-question-solutions-availability')"
            />
          </template>
          <b-form-radio-group id="solutions_availability"
                              v-model="form.solutions_availability"
                              stacked
                              required
                              name="solutions_availability"
                              :class="{ 'is-invalid': form.errors.has('solutions_availability') }"
                              @keydown="form.errors.clear('solutions_availability')"
          >
            <b-form-radio value="automatic">
              Automatic
            </b-form-radio>
            <b-form-radio value="manual">
              Manual
            </b-form-radio>
          </b-form-radio-group>
          <div v-if="form.errors.has('solutions_availability')" class="help-block invalid-feedback">
            Please choose one of the given options.
          </div>
        </b-form-group>
      </div>
      <div v-show="form.assessment_type === 'clicker'">
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="default_clicker_time_to_submit"
        >
          <template slot="label">
            Default Clicker Time To Submit*
            <QuestionCircleTooltip :id="'default_clicker_time_to_submit_tooltip'"/>
          </template>
          <b-form-row>
            <b-col lg="3">
              <b-form-input
                id="default_clicker_time_to_submit"
                v-model="form.default_clicker_time_to_submit"
                type="text"
                placeholder=""
                required
                :class="{ 'is-invalid': form.errors.has('default_clicker_time_to_submit') }"
                :disabled="isBetaAssignment"
                @keydown="form.errors.clear('default_clicker_time_to_submit')"
              />
              <has-error :form="form" field="default_clicker_time_to_submit"/>
            </b-col>
          </b-form-row>
        </b-form-group>
      </div>
      <LearningTreeAssignmentInfo
        v-if="form.assessment_type === 'learning tree'"
        :form="form"
        :has-submissions-or-file-submissions="hasSubmissionsOrFileSubmissions"
        :is-beta-assignment="isBetaAssignment"
      />

      <b-form-group
        v-show="form.assessment_type === 'delayed' && form.source === 'a'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="file_upload_mode"
      >
        <template slot="label">
          File Upload Mode*
        </template>
        <b-form-radio-group id="file_upload_mode"
                            v-model="form.file_upload_mode"
                            stacked
                            required
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                            name="file_upload_mode"
                            :class="{ 'is-invalid': form.errors.has('file_upload_mode') }"
                            @keydown="form.errors.clear('file_upload_mode')"
                            @change="initFileUploadModeSwitch($event);checkDefaultOpenEndedSubmissionType()"
        >
          <!-- <b-form-radio name="default_open_ended_submission" value="a">At the assignment level</b-form-radio>-->
          <b-form-radio name="file_upload_mode" value="compiled_pdf">
            Compiled Upload (PDFs only)
            <QuestionCircleTooltip :id="'compiled_pdf_tooltip'"/>
          </b-form-radio>
          <b-form-radio name="file_upload_mode" value="individual_assessment">
            Individual Assessment Upload
            <QuestionCircleTooltip :id="'individual_assessment_upload_tooltip'"/>
          </b-form-radio>
          <b-form-radio name="file_upload_mode" value="both">
            Compiled Upload & Individual Assessment Upload
            <QuestionCircleTooltip :id="'both_upload_tooltip'"/>
          </b-form-radio>
        </b-form-radio-group>
        <div v-if="form.errors.has('file_upload_mode')" class="help-block invalid-feedback">
          Please choose one of the given options.
        </div>
      </b-form-group>
      <b-form-group
        v-show="form.assessment_type === 'delayed' && form.source === 'a' && parseInt(form.file_upload_mode) !==1"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="default_open_ended_submission_type"
      >
        <template slot="label">
          Default Open-ended Submission Type*
          <QuestionCircleTooltip :id="'default_open_ended_submission_type_tooltip'"/>
        </template>
        <b-form-radio-group id="default_open_ended_submission_type"
                            v-model="form.default_open_ended_submission_type"
                            stacked
                            required
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                            name="default_open_ended_submission_type"
                            :class="{ 'is-invalid': form.errors.has('default_open_ended_submission_type') }"
                            @change="checkIfCompiledPdf()"
                            @keydown="form.errors.clear('default_open_ended_submission_type')"
        >
          <!-- <b-form-radio name="default_open_ended_submission" value="a">At the assignment level</b-form-radio>-->
          <b-form-radio name="default_open_ended_submission_type" value="file">
            File
          </b-form-radio>
          <b-form-radio name="default_open_ended_submission_type"
                        value="rich text"
          >
            Rich Text
          </b-form-radio>
          <b-form-radio name="default_open_ended_submission_type"
                        value="plain text"
          >
            Plain Text
          </b-form-radio>
          <b-form-radio name="default_open_ended_submission_type"
                        value="audio"
          >
            Audio
          </b-form-radio>
          <b-form-radio name="default_open_ended_submission_type" value="0">
            None
          </b-form-radio>
        </b-form-radio-group>
        <div v-if="form.errors.has('default_open_ended_submission_type')" class="help-block invalid-feedback">
          The selected default open ended submission type is invalid.
        </div>
      </b-form-group>
      <b-form-group
        v-show="form.source === 'a'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="late_policy"
      >
        <template slot="label">
          Late Policy*
        </template>
        <b-form-radio-group id="late_policy"
                            v-model="form.late_policy"
                            required
                            stacked
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions)"
        >
          <!-- <b-form-radio name="default_open_ended_submission_type" value="a">At the assignment level</b-form-radio>-->
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
                required
                :class="{ 'is-invalid': form.errors.has('late_deduction_percent') }"
                @keydown="form.errors.clear('late_deduction_percent')"
              />
              <has-error :form="form" field="late_deduction_percent"/>
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="late_deduction_application_period"
        >
          <template slot="label">
            Late Deduction Applied*
          </template>
          <b-form-radio-group v-model="form.late_deduction_applied_once"
                              stacked
                              required
                              :disabled="isLocked(hasSubmissionsOrFileSubmissions)"
          >
          <span @click="form.late_deduction_application_period = ''">
            <b-form-radio value="1">
              Just once
            </b-form-radio>
          </span>
            <b-form-radio class="mt-2" value="0">
              <b-row>
                <b-col lg="4" class="mt-1">
                  Every
                </b-col>
                <b-col lg="6">
                  <b-form-input
                    id="late_deduction_application_period"
                    v-model="form.late_deduction_application_period"
                    :disabled="parseInt(form.late_deduction_applied_once) === 1"
                    type="text"
                    required
                    :class="{ 'is-invalid': form.errors.has('late_deduction_application_period') }"
                    @keydown="form.errors.clear('late_deduction_application_period')"
                  />
                  <has-error :form="form" field="late_deduction_application_period"/>
                </b-col>
                <QuestionCircleTooltip :id="'late_deduction_application_period_tooltip'"/>
              </b-row>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
      <b-form-group
        v-if="!lms"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="include_in_final_score"
      >
        <template slot="label">
          Include In Final Score*
        </template>
        <b-form-radio-group id="include_in_final_score"
                            v-model="form.include_in_weighted_average"
                            required
                            stacked
        >
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
        v-show="form.source === 'x'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="external_source_points"
      >
        <template slot="label">
          Total Points*
        </template>
        <b-form-row>
          <b-col lg="3">
            <b-form-input
              id="external_source_points"
              v-model="form.external_source_points"
              :disabled="isBetaAssignment"
              type="text"
              placeholder=""
              required
              :class="{ 'is-invalid': form.errors.has('external_source_points') }"
              @keydown="form.errors.clear('external_source_points')"
            />
            <has-error :form="form" field="external_source_points"/>
          </b-col>
        </b-form-row>
      </b-form-group>

      <b-form-group
        v-show="form.source === 'a' && !lms"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Instructions"
        label-for="instructions"
      >
        <b-form-row>
          <ckeditor
            id="instructions"
            v-model="form.instructions"
            tabindex="0"
            rows="4"
            :config="richEditorConfig"
            max-rows="4"
            :read-only="isBetaAssignment"
            @namespaceloaded="onCKEditorNamespaceLoaded"
            @ready="handleFixCKEditor()"
          />
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-show="form.source === 'a'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="randomizations"
      >
        <template slot="label">
          Randomizations*
        </template>
        <b-form-radio-group id="randomizations"
                            v-model="form.randomizations"
                            required
                            stacked
                            :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
                            @change="initRandomizationsSwitch($event)"
        >
          <b-form-radio value="0">
            No
          </b-form-radio>
          <b-form-radio value="1">
            Yes
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-show="form.source === 'a' && parseInt(form.randomizations) === 1"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="number_of_randomized_assessments"
      >
        <template slot="label">
          Number of randomized assessments*
          <QuestionCircleTooltip :id="'number_of_randomized_assessments_tooltip'"/>
        </template>
        <b-form-row>
          <b-col lg="2">
            <b-form-input
              id="number_of_randomized_assessments"
              v-model="form.number_of_randomized_assessments"
              type="text"
              required
              :disabled="isLocked(hasSubmissionsOrFileSubmissions) || isBetaAssignment"
              :class="{ 'is-invalid': form.errors.has('number_of_randomized_assessments') }"
              @keydown="form.errors.clear('number_of_randomized_assessments')"
            />
            <has-error :form="form" field="number_of_randomized_assessments"/>
          </b-col>
        </b-form-row>
      </b-form-group>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="notifications"
      >
        <template slot="label">
          Notifications*
          <QuestionCircleTooltip :id="'notifications_tooltip'"/>
        </template>
        <b-form-radio-group id="notifications"
                            v-model="form.notifications"
                            required
                            stacked
        >
          <b-form-radio name="notifications" value="1">
            On
          </b-form-radio>
          <b-form-radio name="notifications" value="0">
            Off
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-if="lms"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="libretexts_url"
      >
        <template slot="label">
          Libretexts URL
          <QuestionCircleTooltip id="'libretexts_url_tooltip'"/>
        </template>
        <b-form-textarea
          id="libretexts_url"
          v-model="form.libretexts_url"
          :class="{ 'is-invalid': form.errors.has('libretexts_url') }"
          rows="4"
          max-rows="4"
        />
        <has-error :form="form" field="libretexts_url"/>
      </b-form-group>
      <div v-for="(assignTo,index) in form.assign_tos"
           :key="index"
      >
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="assign_to"
        >
          <template slot="label">
            Assign to*
            <QuestionCircleTooltip :id="'assign_to_tooltip'"/>
          </template>
          <b-form-row>
            <b-col lg="5">
              <b-form-select id="assign_to"
                             v-model="assignTo.selectedGroup"
                             required
                             :options="assignToGroups"
                             :class="{ 'is-invalid': form.errors.has(`groups_${index}`) }"
                             @change="updateAssignTos(assignTo)"
              />
              <has-error :form="form" :field="`groups_${index}`"/>
            </b-col>
            <b-col>
              <ul
                v-for="(group,group_index) in assignTo.groups"
                :key="group_index"
                class="flex-column align-items-start"
              >
                <li>
                  {{ group.text }}
                  <a href="" @click.prevent="removeAssignToGroup(assignTo, group)">
                    <b-icon icon="trash"
                            :aria-label="`Remove ${group.text} from this Assign To`"
                            class="text-muted"
                    />
                  </a>
                </li>
              </ul>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          :label-for="`available_from_${index}`"
        >
          <template slot="label">
            Available on*
          </template>
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                :id="`available_from_${index}`"
                v-model="assignTo.available_from_date"
                required
                tabindex="0"
                :min="min"
                :class="{ 'is-invalid': form.errors.has(`available_from_date_${index}`) }"
              />
              <has-error :form="form" :field="`available_from_date_${index}`"/>
            </b-col>
            <b-col>
              <b-form-timepicker :id="`available_from_time_${index}`"
                                 v-model="assignTo.available_from_time"
                                 tabindex="0"
                                 locale="en"
                                 :class="{ 'is-invalid': form.errors.has(`available_from_time_${index}`) }"
              />
              <has-error :form="form" :field="`available_from_time_${index}`"/>
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          :label-for="`due_date_${index}`"
        >
          <template slot="label">
            Due Date*
          </template>
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                :id="`due_date_${index}`"
                v-model="assignTo.due_date"
                required
                tabindex="0"
                :min="min"
                :class="{ 'is-invalid': form.errors.has(`due_${index}`) }"
                @shown="form.errors.clear(`due_${index}`)"
              />
              <has-error :form="form" :field="`due_${index}`"/>
            </b-col>
            <b-col>
              <b-form-timepicker :id="`due_time_${index}`"
                                 v-model="assignTo.due_time"
                                 tabindex="0"
                                 locale="en"
                                 required
                                 :class="{ 'is-invalid': form.errors.has(`due_time_${index}`) }"
                                 @shown="form.errors.clear(`due_time_${index}`)"
              />
              <has-error :form="form" :field="`due_time_${index}`"/>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          v-show="form.late_policy !== 'not accepted'"
          label-cols-sm="4"
          label-cols-lg="3"
          :label-for="`final_submission_deadline_${index}`"
        >
          <template slot="label">
            Final Submission Deadline*
            <QuestionCircleTooltip :id="'final_submission_deadline_tooltip'"/>
          </template>
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                :id="`final_submission_deadline_${index}`"
                v-model="assignTo.final_submission_deadline_date"
                required
                tabindex="0"
                :min="min"
                :class="{ 'is-invalid': form.errors.has(`final_submission_deadline_${index}`) }"
                :disabled="Boolean(solutionsReleased) && assessmentType !== 'real time'"
                @shown="form.errors.clear(`final_submission_deadline_${index}`)"
              />
              <has-error :form="form" :field="`final_submission_deadline_${index}`"/>
            </b-col>
            <b-col>
              <b-form-timepicker :id="`final_submission_deadline_time_${index}`"
                                 v-model="assignTo.final_submission_deadline_time"
                                 tabindex="0"
                                 locale="en"
                                 required
                                 :class="{ 'is-invalid': form.errors.has(`final_submission_deadline_time_${index}`) }"
                                 :disabled="Boolean(solutionsReleased) && assessmentType !== 'real time'"
                                 @shown="form.errors.clear(`final_submission_deadline_time_${index}`)"
              />
              <has-error :form="form" :field="`final_submission_deadline_time_${index}`"/>
            </b-col>
          </b-form-row>
        </b-form-group>
        <div v-if="form.assign_tos.length>1">
          <b-row align-h="end">
            <b-button variant="outline-danger" class="mr-4" size="sm" @click="removeAssignTo(assignTo)">
              Remove Assign
              to
            </b-button>
          </b-row>
          <hr>
        </div>
      </div>
      <b-button variant="outline-primary" size="sm" @click="addAssignTo">
        Add Assign to
      </b-button>
      <QuestionCircleTooltip :id="'add_assign_to_tooltip'"/>
    </b-form>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'

import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { isLocked, getAssignments, isLockedMessage } from '~/helpers/Assignments'
import 'vue-loading-overlay/dist/vue-loading.css'
import CKEditor from 'ckeditor4-vue'

import { defaultAssignTos } from '~/helpers/AssignmentProperties'
import { updateCompletionSplitOpenEndedSubmissionPercentage } from '~/helpers/CompletionScoringMode'
import AllFormErrors from '~/components/AllFormErrors'
import { fixDatePicker } from '~/helpers/accessibility/FixDatePicker'
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import LearningTreeAssignmentInfo from '~/components/LearningTreeAssignmentInfo'

export default {
  components: {
    ckeditor: CKEditor.component,
    LearningTreeAssignmentInfo,
    AllFormErrors
  },
  middleware: 'auth',
  props: {
    form: {
      type: Object,
      default: function () {
      }
    },
    assignmentGroups: {
      type: Array,
      default: function () {
      }
    },
    isBetaAssignment: { type: Boolean, default: false },
    lms: { type: Boolean, default: false },
    courseId: { type: Number, default: 0 },
    assignmentId: { type: Number, default: 0 },
    courseEndDate: { type: String, default: '' },
    courseStartDate: { type: String, default: '' },
    hasSubmissionsOrFileSubmissions: { type: Boolean, default: false },
    isAlphaCourse: { type: Boolean, default: false },
    overallStatusIsNotOpen: { type: Boolean, default: false }
  },
  data: () => ({
    showMinimumNumberOfSuccessfulBranches: true,
    showMinimumNumberOfSuccessfulAssessments: true,
    showHintPenalty: false,
    showDefaultPointsPerQuestion: true,
    numberOfAllowedAttemptsOptions: [
      { text: '1', value: '1' },
      { text: '2', value: '2' },
      { text: '3', value: '3' },
      { text: '4', value: '4' },
      { text: 'unlimited', value: 'unlimited' }
    ],
    completionSplitOpenEndedPercentage: '',
    allFormErrors: [],
    richEditorConfig: {
      toolbar: [
        { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo'] },
        {
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']
        },
        {
          name: 'paragraph',
          items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
        },
        '/',
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'insert', items: ['Table', 'HorizontalRule', 'Smiley', 'SpecialChar'] },
        { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
        { name: 'colors', items: ['TextColor', 'BGColor'] }
      ],
      removeButtons: ''
    },
    assignToCourse: [],
    assignToSections: [],
    assignToUsers: [],
    selectedAssignTo: '',
    originalAssignment: {},
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    title: '',
    assessmentType: '',
    isLoading: false,
    solutionsReleased: 0,
    assignments: [],
    completedOrCorrectOptions: [
      { item: 'correct', name: 'correct' },
      { item: 'completed', name: 'completed' }
    ],
    hasAssignments: false,
    has_submissions_or_file_submissions: false,
    min: '',
    canViewAssignments: false,
    showNoAssignmentsAlert: false,
    assignToGroups: []
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.getAssignments = getAssignments
    this.isLocked = isLocked
    this.isLockedMessage = isLockedMessage
    this.defaultAssignTos = defaultAssignTos
    this.updateCompletionSplitOpenEndedSubmissionPercentage = updateCompletionSplitOpenEndedSubmissionPercentage
    this.completionSplitOpenEndedPercentage = 100 - parseInt(this.form.completion_split_auto_graded_percentage)
  },
  async mounted () {
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.$nextTick(() => {
      this.showDefaultPointsPerQuestion = this.form.points_per_question === 'number of points'
      this.showHintPenalty = this.form.hint === 1
      this.showMinimumNumberOfSuccessfulAssessments = this.form.learning_tree_success_criteria === 'assessment based'
      this.showMinimumNumberOfSuccessfulBranches = this.form.learning_tree_success_level === 'branch'
    })

    await this.getAssignToGroups()
    this.fixDatePickerAccessibilitysForAssignTos()
  },
  methods: {
    updateHintPenaltyView (event) {
      this.showHintPenalty = parseInt(event) === 1
    },
    initPointsPerQuestionSwitch (event) {
      this.$nextTick(() => {
        this.showDefaultPointsPerQuestion = event === 'number of points'
        this.form.default_points_per_question = ''
        this.form.total_points = ''
      })
    },
    toggleDefaultPointsPerQuestion (clicked) {
      alert(clicked)
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    fixDatePickerAccessibilitysForAssignTos () {
      for (let i = 0; i < this.form.assign_tos.length; i++) {
        fixDatePicker(`available_from_${i}`, `selected_available_from_${i}`)
        fixDatePicker(`available_from_time_${i}`, `selected_available_from_time_${i}`)
        fixDatePicker(`due_date_${i}`, `selected_due_date_${i}`)
        fixDatePicker(`due_time_${i}`, `selected_due_time_${i}`)
        fixDatePicker(`final_submission_deadline_${i}`, `selected_final_submission_deadline_${i}`)
        fixDatePicker(`final_submission_deadline_time_${i}`, `selected_final_submission_deadline_time_${i}`)
      }
    },
    checkDefaultOpenEndedSubmissionType () {
      let originalFileUploadMode = this.form.file_upload_mode
      this.$nextTick(function () {
        if (this.form.file_upload_mode === 'compiled_pdf' &&
          !['0', 'file'].includes(this.form.default_open_ended_submission_type)) {
          this.form.file_upload_mode = originalFileUploadMode
          this.$noty.info('You cannot choose a File Upload Mode of Compiled PDF\'s unless your Default Open-Ended Submission Type is File or None'
            ,
            { timeout: 9000 })
        }
      })
    },
    checkIfCompiledPdf () {
      let originalDefaultOpenEndedSubmissionType = this.form.default_open_ended_submission_type
      this.$nextTick(function () {
        if (this.form.file_upload_mode === 'compiled_pdf' &&
          !['0', 'file'].includes(this.form.default_open_ended_submission_type)) {
          this.form.default_open_ended_submission_type = originalDefaultOpenEndedSubmissionType
          this.$noty.info('Since you have chosen a File Upload Mode of Compiled Upload, your Default Open-ended Submission Type can only be File or None.',
            { timeout: 9000 })
        }
      })
    },
    async initFileUploadModeSwitch (value) {
      let currentFileUploadMode = this.form.file_upload_mode
      try {
        if (this.assignmentId) {
          if (value === 'compiled_pdf') {
            const { data } = await axios.get(`/api/assignments/${this.assignmentId}/validate-can-switch-to-compiled-pdf`)
            if (data.type === 'error') {
              this.$noty.error(data.message)
              this.form.file_upload_mode = currentFileUploadMode
              return false
            }
          }
          const { data } = await axios.get(`/api/assignments/${this.assignmentId}/validate-can-switch-to-or-from-compiled-pdf`)
          if (data.type === 'error') {
            this.$noty.error(data.message)
            this.form.file_upload_mode = currentFileUploadMode
            return false
          }
        }
        if (this.form.file_upload_mode === 'compiled_pdf') {
          this.form.default_open_ended_submission_type = 'file'
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    initRandomizationsSwitch (event) {
      if (this.form.points_per_question === 'question weight' && +event === 1) {
        this.$noty.info('In Points Per Question above, please choose "Specifiy number of points" if you would like to use Randomizations.')
        this.$nextTick(
          () => {
            this.form.randomizations = '0'
          })
        return false
      }
      if (+event === 0) {
        this.form.number_of_randomized_assessments = null
      }
    },
    removeAssignTo (assignTo) {
      this.form.assign_tos = this.form.assign_tos.filter(e => e !== assignTo)
    },
    addAssignTo () {
      let newAssignTo = this.defaultAssignTos(this.$moment, this.courseStartDate, this.courseEndDate)
      this.form.assign_tos.push(newAssignTo)
      this.$nextTick(() => {
        this.fixDatePickerAccessibilitysForAssignTos()
      })
    },
    updateAssignTos (assignTo) {
      if (assignTo.selectedGroup.hasOwnProperty('user_id')) {
        for (let i = 0; i < this.assignToUsers.length; i++) {
          if (assignTo.selectedGroup.user_id === this.assignToUsers[i].value.user_id) {
            assignTo.groups.push(this.assignToUsers[i])
          }
        }
      }
      if (assignTo.selectedGroup.hasOwnProperty('section_id')) {
        for (let i = 0; i < this.assignToSections.length; i++) {
          if (assignTo.selectedGroup.section_id === this.assignToSections[i].value.section_id) {
            assignTo.groups.push(this.assignToSections[i])
          }
        }
      }

      if (assignTo.selectedGroup.hasOwnProperty('course_id')) {
        assignTo.groups.push(this.assignToCourse)
      }
      assignTo.selectedGroup = null
    },
    removeAssignToGroup (assignTo, group) {
      for (let i = 0; i < assignTo.groups.length; i++) {
        if (assignTo.groups[i].text === group.text) {
          console.log(assignTo.groups[i].text)
          console.log(group.text)
          assignTo.groups.splice(i, 1)
          return
        }
      }
    },
    async getAssignToGroups () {
      try {
        const { data } = await axios.get(`/api/assign-to-groups/${this.courseId}`)
        this.assignToSections = data.sections
        this.assignToUsers = data.users
        this.assignToCourse = data.course
        console.log(data)
        this.assignToGroups = [{ value: null, text: 'Please select a group' }]
        this.assignToGroups.push(data.course)
        this.assignToGroups.push({ label: 'Sections', options: data.sections })
        if (data.users.length) {
          this.assignToGroups.push({ label: 'Students', options: data.users })
        }
        console.log(this.assignToGroups)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async initAssessmentTypeSwitch (assessmentType) {
      let originalAssessmentType = this.form.assessment_type
      if (!this.assignmentId) {
        this.switchAssessmentType(assessmentType, originalAssessmentType)
        return false
      }
      this.$nextTick(async function () {
        try {
          const { data } = await axios.post(`/api/assignments/${this.assignmentId}/validate-assessment-type`,
            { 'assessment_type': this.form.assessment_type })

          if (data.type === 'error') {
            this.$noty.error(data.message)
            this.form.assessment_type = originalAssessmentType
            return false
          }
          this.switchAssessmentType(assessmentType, originalAssessmentType)
        } catch (error) {
          this.form.assessment_type = originalAssessmentType
          this.$noty.error(error.message)
        }
      })
    },
    switchAssessmentType (assessmentType, originalAssessmentType) {
      switch (assessmentType) {
        case ('real time'):
          this.showRealTimeOptions()
          this.form.number_of_allowed_attempts = '1'
          break
        case ('delayed'):
          this.showDelayedOptions()
          break
        case ('learning tree'):
          this.checkIfScoringTypeOfPoints(originalAssessmentType)
          this.form.number_of_allowed_attempts = '2'
          break
        case ('clicker'):
          this.form.number_of_randomized_assessments = null
          this.form.randomizations = 0
          this.checkSourceAndLatePolicy()
          this.form.notifications = 0
      }
    },
    async initInternalExternalSwitch () {
      if (!this.assignmentId) {
        return false
      }
      this.$nextTick(async function () {
        try {
          const { data } = await axios.post(`/api/assignments/${this.assignmentId}/validate-assessment-type`,
            { 'assessment_type': this.form.assessment_type, 'source': this.form.source })
          console.log(data)
          console.log(this.form.source)
          if (data.type === 'error') {
            this.$noty.error(data.message)
            this.form.source = this.originalAssignment.source
            return false
          }

          if (this.form.source === 'a') {
            this.resetOpenEndedResponsesAndPointsPerQuestion()
          }
        } catch (error) {
          this.form.source = this.originalAssignment.source
          this.$noty.error(error.message)
        }
      })
    },
    checkSourceAndLatePolicy () {
      if (this.form.source === 'x') {
        this.$noty.info('Clicker assessments must have a Source of "Internal".')
      }
      if (this.form.late_policy !== 'not accepted') {
        this.$noty.info('Clicker assessments must have a Late Policy of "Do not accept late".')
      }
      if (this.form.number_of_randomized_assessments) {
        this.$noty.info('Clicker assessments can\'t be randomized.')
        this.form.number_of_randomized_assessments = null
      }
    },
    checkIfScoringTypeOfPoints (originalAssessmentType) {
      if (this.form.scoring_type === 'c') {
        this.form.assessment_type = originalAssessmentType
        this.$noty.info('Learning Tree assessments types must have a Scoring Type of "Points".')
        return false
      }
    },
    canSwitchToCompleteIncomplete (event) {
      if (this.form.assessment_type === 'learning tree') {
        event.preventDefault()
        this.$noty.info('Learning Tree assessments types must have a Scoring Type of "Points".')
        return false
      }
    },
    initLateValues (event) {
      if (this.form.assessment_type === 'clicker') {
        event.preventDefault()
        this.$noty.info('Clicker assessments can only have the Late Policy of "Do not accept late".')
        return false
      }
      this.form.late_deduction_percent = null
      this.form.late_deduction_applied_once = 1
      this.form.late_deduction_application_period = null
    },
    showDelayedOptions () {
      this.form.default_open_ended_submission_type = 'file'
      this.form.min_time_needed_in_learning_tree = null
      this.form.percent_earned_for_exploring_learning_tree = null
      this.form.submission_count_percent_decrease = null
    },
    showRealTimeOptions () {
      this.form.min_time_needed_in_learning_tree = null
      this.form.percent_earned_for_exploring_learning_tree = null
      this.form.submission_count_percent_decrease = null
    },
    getLockedQuestionsMessage (assignment) {
      if (assignment.has_submissions_or_file_submissions) {
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
        } else {
          this.allFormErrors = this.assignmentGroupForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-create-assignment-group')
        }
      }
    },
    checkGroupId (groupId) {
      if (groupId === -1) {
        this.$bvModal.show('modal-create-assignment-group')
      }
      // don't notify students for exams
      for (let i = 0; i < this.assignmentGroups.length; i++) {
        if (this.assignmentGroups[i].value === groupId) {
          if (['exam', 'midterm', 'quiz', 'final'].includes(this.assignmentGroups[i].text.toLowerCase())) {
            this.form.notifications = 0
          }
        }
      }
    },

    resetOpenEndedResponsesAndPointsPerQuestion () {
      this.form.default_points_per_question = 10
      this.form.default_open_ended_submission_type = 'file'
      this.form.assessment_type = 'real time'
      this.form.students_can_view_assignment_statistics = 0
      this.form.external_source_points = 100
      this.form.errors.clear('default_points_per_question')
      this.form.errors.clear('external_source_points')
    },
    resetAssignmentGroupForm () {
      this.assignmentGroupForm.errors.clear()
      this.assignmentGroupForm.assignment_group = ''
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
