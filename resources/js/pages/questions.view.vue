<template>
  <div>
    <Email id="contact-grader-modal"
           ref="email"
           extra-email-modal-text="Before you contact your grader, please be sure to look at the solutions first, if they are available."
           :from-user="user"
           title="Contact Grader"
           type="contact_grader"
           :subject="getSubject()"
    />

    <b-modal
      id="modal-upload-file"
      ref="modal"
      :title="getModalUploadFileTitle()"
      ok-title="Submit"
      size="lg"
      :hide-footer="true"
      @ok="handleOk"
    >
      <p>
        <span v-if="user.role === 2">Upload an entire PDF with one solution per page and let Adapt cut up the PDF for you. Or, upload one
          solution at a time. If you upload a full PDF, students will be able to both download a full solution key
          and download solutions on a per question basis.</span>
        <span v-if="user.role !==2">
          Upload an entire PDF with one question file submission per page and let Adapt cut up the PDF for you. Or, upload one
          question file submission at a time, especially helpful if your submissions are in a non-PDF format.
        </span>
      </p>
      <b-form ref="form">
        <b-form-group>
          <b-form-radio v-model="uploadLevel" name="uploadLevel" value="assignment" @click="showCutups = true">
            Upload
            <span v-if="user.role === 2">question solutions</span>
            <span v-if="user.role !== 2">your question file submissions</span>
            from a full PDF that Adapt will cut up for you
          </b-form-radio>
          <b-form-radio v-model="uploadLevel" name="uploadLevel" value="question">
            Upload individual
            <span v-if="user.role === 2">question solution</span>
            <span v-if="user.role !== 2">question file submissions</span>
          </b-form-radio>
        </b-form-group>
        <div v-if="uploadLevel === 'assignment' && showCutups">
          <hr>
          <p>
            Select a single page or a comma separated list of pages to submit as your <span v-if="user.role === 2">solution to</span>
            <span v-if="user.role !== 2">file submission for</span> this question or
            <a href="#" @click="showCutups = false">
              upload a new PDF</a>.
          </p>
          <b-container class="mb-2">
            <b-form-group
              id="chosen_cutups"
              label-cols-sm="2"
              label-cols-lg="2"
              label="Chosen cutups"
              label-for="chosen_cutups"
            >
              <b-form-row lg="12">
                <b-col lg="3">
                  <b-form-input
                    id="name"
                    v-model="cutupsForm.chosen_cutups"
                    lg="3"
                    type="text"
                    :class="{ 'is-invalid': cutupsForm.errors.has('chosen_cutups') }"
                    @keydown="cutupsForm.errors.clear('chosen_cutups')"
                  />
                  <has-error :form="cutupsForm" field="chosen_cutups" />
                </b-col>
                <b-col lg="8" class="ml-3">
                  <b-row>
                    <b-button class="mt-1" size="sm" variant="outline-primary"
                              @click="setCutupAsSolutionOrSubmission(questions[currentPage-1].id)"
                    >
                      Set As <span v-if="user.role === 2">Solution</span>
                      <span v-if="user.role !== 2">Question File Submission</span>
                    </b-button>
                    <span v-show="settingAsSolution" class="ml-2">
                      <b-spinner small type="grow" />
                      Processing...
                    </span>
                  </b-row>
                </b-col>
              </b-form-row>
            </b-form-group>
          </b-container>
          <div class="overflow-auto">
            <b-pagination
              v-model="currentCutup"
              :total-rows="cutups.length"
              :limit="12"
              :per-page="perPage"
              first-number
              last-number
              size="sm"
              align="center"
            />
          </div>
          <div v-if="showCutups && cutups.length && cutups[currentCutup-1]">
            <b-embed
              type="iframe"
              aspect="16by9"
              :src="cutups[currentCutup-1].temporary_url"
              allowfullscreen
            />
          </div>
        </div>
        <b-container v-show="uploadLevel === 'assignment' && (!showCutups && cutups.length)">
          <b-row align-h="center">
            <b-button class="ml-2" size="sm" variant="outline-primary" @click="showCutups = true">
              Use Current Cutups
            </b-button>
          </b-row>
        </b-container>
        <div v-show="uploadLevel === 'question' || !showCutups">
          <p>Accepted file types are: {{ getSolutionUploadTypes() }}.</p>
          <b-form-file
            ref="questionFileInput"
            v-model="uploadFileForm[`${uploadFileType}File`]"
            placeholder="Choose a file or drop it here..."
            drop-placeholder="Drop file here..."
            :accept="getSolutionUploadTypes()"
          />
          <div v-if="uploading">
            <b-spinner small type="grow" />
            Uploading file...
          </div>
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">
            {{ uploadFileForm.errors.get(this.uploadFileType) }}
          </div>
          <b-container>
            <hr>
            <b-row align-h="end">
              <b-button class="mr-2" @click="handleCancel">
                Cancel
              </b-button>
              <b-button variant="primary" @click="handleOk">
                Submit
              </b-button>
            </b-row>
          </b-container>
        </div>
      </b-form>
    </b-modal>
    <div v-if="questionId" class="text-center">
      <h5 v-if="questions !==['init']">
        {{ name }}: Assessment {{ currentPage }} of {{ questions.length }}
      </h5>
    </div>
    <div v-else>
      <PageTitle v-if="questions !==['init']" :title="title" />
    </div>
    <div v-if="questions.length && !initializing">
      <div v-if="isInstructor() && (has_submissions_or_file_submissions || solutionsReleased)">
        <b-alert variant="info" show>
          <strong>This problem is locked.
            Either students have already submitted responses to this assignment or the solutions have been released. You
            can view the questions but you cannot add or remove them.
            In addition, you cannot update the number of points per question.</strong>
        </b-alert>
      </div>

      <div v-if="questions.length">
        <div class="mb-3">
          <b-container>
            <b-col>
              <div v-if="source === 'a' && scoring_type === 'p' && !questionId">
                <div class="text-center">
                  <h4>This assignment is worth {{ totalPoints.toString() }} points.</h4>
                </div>
                <div v-if="!isInstructor() && showPointsPerQuestion" class="text-center">
                  <h5>
                    This question is worth
                    {{ 1 * (questions[currentPage - 1].points) }}
                    points.
                  </h5>
                </div>

                <div v-if="isInstructor()" class="text-center">
                  <b-form-row>
                    <b-col />
                    <h5 class="mt-1">
                      This question is worth
                    </h5>
                    <b-col lg="1">
                      <b-form-input
                        id="points"
                        v-model="questionPointsForm.points"
                        :value="questions[currentPage-1].points"
                        type="text"
                        placeholder=""
                        :class="{ 'is-invalid': questionPointsForm.errors.has('points') }"
                        @keydown="questionPointsForm.errors.clear('points')"
                      />
                      <has-error :form="questionPointsForm" field="points" />
                    </b-col>
                    <h5 class="mt-1">
                      points.
                    </h5>
                    <b-col>
                      <div class="float-left">
                        <b-button variant="primary"
                                  size="sm"
                                  class="m-1"
                                  :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
                                  @click="updatePoints((questions[currentPage-1].id))"
                        >
                          Update Points
                        </b-button>
                      </div>
                    </b-col>
                  </b-form-row>
                </div>
              </div>
            </b-col>
            <b-row class="text-center">
              <b-col>
                <div v-if="timeLeft>0">
                  <countdown :time="timeLeft">
                    <template slot-scope="props">
                      Time Until due：{{ props.days }} days, {{ props.hours }} hours,
                      {{ props.minutes }} minutes, {{ props.seconds }} seconds.
                    </template>
                  </countdown>
                </div>
                <div v-if="user.role === 2">
                  Link to Question: {{ getCurrentPage() }}
                </div>
                <div v-if="timerSetToGetLearningTreePoints && !showLearningTreePointsMessage">
                  <countdown :time="timeLeftToGetLearningTreePoints" @end="awardPointsForVisitingLearningTree">
                    <template slot-scope="props">
                      Time Left Until Learning Tree Points：{{ props.days }} days, {{ props.hours }} hours,
                      {{ props.minutes }} minutes, {{ props.seconds }} seconds.
                    </template>
                  </countdown>
                </div>
                <div v-if="(!timerSetToGetLearningTreePoints) && showLearningTreePointsMessage && (user.role === 3)">
                  You have been awarded {{ 1 * (questions[currentPage - 1].points) }} points for exploring the Learning
                  Tree.
                </div>
                <div class="font-italic font-weight-bold">
                  <div v-if="(scoring_type === 'p')">
                    <div v-if="user.role === 3 && showScores">
                      <p>
                        <span v-if="questions[currentPage-1].questionFiles">
                          You achieved a total score of
                          {{ questions[currentPage - 1].total_score * 1 }}
                          out of a possible
                          {{ questions[currentPage - 1].points * 1 }} points.</span>
                      </p>
                    </div>
                  </div>
                </div>
                <div v-if="showScores && showAssignmentStatistics && !isInstructor()">
                  <b-button variant="outline-primary" @click="openShowAssignmentStatisticsModal()">
                    View Question
                    Statistics
                  </b-button>
                </div>
                <div v-if="isInstructor() && !(has_submissions_or_file_submissions || solutionsReleased)">
                  <b-button class="mt-1 mb-2 mr-2" variant="success" @click="getQuestionsForAssignment()">
                    Add Questions
                  </b-button>
                </div>
              </b-col>
            </b-row>
          </b-container>

          <b-modal v-model="showAssignmentStatisticsModal" size="xl" title="Question Level Statistics">
            <b-container>
              <b-row v-if="(scoring_type === 'p') && showAssignmentStatistics && loaded && user.role === 3">
                <b-col>
                  <b-card header="default" header-html="<h5>Summary</h5>">
                    <b-card-text>
                      <ul>
                        <li>{{ scores.length }} student submissions</li>
                        <li v-if="scores.length">
                          Maximum score of {{ max }}
                        </li>
                        <li v-if="scores.length">
                          Minimum score of {{ min }}
                        </li>
                        <li v-if="scores.length">
                          Mean score of {{ mean }}
                        </li>
                        <li v-if="scores.length">
                          Standard deviation of {{ stdev }}
                        </li>
                      </ul>
                    </b-card-text>
                  </b-card>
                </b-col>
                <b-col>
                  <scores v-if="scores.length" class="border-1 border-info"
                          :chartdata="chartdata"
                          :height="300" :width="300"
                  />
                </b-col>
              </b-row>
            </b-container>
          </b-modal>
        </div>
        <div class="overflow-auto">
          <b-pagination
            v-if="!questionId"
            v-model="currentPage"
            :total-rows="questions.length"
            :per-page="perPage"
            first-number
            last-number
            align="center"
            @input="changePage(currentPage)"
          />
        </div>
        <div v-if="isInstructor()">
          <b-container>
            <b-row>
              <div>
                <b-button class="mt-1 mb-2"
                          variant="danger"
                          :disabled="Boolean(has_submissions_or_file_submissions || solutionsReleased)"
                          @click="removeQuestion(currentPage)"
                >
                  Remove Question
                </b-button>
                <span v-if="questionFilesAllowed">
                  <span class="font-italic">Question File Upload Enabled: </span><toggle-button
                    :width="60"
                    :value="Boolean(questions[currentPage-1].questionFiles)"
                    :sync="true"
                    :font-size="14"
                    :margin="4"
                    :color="{checked: '#28a745', unchecked: '#6c757d'}"
                    :labels="{checked: 'Yes', unchecked: 'No'}"
                    @change="toggleQuestionFiles(questions, currentPage, assignmentId, $noty)"
                  />
                </span>
              </div>
              <div>
                <b-button v-b-modal.modal-upload-file
                          class="mt-1 mb-2 ml-1"
                          variant="dark"
                          @click="openUploadFileModal(questions[currentPage-1].id)"
                >
                  Upload Solution
                </b-button>
                <span v-if="questions[currentPage-1].solution">
                  Uploaded solution:
                  <a href=""
                     @click.prevent="downloadSolutionFile('q', assignmentId, questions[currentPage - 1].id, questions[currentPage - 1].solution)"
                  >
                    {{ questions[currentPage - 1].solution }}
                  </a>
                </span>
                <span v-if="!questions[currentPage-1].solution">No solutions have been uploaded.</span>
              </div>
            </b-row>
            <hr>
          </b-container>
        </div>

        <b-container>
          <b-row>
            <b-col :cols="questionCol">
              <div v-if="learningTreeAsList.length>0">
                <b-alert show>
                  <div v-if="!loadedTitles" class="text-center">
                    <h5>
                      <b-spinner variant="primary" type="grow" label="Spinning" />
                      Loading
                    </h5>
                  </div>
                  <div v-else>
                    <div class="d-flex justify-content-between mb-2">
                      <h5>Need some help? Explore the topics below.</h5>
                      <b-button class="float-right" :disabled="Boolean(showQuestion)" variant="primary"
                                @click="viewOriginalQuestion"
                      >
                        View Original
                        Question
                      </b-button>
                    </div>
                    <hr>
                    <b-container class="bv-example-row">
                      <b-row align-h="center">
                        <template v-for="remediationObject in this.learningTreeAsList">
                          <b-col v-for="(value, name) in remediationObject"
                                 v-if="(remediationObject.show) && (name === 'title')" :key="value.id"
                                 cols="4"
                          >
                            <b-row align-h="center">
                              <b-col cols="4">
                                <div class="h2 mb-0">
                                  <b-icon v-if="remediationObject.parent > 0" variant="info"
                                          icon="arrow-up-square-fill" @click="back(remediationObject)"
                                  />
                                </div>
                              </b-col>
                            </b-row>
                            <div class="border border-info mr-1 p-3 rounded">
                              <b-row align-h="center">
                                <div class="mr-1 ml-2">
                                  <strong>{{ remediationObject.title }}</strong>
                                </div>
                                <b-button size="sm" class="mr-2" variant="success"
                                          @click="explore(remediationObject.library, remediationObject.pageId)"
                                >
                                  Go!
                                </b-button>
                              </b-row>
                            </div>
                            <b-container>
                              <b-row align-h="center">
                                <b-col cols="4">
                                  <div class="h2 mb-0">
                                    <b-icon v-if="remediationObject.children.length"
                                            icon="arrow-down-square-fill" variant="info"
                                            @click="more(remediationObject)"
                                    />
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
                  <b-spinner variant="primary" type="grow" label="Spinning" />
                  Loading...
                </h5>
              </div>
              <iframe v-if="!showQuestion"
                      v-show="iframeLoaded" :id="remediationIframeId"
                      allowtransparency="true"
                      frameborder="0"
                      :src="remediationSrc"
                      style="width: 1px;min-width: 100%;" @load="showIframe(remediationIframeId)"
              />
              <div v-if="showQuestion">
                <div>
                  <iframe v-show="showQuestion && questions[currentPage-1].non_technology"
                          :id="`non-technology-iframe-${currentPage}`"
                          allowtransparency="true"
                          frameborder="0"
                          :src="questions[currentPage-1].non_technology_iframe_src"
                          style="width: 1px;min-width: 100%;"
                  />
                </div>
                <div v-html="questions[currentPage-1].technology_iframe" />
              </div>
            </b-col>
            <b-col v-if="(scoring_type === 'p') && showAssignmentStatistics && loaded && user.role === 2" cols="4">
              <b-card header="default" header-html="<h5>Question Statistics</h5>" class="mb-2">
                <b-card-text>
                  <ul>
                    <li>{{ scores.length }} student submissions</li>
                    <li v-if="scores.length">
                      Maximum score of {{ max }}
                    </li>
                    <li v-if="scores.length">
                      Minimum score of {{ min }}
                    </li>
                    <li v-if="scores.length">
                      Mean score of {{ mean }}
                    </li>
                    <li v-if="scores.length">
                      Standard deviation of {{ stdev }}
                    </li>
                  </ul>
                </b-card-text>
              </b-card>
              <scores v-if="scores.length" class="border-1 border-info"
                      :chartdata="chartdata"
                      :height="400"
              />
            </b-col>
            <b-col v-if="(user.role === 3)" cols="4">
              <b-row>
                <b-card header="default" header-html="<h5>Question Submission Information</h5>">
                  <b-card-text>
                    <span v-if="questions[currentPage-1].solution">
                      <span class="font-weight-bold">Solution:</span>
                      <a href=""
                         @click.prevent="downloadSolutionFile('q', assignmentId,questions[currentPage - 1].id, standardizeFilename(questions[currentPage - 1].solution))"
                      >
                        {{ standardizeFilename(questions[currentPage - 1].solution) }}
                      </a>
                      <br>
                    </span>
                    <span class="font-weight-bold">Number of attempts: </span> {{
                      questions[currentPage - 1].submission_count
                    }}<br>
                    <span class="font-weight-bold">Last submitted:</span> {{
                      questions[currentPage - 1].last_submitted
                    }}<br>

                    <span class="font-weight-bold">Last response:</span> {{
                      questions[currentPage - 1].student_response
                    }}<br>

                    <b-alert :variant="submissionDataType" :show="showSubmissionMessage">
                      <span class="font-weight-bold">{{ submissionDataMessage }}</span>
                    </b-alert>

                    <div v-if="(scoring_type === 'p') && showScores">
                      <span class="font-weight-bold">Question Score:</span> {{
                        questions[currentPage - 1].submission_score
                      }}<br>
                    </div>
                  </b-card-text>
                </b-card>
              </b-row>
              <b-row v-if="questions[currentPage-1].questionFiles && (user.role === 3)" class="mt-3 mb-3">
                <b-card header="Default" header-html="<h5>File Submission Information</h5>">
                  <b-card-text>
                    <strong> Uploaded file:</strong>
                    <span v-if="questions[currentPage-1].submission_file_exists">
                      <a href=""
                         @click.prevent="downloadSubmissionFile(assignmentId, questions[currentPage-1].submission, questions[currentPage-1].original_filename)"
                      >
                        {{ questions[currentPage - 1].original_filename }}
                      </a>
                    </span>
                    <span v-if="!questions[currentPage-1].submission_file_exists">
                      No files have been uploaded
                    </span><br>
                    <strong>Date Submitted:</strong> {{ questions[currentPage - 1].date_submitted }}<br>
                    <span v-if="showScores">
                      <strong>Date Graded:</strong> {{ questions[currentPage - 1].date_graded }}<br>
                    </span>
                    <span v-if="solutionsReleased">
                      <strong>File Feedback:</strong> <span v-if="!questions[currentPage-1].file_feedback">
                        N/A
                        <span v-if="questions[currentPage-1].file_feedback">
                          <a href=""
                             @click.prevent="downloadSubmissionFile(assignmentId, questions[currentPage-1].file_feedback, questions[currentPage-1].file_feedback)"
                          >
                            file_feedback
                          </a>
                        </span>
                        <br>
                        <strong>Comments:</strong> {{ questions[currentPage - 1].text_feedback }}<br>
                      </span>
                    </span>
                    <span v-if="showScores">
                      <strong>File Score:</strong> {{ questions[currentPage - 1].submission_file_score }}
                      <span v-if="questions[currentPage - 1].grader_id">
                        <b-button size="sm" variant="outline-primary"
                                  @click="openContactGraderModal( questions[currentPage - 1].grader_id)"
                        >Contact Grader</b-button>
                      </span>
                    </span><br>
                    <hr>
                    <div class="mt-2">
                      <b-button v-b-modal.modal-upload-file variant="primary"
                                class="float-right mr-2"
                                @click="openUploadFileModal(questions[currentPage-1].id)"
                      >
                        Upload New File
                      </b-button>
                    </div>
                  </b-card-text>
                </b-card>
              </b-row>
            </b-col>
          </b-row>
        </b-container>
      </div>
      <div v-else>
        <div v-if="questions !== ['init']">
          <div v-if="isInstructor()" class="mt-1 mb-2" @click="getAssessmentsForAssignment()">
            <b-button variant="success">
              Get More {{ capitalFormattedAssessmentType }}
            </b-button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="!initializing && !questions.length" class="mt-4">
      <div v-if="isInstructor()" class="mb-0" @click="getAssessmentsForAssignment()">
        <b-button variant="success">
          Add {{ capitalFormattedAssessmentType }}
        </b-button>
      </div>

      <b-alert show variant="warning" class="mt-3">
        <a href="#" class="alert-link">
          <span v-show="source === 'a'">This assignment currently has no assessments.</span>
          <span v-show="source === 'x'">This is an external assignment.  Please contact your instructor for more information.</span>
        </a>
      </b-alert>
    </div>
    <div v-if="showQuestionDoesNotExistMessage">
      <b-alert show variant="warning" class="mt-3">
        We could not find any questions associated with this assignment linked to:
        <p class="text-center m-2">
          <strong>{{ getWindowLocation() }}</strong>
        </p>
        Please ask your instructor to update this link so that it matches a question in the assignment.
      </b-alert>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'
