<template>
  <div>
    <PageTitle v-if="canViewAssignments" :title="title"></PageTitle>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"></loading>

      <div v-if="user.role === 2">
        <b-container v-if="canViewAssignments" class="mb-3">
          <b-row>
            <b-col cols="6">
              <div v-if="hasAssignments">
                <span class="font-italic">Students can view their weighted averages: </span>
                <toggle-button
                  class="mt-2"
                  :width="55"
                  :value="studentsCanViewWeightedAverage"
                  @change="submitShowWeightedAverage()"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Yes', unchecked: 'No'}"/>
              </div>
            </b-col>
            <b-col cols="6">
              <div class="float-right">
                <b-button variant="outline-primary"
                          v-on:click="openLetterGradesModal">Set Letter Grades
                </b-button>
                <b-button variant="outline-primary"
                          v-on:click="initAssignmentGroupWeights">Set Assignment Group Weights
                </b-button>
                <b-button class="mr-1" variant="primary" v-b-modal.modal-assignment-details
                          v-on:click="initAddAssignment">Add Assignment
                </b-button>
              </div>
            </b-col>
          </b-row>
        </b-container>
      </div>
      <b-modal
        id="modal-assignment-group-weights"
        ref="modal"
        title="Assignment Group Weights"
        @ok="submitAssignmentGroupWeights"
        ok-title="Submit"
      >
        <p>Tell Adapt how you would like to weight your assignment groups so that it can compute a weighted average of
          all scores.</p>
        <b-table striped hover :fields="assignmentGroupWeightsFields" :items="assignmentGroupWeights">
          <template v-slot:cell(assignment_group_weight)="data">
            <b-col lg="5">
              <b-form-input
                :id="`assignment_group_id_${data.item.id}}`"
                v-model="assignmentGroupWeightsForm[data.item.id]"
                type="text"
                :class="{ 'is-invalid': assignmentGroupWeightsFormWeightError }"
                @keydown="assignmentGroupWeightsFormWeightError = ''"
              >
              </b-form-input>
            </b-col>
          </template>
        </b-table>
        <div class="ml-5">
          <b-form-invalid-feedback :state="false">
            {{ assignmentGroupWeightsFormWeightError }}
          </b-form-invalid-feedback>
        </div>
      </b-modal>

      <b-modal
        id="modal-letter-grades-editor"
        ref="modal"
        title="Letter Grades"
        @ok="submitLetterGrades"
        ok-title="Submit"
      >
        <p>Use the text area below to customize your letter grades, in a comma separated list of
          the form "Minimum grade for the group, Letter Grade". As an example, if the two letter grades that you offer are
        Pass and Fail, and students need at least a 60 to pass for the course, you should enter 0,Fail,60,Pass</p>
        <b-form-input
          id="letter_grades"
          v-model="letterGradesForm.letter_grades"
          type="text"
          placeholder=""
          :class="{ 'is-invalid': letterGradesForm.errors.has('letter_grades') }"
          @keydown="letterGradesForm.errors.clear('letter_grades')"
        >
        </b-form-input>
        <has-error :form="letterGradesForm" field="letter_grades"></has-error>
      </b-modal>

      <b-modal
        id="modal-letter-grades"
        ref="modal"
        title="Letter Grades"
        ok-only
      >
        <p>Let Adapt know how you would like to convert your students' weighted scores into letter grades.
          You can choose any type of text to represent each category.  Some examples might be "A+, A, A-,..." or "Excellent, Very Good, Good" or
          just "P,F".  You can use the <button class="btn-link" v-on:click="openLetterGradesEditorModal">letter grade editor</button> to customize the letter grades or just use the default.</p>
        <b-table striped
                 hover
                 :sticky-header="true"
                 :fields="letterGradeFields" :items="letterGradeItems">

        </b-table>
      </b-modal>


      <b-modal
        id="modal-assignment-details"
        ref="modal"
        title="Assignment Details"
        @ok="submitAssignmentInfo"
        @hidden="resetModalForms"
        ok-title="Submit"
        size="lg"
      >
        <b-form ref="form" @submit="createAssignment">
          <div v-if="has_submissions_or_file_submissions && !solutionsReleased">
            <b-alert variant="info" show><strong>Students have submitted responses to questions in the assignment so you
              can't change the source of the questions, the scoring type, the default points per question, or the type
              of file uploads. </strong>
            </b-alert>
          </div>
          <div v-show="solutionsReleased">
            <b-alert variant="info" show><strong>You have already released the solutions to this assignment. The only
              item
              that you can update is the assignment's name, the assignment's group, the instructions, and whether students can view the
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
                >
                </b-form-input>
                <has-error :form="form" field="name"></has-error>
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
                  v-on:shown="form.errors.clear('available_from_date')"
                  :disabled="Boolean(solutionsReleased)">
                </b-form-datepicker>
                <has-error :form="form" field="available_from_date"></has-error>
              </b-col>
              <b-col>
                <b-form-timepicker v-model="form.available_from_time"
                                   locale="en"
                                   :class="{ 'is-invalid': form.errors.has('available_from_time') }"
                                   v-on:shown="form.errors.clear('available_from_time')"
                                   :disabled="Boolean(solutionsReleased)">

                </b-form-timepicker>
                <has-error :form="form" field="available_from_time"></has-error>
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
                  v-on:shown="form.errors.clear('due_date')"
                  :disabled="Boolean(solutionsReleased)">
                </b-form-datepicker>
                <has-error :form="form" field="due_date"></has-error>
              </b-col>
              <b-col>
                <b-form-timepicker v-model="form.due_time"
                                   locale="en"
                                   :class="{ 'is-invalid': form.errors.has('due_time') }"
                                   v-on:shown="form.errors.clear('due_time')"
                                   :disabled="Boolean(solutionsReleased)">
                </b-form-timepicker>
                <has-error :form="form" field="due_time"></has-error>
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
                ></b-form-select>
                <has-error :form="form" field="assignment_group_id"></has-error>
              </b-col>
              <b-modal
                id="modal-create-assignment-group"
                ref="modal"
                title="Create Assignment Group"
                @ok="handleCreateAssignmentGroup"
                @hidden="resetAssignmentGroupForm"
                ok-title="Submit"
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
                    >
                    </b-form-input>
                    <has-error :form="assignmentGroupForm" field="assignment_group"></has-error>

                  </b-form-group>
                </b-form-row>
              </b-modal>
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

              <b-form-radio name="include_in_weighted_average" value="1">Include the assignment in computing a final
                weighted score
              </b-form-radio>
              <b-form-radio name="include_in_weighted_average" value="0">Do not include the assignment in computing a
                final weighted score
              </b-form-radio>
            </b-form-radio-group>
          </b-form-group>

          <b-tooltip target="internal"
                     delay="250">
            Get questions from the Adapt database or from the Query library
          </b-tooltip>

          <b-tooltip target="external"
                     delay="250">
            Use questions outside of Adapt and manually input scores into the grade book
          </b-tooltip>
          <b-tooltip target="can_view_assignment_statistics"
                     delay="250">
            Allows students to see how the class performed at the assignment and question level. Choose this option
            and then Show Scores when you are ready for them to see the statistics.
          </b-tooltip>
          <b-form-group
            id="source"
            label-cols-sm="4"
            label-cols-lg="3"
            label="Source"
            label-for="Source"
          >
            <b-form-radio-group v-model="form.source" stacked
                                :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)">
            <span v-on:click="resetSubmissionFilesAndPointsPerQuestion">

              <b-form-radio name="source" value="a">Internal <span id="internal" class="text-muted"><b-icon
                icon="question-circle"></b-icon></span></b-form-radio>
                </span>
              <b-form-radio name="scoring_type" value="x">External <span id="external" class="text-muted"><b-icon
                icon="question-circle"></b-icon></span></b-form-radio>
            </b-form-radio-group>
          </b-form-group>

          <b-form-group
            v-if="form.source === 'a'"
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
              >
              </b-form-textarea>
            </b-form-row>

          </b-form-group>
          <b-form-group
            id="scoring_type"
            label-cols-sm="4"
            label-cols-lg="3"
            label="Scoring Type"
            label-for="Scoring Type"
          >

            <b-form-radio-group v-model="form.scoring_type" stacked
                                :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)">
            <span v-on:click="resetSubmissionFilesAndPointsPerQuestion">

          <b-form-radio name="scoring_type" value="c">Complete/Incomplete</b-form-radio>
                </span>
              <span v-on:click="form.students_can_view_assignment_statistics = 1">
              <b-form-radio name="scoring_type" value="p">Points</b-form-radio></span>
            </b-form-radio-group>
          </b-form-group>

          <b-form-group
            v-if="form.source === 'x' && form.scoring_type === 'p'"
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
                >
                </b-form-input>
                <has-error :form="form" field="external_source_points"></has-error>
              </b-col>
            </b-form-row>
          </b-form-group>
          <div v-show="form.source === 'a'">
            <b-form-group
              v-if="form.scoring_type === 'p'"
              id="submission_files"
              label-cols-sm="4"
              label-cols-lg="3"
              label="Submission Files"
              label-for="Submission Files"
            >

              <b-form-radio-group v-model="form.submission_files" stacked
                                  :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)">
               <!-- <b-form-radio name="submission_files" value="a">At the assignment level</b-form-radio>-->
                <b-form-radio name="submission_files" value="q">At the question level</b-form-radio>
                <b-form-radio name="submission_files" value="0">Students cannot upload files</b-form-radio>
              </b-form-radio-group>
            </b-form-group>

            <b-form-group
              v-if="form.scoring_type === 'p'"
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
                    @keydown="form.errors.clear('default_points_per_question')"
                    :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
                  >
                  </b-form-input>
                  <has-error :form="form" field="default_points_per_question"></has-error>
                </b-col>
              </b-form-row>

            </b-form-group>
          </div>
        </b-form>
      </b-modal>
      <b-modal
        id="modal-delete-assignment"
        ref="modal"
        title="Confirm Delete Assignment"
        @ok="handleDeleteAssignment"
        @hidden="resetModalForms"
        ok-title="Yes, delete assignment!"

      >
        <p>By deleting the assignment, you will also delete all student scores associated with the assignment.</p>
        <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
      </b-modal>
      <div v-if="hasAssignments">
        <hr>
        <b-table class="header-high-z-index"
                 striped
                 hover
                 sticky-header="600px"
                 :fields="fields"
                 :items="assignments">
          <template v-slot:cell(name)="data">
            <div class="mb-0">
              <a href="" v-on:click.prevent="getAssignmentView(data.item)">{{ data.item.name }}</a>
            </div>
          </template>

          <template v-slot:cell(available_from)="data">
            {{ $moment(data.item.available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
          </template>
          <template v-slot:cell(due)="data">
            {{ $moment(data.item.due, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
          </template>
          <template v-slot:cell(show_scores)="data">
            <toggle-button
              :width="80"
              :value="Boolean(data.item.show_scores)"
              @change="submitShowScores(data.item)"
              :sync="true"
              :font-size="14"
              :margin="4"
              :color="{checked: '#28a745', unchecked: '#6c757d'}"
              :labels="{checked: 'Shown', unchecked: 'Hidden'}"/>
          </template>
          <template v-slot:cell(solutions_released)="data">
            <toggle-button
              :width="80"
              :value="Boolean(data.item.solutions_released)"
              @change="submitSolutionsReleased(data.item)"
              :sync="true"
              :font-size="14"
              :margin="4"
              :color="{checked: '#28a745', unchecked: '#6c757d'}"
              :labels="{checked: 'Shown', unchecked: 'Hidden'}"/>
          </template>
          <template v-slot:cell(students_can_view_assignment_statistics)="data">
            <toggle-button
              :width="80"
              :value="Boolean(data.item.students_can_view_assignment_statistics)"
              @change="submitShowAssignmentStatistics(data.item)"
              :sync="true"
              :font-size="14"
              :margin="4"
              :color="{checked: '#28a745', unchecked: '#6c757d'}"
              :labels="{checked: 'Shown', unchecked: 'Hidden'}"/>
          </template>
          <template v-slot:cell(actions)="data">
            <div class="mb-0">
             <span v-if="user.role === 2">
                <b-tooltip :target="getTooltipTarget('getQuestions',data.item.id)"
                           delay="500">
                    Get Questions
                  </b-tooltip>
            <span v-show="data.item.source === 'a'" class="pr-1" v-on:click="getQuestions(data.item)">
              <b-icon
                :variant="hasSubmissionsColor(data.item)"
                :id="getTooltipTarget('getQuestions',data.item.id)"
                icon="plus-circle"></b-icon>
            </span>
             </span>
              <b-tooltip :target="getTooltipTarget('viewSubmissionFiles',data.item.id)"
                         delay="500">
                View File Submissions
              </b-tooltip>
              <span v-show="data.item.source === 'a'" class="pr-1"
                    v-on:click="getSubmissionFileView(data.item.id, data.item.submission_files)">
              <b-icon
                v-if="data.item.submission_files !== '0'"
                icon="cloud-upload"
                :id="getTooltipTarget('viewSubmissionFiles',data.item.id)"
              >
              </b-icon>
            </span>
              <span v-if="user.role === 2">
               <b-tooltip :target="getTooltipTarget('editAssignment',data.item.id)"
                          delay="500">
              Edit Assignment
            </b-tooltip>
            <span class="pr-1" v-on:click="editAssignment(data.item)">
              <b-icon icon="pencil"
                      :id="getTooltipTarget('editAssignment',data.item.id)">
                      </b-icon>
            </span>
               <b-tooltip :target="getTooltipTarget('deleteAssignment',data.item.id)"
                          delay="500">
              Delete Assignment
            </b-tooltip>
            <b-icon icon="trash"
                    v-on:click="deleteAssignment(data.item.id)"
                    :id="getTooltipTarget('deleteAssignment',data.item.id)"></b-icon>
              </span>
            </div>
          </template>
        </b-table>
      </div>
      <div v-else>
        <br>
        <div class="mt-4">
          <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently
            has
            no assignments.</a></b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from "vform"
import {mapGetters} from "vuex"
import {ToggleButton} from 'vue-js-toggle-button'
import {getTooltipTarget} from '../../helpers/Tooptips'
import {initTooltips} from "../../helpers/Tooptips"
import {getAssignments} from "../../helpers/Assignments"
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  components: {
    ToggleButton,
    Loading
  },
  data: () => ({
    letterGradeFields: [
      'letter_grade',
      'min',
      'max'
    ],
    letterGradeItems:[
      {'letter_grade' : 'A', 'min': 90, 'max': '-'},
      {'letter_grade' : 'B', 'min': 80, 'max': 90},
      {'letter_grade' : 'C', 'min': 70, 'max': 80},
      {'letter_grade' : 'D', 'min': 60, 'max': 70},
      {'letter_grade' : 'F', 'min':  0, 'max': 60}
    ],
    title: '',
    studentsCanViewWeightedAverage: false,
    assignmentGroupWeights: [],
    assignmentGroups: [{value: null, text: 'Please choose one'}],
    isLoading: false,
    solutionsReleased: 0,
    assignmentId: false, //if there's an assignmentId it's an update
    assignments: [],
    completedOrCorrectOptions: [
      {item: 'correct', name: 'correct'},
      {item: 'completed', name: 'completed'}
    ],
    courseId: false,
    assignmentGroupWeightsFields: [
      'assignment_group',
      {
        key: 'assignment_group_weight',
        label: 'Weighting Percentage'
      }
    ],
    fields: [
      'name',
      'available_from',
      'due',
      'status',
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

      'actions'
    ],
    assignmentGroupWeightsFormWeightError: '',
    assignmentGroupWeightsForm: {},
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    letterGradesForm: new Form({
      letter_grades:'',
    }),
    form: new Form({
      name: '',
      available_from: '',
      due: '',
      available_from_date: '',
      assignment_group_id: null,
      available_from_time: '09:00:00',
      due_date: '',
      due_time: '09:00:00',
      submission_files: '0',
      type_of_submission: 'correct',
      source: 'a',
      scoring_type: 'c',
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
    showNoAssignmentsAlert: false,
  }),
  created() {
    this.getAssignments = getAssignments
  },
  mounted() {
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
    openLetterGradesEditorModal(){
      this.$bvModal.show('modal-letter-grades-editor')
      let formattedLetterGrades = ''
      for (let i=0; i<this.letterGradeItems.length;i++){
        formattedLetterGrades += `${this.letterGradeItems[i]['min']},${this.letterGradeItems[i]['letter_grade']}`
        if (i !== this.letterGradeItems.length-1){
          formattedLetterGrades += ','
        }
      }

      this.letterGradesForm.letter_grades = formattedLetterGrades
    },
    async handleCreateAssignmentGroup(bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const {data} = await this.assignmentGroupForm.post(`/api/assignmentGroups/${this.courseId}`)
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
    checkGroupId(groupId) {
      if (groupId === -1) {
        this.$bvModal.show('modal-create-assignment-group')
      }
    },
    async getCourseInfo() {
      try {
        const {data} = await axios.get(`/api/courses/${this.courseId}`)
        console.log(data)
        this.title = `${data.course.name} Assignments`
        this.studentsCanViewWeightedAverage = Boolean(data.course.students_can_view_weighted_average)
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)

      }
    },
    async submitShowWeightedAverage() {

      try {
        const {data} = await axios.patch(`/api/courses/${this.courseId}/students-can-view-weighted-average`,
          {'students_can_view_weighted_average': this.studentsCanViewWeightedAverage})
        this.$noty[data.type](data.message)
        if (data.error) {
          return false
        }
        this.studentsCanViewWeightedAverage = !this.studentsCanViewWeightedAverage
      } catch (error) {
        this.$noty.error(error.message)

      }
    },
    openLetterGradesModal(){
      this.$bvModal.show('modal-letter-grades')
    },
    isValidLetterGrades(){
      let letterGradesArray = this.letterGradesForm.letter_grades.split(',')
      if (letterGradesArray.length === 1) {
        this.letterGradesForm.errors.set('letter_grades','Please enter your list of letter grades and associated minimum scores.')
        return false
      }
      if (letterGradesArray.length % 2 !== 0){
        this.letterGradesForm.errors.set('letter_grades','Not every letter grade has a minimum score associated with it.')
        return false
      }
      let usedLetters = []
      let usedCutoffs = []
      for(let i=0;i<letterGradesArray.length/2; i++) {

        if (isNaN(letterGradesArray[2*i])) {
          this.letterGradesForm.errors.set('letter_grades', `${letterGradesArray[2*i]} is not a number.`)
          return false
        }
        if (letterGradesArray[2*i]<0) {
          this.letterGradesForm.errors.set('letter_grades', `${letterGradesArray[2*i]} should be a positive number.`)
          return false
        }
        if (usedLetters.includes(letterGradesArray[2*i+1])) {
          this.letterGradesForm.errors.set('letter_grades', `You used the letter grade "${letterGradesArray[2*i+1]}" multiple times.`)
          return false
        } else {
          usedLetters.push(letterGradesArray[2*i+1])
        }

        if (usedCutoffs.includes(letterGradesArray[2*i])) {
          this.letterGradesForm.errors.set('letter_grades', `You used the grade cutoff "${letterGradesArray[2*i]}" multiple times.`)
          return false
        } else {
          usedCutoffs.push(letterGradesArray[2*i])
        }
      }
     return true
    },
    async submitLetterGrades(bvModalEvt){
      bvModalEvt.preventDefault()
      if (!this.isValidLetterGrades()){
        return false
      }
      try {
        const {data} = await this.letterGradesForm.patch(`/api/letter-grades/${this.courseId}`)
        if (data.error) {
          this.$noty.error(data.message)
          return false
        }

      } catch (error) {
        this.$noty.error(error.message)
      }


    },
    async initAssignmentGroupWeights() {
      try {
        this.$bvModal.show('modal-assignment-group-weights')
        const {data} = await axios.get(`/api/assignmentGroupWeights/${this.courseId}`)
        if (data.error) {
          this.$noty.error(data.message)
          return false
        }
        this.assignmentGroupWeights = data.assignment_group_weights
        let formInputs = {}
        for (let i = 0; i < data.assignment_group_weights.length; i++) {
          formInputs[data.assignment_group_weights[i].id] = data.assignment_group_weights[i].assignment_group_weight
        }
        console.log(this.assignmentGroupWeights)
        this.assignmentGroupWeightsForm = new Form(formInputs)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitAssignmentGroupWeights(bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const {data} = await this.assignmentGroupWeightsForm.patch(`/api/assignmentGroupWeights/${this.courseId}`)
        if (data.form_error) {
          this.assignmentGroupWeightsFormWeightError = data.message
          return false
        }
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-assignment-group-weights')
      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    async getAssignmentGroups(courseId) {
      try {
        const {data} = await axios.get(`/api/assignmentGroups/${courseId}`)
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
    initAddAssignment() {
      this.has_submissions_or_file_submissions = 0
      this.solutionsReleased = 0
      this.form.assignment_group_id = null
      this.form.available_from_date = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
      this.form.available_from_time = this.$moment(this.$moment(), 'YYYY-MM-DD HH:mm:SS').format('HH:mm:00')
      this.form.due_date = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
      this.form.due_time = this.$moment(this.$moment(), 'YYYY-MM-DD HH:mm:SS').format('HH:mm:00')
    },
    hasSubmissionsColor(assignment) {
      //0, 1, 2 since has_submissions_or_file_submissions is additive
      return (assignment.has_submissions_or_file_submissions > 0) ? 'warning' : ''


    },
    async submitShowAssignmentStatistics(assignment) {
      if (!assignment.students_can_view_assignment_statistics && !assignment.show_scores) {
        this.$noty.info('If you would like students to view the assignment statistics, please first allow them to view the scores.')
        return false
      }

      try {
        const {data} = await axios.patch(`/api/assignments/${assignment.id}/show-assignment-statistics/${Number(assignment.students_can_view_assignment_statistics)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.students_can_view_assignment_statistics = !assignment.students_can_view_assignment_statistics
      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    async submitShowScores(assignment) {
      if (assignment.students_can_view_assignment_statistics && assignment.show_scores) {
        this.$noty.info('If you would like students to view the scores, please first hide the assignment statistics.')
        return false
      }
      console.log(assignment)
      try {
        const {data} = await axios.patch(`/api/assignments/${assignment.id}/show-scores/${Number(assignment.show_scores)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.show_scores = !assignment.show_scores
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitSolutionsReleased(assignment) {
      try {
        const {data} = await axios.patch(`/api/assignments/${assignment.id}/solutions-released/${Number(assignment.solutions_released)}`)
        this.$noty[data.type](data.message)

        assignment.solutions_released = !assignment.solutions_released
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleReleaseSolutions(bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const {data} = await this.patch(`/api/assignments/${this.assignmentId}/release-solutions`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-release-solutions-show-scores')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    resetSubmissionFilesAndPointsPerQuestion() {
      this.form.default_points_per_question = 10
      this.form.submission_files = 0
      this.form.students_can_view_assignment_statistics = 0
      this.form.external_source_points = 100
      this.form.errors.clear('default_points_per_question')
      this.form.errors.clear('external_source_points')

    },
    editAssignment(assignment) {
      console.log(assignment)

      this.has_submissions_or_file_submissions = (assignment.has_submissions_or_file_submissions === 1)
      this.solutionsReleased = assignment.solutions_released
      this.assignmentId = assignment.id
      this.number_of_questions = assignment.number_of_questions
      this.form.name = assignment.name
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
      this.$bvModal.show('modal-assignment-details')
    }
    ,
    getQuestions(assignment) {

      if (Boolean(Number(assignment.has_submissions_or_file_submissions))) {
        this.$noty.info("Since students have already submitted responses to this assignment, you won't be able to add or remove questions.")
        return false
      }
      if (Boolean(Number(assignment.solutions_released))) {
        this.$noty.info("You have already released the solutions to this assignment, so you won't be able to add or remove questions.")
        return false
      }
      this.$router.push(`/assignments/${assignment.id}/questions/get`)
    }
    ,
    getAssignmentView(assignment) {
      if (assignment.source === 'x') {
        this.$noty.info("This assignment has no questions to view because it is an external assignment.  To add questions, please edit the assignment and change the Source to Adapt.")
        return false
      }
      if (assignment.scoring_type === 'c') {
        this.$router.push(`/assignments/${assignment.id}/questions/view`)//no summary statistics
        return false
      }

      this.$router.push(`/assignments/${assignment.id}/summary`)
    }
    ,
    getSubmissionFileView(assignmentId, submissionFiles) {
      if (submissionFiles === 0) {
        this.$noty.info('If you would like students to upload files as part of the assignment, please edit this assignment.')
        return false
      }
      let type
      switch (submissionFiles) {
        case('q'):
          type = 'question'
          break
        case('a'):
          type = 'assignment'
          break
      }

      this.$router.push(`/assignments/${assignmentId}/${type}-files`)
    }
    ,
    async handleDeleteAssignment() {
      try {
        const {data} = await axios.delete(`/api/assignments/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    ,
    submitAssignmentInfo(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.form.available_from = this.form.available_from_date + ' ' + this.form.available_from_time
      this.form.due = this.form.due_date + ' ' + this.form.due_time
      !this.assignmentId ? this.createAssignment() : this.updateAssignment()
    }
    ,
    deleteAssignment(assignmentId) {
      this.assignmentId = assignmentId
      this.$bvModal.show('modal-delete-assignment')
    }
    ,
    async updateAssignment() {

      try {

        const {data} = await this.form.patch(`/api/assignments/${this.assignmentId}`)

        console.log(data)
        if (data.available_after_due) {
          //had to create a custom process for checking available date past due date
          this.form.errors.set('due_date', data.message)
          console.log(this.form.errors)
          return false
        }
        this.$noty[data.type](data.message)
        this.resetAll('modal-assignment-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    }
    ,
    async createAssignment() {
      try {
        this.form.course_id = this.courseId
        const {data} = await this.form.post(`/api/assignments`)

        console.log(data)
        if (data.available_after_due) {
          //had to create a custom process for checking available date past due date
          this.form.errors.set('due_date', data.message)
          console.log(this.form.errors)
          return false
        }
        this.$noty[data.type](data.message)
        this.resetAll('modal-assignment-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    }
    ,
    resetAll(modalId) {
      this.getAssignments()
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    resetAssignmentGroupForm() {
      this.assignmentGroupForm.errors.clear()
      this.assignmentGroupForm.assignment_group = ''
    },
    resetModalForms() {
      this.form.name = ''
      this.form.available_from_date = ''
      this.form.available_from_time = '09:00:00'
      this.form.due_date = ''
      this.form.due_time = '09:00:00'
      this.form.type_of_submission = 'correct'
      this.form.num_submissions_needed = '2'
      this.form.submission_files = '0'
      this.form.default_points_per_question = '10'
      this.form.scoring_type = 'c'

      this.assignmentId = false
      this.form.errors.clear()
    }
    ,
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
}
</script>
<style>
svg:focus, svg:active:focus {
  outline: none !important;
}
.header-high-z-index table thead tr th{
  z-index: 5 !important;
  border-top: 1px !important; /*gets rid of the flickering issue at top when scrolling.*/
}
</style>
