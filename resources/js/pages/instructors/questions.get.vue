<template>
  <div>
    <SavedQuestionsFolders
      v-show="false"
      ref="moveOrRemoveQuestionsMyFavorites"
      :key="`move-or-remove-questions-my-favorites-${moveOrRemoveQuestionsMyFavoritesKey}`"
      :question-source-is-my-favorites="questionSource === 'my_favorites'"
      :type="savedQuestionsFoldersType"
      :folder-level="folderLevel"
      :assignment="chosenAssignment"
      :create-modal-add-saved-questions-folder="true"
      @savedQuestionsFolderSet="setSavedQuestionsFolder"
      @getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder="getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder"
      @reloadSavedQuestionsFolders="getCollection"
      @resetFolderAction="resetFolderAction"
      @removeMyFavoritesQuestion="removeMyFavoritesQuestion"
      @reloadMyFavoritesOptions="reloadMyFavoritesOptions"
      @updateTopicInList="updateTopicInList"
      @addTopicToList="addTopicToList"
      @removeTopic="removeTopic"
    />
    <b-modal
      id="modal-bulk-move-to-new-topic"
      title="Bulk move questions to new topic"
    >
      <b-form-select id="topics"
                     v-model="topicToMoveQuestionsTo"
                     :options="filteredTopicsOptions"
      />

      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-bulk-move-to-new-topic')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleBulkMoveToNewTopic(topicToMoveQuestionsTo)"
        >
          Move
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-init-remove-question-from-favorites-folder"
      :title="`Remove question from My Favorites`"
    >
      <p>
        Please confirm that you would like to remove the question <span class="font-weight-bold">{{ questionToRemoveFromFavoritesFolder.title }}</span> from
        the My Favorites folder <span class="font-weight-bold">{{ questionToRemoveFromFavoritesFolder.my_favorites_folder_name }}</span>.
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-init-remove-question-from-favorites-folder')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="removeMyFavoritesQuestion(questionToRemoveFromFavoritesFolder.my_favorites_folder_id,questionToRemoveFromFavoritesFolder.question_id)"
        >
          Remove
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-save-to-my-favorites"
      ref="saveToMyFavorites"
      title="Save Question To My Favorites"
    >
      <SavedQuestionsFolders
        ref="savedQuestionsFolders"
        :key="`save-to-my-favorites-${saveToMyFavoritesKey}`"
        :type="'my_favorites'"
        :init-saved-questions-folder="savedQuestionsFolder"
        :question-source-is-my-favorites="questionSource === 'my_favorites'"
        @savedQuestionsFolderSet="setSavedQuestionsFolder"
        @getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder="getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder"
        @reloadSavedQuestionsFolders="getCollection"
        @resetFolderAction="resetFolderAction"
        @reloadMyFavoritesOptions="reloadMyFavoritesOptions"
      />
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-save-to-my-favorites')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="saveMyFavoritesQuestions()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="question-bank-view-questions"
      title=""
      size="lg"
      :hide-footer="true"
      @show="questionBankModalShown = true"
      @hide="questionBankModalShown = false;resetBulkActionData()"
    >
      <div class="pb-2">
        <span class="pr-2">
          <span v-if="questionToView.in_current_assignment">
            <b-button
              variant="danger"
              size="sm"
              @click="questionToRemove = questionToView; openRemoveQuestionModal(questionToView)"
            >
              Remove From Assignment
            </b-button>
          </span>
          <span v-show="!questionToView.in_current_assignment">
            <b-button
              variant="primary"
              size="sm"

              @click="addQuestions([questionToView])"
            >
              Add To Assignment
            </b-button>
          </span>
        </span>
        <span v-show="!questionToView.my_favorites_folder_id">
          <b-button
            variant="outline-secondary"
            size="sm"
            @click="saveToMyFavoritesQuestionIds =[questionToView.question_id];saveMyFavoritesQuestions()"
          >
            Add To My Favorites
          </b-button>
          <SavedQuestionsFolders
            ref="savedQuestionsFolders"
            :key="`modal-save-to-my-favorites-${saveToMyFavoritesKey}`"
            :init-saved-questions-folder="savedQuestionsFolder"
            :type="'my_favorites'"
            :question-source-is-my-favorites="questionSource === 'my_favorites'"
            @savedQuestionsFolderSet="setSavedQuestionsFolder"
            @getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder="getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder"
            @reloadSavedQuestionsFolders="getCollection"
            @resetFolderAction="resetFolderAction"
            @reloadMyFavoritesOptions="reloadMyFavoritesOptions"
          />
        </span>
        <span v-if="questionSource !== 'my_favorites' && questionToView.my_favorites_folder_id">
          <b-button
            variant="outline-danger"
            size="sm"
            @click="removeMyFavoritesQuestion(questionToView.my_favorites_folder_id, questionToView.question_id)"
          >
            Remove From My Favorites
          </b-button>
        </span>
      </div>

      <ViewQuestions :key="`view-selected-questions-clicked-${numViewSelectedQuestionsClicked}`"
                     :question-ids-to-view="selectedQuestionIds"
                     @questionToViewSet="setQuestionToView"
      />
    </b-modal>
    <b-modal
      id="modal-remove-question"
      ref="modal"
      title="Confirm Remove Question"
    >
      <RemoveQuestion :beta-assignments-exist="betaAssignmentsExist" :question-to-remove="questionToRemove" />
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-remove-question')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="submitRemoveQuestion()"
        >
          Remove question
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-upload-file"
      ref="solutionFileInput"
      title="Upload File"
      ok-title="Submit"
      size="lg"
      @ok="handleOk"
    >
      <b-form ref="form">
        <p>Accepted file types are: {{ getAcceptedFileTypes() }}.</p>
        <b-form-file
          ref="solutionFileInput"
          v-model="uploadFileForm.solutionFile"
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
          {{ uploadFileForm.errors.get('solutionFile') }}
        </div>
      </b-form>
    </b-modal>
    <b-modal
      id="modal-non-h5p"
      ref="h5pModal"
      title="Non-H5P assessments in clicker assignment"
    >
      <b-alert :show="true" variant="danger">
        <span class="font-weight-bold">
          {{
            h5pText()
          }}
        </span>
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-non-h5p')">
          OK
        </b-button>
      </template>
    </b-modal>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle :title="title" />
        <b-container>
          <AssessmentTypeWarnings :assessment-type="assessmentType"
                                  :open-ended-questions-in-real-time="openEndedQuestionsInRealTime"
                                  :learning-tree-questions-in-non-learning-tree="learningTreeQuestionsInNonLearningTree"
                                  :non-learning-tree-questions="nonLearningTreeQuestions"
                                  :beta-assignments-exist="betaAssignmentsExist"
                                  :h5p-questions-with-anonymous-users="h5pQuestionsWithAnonymousUsers"
          />
          <b-row align-h="end">
            <b-button variant="primary" size="sm" @click="getStudentView(assignmentId)">
              View Questions
            </b-button>
          </b-row>
        </b-container>
        <hr>
        <div>
          <div v-if="isPointsByQuestionWeightAndSubmissionsExist">
            <b-alert show>
              <span class="font-weight-bold">
                You cannot add or remove questions to this assignment since there are already submissions and this assignment computes points using question weights.
              </span>
            </b-alert>
          </div>
          <div v-else>
            <b-tabs content-class="mt-3">
              <b-tab title="Question Bank" active
                     @click="showQuestions = false;"
              >
                <b-container>
                  <b-row>
                    <b-col cols="4">
                      <b-row class="pb-2">
                        <b-form-select id="collections"
                                       v-model="questionSource"
                                       :options="questionSourceOptions"
                                       @change="initGetQuestionSource($event)"
                        />
                      </b-row>
                      <b-row class="pb-4">
                        <b-form-select v-show="questionChosenFromAssignment()"
                                       id="collections"
                                       v-model="collection"
                                       :disabled="questionSource === null"
                                       :options="collectionsOptions"
                                       @change="getCollection($event)"
                        />
                        <b-button v-show="questionSource !== null && !questionChosenFromAssignment()"
                                  size="sm"
                                  variant="primary"
                                  @click="savedQuestionsFoldersType = questionSource;$bvModal.show('modal-add-saved-questions-folder')"
                        >
                          New {{ getQuestionSourceText() }} Folder
                        </b-button>
                      </b-row>
                      <div class="question-bank-scroll" :style="{ maxHeight: questionBankScrollHeight}">
                        <div v-if="questionChosenFromAssignment()" class="list-group">
                          <div v-if="assignments.length"
                               class="list-group-item all-questions"
                               :style="chosenCourseId === collection ? 'background-color: #EAECEF' : ''"
                          >
                            <a class="hover-underline"
                               @click.prevent="chosenAssignmentId = null;chosenCourseId = collection;getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()"
                            >All questions</a> <span
                              v-show="assignments.filter(assignment => assignment.topics.length).length"
                            >
                              <font-awesome-icon v-if="allTopicsShown"
                                                 :icon="caretDownIcon"
                                                 @click="allTopicsShown=false;showAllTopics(allTopicsShown)"
                              />
                              <font-awesome-icon v-if="!allTopicsShown"
                                                 :icon="caretRightIcon"
                                                 @click="allTopicsShown=true;showAllTopics(allTopicsShown)"
                              />
                            </span>

                            <span class="float-right">{{ all.num_questions }}</span>
                          </div>
                          <div v-for="assignment in assignments" :key="`assignment-${assignment.id}`">
                            <div class="list-group-item"
                                 :style="chosenAssignmentId === assignment.id &&chosenTopicId === null ? 'background-color: #EAECEF' : ''"
                            >
                              <draggable :key="`draggable-key-assignment-${assignment.id}`"
                                         :list="[assignment]"
                                         group="shared"
                              >
                                <span
                                  v-if="!questionChosenFromAssignment() || questionSource === 'my_courses'"
                                  @click="chosenAssignment = assignment;isTopic = true;initFolderAction ('new')"
                                >+</span>
                                <a class="hover-underline assignment"
                                   @click.prevent="chosenCourseId=null;chosenTopicId = null;chosenAssignmentId = assignment.id;getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()"
                                >{{ assignment.name }}</a>
                                <span v-show="assignment.topics.length">
                                  <font-awesome-icon v-if="assignment.topics_shown"
                                                     :icon="caretDownIcon"
                                                     @click="assignment.topics_shown = false;chosenTopicId = null;getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()"
                                  />
                                  <font-awesome-icon v-if="!assignment.topics_shown"
                                                     :icon="caretRightIcon"
                                                     @click="assignment.topics_shown = true"
                                  />
                                </span>
                                <span class="float-right">
                                  {{ assignment.num_questions }}
                                </span>
                              </draggable>
                            </div>
                            <div v-if="assignment.topics.length && assignment.topics_shown">
                              <div v-for="topic in assignment.topics"
                                   :key="`topic-${topic.id}`"
                                   class="list-group-item"
                                   :style="chosenAssignmentId === assignment.id && chosenTopicId === topic.id ? 'background-color: #EAECEF' : ''"
                              >
                                <draggable :key="`draggable-key-topic-${topic.id}`"
                                           :list="[assignment]"
                                           group="shared"
                                >
                                  <span class="ml-5 topic"
                                        :data-assignment-id="`${topic.assignment_id}`"
                                        :data-topic-id="`${topic.id}`"
                                  > <a class="hover-underline"
                                       @click.prevent="chosenCourseId=null;chosenTopicId = topic.id;chosenAssignmentId = assignment.id;getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()"
                                    >{{ topic.name }}</a>
                                    <b-icon icon="pencil"
                                            class="text-muted"
                                            :aria-label="`Edit ${topic.name}`"
                                            @click="chosenTopicAssignmentId=topic.assignment_id;isTopic=true;initFolderAction ('edit', topic)"
                                    />
                                    <b-icon icon="trash"
                                            class="text-muted"
                                            :aria-label="`Delete ${topic.name}`"
                                            @click="chosenTopicAssignmentId=topic.assignment_id;isTopic=true;initFolderAction('delete', topic)"
                                    />
                                  </span>
                                  <span class="float-right">
                                    {{ topic.num_questions }}
                                  </span>
                                </draggable>
                              </div>
                            </div>
                          </div>
                        </div>
                        <ul v-if="!questionChosenFromAssignment()" class="list-group">
                          <li v-if="savedQuestionsFolders.length"
                              class="list-group-item"
                              :style="allSavedQuestionFoldersByQuestionSource===true ? 'background-color: #EAECEF' : ''"
                          >
                            <a class="hover-underline"
                               @click.prevent="allSavedQuestionFoldersByQuestionSource = true;getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()"
                            >All questions</a><span class="float-right">{{ all.num_questions }}</span>
                          </li>

                          <li v-for="(currentSavedQuestionsFolder,index) in savedQuestionsFolders"
                              :key="`folder-${currentSavedQuestionsFolder.id}`"
                              class="list-group-item"
                              :style="allSavedQuestionFoldersByQuestionSource===false && chosenAssignmentId === currentSavedQuestionsFolder.id ? 'background-color: #EAECEF' : ''"
                          >
                            <draggable :key="`draggable-key-${index}`"
                                       :list="[currentSavedQuestionsFolder]"
                                       group="shared"
                            >
                              <a
                                :data-folder-id="`${currentSavedQuestionsFolder.id}`"
                                class="hover-underline saved-questions-folder"
                                @click.prevent="allSavedQuestionFoldersByQuestionSource=false;chosenAssignmentId = currentSavedQuestionsFolder.id;getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()"
                              >{{ currentSavedQuestionsFolder.name }}</a>
                              <a
                                href=""
                                aria-label="Edit Folder"
                                @click.prevent="isTopic=false;initFolderAction('edit', currentSavedQuestionsFolder)"
                              >
                                <b-icon icon="pencil"
                                        class="text-muted"
                                        :aria-label="`Edit ${currentSavedQuestionsFolder.name}`"
                                />
                              </a>
                              <a
                                href=""
                                aria-label="Delete Folder"
                                @click.prevent="isTopic=false;initFolderAction('delete', currentSavedQuestionsFolder)"
                              >
                                <b-icon icon="trash"
                                        class="text-muted"
                                        :aria-label="`Delete ${currentSavedQuestionsFolder.name}`"
                                />
                              </a>
                              <span class="float-right">
                                {{ currentSavedQuestionsFolder.num_questions }}
                              </span>
                            </draggable>
                          </li>
                        </ul>
                      </div>
                    </b-col>
                    <b-col>
                      <b-row class="pb-3">
                        <b-col>
                          <b-form-group
                            label="Question Type"
                            label-for="question-type"
                            label-cols-sm="4"
                            label-align-sm="right"
                            label-size="sm"
                            class="mb-0"
                          >
                            <b-form-select id="question-type"
                                           v-model="questionType"
                                           :options="questionTypeOptions"
                                           inline
                                           size="sm"
                                           @change="filterByQuestionType($event)"
                            />
                          </b-form-group>
                        </b-col>
                        <b-col>
                          <b-form-group
                            label-for="filter-input"
                            label-cols-sm="3"
                            label-align-sm="right"
                            label-size="sm"
                            class="mb-0"
                          >
                            <template slot="label">
                              Filter
                              <QuestionCircleTooltip :id="'filter-tooltip'" />
                              <b-tooltip target="filter-tooltip"
                                         delay="250"
                                         triggers="hover focus"
                              >
                                You can filter questions by any text you see in the table. In addition, you can find
                                text
                                based questions or questions
                                converted to text by ADAPT by using the filter.
                              </b-tooltip>
                            </template>
                            <b-input-group size="sm">
                              <b-form-input
                                id="filter-input"
                                v-model="filter"
                                type="search"
                                placeholder="Type to Search"
                                @input="filterResults"
                              />

                              <b-input-group-append>
                                <b-button :disabled="!filter"
                                          @click="filter = ''; assignmentQuestions = originalAssignmentQuestions"
                                >
                                  Clear
                                </b-button>
                              </b-input-group-append>
                            </b-input-group>
                          </b-form-group>
                        </b-col>
                      </b-row>
                      <div class="question-bank-scroll" :style="{ maxHeight: questionBankScrollHeight}">
                        <table class="table table-striped" style="position: sticky;top: 0">
                          <thead>
                            <tr>
                              <th scope="col" class="header">
                                <input id="select_all" type="checkbox"
                                       @click="numViewSelectedQuestionsClicked++;selectAll()"
                                >
                                Title <span class="float-right"><b-form-select id="selected"
                                                                               v-model="bulkAction"
                                                                               inline
                                                                               :disabled="!selectedQuestionIds.length"
                                                                               :options="chosenAssignmentId && questionSource === 'my_courses'
                                                                                 ? bulkActionOptions
                                                                                 : bulkActionOptions.filter(option => option.text !== 'Move To Topic')"
                                                                               style="width:200px"
                                                                               size="sm"
                                                                               @change="actOnBulkAction($event)"
                                />
                                </span>
                              </th>
                              <th v-if="chosenAssignmentId && !chosenTopicId" scope="col" class="header">
                                Topic
                              </th>
                              <th scope="col" class="pb-3 header">
                                ID
                              </th>

                              <th scope="col" class="pb-3 header wrapWord">
                                Tags
                              </th>
                              <th scope="col" class="pb-3 header" style="width:100px">
                                Actions
                              </th>
                            </tr>
                          </thead>
                          <draggable
                            :list="assignmentQuestions"
                            group="shared"
                            tag="tbody"
                            :draggable="'.can-drag'"
                            @end="questionChosenFromAssignment() && questionSource === 'my_courses' ? moveToNewTopic($event) : moveToNewFolder($event)"
                            @start="setQuestionId"
                          >
                            <tr v-for="(assignmentQuestion, index) in assignmentQuestions"
                                :key="`assignmentQuestion-${index}`"
                                :data-question-id-to-move="assignmentQuestion.question_id"
                                :class="{'can-drag': !questionChosenFromAssignment() || (questionChosenFromAssignment() && questionSource === 'my_courses')}"
                            >
                              <td>
                                <font-awesome-icon
                                  v-if="!questionChosenFromAssignment() || questionSource === 'my_courses'"
                                  :icon="barsIcon"
                                />
                                <input v-model="selectedQuestionIds" type="checkbox"
                                       :value="assignmentQuestion.question_id"
                                       class="selected-question-id"
                                >
                                <span
                                  :class="{'text-danger' : assignmentQuestion.in_other_assignments}"
                                >
                                  <span v-if="assignmentQuestion.title">{{ assignmentQuestion.title }}</span>
                                  <span v-if="!assignmentQuestion.title">None provided</span>

                                </span>
                                <span
                                  v-if="assignmentQuestion.in_other_assignments"
                                >
                                  <QuestionCircleTooltip
                                    :id="`in-assignment-tooltip-${assignmentQuestion.question_id}`"
                                  />
                                  <b-tooltip :target="`in-assignment-tooltip-${assignmentQuestion.question_id}`"
                                             delay="250"
                                             triggers="hover focus"
                                  >
                                    This question is in the assignment<span
                                      v-if="assignmentQuestion.in_assignments_count>1"
                                    >s</span> "{{ assignmentQuestion.in_assignments_names }}".
                                  </b-tooltip>
                                </span>
                              </td>
                              <td v-if="chosenAssignmentId && !chosenTopicId">
                                {{ assignmentQuestion.topic }}
                              </td>
                              <td>{{ assignmentQuestion.question_id }}</td>
                              <td class="wrapWord">
                                {{ assignmentQuestion.tags }}
                              </td>
                              <td style="width:100px">
                                <span v-if="!assignmentQuestion.in_current_assignment">
                                  <b-button
                                    variant="primary"
                                    class="p-1"
                                    @click.prevent="addQuestions([assignmentQuestion])"
                                  ><span :aria-label="`Add ${assignmentQuestion.title} to the assignment`">+</span>
                                  </b-button>
                                  <b-tooltip
                                    :target="getTooltipTarget('add-question-to-assignment',assignmentQuestion.question_id)"
                                    delay="1000"
                                    triggers="hover focus"
                                    :title="`Add ${assignmentQuestion.my_favorites_folder_name} to the assignment`"
                                  >
                                    Add {{ assignmentQuestion.title }} to the assignment
                                  </b-tooltip>
                                </span>
                                <span v-if="assignmentQuestion.in_current_assignment">
                                  <b-button
                                    :id="getTooltipTarget('remove-question-from-assignment',assignmentQuestion.question_id)"
                                    variant="danger"
                                    class="p-1"
                                    @click.prevent="isRemixerTab = true; questionToRemove = assignmentQuestion; openRemoveQuestionModal(assignmentQuestion)"
                                  ><span :aria-label="`Remove ${assignmentQuestion.title} from the assignment`">-</span>
                                  </b-button>
                                  <b-tooltip
                                    :target="getTooltipTarget('remove-question-from-assignment',assignmentQuestion.question_id)"
                                    delay="1000"
                                    triggers="hover focus"
                                    :title="`Remove ${assignmentQuestion.my_favorites_folder_name} from the assignment`"
                                  >
                                    Remove {{ assignmentQuestion.title }} from the assignment
                                  </b-tooltip>
                                </span>
                                <span v-if="questionSource !== 'my_favorites'">

                                  <span v-show="!assignmentQuestion.my_favorites_folder_id">
                                    <a
                                      href=""
                                      @click.prevent="initSaveToMyFavorites([assignmentQuestion.question_id])"
                                    >
                                      <font-awesome-icon
                                        class="text-muted"
                                        :icon="heartIcon"
                                        :aria-label="`Add ${assignmentQuestion.title} to My Favorites`"
                                      />
                                    </a>
                                  </span>
                                  <span v-if="assignmentQuestion.my_favorites_folder_id">
                                    <a :id="getTooltipTarget('remove-from-my-favorites',assignmentQuestion.question_id)"
                                       href=""
                                       @click.prevent="removeMyFavoritesQuestion(assignmentQuestion.my_favorites_folder_id,assignmentQuestion.question_id)"
                                    >
                                      <font-awesome-icon
                                        class="text-danger"
                                        :icon="heartIcon"
                                        :aria-label="`Remove from ${assignmentQuestion.my_favorites_folder_name}`"
                                      />
                                    </a>
                                    <b-tooltip
                                      :target="getTooltipTarget('remove-from-my-favorites',assignmentQuestion.question_id)"
                                      delay="1000"
                                      triggers="hover focus"
                                      :title="`Move from ${assignmentQuestion.my_favorites_folder_name} or remove`"
                                    >
                                      Remove from the My Favorites folder {{
                                        assignmentQuestion.my_favorites_folder_name
                                      }}
                                    </b-tooltip>
                                  </span>
                                </span>
                                <span v-if="questionSource === 'my_favorites'">
                                  <a
                                    href=""
                                    @click.prevent="removeMyFavoritesQuestion(assignmentQuestion.my_favorites_folder_id,assignmentQuestion.question_id)"
                                  >
                                    <b-icon icon="trash"
                                            class="text-muted"
                                            :aria-label="`Remove from ${assignmentQuestion.my_favorites_folder_name}`"
                                    />
                                  </a>
                                  <b-tooltip
                                    :target="getTooltipTarget('remove-from-my-favorites-within-my-favorites',assignmentQuestion.question_id)"
                                    delay="1000"
                                    triggers="hover focus"
                                    :title="`Remove from ${assignmentQuestion.my_favorites_folder_name}`"
                                  >
                                    Remove from the My Favorites folder {{
                                      assignmentQuestion.my_favorites_folder_name
                                    }}
                                  </b-tooltip>
                                </span>
                              </td>
                            </tr>
                          </draggable>
                        </table>
                        <div v-if="processingGetCollection" class="text-center mt-5">
                          <b-spinner small type="grow" />
                          <span style="font-size:20px;">Loading</span>
                        </div>
                        <div v-if="!processingGetCollection">
                          <div v-if="questionChosenFromAssignment()">
                            <b-alert :show="!assignmentQuestions.length && collection !== null" variant="info">
                              <span class="font-weight-bold">
                                There are no questions for this selection.
                              </span>
                            </b-alert>
                          </div>
                          <div v-if="!questionChosenFromAssignment()">
                            <b-alert :show="!assignmentQuestions.length" variant="info">
                              <span class="font-weight-bold">
                                This folder has no questions.
                              </span>
                            </b-alert>
                          </div>
                        </div>
                      </div>
                    </b-col>
                  </b-row>
                </b-container>
              </b-tab>

              <b-tab v-if="user.id === 1" title="Search Query By Tag">
                <b-col @click="resetDirectImport()">
                  <b-card header-html="<h2 class=&quot;h7&quot;>Search Query By Tag</h2>" class="h-100">
                    <b-card-text>
                      <b-container>
                        <b-row>
                          <b-col class="border-right">
                            <p>
                              Search for query questions by tag which can then be added to your assignment.
                              <b-icon id="search-by-tag-tooltip"
                                      v-b-tooltip.hover
                                      class="text-muted"
                                      icon="question-circle"
                              />
                              <b-tooltip target="search-by-tag-tooltip" triggers="hover">
                                Using the search box you can find query questions by tag.
                                Note that adding multiple tags will result in a search result which matches all of the
                                conditions.
                              </b-tooltip>
                            </p>
                            <div class="col-7 p-0">
                              <vue-bootstrap-typeahead
                                ref="queryTypeahead"
                                v-model="query"
                                :data="tags"
                                placeholder="Enter a tag"
                              />
                            </div>
                            <div class="mt-3 ">
                              <b-button variant="primary" size="sm" class="mr-2" @click="addTag()">
                                Add Tag
                              </b-button>
                              <b-button variant="success" size="sm" class="mr-2" @click="getQuestionsByTags()">
                                <b-spinner v-if="gettingQuestions" small type="grow" />
                                Get Questions
                              </b-button>
                            </div>
                          </b-col>
                          <b-col>
                            <span class="font-weight-bold">Chosen Tags:</span>
                            <div v-if="chosenTags.length>0">
                              <ol>
                                <li v-for="chosenTag in chosenTags" :key="chosenTag">
                                  <span @click="removeTag(chosenTag)">{{ chosenTag }}
                                    <b-icon icon="trash" variant="danger" /></span>
                                </li>
                              </ol>
                            </div>
                            <div v-else>
                              <span class="text-danger">No tags have been chosen.</span>
                            </div>
                          </b-col>
                        </b-row>
                      </b-container>
                    </b-card-text>
                  </b-card>
                </b-col>
              </b-tab>
              <b-tab v-if="isMe" title="Direct Import By Libretexts ID" class="pb-8"
                     @click="resetDirectImportMessages();showQuestions = false"
              >
                <b-card header-html="<h2 class='h7'>Direct Import By Libretexts ID</h2>" style="height:425px">
                  <b-card-text>
                    <b-container>
                      <b-row>
                        <b-col @click="resetSearchByTag">
                          <p>
                            Perform a direct import of questions directly into your assignment using the Libretexts ID.
                            Please
                            enter
                            your questions using a comma
                            separated list of the form {library}-{page id}.
                          </p>
                          <b-form-group
                            id="default_library"
                            label-cols-sm="5"
                            label-cols-lg="4"
                            label-for="Default Library"
                          >
                            <template slot="label">
                              Default Library
                              <b-icon id="default-library-tooltip"
                                      v-b-tooltip.hover
                                      class="text-muted"
                                      icon="question-circle"
                              />
                              <b-tooltip target="default-library-tooltip" triggers="hover">
                                By setting the default library, you can just enter page ids. As an example, choosing
                                Query
                                as
                                the default
                                library, you can then enter 123,chemistry-927,149 instead of
                                query-123,chemistry-927,query-149.
                              </b-tooltip>
                            </template>
                            <b-form-row>
                              <b-form-select v-model="defaultImportLibrary"
                                             :options="libraryOptions"
                                             @change="setDefaultImportLibrary()"
                              />
                            </b-form-row>
                          </b-form-group>
                        </b-col>
                        <b-col>
                          <b-form-textarea
                            v-model="directImport"
                            aria-label="Libretext IDs to direct import"
                            placeholder="Example. query-1023, chemistry-2213, chem-2213"
                            rows="4"
                            max-rows="5"
                          />
                          <div class="float-right mt-2">
                            <span v-if="directImportingQuestions" class="mr-3">
                              Processing {{ parseInt(directImportIndex) + 1 }} of {{ directImportCount }}
                            </span>
                            <b-button variant="success" size="sm" class="mr-2"
                                      @click="directImportQuestions('libretexts id')"
                            >
                              <b-spinner v-if="directImportingQuestions" small type="grow" />
                              Import Questions
                            </b-button>
                          </div>
                        </b-col>
                      </b-row>
                    </b-container>
                    <div class="pt-4">
                      <div v-if="errorDirectImportIdsMessage.length>0">
                        <b-alert :show="true" variant="danger">
                          <span class="font-weight-bold">{{ errorDirectImportIdsMessage }}</span>
                        </b-alert>
                      </div>
                      <div v-if="directImportIdsAddedToAssignmentMessage.length>0">
                        <b-alert :show="true" variant="success">
                          <span class="font-weight-bold">{{ directImportIdsAddedToAssignmentMessage }}</span>
                        </b-alert>
                      </div>
                      <div v-if="directImportIdsNotAddedToAssignmentMessage.length>0">
                        <b-alert :show="true" variant="info">
                          <span class="font-weight-bold">{{ directImportIdsNotAddedToAssignmentMessage }}</span>
                        </b-alert>
                      </div>
                    </div>
                  </b-card-text>
                </b-card>
              </b-tab>
              <b-tab title="Direct Import By ID" class="pb-8"
                     @click="resetDirectImportMessages();showQuestions = false"
              >
                <b-card header-html="<h2 class='h7'>Direct Import By ID</h2>" style="height:425px">
                  <b-card-text>
                    <b-container>
                      <b-row>
                        <b-col @click="resetSearchByTag">
                          <p>
                            Perform a direct import of questions into your assignment either using the ADAPT ID
                            or the Question ID.
                          </p>
                          <p>
                            ADAPT IDs can be found in the Questions tab of any assignment and are of the form
                            {Assignment
                            ID}-{Question ID}.
                            Question IDs can be copied directly from
                            <span><router-link :to="{path: '/question-editor/my-questions'}" target="_blank">
                              My Questions</router-link>.</span>
                          </p>
                          <p>
                            Please enter the IDs in a comma separated list.
                          </p>
                        </b-col>
                        <b-col>
                          <b-form-textarea
                            v-model="directImport"
                            aria-label="ADAPT IDs to direct import"
                            placeholder="Example. 1027-34, 1029-38, 1051-44, 111130"
                            rows="4"
                            max-rows="5"
                          />
                          <div class="float-right mt-2">
                            <span v-if="directImportingQuestions" class="mr-3">
                              Processing {{ parseInt(directImportIndex) + 1 }} of {{ directImportCount }}
                            </span>
                            <b-button variant="success" size="sm" class="mr-2"
                                      @click="directImportQuestions('adapt id')"
                            >
                              <b-spinner v-if="directImportingQuestions" small type="grow" />
                              Import Questions
                            </b-button>
                          </div>
                        </b-col>
                      </b-row>
                    </b-container>
                    <div class="pt-4">
                      <div v-if="errorDirectImportIdsMessage.length>0">
                        <b-alert :show="true" variant="danger">
                          <span class="font-weight-bold">{{ errorDirectImportIdsMessage }}</span>
                        </b-alert>
                      </div>
                      <div v-if="directImportIdsAddedToAssignmentMessage.length>0">
                        <b-alert :show="true" variant="success">
                          <span class="font-weight-bold">{{ directImportIdsAddedToAssignmentMessage }}</span>
                        </b-alert>
                      </div>
                      <div v-if="directImportIdsNotAddedToAssignmentMessage.length>0">
                        <b-alert :show="true" variant="info">
                          <span class="font-weight-bold">{{ directImportIdsNotAddedToAssignmentMessage }}</span>
                        </b-alert>
                      </div>
                    </div>
                  </b-card-text>
                </b-card>
              </b-tab>
            </b-tabs>
          </div>
        </div>

        <div v-if="questions.length>0 && showQuestions" class="overflow-auto">
          <b-pagination
            v-model="currentPage"
            :total-rows="questions.length"
            :per-page="perPage"
            align="center"
            first-number
            last-number
            @input="changePage(currentPage)"
          />
        </div>
        <div v-if="showQuestions">
          <b-container>
            <b-row v-if="questions[currentPage-1]">
              <span v-if="!questions[currentPage-1].inAssignment">
                <b-button class="mt-1 mb-2 mr-2"
                          variant="primary"
                          size="sm"
                          @click="addQuestion(questions[currentPage-1])"
                >Add Question
                </b-button>
              </span>
              <span v-if="questions[currentPage-1].inAssignment">
                <b-button class="mt-1 mb-2 mr-2"
                          variant="danger"
                          size="sm"
                          @click="isRemixerTab = false; questionToRemove = questions[currentPage-1];openRemoveQuestionModal()"
                >Remove Question
                </b-button>
              </span>
            </b-row>
          </b-container>
          <div>
            <iframe v-if="showQuestions && questions[currentPage-1] && questions[currentPage-1].non_technology"
                    id="non-technology-iframe"
                    allowtransparency="true"
                    frameborder="0"
                    :src="questions[currentPage-1].non_technology_iframe_src"
                    style="width: 1px;min-width: 100%;"
            />
          </div>
          <div v-if="questions[currentPage-1] && questions[currentPage-1].technology_iframe">
            <iframe
              :key="`technology-iframe-${questions[currentPage-1].id}`"
              v-resize="{ log: true, checkOrigin: false }"
              width="100%"
              :src="questions[currentPage-1].technology_iframe"
              frameborder="0"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import { h5pResizer } from '~/helpers/H5PResizer'
