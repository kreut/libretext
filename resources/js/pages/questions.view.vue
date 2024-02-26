<template>
  <div :style="!inIFrame ? 'min-height:400px; margin-bottom:100px' : 'margin-bottom:10px;'">
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-completion-scoring-mode'" />
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-libretexts-solution-error-form'" />
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-file-upload'" />
    <AllFormErrors :all-form-errors="allFormErrors"
                   :modal-id="'modal-form-errors-assignment-question-learning-tree-info'"
    />
    <b-modal id="modal-instructor-clicker-question"
             no-close-on-backdrop
             hide-header
             size="huge"
             footer-class="d-block"
             @hidden="modalInstructorClickerQuestionShown = false"
    >
      <div v-show="viewingClickerSubmissions">
        <div class="d-inline-flex d-inline">
          <div style="margin-right:200px">
            <div style="font-size:20px;margin-bottom:75px">
              {{ responsePercent }}% of students have responded
            </div>
            <div v-for="(label,piechartdataIndex) in piechartdata.labels"
                 :key="`pie-chart-data-${piechartdataIndex}`"
                 style="font-size:x-large"
            >
              <b-icon-square-fill
                :style="`color:${piechartdata.datasets.backgroundColor[piechartdataIndex]}`"
              />
              <span v-html="label" /> <span v-show="clickerAnswerShown && (piechartdataIndex === correctAnswerIndex)"><img alt="Checkmark for correct answer"
                                                                                                                           style="height:30px;margin-bottom:10px"
                                                                                                                           :src="asset('assets/img/check-mark.png')"
              ></span>
            </div>
          </div>
          <div v-if="piechartdata.labels && piechartdata.labels.length">
            <pie-chart :key="currentPage"
                       :chartdata="piechartdata"
                       @pieChartLoaded="updateIsLoadingPieChart"
            />
          </div>
        </div>
      </div>
      <div v-show="!viewingClickerSubmissions">
        <div
          v-if="questions[currentPage-1] && questions[currentPage-1]['qti_json'] && getQtiJson()['qtiJson'] && showQtiJsonQuestionViewer"
        >
          <QtiJsonQuestionViewer
            :key="`qti-json-${currentPage}-${cacheIndex}-${questions[currentPage - 1].student_response}`"
            :qti-json="getQtiJson()['qtiJson']"
            :student-response="questions[currentPage - 1].student_response"
            :show-submit="false"
            :submit-button-active="getQtiJson()['submitButtonActive']"
            :show-reset-response="false"
            :presentation-mode="presentationMode"
            @submitResponse="receiveMessage"
            @resetResponse="resetSubmission"
          />
        </div>
        <div
          v-if="questions[currentPage-1] && questions[currentPage-1].technology_iframe.length"
        >
          <div
            v-if="(technologySrcDoc === '' && questions[currentPage-1].technology !== 'webwork')"
          >
            <iframe
              :key="`technology-iframe-${currentPage}-${cacheIndex}`"
              v-resize="{ log: false }"
              aria-label="auto_graded_submission_text"
              width="100%"
              allowtransparency="true"
              :src="questions[currentPage-1].technology_iframe"
              frameborder="0"
              :title="getIframeTitle()"
            />
          </div>
        </div>
      </div>
      <template #modal-footer>
        <div>
          <countdown
            :time="timeLeft"
            class="float-left"
            @end="endClickerAssessment"
          >
            <template slot-scope="props">
              <span style="font-size: x-large" class="pt-5"
                    v-html="getTimeLeftMessage(props, assessmentType)"
              />
            </template>
          </countdown>
          <span class="float-right">
            <b-button v-show="clickerModalButtons.submissions"
                      variant="primary"
                      @click="initViewClickerSubmissions"
            >View Submissions
            </b-button>
            <b-button v-show="clickerModalButtons.answer"
                      variant="success"
                      @click="initShowClickerAnswer"
            >Show Answer
            </b-button>
            <b-button v-show="clickerModalButtons.close"
                      @click="$bvModal.hide('modal-instructor-clicker-question')"
            >Close
            </b-button>
          </span>
        </div>
      </template>
    </b-modal>
    <b-modal id="modal-confirm-close-poll"
             title="Close Poll Confirmation"
    >
      <p>The poll is still open. Please confirm that you would like to close the poll.</p>
      <template #modal-footer>
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-close-poll')">
          Cancel
        </b-button>
        <b-button size="sm" variant="danger" @click="closePoll()">
          Close Poll
        </b-button>
      </template>
    </b-modal>
    <b-modal v-if="questions[currentPage-1] && questions[currentPage-1].number_resets_available > 0"
             id="modal-reset-root-node-submission"
             title="Reset Submission"
    >
      <p>
        You currently can reset your submission {{ questions[currentPage - 1].number_resets_available }} time<span
          v-show="questions[currentPage-1].number_resets_available>1"
        >s</span>.
      </p>
      <p>By resetting the submission, you will be able to try the question again but your score will be reset to 0.</p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-reset-root-node-submission')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="resetRootNodeSubmission()"
        >
          Reset Submission
        </b-button>
      </template>
    </b-modal>
    <b-modal v-if="learningTreeSrc.length > 0"
             id="modal-learning-tree"
             size="xl"
             hide-footer
             @shown="increaseLearningTreeModalSize"
             @hidden="reloadSingleQuestion"
    >
      <template #modal-header="{ close }">
        <!-- Emulate built in modal header close button action -->
        <h5>
          {{ questions[currentPage - 1].title }}
        </h5>
        <b-button size="sm" variant="outline-success" @click="$bvModal.hide('modal-learning-tree')">
          Exit Learning Tree
        </b-button>
      </template>
      <iframe
        allowtransparency="true"
        frameborder="0"
        :src="learningTreeSrc"
        style="width: 800px;min-width: 100%;height:800px"
        @load="increaseLearningTreeModalSize"
      />
    </b-modal>
    <b-modal id="modal-confirm-delete-open-ended-submissions"
             title="Confirm Delete Open Ended Submissions"
    >
      {{ confirmDeleteOpenEndedSubmissionsMessage }}
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="openEndedSubmissionType = originalOpenEndedSubmissionType;$bvModal.hide('modal-confirm-delete-open-ended-submissions')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-delete-open-ended-submissions');updateOpenEndedSubmissionType (questions[currentPage-1].id)"
        >
          Do it!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-save-questions-from-open-course"
      title="Saving Questions"
    >
      <p>
        You can save questions from this open course to one of your Favorites folders and then import them to any
        of your assignments.
      </p>
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-save-questions-from-open-course')"
        >
          Got it!
        </b-button>
      </template>
    </b-modal>
    <b-modal v-if="questions[currentPage - 1] && questions[currentPage - 1].has_h5p_video_interaction_submissions"
             id="modal-h5p-video-interaction-submissions"
             title="Partial Submissions"
             hide-footer
             size="lg"
    >
      <b-table
        aria-label="Submissions"
        striped
        hover
        :no-border-collapse="true"
        :items="questions[currentPage - 1].h5p_video_interaction_submissions"
        :fields="h5pVideoInteractionSubmissionsFields"
      >
        <template #cell(question)="data">
          <span v-html="data.value" />
        </template>
        <template #cell(response)="data">
          <span v-html="data.value" />
        </template>
      </b-table>
    </b-modal>
    <b-modal
      v-if="questionToEdit"
      :id="`modal-edit-question-${questionToEdit.id}`"
      :key="`modal-edit-question-${questionToEdit.id}`"
      :title="`Edit Question &quot;${questionToEdit.title}&quot;`"
      :no-close-on-backdrop="true"
      :no-close-on-esc="true"
      size="xl"
      hide-footer
      @hidden="reloadAndRemoveQuestionEditorUpdatedAt"
    >
      <CreateQuestion :key="`question-to-edit-${questionToEdit.id}-${questionToEdit.question_revision_id}`"
                      :question-to-edit="questionToEdit"
                      :parent-get-my-questions="reloadSingleQuestion"
                      :modal-id="'my-questions-question-to-view-questions-editor'"
                      :question-exists-in-own-assignment="questionToEdit.question_exists_in_own_assignment"
                      :question-exists-in-another-instructors-assignment="questionToEdit.question_exists_in_another_instructors_assignment"
                      @setQuestionRevision="setQuestionRevision"
      />
    </b-modal>
    <b-modal v-model="showAssignmentStatisticsModal"
             size="xl"
             title="Question Level Statistics"
             hide-footer
    >
      <b-container>
        <b-row v-if="showAssignmentStatistics && loaded && user.role === 3 && !isAnonymousUser">
          <b-col>
            <b-card header="default" header-html="<span class=&quot;font-weight-bold&quot;>Summary</span>">
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
            <HistogramAndTableView :chartdata="chartdata"
                                   :height="300"
                                   :width="300"
            />
          </b-col>
        </b-row>
      </b-container>
    </b-modal>

    <b-modal id="modal-cannot-give-up-yet"
             title="Submit Before Giving Up"
             hide-footer
    >
      <p>
        You'll be able to "give up" after you attempt this problem at least once. And, if you're not
        sure how to start this problem, a good strategy is to always go back to your notes or textbook to
        either find a related problem or review the underlying concept.
      </p>
    </b-modal>

    <b-modal v-if="questions[currentPage - 1]"
             id="modal-hint"
             title="Hint"
    >
      <b-alert :show="user.role === 2" variant="info">
        Students receive a {{ hintPenaltyIfShownHint }}% penalty for viewing the hint.
      </b-alert>
      <span v-html="questions[currentPage - 1].hint" />
      <template #modal-footer="{ ok}">
        <b-button
          size="sm"
          variant="primary"
          @click="$bvModal.hide('modal-hint')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-confirm-show-hint"
             title="Confirm Show Hint"
    >
      <p v-if="questions[currentPage - 1]">
        <span v-if="hintPenaltyIfShownHint === 0">
          You can view a hint and no penalty will be applied.
        </span>
        <span v-if="hintPenaltyIfShownHint !== 0">
          You can view a hint, but if you do, a penalty of {{ hintPenaltyIfShownHint }}% will be applied to your next submission.
        </span>
      </p>

      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-show-hint')">
          Cancel
        </b-button>
        <b-button v-if="!questions[currentPage-1].shown_hint"
                  size="sm"
                  variant="primary"
                  @click="handleShownHint"
        >
          Confirm Showing Hint
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-confirm-give-up"
             title="Confirm Giving Up"
    >
      <p v-if="questions[currentPage - 1]">
        You can give up now and get access to the solution, but if you do, your current score of
        {{ questions[currentPage - 1].submission_score }} will be recorded.
      </p>
      <p>
        In addition, once the solution becomes available, you will no longer be able to submit a new
        response.
      </p>

      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-give-up')">
          Cancel
        </b-button>
        <b-button v-if="!questions[currentPage-1].show_solution"
                  size="sm"
                  variant="primary"
                  @click="handleShowSolution"
        >
          Confirm Giving Up
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-reason-for-uploading-local-solution"
      title="Reason For Uploading Local Solution"
      size="lg"
    >
      <RequiredText />
      <b-container>
        <b-form-group
          id="reason_for_uploading_local_solution"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Reason For Upload*"
          label-for="reason_for_uploading_local_solution"
        >
          <b-form-row>
            <b-form-radio-group
              v-model="reasonForUploadingLocalSolution"
              stacked
            >
              <b-form-radio name="reason_for_uploading_local_solution" value="prefer_own_solution">
                I prefer to use my own solution
              </b-form-radio>

              <b-form-radio name="reason_for_uploading_local_solution" value="libretexts_solution_error">
                There's an error in the Libretexts solution
              </b-form-radio>
            </b-form-radio-group>
          </b-form-row>
        </b-form-group>
        <b-form-group
          v-show="reasonForUploadingLocalSolution === 'libretexts_solution_error'"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Description of Error*"
          label-for="description_of_libretexts_solution_error"
        >
          <ckeditor
            id="description_of_libretexts_solution_error"
            v-model="libretextsSolutionErrorForm.text"
            tabindex="0"
            :config="richEditorSolutionErrorConfig"
            required
            :class="{ 'is-invalid': libretextsSolutionErrorForm.errors.has('text') }"
            @keydown="libretextsSolutionErrorForm.errors.clear('text')"
            @namespaceloaded="onCKEditorNamespaceLoaded"
            @ready="handleFixCKEditor()"
          />
          <has-error :form="libretextsSolutionErrorForm" field="text" />
        </b-form-group>
      </b-container>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-reason-for-uploading-local-solution')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="submitReasonForUploadingLocalSolution()">
          Submit
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-update-completion-scoring-mode"
      ref="modalUpdateCompletionScoringMode"
      title="Update Completion Scoring Mode"

      size="lg"
    >
      <RequiredText />
      <b-form-group
        label-cols-sm="5"
        label-cols-lg="4"
        label-for="completion_scoring_mode"
      >
        <template v-slot:label>
          Completion Scoring Mode*
        </template>

        <b-form-radio-group id="completion_scoring_mode"
                            v-model="completionScoringModeForm.completion_scoring_mode"
                            stacked
                            required
                            :class="{ 'is-invalid': completionScoringModeForm.errors.has('completion_scoring_mode') }"
                            @keydown="completionScoringModeForm.errors.clear('completion_scoring_mode')"
        >
          <b-form-radio value="100% for either">
            100% of points for either auto-graded or open-ended submission
          </b-form-radio>
          <b-form-radio value="split">
            <input v-model="completionScoringModeForm.completion_split_auto_graded_percentage"
                   class="percent-input"
                   @keyup="completionSplitOpenEndedPercentage = updateCompletionSplitOpenEndedSubmissionPercentage(completionScoringModeForm)"
                   @click="completionScoringModeForm.completion_scoring_mode = 'split'"
                   @keydown="completionScoringModeForm.completion_scoring_mode = 'split'"
            >% of points awarded for an auto-graded
            submission<br>
            <span v-if="!isNaN(parseFloat(completionSplitOpenEndedPercentage))">
              <input v-model="completionSplitOpenEndedPercentage"
                     class="percent-input"
                     disabled
                     :aria-disabled="true"
              >%
              of the points awarded for an open-ended submission
            </span>
          </b-form-radio>
        </b-form-radio-group>
        <has-error :form="completionScoringModeForm" field="completion_scoring_mode" />
      </b-form-group>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-update-completion-scoring-mode')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="updateCompletionScoringMode(questions[currentPage-1].id)">
          Update
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-attribution"
      ref="modalAttribution"
      hide-footer
      title="Attribution"
    >
      <div v-if="questions[currentPage-1]">
        <span v-html="questions[currentPage - 1].attribution !== null
          ? questions[currentPage - 1].attribution
          : autoAttributionHTML
        "
        />
      </div>
    </b-modal>
    <div v-if="modalEnrollInCourseIsShown" style="height: 375px" />
    <b-modal
      id="modal-not-updated"
      ref="modalNotUpdated"
      hide-footer
      title="Not Updated"
    >
      <b-container>
        <b-row>
          <span class="font-weight-bold" style="font-size: large">
            {{ submissionDataMessage }}
          </span>
        </b-row>
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
        <span class="font-weight-bold" style="font-size: large" v-html="submissionDataMessage" />
      </b-alert>
    </b-modal>
    <b-modal
      id="modal-confirm-submission"
      ref="modalConfirmSubmission"
      hide-header-close
      no-close-on-backdrop
      no-close-on-esc
      title="Preview"
      @shown="renderMathJax()"
    >
      <div class="d-flex justify-content-center">
        <table class="table table-striped pb-3" style="width:100%">
          <thead>
            <tr>
              <th scope="col">
                Submission
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, itemIndex) in unconfirmedSubmission"
                :key="`unconfirmed-submission-${itemIndex}`"
            >
              <td>{{ item ? item : 'Nothing submitted' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="cancelWebworkSubmission()"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="completeSubmission()"
        >
          Complete Submission
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-assignment-completed"
             title="Assignment Completed"
             size="lg"
             hide-footer
    >
      You have submitted responses to all questions in this assignment.
    </b-modal>
    <b-modal
      id="modal-submission-accepted"
      ref="modalSubmissionAccepted"
      :hide-footer="!learningTreeMessage"
      :title="modalSubmissionAcceptedTitle"
      size="lg"
      @hidden="hideModalSubmissionAccepted"
    >
      <div v-if="learningTreeMessage">
        {{ submissionDataMessage }}
      </div>
      <div v-if="questions[currentPage - 1] && questions[currentPage - 1].report">
        Be sure to paste the different sections of the report in the form below.
      </div>
      <b-container>
        <b-row v-if="submissionArray && questions[currentPage - 1] &&
          questions[currentPage - 1].submission_array &&
          questions[currentPage - 1].submission_array.length"
        >
          <ul v-show="scoringType === 'p'" class="font-weight-bold p-0" style="list-style-type: none">
            <li>Total points: {{ sumArrBy(questions[currentPage - 1].submission_array, 'points', 4) }}</li>
            <li>Percent correct: {{ sumArrBy(questions[currentPage - 1].submission_array, 'percent') }}%</li>
          </ul>
          <div class="table-responsive">
            <table class="table table-striped pb-3">
              <thead>
                <tr>
                  <th scope="col">
                    Submission
                  </th>
                  <th scope="col">
                    Result
                  </th>
                  <th v-if="user.role === 2 && questions[currentPage-1].technology === 'webwork'" scope="col">
                    Correct Answer
                  </th>
                  <th v-if="scoringType === 'p'" scope="col">
                    Points
                  </th>
                  <th v-if="scoringType === 'p'" scope="col">
                    Percent
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, itemIndex) in questions[currentPage-1].submission_array"
                    :key="`submission-result-${itemIndex}`"
                >
                  <td>
                    <span :class="item.correct ? 'text-success' : 'text-danger'">
                      {{ item.submission ? item.submission : 'Nothing submitted' }}
                    </span>
                  </td>
                  <td>
                    <span v-show="item.correct" class="text-success">Correct</span>
                    <span v-show="!item.correct" class="text-danger">
                      {{ item.partial_credit ? 'Partial Credit' : 'Incorrect' }}
                    </span>
                  </td>
                  <td v-if="user.role === 2 && questions[currentPage-1].technology === 'webwork'">
                    <span :class="item.correct ? 'text-success' : 'text-danger'">{{ item.correct_ans }}</span>
                  </td>
                  <td v-if="scoringType === 'p'">
                    <span :class="item.correct ? 'text-success' : 'text-danger'">
                      {{ item.points }}</span>
                  </td>
                  <td v-if="scoringType === 'p'">
                    <span :class="item.correct ? 'text-success' : 'text-danger'">{{ item.percent }}%</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </b-row>
      </b-container>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="$bvModal.hide('modal-submission-accepted')">
          Cancel
        </b-button>
        <b-button v-show="assessmentType === 'learning tree'"
                  size="sm"
                  variant="success"
                  @click="enterLearningTree"
        >
          View Learning Tree
        </b-button>
      </template>
    </b-modal>
    <div v-show="user.role === 3 && clickerStatus === 'neither_view_nor_submit'">
      <p class="pl-3 pt-2">
        {{ clickerMessage }}
      </p>
    </div>
    <b-alert :show="showInvalidAssignmentMessage" variant="info">
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
    <EnrollInCourse :is-lms="isLMS" />
    <Email id="contact-grader-modal"
           ref="email"
           extra-email-modal-text="Before you contact your grader, please be sure to look at the solutions first, if they are available."
           :from-user="user"
           title="Contact Grader"
           type="contact_grader"
           :subject="getSubject()"
    />
    <CannotAddAssessmentToBetaAssignmentModal />
    <b-modal
      id="modal-cannot-update-solution"
      ref="modalCannotUpdateSolutionIfBetaAssignment"
      title="Cannot Update Solution"
      hide-footer
    >
      Since this a Beta assignment tethered to an Alpha assignment, you cannot update update the solution.
    </b-modal>
    <b-modal
      id="modal-cannot-update-points-if-beta-assignment"
      ref="modalCannotUpdatePointsIfBetaAssignment"
      title="Cannot Update"
      hide-footer
    >
      You are trying to update the points on a Beta assignment. Since this assignment is tethered to an Alpha
      assignment, you cannot update this value.
    </b-modal>
    <b-modal
      id="modal-should-not-edit-question-source-if-beta-assignment"
      ref="modalShouldNotEditQuestionSourceIfBetaAssignment"
      title="Should Not Edit"
      hide-footer
    >
      <p>
        You are trying to edit a question that is part of a Beta assignment. If you edit the question source, it will
        affect all other Beta assignments. Please get in touch with the Alpha instructor to see if an edit is possible.
      </p>
      <p>Please go to Tethered Courses in the Course Properties for their contact information.</p>
    </b-modal>
    <b-modal
      id="modal-properties"
      ref="modalProperties"
      title="Properties"
      size="xl"
      hide-footer
      @shown="createQrCode"
    >
      <b-container>
        <div class="pb-4">
          <b-card>
            <template #header>
              <h6 class="mb-0">
                Identifiers
              </h6>
            </template>
            <div class="mb-2">
              ADAPT ID: <span id="adaptID">{{ adaptId }}</span>
              <span class="text-info">
                <a
                  href=""
                  class="pr-1"
                  aria-label="Copy ADAPT ID"
                  @click.prevent="doCopy('adaptID')"
                >
                  <font-awesome-icon :icon="copyIcon" />
                </a>
              </span>
            </div>
            <div v-if="!formativeQuestionURL" class="mb-2">
              ADAPT URL: <span id="currentURL">{{
                currentUrl
              }}</span>
              <span class="text-info">
                <a
                  href=""
                  class="pr-1"
                  aria-label="Copy ADAPT URL"
                  @click.prevent="doCopy('currentURL')"
                >
                  <font-awesome-icon :icon="copyIcon" />
                </a>
              </span>
              <div class="mb-2 flex d-flex">
                QR Code
                <QuestionCircleTooltip id="summative-qr-code-tooltip" class="ml-1" />
                <b-tooltip target="summative-qr-code-tooltip" delay="250"
                           triggers="hover focus"
                >
                  If logged in, students can summatively attempt this question by using this QR code. You can copy the
                  code by right-clicking it.
                </b-tooltip>
                <div id="qrCodeCanvas" ref="qrCodeCanvas" class="ml-2" />
              </div>
            </div>
            <div v-if="formativeQuestionURL">
              <div class="mb-2">
                Formative URL
                <QuestionCircleTooltip id="formative-url-tooltip" />
                <b-tooltip target="formative-url-tooltip" delay="250"
                           triggers="hover focus"
                >
                  Students can formatively attempt this question by visiting this URL.
                </b-tooltip>
                <span id="formative_question_url">{{ formativeQuestionURL }}</span> <a
                  href=""
                  class="pr-1"
                  aria-label="Copy formative question URL"
                  @click.prevent="doCopy('formative_question_url')"
                >
                  <font-awesome-icon :icon="copyIcon" />
                </a>
              </div>
              <div class="mb-2 flex d-flex">
                QR Code
                <QuestionCircleTooltip id="formative-qr-code-tooltip" class="ml-1" />
                <b-tooltip target="formative-qr-code-tooltip" delay="250"
                           triggers="hover focus"
                >
                  Students can formatively attempt this question by using this QR code.
                </b-tooltip>
                <div id="qrCodeCanvas" ref="qrCodeCanvas" class="ml-2" />
              </div>
            </div>
            <div v-if="!['text','qti'].includes(technology)" class="mb-2">
              Technology:
              <span id="technology" class="text-hide">
                {{ technology }}
              </span>
              {{ formattedTechnology }}
              <span class="text-info">
                <a
                  href=""
                  class="pr-1"
                  aria-label="Copy Technology"
                  @click.prevent="doCopy('technology')"
                >
                  <font-awesome-icon :icon="copyIcon" />
                </a>
              </span>
            </div>
            <div v-if="a11yTechnologySrc" class="mb-2">
              A11y Technology URL: <span id="a11yTechnologySrc"
                                         v-html="a11yTechnologySrc"
              />
            </div>
            <div v-if="questions[currentPage - 1] && technology !== 'text'" class="mb-2">
              <span v-show="false" id="embed_formatively">
                {{ technology }}:{{ questions[currentPage - 1].technology_id }}
              </span>
              <span v-if="technology !== 'qti'">Embed Formatively: {{
                technology
              }}:{{ questions[currentPage - 1].technology_id }} <a
                href=""
                class="pr-1"
                aria-label="Copy Technology"
                @click.prevent="doCopy('embed_formatively')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
              </span>
            </div>
            <div v-if="technologySrc" class="mb-2">
              Technology URL: <span id="technologySrc"
                                    v-html="technologySrc"
              />
              <span id="technology_src" class="text-hide">
                {{ questions[currentPage - 1].technology_src }}
              </span>
              <span class="text-info">
                <a
                  href=""
                  class="pr-1"
                  aria-label="Copy Technology"
                  @click.prevent="doCopy('technology_src')"
                >
                  <font-awesome-icon :icon="copyIcon" />
                </a>
              </span>
            </div>
          </b-card>
        </div>
        <div v-if="questions && questions[currentPage-1]" class="pb-4">
          <b-card>
            <template #header>
              <h6 class="mb-0">
                Embed Settings
              </h6>
            </template>
            <IframeInformation :assignment-information-shown-in-i-frame="assignmentInformationShownInIFrame"
                               :submission-information-shown-in-i-frame="submissionInformationShownInIFrame"
                               :attribution-information-shown-in-i-frame="attributionInformationShownInIFrame"
                               :parent-update-shown-in-i-frame="parentUpdateShownInIFrame"
            />
          </b-card>
        </div>
        <b-card>
          <template #header>
            <h6 class="mb-0">
              Description and Attribution
            </h6>
          </template>
          <b-form-group
            id="private_description"
            label-cols-sm="3"
            label-cols-lg="2"
          >
            <template v-slot:label>
              Private Description
              <QuestionCircleTooltip :id="'private-description-tooltip'" />
              <b-tooltip target="private-description-tooltip" delay="250"
                         triggers="hover focus"
              >
                An optional description for the assessment. This description will only be viewable by you.
              </b-tooltip>
            </template>
            <b-form-textarea
              id="private_description"
              v-model="propertiesForm.private_description"
              rows="2"
              max-rows="2"
            />
          </b-form-group>
          <b-form-group
            id="attribution"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Attribution"
            label-for="attribution"
          >
            <b-form-row>
              <toggle-button
                :width="90"
                class="mt-2"
                :value="autoAttribution"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="toggleColors"
                :labels="{checked: 'Auto', unchecked: 'Custom'}"
                @change="autoAttribution = !autoAttribution"
              />
            </b-form-row>
            <b-form-row v-show="autoAttribution">
              <span v-show="!autoAttributionHTML.length">No licensing information is available.</span>
              <span v-show="autoAttributionHTML.length" class="ml-2" v-html="autoAttributionHTML" />
            </b-form-row>
            <ckeditor v-show="!autoAttribution"
                      v-model="propertiesForm.attribution"
                      :config="richEditorConfig"
                      tabindex="0"
                      @ready="handleFixCKEditor()"
            />
          </b-form-group>
          <b-button size="sm" @click="$bvModal.hide('modal-properties')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary" @click="updateProperties()">
            Update
          </b-button>
        </b-card>
      </b-container>
    </b-modal>
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
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-reset-to-default-text')">
          Cancel
        </b-button>
        <b-button size="sm" variant="danger" @click="submitResetDefaultOpenEndedText">
          Delete Submission And Reset Default Text
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-remove-solution"
      ref="modal"
      title="Confirm Remove Solution"
    >
      <p>
        Please confirm that you would like to remove this solution. Note that you will still be able to upload
        a different solution at any time.
      </p>
      <p v-if="questions.length && questions[currentPage-1].solution_html">
        Note that students will still have access to the Libretexts solution.
      </p>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="$bvModal.hide('modal-remove-solution')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="submitRemoveSolution">
          Yes, remove this solution!
        </b-button>
      </template>
    </b-modal>
    <CannotDeleteAssessmentFromBetaAssignmentModal />
    <b-modal
      id="modal-remove-question"
      ref="modal"
      title="Confirm Remove Question"
    >
      <RemoveQuestion :beta-assignments-exist="betaAssignmentsExist" />
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
      :title="getModalUploadFileTitle()"
      ok-title="Submit"
      size="lg"
      hide-footer
      @hidden="showAudioUploadComponent = true;handleCancel()"
    >
      <span v-if="user.role === 2">
        <toggle-button
          class="mt-1"
          :width="105"
          :value="solutionTypeIsPdfImage"
          :sync="true"
          :font-size="14"
          :margin="4"
          :color="toggleColors"
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
                      tabindex="0"
                      :class="{ 'is-invalid': solutionTextForm.errors.has('solution_text') }"
                      @keydown="solutionTextForm.errors.clear('solution_text')"
                      @ready="handleFixCKEditor()"
            />
            <has-error :form="solutionTextForm" field="solution_text" />
          </div>
          <div>
            <span class="float-right"><b-button variant="primary" @click="submitSolutionText">Save Text</b-button></span>
          </div>
        </div>
      </div>

      <div v-if="solutionTypeIsPdfImage">
        <p>
          <span v-if="user.role === 2">Upload an entire PDF with one solution per page and let ADAPT cut up the PDF for you. Or, upload one
            solution at a time. If you upload a full PDF, students will be able to both download a full solution key
            and download solutions on a per question basis.</span>
        </p>
        <p v-if="user.role === 2">
          <span><span class="font-weight-bold">Important:</span> For best results, don't crop any of your pages.  In addition, please make sure that they are all oriented in the same direction.</span>
        </p>
        <b-form ref="form">
          <b-form-group v-show="user.role !== 3">
            <b-form-radio v-model="uploadLevel" name="uploadLevel" value="assignment"
                          @click="showCurrentFullPDF = true"
            >
              Upload
              <span v-if="user.role === 2">solutions from a single PDF that ADAPT can cutup for you.</span>
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
            <p v-show="user.role === 3">
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
                    <has-error :form="cutupsForm" field="chosen_cutups" />
                  </b-col>
                  <b-col lg="8" class="ml-3">
                    <b-row>
                      <b-button class="mt-1" size="sm" variant="outline-primary"
                                @click="setCutupAsSolution(questions[currentPage-1].id)"
                      >
                        Set As Solution
                      </b-button>
                      <span v-show="settingAsSolution" class="ml-2">
                        <b-spinner small type="grow" />
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
                    <has-error :form="questionSubmissionPageForm" field="page" />
                  </b-col>
                  <b-col lg="8" class="ml-3">
                    <b-row>
                      <b-button class="mt-1" size="sm" variant="outline-primary"
                                @click="setPageAsSubmission(questions[currentPage-1].id)"
                      >
                        Set As Question File Submission
                      </b-button>
                      <span v-show="settingAsSolution" class="ml-2">
                        <b-spinner small type="grow" />
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
          <b-container>
            <hr v-show="user.role !== 3">
            <file-upload
              v-if="isOpenEndedAudioSubmission"
              ref="upload"
              v-model="files"
              accept=".mp3"
              put-action="/put.method"
              @input-file="inputFile"
              @input-filter="inputFilter"
            />
            <file-upload
              v-if="!isOpenEndedAudioSubmission"
              ref="upload"
              v-model="files"
              put-action="/put.method"
              @input-file="inputFile"
              @input-filter="inputFilter"
            />
          </b-container>
          <div v-show="uploadLevel === 'question' || !showCurrentFullPDF">
            <div class="example-drag">
              <div class="upload mt-3">
                <ul v-if="files.length && (preSignedURL !== '')">
                  <li v-for="file in files" :key="file.id">
                    <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
                      file.name
                    }}</span> -
                    <span>{{ formatFileSize(file.size) }} </span>
                    <span v-if="file.size > 10000000">Note: large files may take up to a minute to process.</span>
                    <span v-if="file.error" class="text-danger">Error: {{ file.error }}</span>
                    <span v-else-if="file.active" class="ml-2">
                      <b-spinner small type="grow" />
                      Uploading File...
                    </span>
                    <span v-if="processingFile">
                      <b-spinner small type="grow" />
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
      <div
        v-if="questions !==['init'] && !inIFrame && !cannotViewAssessmentMessage && !presentationMode && !clickerApp"
      >
        <PageTitle :title="getTitle(currentPage)"
                   :adapt-id="getAdaptId()"
                   :learning-tree-id="getLearningTreeId()"
                   :show-formative-warning="questions[currentPage - 1] && questions[currentPage - 1].is_formative_question"
                   :show-pencil="user && user.role===2"
                   :assignment-id="+assignmentId"
                   :question-id="questions.length && questions[currentPage-1].id"
                   @updateCustomQuestionTitle="updateCustomQuestionTitle"
        />
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
              <span v-if="showScores">  You received a score of {{
                questions[currentPage - 1].submission_score
              }}.</span></span>
          </b-alert>
        </div>
      </div>
      <div v-if="questions.length && !initializing && !isLoading">
        <UpdateRevision :key="`update-revision`"
                        :assignment-id="+assignmentId"
                        :current-question="questions[currentPage-1]"
                        :pending-question-revision="pendingQuestionRevision"
                        :latest-question-revision-id="questions[currentPage-1].question_revision_id_latest"
                        :assignment-name="name"
                        :question-number="currentPage"
                        @reloadSingleQuestion="reloadSingleQuestion"
        />
        <div v-show="isInstructorLoggedInAsStudent">
          <LoggedInAsStudent :student-name="user.first_name + ' ' + user.last_name" />
        </div>
        <div v-if="inIFrame && (user.role === 2)">
          <b-button variant="primary" size="sm" class="mb-3" @click="viewInADAPT">
            View in ADAPT
          </b-button>
          <b-alert variant="info" :show="true">
            <strong>You are currently logged in as an instructor. No responses will be saved.</strong>
          </b-alert>
        </div>
      </div>
      <div v-if="user.role === 3 && cannotViewAssessmentMessage">
        <b-alert variant="info" show>
          <span v-if="!assignmentShown" class="font-weight-bold">
            This assessment is part of an assignment which is not yet being shown to any students in this course.
          </span>
          <span v-if="assignmentShown" class="font-weight-bold">
            This assignment will become available on {{
              $moment(availableOn, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY')
            }} at {{ $moment(availableOn, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}.
          </span>
        </b-alert>
      </div>
      <div v-if="user.role === 3 && launchThroughLMSMessage">
        <b-alert variant="info" show>
          <span class="font-weight-bold">
            This assessment is part of an assignment which should be initially launched through your LMS
            so that ADAPT can pass back your score.  Please log into your LMS and launch the assignment
            "{{ name }}".
          </span>
        </b-alert>
      </div>
      <div
        v-if="hasAtLeastOneSubmission
          && !presentationMode
          && !inIFrame
          && !isLoading
          && user.role === 2
          && !isInstructorWithAnonymousView
          && !isFormative
          && !clickerApp"
      >
        <b-alert variant="info" :show="true">
          <p>
            This problem is locked. Since students have already submitted responses, you cannot update the
            points per question nor change the open-ended submission type.
          </p>
          <p v-if="!showUpdatePointsPerQuestion">
            In addition, since you are computing points by question weights, you will not be able to remove the
            question
            as it will affect already submitted questions.
          </p>
        </b-alert>
      </div>
      <div v-if="user.role === 2 && !inIFrame && !isLoading && !isInstructorWithAnonymousView">
        <AssessmentTypeWarnings :beta-assignments-exist="betaAssignmentsExist" />
      </div>
      <div v-if="user.role === 2 && questions[currentPage-1] && questions[currentPage-1].h5p_non_adapt">
        <b-alert variant="info" show>
          This H5P question has type "{{ questions[currentPage - 1].h5p_non_adapt }}" which is not on the <a
            href="https://chem.libretexts.org/Courses/Remixer_University/Mastering_ADAPT%3A_A_User%27s_Guide/07%3A_Building_H5P_Assessments/H5P-ADAPT_Assessment_Status"
            target="blank"
          >list of Adapt ready H5P questions</a>.
          Please attempt this question in Student View to verify that it is working as expected.
        </b-alert>
      </div>
      <div v-if="questions.length && !cannotViewAssessmentMessage && !launchThroughLMSMessage">
        <div :class="assignmentInformationMarginBottom">
          <b-container>
            <div v-if="isInstructor() && assessmentType === 'clicker'" class="mb-2">
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
                  :color="toggleColors"
                  :labels="{checked: 'On', unchecked: 'Off'}"
                  @change="togglePresentationMode()"
                />
              </h5>
              <PageTitle v-show="presentationMode && loaded"
                         :title="`Question #${currentPage}`"
                         :show-pencil="false"
                         @updateCustomQuestionTitle="updateCustomQuestionTitle"
              />
            </div>

            <ul v-if="!clickerApp" style="list-style-type:none" class="p-0">
              <li
                v-if="isInstructor() && !isInstructorWithAnonymousView && assessmentType !== 'clicker' && !inIFrame"
                class="mb-2"
              >
                <span class="font-weight-bold">Question View:</span>
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
              </li>
              <li v-if="assessmentType !== 'clicker'
                && user.role === 3
                && ((!isFormative && !inIFrame && timeLeft>0)
                || (inIFrame && showAssignmentInformation))"
              >
                <span v-if="showCountdown">
                  <countdown :time="timeLeft"
                             @end="endClickerAssessment"
                  >
                    <template v-slot="props">
                      <span v-html="getTimeLeftMessage(props, assessmentType)" />
                    </template>
                  </countdown>
                  <b-button size="sm" variant="outline-info" @click="showCountdown = false">
                    Hide Time Until Due
                  </b-button>
                </span>
                <span v-if="!showCountdown" class="pt-1">
                  <b-button size="sm" variant="outline-info" @click="showCountdown = true">
                    Show Time Until Due
                  </b-button>
                </span>
              </li>
              <li v-if="studentShowPointsNonClicker()" class="font-weight-bold">
                This question is worth
                {{ 1 * (questions[currentPage - 1].points) }}
                point{{ 1 * (questions[currentPage - 1].points) !== 1 ? 's' : '' }}.
              </li>
              <li
                v-if="studentNonClicker() && assessmentType === 'real time'
                  && numberOfAllowedAttempts === 'unlimited'
                  && !isFormative"
              >
                {{ questions[currentPage - 1].submission_count }}/<span><span
                  style="font-size:x-large;position: relative;bottom: -2px"
                >&infin;</span> attempts</span>
              </li>
              <li>
                <span v-if="['real time','learning tree'].includes(assessmentType)
                  && canViewHint
                  && questions[currentPage-1].hint_exists"
                >
                  <b-button
                    size="sm"
                    variant="info"
                    @click="!questions[currentPage-1].shown_hint
                      ? $bvModal.show('modal-confirm-show-hint')
                      : $bvModal.show('modal-hint')"
                  >
                    Show Hint
                  </b-button>
                </span>
                <span v-if="studentNonClicker()
                  && ['real time'].includes(assessmentType)
                  && numberOfAllowedAttempts !== '1'
                  && !questions[currentPage-1].solution_type
                  && (questions[currentPage-1].solution_exists || questions[currentPage-1].qti_json)"
                >
                  <b-button
                    size="sm"
                    variant="primary"
                    @click="questions[currentPage-1].submission_count || questions[currentPage-1].can_give_up
                      ? $bvModal.show('modal-confirm-give-up')
                      : $bvModal.show('modal-cannot-give-up-yet')"
                  >
                    I Give Up
                  </b-button>
                  <QuestionCircleTooltip id="i-give-up-tooltip" class="ml-1" />
                  <b-tooltip target="i-give-up-tooltip" delay="250"
                             triggers="hover focus"
                  >
                    If you choose this option, you will be shown the solution to the question and will no longer to be able
                    to re-submit, regardless of whether you have resets available. In addition, you will be awarded the current number
                    of points that you currently have.
                  </b-tooltip>
                </span>
              </li>
            </ul>
            <div
              v-if="studentNonClicker() && assessmentType === 'real time' && numberOfAllowedAttempts !== 'unlimited' && scoringType === 'p'"
            >
              {{ numberOfRemainingAttempts }}
            </div>
            <div
              v-if="studentShowPointsNonClicker()
                && assessmentType === 'real time'
                && numberOfAllowedAttempts !== '1'
                && numberOfAllowedAttemptsPenalty
                && !isFormative"
            >
              Next Attempt Points: {{ maximumNumberOfPointsPossible }}
              <QuestionCircleTooltip :id="'real-time-per-attempt-penalty-tooltip'" />
              <b-tooltip target="real-time-per-attempt-penalty-tooltip" delay="250"
                         triggers="hover focus"
              >
                A per attempt penalty of {{ numberOfAllowedAttemptsPenalty }}% is applied after the first
                attempt. {{ getHintPenaltyMessage() }} Applying any penalty, the maximum number of points possible for
                the next attempt is
                {{ maximumNumberOfPointsPossible }} points.
              </b-tooltip>
            </div>
            <div
              v-if="studentNonClicker() || (user.role === 3 && assessmentType === 'clicker' && solutionsReleased)"
            >
              <div v-if="isLocalMe && questions[currentPage-1].qti_json && false">
                Qti Json: {{ questions[currentPage - 1].qti_json }}<br><br>
                Qti Answer Json: {{ questions[currentPage - 1].qti_answer_json }}<br><br>
                Solution html: {{ questions[currentPage - 1].solution_html }}<br><br>
                Solution: {{ questions[currentPage - 1].solution }}<br><br>
              </div>
              <ul v-if="caseStudyNotesByQuestion.length && !user.formative_student" style="list-style:none"
                  class="pl-0 pb-0"
              >
                <li>
                  <span class="font-weight-bold">Submitted At:
                    <span
                      :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                    >{{
                      questions[currentPage - 1].last_submitted
                    }} </span>
                  </span>
                </li>
                <li v-if="showScores">
                  <span class="font-weight-bold">Score: {{
                    questions[currentPage - 1].submission_score
                  }}</span>
                </li>
              </ul>
              <SolutionFileHtml v-if="questions[currentPage-1].solution || questions[currentPage-1].solution_html"
                                :key="`solution-file-html-key-${questions[currentPage-1].solution || questions[currentPage-1].solution_html}`"
                                :questions="questions"
                                :current-page="currentPage"
                                class="pr-2"
                                :assignment-name="name"
                                :use-view-solution-as-text="true"
              />
              <span v-if="questions[currentPage-1].qti_answer_json">
                <QtiJsonAnswerViewer v-if="questions[currentPage-1].qti_answer_json"
                                     :key="`modal-answer-${questions[currentPage-1].id}`"
                                     :modal-id="questions[currentPage-1].id"
                                     :qti-json="questions[currentPage-1].qti_answer_json"
                />
                <b-button
                  size="sm"
                  variant="outline-info"
                  @click="$bvModal.show(`qti-answer-${questions[currentPage-1].id}`)"
                >
                  View Correct Answer
                </b-button>
              </span>
            </div>

            <div v-if="studentNonClicker() && completionScoringModeMessage">
              <span class="font-weight-bold" v-html="completionScoringModeMessage" />
            </div>
            <div
              v-if="studentNonClicker()
                && (parseInt(questions[currentPage - 1].submission_count) === 0 || questions[currentPage - 1].late_question_submission) && latePolicy === 'deduction' && timeLeft === 0"
            >
              <b-alert variant="warning" show>
                <span class="alert-link">
                  This submission will be marked late.</span>
              </b-alert>
            </div>
            <div v-if="instructorInNonBasicView() && !clickerApp">
              <div id="action-icons" class="pb-1">
                <a id="question-properties-tooltip" href="" class="p-1" @click.prevent="openModalProperties()">
                  <b-icon icon="gear"
                          aria-label="Properties"
                          class="text-muted"
                          scale="1.2"
                  />
                </a>
                <b-tooltip target="question-properties-tooltip"
                           delay="750"
                           triggers="hover"
                >
                  View the question's properties
                </b-tooltip>
                <CloneQuestion
                  :key="`copy-question-${questions[currentPage - 1].id}`"
                  :assignment-id="+assignmentId"
                  class="pl-1"
                  :question-id="questions[currentPage - 1].id"
                  :question-editor-user-id="questions[currentPage - 1].question_editor_user_id"
                  :title="questions[currentPage - 1].title"
                  :license="questions[currentPage - 1].license"
                  :public="questions[currentPage - 1].public"
                  :library="questions[currentPage - 1].library"
                  :non-technology="+questions[currentPage - 1].non_technology"
                  @reloadQuestions="getSelectedQuestions(assignmentId, questions[currentPage - 1].id)"
                />
                <RefreshQuestion v-if="false"
                                 :assignment-id="parseInt(assignmentId)"
                                 :question-id="questions[currentPage - 1].id"
                                 :reload-question-parent="reloadQuestionParent"
                                 :icon="true"
                />
                <a v-if="questionView !== 'basic' && assessmentType !== 'learning tree'"
                   id="edit-question-tooltip"
                   class="p-1"
                   href=""
                   @click.prevent="editQuestionSource(questions[currentPage-1])"
                >
                  <b-icon icon="pencil"
                          aria-label="Edit Question"
                          class="text-muted"
                          scale="1.1"
                  />
                </a>
                <b-tooltip target="edit-question-tooltip"
                           delay="750"
                           triggers="hover"
                >
                  Edit the question
                </b-tooltip>
                <a v-if="questionView !== 'basic' && assessmentType === 'learning tree'"
                   id="edit-learning-tree-tooltip"
                   class="p-1"
                   href=""
                   @click.prevent="editLearningTree(questions[currentPage-1].learning_tree_id)"
                >
                  <b-icon icon="pencil"
                          aria-label="Edit Learning Tree"
                          class="text-muted"
                          scale="1.1"
                  />
                </a>
                <b-tooltip target="edit-learning-tree-tooltip"
                           delay="750"
                           triggers="hover"
                >
                  Edit the learning tree
                </b-tooltip>
                <a id="remove-question-tooltip"
                   href=""
                   class="p-1"
                   @click.prevent="openRemoveQuestionModal()"
                >
                  <b-icon icon="trash"
                          aria-label="Remove Question"
                          class="text-muted"
                          scale="1.1"
                  />
                </a>
                <b-tooltip target="remove-question-tooltip"
                           delay="750"
                           triggers="hover"
                >
                  Remove question from the assignment
                </b-tooltip>

                <span v-if="!myFavoriteQuestionIds.includes(questions[currentPage-1].id)">
                  <a id="add-to-favorites-tooltip"
                     href=""
                     class="p-1"
                     @click.prevent="addQuestionToFavorites('single')"
                  >
                    <font-awesome-icon :icon="heartIcon"
                                       aria-label="Add To My Favorites"
                                       class="text-muted"
                                       scale="1.1"
                    />
                  </a>
                  <SavedQuestionsFolders
                    ref="savedQuestionsFolders"
                    :type="'my_favorites'"
                    :init-saved-questions-folder="myFavoritesFolder"
                    :create-modal-add-saved-questions-folder="true"
                    :question-source-is-my-favorites="false"
                    @savedQuestionsFolderSet="setMyFavoritesFolder"
                  />
                  <b-tooltip target="add-to-favorites-tooltip"
                             delay="750"
                             triggers="hover"
                  >
                    Add question to My Favorites
                  </b-tooltip>
                </span>
                <span v-if="myFavoriteQuestionIds.includes(questions[currentPage-1].id)">
                  <a id="remove-from-favorites-tooltip"
                     href=""
                     class="p-1"
                     @click.prevent="removeMyFavoritesQuestion()"
                  >
                    <font-awesome-icon :icon="heartIcon"
                                       aria-label="Add To My Favorites"
                                       class="text-danger"
                                       scale="1.1"
                    />
                  </a>
                  <b-tooltip target="remove-from-favorites-tooltip"
                             delay="750"
                             triggers="hover"
                  >
                    Remove question from My Favorites
                  </b-tooltip>
                </span>
              </div>
              <b-form-row v-show="!isFormative" style="margin-left:0">
                This question is worth <span v-show="!showUpdatePointsPerQuestion" class="pl-1 pr-1"> {{ questions[currentPage - 1].points }} </span>
                <b-form-input
                  v-if="showUpdatePointsPerQuestion"
                  id="points"
                  v-model="questionPointsForm.points"
                  size="sm"
                  type="text"
                  placeholder=""
                  style="width:50px"
                  :class="{ 'is-invalid': questionPointsForm.errors.has('points') }"
                  class="ml-2 mr-2"
                  @keydown="enteredPoints =true;questionPointsForm.errors.clear('points')"
                />
                <has-error v-if="showUpdatePointsPerQuestion" :form="questionPointsForm" field="points" />
                point{{ 1 * (questions[currentPage - 1].points) !== 1 ? 's' : '' }}<span
                  v-show="showUpdatePointsPerQuestion"
                >.</span>
                <span v-show="!showUpdatePointsPerQuestion" class="pl-1"> with a weight of</span>
                <b-form-input
                  v-if="!showUpdatePointsPerQuestion"
                  id="weight"
                  v-model="questionWeightForm.weight"
                  size="sm"
                  type="text"
                  placeholder=""
                  style="width:60px"
                  :class="{ 'is-invalid': questionWeightForm.errors.has('weight') }"
                  class="ml-2 mr-2"
                  @keydown="enteredPoints = true;questionWeightForm.errors.clear('weight')"
                />
                <b-col>
                  <div class="float-left">
                    <b-button :variant="enteredPoints && !hasAtLeastOneSubmission ? 'success' : 'primary'"
                              size="sm"
                              class="mb-2"
                              :disabled="hasAtLeastOneSubmission"
                              @click="showUpdatePointsPerQuestion ? updatePoints(questions[currentPage-1].id): updateWeight(questions[currentPage-1].id)"
                    >
                      Update <span v-show="showUpdatePointsPerQuestion">Points</span><span
                        v-show="!showUpdatePointsPerQuestion"
                      >Weight</span>
                    </b-button>
                  </div>
                </b-col>
              </b-form-row>
              <b-row align-h="center">
                <span class="pr-1 font-weight-bold" v-html="completionScoringModeMessage" />
                <a href="" @click.prevent="openUpdateCompletionScoringModeModal()">
                  <b-icon v-if="completionScoringModeMessage"
                          icon="pencil"
                          class="text-muted"
                          aria-label="Edit completion scoring mode"
                  />
                </a>
              </b-row>
            </div>
            <b-form-row v-if="instructorInNonBasicView() && openEndedSubmissionTypeAllowed" style="margin-left:0">
              <span class="pr-2">
                Open-Ended Submission Type:
              </span>
              <b-form-select v-model="openEndedSubmissionType"
                             :options="compiledPDF ? openEndedSubmissionCompiledPDFTypeOptions : openEndedSubmissionTypeOptions"
                             style="width:100px"
                             size="sm"
                             @change="initUpdateOpenEndedSubmissionType(questions[currentPage-1].id)"
              />
            </b-form-row>

            <div v-if="instructorInNonBasicView() && !clickerApp">
              <span v-if="!questions[currentPage-1].solution && false">
                <b-button
                  class="mt-2 mb-2 ml-1"
                  variant="dark"
                  size="sm"
                  @click="isBetaAssignment
                    ? $bvModal.show('modal-cannot-update-solution')
                    : initOpenUploadSolutionModal()"
                >
                  Upload Local Solution
                </b-button>
                <a id="local_solution_tooltip"
                   href="#"
                >
                  <b-icon class="text-muted"
                          icon="question-circle"
                          aria-label="Explanation of local solutions"
                  />
                </a>
                <b-tooltip target="local_solution_tooltip"
                           triggers="hover focus"
                           delay="250"
                >
                  Optionally, you can provide your own solution. If this question has a Libretext solution
                  associated with it, your local solution will be shown to your students.
                </b-tooltip>
              </span>
              <b-button
                v-if="questions[currentPage-1].solution"
                class="mt-1 mb-2 ml-1"
                variant="danger"
                size="sm"
                @click="isBetaAssignment
                  ? $bvModal.show('modal-cannot-update-solution')
                  : $bvModal.show('modal-remove-solution')"
              >
                Remove Local Solution
              </b-button>
              <span v-if="questions[currentPage-1].solution || questions[currentPage-1].solution_html">
                <span v-if="!showUploadedAudioSolutionMessage">
                  <SolutionFileHtml :key="savedText"
                                    :questions="questions"
                                    :current-page="currentPage"
                                    :assignment-name="name"
                                    :modal-id="uniqueId().toString()"
                  />

                  <span v-if="showUploadedAudioSolutionMessage"
                        :class="uploadedAudioSolutionDataType"
                  >
                    {{ uploadedAudioSolutionDataMessage }}</span>
                </span>
                <span v-if="!questions[currentPage-1].solution && !questions[currentPage-1].solution_html">No solutions are available.</span>
              </span>
              <span v-if="questions[currentPage-1].qti_answer_json">
                <QtiJsonAnswerViewer
                  :modal-id="questions[currentPage-1].id"
                  :qti-json="questions[currentPage-1].qti_answer_json"
                />
                <b-button size="sm"
                          variant="outline-info"
                          @click="$bvModal.show(`qti-answer-${questions[currentPage-1].id}`)"
                >
                  View Correct Answer
                </b-button>
              </span>
            </div>
            <div
              v-if="user.role === 3 && showScores && isOpenEnded && questions[currentPage-1].grader_id && !isAnonymousUser"
            >
              You achieved a total score of
              {{ questions[currentPage - 1].total_score * 1 }}
              out of a possible
              {{ questions[currentPage - 1].points * 1 }} points.
            </div>
            <div v-if="showScores && showAssignmentStatistics && !isInstructor() && scores.length">
              <b-button variant="outline-primary" @click="openShowAssignmentStatisticsModal()">
                View Question
                Statistics
              </b-button>
            </div>
          </b-container>
        </div>

        <b-container v-if="!clickerApp">
          <span v-if="user.fake_student === 1">
            <b-button size="sm" @click="resetSubmission">Reset Submission</b-button>
            <QuestionCircleTooltip id="reset-submission-tooltip" />
            <b-tooltip target="reset-submission-tooltip" delay="250"
                       triggers="hover focus"
            >
              While in Student View, you can reset the submission which may aid in testing questions.
            </b-tooltip>
          </span>
          <hr v-if="user.role !== 5
            && !isAnonymousUser
            && !inIFrame
            && !user.formative_student
            && !presentationMode"
          >
          <div v-show="(!user.formative_student || (user.formative_student && !$route.params.questionId))"
               class="overflow-auto mt-2"
          >
            <b-pagination
              v-if="((assessmentType === 'clicker' && (user.role === 3 && solutionsReleased && !clickerApp)) ||
                (user.role === 2 && !presentationMode)) || (assessmentType !== 'clicker' && ((inIFrame && questionNumbersShownInIframe)
                || (!inIFrame && questionNumbersShownOutOfIframe && (assessmentType !== 'clicker' || isInstructor() || pastDue))))"
              v-model="currentPage"
              :total-rows="questions.length"
              :per-page="perPage"
              limit="22"
              first-number
              last-number
              @change="changePage($event)"
            />
          </div>
          <div v-if="user.role === 5" class="mt-2 mb-2">
            <b-button
              size="sm"
              variant="primary"
              @click.prevent="editQuestionSource(questions[currentPage-1])"
            >
              Edit Question
            </b-button>
            <SolutionFileHtml v-if="questions[currentPage-1].solution || questions[currentPage-1].solution_html"
                              :key="`instructor-solution-file-html-${cacheKey}`"
                              :questions="questions"
                              :current-page="currentPage"
                              class="pr-2"
                              :assignment-name="name"
                              :use-view-solution-as-text="true"
            />
            <span v-if="questions[currentPage-1].qti_answer_json">
              <QtiJsonAnswerViewer
                :modal-id="questions[currentPage-1].id"
                :qti-json="questions[currentPage-1].qti_answer_json"
              />
              <b-button size="sm"
                        variant="outline-info"
                        @click="$bvModal.show(`qti-answer-${questions[currentPage-1].id}`)"
              >
                View Correct Answer
              </b-button>
            </span>
          </div>
          <div v-if="[2,5].includes(user.role)" class="mb-2">
            <div v-show="!presentationMode">
              <b-button size="sm" @click="resetSubmission">
                Reset Submission
              </b-button>
            </div>
            <div class="mt-2">
              <div
                v-if="questions.length
                  && questions[currentPage-1].question_revision_id !== questions[currentPage-1].question_revision_id_latest
                  && !questions[currentPage-1].viewing_latest_revision"
              >
                <b-alert show variant="warning" class="text-center">
                  You are viewing question revision {{ questions[currentPage - 1].question_revision_number }}. This
                  question has a more up-to-date revision.
                  <span class="ml-2">
                    <b-button v-if="!processingUpdatingQuestionView"
                              size="sm"
                              variant="info"
                              @click="viewLatestRevision"
                    >
                      View Latest Revision
                    </b-button>
                    <span v-if="processingUpdatingQuestionView">
                      <b-spinner small type="grow" />
                      Updating Question...
                    </span>
                  </span>
                  <b-button
                    size="sm"
                    variant="primary"
                    @click.prevent="showLatestRevision()"
                  >
                    Update to Latest Revision
                  </b-button>
                </b-alert>
              </div>
              <div
                v-if="questions.length && questions[currentPage-1].question_revision_id_original"
              >
                <b-alert show variant="info" class="text-center">
                  You are viewing the most up-to-date revision; your assignment uses an older
                  revision.<span class="ml-2">
                    <b-button v-if="!processingUpdatingQuestionView" size="sm" variant="info"
                              @click="viewCurrentRevision();"
                    >
                      View Revision in Assignment
                    </b-button>
                    <span v-if="processingUpdatingQuestionView">
                      <b-spinner small type="grow" />
                      Updating Question...
                    </span>
                  </span>
                  <b-button
                    size="sm"
                    variant="primary"
                    @click.prevent="showLatestRevision()"
                  >
                    Update to Latest Revision
                  </b-button>
                </b-alert>
              </div>
            </div>
          </div>
        </b-container>
        <b-container
          v-if="questions[currentPage-1] && questions[currentPage-1].learning_tree_id"
        >
          <b-row class="pl-3 pb-2">
            <b-button
              v-show="user.role !== 3 || questions[currentPage-1].submission_count || questions[currentPage-1].at_least_one_learning_tree_node_submission"
              size="sm"
              variant="success"
              @click="enterLearningTree"
            >
              Enter Learning Tree
            </b-button>
            <span class="pl-2">

              <b-button v-show="questions[currentPage-1] && questions[currentPage-1].number_resets_available > 0"
                        size="sm"
                        variant="info"
                        @click="$bvModal.show('modal-reset-root-node-submission')"
              >
                Reset Submission
              </b-button>
            </span>
          </b-row>
          <ul v-show="user.role === 3" style="list-style:none;" class="pl-1">
            <li>
              <span class="font-weight-bold">
                Number of resets currently available:</span> {{ questions[currentPage - 1].number_resets_available }}
              <span>
                <QuestionCircleTooltip :id="'learning-tree-number-resets-available-tooltip'" />
                <b-tooltip target="learning-tree-number-resets-available-tooltip" delay="250"
                           triggers="hover focus"
                >
                  Resets are earned by completing paths within the learning tree.  With available resets, you can reset the original
                  submission and try again without penalty.
                </b-tooltip>
              </span>
            </li>
            <li>
              <span class="font-weight-bold">
                <span
                  v-show="(questions[currentPage-1].submission_count || questions[currentPage-1].at_least_one_learning_tree_node_submission) && canEarnLearningTreeReset()"
                >
                  Earn a reset for after completing {{
                    questions[currentPage - 1].number_of_successful_paths_for_a_reset
                  }} path<span
                    v-if="questions[currentPage - 1].number_of_successful_paths_for_a_reset>1"
                  >s</span>.
                </span>
                <span v-show="!canEarnLearningTreeReset()">
                  Completing additional paths will not give you additional resets.
                </span>
              </span>
            </li>
          </ul>
        </b-container>
        <div v-if="isInstructorWithAnonymousView && questions.length && !isLoading" class="pb-3">
          <div>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label="Filter By Question Type:"
              label-for="assessment_type"
            >
              <b-form-select id="assessment_type"
                             v-model="questionType"
                             style="width:280px"
                             class="mt-1"
                             :options="questionTypeOptions"
                             required
                             size="sm"
                             @change="filterByQuestionType($event)"
              />
              <span v-if="filteringByQuestionType" class="pl-2">
                <b-spinner small type="grow" />
                Updating view...
              </span>
            </b-form-group>
          </div>
          <CloneQuestion
            :key="`clone-question-commons-${questions[currentPage-1].id}`"
            class="pl-1"
            :as-button="true"
            :question-id="questions[currentPage-1].id"
            :question-editor-user-id="questions[currentPage-1].question_editor_user_id"
            :title="questions[currentPage-1].title"
            :license="questions[currentPage-1].license"
            :public="questions[currentPage-1].public"
            :library="questions[currentPage-1].library"
            :non-technology="questions[currentPage-1].non_technology"
            :big-icon="true"
          />
          <span v-if="!myFavoriteQuestionIds.includes(questions[currentPage-1].id)">
            <b-button
              variant="outline-secondary"
              size="sm"
              @click="addQuestionToFavorites('single')"
            >
              <font-awesome-icon :icon="heartIcon" />Add To My Favorites
            </b-button>
            <SavedQuestionsFolders
              ref="savedQuestionsFolders"
              :type="'my_favorites'"
              :init-saved-questions-folder="myFavoritesFolder"
              :create-modal-add-saved-questions-folder="true"
              :question-source-is-my-favorites="false"
              @savedQuestionsFolderSet="setMyFavoritesFolder"
            />
          </span>
          <span v-if="myFavoriteQuestionIds.includes(questions[currentPage-1].id)">

            <b-button size="sm" variant="outline-danger" @click="removeMyFavoritesQuestion()">
              Remove From My Favorites
            </b-button>
          </span>
          <SolutionFileHtml v-if="questions[currentPage-1].solution || questions[currentPage-1].solution_html"
                            :questions="questions"
                            :current-page="currentPage"
                            class="pr-2"
                            :assignment-name="name"
                            :use-view-solution-as-text="true"
          />
          <span v-if="questions[currentPage-1].qti_answer_json">
            <QtiJsonAnswerViewer v-if="questions[currentPage-1].qti_answer_json"
                                 :key="`modal-answer-${questions[currentPage-1].id}`"
                                 :modal-id="questions[currentPage-1].id"
                                 :qti-json="questions[currentPage-1].qti_answer_json"
            />
            <b-button
              size="sm"
              variant="outline-info"
              @click="$bvModal.show(`qti-answer-${questions[currentPage-1].id}`)"
            >
              View Correct Answer
            </b-button>
          </span>
        </div>
        <b-container v-if="caseStudyNotesByQuestion.length">
          <b-row v-if="questions[currentPage - 1].common_question_text" class="p-3">
            <p>{{ questions[currentPage - 1].common_question_text }}</p>
          </b-row>
          <b-row>
            <Transition>
              <b-col v-if="showLeftColumn">
                <div class="d-flex d-inline-flex pl-2">
                  <CaseStudyNotesViewer :key="`case-study-notes-viewer-key-${caseStudyNotesViewerKey}`"
                                        :case-study-notes="caseStudyNotesByQuestion"
                  />
                  <div>
                    <b-button v-if="showRightColumn"
                              id="expand-case-study-notes-tooltip"
                              size="sm"
                              variant="outline-info"
                              @click="showHideLeftAndRightColumns(true, false)"
                    >
                      <font-awesome-icon
                        :icon="expandArrowsIcon"
                      />
                      <b-tooltip target="expand-case-study-notes-tooltip" delay="750"
                                 triggers="hover"
                      >
                        Expand the view to just show the Case Study notes.
                      </b-tooltip>
                    </b-button>
                    <b-button v-if="!showRightColumn"
                              id="collapse-case-study-notes-tooltip"
                              size="sm"
                              variant="outline-info"
                              @click="showHideLeftAndRightColumns(true, true)"
                    >
                      <font-awesome-icon
                        :icon="exitExpandArrowsIcon"
                      />
                      <b-tooltip target="collapse-case-study-notes-tooltip" delay="750"
                                 triggers="hover"
                      >
                        Show both the Case Study notes and the question.
                      </b-tooltip>
                    </b-button>
                  </div>
                </div>
              </b-col>
            </Transition>
            <Transition>
              <b-col v-if="showRightColumn && showQtiJsonQuestionViewer">
                <div class="card p-2">
                  <div class="d-flex d-inline-flex">
                    <div v-if="questions[currentPage-1]['qti_json'] && getQtiJson()['qtiJson']">
                      <QtiJsonQuestionViewer
                        :key="`qti-json-${currentPage}-${cacheIndex}-${questions[currentPage - 1].student_response}`"
                        :qti-json="getQtiJson()['qtiJson']"
                        :student-response="questions[currentPage - 1].student_response"
                        :show-submit="[2,3,5].includes(user.role)"
                        :submit-button-active="getQtiJson()['submitButtonActive']"
                        :show-reset-response="Boolean(user.formative_student)"
                        @submitResponse="receiveMessage"
                        @resetResponse="resetSubmission"
                      />
                      <b-alert :show="!submitButtonActive" variant="info">
                        No additional submissions will be accepted.
                      </b-alert>
                    </div>
                    <div style="margin-left: auto">
                      <b-button v-if="showLeftColumn"
                                id="expand-question-tooltip"
                                size="sm"
                                variant="outline-info"
                                @click="showHideLeftAndRightColumns(false, true)"
                      >
                        <font-awesome-icon
                          :icon="expandArrowsIcon"
                        />
                        <b-tooltip target="expand-question-tooltip" delay="750"
                                   triggers="hover"
                        >
                          Expand the view to just show the question.
                        </b-tooltip>
                      </b-button>
                      <b-button v-if="!showLeftColumn"
                                id="collapse-question-tooltip"
                                size="sm"
                                variant="outline-info"
                                @click="showHideLeftAndRightColumns(true, true)"
                      >
                        <font-awesome-icon
                          :icon="exitExpandArrowsIcon"
                        />
                        <b-tooltip target="collapse-question-tooltip" delay="750"
                                   triggers="hover"
                        >
                          Show both the Case Study notes and the question.
                        </b-tooltip>
                      </b-button>
                    </div>
                  </div>
                </div>
                <div v-show="!clickerApp" class="pl-1 pt-3">
                  <b-button size="sm" variant="outline-primary" @click="showAttributionModal">
                    <span>
                      Attribution
                    </span>
                  </b-button>
                </div>
              </b-col>
            </Transition>
          </b-row>
        </b-container>
        <b-container v-if="!caseStudyNotesByQuestion.length"
                     id="questionContainer"
                     ref="questionContainer"
        >
          <div v-if="false">
            {{ clickerStatus }}
          </div>
          <b-row v-show="assessmentType === 'clicker'
            && !modalInstructorClickerQuestionShown
            && user.role === 2
            && presentationMode" class="pb-8"
          >
            <b-col cols="12">
              <div
                class="text-center"
                style="margin-bottom:50px"
              >
                <b-button id="forward-arrow"
                          style="padding:10px;margin-right:10px"
                          variant="primary"
                          :disabled="currentPage === 1"
                          @click="movePageByArrow(currentPage-1)"
                >
                  <font-awesome-icon style="font-size:30px"
                                     :icon="arrowLeftIcon"
                  />
                </b-button>
                <b-button id="back-arrow"
                          style="padding:10px;width:50px"
                          variant="primary"
                          :disabled="currentPage === questions.length"
                          @click="movePageByArrow(currentPage+1)"
                >
                  <font-awesome-icon style="font-size:30px"
                                     :icon="arrowRightIcon"
                  />
                </b-button>
              </div>

              <b-card
                v-show="loaded && !modalInstructorClickerQuestionShown && user.role === 2 && presentationMode"
                style="border-width: 1px;border-color:black;padding:10px"
                class="text-center"
              >
                <b-button v-show="clickerStatus === 'show_go' && !openingClicker"
                          style="padding:20px"
                          variant="success"
                          @click="startClickerAssessment"
                >
                  <span style="font-size:30px">Open</span>
                </b-button>
                <span v-show="openingClicker" style="font-size:30px"><b-spinner v-show="openingClicker" /> Loading... </span>

                <b-tooltip target="reset-clicker-question-tooltip" delay="250"
                           triggers="hover focus"
                >
                  This question has already been opened. You'll need to reset the timer before
                  opening it again.
                </b-tooltip>
                <b-button v-show="clickerStatus !== 'show_go'"
                          id="reset-clicker-question-tooltip"
                          style="padding:20px"
                          variant="danger"
                          @click="resetClickerTimer"
                >
                  <span style="font-size:30px">Reset</span>
                </b-button>
              </b-card>
            </b-col>
          </b-row>
          <b-row>
            <Transition>
              <b-col v-if="showLeftColumn" :cols="questionCol">
                <div>
                  <b-card
                    v-show="assessmentType !== 'clicker' || (assessmentType === 'clicker' && (user.role === 2 && !presentationMode) ||(user.role === 3 && clickerStatus !== 'neither_view_nor_submit'))"
                    no-body
                    class="p-2"
                    :style="clickerApp || assessmentType === 'clicker' ? 'border:0' : ''"
                  >
                    <div>
                      <div v-if="!caseStudyNotesByQuestion.length && showQuestion"
                           id="question-to-view"
                      >
                        <div v-if="questions[currentPage-1].a11y_question_html && user.role === 3"
                             class="m-2"
                             v-html="formatA11YQuestionHtml(questions[currentPage - 1].a11y_question_html)"
                        />
                        <div
                          v-if="(!questions[currentPage-1].a11y_question_html && user.role === 3) || [2,4,5].includes(user.role)"
                        >
                          <div v-if="questions[currentPage-1].non_technology">
                            <iframe
                              id="open_ended_question_text"
                              :key="`non-technology-iframe-${currentPage}-${cacheIndex}`"
                              v-resize="{ log: false }"
                              aria-label="open_ended_question_text"
                              style="height: 30px"
                              width="100%"
                              scrolling="no"
                              :src="questions[currentPage-1].non_technology_iframe_src"
                              frameborder="0"
                              :title="getIframeTitle()"
                              @load="fixLinks('open_ended_question_text')"
                            />
                          </div>
                          <div
                            v-if="questions[currentPage-1]['qti_json'] && getQtiJson()['qtiJson'] && showQtiJsonQuestionViewer"
                          >
                            <QtiJsonQuestionViewer
                              :key="`qti-json-${currentPage}-${cacheIndex}-${questions[currentPage - 1].student_response}`"
                              :qti-json="getQtiJson()['qtiJson']"
                              :student-response="questions[currentPage - 1].student_response"
                              :show-submit="[2,3,5].includes(user.role) && (assessmentType !== 'clicker' || timeLeft>0)"
                              :submit-button-active="getQtiJson()['submitButtonActive']"
                              :show-reset-response="Boolean(user.formative_student)"
                              @submitResponse="receiveMessage"
                              @resetResponse="resetSubmission"
                            />
                            <b-alert :show="!submitButtonActive && assessmentType !== 'clicker'" variant="info">
                              No additional submissions will be accepted.
                            </b-alert>
                          </div>
                          <div
                            v-if="questions[currentPage-1].technology_iframe.length
                              && !(user.role === 3 && clickerStatus === 'neither_view_nor_submit')"
                            :class="(!submitButtonActive && inIFrame) ? 'mb-4' :''"
                          >
                            <div
                              v-if="[4,5].includes(user.role) ||(technologySrcDoc === '' && questions[currentPage-1].technology !== 'webwork')"
                            >
                              <iframe
                                :key="`technology-iframe-${currentPage}-${cacheIndex}`"
                                v-resize="{ log: false }"
                                aria-label="auto_graded_submission_text"
                                width="100%"
                                allowtransparency="true"
                                :src="questions[currentPage-1].technology_iframe"
                                frameborder="0"
                                :title="getIframeTitle()"
                              />
                            </div>
                            <div v-else>
                              <iframe
                                :key="`technology-iframe-srcdoc-${currentPage}-${cacheIndex}`"
                                v-resize="{ log: false, checkOrigin: false }"
                                aria-label="auto_graded_submission_text"
                                width="100%"
                                :srcdoc="technologySrcDoc"
                                frameborder="0"
                                allowtransparency="true"
                                :title="getIframeTitle()"
                              />
                            </div>
                            <b-alert :show="!submitButtonActive && iframeDomLoaded" variant="info">
                              No additional submissions will be accepted.
                            </b-alert>
                          </div>
                        </div>
                      </div>
                    </div>
                  </b-card>
                  <div v-show="assessmentType === 'clicker' && user.role=== 3 && clickerStatus === 'view_and_submit'">
                    <countdown
                      :time="timeLeft"
                      class="float-left"
                      @end="endClickerAssessment"
                    >
                      <template slot-scope="props">
                        <span style="font-size: x-large" class="pt-5"
                              v-html="getTimeLeftMessage(props, assessmentType)"
                        />
                      </template>
                    </countdown>
                  </div>
                  <b-card v-if="!isLoading && questions[currentPage-1]
                    && user.role === 2
                    && questions[currentPage-1].question_type ==='report'" class="mt-2"
                  >
                    <Report
                      :key="`instructor-report-key-${reportCacheKey}`"
                      :assignment-id="Number(assignmentId)"
                      :question-id="Number(questions[currentPage-1].id)"
                      :user-id="user.id"
                      :rubric-categories="questions[currentPage-1].rubric_categories"
                      :points="+questions[currentPage-1].points"
                      :overall-comments="questions[currentPage - 1].text_feedback"
                    />
                  </b-card>
                  <div class="pt-2 pb-2">
                    <!-- todo: completely removed the button when on a page -->
                    <span v-if="!inIFrame && (((!inIFrame || showAttribution) && questions[currentPage-1].attribution !== null
                      || (questions[currentPage-1].auto_attribution && autoAttributionHTML))
                      && !(user.role === 3 && clickerStatus === 'neither_view_nor_submit')
                      && !clickerApp && !presentationMode)"
                    >
                      <b-button size="sm" variant="outline-primary" @click="showAttributionModal">
                        Attribution
                      </b-button>
                    </span>
                    <span v-if="!inIFrame && !isFormative && assessmentType !== 'clicker'">
                      <b-button v-if="showRightColumn"
                                id="expand-question-tooltip"
                                size="sm"
                                variant="outline-info"
                                @click="showHideLeftAndRightColumns(true, false)"
                      >
                        <font-awesome-icon
                          :icon="expandArrowsIcon"
                        />
                        <b-tooltip target="expand-question-tooltip" delay="750"
                                   triggers="hover"
                        >
                          Just show the question.
                        </b-tooltip>
                      </b-button>
                      <b-button v-if="!showRightColumn && !clickerApp"
                                id="collapse-question-tooltip"
                                size="sm"
                                variant="outline-info"
                                @click="showHideLeftAndRightColumns(true, true)"
                      >
                        <font-awesome-icon
                          :icon="exitExpandArrowsIcon"
                        />
                        <b-tooltip target="collapse-question-tooltip" delay="750"
                                   triggers="hover"
                        >
                          Show both the question and the submission information.
                        </b-tooltip>
                      </b-button>
                    </span>
                  </div>
                  <div>
                    <div
                      v-if="openEndedSubmissionType === 'rich text' && user.role === 2 && !inIFrame && !isInstructorWithAnonymousView"
                    >
                      <div class="mt-3">
                        <b-card header-html="<span class=&quot;font-weight-bold&quot;>Default Text</span>">
                          <p>
                            You can add default text for your students to see in their own text editors when they
                            attempt this question.
                          </p>
                          <ckeditor
                            :key="questions[currentPage-1].id"
                            v-model="openEndedDefaultTextForm.open_ended_default_text"
                            tabindex="0"
                            :config="richEditorConfig"
                            :class="{ 'is-invalid': openEndedDefaultTextForm.errors.has('open_ended_default_text') }"
                            @keydown="openEndedDefaultTextForm.errors.clear('open_ended_default_text')"
                            @namespaceloaded="onCKEditorNamespaceLoaded"
                            @ready="handleFixCKEditor()"
                          />
                          <has-error :form="openEndedDefaultTextForm" field="open_ended_default_text" />
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
                    <div v-if="isOpenEndedTextSubmission && user.role === 3 && !isAnonymousUser">
                      <div class="mt-1">
                        <ckeditor
                          ref="textSubmissionEditor"
                          :key="questions[currentPage-1].id"
                          v-model="textSubmissionForm.text_submission"
                          tabindex="0"
                          aria-label="Text submission box"
                          :config="richEditorConfig"
                          @ready="handleFixCKEditor()"
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
                    <div v-if="isOpenEndedAudioSubmission && user.role === 3 && !isAnonymousUser" class="mt-3 mb-3">
                      <h2 class="h7">
                        Instructions
                      </h2>
                      <p>
                        Use the built-in "ADAPT recorder" below to record and upload your audio submission directly to
                        ADAPT.  After you hit record, click on the recording (for example, Record 1), and then click the disk
                        icon to save it and submit it.
                        Otherwise, you may record your audio submission as an .mp3 file with another program (outside of
                        ADAPT),
                        save the .mp3 file to your computer, then <a href=""
                                                                     variant="sm"
                                                                     @click.prevent="openUploadFileModal(questions[currentPage - 1].id)"
                        >
                          upload the .mp3 file</a> from your computer into ADAPT.
                      </p>
                      <div class="ml-5">
                        <audio-recorder
                          v-show="showAudioUploadComponent"
                          ref="uploadRecorder"
                          :key="questions[currentPage-1].id"
                          tabindex="0"
                          class="m-auto"
                          :upload-url="audioUploadUrl"
                          :time="1"
                          :successful-upload="submittedAudioUpload"
                          :failed-upload="failedAudioUpload"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </b-col>
            </Transition>
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

                  <div v-if="!isLoadingPieChart">
                    <b-form-row v-if="!presentationMode && false">
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
                        <has-error :form="clickerTimeForm" field="time_to_submit" />
                      </b-form-group>
                    </b-form-row>
                  </div>
                </div>
              </div>
            </b-col>
            <b-col
              v-if="assessmentType !== 'clicker' && showAssignmentStatistics && loaded && user.role === 2 && !inIFrame && !isInstructorWithAnonymousView &&!clickerApp"
              :cols="bCardCols"
            >
              <b-card header="default" header-html="<span class=&quot;font-weight-bold&quot;>Question Statistics</span>"
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
              <HistogramAndTableView :chartdata="chartdata"
                                     :height="400"
              />
            </b-col>
            <b-col
              v-if="showRightColumn && (user.role === 3)
                && (assessmentType !== 'clicker')
                && (showSubmissionInformation || openEndedSubmissionType === 'file')"
              :cols="bCardCols"
            >
              <div v-show="showScores && questions[currentPage-1].submission_score_override">
                <b-alert show variant="info">
                  Override score provided by instructor: {{ questions[currentPage - 1].submission_score_override }}
                </b-alert>
              </div>
              <b-row>
                <b-card
                  v-if="assessmentType === 'learning tree' && studentShowPointsNonClicker()"
                  header="default"
                  header-html="<h2 class=&quot;h7&quot;>Root Assessment Submission</h2>"
                  class="sidebar-card"
                  :class="{ 'mt-3': zoomedOut}"
                >
                  <div style="font-size:large">
                    <div
                      v-if="numberOfAllowedAttempts !== 'unlimited' && scoringType === 'p'"
                    >
                      {{
                        numberOfRemainingAttempts
                      }}
                    </div>
                    <div v-if="studentShowPointsNonClicker() && assessmentType === 'learning tree'">
                      Current Points: {{ questions[currentPage - 1].submission_score }}
                    </div>
                    <div
                      v-if="numberOfAllowedAttempts !== '1'
                        && numberOfAllowedAttemptsPenalty"
                    >
                      Next Attempt Points: {{ maximumNumberOfPointsPossible }}
                      <span>
                        <QuestionCircleTooltip :id="'learning-tree-per-attempt-penalty-tooltip'" />
                        <b-tooltip target="learning-tree-per-attempt-penalty-tooltip" delay="250"
                                   triggers="hover focus"
                        >
                          A per attempt penalty of {{ numberOfAllowedAttemptsPenalty }}% is applied after the first
                          attempt.
                          {{ getHintPenaltyMessage() }}  With the penalty, the maximum number of points possible for the next attempt is
                          {{ maximumNumberOfPointsPossible }} points.
                        </b-tooltip>
                      </span>
                    </div>
                  </div>
                  <hr>
                  <div style="font-size: smaller">
                    <div>
                      Last submission: <span
                        :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                      >{{
                        questions[currentPage - 1].student_response
                      }}</span>
                      <div>
                        Submitted At:
                        <span
                          :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                        >{{
                          questions[currentPage - 1].last_submitted
                        }} </span>
                      </div>
                    </div>
                  </div>
                </b-card>
              </b-row>
              <b-row v-if="(questions[currentPage-1].technology === 'qti' || questions[currentPage-1].technology_iframe)
                && showSubmissionInformation && showQuestion && assessmentType !== 'learning tree' && !isFormative"
              >
                <b-card header="default"
                        header-html="<h2 class=&quot;h7&quot;>Auto-Graded Submission Information</h2>"
                        class="sidebar-card"
                        :class="{ 'mt-3': zoomedOut}"
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
                    <ul style="list-style-type:none" class="pl-0">
                      <li v-if="!['qti','webwork','imathas'].includes(questions[currentPage-1].technology)">
                        <span class="font-weight-bold">Submission
                          <span v-if="!questions[currentPage - 1].has_h5p_video_interaction_submissions">
                            <span
                              :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                            >{{
                              questions[currentPage - 1].student_response
                            }}</span>
                          </span>
                        </span>
                        <span v-if="questions[currentPage - 1].has_h5p_video_interaction_submissions">
                          <b-button size="sm" variant="primary"
                                    @click="$bvModal.show('modal-h5p-video-interaction-submissions')"
                          >View</b-button>
                        </span>
                      </li>
                      <li
                        v-if="['webwork','imathas'].includes(questions[currentPage-1].technology) && submissionArray.length"
                      >
                        <span class="font-weight-bold">Submission:
                          <span v-if="questions[currentPage - 1].last_submitted === 'N/A'" class="text-danger">N/A</span>
                        </span>
                        <span v-if="questions[currentPage - 1].last_submitted !== 'N/A'">
                          <b-button size="sm" variant="info" @click="showSubmissionArray">View Summary</b-button></span>
                      </li>
                      <li>
                        <span class="font-weight-bold">Submitted At:
                          <span
                            :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                          >{{
                            questions[currentPage - 1].last_submitted
                          }} </span>
                        </span>
                      </li>
                      <li v-if="showScores">
                        <span class="font-weight-bold">Score: {{
                          questions[currentPage - 1].submission_score
                        }}</span>
                      </li>
                      <li v-if="showScores">
                        <strong>Z-Score: {{ questions[currentPage - 1].submission_z_score }}</strong> <br>
                      </li>
                      <li v-if="parseFloat(questions[currentPage - 1].late_penalty_percent) > 0 && showScores">
                        <span class="font-weight-bold">Late Penalty: {{
                          questions[currentPage - 1].late_penalty_percent
                        }}%</span>
                      </li>
                    </ul>
                    <div v-show="showContactGrader()">
                      <hr>
                      <span class="pr-2">
                        <b-button size="sm" variant="outline-primary"
                                  @click="openContactGraderModal( 'auto-graded')"
                        >Contact Grader</b-button>
                      </span>
                    </div>
                  </b-card-text>
                </b-card>
              </b-row>
              <b-row v-if="isOpenEnded
                       && (user.role === 3)
                       && (showSubmissionInformation || openEndedSubmissionType === 'file')
                       && !isAnonymousUser"
                     :class="{ 'mt-3': (questions[currentPage-1].technology_iframe && showSubmissionInformation) || zoomedOut, 'mb-3': true }"
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
                    <ul style="list-style-type:none" class="pl-0">
                      <li v-if="isOpenEndedFileSubmission || isOpenEndedAudioSubmission">
                        <strong> Uploaded file:
                          <span v-if="questions[currentPage-1].submission_file_exists">
                            <a
                              :href="questions[currentPage-1].submission_file_url"
                              target="”_blank”"
                            >
                              View Submission
                            </a>
                          </span>
                          <span v-if="!questions[currentPage-1].submission_file_exists" class="text-danger">
                            No files have been uploaded.</span>
                        </strong>
                      </li>
                      <li>
                        <strong>Submitted At:
                          <span
                            :class="{ 'text-danger': questions[currentPage - 1].date_submitted === 'N/A' }"
                          >{{ questions[currentPage - 1].date_submitted }}</span></strong>
                      </li>
                      <li v-if="showScores">
                        <strong>Date Graded: {{ questions[currentPage - 1].date_graded }}</strong>
                      </li>

                      <li v-if="showScores && questions[currentPage-1].file_feedback">
                        <strong>{{ capitalize(questions[currentPage - 1].file_feedback_type) }} Feedback:
                          <a :href="questions[currentPage-1].file_feedback_url"
                             target="”_blank”"
                          >
                            {{
                              questions[currentPage - 1].file_feedback_type === 'audio' ? 'Listen To Feedback' : 'View Feedback'
                            }}
                          </a>
                        </strong>
                      </li>
                      <li v-if="showScores">
                        <strong>Comments:
                          <span v-if="questions[currentPage - 1].text_feedback"
                                v-html="questions[currentPage - 1].text_feedback"
                          />
                          <span v-if="!questions[currentPage - 1].text_feedback">None Provided.</span>
                        </strong>
                      </li>
                      <li v-if="showScores">
                        <strong>Score: {{ questions[currentPage - 1].submission_file_score }}</strong>
                      </li>
                      <li v-if="questions[currentPage - 1].submission_file_late_penalty_percent">
                        <span class="font-weight-bold">Late Penalty: {{
                          questions[currentPage - 1].submission_file_late_penalty_percent
                        }}%</span>
                      </li>
                      <li v-if="showScores">
                        <strong>Z-Score: {{ questions[currentPage - 1].submission_file_z_score }}</strong>
                      </li>
                    </ul>
                    <div v-if="isOpenEndedFileSubmission">
                      <hr>
                      <b-container>
                        <b-row class="mt-2 mr-2" align-h="end">
                          <b-button variant="primary"
                                    size="sm"
                                    @click="openUploadFileModal(questions[currentPage-1].id)"
                          >
                            Upload New File
                          </b-button>
                        </b-row>
                        <b-row v-show="!inIFrame && (compiledPDF || bothFileUploadMode) && user.role === 3"
                               class="mt-2"
                        >
                          <span>
                            {{ bothFileUploadMode ? 'Optionally' : 'Please' }}, upload your compiled PDF on the assignment's <router-link
                              :to="{ name: 'students.assignments.summary', params: { assignmentId: assignmentId }}"
                            >summary page</router-link>.
                          </span>
                        </b-row>
                      </b-container>
                    </div>
                    <div v-show="showContactGrader()" class="pr-2">
                      <hr>
                      <b-button size="sm" variant="outline-primary"
                                @click="openContactGraderModal( 'open-ended')"
                      >
                        Contact Grader
                      </b-button>
                    </div>
                    <b-alert :variant="openEndedSubmissionDataType" :show="showOpenEndedSubmissionMessage">
                      <span class="font-weight-bold">{{ openEndedSubmissionDataMessage }}</span>
                    </b-alert>
                  </b-card-text>
                </b-card>
              </b-row>
            </b-col>

            <div
              v-if="isInstructor() && !isInstructorWithAnonymousView && !presentationMode && questionView !== 'basic' && !inIFrame"
              class="mt-1 libretexts-font" style="width:100%"
            >
              <div v-if="questions[currentPage - 1].text_question"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].text_question" />
              </div>
              <div v-show="questions[currentPage - 1].a11y_auto_graded_question_id" class="mt-3 libretexts-border">
                <h2 class="editable mb-0">
                  A11y Question
                </h2>
                <span v-if="questions[currentPage - 1].a11y_auto_graded_question_id" class="mb-2"
                      style="font-size:14px;color:black"
                >
                  ADAPT ID: <span id="a11yAutoGradedAdaptId">{{
                    questions[currentPage - 1].a11y_auto_graded_question_id
                  }}</span>
                  <span class="text-info">
                    <a
                      href=""
                      class="pr-1"
                      aria-label="Copy a11y auto-graded ADAPT ID"
                      @click.prevent="doCopy('a11yAutoGradedAdaptId')"
                    >
                      <font-awesome-icon :icon="copyIcon" />
                    </a>
                  </span>
                </span>

                <iframe
                  v-if="questions[currentPage-1].a11y_technology_iframe"
                  :key="`a11y-technology-iframe-${currentPage}-${cacheIndex}`"
                  v-resize="{ log: false }"
                  aria-label="a11y_auto_graded_text"
                  width="100%"
                  :src="questions[currentPage-1].a11y_technology_iframe"
                  frameborder="0"
                  :title="getIframeTitle()"
                />
                <div
                  v-if="questions[currentPage-1]['a11y_qti_json'] && getA11yQtiJson()['qtiJson']"
                >
                  <QtiJsonQuestionViewer
                    :key="`qti-json-${currentPage}-${cacheIndex}-${questions[currentPage - 1].student_response}`"
                    :qti-json="getA11yQtiJson()['qtiJson']"
                    :student-response="questions[currentPage - 1].student_response"
                    :show-submit="false"
                    :submit-button-active="getA11yQtiJson()['submitButtonActive']"
                    :show-reset-response="Boolean(user.formative_student)"
                  />
                  <b-alert :show="!submitButtonActive" variant="info">
                    No additional submissions will be accepted.
                  </b-alert>
                </div>
              </div>
              <div v-if="questions[currentPage-1].answer_html"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].answer_html" />
              </div>
              <div v-if="questions[currentPage-1].solution_html"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].solution_html" />
              </div>
              <div v-if="questions[currentPage-1].hint"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].hint" />
              </div>
              <div v-if="questions[currentPage-1].libretexts_link"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].libretexts_link" />
              </div>
              <div v-if="questions[currentPage-1].notes"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].notes" />
              </div>
            </div>
          </b-row>
        </b-container>
      </div>
    </div>
    <div v-if="!initializing && !questions.length" class="mt-4">
      <b-alert show variant="warning" class="mt-3">
        <span class="alert-link">
          <span v-show="source === 'a'">This assignment currently has no assessments.</span>
          <span v-show="source === 'x'">This is an external assignment.  Please contact your instructor for more information.</span>
        </span>
      </b-alert>
    </div>
    <div v-if="showQuestionDoesNotExistMessage">
      <b-alert :show="true" variant="warning" class="mt-3">
        We could not find any questions associated with this assignment linked to:
        <p class="text-center m-2">
          <strong>{{ getWindowLocation() }}</strong>
        </p>
        Please ask your instructor to update this link so that it matches a question in the assignment.
      </b-alert>
    </div>
    <Report
      v-if="!isLoading && questions[currentPage-1]
        && (questions[currentPage-1].submission_file_exists && user.role === 3)
        && questions[currentPage-1].question_type ==='report'"
      :assignment-id="Number(assignmentId)"
      :question-id="Number(questions[currentPage-1].id)"
      :user-id="user.id"
      :rubric-categories="questions[currentPage-1].rubric_categories"
      :points="+questions[currentPage-1].points"
      :overall-comments="questions[currentPage - 1].text_feedback"
    />
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'

