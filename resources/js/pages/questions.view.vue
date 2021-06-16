<template>
  <div style="min-height:400px; margin-bottom:100px">
    <b-modal
      id="modal-not-updated"
      ref="modalNotUpdated"
      hide-footer
      title="Not Updated"
    >
      <b-container>
        <b-row>
          <span class="font-italic font-weight-bold" style="font-size: large">
           {{ submissionDataMessage }}
          </span>
        </b-row>
      </b-container>
    </b-modal>
    <b-modal
      id="modal-learning-tree"
      ref="modalLearningTree"
      hide-footer
      title="Explore Learning Tree"
    >
      <b-container>
        <b-row>
          <span class="font-italic font-weight-bold" style="font-size: large">
          <font-awesome-icon :icon="treeIcon" class="text-success"/>
           {{ submissionDataMessage }}
          </span>
        </b-row>
      </b-container>
    </b-modal>
    <b-modal
      id="modal-completed-assignment"
      ref="modalThumbsUp"
      hide-footer
      size="sm"
      title="Congratulations!"
    >
      <b-container>
        <b-row>
          <img src="/assets/img/391906020_THUMBS_UP_400px.gif" alt="Thumbs up" width="275">
        </b-row>
        <b-row><h5>All Question Submissions Successfully Completed.</h5></b-row>
      </b-container>
    </b-modal>
    <b-modal
      id="modal-thumbs-down"
      ref="modalThumbsUp"
      hide-footer
      size="lg"
      title="Submission Not Accepted"
    >
      <b-alert variant="danger" :show="true">
        <span class="font-italic font-weight-bold" style="font-size: large" v-html="submissionDataMessage"/>
      </b-alert>
    </b-modal>
    <b-modal
      id="modal-submission-accepted"
      ref="modalThumbsUp"
      hide-footer
      title="Submission Accepted"
      @hidden="checkIfAssignmentCompleted"
    >
      <font-awesome-icon class="text-success"
                         :icon="checkIcon"
      />
      <span class="font-italic" style="font-size: large" v-html="submissionDataMessage"/>
    </b-modal>
    <div v-if="showInvalidAssignmentMessage">
      <b-alert show variant="info">
        <div class="font-weight-bold">
          <p>
            It looks like you're trying to access an assignment with the URL {{ $router.currentRoute.path }}
            but we can't find that assignment. Please log out then log back in as your instructor may have updated the
            link.
            However, if you are still having issues and this is an embedded problem, please let your instructor know so
            that they can fix the URL.
          </p>
        </div>
      </b-alert>
    </div>
    <EnrollInCourse/>
    <Email id="contact-grader-modal"
           ref="email"
           extra-email-modal-text="Before you contact your grader, please be sure to look at the solutions first, if they are available."
           :from-user="user"
           title="Contact Grader"
           type="contact_grader"
           :subject="getSubject()"
    />

    <b-modal
      id="modal-reset-to-default-text"
      ref="modal"
      title="Confirm Reset To Default Text"
    >
      <p>
        By resetting to the default text, your current text submission will be removed.
      </p>
      <p><strong>Once the submission is removed, we will not be able to retrieve it!</strong></p>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" variant="primary" @click="close()">
          Cancel
        </b-button>
        <b-button size="sm" variant="danger" @click="submitResetDefaultOpenEndedText">
          Delete Submission And Reset Default Text
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-remove-question"
      ref="modal"
      title="Confirm Remove Question"
      ok-title="Yes, remove question"
      @ok="submitRemoveQuestion"
    >
      <RemoveQuestion/>
    </b-modal>

    <b-modal
      id="modal-share"
      ref="modal_share"
      title="Share"
      ok-title="OK"
      size="xl"
    >
      Attribution:
      <toggle-button
        :width="80"
        class="mt-1"
        :value="iFrameAttribution"
        :sync="true"
        :font-size="14"
        :margin="4"
        :color="{checked: '#28a745', unchecked: '#6c757d'}"
        :labels="{checked: 'Shown', unchecked: 'Hidden'}"
        @change="iFrameAttribution = !iFrameAttribution;updateShare()"
      />
      <b-icon id="attribution-tooltip"
              v-b-tooltip.hover
              class="text-muted"
              icon="question-circle"
      />
      <b-tooltip target="attribution-tooltip" triggers="hover">
        The attribution includes who authored the question and the license associated with the question.
      </b-tooltip>
      <br>
      Submission Information:
      <toggle-button
        class="mt-1"
        :width="80"
        :value="iFrameSubmissionInformation"
        :sync="true"
        :font-size="14"
        :margin="4"
        :color="{checked: '#28a745', unchecked: '#6c757d'}"
        :labels="{checked: 'Shown', unchecked: 'Hidden'}"
        @change="iFrameSubmissionInformation = !iFrameSubmissionInformation;updateShare()"
      />
      <b-icon id="submissionInformation-tooltip"
              v-b-tooltip.hover
              class="text-muted"
              icon="question-circle"
      />
      <b-tooltip target="submissionInformation-tooltip" triggers="hover">
        The submission information includes when the question was submitted, the score on the question, and the last
        submitted.
      </b-tooltip>
      <br>
      Assignment Information:
      <toggle-button
        class="mt-1"
        :width="80"
        :value="iFrameAssignmentInformation"
        :sync="true"
        :font-size="14"
        :margin="4"
        :color="{checked: '#28a745', unchecked: '#6c757d'}"
        :labels="{checked: 'Shown', unchecked: 'Hidden'}"
        @change="iFrameAssignmentInformation = !iFrameAssignmentInformation; updateShare()"
      />
      <b-icon id="assignmentInformation-tooltip"
              v-b-tooltip.hover
              class="text-muted"
              icon="question-circle"
      />
      <b-tooltip target="assignmentInformation-tooltip" triggers="hover">
        This information includes the name of the assignment, the question number in the assignment, and the time left
        in the assignment.
      </b-tooltip>
      <div class="mb-2">
        <span class="font-weight-bold">Library:</span> <span id="libraryText">{{ libraryText }}</span>
        <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('libraryText')"/>
        </span>
      </div>
      <div class="mb-2">
        <span class="font-weight-bold">Page ID:</span> <span id="pageId">{{ pageId }}</span>
        <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('pageId')"/>
        </span>
      </div>
      <div class="mb-2">
        <span class="font-weight-bold">Adapt ID: </span><span id="adaptID">{{ adaptId }}</span>
        <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('adaptID')"/>
        </span>
      </div>
      <div class="mb-2">
        <span class="font-weight-bold">Adapt URL:</span> <span id="currentURL" class="font-italic">{{
          currentUrl
        }}</span>
        <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('currentURL')"/>
        </span>
      </div>
      <div class="mb-2">
        <span class="font-weight-bold">iframe:</span> <span id="embedCode" class="font-italic">{{ embedCode }}</span>
        <span class="text-info">
          <font-awesome-icon :icon="copyIcon" @click="doCopy('embedCode')"/>
        </span>
      </div>
      <div v-if="technologySrc" class="mb-2">
        <span class="font-weight-bold">Technology URL: </span><span id="technologySrc" class="font-italic"
                                                                    v-html="technologySrc"
      />
      </div>
    </b-modal>

    <b-modal
      id="modal-upload-file"
      ref="modal"
      :title="getModalUploadFileTitle()"
      ok-title="Submit"
      size="lg"
      :hide-footer="true"
    >
      <span v-if="user.role === 2">
        <toggle-button
          class="mt-1"
          :width="105"
          :value="solutionTypeIsPdfImage"
          :sync="true"
          :font-size="14"
          :margin="4"
          :color="{checked: '#28a745', unchecked: '#6c757d'}"
          :labels="{checked: 'PDF/Image', unchecked: 'Audio'}"
          @change="solutionTypeIsPdfImage= !solutionTypeIsPdfImage"
        />

      </span>
      <div v-if="!solutionTypeIsPdfImage">
        <audio-recorder
          ref="recorder"
          class="m-auto"
          :upload-url="audioSolutionUploadUrl"
          :time="1"
          :successful-upload="submittedAudioSolutionUpload"
          :failed-upload="failedAudioUpload"
        />
        <div v-if="showAddTextToSupportTheAudioFile">
          <hr>
          <b-form-group>
            Would you like to add text to support the audio?
            <span @click="showSolutionTextForm = true">
              <b-form-radio class="custom-control-inline" name="show-text-solution-form">Yes</b-form-radio>
            </span>
            <span @click="closeAudioSolutionModal"> <b-form-radio class="custom-control-inline"
                                                                  name="show-text-solution-form"
            >No</b-form-radio>
            </span>
          </b-form-group>
        </div>
        <div v-if="showSolutionTextForm">
          <div class="pt-3 pb-3">
            <ckeditor v-model="solutionTextForm.solution_text"
                      :config="richEditorConfig"
                      :class="{ 'is-invalid': solutionTextForm.errors.has('solution_text') }"
                      @keydown="solutionTextForm.errors.clear('solution_text')"
            />
            <has-error :form="solutionTextForm" field="solution_text"/>
          </div>
          <div>
            <span class="float-right"><b-button variant="primary" @click="submitSolutionText"
            >Save Text</b-button></span>
          </div>
        </div>
      </div>

      <div v-if="solutionTypeIsPdfImage">
        <p>
          <span v-if="user.role === 2">Upload an entire PDF with one solution per page and let Adapt cut up the PDF for you. Or, upload one
            solution at a time. If you upload a full PDF, students will be able to both download a full solution key
            and download solutions on a per question basis.</span>
        </p>
        <p v-if="user.role === 2">
          <span class="font-italic"><span class="font-weight-bold">Important:</span> For best results, don't crop any of your pages.  In addition, please make sure that they are all oriented in the same direction.</span>
        </p>
        <b-form ref="form">
          <b-form-group v-show="user.role !== 3">
            <b-form-radio v-model="uploadLevel" name="uploadLevel" value="assignment"
                          @click="showCurrentFullPDF = true"
            >
              Upload
              <span v-if="user.role === 2">solutions from a single PDF that Adapt can cutup for you.</span>
              <span v-if="user.role !== 2">a PDF and let us know which page your submission is on.</span>
            </b-form-radio>
            <b-form-radio v-model="uploadLevel" name="uploadLevel" value="question">
              Upload individual
              <span v-if="user.role === 2">question solutions</span>
              <span v-if="user.role !== 2">question file submissions</span>
            </b-form-radio>
          </b-form-group>

          <div v-if="uploadLevel === 'assignment' && showCurrentFullPDF">
            <hr>
            <p>
              <span v-show="user.role === 2">Select a single page or a comma separated list of pages to submit as your solution to
                this question or </span>
              <span v-show="user.role === 3">Tell us which page your question submission starts on or
              </span>
              <a href="#" @click="showCurrentFullPDF = false">
                upload a new PDF</a>.
            </p>
            <p v-show="user.role === 3" class="font-italic">
              <span class="font-weight-bold">Important:</span>
              If your submission spans multiple pages, just enter the first page where the submission starts.
            </p>
            <b-container v-show="user.role === 2" class="mb-2">
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
                    <has-error :form="cutupsForm" field="chosen_cutups"/>
                  </b-col>
                  <b-col lg="8" class="ml-3">
                    <b-row>
                      <b-button class="mt-1" size="sm" variant="outline-primary"
                                @click="setCutupAsSolution(questions[currentPage-1].id)"
                      >
                        Set As Solution
                      </b-button>
                      <span v-show="settingAsSolution" class="ml-2">
                        <b-spinner small type="grow"/>
                        Processing your file...
                      </span>
                    </b-row>
                  </b-col>
                </b-form-row>
              </b-form-group>
            </b-container>
            <div v-show="user.role === 2" class="overflow-auto">
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

            <b-container v-show="user.role === 3" class="mb-2">
              <b-form-group
                id="page"
                label-cols-sm="4"
                label-cols-lg="4"
                label="My submission starts on page:"
                label-for="Page"
              >
                <b-form-row lg="12">
                  <b-col lg="2">
                    <b-form-input
                      id="name"
                      v-model="questionSubmissionPageForm.page"
                      lg="2"
                      type="text"
                      :class="{ 'is-invalid': questionSubmissionPageForm.errors.has('page') }"
                      @keydown="questionSubmissionPageForm.errors.clear('page')"
                    />
                    <has-error :form="questionSubmissionPageForm" field="page"/>
                  </b-col>
                  <b-col lg="8" class="ml-3">
                    <b-row>
                      <b-button class="mt-1" size="sm" variant="outline-primary"
                                @click="setPageAsSubmission(questions[currentPage-1].id)"
                      >
                        Set As Question File Submission
                      </b-button>
                      <span v-show="settingAsSolution" class="ml-2">
                        <b-spinner small type="grow"/>
                        Processing your file...
                      </span>
                    </b-row>
                  </b-col>
                </b-form-row>
              </b-form-group>
            </b-container>

            <div v-if="showCurrentFullPDF && cutups.length && cutups[currentCutup-1]">
              <b-embed
                type="iframe"
                aspect="16by9"
                :src="cutups[currentCutup-1].temporary_url"
                allowfullscreen
              />
            </div>
            <div v-if="fullPdfUrl">
              <b-embed
                :key="questionSubmissionPageForm.page"
                type="iframe"
                aspect="16by9"
                :src="getFullPdfUrlAtPage(fullPdfUrl, questionSubmissionPageForm.page)"
                allowfullscreen
              />
            </div>
          </div>
          <b-container v-show="uploadLevel === 'assignment' && (!showCurrentFullPDF && (cutups.length || fullPdfUrl))">
            <b-row align-h="center">
              <b-button class="ml-2" size="sm" variant="outline-primary" @click="showCurrentFullPDF = true">
                Use Current PDF
              </b-button>
            </b-row>
          </b-container>
          <div v-show="uploadLevel === 'question' || !showCurrentFullPDF">
            <div class="example-drag">
              <div class="upload mt-3">
                <ul v-if="files.length && (preSignedURL !== '')">
                  <li v-for="file in files" :key="file.id">
                    <span :class="file.success ? 'text-success font-italic font-weight-bold' : ''">{{
                        file.name
                      }}</span> -
                    <span>{{ formatFileSize(file.size) }} </span>
                    <span v-if="file.size > 10000000" class="font-italic">Note: large files may take up to a minute to process.</span>
                    <span v-if="file.error" class="text-danger">Error: {{ file.error }}</span>
                    <span v-else-if="file.active" class="ml-2">
                      <b-spinner small type="grow"/>
                      Uploading File...
                    </span>
                    <span v-if="processingFile">
                      <b-spinner small type="grow"/>
                      Processing file...
                    </span>
                    <b-button v-if="!processingFile && (preSignedURL !== '') && (!$refs.upload || !$refs.upload.active)"
                              variant="success"
                              size="sm"
                              style="vertical-align: top"
                              @click.prevent="$refs.upload.active = true"
                    >
                      Start Upload
                    </b-button>
                  </li>
                </ul>
              </div>

              <input type="hidden" class="form-control is-invalid">
              <div class="help-block invalid-feedback">
                {{ uploadFileForm.errors.get(uploadFileType) }}
              </div>

              <b-container>
                <hr v-show="user.role !== 3">
                <b-row :align-h="user.role === 3 ? 'start' : 'end'">
                  <div style="vertical-align: bottom">
                    <span class="font-weight-bold font-italic mr-4">Accepted file types are: {{
                        getSolutionUploadTypes()
                      }}.</span>
                  </div>
                  <file-upload
                    ref="upload"
                    :key="fileUploadKey"
                    v-model="files"
                    put-action="/put.method"
                    @input-file="inputFile"
                    @input-filter="inputFilter"
                  >
                    <b-button variant="primary" size="sm" class="mr-3">
                      Select file
                    </b-button>
                  </file-upload>
                  <b-button class="mr-2" size="sm" @click="handleCancel">
                    Cancel
                  </b-button>
                </b-row>
              </b-container>
            </div>
          </div>
        </b-form>
      </div>
    </b-modal>
    <div v-if="inIFrame && showAssignmentInformation" class="text-center">
      <h5 v-if="(questions !==['init']) && canView">
        {{ name }}: Assessment {{ currentPage }} of {{ questions.length }}
      </h5>
    </div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="questions !==['init'] && !inIFrame">
        <PageTitle :title="title"/>
      </div>
      <div v-if="questions.length && !initializing && inIFrame && !showSubmissionInformation">
        <div
          v-show="(parseInt(questions[currentPage - 1].submission_count) === 0 || questions[currentPage - 1].late_question_submission) && latePolicy === 'marked late' && timeLeft === 0"
        >
          <b-alert variant="warning" show>
            <span class="alert-link">
              Your question submission will be marked late.</span>
          </b-alert>
        </div>
        <div v-show="parseInt(questions[currentPage - 1].submission_count) > 0" class="text-center">
          <b-alert variant="success" show>
            <span class="font-weight-bold">
              You have successfully submitted a response on  {{ questions[currentPage - 1].last_submitted }}.
              <span v-if="showScores">  You received a score of {{ questions[currentPage - 1].submission_score }}</span>.</span>
          </b-alert>
        </div>
      </div>
      <div v-if="user.role === 3 && showAssessmentClosedMessage">
        <b-alert variant="info" show>
          <span class="font-weight-bold">Assessment is closed. Contact the instructor for more details.</span>
        </b-alert>
      </div>
      <div v-if="questions.length && !initializing && !isLoading">
        <div v-show="isInstructorLoggedInAsStudent">
          <LoggedInAsStudent :student-name="user.first_name + ' ' + user.last_name"/>
        </div>
        <div v-if="isLocked() && !presentationMode">
          <b-alert variant="info" :show="true">
            <strong>This problem is locked. Since students have already submitted responses, you cannot update the
              points per question nor change the open-ended submission type.</strong>
          </b-alert>
        </div>
        <div v-if="questions.length">
          <div :class="assignmentInformationMarginBottom">
            <b-container>
              <b-col>
                <div v-if="isInstructor() && assessmentType === 'clicker'" class="mb-2 text-center font-italic">
                  <h5>
                    Presentation Mode:
                    <toggle-button
                      :width="60"
                      class="mt-2"
                      :value="presentationMode"
                      :sync="true"
                      size="lg"
                      :font-size="14"
                      :margin="4"
                      :color="{checked: '#28a745', unchecked: '#6c757d'}"
                      :labels="{checked: 'On', unchecked: 'Off'}"
                      @change="presentationMode = !presentationMode"
                    />
                  </h5>
                  <b-button variant="success" @click="startClickerAssessment">
                    GO!
                  </b-button>
                </div>
                <div v-if="isInstructor() && assessmentType !== 'clicker'" class="mb-2 text-center font-italic">
                  <span class="font-italic">Question View</span>
                  <toggle-button
                    :width="100"
                    class="mt-2"
                    :value="questionView === 'basic'"
                    :sync="true"
                    :font-size="14"
                    :margin="4"
                    :color="{checked: '#17a2b8', unchecked: '#6c757d'}"
                    :labels="{checked: 'Basic', unchecked: 'Advanced'}"
                    @change="toggleQuestionView()"
                  />
                </div>
                <div v-if="source === 'a' && !inIFrame ">
                  <div
                    v-if="!['clicker','learning tree'].includes(assessmentType) && (user.role !== 2) ||(user.role ===2 && questionView !== 'basic')"
                    class="text-center"
                  >
                    <h4>This assignment is worth {{ totalPoints.toString() }} points.</h4>
                  </div>
                  <div v-if="!isInstructor() && showPointsPerQuestion && assessmentType !== 'clicker'"
                       class="text-center"
                  >
                    <h5>
                      This question is worth
                      {{ 1 * (questions[currentPage - 1].points) }}
                      points.
                    </h5>
                  </div>
                  <div
                    v-if="!isInstructor() && showPointsPerQuestion && assessmentType === 'learning tree' && parseInt(questions[currentPage-1].answered_correctly_at_least_once)!==1"
                    class="text-center"
                  >
                    <span v-if="parseInt(questions[currentPage - 1].submission_count) <= 1" class="text-bold">
                      A penalty of
                      {{ submissionCountPercentDecrease }}% will applied for each attempt starting with the 3rd.
                    </span>
                    <span v-if="parseInt(questions[currentPage - 1].submission_count) > 1" class="text-bold text-info">
                      With the penalty, the maximum score that you can receive for this question is
                      {{
                        parseFloat(questions[currentPage - 1].points) * (100 - parseFloat(submissionCountPercentDecrease) * (parseFloat(questions[currentPage - 1].submission_count) - 1)) / 100
                      }}
                      points.</span>
                  </div>
                  <div
                    v-show="!isInstructor && (parseInt(questions[currentPage - 1].submission_count) === 0 || questions[currentPage - 1].late_question_submission) && latePolicy === 'deduction' && timeLeft === 0"
                    class="text-center"
                  >
                    <b-alert variant="warning" show>
                      <span class="alert-link">
                        This submission will be marked late.</span>
                    </b-alert>
                  </div>
                  <div v-if="isInstructor() && !presentationMode && questionView !== 'basic' " class="text-center">
                    <b-form-row>
                      <b-col/>
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
                        <has-error :form="questionPointsForm" field="points"/>
                      </b-col>
                      <h5 class="mt-1">
                        points.
                      </h5>
                      <b-col>
                        <div class="float-left">
                          <b-button variant="primary"
                                    size="sm"
                                    class="m-1"
                                    :disabled="isLocked()"
                                    @click="updatePoints(questions[currentPage-1].id)"
                          >
                            Update Points
                          </b-button>
                        </div>
                      </b-col>
                    </b-form-row>
                  </div>
                </div>
              </b-col>
              <b-row class="text-center font-italic">
                <b-col>
                  <div v-if="assessmentType === 'learning tree'">
                    <div v-if="parseInt(questions[currentPage - 1].submission_count) > 0">
                      <span class="font-italic">Attempt {{ questions[currentPage - 1].submission_count }} was submitted {{
                          questions[currentPage - 1].last_submitted
                        }}</span>
                    </div>
                    <span v-if="parseFloat(questions[currentPage - 1].late_penalty_percent) > 0 && showScores">
                      <span class="font-weight-bold">You had a late penalty of </span> {{
                        questions[currentPage - 1].late_penalty_percent
                      }}%
                    </span>
                  </div>
                  <div v-if="(!inIFrame && timeLeft>0) || (inIFrame && showAssignmentInformation && timeLeft>0)">
                    <countdown :time="timeLeft" @end="cleanUpClickerCounter">
                      <template slot-scope="props">
                        <span v-html="getTimeLeftMessage(props, assessmentType)"/>
                      </template>
                    </countdown>
                  </div>
                  <div v-if="isInstructor() && !presentationMode && questionView !== 'basic'" class="mt-1">
                    <b-button
                      variant="info"
                      size="sm"
                      @click="openModalShare()"
                    >
                      <b-icon icon="share"/>
                      Share
                    </b-button>
                  </div>
                  <div class="font-italic font-weight-bold">
                    <div v-if="user.role === 3 && showScores && isOpenEnded">
                      <p>
                        You achieved a total score of
                        {{ questions[currentPage - 1].total_score * 1 }}
                        out of a possible
                        {{ questions[currentPage - 1].points * 1 }} points.
                      </p>
                    </div>
                  </div>
                  <div v-if="showScores && showAssignmentStatistics && !isInstructor()">
                    <b-button variant="outline-primary" @click="openShowAssignmentStatisticsModal()">
                      View Question
                      Statistics
                    </b-button>
                  </div>
                  <div v-if="isInstructor() && !presentationMode">
                    <b-button class="mt-1 mb-2 mr-2"
                              variant="success"
                              size="sm"
                              @click="getAssessmentsForAssignment()"
                    >
                      Add Questions
                    </b-button>
                  </div>
                </b-col>
              </b-row>
            </b-container>

            <b-modal v-model="showAssignmentStatisticsModal" size="xl" title="Question Level Statistics">
              <b-container>
                <b-row v-if="showAssignmentStatistics && loaded && user.role === 3">
                  <b-col>
                    <b-card header="default" header-html="<h6 class=&quot;font-weight-bold&quot;>Summary</h6>">
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
              v-if="!inIFrame && (assessmentType !== 'clicker' || (isInstructor() && !presentationMode) || pastDue)"
              v-model="currentPage"
              :total-rows="questions.length"
              :per-page="perPage"
              limit="25"
              first-number
              last-number
              align="center"
              @input="changePage(currentPage)"
            />
          </div>
          <div v-if="assessmentType === 'learning tree'">
            <b-alert variant="success" :show="parseInt(questions[currentPage - 1].submission_count) > 0">
              <span class="font-weight-bold">You achieved a score of {{
                  questions[currentPage - 1].submission_score
                }} point<span v-if="parseInt(questions[currentPage - 1].submission_score) !== 1">s</span>.</span>
            </b-alert>
          </div>
          <div v-if="isInstructor() && !presentationMode" class="d-flex flex-row">
            <div class="p-2">
              <b-button v-if="questionView !== 'basic'"
                        class="mt-1 mb-2"
                        variant="primary"
                        size="sm"
                        @click="editQuestionSource(currentPage)"
              >
                Edit Question Source
              </b-button>
              <b-button class="mt-1 mb-2"
                        variant="danger"
                        size="sm"
                        @click="openRemoveQuestionModal()"
              >
                Remove Question
              </b-button>
            </div>
            <div v-if="openEndedSubmissionTypeAllowed" class="p-2">
              <span class="font-italic">Open-Ended Submission Type:</span>
              <b-form-select v-model="openEndedSubmissionType"
                             :options="compiledPDF ? openEndedSubmissionCompiledPDFTypeOptions : openEndedSubmissionTypeOptions"
                             style="width:100px"
                             class="mt-1"
                             size="sm"
                             @change="updateOpenEndedSubmissionType(questions[currentPage-1].id)"
              />
            </div>
            <div v-if="questionView !== 'basic'" class="p-2">
              <b-button
                class="mt-1 mb-2 ml-1"
                variant="dark"
                size="sm"
                @click="openUploadSolutionModal(questions[currentPage-1])"
              >
                Upload Solution
              </b-button>
              <span v-if="questions[currentPage-1].solution">
                <span v-if="!showUploadedAudioSolutionMessage">
                  <SolutionFileHtml :key="savedText" :questions="questions" :current-page="currentPage"
                                    :assignment-name="name"
                  />

                  <span v-if="showUploadedAudioSolutionMessage"
                        :class="uploadedAudioSolutionDataType"
                  >
                    {{ uploadedAudioSolutionDataMessage }}</span>
                </span>
                <span v-if="!questions[currentPage-1].solution">No solutions have been uploaded.</span>
              </span>
            </div>
          </div>
          <b-container
            v-if="assessmentType === 'learning tree' && learningTreeAsList.length && !answeredCorrectlyOnTheFirstAttempt"
            class="mb-2"
          >
            <b-row>
              <b-col cols="4">
                <b-button class="mr-2" variant="primary" size="sm" @click="showRootAssessment">
                  Root Assessment
                </b-button>
                <toggle-button
                  :width="160"
                  class="mt-2"
                  :value="showPathwayNavigator"
                  :sync="true"
                  size="lg"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#467fd0', unchecked: '#28a745'}"
                  :labels="{checked: 'Pathway Navigator', unchecked: 'Learning Tree'}"
                  @change="togglePathwayNavigatorLearningTree"
                />
              </b-col>
              <b-col>
                <b-alert :variant="submissionDataType" :show="showSubmissionMessage">
                  <span class="font-weight-bold">{{ submissionDataMessage }}</span>
                </b-alert>
                <b-alert :show="timerSetToGetLearningTreePoints && !showLearningTreePointsMessage" variant="info">
                  <countdown :time="timeLeftToGetLearningTreePoints" @end="updateExploredLearningTree">
                    <template slot-scope="props">
                      <span class="font-weight-bold">  Explore the Learning Tree for {{ props.minutes }} minutes, {{
                          props.seconds
                        }} seconds, then re-submit.
                      </span>
                    </template>
                  </countdown>
                </b-alert>
                <b-alert variant="info" :show="!showSubmissionMessage &&
                  !(Number(questions[currentPage - 1].learning_tree_exploration_points) > 0 ) &&
                  !timerSetToGetLearningTreePoints && showLearningTreePointsMessage
                  && (user.role === 3)"
                >
                  <span class="font-weight-bold"> Try again and you will receive
                    {{ (percentEarnedForExploringLearningTree / 100) * (questions[currentPage - 1].points) }} point<span
                      v-if="(this.percentEarnedForExploringLearningTree / 100) * (questions[currentPage - 1].points)>1"
                    >s</span> just for exploring the Learning
                    Tree.</span>
                </b-alert>
                <b-alert variant="info"
                         :show="!showSubmissionMessage && showDidNotAnswerCorrectlyMessage && !timerSetToGetLearningTreePoints"
                >
                  <span class="font-weight-bold">Explore the Learning Tree, and then you can try again!</span>
                </b-alert>
              </b-col>
            </b-row>
          </b-container>
          <b-container v-if="assessmentType === 'learning tree' && showLearningTree">
            <iframe
              allowtransparency="true"
              frameborder="0"
              :src="learningTreeSrc"
              style="width: 1200px;min-width: 100%;height:800px"
            />
          </b-container>
          <b-container v-if="!showLearningTree">
            <b-row>
              <b-col :cols="questionCol">
                <div v-if="assessmentType === 'clicker'">
                  <b-alert show :variant="clickerMessageType">
                    <span class="font-weight-bold">{{ clickerMessage }}</span>
                  </b-alert>
                </div>

                <div v-if="showQuestion">
                  <div class="border border-gray p-0">
                    <div>
                      <iframe v-show="questions[currentPage-1].non_technology"
                              :id="`non-technology-iframe-${currentPage}`"
                              allowtransparency="true"
                              frameborder="0"
                              :src="questions[currentPage-1].non_technology_iframe_src"
                              style="width: 1px;min-width: 100%;"
                      />
                    </div>
                    <div v-if="!(user.role === 3 && clickerStatus === 'neither_view_nor_submit')"
                         v-html="questions[currentPage-1].technology_iframe"
                    />
                  </div>
                  <div v-if="assessmentType === 'clicker'">
                    <b-alert :variant="submissionDataType" :show="showSubmissionMessage">
                      <span class="font-weight-bold">{{ submissionDataMessage }}</span>
                    </b-alert>
                  </div>
                  <div>
                    <div class="vld-parent">
                      <loading
                        :active.sync="isLoadingPieChart"
                        :can-cancel="true"
                        :is-full-page="false"
                        :width="128"
                        :height="128"
                        color="#007BFF"
                        background="#FFFFFF"
                      />
                      <div v-if="assessmentType === 'clicker' && user.role === 3 && piechartdata" class="text-center">
                        <hr>
                        <h5 v-if="correctAnswer" class="font-italic">
                          The correct answer is "{{ correctAnswer }}"
                        </h5>
                        <pie-chart :key="currentPage" :chartdata="piechartdata"
                                   @pieChartLoaded="updateIsLoadingPieChart"
                        />
                      </div>
                    </div>
                    <div v-if="['rich text', 'plain text'].includes(openEndedSubmissionType) && user.role === 2">
                      <div class="mt-3">
                        <b-card header-html="<h6 class=&quot;font-weight-bold&quot;>Default Text</h6>">
                          <p>
                            You can add default text for your students to see in their own text editors when they
                            attempt this question.
                          </p>
                          <ckeditor
                            :key="questions[currentPage-1].id"
                            v-model="openEndedDefaultTextForm.open_ended_default_text"
                            :config="richEditorConfig"
                            :class="{ 'is-invalid': openEndedDefaultTextForm.errors.has('open_ended_default_text') }"
                            @keydown="openEndedDefaultTextForm.errors.clear('open_ended_default_text')"
                            @namespaceloaded="onCKEditorNamespaceLoaded"
                          />
                          <has-error :form="openEndedDefaultTextForm" field="open_ended_default_text"/>
                        </b-card>
                        <b-container class="mt-2">
                          <b-row align-h="end">
                            <b-button variant="primary" size="sm" @click="submitDefaultOpenEndedText">
                              Update Default Text
                            </b-button>
                          </b-row>
                        </b-container>
                      </div>
                    </div>
                    <div v-if="isOpenEndedTextSubmission && user.role === 3">
                      <div class="mt-3">
                        <ckeditor
                          ref="textSubmissionEditor"
                          :key="questions[currentPage-1].id"
                          v-model="textSubmissionForm.text_submission"
                          :config="questions[currentPage-1].open_ended_text_editor === 'rich' ? richEditorConfig: plainEditorConfig"
                          @ready="editorReady"
                          @namespaceloaded="onCKEditorNamespaceLoaded"
                        />
                      </div>
                      <b-container class="mt-2 mb-3">
                        <b-row align-h="end">
                          <b-button v-if="questions[currentPage-1].open_ended_default_text"
                                    v-b-modal.modal-reset-to-default-text
                                    variant="danger"
                                    size="sm"
                                    class="mr-2"
                          >
                            Reset Default Text
                          </b-button>
                          <b-button variant="primary"
                                    size="sm"
                                    @click="submitText"
                          >
                            Submit
                          </b-button>
                        </b-row>
                      </b-container>
                    </div>
                    <div v-if="isOpenEndedAudioSubmission && user.role === 3">
                      <audio-recorder
                        ref="uploadRecorder"
                        :key="questions[currentPage-1].id"
                        class="m-auto"
                        :upload-url="audioUploadUrl"
                        :time="1"
                        :successful-upload="submittedAudioUpload"
                        :failed-upload="failedAudioUpload"
                      />
                    </div>
                  </div>
                </div>
                <iframe
                  v-if="!showQuestion" v-show="iframeLoaded"
                  :id="remediationIframeId"
                  allowtransparency="true"
                  frameborder="0"
                  :src="remediationSrc"
                  style="width: 1px;min-width: 100%;" @load="showIframe(remediationIframeId)"
                />
              </b-col>
              <b-col v-if="assessmentType === 'clicker' && piechartdata && user.role === 2">
                <div>
                  <div class="vld-parent">
                    <loading
                      :active.sync="isLoadingPieChart"
                      :can-cancel="true"
                      :is-full-page="false"
                      :width="128"
                      :height="128"
                      color="#007BFF"
                      background="#FFFFFF"
                    />

                    <div v-if="!isLoadingPieChart" class="font-italic">
                      <b-form-row v-if="!presentationMode">
                        <b-form-group
                          id="submission_time"
                          label-cols-sm="4"
                          label-cols-lg="5"
                          label="Time To Submit:"
                          label-for="Time To Submit"
                        >
                          <b-form-input
                            id="time_to_submit"
                            v-model="clickerTimeForm.time_to_submit"
                            type="text"
                            placeholder=""
                            :class="{ 'is-invalid': clickerTimeForm.errors.has('time_to_submit') }"
                            @keydown="clickerTimeForm.errors.clear('time_to_submit')"
                          />
                          <has-error :form="clickerTimeForm" field="time_to_submit"/>
                        </b-form-group>
                        <b-col>
                          <b-button variant="success" @click="startClickerAssessment">
                            GO!
                          </b-button>
                        </b-col>
                      </b-form-row>
                      <div class="text-center">
                        <hr>
                        <h4>{{ responsePercent }}% of students have responded</h4>
                        <h5 v-if="responsePercent">
                          The correct answer is "{{ correctAnswer }}"
                        </h5>
                      </div>
                    </div>
                    <pie-chart :key="currentPage" :chartdata="piechartdata" @pieChartLoaded="updateIsLoadingPieChart"/>
                  </div>
                </div>
              </b-col>
              <b-col
                v-if="assessmentType !== 'clicker' && showAssignmentStatistics && loaded && user.role === 2"
                cols="4"
              >
                <b-card header="default" header-html="<h6 class=&quot;font-weight-bold&quot;>Question Statistics</h6>"
                        class="mb-2"
                >
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
              <b-col
                v-if="(user.role === 3) && (assessmentType !== 'clicker') && showSubmissionInformation"
                cols="4"
              >
                <b-row
                  v-if="assessmentType === 'learning tree' && learningTreeAsList.length && !answeredCorrectlyOnTheFirstAttempt"
                >
                  <b-card header="default" header-html="<h6 class=&quot;font-weight-bold&quot;>Pathway Navigator</h6>"
                          class="sidebar-card mb-2"
                  >
                    <b-card-text>
                      <div v-if="previousNode.title">
                        <b-row align-h="center" class="p-2">
                          <a href=""
                             @click.prevent="explore(previousNode.library, previousNode.pageId, previousNode.id)"
                          >{{
                              previousNode.title
                            }}</a>
                        </b-row>
                        <b-row align-h="center">
                          <b-icon icon="arrow-down-square-fill" variant="success"/>
                        </b-row>
                      </div>
                      <b-row align-h="center" class="p-2">
                        <span class="font-weight-bold font-italic text-muted">{{
                            activeNode.title
                          }}</span>
                      </b-row>
                      <div v-if="futureNodes.length>0">
                        <b-row align-h="center">
                          <b-icon icon="arrow-down-square-fill" variant="success"/>
                        </b-row>
                        <b-row class="p-2">
                          <b-col v-for="remediationObject in futureNodes" :key="remediationObject.id"
                                 class="border border-primary rounded m-1"
                          >
                            <a href=""
                               @click.prevent="explore(remediationObject.library, remediationObject.pageId, remediationObject.id)"
                            >{{ remediationObject.title }}</a>
                          </b-col>
                        </b-row>
                      </div>
                    </b-card-text>
                  </b-card>
                </b-row>
                <b-row v-if="assessmentType !== 'learning tree' && questions[currentPage-1].technology_iframe">
                  <b-card header="default"
                          header-html="<h6 class=&quot;font-weight-bold&quot;>Auto-Graded Submission Information</h6>"
                          class="sidebar-card"
                  >
                    <b-card-text>
                      <span
                        v-show="(parseInt(questions[currentPage - 1].submission_count) === 0 || questions[currentPage - 1].late_question_submission) && latePolicy === 'marked late' && timeLeft === 0"
                      >
                        <b-alert variant="warning" show>
                          <span class="alert-link">
                            Your question submission will be marked late.</span>
                        </b-alert>
                      </span>
                      <span v-if="!questions[currentPage-1].open_ended_submission_type">
                        <span class="font-weight-bold">Solution: </span><SolutionFileHtml :questions="questions"
                                                                                          :current-page="currentPage"
                                                                                          :assignment-name="name"
                      /><br>
                      </span>
                      <span v-if="assessmentType==='learning tree'">
                        <span class="font-weight-bold">Number of attempts: </span>
                        {{
                          questions[currentPage - 1].submission_count
                        }}<br></span>

                      <span class="font-weight-bold">Submission:</span>
                      <span
                        :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                      >{{
                          questions[currentPage - 1].student_response
                        }}</span> <br>
                      <span class="font-weight-bold">Submitted At:</span>
                      <span
                        :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                      >{{
                          questions[currentPage - 1].last_submitted
                        }} </span>
                      <font-awesome-icon v-show="questions[currentPage - 1].last_submitted !== 'N/A'"
                                         class="text-success"
                                         :icon="checkIcon"
                      />
                      <br>
                      <div v-if="showScores">
                        <span class="font-weight-bold">Score:</span> {{
                          questions[currentPage - 1].submission_score
                        }}<br>
                        <strong>Z-Score:</strong> {{ questions[currentPage - 1].submission_z_score }}<br>
                      </div>
                      <div v-if="parseFloat(questions[currentPage - 1].late_penalty_percent) > 0 && showScores">
                        <span class="font-weight-bold">Late Penalty:</span> {{
                          questions[currentPage - 1].late_penalty_percent
                        }}%<br>
                      </div>

                      <b-alert :variant="submissionDataType"
                               :show="assessmentType!== 'learning tree' && showSubmissionMessage"
                      >
                        <span class="font-weight-bold">{{ submissionDataMessage }}</span>
                      </b-alert>
                    </b-card-text>
                  </b-card>
                </b-row>
                <b-row v-if="isOpenEnded && (user.role === 3)"
                       :class="{ 'mt-3': questions[currentPage-1].technology_iframe, 'mb-3': true }"
                >
                  <b-card header="Default" :header-html="getOpenEndedTitle()" class="sidebar-card">
                    <b-card-text>
                      <span
                        v-if="(!questions[currentPage-1].submission_file_exists ||questions[currentPage-1].late_file_submission) && latePolicy === 'marked late' && timeLeft === 0"
                      >
                        <b-alert variant="warning" show>
                          <a href="#" class="alert-link">Your {{ openEndedSubmissionType }} submission will be marked late.</a>
                        </b-alert>
                        <br>
                      </span>
                      <span v-if="isOpenEndedFileSubmission || isOpenEndedAudioSubmission">
                        <strong> Uploaded file:</strong>
                        <span v-if="questions[currentPage-1].submission_file_exists">
                          <a
                            :href="questions[currentPage-1].submission_file_url"
                            target="”_blank”"
                          >
                            View Submission
                          </a>
                        </span>
                        <span v-if="!questions[currentPage-1].submission_file_exists" class="text-danger">
                          No files have been uploaded.</span><br>
                      </span>
                      <strong>Submitted At:</strong>
                      <span
                        :class="{ 'text-danger': questions[currentPage - 1].date_submitted === 'N/A' }"
                      >{{ questions[currentPage - 1].date_submitted }}</span>
                      <font-awesome-icon v-show="questions[currentPage - 1].date_submitted !== 'N/A'"
                                         class="text-success"
                                         :icon="checkIcon"
                      />
                      <br>
                      <div v-if="solutionsReleased">
                        <span class="font-weight-bold">Solution: </span>
                        <SolutionFileHtml :questions="questions" :current-page="currentPage" :assignment-name="name"/>
                      </div>
                      <br>
                      <span v-if="showScores">
                        <strong>Date Graded:</strong> {{ questions[currentPage - 1].date_graded }}<br>
                      </span>
                      <span v-if="showScores">
                        <span v-if="questions[currentPage-1].file_feedback">
                          <strong>{{ capitalize(questions[currentPage - 1].file_feedback_type) }} Feedback:</strong>
                          <a :href="questions[currentPage-1].file_feedback_url"
                             target="”_blank”"
                          >
                            {{
                              questions[currentPage - 1].file_feedback_type === 'audio' ? 'Listen To Feedback' : 'View Feedback'
                            }}
                          </a>
                          <br>
                        </span>
                        <strong>Comments:</strong> <span v-html="questions[currentPage - 1].text_feedback"/><br>

                        <strong>Score:</strong> {{ questions[currentPage - 1].submission_file_score }}
                        <span v-if="questions[currentPage - 1].grader_id">
                          <b-button size="sm" variant="outline-primary"
                                    @click="openContactGraderModal( questions[currentPage - 1].grader_id)"
                          >Contact Grader</b-button>
                        </span>
                        <br>
                        <strong>Z-Score:</strong> {{ questions[currentPage - 1].submission_file_z_score }}<br>
                      </span>
                      <div v-if="isOpenEndedFileSubmission">
                        <hr>
                        <b-container>
                          <b-row v-show="!compiledPDF" class="mt-2 mr-2" align-h="end">
                            <b-button variant="primary"

                                      size="sm"
                                      @click="openUploadFileModal(questions[currentPage-1].id)"
                            >
                              Upload New File
                            </b-button>
                          </b-row>
                          <b-row v-show="(compiledPDF || bothFileUploadMode) && user.role === 3" class="mt-2">
                            <span class="font-italic">
                              {{ bothFileUploadMode ? 'Optionally' : 'Please' }}, upload your compiled PDF on the assignment's <router-link
                              :to="{ name: 'students.assignments.summary', params: { assignmentId: assignmentId }}"
                            >summary page</router-link>.
                            </span>
                          </b-row>
                        </b-container>
                      </div>
                      <b-alert :variant="openEndedSubmissionDataType" :show="showOpenEndedSubmissionMessage">
                        <span class="font-weight-bold">{{ openEndedSubmissionDataMessage }}</span>
                      </b-alert>
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
          <span class="alert-link">
            <span v-show="source === 'a'">This assignment currently has no assessments.</span>
            <span v-show="source === 'x'">This is an external assignment.  Please contact your instructor for more information.</span>
          </span>
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
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'

