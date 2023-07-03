<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="`modal-form-errors-questions-form-${questionsFormKey}`" />
    <b-modal id="modal-compare-revisions"
             title="Compare Revisions"
             size="xl"
             scrollable
             hide-footer
             no-close-on-backdrop
             @shown="increaseModalSize"
    >
      <b-form-group>
        <b-form-row>
          <b-form-select v-model="revision1Id"
                         style="width:500px"
                         size="sm"
                         :options="revisionOptions"
                         class="mt-2 mr-2"
                         @change="compareRevisions"
          />
          <b-form-select v-model="revision2Id"
                         style="width:500px"
                         size="sm"
                         :options="revisionOptions"
                         class="mt-2 mr-2"
                         @change="compareRevisions"
          />
        </b-form-row>
      </b-form-group>
      <QuestionRevisionDifferences :key="`question-revision-differences-${questionRevisionDifferencesKey}`"
                                   :revision1="revision1"
                                   :revision2="revision2"
                                   :diffs-shown="diffsShown"
                                   :math-jax-rendered="mathJaxRendered"
                                   @reloadQuestionRevisionDifferences="reloadQuestionRevisionDifferences"
      />
    </b-modal>
    <b-modal id="modal-save-and-propagate"
             title="Save and Propagate"
             size="lg"
    >
      <b-form-group label="Reason for Edit (Optional)">
        <b-textarea v-model="questionForm.reason_for_edit"
                    style="width:100%"
                    rows="5"
        />
      </b-form-group>
      <b-form-checkbox
        id="checkbox-1"
        v-model="questionForm.changes_are_topical"
        name="changes_made_are_topical"
        :value="true"
        :unchecked-value="false"
      >
        The changes I made are topical in nature.
      </b-form-checkbox>

      <template #modal-footer>
        <b-button
          variant="secondary"
          size="sm"
          class="Cancel"
          @click="$bvModal.hide('modal-save-and-propagate')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitSaveAndPropagate"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-reason-for-edit"
             title="Reason for Edit"
             size="lg"
             no-close-on-backdrop
    >
      <b-form-group
        v-if="powerUser"
        id="revision_action"
        label-cols-sm="3"
        label-cols-lg="2"
        label="Revision Action"
      >
        <b-form-radio-group
          v-model="revisionAction"
          class="mt-2"
        >
          <b-form-radio name="revision_action" value="notify">
            Notify
          </b-form-radio>

          <b-form-radio name="revision_action" value="propagate">
            Propagate
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <div v-if="revisionAction === 'notify' || (revisionAction === 'propagate' && !powerUser)">
        <p>
          Reason for editing the question:
        </p>
        <b-textarea v-model="questionForm.reason_for_edit"
                    style="width:100%"
                    rows="5"
                    :class="{ 'is-invalid': questionForm.errors.has('reason_for_edit')}"
                    @keydown="questionForm.errors.clear('reason_for_edit')"
        />
        <has-error :form="questionForm" field="reason_for_edit" />

        <hr class="pt-2 pb-2">
      </div>
      <div v-if="revisionAction === 'notify'">
        <p>
          Instructors will optionally be able to update their question with the new version, but this will remove
          any student submissions that might currently exist.
        </p>

        <b-form-group
          id="automatically_update_revision"
          label-cols-sm="5"
          label-cols-lg="4"
          label="For my own current assignments:"
        >
          <b-form-row>
            <b-form-radio-group
              v-model="questionForm.automatically_update_revision"
              stacked
            >
              <b-form-radio name="automatically_update_revision" value="1">
                Automatically update the question and reset any student submissions
              </b-form-radio>

              <b-form-radio name="automatically_update_revision" value="0">
                Do not automatically update the question
              </b-form-radio>
            </b-form-radio-group>
          </b-form-row>
          <ErrorMessage :message="questionForm.errors.get('automatically_update_revision')" />
        </b-form-group>
      </div>
      <div v-if="revisionAction === 'propagate'">
        <div v-if="!powerUser">
          <b-alert show variant="info">
            Since your edits were purely topical in nature, all instructors will automatically receive your updated
            question.
          </b-alert>
        </div>
        <div v-if="powerUser">
          <b-form-checkbox
            id="checkbox-1"
            v-model="questionForm.changes_are_topical"
            name="changes_made_are_topical"
            :value="true"
            :unchecked-value="false"
          >
            The changes I made are topical in nature.
          </b-form-checkbox>
          <ErrorMessage :message="questionForm.errors.get('changes_are_topical')" />
        </div>
      </div>
      <template #modal-footer>
        <b-button
          variant="secondary"
          size="sm"
          class="Cancel"
          @click="$bvModal.hide('modal-reason-for-edit')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          :disabled="!revisionAction"
          @click="saveQuestion()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-confirm-delete-webwork-attachment"
      :title="`Delete ${webworkAttachmentToDelete.filename}`"
    >
      Please confirm whether you would like to delete {{ webworkAttachmentToDelete.filename }}. If you delete this file,
      be sure to update your WeBWork code as well.
      <template #modal-footer>
        <b-button
          variant="secondary"
          size="sm"
          class="Cancel"
          @click="$bvModal.hide('modal-confirm-delete-webwork-attachment')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="deleteWebworkAttachment()"
        >
          Delete Attachment
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-webwork-image-options"
      no-close-on-backdrop
      size="lg"
      :title="`Create webWork code for ${webworkImageOptions.filename}`"
    >
      <p>
        ADAPT can automatically create the necessary WeBWork to create <a
          href="https://webwork.maa.org/wiki/StaticImages" target="_blank"
        >static images</a>. All parameters are optional.
      </p>
      <b-card header-html="<h5>Resize Image</h5>" class="mb-2">
        <template #header>
          <div>
            <h5 class="mb-0">
              Resize Image
            </h5>
          </div>
          <div>
            {{
              webworkImageOptions.width
            }}px by {{ webworkImageOptions.height }}px
            <b-button size="sm" variant="info"
                      @click="resetImageResize"
            >
              Reset
            </b-button>
          </div>
        </template>

        <b-form-radio-group
          id="resize-by"
          v-model="resizeImageBy"
          label="By"
          @input="initImageSize(resizeImageBy)"
        >
          <b-form-radio name="resize_image_by" value="percentage">
            Percentage
          </b-form-radio>
          <b-form-radio name="resize_image_by" value="pixels">
            Pixels
          </b-form-radio>
        </b-form-radio-group>
        <b-form-checkbox
          id="aspect-ratio"
          v-model="maintainAspectRatio"
          name="aspect-ratio"
          :value="true"
          :unchecked-value="false"
        >
          Maintain Aspect Ratio
        </b-form-checkbox>
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="1"
          label-for="width"
          label="Width"
        >
          <b-form-row>
            <b-form-input
              id="width"
              v-model="resizeWidth"
              style="width:100px"
              type="text"
              @focus="initResizeWidth = resizeWidth"
              @blur="updateResizeHeight"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="1"
          label-for="height"
          label="Height"
        >
          <b-form-row>
            <b-form-input
              id="height"
              v-model="resizeHeight"
              style="width:100px"
              type="text"
              @focus="initResizeHeight = resizeHeight"
              @blur="updateResizeWidth"
            />
          </b-form-row>
        </b-form-group>
      </b-card>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="tex_size"
        label="Tex size"
      >
        <b-form-row>
          <b-form-input
            id="tex_size"
            v-model="webworkImageOptions.tex_size"
            style="width:200px"
            type="text"
          />
        </b-form-row>
      </b-form-group>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="extra_html_tags"
        label="Extra HTML tags"
      >
        <b-form-row>
          <b-form-input
            id="extra_html_tags"
            v-model="webworkImageOptions.extra_html_tags"
            style="width:200px"
            type="text"
          />
        </b-form-row>
      </b-form-group>
      <template #modal-footer>
        <b-button
          variant="secondary"
          size="sm"
          class="Cancel"
          @click="$bvModal.hide('modal-webwork-image-options')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="copyWebworkImageCode()"
        >
          Create Code
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-auto-graded-redirect"
      no-close-on-backdrop
      :title="`Create ${getTextFromTechnology(questionForm.technology)} question`"
      @hidden="newAutoGradedTechnology=null;$bvModal.hide('modal-auto-graded-redirect')"
    >
      <p>
        In order to create an {{ getTextFromTechnology(questionForm.technology) }} question, we will need to re-direct
        you to
        {{ getTextFromTechnology(questionForm.technology) }}'s question editor. Please note that you must have
        access to the editor.
      </p>
      <p>
        Once the question is created, choose {{ getTextFromTechnology(questionForm.technology) }} from the "Existing"
        auto-graded technology options to import it back into ADAPT.
      </p>
      <template #modal-footer>
        <b-button
          variant="secondary"
          size="sm"
          class="Cancel"
          @click="newAutoGradedTechnology=null;$bvModal.hide('modal-auto-graded-redirect')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleAutoGradedRedirect()"
        >
          Proceed
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-clone-history"
      size="lg"
      no-close-on-backdrop
    >
      <template #modal-header>
        <div class="modal-header" style="width:100%;border:none;padding:0px">
          <h2 class="h5 modal-title">
            ADAPT ID <span id="clone-history-question-id">{{ copyHistoryQuestionId }}</span>
            <span class="text-muted" @click="doCopy('clone-history-question-id')"><font-awesome-icon :icon="copyIcon" /></span>
          </h2>
          <button type="button" aria-label="Close" class="close" @click="$bvModal.hide('modal-clone-history')">
            ×
          </button>
        </div>
      </template>
      <ViewQuestions :key="`view-clone-history-${copyHistoryQuestionId}`"
                     :question-ids-to-view="[copyHistoryQuestionId]"
                     :show-solutions="true"
      />
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-clone-history')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <div v-if="!questionExistsInAnotherInstructorsAssignment && questionExistsInOwnAssignment">
      <b-alert :show="true" class="font-weight-bold">
        Warning: You are editing a question which already exists in one of your assignments.
      </b-alert>
    </div>
    <b-modal
      id="modal-current-question-editor"
      title="Question Currently Being Edited"
    >
      <p>{{ currentQuestionEditor }}</p>
      <template #modal-footer>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-current-question-editor')"
        >
          Got it!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-img-needs-alt"
      title="Image missing alternative text"
      hide-footer
    >
      <p>
        The following image is missing alternative text, which is descriptive text to help with accessibility issues.
        After closing this
        window, please locate the image, right-click, then click on Image Properties and add alternative text.
      </p>
      <img :src="imgNeedsAltSrc" alt="missing alternative text">
    </b-modal>
    <b-modal
      :id="`modal-confirm-delete-qti-${modalId}`"
      title="Confirm reset Native technology"
    >
      Hiding this area will delete the information associated with the Native technology. Are you sure you would like
      to
      do this?
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`modal-confirm-delete-qti-${modalId}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="deleteQtiTechnology()"
        >
          Delete
        </b-button>
      </template>
    </b-modal>

    <b-modal
      :id="modalId"
      title="Preview Question"
      size="lg"
      ok-title="OK"
      ok-only
    >
      <SolutionFileHtml v-if="questionForm.solution_html"
                        :key="`solution-file-html-${modalId}`"
                        :questions="[questionForm]"
                        :current-page="1"
                        assignment-name="Question"
                        :is-preview-solution-html="true"
      />
      <div v-if="questionForm.technology === 'qti'">
        <QtiJsonQuestionViewer
          :key="`qti-json-question-viewer-${qtiJsonQuestionViewerKey}`"
          :qti-json="showQtiAnswer ? qtiAnswerJson : JSON.stringify(qtiJson)"
          :show-qti-answer="showQtiAnswer"
          :show-submit="false"
          :show-response-feedback="false"
        />
      </div>
      <ViewQuestions v-if="questionForm.technology !== 'qti'"
                     :key="questionToViewKey"
                     :question-to-view="questionToView"
      />
      <template #modal-footer>
        <b-button
          v-if="questionForm.technology === 'qti'"
          size="sm"
          class="float-right"
          @click="getQtiAnswerJson()"
        >
          Show Answer
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="$bvModal.hide(modalId)"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <div ref="top-of-form" class="mb-3">
      <RequiredText />
      Fields marked with the
      <font-awesome-icon v-if="!sourceExpanded" :icon="caretRightIcon" size="lg" />
      icon contain expandable text areas.
    </div>
    <b-card border-variant="primary"
            header-bg-variant="primary"
            header-text-variant="white"
            class="mb-3"
    >
      <template #header>
        Meta-Information
        <QuestionCircleTooltip id="meta-information-tooltip" :icon-style="'color:#fff'" />
        <b-tooltip target="meta-information-tooltip"
                   delay="250"
                   triggers="hover focus"
        >
          The meta-information provides information about the question. This information helps us to organize the
          questions within ADAPT for searchability and also helps
          us to provide accurate authorship and license information.
        </b-tooltip>
      </template>
      <b-form-group
        v-if="questionForm.clone_history && questionForm.clone_history.length"
        label-cols-sm="3"
        label-cols-lg="2"
      >
        <template v-slot:label>
          Clone History
          <QuestionCircleTooltip :id="'clone-history-tooltip'" />
          <b-tooltip target="clone-history-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            You can view the complete clone history of this question if it was created as a clone of another question
            or series of questions.
          </b-tooltip>
        </template>
        <b-form-row class="pt-2">
          <span v-for="(questionId, index) in questionForm.clone_history"
                :key="`view-clone-history-${index}`"
          >
            <a href="" @click.prevent="copyHistoryQuestionId=questionId;$bvModal.show('modal-clone-history')">{{
              questionId
            }}</a>
            <span v-if="questionForm.clone_history.length > 1 && index !== questionForm.clone_history.length-1">-></span>
          </span>
        </b-form-row>
      </b-form-group>
      <b-modal id="modal-framework-aligner"
               title="Framework Alignment"
               size="lg"
               no-close-on-backdrop
      >
        <FrameworkAligner :key="`framework-aligner-key-${isEdit ? +questionToEdit.id : 0}`"
                          :question-id="isEdit ? +questionToEdit.id : 0"
                          :framework-item-sync-question="frameworkItemSyncQuestion"
                          :is-create-question="true"
                          @setFrameworkItemSyncQuestion="setFrameworkItemSyncQuestion"
        />

        <template #modal-footer>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-framework-aligner')"
          >
            OK
          </b-button>
        </template>
      </b-modal>
      <b-form-group
        v-if="revisionOptions.length"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="revision"
        label="Revision*"
      >
        <b-form-row>
          <b-form-select v-model="revision"
                         style="width:500px"
                         size="sm"
                         :options="revisionOptions"
                         class="mt-2 mr-2"
                         @change="setNewQuestionToEdit"
          />
          <b-button size="sm"
                    variant="outline-primary"
                    style="height:30px;margin-top:8px"
                    @click="initCompareRevisions"
          >
            Compare Revisions
          </b-button>
        </b-form-row>
      </b-form-group>
      <b-form-group
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="title"
        label="Title*"
      >
        <b-form-row>
          <b-form-input
            id="title"
            v-model="questionForm.title"
            size="sm"
            type="text"
            required
            :class="{ 'is-invalid': questionForm.errors.has('title') }"
            class="mt-2"
            @keydown="questionForm.errors.clear('title')"
          />
          <has-error :form="questionForm" field="title" />
        </b-form-row>
      </b-form-group>
      <div>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="question_type"
          :label="isEdit ? 'Question Type' : 'Question Type*'"
        >
          <b-form-row>
            <b-form-radio-group
              id="question_type"
              v-model="questionForm.question_type"
              stacked
              :aria-required="!isEdit"
              @change="resetQuestionForm($event)"
            >
              <b-form-radio name="question_type" value="assessment">
                Question
                <QuestionCircleTooltip :id="'assessment-question-type-tooltip'" />
                <b-tooltip target="assessment-question-type-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  Questions can be used within assignments. In addition, if they are purely auto-graded,
                  they can be used as root nodes in Learning Trees. Regardless of whether they have an auto-graded
                  technology, questions can be used in non-root nodes of
                  Learning Trees.
                </b-tooltip>
              </b-form-radio>
              <b-form-radio name="question_type" value="exposition">
                Exposition (use in Learning Trees only)
                <QuestionCircleTooltip :id="'exposition-question-type-tooltip'" />
                <b-tooltip target="exposition-question-type-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  An Exposition consists of source (text, video, simulation, any other html) without an auto-graded
                  component. They can be used in any of the non-root
                  nodes within Learning Trees.
                </b-tooltip>
              </b-form-radio>
              <b-form-radio v-if="isMe" name="question_type" value="report">
                Report
                <QuestionCircleTooltip :id="'report-question-type-tooltip'" />
                <b-tooltip target="report-question-type-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  For a report, you'll create a rubric which will then be used to grade each of the sections of the
                  report. The
                  analysis of the
                  reports is integrated with AI.
                </b-tooltip>
              </b-form-radio>
            </b-form-radio-group>
          </b-form-row>
        </b-form-group>
      </div>

      <div>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="public"
        >
          <template v-slot:label>
            Public*
            <QuestionCircleTooltip :id="'public-question-tooltip'" />
            <b-tooltip target="public-question-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Questions that are public can be used by any instructor. Questions that are not public are only
              accessible
              by you.
            </b-tooltip>
          </template>
          <b-form-row class="mt-2">
            <b-form-radio-group
              id="public"
              v-model="questionForm.public"
            >
              <b-form-radio name="public" value="1">
                Yes
              </b-form-radio>
              <b-form-radio name="public" value="0">
                No
              </b-form-radio>
            </b-form-radio-group>
          </b-form-row>
        </b-form-group>
      </div>

      <b-form-group
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="folder"
        label="Folder*"
      >
        <b-form-row>
          <span v-show="!showFolderOptions" class="mt-2">
            The folder is set by the question owner ({{ questionForm.question_editor_name }}).
          </span>
          <span v-show="showFolderOptions">
            <SavedQuestionsFolders
              ref="savedQuestionsFolders1"
              :key="`saved-questions-folders-key-${savedQuestionsFolderKey}`"
              class="mt-2"
              :type="'my_questions'"
              :init-saved-questions-folder="questionForm.folder_id"
              :create-modal-add-saved-questions-folder="true"
              :folder-to-choose-from="'My Questions'"
              :question-source-is-my-favorites="false"
              @reloadSavedQuestionsFolders="reloadCreateQuestionSavedQuestionsFolders"
              @savedQuestionsFolderSet="setMyCoursesFolder"
            />
          </span>
        </b-form-row>
        <input type="hidden" class="form-control is-invalid">
        <div class="help-block invalid-feedback">
          {{ questionForm.errors.get('folder_id') }}
        </div>
      </b-form-group>
      <div>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="author"
          label="Author(s)*"
        >
          <b-form-row>
            <b-form-input
              id="author"
              v-model="questionForm.author"
              size="sm"
              type="text"
              :class="{ 'is-invalid': questionForm.errors.has('author') }"
              class="mt-2"
              @keydown="questionForm.errors.clear('author')"
            />
            <has-error :form="questionForm" field="author" />
          </b-form-row>
        </b-form-group>
      </div>

      <b-form-group
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="license"
        label="License*"
      >
        <b-form-row>
          <b-form-select v-model="questionForm.license"
                         style="width:365px"
                         title="license"
                         size="sm"
                         class="mt-2 mr-2"
                         :class="{ 'is-invalid': questionForm.errors.has('license') }"
                         :options="licenseOptions"
                         @change="questionForm.errors.clear('license');questionForm.license_version = updateLicenseVersions(questionForm.license)"
          />
          <has-error :form="questionForm" field="license" />
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-if="licenseVersionOptions.length"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="license_version"
        label="License Version*"
      >
        <b-form-row>
          <b-form-select v-model="questionForm.license_version"
                         style="width:100px"
                         title="license version"
                         required
                         size="sm"
                         class="mt-2"
                         :options="licenseVersionOptions"
          />
        </b-form-row>
      </b-form-group>

      <div>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="source_url"
        >
          <template v-slot:label>
            Source URL*
            <QuestionCircleTooltip id="source_url-tooltip" />
            <b-tooltip target="source_url-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              URL where the question was created
            </b-tooltip>
          </template>
          <b-form-row>
            <b-form-input
              id="source_url"
              v-model="questionForm.source_url"
              size="sm"
              type="text"
              :class="{ 'is-invalid': questionForm.errors.has('source_url') }"
              class="mt-2"
              @keydown="questionForm.errors.clear('source_url')"
            />
            <has-error :form="questionForm" field="source_url" />
          </b-form-row>
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="tags"
          label="Tags"
        >
          <b-form-row class="mt-2">
            <b-form-input
              id="tags"
              v-model="tag"
              style="width:200px"
              type="text"
              class="mr-2"
              size="sm"
            />
            <b-button variant="outline-primary" size="sm" @click="addTag()">
              Add Tag
            </b-button>
          </b-form-row>
          <div class="d-flex flex-row">
            <span v-for="chosenTag in questionForm.tags" :key="chosenTag" class="mt-2">
              <b-button size="sm"
                        variant="secondary"
                        class="mr-2"
                        style="line-height:.8"
                        @click="removeTag(chosenTag)"
              ><span v-html="chosenTag" /> x</b-button>
            </span>
          </div>
        </b-form-group>
        <b-form-group
          label-for="framework_alignment"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Framework Alignment"
        >
          <div class="mt-1">
            <b-button size="sm" variant="outline-primary" @click="$bvModal.show('modal-framework-aligner');">
              Update
            </b-button>
          </div>
        </b-form-group>
        <span v-if="frameworkItemSyncQuestion.descriptors.length">
          <span v-for="(descriptor, descriptorsIndex) in frameworkItemSyncQuestion.descriptors"
                :key="`framework-item-sync-questions-descriptors-${descriptorsIndex}`"
                class="mr-2"
          >
            <b-button size="sm"
                      variant="secondary"
                      style="line-height:.8"
                      @click="removeFrameworkItemSyncQuestion('descriptors',descriptor.id)"
            >{{
              descriptor.text
            }} x
            </b-button>
          </span>
        </span>
        <span v-if="frameworkItemSyncQuestion.levels.length">
          <span v-for="(level, levelsIndex) in frameworkItemSyncQuestion.levels"
                :key="`framework-item-sync-questions-levels-${levelsIndex}`"
                class="mr-2"
          >
            <b-button size="sm"
                      variant="secondary"
                      style="line-height:.8"
                      @click="removeFrameworkItemSyncQuestion('levels',level.id)"
            >{{
              level.text
            }} x
            </b-button>
          </span>
        </span>
        <b-form-group
          v-show="false"
          key="learning_outcome"
          label-for="learning_outcome"
          label-cols-sm="3"
          label-cols-lg="2"
        >
          <template v-slot:label>
            Learning Outcome
            <QuestionCircleTooltip :id="'learning-outcome-tooltip'" />
            <b-tooltip target="learning-outcome-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Over time, we will be adding new learning outcome frameworks for different subjects. If you are
              aware
              of a learning outcome framework and your subject is not shown here, please contact us with the source of
              the framework.
            </b-tooltip>
          </template>
          <b-form-row class="mt-2">
            <b-form-select id="learning_outcome"
                           v-model="subject"
                           style="width:150px"
                           size="sm"
                           class="mr-2"
                           :options="subjectOptions"
                           @change="updateLearningOutcomes($event)"
            />
            <v-select :key="`subject-${subject}`"
                      v-model="learningOutcome"
                      style="width:520px"
                      placeholder="Choose a learning outcome"
                      :options="learningOutcomeOptions.filter(learningOutcomeOption => !questionForm.learning_outcomes.includes(learningOutcomeOption.id))"
                      class="mb-2"
                      @input="addLearningOutcome(learningOutcome)"
            />
          </b-form-row>
          <div v-for="(chosenLearningOutcome, index) in questionForm.learning_outcomes"
               :key="`chosen-learning-outcome-${index}`"
               class="mt-2"
          >
            <b-button size="sm" variant="secondary" class="mr-2"
                      @click="removeLearningOutcome(chosenLearningOutcome)"
            >
              {{
                //labels are brought in if it's an edited question otherwise it's done on the fly
                chosenLearningOutcome.label ? chosenLearningOutcome.label :
                getLearningOutcomeLabel(chosenLearningOutcome)
              }} x
            </b-button>
          </div>
        </b-form-group>
      </div>
    </b-card>
    <b-card border-variant="primary"
            header-bg-variant="primary"
            header-text-variant="white"
            class="mb-3"
    >
      <template #header>
        Content
        <QuestionCircleTooltip id="content-tooltip" :icon-style="'color:#fff'" />
        <b-tooltip target="content-tooltip"
                   delay="250"
                   triggers="hover focus"
        >
          Questions can consist of either pure HTML (text-based question), an auto-graded technology for automatic
          scoring, or
          may also consist of both types. Though the combined type is less common, it provides a way to incorporate
          additional resources
          such as video or other embedded media to complement the auto-graded portion of the question.
        </b-tooltip>
      </template>
      <b-form-group
        key="source"
        label-for="non_technology_text"
      >
        <template v-if="questionForm.question_type === 'assessment'" v-slot:label>
          <span style="cursor: pointer;" @click="toggleExpanded ('non_technology_text')">
            Open-Ended Content    <QuestionCircleTooltip id="open-ended-content-tooltip" />
            <b-tooltip target="open-ended-content-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Questions may be created with or without open-ended content.  This content can be used by itself (typically for students who upload submissions) or can be used to enhance
              questions that use one of the non-native auto-graded technologies.  For native questions, you can use the question's Prompt in lieu of the open-ended content block.
            </b-tooltip>
            <font-awesome-icon v-if="!editorGroups.find(group => group.id === 'non_technology_text').expanded"
                               :icon="caretRightIcon" size="lg"
            />
            <font-awesome-icon v-if="editorGroups.find(group => group.id === 'non_technology_text').expanded"
                               :icon="caretDownIcon" size="lg"
            />
          </span>
        </template>
      </b-form-group>
      <ckeditor
        v-show="editorGroups.find(group => group.id === 'non_technology_text').expanded || ['exposition','report'].includes(questionForm.question_type)"
        id="non_technology_text"
        ref="non_technology_text"
        v-model="questionForm.non_technology_text"
        tabindex="0"
        required
        :config="richEditorConfig"
        :class="{ 'is-invalid': questionForm.errors.has('non_technology_text')}"
        class="mb-2"
        @namespaceloaded="onCKEditorNamespaceLoaded"
        @ready="handleFixCKEditor()"
        @focus="ckeditorKeyDown=true"
        @keydown="questionForm.errors.clear('non_technology_text')"
      />
      <has-error :form="questionForm" field="non_technology_text" />
      <b-form-group
        v-if="questionForm.question_type === 'assessment'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="technology"
      >
        <template v-slot:label>
          <span style="cursor: pointer;" @click="toggleExpanded ('technology')">
            Auto-Graded Technology
            <font-awesome-icon v-if="!editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded"
                               :icon="caretRightIcon" size="lg"
            />
            <font-awesome-icon v-if="editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded"
                               :icon="caretDownIcon" size="lg"
            />
          </span>
        </template>
        <b-form-group v-if="editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded">
          <b-form-row class="pb-2">
            <span class="mr-2">Existing
              <QuestionCircleTooltip id="existing-question-tooltip" />
              <b-tooltip target="existing-question-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                If you've created a WebWork, H5P, or IMathAS question outside of ADAPT, then you can use the existing path (WebWork)
                or ID (H5P or IMathAS) to import that question into ADAPT so that it can be used within an ADAPT assignment.
              </b-tooltip>
            </span>
            <b-form-select
              v-model="existingQuestionFormTechnology"
              style="width:110px"
              title="technologies"
              size="sm"
              :options="existingAutoGradedTechnologyOptions"
              :aria-required="!isEdit"
              @change="initChangeExistingAutoGradedTechnology($event)"
            />
          </b-form-row>

          <b-form-row>
            <span style="margin-left:24px" class="mr-2">New  <QuestionCircleTooltip id="new-question-tooltip" />
              <b-tooltip target="new-question-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                Create a question using one of ADAPT's native question types (multplie choice, true/false, numerical, etc.), use ADAPT's
                editor to create a new WebWork question, or ADAPT can re-direct you to H5P/IMathAS so that you can create new questions and
                then import them back into ADAPT.
              </b-tooltip></span>
            <b-form-select
              v-model="newAutoGradedTechnology"
              style="width:110px"
              title="auto-graded technologies"
              size="sm"
              :options="newAutoGradedTechnologyOptions"
              @change="openCreateAutoGradedTechnologyCode($event)"
            />
            <b-form-select
              v-if="webworkEditorShown"
              v-model="webworkTemplate"
              style="width:250px"
              title="webwork templates"
              size="sm"
              class="ml-3"
              :options="webworkTemplateOptions"
              @change="setWebworkTemplate($event)"
            />
          </b-form-row>
        </b-form-group>
      </b-form-group>
      <div v-if="questionForm.technology === 'qti'">
        <b-form-group label="Native Question Type"
                      label-cols-sm="3"
                      label-cols-lg="2"
                      label-for="native-question-type"
        >
          <b-form-radio-group
            id="native-question-type"
            v-model="nativeType"
            inline
            name="native-question-type"
            class="pt-2"
            @input="initNativeType()"
          >
            <b-form-radio value="basic">
              Basic
            </b-form-radio>
            <b-form-radio value="nursing">
              Nursing
              <QuestionCircleTooltip id="nursing-questions-tooltip" />
              <b-tooltip target="nursing-questions-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                Nursing questions are question types specifically written to prepare nursing students for the NCLEX
                exam.
              </b-tooltip>
            </b-form-radio>
            <b-form-radio value="all">
              All
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <b-form-group>
          <div v-if="['all','basic'].includes(nativeType)">
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="multiple_choice"
                          @change="initQTIQuestionType($event)"
            >
              Multiple Choice
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="true_false"
                          @change="initQTIQuestionType($event)"
            >
              True/False
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="numerical"
                          @change="initQTIQuestionType($event)"
            >
              Numerical
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="multiple_answers"
                          @change="initQTIQuestionType($event)"
            >
              Multiple Answers
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="fill_in_the_blank"
                          @change="initQTIQuestionType($event)"
            >
              Fill-in-the-blank
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="select_choice"
                          @change="initQTIQuestionType($event)"
            >
              Select Choice
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType"
                          name="qti-question-type"
                          value="matching"
                          @change="initQTIQuestionType($event)"
            >
              Matching
            </b-form-radio>
          </div>
          <div v-if="['all','nursing'].includes(nativeType)">
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="bow_tie"
                          @change="initQTIQuestionType($event)"
            >
              Bow Tie
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="multiple_choice"
                          @change="initQTIQuestionType($event)"
            >
              Multiple Choice
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="matrix_multiple_choice"
                          @change="initQTIQuestionType($event)"
            >
              Matrix Multiple Choice
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="multiple_response_select_n"
                          @change="initQTIQuestionType($event)"
            >
              Multiple Response Select N
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type"
                          value="multiple_response_select_all_that_apply"
                          @change="initQTIQuestionType($event)"
            >
              Multiple Response Select All That Apply
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="highlight_table"
                          @change="initQTIQuestionType($event)"
            >
              Highlight Table
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="highlight_text"
                          @change="initQTIQuestionType($event)"
            >
              Highlight Text
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="multiple_response_grouping"
                          @change="initQTIQuestionType($event)"
            >
              Multiple Response Grouping
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drop_down_table"
                          @change="initQTIQuestionType($event)"
            >
              Drop-Down Table
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drop_down_rationale_dyad"
                          @change="initQTIQuestionType($event)"
            >
              Drop-Down Dyad
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drop_down_rationale_triad"
                          @change="initQTIQuestionType($event)"
            >
              Drop-Down Triad
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="select_choice"
                          @change="initQTIQuestionType($event)"
            >
              Drop-Down Cloze
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="matrix_multiple_response"
                          @change="initQTIQuestionType($event)"
            >
              Matrix Multiple Response
            </b-form-radio>
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drag_and_drop_cloze"
                          @change="initQTIQuestionType($event)"
            >
              Drag and Drop Cloze
            </b-form-radio>
          </div>
        </b-form-group>
        <div v-if="qtiQuestionType === 'highlight_table'">
          <b-alert show variant="info">
            Optionally add a prompt for this question. Then, in each row, add a description in the first column. Then in
            the second column, write text, where text within
            brackets will automatically become your highlighted text. Once the text is added, determine whether it is a
            correct answer or a distractor.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'drop_down_table'">
          <b-alert show variant="info">
            Write out a prompt, then add a series of drop-downs to your table. Each drop-down should have at least two
            selections.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'highlight_text'">
          <b-alert show variant="info">
            Write out a prompt, where text within brackets will automatically become your highlighted text. Once the
            text is added, determine whether it is a correct answer or a distractor.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'select_choice'">
          <b-alert show variant="info">
            Using brackets, place a non-space-containing identifier to show where
            you want the select placed.
            Example. The [planet] is the closest planet to the sun; there are [number-of-planets] planets.
            Then, add the choices below with your first choice being the correct response. Each student will
            receive a randomized ordering of the choices.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'drop_down_rationale_dyad'">
          <b-alert show variant="info">
            The structure of a “dyad rationale” question is, “The client is at risk for developing X as evidenced by Y.”
            X and Y are pulled from different pools of choices.
            Using brackets, place a non-space-containing identifier to show where you want “X” and “Y” placed.
            Example: The client is at risk for [disease] as evidenced by [type-of-assessment]. Then, under the choices
            column that appears, add the correct choice for that selection, then add the distractors for each indicator.
            Each student will receive a randomized ordering of the choices.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'drop_down_rationale_triad'">
          <b-alert show variant="info">
            <p>
              The structure of a “triad rationale” question is, “The client is at risk for developing X as evidenced by
              Y
              and Z.”
              X is pulled from a pool of choices (the condition) and Y and Z are pulled from the same pool of choices
              (the
              rationales).
              Using [condition] and [rationale], indicate where you want the condition and the two rationales placed.
            </p>
            <p>Example: The client is at risk for [condition] as evidenced by [rationale] and [rationale].</p>
            <p>
              Next, under the Condition column, add the correct choice and distractors. Under the Rationale column, add
              the two correct rationales and distractors.
            </p>
            Each student will receive a randomized ordering of the choices.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'matching'">
          <b-alert show variant="info">
            Create a list of terms to match along with their matching terms. Matching terms can include media such
            as images.
            Optionally, add distractors which do not satisfy any of the matches.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'true_false'">
          <b-alert show variant="info">
            Write a question prompt and then select either True or False for the correct answer.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'multiple_choice'">
          <b-alert show variant="info">
            Write a question prompt and then create a selection of answers, choosing the correct answer from the
            list. Students will receive a shuffled ordering of the selection.
            Optionally provide feedback at the individual question level or general feedback for a correct
            response, an incorrect response, or any response.
          </b-alert>
        </div>

        <div v-if="qtiQuestionType === 'numerical'">
          <b-alert show variant="info">
            Write a question prompt which requires a numerical response, specifying the margin of error accepted in
            the response.
            Optionally provide general feedback for a correct response, an incorrect response, or any response.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'drag_and_drop_cloze'">
          <b-alert show variant="info">
            Bracket off the portions of the text where you would like the Drag and Drop Cloze to occur, using a
            bracketed response
            to
            denote the correct answer. Then, add distractors below. Example: The client is at risk for developing
            [high blood pressure]
            and [a heart attack]. Students will then see a drop-down for each item and will only be able to choose each
            item once.
            This question mimics the Drag and Drop Cloze functionality in a way that is accessible. Because of this,
            there will be a single pool of choices.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'bow_tie'">
          <b-alert show variant="info">
            Write a question prompt and then add two correct Actions to Take, one correct Potential Condition, and two
            Parameters to Monitor. Each of these groups should have at least one Distractor.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'multiple_response_select_n'">
          <b-alert show variant="info">
            Using brackets and associated text, indicate the number of correct responses.
            Example. The [3] most likely reasons the patient has high blood pressure are:
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'multiple_response_select_all_that_apply'">
          <b-alert show variant="info">
            Write a question prompt where students have to select all responses that apply. Example. Select the
            following activities which contribute to heart disease:
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'multiple_response_grouping'">
          <b-alert show variant="info">
            Write a question prompt and then create groupings with sets of checkboxes, checking off the correct
            responses for each grouping.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'matrix_multiple_choice'">
          <b-alert show variant="info">
            Write a question prompt and then construct a table with one correct choice per row, selecting that choice
            by
            clicking
            on the corresponding radio button.
          </b-alert>
        </div>
        <div
          v-if="['matching',
                 'multiple_answers',
                 'true_false',
                 'multiple_choice',
                 'numerical',
                 'multiple_response_select_all_that_apply',
                 'multiple_response_select_n',
                 'matrix_multiple_response',
                 'multiple_response_grouping',
                 'drop_down_table',
                 'drag_and_drop_cloze',
                 'matrix_multiple_choice',
                 'bow_tie',
                 'highlight_text',
                 'highlight_table'].includes(qtiQuestionType) && qtiJson"
          class="mb-2"
        >
          <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Prompt</h2>">
            <ckeditor
              id="qtiItemPrompt"
              :key="`question-type-${qtiQuestionType}`"
              v-model="qtiJson['prompt']"
              tabindex="0"
              required
              :config="shorterRichEditorConfig"
              class="pb-3"
              @namespaceloaded="onCKEditorNamespaceLoaded"
              @ready="handleFixCKEditor()"
              @focus="ckeditorKeyDown=true"
              @input="questionForm.errors.clear('qti_prompt')"
            />

            <input type="hidden" class="form-control is-invalid">
            <div class="help-block invalid-feedback">
              {{ questionForm.errors.get(`qti_prompt`) }}
            </div>
          </b-card>
        </div>
        <div v-if="localMe">
          {{ qtiJson }}
        </div>
        <DragAndDropCloze v-if="qtiQuestionType === 'drag_and_drop_cloze'"
                          ref="dragAndDropCloze"
                          :qti-json="qtiJson"
                          :question-form="questionForm"
        />

        <MatrixMultipleChoice v-if="qtiQuestionType === 'matrix_multiple_choice'"
                              ref="dropDownTable"
                              :qti-json="qtiJson"
                              :question-form="questionForm"
        />
        <DropDownTable v-if="qtiQuestionType === 'drop_down_table'"
                       ref="dropDownTable"
                       :qti-json="qtiJson"
                       :question-form="questionForm"
        />

        <MatrixMultipleResponse v-if="qtiQuestionType === 'matrix_multiple_response'"
                                ref="matrixMultipleResponse"
                                :qti-json="qtiJson"
                                :question-form="questionForm"
        />
        <MultipleResponseGrouping v-if="qtiQuestionType === 'multiple_response_grouping'"
                                  ref="multipleResponseGrouping"
                                  :qti-json="qtiJson"
                                  :question-form="questionForm"
        />

        <MultipleResponseSelectAllThatApplyOrSelectN
          v-if="['multiple_response_select_all_that_apply','multiple_response_select_n'].includes(qtiQuestionType)"
          ref="MultipleResponseSelectAllThatApplyOrSelectN"
          :qti-json="qtiJson"
          :question-form="questionForm"
        />
        <BowTie v-if="qtiQuestionType === 'bow_tie'"
                ref="bowTie"
                :qti-json="qtiJson"
                :question-form="questionForm"
                class="p-0"
        />
        <FillInTheBlank v-if="qtiQuestionType === 'fill_in_the_blank'"
                        ref="fillInTheBlank"
                        :qti-json="qtiJson"
                        :rich-editor-config="richEditorConfig"
                        :question-form="questionForm"
                        class="p-0"
        />
        <HighlightTable v-if="qtiQuestionType === 'highlight_table'"
                        ref="HighlightTable"
                        :qti-json="qtiJson"
                        :question-form="questionForm"
        />
        <Numerical v-if="qtiQuestionType === 'numerical'"
                   ref="Nuemrical"
                   :qti-json="qtiJson"
                   :question-form="questionForm"
        />

        <div v-if="['drop_down_rationale_dyad','drop_down_rationale_triad','select_choice'].includes(qtiQuestionType)">
          <ckeditor
            id="qtiItemBody"
            :key="`question-type-${qtiQuestionType}`"
            v-model="qtiJson.itemBody"
            tabindex="0"
            required
            :config="richEditorConfig"
            :class="{ 'is-invalid': questionForm.errors.has('qti_item_body')}"
            class="pb-3"
            @namespaceloaded="onCKEditorNamespaceLoaded"
            @ready="handleFixCKEditor()"
            @focus="ckeditorKeyDown=true"
            @keydown="questionForm.errors.clear('qti_item_body')"
          />
          <has-error :form="questionForm" field="qti_item_body" />
        </div>
        <SelectChoiceDropDownRationale
          v-if="['select_choice','drop_down_rationale_dyad'].includes(qtiQuestionType)"
          ref="selectChoiceDropDownRationale"
          :qti-json="qtiJson"
          :question-form="questionForm"
        />
        <DropDownRationaleTriad
          v-if="qtiQuestionType === 'drop_down_rationale_triad'"
          ref="dropDownTriad"
          :qti-json="qtiJson"
          :question-form="questionForm"
        />

        <Matching v-if="qtiQuestionType === 'matching'"
                  ref="matching"
                  :qti-json="qtiJson"
                  :question-form="questionForm"
                  :matching-rich-editor-config="matchingRichEditorConfig"
        />

        <MultipleAnswers v-if="qtiQuestionType === 'multiple_answers'"
                         ref="multipleAnswers"
                         :qti-json="qtiJson"
                         :question-form="questionForm"
                         :matching-rich-editor-config="matchingRichEditorConfig"
                         :multiple-response-rich-editor-config="multipleResponseRichEditorConfig"
                         class="p-0"
        />

        <MultipleChoiceTrueFalse v-if="['true_false','multiple_choice'].includes(qtiQuestionType)"
                                 ref="multipleChoiceTrueFalse"
                                 :key="qtiQuestionType"
                                 :qti-json="qtiJson"
                                 :question-form="questionForm"
                                 :simple-choice-config="simpleChoiceConfig"
        />
        <HighlightText v-if="qtiQuestionType === 'highlight_text'"
                       ref="HighlightText"
                       :qti-json="qtiJson"
                       :question-form="questionForm"
        />
        <div class="pb-2">
          <b-card
            v-if="['multiple_choice','numerical'].includes(qtiQuestionType)
              || nursingQuestions.includes(qtiQuestionType)
              ||qtiQuestionType.includes('drop_down_rationale')
              || (qtiQuestionType === 'select_choice' && nativeType === 'nursing')"
            header="default"
          >
            <template #header>
              <span class="ml-2 h7">General Feedback</span>
            </template>
            <div v-for="(generalFeedback,index) in generalFeedbacks"
                 :key="`feedback-${generalFeedback.label}`"
            >
              <div
                v-if="generalFeedback.label !== 'Any Response'
                  || !((nursingQuestions.includes(qtiQuestionType)||(qtiQuestionType === 'select_choice' && nativeType === 'nursing'))
                    || qtiQuestionType.includes('drop_down_rationale'))"
              >
                <b-form-group
                  :label-for="generalFeedback.id"
                  class="mb-0"
                >
                  <template v-slot:label>
                    <span class="font-weight-bold">{{ generalFeedback.label }}</span>
                    <b-icon icon="pencil"
                            :variant="generalFeedback.editorShown ? 'secondary' : 'primary'"
                            :aria-label="`Edit ${generalFeedback.label} feedback`"
                            @click="toggleGeneralFeedbackEditorShown(generalFeedback.key,true)"
                    />
                  </template>
                  <div v-if="generalFeedback.editorShown">
                    <ckeditor
                      :id="generalFeedback.id"
                      v-model="qtiJson.feedback[generalFeedback.key]"
                      tabindex="0"
                      :config="simpleChoiceFeedbackConfig"
                      @namespaceloaded="onCKEditorNamespaceLoaded"
                      @ready="handleFixCKEditor()"
                    />
                    <div class="mt-2">
                      <b-button
                        size="sm"
                        variant="primary"
                        @click="toggleGeneralFeedbackEditorShown(generalFeedback.key,false)"
                      >
                        Close
                      </b-button>
                    </div>
                  </div>
                  <div v-if="qtiJson.feedback && !generalFeedback.editorShown">
                    <span v-html="qtiJson.feedback[generalFeedback.key]" />
                  </div>
                </b-form-group>
                <hr
                  v-if="(index !==2 && !nursingQuestions.includes(qtiQuestionType)) || (index === 0 && nursingQuestions.includes(qtiQuestionType))"
                >
              </div>
            </div>
          </b-card>
        </div>
      </div>
      <b-form-group v-if="showPreexistingWebworkFilePath"
                    label-cols-sm="4"
                    label-cols-lg="3"
                    label="ADAPT ID or WeBWork File Path"
                    label-for="pre_existing_webwork_problem"
      >
        <b-form-row>
          <b-form-input
            id="pre_existing_webwork_problem"
            v-model="preExistingWebworkFilePath"
            type="text"
            size="sm"
            style="width:500px"
            class="mr-2"
          />
          <b-button size="sm" @click="updateTemplateWithPreexistingWebworkFilePath(preExistingWebworkFilePath)">
            <span v-if="!updatingTempalteWithPreexistingWebworkFilePath">Update template</span>
            <span v-if="updatingTempalteWithPreexistingWebworkFilePath"><b-spinner small type="grow" />
              Updating...
            </span>
          </b-button>
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-if="existingQuestionFormTechnology !== 'text'
          && !webworkEditorShown
          && questionForm.question_type === 'assessment'"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="technology_id"
        :label="existingQuestionFormTechnology === 'webwork' ? 'WeBWork Path' : `${getTextFromTechnology(questionForm.technology)} ID`"
      >
        <b-form-row>
          <b-form-input
            id="technology_id"
            v-model="questionForm.technology_id"
            type="text"
            :style="existingQuestionFormTechnology === 'webwork' ? 'width:700px' : ''"
            :class="{ 'is-invalid': questionForm.errors.has('technology_id'), 'numerical-input' : questionForm.technology !== 'webwork' }"
            @keydown="questionForm.errors.clear('technology_id')"
          />
          <has-error :form="questionForm" field="technology_id" />
        </b-form-row>
      </b-form-group>
      <div v-show="webworkEditorShown">
        <div class="mb-2">
          If you need help getting started, please visit <a href="https://webwork.maa.org/wiki/Authors"
                                                            target="_blank"
          >https://webwork.maa.org/wiki/Authors</a>.
        </div>
        <b-row>
          <b-col cols="6">
            <b-form-file
              v-model="webworkAttachmentsForm.attachment"
              class="mb-2"
              size="sm"
              placeholder="Choose an image or drop it here..."
              drop-placeholder="Drop Image here..."
            />
            <div v-if="uploading">
              <b-spinner small type="grow" />
              Uploading file...
            </div>
            <div v-for="(errorMessage, errorMessageIndex) in errorMessages" :key="`error-message-${errorMessageIndex}`">
              <ErrorMessage :message="errorMessage" />
            </div>
          </b-col>
          <b-col>
            <b-button variant="info"
                      size="sm"
                      :disabled="!webworkAttachmentsForm.attachment || (webworkAttachmentsForm.attachment && webworkAttachmentsForm.attachment.length === 0)"
                      @click="uploadWebworkAttachment"
            >
              Upload Image
            </b-button>
            <a v-if="questionForm.id && questionToView.webwork_code !== null"
               class="btn btn-sm btn-outline-primary link-outline-primary-btn ml-2"
               :href="`/api/questions/export-webwork-code/${questionForm.id}`"
            >
              <div style="margin-top:3px">Export webWork code</div>
            </a>
          </b-col>
        </b-row>
        <b-row v-if="webworkAttachments">
          <ul>
            <li v-for="(webworkAttachment, webworkAttachmentIndex) in webworkAttachments"
                :key="`webwork-attachment-${webworkAttachmentIndex}`"
            >
              {{ webworkAttachment.filename }}
              <span class="text-primary"
                    @click="setWebworkAttachmentOptions(webworkAttachment);$bvModal.show('modal-webwork-image-options')"
              ><font-awesome-icon
                :icon="copyIcon"
              /></span>
              <b-icon-trash @click="confirmDeleteWebworkAttachment(webworkAttachment)" />
            </li>
          </ul>
        </b-row>
        <b-textarea v-model="questionForm.webwork_code"
                    style="width:100%"
                    :class="{ 'is-invalid': questionForm.errors.has('webwork_code')}"
                    rows="10"
                    @keydown="questionForm.errors.clear('webwork_code')"
        />
        <has-error :form="questionForm" field="webwork_code" />
      </div>
    </b-card>
    <b-card
      v-if="questionForm.question_type === 'report'"
      border-variant="primary"
      header-bg-variant="primary"
      header-text-variant="white"
      class="mb-3"
    >
      <template #header>
        Report Grading
        <QuestionCircleTooltip id="report-grading-tooltip" :icon-style="'color:#fff'" />
        <b-tooltip target="report-grading-tooltip"
                   delay="250"
                   triggers="hover focus"
        >
          Specify the purpose of the report and the report's rubric to help the AI process the report
        </b-tooltip>
      </template>
      <div v-if="questionExistsInAnotherInstructorsAssignment">
        <b-alert :show="true" class="font-weight-bold">
          This question exists is another instructor's assignment so the rubric information may not be edited.
        </b-alert>
      </div>
      <div class="mb-3">
        <div v-if="questionExistsInAnotherInstructorsAssignment">
          The purpose of the report:
        </div>
        <div v-if="!questionExistsInAnotherInstructorsAssignment">
          Please specify the purpose of the report so the AI has some context in which to grade.
        </div>
      </div>
      <b-form-group>
        <b-form-row>
          <b-textarea
            v-show="!questionExistsInAnotherInstructorsAssignment"
            id="purpose"
            v-model="questionForm.purpose"
            required
            rows="3"
            type="text"
            :class="{ 'is-invalid': questionForm.errors.has('purpose') }"
            @keydown="questionForm.errors.clear('purpose')"
          />
          <has-error :form="questionForm" field="purpose" />
        </b-form-row>
        <div v-show="questionExistsInAnotherInstructorsAssignment">
          {{ questionForm.purpose }}
        </div>
      </b-form-group>

      <b-form-group
        label-for="grading-style"
        label-cols-sm="2"
        label-align-sm="center"
        class="mb-0"
      >
        <template v-slot:label>
          Grading Style
          <QuestionCircleTooltip id="grading-style-tooltip" />
          <b-tooltip target="grading-style-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            Choosing the grading style will affect how the AI responds with feedback and how it scores the lab
          </b-tooltip>
        </template>
        <b-form-select v-show="!questionExistsInAnotherInstructorsAssignment"
                       v-model="questionForm.grading_style_id"
                       :options="gradingStyleOptions"
                       :class="{ 'is-invalid': questionForm.errors.has('grading_style_id') }"
                       style="width: 250px"
                       @change="questionForm.errors.clear('grading_style_id')"
        />
        <has-error :form="questionForm" field="grading_style_id" />
        <div v-if="questionExistsInAnotherInstructorsAssignment" class="mt-1">
          {{ gradingStyleOptions.find(item => item.value === questionForm.grading_style_id).text }}
        </div>
      </b-form-group>
      <Rubric :key="`rubric-${revision}`"
              class="mt-3"
              :question-id="isEdit && questionForm.question_type === 'report' ? questionToEdit.id : 0"
              :question-revision-id="revision"
              :question-form="questionForm"
              :question-exists-in-another-instructors-assignment="questionExistsInAnotherInstructorsAssignment"
              @updateQuestionFormRubricCategories="updateQuestionFormRubricCategories"
      />
    </b-card>

    <b-card v-if="questionForm.question_type === 'assessment'"
            border-variant="primary"
            header-bg-variant="primary"
            header-text-variant="white"
            class="mb-3"
    >
      <template #header>
        Accessibility Alternatives
        <QuestionCircleTooltip id="accessibility-tooltip" :icon-style="'color:#fff'" />
        <b-tooltip target="accessibility-tooltip"
                   delay="250"
                   triggers="hover focus"
        >
          An accessible open-ended text alternative or an accessible auto-graded version of the question.
        </b-tooltip>
      </template>
      <b-form-group
        label-for="Open-Ended Text Alternative"
      >
        <template v-slot:label>
          <span style="cursor: pointer;" @click="toggleExpanded ('text_question')">
            Open-Ended Text Alternative

            <QuestionCircleTooltip id="text-question-tooltip" />
            <b-tooltip target="text-question-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              You can optionally create an open-ended text version of your question which may be useful if you are using one of the
              non-native auto-graded technologies.
            </b-tooltip>

            <font-awesome-icon v-if="!editorGroups.find(group => group.id === 'text_question').expanded"
                               :icon="caretRightIcon" size="lg"
            />
            <font-awesome-icon v-if="editorGroups.find(group => group.id === 'text_question').expanded"
                               :icon="caretDownIcon" size="lg"
            />
          </span>
        </template>
        <ckeditor
          v-show="editorGroups.find(group => group.id === 'text_question').expanded"
          id="Open-Ended Text Alternative"
          v-model="questionForm.text_question"
          tabindex="0"
          :config="richEditorConfig"
          @namespaceloaded="onCKEditorNamespaceLoaded"
          @ready="handleFixCKEditor()"
        />
      </b-form-group>
      <div v-if="questionForm.question_type === 'assessment'">
        <b-form-group
          label-cols-sm="5"
          label-cols-lg="4"
          label-for="a11y_technology"
        >
          <template v-slot:label>
            <span style="cursor: pointer;" @click="toggleExpanded ('a11y_technology')">
              Auto-Graded Technology Alternative    <QuestionCircleTooltip id="a11y-auto-graded-tooltip" />
              <b-tooltip target="a11y-auto-graded-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                You can optionally provide a secondary accessible auto-graded question which can be shown on a per-student basis.
              </b-tooltip>
              <font-awesome-icon
                v-if="!editorGroups.find(editorGroup => editorGroup.id === 'a11y_technology').expanded"
                :icon="caretRightIcon" size="lg"
              />
              <font-awesome-icon
                v-if="editorGroups.find(editorGroup => editorGroup.id === 'a11y_technology').expanded"
                :icon="caretDownIcon" size="lg"
              />
            </span>
          </template>
          <b-form-row v-if="editorGroups.find(editorGroup => editorGroup.id === 'a11y_technology').expanded">
            <div v-if="questionForm.technology ==='text'">
              <b-alert show variant="info">
                Please first select an auto-graded technology for the original question.
              </b-alert>
            </div>
            <div v-else>
              <b-form-select
                id="a11y_technology"
                v-model="questionForm.a11y_technology"
                style="width:110px"
                title="accessible alternative technologies"
                size="sm"
                class="mt-2"
                :options="a11yAutoGradedTechnologyOptions"
                :aria-required="!isEdit"
              />
            </div>
          </b-form-row>
        </b-form-group>
        <div v-if="questionForm.a11y_technology !== null">
          <b-alert v-if="questionForm.a11y_technology === 'qti'" show variant="info">
            Native ADAPT questions are accessible by design so no alternative is needed.
          </b-alert>
          <div v-if="questionForm.a11y_technology !== 'qti'">
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="technology_id"
              :label="questionForm.a11y_technology === 'webwork' ? 'WeBWork Path' : `${getTextFromTechnology(questionForm.a11y_technology)} ID`"
            >
              <b-form-row>
                <b-form-input
                  id="a11y_technology_id"
                  v-model="questionForm.a11y_technology_id"
                  type="text"
                  :class="{ 'is-invalid': questionForm.errors.has('a11y_technology_id'), 'numerical-input' : questionForm.a11y_technology !== 'webwork' }"
                  @keydown="questionForm.errors.clear('a11y_technology_id')"
                />
                <has-error :form="questionForm" field="a11y_technology_id" />
              </b-form-row>
            </b-form-group>
          </div>
        </div>
      </div>
    </b-card>
    <b-card
      border-variant="primary"
      header-bg-variant="primary"
      header-text-variant="white"
      class="mb-3"
    >
      <template #header>
        Supplemental Content
        <QuestionCircleTooltip id="supplemental-content-tooltip" :icon-style="'color:#fff'" />
        <b-tooltip target="supplemental-content-tooltip"
                   delay="250"
                   triggers="hover focus"
        >
          An answer/solution to the question, a hint for students, and
          personal notes may
          be optionally associated with the question.
        </b-tooltip>
      </template>
      <div
        v-for="editorGroup in editorGroups.filter(group => !['technology','a11y_technology','non_technology_text','text_question'].includes(group.id))"
        :key="editorGroup.id"
      >
        <b-form-group
          v-if="questionForm.question_type === 'assessment' ||editorGroup.id==='notes'"
          :label-for=" editorGroup.label "
        >
          <template v-slot:label>
            <span style="cursor: pointer;" @click="toggleExpanded (editorGroup.id)">
              {{ editorGroup.label }}
              <span v-if="editorGroup.label === 'Answer'"><QuestionCircleTooltip id="answer-tooltip" />
                <b-tooltip target="answer-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  The answer to the question.  Answers are optional.
                </b-tooltip>
              </span>
              <span v-if="editorGroup.label === 'Solution'"><QuestionCircleTooltip id="solution-tooltip" />
                <b-tooltip target="solution-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  A more detailed solution to the question. Solutions are optional.
                </b-tooltip>
              </span>
              <span v-if="editorGroup.label === 'Hint'"><QuestionCircleTooltip id="hint-tooltip" />
                <b-tooltip target="hint-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  Hints can be provided to students within assignments. Hints are optional.
                </b-tooltip>
              </span>
              <span v-if="editorGroup.label === 'Notes'"><QuestionCircleTooltip id="notes-tooltip" />
                <b-tooltip target="notes-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  Notes are for a way for the creator of the question to associate additional information with the question.  Students
                  will never see this information.  Notes are optional.
                </b-tooltip>
              </span>
              <font-awesome-icon v-if="!editorGroup.expanded" :icon="caretRightIcon" size="lg" />
              <font-awesome-icon v-if="editorGroup.expanded" :icon="caretDownIcon" size="lg" />
            </span>
          </template>
          <ckeditor
            v-show="editorGroup.expanded"
            :id="editorGroup.label"
            v-model="questionForm[editorGroup.id]"
            tabindex="0"
            :config="richEditorConfig"
            @namespaceloaded="onCKEditorNamespaceLoaded"
            @ready="handleFixCKEditor()"
          />
        </b-form-group>
      </div>
    </b-card>

    <span class="float-right">
      <b-button v-if="isEdit"
                size="sm"
                @click="$bvModal.hide(`modal-edit-question-${questionToEdit.id}`)"
      >
        Cancel</b-button>
      <b-button v-if="isMe && questionForm.technology === 'qti' && jsonShown" size="sm"
                @click="jsonShown = false"
      >
        Hide json
      </b-button>
      <b-button v-if="isMe && questionForm.technology=== 'qti' && !jsonShown" size="sm"
                @click="jsonShown = true"
      >
        Show json
      </b-button>
      <b-button size="sm"
                variant="info"
                @click="previewQuestion"
      >
        <span v-if="processingPreview"><b-spinner small type="grow" /> </span>
        Preview
      </b-button>

      <b-button
        v-if="!savingQuestion"
        size="sm"
        variant="primary"
        @click="initSaveQuestion()"
      >Save</b-button>
    </span>
    <span v-if="savingQuestion">
      <b-spinner small type="grow" />
      Saving...
    </span>
    <b-container v-if="jsonShown" class="pt-4 mt-4">
      <b-row>{{ qtiJson }}</b-row>
    </b-container>
  </div>
</template>

<script>
import { doCopy } from '~/helpers/Copy'
import AllFormErrors from '~/components/AllFormErrors'
import ErrorMessage from '~/components/ErrorMessage'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import Form from 'vform/src'
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCaretDown, faCaretRight } from '@fortawesome/free-solid-svg-icons'
import CKEditor from 'ckeditor4-vue'
import { mapGetters } from 'vuex'
import { defaultLicenseVersionOptions, licenseOptions, updateLicenseVersions } from '~/helpers/Licenses'
import ViewQuestions from '~/components/ViewQuestions'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'
import QtiJsonQuestionViewer from '~/components/QtiJsonQuestionViewer'
import { v4 as uuidv4 } from 'uuid'
import { getLearningOutcomes, subjectOptions } from '~/helpers/LearningOutcomes'
import 'vue-select/dist/vue-select.css'
import SolutionFileHtml from '~/components/SolutionFileHtml'
import $ from 'jquery'

import axios from 'axios'
import FillInTheBlank from './FillInTheBlank'
import MatrixMultipleResponse from './nursing/MatrixMultipleResponse'
import MultipleResponseGrouping from './nursing/MultipleResponseGrouping'
import MultipleChoiceTrueFalse from './MultipleChoiceTrueFalse'
import BowTie from './nursing/BowTie'
import MultipleResponseSelectAllThatApplyOrSelectN from './nursing/MultipleResponseSelectAllThatApplyOrSelectN'
import DropDownTable from './nursing/DropDownTable'
import DragAndDropCloze from './nursing/DragAndDropCloze'
import MatrixMultipleChoice from './nursing/MatrixMultipleChoice'
import SelectChoiceDropDownRationale from './nursing/SelectChoiceDropDownRationale'
import HighlightText from './nursing/HighlightText'
import HighlightTable from './nursing/HighlightTable'
import Matching from './Matching'
import Numerical from './Numerical'
import MultipleAnswers from './MultipleAnswers'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import FrameworkAligner from '../FrameworkAligner'
import DropDownRationaleTriad from './nursing/DropDownRationaleTriad.vue'
import Rubric from './Rubric.vue'
import QuestionRevisionDifferences from '../QuestionRevisionDifferences.vue'

const defaultQuestionForm = {
  question_type: 'assessment',
  public: '0',
  title: '',
  learning_outcomes: [],
  author: '',
  tags: [],
  technology: 'text',
  technology_id: '',
  non_technology_text: '',
  purpose: '',
  grading_style_id: null,
  rubric_categories: [],
  text_question: null,
  a11y_technology: null,
  a11y_technology_id: '',
  answer_html: null,
  solution_html: null,
  notes: null,
  hint: null,
  license: null,
  license_version: null,
  source_url: ''
}

let newAutoGradedTechnologyOptions
newAutoGradedTechnologyOptions = [{ value: null, text: 'None' }, { value: 'qti', text: 'Native' }]
const h5pUrl = 'https://studio.libretexts.org/node/add/h5p'
const imathASUrl = 'https://imathas.libretexts.org/imathas/course/moddataset.php'
let commonTechnologyOptions = [{ value: h5pUrl, text: 'H5P' },
  { value: 'webwork', text: 'WeBWork' },
  { value: imathASUrl, text: 'IMathAS' }]

for (let i = 0; i < commonTechnologyOptions.length; i++) {
  newAutoGradedTechnologyOptions.push(commonTechnologyOptions[i])
}
const multipleResponseRichEditorConfig = {
  toolbar: [
    { name: 'math', items: ['Mathjax'] },
    { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo'] },
    {
      name: 'basicstyles',
      items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript']
    },
    { name: 'links', items: ['Link', 'Unlink', 'IFrame', 'Embed'] },
    { name: 'extra', items: ['Source', 'Maximize'] }
  ],
  embed_provider: '//ckeditor.iframe.ly/api/oembed?url={url}&callback={callback}',
  // Configure the Enhanced Image plugin to use classes instead of styles and to disable the
  // resizer (because image size is controlled by widget styles or the image takes maximum
  // 100% of the editor width).
  removeButtons: '',
  extraPlugins: 'mathjax,embed,dialog,contextmenu',
  mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
  height: 50,
  allowedContent: true,
  disableNativeSpellChecker: false
}

const matchingRichEditorConfig = {
  toolbar: [
    { name: 'image', items: ['Image'] },
    { name: 'math', items: ['Mathjax'] },
    { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo'] },
    {
      name: 'basicstyles',
      items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', 'SpecialChar']
    },
    { name: 'links', items: ['Link', 'Unlink', 'IFrame', 'Embed'] },
    { name: 'extra', items: ['Source', 'Maximize'] }
  ],
  embed_provider: '//ckeditor.iframe.ly/api/oembed?url={url}&callback={callback}',
  // Configure the Enhanced Image plugin to use classes instead of styles and to disable the
  // resizer (because image size is controlled by widget styles or the image takes maximum
  // 100% of the editor width).
  removeButtons: '',
  extraPlugins: 'mathjax,embed,dialog,image2,contextmenu,autogrow',
  image2_alignClasses: ['image-align-left', 'image-align-center', 'image-align-right'],
  image2_altRequired: true,
  mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
  autoGrow_minHeight: 75,
  filebrowserUploadUrl: '/api/ckeditor/upload',
  filebrowserUploadMethod: 'form',
  allowedContent: true,
  disableNativeSpellChecker: false
}

const simpleChoiceFeedbackConfig = JSON.parse(JSON.stringify(matchingRichEditorConfig))
const simpleChoiceConfig = JSON.parse(JSON.stringify(matchingRichEditorConfig))

const richEditorConfig = {
  toolbar: [
    { name: 'image', items: ['Image'] },
    { name: 'math', items: ['Mathjax'] },
    { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo'] },
    {
      name: 'basicstyles',
      items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']
    },
    {
      name: 'paragraph',
      items: ['BulletedList', 'NumberedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
    },
    { name: 'links', items: ['Link', 'Unlink', 'IFrame', 'Embed'] },
    { name: 'insert', items: ['Table', 'HorizontalRule', 'Smiley', 'SpecialChar'] },
    { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
    { name: 'colors', items: ['TextColor', 'BGColor'] },
    { name: 'extra', items: ['Source', 'Maximize'] }
  ],
  embed_provider: '//ckeditor.iframe.ly/api/oembed?url={url}&callback={callback}',
  // Configure the Enhanced Image plugin to use classes instead of styles and to disable the
  // resizer (because image size is controlled by widget styles or the image takes maximum
  // 100% of the editor width).
  image2_alignClasses: ['image-align-left', 'image-align-center', 'image-align-right'],
  image2_altRequired: true,
  removeButtons: '',
  extraPlugins: 'mathjax,embed,dialog,contextmenu,liststyle,image2,autogrow',
  mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
  filebrowserUploadUrl: '/api/ckeditor/upload',
  filebrowserUploadMethod: 'form',
  format_tags: 'p;h2;h3;pre',
  allowedContent: true,
  disableNativeSpellChecker: false
}
let shorterRichEditorConfig = JSON.parse(JSON.stringify(richEditorConfig))
shorterRichEditorConfig.autoGrow_minHeight = 100

const simpleChoiceJson = {
  questionType: 'multiple_choice',
  prompt: '',
  simpleChoice: {}
}

const textEntryInteractionJson = {
  questionType: 'fill_in_the_blank',
  'responseDeclaration': {
    'correctResponse': {
      'value': ''
    }
  },
  'itemBody': {}
}
export default {
  name: 'CreateQuestion',
  components: {
    QuestionRevisionDifferences,
    Rubric,
    DropDownRationaleTriad,
    ErrorMessage,
    FrameworkAligner,
    MultipleChoiceTrueFalse,
    MultipleAnswers,
    Numerical,
    FillInTheBlank,
    Matching,
    HighlightTable,
    HighlightText,
    SelectChoiceDropDownRationale,
    MatrixMultipleChoice,
    DragAndDropCloze,
    DropDownTable,
    MultipleResponseSelectAllThatApplyOrSelectN,
    MatrixMultipleResponse,
    MultipleResponseGrouping,
    BowTie,
    FontAwesomeIcon,
    ckeditor: CKEditor.component,
    AllFormErrors,
    ViewQuestions,
    SavedQuestionsFolders,
    QtiJsonQuestionViewer,
    SolutionFileHtml
  },
  props: {
    assignmentId: {
      type: Number,
      default: 0
    },
    modalId: {
      type: String,
      default: ''
    },
    questionToEdit: {
      type: Object,
      default: () => {
      }
    },
    parentGetMyQuestions: {
      type: Function,
      default: () => {
      }
    },
    questionExistsInOwnAssignment: {
      type: Boolean,
      default: false
    },
    questionExistsInAnotherInstructorsAssignment: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    changeResizeType: false,
    initResizeHeight: 0,
    initResizeWidth: 0,
    resizeWidth: 100,
    resizeHeight: 100,
    resizeImageBy: 'percentage',
    maintainAspectRatio: true,
    diffsShown: true,
    mathJaxRendered: false,
    revision1: {},
    revision2: {},
    questionRevisionDifferencesKey: 0,
    revisionAction: '',
    powerUser: false,
    differences: [],
    revision1Id: null,
    revision2Id: null,
    revisionOptions: [],
    revision: 0,
    multipleChoiceTrueFalseKey: 0,
    gradingStyleOptions: [],
    showFolderOptions: true,
    ckeditorKeyDown: false,
    nativeType: 'basic',
    fullyMounted: false,
    webworkAttachmentToDelete: '',
    webworkImageOptions: {
      filename: '',
      width: '',
      height: '',
      tex_size: '',
      extras_html_tags: ''
    },
    webworkAttachments: [],
    sessionIdentifier: '',
    uploading: false,
    errorMessages: [],
    webworkAttachmentsForm: new Form({
      attachment: []
    }),
    savingQuestion: false,
    frameworkItemSyncQuestion: { 'descriptors': [], 'levels': [] },
    copyIcon: faCopy,
    copyHistoryQuestionId: null,
    simpleChoiceToRemove: {},
    qtiAnswerJson: '',
    nursingQuestions: ['bow_tie',
      'multiple_response_select_all_that_apply',
      'multiple_response_select_n',
      'matrix_multiple_response',
      'multiple_response_grouping',
      'drop_down_table',
      'drag_and_drop_cloze',
      'matrix_multiple_choice',
      'bow_tie',
      'highlight_text',
      'highlight_table',
      'drop_down_rationale'],
    existingQuestionFormTechnology: 'text',
    nursing: false,
    currentQuestionEditor: '',
    learningOutcome: '',
    subject: null,
    subjectOptions: subjectOptions,
    learningOutcomeOptions: [],
    imgNeedsAltSrc: '',
    originalPreexistingWebworkCode: '',
    updatingTempalteWithPreexistingWebworkFilePath: false,
    preExistingWebworkFilePath: '',
    showPreexistingWebworkFilePath: false,
    generalFeedbacks: [{
      key: 'correct',
      id: 'correct-response-feedback',
      label: 'Correct Response',
      editorShown: false
    },
    {
      key: 'incorrect',
      id: 'incorrect-response-feedback',
      label: 'Incorrect Response',
      editorShown: false
    },
    {
      key: 'any',
      id: 'any-response-feedback',
      label: 'Any Response',
      editorShown: false
    }
    ],
    webworkTemplate: null,
    webworkTemplateOptions: [],
    processingPreview: false,
    webworkEditorShown: false,
    multipleChoiceGeneralFeedbacks: [{
      key: 'correct',
      id: 'correct-response-feedback',
      label: 'Correct Response',
      editorShown: false
    },
    {
      key: 'incorrect',
      id: 'incorrect-response-feedback',
      label: 'Incorrect Response',
      editorShown: false
    },
    {
      key: 'any',
      id: 'any-response-feedback',
      label: 'Any Response',
      editorShown: false
    }
    ],
    simpleChoiceFeedbackConfig: simpleChoiceFeedbackConfig,
    jsonShown: false,
    qtiJsonQuestionViewerKey: 0,
    showQtiAnswer: false,
    questionFormTechnology: 'text',
    qtiQuestionType: 'multiple_choice',
    qtiPrompt: '',
    correctResponse: '',
    qtiJson: {},
    sourceExpanded: false,
    caretDownIcon: faCaretDown,
    caretRightIcon: faCaretRight,
    newAutoGradedTechnology: null,
    createA11yAutoGradedTechnology: null,
    newAutoGradedTechnologyOptions: newAutoGradedTechnologyOptions,
    savedQuestionsFolderKey: 0,
    questionToView: {},
    questionToViewKey: 0,
    questionsFormKey: 0,
    isEdit: false,
    tag: '',
    toggleColors: window.config.toggleColors,
    view: 'basic',
    licenseOptions: licenseOptions,
    defaultLicenseVersionOptions: defaultLicenseVersionOptions,
    licenseVersionOptions: [],
    editorGroups: [
      { id: 'technology', expanded: false },
      { id: 'a11y_technology', expanded: false },
      { id: 'non_technology_text', label: 'Open-Ended Content', expanded: false },
      { label: 'Open-Ended Text Alternative', id: 'text_question', expanded: false },
      { label: 'Answer', id: 'answer_html', expanded: false },
      { label: 'Solution', id: 'solution_html', expanded: false },
      { label: 'Hint', id: 'hint', expanded: false },
      { label: 'Notes', id: 'notes', expanded: false }
    ],
    questionForm: new Form(defaultQuestionForm),
    allFormErrors: [],
    existingAutoGradedTechnologyOptions: [
      { value: 'text', text: 'None' },
      { value: 'webwork', text: 'WeBWorK' },
      { value: 'h5p', text: 'H5P' },
      { value: 'imathas', text: 'IMathAS' }
    ],
    a11yAutoGradedTechnologyOptions: [
      { value: null, text: 'None' },
      { value: 'qti', text: 'Native' },
      { value: 'webwork', text: 'WeBWorK' },
      { value: 'h5p', text: 'H5P' },
      { value: 'imathas', text: 'IMathAS' }
    ],
    richEditorConfig: richEditorConfig,
    shorterRichEditorConfig: shorterRichEditorConfig,
    multipleResponseRichEditorConfig: multipleResponseRichEditorConfig,
    simpleChoiceConfig: simpleChoiceConfig,
    matchingRichEditorConfig: matchingRichEditorConfig
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    localMe: () => window.config.isMe && window.location.hostname === 'local.adapt',
    isMe: () => window.config.isMe
  },
  created () {
    this.getLearningOutcomes = getLearningOutcomes
  },
  beforeDestroy () {
    window.removeEventListener('keydown', this.hotKeys)
  },
  updated: function () {
    if (this.ckeditorKeyDown) {
      console.log('no update needed')
      return
    }
    this.$nextTick(function () {
      if (this.fullyMounted) {
        if (this.questionForm.non_technology_text) {
          if (this.questionForm.non_technology_text.search('<p>&nbsp;</p>') !== -1) {
            this.fixEmptyParagraphs('non_technology_text')
          } else {
            if (this.questionForm.non_technology_text.search('<p>&nbsp;</p>') === -1) {
              console.log('non-technology paragraphs have been removed')
            }
          }
        }
        if (this.qtiJson.itemBody && typeof this.qtiJson.itemBody === 'string' && this.qtiJson.itemBody.length > 0) {
          if (this.qtiJson.itemBody.search('<p>&nbsp;</p>') !== -1) {
            this.fixEmptyParagraphs('qti_json_item_body')
          } else {
            if (this.qtiJson.itemBody.search('<p>&nbsp;</p>') === -1) {
              console.log('qti json item body paragraphs have been removed')
            }
          }
        }
        if (this.qtiJson.prompt) {
          if (this.qtiJson.prompt.search('<p>&nbsp;</p>') !== -1) {
            this.fixEmptyParagraphs('qti_json_prompt')
          } else {
            if (this.qtiJson.prompt.search('<p>&nbsp;</p>') === -1) {
              console.log('qti json prompt paragraphs have been removed')
            }
          }
        }
      }
    })
  },
  async mounted () {
    if (![2, 5].includes(this.user.role)) {
      return false
    }
    this.doCopy = doCopy
    window.addEventListener('keydown', this.hotKeys)
    this.$nextTick(() => {
      // want to add more text to this
      $('#required_text').replaceWith($('<span>' + document.getElementById('required_text').innerText + '</span>'))
    })
    this.updateLicenseVersions = updateLicenseVersions
    console.log(this.questionToEdit)
    this.sessionIdentifier = uuidv4()
    this.webworkAttachments = []
    await this.getGradingStyles()
    await this.getWebworkTemplateOptions()
    if (this.questionToEdit && Object.keys(this.questionToEdit).length !== 0) {
      await this.setQuestionToEdit()
    } else {
      await this.resetQuestionForm('assessment')
      this.initNativeType()
    }
    this.questionForm.source_url = this.questionForm.source_url ? this.questionForm.source_url : window.location.origin
    this.fullyMounted = true
  },
  destroyed () {
    if (this.questionToEdit) {
      axios.delete(`/api/current-question-editor/${this.questionToEdit.id}`)
    }
  },
  methods: {
    reloadQuestionRevisionDifferences (mathJaxRendered, diffsShown) {
      this.mathJaxRendered = mathJaxRendered
      this.diffsShown = diffsShown
      this.questionRevisionDifferencesKey++
    },
    initImageSize (type) {
      switch (type) {
        case ('pixels'):
          this.resizeWidth = Math.round(this.webworkImageOptions.width / 100 * this.resizeWidth)
          this.resizeHeight = Math.round(this.webworkImageOptions.height / 100 * this.resizeHeight)
          break
        case ('percentage'):
          this.resizeWidth = Math.round(100 * this.resizeWidth / this.webworkImageOptions.width)
          this.resizeHeight = Math.round(100 * this.resizeHeight / this.webworkImageOptions.height)
          break
        default:
          alert('Not a valid type of image size.')
      }
    },
    resetImageResize () {
      switch (this.resizeImageBy) {
        case ('pixels'):
          this.resizeWidth = this.webworkImageOptions.width
          this.resizeHeight = this.webworkImageOptions.height
          break
        case ('percentage'):
          this.resizeWidth = 100
          this.resizeHeight = 100
          break
        default:
          alert('Not a valid type of image size.')
      }
      this.initResizeWidth = this.resizeWidth
      this.initResizeHeight = this.resizeHeight
    },
    updateResizeHeight () {
      if (this.maintainAspectRatio) {
        this.resizeHeight = Math.round(this.resizeHeight * this.resizeWidth / this.initResizeWidth)
      }
    },
    updateResizeWidth () {
      if (this.maintainAspectRatio) {
        this.resizeWidth = Math.round(this.resizeWidth * this.resizeHeight / this.initResizeHeight)
      }
    },
    setWebworkAttachmentOptions (webworkAttachment) {
      this.webworkImageOptions.filename = webworkAttachment.filename
      this.webworkImageOptions.width = webworkAttachment.width
      this.webworkImageOptions.height = webworkAttachment.height
      this.resizeWidth = 100
      this.resizeHeight = 100
    },
    unrenderMathJax () {
      this.mathJaxRendered = false
      this.differences = []
      this.$nextTick(() => {
        this.compareRevisions()
        this.$forceUpdate()
      })
    },
    async initSaveQuestion () {
      this.questionForm.changes_are_topical = ''
      console.log(`Technology: ${this.questionForm.technology}`)
      if (!this.validateImagesHaveAlts()) {
        return false
      }
      this.questionForm.session_identifier = this.sessionIdentifier
      this.questionForm.source_url_required = true
      this.questionForm.framework_item_sync_question = this.frameworkItemSyncQuestion
      this.questionForm.webwork_attachments = this.webworkAttachments
      if (this.questionForm.source_url) {
        const withHttps = url => !/^https?:\/\//i.test(url) ? `https://${url}` : url
        this.questionForm.source_url = withHttps(this.questionForm.source_url)
      }
      if (this.originalPreexistingWebworkCode.length &&
        this.originalPreexistingWebworkCode === this.questionForm.webwork_code) {
        this.$noty.info('Please make some changes to the webWork code before saving it as your own.')
        return false
      }
      if (this.questionForm.technology === 'qti') {
        for (const key in this.questionForm) {
          if (key.includes('qti_')) {
            console.log(key)
            delete this.questionForm[key]
          }
        }
        switch (this.qtiQuestionType) {
          case ('highlight_table'):
            this.$forceUpdate()
            this.questionForm.colHeaders = this.qtiJson['colHeaders']
            this.questionForm.rows = this.qtiJson.rows
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('highlight_text'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.questionForm.responses = this.qtiJson.responses
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('drag_and_drop_cloze'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.questionForm.correct_responses = this.qtiJson.correctResponses
            this.questionForm.distractors = this.qtiJson.distractors
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('bow_tie'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.questionForm.actions_to_take = this.qtiJson.actionsToTake
            this.questionForm.potential_conditions = this.qtiJson.potentialConditions
            this.questionForm.parameters_to_monitor = this.qtiJson.parametersToMonitor
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('multiple_response_select_all_that_apply'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.questionForm.responses = this.qtiJson.responses
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('multiple_response_select_n'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.questionForm.responses = this.qtiJson.responses
            const regex = /\[[1-9]\d*]/
            let match = this.questionForm.qti_prompt.match(regex)
            this.qtiJson.numberToSelect = match ? match[0].replace('[', '').replace(']', '') : 0
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('matrix_multiple_response'):
          case ('multiple_response_grouping'):
          case ('matrix_multiple_choice'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            if (this.qtiQuestionType === 'matrix_multiple_response') {
              this.questionForm.colHeaders = this.qtiJson.colHeaders
            } else {
              this.questionForm.headers = this.qtiJson.headers
            }
            this.questionForm.rows = this.qtiJson.rows
            if (this.qtiQuestionType === 'multiple_response_grouping') {
              for (let i = 0; i < this.questionForm.rows.length; i++) {
                let row = this.questionForm.rows[i]
                for (let j = 0; j < row.responses.length; j++) {
                  let response = row.responses[j]
                  if (!response.hasOwnProperty('correctResponse')) {
                    response.correctResponse = false
                  }
                }
              }
            }
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('drop_down_table'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.questionForm.colHeaders = this.qtiJson.colHeaders
            this.questionForm.rows = this.qtiJson.rows
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('numerical'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.questionForm.correct_response = this.qtiJson.correctResponse.value
            this.questionForm.margin_of_error = this.qtiJson.correctResponse.marginOfError
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('matching'):
            this.$forceUpdate()
            this.questionForm.qti_prompt = this.qtiJson['prompt']
            this.qtiJson.termsToMatch = []
            this.qtiJson.possibleMatches = []
            let usedTermsToMatch = []
            console.log(this.termsToMatch)
            for (let i = 0; i < this.$refs.matching.termsToMatch.length; i++) {
              let item = this.$refs.matching.termsToMatch[i]
              if (!usedTermsToMatch.includes(item.termToMatch)) {
                this.questionForm[`qti_matching_term_to_match_${i}`] = item.termToMatch
                this.qtiJson.termsToMatch.push({
                  identifier: item.identifier,
                  termToMatch: item.termToMatch,
                  matchingTermIdentifier: this.$refs.matching.possibleMatches[i].identifier,
                  feedback: this.$refs.matching.termsToMatch[i].feedback
                })
                if (item.termToMatch !== '') {
                  usedTermsToMatch.push(item.termToMatch)
                }
              } else {
                this.$noty.error(`${item.termToMatch} appears multiple times as a term to match.`)
                return false
              }
            }

            let usedPossibleMatches = []
            let distractorIndex = 0
            let possibleMatchIndex = 0
            let key
            for (let i = 0; i < this.$refs.matching.possibleMatches.length; i++) {
              let item = this.$refs.matching.possibleMatches[i]
              let distractor = this.$refs.matching.matchingDistractors.find(distractor => distractor.identifier === item.identifier)
              if (!usedPossibleMatches.includes(item.matchingTerm)) {
                if (distractor) {
                  key = `qti_matching_distractor_${distractorIndex}`
                  item.matchingTerm = distractor.matchingTerm
                  distractorIndex++
                } else {
                  key = `qti_matching_matching_term_${possibleMatchIndex}`
                  possibleMatchIndex++
                }
                this.questionForm[key] = item.matchingTerm
                this.qtiJson.possibleMatches.push({
                  identifier: item.identifier,
                  matchingTerm: item.matchingTerm
                })
                if (item.matchingTerm !== '') {
                  usedPossibleMatches.push(item.matchingTerm)
                }
              } else {
                this.$noty.error(`${item.matchingTerm} appears multiple times as a potential matching term.`)
                return false
              }
            }

            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('multiple_answers'):
          case ('multiple_choice'):
          case ('true_false'):
            for (const property in this.questionForm) {
              if (property.startsWith('qti_simple_choice_')) {
                // clean up in case it's been deleted then recreate from the json below
                delete this.questionForm[property]
              }
            }

            this.questionForm.qti_prompt = this.qtiJson['prompt']
            let correctResponse = this.qtiJson.simpleChoice.find(choice => choice.correctResponse)
            if (!correctResponse) {
              this.$noty.error('Please choose at least one correct response before submitting.')
              return false
            }
            // delete this.qtiJson.feedbackEditorShown
            for (let i = 0; i < this.qtiJson.simpleChoice.length; i++) {
              // delete this.qtiJson.simpleChoice[i].editorShown
              this.questionForm[`qti_simple_choice_${i}`] = this.qtiJson.simpleChoice[i].value
            }
            switch (this.questionType) {
              case ('true_false'):
                this.qtiJson['language'] = this.trueFalseLanguage
                this.qtiJson['questionType'] = 'true_false'
                break
              case ('multiple_choice'):
                this.qtiJson['questionType'] = 'multiple_choice'
                break
              case ('multiple_answers'):
                this.qtiJson['questionType'] = 'multiple_answers'
                break
            }

            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
          case ('fill_in_the_blank'):
            this.questionForm.qti_item_body = this.qtiJson.itemBody
            this.questionForm.qti_text_entry_interactions = this.textEntryInteractions
            this.questionForm.uTags = this.$refs.fillInTheBlank.uTags
            this.qti_json = textEntryInteractionJson
            let qtiJson = {}
            qtiJson.responseDeclaration = {}
            qtiJson.responseDeclaration.correctResponse = this.$refs.fillInTheBlank.getFillInTheBlankResponseDeclarations()
            console.log(qtiJson)
            let textEntryInteraction = JSON.parse(JSON.stringify(this.qtiJson.itemBody.textEntryInteraction))
            console.log(textEntryInteraction)
            let textInteractionWithoutUnderlines = textEntryInteraction.replace(/\<u>(.*?)<\/u>/gm, function () {
              return '<u></u>'
            })
            qtiJson.itemBody = { textEntryInteraction: textInteractionWithoutUnderlines }
            qtiJson['questionType'] = 'fill_in_the_blank'
            this.questionForm.qti_json = JSON.stringify(qtiJson)
            break
          case ('drop_down_rationale_dyad'):
          case ('drop_down_rationale_triad'):
          case ('select_choice'):
            this.$forceUpdate()
            if (this.qtiJson.questionType === 'drop_down_rationale_dyad') {
              this.qtiJson.dropDownRationaleType = 'dyad'
              this.qtiJson.questionType = 'drop_down_rationale'
            }
            for (const selectChoice in this.qtiJson.inline_choice_interactions) {
              this.questionForm[`qti_select_choice_${selectChoice}`] = this.qtiJson.inline_choice_interactions[selectChoice]
            }
            this.questionForm['qti_item_body'] = this.qtiJson.itemBody
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
        }
      } else {
        this.questionForm.qti_json = null
      }
      const { data } = await axios.get('/api/questions/non-meta-properties')
      const nonMetaProperties = data.non_meta_properties
      if (this.isEdit) {
        if (this.powerUser) {
          this.revisionAction = ''
        } else {
          this.revisionAction = 'propagate'
          for (let i = 0; i < nonMetaProperties.length; i++) {
            const nonMetaProperty = nonMetaProperties[i]
            if (this.questionForm[nonMetaProperty] !== this.questionToEdit[nonMetaProperty]) {
              console.log(nonMetaProperty + ': ' + this.questionForm[nonMetaProperty] + ' --- ' + this.questionToEdit[nonMetaProperty])
              this.revisionAction = 'notify'
            }
          }
        }
        this.$bvModal.show('modal-reason-for-edit')
      } else {
        this.revisionAction = 'none'
        await this.saveQuestion()
      }
    },
    initCompareRevisions () {
      this.revision1Id = this.revision2Id = this.revisionOptions[0].value
      this.$bvModal.show('modal-compare-revisions')
    },
    increaseModalSize () {
      this.$nextTick(() => {
        document.getElementById('modal-compare-revisions').getElementsByClassName('modal-dialog')[0].style.maxWidth = '90%'
      })
    },
    compareRevisions () {
      this.revision1 = this.revisionOptions.find(revision => revision.value === this.revision1Id)
      this.revision2 = this.revisionOptions.find(revision => revision.value === this.revision2Id)
      this.questionRevisionDifferencesKey++
    },
    submitSaveAndPropagate () {
      if (!this.questionForm.changes_are_topical) {
        this.$noty.info('Please check the box stating that you agree that the changes are topical in nature.')
        return false
      }
      this.$bvModal.hide('modal-reason-for-edit')
      this.saveQuestion()
    },
    setNewQuestionToEdit (revision) {
      this.$emit('setQuestionRevision', revision)
    },
    async setQuestionToEdit () {
      if (this.user.role === 5) {
        await this.getCurrentQuestionEditor()
        await this.updateCurrentQuestionEditor()
        this.checkForOtherNonInstructorEditors()
      }
      this.isEdit = true
      this.powerUser = this.isMe
      console.log(this.questionToEdit)
      await this.getRevisions(this.questionToEdit)
      if (this.questionToEdit.technology === 'webwork' && this.questionToEdit.webwork_code) {
        await this.getWebworkAttachments()
      }
      this.questionForm.folder_id = this.questionToEdit.folder_id
      this.showFolderOptions = this.user.id === this.questionToEdit.question_editor_user_id
      await this.getFrameworkItemSyncQuestion()
      if (this.questionToEdit.learning_outcomes) {
        this.subject = this.questionToEdit.subject
        await this.getLearningOutcomes(this.subject)
      }
      if (this.questionToEdit.qti_json) {
        this.qtiJson = JSON.parse(this.questionToEdit.qti_json)
        if (this.nursingQuestions.includes(this.qtiJson.questionType)) {
          this.nativeType = this.nursingQuestions.includes(this.qtiJson.questionType) ? 'nursing' : 'basic'
        }
        if (this.qtiJson.dropDownCloze) {
          // made select_choice do double duty
          this.nativeType = 'nursing'
        }
        console.log(this.qtiJson.questionType)
        switch (this.qtiJson.questionType) {
          case ('drag_and_drop_cloze'):
            this.qtiQuestionType = this.qtiJson.questionType
            this.qtiPrompt = this.qtiJson['prompt']
            let correctResponses = []
            for (let i = 0; i < this.qtiJson.correctResponses.length; i++) {
              correctResponses.push(this.qtiJson.correctResponses[i].value)
            }
            let allSelects = String(this.qtiJson.prompt).split(/(\[.*?])/)
            let j = 0
            for (let i = 0; i < allSelects.length; i++) {
              if (allSelects[i] === '[select]') {
                allSelects[i] = '[' + correctResponses[j] + ']'
                j++
              }
            }
            this.qtiJson.prompt = allSelects.join('')
            break
          case ('highlight_text'):
          case ('highlight_table'):
          case ('matrix_multiple_choice'):
          case ('drop_down_rationale_dyad'):
          case ('drop_down_rationale_triad'):
          case ('drop_down_table'):
          case ('multiple_response_grouping'):
          case ('multiple_response_select_n'):
          case ('multiple_response_select_all_that_apply'):
          case ('bow_tie'):
            this.qtiQuestionType = this.qtiJson.questionType
            break
          case ('numerical'):
            this.qtiQuestionType = 'numerical'
            this.qtiPrompt = this.qtiJson['prompt']
            if (!this.qtiJson.feedback) {
              this.qtiJson.feedback = {}
            }
            break
          case ('matching'):
            this.qtiQuestionType = 'matching'
            this.qtiPrompt = this.qtiJson['prompt']
            break
          case ('true_false'):
          case ('multiple_choice'):
            let qtiQuestionType = this.qtiQuestionType
            this.qtiQuestionType = ''
            this.qtiPrompt = this.qtiJson['prompt']
            this.simpleChoices = this.qtiJson.simpleChoice
            this.qtiJson.feedbackEditorShown = {}

            if (typeof this.qtiJson.feedback === 'undefined' || JSON.stringify(this.qtiJson.feedback) === '[]') {
              this.qtiJson.feedback = {}
            }

            for (let i = 0; i < this.qtiJson.simpleChoice.length; i++) {
              this.qtiJson.simpleChoice[i].editorShown = false
              this.qtiJson.feedbackEditorShown[this.simpleChoices[i].identifier] = false
              if (!this.qtiJson.feedback[this.simpleChoices[i].identifier]) {
                this.qtiJson.feedback[this.simpleChoices[i].identifier] = ''
              }
            }
            this.qtiQuestionType = qtiQuestionType
            break
          case ('matrix_multiple_response'):
            this.qtiQuestionType = 'matrix_multiple_response'
            this.qtiPrompt = this.qtiJson['prompt']
            break
          case ('multiple_answers'):
            this.qtiQuestionType = 'multiple_answers'
            this.qtiPrompt = this.qtiJson['prompt']
            break
          case ('fill_in_the_blank'):
            this.qtiQuestionType = 'fill_in_the_blank'
            break
          case ('drop_down_rationale'):
            this.qtiQuestionType = `drop_down_rationale_${this.qtiJson.dropDownRationaleType}`
            this.qtiJson.questionType = this.qtiQuestionType
            break
          case ('select_choice'):
            this.qtiQuestionType = 'select_choice'
            break
          default:
            alert('Not a valid question type:' + this.qtiJson.questionType)
        }
      }
      if (this.nursingQuestions.includes(this.qtiJson.questionType)) {
        if (!this.qtiJson.feedback) {
          this.qtiJson.feedback = { correct: '', incorrect: '' }
        }
      }
      for (let i = 0; i < this.editorGroups.length; i++) {
        let editorGroup = this.editorGroups[i]
        switch (editorGroup.id) {
          case ('qti'):
            editorGroup.expanded = this.qtiPrompt
            break
          case ('technology'):
            editorGroup.expanded = this.questionToEdit.technology !== 'text'
            break
          case ('a11y_technology'):
            editorGroup.expanded = this.questionToEdit.a11y_technology
            break
          default:
            editorGroup.expanded = this.questionToEdit[editorGroup.id]
        }
      }
      switch (this.questionToEdit.technology) {
        case ('webwork'):
          if (this.questionToEdit.webwork_code) {
            this.newAutoGradedTechnology = 'webwork'
          } else {
            this.existingQuestionFormTechnology = 'webwork'
          }
          break
        case ('imathas'):
        case ('h5p'):
          this.existingQuestionFormTechnology = this.questionToEdit.technology
          break
        case ('qti'):
          this.newAutoGradedTechnology = 'qti'
          break
      }

      if (this.questionToEdit.webwork_code) {
        this.newAutoGradedTechnology = 'webwork'
        this.webworkEditorShown = true
        this.questionToEdit.new_auto_graded_code = 'webwork'
      }
      if (this.questionToEdit.license_version) {
        this.questionToEdit.license_version = Number(this.questionToEdit.license_version).toFixed(1) // some may be saved as 4 vs 4.0 in the database
      }
      this.questionForm = new Form(this.questionToEdit)
      this.questionFormTechnology = this.questionForm.technology
      console.log(this.questionForm)
      console.log(this.questionToEdit)
      this.updateLicenseVersions(this.questionForm.license)
      if (this.questionToEdit.tags.length === 1 && this.questionToEdit.tags[0] === 'none') {
        this.questionForm.tags = []
      }
    },
    async getRevisions (questionToEdit) {
      try {
        const { data } = await axios.get(`/api/question-revisions/question/${questionToEdit.id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.revisionOptions = []
        if (questionToEdit.id) {
          for (let i = 0; i < data.revisions.length; i++) {
            let revision = data.revisions[i]
            revision.value = revision.id
            this.revisionOptions.push(revision)
          }
          if (this.revisionOptions.length) {
            this.revision = questionToEdit.question_revision_id ? questionToEdit.question_revision_id : this.revisionOptions[0].value
          }
        }
        console.log('got the revisions')
        console.log(this.revisionOptions)
        console.log(this.revision)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getWebworkTemplateOptions () {
      try {
        const { data } = await axios.get('/api/webwork/templates')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.webworkTemplateOptions = [{
          text: 'Choose a template',
          value: null,
          template: ''
        }]
        for (let i = 0; i < data.webwork_templates.length; i++) {
          let webworkTemplate = data.webwork_templates[i]
          this.webworkTemplateOptions.push({
            text: webworkTemplate.title,
            value: webworkTemplate.id,
            template: webworkTemplate.webwork_code
          })
        }
        this.webworkTemplateOptions.push({ text: 'Pre-existing problem', value: 'pre-existing problem' })
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateQuestionFormRubricCategories (rubricCategories) {
      this.questionForm.rubric_categories = rubricCategories
      let totalPercent
      totalPercent = 0
      for (let i = 0; i < this.questionForm.rubric_categories.length; i++) {
        totalPercent += +this.questionForm.rubric_categories[i].percent
      }
      if (totalPercent === 100) {
        this.questionForm.errors.clear('rubric_categories')
      }
    },
    async getGradingStyles () {
      try {
        const { data } = await axios.get('/api/grading-styles')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.gradingStyleOptions = [{ text: 'Choose a grading style', value: null }]
        for (let i = 0; i < data.grading_styles.length; i++) {
          let gradingStyle = data.grading_styles[i]
          this.gradingStyleOptions.push({ text: gradingStyle.description, value: gradingStyle.id })
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initNativeType () {
      if (this.nativeType === 'nursing') {
        this.initNursingQuestion()
      } else {
        this.qtiQuestionType = 'multiple_choice'
        this.initQTIQuestionType('multiple_choice')
      }
    },
    async deleteWebworkAttachment () {
      try {
        const { data } = await axios.post('/api/webwork-attachments/destroy', {
          question_id: this.questionToEdit ? this.questionToEdit.id : 0,
          webwork_attachment: this.webworkAttachmentToDelete,
          question_revision_id: this.revision
        })
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.webworkAttachments = this.webworkAttachments.filter(attachment => attachment.filename !== this.webworkAttachmentToDelete.filename)
          this.$forceUpdate()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-confirm-delete-webwork-attachment')
    },
    confirmDeleteWebworkAttachment (webworkAttachment) {
      this.webworkAttachmentToDelete = webworkAttachment
      this.$bvModal.show('modal-confirm-delete-webwork-attachment')
    },
    async getWebworkAttachments () {
      try {
        const { data } = await axios.get(`/api/webwork-attachments/question/${this.questionToEdit.id}/${this.revision}`)
        if (data.type === 'error') {
          this.$noty.error(data.error.message)
          return false
        }
        this.webworkAttachments = data.webwork_attachments
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    copyWebworkImageCode () {
      let optionsArray = [`"${this.webworkImageOptions.filename}"`]
      if (this.resizeImageBy === 'percentage') {
        this.initImageSize('pixels')
      }
      if (this.resizeWidth) {
        optionsArray.push(`width => ${this.resizeWidth}`)
      }
      if (this.resizeHeight) {
        optionsArray.push(`height => ${this.resizeHeight}`)
      }
      if (this.webworkImageOptions.tex_size) {
        optionsArray.push(`tex_size => ${this.webworkImageOptions.tex_size}`)
      }

      if (this.webworkImageOptions.extra_html_tags) {
        optionsArray.push(`"extra_html_tags => '${this.webworkImageOptions.extra_html_tags}'"`)
      }
      let options = optionsArray.join(', ')

      let elem = document.createElement('input')
      document.body.appendChild(elem)

      elem.value = this.questionForm.webwork_code && this.questionForm.webwork_code.search('BEGIN_PGML') > 0 ? `[@ image( ${options} ) @]*` : `\\{ image( ${options} ) \\}`
      elem.select()
      elem.focus()
      document.execCommand('copy', false)
      elem.remove()
      this.$bvModal.hide('modal-webwork-image-options')
      this.$noty.success('Successfully copied!')
    },
    async uploadWebworkAttachment () {
      this.errorMessages = ''
      try {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        this.uploading = true
        let uploadWebworkAttachmentFormData = new FormData()
        uploadWebworkAttachmentFormData.append('file', this.webworkAttachmentsForm.attachment)
        uploadWebworkAttachmentFormData.append('_method', 'put') // add this
        uploadWebworkAttachmentFormData.append('session_identifier', this.sessionIdentifier)
        const { data } = await axios.post(`/api/webwork-attachments/upload`, uploadWebworkAttachmentFormData)
        if (data.type !== 'success') {
          this.errorMessages = data.message
        } else {
          this.webworkAttachmentsForm.attachment = []
          if (!this.webworkAttachments.find(attachment => attachment.filename === data.attachment.filename)) {
            this.webworkAttachments.push(data.attachment)
          } else {
            this.$noty.info(`${data.attachment.filename} has been overwritten with the newer version of the file.`)
          }
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)
      }
      this.uploading = false
    },
    async getFrameworkItemSyncQuestion () {
      try {
        const { data } = await axios.get(`/api/framework-item-sync-question/question/${this.questionToEdit.id}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
        }
        this.frameworkItemSyncQuestion = data.framework_item_sync_question
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    removeFrameworkItemSyncQuestion (itemType, itemId) {
      this.frameworkItemSyncQuestion[itemType] = this.frameworkItemSyncQuestion[itemType].filter(item => item.id !== itemId)
    },
    setFrameworkItemSyncQuestion (frameworkItemSyncQuestion) {
      this.frameworkItemSyncQuestion = frameworkItemSyncQuestion
    },
    getTextFromTechnology (technology) {
      let option = this.existingAutoGradedTechnologyOptions.find(item => item.value === technology)
      return option ? option.text : 'unknown technology'
    },
    initNursingQuestion () {
      let questionType = 'bow_tie'
      this.qtiQuestionType = questionType
      this.initQTIQuestionType(questionType)
      this.questionFormTechnology = 'qti'
      this.newAutoGradedTechnology = 'qti'
      this.questionForm.technology = 'qti'
      this.editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded = true
    },
    hotKeys (event) {
      if (event.key === 'Escape' && $('#modal-framework-aligner___BV_modal_content_').length) {
        this.$bvModal.hide('modal-framework-aligner')
        return
      }
      if (event.key === 'Escape' &&
        this.questionToEdit.id &&
        !$('#my-questions-question-to-view-questions-editor___BV_modal_content_').length) {
        // hack....just close if the preview isn't open.  For some reason, I couldn't get the edit modal to close
        this.$bvModal.hide(`modal-edit-question-${this.questionToEdit.id}`)
        return
      }
      if (event.ctrlKey) {
        switch (event.key) {
          case ('S'):
            if (this.isEdit) {
              this.initSaveQuestion()
            } else {
              this.revisionAction = 'none'
              this.saveQuestion()
            }
            break
          case ('V'):
            this.previewQuestion()
            break
        }
      }
    },
    async getQtiAnswerJson () {
      try {
        const { data } = await axios.post('/api/questions/qti-answer-json', { qti_json: JSON.stringify(this.qtiJson) })
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        console.log(data)
        this.qtiAnswerJson = data.qti_answer_json
        this.showQtiAnswer = true
        this.qtiJsonQuestionViewerKey++
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    checkForOtherNonInstructorEditors: function () {
      window.currentQuestionEditorUpdatedAt = setInterval(() => {
        this.updateCurrentQuestionEditor()
        if (!this.currentQuestionEditor) {
          this.getCurrentQuestionEditor()
        }
      }, 5000)
    },
    async updateCurrentQuestionEditor () {
      try {
        const { data } = await axios.patch(`/api/current-question-editor/${this.questionToEdit.id}`)
        if (data.type === 'error') {
          this.noty.error(data.message)
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCurrentQuestionEditor () {
      try {
        const { data } = await axios.get(`/api/current-question-editor/${this.questionToEdit.id}`)
        if (data.type === 'error') {
          this.noty.error(data.message)
          return false
        }
        this.currentQuestionEditor = data.current_question_editor
        if (this.currentQuestionEditor) {
          this.$bvModal.show('modal-current-question-editor')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateLearningOutcomes (subject) {
      let chosenLearningOutcomes = []
      for (let i = 0; i < this.questionForm.learning_outcomes.length; i++) {
        let chosenLearningOutcome = this.learningOutcomeOptions.find(learningOutcome => learningOutcome.id === this.questionForm.learning_outcomes[i])
        chosenLearningOutcomes.push(chosenLearningOutcome)
      }
      console.log(this.questionForm.learning_outcomes)
      console.log(chosenLearningOutcomes)
      this.learningOutcomeOptions = []
      this.getLearningOutcomes(subject)
      for (let i = 0; i < chosenLearningOutcomes.length; i++) {
        let chosenLearningOutcome = chosenLearningOutcomes[i]
        if (chosenLearningOutcome &&
          !this.learningOutcomeOptions.find(learningOutcomeOption => learningOutcomeOption.label === chosenLearningOutcome.label)) {
          this.learningOutcomeOptions.push(chosenLearningOutcome)
        }
      }
    },
    getLearningOutcomeLabel (chosenLearningOutcomeId) {
      if (this.learningOutcomeOptions.length) {
        let chosenLearningOutcome = this.learningOutcomeOptions.find(learningOutcome => learningOutcome.id === chosenLearningOutcomeId)
        if (chosenLearningOutcome) {
          return chosenLearningOutcome.label
        }
      }
    },
    async getDefaultSubject () {
      try {
        const { data } = await axios.get('/api/learning-outcomes/default-subject')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.subject = data.default_subject
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateTemplateWithPreexistingWebworkFilePath (filePath) {
      this.updatingTempalteWithPreexistingWebworkFilePath = true
      let parts = filePath.split('-')
      let questionId = parts.pop()
      try {
        const { data } = await axios.post('/api/questions/get-webwork-code-from-file-path', { file_path: filePath })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.updatingTempalteWithPreexistingWebworkFilePath = false
          return false
        }
        this.questionForm.webwork_code = data.webwork_code
        this.originalPreexistingWebworkCode = data.webwork_code
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$forceUpdate()
      this.updatingTempalteWithPreexistingWebworkFilePath = false
    },
    setWebworkTemplate (chosenOption) {
      this.showPreexistingWebworkFilePath = chosenOption === 'pre-existing problem'
      this.questionForm.webwork_code = this.webworkTemplateOptions.find(option => option.value === chosenOption).template
      this.$forceUpdate()
    },
    toggleGeneralFeedbackEditorShown (key, boolean) {
      this.generalFeedbacks.find(generalFeedback => generalFeedback.key === key).editorShown = boolean
      this.$forceUpdate()
    },
    initChangeExistingAutoGradedTechnology (technology) {
      this.questionForm.webwork_code = ''
      this.newAutoGradedTechnology = null
      this.showPreexistingWebworkFilePath = false
      this.preexisitingWebworkFilePath = ''
      this.webworkTemplate = null
      this.webworkEditorShown = false
      if (technology === 'text') {
        this.questionForm.a11y_technology = null
        this.questionForm.a11y_technology_id = ''
      }
      if (technology === 'qti') {
        if (this.questionForm.non_technology_text) {
          this.$noty.info('Please remove any Open-Ended Content before changing to Native.  You can always move your Open-Ended Content into the Prompt of your Native question.')
          this.questionFormTechnology = this.questionForm.technology
        } else {
          this.editorGroups.find(editorGroup => editorGroup.id === 'non_technology_text').expanded = false
          this.questionForm.technology = 'qti'
          this.qtiQuestionType = 'multiple_choice'
          this.initQTIQuestionType(this.qtiQuestionType)
        }
      } else {
        this.questionForm.technology = technology
      }
    },
    initQTIQuestionType (questionType) {
      this.questionForm.errors.clear()
      this.qtiJson = {}
      this.simpleChoices = []
      for (let i = 0; i < this.generalFeedbacks.length; i++) {
        this.generalFeedbacks[i].editorShown = false
      }
      switch (questionType) {
        case ('highlight_table'):
          this.qtiJson = {
            questionType: 'highlight_table',
            prompt: '',
            colHeaders: ['', ''],
            rows: [{
              header: '',
              prompt: '',
              responses: []
            }]
          }
          break
        case ('highlight_text'):
          this.qtiJson = {
            questionType: 'highlight_text',
            prompt: '',
            responses: []
          }
          break
        case ('drag_and_drop_cloze'):
          this.qtiJson = {
            questionType: 'drag_and_drop_cloze',
            prompt: '',
            correctResponses: [],
            distractors: [{ identifier: uuidv4(), value: '' }]
          }
          break
        case ('drop_down_table'):
          this.qtiJson = {
            questionType: 'drop_down_table',
            prompt: '',
            colHeaders: ['', ''],
            rows: [
              {
                header: '',
                selected: null,
                responses: [{ identifier: uuidv4(), value: '', correctResponse: true }]
              },
              {
                header: '',
                selected: null,
                responses: [{ identifier: uuidv4(), value: '', correctResponse: true }]
              }
            ]
          }
          break
        case ('multiple_response_grouping'):
          this.qtiJson = {
            questionType: 'multiple_response_grouping',
            prompt: '',
            headers: ['', ''],
            rows: [
              {
                grouping: '',
                responses: [{ identifier: uuidv4(), value: '', correctResponse: false }]
              },
              {
                grouping: '',
                responses: [{ identifier: uuidv4(), value: '', correctResponse: false }]
              }
            ]
          }
          break
        case ('matrix_multiple_choice'):
          this.qtiJson = {
            questionType: 'matrix_multiple_choice',
            prompt: '',
            headers: ['', '', ''],
            rows: [{ label: '', correctResponse: '' }]
          }
          break
        case ('matrix_multiple_response'):
          this.qtiJson = {
            questionType: 'matrix_multiple_response',
            prompt: '',
            colHeaders: ['', '', ''],
            rows: [{
              header: '',
              responses: [
                {
                  identifier: uuidv4(),
                  correctResponse: false
                }, {
                  identifier: uuidv4(),
                  correctResponse: false
                }
              ]
            }
            ]
          }
          break
        case ('multiple_response_select_n'):
          this.qtiJson = {
            questionType: 'multiple_response_select_n',
            prompt: '',
            numberToSelect: 0,
            responses: [{ identifier: uuidv4(), value: '', correctResponse: true },
              { identifier: uuidv4(), value: '', correctResponse: false }]
          }
          break
        case
          ('multiple_response_select_all_that_apply')
          :
          this.qtiJson = {
            questionType: 'multiple_response_select_all_that_apply',
            prompt: '',
            responses: [{ identifier: uuidv4(), value: '', correctResponse: true },
              { identifier: uuidv4(), value: '', correctResponse: false }]
          }
          break
        case
          ('bow_tie')
          :
          this.qtiJson = {
            questionType: 'bow_tie',
            actionsToTake: [{ identifier: uuidv4(), value: '', correctResponse: true },
              { identifier: uuidv4(), value: '', correctResponse: true }],
            potentialConditions: [{ identifier: uuidv4(), value: '', correctResponse: true }],
            parametersToMonitor: [{ identifier: uuidv4(), value: '', correctResponse: true },
              { identifier: uuidv4(), value: '', correctResponse: true }]
          }
          break
        case
          ('numerical')
          :
          this.qtiJson = {
            questionType: 'numerical',
            prompt: '',
            correctResponse: {
              value: '',
              marginOfError: '0'
            },
            feedback: {
              any: '',
              correct: '',
              incorrect: ''
            }
          }
          break
        case
          ('matching')
          :
          this.qtiJson = { questionType: 'matching' }
          this.qtiJson.prompt = {}
          this.qtiJson.termsToMatch = []
          this.qtiJson.possibleMatches = []
          break
        case
          ('multiple_answers')
          :
        case
          ('multiple_choice')
          :
          let qtiJson
          qtiJson = simpleChoiceJson
          qtiJson.prompt = ''
          qtiJson.feedback = {}
          this.qtiPrompt = ''
          qtiJson.simpleChoice = [
            {
              identifier: uuidv4(),
              value: '',
              correctResponse: false,
              editorShown: true
            },
            {
              identifier: uuidv4(),
              value: '',
              correctResponse: false,
              editorShown: true
            }
          ]
          if (qtiJson['language']) {
            delete qtiJson['language']
          }
          this.simpleChoices = qtiJson.simpleChoice
          if (questionType === 'multiple_choice') {
            qtiJson.feedbackEditorShown = {}
            for (let i = 0; i < this.simpleChoices.length; i++) {
              this.simpleChoices[i].editorShown = true
              qtiJson.feedbackEditorShown[this.simpleChoices[i].identifier] = false
            }
            console.log(qtiJson.feedbackEditorShown)
          }

          this.correctResponse = ''
          qtiJson.questionType = questionType
          this.qtiJson = qtiJson
          this.$forceUpdate()
          break
        case
          ('true_false')
          :
          this.qtiJson = simpleChoiceJson
          this.qtiJson.prompt = ''
          this.qtiPrompt = ''
          this.qtiJson['questionType'] = 'true_false'
          this.qtiJson.simpleChoice = [
            {
              identifier: 'adapt-qti-true',
              value: 'True',
              correctResponse: false
            },
            {
              identifier: 'adapt-qti-false',
              value: 'False',
              correctResponse: false
            }
          ]
          this.simpleChoices = this.qtiJson.simpleChoice
          this.correctResponse = ''
          break
        case
          ('fill_in_the_blank')
          :
          this.qtiJson = {
            questionType: 'fill_in_the_blank',
            itemBody: { textEntryInteraction: '' }
          }
          this.simpleChoices = []
          break
        case ('drop_down_rationale_dyad'):
        case ('drop_down_rationale_triad'):
        case ('select_choice'):
          let dropDownRationaleType
          if (questionType.includes('drop_down_rationale')) {
            dropDownRationaleType = questionType.replace('drop_down_rationale_', '')
            questionType = 'drop_down_rationale'
          }
          const isTriad = dropDownRationaleType === 'triad'
          this.qtiJson = {
            questionType: questionType,
            'responseDeclaration': {
              'correctResponse': []
            },
            'itemBody': '',
            'inline_choice_interactions': {}
          }
          if (isTriad) {
            console.log('triad')
            this.qtiJson['inline_choice_interactions'] = {
              condition: [{ value: uuidv4(), text: '', correctResponse: true }],
              rationales: [{ value: uuidv4(), text: '', correctResponse: true }, {
                value: uuidv4(),
                text: '',
                correctResponse: true
              }]
            }
            this.qtiJson['questionType'] = 'drop_down_rationale_triad'
          }
          if (questionType === 'drop_down_rationale') {
            this.qtiJson.dropDownRationaleType = dropDownRationaleType
          }
          this.qtiJson.dropDownCloze = this.nativeType === 'nursing'
          break
        default:
          alert(`Need to update the code for ${questionType}`)
      }
      if (this.nursingQuestions.includes(questionType) || this.qtiJson.dropDownCloze) {
        this.qtiJson.feedback = {
          correct: '',
          incorrect: ''
        }
      }
      this.qtiPrompt = ''
    },
    goto (refName) {
      let element = this.$refs[refName]
      let top = element.offsetTop

      window.scrollTo(0, top)
    },
    deleteQtiTechnology () {
      this.qtiJson = {}
      this.correctResponse = ''
      this.simpleChoices = []
      this.qtiPrompt = ''
      this.$bvModal.hide(`modal-confirm-delete-qti-${this.modalId}`)
      this.editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded = false
      this.questionForm.technology = 'text'
    },
    isCorrect (simpleChoice) {
      return this.correctResponse === simpleChoice.identifier
    },
    qtiType (qtiJson) {
      if (qtiJson.itemBody && !qtiJson.itemBody.simpleChoice) {

      }
    },
    toggleExpanded (id) {
      let editorGroup = this.editorGroups.find(group => group.id === id)
      if (id === 'non_technology_text' &&
        this.questionForm.technology === 'qti' &&
        !editorGroup.expanded) {
        this.$noty.info('For Native questions, please add any Open-Ended Content to the Prompt textarea.')
        return false
      }
      if (editorGroup && editorGroup.expanded) {
        switch (id) {
          case ('technology'):
            if (this.questionFormTechnology === 'qti') {
              this.$bvModal.show(`modal-confirm-delete-qti-${this.modalId}`)
              return false
            }
            if (this.questionForm.technology !== 'text') {
              this.$noty.info('If you would like to hide the auto-graded technology input area, make sure that no technology is chosen.')
              return false
            }
            break
          case ('a11y_technology'):
            if (this.questionForm.a11y_technology !== null) {
              this.$noty.info('If you would like to hide the accessible alternative technology input area, make sure that no a11y technology is chosen.')
              return false
            }
            break
          default:
            if (this.questionForm[id] && this.questionForm[id].length) {
              this.$noty.info(`If you would like to hide the ${editorGroup.label} input area, please first remove any text.`)
              return false
            }
        }
      }
      this.editorGroups.find(group => group.id === id).expanded = !editorGroup.expanded
    },
    openCreateAutoGradedTechnologyCode (value) {
      this.questionForm.new_auto_graded_code = false
      this.webworkEditorShown = false
      this.questionForm.a11y_technology = null
      this.questionForm.a11y_technology_id = ''
      this.existingQuestionFormTechnology = 'text'
      switch (value) {
        case ('webwork'):
          this.webworkEditorShown = true
          this.questionFormTechnology = 'webwork'
          this.questionForm.technology = 'webwork'
          this.questionForm.new_auto_graded_code = 'webwork'
          break
        case ('qti'):
          if (this.questionForm.non_technology_text) {
            this.newAutoGradedTechnology = null
            this.$noty.info('Please first remove any Open-Ended Content.  You can always place additional content in the Native question\'s Prompt.')
            return false
          }
          this.questionForm.technology = 'qti'
          this.editorGroups.find(group => group.id === 'non_technology_text').expanded = false
          break
        case (h5pUrl):
          this.questionForm.technology = 'h5p'
          this.createAutoGradedRedirectTechnology = 'h5p'
          this.$bvModal.show('modal-auto-graded-redirect')
          break
        case (imathASUrl):
          this.createAutoGradedRedirectTechnology = 'imathas'
          this.questionForm.technology = 'imathas'
          this.$bvModal.show('modal-auto-graded-redirect')
          break
        case null:
          this.questionForm.technology = 'text'
          return false
        default:
          alert(`${value} is not a valid option.`)
          break
      }
    },
    handleAutoGradedRedirect () {
      let redirectUrl = ''
      switch (this.createAutoGradedRedirectTechnology) {
        case ('h5p'):
          redirectUrl = h5pUrl
          break
        case ('imathas'):
          redirectUrl = imathASUrl
          break
        default:
          this.$noty.info(`${this.createAutoGradedRedirectTechnology} is not a valid technology for creating a new question.`)
          return false
      }
      this.$bvModal.hide('modal-auto-graded-redirect')
      window.open(redirectUrl, '_blank')
    },
    reloadCreateQuestionSavedQuestionsFolders (type) {
      this.savedQuestionsFolderKey++
    },
    setMyCoursesFolder (myCoursesFolder) {
      this.questionForm.folder_id = myCoursesFolder
      this.questionForm.errors.clear('folder_id')
    },
    async resetQuestionForm (questionType) {
      await this.getDefaultSubject()
      if (this.subject) {
        await this.getLearningOutcomes(this.subject)
      }
      let folderId
      folderId = this.questionForm.folder_id
      this.questionFormTechnology = 'text'
      this.qtiPrompt = ''
      this.simpleChoiceToRemove = {}
      this.correctResponse = ''
      this.simpleChoices = []
      this.qtiJson = {}
      this.questionForm.rubric_categories = []
      if (questionType === 'report') {
        this.questionForm.purpose = ''
        this.questionForm.grading_style_id = null
      }
      if (['exposition', 'report'].includes(questionType)) {
        this.questionForm.technology = this.questionFormTechnology = 'text'
        this.questionForm.technology_id = ''
        this.questionForm.non_technology_text = ''
        this.questionForm.text_question = null
        this.questionForm.a11y_technology = null
        this.questionForm.a11y_technology_id = ''
        this.questionForm.answer_html = null
        this.questionForm.solution_html = null
        this.questionForm.hint = null
      } else {
        if (this.isEdit) {
          // switching from exposition to assessment so it's OK!
        } else {
          this.questionForm = new Form(defaultQuestionForm)
          this.questionForm.source_url = window.location.origin
          this.webworkAttachments = []
          this.webworkAttachmentsForm = new Form({ attachment: [] })
          this.webworkEditorShown = false
          this.questionForm.author = this.user.first_name + ' ' + this.user.last_name
          this.newAutoGradedTechnology = null
          this.existingQuestionFormTechnology = 'text'
          for (let i = 0; i < this.editorGroups.length; i++) {
            this.editorGroups[i].expanded = false
          }
        }
      }
      if (this.nursing) {
        this.initNursingQuestion()
      }
      this.questionForm.question_type = questionType
      this.questionForm.folder_id = folderId
    },
    getQuestionType () {
      if (this.questionForm.question_type === 'auto_graded') {
        return 'Auto-Graded'
      } else if (this.questionForm.question_type === 'open_ended') {
        return 'Open-Ended'
      } else if (this.questionForm.question_type === 'frankenstein') {
        return 'Frankenstein'
      } else {
        return 'Question type not valid; please contact us.'
      }
    },
    validateImagesHaveAlts () {
      let imgNeedsAltSrc = false
      $('.cke_contents').each(function () {
        let images = $(this).find('iframe').contents().find('img')

        if ($(this).find('iframe').contents().find('img.cke_widget_element')) {
          images.each(function () {
            let html = $(this)[0].outerHTML
            if ($(this).attr('src', 'data:image/gif;base64,R0lGODlhAQABAPABAP///wAAACH5BAEKAAAALAAAAAABAAEAAAICRAEAOw==')) {
              // ignore the ckeditor handle resizer
              return
            }
            if (html.includes('alt=""') && !imgNeedsAltSrc) {
              const regex = /data-cke-saved-src="(.*?)"/g
              imgNeedsAltSrc = html.match(regex)
            }
          })
        }
      })
      if (imgNeedsAltSrc) {
        this.imgNeedsAltSrc = imgNeedsAltSrc[0].replaceAll('amp;', '').replace('data-cke-saved-src="', '').replace('"', '')
        this.$bvModal.show('modal-img-needs-alt')
        return false
      }
      return true
    },
    async previewQuestion () {
      if (!this.validateImagesHaveAlts()) {
        return false
      }
      if (this.questionForm.technology !== 'text' &&
        !this.questionForm.technology_id &&
        !this.questionForm.webwork_code &&
        this.questionForm.technology !== 'qti') {
        if (this.webworkEditorShown) {
          let message = `WebWork code is required to preview this question.`
          this.questionForm.errors.set('webwork_code', message)
        } else {
          let identifier = this.questionForm.technology === 'webwork' ? 'A File Path' : 'An ID'
          let message = `${identifier} is required to preview this question.`
          this.questionForm.errors.set('technology_id', message)
        }
        return false
      }
      this.processingPreview = true
      this.showQtiAnswer = false
      this.qtiJsonQuestionViewerKey++
      if (this.questionForm.webwork_code) {
        this.$forceUpdate()
        this.questionForm.session_identifier = this.sessionIdentifier
        this.questionForm.pending_webwork_attachments = this.webworkAttachments.filter(attachment => attachment.status === 'pending')
      }
      try {
        if (this.questionForm.technology !== 'qti') {
          const { data } = await this.questionForm.post('/api/questions/preview')
          this.questionToView = data.question
        } else {
          if (this.qtiQuestionType === 'matching') {
            this.qtiJson.termsToMatch = this.$refs.matching.termsToMatch
            console.log(this.qtiJson.termsToMatch)
            this.qtiJson.possibleMatches = this.$refs.matching.possibleMatches
            console.log(this.qtiJson.possibleMatches)
            if (this.$refs.matching.possibleMatches) {
              console.log(this.$refs.matching.possibleMatches)
              for (let i = 0; i < this.$refs.matching.matchingDistractors.length; i++) {
                console.log(this.$refs.matching.matchingDistractors[i])
                let matchingDistractor = this.$refs.matching.matchingDistractors[i]
                let possibleMatch = this.qtiJson.possibleMatches.find(possibleMatch => possibleMatch.identifier === matchingDistractor.identifier)
                if (possibleMatch) {
                  possibleMatch.matchingTerm = matchingDistractor.matchingTerm
                }
              }
            }
          }
          if (this.qtiQuestionType === 'fill_in_the_blank') {
            this.qtiJson.responseDeclaration = {}
            this.qtiJson.responseDeclaration.correctResponse = this.$refs.fillInTheBlank.getFillInTheBlankResponseDeclarations()
          }
          if (this.qtiQuestionType === 'drag_and_drop_cloze') {
            this.qtiJson.selectOptions = [{ value: null, text: 'Please choose an option' }]
            let responses = this.qtiJson.correctResponses.concat(this.qtiJson.distractors)
            for (let i = 0; i < responses.length; i++) {
              let response = responses[i]
              this.qtiJson.selectOptions.push({ value: response.identifier, text: response.value })
            }
          }
          this.$forceUpdate()
          this.questionToView = this.qtiJson
          if (this.questionToView.questionType !== 'drop_down_rationale_triad' &&
            this.qtiQuestionType.includes('drop_down_rationale')) {
            this.questionToView.questionType = 'drop_down_rationale'
          }
        }

        this.showQtiAnswer = false
        this.$bvModal.show(this.modalId)
        this.$nextTick(() => {
          MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        })
        console.log(this.questionToView)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingPreview = false
    },
    async saveQuestion () {
      try {
        this.savingQuestion = true
        this.questionForm.assignment_id = this.assignmentId
        if (this.isEdit) {
          this.questionForm.revision_action = this.revisionAction
        }
        const { data } = this.isEdit
          ? await this.questionForm.patch(`/api/questions/${this.questionForm.id}`)
          : await this.questionForm.post('/api/questions')
        if (data.type === 'error' &&
          (data.reason_for_edit_error || data.automatically_update_revision_error || data.changes_are_topical_error)) {
          if (data.reason_for_edit_error) {
            this.questionForm.errors.set('reason_for_edit', data.reason_for_edit_error)
          }
          if (data.automatically_update_revision_error) {
            this.questionForm.errors.set('automatically_update_revision', data.automatically_update_revision_error)
          }

          if (data.changes_are_topical_error) {
            this.questionForm.errors.set('changes_are_topical', data.changes_are_topical_error)
          }
          this.$bvModal.show('modal-reason-for-edit')
          this.savingQuestion = false
          return false
        }
        this.$noty[data.type](data.message)
        this.savingQuestion = false
        if (data.type === 'success') {
          if (this.assignmentId) {
            this.parentGetMyQuestions()
          }
          if (!this.isEdit) {
            this.goto('top-of-form')
          }
          if (this.$route.name !== 'empty_learning_tree_node') {
            await this.resetQuestionForm('assessment')
          }
          this.tag = ''
          this.questionForm.tags.length = 0
          if (this.isEdit) {
            this.$bvModal.hide(`modal-edit-question-${this.questionToEdit.id}`)
            this.parentGetMyQuestions()
          }
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          console.log(this.questionForm.errors)
          this.$nextTick(() => fixInvalid())
          let errors = JSON.parse(JSON.stringify(this.questionForm.errors)).errors
          let formattedErrors = []
          for (const property in errors) {
            console.log(errors[property])
            console.log(property)
            switch (property) {
              case ('rubric_categories'):
                formattedErrors.push('Please fix the Rubric errors.')
                break
              case ('qti_select_choice_condition'):
                formattedErrors.push('Please fix the Condition errors.')
                break
              case ('qti_select_choice_rationales'):
                formattedErrors.push('Please fix the Rationale errors.')
                break
              case ('distractors'):
                formattedErrors.push('Please fix the Distractor errors.')
                break
              case ('actions_to_take'):
                formattedErrors.push('Please fix the Actions To Take errors.')
                break
              case ('potential_conditions'):
                formattedErrors.push('Please fix the Potential Conditions errors.')
                break
              case ('parameters_to_monitor'):
                formattedErrors.push('Please fix the Parameters To Monitor errors.')
                break
              case ('headers'):
                formattedErrors.push('Please fix the table header errors.')
                break
              case ('rows'):
                formattedErrors.push('Please fix the row errors.')
                break
              case ('colHeaders'):
                formattedErrors.push('Please fix the column errors.')
                break
              case ('responses'):
                formattedErrors.push('Please fix the errors with the responses.')
                break
              default:
                formattedErrors.push(errors[property][0])
            }
          }
          this.allFormErrors = formattedErrors
          this.savingQuestion = false
          this.questionsFormKey++
          this.$nextTick(() => this.$bvModal.show(`modal-form-errors-questions-form-${this.questionsFormKey}`))
        }
      }
    },
    removeLearningOutcome (chosenLearningOutcome) {
      let chosenLearningOutcomeLabel = chosenLearningOutcome.label ? chosenLearningOutcome.label : this.getLearningOutcomeLabel(chosenLearningOutcome)

      this.questionForm.learning_outcomes = chosenLearningOutcome.id
        ? this.questionForm.learning_outcomes.filter(learningOutcome => learningOutcome.id !== chosenLearningOutcome.id)
        : this.questionForm.learning_outcomes.filter(learningOutcome => learningOutcome !== chosenLearningOutcome)
      this.$noty.info(`You have removed the learning outcome "<strong>${chosenLearningOutcomeLabel}</strong>"`)
    },
    removeTag (chosenTag) {
      this.questionForm.tags = this.questionForm.tags.filter(tag => tag !== chosenTag)
      this.$noty.info(`${chosenTag} has been removed.`)
    },
    addLearningOutcome (learningOutcome) {
      if (!this.questionForm.learning_outcomes.includes(learningOutcome.id)) {
        this.questionForm.learning_outcomes.push(learningOutcome.id)
      } else {
        this.$noty.info(`${learningOutcome.label} is already on your list of learning outcomes.`)
      }
      this.tag = ''
    },
    addTag () {
      if (!this.questionForm.tags.includes(this.tag)) {
        this.questionForm.tags.push(this.tag)
      } else {
        this.$noty.info(`${this.tag} is already on your list of tags.`)
      }
      this.tag = ''
    },
    fixEmptyParagraphs (type) {
      const emptyParagraph = '<p>&nbsp;</p>'
      console.log('removing empty paragraphs')
      switch (type) {
        case ('non_technology_text'):
          if (this.questionForm.non_technology_text) {
            console.log('non-technology-text')
            this.questionForm.non_technology_text = this.questionForm.non_technology_text.replaceAll(emptyParagraph, '')
            this.questionForm.non_technology_text = this.questionForm.non_technology_text.trim()
            console.log('Empty paragraphs in the non-technology text have been removed')
          }
          break
        case ('qti_json_item_body'):
          if (this.qtiJson.itemBody) {
            console.log('item body')
            this.qtiJson.itemBody = this.qtiJson.itemBody.replaceAll(emptyParagraph, '')
            this.qtiJson.itemBody = this.qtiJson.itemBody.trim()
            console.log('Empty paragraphs in the item body have been removed')
          }
          break
        case ('qti_json_prompt'):
          if (this.qtiJson.prompt) {
            console.log('item prompt')
            this.qtiJson.prompt = this.qtiJson.prompt.replaceAll(emptyParagraph, '')
            this.qtiJson.prompt = this.qtiJson.prompt.trim()
            console.log('Empty paragraphs in the prompt have been removed')
          }
      }
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    }
  }
}
</script>

<style scoped>
.numerical-input {
  width: 150px;
}

</style>