import { ToggleButton } from 'vue-js-toggle-button'
import { getAcceptedFileTypes, submitUploadFile, formatFileSize } from '~/helpers/UploadFiles'
import { h5pResizer } from '~/helpers/H5PResizer'

import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

import { downloadSolutionFile, downloadSubmissionFile, getFullPdfUrlAtPage } from '~/helpers/DownloadFiles'
import { doCopy } from '~/helpers/Copy'

import Email from '~/components/Email'
import EnrollInCourse from '~/components/EnrollInCourse'
import RefreshQuestion from '~/components/RefreshQuestion'
import { getScoresSummary } from '~/helpers/Scores'
import CKEditor from 'ckeditor4-vue'

import PieChart from '~/components/PieChart'
import SolutionFileHtml from '~/components/SolutionFileHtml'

import libraries from '~/helpers/Libraries'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy, faHeart } from '@fortawesome/free-regular-svg-icons'
import {
  faTree,
  faCheck,
  faArrowLeft,
  faArrowRight,
  faInfinity,
  faCompressArrowsAlt,
  faExpandArrowsAlt
} from '@fortawesome/free-solid-svg-icons'
import RemoveQuestion from '~/components/RemoveQuestion'

import Vue from 'vue'

import LoggedInAsStudent from '~/components/LoggedInAsStudent'
import CannotDeleteAssessmentFromBetaAssignmentModal from '../components/CannotDeleteAssessmentFromBetaAssignmentModal'

