<template>
  <div :style="!inIFrame ? 'min-height:400px; margin-bottom:100px' : 'margin-bottom:10px;'">
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-completion-scoring-mode'"/>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-libretexts-solution-error-form'"/>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-file-upload'"/>

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
      <RequiredText/>
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
          <has-error :form="libretextsSolutionErrorForm" field="text"/>
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
      <RequiredText/>
      <b-form-group
        label-cols-sm="5"
        label-cols-lg="4"
        label-for="completion_scoring_mode"
      >
        <template slot="label">
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
        <has-error :form="completionScoringModeForm" field="completion_scoring_mode"/>
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
        <span v-html="
          questions[currentPage - 1].attribution !== null
            ? questions[currentPage - 1].attribution
            : autoAttributionHTML
        "
        />
      </div>
    </b-modal>
    <div v-if="modalEnrollInCourseIsShown" style="height: 375px"/>
    <b-modal
      id="modal-not-updated"
      ref="modalNotUpdated"
      hide-footer
      title="No Updated"
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
      id="modal-learning-tree"
      ref="modalLearningTree"
      hide-footer
      title="Explore Learning Tree"
    >
      <b-container>
        <b-row>
          <span class="font-weight-bold" style="font-size: large">
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
          <img :style="getThumbsUpStyle()" :src="asset('assets/img/thumbs_up_twice.gif?rnd=' + cacheKey)"
               :width="getThumbsUpWidth()"
          >
        </b-row>
        <p class="text-center" style="font-size: large">
          All question submissions successfully completed.
        </p>
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
        <span class="font-weight-bold" style="font-size: large" v-html="submissionDataMessage"/>
      </b-alert>
    </b-modal>
    <b-modal
      id="modal-submission-accepted"
      ref="modalSubmissionAccepted"
      hide-footer
      size="sm"
      title="Submission Accepted"
    >
      <b-container>
        <b-row>
          <img :style="getThumbsUpStyle()" :src="asset('assets/img/check_twice.gif?rnd=' + cacheKey)"
               :width="getThumbsUpWidth()"
          >
        </b-row>
        <b-row>
          <span style="font-size: large" v-html="submissionDataMessage"/>
        </b-row>
      </b-container>
    </b-modal>
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
    <EnrollInCourse :is-lms="isLMS"/>
    <Email id="contact-grader-modal"
           ref="email"
           extra-email-modal-text="Before you contact your grader, please be sure to look at the solutions first, if they are available."
           :from-user="user"
           title="Contact Grader"
           type="contact_grader"
           :subject="getSubject()"
    />
    <CannotAddAssessmentToBetaAssignmentModal/>
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
      You are trying to edit a question that is part of a Beta assignment. If you edit the question source, it will
      affect all other Beta assignments. Please get in touch with the Alpha instructor to see if an edit is possible.
    </b-modal>
    <b-modal
      id="modal-properties"
      ref="modalProperties"
      title="Properties"
      size="lg"
    >
      <b-container>
        <b-form-group
          id="private_description"
          label-cols-sm="3"
          label-cols-lg="2"
        >
          <template slot="label">
            Private Description
            <QuestionCircleTooltip :id="'private-description-tooltip'"/>
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
          <div v-show="autoAttribution">
            <span v-show="!autoAttributionHTML.length">No licensing information is available.</span>
            <span v-show="autoAttributionHTML.length" class="ml-2" v-html="autoAttributionHTML"/>
          </div>
          <ckeditor v-show="!autoAttribution"
                    v-model="propertiesForm.attribution"
                    :config="richEditorConfig"
                    tabindex="0"
                    @ready="handleFixCKEditor()"
          />
        </b-form-group>
      </b-container>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-properties')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="updateProperties()">
          Update
        </b-button>
      </template>
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
        <b-button size="sm" variant="primary" @click="close()">
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
    <CannotDeleteAssessmentFromBetaAssignmentModal/>
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
      id="modal-share"
      ref="modal_share"
      title="Share"
      ok-title="OK"
      size="xl"
    >
      <div v-if="questions && questions[currentPage-1]">
        <IframeInformation :assignment-information-shown-in-i-frame="assignmentInformationShownInIFrame"
                           :submission-information-shown-in-i-frame="submissionInformationShownInIFrame"
                           :attribution-information-shown-in-i-frame="attributionInformationShownInIFrame"
                           :parent-update-shown-in-i-frame="parentUpdateShownInIFrame"
        />
      </div>
      <div class="mb-2">
        <span class="font-weight-bold">Libretexts ID:</span> <span id="libretextsID">
          {{ libraryText }}-{{ pageId }}</span>
        <span class="text-info">
          <a
            href=""
            class="pr-1"
            aria-label="Copy Libretexts ID"
            @click.prevent="doCopy('libretextsID')"
          >
            <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
      </div>
      <div class="mb-2">
        <span class="font-weight-bold">ADAPT ID: </span><span id="adaptID">{{ adaptId }}</span>
        <span class="text-info">
          <a
            href=""
            class="pr-1"
            aria-label="Copy ADAPT Id"
            @click.prevent="doCopy('adaptID')"
          >
            <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
      </div>
      <div class="mb-2">
        <span class="font-weight-bold">ADAPT URL:</span> <span id="currentURL">{{
          currentUrl
        }}</span>
        <span class="text-info">
          <a
            href=""
            class="pr-1"
            aria-label="Copy ADAPT URL"
            @click.prevent="doCopy('currentURL')"
          >
            <font-awesome-icon :icon="copyIcon"/>
          </a>
        </span>
      </div>
      <div v-if="technologySrc" class="mb-2">
        <span class="font-weight-bold">Technology URL: </span><span id="technologySrc"
                                                                    v-html="technologySrc"
      />
      </div>
      <div v-if="a11yTechnologySrc" class="mb-2">
        <span class="font-weight-bold">A11y Technology URL: </span><span id="a11yTechnologySrc"
                                                                         v-html="a11yTechnologySrc"
      />
      </div>
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
                    <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
                        file.name
                      }}</span> -
                    <span>{{ formatFileSize(file.size) }} </span>
                    <span v-if="file.size > 10000000">Note: large files may take up to a minute to process.</span>
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
              <span v-if="showScores">  You received a score of {{
                  questions[currentPage - 1].submission_score
                }}.</span></span>
          </b-alert>
        </div>
      </div>
      <div v-if="questions.length && !initializing && !isLoading">
        <div v-show="isInstructorLoggedInAsStudent">
          <LoggedInAsStudent :student-name="user.first_name + ' ' + user.last_name"/>
        </div>
        <div v-if="inIFrame && (user.role === 2)">
          <b-button variant="primary" size="sm" class="mb-3" @click="viewInADAPT">
            View in ADAPT
          </b-button>
          <b-alert variant="info" :show="true">
            <strong>You are current logged in as an instructor. No responses will be saved.</strong>
          </b-alert>
        </div>
      </div>
      <div v-if="user.role === 3 && cannotViewAssessmentMessage">
        <b-alert variant="info" show>
          <span v-if="!assignmentShown" class="font-weight-bold">
            This assessment is part of an assignment which is not yet being shown to any students in this course.
          </span>
          <span v-if="assignmentShown" class="font-weight-bold">
            This assessment will become available on {{
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
        v-if="hasAtLeastOneSubmission && !presentationMode && !inIFrame && !isLoading && user.role === 2 && !isInstructorWithAnonymousView"
      >
        <b-alert variant="info" :show="true">
          <div class="font-weight-bold">
            <p>
              This problem is locked. Since students have already submitted responses, you cannot update the
              points per question nor change the open-ended submission type.
            </p>
            <p v-show="!showUpdatePointsPerQuestion">
              In addition, since you are computing points by question weights, you will not be able to remove the
              question
              as it will affect already submitted questions.
            </p>
          </div>
        </b-alert>
      </div>
      <div v-if="user.role === 2 && !inIFrame && !isLoading && !isInstructorWithAnonymousView">
        <AssessmentTypeWarnings :beta-assignments-exist="betaAssignmentsExist"/>
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
                  @change="presentationMode = !presentationMode"
                />
              </h5>
              <b-button variant="success" @click="startClickerAssessment">
                GO!
              </b-button>
            </div>

            <ul style="list-style-type:none" class="p-0">
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
              <li v-if="(!inIFrame && timeLeft>0)
                || (inIFrame && (showAssignmentInformation || (assessmentType === 'clicker')) && timeLeft>0)"
              >
                <span v-if="showCountdown">
                  <countdown v-show="assessmentType !== 'clicker' || user.role === 3" :time="timeLeft"
                             @end="cleanUpClickerCounter"
                  >
                    <template slot-scope="props">
                      <span v-html="getTimeLeftMessage(props, assessmentType)"/>
                    </template>
                  </countdown>
                  <span v-show="assessmentType !== 'clicker' && user.role === 3">
                    <b-button size="sm" variant="outline-info" @click="showCountdown = false">
                      Hide Time Until Due
                    </b-button>
                  </span>
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
                v-if="studentNonClicker() && assessmentType === 'real time' && numberOfAllowedAttempts === 'unlimited'"
              >
                {{ questions[currentPage - 1].submission_count }}/<span><span
                style="font-size:x-large;position: relative;bottom: -2px"
              >&infin;</span> possible attempts</span>
              </li>

              <li
                v-if="studentNonClicker() && assessmentType === 'real time'
                  && numberOfAllowedAttempts === 'unlimited'
                  && !questions[currentPage-1].solution_type
                  && questions[currentPage-1].solution_exists"
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
              </li>

              <li
                v-if="studentNonClicker() && assessmentType === 'real time' && numberOfAllowedAttempts !== 'unlimited' && scoringType === 'p'"
              >
                {{ numberOfRemainingAttempts }}
              </li>

              <li
                v-if="studentShowPointsNonClicker()
                  && assessmentType === 'real time'
                  && numberOfAllowedAttempts !== '1'
                  && numberOfAllowedAttemptsPenalty"
              >
                Maximum number of points for next attempt: {{ maximumNumberOfPointsPossible }}
                <QuestionCircleTooltip :id="'per-attempt-penalty-tooltip'"/>
                <b-tooltip target="per-attempt-penalty-tooltip" delay="250"
                           triggers="hover focus"
                >
                  A per attempt penalty of {{ numberOfAllowedAttemptsPenalty }}% is applied after the first
                  attempt. With the penalty, the maximum number of points possible for the next attempt is
                  {{ maximumNumberOfPointsPossible }} points.
                </b-tooltip>
              </li>

              <li
                v-if="studentNonClicker() && (questions[currentPage-1].solution || questions[currentPage-1].solution_html)"
              >
                <SolutionFileHtml :questions="questions"
                                  :current-page="currentPage"
                                  :assignment-name="name"
                                  :use-view-solution-as-text="true"
                />
              </li>

              <li v-if="studentNonClicker() && completionScoringModeMessage">
                <span class="font-weight-bold" v-html="completionScoringModeMessage"/>
              </li>
              <li
                v-if="studentNonClicker()
                  && assessmentType === 'learning tree'
                  && parseInt(questions[currentPage-1].answered_correctly_at_least_once)!==1"
              >
                <span v-if="parseInt(questions[currentPage - 1].submission_count) <= 1" class="text-bold">
                  A penalty of
                  {{ submissionCountPercentDecrease }}% will applied for each attempt starting with the 3rd.
                </span>
                <span v-if="parseInt(questions[currentPage - 1].submission_count) > 1"
                      class="text-bold text-info"
                >
                  With the penalty, the maximum score that you can receive for this question is
                  {{
                    parseFloat(questions[currentPage - 1].points) * (100 - parseFloat(submissionCountPercentDecrease) * (parseFloat(questions[currentPage - 1].submission_count) - 1)) / 100
                  }}
                  points.</span>
              </li>
              <li
                v-if="studentNonClicker()
                  && (parseInt(questions[currentPage - 1].submission_count) === 0 || questions[currentPage - 1].late_question_submission) && latePolicy === 'deduction' && timeLeft === 0"
              >
                <b-alert variant="warning" show>
                  <span class="alert-link">
                    This submission will be marked late.</span>
                </b-alert>
              </li>
              <li v-if="instructorInNonBasicView()">
                <b-form-row>
                  This question is worth <span v-show="!showUpdatePointsPerQuestion" class="pl-1 pr-1"
                > {{ questions[currentPage - 1].points }} </span>
                  <b-form-input
                    v-if="showUpdatePointsPerQuestion"
                    id="points"
                    v-model="questionPointsForm.points"
                    size="sm"
                    type="text"
                    placeholder=""
                    style="width:40px"
                    :class="{ 'is-invalid': questionPointsForm.errors.has('points') }"
                    class="ml-2 mr-2"
                    @keydown="questionPointsForm.errors.clear('points')"
                  />
                  <has-error v-if="showUpdatePointsPerQuestion" :form="questionPointsForm" field="points"/>
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
                    @keydown="questionWeightForm.errors.clear('weight')"
                  />
                  <b-col>
                    <div class="float-left">
                      <b-button variant="primary"
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
                  <span class="pr-1 font-weight-bold" v-html="completionScoringModeMessage"/>
                  <a href="" @click.prevent="openUpdateCompletionScoringModeModal()">
                    <b-icon v-if="completionScoringModeMessage"
                            icon="pencil"
                            class="text-muted"
                            aria-label="Edit completion scoring mode"
                    />
                  </a>
                </b-row>
              </li>
              <li v-if="instructorInNonBasicView()">
                <b-button
                  variant="info"
                  size="sm"
                  @click="openModalShare()"
                >
                  <b-icon icon="share"/>
                  Share
                </b-button>
                <b-button
                  variant="info"
                  size="sm"
                  @click="openModalProperties()"
                >
                  Properties
                </b-button>
                <RefreshQuestion :assignment-id="parseInt(assignmentId)"
                                 :question-id="questions[currentPage - 1].id"
                                 :reload-question-parent="reloadQuestionParent"
                />

                <b-button v-if="questionView !== 'basic'"
                          class="mt-1 mb-2"
                          variant="primary"
                          size="sm"
                          @click="editQuestionSource(currentPage)"
                >
                  Edit Question Source
                </b-button>
                <b-button v-if="questionView !== 'basic'
                && assessmentType === 'learning tree'"
                          class="mt-1 mb-2"
                          variant="success"
                          size="sm"
                          @click="editLearningTree(questions[currentPage-1].learning_tree_id)"
                >
                  Edit Learning Tree
                </b-button>
                <b-button class="mt-1 mb-2"
                          variant="danger"
                          :disabled="hasAtLeastOneSubmission && !showUpdatePointsPerQuestion"
                          size="sm"
                          @click="openRemoveQuestionModal()"
                >
                  Remove Question
                </b-button>
                <span v-if="openEndedSubmissionTypeAllowed" class="p-2">
                  Open-Ended Submission Type:
                  <b-form-select v-model="openEndedSubmissionType"
                                 :options="compiledPDF ? openEndedSubmissionCompiledPDFTypeOptions : openEndedSubmissionTypeOptions"
                                 style="width:100px"
                                 class="mt-1"
                                 size="sm"
                                 @change="updateOpenEndedSubmissionType(questions[currentPage-1].id)"
                  />
                </span>

                <span v-if="!questions[currentPage-1].solution">
                  <b-button
                    class="mt-1 mb-2 ml-1"
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
                    Optionally, you can provide your own solution.  If this question has a Libretext solution
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
                    <SolutionFileHtml :key="savedText" :questions="questions" :current-page="currentPage"
                                      :assignment-name="name"
                    />

                    <span v-if="showUploadedAudioSolutionMessage"
                          :class="uploadedAudioSolutionDataType"
                    >
                      {{ uploadedAudioSolutionDataMessage }}</span>
                  </span>
                  <span v-if="!questions[currentPage-1].solution && !questions[currentPage-1].solution_html">No solutions are available.</span>
                </span>
              </li>
              <li v-if="assessmentType === 'learning tree'">
                <span v-if="parseInt(questions[currentPage - 1].submission_count) > 0">
                  Attempt {{ questions[currentPage - 1].submission_count }} was submitted {{
                    questions[currentPage - 1].last_submitted
                  }}
                </span>
                <span v-if="parseFloat(questions[currentPage - 1].late_penalty_percent) > 0 && showScores">
                  <span class="font-weight-bold">You had a late penalty of </span> {{
                    questions[currentPage - 1].late_penalty_percent
                  }}%
                </span>
              </li>
              <li v-if="user.role === 3 && showScores && isOpenEnded && !isAnonymousUser">
                You achieved a total score of
                {{ questions[currentPage - 1].total_score * 1 }}
                out of a possible
                {{ questions[currentPage - 1].points * 1 }} points.
              </li>
              <li v-if="showScores && showAssignmentStatistics && !isInstructor() && scores.length">
                <b-button variant="outline-primary" @click="openShowAssignmentStatisticsModal()">
                  View Question
                  Statistics
                </b-button>
              </li>
            </ul>
          </b-container>
        </div>
        <div v-if="isInstructorWithAnonymousView && questions.length && !isLoading" class="pb-3">
          <b-card
            header-html="<span class='font-weight-bold'>Save Questions From the Commons</span>"
          >
            <b-card-text>
              You can save questions from this Commons Course to your own account and then import them to any of your
              assignments.
            </b-card-text>
          </b-card>
        </div>
        <b-container>
          <hr>
          <div class="overflow-auto">
            <b-pagination
              v-if="!inIFrame && (assessmentType !== 'clicker' || (isInstructor() && !presentationMode) || pastDue)"
              v-model="currentPage"
              :total-rows="questions.length"
              :per-page="perPage"
              limit="22"
              first-number
              last-number
              @input="changePage(currentPage)"
            />
          </div>
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
                <b-spinner small type="grow"/>
                Updating view...
              </span>
            </b-form-group>
          </div>
          <span v-if="!myFavoriteQuestionIds.includes(questions[currentPage-1].id)">
            <b-button
              variant="outline-secondary"
              size="sm"
              @click="addQuestionToFavorites('single')"
            >
              Add To My Favorites
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
        </div>
        <div
          v-if="assessmentType === 'learning tree'
            && learningTreeAsList.length
            && !answeredCorrectlyOnTheFirstAttempt"
        >
          <b-container
            class="mb-2"
          >
            <b-row>
              <b-col :cols="bCardCols">
                <b-button class="mr-2"
                          variant="primary"
                          size="sm"
                          :disabled="activeNode.parent === -1 && !learningTreeBranchOptions.length"
                          @click="showRootAssessment"
                >
                  Move to Root Assessment
                </b-button>
                <b-button variant="outline-primary"
                          class="pr-2"
                          size="sm"
                          :disabled="activeNode.parent === -1 && !learningTreeBranchOptions.length"
                          @click.prevent="moveBackInTree(activeNode.parent)"
                >
                  <font-awesome-icon :icon="arrowLeftIcon"/>
                </b-button>
                <b-button variant="outline-primary"
                          size="sm"
                          :disabled="activeNode.children && (!activeNode.children.length || (activeNode.children.length && learningTreeBranchOptions.length > 1))"
                          @click.prevent="moveForwardInTree(activeNode.children)"
                >
                  <font-awesome-icon :icon="arrowRightIcon"/>
                </b-button>
              </b-col>
              <b-col>
                <b-alert :variant="submissionDataType" :show="showSubmissionMessage && submissionDataMessage.length">
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
                  <span class="font-weight-bold"> Try the Root Assessment again and you will receive
                    {{ (percentEarnedForExploringLearningTree / 100) * (questions[currentPage - 1].points) }} point<span
                      v-if="(percentEarnedForExploringLearningTree / 100) * (questions[currentPage - 1].points)>1"
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
        </div>
        <b-container v-if="assessmentType === 'learning tree' && showLearningTree">
          <iframe
            allowtransparency="true"
            frameborder="0"
            :src="learningTreeSrc"
            aria-label="learning_tree"
            style="width: 1200px;min-width: 100%;height:800px"
            :title="getIframeTitle()"
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

              <div v-if="showQuestion && !fetchingRemediation">
                <div :class="nonTechnologyClass">
                  <b-container v-if="assessmentType === 'learning tree' && learningTreeBranchOptions.length > 1">
                    <b-row v-for="learningTreeBranchOption in learningTreeBranchOptions"
                           :key="`current-node-${learningTreeBranchOption.id}`" align-h="center" class="pb-3"
                    >
                      <span class="p-2"><a href=""
                                           @click.prevent="learningTreeBranchOptions=[];explore(learningTreeBranchOption.library, learningTreeBranchOption.pageId, learningTreeBranchOption.id)"
                      >{{
                          learningTreeBranchOption.parent !== -1 ? learningTreeBranchOption.branch_description : 'Root Assessment'
                        }}</a></span>
                      <br>
                    </b-row>
                  </b-container>
                  <div v-if="learningTreeBranchOptions.length <= 1">
                    <div v-if="assessmentType === 'learning tree' && parseInt(activeId) === 0">
                      <h2 style="font-size:26px" class="page-title pl-3 pt-2">
                        Root Assessment
                      </h2>
                    </div>
                    <div v-if="questions[currentPage-1].non_technology">
                      <iframe
                        :key="`non-technology-iframe-${currentPage}-${cacheIndex}`"
                        v-resize="{ log: false }"
                        aria-label="open_ended_question_text"
                        style="height: 30px"
                        width="100%"
                        :src="questions[currentPage-1].non_technology_iframe_src"
                        frameborder="0"
                        :title="getIframeTitle()"
                      />
                    </div>

                    <div
                      v-if="questions[currentPage-1].technology_iframe.length
                      && !(user.role === 3 && clickerStatus === 'neither_view_nor_submit')"
                    >
                      <iframe
                        :key="`technology-iframe-${currentPage}-${cacheIndex}`"
                        v-resize="{ log: false }"
                        aria-label="auto_graded_submission_text"
                        width="100%"
                        :src="questions[currentPage-1].technology_iframe"
                        frameborder="0"
                        :title="getIframeTitle()"
                      />
                    </div>
                  </div>
                </div>
                <div v-if="(!inIFrame || showAttribution)
                  &&
                  (questions[currentPage-1].attribution !== null
                  || (questions[currentPage-1].auto_attribution && autoAttributionHTML))
                  && !(user.role === 3 && clickerStatus === 'neither_view_nor_submit')" class="pt-2 pb-2"
                >
                  <b-button size="sm" variant="outline-primary" @click="showAttributionModal">
                    <span>
                      Attribution
                    </span>
                  </b-button>
                </div>
                <div v-if="assessmentType === 'clicker'">
                  <b-alert :variant="submissionDataType" :show="showSubmissionMessage">
                    <span class="font-weight-bold">{{ submissionDataMessage }}</span>
                  </b-alert>
                </div>
                <div>
                  <div v-if="assessmentType === 'clicker' && user.role === 3 && piechartdata.length"
                       class="text-center"
                  >
                    <hr>
                    <h5 v-if="correctAnswer">
                      The correct answer is "{{ correctAnswer }}"
                    </h5>
                    <pie-chart :key="currentPage" :chartdata="piechartdata"
                               @pieChartLoaded="updateIsLoadingPieChart"
                    />
                  </div>
                  <div
                    v-if="['rich text', 'plain text'].includes(openEndedSubmissionType) && user.role === 2 && !inIFrame && !isInstructorWithAnonymousView"
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
                  <div v-if="isOpenEndedTextSubmission && user.role === 3 && !isAnonymousUser">
                    <div class="mt-1">
                      <ckeditor
                        ref="textSubmissionEditor"
                        :key="questions[currentPage-1].id"
                        v-model="textSubmissionForm.text_submission"
                        tabindex="0"
                        aria-label="Text submission box"
                        :config="questions[currentPage-1].open_ended_text_editor === 'rich' ? richEditorConfig: plainEditorConfig"
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
                      ADAPT.
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
              <div v-if="!showQuestion" class="mt-3 border border-gray-200">
                <ViewQuestionWithoutModal
                  :key="`remediation-to-view-${remediationToViewKey}`"
                  :question-to-view="remediationToView"
                />
              </div>
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

                  <div v-if="!isLoadingPieChart">
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
                      <countdown v-show="assessmentType === 'clicker'" :time="timeLeft" @end="cleanUpClickerCounter">
                        <template slot-scope="props">
                          <span v-html="getTimeLeftMessage(props, assessmentType)"/>
                        </template>
                      </countdown>
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
              v-if="assessmentType !== 'clicker' && showAssignmentStatistics && loaded && user.role === 2 && !inIFrame && !isInstructorWithAnonymousView "
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
              v-if="(user.role === 3)
                && (assessmentType !== 'clicker')
                && (showSubmissionInformation || openEndedSubmissionType === 'file')"
              :cols="bCardCols"
            >
              <b-row v-if="questions[currentPage-1].technology_iframe
                && showSubmissionInformation && showQuestion"
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
                      <li v-if="assessmentType==='learning tree'">
                        <span class="font-weight-bold">Number of attempts: </span>
                        {{
                          questions[currentPage - 1].submission_count
                        }}
                      </li>
                      <li>
                        <span class="font-weight-bold">Submission:</span>
                        <span
                          :class="{ 'text-danger': questions[currentPage - 1].last_submitted === 'N/A' }"
                        >{{
                            questions[currentPage - 1].student_response
                          }}</span>
                      </li>
                      <li>
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
                      </li>
                      <li v-if="showScores">
                        <span class="font-weight-bold">Score:</span> {{
                          questions[currentPage - 1].submission_score
                        }}
                      </li>
                      <li v-if="showScores">
                        <strong>Z-Score:</strong> {{ questions[currentPage - 1].submission_z_score }}<br>
                      </li>
                      <li v-if="parseFloat(questions[currentPage - 1].late_penalty_percent) > 0 && showScores">
                        <span class="font-weight-bold">Late Penalty:</span> {{
                          questions[currentPage - 1].late_penalty_percent
                        }}%
                      </li>
                    </ul>
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
                          No files have been uploaded.</span>
                      </li>
                      <li>
                        <strong>Submitted At:</strong>
                        <span
                          :class="{ 'text-danger': questions[currentPage - 1].date_submitted === 'N/A' }"
                        >{{ questions[currentPage - 1].date_submitted }}</span>
                        <font-awesome-icon v-show="questions[currentPage - 1].date_submitted !== 'N/A'"
                                           class="text-success"
                                           :icon="checkIcon"
                        />
                      </li>
                      <li v-if="showScores">
                        <strong>Date Graded:</strong> {{ questions[currentPage - 1].date_graded }}
                      </li>

                      <li v-if="showScores && questions[currentPage-1].file_feedback">
                        <strong>{{ capitalize(questions[currentPage - 1].file_feedback_type) }} Feedback:</strong>
                        <a :href="questions[currentPage-1].file_feedback_url"
                           target="”_blank”"
                        >
                          {{
                            questions[currentPage - 1].file_feedback_type === 'audio' ? 'Listen To Feedback' : 'View Feedback'
                          }}
                        </a>
                      </li>
                      <li v-if="showScores">
                        <strong>Comments:</strong>
                        <span v-if="questions[currentPage - 1].text_feedback"
                              v-html="questions[currentPage - 1].text_feedback"
                        />
                        <span v-if="!questions[currentPage - 1].text_feedback">None Provided.</span>
                      </li>
                      <li v-if="showScores">
                        <strong>Score:</strong> {{ questions[currentPage - 1].submission_file_score }}
                      </li>
                      <li v-if="showScores">
                        <strong>Z-Score:</strong> {{ questions[currentPage - 1].submission_file_z_score }}
                      </li>
                    </ul>
                    <div v-if="isOpenEndedFileSubmission">
                      <hr>
                      <b-container>
                        <b-row v-show="!compiledPDF" class="mt-2 mr-2" align-h="end">
                          <span v-if="questions[currentPage - 1].grader_id" class="pr-2">
                            <b-button size="sm" variant="outline-primary"
                                      @click="openContactGraderModal( questions[currentPage - 1].grader_id)"
                            >Contact Grader</b-button>
                          </span>
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
                    <b-alert :variant="openEndedSubmissionDataType" :show="showOpenEndedSubmissionMessage">
                      <span class="font-weight-bold">{{ openEndedSubmissionDataMessage }}</span>
                    </b-alert>
                  </b-card-text>
                </b-card>
              </b-row>
            </b-col>
          </b-row>
          <b-row>
            <div
              v-if="isInstructor() && !isInstructorWithAnonymousView && !presentationMode && questionView !== 'basic' && !inIFrame"
              class="mt-1 libretexts-font" style="width:100%"
            >
              <div v-if="questions[currentPage - 1].text_question"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].text_question"/>
              </div>
              <div v-show="questions[currentPage - 1].a11y_technology_id" class="mt-3 libretexts-border">
                <h2 class="editable">
                  A11y Question
                </h2>
                <iframe
                  :key="`a11y-technology-iframe-${currentPage}-${cacheIndex}`"
                  v-resize="{ log: false }"
                  aria-label="a11y_auto_graded_text"
                  width="100%"
                  :src="questions[currentPage-1].a11y_technology_iframe"
                  frameborder="0"
                  :title="getIframeTitle()"
                />
              </div>
              <div v-if="questions[currentPage-1].answer_html"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].answer_html"/>
              </div>
              <div v-if="questions[currentPage-1].solution_html"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].solution_html"/>
              </div>
              <div v-if="questions[currentPage-1].hint"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].hint"/>
              </div>
              <div v-if="questions[currentPage-1].libretexts_link"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].libretexts_link"/>
              </div>
              <div v-if="questions[currentPage-1].notes"
                   class="mt-3 libretexts-border"
              >
                <div class="mt-3" v-html="questions[currentPage - 1].notes"/>
              </div>
            </div>
          </b-row>
        </b-container>
      </div>
    </div>
    <div v-if="!initializing && !questions.length" class="mt-4">
      <div v-if="isInstructor()" class="mb-0" @click="getAssessmentsForAssignment()">
        <b-button variant="primary" size="sm">
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
      <b-alert :show="true" variant="warning" class="mt-3">
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
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { faTree, faThumbsUp, faCheck, faArrowLeft, faArrowRight, faInfinity } from '@fortawesome/free-solid-svg-icons'
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
import { licenseOptions, defaultLicenseVersionOptions } from '~/helpers/Licenses'

