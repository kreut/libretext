<template>
  <div>
    <PageTitle v-if="canViewAssignments" :title="title" />
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <AssignmentProperties ref="assignmentProperties" />
      <b-modal
        id="modal-delete-assignment"
        ref="modal"
        title="Confirm Delete Assignment"
        ok-title="Yes, delete assignment!"
        @ok="handleDeleteAssignment"
      >
        <p>By deleting the assignment, you will also delete all student scores associated with the assignment.</p>
        <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
      </b-modal>
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
      <b-container>
        <b-row v-if="canViewAssignments" align-h="end" class="mb-4">
          <b-button v-if="(user && user.role === 2)"
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
                     sticky-header="800px"
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
                  <span v-if="user && user.role === 2">
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
                  <span v-if="user && user.role === 2">
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
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'

import { isLocked, getAssignments } from '~/helpers/Assignments'

import AssignmentProperties from '~/components/AssignmentProperties'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    ToggleButton,
    Loading,
    AssignmentProperties
  },
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
    fields: [
      {
        key: 'name',
        label: 'Assignment Name',
        sortable: true,
        stickyColumn: true,
        thStyle: 'min-width: 190px'
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
        thStyle: 'min-width: 170px'
      },
      {
        key: 'due',
        sortable: true,
        thStyle: 'min-width: 170px'
      },
      'status',
      {
        key: 'show_points_per_question'
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
        thStyle: 'min-width: 100px'
      }
    ],
    hasAssignments: false,
    has_submissions_or_file_submissions: false,
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
  async  mounted () {
    this.courseId = this.$route.params.courseId
    this.initAddAssignment = this.$refs.assignmentProperties.initAddAssignment
    this.editAssignment = this.$refs.assignmentProperties.editAssignment
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.isLoading = true
    await this.getCourseInfo()
    if (this.user) {
      if (![2, 4].includes(this.user.role)) {
        this.isLoading = false
        this.$noty.error('You are not allowed to access this page.')
        return false
      }

      this.getAssignments()
      await this.getAssignmentGroups(this.courseId)
    }
  },
  methods: {
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
    async handleDeleteAssignment () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    deleteAssignment (assignmentId) {
      this.assignmentId = assignmentId
      this.$bvModal.show('modal-delete-assignment')
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
      await this.getAssignments()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
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
<style scoped>

</style>
<style>
svg:focus, svg:active:focus {
  outline: none !important;
}

.header-high-z-index table thead tr th {
  z-index: 5 !important;
  border-top: 1px !important; /*gets rid of the flickering issue at top when scrolling.*/
}
</style>
