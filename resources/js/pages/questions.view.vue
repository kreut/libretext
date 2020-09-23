<template>
  <div>


    <b-modal
      id="modal-upload-question-file"
      ref="modal"
      title="Upload File"
      @ok="handleOk"
      @hidden="resetModalForms"
      ok-title="Submit"

    >

      <b-form ref="form">
        <p>Accepted file types are: {{ getAcceptedFileTypes() }}.</p>
        <b-form-file
          ref="questionFileInput"
          v-model="uploadForm.questionFile"
          placeholder="Choose a file or drop it here..."
          drop-placeholder="Drop file here..."
          :accept="getAcceptedFileTypes()"
        ></b-form-file>
        <div v-if="uploading">
          <b-spinner small type="grow"></b-spinner>
          Uploading file...
        </div>
        <input type="hidden" class="form-control is-invalid">
        <div class="help-block invalid-feedback">{{ uploadForm.errors.get('questionFile') }}
        </div>

      </b-form>
    </b-modal>


    <PageTitle v-bind:title="this.title" v-if="questions !==['init']"></PageTitle>
    <div v-if="questions.length && !initializing">
      <div v-if="questions.length">
        <div class="mb-3 text-center" v-if="user.role === 3">
          <h4>{{ questions[currentPage - 1].last_submitted }}</h4>
        </div>

        <div class="overflow-auto">
          <b-pagination
            v-model="currentPage"
            :total-rows="questions.length"
            :per-page="perPage"
            first-number
            last-number
            align="center"
            v-on:input="changePage(currentPage)"
          ></b-pagination>
        </div>

        <div>
          <div class="d-flex">
            <div v-if="user.role !== 3">
              <div v-if="has_submissions">
                <b-alert variant="info" show>
                  <strong>Since students have already submitted responses, you can view the questions but you can't add
                    or remove them.
                    In addition, you can't update the number of points per question.</strong></b-alert>
              </div>
              <div v-if="!has_submissions">
                <b-button class="mt-1 mb-2 mr-2" v-on:click="getQuestionsForAssignment()" variant="success">Get
                  Questions
                </b-button>
                <b-button class="mt-1 mb-2" v-on:click="removeQuestion(currentPage)" variant="danger">Remove Question
                </b-button>
                <b-button class="mt-1 mb-2"
                          v-on:click="$router.push(`/instructors/assignment/${assignmentId}/remediations/${questions[currentPage-1].id}`)"
                          variant="info">
                  Create Learning Tree
                </b-button>

                <toggle-button
                  v-if="questionFilesAllowed"
                  @change="toggleQuestionFiles(questions, currentPage, assignmentId, $noty)"
                  :width="250"
                  :value="questions[currentPage-1].questionFiles"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#007BFF', unchecked: '#75C791'}"
                  :labels="{checked: 'Disable Question File Upload', unchecked: 'Enable Question File Upload'}"/>
              </div>
            </div>
          </div>
          <b-form ref="form" v-if="!has_submissions && (user.role !== 3)">

            <b-form-group
              id="points"
              label-cols-sm="4"
              label-cols-lg="3"
              label="Number of points for this question"
              label-for="points"
            >

              <b-form-row>
                <b-col lg="2">
                  <b-form-input
                    id="points"
                    v-model="questionPointsForm.points"
                    :value="questions[currentPage-1].points"
                    type="text"
                    placeholder=""
                    :class="{ 'is-invalid': questionPointsForm.errors.has('points') }"
                    @keydown="questionPointsForm.errors.clear('points')"
                  >
                  </b-form-input>
                  <has-error :form="questionPointsForm" field="points"></has-error>
                </b-col>
                <b-col lg="2">
                  <b-button variant="primary" @click="updatePoints((questions[currentPage-1].id))">Update Points
                  </b-button>
                </b-col>
              </b-form-row>

            </b-form-group>

          </b-form>
          <b-alert :variant="this.submissionDataType" :show="showSubmissionMessage">
            <strong>{{ this.submissionDataMessage }}</strong></b-alert>

          <div class="mb-2" v-if="questions[currentPage-1].questionFiles && (user.role === 3)">
            <div class="col-6">
              <b-card title="File Submission Information">
                <b-card-text>
                  Uploaded file:
                  <span v-if="questions[currentPage-1].submission_file_exists">
                  <a href=""
                     v-on:click.prevent="downloadSubmission(assignmentId, questions[currentPage-1].submission, questions[currentPage-1].original_filename, $noty)">
                    {{ questions[currentPage - 1].original_filename }}
                  </a>
                  </span>
                  <span v-if="!questions[currentPage-1].submission_file_exists">
                        No files have been uploaded
                  </span><br>
                  Date Submitted: {{ questions[currentPage - 1].date_submitted }}<br>
                  Date Graded: {{ questions[currentPage - 1].date_graded }}<br>
                  File Feedback: <span v-if="!questions[currentPage-1].file_feedback">
                                      N/A
                  </span>
                  <span v-if="questions[currentPage-1].file_feedback">
                     <a href=""
                        v-on:click.prevent="downloadSubmission(assignmentId, questions[currentPage-1].file_feedback, questions[currentPage-1].file_feedback, $noty)">
                    file_feedback
                  </a>
                  </span>
                  <br>
                  Comments: {{ questions[currentPage - 1].text_feedback }}<br>
                  File Score: {{ questions[currentPage - 1].submission_file_score }}<br>
                  <b-button variant="primary" class="float-right mr-2"
                            v-on:click="openUploadQuestionFileModal(questions[currentPage-1].id)"
                            v-b-modal.modal-upload-question-file>Upload New File
                  </b-button>
                </b-card-text>
              </b-card>
            </div>

          </div>
        </div>
        <div v-if="learningTreeAsList.length>0">
          <b-alert show>

            <div class="text-center" v-if="!loadedTitles">
              <h5>
                <b-spinner variant="primary" type="grow" label="Spinning"></b-spinner>
                Loading
              </h5>
            </div>
            <div v-else>
              <div class="d-flex justify-content-between mb-2">
                <h5>Need some help? Explore the topics below.</h5>
                <b-button class="float-right" :disabled="showQuestion" variant="primary"
                          v-on:click="viewOriginalQuestion">View Original
                  Question
                </b-button>

              </div>
              <hr>
              <b-container class="bv-example-row">
                <b-row align-h="center">
                  <template v-for="remediationObject in this.learningTreeAsList">
                    <b-col cols="4" v-for="(value, name) in remediationObject" v-bind:key="value.id"
                           v-if="(remediationObject.show) && (name === 'title')">
                      <b-row align-h="center">
                        <b-col cols="4">
                          <div class="h2 mb-0">
                            <b-icon variant="info" v-if="remediationObject.parent > 0"
                                    v-on:click="back(remediationObject)" icon="arrow-up-square-fill">
                            </b-icon>
                          </div>
                        </b-col>
                      </b-row>
                      <div class="border border-info mr-1 p-3 rounded">
                        <b-row align-h="center">
                          <div class="mr-1 ml-2"><strong>{{ remediationObject.title }}</strong></div>
                          <b-button size="sm" class="mr-2" variant="success"
                                    v-on:click="explore(remediationObject.library, remediationObject.pageId)">
                            Go!
                          </b-button>
                        </b-row>
                      </div>
                      <b-container>
                        <b-row align-h="center">
                          <b-col cols="4">
                            <div class="h2 mb-0">
                              <b-icon v-if="remediationObject.children.length"
                                      v-on:click="more(remediationObject)" icon="arrow-down-square-fill"
                                      variant="info">
                              </b-icon>
                            </div>
                          </b-col>
                        </b-row>
                      </b-container>
                    </b-col>
                  </template>
                </b-row>
              </b-container>
            </div>
          </b-alert>
        </div>

        <div v-if="!iframeLoaded" class="text-center">
          <h5>
            <b-spinner variant="primary" type="grow" label="Spinning"></b-spinner>
            Loading...
          </h5>
        </div>
        <iframe v-bind:id="remediationIframeId"
                allowtransparency="true" frameborder="0"
                v-bind:src="remediationSrc"
                style="width: 1px;min-width: 100%;"
                v-if="!showQuestion"
                v-on:load="showIframe(remediationIframeId)" v-show="iframeLoaded"
        >
        </iframe>

        <div v-if="showQuestion" v-html="questions[currentPage-1].body"></div>
      </div>
      <div v-else>
        <div v-if="questions !== ['init']">
          <div class="mt-1 mb-2" v-on:click="getQuestionsForAssignment()" v-if="user.role !== 3">
            <b-button variant="success">Get More Questions</b-button>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-4" v-if="!initializing && !questions.length">
      <div class="mt-1 mb-2" v-on:click="getQuestionsForAssignment()" v-if="user.role !== 3">
        <b-button variant="success">Get Questions</b-button>
      </div>
      <b-alert show variant="warning"><a href="#" class="alert-link">This assignment currently has no
        questions.
      </a>
      </b-alert>
    </div>
  </div>
