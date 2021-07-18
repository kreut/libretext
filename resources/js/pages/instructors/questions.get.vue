<template>
  <div>
    <b-modal
      id="modal-remove-question"
      ref="modal"
      title="Confirm Remove Question"
    >
      <RemoveQuestion :beta-assignments-exist="betaAssignmentsExist"/>
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
      id="modal-upload-file"
      ref="solutionFileInput"
      title="Upload File"
      ok-title="Submit"
      size="lg"
      @ok="handleOk"
    >
      <b-form ref="form">
        <p>Accepted file types are: {{ getAcceptedFileTypes() }}.</p>
        <b-form-file
          ref="solutionFileInput"
          v-model="uploadFileForm.solutionFile"
          placeholder="Choose a file or drop it here..."
          drop-placeholder="Drop file here..."
          :accept="getAcceptedFileTypes()"
        />
        <div v-if="uploading">
          <b-spinner small type="grow"/>
          Uploading file...
        </div>
        <input type="hidden" class="form-control is-invalid">
        <div class="help-block invalid-feedback">
          {{ uploadFileForm.errors.get('solutionFile') }}
        </div>
      </b-form>
    </b-modal>
    <b-modal
      id="modal-non-h5p"
      ref="h5pModal"
      title="Non-H5P assessments in clicker assignment"
    >
      <b-alert :show="true" variant="danger">
        <span class="font-weight-bold font-italic">
          {{
            h5pText()
          }}
        </span>
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-non-h5p')">
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-view-question"
      ref="modalViewQuestion"
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
          :src="questionToView.technology_iframe"
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
          @click="removeQuestionFromRemixedAssignment(questionToView.id)"
        >
          Remove Question
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
        <PageTitle :title="title"/>
        <b-container>
          <AssessmentTypeWarnings :assessment-type="assessmentType"
                                  :open-ended-questions-in-real-time="openEndedQuestionsInRealTime"
                                  :learning-tree-questions-in-non-learning-tree="learningTreeQuestionsInNonLearningTree"
                                  :non-learning-tree-questions="nonLearningTreeQuestions"
          />
          <b-row align-h="end">
            <b-button variant="primary" size="sm" @click="getStudentView(assignmentId)">
              View as Student
            </b-button>
          </b-row>
        </b-container>
        <hr>
        <div>
          <b-tabs content-class="mt-3">
            <b-tab title="Assignment Remixer" active @click="showQuestions = false;getCurrentAssignmentQuestions()">
              <b-container>
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
                    Adapt keeps a comprehensive list of colleges and universities, using the school's full name. So,
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
                  label-for="Assignment"
                >
                  <b-form-row>
                    <b-col lg="8">
                      <b-form-select v-model="publicCourseAssignment"
                                     :options="publicCourseAssignmentsOptions"
                                     :disabled="publicCourseAssignmentsOptions.length === 1"
                                     @change="getPublicCourseAssignmentQuestions($event)"
                      />
                    </b-col>
                  </b-form-row>
                </b-form-group>
                <b-row>
                  <b-col>
                    <b-row class="mb-2">
                      <b-col>
                        <h5 class="font-italic">
                          Possible Questions
                        </h5>
                      </b-col>
                      <b-col class="text-right">
                        <a href="" @click.prevent="addAllQuestions()">
                          <span class="font-italic"><b-icon icon="plus-circle"/> Add all questions</span>
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
                          </td>
                          <td class="dragArea">
                            {{ question.submission }}
                          </td>
                        </tr>
                      </draggable>
                    </table>
                  </b-col>
                  <b-col>
                    <h5 class="font-italic">
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
                            <b-icon icon="trash" @click="removeQuestionFromRemixedAssignment(question.question_id)"/>
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
            </b-tab>
            <b-tab title="Search Query By Tag">
              <b-col @click="resetDirectImport()">
                <b-card header-html="<span class='font-weight-bold'>Search Query By Tag</span>" class="h-100">
                  <b-card-text>
                    <b-container>
                      <b-row>
                        <b-col class="border-right">
                          <p>
                            Search for query questions by tag which can then be added to your assignment.
                            <b-icon id="search-by-tag-tooltip"
                                    v-b-tooltip.hover
                                    class="text-muted"
                                    icon="question-circle"
                            />
                            <b-tooltip target="search-by-tag-tooltip" triggers="hover">
                              Using the search box you can find query questions by tag.
                              Note that adding multiple tags will result in a search result which matches all of the
                              conditions.
                            </b-tooltip>
                          </p>
                          <div class="col-7 p-0">
                            <vue-bootstrap-typeahead
                              ref="queryTypeahead"
                              v-model="query"
                              :data="tags"
                              placeholder="Enter a tag"
                            />
                          </div>
                          <div class="mt-3 ">
                            <b-button variant="primary" size="sm" class="mr-2" @click="addTag()">
                              Add Tag
                            </b-button>
                            <b-button variant="success" size="sm" class="mr-2" @click="getQuestionsByTags()">
                              <b-spinner v-if="gettingQuestions" small type="grow"/>
                              Get Questions
                            </b-button>
                          </div>
                        </b-col>
                        <b-col>
                          <span class="font-weight-bold font-italic">Chosen Tags:</span>
                          <div v-if="chosenTags.length>0">
                            <ol>
                              <li v-for="chosenTag in chosenTags" :key="chosenTag">
                                <span @click="removeTag(chosenTag)">{{ chosenTag }}
                                  <b-icon icon="trash" variant="danger"/></span>
                              </li>
                            </ol>
                          </div>
                          <div v-else>
                            <span class="text-danger">No tags have been chosen.</span>
                          </div>
                        </b-col>
                      </b-row>
                    </b-container>
                  </b-card-text>
                </b-card>
              </b-col>
            </b-tab>
            <b-tab title="Direct Import By Page Id" @click="showQuestions = false">
              <b-card header-html="<span class='font-weight-bold'>Direct Import By Page Id" class="h-100">
                <b-card-text>
                  <b-container>
                    <b-row>
                      <b-col @click="resetSearchByTag">
                        <p>
                          Perform a direct import of questions directly into your assignment from any library. Please
                          enter
                          your questions using a comma
                          separated list of the form {library}-{page id}.
                        </p>
                        <b-form-group
                          id="default_library"
                          label-cols-sm="5"
                          label-cols-lg="4"
                          label-for="Default Library"
                        >
                          <template slot="label">
                            Default Library
                            <b-icon id="default-library-tooltip"
                                    v-b-tooltip.hover
                                    class="text-muted"
                                    icon="question-circle"
                            />
                            <b-tooltip target="default-library-tooltip" triggers="hover">
                              By setting the default library, you can just enter page ids. As an example, choosing Query
                              as
                              the default
                              library, you can then enter 123,chemistry-927,149 instead of
                              query-123,chemistry-927,query-149.
                            </b-tooltip>
                          </template>
                          <b-form-row>
                            <b-form-select v-model="defaultImportLibrary"
                                           :options="libraryOptions"
                                           @change="setDefaultImportLibrary()"
                            />
                          </b-form-row>
                        </b-form-group>
                      </b-col>
                      <b-col>
                        <b-form-textarea
                          id="textarea"
                          v-model="directImport"
                          placeholder="Example. query-1023, chemistry-2213, chem-2213"
                          rows="4"
                          max-rows="5"
                        />
                        <div class="float-right mt-2">
                          <b-button variant="success" size="sm" class="mr-2" @click="directImportQuestions()">
                            <b-spinner v-if="directImportingQuestions" small type="grow"/>
                            Import Questions
                          </b-button>
                        </div>
                      </b-col>
                    </b-row>
                  </b-container>
                </b-card-text>
              </b-card>
            </b-tab>
          </b-tabs>
        </div>

        <hr>
      </div>
      <div v-if="pageIdsAddedToAssignmentMessage.length>0">
        <b-alert show variant="success">
          <span class="font-weight-bold">{{ pageIdsAddedToAssignmentMessage }}</span>
        </b-alert>
      </div>
      <div v-if="pageIdsNotAddedToAssignmentMessage.length>0">
        <b-alert show variant="info">
          <span class="font-weight-bold">{{ pageIdsNotAddedToAssignmentMessage }}</span>
        </b-alert>
      </div>
      <div v-if="questions.length>0 && showQuestions" class="overflow-auto">
        <b-pagination
          v-model="currentPage"
          :total-rows="questions.length"
          :per-page="perPage"
          align="center"
          first-number
          last-number
          @input="changePage(currentPage)"
        />
      </div>
      <div v-if="showQuestions">
        <b-container>
          <b-row v-if="questions[currentPage-1]">
            <span v-if="!questions[currentPage-1].inAssignment">
              <b-button class="mt-1 mb-2 mr-2"
                        variant="primary"
                        size="sm"
                        @click="addQuestion(questions[currentPage-1])"
              >Add Question
              </b-button>
            </span>
            <span v-if="questions[currentPage-1].inAssignment">
              <b-button class="mt-1 mb-2 mr-2"
                        variant="danger"
                        size="sm"
                        @click="removeQuestion(questions[currentPage-1])"
              >Remove Question
              </b-button>
            </span>
          </b-row>
        </b-container>
        <div>
          <iframe v-if="showQuestions && questions[currentPage-1] && questions[currentPage-1].non_technology"
                  id="non-technology-iframe"
                  allowtransparency="true"
                  frameborder="0"
                  :src="questions[currentPage-1].non_technology_iframe_src"
                  style="width: 1px;min-width: 100%;"
          />
        </div>
        <div v-if="questions[currentPage-1] && questions[currentPage-1].technology_iframe">
          <iframe
            :key="`technology-iframe-${questions[currentPage-1].id}`"
            v-resize="{ log: true, checkOrigin: false }"
            width="100%"
            :src="questions[currentPage-1].technology_iframe"
            frameborder="0"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import { h5pResizer } from '~/helpers/H5PResizer'
