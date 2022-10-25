<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="`modal-form-errors-questions-form-${questionsFormKey}`"/>
    <div v-if="questionExistsInAnotherInstructorsAssignment">
      <b-alert :show="true" class="font-weight-bold">
        <div v-if="isMe">
          Warning: This question exists in another instructor's assignment. As admin you may edit it.
        </div>
        <div v-else>
          This question exists in another instructor's assignment and cannot be edited.
        </div>
      </b-alert>
    </div>
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
      <img :src="imgNeedsAltSrc" alt="missing alterative text">
    </b-modal>
    <b-modal
      :id="`qti-select-choice-error-${modalId}`"
      title="Select Choice Identifier Error"
      hide-footer
    >
      <b-alert show variant="info">
        {{ selectChoiceIdentifierError }}
      </b-alert>
    </b-modal>
    <b-modal
      :id="`modal-confirm-delete-qti-${modalId}`"
      title="Confirm reset Native technology"
    >
      Hiding this area will delete the information associated with the Native technology. Are you sure you would like to
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
      <RequiredText/>
      Fields marked with the
      <font-awesome-icon v-if="!sourceExpanded" :icon="caretRightIcon" size="lg"/>
      icon contain expandable text areas.
    </div>
    <b-card border-variant="primary"
            header-bg-variant="primary"
            header-text-variant="white"
            class="mb-3"
    >
      <template #header>
        Meta-Information
        <QuestionCircleTooltip id="meta-information-tooltip" :icon-style="'color:#fff'"/>
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
          <has-error :form="questionForm" field="title"/>
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
                <QuestionCircleTooltip :id="'assessment-question-type-tooltip'"/>
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
                <QuestionCircleTooltip :id="'exposition-question-type-tooltip'"/>
                <b-tooltip target="exposition-question-type-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  An Exposition consists of source (text, video, simulation, any other html) without an auto-graded
                  component. They can be used in any of the non-root
                  nodes within Learning Trees.
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
            <QuestionCircleTooltip :id="'public-question-tooltip'"/>
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
          <span v-show="!showFolderOptions()" class="mt-2">
            The folder is set by the question owner.
          </span>
          <span v-show="showFolderOptions()">
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
            <has-error :form="questionForm" field="author"/>
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
          <has-error :form="questionForm" field="license"/>
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
          label="Source URL*"
        >
          <b-form-row>
            <b-form-input
              id="source_url"
              v-model="questionForm.source_url"
              size="sm"
              type="text"
              placeholder="Please leave blank if creating a Native ADAPT question or a question purely consisting of Open-Ended Content."
              :class="{ 'is-invalid': questionForm.errors.has('source_url') }"
              class="mt-2"
              @keydown="questionForm.errors.clear('source_url')"
            />
            <has-error :form="questionForm" field="source_url"/>
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
              <b-button size="sm" variant="secondary" class="mr-2" @click="removeTag(chosenTag)">{{
                  chosenTag
                }} x</b-button>
            </span>
          </div>
        </b-form-group>

        <b-form-group
          key="learning_outcome"
          label-for="learning_outcome"
          label-cols-sm="3"
          label-cols-lg="2"
        >
          <template v-slot:label>
            Learning Outcome
            <QuestionCircleTooltip :id="'learning-outcome-tooltip'"/>
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
        <QuestionCircleTooltip id="content-tooltip" :icon-style="'color:#fff'"/>
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
            Open-Ended Content (Optional)
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
        v-show="editorGroups.find(group => group.id === 'non_technology_text').expanded || questionForm.question_type === 'exposition'"
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
        @keydown="questionForm.errors.clear('non_technology_text')"
      />
      <has-error :form="questionForm" field="non_technology_text"/>
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
              <QuestionCircleTooltip id="existing-question-tooltip"/>
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
            <span style="margin-left:24px" class="mr-2">New  <QuestionCircleTooltip id="new-question-tooltip"/>
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
        <b-form-group>
          <div v-if="nursing">
            <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="bow_tie"
                          @change="initQTIQuestionType($event)"
            >
              Bow Tie
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
            <div v-show="false">
              <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drop_down_table"
                            @change="initQTIQuestionType($event)"
              >
                Drop-Down Table
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

              <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drop_down_rationale"
                            @change="initQTIQuestionType($event)"
              >
                Drop-Down Rationale
              </b-form-radio>
              <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drag_and_drop_cloze"
                            @change="initQTIQuestionType($event)"
              >
                Drag and Drop Cloze (accessible version) ---- TODO
              </b-form-radio>

              <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="multiple_response_grouping"
                            @change="initQTIQuestionType($event)"
              >
                Multiple Response Grouping
              </b-form-radio>
              <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="matrix_multiple_response"
                            @change="initQTIQuestionType($event)"
              >
                Matrix Multiple Response
              </b-form-radio>
            </div>
          </div>
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
          <b-form-radio v-if="user.email === 'atconsultantnc@gmail.com' || isMe"
                        v-model="qtiQuestionType"
                        name="qti-question-type"
                        value="matching"
                        @change="initQTIQuestionType($event)"
          >
            Matching
          </b-form-radio>
        </b-form-group>
        <div v-if="qtiQuestionType === 'select_choice'">
          <b-alert show variant="info">
            Using brackets, place a non-space-containing identifier to show where
            you want the select placed.
            Example. The [planet] is the closest planet to the sun; there are [number-of-planets] planets.
            Then, add the select choices below with your first choice being the correct response. Each student will
            receive a randomized ordering of the choices.
          </b-alert>
        </div>
        <div v-if="qtiQuestionType === 'drop_down_rationale'">
          <b-alert show variant="info">
            Using brackets, place a non-space-containing identifier to show where
            you want the select placed.
            Example. The client is at most risk for [disease] as evidenced by the client's [type-of-assessment].
            Then, add the select choices below with your first choice being the correct response. Each student will
            receive a randomized ordering of the choices.
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
            Bracket off the portions of the text where you would like the Drop-Down Cloze to occur, using a
            bracketed response
            to
            denote the correct answer. Then, add distractors below. Example: The client is at risk for developing
            [infection]
            and [seizures].
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
        <div v-if="qtiQuestionType === 'matrix_multiple_choice'">
          <b-alert show variant="info">
            Write a question prompt and then construct a table with one correct choice per row, selecting that choice by
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
                 'highlight_text'].includes(qtiQuestionType) && qtiJson"
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

        <div v-if="['drop_down_rationale','select_choice'].includes(qtiQuestionType)">
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
            @keydown="questionForm.errors.clear('qti_item_body')"
          />
          <has-error :form="questionForm" field="qti_item_body"/>
        </div>
        <SelectChoiceDropDownRationale v-if="['select_choice','drop_down_rationale'].includes(qtiQuestionType)"
                                       ref="selectChoiceDropDownRationale"
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
            v-if="['multiple_choice','numerical'].includes(qtiQuestionType) || nursingQuestions.includes(qtiQuestionType)"
            header="default"
          >
            <template #header>
              <span class="ml-2 h7">General Feedback</span>
            </template>
            <div v-for="(generalFeedback,index) in generalFeedbacks"
                 :key="`feedback-${generalFeedback.label}`"
            >
              <div v-if="generalFeedback.label !== 'Any Response' || !nursingQuestions.includes(qtiQuestionType)">
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
                    <span v-html="qtiJson.feedback[generalFeedback.key]"/>
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
            <span v-if="updatingTempalteWithPreexistingWebworkFilePath"><b-spinner small type="grow"/>
              Updating...
            </span>
          </b-button>
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-if="existingQuestionFormTechnology !== 'text'
          && !webworkEditorShown
          && questionForm.question_type === 'assessment'"
        label-cols-sm="2"
        label-cols-lg="1"
        label-for="technology_id"
        :label="existingQuestionFormTechnology === 'webwork' ? 'Path' : 'ID'"
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
          <has-error :form="questionForm" field="technology_id"/>
          <div class="ml-2">
            <a v-if="questionForm.technology === 'webwork' && questionForm.id"
               class="btn btn-sm btn-outline-primary link-outline-primary-btn"
               :href="`/api/questions/export-webwork-code/${questionForm.id}`"
            >
              <div style="margin-top:3px">Export webWork code</div>
            </a>
          </div>
        </b-form-row>
      </b-form-group>
      <div v-show="webworkEditorShown">
        <div class="mb-2">
          If you need to get help getting started, please visit <a href="https://webwork.maa.org/wiki/Authors"
                                                                   target="_blank"
        >https://webwork.maa.org/wiki/Authors</a>.
        </div>
        <b-textarea v-model="questionForm.webwork_code"
                    style="width:100%"
                    :class="{ 'is-invalid': questionForm.errors.has('webwork_code')}"
                    rows="10"
                    @keydown="questionForm.errors.clear('webwork_code')"
        />
        <has-error :form="questionForm" field="webwork_code"/>
      </div>
    </b-card>
    <b-card v-if="questionForm.question_type === 'assessment'"
            border-variant="primary"
            header-bg-variant="primary"
            header-text-variant="white"
            class="mb-3"
    >
      <template #header>
        Accessibility Alternatives
        <QuestionCircleTooltip id="accessibility-tooltip" :icon-style="'color:#fff'"/>
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

            <QuestionCircleTooltip id="text-question-tooltip"/>
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
          label-cols-sm="6"
          label-cols-lg="5"
          label-for="a11y_technology"
        >
          <template v-slot:label>
            <span style="cursor: pointer;" @click="toggleExpanded ('a11y_technology')">
              Auto-Graded Technology Alternative    <QuestionCircleTooltip id="a11y-auto-graded-tooltip"/>
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
            ADAPT will automatically create an accessible version of any native question that is not accessible in its
            original form.
          </b-alert>
          <div v-if="questionForm.a11y_technology !== 'qti'">
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="technology_id"
              :label="questionForm.a11y_technology === 'webwork' ? 'Path' : 'ID'"
            >
              <b-form-row>
                <b-form-input
                  id="a11y_technology_id"
                  v-model="questionForm.a11y_technology_id"
                  type="text"
                  :class="{ 'is-invalid': questionForm.errors.has('a11y_technology_id'), 'numerical-input' : questionForm.a11y_technology !== 'webwork' }"
                  @keydown="questionForm.errors.clear('a11y_technology_id')"
                />
                <has-error :form="questionForm" field="a11y_technology_id"/>
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
        <QuestionCircleTooltip id="supplemental-content-tooltip" :icon-style="'color:#fff'"/>
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
              <span v-if="editorGroup.label === 'Answer'"><QuestionCircleTooltip id="answer-tooltip"/>
                <b-tooltip target="answer-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  The answer to the question.  Answers are optional.
                </b-tooltip>
              </span>
              <span v-if="editorGroup.label === 'Solution'"><QuestionCircleTooltip id="solution-tooltip"/>
                <b-tooltip target="solution-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  A more detailed solution to the question. Solutions are optional.
                </b-tooltip>
              </span>
              <span v-if="editorGroup.label === 'Hint'"><QuestionCircleTooltip id="hint-tooltip"/>
                <b-tooltip target="hint-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  Hints can be provided to students within assignments. Hints are optional.
                </b-tooltip>
              </span>
              <span v-if="editorGroup.label === 'Notes'"><QuestionCircleTooltip id="notes-tooltip"/>
                <b-tooltip target="notes-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  Notes are for a way for the creator of the question to associate additional information with the question.  Students
                  will never see this information.  Notes are optional.
                </b-tooltip>
              </span>
              <font-awesome-icon v-if="!editorGroup.expanded" :icon="caretRightIcon" size="lg"/>
              <font-awesome-icon v-if="editorGroup.expanded" :icon="caretDownIcon" size="lg"/>
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
        <span v-if="processingPreview"><b-spinner small type="grow"/> </span>
        Preview
      </b-button>
      <b-button size="sm"
                variant="primary"
                @click="saveQuestion"
      >Save</b-button>
    </span>
    <b-container v-if="jsonShown" class="pt-4 mt-4">
      <b-row>{{ qtiJson }}</b-row>
    </b-container>
  </div>
