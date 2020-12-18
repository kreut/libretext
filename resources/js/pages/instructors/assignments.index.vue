<template>
  <div>
    <PageTitle v-if="canViewAssignments" :title="title" />
    <div class="vld-parent">
      <b-modal
        id="modal-assignment-properties"
        ref="modal"
        title="Assignment Properties"
        ok-title="Submit"
        size="lg"
        @ok="submitAssignmentInfo"
        @hidden="resetModalForms"
      >
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :is-full-page="true"
                 :width="128"
                 :height="128"
                 color="#007BFF"
                 background="#FFFFFF"
        />
        <b-tooltip target="internal"
                   delay="250"
        >
          Get questions from the Adapt database or from the Query library
        </b-tooltip>
        <b-tooltip target="late_deduction_application_period_tooltip"
                   delay="250"
        >
          Enter a timeframe such as 5 minutes, 3 hours, or 1 day.  As a concrete example, if the Late Deduction percent is 20%
          and the timeframe is 1 hour, then if a student uploads the file 1 hour and 40 minutes late, then the percent is applied twice
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
        <b-tooltip target="percent_earned_for_entering_learning_tree_tooltip"
                   delay="250"
        >
          The percent of the question points that a student earns for entering the Learning Tree for at least the
          minimum time as described above.
        </b-tooltip>

        <b-tooltip target="percent_decay_tooltip"
                   delay="250"
        >
          For each new attempt after their first free attempt, students will be awarded the total number of new
          attempts multiplied by the percent decay in addition to the percent awarded for entering the Learning Tree.
        </b-tooltip>

        <b-form ref="form" @submit="createAssignment">
          <div v-if="has_submissions_or_file_submissions && !solutionsReleased">
            <b-alert variant="info" show>
              <strong>Students have submitted responses to questions in the assignment so you
                can't change the source of the questions, the scoring type, the default points per question, or the type
                of file uploads. </strong>
            </b-alert>
          </div>
          <div v-show="solutionsReleased">
            <b-alert variant="info" show>
              <strong>This assignment is locked. The only
                item
                that you can update is the assignment's name, the assignment's group, the instructions, and whether
                students can view the
                assignment
                statistics.</strong>
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
                  :disabled="Boolean(solutionsReleased)"
                  @shown="form.errors.clear('available_from_date')"
                />
                <has-error :form="form" field="available_from_date" />
              </b-col>
              <b-col>
                <b-form-timepicker v-model="form.available_from_time"
                                   locale="en"
                                   :class="{ 'is-invalid': form.errors.has('available_from_time') }"
                                   :disabled="Boolean(solutionsReleased)"
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
                  :class="{ 'is-invalid': form.errors.has('due_date') }"
                  :disabled="Boolean(solutionsReleased)"
                  @shown="form.errors.clear('due_date')"
                />
                <has-error :form="form" field="due_date" />
              </b-col>
              <b-col>
                <b-form-timepicker v-model="form.due_time"
                                   locale="en"
                                   :class="{ 'is-invalid': form.errors.has('due_time') }"
                                   :disabled="Boolean(solutionsReleased)"
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
                                :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
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
                                :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
            >
              <span @click="form.students_can_view_assignment_statistics = 1">
                <b-form-radio name="scoring_type" value="p">Points</b-form-radio></span>
              <span @click="resetSubmissionFilesAndPointsPerQuestion">
                <b-form-radio name="scoring_type" value="c">Complete/Incomplete</b-form-radio>
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
                    :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
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
                              :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
          >
            <span @click="resetLearningTreeToNull">
              <b-form-radio name="assessment_type" value="r">
                Real time <span id="real_time" class="text-muted"><b-icon
                  icon="question-circle"
                />
                </span>
              </b-form-radio>
              <span @click="form.submission_files = 'q'">
                <b-form-radio name="assessment_type" value="d">
                  Delayed <span id="delayed" class="text-muted"><b-icon
                    icon="question-circle"
                  /></span>
                </b-form-radio>
              </span>
            </span>
            <b-form-radio name="assessment_type" value="l">
              Learning Tree <span id="learning_tree" class="text-muted"><b-icon
                icon="question-circle"
              />
              </span>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <div v-show="form.assessment_type === 'l'">
          <b-form-group
            id="min_time_needed_in_learning_tree"
            label-cols-sm="7"
            label-cols-lg="6"
            label-for="min_time_needed_in_learning_tree"
          >
            <template slot="label">
              <b-icon
                icon="tree" variant="success"
              />
              Minimum Time Spent In Learning Tree <span id="min_time_needed_in_learning_tree_tooltip"
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
                  :class="{ 'is-invalid': form.errors.has('min_time_needed_in_learning_tree') }"
                  @keydown="form.errors.clear('min_time_needed_in_learning_tree')"
                />
                <has-error :form="form" field="min_time_needed_in_learning_tree" />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="percent_earned_for_entering_learning_tree"
            label-cols-sm="7"
            label-cols-lg="6"
            label="Percent Earned For Entering Learning Tree"
            label-for="percent_earned_for_entering_learning_tree"
          >
            <template slot="label">
              <b-icon
                icon="tree" variant="success"
              />
              Percent Earned For Entering Learning Tree <span id="percent_earned_for_entering_learning_tree_tooltip"
                                                              class="text-muted"
              ><b-icon
                icon="question-circle"
              /></span>
            </template>
            <b-form-row>
              <b-col lg="4">
                <b-form-input
                  id="percent_earned_for_entering_learning_tree"
                  v-model="form.percent_earned_for_entering_learning_tree"
                  type="text"
                  placeholder="Out of 100"
                  :class="{ 'is-invalid': form.errors.has('percent_earned_for_entering_learning_tree') }"
                  @keydown="form.errors.clear('percent_earned_for_entering_learning_tree')"
                />
                <has-error :form="form" field="percent_earned_for_entering_learning_tree" />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="percent_decay"
            label-cols-sm="7"
            label-cols-lg="6"
            label-for="percent_decay"
          >
            <template slot="label">
              <b-icon
                icon="tree" variant="success"
              />
              Percent Decay By Number Of Attempts <span id="percent_decay_tooltip" class="text-muted"><b-icon
                icon="question-circle"
              /></span>
            </template>
            <b-form-row>
              <b-col lg="4">
                <b-form-input
                  id="decay_percent"
                  v-model="form.percent_decay"
                  type="text"
                  placeholder="Out of 100"
                  :class="{ 'is-invalid': form.errors.has('percent_decay') }"
                  @keydown="form.errors.clear('percent_decay')"
                />
                <has-error :form="form" field="percent_decay" />
              </b-col>
            </b-form-row>
          </b-form-group>
        </div>
        <b-form-group
          v-show="form.scoring_type === 'p' && form.assessment_type === 'd' && form.source === 'a'"
          id="submission_files"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Submission Files"
          label-for="Submission Files"
        >
          <b-form-radio-group v-model="form.submission_files" stacked
                              :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
          >
            <!-- <b-form-radio name="submission_files" value="a">At the assignment level</b-form-radio>-->
            <span @click="form.late_policy = 'not accepted'">
              <b-form-radio name="submission_files" value="q">
                At the question level
              </b-form-radio>
            </span>
            <b-form-radio name="submission_files" value="0">
              Students cannot upload files
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <b-form-group
          v-show="form.submission_files !=='0' &&
            form.scoring_type === 'p' &&
            form.assessment_type === 'd' &&
            form.source === 'a' && form.submission_files !=='0'"
          id="late_policy"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Late Policy"
          label-for="Late Policy"
        >
          <b-form-radio-group v-model="form.late_policy" stacked
                              :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
          >
            <!-- <b-form-radio name="submission_files" value="a">At the assignment level</b-form-radio>-->
            <b-form-radio value="not accepted">
              Do not accept late
            </b-form-radio>
            <b-form-radio value="marked late">
              Accept but mark late
            </b-form-radio>
            <b-form-radio value="deduction">
              Accept late with a deduction
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <div v-if="form.late_policy === 'deduction' && form.submission_files !== '0'">
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
                                :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
            >
              <b-form-radio value="1">
                Just Once
              </b-form-radio>
              <b-form-radio class="mt-2" value="0">
                <b-row>
                  <b-col sm="2" class="mt-1">
                    Every
                  </b-col>
                  <b-col sm="6">
                    <b-form-input
                      id="late_deduction_application_period"
                      v-model="form.late_deduction_application_period"
                      :disabled="parseInt(form.late_deduction_applied_once) === 1"
                      type="text"
                      placeholder="1 hour"
                      :class="{ 'is-invalid': form.errors.has('late_deduction_application_period') }"
                      @keydown="form.errors.clear('late_deduction_application_period')"
                    />
                    <has-error :form="form" field="late_deduction_application_period" />
                  </b-col><span id="late_deduction_application_period_tooltip">
                    <b-icon class="text-muted" icon="question-circle" /></span>
                </b-row>
              </b-form-radio>
            </b-form-radio-group>
          </b-form-group>
        </div>
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
      <b-modal
        id="modal-delete-assignment"
        ref="modal"
        title="Confirm Delete Assignment"
        ok-title="Yes, delete assignment!"
        @ok="handleDeleteAssignment"
        @hidden="resetModalForms"
      >
        <p>By deleting the assignment, you will also delete all student scores associated with the assignment.</p>
        <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
      </b-modal>
      <b-container>
        <b-row v-if="canViewAssignments" align-h="end" class="mb-4">
          <b-button v-if="(user.role === 2)"
                    v-b-modal.modal-assignment-properties
                    class="mr-1" variant="primary"
                    @click="initAddAssignment"
          >
            Add Assignment
          </b-button>
          <b-button class="mr-1"
                    @click="getGradeBook()"
          >
            Gradebook
          </b-button>
        </b-row>
        <b-row v-show="hasAssignments">
          <div class="row">
            <b-table class="header-high-z-index"
                     striped
                     hover
                     responsive="true"
                     sticky-header="600px"
                     :no-border-collapse="true"
                     :fields="fields"
                     :items="assignments"
            >
              <template v-slot:head(show_points_per_question)="data">
                Points Per Question <span v-b-tooltip="showPointsPerQuestionTooltip"><b-icon class="text-muted"
                                                                                             icon="question-circle"
                /></span>
              </template>
              <template v-slot:cell(name)="data">
                <div class="mb-0">
                  <span v-if="user.role === 2">
                    <b-tooltip :target="getTooltipTarget('getQuestions',data.item.id)"
                               delay="500"
                    >
                      {{ getLockedQuestionsMessage(data.item) }}
                    </b-tooltip>
                    <span v-show="data.item.source === 'a'" class="pr-1" @click="getQuestions(data.item)">
                      <b-icon
                        v-show="data.item.has_submissions_or_file_submissions > 0"
                        :id="getTooltipTarget('getQuestions',data.item.id)"
                        icon="lock-fill"
                      />
                    </span>
                  </span>
                  <a href="" @click.prevent="getAssignmentView(user.role, data.item)">{{ data.item.name }}</a>
                </div>
              </template>
              <template v-slot:cell(shown)="data">
                <toggle-button
                  :width="57"
                  :value="Boolean(data.item.shown)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Yes', unchecked: 'No'}"
                  @change="submitShowAssignment(data.item)"
                />
              </template>

              <template v-slot:cell(available_from)="data">
                {{ $moment(data.item.available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
              </template>
              <template v-slot:cell(due)="data">
                {{ $moment(data.item.due, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
              </template>
              <template v-slot:cell(show_points_per_question)="data">
                <toggle-button
                  :width="80"
                  :value="Boolean(data.item.show_points_per_question)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitShowPointsPerQuestion(data.item)"
                />
              </template>
              <template v-slot:cell(show_scores)="data">
                <toggle-button
                  :width="80"
                  :value="Boolean(data.item.show_scores)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitShowScores(data.item)"
                />
              </template>
              <template v-slot:cell(solutions_released)="data">
                <toggle-button
                  :width="80"
                  :value="Boolean(data.item.solutions_released)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitSolutionsReleased(data.item)"
                />
              </template>
              <template v-slot:cell(students_can_view_assignment_statistics)="data">
                <toggle-button
                  :width="80"
                  :value="Boolean(data.item.students_can_view_assignment_statistics)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitShowAssignmentStatistics(data.item)"
                />
              </template>
              <template v-slot:cell(actions)="data">
                <div class="mb-0">
                  <b-tooltip :target="getTooltipTarget('viewSubmissionFiles',data.item.id)"
                             delay="500"
                  >
                    Grading
                  </b-tooltip>
                  <span v-show="data.item.source === 'a'" class="pr-1"
                        @click="getSubmissionFileView(data.item.id, data.item.submission_files)"
                  >
                    <b-icon
                      v-show="data.item.submission_files !== '0'"
                      :id="getTooltipTarget('viewSubmissionFiles',data.item.id)"
                      icon="check2"
                    />
                  </span>
                  <span v-if="user.role === 2">
                    <b-tooltip :target="getTooltipTarget('editAssignment',data.item.id)"
                               delay="500"
                    >
                      Assignment Properties
                    </b-tooltip>
                    <span class="pr-1" @click="editAssignment(data.item)">
                      <b-icon :id="getTooltipTarget('editAssignment',data.item.id)"
                              icon="gear"
                      />
                    </span>
                    <b-tooltip :target="getTooltipTarget('deleteAssignment',data.item.id)"
                               delay="500"
                    >
                      Delete Assignment
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('deleteAssignment',data.item.id)"
                            icon="trash"
                            @click="deleteAssignment(data.item.id)"
                    />
                  </span>
                </div>
              </template>
            </b-table>
          </div>
        </b-row>
      </b-container>
      <div v-if="!hasAssignments">
        <div class="mt-4">
          <b-alert :show="showNoAssignmentsAlert" variant="warning">
            <a href="#" class="alert-link">This course currently
              has
              no assignments.</a>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'
import { getTooltipTarget, initTooltips } from '../../helpers/Tooptips'

import { getAssignments } from '../../helpers/Assignments'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    ToggleButton,
    Loading
  },
  data: () => ({
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    title: '',
    assignmentGroups: [{ value: null, text: 'Please choose one' }],
    isLoading: false,
    solutionsReleased: 0,
    assignmentId: false, // if there's an assignmentId it's an update
    assignments: [],
    showPointsPerQuestionTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: "In case you only grade a random subset of questions, you can hide the number of points per question so that your students won't know which questions you'll be grading."
    },
    completedOrCorrectOptions: [
      { item: 'correct', name: 'correct' },
      { item: 'completed', name: 'completed' }
    ],
    courseId: false,
    fields: [
      {
        key: 'name',
        sortable: true,
        thStyle: { minWidth: '175px' }
      },
      'shown',
      {
        key: 'assignment_group',
        label: 'Group',
        sortable: true
      },
      {
        key: 'available_from',
        sortable: true,
        thStyle: { minWidth: '175px' }
      },
      {
        key: 'due',
        sortable: true,
        thStyle: { minWidth: '175px' }
      },
      'status',
      { key: 'show_points_per_question',
        thStyle: { minWidth: '120px' }
      },
      {
        key: 'show_scores',
        label: 'Scores'
      },
      {
        key: 'solutions_released',
        label: 'Solutions'
      },
      {
        key: 'students_can_view_assignment_statistics',
        label: 'Statistics'
      },
      {
        key: 'actions',
        thStyle: { minWidth: '100px' }
      }
    ],
    form: new Form({
      name: '',
      available_from: '',
      due: '',
      assessment_type: 'r',
      min_time_needed_in_learning_tree: null,
      percent_earned_for_entering_learning_tree: null,
      percent_decay: null,
      available_from_date: '',
      assignment_group_id: null,
      available_from_time: '09:00:00',
      due_date: '',
      due_time: '09:00:00',
      submission_files: '0',
      late_policy: 'not accepted',
      late_deduction_percent: null,
      late_deduction_applied_once: 1,
      late_deduction_application_period: '',
      type_of_submission: 'correct',
      source: 'a',
      scoring_type: 'p',
      include_in_weighted_average: 1,
      num_submissions_needed: '2',
      default_points_per_question: '10',
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
  },
  mounted () {
    this.courseId = this.$route.params.courseId
    this.isLoading = true
    this.getCourseInfo()
    if (![2, 4].includes(this.user.role)) {
      this.isLoading = false
      this.$noty.error('You are not allowed to access this page.')
      return false
    }
    this.getAssignments()
    this.getAssignmentGroups(this.courseId)
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  },
  methods: {
    resetLearningTreeToNull () {
      this.form.min_time_needed_in_learning_tree = null
      this.form.percent_earned_for_entering_learning_tree = null
      this.form.percent_decay = null
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
      this.form.available_from_date = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
      this.form.available_from_time = this.$moment(this.$moment(), 'YYYY-MM-DD HH:mm:SS').format('HH:mm:00')
      this.form.due_date = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
      this.form.due_time = this.$moment(this.$moment(), 'YYYY-MM-DD HH:mm:SS').format('HH:mm:00')
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
      this.form.assessment_type = 'r'
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
      this.form.assessment_type = assignment.assessment_type
      this.form.available_from_date = assignment.available_from_date
      this.form.available_from_time = assignment.available_from_time
      this.form.due_date = assignment.due_date
      this.form.due_time = assignment.due_time
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

      this.$router.push(`/assignments/${assignment.id}/summary`)
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
    async handleDeleteAssignment () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    submitAssignmentInfo (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.form.available_from = this.form.available_from_date + ' ' + this.form.available_from_time
      this.form.due = this.form.due_date + ' ' + this.form.due_time
      !this.assignmentId ? this.createAssignment() : this.updateAssignment()
    },
    deleteAssignment (assignmentId) {
      this.assignmentId = assignmentId
      this.$bvModal.show('modal-delete-assignment')
    },
    async updateAssignment () {
      try {
        const { data } = await this.form.patch(`/api/assignments/${this.assignmentId}`)

        console.log(data)
        if (data.available_after_due) {
          // had to create a custom process for checking available date past due date
          this.form.errors.set('due_date', data.message)
          console.log(this.form.errors)
          return false
        }
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

        console.log(data)
        if (data.available_after_due) {
          // had to create a custom process for checking available date past due date
          this.form.errors.set('due_date', data.message)
          console.log(this.form.errors)
          return false
        }
        this.$noty[data.type](data.message)
        this.resetAll('modal-assignment-properties')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    resetAll (modalId) {
      this.getAssignments()
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
      this.form.scoring_type = 'c'

      this.assignmentId = false
      this.form.errors.clear()
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
<style>
svg:focus, svg:active:focus {
  outline: none !important;
}

.header-high-z-index table thead tr th {
  z-index: 5 !important;
  border-top: 1px !important; /*gets rid of the flickering issue at top when scrolling.*/
}
</style>