</template>


<script>
import axios from 'axios'
import Form from 'vform'
import {mapGetters} from "vuex"
import {ToggleButton} from 'vue-js-toggle-button'
import {toggleQuestionFiles} from '~/helpers/ToggleQuestionFiles'
import {submitUploadFile} from '~/helpers/UploadFiles'
import {getAcceptedFileTypes} from '~/helpers/UploadFiles'
import {h5pResizer} from "~/helpers/H5PResizer"
import {downloadSubmission} from '~/helpers/SubmissionFiles'

export default {
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  components: {
    ToggleButton
  },
  data: () => ({
    has_submissions: false,
    submissionDataType: 'danger',
    submissionDataMessage: '',
    showSubmissionMessage: false,
    uploading: false,
    uploadForm: new Form({
      questionFile: null,
      assignmentId: null,
      questionId: null
    }),
    questionPointsForm: new Form({
      points: null,
      assignmentId: null,
      questionId: null
    }),
    remediationIframeId: '',
    iframeLoaded: false,
    showedInvalidTechnologyMessage: false,
    loadedTitles: false,
    showQuestion: true,
    remediationSrc: '',
    learningTree: [],
    currentLearningTreeLevel: [],
    learningTreeAsList: [],
    learningTreeAsList_1: [],
    perPage: 1,
    currentPage: 1,
    questions: [],
    initializing: true, //use to show a blank screen until all is loaded
    title: '',
    assignmentId: ''
  }),
  created() {

    this.toggleQuestionFiles = toggleQuestionFiles
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.downloadSubmission = downloadSubmission
  },
  mounted() {

    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
    this.getSelectedQuestions(this.assignmentId)

    h5pResizer()
    let vm = this
    if (this.user.role === 3) {
      let receiveMessage = async function (event) {
        vm.hideResponse()
        let technology = vm.getTechnology(event.origin)
        console.log(technology)
        console.log(event.data)
        console.log(event)
        if (technology === 'imathas') {


        }
        let clientSideSubmit
        let serverSideSubmit
        let iMathASResize
        try {
          clientSideSubmit = ((technology === 'h5p') && (JSON.parse(event.data).verb.id === 'http://adlnet.gov/expapi/verbs/answered'))

        } catch (error) {
          clientSideSubmit = false
        }
        try {
          serverSideSubmit = ((technology === 'imathas') && (JSON.parse(event.data).subject === 'lti.ext.imathas.result'))
        } catch (error) {
          serverSideSubmit = false
        }

        try {
          iMathASResize = ((technology === 'imathas') && (JSON.parse(event.data).subject === 'lti.frameResize'))
        } catch (error) {
          iMathASResize = false
        }

    if (iMathASResize){
      let embedWrap = document.getElementById('embed1wrap')
      embedWrap.setAttribute('height',JSON.parse(event.data).wrapheight)
      let iframe = embedWrap.getElementsByTagName("iframe")[0]
      iframe.setAttribute("height", JSON.parse(event.data).height);
    }


        if (serverSideSubmit) {
          vm.showResponse(JSON.parse(event.data))
        }
        if (clientSideSubmit) {
          let submission_data = {
            'submission': event.data,
            'assignment_id': vm.assignmentId,
            'question_id': vm.questions[vm.currentPage - 1].id,
            'technology': technology
          }
          console.log('submitted')
          console.log(submission_data)

          //if incorrect, show the learning tree stuff...
          try {
            const {data} = await axios.post('/api/submissions', submission_data)
            console.log(data)
            if (data.message) {
              vm.showResponse(data)
            }
          } catch (error) {
            alert(`We could not save your submission:  ${error.message}.  Please try again or contact us for assistance.`)

          }
        }
      }
      window.addEventListener("message", receiveMessage, false)
    }
  },
  methods: {
    hideResponse() {
      this.showSubmissionMessage = false
    },
    showResponse(data) {
      console.log('showing response')
      this.submissionDataType = (data.type === 'success') ? 'success' : 'danger'
      if (data.type === 'success') {
        this.questions[this.currentPage - 1]['last_submitted'] = data.last_submitted;
      }
      this.submissionDataMessage = data.message
      this.showSubmissionMessage = true
      setTimeout(() => {
        this.showSubmissionMessage = false;
      }, 5000);
    },
    getTechnology(body) {
      let technology
      if (body.includes('h5p.libretexts.org')) {
        technology = 'h5p'
      } else if (body.includes('imathas.libretexts.org')) {
        technology = 'imathas'
      } else if (body.includes('webwork.libretexts.org')) {
        technology = 'webwork'
      } else {
        technology = false
      }
      return technology
    },
    async updatePoints(questionId) {
      try {

        const {data} = await this.questionPointsForm.patch(`/api/assignments/${this.assignmentId}/questions/${questionId}/update-points`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].points = this.questionPointsForm.points
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }

    },
    resetModalForms() {
      // alert('reset modal')
    },
    openUploadQuestionFileModal(questionId) {
      this.uploadForm.errors.clear('questionFile')
      this.uploadForm.questionId = questionId
      this.uploadForm.assignmentId = this.assignmentId
    },
    async handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      if (this.uploading) {
        this.$noty.info('Please be patient while the file is uploading.')
        return false
      }
      this.uploading = true
      await this.submitUploadFile('question', this.uploadForm, this.$noty, this.$refs, this.$nextTick, this.$bvModal, this.questions[this.currentPage - 1])
      this.uploading = false
      console.log(this.questions[this.currentPage - 1])
    },
    viewOriginalQuestion() {
      this.showQuestion = true
      this.$nextTick(() => {
        this.showIframe(this.questions[this.currentPage - 1].iframe_id)
      })

    },
    showIframe(id) {
      this.iframeLoaded = true
      iFrameResize({log: true}, `#${id}`)
    },
    back(remediationObject) {
      let parentIdToShow = false
      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        if (this.learningTreeAsList[i].id === remediationObject.parent) {
          parentIdToShow = this.learningTreeAsList[i].parent
        }
      }
      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        this.learningTreeAsList[i].show = (this.learningTreeAsList[i].parent === parentIdToShow)
      }

    },
    more(remediationObject) {

      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        //console.log(this.learningTreeAsList[i].id)
        this.learningTreeAsList[i].show = remediationObject.children.includes(this.learningTreeAsList[i].id)
      }
    },
    async changePage(currentPage) {
      this.showQuestion = true
      this.showSubmissionMessage = false
      this.$nextTick(() => {
        this.questionPointsForm.points = this.questions[currentPage - 1].points
        console.log(this.questions[currentPage - 1])
        let iframe_id = this.questions[currentPage - 1].iframe_id
        iFrameResize({log: true}, `#${iframe_id}`)
      })
      this.learningTree = this.questions[currentPage - 1].learning_tree
      this.learningTreeAsList = []
      if (this.learningTree) {
        //loop through and get all with parent = -1
        console.error(this.learningTree)
        //loop through each with parent having this level
        let pageId
        let library
        // console.log('length ' + learningTree.length)
        for (let i = 0; i < this.learningTree.length; i++) {
          let remediation = this.learningTree[i]
          //get the library and page ids
          //go to the server and return with the student learning objectives
          // "parent": 0, "data": [ { "name": "blockelemtype", "value": "2" },{ "name": "page_id", "value": "21691" }, { "name": "library", "value": "chem" }, { "name": "blockid", "value": "1" } ], "at}

          pageId = library = null
          let parent = remediation.parent
          let id = remediation.id
          for (let j = 0; j < remediation.data.length; j++) {
            switch (remediation.data[j].name) {
              case('page_id'):
                pageId = remediation.data[j].value
                break
              case ('library'):
                library = remediation.data[j].value
                break
              case('id'):
                id = remediation.data[j].value
            }
          }
          if (pageId && library) {
            const {data} = await axios.get(`/api/libreverse/library/${library}/page/${pageId}/title`)
            let remediation = {
              'library': library,
              'pageId': pageId,
              'title': data,
              'parent': parent,
              'id': id,
              'show': (parent === 0)
            }
            this.learningTreeAsList.push(remediation)
          }
          for (let i = 0; i < this.learningTreeAsList.length; i++) {
            this.learningTreeAsList[i]['children'] = []

            for (let j = 0; j < this.learningTreeAsList.length; j++) {

              if (i !== j && (this.learningTreeAsList[j]['parent'] === this.learningTreeAsList[i]['id'])) {
                this.learningTreeAsList[i]['children'].push(this.learningTreeAsList[j]['id'])
              }
            }

          }
        }


        console.log('done')
        console.log(this.learningTreeAsList)
        this.loadedTitles = true

      }
    },
    explore(library, pageId) {
      this.showQuestion = false
      this.iframeLoaded = false
      this.remediationSrc = `https://${library}.libretexts.org/@go/page/${pageId}`
      this.remediationIframeId = `remediation-${library}-${pageId}`
    },
    async getAssignmentInfo() {
      try {
        const {data} = await axios.get(`/api/assignments/${this.assignmentId}`)
        this.title = `${data.name} Assignment Questions`
        this.has_submissions = data.has_submissions
        this.questionFilesAllowed = (data.submission_files === 'q')//can upload at the question level
      } catch (error) {
        this.$noty.error(error.message)
        this.title = 'Assignment Questions'
      }
    },
    async getSelectedQuestions(assignmentId) {
      try {
        const {data} = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
        console.log(JSON.parse(JSON.stringify(data)))


        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questions = data.questions
        if (!this.questions.length) {
          this.initializing = false
          return false
        }

        let iframe_id = this.questions[0].iframe_id;
        this.$nextTick(() => {
          iFrameResize({log: true}, `#${iframe_id}`)
        })

        this.questionPointsForm.points = this.questions[0].points

        this.initializing = false
      } catch (error) {

        this.$noty.error('We could not retrieve the questions for this assignment.  Please try again or contact us for assistance.')
      }
      this.iframeLoaded = true
    },
    getQuestionsForAssignment() {
      this.$router.push(`/assignments/${this.assignmentId}/questions/get`)
    },
    async removeQuestion(currentPage) {
      try {
        axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.questions[currentPage - 1].id}`)
        this.$noty.info('The question has been removed from the assignment.')
        this.questions.splice(currentPage - 1, 1);

        if (this.currentPage !== 1) {
          f
          this.currentPage = this.currentPage - 1;
        }

      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    }
  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>
<style scoped>
svg:hover {
  fill: #138496;
}
</style>
