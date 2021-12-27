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
        <span class="font-weight-bold">
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
                                  :beta-assignments-exist="betaAssignmentsExist"
                                  :h5p-questions-with-anonymous-users="h5pQuestionsWithAnonymousUsers"
          />
          <b-row align-h="end">
            <b-button variant="primary" size="sm" @click="getStudentView(assignmentId)">
              View Questions
            </b-button>
          </b-row>
        </b-container>
        <hr>
        <div>
          <b-tabs content-class="mt-3">
            <b-tab title="Saved Questions" active @click="showQuestions = false;">
              <Remixer ref="remixer"
                       type-of-remixer="saved-questions"
                       :assignment-id="parseInt(assignmentId)"
                       :get-question-warning-info="getQuestionWarningInfo"
                       :set-question-to-remove="setQuestionToRemove"
              />
            </b-tab>
            <b-tab title="Assignment Remixer" @click="remixerKey++;showQuestions = false;">
              <Remixer :key="remixerKey"
                       ref="remixer2"
                       type-of-remixer="assignment-remixer"
                       :assignment-id="parseInt(assignmentId)"
                       :get-question-warning-info="getQuestionWarningInfo"
                       :set-question-to-remove="setQuestionToRemove"
              />
            </b-tab>
            <b-tab title="Search Query By Tag">
              <b-col @click="resetDirectImport()">
                <b-card header-html="<h2 class=&quot;h7&quot;>Search Query By Tag</h2>" class="h-100">
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
                          <span class="font-weight-bold">Chosen Tags:</span>
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
            <b-tab title="Direct Import By Libretexts ID" class="pb-8" @click="resetDirectImportMessages();showQuestions = false">
              <b-card header-html="<h2 class='h7'>Direct Import By Libretexts ID</h2>" style="height:425px">
                <b-card-text>
                  <b-container>
                    <b-row>
                      <b-col @click="resetSearchByTag">
                        <p>
                          Perform a direct import of questions directly into your assignment using the Libretexts ID. Please
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
                          v-model="directImport"
                          aria-label="Libretext IDs to direct import"
                          placeholder="Example. query-1023, chemistry-2213, chem-2213"
                          rows="4"
                          max-rows="5"
                        />
                        <div class="float-right mt-2">
                          <span v-if="directImportingQuestions" class="mr-3">
                            Processing {{ parseInt(directImportIndex) + 1 }} of {{ directImportCount }}
                          </span>
                          <b-button variant="success" size="sm" class="mr-2" @click="directImportQuestions('libretexts id')">
                            <b-spinner v-if="directImportingQuestions" small type="grow"/>
                            Import Questions
                          </b-button>
                        </div>
                      </b-col>
                    </b-row>
                  </b-container>
                  <div class="pt-4">
                    <div v-if="errorDirectImportIdsMessage.length>0">
                      <b-alert :show="true" variant="danger">
                        <span class="font-weight-bold">{{ errorDirectImportIdsMessage }}</span>
                      </b-alert>
                    </div>
                    <div v-if="directImportIdsAddedToAssignmentMessage.length>0">
                      <b-alert :show="true" variant="success">
                        <span class="font-weight-bold">{{ directImportIdsAddedToAssignmentMessage }}</span>
                      </b-alert>
                    </div>
                    <div v-if="directImportIdsNotAddedToAssignmentMessage.length>0">
                      <b-alert :show="true" variant="info">
                        <span class="font-weight-bold">{{ directImportIdsNotAddedToAssignmentMessage }}</span>
                      </b-alert>
                    </div>
                  </div>
                </b-card-text>
              </b-card>
            </b-tab>
            <b-tab title="Direct Import By ADAPT ID" class="pb-8" @click="resetDirectImportMessages();showQuestions = false">
              <b-card header-html="<h2 class='h7'>Direct Import By ADAPT ID</h2>" style="height:425px">
                <b-card-text>
                  <b-container>
                    <b-row>
                      <b-col @click="resetSearchByTag">
                        <p>
                          Perform a direct import of questions directly into your assignment using the ADAPT ID. Please
                          enter
                          the ADAPT IDs in a comma separated list.
                        </p>

                      </b-col>
                      <b-col>
                        <b-form-textarea
                          v-model="directImport"
                          aria-label="ADAPT IDs to direct import"
                          placeholder="Example. 1027-34, 1029-38, 1051-44"
                          rows="4"
                          max-rows="5"
                        />
                        <div class="float-right mt-2">
                          <span v-if="directImportingQuestions" class="mr-3">
                            Processing {{ parseInt(directImportIndex) + 1 }} of {{ directImportCount }}
                          </span>
                          <b-button variant="success" size="sm" class="mr-2" @click="directImportQuestions('adapt id')">
                            <b-spinner v-if="directImportingQuestions" small type="grow"/>
                            Import Questions
                          </b-button>
                        </div>
                      </b-col>
                    </b-row>
                  </b-container>
                  <div class="pt-4">
                    <div v-if="errorDirectImportIdsMessage.length>0">
                      <b-alert :show="true" variant="danger">
                        <span class="font-weight-bold">{{ errorDirectImportIdsMessage }}</span>
                      </b-alert>
                    </div>
                    <div v-if="directImportIdsAddedToAssignmentMessage.length>0">
                      <b-alert :show="true" variant="success">
                        <span class="font-weight-bold">{{ directImportIdsAddedToAssignmentMessage }}</span>
                      </b-alert>
                    </div>
                    <div v-if="directImportIdsNotAddedToAssignmentMessage.length>0">
                      <b-alert :show="true" variant="info">
                        <span class="font-weight-bold">{{ directImportIdsNotAddedToAssignmentMessage }}</span>
                      </b-alert>
                    </div>
                  </div>
                </b-card-text>
              </b-card>
            </b-tab>
          </b-tabs>
        </div>

        <hr>
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
                        @click="isRemixerTab = false; questionToRemove = questions[currentPage-1];openRemoveQuestionModal()"
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

