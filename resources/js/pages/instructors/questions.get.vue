<template>
  <div>
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
          <b-spinner small type="grow" />
          Uploading file...
        </div>
        <input type="hidden" class="form-control is-invalid">
        <div class="help-block invalid-feedback">
          {{ uploadFileForm.errors.get('solutionFile') }}
        </div>
      </b-form>
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
        <PageTitle :title="title" />
        <b-container>
          <b-row align-h="end">
            <b-button variant="primary" @click="getStudentView(assignmentId)">
              View as Student
            </b-button>
          </b-row>
          <hr>
          <b-row>
            <b-col cols="6" class="border-right" @click="resetDirectImport()">
              <b-card header-html="<span class='font-weight-bold'>Search By Query Tag Or Page Id</span>" class="h-100">
                <b-card-text>
                  <p>
                    Search for query questions by tag or page id which can then be added to your assignment.
                    <b-icon id="search-by-tag-tooltip"
                            v-b-tooltip.hover
                            class="text-muted"
                            icon="question-circle"
                    />
                    <b-tooltip target="search-by-tag-tooltip" triggers="hover">
                      Using the search box you can find query questions by tag.
                      The tag can be a word associated with the question or can be the query library page id. To search
                      by page id, please use the tag: id={page id}. For example, id=112358.
                      Note that adding multiple tags will result in a search result which matches all of the conditions.
                    </b-tooltip>
                  </p>
                  <div class="col-5 p-0">
                    <vue-bootstrap-typeahead
                      ref="queryTypeahead"
                      v-model="query"
                      :data="tags"
                      placeholder="Enter a tag or id={page id}"
                    />
                  </div>
                  <div class="mt-3 d-flex flex-row">
                    <b-button variant="primary" class="mr-2" @click="addTag()">
                      Add Tag
                    </b-button>
                    <b-button variant="success" class="mr-2" @click="getQuestionsByTags()">
                      <b-spinner v-if="gettingQuestions" small type="grow" />
                      Get Questions
                    </b-button>
                  </div>
                  <hr>
                  <span class="font-weight-bold font-italic">Chosen Tags:</span>
                  <div v-if="chosenTags.length>0">
                    <ol>
                      <li v-for="chosenTag in chosenTags" :key="chosenTag">
                        <span @click="removeTag(chosenTag)">{{ chosenTag }}
                          <b-icon icon="trash" variant="danger" /></span>
                      </li>
                    </ol>
                  </div>
                  <div v-else>
                    <span class="text-danger">No tags have been chosen.</span>
                  </div>
                </b-card-text>
              </b-card>
            </b-col>
            <b-col @click="resetSearchByTag">
              <b-card header-html="<span class='font-weight-bold'>Direct Import By Page Id" class="h-100">
                <b-card-text>
                  <p>
                    Perform a direct import of questions directly into your assignment from any library using a comma
                    separated list of the form {libary}-{page id}.
                  </p>
                  <b-form-group
                    id="default_library"
                    label-cols-sm="5"
                    label-cols-lg="4"
                    label-for="Default Library"
                  >
                    <template slot="label">
                      Default Library <b-icon id="default-library-tooltip"
                                              v-b-tooltip.hover
                                              class="text-muted"
                                              icon="question-circle"
                      />
                      <b-tooltip target="default-library-tooltip" triggers="hover">
                        By setting the default library, you can just enter page ids.  As an example, choosing Query as the default
                        library, you can then enter 123,chemistry-927,149 instead of query-123,chemistry-927,query-149.
                      </b-tooltip>
                    </template>
                    <b-form-row>
                      <b-form-select v-model="defaultImportLibrary"
                                     :options="libraryOptions"
                                     @change="setDefaultImportLibrary()"
                      />
                    </b-form-row>
                  </b-form-group>
                  <b-form-textarea
                    id="textarea"
                    v-model="directImport"
                    placeholder="Example. query-1023, chemistry-2213"
                    rows="4"
                    max-rows="5"
                  />
                  <div class="float-right mt-2">
                    <b-button variant="success" class="mr-2" @click="directImportQuestions()">
                      <b-spinner v-if="directImportingQuestions" small type="grow" />
                      Import Questions
                    </b-button>
                  </div>
                </b-card-text>
              </b-card>
            </b-col>
          </b-row>
        </b-container>

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

      <div v-if="questions.length>0" class="overflow-auto">
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
          <b-row>
            <span v-if="!questions[currentPage-1].inAssignment">
              <b-button class="mt-1 mb-2 mr-2"
                        variant="primary" @click="addQuestion(questions[currentPage-1])"
              >Add Question
              </b-button>
            </span>
            <span v-if="questions[currentPage-1].inAssignment">
              <b-button class="mt-1 mb-2 mr-2"
                        variant="danger" @click="removeQuestion(questions[currentPage-1])"
              >Remove Question
              </b-button>

              <b-button v-if="false && questions[currentPage-1].inAssignment" v-b-modal.modal-upload-file
                        class="mt-1 mb-2"
                        variant="dark"
                        @click="openUploadFileModal(questions[currentPage-1].id)"
              >Upload Solution I'M HIDING THIS BUTTON AND IT'S JUST ON THE VIEW PAGE
              </b-button>

              <span v-if="questions[currentPage-1].solution">
                Uploaded solution:
                <a href=""
                   @click.prevent="downloadSolutionFile('q', assignmentId, questions[currentPage - 1].id, questions[currentPage - 1].solution)"
                >
                  {{ questions[currentPage - 1].solution }}
                </a>
              </span>
              <span
                v-if="!questions[currentPage-1].solution"
              >No solution uploaded.</span>
            </span>
          </b-row>
        </b-container>
        <div>
          <iframe v-if="showQuestions && questions[currentPage-1].non_technology"
                  id="non-technology-iframe"
                  allowtransparency="true"
                  frameborder="0"
                  :src="questions[currentPage-1].non_technology_iframe_src"
                  style="width: 1px;min-width: 100%;"
          />
        </div>
        <div v-html="questions[currentPage-1].technology_iframe" />
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

export default {
  components: {
    VueBootstrapTypeahead,
    Loading
  },
  middleware: 'auth',
  data: () => ({
    defaultImportLibrary: null,
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
  created () {
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.downloadSolutionFile = downloadSolutionFile
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getDefaultImportLibrary()
    this.getAssignmentInfo()
  },
  methods: {
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
        let assignment = data.assignment
        this.title = `Add Questions to "${assignment.name}"`
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
      console.log(this.chosenTags)
    },
    addTag () {
      console.log(this.query)
      if (this.query.includes('id=')) {
        let id = this.query.replace('id=', '')
        if (!((id >>> 0) === parseFloat(id))) {
          this.$noty.error('Your page id should be a positive integer.')
          return
        } else {
          this.chosenTags.push(this.query)
        }
      }
      if (this.query === '') {
        this.$noty.error('You did not include a tag.')
        return
      }
      if (!this.tags.includes(this.query)) {
        this.$noty.error(`The tag <strong>${this.query}</strong> does not exist in our database.`)
        this.$refs.queryTypeahead.inputValue = this.query = ''
        return
      }

      if (!this.chosenTags.includes(this.query)) {
        this.chosenTags.push(this.query)
      }
      this.$refs.queryTypeahead.inputValue = this.query = '' // https://github.com/alexurquhart/vue-bootstrap-typeahead/issues/22
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
      this.addTag() // in case they didn't click

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
