<template>
  <div>
    <div v-if="questionToView.question_id">
      <b-modal
        :id="`modal-view-question-${typeOfRemixer}`"
        :ref="`modal-view-question-${typeOfRemixer}`"
        :title="questionToView.title"
        size="lg"
      >
        <div>
          <iframe v-show="questionToView.non_technology"
                  :key="`non-technology-iframe-${questionToView.id}`"
                  v-resize="{ log: false, checkOrigin: false }"
                  width="100%"
                  :src="questionToView.non_technology_iframe_src"
                  frameborder="0"
          />
        </div>

        <div v-if="questionToView.technology_iframe && showQuestion">
          <iframe
            :key="`technology-iframe-${questionToView.id}`"
            v-resize="{ log: false, checkOrigin: false }"
            width="100%"
            :src="questionToView.technology_iframe_src"
            frameborder="0"
          />
        </div>
        <template #modal-footer>
          <b-button
            v-show="viewQuestionAction==='add'"
            size="sm"
            class="float-right"
            variant="primary"
            @click="addQuestionToAssignmentFromViewQuestion(questionToView.id)"
          >
            Add Question
          </b-button>
          <b-button
            v-show="viewQuestionAction==='remove'"
            size="sm"
            class="float-right"
            variant="danger"
            @click="isRemixerTab = true; openRemoveQuestionModal(questionToView)"
          >
            Remove Question
          </b-button>
        </template>
      </b-modal>
    </div>
    <b-container>
      <div v-if="typeOfRemixer==='assignment-remixer'">
        <b-form-group
          id="school"
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="School"
        >
          <template v-slot:label>
            School
            <span id="school_tooltip">
              <b-icon class="text-muted" icon="question-circle"/></span>
          </template>
          <b-tooltip target="school_tooltip"
                     delay="250"
          >
            ADAPT keeps a comprehensive list of colleges and universities, using the school's full name. So,
            to find UC-Davis, you
            can start typing University of California-Los Angeles. In general, any word within your school's
            name will lead you to your school. If you still can't
            find it, then please contact us.
          </b-tooltip>
          <b-form-row>
            <b-col lg="8">
              <vue-bootstrap-typeahead
                ref="queryTypeaheadSchools"
                v-model="school"
                :data="schools"
                placeholder="Any School With Public Courses"
                @hit="getInstructorsWithPublicCourses()"
              />
            </b-col>

            <b-col/>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="instructor"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Instructor"
          label-for="Instructor"
        >
          <b-form-row>
            <b-col lg="8">
              <vue-bootstrap-typeahead
                ref="instructorTypeahead"
                v-model="instructor"
                placeholder="Any Instructor"
                :serializer="instructorsOptions => instructorsOptions.text"
                :data="instructorsOptions"
                :disabled="instructorsOptions.length === 1"
                @hit="getPublicCourses(instructor)"
              />
            </b-col>
          </b-form-row>
        </b-form-group>

        <b-form-group
          id="course"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Course"
          label-for="Course"
        >
          <b-form-row>
            <b-col lg="8">
              <vue-bootstrap-typeahead
                v-if="textBasedCourseSearchType"
                ref="publicCourseTypeahead"
                :key="publicCoursesKey"
                v-model="publicCourse"
                placeholder="Select A Course"
                :serializer="publicCoursesOptions => publicCoursesOptions.text"
                :data="publicCoursesOptions"
                :disabled="publicCoursesOptions.length === 1"
                @hit="getPublicCourseAssignments(publicCourse)"
              />

              <b-form-select v-if="!textBasedCourseSearchType"
                             id="Course"
                             v-model="publicCourseId"
                             :options="publicCoursesOptions"
                             :disabled="publicCoursesOptions.length === 1"
                             @change="getPublicCourseNameById($event);getPublicCourseAssignments(publicCourse)"
              />
            </b-col>
            <b-col>
              <b-button v-if="!textBasedCourseSearchType"
                        variant="outline-primary"
                        size="sm"
                        @click="textBasedCourseSearchType = !textBasedCourseSearchType"
              >
                Text-based Search
              </b-button>
              <b-button v-if="textBasedCourseSearchType"
                        variant="outline-info"
                        size="sm"
                        @click="textBasedCourseSearchType = !textBasedCourseSearchType;publicCourse=null"
              >
                Show All
              </b-button>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="assignment"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Assignment"
          label-for="assignment"
        >
          <b-form-row>
            <b-col lg="8">
              <b-form-select id="assignment"
                             v-model="publicCourseAssignment"
                             :options="publicCourseAssignmentsOptions"
                             :disabled="publicCourseAssignmentsOptions.length === 1"
                             @change="getPublicCourseAssignmentQuestions($event)"
              />
            </b-col>
          </b-form-row>
        </b-form-group>
      </div>
      <b-form-select id="collections"
                     v-model="collection"
                     :options="collectionsOptions"
                     @change="getCollection($event)"
      />
      <b-container>
        <b-row>
          <b-col>
            <b-card class="settings-card" style="width:400px">
              <ul class="nav nav-pills">
                <li v-for="(assignment, index) in assignments" :key="`assignment-${assignment.id}`" class="nav-item">
                  <span class="hover-underline nav-link"
                        style="font-size:12px"
                        @click="chosenAssignmentId = assignment.id;updateQuestions(assignment.id)"
                  >{{ assignment.name }} {{ index }}</span>
                </li>
              </ul>
            </b-card>
          </b-col>
          <b-col>
            <b-button variant="info"
                      :class="{ 'disabled': !selectedQuestionIds.length}"
                      :aria-disabled="!selectedQuestionIds.length"
                      size="sm"
                      @click="!selectedQuestionIds.length ? '' : viewSelectedQuestions()"
            >
              View Selected
            </b-button>
            <b-button variant="primary"
                      :class="{ 'disabled': !selectedQuestionIds.length}"
                      :aria-disabled="!selectedQuestionIds.length"
                      size="sm"
                      @click="!selectedQuestionIds.length ? '' : convertQuestionIdsToAddToQuestionsToAdd(selectedQuestionIds)"
            >
              Add Selected
            </b-button>

            <b-table
              aria-label="Questions"
              striped
              hover
              responsive
              :no-border-collapse="true"
              :key="`assignment-questions-key-${assignmentQuestionsKey}`"
              :items="assignmentQuestions"
              :fields="assignmentQuestionsFields"
            >
              <template #head(title)="data">
                <input id="select_all" type="checkbox" @click="selectAll"> Title
              </template>
              <template v-slot:cell(title)="data">
                <input v-model="selectedQuestionIds" type="checkbox" :value="data.item.question_id" class="selected-question-id">
                <span :class="{'text-danger' : data.item.in_assignment && data.item.in_assignment !== assignmentName}">
                  {{ data.item.title }}
                </span>
                <span v-if="data.item.in_assignment && data.item.in_assignment !== assignmentName">
                  <QuestionCircleTooltip :id="`in-assignment-tooltip-${data.item.question_id}`"/>
                  <b-tooltip :target="`in-assignment-tooltip-${data.item.question_id}`"
                             delay="250"
                             triggers="hover focus"
                  >
                    This question is in your assignment "{{ data.item.in_assignment }}".
                  </b-tooltip>
                </span>
              </template>
              <template v-slot:cell(actions)="data">
                <b-tooltip :target="getTooltipTarget('view',data.item.id)"
                           delay="500"
                           triggers="hover focus"
                >
                  View the question
                </b-tooltip>
                <a :id="getTooltipTarget('view',data.item.id)"
                   href=""
                   class="pr-1"
                   @click.prevent="selectedQuestionIds=[data.item.id];viewSelectedQuestions()"
                >
                  <b-icon class="text-muted"
                          icon="eye"
                          :aria-label="`View ${data.item.title}`"
                  />
                </a>
                <b-tooltip :target="getTooltipTarget('delete',data.item.id)"
                           delay="500"
                           triggers="hover focus"
                >
                  Remove the question
                </b-tooltip>
                <span v-if="data.item.in_assignment === data.item.title">
                  <a :id="getTooltipTarget('delete',data.item.id)"
                     href=""
                     class="pr-1"
                     @click.prevent="initRemoveQuestions([data.item.id])"
                  >
                    <b-icon class="text-muted"
                            icon="trash"
                            :aria-label="`Delete ${data.item.title}`"
                    />
                  </a>
                </span>
                <span v-if="data.item.in_assignment !== assignmentName">
                  <b-button :id="getTooltipTarget('add',data.item.id)"
                            variant="success"
                            class="p-1"
                            @click.prevent="addQuestions([data.item])"
                  ><span :aria-label="`Add ${data.item.title} to the assignment`">+</span>
                  </b-button>
                </span>
                <span v-if="data.item.in_assignment === assignmentName">
                  <b-button :id="getTooltipTarget('add',data.item.id)"
                            variant="danger"
                            class="p-1"
                            @click.prevent="isRemixerTab = true; openRemoveQuestionModal(data.item)"
                  ><span :aria-label="`Remove ${data.item.title} from the assignment`">-</span>
                  </b-button>
                </span>
                <span>
                  <a :id="getTooltipTarget('save',data.item.id)"
                     href=""
                     class="pr-1"
                     @click.prevent="addQuestions([data.item])"
                  >
                    <font-awesome-icon class="text-muted"
                                       :icon="heartIcon"
                                       :aria-label="`Add ${data.item.title} to saved questions`"
                    />
                  </a>
                </span>
              </template>
            </b-table>
          </b-col>
        </b-row>
      </b-container>
      <div v-if="typeOfRemixer === 'saved-questions'">
        <p>
          Questions that have been saved after the visiting the Commons can be added here simply by dragging them from
          your Saved Questions list to your Chosen Questions list.
        </p>
      </div>
      <b-row>
        <b-col>
          <b-row class="mb-2">
            <b-col>
              <h5>
                {{ typeOfRemixer === 'saved-questions' ? 'Saved' : 'Possible' }} Questions
              </h5>
            </b-col>
            <b-col class="text-right">
              <a href="" @click.prevent="addAllQuestions()">
                <span><b-icon icon="plus-circle"/> Add all questions</span>
              </a>
            </b-col>
          </b-row>
          <table class="table dragArea table-striped">
            <thead>
            <tr>
              <th>Title</th>
              <th>Submission</th>
            </tr>
            </thead>
            <draggable v-model="publicCourseAssignmentQuestions"
                       :group="'remixerQuestions'"
                       :element="'tbody'"
                       :empty-insert-threshold="100"
                       :move="checkMove"
                       @end="updateAssignmentWithChosenQuestions('single')"
            >
              <tr v-for="(question, index) in publicCourseAssignmentQuestions"
                  :key="`possible-question-${index}`"
                  class="dragArea"
              >
                <td class="dragArea">
                  <a href="" @click.stop.prevent="viewQuestion(question.question_id,'add')">
                    <span :class="{'text-danger' : question.in_assignment}"
                    > {{ question.title ? question.title : 'No title' }}</span>
                  </a>
                  <span v-if="question.in_assignment">
                    <QuestionCircleTooltip :id="`in-assignment-tooltip-${question.question_id}`"/>
                    <b-tooltip :target="`in-assignment-tooltip-${question.question_id}`"
                               delay="250"
                               triggers="hover focus"
                    >
                      This question is in your assignment "{{ question.in_assignment }}".
                    </b-tooltip>
                  </span>

                  <a href=""
                     @click.prevent="removeQuestionFromSavedQuestions(question)"
                  >
                    <b-icon v-if="typeOfRemixer === 'saved-questions'"
                            icon="trash"
                            class="text-muted"
                            :aria-label="`Remove ${question.title} from saved questions`"
                    />
                  </a>
                </td>
                <td class="dragArea">
                  {{ question.submission }}
                </td>
              </tr>
            </draggable>
          </table>
        </b-col>
        <b-col>
          <h5 class="pb-2">
            Chosen Questions
          </h5>
          <b-alert
            :show="showAlreadyInAssignmentMessage"
            dismissible
            @dismissed="showAlreadyInAssignmentMessage = false"
          >
            {{ questionToMove ? questionToMove.title : '' }} is already in this assignment.
          </b-alert>
          <table class="table dragArea table-striped">
            <thead>
            <tr>
              <th>Order</th>
              <th>Title</th>
              <th>Submission</th>
            </tr>
            </thead>
            <draggable v-model="chosenPublicCourseAssignmentQuestions"
                       :options="{group:'remixerQuestions'}"
                       :element="'tbody'"
                       :empty-insert-threshold="100"
                       @end="updateAssignmentWithChosenQuestions('single')"
            >
              <tr v-for="(question, index) in chosenPublicCourseAssignmentQuestions"
                  :key="`chosen-question-${index}`"
                  class="dragArea"
              >
                <td class="dragArea">
                  {{ index + 1 }}
                </td>
                <td class="dragArea">
                  <a href="" @click.stop.prevent="viewQuestion(question.question_id,'remove')">
                    {{ question.title ? question.title : 'No title' }}
                  </a>
                  <a href=""
                     @click.prevent="isRemixerTab = true;openRemoveQuestionModal(question)"
                  >
                    <b-icon icon="trash"
                            class="text-muted"
                            :aria-label="`Remove ${question.title} from the assignment`"
                    />
                  </a>
                </td>
                <td class="dragArea">
                  {{ question.submission }}
                </td>
              </tr>
            </draggable>
          </table>
        </b-col>
      </b-row>
    </b-container>
  </div>