import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'

import { ToggleButton } from 'vue-js-toggle-button'

import { getAcceptedFileTypes, submitUploadFile } from '~/helpers/UploadFiles'
import { h5pResizer } from '~/helpers/H5PResizer'

import { isLocked } from '~/helpers/Assignments'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

import { downloadSolutionFile, downloadSubmissionFile, getFullPdfUrlAtPage } from '~/helpers/DownloadFiles'
import { doCopy } from '~/helpers/Copy'

import Email from '~/components/Email'
import Scores from '~/components/Scores'
import EnrollInCourse from '~/components/EnrollInCourse'
import { getScoresSummary } from '~/helpers/Scores'
import CKEditor from 'ckeditor4-vue'

import PieChart from '~/components/PieChart'
import SolutionFileHtml from '~/components/SolutionFileHtml'

import libraries from '~/helpers/Libraries'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { faTree } from '@fortawesome/free-solid-svg-icons'
import RemoveQuestion from '~/components/RemoveQuestion'

import Vue from 'vue'
import { faThumbsUp, faCheck } from '@fortawesome/free-solid-svg-icons'

import LoggedInAsStudent from '~/components/LoggedInAsStudent'

Vue.prototype.$http = axios // needed for the audio player

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)

export default {
  middleware: 'auth',
  components: {
    FontAwesomeIcon,
    EnrollInCourse,
    Scores,
    Email,
    Loading,
    ToggleButton,
    SolutionFileHtml,
    PieChart,
    RemoveQuestion,
    ckeditor: CKEditor.component,
    FileUpload: VueUploadComponent,
    LoggedInAsStudent
  },
  data: () => ({
    isInstructorLoggedInAsStudent: false,
    completedAllAssignmentQuestions: false,
    checkIcon: faCheck,
    thumbsUpIcon: faThumbsUp,
    bothFileUploadMode: false,
    compiledPDF: false,
    fullPdfUrl: '',
    handledOK: false,
    fileUploadKey: 1,
    preSignedURL: '',
    files: [],
    s3Key: '',
    questionView: 'basic',
    copyIcon: faCopy,
    treeIcon: faTree,
    technologySrc: '',
    pageId: '',
    adaptId: '',
    ckeditor: {},
    isLoading: true,
    answeredCorrectlyOnTheFirstAttempt: false,
    showPathwayNavigator: true,
    showLearningTree: false,
    activeId: 0,
    activeNode: {},
    previousNode: {},
    futureNodes: [],
    learningTreeSrc: '',
    assignmentInformationMarginBottom: 'mb-3',
    showSubmissionInformation: true,
    showAssignmentInformation: true,
    showAttribution: true,
    showInvalidAssignmentMessage: false,
    presentationMode: false,
    defaultClickerTimeToSubmit: null,
    libraryText: '',
    libraryOptions: libraries,
    pastDue: false,
    clickerStatus: '',
    countDownKey: 0,
    savedText: 1,
    showSolutionTextForm: false,
    showAddTextToSupportTheAudioFile: false,
    responsePercent: '',
    isLoadingPieChart: true,
    correctAnswer: null,
    piechartdata: [],
    clickerPollingSetInterval: null,
    clickerMessage: '',
    clickerMessageType: '',
    showAssessmentClosedMessage: false,
    uploadedAudioSolutionDataType: '',
    showUploadedAudioSolutionMessage: false,
    uploadedAudioSolutionDataMessage: '',
    solutionFileHtml: '',
    openEndedSubmissionDataType: '',
    showOpenEndedSubmissionMessage: false,
    openEndedSubmissionDataMessage: '',
    solutionTypeIsPdfImage: true,
    audioSolutionUploadUrl: '',
    audioUploadUrl: '',
    shownSections: '',
    iFrameAssignmentInformation: true,
    iFrameSubmissionInformation: true,
    iFrameAttribution: true,
    inIFrame: false,
    editorData: '<p>Content of the editor.</p>',
    plainEditorConfig: {
      toolbar: [],
      removePlugins: 'elementspath',
      resize_enabled: false
    },
    richEditorConfig: {
      toolbar: [
        { name: 'clipboard', items: ['Cut', 'Copy', '-', 'Undo', 'Redo'] },
        {
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']
        },
        {
          name: 'paragraph',
          items: ['NumberedList', 'BulletedList', '-', 'Indent', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
        },
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'insert', items: ['Table', 'HorizontalRule', 'Smiley', 'SpecialChar'] },
        { name: 'colors', items: ['TextColor', 'BGColor'] }
      ],
      removeButtons: '',
      height: 100
    },
    isOpenEnded: false,
    isOpenEndedFileSubmission: false,
    isOpenEndedTextSubmission: false,
    isOpenEndedAudioSubmission: false,

    responseText: '',
    openEndedSubmissionTypeOptions: [
      { value: 'rich text', text: 'Rich Text' },
      { value: 'plain text', text: 'Plain Text' },
      { value: 'file', text: 'File' },
      { value: 'audio', text: 'Audio' },
      { value: 0, text: 'None' }
    ],
    openEndedSubmissionCompiledPDFTypeOptions: [
      { value: 'file', text: 'PDF' },
      { value: 0, text: 'None' }
    ],
    showDidNotAnswerCorrectlyMessage: false,
    embedCode: '',
    canView: false,
    latePolicy: '',
    learningTreePercentPenalty: 0,
    submissionCountPercentDecrease: 0,
    capitalFormattedAssessmentType: '',
    assessmentType: '',
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
    showCurrentFullPDF: false,
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
    processingFile: false,
    questionSubmissionPageForm: new Form({
      page: ''
    }),
    clickerTimeForm: new Form({
      time_to_submit: ''
    }),
    solutionTextForm: new Form({
      solution_text: ''
    }),
    openEndedDefaultTextForm: new Form({
      open_ended_default_text: 'Enter text that you would like to appear when your student sees the text submissions area.'
    }),
    textSubmissionForm: new Form({
      text_submission: '',
      assignmentId: null,
      questionId: null
    }),
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
    openEndedSubmissionTypeAllowed: false,
    openEndedSubmissionType: 'text',
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
    currentUrl: '',
    currentCutup: 1,
    questions: [],
    initializing: true, // use to show a blank screen until all is loaded
    title: '',
    assignmentId: '',
    name: '',
    questionId: false,
    originalOpenEndedSubmissionType: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  watch: {
    openEndedSubmissionType: function (newVal, oldVal) {
      this.originalOpenEndedSubmissionType = oldVal
    }
  },
  created () {
    this.doCopy = doCopy
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
    h5pResizer()
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.downloadSolutionFile = downloadSolutionFile
    this.downloadSubmissionFile = downloadSubmissionFile
    this.getFullPdfUrlAtPage = getFullPdfUrlAtPage
    this.isLocked = isLocked
    window.addEventListener('keydown', this.arrowListener)
  },
  destroyed () {
    window.removeEventListener('keydown', this.arrowListener)
  },
  async mounted () {
    this.isLoading = true

    this.uploadFileType = (this.user.role === 2) ? 'solution' : 'submission' // students upload question submissions and instructors upload solutions
    this.uploadFileUrl = (this.user.role === 2) ? '/api/solution-files' : '/api/submission-files'

    this.assignmentId = this.$route.params.assignmentId
    this.questionId = this.$route.params.questionId
    this.shownSections = this.$route.params.shownSections
    this.canView = await this.getAssignmentInfo()
    if (!this.canView) {
      this.isLoading = false
      return false
    }
    if (this.inIFrame) {
      if (!this.shownSections) {
        this.showSubmissionInformation = false
        this.showAssignmentInformation = false
        this.showAttribution = false
      } else {
        this.showSubmissionInformation = this.shownSections.includes('submissionInformation')
        this.showAssignmentInformation = this.shownSections.includes('assignmentInformation')
        this.showAttribution = this.shownSections.includes('attribution')
      }
      if (!this.showAssignmentInformation) {
        this.assignmentInformationMarginBottom = 'mb-0'
      }
    }
    this.questionCol = this.assessmentType === 'clicker' || !this.showSubmissionInformation ? 12 : 8

    if (this.source === 'a') {
      await this.getSelectedQuestions(this.assignmentId, this.questionId)
      if (!this.questions.length) {
        this.isLoading = false
        return false
      }
      this.showAssignmentStatistics = this.questions.length && (this.user.role === 2 || (this.user.role === 3 && this.students_can_view_assignment_statistics))
      if (this.showAssignmentStatistics) {
        this.getScoresSummary = getScoresSummary
      }
      if (this.questionId) {
        this.currentPage = this.getInitialCurrentPage(this.questionId)
      } else {
        this.questionId = this.questions[0].id
      }
      await this.changePage(this.currentPage)
      if (this.user.role === 2) {
        await this.getCutups(this.assignmentId)
      }
      window.addEventListener('message', this.receiveMessage, false)
    }
  },
  beforeDestroy () {
    window.removeEventListener('message', this.receiveMessage)
    if (this.clickerPollingSetInterval) {
      clearInterval(this.clickerPollingSetInterval)
      this.clickerPollingSetInterval = null
    }
  },
  methods: {
    arrowListener (event) {
      if (event.key === 'ArrowRight' && this.currentPage < this.questions.length) {
        this.currentPage++
        this.changePage(this.currentPage)
      }
      if (event.key === 'ArrowLeft' && this.currentPage > 1) {
        this.currentPage--
        this.changePage(this.currentPage)
      }
    },
    checkIfAssignmentCompleted () {
      if (this.completedAllAssignmentQuestions) {
        this.$bvModal.show('modal-completed-assignment')
      }
    },
    async setPageAsSubmission (questionId) {
      try {
        console.log(this.questionSubmissionPageForm)
        const { data } = await this.questionSubmissionPageForm.patch(`/api/submission-files/${this.assignmentId}/${questionId}/page`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-upload-file')
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].submission = data.submission
          this.questions[this.currentPage - 1].original_filename = data.original_filename
          this.questions[this.currentPage - 1].date_graded = 'N/A'
          this.questions[this.currentPage - 1].file_feedback = 'N/A'
          this.questions[this.currentPage - 1].submission_file_exists = true
          this.questions[this.currentPage - 1].late_file_submission = data.late_file_submission
          this.questions[this.currentPage - 1].submission_file_url = data.submission_file_url
          this.questions[this.currentPage - 1].date_submitted = data.date_submitted
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    formatFileSize (size) {
      let sizes = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB']
      for (let i = 1; i < sizes.length; i++) {
        if (size < Math.pow(1024, i)) return (Math.round((size / Math.pow(1024, i - 1)) * 100) / 100) + sizes[i - 1]
      }
      return size
    },
    inputFile (newFile, oldFile) {
      if (newFile && oldFile && !newFile.active && oldFile.active) {
        // Get response data

        if (newFile.xhr) {
          //  Get the response status code
          console.log('status', newFile.xhr.status)
          if (newFile.xhr.status === 200) {
            if (!this.handledOK) {
              this.handledOK = true
              console.log(this.handledOK)
              this.handleOK()
            }
          } else {
            this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
          }
        } else {
          this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
        }
      }
    },

    async inputFilter (newFile, oldFile, prevent) {
      this.uploadFileForm.errors.clear()
      if (newFile && !oldFile) {
        // Filter non-image file
        if (parseInt(newFile.size) > 20000000) {
          let message = '20 MB max allowed.  Your file is too large.  '
          if (/\.(pdf)$/i.test(newFile.name)) {
            message += 'You might want to try an online PDF compressor such as https://smallpdf.com/compress-pdf to reduce the size.'
          }
          this.uploadFileForm.errors.set(this.uploadFileType, message)
          return prevent()
        }
        let validUploadTypesMessage = `The valid upload types are: ${this.getSolutionUploadTypes()}`

        let validExtension = this.uploadLevel === 'question'
          ? /\.(pdf|text|png|jpeg|jpg)$/i.test(newFile.name)
          : /\.(pdf)$/i.test(newFile.name)

        if (!validExtension) {
          this.uploadFileForm.errors.set(this.uploadFileType, validUploadTypesMessage)

          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              assignment_id: this.assignmentId,
              upload_file_type: this.uploadFileType,
              file_name: newFile.name
            }
            const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)
            if (data.type === 'error') {
              this.$noty.error(data.message)
              return false
            }
            this.preSignedURL = data.preSignedURL
            newFile.putAction = this.preSignedURL
            this.fileUploadKey++
            this.s3Key = data.s3_key
            this.originalFilename = newFile.name
            this.handledOK = false
          } catch (error) {
            this.$noty.error(error.message)
            return false
          }
        }
      }

      // Create a blob field
      newFile.blob = ''
      let URL = window.URL || window.webkitURL
      if (URL && URL.createObjectURL) {
        newFile.blob = URL.createObjectURL(newFile.file)
      }
    },
    async toggleQuestionView () {
      try {
        console.log(this.questionView)
        const { data } = await axios.patch(`/api/cookie/set-question-view/${this.questionView}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.questionView = (this.questionView === 'basic') ? 'expanded' : 'basic'
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    async submitResetDefaultOpenEndedText () {
      try {
        let questionId = this.questions[this.currentPage - 1].id
        const { data } = await axios.delete(`/api/submission-texts/${this.assignmentId}/${questionId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.questions[this.currentPage - 1].date_submitted = data.date_submitted
        this.questions[this.currentPage - 1].submission = this.textSubmissionForm.text_submission = this.questions[this.currentPage - 1].open_ended_default_text
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
      this.$bvModal.hide('modal-reset-to-default-text')
    },
    async submitDefaultOpenEndedText () {
      try {
        let questionId = this.questions[this.currentPage - 1].id
        const { data } = await this.openEndedDefaultTextForm.patch(`/api/assignments/${this.assignmentId}/questions/${questionId}/open-ended-default-text`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.questions[this.currentPage - 1].submission = this.openEndedDefaultTextForm.open_ended_default_text
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    editorReady () {
      if (this.questions[this.currentPage - 1].open_ended_text_editor === 'plain') {
        document.getElementsByClassName('cke_top')[0].style.display = 'none'
      }
    },
    togglePathwayNavigatorLearningTree () {
      this.showPathwayNavigator = !this.showPathwayNavigator
      this.showLearningTree = !this.showPathwayNavigator
      this.showQuestion = this.showPathwayNavigator
    },
    showRootAssessment () {
      this.showLearningTree = false
      this.showPathwayNavigator = true
      this.updateNavigator(0)
      this.viewOriginalQuestion()
    },
    cleanUpClickerCounter () {
      this.timeLeft = 0
      this.updateClickerMessage('view_and_not_submit')
    },
    getTimeLeftMessage (props, assessmentType) {
      let message = ''
      message = (assessmentType === 'clicker') ? 'Time Left: ' : 'Time Until Due: '
      let timeLeft = parseInt(this.timeLeft) / 1000

      if (timeLeft >= 60 * 60 * 24) {
        message += `${props.days} days, ${props.hours} hours,
          ${props.minutes} minutes, ${props.seconds} seconds.`
      } else if (timeLeft >= 60 * 60) {
        message += `${props.hours}  hours,
          ${props.minutes}   minutes, ${props.seconds} seconds.`
      } else if (timeLeft > 60) {
        message += `${props.minutes} minutes, ${props.seconds} seconds.`
      } else {
        message += `${props.seconds} seconds.`
      }
      if (assessmentType === 'clicker') {
        message = `<h4 class="font-italic">${message}</h4>`
      }
      return message
    },
    async startClickerAssessment () {
      try {
        const { data } = await this.clickerTimeForm.post(`/api/assignments/${this.assignmentId}/questions/${this.questions[this.currentPage - 1].id}/start-clicker-assessment`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.timeLeft = data.time_left
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    openShowAudioSolutionModal (event) {
      event.preventDefault()
      this.$bvModal.show('modal-show-audio-solution')
    },
    closeAudioSolutionModal () {
      this.solutionTextForm.solution_text = ''
      this.solutionTextForm.errors.clear()
      this.$bvModal.hide('modal-upload-file')
      this.$refs.recorder.removeRecord()
    },
    async submitSolutionText () {
      try {
        let questionId = this.questions[this.currentPage - 1].id
        this.solutionTextForm.question_id = questionId
        const { data } = await this.solutionTextForm.post(`/api/solutions/text/${this.assignmentId}/${questionId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }

        this.questions[this.currentPage - 1].solution_text = this.solutionTextForm.solution_text
        this.savedText = this.savedText + 1

        this.$refs.recorder.removeRecord()
        this.$bvModal.hide('modal-upload-file')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    updateIsLoadingPieChart () {
      this.isLoadingPieChart = false
    },
    editQuestionSource (currentPage) {
      window.open(this.questions[currentPage - 1].mindtouch_url)
    },
    openUploadSolutionModal (question) {
      this.audioSolutionUploadUrl = `/api/solution-files/audio/${this.assignmentId}/${question.id}`
      this.openUploadFileModal(question.id)
    },
    submittedAudioSolutionUpload (response) {
      let data = response.data
      this.uploadedAudioSolutionDataType = (data.type === 'success') ? 'font-weight-bold text-success' : 'font-weight-bold text-danger'
      this.uploadedAudioSolutionDataMessage = data.message
      this.showUploadedAudioSolutionMessage = true
      setTimeout(() => {
        this.showUploadedAudioSolutionMessage = false
      }, 3000)
      if (data.type === 'success') {
        this.questions[this.currentPage - 1].solution = data.solution
        this.questions[this.currentPage - 1].solution_type = 'audio'
        this.questions[this.currentPage - 1].solution_file_url = data.solution_file_url
        this.showSuccessfulAudioSubmissionMessage = true
        this.showAddTextToSupportTheAudioFile = true
      } else {
        this.$refs.recorder.removeRecord()
        this.$bvModal.hide('modal-upload-file')
        this.$noty.error(data.message)
      }
    },

    submittedAudioUpload (response) {
      let data = response.data
      this.openEndedSubmissionDataType = (data.type === 'success') ? 'success' : 'danger'
      this.submissionDataMessage = data.message
      let modalToShow = this.openEndedSubmissionDataType === 'success' ? 'modal-submission-accepted' : 'modal-thumbs-down'
      this.$bvModal.show(modalToShow)

      if (data.type === 'success') {
        this.questions[this.currentPage - 1].date_submitted = data.date_submitted
        this.questions[this.currentPage - 1].submission_file_url = data.submission_file_url
        this.questions[this.currentPage - 1].late_file_submission = data.late_file_submission
        this.questions[this.currentPage - 1].submission_file_exists = true
        this.completedAllAssignmentQuestions = data.completed_all_assignment_questions
      }
      this.$refs.uploadRecorder.removeRecord()
      this.$bvModal.hide('modal-upload-file')
    },
    failedAudioUpload (data) {
      this.$noty.error('We were not able to perform the upload.  Please try again or contact us for assistance.')
      axios.post('/api/submission-audios/error', JSON.stringify(data))
      this.$refs.recorder.removeRecord()
    },
    updateShare () {
      this.currentUrl = this.getCurrentUrl()
      this.embedCode = this.getEmbedCode()
    },
    openModalShare () {
      this.$bvModal.show('modal-share')
      this.currentUrl = this.getCurrentUrl()
      this.embedCode = this.getEmbedCode()
      this.libraryText = this.getLibraryText(this.questions[this.currentPage - 1].library)
      this.adaptId = `${this.assignmentId}-${this.questions[this.currentPage - 1].id}`
      this.pageId = this.questions[this.currentPage - 1].page_id

      let text
      this.technologySrc = ''
      if (this.questions[this.currentPage - 1].technology_src) {
        let url = new URL(this.questions[this.currentPage - 1].technology_src)
        let urlParams = new URLSearchParams(url.search)

        switch (this.questions[this.currentPage - 1].technology) {
          case ('webwork'):
            text = urlParams.get('sourceFilePath')
            this.technologySrc = `<a href="${this.questions[this.currentPage - 1].technology_src}" target="”_blank”" >webwork:${text}</a>`
            break
          case ('h5p'):
            text = urlParams.get('id')
            this.technologySrc = `<a href="${this.questions[this.currentPage - 1].technology_src}" target="”_blank”" >h5p:${text}</a>`
            break
          case ('imathas'):
            console.log(urlParams)
            text = urlParams.get('id')
            this.technologySrc = `<a href="${this.questions[this.currentPage - 1].technology_src}" target="”_blank”" >imathas:${text}</a>`
            break
          default:
            this.technologySrc = `Please Contact Us.  We have not yet implemented the sharing code for ${this.questions[this.currentPage - 1].technology}`
        }
      }
    },
    getLibraryText (library) {
      let text = library
      for (let i = 0; i < this.libraryOptions.length; i++) {
        if (library === this.libraryOptions[i].value) {
          text = this.libraryOptions[i].text
        }
      }
      return text
    },
    getEmbedCode () {
      return `<iframe id="adapt-${this.assignmentId}-${this.questions[this.currentPage - 1].id}" allowtransparency="true" frameborder="0" scrolling="no" src="${this.currentUrl}" style="width: 1px;min-width: 100%;min-height: 100px;" />`
    },
    getCurrentUrl () {
      let url = `${window.location.origin}/assignments/${this.assignmentId}/questions/view/${this.questions[this.currentPage - 1].id}`
      let extras = []
      if (this.iFrameAssignmentInformation) {
        extras.push('assignmentInformation')
      }
      if (this.iFrameSubmissionInformation) {
        extras.push('submissionInformation')
      }
      if (this.iFrameAttribution) {
        extras.push('attribution')
      }
      url += '/'
      if (extras.length) {
        for (let i = 0; i < extras.length; i++) {
          url += extras[i] + '-'
        }
      }
      return url.slice(0, -1)
    },
    getInitialCurrentPage (questionId) {
      for (let i = 1; i <= this.questions.length; i++) {
        if (parseInt(this.questions[i - 1].id) === parseInt(questionId)) {
          return i
        }
      }
    },
    capitalize (word) {
      return word.charAt(0).toUpperCase() + word.slice(1)
    },
    getOpenEndedTitle () {
      let openEndedSubmissionType = this.openEndedSubmissionType.includes('text') ? 'text' : this.openEndedSubmissionType
      let capitalizedTitle = this.capitalize(openEndedSubmissionType)
      return `<h6 class="font-weight-bold">${capitalizedTitle} Submission Information</h6>`
    },
    async submitText () {
      try {
        this.textSubmissionForm.questionId = this.questions[this.currentPage - 1].id
        this.textSubmissionForm.assignmentId = this.assignmentId
        const { data } = await this.textSubmissionForm.post('/api/submission-texts')
        this.submissionDataMessage = data.message
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].date_submitted = data.date_submitted
          this.questions[this.currentPage - 1].submission = this.textSubmissionForm.text_submission
          this.$bvModal.show('modal-submission-accepted')
          this.completedAllAssignmentQuestions = data.completed_all_assignment_questions
        } else {
          this.$bvModal.show('modal-thumbs-down')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateClickerMessage (clickerStatus) {
      if (this.user.role === 2) {
        this.clickerMessage = ''
        return false
      }
      switch (clickerStatus) {
        case ('view_and_submit'):
          this.clickerMessage = 'This assessment is open and submissions are being recorded.'
          this.clickerMessageType = 'success'
          break
        case ('view_and_not_submit'):
          this.clickerMessage = 'Submissions will not be saved.'
          this.clickerMessageType = 'info'
          break
        case ('neither_view_nor_submit'):
          this.clickerMessage = 'Please wait for your instructor to open this assessment for submission.'
          this.clickerMessageType = 'info'
      }
    },
    initClickerPolling () {
      this.isLoadingPieChart = true
      this.submitClickerPolling(this.questions[this.currentPage - 1].id)
      if (this.clickerPollingSetInterval) {
        clearInterval(this.clickerPollingSetInterval)
        this.clickerPollingSetInterval = null
      }
      const self = this
      this.clickerPollingSetInterval = setInterval(function () {
        self.submitClickerPolling(self.questions[self.currentPage - 1].id)
      }, 3000)
    },
    async submitClickerPolling (questionId) {
      try {
        const { data } = await axios.get(`/api/submissions/${this.assignmentId}/questions/${questionId}/pie-chart-data`)

        if (data.type !== 'error') {
          if (data.redirect_question && !this.pastDue) {
            // send students to the right page
            window.location = `/assignments/${this.assignmentId}/questions/view/${data.redirect_question}`
          }
          console.log(data)
          this.piechartdata = data.pie_chart_data
          this.correctAnswer = data.correct_answer
          this.responsePercent = data.response_percent
          this.clickerStatus = data.clicker_status
          this.updateClickerMessage(this.clickerStatus)
        } else {
          this.$noty.error(data.message)
          clearInterval(this.clickerPollingSetInterval)
          this.clickerPollingSetInterval = null
        }
      } catch (error) {
        this.$noty.error(error.message)
      }

      // this.chartdata = await this.getScoresSummary(this.assignmentId, `/api/scores/summary/${this.assignmentId}/${questionId}`)
    },
    async updateOpenEndedSubmissionType (questionId) {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/${questionId}/update-open-ended-submission-type`, { 'open_ended_submission_type': this.openEndedSubmissionType })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].open_ended_submission_type = this.openEndedSubmissionType
        } else {
          this.openEndedSubmissionType = this.originalOpenEndedSubmissionType
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.openEndedSubmissionType = this.originalOpenEndedSubmissionType
      }
    },
    getWindowLocation () {
      return window.location
    },
    async updateExploredLearningTree () {
      try {
        const { data } = await axios.patch(`/api/submissions/${this.assignmentId}/${this.questions[this.currentPage - 1].id}/explored-learning-tree`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
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
      let solutionType = this.solutionTypeIsPdfImage ? 'PDF/Image' : 'Audio'
      return this.user.role === 3 ? 'Upload File Submission' : `Upload ${solutionType} Solution`
    },
    getSolutionUploadTypes () {
      return this.uploadLevel === 'question' ? getAcceptedFileTypes() : getAcceptedFileTypes('.pdf')
    },
    async updateLastSubmittedAndLastResponse (assignmentId, questionId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/${questionId}/last-submitted-info`)

        this.questions[this.currentPage - 1]['last_submitted'] = data.last_submitted
        this.questions[this.currentPage - 1]['student_response'] = data.student_response
        this.questions[this.currentPage - 1]['submission_count'] = data.submission_count
        this.questions[this.currentPage - 1]['submission_score'] = data.submission_score
        this.questions[this.currentPage - 1]['late_penalty_percent'] = data.late_penalty_percent
        this.questions[this.currentPage - 1]['answered_correctly_at_least_once'] = data.answered_correctly_at_least_once
        this.questions[this.currentPage - 1]['late_question_submission'] = data.late_question_submission
        this.questions[this.currentPage - 1]['solution'] = data.solution
        this.completedAllAssignmentQuestions = data.completed_all_assignment_questions
        if (data.submission_count > 1) {
          // successfully made a submission so they don't need to know about the points for the learning tree anymore
          this.showLearningTreePointsMessage = false
        }
        // show initially if you made no attempts OR you've already visited the learning tree
        // if you made an attempt, hide the question until you visit the learning tree
        // only get additional points and with a penalty IF they get it all correct
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async receiveMessage (event) {
      if (this.user.role === 3) {
        let technology = this.getTechnology(event.origin)

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
          serverSideSubmit = ((technology === 'imathas' && JSON.parse(event.data).subject === 'lti.ext.imathas.result') ||
            (technology === 'webwork' && JSON.parse(event.data).subject === 'webwork.result'))
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

        if (serverSideSubmit) {
          let data = JSON.parse(event.data)
          console.log(data)
          if (technology === 'webwork' && data.status) {
            data.type = data.status < 300 ? 'success' : 'error'
            try {
              data = { ...data, ...JSON.parse(data.message) }
            } catch (error) {
              this.$noty.error(error.message)
              return false
            }
          }
          await this.showResponse(data)
        }
        if (clientSideSubmit) {
          let submissionData = {
            'submission': event.data,
            'assignment_id': this.assignmentId,
            'question_id': this.questions[this.currentPage - 1].id,
            'technology': technology
          }

          // if incorrect, show the learning tree stuff...
          try {
            this.hideResponse()
            const { data } = await axios.post('/api/submissions', submissionData)
            if (!data.message) {
              data.type = 'error'
              data.message = 'The server did not fully respond to this request and your submission may not have been saved.  Please refresh the page to verify the submission and contact support if the problem persists.'
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
      console.log(data)
      console.log(this.learningTree)
      if (data.learning_tree && !this.learningTree) {
        await this.getLearningTree(data.learning_tree)
        this.questions[this.currentPage - 1].learning_tree = data.learning_tree
      }
      this.submissionDataType = ['success', 'info'].includes(data.type) ? data.type : 'danger'

      this.submissionDataMessage = data.message
      this.learningTreePercentPenalty = data.learning_tree_percent_penalty

      if (this.submissionDataType !== 'danger') {
        if (data.learning_tree_message) {
          this.$bvModal.show('modal-learning-tree')
        } else if (data.not_updated_message) {
          this.$bvModal.show('modal-not-updated')
        } else {
          this.$bvModal.show('modal-submission-accepted')
        }
        await this.updateLastSubmittedAndLastResponse(this.assignmentId, this.questions[this.currentPage - 1].id)
      } else {
        this.$bvModal.show('modal-thumbs-down')
      }
    },
    getTechnology (body) {
      let technology
      if (body.includes('h5p.libretexts.org')) {
        technology = 'h5p'
      } else if (body.includes('imathas.libretexts.org')) {
        technology = 'imathas'
      } else if (body.includes('wwrenderer.libretexts.org') || body.includes('webwork.libretexts.org') || (body.includes('demo.webwork.rochester.edu'))) {
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
          if (data.update_points) {
            for (let i = 0; i < this.questions.length; i++) {
              this.questions[i].points = this.questionPointsForm.points
            }
          }
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async openUploadFileModal (questionId) {
      if (this.uploadFileType === 'submission') {
        this.uploadLevel = 'question'
        try {
          const { data } = await axios.post('/api/submission-files/can-submit-file-submission', {
            assignmentId: this.assignmentId,
            questionId: this.questionId
          })
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
        } catch (error) {
          this.$noty.error(error.message)
          return false
        }
      }
      this.$bvModal.show('modal-upload-file')
      this.questionSubmissionPageForm.errors.clear()
      this.questionSubmissionPageForm.page = ''
      this.uploadFileForm.errors.clear(this.uploadFileType)
      this.uploadFileForm.questionId = questionId
      this.uploadFileForm.assignmentId = this.assignmentId
      this.cutupsForm.chosen_cutups = ''
      this.cutupsForm.question_num = this.currentPage
      this.currentCutup = 1
    },
    async handleOK () {
      this.uploadFileForm.errors.clear(this.uploadFileType)
      this.uploadFileForm.uploadLevel = this.uploadLevel
      this.uploadFileForm.s3_key = this.s3Key
      this.uploadFileForm.original_filename = this.originalFilename
      // Prevent modal from closing
      // Trigger submit handler
      if (this.uploading) {
        this.$noty.info('Please be patient while the file is uploading.')
        return false
      }
      this.processingFile = true

      try {
        await this.submitUploadFile(this.uploadFileType, this.uploadFileForm, this.$noty, this.$nextTick, this.$bvModal, this.questions[this.currentPage - 1], this.uploadFileUrl, false)
      } catch (error) {
        this.$noty.error(error.message)
      }

      if (!this.uploadFileForm.errors.has(this.uploadFileType)) {
        this.user.role === 2 ? await this.getCutups(this.assignmentId)
          : this.showCurrentFullPDF = true
      }

      if (!this.uploadFileForm.errors.any() &&
        (this.uploadLevel === 'question' || !this.showCurrentFullPDF)) {
        this.questions[this.currentPage - 1].solution_type = 'q'
        this.$bvModal.hide(`modal-upload-file`)
      }

      this.processingFile = false
      this.files = []
    },
    handleCancel () {
      this.$refs.upload.active = false
      this.files = []
      this.processingFile = false
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
        this.learningTreeAsList[i].show = remediationObject.children.includes(this.learningTreeAsList[i].id)
      }
    },
    async changePage (currentPage) {
      if (this.user.role === 2) {
        this.title = `${this.questions[currentPage - 1].title}` ? this.questions[currentPage - 1].title : `Question #${currentPage - 1}`
      }
      this.clickerStatus = this.questions[currentPage - 1].clicker_status
      this.showSolutionTextForm = false
      this.showAddTextToSupportTheAudioFile = false
      if (this.assessmentType === 'clicker') {
        this.clickerTimeForm.time_to_submit = this.defaultClickerTimeToSubmit
        this.initClickerPolling()
        this.timeLeft = this.questions[this.currentPage - 1].clicker_time_left
        this.updateClickerMessage(this.clickerStatus)
      }
      if (this.assessmentType === 'learning tree') {
        this.learningTree = this.questions[this.currentPage - 1].learning_tree
        await this.getLearningTree(this.learningTree)
        this.showDidNotAnswerCorrectlyMessage = this.questions[this.currentPage - 1].submitted_but_did_not_explore_learning_tree
        this.answeredCorrectlyOnTheFirstAttempt = parseInt(this.questions[this.currentPage - 1].answered_correctly_at_least_once) + parseInt(this.questions[this.currentPage - 1].submission_count) === 2
        this.learningTreeSrc = `/learning-trees/${this.questions[currentPage - 1].learning_tree_id}/get`
      }
      this.showOpenEndedSubmissionMessage = false
      this.solutionTextForm.solution_text = this.questions[currentPage - 1].solution_text
      this.audioUploadUrl = `/api/submission-audios/${this.assignmentId}/${this.questions[currentPage - 1].id}`
      this.showQuestion = true
      this.showSubmissionMessage = false
      this.openEndedSubmissionType = this.questions[currentPage - 1].open_ended_submission_type

      this.isOpenEndedAudioSubmission = (this.openEndedSubmissionType === 'audio')
      this.isOpenEndedFileSubmission = (this.openEndedSubmissionType === 'file')

      this.isOpenEndedTextSubmission = (this.openEndedSubmissionType === 'text')
      if (this.isOpenEndedTextSubmission) {
        this.openEndedSubmissionType = `${this.questions[currentPage - 1].open_ended_text_editor} text`
        if (this.user.role === 2) {
          this.openEndedDefaultTextForm.open_ended_default_text = this.questions[currentPage - 1].open_ended_default_text
        } else {
          this.textSubmissionForm.text_submission = this.questions[currentPage - 1].submission
            ? await this.getTextFromS3(this.questions[currentPage - 1])
            : this.questions[currentPage - 1].open_ended_default_text
        }
      }
      this.isOpenEnded = this.isOpenEndedFileSubmission || this.isOpenEndedTextSubmission || this.isOpenEndedAudioSubmission

      this.$nextTick(() => {
        this.questionPointsForm.points = this.questions[currentPage - 1].points
        let iframeId = this.questions[currentPage - 1].iframe_id
        iFrameResize({ log: false }, `#${iframeId}`)
        iFrameResize({ log: false }, `#non-technology-iframe-${this.currentPage}`)
      })

      if (this.showAssignmentStatistics) {
        this.getScoresSummary = getScoresSummary
        try {
          this.loaded = false
          this.chartdata = await this.getScoresSummary(this.assignmentId, `/api/scores/summary/${this.assignmentId}/${this.questions[this.currentPage - 1].id}`)
          this.loaded = true
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      this.isLoading = false
    },
    async getTextFromS3 (question) {
      try {
        const { data } = await axios.post(`/api/submission-files/get-files-from-s3/${this.assignmentId}/${question.id}/${this.user.id}`,
          { open_ended_submission_type: 'text' })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return data.files.submission_text
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getLearningTree (learningTree) {
      // loop through and get all with parent = -1
      this.learningTree = learningTree
      this.learningTreeAsList = []
      if (!this.learningTree) {
        return false
      }

      // loop through each with parent having this level
      let pageId
      let library
      let librariesAndPagIds = []
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
          console.log(pageId, library)
          librariesAndPagIds.push({
            'library': library,
            'pageId': pageId,
            'id': id
          })
          let remediation = {
            'library': library,
            'pageId': pageId,
            'title': 'None',
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
      const { data } = await axios.post('/api/libreverse/library/titles', { 'libraries_and_page_ids': librariesAndPagIds })

      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        this.learningTreeAsList[i].title = data.titles[i]
      }
      this.updateNavigator(0)
      this.loadedTitles = true
    },
    updateNavigator (activeId) {
      this.activeNode = this.learningTreeAsList[activeId]
      this.previousNode = parseInt(this.activeNode.parent) === -1 ? {} : this.learningTreeAsList[this.activeNode.parent]
      let futureNodes = []
      for (let i = 0; i < this.activeNode.children.length; i++) {
        let child = this.activeNode.children[i]
        futureNodes.push(this.learningTreeAsList[child])
      }
      this.futureNodes = futureNodes
    },
    explore (library, pageId, activeId) {
      this.showSubmissionMessage = false
      this.showQuestion = (activeId === 0)
      if (!this.showQuestion) {
        this.showQuestion = false
      }
      this.updateNavigator(activeId)
      this.remediationSrc = `https://${library}.libretexts.org/@go/page/${pageId}`
      this.remediationIframeId = `remediation-${library}-${pageId}`
      if (!this.timerSetToGetLearningTreePoints && !this.questions[this.currentPage - 1].explored_learning_tree) {
        this.setTimerToGetLearningTreePoints()
      }
      this.logVisitRemediationNode(library, pageId)
    },
    setTimerToGetLearningTreePoints () {
      this.timerSetToGetLearningTreePoints = true
      this.showDidNotAnswerCorrectlyMessage = false
      this.timeLeftToGetLearningTreePoints = this.minTimeNeededInLearningTree
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/view-questions-info`)

        if (data.type === 'error') {
          if (data.message === 'You are not allowed to access this assignment.') {
            this.$bvModal.show('modal-enroll-in-course')
          } else {
            this.$noty.error(data.message)
          }
          return false
        }
        let assignment = data.assignment
        if (this.user.role === 3 && !assignment.shown) {
          this.showAssessmentClosedMessage = true
        }
        if (this.user.role === 3) {
          this.title = `${assignment.name}`
          this.fullPdfUrl = assignment.full_pdf_url
          this.showCurrentFullPDF = !!this.fullPdfUrl.length
        }
        if (this.user.role === 2) {
          this.questionView = assignment.question_view
        }
        this.name = assignment.name
        this.pastDue = assignment.past_due
        this.assessmentType = assignment.assessment_type
        this.presentationMode = (this.assessmentType === 'clicker')
        this.capitalFormattedAssessmentType = this.assessmentType === 'learning tree' ? 'Learning Trees' : 'Questions'
        this.has_submissions_or_file_submissions = assignment.has_submissions_or_file_submissions
        if (this.assessmentType !== 'clicker') {
          this.timeLeft = assignment.time_left
        } else {
          this.defaultClickerTimeToSubmit = assignment.default_clicker_time_to_submit
          this.clickerTimeForm.time_to_submit = this.defaultClickerTimeToSubmit
        }
        this.minTimeNeededInLearningTree = assignment.min_time_needed_in_learning_tree
        this.percentEarnedForExploringLearningTree = parseInt(assignment.percent_earned_for_exploring_learning_tree)
        this.submissionCountPercentDecrease = assignment.submission_count_percent_decrease
        this.totalPoints = parseInt(String(assignment.total_points).replace(/\.00$/, ''))
        this.source = assignment.source
        this.compiledPDF = assignment.file_upload_mode === 'compiled_pdf'
        this.bothFileUploadMode = assignment.file_upload_mode === 'both'
        this.openEndedSubmissionTypeAllowed = (assignment.assessment_type === 'delayed')// can upload at the question level
        this.solutionsReleased = Boolean(Number(assignment.solutions_released))
        this.latePolicy = assignment.late_policy
        this.showScores = Boolean(Number(assignment.show_scores))
        this.scoring_type = assignment.scoring_type
        this.students_can_view_assignment_statistics = assignment.students_can_view_assignment_statistics
        this.showPointsPerQuestion = assignment.show_points_per_question
      } catch (error) {
        if (error.message.includes('status code 404')) {
          this.showInvalidAssignmentMessage = true
          return false
        } else {
          this.$noty.error(error.message)
        }
        this.title = 'Assessments'
      }
      return true
    },
    async setCutupAsSolution (questionId) {
      if (this.settingAsSolution) {
        this.$noty.info('Please be patient while your request is being processed.')
        return false
      }
      this.settingAsSolution = true
      try {
        const { data } = await this.cutupsForm.patch(`/api/cutups/${this.assignmentId}/${questionId}/solution`)

        this.settingAsSolution = false
        if (data.type === 'success') {
          this.$noty.success(data.message)
          this.$bvModal.hide('modal-upload-file')

          if (this.user.role === 2) {
            this.questions[this.currentPage - 1].solution = data.cutup
            this.questions[this.currentPage - 1].solution_file_url = data.solution_file_url
            this.questions[this.currentPage - 1].solution_type = 'q'
          }
          this.questions[this.currentPage - 1].date_submitted = data.date_submitted
        } else {
          this.cutupsForm.errors.set('chosen_cutups', data.message)
        }
      } catch (error) {
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
        this.showCurrentFullPDF = this.cutups.length
      } catch (error) {
        this.$noty.error('We could not retrieve your cutups for this assignment.  Please refresh the page and try again.')
      }
    },
    async getSelectedQuestions (assignmentId, questionId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        this.questions = data.questions
        this.isInstructorLoggedInAsStudent = data.is_instructor_logged_in_as_student
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

        this.questionPointsForm.points = this.questions[this.currentPage - 1].points
        this.learningTree = this.questions[this.currentPage - 1].learning_tree

        this.showDidNotAnswerCorrectlyMessage = this.questions[this.currentPage - 1].submitted_but_did_not_explore_learning_tree

        if (this.questions[this.currentPage - 1].explored_learning_tree && parseInt(this.questions[this.currentPage - 1].submission_score) === 0) {
          // haven't yet gotten points for exploring the learning tree
          this.showLearningTreePointsMessage = true
        }
        await this.getLearningTree(this.learningTree)
        this.initializing = false
      } catch (error) {
        this.$noty.error(`We could not retrieve the questions for this assignment: ${error.message}.  Please try again or contact us for assistance.`)
      }
      this.$nextTick(() => {
        this.showIframe(this.questions[this.currentPage - 1].iframe_id)
      })
    },
    initCurrentPage () {
      let questionExistsInAssignment = false
      for (let i = 0; i <= this.questions.length - 1; i++) {
        if (parseInt(this.questions[i].id) === parseInt(this.questionId)) {
          this.currentPage = i + 1
          questionExistsInAssignment = true
        }
      }
      return questionExistsInAssignment
    },
    logVisitRemediationNode (library, pageId) {
      console.log('To do!!!')
    },
    getAssessmentsForAssignment () {
      this.assessmentType === 'learning tree'
        ? this.$router.push(`/assignments/${this.assignmentId}/learning-trees/get`)
        : this.$router.push(`/assignments/${this.assignmentId}/questions/get`)
    },
    openRemoveQuestionModal () {
      this.$bvModal.show('modal-remove-question')
    },
    async submitRemoveQuestion () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.$noty.info(data.message)
        this.questions.splice(this.currentPage - 1, 1)
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
#cke_bottom {
  height: 9px;
}

.example-drag label.btn {
  margin-bottom: 0;
  margin-right: 1rem;
}

.example-drag .drop-active {
  top: 0;
  bottom: 0;
  right: 0;
  left: 0;
  position: fixed;
  z-index: 9999;
  opacity: .6;
  text-align: center;
  background: #000;
}

.example-drag .drop-active h3 {
  margin: -.5em 0 0;
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  -webkit-transform: translateY(-50%);
  -ms-transform: translateY(-50%);
  transform: translateY(-50%);
  font-size: 40px;
  color: #fff;
  padding: 0;
}
</style>
<style>
div.ar-icon svg {
  vertical-align: top !important;
}

.sidebar-card {
  width: 368px;
}
</style>