</template>

<script>
import AllFormErrors from '~/components/AllFormErrors'
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
import { webworkTemplateOptions } from '~/helpers/WebworkTemplates'

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

let commonTechnologyOptions = [{ value: 'https://studio.libretexts.org/node/add/h5p', text: 'H5P' },
  { value: 'webwork', text: 'WeBWork' },
  { value: 'https://imathas.libretexts.org/imathas/course/moddataset.php', text: 'IMathAS' }]

for (let i = 0; i < commonTechnologyOptions.length; i++) {
  newAutoGradedTechnologyOptions.push(commonTechnologyOptions[i])
}
const multipleResponseRichEditorConfig = {
  toolbar: [
    { name: 'math', items: ['Mathjax'] },
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
  allowedContent: true
}

const matchingRichEditorConfig = {
  toolbar: [
    { name: 'image', items: ['Image'] },
    { name: 'math', items: ['Mathjax'] },
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
  extraPlugins: 'mathjax,embed,dialog,image2,contextmenu,autogrow',
  image2_alignClasses: ['image-align-left', 'image-align-center', 'image-align-right'],
  image2_altRequired: true,
  mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
  autoGrow_minHeight: 75,
  filebrowserUploadUrl: '/api/ckeditor/upload',
  filebrowserUploadMethod: 'form',
  allowedContent: true
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
  allowedContent: true
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
    webworkTemplateOptions: webworkTemplateOptions,
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
    selectChoiceIdentifierError: '',
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
  async mounted () {
    this.nursing = [1, 3279, 3280].includes(this.user.id)
    if (![2, 5].includes(this.user.role)) {
      return false
    }
    window.addEventListener('keydown', this.hotKeys)
    this.$nextTick(() => {
      // want to add more text to this
      $('#required_text').replaceWith($('<span>' + document.getElementById('required_text').innerText + '</span>'))
    })
    this.updateLicenseVersions = updateLicenseVersions
    console.log(this.questionToEdit)
    if (this.questionToEdit && Object.keys(this.questionToEdit).length !== 0) {
      if (this.user.role === 5) {
        await this.getCurrentQuestionEditor()
        await this.updateCurrentQuestionEditor()
        this.checkForOtherNonInstructorEditors()
      }
      this.isEdit = true
      if (this.questionToEdit.learning_outcomes) {
        this.subject = this.questionToEdit.subject
        await this.getLearningOutcomes(this.subject)
      }

      if (this.questionToEdit.qti_json) {
        this.qtiJson = JSON.parse(this.questionToEdit.qti_json)
        switch (this.qtiJson.questionType) {
          case ('highlight_table'):
            this.qtiQuestionType = this.qtiJson.questionType
            break
          case ('highlight_text'):
          case ('matrix_multiple_choice'):
          case ('drop_down_rationale'):
          case ('drag_and_drop_cloze'):
          case ('drop_down_table'):
          case ('multiple_response_grouping'):
          case ('multiple_response_select_n'):
          case ('multiple_response_select_all_that_apply'):
          case ('bow_tie'):
            this.qtiQuestionType = this.qtiJson.questionType
            this.qtiPrompt = this.qtiJson['prompt']
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
            this.qtiPrompt = this.qtiJson['prompt']
            this.simpleChoices = this.qtiJson.simpleChoice
            this.qtiJson.feedbackEditorShown = {}
            for (let i = 0; i < this.qtiJson.simpleChoice.length; i++) {
              this.qtiJson.simpleChoice[i].editorShown = false
              this.qtiJson.feedbackEditorShown[this.simpleChoices[i].identifier] = false
            }
            this.qtiQuestionType = this.qtiJson.questionType
            break
          case ('multiple_answers'):
            this.qtiQuestionType = 'multiple_answers'
            this.qtiPrompt = this.qtiJson['prompt']
            break
          case ('fill_in_the_blank'):
            this.qtiQuestionType = 'fill_in_the_blank'
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
      console.log(this.questionToEdit)
      this.questionFormTechnology = this.questionForm.technology
      console.log(this.questionForm)
      console.log(this.questionToEdit)
      this.updateLicenseVersions()
      if (this.questionToEdit.tags.length === 1 && this.questionToEdit.tags[0] === 'none') {
        this.questionForm.tags = []
      }
    } else {
      await this.resetQuestionForm('assessment')
      if (this.nursing) {
        this.initNursingQuestion()
      } else {
        this.initQTIQuestionType('multiple_choice')
      }
    }
  },
  destroyed () {
    if (this.questionToEdit) {
      axios.delete(`/api/current-question-editor/${this.questionToEdit.id}`)
    }
  },
  methods: {
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
      if (event.key === 'Escape' &&
        this.questionToEdit.id &&
        !$('#my-questions-question-to-view-questions-editor___BV_modal_content_').length) {
        // hack....just close if the preview isn't open.  For some reason, I couldn't get the edit modal to close
        this.$bvModal.hide(`modal-edit-question-${this.questionToEdit.id}`)
      }
      if (event.ctrlKey) {
        switch (event.key) {
          case ('S'):
            this.saveQuestion()
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
    showFolderOptions () {
      if (!this.isEdit) {
        return true
      }
      if (this.isMe) {
        return (this.user.id === 1 && this.questionToEdit.question_editor_user_id === 1) ||
          (this.user.id === 5 && this.questionToEdit.question_editor_user_id === 5)
      }
      if (this.user.role === 5) {
        return this.user.id === this.questionToEdit.question_editor_user_id
      }

      return true
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
            prompt: ''
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
            headers: ['', '', ''],
            rows: [['', false, false]]
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
        case ('multiple_response_select_all_that_apply'):
          this.qtiJson = {
            questionType: 'multiple_response_select_all_that_apply',
            prompt: '',
            responses: [{ identifier: uuidv4(), value: '', correctResponse: true },
              { identifier: uuidv4(), value: '', correctResponse: false }]
          }
          break
        case ('bow_tie'):
          this.qtiJson = {
            questionType: 'bow_tie',
            actionsToTake: [{ identifier: uuidv4(), value: '', correctResponse: true },
              { identifier: uuidv4(), value: '', correctResponse: true }],
            potentialConditions: [{ identifier: uuidv4(), value: '', correctResponse: true }],
            parametersToMonitor: [{ identifier: uuidv4(), value: '', correctResponse: true },
              { identifier: uuidv4(), value: '', correctResponse: true }]
          }
          break
        case ('numerical') :
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
        case ('matching') :
          this.qtiJson = { questionType: 'matching' }
          this.qtiJson.prompt = {}
          this.qtiJson.termsToMatch = []
          this.qtiJson.possibleMatches = []
          break
        case ('multiple_answers'):
        case ('multiple_choice'):
          this.qtiJson = simpleChoiceJson
          this.qtiJson.prompt = ''
          this.qtiJson.feedback = {}
          this.qtiPrompt = ''
          this.qtiJson.simpleChoice = [
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
          if (this.qtiJson['language']) {
            delete this.qtiJson['language']
          }
          this.simpleChoices = this.qtiJson.simpleChoice
          if (questionType === 'multiple_choice') {
            this.qtiJson.feedbackEditorShown = {}
            for (let i = 0; i < this.simpleChoices.length; i++) {
              this.simpleChoices[i].editorShown = true
              this.qtiJson.feedbackEditorShown[this.simpleChoices[i].identifier] = false
            }
            console.log(this.qtiJson.feedbackEditorShown)
          }

          this.correctResponse = ''
          this.qtiJson.questionType = questionType
          this.$forceUpdate()
          break
        case ('true_false'):
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
        case ('fill_in_the_blank'):
          this.qtiJson = {
            questionType: 'fill_in_the_blank',
            itemBody: { textEntryInteraction: '' }
          }
          this.simpleChoices = []
          break
        case ('drop_down_rationale'):
        case ('select_choice'):
          this.qtiJson = {
            questionType: questionType,
            'responseDeclaration': {
              'correctResponse': []
            },
            'itemBody': '',
            'inline_choice_interactions': {}
          }
          break
        default:
          alert(`Need to update the code for ${questionType}`)
      }
      if (this.nursingQuestions.includes(questionType)) {
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
      if (id === 'non_technology_text' && this.questionForm.technology === 'qti') {
        this.$noty.info('Please enter your Open-Ended Content within the Prompt textarea.')
        return false
      }
      let editorGroup = this.editorGroups.find(group => group.id === id)
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
      this.existingQuestionFormTechnology = 'text'
      switch (value) {
        case ('webwork'):
          this.webworkEditorShown = true
          this.questionFormTechnology = 'webwork'
          this.questionForm.technology = 'webwork'
          this.questionForm.new_auto_graded_code = 'webwork'
          break
        case ('qti'):
          this.questionForm.technology = 'qti'
          break
        case null:
          this.questionForm.technology = 'text'
          this.webworkEditorShown = false
          this.questionForm.a11y_technology = null
          this.questionForm.a11y_technology_id = ''
          return false
        default:
          window.open(value, '_blank')
          break
      }
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
      if (questionType === 'exposition') {
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
          this.$forceUpdate()
          this.questionToView = this.qtiJson
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
      console.log(`Technology: ${this.questionForm.technology}`)

      if (!this.validateImagesHaveAlts()) {
        return false
      }
      this.questionForm.source_url_required = true
      if ((this.questionFormTechnology === 'qti' || this.questionFormTechnology === 'text') && (!this.questionForm.source_url)) {
        this.questionForm.source_url = window.location.origin
      }
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
            this.questionForm.headers = this.qtiJson.headers
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
          case ('drop_down_rationale'):
          case ('select_choice'):
            this.$forceUpdate()
            for (const selectChoice in this.qtiJson.inline_choice_interactions) {
              this.questionForm[`qti_select_choice_${selectChoice}`] = this.qtiJson.inline_choice_interactions[selectChoice]
            }
            console.log(this.qtiJson)
            this.questionForm['qti_item_body'] = this.qtiJson.itemBody
            this.qtiJson['questionType'] = this.qtiQuestionType
            this.questionForm.qti_json = JSON.stringify(this.qtiJson)
            break
        }
      } else {
        this.questionForm.qti_json = null
      }
      try {
        const { data } = this.isEdit
          ? await this.questionForm.patch(`/api/questions/${this.questionForm.id}`)
          : await this.questionForm.post('/api/questions')
        this.$noty[data.type](data.message)
        if (data.type === 'success'
        ) {
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
          this.$nextTick(() => fixInvalid())
          let errors = JSON.parse(JSON.stringify(this.questionForm.errors)).errors
          let formattedErrors = []
          for (const property in errors) {
            switch (property) {
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