</template>

<script>
import draggable from 'vuedraggable'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import { faHeart } from '@fortawesome/free-regular-svg-icons'
import axios from 'axios'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  name: 'Remixer',
  components: {
    draggable,
    VueBootstrapTypeahead,
    FontAwesomeIcon
  },
  props: {
    assignmentId: { type: Number, default: 0 },
    assignmentName: { type: String, default: '' },
    getQuestionWarningInfo: {
      type: Function,
      default: function () {
      }
    },
    typeOfRemixer: {
      type: String,
      default: 'assignment-remixer'
    },
    setQuestionToRemove: {
      type: Function,
      default: function () {
      }
    }
  },
  data: () => ({
    assignmentQuestionsKey: 0,
    chosenAssignmentId: 0,
    heartIcon: faHeart,
    selectedQuestionIds: [],
    assignmentQuestionsFields: [
      'title',
      'id',
      {
        key: 'actions',
        label: 'Actions'
      }
    ],
    assignmentQuestions: [],
    collection: null,
    collectionsOptions: [],
    assignments: [],
    questionToMove: {},
    showAlreadyInAssignmentMessage: false,
    isRemixerTab: false,
    viewQuestionAction: '',
    publicCourseAssignmentsOptions: [],
    publicCourse: null,
    publicCourseAssignment: null,
    textBasedCourseSearchType: true,
    showQuestion: false,
    questionToView: {},
    publicCourseId: null,
    publicCoursesKey: 0,
    publicCoursesOptions: [],
    originalChosenPublicCourseAssignmentQuestions: [],
    publicCourseAssignmentQuestions: [],
    school: '',
    schools: [],
    chosenPublicCourseAssignmentQuestions: [],
    instructor: null,
    instructorsOptions: []
  }),
  watch: {
    school: function (newSchool, oldSchool) {
      if (newSchool === '' && this.$refs.instructorTypeahead) {
        this.$refs.instructorTypeahead.inputValue = this.instructor = ''
        this.publicCourseAssignment = null
        this.getInstructorsWithPublicCourses()
      }
    },
    publicCourse: function (newCourse, oldCourse) {
      if (newCourse === '') {
        this.publicCourseAssignmentsOptions = this.getDefaultPublicCourseAssignmentsOptions()
        this.publicCourseAssignment = null
      }
    }
  },
  mounted () {
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.getSchoolsWithPublicCourses()
    this.getInstructorsWithPublicCourses()
    this.getPublicCourses()
    this.getCollections()
    this.getCollection(62)
    this.getCurrentAssignmentQuestions()
    this.getPublicCourseAssignmentQuestions()
    this.getQuestionWarningInfo()
  },
  methods: {
    saveQuestions () {
      alert('to do save questions')

    },
    convertQuestionIdsToAddToQuestionsToAdd (questionIdsToAdd) {
      console.log(questionIdsToAdd)
      let questionsToAdd = []
      for (let i = 0; i < this.assignmentQuestions.length; i++) {
        let question = this.assignmentQuestions[i]
        console.log(question)
        console.log(question.question_id + ' ' + questionIdsToAdd.includes(question.question_id))
        if (questionIdsToAdd.includes(question.question_id)) {
          questionsToAdd.push(question)
        }
      }
      this.addQuestions(questionsToAdd)
    },
    async addQuestions (questionsToAdd) {
      for (let i = 0; i < questionsToAdd.length; i++) {
        try {
          const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/remix-assignment-with-chosen-questions`,
            {
              'chosen_questions': questionsToAdd,
              'type_of_remixer': 'assignment-remixer'

            })
          if (data.type === 'error') {
            this.$noty.error(data.message, {
              timeout: 8000
            })
          }
          if (data.type === 'success') {
            this.assignmentQuestions.find(question => question.question_id === questionsToAdd[i].question_id).in_assignment = this.assignmentName
            this.selectedQuestionIds = []
          }
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      await this.getQuestionWarningInfo()

      if (this.typeOfRemixer === 'saved-questions') {
        this.publicCourseAssignmentQuestions = this.originalChosenPublicCourseAssignmentQuestions
      }

    },
    viewSelectedQuestions () {
      alert('view selected questions')
    },
    initRemoveQuestions () {

    },
    selectAll () {
      this.selectedQuestionIds = []
      let checkboxes = document.getElementsByClassName('selected-question-id')
      if (document.getElementById('select_all').checked) {
        for (let checkbox of checkboxes) {
          this.selectedQuestionIds.push(parseInt(checkbox.value))
        }
      }
    },
    async updateQuestions () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.chosenAssignmentId}/questions/with-course-level-usage-info/${this.$route.params.assignmentId}`)
        this.assignmentQuestions = data.assignment_questions
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCollection (collection) {
      try {
        const { data } = await axios.get(`/api/assignments/courses/public/${collection}/names`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assignments = data.assignments
        if (!this.assignments.length) {
          alert('no assignments')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async removeQuestionFromSavedQuestions (questionToRemove) {
      try {
        const { data } = await axios.delete(`/api/saved-questions/${questionToRemove.question_id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        this.publicCourseAssignmentQuestions = this.publicCourseAssignmentQuestions.filter(question => question.question_id !== questionToRemove.question_id)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCurrentAssignmentQuestions () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/with-course-level-usage-info/${this.$route.params.assignmentId}`)
        this.chosenPublicCourseAssignmentQuestions = data.assignment_questions
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getPublicCourseNameById (courseId) {
      this.publicCourse = this.publicCoursesOptions.find(course => course.value === courseId).text
    },
    async getSchoolsWithPublicCourses () {
      try {
        const { data } = await axios.get(`/api/schools/public-courses`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.schools = data.schools
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    checkMove: function (evt) {
      let questionId = evt.draggedContext.element.question_id
      this.questionToMove = this.chosenPublicCourseAssignmentQuestions.find(question => question.question_id === questionId)
      if (this.questionToMove) {
        this.showAlreadyInAssignmentMessage = true
        return false
      }
      return true
    },
    async removeQuestionFromRemixedAssignment (questionId, chosenAssignmentId) {
      this.$bvModal.hide('modal-remove-question')
      this.$bvModal.hide(`modal-view-question-${this.typeOfRemixer}`)
      this.chosenAssignmentId = chosenAssignmentId
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${questionId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          if (this.typeOfRemixer === 'saved-questions') {
            console.log('there')
            this.publicCourseAssignmentQuestions = this.originalChosenPublicCourseAssignmentQuestions
          } else {
            this.assignmentQuestions = []
            this.assignmentQuestionsKey++
            alert(this.assignmentQuestions.length)
            //await this.updateQuestions()
          }
          await this.getQuestionWarningInfo()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    async updateAssignmentWithChosenQuestions (type) {
      let success = true
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/remix-assignment-with-chosen-questions`,
          {
            'chosen_questions': this.chosenPublicCourseAssignmentQuestions,
            'type': type,
            'type_of_remixer': this.typeOfRemixer

          })
        if (data.type === 'error') {
          this.$noty.error(data.message, {
            timeout: 8000
          })
          await this.getCurrentAssignmentQuestions()
          await this.getPublicCourseAssignmentQuestions(this.publicCourseAssignment)
          success = false
        }
        await this.getQuestionWarningInfo()
        console.log(this.chosenPublicCourseAssignmentQuestions)
        let questionsNeedingInAssignment = this.chosenPublicCourseAssignmentQuestions.filter(question => !question.in_assignment)

        for (let i = 0; i < questionsNeedingInAssignment.length; i++) {
          questionsNeedingInAssignment[i].in_assignment = this.assignmentName
        }
      } catch (error) {
        this.$noty.error(error.message)
        success = false
      }

      this.$bvModal.hide(`modal-view-question-${this.typeOfRemixer}`)
      if (this.typeOfRemixer === 'saved-questions') {
        this.publicCourseAssignmentQuestions = this.originalChosenPublicCourseAssignmentQuestions
      }
      return success
    },
    async addAllQuestions () {
      for (let i = 0; i < this.publicCourseAssignmentQuestions.length; i++) {
        let question = this.publicCourseAssignmentQuestions[i]

        if (!this.chosenPublicCourseAssignmentQuestions.find(chosenQuestion => chosenQuestion.question_id === question.question_id)) {
          this.chosenPublicCourseAssignmentQuestions.push(question)
        }
      }
      this.publicCourseAssignmentQuestions = []
      await this.updateAssignmentWithChosenQuestions('all')
      await this.getQuestionWarningInfo()
    },
    openRemoveQuestionModal (questionToRemove) {
      this.setQuestionToRemove(questionToRemove, this.typeOfRemixer, this.chosenAssignmentId)
    },
    async getPublicCourseAssignmentQuestions (assignmentId) {
      if (this.typeOfRemixer === 'assignment-remixer' && !assignmentId) {
        return false
      }
      try {
        const { data } = this.typeOfRemixer === 'assignment-remixer'
          ? await axios.get(`/api/assignments/${assignmentId}/questions/with-course-level-usage-info/${this.$route.params.assignmentId}`)
          : await axios.get(`/api/saved-questions/with-course-level-usage-info/${this.$route.params.assignmentId}`)
        console.log(data)
        let chosenQuestionIds = []
        for (let i = 0; i < this.chosenPublicCourseAssignmentQuestions.length; i++) {
          chosenQuestionIds.push(this.chosenPublicCourseAssignmentQuestions[i].question_id)
        }
        if (this.typeOfRemixer === 'saved-questions') {
          this.publicCourseAssignmentQuestions = data.saved_questions
        } else {
          this.publicCourseAssignmentQuestions = data.assignment_questions.filter(question => !chosenQuestionIds.includes(question.question_id))
        }
        this.originalChosenPublicCourseAssignmentQuestions = this.publicCourseAssignmentQuestions
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getPublicCourseAssignments (courseName) {
      let course = this.publicCoursesOptions.find(course => course.text === courseName)
      this.publicCourseAssignment = null
      this.publicCourseAssignmentsOptions = this.getDefaultPublicCourseAssignmentsOptions()
      if (course.value) {
        try {
          const { data } = await axios.get(`/api/assignments/courses/public/${course.value}/names`)
          if (data.assignments) {
            for (let i = 0; i < data.assignments.length; i++) {
              let assignment = { value: data.assignments[i].id, text: data.assignments[i].name }
              this.publicCourseAssignmentsOptions.push(assignment)
            }
          }
        } catch (error) {
          this.$noty.error(error.message)
        }
      } else {
        this.publicCourseAssignment = null
        this.publicCourseAssignmentsOptions = [{ value: null, text: 'There are no assignments available' }]
      }
    },
    getDefaultPublicCoursesOptions () {
      return [{ value: null, text: 'Select A Course' }]
    },
    getDefaultPublicCourseAssignmentsOptions () {
      return [{ value: null, text: 'Please select an assignment' }]
    },
    async viewQuestion (questionId, action) {
      this.showQuestion = false
      this.viewQuestionAction = action
      try {
        console.log('modal-view-question-' + this.typeOfRemixer)
        this.loadingQuestion = true
        const { data } = await axios.get(`/api/questions/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.loadingQuestion = false
          return false
        }
        this.questionToView = data.question
        this.questionToView.question_id = data.question.id

        if (action === 'add' && this.publicCourseAssignmentQuestions.find(question => parseInt(question.question_id) === parseInt(questionId)).in_assignment === this.assignmentName) {
          this.$noty.info(`${this.questionToView.title} is already in this assignment.`)
          return false
        }
        this.$nextTick(() => {
          this.$bvModal.show(`modal-view-question-${this.typeOfRemixer}`)
          this.showQuestion = true
        })
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.loadingQuestion = false
    },
    async getInstructorsWithPublicCourses () {
      if (this.$refs.instructorTypeahead) {
        this.$refs.instructorTypeahead.inputValue = this.instructor = ''
      }
      if (this.$refs.publicCourseTypeahead) {
        this.$refs.publicCourseTypeahead.inputValue = this.publicCourse = ''
      }
      this.publicCourseAssignmentsOptions = this.getDefaultPublicCourseAssignmentsOptions()
      try {
        const { data } = await axios.post('/api/user/instructors-with-public-courses',
          { 'name': this.school })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        this.instructorsOptions = []
        if (data.instructors.length) {
          for (let i = 0; i < data.instructors.length; i++) {
            let instructor = { 'text': data.instructors[i].name, 'value': data.instructors[i].user_id }
            this.instructorsOptions.push(instructor)
          }
        } else {
          this.$noty.info('There are no instructors associated with that school.')
          this.instructor = null
          this.publicCourse = null
          this.publicCoursesOptions = this.defaultPublicCoursesOptions
          this.publicCourseAssignmentsOptions = this.getDefaultPublicCourseAssignmentsOptions()
          this.publicCourseAssignment = null
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async addQuestionToAssignmentFromViewQuestion (questionId) {
      let chosenQuestion = this.publicCourseAssignmentQuestions.find(question => question.question_id === questionId)
      this.publicCourseAssignmentQuestions = this.publicCourseAssignmentQuestions.filter(question => question.question_id !== questionId)
      this.chosenPublicCourseAssignmentQuestions.push(chosenQuestion)
      let success = await this.updateAssignmentWithChosenQuestions('single')
      if (success) {
        this.$noty.success(`${chosenQuestion.title} has been added to the assignment.`)
      }
    },
    async getCollections () {
      this.collectionsOptions = [{ value: null, text: 'Choose A Collection' }, { value: 0, text: 'Saved Questions' }]
      this.publicCourse = null
      try {
        const { data } = await axios.get(`/api/courses/public`)
        if (data.public_courses) {
          for (let i = 0; i < data.public_courses.length; i++) {
            let publicCourse = { value: data.public_courses[i].id, text: data.public_courses[i].name }
            this.collectionsOptions.push(publicCourse)
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getPublicCourses (instructorName) {
      this.publicCoursesOptions = this.getDefaultPublicCoursesOptions()
      console.log('getting public courses')
      let instructor = this.instructorsOptions.find(instructor => instructor.text === instructorName)
      this.publicCourse = null
      try {
        const { data } = instructor
          ? await axios.get(`/api/courses/public/${instructor.value}`)
          : await axios.get(`/api/courses/public`)
        if (data.public_courses) {
          for (let i = 0; i < data.public_courses.length; i++) {
            let publicCourse = { value: data.public_courses[i].id, text: data.public_courses[i].name }
            this.publicCoursesOptions.push(publicCourse)
          }
          this.publicCourseAssignmentsOptions = this.getDefaultPublicCourseAssignmentsOptions()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.publicCoursesKey = this.publicCoursesKey + 1
    }

  }
}
</script>

<style scoped>

</style>
