<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-modal
        id="modal-edit-canned-responses"
        ref="modal"
        title="Edit Canned Responses"
        size="lg"
      >
        <b-list-group-item
          v-for="cannedResponse in cannedResponses"
          :key="cannedResponse.id"
          class="flex-column align-items-start"
        >
          {{ cannedResponse.canned_response }}
          <b-icon icon="trash" @click="removeCannedResponse(cannedResponse.id)" />
        </b-list-group-item>
        <b-input-group class="mt-4">
          <b-form-input v-model="cannedResponseForm.canned_response"
                        type="text"
                        :class="{ 'is-invalid': cannedResponseForm.errors.has('canned_response') }"
                        @keydown="cannedResponseForm.errors.clear('canned_response')"
          />
          <b-input-group-append>
            <b-button variant="primary" size="sm" @click="submitCannedResponseForm">
              Save Response
            </b-button>
          </b-input-group-append>
          <has-error :form="cannedResponseForm" field="canned_response" />
        </b-input-group>
        <template #modal-footer="{ ok }">
          <b-button size="sm" variant="success" @click="ok()">
            OK
          </b-button>
        </template>
      </b-modal>

      <b-modal
        id="modal-upload-file"
        ref="modal"
        hide-footer
        size="lg"
      >
        <template v-slot:modal-title>
          {{ feedbackModalTitle }}
        </template>
        <toggle-button
          class="mt-1"
          :width="105"
          :value="feedbackTypeIsPdfImage"
          :sync="true"
          :font-size="14"
          :margin="4"
          :color="$toggleCheckedUnchecked""
          :labels="{checked: 'PDF/Image', unchecked: 'Audio'}"
          @change="toggleFeedbackType()"
        />
        <div v-if="feedbackTypeIsPdfImage">
          <b-form ref="form">
            <p>Accepted file types are: {{ getAcceptedFileTypes() }}.</p>
            <b-form-file
              ref="fileFeedbackInput"
              v-model="fileFeedbackForm.fileFeedback"
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
              {{ fileFeedbackForm.errors.get('fileFeedback') }}
            </div>
            <hr>
            <b-row align-h="end" class="mr-2">
              <b-button class="mr-2" @click="handleCancel">
                Cancel
              </b-button>
              <b-button variant="primary" @click="handleOk">
                Submit
              </b-button>
            </b-row>
          </b-form>
        </div>
        <div v-if="!feedbackTypeIsPdfImage">
          <audio-recorder
            ref="recorder"
            class="m-auto"
            :upload-url="audioFeedbackUploadUrl"
            :time="1"
            :successful-upload="submittedAudioFeedbackUpload"
            :failed-upload="failedAudioFeedbackUpload"
          />
        </div>
      </b-modal>
      <div v-if="!isLoading">
        <PageTitle :title="title" />
        <div v-if="grading.length>0">
          <b-container>
            <b-row>
              <p class="font-italic">
                <strong>Instructions:</strong> For each student, please enter a submission score for the open-ended
                component and optionally
                add comments in the form of text or a file upload. The total number of points that the student receives
                for this questions will be the sum of the points that they received for submitting any automatically
                graded responses (Question Submission Score)
                plus the number of points that you give them for their file submission (File Submission Score). You can
                move through the course roster by using the right/left arrows, searching for students by name, or by
                clicking on individual student numbers.
              </p>
              <p v-if="user.role === 2">
                If you would like to change multiple scores at once, then you can do so through the
                <a href="" @click.prevent="gotoMassGrading"> mass grading view</a>.
              </p>
            </b-row>
          </b-container>
          <b-form-group
            v-if="user.id === 5"
            id="ferpa"
            label-cols-sm="3"
            label-cols-lg="2"
            label="FERPA Mode"
            label-for="FERPA Mode"
          >
            <toggle-button
              class="mt-2"
              :width="55"
              :value="ferpaMode"
              :sync="true"
              :font-size="14"
              :margin="4"
              :color="$toggleCheckedUnchecked""
              :labels="{checked: 'On', unchecked: 'Off'}"
              @change="submitFerpaMode()"
            />
          </b-form-group>
          <b-form-group
            v-if="hasMultipleSections"
            id="sections"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Section View"
            label-for="Section View"
          >
            <b-form-row>
              <b-col lg="3">
                <b-form-select
                  id="section-view"
                  v-model="sectionId"
                  :options="sections"
                  @change="processing=true;getGrading()"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="submission_group"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Submission Group"
            label-for="Submission Group"
          >
            <b-form-row>
              <b-col lg="5">
                <b-form-select
                  id="grade-view"
                  v-model="gradeView"
                  :options="gradeViews"
                  @change="processing=true;getGrading()"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="question"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Question"
            label-for="Question"
          >
            <b-form-row>
              <b-col lg="1">
                <b-form-select
                  id="question-view"
                  v-model="questionView"
                  :options="questionOptions"
                  @change="processing=true;getGrading()"
                />
              </b-col>
              <b-col lg="2">
                <span v-if="processing">
                  <b-spinner small type="grow" />
                  Processing...
                </span>
              </b-col>
            </b-form-row>
          </b-form-group>
          <hr>
          <div v-if="!showNoAutoGradedOrOpenSubmissionsExistAlert">
            <div class="text-center h5">
              Student
            </div>
            <div class="overflow-auto">
              <b-pagination
                :key="currentStudentPage"
                v-model="currentStudentPage"
                :total-rows="numStudents"
                :per-page="perPage"
                align="center"
                first-number
                last-number
                limit="17"
                @input="changePage()"
              />
            </div>
            <div class="text-center">
              <b-container>
                <b-row class="justify-content-md-center">
                  <b-col v-if="user.role === 2 || (user.role === 4 && gradersCanSeeStudentNames)" cols="3">
                    <vue-bootstrap-typeahead
                      ref="queryTypeahead"
                      v-model="jumpToStudent"
                      :data="students"
                      placeholder="Enter A Student's Name"
                      @hit="setQuestionAndStudentByStudentName"
                    />
                  </b-col>
                </b-row>
                <b-row class="justify-content-md-center">
                  <div class="d-flex mt-2 mb-2">
                    <span class="mt-2 mr-2">Jump To:</span>
                    <b-form-select v-model="studentNumberToJumpTo"
                                   style="width:70px"
                                   :options="jumpToStudentsByNumber"
                                   @change="jumpToStudentByNumber()"
                    />
                  </div>
                </b-row>
                <b-row v-if="user.role === 4 && !gradersCanSeeStudentNames" class="justify-content-md-center">
                  <b-alert :show="true" variant="info">
                    <span class="font-weight-bold font-weight-bold">
                      Your instructor has chosen to keep student names anonymous.  The names
                      you see below are randomly generated.
                    </span>
                  </b-alert>
                </b-row>
              </b-container>
              <h5 class="font-italic">
                This question is out of
                {{ grading[currentStudentPage - 1]['open_ended_submission']['points'] * 1 }} points.
              </h5>
              <div class="mb-2">
                <b-button variant="outline-primary"
                          @click="viewQuestion(grading[currentStudentPage - 1]['open_ended_submission'].question_id)"
                >
                  View Question
                </b-button>
                <span v-if="grading[currentStudentPage - 1]['open_ended_submission']['solution'] " class="ml-2">
                  <b-button variant="outline-primary"
                            @click.prevent="downloadSolutionFile('q', assignmentId, grading[currentStudentPage - 1]['open_ended_submission'].question_id, grading[currentStudentPage - 1]['open_ended_submission']['solution'])"
                  >
                    Download Solution
                  </b-button>
                </span>
              </div>
              <span v-if="!grading[currentStudentPage - 1]['open_ended_submission']['solution'] "
                    class="font-italic mt-2"
              >
                You currently have no solution uploaded for this question.
              </span>
            </div>
            <hr>
            <b-container>
              <b-row
                v-if="grading[currentStudentPage - 1]['open_ended_submission']['late_file_submission'] !== false"
              >
                <b-alert
                  :show="true"
                  variant="warning"
                >
                  <span class="alert-link">
                    The file submission was late by  {{
                      grading[currentStudentPage - 1]['open_ended_submission']['late_file_submission']
                    }}.
                    <span v-if="latePolicy === 'deduction'">
                      According to the late policy, a deduction of {{ lateDeductionPercent }}% should be applied once
                      <span v-if="lateDeductionApplicationPeriod !== 'once'">
                        per "{{ lateDeductionApplicationPeriod }}"</span>.
                    </span>
                  </span>
                </b-alert>
              </b-row>
              <b-row>
                <b-col>
                  <b-card header="default" :header-html="getStudentScoresTitle()" class="h-100">
                    <b-card-text>
                      <b-form ref="form">
                        <span v-if="grading[currentStudentPage - 1]['last_graded']" class="font-italic">
                          This score was last updated on {{ grading[currentStudentPage - 1]['last_graded'] }}.
                        </span>
                        <span v-if="!grading[currentStudentPage - 1]['last_graded']" class="font-italic">
                          A score has yet to be entered for this student.
                        </span>
                        <br>
                        <br>
                        <b-form-group
                          id="auto_graded_score"
                          label-cols-sm="5"
                          label-cols-lg="4"
                        >
                          <template slot="label">
                            <span class="font-weight-bold">Auto-graded score:</span>
                          </template>
                          <div v-show="isAutoGraded" class="pt-1">
                            <div class="d-flex">
                              <b-form-input v-show="grading[currentStudentPage - 1]['auto_graded_submission']"
                                            v-model="gradingForm.question_submission_score"
                                            type="text"
                                            size="sm"
                                            style="width:75px"
                                            :class="{ 'is-invalid': questionSubmissionScoreErrorMessage.length }"
                                            @keydown="questionSubmissionScoreErrorMessage = ''"
                              />
                              <span
                                v-if="isAutoGraded && !isOpenEnded && grading[currentStudentPage - 1]['auto_graded_submission']"
                              >
                                <b-button size="sm"
                                          class="ml-2"
                                          variant="outline-success"
                                          @click="submitGradingForm(true,
                                                                    {
                                                                      scoreType: 'question_submission_score',
                                                                      score: grading[currentStudentPage - 1]['open_ended_submission']['points'] * 1
                                                                    })"
                                >Full Score</b-button>
                                <b-button size="sm"
                                          class="ml-2"
                                          variant="outline-danger"
                                          @click="submitGradingForm(true,
                                                                    {
                                                                      scoreType: 'question_submission_score',
                                                                      score: 0
                                                                    })"
                                >
                                  Zero Score</b-button>
                              </span>
                            </div>

                            <div v-if="!grading[currentStudentPage - 1]['auto_graded_submission']"
                                 class="pt-1"
                            >
                              <span class="font-italic">No submission</span>
                            </div>
                          </div>
                          <div v-show="!isAutoGraded" class="pt-2">
                            <span class="font-italic">Not applicable</span>
                          </div>
                          <div v-if="questionSubmissionScoreErrorMessage" class="text-danger" style="font-size: 80%">
                            {{ questionSubmissionScoreErrorMessage }}
                          </div>
                        </b-form-group>
                        <b-form-group
                          id="open_ended_score"
                          label-cols-sm="5"
                          label-cols-lg="4"
                        >
                          <template slot="label">
                            <span class="font-weight-bold">
                              Open-ended score:
                            </span>
                          </template>
                          <div v-show="isOpenEnded" class="pt-1">
                            <div class="d-flex">
                              <b-form-input
                                v-show="grading[currentStudentPage - 1]['open_ended_submission']['submission']"
                                v-model="gradingForm.file_submission_score"
                                type="text"
                                size="sm"
                                style="width:75px"
                                :class="{ 'is-invalid': fileSubmissionScoreErrorMessage.length }"
                                @keydown="fileSubmissionScoreErrorMessage=''"
                              />
                              <span
                                v-if="isOpenEnded && !isAutoGraded && grading[currentStudentPage - 1]['open_ended_submission']['submission']"
                              >
                                <b-button size="sm"
                                          class="ml-2"
                                          variant="outline-success"
                                          @click="submitGradingForm(true,
                                                                    {
                                                                      scoreType: 'file_submission_score',
                                                                      score: grading[currentStudentPage - 1]['open_ended_submission']['points'] * 1
                                                                    })"
                                >Full Score</b-button>
                                <b-button size="sm"
                                          class="ml-2"
                                          variant="outline-danger"
                                          @click="submitGradingForm(true,
                                                                    {
                                                                      scoreType: 'file_submission_score',
                                                                      score: 0
                                                                    })"
                                >
                                  Zero Score</b-button>
                              </span>
                            </div>
                            <div v-show="!grading[currentStudentPage - 1]['open_ended_submission']['submission']"
                                 class="pt-2"
                            >
                              <span class="font-italic">No submission</span>
                            </div>
                          </div>
                          <div v-show="!isOpenEnded" class="pt-2">
                            <span class="font-italic">Not applicable</span>
                          </div>
                          <div v-if="fileSubmissionScoreErrorMessage" class="text-danger" style="font-size: 80%">
                            {{ fileSubmissionScoreErrorMessage }}
                          </div>
                        </b-form-group>
                        <strong>Total:</strong>
                        {{
                          (1 * grading[currentStudentPage - 1]['open_ended_submission']['question_submission_score'] || 0)
                            + (1 * grading[currentStudentPage - 1]['open_ended_submission']['file_submission_score'] || 0)
                        }} out of {{ grading[currentStudentPage - 1]['open_ended_submission']['points'] * 1 }}
                        <br>
                        <hr>
                        <b-container>
                          <b-row>
                            <b-col v-if="isOpenEndedFileSubmission">
                              <b-button variant="outline-primary"
                                        :disabled="grading[currentStudentPage - 1]['open_ended_submission']['submission'] === null"
                                        size="sm"
                                        @click="openInNewTab(getFullPdfUrlAtPage(grading[currentStudentPage - 1]['open_ended_submission']['submission_url'],grading[currentStudentPage - 1]['page']) )"
                              >
                                Open File Submission
                              </b-button>
                            </b-col>
                            <b-col>
                              <b-button :disabled="viewSubmission"
                                        size="sm"
                                        @click="toggleView(currentStudentPage)"
                              >
                                View Submission
                              </b-button>
                            </b-col>
                          </b-row>
                        </b-container>
                      </b-form>
                    </b-card-text>
                  </b-card>
                </b-col>

                <b-col>
                  <b-card header="default"
                          :header-html="getGraderFeedbackTitle()"
                          class="h-100"
                  >
                    <b-card-text align="center">
                      <div v-show="isOpenEnded">
                        <div v-show="grading[currentStudentPage - 1]['open_ended_submission']['submission']">
                          <b-row class="mb-2">
                            <b-col>
                              <b-form-select v-model="textFeedbackMode"
                                             :options="textFeedbackModeOptions"
                                             size="sm"
                              />
                            </b-col>
                            <b-col>
                              <b-button v-if="textFeedbackMode === 'canned_response'"
                                        variant="info"
                                        size="sm"
                                        @click="openEditCannedResponsesModal"
                              >
                                Edit Responses
                              </b-button>
                            </b-col>
                          </b-row>
                          <b-form ref="form">
                            <ckeditor v-if="textFeedbackMode === 'rich_text'"
                                      :key="`${currentQuestionPage}-${currentStudentPage}`"
                                      v-model="richTextFeedback"
                                      :config="richEditorConfig"
                                      style="margin-bottom: 23px"
                                      rows="5"
                                      max-rows="5"
                                      :class="{ 'is-invalid': gradingForm.errors.has('textFeedback') }"
                                      @namespaceloaded="onCKEditorNamespaceLoaded"
                            />
                            <b-form-textarea
                              v-if="textFeedbackMode === 'plain_text'"
                              id="text_comments"
                              v-model="plainTextFeedback"
                              style="margin-bottom: 23px"
                              placeholder="Enter something..."
                              rows="5"
                              max-rows="5"
                              :class="{ 'is-invalid': gradingForm.errors.has('textFeedback') }"
                              @keydown="gradingForm.errors.clear('textFeedback')"
                            />
                            <has-error :form="gradingForm" field="textFeedback" />

                            <b-form-select v-if="textFeedbackMode === 'canned_response'"
                                           v-model="cannedResponse"
                                           :options="cannedResponseOptions"
                                           class="mb-5"
                            />
                            <hr>
                            <b-row class="float-right">
                              <b-button
                                v-b-modal.modal-upload-file
                                variant="primary"
                                size="sm"
                                @click="openUploadFileModal()"
                              >
                                Upload Feedback File
                              </b-button>

                              <b-button
                                :disabled="!viewSubmission"
                                size="sm"
                                class="ml-2 mr-4"
                                @click="toggleView(currentStudentPage)"
                              >
                                View Feedback File
                              </b-button>
                            </b-row>
                          </b-form>
                        </div>
                        <div v-show="!grading[currentStudentPage - 1]['open_ended_submission']['submission']">
                          <h4 class="pt-5">
                            <span class="text-muted">
                              There is no open-ended submission for which to provide feedback.
                            </span>
                          </h4>
                        </div>
                      </div>
                      <div v-show="!isOpenEnded">
                        <h4 class="pt-5">
                          <span class="text-muted">
                            This panel is applicable to open-ended assessments.
                          </span>
                        </h4>
                      </div>
                    </b-card-text>
                  </b-card>
                </b-col>
              </b-row>
            </b-container>
            <b-container>
              <b-row align-h="center" class="pt-3">
                <b-button variant="primary"
                          :disabled="noSubmission"
                          size="sm"
                          class="ml-1 mr-1"
                          @click="submitGradingForm(false)"
                >
                  Submit
                </b-button>
                <b-button :disabled="currentStudentPage === numStudents || noSubmission"
                          size="sm"
                          variant="success"
                          @click="submitGradingForm(true)"
                >
                  Submit And Next
                </b-button>
              </b-row>
            </b-container>
            <hr>
            <div v-show="retrievedFromS3" class="row mt-4 d-flex justify-content-center" style="height:1000px">
              <div v-show="viewSubmission">
                <div v-if="isAutoGraded && grading[currentStudentPage - 1]['auto_graded_submission']['submission']">
                  <b-row align-h="center">
                    <span class="font-weight-bold font-italic">Auto-Graded Submission</span>
                  </b-row>
                  <div>
                    <b-row align-h="center">
                      {{ grading[currentStudentPage - 1]['auto_graded_submission']['submission'] }}
                    </b-row>
                  </div>
                </div>
                <div v-if="isOpenEnded && isAutoGraded">
                  <hr>
                </div>
                <div v-if="isOpenEnded && grading[currentStudentPage - 1]['open_ended_submission']['submission']">
                  <b-row align-h="center" class="pb-2">
                    <span class="font-weight-bold font-italic">Open-Ended Submission</span>
                  </b-row>
                </div>
                <div
                  v-if="(grading[currentStudentPage - 1]['open_ended_submission']['submission_url'])"
                >
                  <div v-if="isOpenEndedFileSubmission" class="row">
                    <iframe :key="grading[currentStudentPage - 1]['open_ended_submission']['submission']"
                            width="600"
                            height="600"
                            :src="getFullPdfUrlAtPage(grading[currentStudentPage - 1]['open_ended_submission']['submission_url'],grading[currentStudentPage - 1]['open_ended_submission']['page'])"
                    />
                  </div>
                  <div v-if="isOpenEndedAudioSubmission">
                    <b-card sub-title="Submission">
                      <audio-player
                        :src="grading[currentStudentPage - 1]['open_ended_submission']['submission_url']"
                      />
                    </b-card>
                  </div>
                </div>
                <b-container
                  v-if="isOpenEndedTextSubmission
                    && grading[currentStudentPage -1]['open_ended_submission']['submission_text']"
                >
                  <b-card>
                    <b-card-body>
                      <b-card-text>
                        <span class="font-weight-bold"
                              v-html="grading[currentStudentPage - 1]['open_ended_submission']['submission_text']"
                        />
                      </b-card-text>
                    </b-card-body>
                  </b-card>
                </b-container>
              </div>
              <div v-show="!viewSubmission">
                <iframe
                  v-if="grading[currentStudentPage - 1]['open_ended_submission']['file_feedback_type'] !== 'audio'"
                  width="600"
                  height="600"
                  :src="grading[currentStudentPage - 1]['open_ended_submission']['file_feedback_url']"
                />
                <b-card
                  v-if="grading[currentStudentPage - 1]['open_ended_submission']['file_feedback_type'] === 'audio'"
                  sub-title="Audio Feedback"
                >
                  <audio-player
                    v-if="grading[currentStudentPage - 1]['open_ended_submission']['file_feedback_type'] === 'audio'"
                    :src="grading[currentStudentPage - 1]['open_ended_submission']['file_feedback_url']"
                  />
                </b-card>
                <b-alert class="mt-1" :variant="audioFeedbackDataType" :show="showAudioFeedbackMessage">
                  <span class="font-weight-bold">{{ audioFeedbackDataMessage }}</span>
                </b-alert>
              </div>
            </div>
          </div>
        </div>
        <div v-if="showNoAutoGradedOrOpenSubmissionsExistAlert" class="mt-4">
          <b-alert show variant="info">
            <span class="alert-link">
              There are no submissions for this view.</span>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { downloadSubmissionFile, downloadSolutionFile, getFullPdfUrlAtPage } from '~/helpers/DownloadFiles'