import AssessmentTypeWarnings from '~/components/AssessmentTypeWarnings'
import CannotAddAssessmentToBetaAssignmentModal from '~/components/CannotAddAssessmentToBetaAssignmentModal'

import IframeInformation from '~/components/IframeInformation'

import { updateCompletionSplitOpenEndedSubmissionPercentage } from '~/helpers/CompletionScoringMode'
import AllFormErrors from '~/components/AllFormErrors'
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import HistogramAndTableView from '~/components/HistogramAndTableView'
import { defaultLicenseVersionOptions, updateAutoAttribution } from '~/helpers/Licenses'
import { getTechnologySrc, editQuestionSource, getQuestionToEdit, getQuestionRevisionToEdit } from '~/helpers/Questions'

import { getCaseStudyNotesByQuestion } from '~/helpers/CaseStudyNotes'
import CloneQuestion from '~/components/CloneQuestion'

import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { makeFileUploaderAccessible } from '~/helpers/accessibility/makeFileUploaderAccessible'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'
import CreateQuestion from '~/components/questions/CreateQuestion'
import QtiJsonQuestionViewer from '~/components/QtiJsonQuestionViewer'
import QtiJsonAnswerViewer from '~/components/QtiJsonAnswerViewer'
import CaseStudyNotesViewer from '~/components/questions/nursing/CaseStudyNotesViewer'

