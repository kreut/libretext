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
      <AssignmentProperties ref="assignmentProperties" :course-id="parseInt(courseId)" />

      <b-modal
        id="modal-import-assignment"
        ref="modal"
        title="Import Assignment"
        ok-title="Yes, import assignment!"
        @ok="handleImportAssignment"
      >
        <b-form-group
          id="import_level"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Import Level"
          label-for="Import Level"
        >
          <b-form-radio-group v-model="importAssignmentForm.level" stacked>
            <b-form-radio value="properties_and_questions">
              Properties and questions
            </b-form-radio>
            <b-form-radio value="properties_and_not_questions">
              Just the properties
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>

        <vue-bootstrap-typeahead
          ref="queryTypeahead"
          v-model="importAssignmentForm.course_assignment"
          :data="allAssignments"
          placeholder="Enter an assignment from one of your courses"
        />
      </b-modal>

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

      <b-container>
        <b-row v-if="canViewAssignments" align-h="end" class="mb-4">
          <b-button v-if="(user && user.role === 2)"
                    class="mr-1"
                    size="sm"
                    variant="primary"
                    @click="initAddAssignment"
          >
            Add Assignment
          </b-button>
          <b-button v-if="(user && user.role === 2)"
                    class="mr-1"
                    size="sm"
                    variant="outline-primary"
                    @click="initImportAssignment"
          >
            Import Assignment
          </b-button>
          <b-button class="mr-1"
                    size="sm"
                    @click="getGradeBook()"
          >
            Gradebook
          </b-button>
        </b-row>
      </b-container>
      <div v-show="hasAssignments" class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th scope="col">
                Assignment Name
              </th>
              <th scope="col">
                Shown
              </th>
              <th scope="col">
                Group
              </th>
              <th scope="col">
                Available On
              </th>
              <th scope="col">
                Due
              </th>
              <th scope="col">
                Status
              </th>
              <th scope="col">
                Points per Question
              </th>
              <th scope="col">
                Scores
              </th>
              <th scope="col">
                Solutions
              </th>
              <th scope="col">
                Statistics
              </th>
              <th scope="col">
                Actions
              </th>
            </tr>
          </thead>
          <tbody is="draggable" v-model="assignments" tag="tbody" @end="saveNewOrder">
            <tr v-for="assignment in assignments" :key="assignment.id">
              <td style="width:300px">
                <b-icon icon="list" /> <span v-show="assignment.source === 'a'" class="pr-1" @click="getQuestions(assignment)">
                  <b-icon
                    v-show="isLocked(assignment)"
                    :id="getTooltipTarget('getQuestions',assignment.id)"
                    icon="lock-fill"
                  />
                </span><a href="" @click.prevent="getAssignmentView(user.role, assignment)">{{ assignment.name }}</a>
                <span v-if="user && [2,4].includes(user.role)">
                  <b-tooltip :target="getTooltipTarget('getQuestions',assignment.id)"
                             delay="500"
                  >
                    {{ getLockedQuestionsMessage(assignment) }}
                  </b-tooltip>

                </span>
              </td>
              <td>
                <toggle-button
                  :width="57"
                  :value="Boolean(assignment.shown)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Yes', unchecked: 'No'}"
                  @change="submitShowAssignment(assignment)"
                />
              </td>
              <td>{{ assignment.assignment_group }}</td>
              <td>
                {{ $moment(assignment.available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}<br>
                {{ $moment(assignment.available_from, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
              </td>
              <td style="width:200px">
                {{ $moment(assignment.due, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}<br>
                {{ $moment(assignment.due, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
              </td>
              <td> {{ assignment.status }}</td>
              <td>
                <toggle-button
                  :width="80"
                  :value="Boolean(assignment.show_points_per_question)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitShowPointsPerQuestion(assignment)"
                />
              </td>
              <td>
                <toggle-button
                  :width="80"
                  :value="Boolean(assignment.show_scores)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitShowScores(assignment)"
                />
              </td>
              <td>
                <toggle-button
                  :width="80"
                  :value="Boolean(assignment.solutions_released)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitSolutionsReleased(assignment)"
                />
              </td>
              <td>
                <toggle-button
                  :width="80"
                  :value="Boolean(assignment.students_can_view_assignment_statistics)"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'Shown', unchecked: 'Hidden'}"
                  @change="submitShowAssignmentStatistics(assignment)"
                />
              </td>
              <td>
                <div class="mb-0">
                  <b-tooltip :target="getTooltipTarget('viewSubmissionFiles',assignment.id)"
                             delay="500"
                  >
                    Grading
                  </b-tooltip>
                  <span v-show="assignment.source === 'a'" class="pr-1"
                        @click="getSubmissionFileView(assignment.id, assignment.submission_files)"
                  >
                    <b-icon
                      v-show="assignment.submission_files !== '0'"
                      :id="getTooltipTarget('viewSubmissionFiles',assignment.id)"
                      icon="check2"
                    />
                  </span>
                  <span v-show="user && user.role === 2">
                    <b-tooltip :target="getTooltipTarget('editAssignment',assignment.id)"
                               delay="500"
                    >
                      Assignment Properties
                    </b-tooltip>
                    <span class="pr-1" @click="editAssignment(assignment)">
                      <b-icon :id="getTooltipTarget('editAssignment',assignment.id)"
                              icon="gear"
                      />
                    </span>
                    <b-tooltip :target="getTooltipTarget('createAssignmentFromTemplate',assignment.id)"
                               triggers="hover"
                               delay="500"
                    >
                      Create Assignment From Template
                    </b-tooltip>
                    <span class="pr-1" @click="createAssignmentFromTemplate(assignment.id)">
                      <b-icon :id="getTooltipTarget('createAssignmentFromTemplate',assignment.id)"
                              icon="clipboard-check"
                      />
                    </span>
                    <b-tooltip :target="getTooltipTarget('deleteAssignment',assignment.id)"
                               delay="500"
                    >
                      Delete Assignment
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('deleteAssignment',assignment.id)"
                            icon="trash"
                            @click="deleteAssignment(assignment.id)"
                    />
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
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
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import { isLocked, getAssignments, isLockedMessage } from '~/helpers/Assignments'

import AssignmentProperties from '~/components/AssignmentProperties'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import draggable from 'vuedraggable'

export default {
  middleware: 'auth',
  components: {
    ToggleButton,
    Loading,
    AssignmentProperties,
    VueBootstrapTypeahead,
    draggable
  },
  data: () => ({
    currentOrderedAssignments: [],
    importAssignmentForm: new Form({
      course_assignment: '',
      level: 'properties_and_questions'
    }),
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    allAssignments: [],
    title: '',
    assessmentType: '',
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
    this.isLockedMessage = isLockedMessage
  },
  async mounted () {
    this.courseId = this.$route.params.courseId
    this.initAddAssignment = this.$refs.assignmentProperties.initAddAssignment
    this.editAssignment = this.$refs.assignmentProperties.editAssignment
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
      await this.getAssignments()
      this.currentOrderedAssignments = this.assignments
    }
  },
  methods: {
    async saveNewOrder () {
      let orderedAssignments = []
      for (let i = 0; i < this.assignments.length; i++) {
        orderedAssignments.push(this.assignments[i].id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedAssignments.length; i++) {
        if (this.currentOrderedAssignments[i] !== this.assignments[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.courseId}/order`, { ordered_assignments: orderedAssignments })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.assignments.length; i++) {
            this.assignments[i].order = i + 1
          }
          this.currentOrderedAssignments = this.assignments
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleImportAssignment (bvEvt) {
      bvEvt.preventDefault()
      try {
        const { data } = await axios.post(`/api/assignments/import/${this.courseId}`, this.importAssignmentForm)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.getAssignments()
        this.$bvModal.hide('modal-import-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async initImportAssignment () {
      try {
        const { data } = await axios.get(`/api/assignments/importable-by-user/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.allAssignments = data.all_assignments
        this.$bvModal.show('modal-import-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async createAssignmentFromTemplate (assignmentId) {
      try {
        const { data } = await axios.post(`/api/assignments/${assignmentId}/create-assignment-from-template`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.getAssignments()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getGradeBook () {
      this.$router.push(`/courses/${this.courseId}/gradebook`)
    },
    getLockedQuestionsMessage (assignment) {
      if ((Number(assignment.has_submissions_or_file_submissions))) {
        return this.isLockedMessage()
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
      this.$router.push(`/assignments/${assignmentId}/grading`)
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
<style scoped></style>
<style>
svg:focus, svg:active:focus {
  outline: none !important;
}

.header-high-z-index table thead tr th {
  z-index: 5 !important;
  border-top: 1px !important; /*gets rid of the flickering issue at top when scrolling.*/
}
</style>
