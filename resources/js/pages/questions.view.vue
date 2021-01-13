<template>
  <div style="min-height:400px; margin-bottom:100px">
    <EnrollInCourse />
    <Email id="contact-grader-modal"
           ref="email"
           extra-email-modal-text="Before you contact your grader, please be sure to look at the solutions first, if they are available."
           :from-user="user"
           title="Contact Grader"
           type="contact_grader"
           :subject="getSubject()"
    />
    <b-modal
      id="modal-share"
      ref="modal"
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
              title="Tooltip directive content"
      /><b-tooltip target="attribution-tooltip" triggers="hover">
        The attribution includes who authored the question and the license associated with the question.
      </b-tooltip><br>
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
      /> <b-icon id="submissionInformation-tooltip"
                 v-b-tooltip.hover
                 class="text-muted"
                 icon="question-circle"
                 title="Tooltip directive content"
      /><b-tooltip target="submissionInformation-tooltip" triggers="hover">
        The submission information includes when the question was submitted, the score on the question, and the last submitted.
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
      <b-icon id="header-tooltip"
              v-b-tooltip.hover
              class="text-muted"
              icon="question-circle"
              title="Tooltip directive content"
      /><b-tooltip target="assignmentInformation-tooltip" triggers="hover">
        This information includes the name of the assignment, the question number in the assignment, and the time left in the assignment.
      </b-tooltip>

      <b-table />

      <span class="font-weight-bold">URL:</span> <span class="font-italic">{{ currentUrl }}</span>
      <b-button v-clipboard:copy="currentUrl"
                v-clipboard:success="onCopy"
                v-clipboard:error="onError"
                size="sm"
                variant="outline-primary"
      >
        Copy
      </b-button>
      <br>
      <br>
      <span class="font-weight-bold">iFrame:</span> <span class="font-italic">{{ embedCode }}</span>
      <b-button v-clipboard:copy="embedCode"
                v-clipboard:success="onCopy"
                v-clipboard:error="onError"
                size="sm"
                variant="outline-primary"
      >
        Copy
      </b-button><br>
    </b-modal>

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
      <p>
        <span class="font-italic"><span class="font-weight-bold">Important:</span> For best results, don't crop any of your pages.  In addition, please make sure that they are all oriented in the same direction.</span>
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
    <div v-if="inIFrame" class="text-center">
      <h5 v-if="(questions !==['init']) && canView">
        {{ name }}: Assessment {{ currentPage }} of {{ questions.length }}
      </h5>
    </div>
    <div v-else>
      <PageTitle v-if="questions !==['init']" :title="title" />
    </div>
    <div v-if="questions.length && !initializing">
      <div v-if="isLocked()">
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
                <div v-if="!isInstructor() && showPointsPerQuestion && assessmentType === 'learning tree'"
                     class="text-center"
                >
                  <span class="text-bold">
                    A penalty of
                    {{ submissionCountPercentDecrease }}% will applied for each attempt starting with the 3rd.
                  </span>
                </div>
                <div
                  v-show="!isInstructor && (parseInt(questions[currentPage - 1].submission_count) === 0 || questions[currentPage - 1].late_question_submission) && latePolicy === 'deduction' && timeLeft === 0"
                  class="text-center"
                >
                  <b-alert variant="warning" show>
                    <span class="alert-link">
                      This submission will be marked lated.</span>
                  </b-alert>
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
                                  :disabled="isLocked()"
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
                  <countdown :time="timeLeft" @end="timeLeft=0">
                    <template slot-scope="props">
                      Time Until due：{{ props.days }} days, {{ props.hours }} hours,
                      {{ props.minutes }} minutes, {{ props.seconds }} seconds.
                    </template>
                  </countdown>
                </div>
                <div v-if="user.role === 2" class="mt-1">
                  <b-button
                    variant="info"
                    size="sm"
                    @click="openModalShare()"
                  >
                    <b-icon icon="share" />
                    Share
                  </b-button>
                </div>
                <div class="font-italic font-weight-bold">
                  <div v-if="(scoring_type === 'p')">
                    <div v-if="user.role === 3 && showScores && isOpenEnded">
                      <p>
                        You achieved a total score of
                        {{ questions[currentPage - 1].total_score * 1 }}
                        out of a possible
                        {{ questions[currentPage - 1].points * 1 }} points.
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
                  <b-button class="mt-1 mb-2 mr-2" variant="success" @click="getAssessmentsForAssignment()">
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
            v-if="!inIFrame"
            v-model="currentPage"
            :total-rows="questions.length"
            :per-page="perPage"
            first-number
            last-number
            align="center"
            @input="changePage(currentPage)"
          />
        </div>

        <div v-if="isInstructor()" class="d-flex flex-row">
          <div class="p-2">
            <b-button class="mt-1 mb-2"
                      variant="danger"
                      :disabled="isLocked()"
                      @click="removeQuestion(currentPage)"
            >
              Remove Question
            </b-button>
          </div>
          <div v-if="openEndedSubmissionTypeAllowed" class="p-2">
            <span class="font-italic">Open-Ended Submission Type:</span>
            <b-form-select v-model="openEndedSubmissionType"
                           :options="openEndedSubmissionTypeOptions"
                           style="width:100px"
                           @change="updateOpenEndedSubmissionType(questions[currentPage-1].id)"
            />
          </div>
          <div class="p-2">
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
        </div>

        <hr>

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
                    <b-container>
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
                <div>
                  <div v-if="isOpenEndedTextSubmission && user.role === 3">
                    <div>
                      <ckeditor v-model="textForm.text_submission" :config="editorConfig" />
                    </div>
                    <div class="mt-2 mb-3">
                      <b-button variant="primary" class="float-right" @click="submitText">
                        Submit
                      </b-button>
                    </div>
                  </div>
                </div>
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
              <b-row v-if="questions[currentPage-1].technology_iframe">
                <b-card header="default" header-html="<h5>Question Submission Information</h5>">
                  <b-card-text>
                    <span
                      v-show="(parseInt(questions[currentPage - 1].submission_count) === 0 || questions[currentPage - 1].late_question_submission) && latePolicy === 'marked late' && timeLeft === 0"
                    >
                      <b-alert variant="warning" show>
                        <span class="alert-link">
                          Your question submission will be marked late.</span>
                      </b-alert>
                    </span>
                    <span v-if="questions[currentPage-1].solution">
                      <span class="font-weight-bold">Solution:</span>
                      <a href=""
                         @click.prevent="downloadSolutionFile('q', assignmentId,questions[currentPage - 1].id, standardizeFilename(questions[currentPage - 1].solution))"
                      >
                        {{ standardizeFilename(questions[currentPage - 1].solution) }}
                      </a>
                      <br>
                    </span>
                    <span v-if="assessmentType==='learning tree'">
                      <span class="font-weight-bold">Number of attempts: </span>
                      {{
                        questions[currentPage - 1].submission_count
                      }}<br></span>
                    <span class="font-weight-bold">Last submitted:</span> {{
                      questions[currentPage - 1].last_submitted
                    }}<br>

                    <span class="font-weight-bold">Last response:</span> {{
                      questions[currentPage - 1].student_response
                    }}<br>
                    <div v-if="(scoring_type === 'p') && showScores">
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
                    <b-alert :show="(timerSetToGetLearningTreePoints && !showLearningTreePointsMessage)" variant="info">
                      <countdown :time="timeLeftToGetLearningTreePoints" @end="updateExploredLearningTree">
                        <template slot-scope="props">
                          <span class="font-weight-bold">  After exploring the Learning Tree for {{ props.minutes }} minutes, {{
                            props.seconds
                          }} seconds, you'll be able to re-submit.
                          </span>
                        </template>
                      </countdown>
                    </b-alert>
                    <b-alert variant="info" :show="!showSubmissionMessage &&
                      !(Number(questions[currentPage - 1].learning_tree_exploration_points) > 0 ) &&
                      !timerSetToGetLearningTreePoints && showLearningTreePointsMessage
                      && (user.role === 3)"
                    >
                      <span class="font-weight-bold"> Upon your next attempt at this assessment, you will receive
                        {{ (percentEarnedForExploringLearningTree / 100) * (questions[currentPage - 1].points) }} points for exploring the Learning
                        Tree.</span>
                    </b-alert>
                    <b-alert variant="info"
                             :show="showDidNotAnswerCorrectlyMessage && !timerSetToGetLearningTreePoints"
                    >
                      <span class="font-weight-bold"> Unfortunately, you didn't answer this question correctly.  Explore the Learning Tree, and then you can try again!</span>
                    </b-alert>
                    <b-alert :variant="submissionDataType" :show="showSubmissionMessage">
                      <span class="font-weight-bold">{{ submissionDataMessage }}</span>
                    </b-alert>
                  </b-card-text>
                </b-card>
              </b-row>
              <b-row v-if="isOpenEnded && (user.role === 3)" class="mt-3 mb-3">
                <b-card header="Default" :header-html="getOpenEndedTitle()">
                  <b-card-text>
                    <span
                      v-show="(!questions[currentPage-1].submission_file_exists ||questions[currentPage-1].late_file_submission) && latePolicy === 'marked late' && timeLeft === 0"
                    >
                      <b-alert variant="warning" show>
                        <a href="#" class="alert-link">Your {{ openEndedSubmissionType }} submission will be marked late.</a>
                      </b-alert>
                    </span>
                    <span v-if="isOpenEndedFileSubmission">
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
                    </span>
                    <strong>Date Submitted:</strong> {{ questions[currentPage - 1].date_submitted }}<br>
                    <span v-if="showScores">
                      <strong>Date Graded:</strong> {{ questions[currentPage - 1].date_graded }}<br>
                    </span>
                    <span v-if="showScores">
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
                      <div class="mt-2">
                        <b-button v-b-modal.modal-upload-file variant="primary"
                                  class="float-right mr-2"
                                  @click="openUploadFileModal(questions[currentPage-1].id)"
                        >
                          Upload New File
                        </b-button>
                      </div>
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
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'