import ViewQuestionWithoutModal from '~/components/ViewQuestionWithoutModal'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { makeFileUploaderAccessible } from '~/helpers/accessibility/makeFileUploaderAccessible'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'

Vue.prototype.$http = axios // needed for the audio player

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)

export default {
  middleware: 'auth',
  components: {
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
    ViewQuestionWithoutModal,
    SavedQuestionsFolders
  },
  data: () => ({
    fetchingRemediation: false,
    learningTreeBranchOptions: [],
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
    currentNodes: [],
    remediationToViewKey: 0,
    remediationToView: {},
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
    infinityIcon: faInfinity,
    arrowLeftIcon: faArrowLeft,
    arrowRightIcon: faArrowRight,
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
    correctAnswer: null,
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
    plainEditorConfig: {
      toolbar: [],
      removePlugins: 'elementspath',
      resize_enabled: false,
      height: 200
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
    licenseOptions: licenseOptions,
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
    showLearningTreePointsMessage: false,
    remediationIframeId: '',
    iframeLoaded: false,
    showedInvalidTechnologyMessage: false,
    loadedBranchDescriptions: false,
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
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    nonTechnologyClass: function () {
      return {
        border: !this.isOpenEndedTextSubmission,
        'border-gray': !this.isOpenEndedTextSubmission,
        'p-0': !this.isOpenEndedTextSubmission
      }
    }
  },
  watch: {
    openEndedSubmissionType: function (newVal, oldVal) {
      this.originalOpenEndedSubmissionType = oldVal
    }
  },
  async created () {
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
    this.updateCompletionSplitOpenEndedSubmissionPercentage = updateCompletionSplitOpenEndedSubmissionPercentage
    window.addEventListener('keydown', this.arrowListener)
  },
  destroyed () {
    window.removeEventListener('keydown', this.arrowListener)
  },
  async mounted () {
    window.addEventListener('resize', this.resizeHandler)
    this.isAnonymousUser = this.user.email === 'anonymous'
    this.isLoading = true

    this.uploadFileType = (this.user.role === 2) ? 'solution' : 'submission' // students upload question submissions and instructors upload solutions
    this.uploadFileUrl = (this.user.role === 2) ? '/api/solution-files' : '/api/submission-files'

    this.assignmentId = this.$route.params.assignmentId
    await this.redirectIfBetaCourse()
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
      if (this.isAnonymousUser || this.isInstructorWithAnonymousView) {
        this.showSubmissionInformation = false
        this.showAssignmentInformation = false
      }
      this.setQuestionCol()
      this.resizeHandler()

      if (this.user.role === 2) {
        await this.getCutups(this.assignmentId)
      }
      this.licenseVersionOptions = this.defaultLicenseVersionOptions
      window.addEventListener('message', this.receiveMessage, false)
    }
  },
  beforeDestroy () {
    window.removeEventListener('message', this.receiveMessage)
    window.removeEventListener('resize', this.resizeHandler)
    if (this.clickerPollingSetInterval) {
      clearInterval(this.clickerPollingSetInterval)
      this.clickerPollingSetInterval = null
    }
  },
  methods: {
    editLearningTree (learningTreeId) {
      window.open(`/instructors/learning-trees/editor/${learningTreeId}`, '_blank')
    },
    instructorInNonBasicView () {
      return this.isInstructor() && !this.isInstructorWithAnonymousView && !this.presentationMode && this.questionView !== 'basic' && !this.inIFrame
    },
    studentShowPointsNonClicker () {
      return this.source === 'a' && !this.inIFrame && !this.isAnonymousUser && !this.isInstructorWithAnonymousView && !this.isInstructor() && this.showPointsPerQuestion && this.assessmentType !== 'clicker'
    },
    studentNonClicker () {
      return this.source === 'a' && !this.inIFrame && !this.isAnonymousUser && !this.isInstructorWithAnonymousView && !this.isInstructor() && this.assessmentType !== 'clicker'
    },
    getIframeTitle () {
      return `${this.title} - Question #${this.currentPage}`
    },
    getMaximumNumberOfPointsPossible () {
      return +Math.max(0, ((1 * this.questions[this.currentPage - 1].points) * (1 - parseFloat(this.questions[this.currentPage - 1].submission_count) * parseFloat(this.numberOfAllowedAttemptsPenalty) / 100))).toFixed(4)
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
      return `${this.questions[this.currentPage - 1].submission_count}/${this.numberOfAllowedAttempts} possible attempts`
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
          alert(data.message)
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
    },
    async getMyFavoriteQuestions () {
      try {
        const { data } = await axios.get(`/api/my-favorites/commons/${this.assignmentId}`)
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
        console.log(this.myFavoriteQuestionIds)
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
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async removeMyFavoritesQuestion () {
      let questionId = this.questions[this.currentPage - 1].id
      console.log(this.myFavoriteQuestions)
      let folderId = this.myFavoriteQuestions.find(myFavoriteQuestion => myFavoriteQuestion.my_favorites_question_id === questionId).my_favorites_folder_id
      console.log(folderId)
      try {
        const { data } = await axios.delete(`/api/my-favorites/folder/${folderId}/question/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.myFavoriteQuestions = this.myFavoriteQuestions.filter(myFavoriteQuestion => myFavoriteQuestion.my_favorites_question_id !== questionId)
        console.log(this.myFavoriteQuestions)
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
    getThumbsUpStyle () {
      return this.inIFrame ? { marginLeft: '50px' } : ''
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
    async reloadQuestionParent (questionId, message) {
      try {
        const { data } = await axios.get(`/api/questions/${questionId}`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.questions[this.currentPage - 1].non_technology_iframe_src = data.question.non_technology_iframe_src
        this.questions[this.currentPage - 1].technology_iframe = data.question.technology_iframe_src
        this.questions[this.currentPage - 1].title = data.question.title
        this.questions[this.currentPage - 1].text_question = data.question.text_question
        this.questions[this.currentPage - 1].a11y_question = data.question.a11y_question
        this.questions[this.currentPage - 1].solution_html = data.question.solution_html
        this.questions[this.currentPage - 1].answer_html = data.question.answer_html
        this.questions[this.currentPage - 1].hint = data.question.hint
        this.questions[this.currentPage - 1].libretexts_link = data.question.libretexts_link
        this.questions[this.currentPage - 1].notes = data.question.notes

        this.$bvModal.hide('modal-question-has-submissions-in-this-assignment')
        await this.changePage(this.currentPage)
        this.cacheIndex++
        this.$noty.success(message)
        console.log(this.cacheIndex)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getCurrentAttributeValue () {
      return this.questions[this.currentPage - 1].attribution.replace('<p>', '<p class=" &quot;mb-0&quot;"><strong>Attribution:</strong> ')
    },
    updateAutoAttribution (license, licenseVersion, author) {
      if (licenseVersion === null) {
        licenseVersion = ''
      }
      let byAuthor = author
        ? `by ${author}`
        : ''
      if (!license) {
        this.autoAttributionHTML = ''
        return
      }
      let chosenLicenseText = this.licenseOptions.find(item => item.value === license).text
      let url = this.licenseOptions.find(item => item.value === license).url

      if (['ccby', 'ccbynd', 'ccbyncnd', 'ccbync', 'ccbyncsa', 'ccbysa'].includes(license)) {
        url += '/' + licenseVersion
      }
      if (['gnu', 'gnufdl'].includes(license)) {
        url += licenseVersion + '.html'
      }
      this.autoAttributionHTML =
        `This assessment ${byAuthor} is licensed under <a href="${url}" target="_blank">${chosenLicenseText} ${licenseVersion}</a>`
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
      this.propertiesForm.attribution = this.questions[this.currentPage - 1].attribution
      this.propertiesForm.private_description = this.questions[this.currentPage - 1].private_description
      this.autoAttribution = !!this.questions[this.currentPage - 1].auto_attribution

      this.updateAutoAttribution(this.questions[this.currentPage - 1].license, this.questions[this.currentPage - 1].license_version, this.questions[this.currentPage - 1].author)
      this.$bvModal.show('modal-properties')
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
    arrowListener (event) {
      if (event.key === 'ArrowRight') {
        this.$bvModal.hide('modal-upload-file')
        this.nextQuestion()
      }
      if (event.key === 'ArrowLeft' && this.currentPage > 1) {
        this.$bvModal.hide('modal-upload-file')
        this.currentPage--
        this.changePage(this.currentPage)
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
      this.activeId = 0
      this.questionCol = 8
      this.updateNavigator(this.activeId)
      this.viewOriginalQuestion()
    },
    cleanUpClickerCounter () {
      this.timeLeft = 0
      this.updateClickerMessage('view_and_not_submit')
    },
    getTimeLeftMessage (props, assessmentType) {
      let message = ''
      message = (assessmentType === 'clicker') ? '<span class="font-weight-bold">Time Left:</span> ' : '<span class="font-weight-bold">Time Until Due:</span> '
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
        message = `${message}`
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
      let question
      question = this.questions[currentPage - 1]
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-should-not-edit-question-source-if-beta-assignment')
        return false
      }
      if (question.library === 'adapt' && question.question_editor_user_id !== this.user.id) {
        this.$noty.info('You cannot edit this question since you did not create it.')
        return false
      }
      let url
      url = this.questions[currentPage - 1].library === 'adapt'
        ? `/question-editor/my-questions/${question.id}`
        : question.mindtouch_url
      window.open(url, '_blank')
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
      let modalToShow
      if (this.openEndedSubmissionDataType !== 'success') {
        modalToShow = 'modal-thumbs-down'
      } else {
        this.cacheKey++
        modalToShow = data.completed_all_assignment_questions ? 'modal-completed-assignment' : 'modal-submission-accepted'
      }
      this.$bvModal.show(modalToShow)
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
    openModalShare () {
      this.$bvModal.show('modal-share')
      this.currentUrl = this.getCurrentUrl()
      this.embedCode = this.getEmbedCode()
      this.libraryText = this.questions[this.currentPage - 1].library
      this.adaptId = `${this.assignmentId}-${this.questions[this.currentPage - 1].id}`
      this.pageId = this.questions[this.currentPage - 1].page_id
      this.assignmentInformationShownInIFrame = this.questions[this.currentPage - 1].assignment_information_shown_in_iframe
      this.submissionInformationShownInIFrame = this.questions[this.currentPage - 1].submission_information_shown_in_iframe
      this.attributionInformationShownInIFrame = this.questions[this.currentPage - 1].attribution_information_shown_in_iframe

      this.technologySrc = this.getTechnologySrc('technology', 'technology_src')
      this.a11yTechnologySrc = this.getTechnologySrc('a11y_technology', 'a11y_technology_src')
    },
    getTechnologySrc (technology, src) {
      let technologySrc = ''
      let text
      if (this.questions[this.currentPage - 1][src]) {
        let url = new URL(this.questions[this.currentPage - 1][src])
        let urlParams = new URLSearchParams(url.search)

        switch (this.questions[this.currentPage - 1][technology]) {
          case ('webwork'):
            text = urlParams.get('sourceFilePath')
            technologySrc = `<a href="${this.questions[this.currentPage - 1][src]}" target="”_blank”" >webwork:${text}</a>`
            break
          case ('h5p'):
            text = this.questions[this.currentPage - 1][src].replace('https://studio.libretexts.org/h5p/', '').replace('/embed', '')
            technologySrc = `<a href="${this.questions[this.currentPage - 1][src]}" target="”_blank”" ><img src="https://studio.libretexts.org/sites/default/files/LibreTexts_icon.png" alt="Libretexts logo" height="22" class="pb-1 pr-1">H5P Resource ID ${text} | LibreStudio</a>`
            break
          case ('imathas'):
            console.log(urlParams)
            text = urlParams.get('id')
            technologySrc = `<a href="${this.questions[this.currentPage - 1][src]}" target="”_blank”" >imathas:${text}</a>`
            break
          default:
            technologySrc = `Please Contact Us.  We have not yet implemented the sharing code for ${this.questions[this.currentPage - 1][technology]}`
        }
        return technologySrc
      }
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
      return word.charAt(0).toUpperCase() + word.slice(1)
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
          data.completed_all_assignment_questions
            ? this.$bvModal.show('modal-completed-assignment')
            : this.$bvModal.show('modal-submission-accepted')
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
          this.timeLeft = data.time_left
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
          'solution',
          'solution_file_url',
          'solution_text',
          'solution_type',
          'answer_html',
          'solution_html'
        ]
        for (let i = 0; i < info.length; i++) {
          this.questions[this.currentPage - 1][info[i]] = data[info[i]]
        }
        this.$forceUpdate()

        if (this.assessmentType === 'real time') {
          this.numberOfRemainingAttempts = this.getNumberOfRemainingAttempts()
          this.maximumNumberOfPointsPossible = this.getMaximumNumberOfPointsPossible()
        }
        this.updateTotalScore()
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
      if (this.user.role === 3 && !this.isAnonymousUser) {
        let technology = this.getTechnology(event.origin)

        if (technology === 'imathas') {

        }
        let clientSideSubmit
        let serverSideSubmit
        let iMathASResize
        try {
          console.log(event)
          clientSideSubmit = ((technology === 'h5p') && (JSON.parse(event.data).verb.id === 'http://adlnet.gov/expapi/verbs/answered'))
        } catch (error) {
          clientSideSubmit = false
          console.log(JSON.parse(JSON.stringify(error)))
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
        let isRemediation = this.questions[this.currentPage - 1].learning_tree_id && parseInt(this.activeId) !== 0
        if (serverSideSubmit) {
          this.questions[this.currentPage - 1].can_give_up = true
          let data = JSON.parse(event.data)
          console.log(data)
          if (technology === 'webwork' && data.status) {
            data.type = data.status < 300 ? 'success' : 'error'
            try {
              let message = JSON.parse(data.message)
              data = { ...data, ...message }
            } catch (error) {
              console.log(error)
            }
          }
          if (!isRemediation) {
            await this.showResponse(data)
          }
        }
        if (clientSideSubmit) {
          let submissionData = {
            'is_remediation': isRemediation,
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
            if (!isRemediation) {
              await this.showResponse(data)
            }
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
      this.cacheKey++
      if (data.learning_tree && !this.learningTree) {
        await this.getLearningTree(data.learning_tree)
        this.questions[this.currentPage - 1].learning_tree = data.learning_tree
      }
      this.submissionDataType = ['success', 'info'].includes(data.type) ? data.type : 'danger'

      this.submissionDataMessage = data.message
      this.showSubmissionMessage = true
      this.learningTreePercentPenalty = data.learning_tree_percent_penalty
      if (this.submissionDataType !== 'danger') {
        if (this.assessmentType === 'learning tree' && data.learning_tree_message) {
          this.$bvModal.show('modal-learning-tree')
        } else if (data.not_updated_message) {
          this.$bvModal.show('modal-not-updated')
        } else {
          data.completed_all_assignment_questions
            ? this.$bvModal.show('modal-completed-assignment')
            : this.$bvModal.show('modal-submission-accepted')
        }
        await this.updateLastSubmittedAndLastResponse(this.assignmentId, this.questions[this.currentPage - 1].id)
      } else {
        this.$bvModal.show('modal-thumbs-down')
      }
    },
    getTechnology (body) {
      let technology
      if (body.includes('h5p.libretexts.org') || body.includes('studio.libretexts.org')) {
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
      this.$refs.upload.active = false
      this.files = []
      this.processingFile = false
      this.$bvModal.hide(`modal-upload-file`)
    },
    viewOriginalQuestion () {
      this.$nextTick(() => {
        this.showQuestion = true
        this.learningTreeBranchOptions = []
        this.showIframe(this.questions[this.currentPage - 1].iframe_id)
      })
    },
    showIframe () {
      this.iframeLoaded = true
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
    getTitle (currentPage) {
      return `${this.questions[currentPage - 1].title}` ? this.questions[currentPage - 1].title : `Question #${currentPage - 1}`
    },
    async changePage (currentPage) {
      if (!this.questions[currentPage - 1]) {
        console.log('No question exists')
        this.isLoading = false
        return false
      }
      if (this.user.role === 2) {
        this.title = this.getTitle(currentPage)
      }
      this.showSolutionTextForm = false
      this.showAddTextToSupportTheAudioFile = false
      if (this.assessmentType === 'real time') {
        this.numberOfRemainingAttempts = this.getNumberOfRemainingAttempts()
        this.maximumNumberOfPointsPossible = this.getMaximumNumberOfPointsPossible()
      }
      if (this.assessmentType === 'clicker') {
        this.clickerStatus = this.questions[currentPage - 1].clicker_status
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
      this.openEndedSubmissionType = this.questions[currentPage - 1].open_ended_submission_type

      this.isOpenEndedAudioSubmission = (this.openEndedSubmissionType === 'audio')
      this.showAudioUploadComponent = this.isOpenEndedAudioSubmission
      this.isOpenEndedFileSubmission = (this.openEndedSubmissionType === 'file')

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
      this.updateAutoAttribution(this.questions[this.currentPage - 1].license, this.questions[this.currentPage - 1].license_version, this.questions[this.currentPage - 1].author)
      this.isLoading = false
      await this.setQuestionUpdatedAtSession(this.questions[this.currentPage - 1].loaded_question_updated_at)
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
      let librariesAndPageIds = []
      console.log(this.learningTree)
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
          librariesAndPageIds.push({
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
        console.log(this.learningTreeAsList)
        for (let i = 0; i < this.learningTreeAsList.length; i++) {
          this.learningTreeAsList[i]['children'] = []

          for (let j = 0; j < this.learningTreeAsList.length; j++) {
            if (i !== j && (this.learningTreeAsList[j]['parent'] === this.learningTreeAsList[i]['id'])) {
              this.learningTreeAsList[i]['children'].push(this.learningTreeAsList[j]['id'])
            }
          }
        }
      }
      const { data } = await axios.post('/api/branches/descriptions', {
        'assignment_id': this.assignmentId,
        'learning_tree_id': this.questions[this.currentPage - 1].learning_tree_id,
        'libraries_and_page_ids': librariesAndPageIds
      })

      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        this.learningTreeAsList[i].branch_description = data.branch_descriptions[i]
      }
      this.updateNavigator(0)
      console.log('navigator updated')
      this.loadedBranchDescriptions = true
    },
    updateNavigator (activeId) {
      this.showSubmissionMessage = activeId === 0
      console.log(this.learningTreeAsList)
      this.activeNode = this.learningTreeAsList.find(learningTree => learningTree.id === activeId)
      console.log('active node')
      console.log(this.activeNode)
      this.previousNode = parseInt(this.activeNode.parent) === -1 ? {} : this.learningTreeAsList[this.activeNode.parent]
      let currentNodes = []
      for (let i = 0; i < this.activeNode.children.length; i++) {
        for (let j = 0; j < this.learningTreeAsList.length; j++) {
          let possibleChild = this.learningTreeAsList[j]
          if (this.learningTreeAsList[j].id === this.activeNode.children[i]) {
            console.log('child' + i)
            console.log(possibleChild)
            currentNodes.push(possibleChild)
          }
        }
      }
      this.currentNodes = currentNodes
      console.log(this.currentNodes)
    },
    moveBackInTree (parentId) {
      this.submissionDataMessage = ''
      this.learningTreeBranchOptions = []
      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        let node = this.learningTreeAsList[i]
        if (parentId === node.id) {
          this.explore(node.library, node.pageId, node.id)
          return
        }
      }
    },
    moveForwardInTree (childrenIds) {
      console.log(childrenIds)
      this.submissionDataMessage = ''
      if (!childrenIds.length) {
        console.log('should not be able to move forward')
        return false
      }
      this.learningTreeBranchOptions = []
      if (childrenIds.length > 1) {
        for (let i = 0; i < this.learningTreeAsList.length; i++) {
          if (childrenIds.includes(this.learningTreeAsList[i].id)) {
            this.learningTreeBranchOptions.push(this.learningTreeAsList[i])
          }
        }
      } else {
        let childId = childrenIds[0]
        for (let i = 0; i < this.learningTreeAsList.length; i++) {
          let node = this.learningTreeAsList[i]
          if (childId === node.id) {
            this.explore(node.library, node.pageId, node.id)
            return
          }
        }
      }
      console.log(this.learningTreeBranchOptions)
    },
    async getRemediationToView (library, pageId, activeId) {
      this.fetchingRemediation = true
      this.activeId = activeId
      try {
        const { data } = await axios.get(`/api/questions/remediation/${this.assignmentId}/${this.questions[this.currentPage - 1].id}/${this.questions[this.currentPage - 1].learning_tree_id}/${activeId}/${library}/${pageId}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.remediationToView = data.remediation
        this.remediationToViewKey = data.remediation.id
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.fetchingRemediation = false
    },
    async explore (library, pageId, activeId) {
      this.updateNavigator(activeId)
      await this.getRemediationToView(library, pageId, activeId)
      this.showSubmissionMessage = false
      this.showQuestion = (activeId === 0)
      if (!this.showQuestion) {
        this.showQuestion = false
      }
      this.activeId = activeId
      this.questionCol = this.activeId === 0 ? 8 : 12
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
            this.isLMS = data.is_lms
            this.$bvModal.show('modal-enroll-in-course')
            this.modalEnrollInCourseIsShown = true
          } else {
            this.$noty.error(data.message)
          }
          return false
        }
        let assignment = data.assignment
        this.betaAssignmentsExist = assignment.beta_assignments_exist
        this.isBetaAssignment = assignment.is_beta_assignment
        this.scoringType = assignment.scoring_type

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
        if (this.isInstructorWithAnonymousView) {
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
      console.log(this.questions)
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
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-add-assessment-to-beta-assignment')
      } else {
        this.assessmentType === 'learning tree'
          ? this.$router.push(`/assignments/${this.assignmentId}/learning-trees/get`)
          : this.$router.push(`/assignments/${this.assignmentId}/questions/get`)
      }
    },
    openRemoveQuestionModal () {
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-delete-assessment-from-beta-assignment')
        return false
      }
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
        this.$bvModal.hide('modal-remove-question')
        this.questions.splice(this.currentPage - 1, 1)
        if (this.currentPage !== 1) {
          this.currentPage = this.currentPage - 1
        }
        if (data.updated_points) {
          this.updatePointsBasedOnNewWeights(data)
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    }
  },
  metaInfo () {
    return { title: 'Assignment Questions' }
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