import { getAcceptedFileTypes } from '~/helpers/UploadFiles'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import Vue from 'vue'
import { ToggleButton } from 'vue-js-toggle-button'
import CKEditor from 'ckeditor4-vue'
import { mapGetters } from 'vuex'

Vue.prototype.$http = axios // needed for the audio player
export default {
  middleware: 'auth',
  components: {
    Loading,
    ToggleButton,
    VueBootstrapTypeahead,
    ckeditor: CKEditor.component
  },
  data: () => ({
    questionSubmissionScoreErrorMessage: '',
    fileSubmissionScoreErrorMessage: '',
    jumpToStudentsByNumber: [],
    studentNumberToJumpTo: '--',
    gradersCanSeeStudentNames: false,
    isIndividualGrading: true,
    noSubmission: false,
    isAutoGraded: false,
    isOpenEnded: false,
    ferpaMode: false,
    message: '',
    processing: false,
    questionView: '',
    questionOptions: [],
    richTextFeedback: null,
    plainTextFeedback: null,
    cannedResponse: null,
    cannedResponseOptions: [],
    cannedResponseForm: new Form({
      canned_response: ''
    }),
    cannedResponses: [],
    richEditorConfig: {
      toolbar: [
        { name: 'clipboard', items: ['Cut', 'Copy', '-', 'Undo', 'Redo'] },
        {
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript']
        }
      ],
      removeButtons: '',
      height: 100
    },
    textFeedbackMode: 'plain_text',
    textFeedbackModeOptions: [
      { text: 'Plain Text', value: 'plain_text' },
      { text: 'Rich Text', value: 'rich_text' },
      { text: 'Canned Response', value: 'canned_response' }
    ],
    retrievedFromS3: false,
    hasMultipleSections: false,
    jumpToStudent: '',
    students: [],
    audioFeedbackDataType: '',
    audioFeedbackDataMessage: '',
    showAudioFeedbackMessage: false,
    feedbackModalTitle: 'Upload PDF/Image File',
    feedbackTypeIsPdfImage: true,
    audioFeedbackUploadUrl: '',
    isOpenEndedFileSubmission: false,
    isOpenEndedAudioSubmission: false,
    isOpenEndedTextSubmission: false,
    openEndedType: '',
    sections: [{ text: 'All Sections', value: 0 }],
    sectionId: 0,
    gradeViews: [
      { text: 'All Students', value: 'allStudents' },
      { text: 'Ungraded Open-Ended Submissions', value: 'ungradedOpenEndedSubmissions' },
      { text: 'Graded Open-Ended Submissions', value: 'gradedOpenEndedSubmissions' }
    ],
    latePolicy: null,
    lateDeductionApplicationPeriod: '',
    lateDeductionPercent: 0,
    isLoading: true,
    gradeView: 'allStudents',
    title: '',
    loaded: true,
    viewSubmission: true,
    showNoAutoGradedOrOpenSubmissionsExistAlert: false,
    uploading: false,
    currentQuestionPage: 1,
    currentStudentPage: 1,
    perPage: 1,
    numStudents: 0,
    grading: [],
    fileFeedbackForm: new Form({
      fileFeedback: null
    }),
    gradingForm: new Form({
      question_submission_score: null,
      file_submission_score: null,
      text_feedback_editor: 'plain',
      textFeedback: null
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.downloadSubmissionFile = downloadSubmissionFile
    this.downloadSolutionFile = downloadSolutionFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.getFullPdfUrlAtPage = getFullPdfUrlAtPage
    window.addEventListener('keydown', this.arrowListener)
  },
  destroyed () {
    window.removeEventListener('keydown', this.arrowListener)
  },
  mounted () {
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfoForGrading()
    this.getFerpaMode()
  },
  methods: {
    jumpToStudentByNumber () {
      if (this.studentNumberToJumpTo !== '--') {
        this.currentStudentPage = this.studentNumberToJumpTo
        this.changePage()
      }
    },
    gotoMassGrading () {
      this.$router.push({ name: 'assignment.mass_grading.index', params: { assignmentId: this.assignmentId } })
    },
    async getFerpaMode () {
      try {
        const { data } = await axios.get(`/api/scores/get-ferpa-mode`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.ferpaMode = Boolean(data.ferpa_mode)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitFerpaMode () {
      try {
        const { data } = await axios.patch(`/api/cookie/set-ferpa-mode/${+this.ferpaMode}`)
        if (data.type === 'success') {
          this.isLoading = true
          this.ferpaMode = !this.ferpaMode
          await this.getGrading(false)
          this.isLoading = false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    arrowListener (event) {
      let inTextArea = document.activeElement.id === 'text_comments'
      if (event.key === 'ArrowRight' &&
        this.currentStudentPage < this.numStudents &&
        !inTextArea) {
        this.currentStudentPage++
        this.changePage()
      }
      if (event.key === 'ArrowLeft' &&
        this.currentStudentPage > 1 &&
        !inTextArea) {
        alert(document.activeElement.id)
        this.currentStudentPage--
        this.changePage()
      }
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    async removeCannedResponse (cannedResponseId) {
      try {
        const { data } = await this.cannedResponseForm.delete(`/api/canned-responses/${cannedResponseId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getCannedResponses()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitCannedResponseForm (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.cannedResponseForm.post('/api/canned-responses')
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.cannedResponseForm.canned_response = ''
          await this.getCannedResponses()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async getCannedResponses () {
      try {
        const { data } = await axios.get('/api/canned-responses')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.cannedResponseOptions = [{ text: 'Please choose a response', value: null }]
        for (let i = 0; i < data.canned_responses.length; i++) {
          let cannedResponse = data.canned_responses[i]
          let cannedResponseOption = { text: cannedResponse.canned_response, value: cannedResponse.id }
          this.cannedResponseOptions.push(cannedResponseOption)
        }
        this.cannedResponses = data.canned_responses
        return true
      } catch (error) {
        this.$noty.error(error.message)
        return false
      }
    },
    async openEditCannedResponsesModal () {
      let success = await this.getCannedResponses()
      if (success) {
        this.$bvModal.show('modal-edit-canned-responses')
      }
    },
    async getFilesFromS3 () {
      try {
        let current = this.grading[this.currentStudentPage - 1]['open_ended_submission']
        if (!current.got_files) {
          const { data } = await axios.post(`/api/submission-files/get-files-from-s3/${this.assignmentId}/${current.question_id}/${current.user_id}`, { open_ended_submission_type: current.open_ended_submission_type })
          console.log('getting files')
          if (data.type === 'success') {
            let files = data.files
            this.grading[this.currentStudentPage - 1]['open_ended_submission'].file_feedback_url = files.file_feedback_url
            this.grading[this.currentStudentPage - 1]['open_ended_submission'].submission_url = files.submission_url
            this.grading[this.currentStudentPage - 1]['open_ended_submission'].submission_text = files.submission_text
            this.grading[this.currentStudentPage - 1]['open_ended_submission'].got_files = true
          } else {
            this.$noty.error(data.message)
          }
        }
        console.log(current)
      } catch (error) {
        this.$noty.error(`We could not retrieve the files for the student. ${error.message}`)
      }
    },
    setQuestionAndStudentByStudentName () {
      for (let j = 0; j < this.grading.length; j++) {
        if (this.jumpToStudent === this.grading[j]['student']['name']) {
          this.currentStudentPage = j + 1
          this.$refs.queryTypeahead.inputValue = this.jumpToStudent = ''
          this.changePage()
          return
        }
      }
    },
    toggleFeedbackType () {
      this.feedbackTypeIsPdfImage = !this.feedbackTypeIsPdfImage
      let feedbackType = this.feedbackTypeIsPdfImage ? 'PDF/Image' : 'Audio'
      this.feedbackModalTitle = `Upload ${feedbackType} File`
    },
    handleCancel () {
      this.$bvModal.hide(`modal-upload-file`)
    },
    failedAudioFeedbackUpload (data) {
      this.$bvModal.hide('modal-upload-file')
      this.$noty.error('We were not able to perform the upload.  Please try again or contact us for assistance.')
      axios.post('/api/submission-audios/error', JSON.stringify(data))
    },
    submittedAudioFeedbackUpload (response) {
      let data = response.data
      this.audioFeedbackDataType = (data.type === 'success') ? 'success' : 'danger'
      this.audioFeedbackDataMessage = data.message
      this.showAudioFeedbackMessage = true
      setTimeout(() => {
        this.showAudioFeedbackMessage = false
      }, 3000)
      if (data.type === 'success') {
        this.grading[this.currentStudentPage - 1]['open_ended_submission'].file_feedback_url = data.file_feedback_url
        this.grading[this.currentStudentPage - 1]['open_ended_submission'].file_feedback_type = data.file_feedback_type
      }
      this.viewSubmission = false
      this.$refs.recorder.removeRecord()
      this.$bvModal.hide('modal-upload-file')
    },
    capitalize (word) {
      return word.charAt(0).toUpperCase() + word.slice(1)
    },
    getGraderFeedbackTitle () {
      let grader = this.grading[this.currentStudentPage - 1]['open_ended_submission'].grader_name
        ? 'by ' + this.grading[this.currentStudentPage - 1]['open_ended_submission'].grader_name
        : ''
      return `<h5>Grader Feedback ${grader}</h5>`
    },
    getStudentScoresTitle () {
      return `<h5>Scores for ${this.grading[this.currentStudentPage - 1]['open_ended_submission']['name']}</h5>`
    },
    viewQuestion (questionId) {
      window.open(`/assignments/${this.assignmentId}/questions/view/${questionId}/view`)
    },
    openInNewTab (url) {
      console.log(url)
      window.open(url, '_blank')
    },
    async getAssignmentInfoForGrading () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/get-info-for-grading`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        let assignment = data.assignment
        this.questionOptions = data.questions
        if (!data.questions.length) {
          this.isLoading = false
          this.showNoAutoGradedOrOpenSubmissionsExistAlert = true
          return false
        }
        this.questionView = this.questionOptions[0].value
        let sections = data.sections
        this.hasMultipleSections = sections.length > 1
        if (this.hasMultipleSections) {
          for (let i = 0; i < sections.length; i++) {
            let section = sections[i]
            this.sections.push({ text: section.name, value: section.id })
          }
        }

        this.title = `Grading For ${assignment.name}`
        this.latePolicy = assignment.late_policy
        this.lateDeductionApplicationPeriod = assignment.late_deduction_application_period
        this.lateDeductionPercent = assignment.late_deduction_percent
        await this.getGrading(false)
        await this.getCannedResponses()
      } catch (error) {
        this.title = 'Grade Submissions'
      }
    },
    async toggleView () {
      if (this.grading.length > 0 && (this.grading[this.currentStudentPage - 1]['open_ended_submission']['file_feedback_url'] === null)) {
        this.$noty.info('You have not uploaded a feedback file.')
        return false
      }
      this.viewSubmission = !this.viewSubmission
    },
    async submitGradingForm (next, prepopulatedScore = {}) {
      if (prepopulatedScore.scoreType) {
        this.gradingForm[prepopulatedScore.scoreType] = prepopulatedScore.score
      }
      try {
        if (this.textFeedbackMode === 'canned_response' && this.cannedResponse === null) {
          this.$noty.error('You need to choose a response.')
          return false
        }
        this.gradingForm.text_feedback_editor = (this.textFeedbackMode === 'rich_text') ? 'rich' : 'plain'
        this.gradingForm.textFeedback = this.getTextFeedback(this.textFeedbackMode)
        if (this.gradingForm.textFeedback === false) {
          this.$noty.error('That is not a valid feedback mode.')
          return false
        }

        this.gradingForm.assignment_id = this.assignmentId
        this.gradingForm.question_id = this.grading[this.currentStudentPage - 1]['open_ended_submission']['question_id']
        this.gradingForm.user_id = this.grading[this.currentStudentPage - 1]['open_ended_submission']['user_id']

        const { data } = await this.gradingForm.post('/api/grading')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          if (this.isOpenEnded && this.grading[this.currentStudentPage - 1]['open_ended_submission']) {
            this.grading[this.currentStudentPage - 1]['open_ended_submission']['file_submission_score'] = this.gradingForm.file_submission_score
            this.grading[this.currentStudentPage - 1]['open_ended_submission']['grader_name'] = data.grader_name
            this.grading[this.currentStudentPage - 1]['open_ended_submission']['text_feedback_editor'] = this.gradingForm.text_feedback_editor
            this.grading[this.currentStudentPage - 1]['open_ended_submission']['text_feedback'] = this.gradingForm.textFeedback
            this.grading[this.currentStudentPage - 1]['last_graded'] = data.last_graded
          }
          if (this.isAutoGraded && this.grading[this.currentStudentPage - 1]['auto_graded_submission']) {
            this.grading[this.currentStudentPage - 1]['auto_graded_submission']['score'] = this.gradingForm.question_submission_score
          }
          if (next && this.currentStudentPage < this.numStudents) {
            this.currentStudentPage++
            await this.changePage()
          }
        }
      } catch (error) {
        console.log(this.gradingForm.errors.errors)

        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          if (this.gradingForm.errors.errors.question_submission_score) {
            this.questionSubmissionScoreErrorMessage = this.gradingForm.errors.errors.question_submission_score[0]
          }
          if (this.gradingForm.errors.errors.file_submission_score) {
            this.fileSubmissionScoreErrorMessage = this.gradingForm.errors.errors.file_submission_score[0]
          }
        }
      }
    },
    openUploadFileModal () {
      this.fileFeedbackForm.errors.clear('fileFeedback')
      let assignmentId = parseInt(this.assignmentId)
      let questionId = parseInt(this.grading[this.currentStudentPage - 1]['open_ended_submission']['question_id'])
      let studentUserId = parseInt(this.grading[this.currentStudentPage - 1]['open_ended_submission']['user_id'])
      this.audioFeedbackUploadUrl = `/api/submission-audios/audio-feedback/${studentUserId}/${assignmentId}/${questionId}`
    },
    async handleOk (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        this.fileFeedbackForm.errors.set('fileFeedback', null)
        this.uploading = true
        // https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
        let formData = new FormData()
        formData.append('fileFeedback', this.fileFeedbackForm.fileFeedback)
        formData.append('assignmentId', this.assignmentId)
        formData.append('questionId', this.grading[this.currentStudentPage - 1]['open_ended_submission']['question_id'])
        formData.append('userId', this.grading[this.currentStudentPage - 1]['open_ended_submission']['user_id'])
        formData.append('_method', 'put') // add this
        const { data } = await axios.post('/api/submission-files/file-feedback', formData)
        console.log(data)
        if (data.type === 'error') {
          this.fileFeedbackForm.errors.set('fileFeedback', data.message)
        } else {
          this.$noty.success(data.message)
          this.grading[this.currentStudentPage - 1]['open_ended_submission']['file_feedback_url'] = data.file_feedback_url
          this.grading[this.currentStudentPage - 1]['open_ended_submission']['file_feedback_type'] = data.file_feedback_type
          this.$bvModal.hide('modal-upload-file')
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)
      }
      this.uploading = false
    },
    getTextFeedback (textFeedbackMode) {
      switch (textFeedbackMode) {
        case ('rich_text'):
          return this.richTextFeedback
        case ('plain_text'):
          return this.plainTextFeedback
        case ('canned_response'):

          for (let i = 0; i < this.cannedResponseOptions.length; i++) {
            console.log(this.cannedResponseOptions[i].value)
            console.log(this.cannedResponse)
            if (this.cannedResponseOptions[i].value === this.cannedResponse) {
              return this.cannedResponseOptions[i].text
            }
          }
          break
        default:
          return false
      }
    },
    setTextFeedback (textFeedback) {
      this.richTextFeedback = ''
      this.plainTextFeedback = ''
      if (textFeedback) {
        this.textFeedbackMode = 'plain_text'
        switch (this.grading[this.currentStudentPage - 1]['open_ended_submission']['text_feedback_editor']) {
          case ('rich'):
            this.richTextFeedback = textFeedback
            this.textFeedbackMode = 'rich_text'
            break
          case ('plain'):
            this.plainTextFeedback = textFeedback
            this.textFeedbackMode = 'plain_text'
            break
          default:
            this.plainTextFeedback = textFeedback
            this.textFeedbackMode = 'plain_text'
            break
        }
      }
    },
    async changePage () {
      this.retrievedFromS3 = false
      this.showAudioFeedbackMessage = false
      this.noSubmission = !this.grading[this.currentStudentPage - 1]['auto_graded_submission'] && this.grading[this.currentStudentPage - 1]['open_ended_submission']['submission'] === null

      let textFeedback = this.grading[this.currentStudentPage - 1]['open_ended_submission']['text_feedback']
      this.gradingForm.file_submission_score = this.grading[this.currentStudentPage - 1]['open_ended_submission']['file_submission_score'] === 'N/A'
        ? null
        : 1 * this.grading[this.currentStudentPage - 1]['open_ended_submission']['file_submission_score']
      this.gradingForm.question_submission_score = !this.grading[this.currentStudentPage - 1]['auto_graded_submission']
        ? null
        : 1 * this.grading[this.currentStudentPage - 1]['auto_graded_submission']['score']

      this.setTextFeedback(textFeedback)
      this.gradingForm.textFeedback = this.grading[this.currentStudentPage - 1]['open_ended_submission']['text_feedback']
      console.log(this.grading[this.currentStudentPage - 1]['open_ended_submission'])

      this.openEndedType = this.grading[this.currentStudentPage - 1]['open_ended_submission'].open_ended_submission_type
      await this.getFilesFromS3()

      let submission = this.grading[this.currentStudentPage - 1]['open_ended_submission'].submission
      if (submission !== null && submission.split('.').pop() === 'pdf') {
        this.isOpenEndedFileSubmission = true
      } else {
        this.isOpenEndedFileSubmission = (this.openEndedType === 'file')
        this.isOpenEndedAudioSubmission = (this.openEndedType === 'audio')
        this.isOpenEndedTextSubmission = (this.openEndedType === 'text')
      }

      this.retrievedFromS3 = true
    },
    async getTemporaryUrl (file, currentQuestionPage, currentStudentPage) {
      if (this.grading[currentStudentPage - 1][file] && !this.grading[currentStudentPage - 1][`${file}_url`]) {
        try {
          const { data } = await axios.post('/api/submission-files/get-temporary-url-from-request',
            {
              'assignment_id': this.assignmentId,
              'file': this.grading[currentStudentPage - 1][file]
            })
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
          this.grading[currentStudentPage - 1][`${file}_url`] = data.temporary_url
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
    },
    submissionUrlExists (currentStudentPage) {
      return (this.grading[currentStudentPage - 1]['open_ended_submission']['submission_url'] !== null)
    },
    setQuestionAndStudentByQuestionIdAndStudentUserId (questionId, studentUserId) {
      for (let i = 0; i < this.grading.length; i++) {
        if (parseInt(studentUserId) === parseInt(this.grading[i]['student']['user_id'])) {
          this.currentStudentPage = i + 1
          return
        }
      }
    },
    async getGrading (showMessage = true) {
      if (this.$route.params.questionId && this.$route.params.studentUserId) {
        this.questionView = this.$route.params.questionId
        this.gradeView = 'allStudents'
        this.sectionId = 0
      }
      try {
        const { data } = await axios.get(`/api/grading/${this.assignmentId}/${this.questionView}/${parseInt(this.sectionId)}/${this.gradeView}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          this.processing = false
          return false
        }

        this.showNoAutoGradedOrOpenSubmissionsExistAlert = !(data.grading.length > 0)
        if (this.showNoAutoGradedOrOpenSubmissionsExistAlert) {
          this.isLoading = false
          this.processing = false
          return false
        }

        this.grading = data.grading
        this.isOpenEnded = data.is_open_ended
        this.isAutoGraded = data.is_auto_graded
        this.gradersCanSeeStudentNames = data.graders_can_see_student_names
        this.students = []
        this.numStudents = Object.keys(this.grading).length
        this.jumpToStudentsByNumber = [{ text: '--', value: '--' }]
        for (let i = 0; i < this.numStudents; i++) {
          this.students.push(this.grading[i]['student'].name)
          this.jumpToStudentsByNumber.push({ text: i + 1, value: i + 1 })
        }

        this.currentStudentPage = 1

        // loop through questions, inner loop through students, if match, then set question and student)

        if (this.$route.params.questionId && this.$route.params.studentUserId) {
          this.setQuestionAndStudentByQuestionIdAndStudentUserId(this.$route.params.questionId, this.$route.params.studentUserId)
        }
        await this.changePage()
        if (showMessage) {
          this.$noty.info(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
      this.processing = false
    }
  }
}
</script>
<style>
div.ar-icon svg {
  vertical-align: top !important;
}
</style>
