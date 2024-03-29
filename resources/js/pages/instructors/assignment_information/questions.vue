<template>
  <div>
    <CannotDeleteAssessmentFromBetaAssignmentModal />
    <MigrateToAdapt :key="`migrate-to-adapt-${migrateToAdaptAssignmentId}-${migrateToAdaptQuestionId}`"
                    :assignment-id="Number(assignmentId)"
                    :question-id="migrateToAdaptQuestionId"
                    :question-title="migrateToAdaptQuestionTitle"
                    :questions="items"
                    @updateMigrationMessage="updateMigrationMessage"
    />
    <b-modal
      v-if="questionToEdit"
      :id="`modal-edit-question-${questionToEdit.id}`"
      :key="`modal-edit-question-${questionToEdit.id}`"
      :title="`Edit Question &quot;${questionToEdit.title}&quot;`"
      size="xl"
      no-close-on-backdrop
      hide-footer
      @hidden="$emit('reloadCurrentAssignmentQuestions')"
    >
      <CreateQuestion :key="`question-to-edit-${questionToEdit.id}`"
                      :question-to-edit="questionToEdit"
                      :parent-get-my-questions="getAssignmentInfo"
                      :modal-id="'my-questions-question-to-view-questions-editor'"
                      :question-exists-in-own-assignment="questionToEdit.question_exists_in_own_assignment"
                      :question-exists-in-another-instructors-assignment="questionToEdit.question_exists_in_another_instructors_assignment"
      />
    </b-modal>
    <b-modal
      id="modal-confirm-refresh-questions-and-properties"
      title="Confirm refresh questions and properties"
    >
      <b-form-checkbox
        id="checkbox-1"
        v-model="confirmedRefreshQuestionsAndProperties"
        name="confirm-"
        :value="true"
        :unchecked-value="false"
      >
        I understand that the question will be refreshed in all ADAPT assignments where this question exists. Student
        submissions will not be removed.
      </b-form-checkbox>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-refresh-questions-and-properties')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary"
                  @click="refreshQuestions('questions_and_properties')"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal
      v-if="alphaAssignmentQuestion"
      id="modal-view-question"
      ref="modalViewQuestion"
      title="View Question"
      size="lg"
    >
      <div>
        <iframe v-show="alphaAssignmentQuestion.non_technology"
                :key="`non-technology-iframe-${alphaAssignmentQuestion.id}`"
                v-resize="{checkOrigin: false }"
                width="100%"
                :src="alphaAssignmentQuestion.non_technology_iframe_src"
                frameborder="0"
        />
      </div>

      <div v-if="alphaAssignmentQuestion.technology_iframe">
        <iframe
          :key="`technology-iframe-${alphaAssignmentQuestion.id}`"
          v-resize="{ checkOrigin: false }"
          width="100%"
          :src="alphaAssignmentQuestion.technology_iframe"
          frameborder="0"
        />
      </div>
      <template #modal-footer>
        <b-button
          v-show="viewQuestionAction==='add'"
          size="sm"
          class="float-right"
          variant="primary"
          @click="addQuestionFromAlphaAssignment()"
        >
          Add Question
        </b-button>
        <b-button
          v-show="viewQuestionAction==='remove'"
          size="sm"
          class="float-right"
          variant="danger"
          @click="removeQuestionFromBetaAssignment()"
        >
          Remove Question
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-remove-question"
      ref="modal"
      title="Confirm Remove Question"
    >
      <RemoveQuestion :beta-assignments-exist="betaAssignmentsExist" />
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-remove-question')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitRemoveQuestion()"
        >
          Yes, remove question!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-non-h5p"
      ref="h5pModal"
      title="Non-H5P assessments in clicker assignment"
    >
      <b-alert :show="true" variant="danger">
        <span class="font-weight-bold">
          {{ h5pText }}
        </span>
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-non-h5p')">
          OK
        </b-button>
      </template>
    </b-modal>

    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle title="Questions" />
        <AssessmentTypeWarnings :assessment-type="assessmentType"
                                :open-ended-questions-in-real-time="openEndedQuestionsInRealTime"
                                :learning-tree-questions-in-non-learning-tree="learningTreeQuestionsInNonLearningTree"
                                :non-learning-tree-questions="nonLearningTreeQuestions"
                                :beta-assignments-exist="betaAssignmentsExist"
                                :h5p-questions-with-anonymous-users="h5pQuestionsWithAnonymousUsers"
                                :h5p-questions-with-real-time-and-multiple-attempts="h5pQuestionsWithRealTimeAndMultipleAttempts"
                                :h5p-non-adapt-questions="h5pNonAdaptQuestions"
        />
        <div v-if="items.length">
          <div v-if="formative">
            <p>This is a formative assignment. Students will have access to their submissions during a single session.</p>
            </div>
          <div v-else>
          <p>
            The assessments that make up this assignment are <span class="font-weight-bold">{{ assessmentType }}</span>
            assessments.
            <span v-if="assessmentType === 'delayed'">
              Students will be able to get feedback for their responses after the assignment is closed.
            </span>
            <span v-if="assessmentType === 'learning tree'">
              Learning trees provide additional resources if they are unable to answer a question correctly.
            </span>
            <span v-if="assessmentType === 'clicker'">
              Students answer questions within a short timeframe and instructors get up-to-date statistics on submissions.
            </span>
          </p>
          </div>
        </div>
        <b-card v-show="user.role === 2 && betaCourseApprovals.length"
                header="default"
                header-html="<h2 class=&quot;h7&quot;>Beta Course Approvals</h2>"
        >
          <p>
            The Alpha course instructor has either added or removed assessments on the tethered assignment.
            By approving any changes here, your own students' assignments will reflect the changes.
            In addition, their scores will be automatically updated to reflect the change; it is therefore
            advisable not to approve any changes during a grading period.
          </p>
          <p class="font-weight-bold">
            Due to the tethered nature of the assignment, once you approve an add or remove, this action cannot be
            undone.
          </p>
          <b-card-text>
            <b-table aria-label="Beta course approvals"
                     striped
                     hover
                     :fields="fields"
                     :items="betaCourseApprovals"
            >
              <template v-slot:cell(title)="data">
                <a href="" @click.prevent="viewQuestionInModal(data.item,data.item.action)">
                  {{ data.item.title !== null ? data.item.title : 'None provided' }}
                </a>
              </template>
              <template v-slot:cell(action)="data">
                <b-button v-if="data.item.action === 'add'"
                          variant="primary"
                          size="sm"
                          @click="alphaAssignmentQuestion=data.item;addQuestionFromAlphaAssignment()"
                >
                  Add
                </b-button>
                <b-button v-if="data.item.action === 'remove'"
                          variant="danger"
                          size="sm"
                          @click="alphaAssignmentQuestion=data.item;removeQuestionFromBetaAssignment()"
                >
                  Remove
                </b-button>
              </template>
            </b-table>
          </b-card-text>
        </b-card>
        <div v-if="items.length">
          <b-button v-if="false"
                    variant="primary"
                    size="sm"
                    @click="refreshQuestions('question_properties')"
          >
            Refresh Question Properties
          </b-button>
          <QuestionCircleTooltip v-if="false" :id="'refresh-question-properties'" />
          <b-tooltip target="refresh-question-properties"
                     delay="500"
                     triggers="hover focus"
          >
            If you have updated the text, solution, a11y, hint, or libretexts link, in any of questions in this
            assignment,
            then you can perform a mass refresh for all of them.
          </b-tooltip>
          <span class="float-right">
            <b-button v-if="false"
                      variant="danger"
                      size="sm"
                      @click="confirmRefreshQuestionsAndProperties"
            >
              Refresh Questions And Properties
            </b-button>
          </span>

          <b-button v-if="isMe && isCommonsCourse"
                    size="sm"
                    variant="primary"
                    @click="showConfirmMigrateToAdapt(Number(assignmentId),0,'')"
          >
            Re-migrate assignment
          </b-button>
        </div>
        <table class=" table table-striped mt-2"
               aria-label="Assignment questions"
        >
          <thead>
            <tr>
              <th scope="col">
                Order
              </th>
              <th scope="col">
                Title
                <b-icon-sort-alpha-down id="sort-by-title" @click="sortByTitle" />
              </th>
              <th v-if="user.role === 2" scope="col" style="width: 150px;">
                ADAPT ID
                <QuestionCircleTooltip :id="'adapt-id-tooltip'" />
                <b-tooltip target="adapt-id-tooltip"
                           delay="500"
                           triggers="hover focus"
                >
                  This ID is of the form {Assignment ID}-{Question ID} and is unique at the assignment level.
                </b-tooltip>
              </th>
              <th v-if="user.role === 2 && assessmentType==='learning tree'" scope="col">
                Learning Tree ID
              </th>
              <th scope="col">
                Submission
              </th>
              <th scope="col" v-if="!formative">
                Points
              </th>
              <th scope="col">
                Solution
              </th>
              <th v-if="user.role === 2" scope="col" :style="isMe && isCommonsCourse ? 'width:115px;' : 'width:90px;'">
                Actions
              </th>
              <th v-if="showRefreshStatus" scope="col">
                Refresh Status
              </th>
            </tr>
          </thead>
          <tbody is="draggable"
                 v-model="items"
                 tag="tbody"
                 :options="{disabled : user.role === 4}"
                 @end="saveNewOrder"
          >
            <tr v-for="item in items" :key="item.id">
              <th scope="row">
                <b-icon v-if="user.role === 2" icon="list" />
                {{ item.order }}
              </th>
              <td>
                <span v-show="isBetaAssignment"
                      class="text-muted"
                >&beta; </span>
                <span v-show="isAlphaCourse"
                      class="text-muted"
                >&alpha; </span>
                <a href="" @click.stop.prevent="viewQuestion(item.question_id)">{{ item.title }}</a>
                <FormativeWarning :formative-question="item.is_formative_question"
                                  :question-id="item.question_id"
                />
                <span v-html="item.migrationMessage" />
              </td>
              <td v-if="user.role === 2">
                {{ item.assignment_id_question_id }}
                <b-tooltip :target="getTooltipTarget('remove',item.question_id)"
                           delay="500"
                           triggers="hover focus"
                >
                  Copy the ADAPT ID
                </b-tooltip>
                <a :id="getTooltipTarget('copy',item.question_id)"
                   href=""
                   class="pr-1"
                   :aria-label="`Copy ADAPT ID for ${item.title}`"
                   @click.prevent="doCopy(item.assignment_id_question_id)"
                >
                  <font-awesome-icon :icon="copyIcon" />
                </a>
              </td>

              <td v-if="user.role === 2 && assessmentType==='learning tree'">
                <span v-if="item.learning_tree_user_id !== user.id">{{ item.learning_tree_id }}</span>
                <span v-if="item.learning_tree_user_id === user.id">
                  <a :href="`/instructors/learning-trees/editor/${item.learning_tree_id}`" target="_blank">{{ item.learning_tree_id }}</a>
                </span>
              </td>
              <td>
                {{ item.submission }}
              </td>
              <td v-if="!formative">{{ item.points }}</td>
              <td>
                <span v-if="item.qti_answer_json">
                  <QtiJsonAnswerViewer
                    :modal-id="item.id"
                    :qti-json="item.qti_answer_json"
                  />
                  <b-button
                    size="sm"
                    variant="outline-info"
                    @click="$bvModal.show(`qti-answer-${item.id}`)"
                  >
                    View Correct Answer
                  </b-button>
                </span>
                <SolutionFileHtml v-if="!item.qti_answer_json"
                                  :key="item.question_id"
                                  :questions="items"
                                  :current-page="item.order"
                                  :format-filename="false"
                />
              </td>
              <td v-if="user.role === 2">
                <a v-if="isMe && isCommonsCourse"
                   :id="getTooltipTarget('re-migrate',item.question_id)"
                   href=""
                   class="pr-1"
                   @click.prevent="showConfirmMigrateToAdapt(0, item.question_id, item.title)"
                >
                  <b-icon-arrow-clockwise class="text-muted"/>
                </a>
                <b-tooltip :target="getTooltipTarget('re-migrate',item.question_id)"
                           delay="500"
                           triggers="hover focus"
                >
                  Re-migrate question
                </b-tooltip>
                <b-tooltip :target="getTooltipTarget('edit',item.question_id)"
                           delay="500"
                           triggers="hover focus"
                >
                  Edit question source
                </b-tooltip>

                <a :id="getTooltipTarget('edit',item.question_id)"
                   href=""
                   class="pr-1"
                   @click.prevent="editQuestionSource(item)"
                >
                  <b-icon class="text-muted"
                          icon="pencil"
                          aria-label="Edit question source"
                  />
                </a>
                <CloneQuestion
                  :key="`copy-question-${item.question_id}`"
                  :question-id="item.question_id"
                  :question-editor-user-id="item.question_editor_user_id"
                  :title="item.title"
                  :license="item.license"
                  :public="item.public"
                  :library="item.library"
                  :non-technology="item.non_technology"
                  :assignment-id="+assignmentId"
                  @reloadQuestions="getAssignmentInfo()"
                />
                <b-tooltip :target="getTooltipTarget('remove',item.question_id)"
                           delay="500"
                           triggers="hover focus"
                >
                  Remove the question from the assignment
                </b-tooltip>
                <a :id="getTooltipTarget('remove',item.question_id)"
                   href=""
                   class="pr-1"
                   @click.prevent="initRemoveQuestionFromAssignment(item.question_id)"
                >
                  <b-icon class="text-muted"
                          icon="trash"
                          :aria-label="`Remove question ${item.title} from the assignment`"
                  />
                </a>
              </td>
              <td v-if="showRefreshStatus">
                <span v-html="item.refresh_status" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-if="!items.length && !isLoading" class="mt-5">
      <b-alert variant="warning" :show="true">
        <span class="font-weight-bold">This assignment doesn't have any questions.</span>
        <strong />
      </b-alert>
    </div>
  </div>