import { ToggleButton } from 'vue-js-toggle-button'

import { getAcceptedFileTypes, submitUploadFile } from '~/helpers/UploadFiles'
import { h5pResizer } from '~/helpers/H5PResizer'

import { isLocked } from '~/helpers/Assignments'

import { downloadSolutionFile, downloadSubmissionFile } from '~/helpers/DownloadFiles'

import Email from '~/components/Email'
import Scores from '~/components/Scores'
import EnrollInCourse from '~/components/EnrollInCourse'
import { getScoresSummary } from '~/helpers/Scores'
import CKEditor from 'ckeditor4-vue'

export default {
  middleware: 'auth',
  components: {
    EnrollInCourse,
    Scores,
    Email,
    ToggleButton,
    ckeditor: CKEditor.component
  },
  data: () => ({
    shownSections: '',
    iFrameAssignmentInformation: true,
    iFrameSubmissionInformation: true,
    iFrameAttribution: true,
    inIFrame: false,
    editorData: '<p>Content of the editor.</p>',
    editorConfig: {
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
    responseText: '',
    openEndedSubmissionTypeOptions: [
      { value: 'text', text: 'Text' },
      { value: 'file', text: 'File' },
      { value: 'none', text: 'None' }
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
    textForm: new Form({
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
    this.isLocked = isLocked
  },
  async mounted () {
    this.uploadFileType = (this.user.role === 2) ? 'solution' : 'submission' // students upload question submissions and instructors upload solutions
    this.uploadFileUrl = (this.user.role === 2) ? '/api/solution-files' : '/api/submission-files'

    this.assignmentId = this.$route.params.assignmentId
    this.questionId = this.$route.params.questionId
    this.shownSections = this.$route.params.shownSections
    this.canView = await this.getAssignmentInfo()
    if (!this.canView) {
      return false
    }

    this.questionCol = (this.user.role === 2 && this.scoring_type === 'c') ? 12 : 8
    if (this.source === 'a') {
      await this.getSelectedQuestions(this.assignmentId, this.questionId)
      if (this.questionId) {
        this.currentPage = this.getInitialCurrentPage(this.questionId)
      }
      await this.changePage(this.currentPage)
      await this.getCutups(this.assignmentId)
      window.addEventListener('message', this.receiveMessage, false)
    }
    this.showAssignmentStatistics = this.questions.length && (this.user.role === 2 || (this.user.role === 3 && this.students_can_view_assignment_statistics))
    if (this.showAssignmentStatistics) {
      this.loaded = false
      this.getScoresSummary = getScoresSummary
      try {
        this.chartdata = await this.getScoresSummary(this.assignmentId, `/api/scores/summary/${this.assignmentId}/${this.questions[0]['id']}`)
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
    updateShare () {
      this.currentUrl = this.getCurrentUrl()
      this.embedCode = this.getEmbedCode()
    },
    openModalShare () {
      this.$bvModal.show('modal-share')
      this.currentUrl = this.getCurrentUrl()
      this.embedCode = this.getEmbedCode()
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
      if (extras.length) {
        url += '/'
        for (let i = 0; i < extras.length; i++) {
          url += extras[i] + '-'
        }
      }
      return url.slice(0, -1)
    },
    getInitialCurrentPage (questionId) {
      console.log('here')
      console.log(this.questions)
      for (let i = 1; i <= this.questions.length; i++) {
        console.log(parseInt(this.questions[i - 1].id) + '  ' + parseInt(questionId))
        if (parseInt(this.questions[i - 1].id) === parseInt(questionId)) {
          return i
        }
      }
    },
    getOpenEndedTitle () {
      let capitalizedTitle = this.openEndedSubmissionType.charAt(0).toUpperCase() + this.openEndedSubmissionType.slice(1)
      return `<h5>${capitalizedTitle} Submission Information</h5>`
    },
    async submitText () {
      try {
        this.textForm.questionId = this.questions[this.currentPage - 1].id
        this.textForm.assignmentId = this.assignmentId
        const { data } = await this.textForm.post('/api/submission-texts')
        console.log(data)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].date_submitted = data.date_submitted
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateOpenEndedSubmissionType (questionId) {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/${questionId}/update-open-ended-submission-type`, { 'open_ended_submission_type': this.openEndedSubmissionType })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].open_ended_submission_type = this.openEndedSubmissionType
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    onCopy: function () {
      this.$noty.success('The code to share has been copied to your clipboard.')
    },
    onError: function () {
      this.$noty.error('There was a problem copying the embed code to your clipboard.')
    },
    getWindowLocation () {
      return window.location
    },
    async updateExploredLearningTree () {
      try {
        const { data } = await axios.patch(`/api/submissions/${this.assignmentId}/${this.questions[this.currentPage - 1].id}/explored-learning-tree`)
        console.log(data)
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
        console.log(data)
        this.questions[this.currentPage - 1]['last_submitted'] = data.last_submitted
        this.questions[this.currentPage - 1]['student_response'] = data.student_response
        this.questions[this.currentPage - 1]['submission_count'] = data.submission_count
        this.questions[this.currentPage - 1]['submission_score'] = data.submission_score
        this.questions[this.currentPage - 1]['late_penalty_percent'] = data.late_penalty_percent
        this.questions[this.currentPage - 1]['late_question_submission'] = data.late_question_submission
        this.questions[this.currentPage - 1]['solution'] = data.solution
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
      // console.log(event.data)
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

        console.log('server side submit' + serverSideSubmit)
        if (serverSideSubmit) {
          console.log('serverSideSubmit')
          await this.showResponse(JSON.parse(event.data))
        }
        if (clientSideSubmit) {
          let submissionData = {
            'submission': event.data,
            'assignment_id': this.assignmentId,
            'question_id': this.questions[this.currentPage - 1].id,
            'technology': technology
          }

          console.log('submitted')
          console.log(submissionData)

          // if incorrect, show the learning tree stuff...
          try {
            this.hideResponse()
            const { data } = await axios.post('/api/submissions', submissionData)
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
      console.log(data)
      if (data.learning_tree && !this.learningTree) {
        await this.showLearningTree(data.learning_tree)
      }
      this.submissionDataType = ['success', 'info'].includes(data.type) ? data.type : 'danger'

      this.submissionDataMessage = data.message
      this.learningTreePercentPenalty = data.learning_tree_percent_penalty
      this.showSubmissionMessage = true
      setTimeout(() => {
        this.showSubmissionMessage = false
      }, 8000)
      if (this.submissionDataType !== 'danger') {
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
      console.log(this.questions[currentPage - 1])
      this.showQuestion = true
      this.showSubmissionMessage = false
      this.openEndedSubmissionType = this.questions[currentPage - 1].open_ended_submission_type
      this.isOpenEndedFileSubmission = (this.openEndedSubmissionType === 'file')

      this.isOpenEndedTextSubmission = (this.openEndedSubmissionType === 'text')
      if (this.isOpenEndedTextSubmission) {
        this.textForm.text_submission = this.questions[currentPage - 1].submission
      }
      this.isOpenEnded = this.isOpenEndedFileSubmission || this.isOpenEndedTextSubmission

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
      this.showDidNotAnswerCorrectlyMessage = false
      this.timeLeftToGetLearningTreePoints = this.minTimeNeededInLearningTree
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/view-questions-info`)
        console.log(data)
        if (data.type === 'error') {
          if (data.message === 'You are not allowed to access this assignment.') {
            this.$bvModal.show('modal-enroll-in-course')
          } else {
            this.$noty.error(data.message)
          }
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
        this.percentEarnedForExploringLearningTree = parseInt(assignment.percent_earned_for_exploring_learning_tree)
        this.submissionCountPercentDecrease = assignment.submission_count_percent_decrease
        this.totalPoints = parseInt(String(assignment.total_points).replace(/\.00$/, ''))
        this.source = assignment.source
        this.openEndedSubmissionTypeAllowed = (assignment.assessment_type === 'delayed')// can upload at the question level
        this.solutionsReleased = Boolean(Number(assignment.solutions_released))
        this.latePolicy = assignment.late_policy
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
            this.questions[this.currentPage - 1].late_file_submission = data.late_file_submission
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
        this.questionPointsForm.points = this.questions[this.currentPage - 1].points
        this.learningTree = this.questions[this.currentPage - 1].learning_tree
        if (this.questions[this.currentPage - 1].submitted_but_did_not_explore_learning_tree) {
          this.showDidNotAnswerCorrectlyMessage = true
        }

        if (this.questions[this.currentPage - 1].explored_learning_tree && parseInt(this.questions[this.currentPage - 1].submission_score) === 0) {
          // haven't yet gotten points for exploring the learning tree
          this.showLearningTreePointsMessage = true
        }
        await this.showLearningTree(this.learningTree)

        this.initializing = false
      } catch (error) {
        this.$noty.error('We could not retrieve the questions for this assignment.  Please try again or contact us for assistance.')
      }
      this.iframeLoaded = true
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
