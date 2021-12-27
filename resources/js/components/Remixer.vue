<template>
  <div>
    <b-modal
      :id="'modal-view-question-' + typeOfRemixer"
      title="View Question"
      size="lg"
    >
      <div>
        <iframe v-show="questionToView.non_technology"
                :key="`non-technology-iframe-${questionToView.id}`"
                v-resize="{ log: true, checkOrigin: false }"
                width="100%"
                :src="questionToView.non_technology_iframe_src"
                frameborder="0"
        />
      </div>

      <div v-if="questionToView.technology_iframe && showQuestion">
        <iframe
          :key="`technology-iframe-${questionToView.id}`"
          v-resize="{ log: true, checkOrigin: false }"
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
    <b-container>
      <div v-if="typeOfRemixer==='assignment-remixer'">
        <b-form-group
          id="school"
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="School"
        >
          <template slot="label">
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

              <b-form-select id="Course"
                             v-if="!textBasedCourseSearchType"
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
                  :key="question.id"
                  class="dragArea"
              >
                <td class="dragArea">
                  <a href="" @click.stop.prevent="viewQuestion(question.question_id,'add')">
                    {{ question.title ? question.title : 'No title' }}
                  </a>
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
                  :key="question.id"
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
                     @click="isRemixerTab = true; openRemoveQuestionModal(question)"
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
import axios from 'axios'

export default {
  name: 'Remixer',
  components: { draggable, VueBootstrapTypeahead },
  props: {
    assignmentId: { type: Number, default: 0 },
    getQuestionWarningInfo: {
      type: Function,
      default: function () {
      }
    },
    typeOfRemixer: {
      type: String,
      default: ''
    },
    setQuestionToRemove: {
      type: Function,
      default: function () {
      }
    }
  },
  data: () => ({
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
    this.getSchoolsWithPublicCourses()
    this.getInstructorsWithPublicCourses()
    this.getPublicCourses()
    this.getCurrentAssignmentQuestions()
    this.getPublicCourseAssignmentQuestions()
    this.getQuestionWarningInfo()
  },
  methods: {
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
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/titles`)
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
      if (this.chosenPublicCourseAssignmentQuestions.find(question => question.question_id === questionId)) {
        this.$noty.info('That assessment is already in your assignment.')
        return false
      }
      return true
    },
    async removeQuestionFromRemixedAssignment (questionId) {
      this.$bvModal.hide('modal-remove-question')
      this.$bvModal.hide('modal-view-question-' + this.typeOfRemixer)
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${questionId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          let questionFromPublicCourseAssignmentQuestions = this.originalChosenPublicCourseAssignmentQuestions.find(question => question.question_id === questionId)
          if (questionFromPublicCourseAssignmentQuestions) {
            this.publicCourseAssignmentQuestions.push(questionFromPublicCourseAssignmentQuestions)
          }
          this.chosenPublicCourseAssignmentQuestions = this.chosenPublicCourseAssignmentQuestions.filter(question => question.question_id !== questionId)
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
      } catch (error) {
        this.$noty.error(error.message)
        success = false
      }

      this.$bvModal.hide('modal-view-question-' + this.typeOfRemixer)
      return success
    },
    async addAllQuestions () {
      for (let i = 0; i < this.publicCourseAssignmentQuestions.length; i++) {
        let question = this.publicCourseAssignmentQuestions[i]
        this.chosenPublicCourseAssignmentQuestions.push(question)
        console.log(i)
      }
      this.publicCourseAssignmentQuestions = []
      await this.updateAssignmentWithChosenQuestions('all')
      await this.getQuestionWarningInfo()
    },
    openRemoveQuestionModal (questionToRemove) {
      this.setQuestionToRemove(questionToRemove, this.typeOfRemixer)
    },
    async getPublicCourseAssignmentQuestions (assignmentId) {
      if (this.typeOfRemixer === 'assignment-remixer' && !assignmentId) {
        return false
      }
      try {
        const { data } = this.typeOfRemixer === 'assignment-remixer'
          ? await axios.get(`/api/assignments/${assignmentId}/questions/titles`)
          : await axios.get('/api/saved-questions')
        let chosenQuestionIds = []
        for (let i = 0; i < this.chosenPublicCourseAssignmentQuestions.length; i++) {
          chosenQuestionIds.push(this.chosenPublicCourseAssignmentQuestions[i].question_id)
        }
        this.publicCourseAssignmentQuestions = data.assignment_questions.filter(question => !chosenQuestionIds.includes(question.question_id))
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
        this.$bvModal.show('modal-view-question-' + this.typeOfRemixer)
        this.loadingQuestion = true
        const { data } = await axios.get(`/api/questions/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.loadingQuestion = false
          return false
        }
        this.questionToView = data.question
        this.questionToView.question_id = data.question.id
        this.showQuestion = true
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
        this.$noty.success('The question has been added to the assignment.')
      }
    },

    async getPublicCourses (instructorName) {
      this.publicCoursesOptions = this.getDefaultPublicCoursesOptions()
      console.log('getting public coursess')
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