import { mapGetters } from 'vuex'
import { submitUploadFile, getAcceptedFileTypes } from '~/helpers/UploadFiles'
import { downloadSolutionFile } from '~/helpers/DownloadFiles'

import Form from 'vform'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import libraries from '~/helpers/Libraries'
import AssessmentTypeWarnings from '~/components/AssessmentTypeWarnings'
import ViewQuestions from '~/components/ViewQuestions'

import {
  h5pText,
  updateOpenEndedInRealTimeMessage,
  updateLearningTreeInNonLearningTreeMessage,
  updateNonLearningTreeInLearningTreeMessage
} from '~/helpers/AssessmentTypeWarnings'

import RemoveQuestion from '~/components/RemoveQuestion'
import { faHeart } from '@fortawesome/free-regular-svg-icons'
import { faBars, faCaretDown, faCaretRight } from '@fortawesome/free-solid-svg-icons'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'
import _ from 'lodash'
import draggable from 'vuedraggable'

export default {
  components: {
    SavedQuestionsFolders,
    VueBootstrapTypeahead,
    AssessmentTypeWarnings,
    Loading,
    RemoveQuestion,
    FontAwesomeIcon,
    ViewQuestions,
    draggable
  },
  middleware: 'auth',
  data: () => ({
    filteredTopicsOptions: [],
    allTopicsShown: false,
    chosenTopicAssignmentId: 0,
    topicsOptions: [],
    topicToMoveQuestionsTo: null,
    chosenTopicId: null,
    chosenAssignment: {},
    isTopic: false,
    folderLevel: 1,
    isPointsByQuestionWeightAndSubmissionsExist: false,
    saveToMyFavoritesKey: 0,
    savedQuestionsFoldersType: 'my_favorites',
    processingGetCollection: false,
    questionIdToMove: 0,
    all: {},
    allSavedQuestionFoldersByQuestionSource: false,
    chosenCourseId: null,
    moveOrRemoveQuestionsMyFavoritesKey: 0,
    updatedDraggable: 0,
    chosenAssignmentIndex: null,
    originalAssignmentQuestions: [],
    questionToRemoveFromFavoritesFolder: {},
    saveToMyFavoritesQuestionIds: [],
    bulkAction: 'null',
    bulkActionOptions: [
      { value: null, text: 'Choose Bulk Action' },
      { value: 'view', text: 'View Questions' },
      { value: 'add_to_assignment', text: 'Add To Assignment' },
      { value: 'bulk_move_to_new_topic', text: 'Move To Topic' }
    ],
    questionType: 'both',
    questionTypeOptions: [
      {
        value: 'both', text: 'Either question type'
      },
      {
        value: 'auto_graded_only', text: 'Auto-graded, only'
      },
      {
        value: 'open_ended_only', text: 'Open-ended, only'
      }],
    filter: '',
    questionSource: null,
    questionSourceOptions: [{ value: null, text: 'Please choose a question source' },
      { value: 'my_favorites', text: 'My Favorites' },
      { value: 'my_questions', text: 'My Questions' },
      { value: 'my_courses', text: 'My Courses' },
      { value: 'commons', text: 'Commons' },
      { value: 'all_public_courses', text: 'All Public Courses' }
    ],
    folderAction: null,
    savedQuestionsFolders: [],
    savedQuestionsFolder: null,
    questionBankScrollHeight: 0,
    questionBankModalShown: false,
    questionToView: {},
    numViewSelectedQuestionsClicked: 0,
    assignmentQuestionsKey: 0,
    heartIcon: faHeart,
    caretDownIcon: faCaretDown,
    caretRightIcon: faCaretRight,
    barsIcon: faBars,
    selectedQuestionIds: [],
    assignmentQuestions: [],
    collection: null,
    collectionsOptions: [{ value: null, text: `Please choose a collection` }],
    assignments: [],
    questionToMove: {},
    chosenAssignmentId: 0,
    assignmentName: '',
    assignmentId: 0,
    remixerKey: 0,
    modalRemoveQuestionKey: 0,
    h5pQuestionsWithAnonymousUsers: false,
    assessmentTypeWarningsKey: 0,
    betaAssignmentsExist: false,
    questionToRemove: {},
    isRemixerTab: true,
    errorDirectImportIdsMessage: '',
    directImportCount: '',
    directImportIndex: '',
    openEndedQuestionsInRealTime: '',
    learningTreeQuestionsInNonLearningTree: '',
    nonLearningTreeQuestions: '',
    showQuestion: false,
    school: '',
    schools: [],
    assessmentType: '',
    loadingQuestion: false,
    defaultImportLibrary: null,
    libraryOptions: libraries,
    directImportIdsNotAddedToAssignmentMessage: '',
    directImportIdsAddedToAssignmentMessage: '',
    directImportingQuestions: false,
    directImport: '',
    questionFilesAllowed: false,
    uploading: false,
    continueLoading: true,
    isLoading: true,
    iframeLoaded: false,
    perPage: 1,
    currentPage: 1,
    query: '',
    tags: [],
    questions: [],
    chosenTags: [],
    question: {},
    showQuestions: false,
    gettingQuestions: false,
    title: '',
    uploadFileForm: new Form({
      questionFile: null,
      assignmentId: null,
      questionId: null
    })
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  created () {
    this.submitUploadFile = submitUploadFile
    this.getAcceptedFileTypes = getAcceptedFileTypes
    this.downloadSolutionFile = downloadSolutionFile
    this.updateOpenEndedInRealTimeMessage = updateOpenEndedInRealTimeMessage
    this.updateLearningTreeInNonLearningTreeMessage = updateLearningTreeInNonLearningTreeMessage
    this.updateNonLearningTreeInLearningTreeMessage = updateNonLearningTreeInLearningTreeMessage
    this.h5pText = h5pText
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to this page.')
      return false
    }

    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.assignmentId = this.$route.params.assignmentId

    for (let i = 1; i < this.libraryOptions.length; i++) {
      let library = this.libraryOptions[i]
      this.libraryOptions[i].text = `${library.text} (${library.value})`
    }
    this.getDefaultImportLibrary()
    this.getAssignmentInfo()
    this.getQuestionWarningInfo()
    this.fixQuestionBankScrollHeight()
  },
  methods: {
    showAllTopics (boolean) {
      for (let i = 0; i < this.assignments.length; i++) {
        this.assignments[i].topics_shown = boolean
      }
    },
    removeTopic (deletedTopicId, moveToAssignmentId, moveToAssignmentNumQuestions, moveToTopicId, moveToTopicNumQuestions) {
      for (let i = 0; i < this.assignments.length; i++) {
        if (this.assignments[i].id === moveToAssignmentId) {
          this.assignments[i].num_questions = moveToAssignmentNumQuestions
        }
        for (let j = 0; j < this.assignments[i]['topics'].length; j++) {
          console.log(this.assignments[i]['topics'][j])
          if (this.assignments[i]['topics'][j].id === moveToTopicId) {
            this.assignments[i]['topics'][j].num_questions = moveToTopicNumQuestions
          }
          if (this.assignments[i]['topics'][j].id === deletedTopicId) {
            this.assignments[i].topics.splice(j, 1)
          }
        }
      }
    },
    addTopicToList (name, assignmentId, topicId) {
      for (let i = 0; i < this.assignments.length; i++) {
        if (this.assignments[i].id === assignmentId) {
          this.assignments[i].topics.push({ assignment_id: assignmentId, name: name, id: topicId, num_questions: 0 })
          this.assignments[i].topics_shown = true
          this.assignments[i].topics = this.assignments[i].topics.sort(function (a, b) {
            return a.name.toLowerCase() < b.name.toLowerCase() ? -1 : 1
          })
          this.topicsOptions.push({ text: name, value: topicId, assignmentId: assignmentId })
          return
        }
      }
    },
    updateTopicInList (name, topicId) {
      for (let i = 0; i < this.assignments.length; i++) {
        for (let j = 0; j < this.assignments[i]['topics'].length; j++) {
          console.log(this.assignments[i]['topics'][j])
          if (this.assignments[i]['topics'][j].id === topicId) {
            this.assignments[i]['topics'][j].name = name
            return
          }
        }
      }
    },
    async getTopicsByCourse (courseId) {
      const { data } = await axios.get(`/api/assignment-topics/course/${courseId}`)
      if (data.type !== 'success') {
        this.$noty.message(data.message)
      }

      this.topicsOptions = [{ text: 'Choose a topic from within this assignment', value: null }]
      for (let i = 0; i < data.topics.length; i++) {
        let topic = data.topics[i]
        this.topicsOptions.push({ text: topic.name, value: topic.id, assignmentId: topic.assignment_id })
      }
    },
    reloadMyFavoritesOptions () {
      this.saveToMyFavoritesKey++
    },
    filterResults () {
      this.assignmentQuestions = this.originalAssignmentQuestions
      this.assignmentQuestions = this.assignmentQuestions.filter(question =>
        (question.topic.toString().includes(this.filter)) ||
        (question.question_id.toString().includes(this.filter)) ||
        (question.text_question && question.text_question.includes(this.filter)) ||
        (question.tags && question.tags.includes(this.filter)) ||
        (question.title && question.title.includes(this.filter))
      )
    },
    getAll () {
      let objectToSum
      let totalQuestions
      totalQuestions = 0
      objectToSum = this.questionChosenFromAssignment() ? this.assignments : this.savedQuestionsFolders
      for (let i = 0; i < objectToSum.length; i++) {
        totalQuestions += objectToSum[i].num_questions
      }
      this.all = {
        name: this.questionChosenFromAssignment()
          ? this.collectionsOptions.find(option => option.value === this.collection).text
          : this.questionSource,
        question_source: this.questionSource,
        num_questions: totalQuestions
      }
    },
    setQuestionId (evt) {
      this.questionIdToMove = evt.item.dataset.questionIdToMove
    },
    async moveToNewTopic (evt) {
      console.log(evt.to)
      if (evt.to.getElementsByClassName('assignment').length || evt.to.getElementsByClassName('all-questions').length) {
        this.$noty.info('You can only move questions from one topic to another topic within the same assignment.')
        await this.getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()
      }
      if (evt.to.getElementsByClassName('topic').length) {
        console.log(evt.to.getElementsByClassName('topic')[0])
        let topicId = evt.to.getElementsByClassName('topic')[0].dataset.topicId
        let assignmentId = evt.to.getElementsByClassName('topic')[0].dataset.assignmentId
        console.log(assignmentId)
        console.log(this.chosenAssignmentId)
        if (parseInt(assignmentId) !== parseInt(this.chosenAssignmentId)) {
          this.$noty.info('You can only move questions to topics within the same assignment.')
          await this.getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()
          return false
        }
        console.log(this.selectedQuestionIds)
        this.selectedQuestionIds = [this.questionIdToMove]
        await this.handleBulkMoveToNewTopic(topicId)
      }
    },
    async handleBulkMoveToNewTopic (topicToMoveQuestionsTo) {
      try {
        const { data } = await axios.patch(`/api/assignment-topics/move/from-assignment/${this.chosenAssignmentId}/to/topic/${topicToMoveQuestionsTo}`,
          { question_ids_to_move: this.selectedQuestionIds })
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getTopicsByAssignment(this.chosenAssignmentId)
          this.$bvModal.hide('modal-bulk-move-to-new-topic')
          this.selectedQuestionIds = []
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      await this.getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()
    },
    async getTopicsByAssignment (assignmentId) {
      try {
        const { data } = await axios.get(`/api/assignment-topics/assignment/${assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        for (let i = 0; i < this.assignments.length; i++) {
          if (this.assignments[i].id === assignmentId) {
            this.assignments[i].topics = data.topics
            this.$forceUpdate()
            return
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    moveToNewFolder (evt) {
      if (evt.to.getElementsByClassName('saved-questions-folder').length) {
        let toFolderId = evt.to.getElementsByClassName('saved-questions-folder')[0].dataset.folderId
        let fromFolderId = this.chosenAssignmentId
        this.$refs.moveOrRemoveQuestionsMyFavorites.moveQuestionToNewFolder(this.questionIdToMove, fromFolderId, toFolderId)
      } else {
        this.$noty.info('Questions can be dragged to your folders on the left.')
      }
    },
    resetBulkActionData () {
      if (!this.questionBankModalShown) {
        this.bulkAction = null
        this.selectedQuestionIds = []
        if (document.getElementById('select_all')) {
          document.getElementById('select_all').checked = false
        }
      }
    },
    filterByQuestionType (type) {
      this.assignmentQuestions = this.originalAssignmentQuestions
      switch (type) {
        case ('both'):
          this.assignmentQuestions = this.originalAssignmentQuestions
          break
        case ('auto_graded_only'):
          this.assignmentQuestions = this.originalAssignmentQuestions.filter(question => question.technology !== 'text')
          break
        case ('open_ended_only'):
          this.assignmentQuestions = this.originalAssignmentQuestions.filter(question => question.technology === 'text')
      }
    },
    initRemoveMyFavoritesQuestion (questionToRemoveFromFavoritesFolder) {
      this.questionToRemoveFromFavoritesFolder = questionToRemoveFromFavoritesFolder
      this.$bvModal.show('modal-init-remove-question-from-favorites-folder')
    },
    initSaveToMyFavorites (questionIds) {
      this.savedQuestionsFoldersType = 'my_favorites'
      this.saveToMyFavoritesQuestionIds = questionIds
      this.$bvModal.show('modal-save-to-my-favorites')
    },
    actOnBulkAction (action) {
      if (action === null) return
      this.savedQuestionsFoldersType = 'my_favorites'
      switch (action) {
        case ('view'):
          this.viewSelectedQuestions()
          break
        case ('add_to_assignment'):
          this.convertQuestionIdsToAddToQuestionsToAdd(this.selectedQuestionIds)
          break
        case ('bulk_move_to_new_topic'):
          this.filteredTopicsOptions = this.topicsOptions.filter(topic => topic.assignmentId === this.chosenAssignmentId)
          this.filteredTopicsOptions = this.filteredTopicsOptions.filter(topic => topic.value !== this.chosenTopicId)
          let defaultOption = {
            value: null,
            text: 'Please choose another topic within this assignment'
          }
          if (this.filteredTopicsOptions.length) {
            this.filteredTopicsOptions.unshift(defaultOption)
          } else {
            this.filteredTopicsOptions = [defaultOption]
          }
          this.topicToMoveQuestionsTo = null
          this.$bvModal.show('modal-bulk-move-to-new-topic')
          break
        default:
          alert(`${action} is not a valid action`)
      }
    },
    getQuestionSourceText () {
      if (this.questionSource) {
        return _.startCase(this.questionSource.replace('_', ' '))
      }
    },
    resetFolderAction () {
      this.folderAction = null
    },
    async initFolderAction (action, questionsFolder = {}) {
      switch (action) {
        case ('new'):
          this.$refs.moveOrRemoveQuestionsMyFavorites.initCreateSavedQuestionsFolder(this.isTopic, this.chosenAssignment.id)
          break
        case ('edit'):
          let chosenTopicAssignmentId = this.isTopic
            ? this.chosenTopicAssignmentId
            : null
          this.$refs.moveOrRemoveQuestionsMyFavorites.initUpdateSavedQuestionsFolder(this.isTopic, chosenTopicAssignmentId, questionsFolder)
          break
        case ('delete'):
          // only allow them to be moved within the current assignment
          let topicsOptions = this.topicsOptions.filter(topic => topic.assignmentId === this.chosenTopicAssignmentId)
          this.$refs.moveOrRemoveQuestionsMyFavorites.initDeleteSavedQuestionsFolder(this.isTopic, topicsOptions, questionsFolder, this.questionSource)
          break
      }
    },
    initMoveOrRemoveSavedQuestion (question) {
      this.$refs.moveOrRemoveQuestionsMyFavorites.initMoveOrRemoveSavedQuestion(question)
    },
    setSavedQuestionsFolder (savedQuestionsFolder) {
      this.savedQuestionsFolder = savedQuestionsFolder
    },
    fixQuestionBankScrollHeight () {
      this.questionBankScrollHeight = (window.screen.height - 200) + 'px'
    },
    setQuestionToView (questionToView) {
      this.questionToView = questionToView
      let assignmentQuestion = this.assignmentQuestions.find(question => question.question_id === this.questionToView.question_id)
      this.questionToView.in_current_assignment = assignmentQuestion.in_current_assignment
      this.questionToView.my_favorites_folder_id = assignmentQuestion.my_favorites_folder_id
      this.questionToView.my_favorites_folder_name = assignmentQuestion.my_favorites_folder_name
      this.questionToView.saved_question_folder = assignmentQuestion.saved_question_folder
      document.getElementById('question-bank-view-questions___BV_modal_title').innerHTML = questionToView.title
      this.$forceUpdate()
    },
    async removeMyFavoritesQuestion (folderId, questionId) {
      try {
        const { data } = await axios.delete(`/api/my-favorites/folder/${folderId}/question/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.$root.$emit('bv::hide::tooltip')
        this.$bvModal.hide('modal-move-or-remove-question')
        this.$bvModal.hide('modal-init-remove-question-from-favorites-folder')
        await this.getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder(false)
        if (this.questionSource === 'my_favorites') {
          await this.getCollection('my_favorites', folderId)
        }
        if (this.questionBankModalShown) {
          this.originalAssignmentQuestions.length
            ? this.setQuestionToView(this.questionToView)
            : this.$bvModal.hide('question-bank-view-questions')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async saveMyFavoritesQuestions () {
      if (this.savedQuestionsFolder === null) {
        this.$noty.info('Please first choose a Favorites folder.')
        return false
      }
      try {
        let chosenAssignmentIds
        if (this.questionSource === 'my_questions') {
          chosenAssignmentIds = [0]
        }
        if (this.chosenAssignmentId) {
          chosenAssignmentIds = [this.chosenAssignmentId]
        }
        if (this.chosenCourseId) {
          chosenAssignmentIds = []
          for (let i = 0; i < this.saveToMyFavoritesQuestionIds.length; i++) {
            let questionId = this.saveToMyFavoritesQuestionIds[i]
            chosenAssignmentIds.push(this.assignmentQuestions.find(question => question.question_id === questionId).assignment_id)
          }
        }
        const { data } = await axios.post('/api/my-favorites',
          {
            question_ids: this.saveToMyFavoritesQuestionIds,
            folder_id: this.savedQuestionsFolder,
            chosen_assignment_ids: chosenAssignmentIds
          })

        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        await this.getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()
        if (this.questionBankModalShown) {
          this.setQuestionToView(this.questionToView)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-save-to-my-favorites')
    },
    async removeQuestionFromRemixedAssignment (questionId) {
      this.$bvModal.hide('modal-remove-question')
      this.$bvModal.hide(`modal-view-question-${this.typeOfRemixer}`)

      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${questionId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          if (this.typeOfRemixer === 'saved-questions') {
            this.publicCourseAssignmentQuestions = this.originalChosenPublicCourseAssignmentQuestions
          } else {
            if (this.questionSource === 'my_courses' &&
              this.chosenAssignmentId &&
              this.assignmentId &&
              parseInt(this.chosenAssignmentId) === parseInt(this.assignmentId)) {
              alert(this.chosenAssignmentId)
              await this.getTopicsByAssignment(this.chosenAssignmentId)
            }
            await this.getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder()
            if (this.questionBankModalShown) {
              this.questionToView.in_assignment = this.assignmentQuestions.find(question => question.question_id === this.questionToView.question_id).in_assignment === true
              this.$forceUpdate()
            }
          }
          await this.getQuestionWarningInfo()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initGetQuestionSource (questionSource) {
      this.savedQuestionsFolders = this.assignments = this.assignmentQuestions = []
      this.collection = null
      if (!['commons', 'my_courses', 'all_public_courses', 'my_questions', 'my_favorites'].includes(questionSource)) {
        if (questionSource) {
          alert(`${questionSource} is not a valid question source`)
        }
        return false
      }

      this.questionChosenFromAssignment()
        ? this.getCollections(questionSource)
        : this.getCollection(questionSource)
    },
    async getCollections (questionSource) {
      this.resetBulkActionData()
      this.publicCourse = null
      try {
        let url
        let collectionName
        let defaultText
        switch (questionSource) {
          case ('commons'):
            url = '/api/courses/commons'
            collectionName = 'commons_courses'
            defaultText = 'collection'
            break
          case ('my_courses'):
            collectionName = 'courses'
            defaultText = 'course'
            url = '/api/courses'
            break
          case ('all_public_courses'):
            collectionName = 'public_courses'
            defaultText = 'course'
            url = '/api/courses/public'
            break
        }
        this.collectionsOptions = [{ value: null, text: `Please choose a ${defaultText}` }]
        const { data } = await axios.get(url)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        if (data[collectionName]) {
          for (let i = 0; i < data[collectionName].length; i++) {
            let course = data[collectionName][i]
            let text = course.name
            if (questionSource === 'all_public_courses' && course.instructor === 'Commons Instructor') {
              continue
            }
            if (this.questionSource === 'all_public_courses') {
              text += ` --- ${course.instructor}`
              if (course.school !== 'Not Specified') {
                text += `/${course.school}`
              }
            }
            let publicCourse = { value: course.id, text: text }
            this.collectionsOptions.push(publicCourse)
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    convertQuestionIdsToAddToQuestionsToAdd (questionIdsToAdd) {
      let questionsToAdd = []
      for (let i = 0; i < this.assignmentQuestions.length; i++) {
        let question = this.assignmentQuestions[i]
        if (questionIdsToAdd.includes(question.question_id)) {
          questionsToAdd.push(question)
        }
      }
      this.addQuestions(questionsToAdd)
    },
    async addQuestions (questionsToAdd) {
      this.$root.$emit('bv::hide::tooltip')
      if (['commons', 'my_courses', 'all_public_courses'].includes(this.questionSource)) {
        for (let i = 0; i < questionsToAdd.length; i++) {
          questionsToAdd[i].assignment_id = this.chosenAssignmentId
            ? this.chosenAssignmentId
            : this.assignmentQuestions.find(question => question.question_id === questionsToAdd[i].question_id).assignment_id
        }
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/remix-assignment-with-chosen-questions`,
          {
            'chosen_questions': questionsToAdd,
            'question_source': this.questionSource

          })
        if (data.type === 'error') {
          this.$noty.error(data.message, {
            timeout: 8000
          })
        }
        if (data.type === 'success') {
          for (let i = 0; i < questionsToAdd.length; i++) {
            this.assignmentQuestions.find(question => question.question_id === questionsToAdd[i].question_id).in_current_assignment = true
            this.$forceUpdate()
          }
          if (this.questionBankModalShown) {
            this.setQuestionToView(this.questionToView)
          } else {
            this.selectedQuestionIds = []
            document.getElementById('select_all').checked = false
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }

      await this.getQuestionWarningInfo()

      if (this.typeOfRemixer === 'saved-questions') {
        this.publicCourseAssignmentQuestions = this.originalChosenPublicCourseAssignmentQuestions
      }
    },
    viewSelectedQuestions () {
      this.numViewSelectedQuestionsClicked++
      this.$bvModal.show('question-bank-view-questions')
    },

    selectAll () {
      this.selectedQuestionIds = []
      let checkboxes = document.getElementsByClassName('selected-question-id')
      if (document.getElementById('select_all').checked) {
        for (let checkbox of checkboxes) {
          this.selectedQuestionIds.push(parseInt(checkbox.value))
        }
      }
    },
    questionChosenFromAssignment () {
      return !['my_favorites', 'my_questions'].includes(this.questionSource)
    },
    async getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder (clearAll = true) {
      if (!this.questionBankModalShown && clearAll) {
        this.processingGetCollection = true
        this.assignmentQuestions = []
      }
      try {
        let folderInformation
        folderInformation = {
          user_assignment_id: this.$route.params.assignmentId,
          collection_type: this.questionChosenFromAssignment() ? 'assignment' : this.questionSource
        }
        if (this.questionChosenFromAssignment()) {
          this.chosenCourseId
            ? folderInformation.course_id = this.chosenCourseId
            : folderInformation.assignment_id = this.chosenAssignmentId
          if (this.chosenTopicId) {
            folderInformation.topic_id = this.chosenTopicId
          }
        } else {
          this.allSavedQuestionFoldersByQuestionSource
            ? folderInformation.folder_id = 'all_folders'
            : folderInformation.folder_id = this.chosenAssignmentId
        }
        const { data } = await axios.post('/api/question-bank/potential-questions-with-course-level-usage-info', folderInformation)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.chosenCourseId = null
          return false
        }
        this.originalAssignmentQuestions = data.assignment_questions
        for (let i = 0; i < this.originalAssignmentQuestions.length; i++) {
          if (this.originalAssignmentQuestions[i].topic === null) {
            this.originalAssignmentQuestions[i].topic = 'None'
          }
        }
        this.resetBulkActionData()
        this.filterByQuestionType(this.questionType)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingGetCollection = false
    },
    async getCollection (collection, toFolderId = null) {
      this.processingGetCollection = true
      this.chosenCourseId = null
      this.resetBulkActionData()
      let url
      switch (this.questionSource) {
        case ('my_courses'):
          url = `/api/assignments/courses/${collection}`
          break
        case ('all_public_courses'):
          url = `/api/assignments/courses/public/${collection}/names`
          break
        case ('commons'):
          url = `/api/assignments/commons/${collection}`
          break
        case ('my_favorites'):
        case ('my_questions'):
          this.moveOrRemoveQuestionsMyFavoritesKey++
          url = `/api/saved-questions-folders/${this.questionSource}`
          break
        default:
          alert(`${collection} does not exist.  Please contact us.`)
          return false
      }
      try {
        const { data } = await axios.get(url)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        if (this.questionChosenFromAssignment()) {
          data.assignments.forEach(function (assignment) {
            assignment.topics_shown = false
          })
          this.assignments = data.assignments
        } else {
          this.savedQuestionsFolders = data.saved_questions_folders
        }
        this.chosenAssignmentId = this.questionChosenFromAssignment()
          ? this.assignments[0].id
          : toFolderId || this.savedQuestionsFolders[0].id
        if (this.questionSource === 'my_courses') {
          await this.getTopicsByCourse(this.collection)
        }
        await this.getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder(this.chosenAssignmentId)
        this.updatedDraggable++
        this.assignmentQuestionsKey++
        this.getAll()
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingGetCollection = false
    },
    resetDirectImportMessages () {
      this.directImportIdsAddedToAssignmentMessage = ''
      this.errorDirectImportIdsMessage = ''
      this.directImportIdsNotAddedToAssignmentMessage = ''
    },
    setQuestionToRemove (questionToRemove, chosenAssignmentId) {
      this.questionToRemove = questionToRemove
      this.chosenAssignmentId = chosenAssignmentId
      this.$bvModal.show('modal-remove-question')
    },
    submitRemoveQuestion () {
      this.isRemixerTab ? this.removeQuestionFromRemixedAssignment(this.questionToRemove.question_id) : this.removeQuestionFromSearchResult(this.questionToRemove)
    },
    openRemoveQuestionModal () {
      this.$bvModal.show('modal-remove-question')
    },
    async getQuestionWarningInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.betaAssignmentsExist = data.beta_assignments_exist
        this.h5pQuestionsWithAnonymousUsers = data.h5p_questions_exist && data.course_has_anonymous_users
        this.isPointsByQuestionWeightAndSubmissionsExist = data.is_question_weight && data.submissions_exist
        this.assessmentTypeWarningsKey = 1
        this.items = data.rows
        let hasNonH5P
        for (let i = 0; i < this.items.length; i++) {
          if (this.items[i].submission !== 'h5p') {
            hasNonH5P = true
          }
          if (this.assessmentType !== 'delayed' && !this.items[i].auto_graded_only) {
            this.openEndedQuestionsInRealTime += this.items[i].order + ', '
          }
        }
        this.updateOpenEndedInRealTimeMessage()
        this.updateLearningTreeInNonLearningTreeMessage()
        this.updateNonLearningTreeInLearningTreeMessage()

        if (this.assessment_type === 'clicker' && hasNonH5P) {
          this.$bvModal.show('modal-non-h5p')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getDefaultImportLibrary () {
      try {
        const { data } = await axios.get('/api/questions/default-import-library')
        this.defaultImportLibrary = data.default_import_library
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async setDefaultImportLibrary () {
      try {
        const { data } = await axios.post('/api/questions/default-import-library', { 'default_import_library': this.defaultImportLibrary })
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    resetDirectImport () {
      this.questions = []
      this.directImportIdsAddedToAssignmentMessage = ''
      this.directImportIdsNotAddedToAssignmentMessage = ''
      this.directImport = ''
    },
    resetSearchByTag () {
      this.showQuestions = false
      this.chosenTags = []
    },
    async directImportQuestions (type) {
      if (this.directImportingQuestions) {
        let timeToProcess = Math.ceil(((this.directImport.match(/,/g) || []).length) / 3)
        let message = `Please be patient.  Validating all of your Libretexts Ids  will take about ${timeToProcess} seconds.`
        this.$noty.info(message)
        return false
      }

      this.pageIdsAddedToAssignmentMessage = ''
      this.pageIdsNotAddedToAssignmentMessage = ''
      this.errorDirectImportIdsMessage = ''
      this.directImportIdsAddedToAssignmentMessage = ''
      this.directImportIdsNotAddedToAssignmentMessage = ''
      this.directImportingQuestions = true
      let directImport = this.directImport.split(',')
      this.directImportCount = directImport.length
      let directImportIdsAddedToAssignment = []
      let directImportIdsNotAddedToAssignment = []
      let errorDirectImportIds = []
      for (this.directImportIndex = 0; this.directImportIndex < directImport.length; this.directImportIndex++) {
        try {
          const { data } = await axios.post(`/api/questions/${this.assignmentId}/direct-import-question`,
            {
              'direct_import': directImport[this.directImportIndex],
              'type': type
            }
          )
          if (data.type === 'error') {
            errorDirectImportIds.push(directImport[this.directImportIndex])
            this.$noty.error(data.message)
          }
          if (data.direct_import_id_added_to_assignment) {
            directImportIdsAddedToAssignment.push(data.direct_import_id_added_to_assignment)
          }
          if (data.direct_import_id_not_added_to_assignment) {
            directImportIdsNotAddedToAssignment.push(data.direct_import_id_not_added_to_assignment)
          }
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      this.directImportingQuestions = false
      directImportIdsAddedToAssignment = directImportIdsAddedToAssignment.join(', ')
      directImportIdsNotAddedToAssignment = directImportIdsNotAddedToAssignment.join(', ')
      let verb
      verb = directImportIdsAddedToAssignment.includes(',') ? 'were' : 'was'
      if (directImportIdsAddedToAssignment !== '') {
        this.directImportIdsAddedToAssignmentMessage = `${directImportIdsAddedToAssignment} ${verb} added to this assignment.`
      }
      if (errorDirectImportIds.length) {
        this.errorDirectImportIdsMessage = `Errors found with: ${errorDirectImportIds}`
      }
      verb = directImportIdsNotAddedToAssignment.includes(',') ? 'were' : 'was'
      let pronoun = directImportIdsNotAddedToAssignment.includes(',') ? 'they' : 'it'
      if (directImportIdsNotAddedToAssignment !== '') {
        this.directImportIdsNotAddedToAssignmentMessage = `${directImportIdsNotAddedToAssignment} ${verb} not added to this assignment since ${pronoun} ${verb} already a part of the assignment.`
      }
      this.directImport = ''
    },
    openUploadFileModal (questionId) {
      this.uploadFileForm.errors.clear(this.uploadFileType)
      this.uploadFileForm.questionId = questionId
      this.uploadFileForm.assignmentId = this.assignmentId
    },
    async handleOk (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      if (this.uploading) {
        this.$noty.info('Please be patient while the file is uploading.')
        return false
      }
      this.uploading = true
      try {
        await this.submitUploadFile('solution', this.uploadFileForm, this.$noty, this.$nextTick, this.$bvModal, this.questions[this.currentPage - 1], '/api/solution-files')
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.uploading = false
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/get-questions-info`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        let assignment = data.assignment
        this.assignmentName = assignment.name
        this.title = `Add Questions to "${this.assignmentName}"`
        this.assessmentType = assignment.assessment_type
        this.questionFilesAllowed = (assignment.submission_files === 'q')// can upload at the question level
      } catch (error) {
        this.title = 'Add Questions'
      }
      if (this.continueLoading) { // OK to load the rest of the page
        this.getTags()
        h5pResizer()
      }
      this.isLoading = false
    },
    changePage (currentPage) {
      this.$nextTick(() => {
        let iframeId = this.questions[currentPage - 1].iframe_id
        iFrameResize({ log: false }, `#${iframeId}`)
        iFrameResize({ log: false }, '#non-technology-iframe')
      })
    },
    removeTag (chosenTag) {
      this.chosenTags = _.without(this.chosenTags, chosenTag)
      this.questions = []
    },
    addTag () {
      if (this.chosenTags.length === 0 && this.query === '') {
        this.$noty.error('You did not include a tag.')
        return false
      }

      if (!this.tags.includes(this.query)) {
        this.$noty.error(`The tag <strong>${this.query}</strong> does not exist in our database.`)
        this.$refs.queryTypeahead.inputValue = this.query = ''
        return false
      }

      if (!this.chosenTags.includes(this.query)) {
        this.chosenTags.push(this.query)
      }
      this.$refs.queryTypeahead.inputValue = this.query = '' // https://github.com/alexurquhart/vue-bootstrap-typeahead/issues/22
      return true
    },
    async getTags () {
      try {
        const { data } = await axios.get(`/api/tags`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          this.tags = data.tags
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async addQuestion (question) {
      try {
        this.questions[this.currentPage - 1].questionFiles = false
        const { data } = await axios.post(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].inAssignment = true
        }
      } catch (error) {
        this.$noty.error('We could not add the question to the assignment.  Please try again or contact us for assistance.')
      }
    },
    async removeQuestionFromSearchResult (question) {
      this.$bvModal.hide('modal-remove-question')
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
        if (data.type === 'info') {
          this.$noty.info(data.message)
          this.questions[this.currentPage - 1].inAssignment = false
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },
    async getQuestionsByTags () {
      this.questions = []
      this.showQuestions = false
      this.gettingQuestions = true
      if (this.query) {
        // in case they didn't click
        let validTag = this.addTag()
        if (!validTag) {
          this.gettingQuestions = false
          return false
        }
      }
      try {
        if (this.chosenTags.length === 0) {
          this.$noty.error('Please choose at least one tag.')
          this.gettingQuestions = false
          return false
        }
        const { data } = await axios.post(`/api/questions/getQuestionsByTags`, { 'tags': this.chosenTags })
        let questionsByTags = data

        if (questionsByTags.type === 'success' && questionsByTags.questions.length > 0) {
          // get whether in the assignment and get the url
          const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/question-info`)

          let questionInfo = data

          if ((questionInfo.type === 'success')) {
            for (let i = 0; i < questionsByTags.questions.length; i++) {
              questionsByTags.questions[i].inAssignment = questionInfo.question_ids.includes(questionsByTags.questions[i].id)

              questionsByTags.questions[i].questionFiles = questionInfo.question_files.includes(questionsByTags.questions[i].id)
            }

            this.questions = questionsByTags.questions
            let iframeId = this.questions[0].iframe_id
            this.$nextTick(() => {
              iFrameResize({ log: false }, `#${iframeId}`)
              iFrameResize({ log: false }, '#non-technology-iframe')
            })

            this.showQuestions = true
          } else {
            this.$noty.error(questionInfo.message)
          }
        } else {
          let timeout = questionsByTags.timeout ? questionsByTags.timeout : 6000
          this.$noty.error(questionsByTags.message, { timeout: timeout })
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.gettingQuestions = false
    },
    async getStudentView (assignmentId) {
      await this.$router.push(`/assignments/${assignmentId}/questions/view`)
    }
  },
  metaInfo () {
    return { title: 'Get Questions' }
  }
}
</script>
<style scoped>
.question-bank-scroll {
  overflow-y: auto;
}

.wrapWord {
  word-wrap: break-word;
  max-width: 150px;
}

.wrapWord {
  word-wrap: break-word;
  max-width: 150px;
}

.header {
  position: sticky;
  top: 0;
}

.saved-question-folder-list {
  border-bottom: 0
}

thead th {
  background-color: white;
  color: black;
}
</style>
<style>
body, html {
  overflow: visible;

}

input[type=checkbox] {
  transform: scale(1.25);
}
</style>