import { toggleQuestionFiles } from '~/helpers/ToggleQuestionFiles'
import { getAcceptedFileTypes, submitUploadFile } from '~/helpers/UploadFiles'
import { h5pResizer } from '~/helpers/H5PResizer'

import { downloadSolutionFile, downloadSubmissionFile } from '~/helpers/DownloadFiles'

import Email from '~/components/Email'
import Scores from '~/components/Scores'
import { getScoresSummary } from '~/helpers/Scores'

export default {
  middleware: 'auth',
  components: {
    Scores,
    ToggleButton,
    Email
  },
  data: () => ({
    capitalFormattedAssessmentType: '',
    showPointsPerQuestion: false,
    showQuestionDoesNotExistMessage: false,
    timerSetToGetLearningTreePoints: false,
    timeLeftToGetLearningTreePoints: 0,
    maintainAspectRatio: false,
    showAssignmentStatisticsModal: false,
    showAssignmentStatistics: false,
    questionCol: 0,
    loaded: false,
    chartdata: null,
    assignmentInfo: {},
    scores: [],
    mean: 0,
    stdev: 0,
    max: 0,
    min: 0,
    range: 0,
    graderEmailSubject: '',
    to_user_id: false,
    showCutups: false,
    settingAsSolution: false,
    cutups: [],
    uploadLevel: 'assignment',
    timeLeft: 0,
    totalPoints: 0,
    uploadFileType: '',
    source: 'a',
    scoring_type: '',
    solutionsReleased: false,
    showScores: false,
    has_submissions_or_file_submissions: false,
    students_can_view_assignment_statistics: false,
    submissionDataType: 'danger',
    submissionDataMessage: '',
    showSubmissionMessage: false,
    uploading: false,
    uploadFileForm: new Form({
      questionFile: null,
      assignmentId: null,
      questionId: null
    }),
    cutupsForm: new Form({
      chosen_cutups: ''
    }),
    uploadSolutionForm: new Form({
      questionId: null
    }),
    questionPointsForm: new Form({
      points: null
    }),
    showLearningTreePointsMessage: false,
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
    currentCutup: 1,
    questions: [],
    initializing: true, // use to show a blank screen until all is loaded
    title: '',
    assignmentId: '',
    name: '',
    questionId: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  watch: {
    chartData: function () {
      this.renderChart(this.chartData, this.options)
    }
  },
  created () {
    h5pResizer()
    this.toggleQuestionFiles = toggleQuestionFiles
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.downloadSolutionFile = downloadSolutionFile
    this.downloadSubmissionFile = downloadSubmissionFile
  },
  async mounted () {
    this.uploadFileType = (this.user.role === 2) ? 'solution' : 'submission' // students upload question submissions and instructors upload solutions
    this.uploadFileUrl = (this.user.role === 2) ? '/api/solution-files' : '/api/submission-files'

    this.assignmentId = this.$route.params.assignmentId
    this.questionId = this.$route.params.questionId
    let canView = await this.getAssignmentInfo()
    if (!canView) {
      return false
    }

    this.questionCol = (this.user.role === 2 && this.scoring_type === 'c') ? 12 : 8
    if (this.source === 'a') {
      await this.getSelectedQuestions(this.assignmentId, this.questionId)
      await this.getCutups(this.assignmentId)
      window.addEventListener('message', this.receiveMessage, false)
    }
    this.showAssignmentStatistics = this.questions.length && (this.user.role === 2 || (this.user.role === 3 && this.students_can_view_assignment_statistics))
    if (this.showAssignmentStatistics) {
      this.loaded = false
      this.getScoresSummary = getScoresSummary
      try {
        const scoresData = await this.getScoresSummary(this.assignmentId, `/api/scores/summary/${this.assignmentId}/${this.questions[0]['id']}`)
        this.chartdata = scoresData
        this.loaded = true
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    this.logVisitAssessment(this.assignmentId, this.questions[0].id)
  },
  beforeDestroy () {
    window.removeEventListener('message', this.receiveMessage)
  },
  methods: {
    getWindowLocation () {
      return window.location
    },
    getCurrentPage () {
      return `${window.location.origin}/assignments/${this.assignmentId}/questions/view/${this.questions[this.currentPage - 1].id}`
    },
    async awardPointsForVisitingLearningTree () {
      alert('Start here!!')
      return false
      try {
        const { data } = await axios.post(`/api/submissions/${this.assignmentId}/${this.questions[this.page_id - 1].id}/award-points-for-visiting-learning-tree`)
        console.log(data)
        this.showLearningTreePointsMessage = true
        this.timerSetToGetLearningTreePoints = false
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    openShowAssignmentStatisticsModal () {
      this.showAssignmentStatisticsModal = true
    },
    getSubject () {
      return `${this.name}, Question #${this.currentPage}`
    },
    openContactGraderModal (graderId) {
      this.$refs.email.setExtraParams({
        'assignment_id': this.assignmentId,
        'question_id': this.questions[this.currentPage - 1].id
      })
      this.$refs.email.openSendEmailModal(graderId)
    },
    getModalUploadFileTitle () {
      return this.user.role === 3 ? 'Upload File Submission' : 'Upload Solutions'
    },
    getSolutionUploadTypes () {
      return this.uploadLevel === 'question' ? getAcceptedFileTypes() : getAcceptedFileTypes('.pdf')
    },
    standardizeFilename (filename) {
      let ext = filename.slice((Math.max(0, filename.lastIndexOf('.')) || Infinity) + 1)
      let name = this.name.replace(/[/\\?%*:|"<>]/g, '-')
      return `${name}-${this.currentPage}.${ext}`
    },
    async updateLastSubmittedAndLastResponse (assignmentId, questionId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/${questionId}/last-submitted-info`)
        this.questions[this.currentPage - 1]['last_submitted'] = data.last_submitted
        this.questions[this.currentPage - 1]['student_response'] = data.student_response
        this.questions[this.currentPage - 1]['submission_count'] = data.submission_count
      } catch (error) {
        console.log(error)
      }
    },
    async receiveMessage (event) {
      console.log(event.data)
      if (this.user.role === 3) {
        let technology = this.getTechnology(event.origin)
        // console.log(technology)
        // console.log(event.data)
        // console.log(event)
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
          serverSideSubmit = ((technology === 'imathas') && (JSON.parse(event.data).subject === 'lti.ext.imathas.result') ||
            (technology === 'webwork') && (JSON.parse(event.data).subject === 'webwork.result'))
        } catch (error) {
          serverSideSubmit = false
        }

        try {
          iMathASResize = ((technology === 'imathas') && (JSON.parse(event.data).subject === 'lti.frameResize'))
        } catch (error) {
          iMathASResize = false
        }

        if (iMathASResize) {
          let embedWrap = document.getElementById('embed1wrap')
          embedWrap.setAttribute('height', JSON.parse(event.data).wrapheight)
          let iframe = embedWrap.getElementsByTagName('iframe')[0]
          iframe.setAttribute('height', JSON.parse(event.data).height)
        }

        console.log('server side submit' + serverSideSubmit)
        if (serverSideSubmit) {
          console.log('serverSideSubmit')
          await this.showResponse(JSON.parse(event.data))
        }
        if (clientSideSubmit) {
          let submission_data = {
            'submission': event.data,
            'assignment_id': this.assignmentId,
            'question_id': this.questions[this.currentPage - 1].id,
            'technology': technology
          }

          console.log('submitted')
          console.log(submission_data)

          // if incorrect, show the learning tree stuff...
          try {
            this.hideResponse()
            const { data } = await axios.post('/api/submissions', submission_data)
            console.log(data)
            if (!data.message) {
              data.type = 'error'
              data.message = 'The server did not fully to this request and your submission may not have been saved.  Please refresh the page to verify the submission and contact support if the problem persists.'
            }
            await this.showResponse(data)
          } catch (error) {
            error.type = 'error'
            error.message = `The following error occurred: ${error}. Please refresh the page and try again and contact us if the problem persists.`
            await this.showResponse(error)
          }
        }
      }
    },
    isInstructor () {
      return (this.user.role === 2)
    },
    hideResponse () {
      this.showSubmissionMessage = false
    },
    async showResponse (data) {
      console.log('showing response')
      if (data.learning_tree) {
        await this.showLearningTree(data.learning_tree)
      }
      this.submissionDataType = (data.type === 'success') ? 'success' : 'danger'
      this.submissionDataMessage = data.message
      this.showSubmissionMessage = true
      setTimeout(() => {
        this.showSubmissionMessage = false
      }, 5000)
      if (data.type === 'success') {
        await this.updateLastSubmittedAndLastResponse(this.assignmentId, this.questions[this.currentPage - 1].id)
      }
    },
    getTechnology (body) {
      let technology
      if (body.includes('h5p.libretexts.org')) {
        technology = 'h5p'
      } else if (body.includes('imathas.libretexts.org')) {
        technology = 'imathas'
      } else if (body.includes('webwork.libretexts.org') || (body.includes('demo.webwork.rochester.edu'))) {
        technology = 'webwork'
      } else {
        technology = false
      }
      return technology
    },
    async updatePoints (questionId) {
      try {
        const { data } = await this.questionPointsForm.patch(`/api/assignments/${this.assignmentId}/questions/${questionId}/update-points`)
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
    openUploadFileModal (questionId) {
      this.uploadFileForm.errors.clear(this.uploadFileType)
      this.uploadFileForm.questionId = questionId
      this.uploadFileForm.assignmentId = this.assignmentId
      this.cutupsForm.chosen_cutups = ''
      this.cutupsForm.question_num = this.currentPage
      this.currentCutup = 1
    },
    async handleOk (bvModalEvt) {
      this.uploadFileForm.errors.clear(this.uploadFileType)
      this.uploadFileForm.uploadLevel = this.uploadLevel
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      if (this.uploading) {
        this.$noty.info('Please be patient while the file is uploading.')
        return false
      }
      this.uploading = true

      try {
        await this.submitUploadFile(this.uploadFileType, this.uploadFileForm, this.$noty, this.$nextTick, this.$bvModal, this.questions[this.currentPage - 1], this.uploadFileUrl, false)
      } catch (error) {
        this.$noty.error(error.message)
      }

      if (!this.uploadFileForm.errors.has(this.uploadFileType)) {
        await this.getCutups(this.assignmentId)
      }
      if (!this.uploadFileForm.errors.any() &&
        (this.uploadLevel === 'question' || !this.cutups.length)) {
        this.$bvModal.hide(`modal-upload-file`)
      }

      this.uploading = false
    },
    handleCancel () {
      this.$bvModal.hide(`modal-upload-file`)
    },
    viewOriginalQuestion () {
      this.showQuestion = true
      this.$nextTick(() => {
        this.showIframe(this.questions[this.currentPage - 1].iframe_id)
      })
    },
    showIframe (id) {
      this.iframeLoaded = true
      iFrameResize({ log: false }, `#${id}`)
      iFrameResize({ log: false }, `#non-technology-iframe-${this.currentPage}`)
    },
    back (remediationObject) {
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
    more (remediationObject) {
      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        // console.log(this.learningTreeAsList[i].id)
        this.learningTreeAsList[i].show = remediationObject.children.includes(this.learningTreeAsList[i].id)
      }
    },
    async changePage (currentPage) {
      this.showQuestion = true
      this.showSubmissionMessage = false
      this.$nextTick(() => {
        this.questionPointsForm.points = this.questions[currentPage - 1].points
        let iframeId = this.questions[currentPage - 1].iframe_id
        iFrameResize({ log: false }, `#${iframeId}`)
        iFrameResize({ log: false }, `#non-technology-iframe-${this.currentPage}`)
      })
      if (this.showAssignmentStatistics) {
        try {
          this.loaded = false
          const scoresData = await this.getScoresSummary(this.assignmentId, `/api/scores/summary/${this.assignmentId}/${this.questions[this.currentPage - 1]['id']}`)
          console.log(scoresData)
          this.chartdata = scoresData
          this.loaded = true
        } catch (error) {
          this.$noty.error(error.message)
        }
      }

      this.logVisitAssessment(this.assignmentId, this.questions[this.currentPage - 1].id)
    },
    async showLearningTree (learningTree) {
      // loop through and get all with parent = -1
      this.learningTree = learningTree
      if (!this.learningTree) {
        return false
      }
      // loop through each with parent having this level
      let pageId
      let library
      // console.log('length ' + learningTree.length)
      for (let i = 0; i < this.learningTree.length; i++) {
        let remediation = this.learningTree[i]
        // get the library and page ids
        // go to the server and return with the student learning objectives
        // "parent": 0, "data": [ { "name": "blockelemtype", "value": "2" },{ "name": "page_id", "value": "21691" }, { "name": "library", "value": "chem" }, { "name": "blockid", "value": "1" } ], "at}

        pageId = library = null
        let parent = remediation.parent
        let id = remediation.id
        for (let j = 0; j < remediation.data.length; j++) {
          switch (remediation.data[j].name) {
            case ('page_id'):
              pageId = remediation.data[j].value
              break
            case ('library'):
              library = remediation.data[j].value
              break
            case ('id'):
              id = remediation.data[j].value
          }
        }
        if (pageId && library) {
          const { data } = await axios.get(`/api/libreverse/library/${library}/page/${pageId}/title`)
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
    },
    explore (library, pageId) {
      this.showQuestion = false
      this.iframeLoaded = false
      this.remediationSrc = `https://${library}.libretexts.org/@go/page/${pageId}`
      this.remediationIframeId = `remediation-${library}-${pageId}`
      if (!this.timerSetToGetLearningTreePoints) {
        this.setTimerToGetLearningTreePoints()
      }
    },
    setTimerToGetLearningTreePoints () {
      this.timerSetToGetLearningTreePoints = true
      this.timeLeftToGetLearningTreePoints = this.minTimeNeededInLearningTree
      this.timeLeftToGetLearningTreePoints = 5000
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/view-questions-info`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.title = `${assignment.name} Assessments`
        this.name = assignment.name
        this.assessmentType = assignment.assessment_type
        this.capitalFormattedAssessmentType = this.assessmentType === 'learning tree' ? 'Learning Trees' : 'Questions'
        this.has_submissions_or_file_submissions = assignment.has_submissions_or_file_submissions
        this.timeLeft = assignment.time_left
        this.minTimeNeededInLearningTree = assignment.min_time_needed_in_learning_tree
        this.totalPoints = String(assignment.total_points).replace(/\.00$/, '')
        this.source = assignment.source
        this.questionFilesAllowed = (assignment.submission_files === 'q')// can upload at the question level
        this.solutionsReleased = Boolean(Number(assignment.solutions_released))
        this.showScores = Boolean(Number(assignment.show_scores))
        this.scoring_type = assignment.scoring_type
        this.students_can_view_assignment_statistics = assignment.students_can_view_assignment_statistics
        this.showPointsPerQuestion = assignment.show_points_per_question
      } catch (error) {
        this.$noty.error(error.message)
        this.title = 'Assessments'
      }
      return true
    },
    async setCutupAsSolutionOrSubmission (questionId) {
      if (this.settingAsSolution) {
        this.$noty.info('Please be patient while your request is being processed.')
        return false
      }
      this.settingAsSolution = true
      try {
        const { data } = await this.cutupsForm.post(`/api/cutups/${this.assignmentId}/${questionId}/set-as-solution-or-submission`)
        console.log(data)
        this.settingAsSolution = false
        if (data.type === 'success') {
          this.$noty.success(data.message)
          this.$bvModal.hide('modal-upload-file')
          // for instructor set the solution, for the student set an original_filename
          console.log(data)
          if (this.user.role === 3) {
            console.log(data)
            this.questions[this.currentPage - 1].submission = data.submission
            this.questions[this.currentPage - 1].original_filename = data.cutup
            this.questions[this.currentPage - 1].date_graded = 'N/A'
            this.questions[this.currentPage - 1].file_feedback = 'N/A'
            this.questions[this.currentPage - 1].submission_file_exists = true
          }
          if (this.user.role === 2) {
            this.questions[this.currentPage - 1].solution = data.cutup
          }
          this.questions[this.currentPage - 1].date_submitted = data.date_submitted
        } else {
          this.cutupsForm.errors.set('chosen_cutups', data.message)
        }
      } catch (error) {
        console.log(error)
        this.$noty.error('We could not set this cutup as your solution.  Please try again or contact us for assistance.')
      }
    },
    async getCutups (assignmentId) {
      try {
        const { data } = await axios.get(`/api/cutups/${assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.cutups = data.cutups
        this.showCutups = this.cutups.length
      } catch (error) {
        this.$noty.error('We could not retrieve your cutup solutions for this assignment.  Please try again or contact us for assistance.')
      }
    },
    async getSelectedQuestions (assignmentId, questionId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
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

        if (this.questionId) {
          if (!this.initCurrentPage(this.questionId)) {
            this.showQuestionDoesNotExistMessage = true
            return false
          }
        }
        let iframeId = this.questions[this.currentPage - 1].iframe_id
        this.$nextTick(() => {
          iFrameResize({ log: false }, `#${iframeId}`)
          iFrameResize({ log: false }, `#non-technology-iframe-${this.currentPage}`)
        })

        this.questionPointsForm.points = this.questions[this.currentPage - 1].points
        this.learningTree = this.questions[this.currentPage - 1].learning_tree

        console.log(this.learningTree)
        this.showLearningTree()

        this.initializing = false
      } catch (error) {
        this.$noty.error('We could not retrieve the questions for this assignment.  Please try again or contact us for assistance.')
      }
      this.iframeLoaded = true
    },
    initCurrentPage (questionId) {
      let questionExistsInAssignment = false
      for (let i = 0; i <= this.questions.length - 1; i++) {
        if (parseInt(this.questions[i].id) === parseInt(this.questionId)) {
          this.currentPage = i + 1
          questionExistsInAssignment = true
        }
      }
      return questionExistsInAssignment
    },
    logVisitAssessment (assignmentId, questionId) {
      try {
        if (this.user.role === 3) {
          axios.post('/api/logs', {
            'action': 'visit-assessment',
            'data': { 'assignment_id': assignmentId, 'question_id': questionId }
          })
        }
      } catch (error) {
        console.log(error.message)
      }
    },
    getAssessmentsForAssignment () {
      this.assessmentType === 'learning tree'
        ? this.$router.push(`/assignments/${this.assignmentId}/learning-trees/get`)
        : this.$router.push(`/assignments/${this.assignmentId}/questions/get`)
    },
    async removeQuestion (currentPage) {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.questions[currentPage - 1].id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.$noty.info('The question has been removed from the assignment.')
        this.questions.splice(currentPage - 1, 1)
        if (this.currentPage !== 1) {
          this.currentPage = this.currentPage - 1
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    }
  },
  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
<style scoped>
svg:hover {
  fill: #138496;
}
</style>