import Form from 'vform'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import libraries from '~/helpers/Libraries'
import AssessmentTypeWarnings from '~/components/AssessmentTypeWarnings'
import Remixer from '~/components/Remixer'

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
    Remixer,
    AssessmentTypeWarnings,
    Loading,
    RemoveQuestion
  },
  middleware: 'auth',
  data: () => ({
    assignmentId: 0,
    remixerKey: 0,
    modalRemoveQuestionKey: 0,
    typeOfRemixer: '',
    h5pQuestionsWithAnonymousUsers: false,
    assessmentTypeWarningsKey: 0,
    betaAssignmentsExist: false,
    questionToRemove: {},
    isRemixerTab: true,
    errorDirectImportIdsMessage: '',
    directImportCount: '',
    directImportIndex: '',
    openEndedQuestionsInRealTime: '',
    learningTreeQuestionsInNonLearningTree: '',
    nonLearningTreeQuestions: '',
    showQuestion: false,
    school: '',
    schools: [],
    assessmentType: '',
    loadingQuestion: false,
    defaultImportLibrary: null,
    libraryOptions: libraries,
    directImportIdsNotAddedToAssignmentMessage: '',
    directImportIdsAddedToAssignmentMessage: '',
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
    this.getDefaultImportLibrary()
    this.getAssignmentInfo()
    this.getQuestionWarningInfo()
  },
  methods: {
    resetDirectImportMessages () {
      this.directImportIdsAddedToAssignmentMessage = ''
      this.errorDirectImportIdsMessage = ''
      this.directImportIdsNotAddedToAssignmentMessage = ''
    },
    setQuestionToRemove (questionToRemove, typeOfRemixer) {
      this.questionToRemove = questionToRemove
      this.typeOfRemixer = typeOfRemixer
      this.$bvModal.show('modal-remove-question')
    },
    submitRemoveQuestion () {
      this.isRemixerTab ? this.$refs.remixer.removeQuestionFromRemixedAssignment(this.questionToRemove.question_id) : this.removeQuestionFromSearchResult(this.questionToRemove)
    },
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
        this.betaAssignmentsExist = data.beta_assignments_exist
        this.h5pQuestionsWithAnonymousUsers = data.h5p_questions_exist && data.course_has_anonymous_users
        this.assessmentTypeWarningsKey = 1
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
      this.directImportIdsAddedToAssignmentMessage = ''
      this.directImportIdsNotAddedToAssignmentMessage = ''
      this.directImport = ''
    },
    resetSearchByTag () {
      this.showQuestions = false
      this.chosenTags = []
    },
    async directImportQuestions (type) {
      if (this.directImportingQuestions) {
        let timeToProcess = Math.ceil(((this.directImport.match(/,/g) || []).length) / 3)
        let message = `Please be patient.  Validating all of your Libretexts Ids  will take about ${timeToProcess} seconds.`
        this.$noty.info(message)
        return false
      }

      this.pageIdsAddedToAssignmentMessage = ''
      this.pageIdsNotAddedToAssignmentMessage = ''
      this.errorDirectImportIdsMessage = ''
      this.directImportIdsAddedToAssignmentMessage = ''
      this.directImportIdsNotAddedToAssignmentMessage = ''
      this.directImportingQuestions = true
      let directImport = this.directImport.split(',')
      this.directImportCount = directImport.length
      let directImportIdsAddedToAssignment = []
      let directImportIdsNotAddedToAssignment = []
      let errorDirectImportIds = []
      for (this.directImportIndex = 0; this.directImportIndex < directImport.length; this.directImportIndex++) {
        try {
          const { data } = await axios.post(`/api/questions/${this.assignmentId}/direct-import-question`,
            {
              'direct_import': directImport[this.directImportIndex],
              'type': type
            }
          )
          if (data.type === 'error') {
            errorDirectImportIds.push(directImport[this.directImportIndex])
            this.$noty.error(data.message)
          }
          if (data.direct_import_id_added_to_assignment) {
            directImportIdsAddedToAssignment.push(data.direct_import_id_added_to_assignment)
          }
          if (data.direct_import_id_not_added_to_assignment) {
            directImportIdsNotAddedToAssignment.push(data.direct_import_id_not_added_to_assignment)
          }
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      console.log(directImportIdsNotAddedToAssignment)
      this.directImportingQuestions = false
      directImportIdsAddedToAssignment = directImportIdsAddedToAssignment.join(', ')
      directImportIdsNotAddedToAssignment = directImportIdsNotAddedToAssignment.join(', ')
      let verb
      verb = directImportIdsAddedToAssignment.includes(',') ? 'were' : 'was'
      if (directImportIdsAddedToAssignment !== '') {
        this.directImportIdsAddedToAssignmentMessage = `${directImportIdsAddedToAssignment} ${verb} added to this assignment.`
      }
      if (errorDirectImportIds.length) {
        this.errorDirectImportIdsMessage = `Errors found with: ${errorDirectImportIds}`
      }
      verb = directImportIdsNotAddedToAssignment.includes(',') ? 'were' : 'was'
      let pronoun = directImportIdsNotAddedToAssignment.includes(',') ? 'they' : 'it'
      if (directImportIdsNotAddedToAssignment !== '') {
        this.directImportIdsNotAddedToAssignmentMessage = `${directImportIdsNotAddedToAssignment} ${verb} not added to this assignment since ${pronoun} ${verb} already a part of the assignment.`
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
    async removeQuestionFromSearchResult (question) {
      this.$bvModal.hide('modal-remove-question')
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
        if (data.type === 'info') {
          this.$noty.info(data.message)
          this.questions[this.currentPage - 1].inAssignment = false
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
    async getStudentView (assignmentId) {
      await this.$router.push(`/assignments/${assignmentId}/questions/view`)
    }
  },
  metaInfo () {
    return { title: 'Get Questions' }
  }
}
</script>
<style>
body, html {
  overflow: visible;

}
</style>
