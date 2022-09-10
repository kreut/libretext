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
      :id="`confirm-remove-simple-choice-${modalId}`"
      title="Confirm deleting response"
    >
      <p>Please confirm that you would like to delete the response:</p>
      <p class="text-center font-weight-bold">
        <span v-html="simpleChoiceToRemove.value"/>
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`confirm-remove-simple-choice-${modalId}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="deleteQtiResponse()"
        >
          Delete
        </b-button>
      </template>
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
      <QtiJsonQuestionViewer v-if="questionForm.technology === 'qti'"
                             :key="`qti-json-question-viewer-${qtiJsonQuestionViewerKey}`"
                             :qti-json="JSON.stringify(qtiJson)"
                             :show-submit="false"
                             :show-qti-answer="showQtiAnswer"
      />
      <ViewQuestions v-if="questionForm.technology !== 'qti'"
                     :key="questionToViewKey"
                     :question-to-view="questionToView"
      />
      <template #modal-footer>
        <b-button
          v-if="questionForm.technology === 'qti'"
          size="sm"
          class="float-right"
          @click="qtiJsonQuestionViewerKey++;showQtiAnswer = true"
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
    <div v-show="!nursing">
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
              Assessment
              <QuestionCircleTooltip :id="'assessment-question-type-tooltip'"/>
              <b-tooltip target="assessment-question-type-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                Assessments can be used within assignments as questions. In addition, if they are an auto-graded
                technology,
                they can be used as root nodes in Learning Trees. Regardless of whether they have an auto-graded
                technology, assessments can be used in non-root nodes of
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
    <div v-if="questionForm.question_type">
      <b-form ref="form">
        <div v-show="!nursing">
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
        <div v-show="!nursing">
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
        <div v-show="!nursing">
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
                placeholder="Please leave blank if creating a Native ADAPT question or a question purely consisting of Header HTML."
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
                             style="width:200px"
                             size="sm"
                             class="mr-2"
                             :options="subjectOptions"
                             @change="updateLearningOutcomes($event)"
              />
              <v-select :key="`subject-${subject}`"
                        v-model="learningOutcome"
                        style="width:685px"
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
                  chosenLearningOutcome.label ? chosenLearningOutcome.label : getLearningOutcomeLabel(chosenLearningOutcome)
                }} x
              </b-button>
            </div>
          </b-form-group>
          <b-form-group
            key="source"
            label-for="non_technology_text"
          >
            <template v-slot:label>
              <span style="cursor: pointer;" @click="toggleExpanded ('non_technology_text')">
                {{ questionForm.question_type === 'assessment' ? 'Header HTML (Optional)' : 'Header HTML*' }}
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
            v-show="editorGroups.find(group => group.id === 'non_technology_text').expanded"
            id="non_technology_text"
            ref="non_technology_text"
            v-model="questionForm.non_technology_text"
            tabindex="0"
            required
            :config="richEditorConfig"
            :class="{ 'is-invalid': questionForm.errors.has('non_technology_text')}"
            @namespaceloaded="onCKEditorNamespaceLoaded"
            @ready="handleFixCKEditor()"
            @keydown="questionForm.errors.clear('non_technology_text')"
          />
          <has-error :form="questionForm" field="non_technology_text"/>
        </div>
        <div v-if="questionForm.question_type === 'assessment'">
          <b-form-group
            label-cols-sm="3"
            label-cols-lg="2"
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
            <b-form-row v-if="editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded">
              <b-form-select
                v-model="questionFormTechnology"
                style="width:110px"
                title="technologies"
                size="sm"
                class="mt-2"
                :options="autoGradedTechnologyOptions"
                :aria-required="!isEdit"
                @change="initChangeAutoGradedTechnology($event)"
              />
              <b-form-select
                v-model="createAutoGradedTechnology"
                style="width:250px"
                title="auto-graded technologies"
                size="sm"
                class="mt-2 ml-3"
                :options="createAutoGradedTechnologyOptions"
                @change="openCreateAutoGradedTechnologyCode($event)"
              />
              <b-form-select
                v-if="webworkEditorShown"
                v-model="webworkTemplate"
                style="width:250px"
                title="webwork templates"
                size="sm"
                class="mt-2 ml-3"
                :options="webworkTemplateOptions"
                @change="setWebworkTemplate($event)"
              />
            </b-form-row>
          </b-form-group>
          <div v-if="questionForm.technology === 'qti'">
            <b-form-group label="Native Question Type">
              <div v-if="nursing">
                <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="matrix_multiple_choice"
                              @change="initQTIQuestionType($event)"
                >
                  Matrix Multiple Choice
                </b-form-radio>
                <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drop_down_rationale"
                              @change="initQTIQuestionType($event)"
                >
                  Drop-Down Rationale
                </b-form-radio>
                <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drag_and_drop_cloze"
                              @change="initQTIQuestionType($event)"
                >
                  Drag and Drop Cloze (accessible version)
                </b-form-radio>

                <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="drop_down_table"
                              @change="initQTIQuestionType($event)"
                >
                  Drop-Down Table
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
                <b-form-radio v-model="qtiQuestionType" name="qti-question-type" value="bow_tie"
                              @change="initQTIQuestionType($event)"
                >
                  Bow Tie
                </b-form-radio>
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
                Example. The [planet] is the closest planet to the sun; there are [number-of-planets]
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
            <div v-if="qtiQuestionType === 'fill_in_the_blank'">
              <b-alert show variant="info">
                Create a question with fill in the blanks by underlining
                the correct responses. Example. A <u>stitch</u> in time saves <u>nine</u>.
              </b-alert>
            </div>

            <div v-if="qtiQuestionType === 'true_false'">
              <b-alert show variant="info">
                Create a question prompt and then select either True or False for the correct answer.
              </b-alert>
            </div>
            <div v-if="qtiQuestionType === 'multiple_choice'">
              <b-alert show variant="info">
                Create a question prompt and then create a selection of answers, choosing the correct answer from the
                list. Students will receive a shuffled ordering of the selection.
                Optionally provide feedback at the individual question level or general feedback for a correct
                response, an incorrect response, or any response.
              </b-alert>
            </div>

            <div v-if="qtiQuestionType === 'numerical'">
              <b-alert show variant="info">
                Create a question prompt which requires a numerical response, specifying the margin of error accepted in
                the response.
                Optionally provide general feedback for a correct response, an incorrect response, or any response.
              </b-alert>
            </div>
            <div
              v-if="['matching',
                     'multiple_answers',
                     'true_false',
                     'multiple_choice',
                     'multiple_answers',
                     'numerical',
                     'multiple_response_select_all_that_apply',
                     'multiple_response_select_n',
                     'matrix_multiple_response',
                     'multiple_response_grouping',
                     'drop_down_table',
                     'drag_and_drop_cloze',
                     'matrix_multiple_choice'].includes(qtiQuestionType) && qtiJson"
            >
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
            </div>
            <DragAndDropCloze v-if="qtiQuestionType === 'drag_and_drop_cloze'"
                              ref="dragAndDropCloze"
                              :qti-json="qtiJson"
            />

            <MatrixMultipleChoice v-if="qtiQuestionType === 'matrix_multiple_choice'"
                                  ref="dropDownTable"
                                  :qti-json="qtiJson"
            />
            <DropDownTable v-if="qtiQuestionType === 'drop_down_table'"
                           ref="dropDownTable"
                           :qti-json="qtiJson"
            />

            <MatrixMultipleResponse v-if="qtiQuestionType === 'matrix_multiple_response'"
                                    ref="matrixMultipleResponse"
                                    :qti-json="qtiJson"
            />
            <MultipleResponseGrouping v-if="qtiQuestionType === 'multiple_response_grouping'"
                                      ref="multipleResponseGrouping"
                                      :qti-json="qtiJson"
            />

            <BowTie v-if="qtiQuestionType === 'bow_tie'"
                    ref="bowTie"
                    :qti-json="qtiJson"
            />
            <MultipleResponseSelectAllThatApply
              v-if="['multiple_response_select_all_that_apply','multiple_response_select_n'].includes(qtiQuestionType)"
              ref="multipleResponseSelectAllThatApply"
              :qti-json="qtiJson"
            />

            <div v-if="qtiQuestionType === 'numerical'">
              <b-form-group
                label-cols-sm="3"
                label-cols-lg="2"
                label-for="numerical_correct_response"
                label="Correct Response"
              >
                <b-form-row>
                  <b-form-input
                    id="numerical_correct_response"
                    v-model="qtiJson.correctResponse.value"
                    type="text"
                    :class="{ 'is-invalid': questionForm.errors.has('correct_response')}"
                    style="width:100px"
                    @keydown="questionForm.errors.clear('correct_response')"
                  />
                  <has-error :form="questionForm" field="correct_response"/>
                </b-form-row>
              </b-form-group>

              <b-form-group
                label-cols-sm="3"
                label-cols-lg="2"
                label-for="numerical_correct_response_margin_of_error"
                label="Margin of Error"
              >
                <b-form-row>
                  <b-form-input
                    id="numerical_correct_response_margin_of_error"
                    v-model="qtiJson.correctResponse.marginOfError"
                    style="width:100px"
                    type="text"
                    :class="{ 'is-invalid': questionForm.errors.has('margin_of_error')}"
                    @keydown="questionForm.errors.clear('margin_of_error')"
                  />
                  <has-error :form="questionForm" field="margin_of_error"/>
                </b-form-row>
              </b-form-group>
              <div
                v-if="qtiJson.correctResponse.marginOfError !== ''
                  && qtiJson.correctResponse.value !== ''
                  && !isNaN(qtiJson.correctResponse.marginOfError)
                  && qtiJson.correctResponse.marginOfError >0
                  && !isNaN(qtiJson.correctResponse.value)"
                class="mb-3"
              >
                Responses between {{
                  parseFloat(qtiJson.correctResponse.value) - parseFloat(qtiJson.correctResponse.marginOfError)
                }}
                and {{
                  parseFloat(qtiJson.correctResponse.value) + parseFloat(qtiJson.correctResponse.marginOfError)
                }} will be market as correct.
              </div>
            </div>
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
            <div v-if="qtiQuestionType === 'fill_in_the_blank'">
              <ckeditor
                id="qtiItemBodyTextEntryInteraction"
                :key="`question-type-${qtiQuestionType}`"
                v-model="qtiJson.itemBody.textEntryInteraction"
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
            <table
              v-if="['select_choice','drop_down_rationale'].includes(qtiQuestionType) && qtiJson.inline_choice_interactions"
              class="table table-striped"
            >
              <thead>
              <tr>
                <th scope="col">
                  Identifier
                </th>
                <th scope="col">
                  Choices
                </th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(selectChoice,index) in selectChoices" :key="`selectChoices-${index}`">
                <td>
                  {{ selectChoice }}
                  <input type="hidden" class="form-control is-invalid">
                  <div class="help-block invalid-feedback">
                    <span v-html="questionForm.errors.get(`qti_select_choice_${selectChoice}`)"/>
                  </div>
                </td>
                <td>
                  <ul v-for="(choice, choiceIndex) in qtiJson.inline_choice_interactions[selectChoice]"
                      :key="`selectChoice-${choiceIndex}`"
                      style="padding-left:0"
                  >
                    <li v-if="qtiJson.inline_choice_interactions[selectChoice][choiceIndex]" style="list-style:none;">
                      <div class="pb-2">
                          <span v-if="choice.correctResponse">
                            Correct Response</span>
                        <span v-if="choiceIndex > 0" class="pr-3">Distractor {{ choiceIndex }}
                            <b-icon-trash scale="1.1"
                                          @click="deleteChoiceFromSelectChoice(selectChoice,choice)"
                            /></span>
                      </div>
                      <b-form-input
                        id="title"
                        v-model="qtiJson.inline_choice_interactions[selectChoice][choiceIndex].text"
                        type="text"
                        :placeholder="choiceIndex === 0 ? 'Please enter the correct response.' : 'Please enter a value.'"
                        :class="{'text-success' : choiceIndex === 0 }"
                        required
                      />
                      <has-error :form="questionForm" field="title"/>
                    </li>
                  </ul>
                  <b-button size="sm" variant="outline-primary" @click="addChoiceToSelectChoice(selectChoice)">
                    Add Distractor
                  </b-button>
                </td>
              </tr>
              </tbody>
            </table>
            <table v-if="qtiQuestionType === 'fill_in_the_blank'" class="table table-striped">
              <thead>
              <tr>
                <th scope="col">
                  Correct Response
                </th>
                <th scope="col">
                  Matching Type
                  <QuestionCircleTooltip :id="'matching-type-tooltip'"/>
                  <b-tooltip target="matching-type-tooltip"
                             delay="250"
                             triggers="hover focus"
                  >
                    Example. 'the city' would be considered correct if the answer really is 'the city' if you choose
                    Exact. If you choose Substring and student
                    submits 'city'.
                  </b-tooltip>
                </th>
                <th scope="col">
                  Case Sensitive
                  <QuestionCircleTooltip :id="'case-sensitive-tooltip'"/>
                  <b-tooltip target="case-sensitive-tooltip-tooltip"
                             delay="250"
                             triggers="hover focus"
                  >
                    Example. 'new york' would be correct if the correct answer is 'New York' and you choose 'no' for
                    Case Sensitive. Otherwise, it would be
                    considered incorrect.
                  </b-tooltip>
                </th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(uTag,index) in uTags" :key="`uTag-${index}`">
                <td>{{ uTag }}</td>
                <td>
                  <b-form-radio v-model="textEntryInteractions[index].matchingType" :name="`matching_type-${index}`"
                                value="exact"
                  >
                    Exact
                  </b-form-radio>
                  <b-form-radio v-model="textEntryInteractions[index].matchingType" :name="`matching_type-${index}`"
                                value="substring"
                  >
                    Substring
                  </b-form-radio>
                </td>
                <td>
                  <b-form-radio v-model="textEntryInteractions[index].caseSensitive" :name="`case_sensitive-${index}`"
                                value="no"
                  >
                    No
                  </b-form-radio>
                  <b-form-radio v-model="textEntryInteractions[index].caseSensitive" :name="`case_sensitive-${index}`"
                                value="yes"
                  >
                    Yes
                  </b-form-radio>
                </td>
              </tr>
              </tbody>
            </table>
            <div v-if="qtiQuestionType === 'matching'">
              <ul class="pt-2 pl-0">
                <li v-for="(item, index) in termsToMatch" :key="`terms-to-match-${item.identifier}`"
                    style="list-style: none;" class="pb-3"
                >
                  <b-card header="default">
                    <template #header>
                      <span class="ml-2 h7">Matching {{ index + 1 }}</span>
                      <span class="float-right"><b-icon-trash scale="1.5" @click="deleteMatchingTerm(item.identifier)"/></span>
                    </template>
                    <b-card-text>
                      <b-row>
                        <b-col>
                          <b-form-group
                            :label-for="`qti_matching_term_to_match_${index}`"
                            class="mt-3"
                          >
                            <template v-slot:label>
                              Term to match
                            </template>
                            <ckeditor
                              :id="`qti_matching_term_to_match_${index}`"
                              v-model="item.termToMatch"
                              tabindex="0"
                              :config="matchingRichEditorConfig"
                              @namespaceloaded="onCKEditorNamespaceLoaded"
                              @ready="handleFixCKEditor()"
                            />
                            <input type="hidden" class="form-control is-invalid">
                            <div class="help-block invalid-feedback">
                              {{ questionForm.errors.get(`qti_matching_term_to_match_${index}`) }}
                            </div>
                          </b-form-group>
                        </b-col>
                        <b-col>
                          <b-form-group
                            :label-for="`qti_matching_matching_term_${index}`"
                            class="mt-3"
                          >
                            <template v-slot:label>
                              Matching term
                            </template>
                            <ckeditor
                              :id="`qti_matching_matching_term_${index}`"
                              v-model="possibleMatches.find(possibleMatch => possibleMatch.identifier === item.matchingTermIdentifier).matchingTerm"
                              tabindex="0"
                              :config="matchingRichEditorConfig"
                              @namespaceloaded="onCKEditorNamespaceLoaded"
                              @ready="handleFixCKEditor()"
                            />
                            <input type="hidden" class="form-control is-invalid">
                            <div class="help-block invalid-feedback">
                              {{ questionForm.errors.get(`qti_matching_matching_term_${index}`) }}
                            </div>
                          </b-form-group>
                        </b-col>
                      </b-row>
                      <b-form-group
                        :label-for="`qti_matching_feedback_${index}`"
                        class="mt-3"
                      >
                        <template v-slot:label>
                          Feedback (Optional)
                        </template>
                        <ckeditor
                          :id="`qti_matching_feedback_${index}`"
                          v-model="item.feedback"
                          tabindex="0"
                          :config="matchingRichEditorConfig"
                          @namespaceloaded="onCKEditorNamespaceLoaded"
                          @ready="handleFixCKEditor()"
                        />
                      </b-form-group>
                    </b-card-text>
                  </b-card>
                </li>
              </ul>
              <div v-if="matchingDistractors.length">
                <hr>
                <ul class="pt-2 pl-0">
                  <li v-for="(item, index) in matchingDistractors" :key="`terms-to-match-${item.identifier}`"
                      style="list-style: none;" class="pb-3"
                  >
                    <b-alert show variant="secondary">
                      <span class="ml-2 h7">Distractor {{ index + 1 }}</span>
                      <span class="float-right"><b-icon-trash scale="1.5" @click="deleteDistractor(item.identifier)"
                      /></span>
                    </b-alert>
                    <b-form-group>
                      <ckeditor
                        :id="`qti_matching_distractor_${index}`"
                        v-model="item.matchingTerm"
                        tabindex="0"
                        :config="matchingRichEditorConfig"
                        @namespaceloaded="onCKEditorNamespaceLoaded"
                        @ready="handleFixCKEditor()"
                      />
                      <input type="hidden" class="form-control is-invalid">
                      <div class="help-block invalid-feedback">
                        {{ questionForm.errors.get(`qti_matching_distractor_${index}`) }}
                      </div>
                    </b-form-group>
                  </li>
                </ul>
              </div>
              <span class="mr-2">
                <b-button variant="primary"
                          size="sm"
                          @click="addQTIMatchingItem"
                >
                  <span v-if="addingMatching"><b-spinner small type="grow"/>
                    Adding...
                  </span> <span v-if="!addingMatching">Add Matching</span>
                </b-button>
              </span>
              <b-button size="sm" @click="addQTIMatchingDistractor">
                <span v-if="addingDistractor"><b-spinner small type="grow"/>
                  Adding...
                </span> <span v-if="!addingDistractor">
                  Add Distractor</span>
              </b-button>
            </div>
            <div v-if="qtiQuestionType === 'multiple_answers'">
              <ul class="pt-2 pl-0">
                <li v-for="(simpleChoice, index) in simpleChoices" :key="simpleChoice.identifier"
                    style="list-style: none;" class="pb-3"
                >
                  <span v-show="false" class="aaa">{{ simpleChoice.identifier }} {{
                      simpleChoice.value
                    }}
                  </span>
                  <b-card header="default">
                    <template #header>
                      <h2 class="h7">
                        <span>
                          <span @click="toggleMultipleAnswersCorrectResponse(simpleChoice)">
                            <b-icon-square v-show="!simpleChoice.correctResponse" scale="1.5"/>
                            <b-icon-check-square-fill v-show="simpleChoice.correctResponse"
                                                      scale="1.5" class="text-success"
                            />
                            <span class="ml-2">Response {{ index + 1 }}</span>
                          </span>
                          <span class="float-right">
                            <b-icon-trash scale="1.5" @click="initDeleteQtiResponse(simpleChoice)"/>
                          </span>
                        </span>
                      </h2>
                    </template>
                    <b-card-text>
                      <b-form-group
                        :label-for="`qti_simple_choice_${index}`"
                        class="mb-0"
                      >
                        <template v-slot:label>
                          Text
                        </template>
                        <ckeditor
                          :id="`qti_simple_choice_${index}`"
                          v-model="simpleChoice.value"
                          tabindex="0"
                          :config="multipleResponseRichEditorConfig"
                          @namespaceloaded="onCKEditorNamespaceLoaded"
                          @ready="handleFixCKEditor()"
                          @input="questionForm.errors.clear(`qti_simple_choice_${index}`)"
                        />
                        <input type="hidden" class="form-control is-invalid">
                        <div class="help-block invalid-feedback">
                          {{ questionForm.errors.get(`qti_simple_choice_${index}`) }}
                        </div>
                      </b-form-group>
                      <b-form-group
                        :label-for="`qti_feedback_${index}`"
                        class="mt-3"
                      >
                        <template v-slot:label>
                          Feedback (Optional)
                        </template>
                        <ckeditor
                          :id="`qti_feedback_${index}`"
                          v-model="simpleChoice.feedback"
                          tabindex="0"
                          :config="matchingRichEditorConfig"
                          @namespaceloaded="onCKEditorNamespaceLoaded"
                          @ready="handleFixCKEditor()"
                        />
                      </b-form-group>
                    </b-card-text>
                  </b-card>
                </li>
                <li style="list-style: none;" class="pt-3">
                  <b-row>
                    <b-col sm="10">
                      <b-button size="sm" variant="info"
                                @click="addQtiResponse"
                      >
                        Add Response
                      </b-button>
                      <span v-show="false">{{ qtiJson }}</span>
                    </b-col>
                  </b-row>
                </li>
              </ul>
            </div>

            <div v-if="['true_false','multiple_choice'].includes(qtiQuestionType)">
              <b-form-group
                v-if="qtiQuestionType === 'true_false'"
                label-cols-sm="2"
                label-cols-lg="1"
                label-for="true_false_language"
                label="Language"
              >
                <b-form-row>
                  <b-form-select
                    id="true_false_language"
                    v-model="trueFalseLanguage"
                    style="width:100px"
                    title="true/false language"
                    size="sm"
                    inline
                    class="mt-2"
                    :options="trueFalseLanguageOptions"
                    @change="translateTrueFalse($event)"
                  />
                </b-form-row>
              </b-form-group>

              <ul v-for="(simpleChoice, index) in simpleChoices"
                  :key="simpleChoice.identifier"
                  class="pt-2 pl-0"
              >
                <li style="list-style: none;">
                  <span v-show="false" class="aaa">{{ simpleChoice.identifier }} {{
                      simpleChoice.value
                    }}
                  </span>
                  <b-row v-if="qtiQuestionType==='true_false'">
                    <b-col sm="1"
                           align-self="center"
                           class="text-right"
                           @click="updateCorrectResponse(simpleChoice)"
                    >
                      <b-icon-check-circle-fill v-show="simpleChoice.correctResponse"
                                                scale="1.5" class="text-success"
                      />
                      <b-icon-circle v-show="!simpleChoice.correctResponse" scale="1.5"/>
                    </b-col>
                    <b-col style="padding:0;margin-top:5px">
                      <b-form-group
                        v-if="qtiQuestionType==='true_false'"
                        :label-for="`qti_simple_choice_${index}`"
                        class="mb-0"
                      >
                        <template v-slot:label>
                          <span style="font-size:1.25em;">
                            {{ simpleChoice.value }}</span>
                        </template>
                        <input type="hidden" class="form-control is-invalid">
                        <div class="help-block invalid-feedback">
                          {{ questionForm.errors.get(`qti_simple_choice_${index}`) }}
                        </div>
                      </b-form-group>
                    </b-col>
                  </b-row>
                  <b-card v-if="qtiQuestionType ==='multiple_choice'" header="default">
                    <template #header>
                      <div>
                        <span @click="updateCorrectResponse(simpleChoice)">
                          <b-icon-check-circle-fill v-show="simpleChoice.correctResponse"
                                                    scale="1.5" class="text-success"
                          />
                          <b-icon-circle v-show="!simpleChoice.correctResponse" scale="1.5"/>
                        </span>
                        <span class="ml-2 h6">Response {{ index + 1 }}</span>
                        <span class="float-right">
                          <b-icon-trash scale="1.5" @click="initDeleteQtiResponse(simpleChoice)"/></span>
                      </div>
                    </template>
                    <ul class="pl-0" style="list-style:none;">
                      <li>
                        <b-form-group
                          :label-for="`qti_simple_choice_${index}`"
                          class="mb-0"
                        >
                          <template v-slot:label>
                            <span class="font-weight-bold">Text</span>
                            <b-icon
                              :variant="simpleChoice.editorShown ? 'secondary' : 'primary'"
                              icon="pencil"
                              :aria-label="`Edit Response ${index + 1 } text`"
                              @click="toggleSimpleChoiceEditorShown(index,true)"
                            />
                          </template>
                          <div v-if="simpleChoice.editorShown">
                            <ckeditor
                              :id="`qti_simple_choice_${index}`"
                              v-model="simpleChoice.value"
                              tabindex="0"
                              :config="simpleChoiceConfig"
                              @namespaceloaded="onCKEditorNamespaceLoaded"
                              @ready="handleFixCKEditor()"
                              @input="questionForm.errors.clear(`qti_simple_choice_${index}`)"
                            />
                            <input type="hidden" class="form-control is-invalid">
                            <div class="help-block invalid-feedback">
                              {{ questionForm.errors.get(`qti_simple_choice_${index}`) }}
                            </div>
                            <div class="mt-2">
                              <b-button
                                size="sm"
                                variant="primary"
                                @click="toggleSimpleChoiceEditorShown(index,false)"
                              >
                                Close
                              </b-button>
                            </div>
                          </div>
                          <div v-if="!simpleChoice.editorShown">
                            <span v-html="simpleChoice.value"/>
                          </div>
                        </b-form-group>
                      </li>
                      <li>
                        <b-form-group
                          :label-for="`qti_simple_choice_feedback_${index}`"
                          class="mb-0"
                        >
                          <template v-slot:label>
                            <span class="font-weight-bold">Feedback</span>
                            <b-icon icon="pencil"
                                    :variant="qtiJson.feedbackEditorShown[simpleChoice.identifier] ? 'secondary' : 'primary'"
                                    :aria-label="`Edit Feedback ${index + 1 } text`"
                                    @click="toggleFeedbackEditorShown(simpleChoice.identifier,true)"
                            />
                          </template>
                          <div v-if="qtiJson.feedbackEditorShown[simpleChoice.identifier]">
                            <ckeditor
                              :id="`qti_simple_choice_feedback_${index}`"
                              v-model="qtiJson.feedback[simpleChoice.identifier]"
                              tabindex="0"
                              :config="simpleChoiceConfig"
                              @namespaceloaded="onCKEditorNamespaceLoaded"
                              @ready="handleFixCKEditor()"
                            />
                            <div class="mt-2">
                              <b-button
                                size="sm"
                                variant="primary"
                                @click="toggleFeedbackEditorShown(simpleChoice.identifier,false)"
                              >
                                Close
                              </b-button>
                            </div>
                          </div>
                        </b-form-group>
                        <div v-if="!qtiJson.feedbackEditorShown[simpleChoice.identifier]">
                          <span v-html="qtiJson.feedback[simpleChoice.identifier]"/>
                        </div>
                      </li>
                    </ul>
                  </b-card>
                </li>
                <li v-if="index === simpleChoices.length-1" style="list-style: none;" class="pt-3">
                  <b-row>
                    <b-col sm="10">
                      <b-button v-if="qtiQuestionType === 'multiple_choice'" size="sm" variant="info"
                                @click="addQtiResponse"
                      >
                        Add Response
                      </b-button>
                      <span v-show="false">{{ qtiJson }}</span>
                    </b-col>
                  </b-row>
                </li>
              </ul>
            </div>
            <b-card v-if="['multiple_choice','numerical'].includes(qtiQuestionType)" header="default">
              <template #header>
                <span class="ml-2 h7">General Feedback</span>
              </template>
              <div v-for="(generalFeedback,index) in generalFeedbacks"
                   :key="`feedback-${generalFeedback.label}`"
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
                    <span v-html="qtiJson.feedback[generalFeedback.key]"/>
                  </div>
                </b-form-group>
                <hr v-if="index !==2">
              </div>
            </b-card>
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
            v-if="!['text','qti'].includes(questionForm.technology) && !webworkEditorShown"
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="technology_id"
            :label="questionForm.technology === 'webwork' ? 'File Path' : 'ID'"
          >
            <b-form-row>
              <b-form-input
                id="technology_id"
                v-model="questionForm.technology_id"
                type="text"
                :style="questionForm.technology === 'webwork' ? 'width:740px' : ''"
                :class="{ 'is-invalid': questionForm.errors.has('technology_id'), 'numerical-input' : questionForm.technology !== 'webwork' }"
                @keydown="questionForm.errors.clear('technology_id')"
              />
              <has-error :form="questionForm" field="technology_id"/>
              <a v-if="questionForm.technology === 'webwork'"
                 class="btn btn-sm btn-outline-primary link-outline-primary-btn ml-2"
                 :href="`/api/questions/export-webwork-code/${questionForm.id}`"
              >
                <div style="margin-top:3px">Export webWork code</div>
              </a>
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
          <div v-show="!nursing">
            <div v-if="questionForm.question_type === 'assessment'">
              <b-form-group
                label-cols-sm="3"
                label-cols-lg="2"
                label-for="a11y_technology"
              >
                <template v-slot:label>
                  <span style="cursor: pointer;" @click="toggleExpanded ('a11y_technology')">
                    A11y Auto-Graded Technology
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
                      title="a11y technologies"
                      size="sm"
                      class="mt-2"
                      :options="a11yAutoGradedTechnologyOptions"
                      :aria-required="!isEdit"
                    />
                    <b-form-select
                      v-model="createA11yAutoGradedTechnology"
                      style="width:250px"
                      title="technologies"
                      size="sm"
                      class="mt-2 ml-3"
                      :options="createA11yAutoGradedTechnologyOptions"
                      @change="openCreateAutoGradedTechnologyCode($event)"
                    />
                  </div>
                </b-form-row>
              </b-form-group>
              <b-form-group
                v-if="questionForm.a11y_technology !== null"
                label-cols-sm="3"
                label-cols-lg="2"
                label-for="technology_id"
                :label="questionForm.a11y_technology === 'webwork' ? 'A11y File Path' : 'A11y ID'"
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
            <b-form-group
              v-for="editorGroup in editorGroups.filter(group => !['technology','a11y_technology','non_technology_text'].includes(group.id))"
              :key="editorGroup.id"
              :label-for="editorGroup.label"
            >
              <template v-slot:label>
                <span style="cursor: pointer;" @click="toggleExpanded (editorGroup.id)">
                  {{ editorGroup.label }}
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
        </div>
      </b-form>
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
    </div>
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
import MatrixMultipleResponse from './nursing/MatrixMultipleResponse'
import MultipleResponseGrouping from './nursing/MultipleResponseGrouping'
import BowTie from './nursing/BowTie'
import MultipleResponseSelectAllThatApply from './nursing/MultipleResponseSelectAllThatApply'
import DropDownTable from './nursing/DropDownTable'
import DragAndDropCloze from './nursing/DragAndDropCloze'
import MatrixMultipleChoice from './nursing/MatrixMultipleChoice'

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

let createAutoGradedTechnologyOptions
let createA11yAutoGradedTechnologyOptions
createAutoGradedTechnologyOptions = [{ value: null, text: 'Create Auto-graded code' }]
createA11yAutoGradedTechnologyOptions = [{ value: null, text: 'Create A11y Auto-graded code' }]

let commonTechnologyOptions = [{ value: 'https://studio.libretexts.org/node/add/h5p', text: 'H5P' },
  { value: 'webwork', text: 'WeBWork' },
  { value: 'https://imathas.libretexts.org/imathas/course/moddataset.php', text: 'IMathAS' }]

for (let i = 0; i < commonTechnologyOptions.length; i++) {
  createAutoGradedTechnologyOptions.push(commonTechnologyOptions[i])
  createA11yAutoGradedTechnologyOptions.push(commonTechnologyOptions[i])
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
    MatrixMultipleChoice,
    DragAndDropCloze,
    DropDownTable,
    MultipleResponseSelectAllThatApply,
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
    addingMatching: false,
    addingDistractor: false,
    matchingDistractors: [],
    termsToMatch: [],
    possibleMatches: [],
    selectChoiceIdentifierError: '',
    qtiJsonQuestionViewerKey: 0,
    showQtiAnswer: false,
    textEntryInteractions: [],
    questionFormTechnology: 'text',
    qtiQuestionType: 'multiple_choice',
    trueFalseLanguage: 'English',
    trueFalseLanguageOptions: [
      { text: 'English', value: 'English' },
      { text: 'Spanish', value: 'Spanish' },
      { text: 'French', value: 'French' },
      { text: 'Italian', value: 'Italian' },
      { text: 'German', value: 'German' }
    ],
    qtiPrompt: '',
    simpleChoiceToRemove: {},
    correctResponse: '',
    simpleChoices: [],
    qtiJson: {},
    sourceExpanded: false,
    caretDownIcon: faCaretDown,
    caretRightIcon: faCaretRight,
    createAutoGradedTechnology: null,
    createA11yAutoGradedTechnology: null,
    createA11yAutoGradedTechnologyOptions: createA11yAutoGradedTechnologyOptions,
    createAutoGradedTechnologyOptions: createAutoGradedTechnologyOptions,
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
      { id: 'non_technology_text', label: 'Header HTML', expanded: false },
      { label: 'Text Question', id: 'text_question', expanded: false },
      { label: 'Answer', id: 'answer_html', expanded: false },
      { label: 'Solution', id: 'solution_html', expanded: false },
      { label: 'Hint', id: 'hint', expanded: false },
      { label: 'Notes', id: 'notes', expanded: false }
    ],
    questionForm: new Form(defaultQuestionForm),
    allFormErrors: [],
    autoGradedTechnologyOptions: [
      { value: 'text', text: 'None' },
      { value: 'qti', text: 'Native' },
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
    isMe: () => window.config.isMe,
    uTags () {
      if (this.qtiQuestionType === 'fill_in_the_blank' && this.qtiJson.itemBody.textEntryInteraction) {
        const regex = /(<u>.*?<\/u>)/
        let matches = String(this.qtiJson.itemBody.textEntryInteraction).split(regex).filter(Boolean)
        let uTags = []
        if (matches && matches.length) {
          for (let i = 0; i < matches.length; i++) {
            let match = matches[i]
            if (match.includes('<u>') && match.includes('</u>')) {
              uTags.push(match.replace('<u>', '').replace('</u>', ''))
            }
          }
          this.questionForm.errors.clear('qti_item_body')
        }
        if (!uTags.length) {
          uTags = null
        }
        console.log(uTags)
        return uTags
      } else {
        return []
      }
    },
    selectChoices () {
      let uniqueMatches = []
      if (['drop_down_rationale', 'select_choice'].includes(this.qtiQuestionType) && this.qtiJson && this.qtiJson.itemBody) {
        const regex = /(\[.*?])/
        let allMatches = String(this.qtiJson.itemBody).split(regex)
        console.log(allMatches)
        if (allMatches) {
          for (let i = 0; i < allMatches.length; i++) {
            if (allMatches[i].includes('[') && allMatches[i].includes(']')) {
              let match = allMatches[i].replace('[', '').replace(']', '')
              if (!uniqueMatches.includes(match)) {
                uniqueMatches.push(match)
              }
            }
          }
        }
      }
      console.log(uniqueMatches)
      return uniqueMatches
    }
  },
  watch: {
    selectChoices (newSelectChoices) {
      if (['drop_down_rationale', 'select_choice'].includes(this.qtiQuestionType) &&
        this.qtiJson.inline_choice_interactions &&
        Array.isArray(newSelectChoices) &&
        newSelectChoices.length) {
        for (let i = 0; i < newSelectChoices.length; i++) {
          if (newSelectChoices[i] === '') {
            this.selectChoiceIdentifierError = `You have just added empty brackets.  Please include text within the bracket to identify the select choice item.`
            this.$bvModal.show(`qti-select-choice-error-${this.modalId}`)
            return false
          }
          if (newSelectChoices[i].includes(' ')) {
            this.selectChoiceIdentifierError = `The identifier [${newSelectChoices[i]}] contains a space. Identifiers should not contain any spaces.`
            this.$bvModal.show(`qti-select-choice-error-${this.modalId}`)
            return false
          }
        }
        for (let i = 0; i < newSelectChoices.length; i++) {
          let choice = newSelectChoices[i]
          if (!Object.keys(this.qtiJson.inline_choice_interactions).includes(choice)) {
            this.qtiJson.inline_choice_interactions[choice] = [{
              value: Date.now().toString(),
              text: '',
              correctResponse: true
            }]
          }
        }
      }
      for (const identifier in this.qtiJson.inline_choice_interactions) {
        if (!newSelectChoices.includes(identifier)) {
          delete this.qtiJson.inline_choice_interactions[identifier]
        }
      }
    }
  },
  created () {
    this.getLearningOutcomes = getLearningOutcomes
  },
  beforeDestroy () {
    window.removeEventListener('keydown', this.quickSave)
  },
  async mounted () {
    this.nursing = window.location.hostname === 'local.adapt' && this.user.id === 1
    if (![2, 5].includes(this.user.role)) {
      return false
    }
    window.addEventListener('keydown', this.quickSave)
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
            this.termsToMatch = this.qtiJson.termsToMatch
            let answerIdentifiers = []
            for (let i = 0; i < this.termsToMatch.length; i++) {
              answerIdentifiers.push(this.termsToMatch[i].matchingTermIdentifier)
            }
            this.possibleMatches = this.qtiJson.possibleMatches
            for (let i = 0; i < this.possibleMatches.length; i++) {
              if (!answerIdentifiers.includes(this.possibleMatches[i].identifier)) {
                this.matchingDistractors.push(this.possibleMatches[i])
              }
            }
            break
          case ('true_false'):
          case ('multiple_choice'):
            this.qtiPrompt = this.qtiJson['prompt']
            this.simpleChoices = this.qtiJson.simpleChoice
            this.qtiJson.feedbackEditorShown = {}
            for (let i = 0; i < this.simpleChoices.length; i++) {
              this.simpleChoices[i].editorShown = false
              this.qtiJson.feedbackEditorShown[this.simpleChoices[i].identifier] = false
            }
            this.correctResponse = this.qtiJson.simpleChoice.find(choice => choice.correctResponse).identifier
            let qtiQuestionType = this.qtiJson['questionType']
            if (qtiQuestionType && qtiQuestionType === 'true_false') {
              this.setTrueFalseLanguage(this.qtiJson.simpleChoice[0].value)
              this.qtiQuestionType = 'true_false'
            }
            break
          case ('multiple_answers'):
            this.qtiQuestionType = 'multiple_answers'
            this.qtiPrompt = this.qtiJson['prompt']
            this.simpleChoices = this.qtiJson.simpleChoice
            break
          case ('fill_in_the_blank'):
            this.qtiQuestionType = 'fill_in_the_blank'
            let correctResponse = this.qtiJson.responseDeclaration.correctResponse
            for (let i = 0; i < correctResponse.length; i++) {
              this.qtiJson.itemBody.textEntryInteraction = this.qtiJson.itemBody.textEntryInteraction.replace('<u></u>', `<u>${correctResponse[i].value}</u>`)
            }
            for (let i = 0; i < correctResponse.length; i++) {
              this.textEntryInteractions[i] = {
                matchingType: correctResponse[i].matchingType,
                caseSensitive: correctResponse[i].caseSensitive
              }
            }
            for (let i = correctResponse.length; i < 100; i++) {
              this.textEntryInteractions[i] = { matchingType: 'exact', caseSensitive: 'no' }
            }
            console.log(this.textEntryInteractions)
            break
          case ('select_choice'):
            this.qtiQuestionType = 'select_choice'
            break
          default:
            alert('Not a valid question type:' + this.qtiJson.questionType)
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

      if (this.questionToEdit.webwork_code) {
        this.createAutoGradedTechnology = 'webwork'
        this.webworkEditorShown = true
        this.questionToEdit.create_auto_graded_code = 'webwork'
      }
      if (this.questionToEdit.license_version) {
        this.questionToEdit.license_version = Number(this.questionToEdit.license_version).toFixed(1) // some may be saved as 4 vs 4.0 in the database
      }
      this.questionForm = new Form(this.questionToEdit)
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
        let questionType = 'matrix_multiple_choice'
        this.qtiQuestionType = questionType
        this.initQTIQuestionType(questionType)
        this.questionFormTechnology = 'qti'
        this.questionForm.technology = 'qti'
        this.editorGroups.find(editorGroup => editorGroup.id === 'technology').expanded = true
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
    quickSave (event) {
      if (event.ctrlKey && event.key === 's') {
        this.saveQuestion()
      }
    },
    showFolderOptions () {
      if (!this.isEdit) {
        return true
      }
      if (this.isMe) {
        return [1, 5].includes(this.user.id)
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
    toggleFeedbackEditorShown (identifier, boolean) {
      this.qtiJson.feedbackEditorShown[identifier] = boolean
      this.$forceUpdate()
    },
    toggleSimpleChoiceEditorShown (index, boolean) {
      this.simpleChoices[index].editorShown = boolean
      this.$forceUpdate()
    },
    deleteMatchingTerm (identifier) {
      if (this.possibleMatches.length + this.matchingDistractors.length <= 2) {
        this.$noty.error('You need at least 2 possible matches.')
        return false
      }
      let matchingTermIdentifier = this.termsToMatch.find(termToMatch => termToMatch.identifier === identifier).matchingTermIdentifier
      this.possibleMatches = this.possibleMatches.filter(possibleMatch => possibleMatch.identifier !== matchingTermIdentifier)
      this.termsToMatch = this.termsToMatch.filter(termToMatch => termToMatch.identifier !== identifier)
    },
    deleteDistractor (identifier) {
      if (this.possibleMatches.length + this.matchingDistractors.length <= 2) {
        this.$noty.error('You need at least 2 possible matches.')
        return false
      }
      this.matchingDistractors = this.matchingDistractors.filter(distractor => distractor.identifier !== identifier)
      this.possibleMatches = this.possibleMatches.filter(possibleMatch => possibleMatch.identifier !== identifier)
      this.$forceUpdate()
    },
    async addQTIMatchingItem () {
      this.addingMatching = true
      let matchingTermIdentifier = uuidv4()
      this.termsToMatch.push({
          identifier: uuidv4(),
          termToMatch: '',
          matchingTermIdentifier: matchingTermIdentifier,
          feedback: ''
        }
      )
      this.possibleMatches.push({
        identifier: matchingTermIdentifier,
        matchingTerm: ''
      })
      this.addingMatching = false
    },
    async addQTIMatchingDistractor () {
      this.addingDistractor = true
      let identifier = uuidv4()
      this.matchingDistractors.push({ identifier: identifier, matchingTerm: '' })
      this.possibleMatches.push({ identifier: identifier, matchingTerm: '' })
      this.addingDistractor = false
    },
    toggleMultipleAnswersCorrectResponse (simpleChoice) {
      simpleChoice.correctResponse = !simpleChoice.correctResponse
    },
    setTrueFalseLanguage (trueValue) {
      switch (trueValue) {
        case ('True'):
          this.trueFalseLanguage = 'English'
          break
        case ('Verdadero'):
          this.trueFalseLanguage = 'Spanish'
          break
        case ('Vrai'):
          this.trueFalseLanguage = 'French'
          break
        case ('Vero'):
          this.trueFalseLanguage = 'Italian'
          break
        case ('Richtig'):
          this.trueFalseLanguage = 'German'
          break
        default:
          this.trueFalseLanguage = 'English'
      }
    },
    deleteChoiceFromSelectChoice (selectChoice, choice) {
      this.qtiJson.inline_choice_interactions[selectChoice] = this.qtiJson.inline_choice_interactions[selectChoice].filter(item => item !== choice)
      this.$forceUpdate()
    },
    addChoiceToSelectChoice (selectChoice) {
      this.qtiJson.inline_choice_interactions[selectChoice].push({
        value: uuidv4(),
        text: '',
        correctResponse: false
      })
      this.$forceUpdate()
    },
    initChangeAutoGradedTechnology (technology) {
      this.questionForm.webwork_code = ''
      this.createAutoGradedTechnology = null
      this.showPreexistingWebworkFilePath = false
      this.preexisitingWebworkFilePath = ''
      this.webworkTemplate = null
      this.webworkEditorShown = false
      if (technology === 'qti') {
        if (this.questionForm.non_technology_text) {
          this.$noty.info('Please remove any Header HTML before changing to Native.  You can always move your Header HTML into the Prompt of your Native question.')
          this.questionFormTechnology = this.questionForm.technology
        } else {
          this.editorGroups.find(editorGroup => editorGroup.id === 'non_technology_text').expanded = false
          this.questionForm.technology = 'qti'
          this.qtiQuestionType = 'multiple_choice'
          this.initQTIQuestionType(this.qtiQuestionType)
        }
      } else {
        this.questionForm.technology = this.questionFormTechnology
      }
    },
    translateTrueFalse (language) {
      let trueResponse
      let falseResponse
      switch (language) {
        case ('English'):
          trueResponse = 'True'
          falseResponse = 'False'
          break
        case ('Spanish'):
          trueResponse = 'Verdadero'
          falseResponse = 'Falso'
          break
        case ('French'):
          trueResponse = 'Vrai'
          falseResponse = 'Faux'
          break
        case ('Italian'):
          trueResponse = 'Vero'
          falseResponse = 'Falso'
          break
        case ('German'):
          trueResponse = 'Richtig'
          falseResponse = 'Falsch'
          break
      }
      this.qtiJson.simpleChoice[0].value = trueResponse
      this.qtiJson.simpleChoice[1].value = falseResponse
    },
    initQTIQuestionType (questionType) {
      this.questionForm.errors.clear()
      this.qtiJson = {}
      this.simpleChoices = []
      for (let i = 0; i < this.generalFeedbacks.length; i++) {
        this.generalFeedbacks[i].editorShown = false
      }
      switch (questionType) {
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
        case
        ('bow_tie')
        :
          this.qtiJson = {
            questionType: 'bow_tie',
            actionsToTake: [{ identifier: uuidv4(), value: '' }],
            potentialConditions: [{ identifier: uuidv4(), value: '' }],
            parametersToMonitor: [{ identifier: uuidv4(), value: '' }]
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
          this.termsToMatch = []
          this.possibleMatches = []
          this.addQTIMatchingItem(false)
          break
        case
        ('multiple_answers')
        :
        case
        ('multiple_choice')
        :
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
        case
        ('true_false')
        :
          this.qtiJson = simpleChoiceJson
          this.qtiJson.prompt = ''
          this.qtiPrompt = ''
          this.qtiJson['language'] = this.trueFalseLanguage
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
          this.translateTrueFalse(this.trueFalseLanguage)
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
          for (let i = 0; i < 100; i++) {
            this.textEntryInteractions[i] = { matchingType: 'exact', caseSensitive: 'no' }
          }
          break
        case ('drop_down_rationale'):
        case ('select_choice'):
          this.qtiJson = {
            questionType: this.qtiQuestionType,
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
      this.qtiPrompt = ''
    },
    initDeleteQtiResponse (simpleChoiceToRemove) {
      let onlyOneResponse
      switch (this.qtiQuestionType) {
        case ('multiple_choice'):
          onlyOneResponse = this.qtiJson.simpleChoice.length === 1
          if (simpleChoiceToRemove.correctResponse) {
            this.$noty.info('Please choose a different correct answer before removing this response.')
            return false
          }
          break
        case ('multiple_answers'):
          onlyOneResponse = this.qtiJson.simpleChoice.length === 1
          break
      }
      if (onlyOneResponse) {
        this.$noty.info('There must be at least one response.')
        return false
      }

      this.simpleChoiceToRemove = simpleChoiceToRemove
      if (this.simpleChoiceToRemove.value === '') {
        this.deleteQtiResponse()
        return false
      }
      this.$bvModal.show(`confirm-remove-simple-choice-${this.modalId}`)
    },
    deleteQtiResponse () {
      switch (this.qtiQuestionType) {
        case ('multiple_choice'):
          this.qtiJson.simpleChoice = this.qtiJson.simpleChoice.filter(item => item.identifier !== this.simpleChoiceToRemove.identifier)
          this.simpleChoices = this.qtiJson.simpleChoice
          break
        case ('multiple_answers'):
          this.qtiJson.simpleChoice = this.qtiJson.simpleChoice.filter(item => item.identifier !== this.simpleChoiceToRemove.identifier)
          this.simpleChoices = this.qtiJson.simpleChoice
      }
      this.$bvModal.hide(`confirm-remove-simple-choice-${this.modalId}`)
    },
    goto (refName) {
      let element = this.$refs[refName]
      let top = element.offsetTop

      window.scrollTo(0, top)
    },
    addQtiResponse () {
      let response
      switch (this.qtiQuestionType) {
        case ('multiple_choice'):
          response = {
            identifier: Date.now().toString(),
            value: ''
          }
          this.qtiJson.simpleChoice.push(response)
          break
        case ('multiple_answers'):
          response = {
            identifier: Date.now().toString(),
            value: '',
            correctResponse: false,
            feedback: ''
          }
          this.qtiJson.simpleChoice.push(response)
          break
        default:
          alert(`No addQtiResponse case for ${this.qtiQuestionType}`)
      }
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
    updateCorrectResponse (simpleChoice) {
      this.simpleChoices.find(choice => choice.identifier !== simpleChoice.identifier).correctResponse = false
      simpleChoice.correctResponse = true
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
        this.$noty.info('Please enter your Header HTML within the Prompt textarea.')
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
              this.$noty.info('If you would like to hide the a11y technology input area, make sure that no a11y technology is chosen.')
              return false
            }
            break
          default:
            if (this.questionForm[id].length) {
              this.$noty.info(`If you would like to hide the ${editorGroup.label} input area, please first remove any text.`)
              return false
            }
        }
      }
      this.editorGroups.find(group => group.id === id).expanded = !editorGroup.expanded
    },
    openCreateAutoGradedTechnologyCode (value) {
      this.questionForm.create_auto_graded_code = false
      if (value === 'webwork') {
        this.webworkEditorShown = true
        this.questionFormTechnology = 'webwork'
        this.questionForm.technology = 'webwork'
        this.questionForm.create_auto_graded_code = 'webwork'
      } else if (value) {
        window.open(value, '_blank')
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
        this.questionForm = new Form(defaultQuestionForm)
        this.questionForm.author = this.user.first_name + ' ' + this.user.last_name
      }
      this.questionForm.question_type = questionType
      this.questionForm.folder_id = folderId
    },
    getQuestionType () {
      if (this.questionForm.question_type === 'auto_graded') {
        return 'Auto-Graded'
      } else if (this.questionForm.question_type === 'open_ended') {
        return 'Open-ended'
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
      try {
        if (this.questionForm.technology !== 'qti') {
          const { data } = await this.questionForm.post('/api/questions/preview')
          this.questionToView = data.question
        } else {
          if (this.qtiQuestionType === 'matching') {
            this.qtiJson.termsToMatch = this.termsToMatch
            this.qtiJson.possibleMatches = this.possibleMatches
            if (this.possibleMatches) {
              for (let i = 0; i < this.matchingDistractors.length; i++) {
                let matchingDistractor = this.matchingDistractors[i]
                let possibleMatch = this.qtiJson.possibleMatches.find(possibleMatch => possibleMatch.identifier === matchingDistractor.identifier)
                if (possibleMatch) {
                  possibleMatch.matchingTerm = matchingDistractor.matchingTerm
                }
              }
            }
          }
          this.$forceUpdate()
          this.questionToView = this.qtiJson
          if (this.qtiQuestionType === 'fill_in_the_blank') {
            this.questionToView.responseDeclaration = {}
            this.questionToView.responseDeclaration.correctResponse = this.getFillInTheBlankResponseDeclarations()
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
            for (let i = 0; i < this.termsToMatch.length; i++) {
              let item = this.termsToMatch[i]
              if (!usedTermsToMatch.includes(item.termToMatch)) {
                this.questionForm[`qti_matching_term_to_match_${i}`] = item.termToMatch
                this.qtiJson.termsToMatch.push({
                  identifier: item.identifier,
                  termToMatch: item.termToMatch,
                  matchingTermIdentifier: this.possibleMatches[i].identifier,
                  feedback: this.termsToMatch[i].feedback
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
            for (let i = 0; i < this.possibleMatches.length; i++) {
              let item = this.possibleMatches[i]
              let distractor = this.matchingDistractors.find(distractor => distractor.identifier === item.identifier)
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
          case
          ('fill_in_the_blank')
          :
            this.questionForm.qti_item_body = this.qtiJson.itemBody
            this.questionForm.qti_text_entry_interactions = this.textEntryInteractions
            this.questionForm.uTags = this.uTags
            this.qti_json = textEntryInteractionJson

            let qtiJson = {}
            qtiJson.responseDeclaration = {}
            qtiJson.responseDeclaration.correctResponse = this.getFillInTheBlankResponseDeclarations()
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
          this.allFormErrors = this.questionForm.errors.flatten()
          this.questionsFormKey++
          this.$nextTick(() => this.$bvModal.show(`modal-form-errors-questions-form-${this.questionsFormKey}`))
        }
      }
    },
    getFillInTheBlankResponseDeclarations () {
      let responseDeclarations = []
      console.log(this.uTags)
      if (this.uTags) {
        for (let i = 0; i < this.uTags.length; i++) {
          let uTag = this.uTags[i]
          console.log(uTag)
          let responseDeclaration = {
            'value': uTag,
            'matchingType': this.textEntryInteractions[i].matchingType,
            'caseSensitive': this.textEntryInteractions[i].caseSensitive
          }
          responseDeclarations.push(responseDeclaration)
        }
      }
      return responseDeclarations
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
