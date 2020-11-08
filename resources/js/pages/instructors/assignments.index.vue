<template>
  <div>
    <PageTitle v-if="canViewAssignments" title="Assignments"></PageTitle>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"></loading>

      <div v-if="user.role === 2">
        <div class="row mb-4 float-right" v-if="canViewAssignments">
          <b-button variant="primary" v-b-modal.modal-assignment-details v-on:click="initAddAssignment">Add Assignment
          </b-button>
        </div>
      </div>
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
              that you can update is the assignment's name and whether students can view the assignment
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
          id="assignment_type"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Assignment Type"
          label-for="Assignment Type"
        >
            <b-form-row>
              <b-col lg="4">
            <b-form-select v-model="form.assignment_type_id"
                           :options="assignmentTypes"
                           :class="{ 'is-invalid': form.errors.has('assignment_type_id') }"
                           @change="form.errors.clear('assignment_type_id')"
            ></b-form-select>
                <has-error :form="form" field="assignment_type_id"></has-error>
              </b-col>
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
                                :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)">
            <span v-on:click="resetSubmissionFilesAndPointsPerQuestion">

          <b-form-radio name="source" value="a">Adapt</b-form-radio>
                </span>
              <b-form-radio name="scoring_type" value="x">External</b-form-radio>
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
                                :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)">
            <span v-on:click="resetSubmissionFilesAndPointsPerQuestion">

          <b-form-radio name="scoring_type" value="c">Complete/Incomplete</b-form-radio>
                </span>
              <span v-on:click="form.students_can_view_assignment_statistics = 1">
              <b-form-radio name="scoring_type" value="p">Points</b-form-radio></span>
            </b-form-radio-group>
          </b-form-group>
          <div v-show="form.source === 'a'">
            <b-form-group
              v-if="form.scoring_type === 'p'"
              id="score_summary"
              label-cols-sm="4"
              label-cols-lg="3"
              label="Assignment Statistics"
              label-for="Assignment Statistics"
            >

              <b-form-radio-group v-model="form.students_can_view_assignment_statistics" stacked>

                <b-form-radio name="students_can_view_assignment_statistics" value="1">Students can view</b-form-radio>
                <b-form-radio name="students_can_view_assignment_statistics" value="0">Students cannot view
                </b-form-radio>
              </b-form-radio-group>
            </b-form-group>
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
                <b-form-radio name="submission_files" value="a">At the assignment level</b-form-radio>
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
        <b-table striped hover :fields="fields" :items="assignments">
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
import {getTooltipTarget} from '../../helpers/tooptips'
import {initTooltips} from "../../helpers/tooptips"
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
    selectedAssignmentType: null,
    assignmentTypes: [{value: null, text: 'Please choose one'}],
    isLoading: false,
    solutionsReleased: 0,
    assignmentId: false, //if there's an assignmentId it's an update
    assignments: [],
    completedOrCorrectOptions: [
      {item: 'correct', name: 'correct'},
      {item: 'completed', name: 'completed'}
    ],
    courseId: false,
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
      'actions'
    ],
    form: new Form({
      name: '',
      available_from: '',
      due: '',
      available_from_date: '',
      assignment_type_id: null,
      available_from_time: '09:00:00',
      due_date: '',
      due_time: '09:00:00',
      submission_files: '0',
      type_of_submission: 'correct',
      source: 'a',
      scoring_type: 'c',
      students_can_view_assignment_statistics: 0,
      num_submissions_needed: '2',
      default_points_per_question: '10'
    }),
    hasAssignments: false,
    has_submissions_or_file_submissions: false,
    min: '',
    canViewAssignments: false,
    showNoAssignmentsAlert: false,
  }),
  mounted() {
    this.courseId = this.$route.params.courseId
    this.isLoading = true
    this.getAssignments()
    this.getAssignmentTypes(this.courseId)
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  },
  methods: {
    async getAssignmentTypes(courseId) {
      try {
        const {data} = await axios.get(`/api/assignmentTypes/${courseId}`)

        for (let i = 0; i < data.assignment_types.length; i++) {
          this.assignmentTypes.push({
            value: data.assignment_types[i]['id'],
            text: data.assignment_types[i]['assignment_type']
          })
        }
      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    initAddAssignment() {
      this.has_submissions_or_file_submissions = 0
      this.solutionsReleased = 0
      this.form.available_from_date = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
      this.form.available_from_time = this.$moment(this.$moment(), 'YYYY-MM-DD HH:mm:SS').format('HH:mm:00')
      this.form.due_date = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')

    },
    hasSubmissionsColor(assignment) {
      //0, 1, 2 since has_submissions_or_file_submissions is additive
      return (assignment.has_submissions_or_file_submissions > 0) ? 'warning' : ''


    },
    async submitShowScores(assignment) {
      try {
        const {data} = await axios.patch(`/api/assignments/${assignment.id}/show-scores/${assignment.show_scores}`)
        await this.getAssignments()
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitSolutionsReleased(assignment) {
      try {
        const {data} = await axios.patch(`/api/assignments/${assignment.id}/solutions-released/${assignment.solutions_released}`)
        await this.getAssignments()
        this.$noty[data.type](data.message)
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
      console.log('click')
      this.form.default_points_per_question = 10
      this.form.submission_files = 0
      this.form.students_can_view_assignment_statistics = 0
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
      this.form.assignment_type_id = assignment.assignment_type_id
      this.form.source = assignment.source
      this.form.type_of_submission = assignment.type_of_submission
      this.form.submission_files = assignment.submission_files
      this.form.num_submissions_needed = assignment.num_submissions_needed
      this.form.default_points_per_question = assignment.default_points_per_question
      this.form.scoring_type = assignment.scoring_type
      this.form.students_can_view_assignment_statistics = assignment.students_can_view_assignment_statistics
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
    async getAssignments() {
      try {
        const {data} = await axios.get(`/api/assignments/courses/${this.courseId}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.canViewAssignments = true
        this.isLoading = false
        this.hasAssignments = data.length > 0
        this.showNoAssignmentsAlert = !this.hasAssignments
        this.assignments = data

      } catch (error) {
        this.$noty.error(error.message)
      }
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
    }
    ,
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

</style>