import { mapGetters } from 'vuex'
import { submitUploadFile, getAcceptedFileTypes } from '~/helpers/UploadFiles'
import { downloadSolutionFile } from '~/helpers/DownloadFiles'
import draggable from 'vuedraggable'

import Form from 'vform'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import libraries from '~/helpers/Libraries'
import AssessmentTypeWarnings from '~/components/AssessmentTypeWarnings'

import {
  h5pText,
  updateOpenEndedInRealTimeMessage,
  updateLearningTreeInNonLearningTreeMessage,
  updateNonLearningTreeInLearningTreeMessage
} from '~/helpers/AssessmentTypeWarnings'

import RemoveQuestion from '~/components/RemoveQuestion'
export default {
  components: {
    VueBootstrapTypeahead,
    draggable,
    AssessmentTypeWarnings,
    Loading,
    RemoveQuestion
  },
  middleware: 'auth',
  data: () => ({
    openEndedQuestionsInRealTime: '',
    learningTreeQuestionsInNonLearningTree: '',
    nonLearningTreeQuestions: '',
    publicCourseId: null,
    textBasedCourseSearchType: true,
    showQuestion: false,
    publicCoursesKey: 0,
    viewQuestionAction: '',
    school: '',
    schools: [],
    instructor: null,
    instructorsOptions: [],
    assessmentType: '',
    loadingQuestion: false,
    questionToView: {},
    originalChosenPublicCourseAssignmentQuestions: [],
    publicCourseAssignmentQuestions: [],
    chosenPublicCourseAssignmentQuestions: [],
    defaultImportLibrary: null,
    publicCoursesOptions: [],
    publicCourseAssignmentsOptions: [],
    publicCourse: null,
    publicCourseAssignment: null,
    libraryOptions: libraries,
    pageIdsNotAddedToAssignmentMessage: '',
    pageIdsAddedToAssignmentMessage: '',
    directImportingQuestions: false,
    directImport: '',
    questionFilesAllowed: false,
    uploading: false,
    continueLoading: true,
    isLoading: true,
    iframeLoaded: false,
    perPage: 1,
    currentPage: 1,
    query: '',
    tags: [],
    questions: [],
    chosenTags: [],
    question: {},
    showQuestions: false,
    gettingQuestions: false,
    title: '',
    uploadFileForm: new Form({
      questionFile: null,
      assignmentId: null,
      questionId: null
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
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
  created () {
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.downloadSolutionFile = downloadSolutionFile
    this.updateOpenEndedInRealTimeMessage = updateOpenEndedInRealTimeMessage
    this.updateLearningTreeInNonLearningTreeMessage = updateLearningTreeInNonLearningTreeMessage
    this.updateNonLearningTreeInLearningTreeMessage = updateNonLearningTreeInLearningTreeMessage
    this.h5pText = h5pText
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId

    console.log(this.libraries)
    for (let i = 1; i < this.libraryOptions.length; i++) {
      let library = this.libraryOptions[i]
      this.libraryOptions[i].text = `${library.text} (${library.value})`
    }
    this.getSchoolsWithPublicCourses()
    this.getInstructorsWithPublicCourses()
    this.getPublicCourses()
    this.getDefaultImportLibrary()
    this.getAssignmentInfo()
    this.getCurrentAssignmentQuestions()
    this.getQuestionWarningInfo()
  },
  methods: {
    openRemoveQuestionModal () {
      this.$bvModal.show('modal-remove-question')
    },
    async getQuestionWarningInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.rows
        let hasNonH5P
        for (let i = 0; i < this.items.length; i++) {
          if (this.items[i].submission !== 'h5p') {
            hasNonH5P = true
          }
          if (this.assessmentType !== 'delayed' && !this.items[i].auto_graded_only) {
            this.openEndedQuestionsInRealTime += this.items[i].order + ', '
          }
        }
        this.updateOpenEndedInRealTimeMessage()
        this.updateLearningTreeInNonLearningTreeMessage()
        this.updateNonLearningTreeInLearningTreeMessage()
        if (this.assessment_type === 'clicker' && hasNonH5P) {
          this.$bvModal.show('modal-non-h5p')
        }
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
    checkMove: function (evt) {
      let questionId = evt.draggedContext.element.question_id
      if (this.chosenPublicCourseAssignmentQuestions.find(question => question.question_id === questionId)) {
        this.$noty.info('That assessment is already in your assignment.')
        return false
      }
      return true
    },
    async removeQuestionFromRemixedAssignment (questionId) {
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
          this.$bvModal.hide('modal-view-question')
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
    async updateAssignmentWithChosenQuestions (type) {
      let success = true
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/remix-assignment-with-chosen-questions`,
          {
            'chosen_questions': this.chosenPublicCourseAssignmentQuestions,
            'type': type
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

      this.$bvModal.hide('modal-view-question')
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
    async getCurrentAssignmentQuestions () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/titles`)
        this.chosenPublicCourseAssignmentQuestions = data.assignment_questions
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async viewQuestion (questionId, action) {
      this.showQuestion = false
      this.viewQuestionAction = action
      try {
        this.$bvModal.show('modal-view-question')
        this.loadingQuestion = true
        const { data } = await axios.get(`/api/questions/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.loadingQuestion = false
          return false
        }
        this.questionToView = data.question
        this.showQuestion = true
        console.log(`#${this.questionToView.iframe_id}`)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.loadingQuestion = false
    },
    async getPublicCourseAssignmentQuestions (assignmentId) {
      if (assignmentId === null) {
        return false
      }
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/questions/titles`)
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
    },
    async getDefaultImportLibrary () {
      try {
        const { data } = await axios.get('/api/questions/default-import-library')
        console.log(data)
        this.defaultImportLibrary = data.default_import_library
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async setDefaultImportLibrary () {
      try {
        const { data } = await axios.post('/api/questions/default-import-library', { 'default_import_library': this.defaultImportLibrary })
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    resetDirectImport () {
      this.questions = []
      this.pageIdsAddedToAssignmentMessage = ''
      this.pageIdsNotAddedToAssignmentMessage = ''
      this.directImport = ''
    },
    resetSearchByTag () {
      this.showQuestions = false
      this.chosenTags = []
    },
    async directImportQuestions () {
      if (this.directImportingQuestions) {
        let timeToProcess = Math.ceil(((this.directImport.match(/,/g) || []).length) / 3)
        let message = `Please be patient.  Validating all of your page id's  will take about ${timeToProcess} seconds.`
        this.$noty.info(message)
        return false
      }
      this.pageIdsAddedToAssignmentMessage = ''
      this.pageIdsNotAddedToAssignmentMessage = ''
      this.directImportingQuestions = true
      try {
        const { data } = await axios.post(`/api/questions/${this.assignmentId}/direct-import-questions`, { 'direct_import': this.directImport })
        this.directImportingQuestions = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        if (data.page_ids_added_to_assignment) {
          let verb = data.page_ids_added_to_assignment.includes(',') ? 'were' : 'was'
          this.pageIdsAddedToAssignmentMessage = `${data.page_ids_added_to_assignment} ${verb} added to this assignment.`
        }
        if (data.page_ids_not_added_to_assignment) {
          let verb = data.page_ids_not_added_to_assignment.includes(',') ? 'were' : 'was'
          let pronoun = data.page_ids_not_added_to_assignment.includes(',') ? 'they' : 'it'
          this.pageIdsNotAddedToAssignmentMessage = `${data.page_ids_not_added_to_assignment} ${verb} not added to this assignment since ${pronoun} ${verb} already a part of the assignment.`
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.directImportingQuestions = false
      }
      this.directImport = ''
    },
    openUploadFileModal (questionId) {
      this.uploadFileForm.errors.clear(this.uploadFileType)
      this.uploadFileForm.questionId = questionId
      this.uploadFileForm.assignmentId = this.assignmentId
    },
    async handleOk (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      if (this.uploading) {
        this.$noty.info('Please be patient while the file is uploading.')
        return false
      }
      this.uploading = true
      try {
        await this.submitUploadFile('solution', this.uploadFileForm, this.$noty, this.$nextTick, this.$bvModal, this.questions[this.currentPage - 1], '/api/solution-files')
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.uploading = false
      console.log(this.questions[this.currentPage - 1])
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/get-questions-info`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        console.log(data.assignment)
        let assignment = data.assignment
        this.title = `Add Questions to "${assignment.name}"`
        this.assessmentType = assignment.assessment_type
        this.questionFilesAllowed = (assignment.submission_files === 'q')// can upload at the question level
      } catch (error) {
        console.log(error.message)
        this.title = 'Add Questions'
      }
      if (this.continueLoading) { // OK to load the rest of the page
        this.getTags()
        h5pResizer()
      }
      this.isLoading = false
    },
    changePage (currentPage) {
      this.$nextTick(() => {
        let iframeId = this.questions[currentPage - 1].iframe_id
        iFrameResize({ log: false }, `#${iframeId}`)
        iFrameResize({ log: false }, '#non-technology-iframe')
      })
    },
    removeTag (chosenTag) {
      this.chosenTags = _.without(this.chosenTags, chosenTag)
      this.questions = []
    },
    addTag () {
      if (this.chosenTags.length === 0 && this.query === '') {
        this.$noty.error('You did not include a tag.')
        return false
      }
      console.log(this.chosenTags)
      if (!this.tags.includes(this.query)) {
        this.$noty.error(`The tag <strong>${this.query}</strong> does not exist in our database.`)
        this.$refs.queryTypeahead.inputValue = this.query = ''
        return false
      }

      if (!this.chosenTags.includes(this.query)) {
        this.chosenTags.push(this.query)
      }
      this.$refs.queryTypeahead.inputValue = this.query = '' // https://github.com/alexurquhart/vue-bootstrap-typeahead/issues/22
      return true
    },
    async getTags () {
      try {
        const { data } = await axios.get(`/api/tags`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          this.tags = data.tags
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async addQuestion (question) {
      try {
        this.questions[this.currentPage - 1].questionFiles = false
        const { data } = await axios.post(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].inAssignment = true
        }
      } catch (error) {
        console.log(error)
        this.$noty.error('We could not add the question to the assignment.  Please try again or contact us for assistance.')
      }
    },
    async removeQuestion (question) {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
        if (data.type === 'success') {
          this.$noty.info(data.message)
          question.inAssignment = false
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },
    async getQuestionsByTags () {
      this.questions = []
      this.showQuestions = false
      this.gettingQuestions = true
      if (this.query) {
        // in case they didn't click
        let validTag = this.addTag()
        if (!validTag) {
          this.gettingQuestions = false
          return false
        }
      }
      try {
        if (this.chosenTags.length === 0) {
          this.$noty.error('Please choose at least one tag.')
          this.gettingQuestions = false
          return false
        }
        const { data } = await axios.post(`/api/questions/getQuestionsByTags`, { 'tags': this.chosenTags })
        let questionsByTags = data

        if (questionsByTags.type === 'success' && questionsByTags.questions.length > 0) {
          // get whether in the assignment and get the url
          const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/question-info`)

          let questionInfo = data

          console.log(questionsByTags.questions)
          console.log('by assignment')
          console.log(questionInfo)
          if ((questionInfo.type === 'success')) {
            for (let i = 0; i < questionsByTags.questions.length; i++) {
              questionsByTags.questions[i].inAssignment = questionInfo.question_ids.includes(questionsByTags.questions[i].id)

              questionsByTags.questions[i].questionFiles = questionInfo.question_files.includes(questionsByTags.questions[i].id)
            }

            this.questions = questionsByTags.questions
            let iframeId = this.questions[0].iframe_id
            this.$nextTick(() => {
              iFrameResize({ log: false }, `#${iframeId}`)
              iFrameResize({ log: false }, '#non-technology-iframe')
            })
            // console.log(this.questions)
            this.showQuestions = true
          } else {
            this.$noty.error(questionInfo.message)
          }
        } else {
          let timeout = questionsByTags.timeout ? questionsByTags.timeout : 6000
          this.$noty.error(questionsByTags.message, { timeout: timeout })
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.gettingQuestions = false
    },
    getStudentView (assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/view`)
    }
  },
  metaInfo () {
    return { title: this.$t('home') }
  }
}

</script>
<style>
body, html {
  overflow: visible;

}
</style>