import { v4 as uuidv4 } from 'uuid'
import $ from 'jquery'
import QRCodeStyling from 'qr-code-styling'
import { qrCodeConfig } from '../helpers/QrCode'
import Report from '../components/Report.vue'
import UpdateRevision from '../components/questions/UpdateRevision.vue'
import uniqueId from 'vue-select/src/utility/uniqueId'
import {
  processReceiveMessage,
  hideSubmitButtonsIfCannotSubmit,
  addGlow,
  getTechnologySrcDoc
} from '~/helpers/HandleTechnologyResponse'
import { initCentrifuge } from '../helpers/Centrifuge'

Vue.prototype.$http = axios // needed for the audio player

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)

export default {
  middleware: 'auth',
  layout: window.config.clickerApp ? 'blank' : 'default',
  components: {
    UpdateRevision,
    Report,
    CaseStudyNotesViewer,
    QtiJsonAnswerViewer,
    QtiJsonQuestionViewer,
    CannotDeleteAssessmentFromBetaAssignmentModal,
    FontAwesomeIcon,
    EnrollInCourse,
    Email,
    Loading,
    ToggleButton,
    SolutionFileHtml,
    PieChart,
    RemoveQuestion,
    ckeditor: CKEditor.component,
    FileUpload: VueUploadComponent,
    LoggedInAsStudent,
    AssessmentTypeWarnings,
    IframeInformation,
    CannotAddAssessmentToBetaAssignmentModal,
    RefreshQuestion,
    HistogramAndTableView,
    AllFormErrors,
    SavedQuestionsFolders,
    CreateQuestion,
    CloneQuestion
  },
  data: () => ({
    modalInstructorClickerQuestionShown: false,
    clickerModalButtons: {},
    clickerAnswerShown: false,
    viewingClickerSubmissions: false,
    openingClicker: false,
    clickerApp: window.config.clickerApp,
    pendingQuestionRevision: {},
    updateRevisionKey: 0,
    canContactGrader: false,
    learningTreeMessage: '',
    processingUpdatingQuestionView: false,
    modalSubmissionAcceptedTitle: 'Submission Accepted',
    reportCacheKey: 0,
    completedAllAssignmentQuestions: false,
    submissionArray: [],
    unconfirmedSubmission: [],
    questionNumbersShownOutOfIframe: true,
    formativeQuestionURL: '',
    technologySrcDoc: '',
    confirmDeleteOpenEndedSubmissionsMessage: '',
    enteredPoints: false,
    showQtiJsonQuestionViewer: false,
    submitButtonsDisabled: false,
    iframeDomLoaded: false,
    event: {},
    submitButtonActive: true,
    showLeftColumn: true,
    showRightColumn: true,
    taskStartTime: 0,
    totalTimeInTaskInactive: 0,
    startTimeTaskInactive: 0,
    tabFocus: false,
    caseStudyNotesViewerKey: 0,
    caseStudyNotesByQuestion: [],
    reviewQuestionPollingSetInterval: null,
    isH5pVideoInteraction: false,
    qtiJson: '',
    maxScore: null,
    h5pVideoInteractionSubmissionsFields: [
      {
        key: 'question',
        isRowHeader: true
      },
      'response'],
    technology: '',
    formattedTechnology: '',
    hintPenaltyIfShownHint: 0,
    questionNumbersShownInIframe: false,
    hintPenalty: 0,
    canViewHint: false,
    isFormative: false,
    isBetaAssignment: false,
    rubricCategories: [],
    questionToEdit: {},
    a11yTechnologySrc: '',
    cacheKey: 1,
    showCountdown: true,
    showUpdatePointsPerQuestion: false,
    numberOfRemainingAttempts: '',
    numberOfAllowedAttempts: '',
    numberOfAllowedAttemptsPenalty: '',
    maximumNumberOfPointsPossible: '',
    myFavoriteQuestions: {},
    currentFavoritesFolder: null,
    myFavoritesFolder: null,
    filteringByQuestionType: false,
    questionType: 'any-question-type',
    questionTypeOptions: [
      { text: 'Auto-graded or Open-ended', value: 'any-question-type' },
      { text: 'Auto-graded, only', value: 'auto-graded-only' },
      { text: 'Open-ended, only', value: 'open-ended-only' }
    ],
    reasonForUploadingLocalSolution: 'prefer_own_solution',
    libretextsSolutionErrorForm: new Form({
      text: '',
      question_id: 0
    }),
    showAudioUploadComponent: false,
    reload: 0,
    showHistogramView: true,
    allFormErrors: [],
    completionSplitOpenEndedPercentage: '',
    completionScoringModeForm: new Form({
      completion_scoring_mode: null,
      completion_split_auto_graded_percentage: null
    }),
    scoringType: '',
    completionScoringModeMessage: '',
    bCardCols: 4,
    zoomedOut: false,
    toggleColors: window.config.toggleColors,
    savedQuestions: [],
    myFavoriteQuestionIds: [],
    isAnonymousUser: false,
    isInstructorWithAnonymousView: false,
    launchThroughLMSMessage: false,
    isLMS: false,
    availableOn: '',
    assignmentShown: true,
    hasAtLeastOneSubmission: false,
    assignmentInformationShownInIFrame: false,
    submissionInformationShownInIFrame: false,
    attributionInformationShownInIFrame: false,
    cacheIndex: 1,
    modalEnrollInCourseIsShown: false,
    betaAssignmentsExist: false,
    autoAttributionHTML: '',
    autoAttribution: true,
    isInstructorLoggedInAsStudent: false,
    checkIcon: faCheck,
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
    heartIcon: faHeart,
    expandArrowsIcon: faExpandArrowsAlt,
    exitExpandArrowsIcon: faCompressArrowsAlt,
    treeIcon: faTree,
    infinityIcon: faInfinity,
    arrowLeftIcon: faArrowLeft,
    arrowRightIcon: faArrowRight,
    technologySrc: '',
    pageId: '',
    adaptId: '',
    ckeditor: {},
    isLoading: true,
    activeId: 0,
    learningTreeSrc: '',
    assignmentInformationMarginBottom: 'mb-3',
    showSubmissionInformation: true,
    showAssignmentInformation: false,
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
    correctAnswerIndex: null,
    piechartdata: [],
    clickerPollingSetInterval: null,
    clickerMessage: '',
    clickerMessageType: '',
    cannotViewAssessmentMessage: false,
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
      resize_enabled: false,
      height: 200
    },
    richEditorSolutionErrorConfig: {
      toolbar: [
        { name: 'clipboard', items: ['Cut', 'Copy', '-', 'Undo', 'Redo'] },
        {
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']
        },
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'insert', items: ['Table', 'HorizontalRule', 'Smiley', 'SpecialChar'] },
        { name: 'colors', items: ['TextColor', 'BGColor'] }
      ],
      removeButtons: '',
      height: 200,
      resize_enabled: false
    },
    isOpenEnded: false,
    isOpenEndedFileSubmission: false,
    isOpenEndedTextSubmission: false,
    isOpenEndedAudioSubmission: false,

    responseText: '',
    openEndedSubmissionTypeOptions: [
      { value: 'rich text', text: 'Rich Text' },
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
    capitalFormattedAssessmentType: '',
    assessmentType: '',
    showPointsPerQuestion: false,
    showQuestionDoesNotExistMessage: false,
    maintainAspectRatio: false,
    showAssignmentStatisticsModal: false,
    showAssignmentStatistics: false,
    questionCol: 8,
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
    propertiesForm: new Form({
      private_description: '',
      auto_attribution: '',
      attribution: ''
    }),
    licenseVersionOptions: [],
    defaultLicenseVersionOptions: defaultLicenseVersionOptions,
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
    questionWeightForm: new Form({
      weight: null
    }),
    openEndedSubmissionTypeAllowed: false,
    openEndedSubmissionType: 'text',
    iframeLoaded: false,
    showedInvalidTechnologyMessage: false,
    showQuestion: true,
    perPage: 1,
    currentPage: 1,
    currentUrl: '',
    currentCutup: 1,
    questions: [],
    initializing: true, // use to show a blank screen until all is loaded
    assignmentId: '',
    name: '',
    questionId: false,
    originalOpenEndedSubmissionType: ''
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe,
    isLocalMe: () => window.config.isMe && window.location.hostname === 'local.adapt'
  },
  watch: {
    openEndedSubmissionType: function (newVal, oldVal) {
      this.originalOpenEndedSubmissionType = oldVal
    }
  },
  async created () {
    window.addEventListener('message', this.receiveMessage, false)
    this.doCopy = doCopy
    this.getTechnologySrc = getTechnologySrc
    this.editQuestionSource = editQuestionSource
    this.getQuestionToEdit = getQuestionToEdit
    this.getQuestionRevisionToEdit = getQuestionRevisionToEdit
    this.getCaseStudyNotesByQuestion = getCaseStudyNotesByQuestion
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
    h5pResizer()
    this.submitUploadFile = submitUploadFile
    this.formatFileSize = formatFileSize
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.downloadSolutionFile = downloadSolutionFile
    this.downloadSubmissionFile = downloadSubmissionFile
    this.getFullPdfUrlAtPage = getFullPdfUrlAtPage
    this.updateCompletionSplitOpenEndedSubmissionPercentage = updateCompletionSplitOpenEndedSubmissionPercentage
    window.addEventListener('keydown', this.hotKeys)
    window.addEventListener('visibilitychange', this.visibilityChange)
  },
  destroyed () {
    window.removeEventListener('keydown', this.hotKeys)
    window.removeEventListener('visibilitychange', this.visibilityChange)
  },
  async mounted () {
    if (localStorage.ltiTokenId) {
      await this.refreshToken()
    }
    window.addEventListener('resize', this.resizeHandler)
    this.isAnonymousUser = this.user.email === 'anonymous'
    this.isLoading = true
    this.showRightColumn = !this.clickerApp

    this.uploadFileType = (this.user.role === 2) ? 'solution' : 'submission' // students upload question submissions and instructors upload solutions
    this.uploadFileUrl = (this.user.role === 2) ? '/api/solution-files' : '/api/submission-files'

    this.assignmentId = this.$route.params.assignmentId

    /// Why do I need the inIFrame???
    if (this.inIFrame && this.user.role === 3) {
      await this.redirectIfBetaCourse()
    }
    if (this.inIFrame && this.user.role === 2) {
      await this.redirectIfBetaCourse()
    }
    this.questionId = this.$route.params.questionId
    this.shownSections = this.$route.params.shownSections
    this.canView = await this.getAssignmentInfo()
    if (!this.canView) {
      this.isLoading = false
      return false
    }

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
      console.log(this.questions[this.currentPage - 1])
      this.hasAtLeastOneSubmission = this.questions[this.currentPage - 1].has_at_least_one_submission
      if (this.inIFrame) {
        this.showSubmissionInformation = this.questions[this.currentPage - 1].submission_information_shown_in_iframe &&
          this.user.role !== 2
        this.showAssignmentInformation = this.questions[this.currentPage - 1].assignment_information_shown_in_iframe
        this.showAttribution = this.questions[this.currentPage - 1].attribution_information_shown_in_iframe
        if (!this.showAssignmentInformation) {
          this.assignmentInformationMarginBottom = 'mb-0'
        }
      }
      if (this.isAnonymousUser || this.isInstructorWithAnonymousView || this.user.role === 5) {
        this.showSubmissionInformation = false
        this.showAssignmentInformation = false
      }
      this.setQuestionCol()
      this.resizeHandler()

      if (this.user.role === 2) {
        await this.getCutups(this.assignmentId)
      }

      this.licenseVersionOptions = this.defaultLicenseVersionOptions
    }
    if (this.assessmentType === 'clicker') {
      this.centrifuge = await initCentrifuge()
      const sub = this.centrifuge.newSubscription(`clicker-status-${this.assignmentId}`)
      const clickerStatusUpdated = this.clickerStatusUpdated
      sub.on('publication', async function (ctx) {
        await clickerStatusUpdated(ctx)
      }).subscribe()

      if (this.user.role === 3) {
        this.centrifuge2 = await initCentrifuge()
        const sub2 = this.centrifuge2.newSubscription(`set-current-page-${this.assignmentId}`)
        const setCurrentPage = this.setCurrentPage
        sub2.on('publication', async function (ctx) {
          await setCurrentPage(ctx)
        }).subscribe()
      }
      if (this.user.role === 2) {
        await this.setStudentQuestionPage()
      }
    }
    if (this.isInstructorWithAnonymousView && this.questions.length && !this.isLoading) {
      this.$bvModal.show('modal-save-questions-from-open-course')
    }
    if (this.inIFrame) {
      this.$refs['questionContainer'].classList.remove('container')
      $('.row').removeClass('row')
      $('.col-12').removeClass('col-12')
      $('.container').removeClass('container')
    }
  },
  beforeDestroy () {
    window.removeEventListener('message', this.receiveMessage)
    window.removeEventListener('resize', this.resizeHandler)
    if (this.clickerPollingSetInterval) {
      clearInterval(this.clickerPollingSetInterval)
      this.clickerPollingSetInterval = null
    }
    if (this.reviewQuestionPollingSetInterval) {
      clearInterval(this.reviewQuestionPollingSetInterval)
      this.reviewQuestionPollingSetInterval = null
    }
    try {
      if (this.centrifuge) {
        this.centrifuge.disconnect()
      }
    } catch (error) {
      // won't be a function for all the other ones that haven't been defined on the page
    }
  },
  methods: {
    updateAutoAttribution,
    getTechnologySrcDoc,
    addGlow,
    hideSubmitButtonsIfCannotSubmit,
    togglePresentationMode () {
      this.presentationMode = !this.presentationMode
      this.renderMathJax()
    },
    async setStudentQuestionPage () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/${this.questions[this.currentPage - 1].id}/set-current-page`)
        console.log(data)
      } catch (error) {
        console.error(error.message)
      }
    },
    initShowClickerAnswer () {
      this.clickerAnswerShown = true
      this.clickerModalButtons.close = true
      this.clickerModalButtons.answer = false
    },
    async closePoll () {
      await this.endClickerAssessment()
      this.clickerModalButtons = {
        'submissions': false,
        'answer': true,
        'close': false
      }
      this.clickerAnswerShown = false
      this.viewingClickerSubmissions = true
    },
    async initViewClickerSubmissions () {
      this.clickerStatus === 'view_and_submit' ? this.$bvModal.show('modal-confirm-close-poll') : await this.closePoll()
    },
    movePageByArrow (newPage) {
      window.location = `/assignments/${this.assignmentId}/questions/view/${this.questions[newPage - 1].id}`
    },
    updateToLatestRevision () {
      if (this.questions[this.currentPage - 1].pending_question_revision === null) {
        this.$noty.info('We are unable to locate the question revision.  Please contact support for assistance.')
        return false
      }
      this.$bvModal.show('modal-show-revision')
    },
    async showLatestRevision () {
      try {
        const { data } = await axios.get(`/api/pending-question-revisions/${this.questions[this.currentPage - 1].question_revision_id_latest}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questions[this.currentPage - 1].pending_question_revision = data.question_revision
        this.pendingQuestionRevision = data.question_revision
        this.$bvModal.show('modal-show-revision')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async resetClickerTimer () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/${this.questions[this.currentPage - 1].id}/reset-clicker-timer`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        window.location = `/assignments/${this.assignmentId}/questions/view/${this.questions[this.currentPage - 1].id}`
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async setCurrentPage (ctx) {
      console.log('set-current-page')
      console.log(ctx)
      const data = ctx.data
      if (data.assignment_id === +this.assignmentId && data.question_id !== +this.questions[this.currentPage - 1].id) {
        window.location.href = `/assignments/${this.assignmentId}/questions/view/${data.question_id}`
      }
    },
    async clickerStatusUpdated (ctx) {
      const data = ctx.data
      console.log('clicker status updated')
      console.log(data)
      if (data.assignment_id === +this.assignmentId) {
        if (data.question_id === +this.questions[this.currentPage - 1].id) {
          console.log('updating clicker status')
          this.timeLeft = data.time_left
          await this.canSubmit()
          this.updateClickerMessage(data.status)
        } else {
          window.location.href = `/assignments/${this.assignmentId}/questions/view/${data.question_id}`
        }
      }
    },
    reloadAndRemoveQuestionEditorUpdatedAt () {
      this.$emit('reloadCurrentAssignmentQuestions')
      clearInterval(window.currentQuestionEditorUpdatedAt)
    },
    async submitRemoveQuestion () {
      let questionId = this.questions[this.currentPage - 1].id
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${questionId}`)
        this.$bvModal.hide('modal-remove-question')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questions = this.questions.filter(question => question.id !== questionId)
        this.$noty.info(data.message)
        if (this.currentPage > 1) {
          this.currentPage--
        } else {
          this.currentPage = 1
        }
        if (this.questions.length) {
          await this.changePage(this.currentPage)
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },
    openRemoveQuestionModal () {
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-delete-assessment-from-beta-assignment')
        return false
      }
      this.$bvModal.show('modal-remove-question')
    },
    canEarnLearningTreeReset () {
      return this.questions[this.currentPage - 1].number_of_learning_tree_paths - this.questions[this.currentPage - 1].number_resets_available >= this.questions[this.currentPage - 1].number_of_successful_paths_for_a_reset
    },
    async resetRootNodeSubmission () {
      try {
        const { data } = await axios.post(`/api/learning-tree-node/reset-root-node-submission/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.reloadSingleQuestion()
          this.$bvModal.hide('modal-reset-root-node-submission')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    enterLearningTree () {
      this.$bvModal.hide('modal-submission-accepted')
      this.$bvModal.show('modal-learning-tree')
    },
    processReceiveMessage,
    receiveMessage (event) {
      console.log(event)
      if (event.data === 'Close learning tree modal') {
        this.$bvModal.hide('modal-learning-tree')
        $('button.close').click()
        return false
      }
      let vm = this
      console.log(this.$route.name)
      this.processReceiveMessage(vm, this.$route.name, event)
    },
    increaseLearningTreeModalSize () {
      this.$nextTick(() => {
        $('.modal-dialog.modal-xl').css('max-width', '95%')
      })
    },
    async viewLatestRevision () {
      this.processingUpdatingQuestionView = true
      try {
        const { data } = await axios.get(`/api/questions/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'success') {
          let originalQuestionRevisionId = this.questions[this.currentPage - 1].question_revision_id
          // known issue is that the webwork solution won't load.  Need to go into the DOM
          // hideSubmitButtonsIfCannotSubmit (technology, updatedLastSubmittedInfo = false) maybe
          for (const property in data['question']) {
            this.questions[this.currentPage - 1][property] = data['question'][property]
          }
          if (this.questions[this.currentPage - 1].rubric_categories.length) {
            this.questions[this.currentPage - 1].question_type = 'report'
          }
          this.questions[this.currentPage - 1].viewing_latest_revision = true
          if (this.questions[this.currentPage - 1].solution_html) {
            this.questions[this.currentPage - 1].solution_type = 'html'
          }
          if (this.questions[this.currentPage - 1].solution_file_url) {
            this.questions[this.currentPage - 1].solution_type = 'q'
          }
          this.questions[this.currentPage - 1].question_revision_id_original = originalQuestionRevisionId
          this.questions[this.currentPage - 1].technology_iframe = data['question'].technology_iframe_src
          this.cacheKey++
          this.reportCacheKey++
          await this.changePage(this.currentPage)
          this.$noty.info('The question has been updated to the latest revision.')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingUpdatingQuestionView = false
    },
    async viewCurrentRevision () {
      this.processingUpdatingQuestionView = true
      this.questionId = this.questions[this.currentPage - 1].id
      await this.getSelectedQuestions(this.assignmentId, this.questions[this.currentPage - 1].id)
      await this.changePage(this.currentPage)
      this.$noty.info('The question has been updated to the current revision.')
      this.processingUpdatingQuestionView = false
    },
    uniqueId,
    hideModalSubmissionAccepted () {
      this.modalSubmissionAcceptedTitle = 'Submission Accepted'
      this.saveSubmissionConfirmation()
    },
    async showSubmissionArray () {
      try {
        const { data } = await axios.get(`/api/submissions/submission-array/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.questions[this.currentPage - 1].submission_array = data.submission_array
          this.submissionArray = data.submission_array
          console.log(this.questions[this.currentPage - 1].submission_array)
          this.completedAllAssignmentQuestions = false
          this.modalSubmissionAcceptedTitle = 'Submission Summary'
          this.$bvModal.show('modal-submission-accepted')
          this.renderMathJax()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    setQuestionRevision (revision) {
      console.log('setting revision')
      console.log(this.questionToEdit)
      this.getQuestionRevisionToEdit(revision)
    },
    updateCustomQuestionTitle (newTitle) {
      this.questions[this.currentPage - 1].title = newTitle
    },
    sumArrBy (arr, key, places = 0) {
      let sum
      let factor
      sum = arr.reduce((sum, item) => {
        return sum + Number(item[key])
      }, 0)
      factor = 10 ** places
      return Math.round(sum * factor) / factor
    },
    cancelWebworkSubmission () {
      this.$bvModal.hide('modal-confirm-submission')
      this.$noty.info('Your submission has not been saved.')
    },
    async initConfirmSubmission () {
      try {
        const { data } = await axios.get(`/api/unconfirmed-submissions/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.unconfirmedSubmission = data.unconfirmed_submission
          this.$bvModal.show('modal-confirm-submission')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async completeSubmission () {
      this.$bvModal.hide('modal-confirm-submission')
      try {
        const { data } = await axios.post(`/api/unconfirmed-submissions/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}/store-submission`)
        await this.showResponse(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    renderMathJax () {
      this.$nextTick(() => {
        MathJax.Hub.Queue(['Typeset', MathJax.Hub])
      })
    },

    showContactGrader () {
      return (this.questions && this.questions[this.currentPage - 1].grader_id && (this.showScores ||
        this.solutionsReleased ||
        this.questions[this.currentPage - 1].solution ||
        this.questions[this.currentPage - 1].solution_html ||
        this.questions[this.currentPage - 1].qti_answer_json) && this.canContactGrader)
    },
    getAdaptId () {
      let adaptId = ''
      if (this.user.role !== 3 && this.questions.length && !this.isLoading) {
        return `${this.assignmentId}-${this.questions[this.currentPage - 1].id}`
      }
      return adaptId
    },
    getLearningTreeId () {
      let learningTreeId = ''
      if (this.user.role !== 3 && this.questions.length && !this.isLoading && this.questions[this.currentPage - 1].learning_tree_id) {
        return `${this.questions[this.currentPage - 1].learning_tree_id}`
      }
      return learningTreeId
    },
    async canSubmit () {
      try {
        const { data } = await axios.get(`/api/submissions/can-submit/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'error') {
          console.log(`Cannot submit: ${data.message}`)
        }
        this.submitButtonActive = data.type === 'success'
        if (this.questions[this.currentPage - 1]['technology'] === 'h5p') {
          let vm = this
          await this.hideSubmitButtonsIfCannotSubmit(vm, 'questions.view', 'h5p', true)
        }
      } catch (error) {
        console.log(error.message)
      }
    },
    async saveSubmissionConfirmation () {
      if (this.completedAllAssignmentQuestions) {
        this.$bvModal.show('modal-assignment-completed')
      }
      try {
        const { data } = await axios.post(`/api/submission-confirmations/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'error') {
          console.error(`Error saving submission confirmation: ${data.message}`)
        }
      } catch (error) {
        console.error(`Error saving submission confirmation: ${error.message}`)
      }
    },
    showHideLeftAndRightColumns (showLeftColumn, showRightColumn) {
      this.$root.$emit('bv::hide::tooltip')
      this.$nextTick(() => {
        this.showLeftColumn = showLeftColumn
        this.showRightColumn = showRightColumn
        this.questionCol = this.showLeftColumn && !this.showRightColumn ? 12 : 8
      }
      )
    },
    initReviewQuestionTimeSpent () {
      if (this.reviewQuestionPollingSetInterval) {
        clearInterval(this.reviewQuestionPollingSetInterval)
      }
      let reviewSessionId = uuidv4()
      console.log(`Review session: ${reviewSessionId}`)
      this.reviewQuestionPollingSetInterval = setInterval(() => {
        this.updateReviewQuestionTime(reviewSessionId)
      }, 3000)
    },
    updateTotalTimeInTaskInactive () {
      let endTime = performance.now()
      let timeDiff = endTime - this.startTimeTaskInactive

      timeDiff /= 1000
      let seconds = Math.round(timeDiff)
      this.totalTimeInTaskInactive += seconds
      console.log(`update total time task inactive: ${this.totalTimeInTaskInactive}`)
    },
    clearReviewQuestionTimeSpentInterval () {
      console.log('clear review time spent interval')
      clearInterval(this.reviewQuestionPollingSetInterval)
      this.reviewQuestionPollingSetInterval = null
    },
    initStartTimeInactive () {
      console.log('init time start inactive')
      this.startTimeTaskInactive = performance.now()
    },
    visibilityChange () {
      if (this.user.role === 3) {
        switch (document.visibilityState) {
          case ('visible'):
            this.pastDue ? this.initReviewQuestionTimeSpent() : this.updateTotalTimeInTaskInactive()
            break
          case ('hidden'):
            this.pastDue ? this.clearReviewQuestionTimeSpentInterval() : this.initStartTimeInactive()
            break
        }
      }
    },
    formatA11YQuestionHtml (a11yQuestionHTML) {
      return a11yQuestionHTML.replace('<div class="mt-section"><h2 class="editable">Text Question</h2>', '').replace('</div>', '')
    },
    async getH5pVideoInteractionSubmissions () {
      try {
        const { data } = await axios.get(`/api/h5p-video-interaction/submissions/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let hasH5pVideoInteractionSubmissions = data.h5p_video_interaction_submissions.length > 0
        this.questions[this.currentPage - 1].has_h5p_video_interaction_submissions = hasH5pVideoInteractionSubmissions
        if (hasH5pVideoInteractionSubmissions) {
          let h5pVideoInteractionSubmissions = []
          for (let i = 0; i < data.h5p_video_interaction_submissions.length; i++) {
            let question
            let submission = JSON.parse(data.h5p_video_interaction_submissions[i].submission)
            try {
              question = submission.object.definition.description['en-US']
              let submissionResultsResponses
              console.log(submission.result.response)
              if (submission.result.response.includes('[,]')) {
                submissionResultsResponses = submission.result.response.split('[,]')
                console.log(submissionResultsResponses)
              } else {
                submissionResultsResponses = [submission.result.response]
              }
              let responses = []
              for (let j = 0; j < submissionResultsResponses.length; j++) {
                responses.push(submission.object.definition.choices.find(choice => parseInt(choice.id) === parseInt(submissionResultsResponses[j])).description['en-US'])
              }

              h5pVideoInteractionSubmissions.push({ question: question, response: responses.join('<br>') })
            } catch (error) {
              console.log('Error processing H5P response')
              h5pVideoInteractionSubmissions.push({ question: question, response: submission.result.response })
              console.log(submission)
            }
          }
          this.questions[this.currentPage - 1].h5p_video_interaction_submissions = h5pVideoInteractionSubmissions
          console.log(h5pVideoInteractionSubmissions)
        }

        this.$forceUpdate()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getNumberOfAttemptsLeftMessage () {
      if (this.numberOfAllowedAttempts === 'unlimited') {
        return 'You still have an unlimited number of attempts left.'
      } else {
        let numLeft = parseInt(this.numberOfAllowedAttempts) - parseInt(this.questions[this.currentPage - 1].submission_count)
        let plural = numLeft > 1
          ? 's' : ''
        return `You currently have ${numLeft} reset${plural} left.`
      }
    },
    async refreshToken () {
      try {
        const { data } = await axios.post('/api/refresh-token', { token: localStorage.ltiTokenId })
        // Save the token.
        localStorage.removeItem('ltiTokenId')
        if (data.type !== 'success') {
          this.$noty.error('Could not refresh token')
          return false
        }
        await this.$store.dispatch('auth/saveToken', {
          token: data.new_token,
          remember: false
        })
      } catch (error) {
        this.$noty.error(error.message)
      }
      localStorage.removeItem('ltiTokenId')
    },
    async resetSubmission () {
      try {
        const { data } = await axios.patch(`/api/submissions/assignments/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}/reset-submission`)
        this.$noty[data.type](data.message)
        if (data.type === 'info') {
          setTimeout(() => {
            window.location.href = `/assignments/${this.assignmentId}/questions/view/${this.questions[this.currentPage - 1].id}`
          }, 2000)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleShownHint () {
      try {
        const { data } = await axios.post(`/api/shown-hints/assignments/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questions[this.currentPage - 1].shown_hint = true
        this.$bvModal.hide('modal-confirm-show-hint')
        this.$bvModal.show('modal-hint')
        this.questions[this.currentPage - 1].hint = data.hint
        this.maximumNumberOfPointsPossible = this.getMaximumNumberOfPointsPossible()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    editLearningTree (learningTreeId) {
      window.open(`/instructors/learning-trees/editor/${learningTreeId}`, '_blank')
    },
    instructorInNonBasicView () {
      return this.isInstructor() && !this.isInstructorWithAnonymousView && !this.presentationMode && this.questionView !== 'basic' && !this.inIFrame
    },
    studentShowPointsNonClicker () {
      return this.source === 'a' && !this.inIFrame && !this.isAnonymousUser && !this.isInstructorWithAnonymousView && !this.isInstructor() && this.user.role !== 5 && this.showPointsPerQuestion && this.assessmentType !== 'clicker' && !this.isFormative
    },
    studentNonClicker () {
      return this.source === 'a' && !this.inIFrame && !this.isAnonymousUser && !this.isInstructorWithAnonymousView && !this.isInstructor() && this.user.role !== 5 && this.assessmentType !== 'clicker'
    },
    getIframeTitle () {
      return `${this.title} - Question #${this.currentPage}`
    },
    fixLinks (iframeId) {
      $(`#${iframeId}`).contents().find('body').find('a').attr('target', '_blank')
    },
    getHintPenaltyMessage () {
      let message = ''

      if (this.questions[this.currentPage - 1].hint && this.hintPenalty) {
        message = `  In addition, a hint penalty of ${this.hintPenalty}% will be applied since the hint is viewable.`
      }
      return message
    },
    getMaximumNumberOfPointsPossible () {
      let numDeductionsToApply = parseFloat(this.questions[this.currentPage - 1].submission_count)
      this.hintPenalty = this.questions[this.currentPage - 1].shown_hint ? this.hintPenaltyIfShownHint : 0
      let totalPenalty = numDeductionsToApply * parseFloat(this.numberOfAllowedAttemptsPenalty) + this.hintPenalty
      return +Math.max(0, ((1 * this.questions[this.currentPage - 1].points) * (1 - totalPenalty / 100))).toFixed(4)
    },
    async handleShowSolution () {
      try {
        const { data } = await axios.post(`/api/solutions/show-solution/${this.assignmentId}/${this.questions[this.currentPage - 1].id}`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        await this.updateLastSubmittedAndLastResponse(this.assignmentId, this.questions[this.currentPage - 1].id)
        this.$bvModal.hide('modal-confirm-give-up')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getNumberOfRemainingAttempts () {
      let plural = this.numberOfAllowedAttempts > 1 ? 's' : ''
      return this.isH5pVideoInteraction
        ? `For each partial submission you are allowed ${this.numberOfAllowedAttempts} attempt${plural}.`
        : `${this.questions[this.currentPage - 1].submission_count}/${this.numberOfAllowedAttempts} attempts`
    },
    setMyFavoritesFolder (myFavoritesFolder) {
      this.myFavoritesFolder = myFavoritesFolder
    },
    async filterByQuestionType (questionType) {
      this.filteringByQuestionType = true
      await this.getSelectedQuestions(this.assignmentId, this.questionId)
      let questions
      switch (questionType) {
        case ('auto-graded-only'):
          questions = this.questions.filter(question => question.technology !== 'text')
          questions.length ? this.questions = questions
            : this.$noty.info('This assignment does not have any auto-graded only assessments.')
          break
        case ('open-ended-only'):
          questions = this.questions.filter(question => question.technology === 'text')
          questions.length ? this.questions = questions
            : this.$noty.info('This assignment does not have any open-ended only assessments.')
          break
        case ('any-question-type'):
          break
      }
      this.filteringByQuestionType = false
    },
    async submitReasonForUploadingLocalSolution () {
      if (this.reasonForUploadingLocalSolution === 'libretexts_solution_error') {
        try {
          this.libretextsSolutionErrorForm.question_id = this.questions[this.currentPage - 1].id
          const { data } = await this.libretextsSolutionErrorForm.post(`/api/libretexts/solution-error`)
          if (data.type === 'success') {
            this.$noty.success(data.message)
          }
        } catch (error) {
          if (error.message.includes('status code 422')) {
            this.$nextTick(() => fixInvalid())
            this.allFormErrors = this.libretextsSolutionErrorForm.errors.flatten()
            this.$bvModal.show('modal-form-errors-libretexts-solution-error-form')
          }
          return false
        }
      }
      this.$bvModal.hide('modal-reason-for-uploading-local-solution')
      await this.openUploadFileModal(this.questions[this.currentPage - 1].id)
    },
    async initOpenUploadSolutionModal () {
      this.reasonForUploadingLocalSolution = 'prefer_own_solution'
      this.libretextsSolutionErrorForm.errors.clear()
      this.libretextsSolutionErrorForm.text = ''
      this.audioSolutionUploadUrl = `/api/solution-files/audio/${this.assignmentId}/${this.questions[this.currentPage - 1].id}`
      this.questions[this.currentPage - 1].solution_html
        ? this.$bvModal.show('modal-reason-for-uploading-local-solution')
        : await this.openUploadFileModal(this.questions[this.currentPage - 1].id)
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    async redirectIfBetaCourse () {
      try {
        const { data } = await axios.get(`/api/beta-assignments/get-from-alpha-assignment/${this.assignmentId}`)
        if (data.type !== 'success') {
          await this.$router.push({ name: 'beta_assignments_redirect_error' })
        }
        if (data.login_redirect) {
          await this.$router.push({ name: 'login' })
        } else if (data.beta_assignment_id) {
          this.assignmentId = data.beta_assignment_id
          console.log(`Beta assignment with id: ${this.assignmentId}`)
        }
      } catch (error) {
        await this.$router.push({ name: 'beta_assignments_redirect_error' })
      }
    },
    updateTotalScore () {
      let autoGradedScore = this.questions[this.currentPage - 1].submission_score
      let openEndedScore = this.questions[this.currentPage - 1].submission_file_score
      this.questions[this.currentPage - 1].total_score =
        (autoGradedScore === 'N/A' ? 0 : parseFloat(autoGradedScore)) +
        (openEndedScore === 'N/A' ? 0 : parseFloat(openEndedScore))
    },
    async updateCompletionScoringMode (questionId) {
      try {
        const { data } = await this.completionScoringModeForm.patch(`/api/assignments/${this.assignmentId}/questions/${questionId}/update-completion-scoring-mode`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].completion_scoring_mode = data.completion_scoring_mode
          if (data.update_completion_scoring_mode) {
            for (let i = 0; i < this.questions.length; i++) {
              this.questions[i].completion_scoring_mode = data.completion_scoring_mode
            }
          }
          this.setCompletionScoringModeMessage()
          this.$bvModal.hide('modal-update-completion-scoring-mode')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.completionScoringModeForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-completion-scoring-mode')
        }
      }
    },
    openUpdateCompletionScoringModeModal () {
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-update-points-if-beta-assignment')
        return false
      }
      if (this.hasAtLeastOneSubmission) {
        this.$noty.info('Students have already made submission for this question so you can\'t change the scoring method.')
        return false
      }
      if (this.scoringType === 'c') {
        if (this.questions[this.currentPage - 1].completion_scoring_mode === '100% for either') {
          this.completionScoringModeForm.completion_scoring_mode = '100% for either'
          this.completionScoringModeForm.completion_split_auto_graded_percentage = '50'
          this.completionSplitOpenEndedPercentage = '50'
        } else {
          this.completionScoringModeForm.completion_scoring_mode = 'split'
          this.completionScoringModeForm.completion_split_auto_graded_percentage = this.questions[this.currentPage - 1].completion_scoring_mode.replace(/\D/g, '')
          this.completionSplitOpenEndedPercentage = 100 - parseInt(this.completionScoringModeForm.completion_split_auto_graded_percentage)
        }
      }
      this.$bvModal.show('modal-update-completion-scoring-mode')
    },
    setCompletionScoringModeMessage () {
      this.completionScoringModeMessage = ''
      let completionScoringMode = this.questions[this.currentPage - 1].completion_scoring_mode
      if (this.scoringType === 'c' && this.questions[this.currentPage - 1].has_auto_graded_and_open_ended) {
        let autoGradedPercent = parseFloat(completionScoringMode.replace(/\D/g, ''))
        let openEndedPercent = (100 - autoGradedPercent) / 100
        let autoGradedPoints = (autoGradedPercent / 100) * this.questions[this.currentPage - 1].points
        let openEndedPoints = openEndedPercent * this.questions[this.currentPage - 1].points
        if (completionScoringMode.includes('either')) {
          this.completionScoringModeMessage = this.isInstructor()
            ? 'Students receive full credit for either an auto-graded or open-ended submission.'
            : 'You will receive full credit for either an auto-graded or open-ended submission.<br>Note that your score will only be saved for the first submitted response.'
        } else {
          this.completionScoringModeMessage = this.isInstructor()
            ? 'Students'
            : 'You will'
          this.completionScoringModeMessage += ` receive ${autoGradedPoints} points for an auto-graded submission and ${openEndedPoints} points for an open-ended submission.`
        }
      }
    },
    resizeHandler () {
      this.zoomedOut = this.zoomGreaterThan(1.2)
      if (this.zoomedOut) {
        this.questionCol = 12
        this.bCardCols = 12
      } else {
        this.setQuestionCol()
        this.bCardCols = 4
      }
    },
    setQuestionCol () {
      this.questionCol = this.assessmentType === 'clicker' ||
      !this.showSubmissionInformation
        ? 12 : 8
      // override this for files
      if (this.questions[this.currentPage - 1] && this.questions[this.currentPage - 1].open_ended_submission_type === 'file' &&
        ((this.user.role === 3 && !this.isAnonymousUser) || (this.isInstructor() && !this.isInstructorWithAnonymousView))) {
        this.questionCol = 8
      }
      // override again for formative courses
      if (this.isFormative && this.user.role === 3) {
        this.questionCol = 12
      }
      // override for clicker app
      if (this.clickerApp) {
        this.questionCol = 12
      }
    },
    async getMyFavoriteQuestions () {
      try {
        const { data } = await axios.get(`/api/my-favorites/assignment/${this.assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.myFavoriteQuestionIds = []
        this.myFavoriteQuestions = data.my_favorite_questions
        console.info(this.myFavoriteQuestions)
        for (let i = 0; i < this.myFavoriteQuestions.length; i++) {
          this.myFavoriteQuestionIds.push(this.myFavoriteQuestions[i].my_favorites_question_id)
        }
        // console.log(this.myFavoriteQuestionIds)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async addQuestionToFavorites (type = 'single') {
      let questionIds = (type === 'all')
        ? this.questions.map(question => question.id)
        : [this.questions[this.currentPage - 1].id]
      if (this.myFavoritesFolder === null) {
        this.$noty.info('Please first choose a Favorites folder.')
        return false
      }
      try {
        const { data } = await axios.post('/api/my-favorites',
          {
            question_ids: questionIds,
            folder_id: this.myFavoritesFolder,
            chosen_assignment_ids: [this.assignmentId]
          })

        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        for (let i = 0; i < questionIds.length; i++) {
          this.myFavoriteQuestionIds.push(questionIds[i])
        }
        this.myFavoriteQuestions.push({
          my_favorites_folder_id: this.myFavoritesFolder,
          my_favorites_question_id: this.questions[this.currentPage - 1].id
        })
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async removeMyFavoritesQuestion () {
      let questionId = this.questions[this.currentPage - 1].id
      // console.log(this.myFavoriteQuestions)
      let folderId = this.myFavoriteQuestions.find(myFavoriteQuestion => myFavoriteQuestion.my_favorites_question_id === questionId).my_favorites_folder_id
      // console.log(folderId)
      try {
        const { data } = await axios.delete(`/api/my-favorites/folder/${folderId}/question/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.myFavoriteQuestions = this.myFavoriteQuestions.filter(myFavoriteQuestion => myFavoriteQuestion.my_favorites_question_id !== questionId)
        // console.log(this.myFavoriteQuestions)
        this.myFavoriteQuestionIds = []
        for (let i = 0; i < this.myFavoriteQuestions.length; i++) {
          this.myFavoriteQuestionIds.push(this.myFavoriteQuestions[i].my_favorites_question_id)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    nextQuestion () {
      if (this.currentPage < this.questions.length) {
        this.currentPage++
        this.changePage(this.currentPage)
      }
    },
    showAttributionModal () {
      this.$bvModal.show('modal-attribution')
    },
    getThumbsUpWidth () {
      return this.inIFrame ? 150 : 275
    },
    viewInADAPT () {
      let link = window.location.href
      window.open(link)
    },
    async parentUpdateShownInIFrame (item, newValue) {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/${this.questions[this.currentPage - 1].id}/iframe-properties`,
          { item: item })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.questions[this.currentPage - 1][`${item}_information_shown_in_iframe`] = newValue
        switch (item) {
          case ('submission'):
            this.submissionInformationShownInIFrame = newValue
            break
          case ('assignment'):
            this.assignmentInformationShownInIFrame = newValue
            break
          case ('attribution'):
            this.attributionInformationShownInIFrame = newValue
            break
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.questions[this.currentPage - 1][`${item}_information_shown_in_iframe`] = !newValue
        switch (item) {
          case ('submission'):
            this.submissionInformationShownInIFrame = !newValue
            break
          case ('assignment'):
            this.assignmentInformationShownInIFrame = !newValue
            break
          case ('attribution'):
            this.attributionInformationShownInIFrame = !newValue
            break
        }
      }
    },
    refreshQuestionParent (message) {
      this.$noty.success(message)
    },
    async reloadSingleQuestion () {
      this.questionId = this.questions[this.currentPage - 1].id
      await this.getSelectedQuestions(this.assignmentId, this.questionId)
      this.currentPage = this.getInitialCurrentPage(this.questionId)
      this.cacheIndex++
      await this.changePage(this.currentPage)
      this.reportCacheKey++
    },
    async reloadQuestionParent (questionId, message) {
      try {
        const { data } = await axios.get(`/api/questions/${questionId}`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.questions[this.currentPage - 1].non_technology_iframe_src = data.question.non_technology_iframe_src
        this.questions[this.currentPage - 1].non_technology = this.questions[this.currentPage - 1].non_technology_iframe_src !== ''
        this.questions[this.currentPage - 1].technology_iframe = data.question.technology_iframe_src
        this.questions[this.currentPage - 1].title = data.question.title
        this.questions[this.currentPage - 1].qti_json = data.question.qti_json
        this.qtiJson = data.question.qti_json
        this.questions[this.currentPage - 1].text_question = data.question.text_question
        this.questions[this.currentPage - 1].a11y_question = data.question.a11y_question
        this.questions[this.currentPage - 1].solution_html = data.question.solution_html
        this.questions[this.currentPage - 1].answer_html = data.question.answer_html
        this.questions[this.currentPage - 1].hint = data.question.hint
        this.questions[this.currentPage - 1].libretexts_link = data.question.libretexts_link
        this.questions[this.currentPage - 1].notes = data.question.notes
        this.$bvModal.hide('modal-question-has-submissions-in-this-assignment')
        this.cacheIndex++
        this.$forceUpdate()
        await this.$nextTick(() => {
          this.changePage(this.currentPage)
        })

        if (message) {
          this.$noty.success(message)
        }
        // console.log(this.cacheIndex)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateProperties () {
      this.propertiesForm.auto_attribution = this.autoAttribution
      try {
        const { data } = await this.propertiesForm.patch(`/api/questions/properties/${this.questions[this.currentPage - 1].id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'success') {
          return false
        }
        this.questions[this.currentPage - 1].auto_attribution = this.autoAttribution
        this.questions[this.currentPage - 1].attribution = this.autoAttribution ? null : this.propertiesForm.attribution
        this.questions[this.currentPage - 1].private_description = this.propertiesForm.private_description
        this.$bvModal.hide('modal-properties')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async openModalProperties () {
      this.currentUrl = this.getCurrentUrl()
      this.embedCode = this.getEmbedCode()
      this.libraryText = this.questions[this.currentPage - 1].library
      this.adaptId = `${this.assignmentId}-${this.questions[this.currentPage - 1].id}`
      this.pageId = this.questions[this.currentPage - 1].page_id
      this.assignmentInformationShownInIFrame = this.questions[this.currentPage - 1].assignment_information_shown_in_iframe
      this.submissionInformationShownInIFrame = this.questions[this.currentPage - 1].submission_information_shown_in_iframe
      this.attributionInformationShownInIFrame = this.questions[this.currentPage - 1].attribution_information_shown_in_iframe
      this.technology = this.questions[this.currentPage - 1].technology
      this.formattedTechnology = this.getFormattedTechnology(this.technology)
      this.technologySrc = this.getTechnologySrc('technology', 'technology_src', this.questions[this.currentPage - 1])
      this.a11yTechnologySrc = this.getTechnologySrc('a11y_technology', 'a11y_technology_src', this.questions[this.currentPage - 1])
      this.propertiesForm.attribution = this.questions[this.currentPage - 1].attribution
      this.propertiesForm.private_description = this.questions[this.currentPage - 1].private_description
      this.autoAttribution = !!this.questions[this.currentPage - 1].auto_attribution
      let vm = this
      this.updateAutoAttribution(vm, this.questions[this.currentPage - 1].license, this.questions[this.currentPage - 1].license_version, this.questions[this.currentPage - 1].author, this.questions[this.currentPage - 1].source_url)
      this.$bvModal.show('modal-properties')
    },
    createQrCode () {
      qrCodeConfig.data = this.formativeQuestionURL ? this.formativeQuestionURL : this.currentUrl
      const qrCode = new QRCodeStyling(qrCodeConfig)
      qrCode.append(this.$refs['qrCodeCanvas'])
    },
    async submitRemoveSolution () {
      try {
        const { data } = await axios.delete(`/api/solution-files/${this.assignmentId}/${this.questions[this.currentPage - 1].id}`)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].solution = null
          if (this.questions[this.currentPage - 1].solution_html) {
            this.questions[this.currentPage - 1].solution_type = 'html'
          }
          this.$bvModal.hide('modal-remove-solution')
        }
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    hotKeys (event) {
      let target = $(event.target)
      if (target.parents('div#question-to-view').length) {
        // don't want any right or left arrow while within the question context
        return
      }
      if (event.key === 'ArrowRight') {
        let modalElements = document.getElementsByClassName('modal-content')
        this.$bvModal.hide('modal-upload-file')
        if (!modalElements.length) {
          this.nextQuestion()
        }
      }
      if (event.key === 'ArrowLeft' && this.currentPage > 1) {
        let modalElements = document.getElementsByClassName('modal-content')
        this.$bvModal.hide('modal-upload-file')
        if (!modalElements.length) {
          this.currentPage--
          this.changePage(this.currentPage)
        }
      }
      if (this.isInstructor() && event.ctrlKey && event.key === 'e') {
        this.editQuestionSource(this.questions[this.currentPage - 1])
      }

      if ((event.key === 'Escape' && ('#modal-instructor-clicker-question___BV_modal_content_').length)) {
        this.$bvModal.hide('modal-instructor-clicker-question')
      }
      if (event.key === 'Escape' &&
        this.questionToEdit.id &&
        !$('#my-questions-question-to-view-questions-editor___BV_modal_content_').length &&
        !$('#modal-framework-aligner___BV_modal_content_').length) {
        // hack....just close if the preview isn't open.  For some reason, I couldn't get the edit modal to close
        this.$bvModal.hide(`modal-edit-question-${this.questionToEdit.id}`)
      }
    },
    async setPageAsSubmission (questionId) {
      try {
        // console.log(this.questionSubmissionPageForm)
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
    inputFile (newFile, oldFile) {
      if (newFile && oldFile && !newFile.active && oldFile.active) {
        // Get response data

        if (newFile.xhr) {
          //  Get the response status code
          // console.log('status', newFile.xhr.status)
          if (newFile.xhr.status === 200) {
            if (!this.handledOK) {
              this.handledOK = true
              // console.log(this.handledOK)
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

          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.uploadFileForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-file-upload')

          return prevent()
        }
        let validUploadTypesMessage = `The valid upload types are: ${this.getSolutionUploadTypes()}`
        let validExtension
        if (this.uploadLevel === 'question') {
          validExtension = this.isOpenEndedAudioSubmission ? /\.(mp3)$/i.test(newFile.name) : /\.(pdf|txt|png|jpeg|jpg)$/i.test(newFile.name)
        } else {
          validExtension = /\.(pdf)$/i.test(newFile.name)
        }

        if (!validExtension) {
          this.uploadFileForm.errors.set(this.uploadFileType, validUploadTypesMessage)
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.uploadFileForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-file-upload')
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
        // console.log(this.questionView)
        const { data } = await axios.patch(`/api/cookie/set-question-view/${this.questionView}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.questionView = (this.questionView === 'basic') ? 'expanded' : 'basic'
      } catch (error) {
        this.$noty.error(error.message)
      }

      if (this.questionView === 'expanded') {
        this.$nextTick(() => {
          MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        })
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
        this.textSubmissionForm.text_submission = this.questions[this.currentPage - 1].open_ended_default_text
        this.questions[this.currentPage - 1].submission = null
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
        this.questions[this.currentPage - 1].open_ended_default_text = this.openEndedDefaultTextForm.open_ended_default_text
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async endClickerAssessment () {
      if (this.clickerStatus !== 'view_and_submit') {
        return
      }
      this.timeLeft = 0
      if (this.user.role === 2) {
        try {
          const { data } = await axios.post(`/api/assignments/${this.assignmentId}/questions/${this.questions[this.currentPage - 1].id}/end-clicker-assessment`)
          if (data.type === 'error') {
            this.$noty.error(data.message)
          } else {
            this.$bvModal.hide('modal-confirm-close-poll')
          }
        } catch (error) {
          this.$noty.error(error.message)
        }
      } else {
        if (this.clickerApp) {
          window.parent.postMessage(`{"source": "app_clicker","message": "Submissions are no longer accepted.","type":"info"}`, '*')
        }
      }
      this.clickerStatus = 'view_and_not_submit'
    },
    getTimeLeftMessage (props, assessmentType) {
      let message = ''
      if (assessmentType !== 'clicker') {
        message = '<span class="font-weight-bold">Time Until Due:</span> '
      }

      let timeLeft = parseInt(this.timeLeft) / 1000
      if (timeLeft >= 60 * 60 * 24) {
        message += `${props.days} days, ${props.hours} hours,
          ${props.minutes} minutes, ${props.seconds} seconds`
      } else if (timeLeft >= 60 * 60) {
        message += `${props.hours}  hours,
          ${props.minutes}   minutes, ${props.seconds} seconds`
      } else if (timeLeft > 60) {
        message += `${props.minutes} minutes, ${props.seconds} seconds`
      } else {
        message += `${props.seconds} seconds`
      }
      message += assessmentType === 'clicker' ? ` left` : '.'
      return message
    },
    async startClickerAssessment () {
      this.clickerModalButtons = {
        'submissions': true,
        'answer': false,
        'close': false
      }
      this.openingClicker = true
      try {
        const { data } = await this.clickerTimeForm.post(`/api/assignments/${this.assignmentId}/questions/${this.questions[this.currentPage - 1].id}/start-clicker-assessment`)
        if (data.type === 'error') {
          this.$noty[data.type](data.message)
          return false
        }
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
      if (this.openEndedSubmissionDataType !== 'success') {
        this.$bvModal.show('modal-thumbs-down')
      } else {
        this.cacheKey++
        this.$bvModal.show('modal-submission-accepted')
        this.completedAllAssignmentQuestions = data.completed_all_assignment_questions && this.user.role === 3
      }

      if (data.type === 'success') {
        this.questions[this.currentPage - 1].date_submitted = data.date_submitted
        this.questions[this.currentPage - 1].submission_file_url = data.submission_file_url
        this.questions[this.currentPage - 1].late_file_submission = data.late_file_submission
        this.questions[this.currentPage - 1].submission_file_exists = true
        this.questions[this.currentPage - 1].submission_file_score = data.score
        this.updateTotalScore()
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
    getFormattedTechnology (technology) {
      let formattedTechnology
      switch (technology) {
        case ('h5p'):
          formattedTechnology = 'H5P'
          break
        case ('webwork'):
          formattedTechnology = 'WebWork'
          break
        case ('imathas'):
          formattedTechnology = 'IMathAS'
          break
        default:
          formattedTechnology = technology
      }
      return formattedTechnology
    },
    getEmbedCode () {
      return `<iframe id="adapt-${this.assignmentId}-${this.questions[this.currentPage - 1].id}" allowtransparency="true" frameborder="0" scrolling="no" src="${this.currentUrl}" style="width: 1px;min-width: 100%;min-height: 100px;" />`
    },
    getCurrentUrl () {
      return `${window.location.origin}/assignments/${this.assignmentId}/questions/view/${this.questions[this.currentPage - 1].id}`
    },
    getInitialCurrentPage (questionId) {
      for (let i = 1; i <= this.questions.length; i++) {
        if (parseInt(this.questions[i - 1].id) === parseInt(questionId)) {
          return i
        }
      }
    },
    capitalize (word) {
      return word ? word.charAt(0).toUpperCase() + word.slice(1) : ''
    },
    getOpenEndedTitle () {
      let openEndedSubmissionType = this.openEndedSubmissionType.includes('text') ? 'text' : this.openEndedSubmissionType
      let capitalizedTitle = this.capitalize(openEndedSubmissionType)
      return `<h2 class="h7">${capitalizedTitle} Submission Information</h2>`
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
          this.questions[this.currentPage - 1].submission_file_score = data.score
          this.updateTotalScore()
          this.cacheKey++
          this.$bvModal.show('modal-submission-accepted')
          this.completedAllAssignmentQuestions = data.completed_all_assignment_questions && this.user.role === 3
        } else {
          this.$bvModal.show('modal-thumbs-down')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateClickerMessage (clickerStatus) {
      switch (clickerStatus) {
        case ('show_go'):
          this.clickerMessage = 'This question is ready to be opened.'
          this.clickerMessageType = 'info'
          break
        case ('view_and_submit'):
          this.clickerMessage = 'This question is open and submissions are being recorded.'
          this.clickerMessageType = 'success'
          if (this.user.role === 2) {
            this.openingClicker = false
            this.modalInstructorClickerQuestionShown = true
            this.$bvModal.show('modal-instructor-clicker-question')
          }
          break
        case ('view_and_not_submit'):
          break
        case ('neither_view_nor_submit'):
          this.clickerMessage = this.user.role === 2
            ? 'This question is not yet open.'
            : `Please wait for your instructor to open Question #${this.currentPage} for submission.`
          this.clickerMessageType = 'info'
      }
      this.clickerStatus = clickerStatus
      console.log('New clicker status: ' + this.clickerStatus)
    },
    initClickerPolling () {
      this.isLoadingPieChart = true
      this.submitClickerPolling(this.questions[this.currentPage - 1].id)
      if (this.clickerPollingSetInterval) {
        clearInterval(this.clickerPollingSetInterval)
        this.clickerPollingSetInterval = null
      }
      const self = this
      if (this.user.role === 2) {
        this.clickerPollingSetInterval = setInterval(function () {
          self.submitClickerPolling(self.questions[self.currentPage - 1].id)
        }, 3000)
      }
    },
    async submitClickerPolling (questionId) {
      try {
        const { data } = await axios.get(`/api/submissions/${this.assignmentId}/questions/${questionId}/pie-chart-data`)

        if (data.type !== 'error') {
          // window.location = `/assignments/${this.assignmentId}/questions/view/${data.redirect_question}`
          // console.log(data)
          this.piechartdata = data.pie_chart_data
          this.responsePercent = data.response_percent
          this.correctAnswerIndex = data.correct_answer_index
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
    async initUpdateOpenEndedSubmissionType (questionId) {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/${questionId}/has-non-scored-submission-files`)
        if (data.type === 'success') {
          if (data.has_non_scored_submission_files) {
            this.confirmDeleteOpenEndedSubmissionsMessage = data.message
            this.$bvModal.show('modal-confirm-delete-open-ended-submissions')
          } else {
            await this.updateOpenEndedSubmissionType(questionId)
          }
        } else {
          this.openEndedSubmissionType = this.originalOpenEndedSubmissionType
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.openEndedSubmissionType = this.originalOpenEndedSubmissionType
      }
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
    openShowAssignmentStatisticsModal () {
      this.showAssignmentStatisticsModal = true
    },
    getSubject () {
      return `${this.name}, Question #${this.currentPage}`
    },

    async openContactGraderModal (type = 'open-ended') {
      const { data } = await axios.get(`/api/contact-grader-overrides/${this.assignmentId}`)
      if (data.type === 'error') {
        this.$noty.error(data.message)
        return false
      }
      let graderId = this.questions[this.currentPage - 1].grader_id
      let contactGraderOverrideId = data.contact_grader_override_id
      let defaultGraderId = data.default_grader_id
      if (type === 'auto-graded') {
        graderId = contactGraderOverrideId || defaultGraderId
      } else if (contactGraderOverrideId) {
        graderId = contactGraderOverrideId
      }
      this.$refs.email.setExtraParams({
        'assignment_id': this.assignmentId,
        'question_id': this.questions[this.currentPage - 1].id
      })
      this.$refs.email.openSendEmailModal(graderId)
    },
    getModalUploadFileTitle () {
      let solutionType = this.solutionTypeIsPdfImage ? 'PDF/Image' : 'Audio'
      return this.user.role === 3 ? 'Upload Open-ended Submission (' + this.getSolutionUploadTypes() + ')' : `Upload ${solutionType} Solution`
    },
    getSolutionUploadTypes () {
      if (this.uploadLevel === 'question') {
        return this.isOpenEndedAudioSubmission ? getAcceptedFileTypes('.mp3') : getAcceptedFileTypes()
      } else {
        return getAcceptedFileTypes('.pdf')
      }
    },
    async updateLastSubmittedAndLastResponse (assignmentId, questionId) {
      try {
        const { data } = await axios.get(`/api/assignments/${assignmentId}/${questionId}/last-submitted-info`)
        let info = ['last_submitted',
          'student_response',
          'submission_count',
          'submission_score',
          'late_penalty_percent',
          'answered_correctly_at_least_once',
          'late_question_submission',
          'qti_answer_json',
          'session_jwt',
          'qti_json',
          'solution',
          'solution_file_url',
          'solution_text',
          'solution_type',
          'answer_html',
          'solution_html',
          'submission_array'
        ]
        console.log(data['submission_array'])
        for (let i = 0; i < info.length; i++) {
          this.questions[this.currentPage - 1][info[i]] = data[info[i]]
        }
        if (this.questions[this.currentPage - 1]['technology'] === 'webwork') {
          if (data.technology_iframe_src) {
            // need to re-load the question potentially for alogrithmic solutions
            this.questions[this.currentPage - 1].technology_iframe = data.technology_iframe_src
            let vm = this
            await this.getTechnologySrcDoc(vm, data.technology_iframe_src, this.assignmentId, this.questionId, 'submissions')
            this.cacheIndex++
          }
          this.addGlow(this.event, data['submission_array'], this.questions[this.currentPage - 1]['technology'])
        }
        if (this.questions[this.currentPage - 1]['technology'] === 'imathas') {
          this.questions[this.currentPage - 1].technology_iframe = data.technology_iframe_src
        }
        if (['webwork', 'imathas'].includes(this.questions[this.currentPage - 1]['technology'])) {
          this.submissionArray = data['submission_array']
        }

        this.qtiJson = this.questions[this.currentPage - 1]['qti_json']
        this.$forceUpdate()
        console.log(data.too_many_submissions)
        this.submitButtonActive = !data.too_many_submissions
        if (['real time', 'learning tree'].includes(this.assessmentType)) {
          this.numberOfRemainingAttempts = this.getNumberOfRemainingAttempts()
          this.maximumNumberOfPointsPossible = this.getMaximumNumberOfPointsPossible()
        }
        this.updateTotalScore()
        await this.updateTimeOnTask(assignmentId, questionId)

        // show initially if you made no attempts OR you've already visited the learning tree
        // if you made an attempt, hide the question until you visit the learning tree
        // only get additional points and with a penalty IF they get it all correct
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateTimeOnTask (assignmentId, questionId) {
      let submitTimeLessStartTime = performance.now() - this.taskStartTime
      submitTimeLessStartTime /= 1000
      submitTimeLessStartTime = Math.round(submitTimeLessStartTime)
      console.log(`Submit less start time: ${submitTimeLessStartTime}`)
      console.log(`Time inactive: ${this.totalTimeInTaskInactive}`)
      try {
        await axios.patch(`/api/assignment-question-time-on-tasks/assignment/${assignmentId}/question/${questionId}`, {
          time_on_task: submitTimeLessStartTime - this.totalTimeInTaskInactive > 0 ? submitTimeLessStartTime - this.totalTimeInTaskInactive : submitTimeLessStartTime,
          submit_time_less_start_time: submitTimeLessStartTime,
          total_time_in_task_inactive: this.totalTimeInTaskInactive
        })
      } catch (error) {
        console.log(error.message)
      }
      this.startTimeTaskInactive = 0
    },
    isInstructor () {
      return (this.user.role === 2)
    },
    hideResponse () {
      this.showSubmissionMessage = false
    },
    async showResponse (data) {
      console.log(data)
      this.cacheKey++
      this.questions[this.currentPage - 1].submissions_array = []
      this.submissionDataType = ['success', 'info'].includes(data.type) ? data.type : 'danger'
      if (data.type === 'unconfirmed') {
        this.user.role === 3 ? await this.initConfirmSubmission() : await this.completeSubmission()
        return
      }
      this.submissionDataMessage = data.message
      this.learningTreeMessage = data.learning_tree_message
      this.showSubmissionMessage = true
      if (this.submissionDataType !== 'danger') {
        if (data.not_updated_message) {
          this.$bvModal.show('modal-not-updated')
        } else {
          if (this.isH5pVideoInteraction) {
            await this.getH5pVideoInteractionSubmissions()
            this.completedAllAssignmentQuestions = false
            this.$bvModal.show('modal-submission-accepted')
          } else {
            if (!this.isFormative) {
              if (this.clickerApp) {
                let submissionDataMessage = data.message.replace('"', '\'\'')
                let type = data.type
                window.parent.postMessage(`{"source": "app_clicker","message": "${submissionDataMessage}","type":"${type}"}`, '*')
              } else {
                this.$bvModal.show('modal-submission-accepted')
                this.completedAllAssignmentQuestions = data.completed_all_assignment_questions && this.user.role === 3
              }
            }
          }
        }
        await this.updateLastSubmittedAndLastResponse(this.assignmentId, this.questions[this.currentPage - 1].id)
        this.renderMathJax()
      } else {
        if (this.clickerApp) {
          let submissionDataMessage = data.message.replace('"', '\'\'')
          window.parent.postMessage(`{"source": "app_clicker","message": "${submissionDataMessage}","type":"error"}`, '*')
        } else {
          this.$bvModal.show('modal-thumbs-down')
        }
      }
    },
    async updatePoints (questionId) {
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-update-points-if-beta-assignment')
        return false
      }
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
          this.setCompletionScoringModeMessage()
          this.enteredPoints = false
          this.reportCacheKey++
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    updatePointsBasedOnNewWeights (data) {
      for (let i = 0; i < data.updated_points.length; i++) {
        let updatedQuestion = data.updated_points[i]
        this.questions.find(question => parseInt(question.id) === parseInt(updatedQuestion.question_id)).points = updatedQuestion.points
      }
    },
    async updateWeight (questionId) {
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-update-points-if-beta-assignment')
        return false
      }
      try {
        const { data } = await this.questionWeightForm.patch(`/api/assignments/${this.assignmentId}/questions/${questionId}/update-weight`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].points = data.points
          this.questions[this.currentPage - 1].weight = this.questionWeightForm.weight
          this.updatePointsBasedOnNewWeights(data)
          this.setCompletionScoringModeMessage()
          this.enteredPoints = false
        }
      } catch (error) {
        error.message.includes('status code 422')
          ? this.$noty.error(this.questionWeightForm.errors.get('weight'))
          : this.$noty.error(error.message)
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
      this.$nextTick(() => {
        makeFileUploaderAccessible()
      })
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
        if (this.user.role === 3) {
          this.updateTotalScore()
        }
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
      if (this.$refs.upload) {
        this.$refs.upload.active = false
      }
      this.files = []
      this.processingFile = false
      this.$bvModal.hide(`modal-upload-file`)
    },
    showIframe () {
      this.iframeLoaded = true
    },
    getTitle (currentPage) {
      if (!this.questions[currentPage - 1]) {
        return ''
      }
      return `${this.questions[currentPage - 1].title}` ? this.questions[currentPage - 1].title : `Question #${currentPage - 1}`
    },
    getQtiJson () {
      let qtiJson
      qtiJson = this.questions[this.currentPage - 1].qti_json
      try {
        const parsedQtiJson = JSON.parse(qtiJson)
        if (this.presentationMode) {
          switch (parsedQtiJson.questionType) {
            case ('multiple_choice'):
              parsedQtiJson.feedback = {}
              qtiJson = JSON.stringify(parsedQtiJson)
              break
            default:
              break
          }
        }
      } catch (error) {
        console.log(error)
        // do nothing and keep the original
      }

      return { 'qtiJson': qtiJson, 'submitButtonActive': this.submitButtonActive }
    },
    getA11yQtiJson () {
      return {
        'qtiJson': this.questions[this.currentPage - 1].a11y_qti_json,
        'submitButtonActive': this.submitButtonActive
      }
    },
    async changePage (currentPage) {
      this.enteredPoints = false
      this.showQtiJsonQuestionViewer = false
      this.submitButtonActive = true
      if (!this.questions[currentPage - 1]) {
        this.$noty.error('No question exists; you may be trying to reload a question that you have removed from the assignment.')
        this.isLoading = false
        return false
      }

      if (this.isFormative) {
        this.formativeQuestionURL = window.location.origin + `/students/assignments/${this.assignmentId}/init-formative/${this.questions[currentPage - 1].id}`
      }

      this.qtiJson = this.questions[this.currentPage - 1].qti_json
      this.iframeDomLoaded = false
      this.submitButtonsDisabled = false
      console.log('webwork stuff')
      this.technologySrcDoc = ''

      await this.$nextTick(() => {
        switch (this.questions[this.currentPage - 1].technology) {
          case ('webwork'):
            let href = new URL(this.questions[this.currentPage - 1].technology_iframe)
            if (this.questions[this.currentPage - 1].session_jwt) {
              console.log(`New session JWT: ${this.questions[this.currentPage - 1].session_jwt}`)
              href.searchParams.set('sessionJWT', this.questions[this.currentPage - 1].session_jwt)
            }
            let vm = this
            this.getTechnologySrcDoc(vm, href.toString(), this.assignmentId, this.questions[this.currentPage - 1].id, 'submissions')
            break
        }
        this.submissionArray = this.questions[this.currentPage - 1]['submission_array']
      })
      if (this.user.role === 3) {
        if (this.pastDue) {
          this.initReviewQuestionTimeSpent()
        } else {
          this.taskStartTime = performance.now()
          this.totalTimeInTaskInactive = 0
        }
      }
      this.totalTimeInTaskInactive = 0
      this.startTimeTaskInactive = 0
      this.maxScore = null // used for H5P video interaction questions
      this.showSolutionTextForm = false
      this.showAddTextToSupportTheAudioFile = false
      this.$nextTick(() => {
        if (['real time', 'learning tree'].includes(this.assessmentType)) {
          this.numberOfRemainingAttempts = this.getNumberOfRemainingAttempts()
          this.maximumNumberOfPointsPossible = this.getMaximumNumberOfPointsPossible()
        }
      })

      await this.getCaseStudyNotesByQuestion()

      if (this.assessmentType === 'clicker') {
        this.clickerStatus = this.questions[currentPage - 1].clicker_status
        this.clickerTimeForm.time_to_submit = this.defaultClickerTimeToSubmit
        this.initClickerPolling()
        this.timeLeft = this.questions[this.currentPage - 1].clicker_time_left
        this.updateClickerMessage(this.clickerStatus)
      }
      if (this.assessmentType === 'learning tree') {
        const xCenter = window.innerWidth / 2
        this.learningTreeSrc = this.user.role === 3
          ? `/students/learning-trees/${this.assignmentId}/${this.questions[currentPage - 1].learning_tree_id}/${this.questions[this.currentPage - 1].id}/${xCenter}`
          : `/instructors/learning-trees/editor/${this.questions[currentPage - 1].learning_tree_id}/0/${xCenter}`
      }
      this.showOpenEndedSubmissionMessage = false
      this.solutionTextForm.solution_text = this.questions[currentPage - 1].solution_text
      this.audioUploadUrl = `/api/submission-audios/${this.assignmentId}/${this.questions[currentPage - 1].id}`
      this.showQuestion = true
      this.openEndedSubmissionType = this.questions[currentPage - 1].open_ended_submission_type
      this.isH5pVideoInteraction = this.questions[currentPage - 1].h5p_type === 'Interactive Video'
      this.isOpenEndedAudioSubmission = (this.openEndedSubmissionType === 'audio')
      this.showAudioUploadComponent = this.isOpenEndedAudioSubmission
      this.isOpenEndedFileSubmission = (this.openEndedSubmissionType === 'file')
      if (this.isH5pVideoInteraction) {
        await this.getH5pVideoInteractionSubmissions()
      }
      this.setCompletionScoringModeMessage()
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
        this.questionWeightForm.weight = this.questions[currentPage - 1].weight
        MathJax.Hub.Queue(['Typeset', MathJax.Hub])
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
      this.autoAttributionHTML = ''
      let vm = this
      this.updateAutoAttribution(vm, this.questions[this.currentPage - 1].license, this.questions[this.currentPage - 1].license_version, this.questions[this.currentPage - 1].author, this.questions[this.currentPage - 1].source_url)
      await this.setQuestionUpdatedAtSession(this.questions[this.currentPage - 1].loaded_question_updated_at)
      if (this.user.role === 3) {
        await this.canSubmit()
      }
      this.isLoading = false
      if (this.questions[this.currentPage - 1].qti_json || this.questions[this.currentPage - 1].a11y_qti_json) {
        this.showQtiJsonQuestionViewer = true
      }
    },
    async updateReviewQuestionTime (reviewSessionId) {
      try {
        const { data } = await axios.patch(`/api/review-history/assignment/${this.assignmentId}/question/${this.questions[this.currentPage - 1].id}`, {
          reviewSessionId: reviewSessionId,
          totalTimeInTaskInactive: this.totalTimeInTaskInactive
        })
        if (['unauthorized', 'error'].includes(data.message)) {
          clearInterval(this.reviewQuestionPollingSetInterval)
          this.reviewQuestionPollingSetInterval = null
        }
      } catch (error) {
        console.log(error.message)
      }
    },
    async setQuestionUpdatedAtSession (loadedQuestionUpdatedAt) {
      try {
        const { data } = await axios.post(`/api/questions/set-question-updated-at-session`,
          { loaded_question_updated_at: loadedQuestionUpdatedAt })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
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
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/view-questions-info`)
        if (data.type === 'error') {
          if (data.message === 'You are not allowed to access this assignment.') {
            this.isLMS = data.is_lms
            this.$bvModal.show('modal-enroll-in-course')
            this.modalEnrollInCourseIsShown = true
          } else {
            this.$noty.error(data.message)
          }
          return false
        }
        if (!this.modalEnrollInCourseIsShown && this.inIFrame) {
          $('#default-padding-top').css('padding-top', '0')
          $('#skip-link').remove()
        }
        let assignment = data.assignment
        this.canContactGrader = assignment.can_contact_grader
        this.betaAssignmentsExist = assignment.beta_assignments_exist
        this.isBetaAssignment = assignment.is_beta_assignment
        this.isFormative = assignment.is_formative_course || assignment.formative
        this.scoringType = assignment.scoring_type
        this.canViewHint = assignment.can_view_hint
        this.hintPenaltyIfShownHint = assignment.hint_penalty
        this.questionNumbersShownInIframe = assignment.question_numbers_shown_in_iframe
        this.questionNumbersShownOutOfIframe = this.user.role !== 3 || (assignment.question_url_view === 'assignment' || (assignment.question_url_view === 'question' && !this.$route.params.questionId))
        if (this.user.role === 3) {
          if (this.isLMS && !assignment.lti_launch_exists) {
            this.launchThroughLMSMessage = true
          } else if (!assignment.available || !assignment.shown) {
            this.availableOn = assignment.available_on
            this.assignmentShown = assignment.shown
            this.cannotViewAssessmentMessage = true
          }
          this.title = `${assignment.name}`
          this.fullPdfUrl = assignment.full_pdf_url
          this.showCurrentFullPDF = !!this.fullPdfUrl.length
        }

        if (this.user.role === 2) {
          this.questionView = assignment.question_view
        }
        this.name = assignment.name
        this.pastDue = assignment.past_due

        if (this.user.role === 3) {
          this.questionStartTime = this.$moment().unix()
          this.startTimeInactive = 0
        }

        this.assessmentType = assignment.assessment_type
        this.numberOfAllowedAttempts = assignment.number_of_allowed_attempts
        this.numberOfAllowedAttemptsPenalty = assignment.number_of_allowed_attempts_penalty
        this.presentationMode = (this.assessmentType === 'clicker')
        this.capitalFormattedAssessmentType = this.assessmentType === 'learning tree' ? 'Learning Trees' : 'Questions'
        this.has_submissions_or_file_submissions = assignment.has_submissions_or_file_submissions
        if (this.assessmentType !== 'clicker') {
          this.timeLeft = assignment.time_left
        } else {
          this.defaultClickerTimeToSubmit = assignment.default_clicker_time_to_submit
          this.clickerTimeForm.time_to_submit = this.defaultClickerTimeToSubmit
        }

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
        this.showUpdatePointsPerQuestion = assignment.points_per_question === 'number of points'
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
        this.isInstructorWithAnonymousView = data.is_instructor_with_anonymous_view
        if (this.isInstructor()) {
          await this.getMyFavoriteQuestions()
        }
        if (!this.questions.length) {
          this.initializing = false
          return false
        }

        if (this.questionId) {
          if (!this.initCurrentPage(this.questionId)) {
            this.showQuestionDoesNotExistMessage = true
            this.isLoading = false
            return false
          }
        }

        this.questionPointsForm.points = this.questions[this.currentPage - 1].points
        this.questionWeightForm.weight = this.questions[this.currentPage - 1].weight

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
      // console.log(this.questions)
      for (let i = 0; i <= this.questions.length - 1; i++) {
        if (parseInt(this.questions[i].id) === parseInt(this.questionId)) {
          this.currentPage = i + 1
          questionExistsInAssignment = true
        }
      }
      return questionExistsInAssignment
    },
    metaInfo () {
      return { title: 'Assignment Questions' }
    }
  }
}
</script>
<style scoped>
.v-enter-active,
.v-leave-active {
  transition: opacity .75s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}

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

#modal-instructor-clicker-question___BV_modal_backdrop_ {
  backdrop-filter: blur(5px);
  background-color: rgba(0, 0, 0, 0.5);
  opacity: 1 !important;
}

.modal .modal-huge {
  max-width: 90%;
  width: 90%;
}

</style>