</template>
<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import draggable from 'vuedraggable'
import { h5pResizer } from '~/helpers/H5PResizer'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'

import RemoveQuestion from '~/components/RemoveQuestion'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { viewQuestion, doCopy, editQuestionSource, getQuestionToEdit } from '~/helpers/Questions'
import AssessmentTypeWarnings from '~/components/AssessmentTypeWarnings'
import CannotDeleteAssessmentFromBetaAssignmentModal from '~/components/CannotDeleteAssessmentFromBetaAssignmentModal'
import CreateQuestion from '~/components/questions/CreateQuestion'
import QtiJsonAnswerViewer from '~/components/QtiJsonAnswerViewer'
import {
  h5pText,
  updateOpenEndedInRealTimeMessage,
  updateLearningTreeInNonLearningTreeMessage,
  updateNonLearningTreeInLearningTreeMessage,
  updateH5pNonAdaptQuestionsMessage
} from '~/helpers/AssessmentTypeWarnings'
import SolutionFileHtml from '~/components/SolutionFileHtml'
import MigrateToAdapt from '~/components/MigrateToAdapt'
import CloneQuestion from '~/components/CloneQuestion'
import FormativeWarning from '~/components/FormativeWarning.vue'

export default {
  middleware: 'auth',
  components: {
    FormativeWarning,
    MigrateToAdapt,
    QtiJsonAnswerViewer,
    AssessmentTypeWarnings,
    FontAwesomeIcon,
    Loading,
    draggable,
    RemoveQuestion,
    CannotDeleteAssessmentFromBetaAssignmentModal,
    SolutionFileHtml,
    CreateQuestion,
    CloneQuestion
  },
  metaInfo () {
    return { title: 'Assignment Questions' }
  },
  data: () => ({
    formative: false,
    isCommonsCourse: false,
    assignmentId: 0,
    migrateToAdaptQuestionId: 0,
    migrateToAdaptQuestionTitle: '',
    migrateToAdaptAssignmentId: 0,
    h5pNonAdaptQuestions: [],
    isQuestionWeight: false,
    submissionsExist: false,
    h5pQuestionsWithRealTimeAndMultipleAttempts: false,
    confirmedRefreshQuestionsAndProperties: false,
    showRefreshStatus: false,
    h5pQuestionsWithAnonymousUsers: false,
    isAlphaCourse: false,
    viewQuestionAction: '',
    alphaAssignmentQuestion: {},
    fields: [
      'title',
      'action'
    ],
    betaCourseApprovals: [],
    isBetaAssignment: false,
    betaAssignmentsExist: false,
    openEndedQuestionsInRealTime: '',
    learningTreeQuestionsInNonLearningTree: '',
    nonLearningTreeQuestions: '',
    assessmentType: '',
    adaptId: 0,
    copyIcon: faCopy,
    currentOrderedQuestions: [],
    items: [],
    isLoading: true,
    questionId: 0,
    questionToEdit: {}
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  created () {
    this.updateOpenEndedInRealTimeMessage = updateOpenEndedInRealTimeMessage
    this.updateLearningTreeInNonLearningTreeMessage = updateLearningTreeInNonLearningTreeMessage
    this.updateNonLearningTreeInLearningTreeMessage = updateNonLearningTreeInLearningTreeMessage
    this.updateH5pNonAdaptQuestionsMessage = updateH5pNonAdaptQuestionsMessage
    this.h5pText = h5pText
    this.editQuestionSource = editQuestionSource
    this.getQuestionToEdit = getQuestionToEdit
  },
  mounted () {
    if (![2, 4, 5].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.getTooltipTarget = getTooltipTarget
    this.viewQuestion = viewQuestion
    this.doCopy = doCopy
    initTooltips(this)
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
    this.getBetaCourseApprovals()
    h5pResizer()
  },
  methods: {
    updateMigrationMessage (questionId, type, message) {
      let migratedQuestion = this.items.find(question => question.id === questionId)
      if (migratedQuestion) {
        let messageClass = type === 'success' ? 'text-success' : 'text-danger'
        migratedQuestion.migrationMessage = `<span class="${messageClass}">${message}</span>`
      }
      this.$forceUpdate()
    },
    showConfirmMigrateToAdapt (assignmentId, questionId, questionTitle) {
      this.migrateToAdaptQuestionId = questionId
      this.migrateToAdaptAssignmentId = assignmentId
      this.migrateToAdaptQuestionTitle = questionTitle
      this.$nextTick(() => {
        this.$bvModal.show(`modal-confirm-migrate-to-adapt-${this.assignmentId}-${this.migrateToAdaptQuestionId}`)
      })
    },
    initRemoveQuestionFromAssignment (questionId) {
      this.submissionsExist && this.isQuestionWeight
        ? this.$noty.info('You cannot remove this question since there are already submissions and this assignment computes points using question weights.')
        : this.openRemoveQuestionModal(questionId)
    },
    confirmRefreshQuestionsAndProperties () {
      this.confirmedRefreshQuestionsAndProperties = false
      this.$bvModal.show('modal-confirm-refresh-questions-and-properties')
    },
    async refreshQuestions (type) {
      if (type === 'questions_and_properties') {
        if (!this.confirmedRefreshQuestionsAndProperties) {
          this.$noty.info('Please check the box before proceeding.')
          return false
        }
        this.$bvModal.hide('modal-confirm-refresh-questions-and-properties')
      }
      let url
      let method
      for (let i = 0; i < this.items.length; i++) {
        this.items[i].refresh_status = 'Pending'
      }
      this.showRefreshStatus = true
      for (let i = 0; i < this.items.length; i++) {
        try {
          url = (type === 'question_properties')
            ? `/api/questions/${this.items[i].question_id}/refresh-properties`
            : `/api/questions/${this.items[i].question_id}/refresh/${this.assignmentId}`
          method = (type === 'question_properties')
            ? 'patch'
            : 'post'
          const { data } = await axios[method](url)
          this.items[i].refresh_status = data.type === 'success'
            ? '<span class="text-success">Success</span>'
            : '<span class="text-danger">Error</span>'
          if (data.type === 'success' && data.solution_html) {
            this.items[i].solution_html = data.solution_html
            this.items[i].solution_type = 'html'
          }
        } catch (error) {
          this.items[i].refresh_status = '<span class="text-danger">Error</span>'
        }
        this.$forceUpdate()
      }
    },
    viewQuestionInModal (question, action) {
      this.alphaAssignmentQuestion = question
      this.viewQuestionAction = action
      this.$bvModal.show('modal-view-question')
    },
    async removeQuestionFromBetaAssignment () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.alphaAssignmentQuestion.question_id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.betaCourseApprovals = this.betaCourseApprovals.filter(question => question.question_id !== this.alphaAssignmentQuestion.question_id)
          await this.getAssignmentInfo()
          this.$bvModal.hide('modal-view-question')
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },

    async addQuestionFromAlphaAssignment () {
      try {
        const { data } = await axios.post(`/api/assignments/${this.assignmentId}/questions/${this.alphaAssignmentQuestion.question_id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.betaCourseApprovals = this.betaCourseApprovals.filter(question => question.question_id !== this.alphaAssignmentQuestion.question_id)
          await this.getAssignmentInfo()
          this.$bvModal.hide('modal-view-question')
        }
      } catch (error) {
        this.$noty.error('We could not add the question to the assignment.  Please try again or contact us for assistance.')
      }
    },
    async getBetaCourseApprovals () {
      try {
        const { data } = await axios.get(`/api/beta-course-approvals/assignment/${this.assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.betaCourseApprovals = data.beta_course_approvals
      } catch (error) {
        this.$noty.error('We could not retrieve your Beta course approvals.  Please try again or contact us for assistance.')
      }
    },
    async submitRemoveQuestion () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.questionId}`)
        this.$bvModal.hide('modal-remove-question')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.$noty.info(data.message)
        await this.getAssignmentInfo()
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },
    openRemoveQuestionModal (questionId) {
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-delete-assessment-from-beta-assignment')
        return false
      }
      this.questionId = questionId
      this.$bvModal.show('modal-remove-question')
    },
    async sortByTitle () {
      this.items.sort(function (a, b) {
        if (a.title < b.title) return -1
        if (a.title > b.title) return 1
        return 0
      })
      for (let i = 0; i < this.items.length; i++) {
        this.items[i].order = i + 1
      }
      this.saveNewOrder()
    },
    async saveNewOrder () {
      let orderedQuestions = []
      for (let i = 0; i < this.items.length; i++) {
        orderedQuestions.push(this.items[i].question_id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedQuestions.length; i++) {
        if (this.currentOrderedQuestions[i] !== this.items[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/order`, { ordered_questions: orderedQuestions })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.items.length; i++) {
            this.items[i].order = i + 1
          }
          this.currentOrderedQuestions = this.items
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.formative = data.formative
        this.submissionsExist = data.submissions_exist
        this.assessmentType = data.assessment_type
        this.betaAssignmentsExist = data.beta_assignments_exist
        this.isBetaAssignment = data.is_beta_assignment
        this.isAlphaCourse = data.is_alpha_course
        this.isCommonsCourse = data.is_commons_course
        this.isQuestionWeight = data.is_question_weight
        this.h5pQuestionsWithAnonymousUsers = data.h5p_questions_exist && data.course_has_anonymous_users
        this.h5pQuestionsWithRealTimeAndMultipleAttempts = data.h5p_questions_exist && data.real_time_with_multiple_attempts
        this.items = data.rows
        let hasNonH5P
        for (let i = 0; i < this.items.length; i++) {
          this.items[i].migrationMessage = ''
          if (this.items[i].submission !== 'h5p') {
            hasNonH5P = true
          }
          if (this.assessmentType !== 'delayed' && !this.items[i].auto_graded_only) {
            this.openEndedQuestionsInRealTime += this.items[i].order + ', '
          }
          this.currentOrderedQuestions.push(this.items[i].question_id)
        }

        console.log(data)
        this.updateH5pNonAdaptQuestionsMessage()
        this.updateOpenEndedInRealTimeMessage()
        this.updateLearningTreeInNonLearningTreeMessage()
        this.updateNonLearningTreeInLearningTreeMessage()

        if (this.assessment_type === 'clicker' && hasNonH5P) {
          this.$bvModal.show('modal-non-h5p')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
