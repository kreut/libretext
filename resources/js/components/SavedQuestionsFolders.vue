<template>
  <span>
    <b-modal id="modal-move-or-remove-question"
             title="Move/remove to new question"
    >
      <p>Either move the question to a new {{ typeText }} folder or remove it from your {{ typeText }} folder.  Removing a question
        from a folder has no impact on its status in an assignment.</p>
      Move {{ questionToMoveOrRemove.title }} to:   <b-form-select
      id="saved_questions_folders"
      v-model="savedQuestionsFolderToMoveQuestionTo"
      style="width: 300px"
      :options="moveToFolderOptions"
      size="sm"
      @change="checkIfCreateNewFolder($event)"
    />
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-move-or-remove-question')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="removeMyFavoritesQuestion"
        >
          Remove Question
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleMoveQuestionToANewFolder"
        >
          Move Question
        </b-button>
      </template>
    </b-modal>
       <b-modal id="modal-init-delete-topic"
                :title="`Delete the topic ${folderToDelete.name}`"
                size="lg"
       >
      <p>You are about to delete the topic  {{ folderToDelete.name }}.</p>Please choose a <span v-if="isTopic"
       >topic</span><span v-if="!isTopic">folder</span> in which to remove the current questions:
     <b-form-select
       id="my_questions_folder"
       v-model="questionsFolderToMoveQuestionsTo"
       class="mt-2"
       style="width: 300px"
       :options="deleteFolderOptions"
       size="sm"
     />

      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-init-delete-topic')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="handleDeleteTopic"
        >
          Delete topic
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-init-delete-saved-questions-folder"
             :title="`Delete the folder ${folderToDelete.name}`"
             size="lg"
    >
      You are about to delete the folder  {{ folderToDelete.name }}. <span v-if="type === 'my_favorites'">Would you like to:</span>
      <b-form-group
        v-if="type === 'my_questions'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="my_questions_folder"
        label="Move the questions to:"
      ><b-form-select
        id="my_questions_folder"
        v-model="questionsFolderToMoveQuestionsTo"
        class="mt-2"
        style="width: 300px"
        :options="deleteFolderOptions"
        size="sm"
      />
      </b-form-group>
      <b-form-group v-if="type === 'my_favorites'">
        <b-form-radio-group v-model="deleteSavedFolderAction"
                            aria-describedby="Choose what to do with saved questions in this folder"
                            name="choose-action-for-saved-questions-in-deleted-folder"
                            stacked
        >
          <b-form-radio value="move">Move the questions to:   <b-form-select
            id="my_favorites_folders"
            v-model="questionsFolderToMoveQuestionsTo"
            style="width: 300px"
            :options="deleteFolderOptions"
            size="sm"
          /></b-form-radio>
          <b-form-radio value="delete_without_moving">Delete all saved questions in the folder</b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-init-delete-saved-questions-folder')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="handleDeleteSavedQuestionsFolder"
        >
          Delete folder
        </b-button>
      </template>
    </b-modal>
    <b-modal
      v-if="createModalAddSavedQuestionsFolder"
      :id="modalId"
      :title="getSavedQuestionsFolderTitle()"
      @hide="isFolderUpdate = false;$bvModal.hide(modalId);"
    >
      <RequiredText :plural="false"/>
      <b-container fluid>
        <b-row>
          <b-col sm="3">
            <label for="saved-questions-folder"><span v-if="isFolderUpdate">New </span>Name*</label>
          </b-col>
          <b-col sm="9">
            <b-form-input
              id="saved-questions-folder"
              v-model="savedQuestionsFolderForm.name"
              required
              type="text"
              placeholder=""
              :class="{ 'is-invalid': savedQuestionsFolderForm.errors.has('name') }"
              @keydown="savedQuestionsFolderForm.errors.clear('name')"
            />
            <has-error :form="savedQuestionsFolderForm" field="name"/>
          </b-col>
        </b-row>
      </b-container>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(modalId)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleCreateAndUpdate()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
    <b-form-select
      id="saved_questions_folders"
      v-model="savedQuestionsFolder"
      size="sm"
      style="width: 250px"
      :options="savedQuestionsFoldersOptions"
      @change="changeSavedQuestionsFolder($event)"
    />
    <AllFormErrors v-if="createModalAddSavedQuestionsFolder" :all-form-errors="allFormErrors"
                   :modal-id="'modal-form-errors-add-saved-questions-folder'"
    />
  </span>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import _ from 'lodash'

export default {
  name: 'SavedQuestionsFolders',
  components: { AllFormErrors },
  props: {
    folderLevel: {
      type: Number,
      default: 1
    },
    assignment: {
      type: Object,
      default: () => {
      }
    },
    modalId: {
      type: String,
      default: 'modal-add-saved-questions-folder'
    },
    type: {
      type: String,
      default: 'my_favorites'
    },
    createModalAddSavedQuestionsFolder: {
      /**
       * Needed since I was getting a double modal when doing the add new saved questions folder
       */
      type: Boolean,
      default: false
    },
    questionSourceIsMyFavorites: {
      type: Boolean,
      default: false
    },
    initSavedQuestionsFolder: {
      type: [Number, null],
      default: null
    },
    folderToChooseFrom: {
      type: String,
      default: 'My Favorites'
    }
  },
  data: () => ({
    isTopic: false,
    typeText: '',
    savedQuestionsFolderToMoveQuestionTo: 0,
    moveToFolderOptions: [],
    originalFolderId: 0,
    questionToMoveOrRemove: 0,
    folderToUpdate: '',
    isFolderUpdate: false,
    questionsFolderToMoveQuestionsTo: null,
    deleteSavedFolderAction: 'move',
    deleteFolderOptions: [],
    folderToDelete: {},
    allFormErrors: [],
    savedQuestionsFolder: 0,
    savedQuestionsFoldersOptions: [],
    savedQuestionsFolderForm: new Form({
      name: ''
    }),
    savedQuestionsSubFolderForm: new Form({
      name: ''
    })
  }),
  mounted () {
    this.savedQuestionsFoldersOptions = [{
      text: `Choose a ${this.folderToChooseFrom} folder`,
      value: null
    }]
    this.typeText = this.getTypeText()
    if (this.type) {
      this.getSavedQuestionsFolders()
    }
  },
  methods: {
    getSavedQuestionsFolderTitle () {
      if (this.isFolderUpdate) {
        return `Update ${this.folderToUpdate.name}`
      } else {
        return this.isTopic
          ? `New topic for ${this.assignment.name}`
          : `New ${this.getTypeText()} Folder`
      }
    },
    getTypeText () {
      return this.type ? _.startCase(this.type.replace('_', ' ')) : ''
    },
    checkIfCreateNewFolder (folder) {
      if (folder === 0) {
        this.isFolderUpdate = false
        this.$bvModal.show(this.modalId)
      }
    },
    removeMyFavoritesQuestion () {
      this.$emit('removeMyFavoritesQuestion', this.originalFolderId, this.questionToMoveOrRemove.question_id)
    },
    async moveQuestionToNewFolder (questionId, fromFolderId, toFolderId) {
      try {
        const { data } = await axios.patch(`/api/saved-questions-folders/move/${questionId}/from/${fromFolderId}/to/${toFolderId}`)
        if (data.type !== 'error') {
          this.$emit('reloadSavedQuestionsFolders', 'my_favorites', fromFolderId)
        }
        this.$noty.info(data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleMoveQuestionToANewFolder () {
      try {
        const { data } = await axios.patch(`/api/saved-questions-folders/move/${this.questionToMoveOrRemove.question_id}/from/${this.originalFolderId}/to/${this.savedQuestionsFolderToMoveQuestionTo}`)
        if (data.type !== 'error') {
          this.$emit('getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder', this.originalFolderId)
          this.$bvModal.hide('modal-move-or-remove-question')
          this.$emit('reloadSavedQuestionsFolders', 'my_favorites')
        }
        this.$noty.info(data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initMoveOrRemoveSavedQuestion (questionToMoveOrRemove) {
      this.originalFolderId = questionToMoveOrRemove.my_favorites_folder_id
      this.questionToMoveOrRemove = questionToMoveOrRemove
      this.$bvModal.show('modal-move-or-remove-question')
    },
    initCreateSavedQuestionsFolder (isTopic, chosenAssignmentId) {
      this.isTopic = isTopic
      if (this.isTopic) {
        this.savedQuestionsFolderForm.assignment_id = chosenAssignmentId
      }
      this.isFolderUpdate = false
      this.$bvModal.show(this.modalId)
      console.log(this.isTopic)
    },
    initUpdateSavedQuestionsFolder (isTopic, chosenTopicAssignmentId, folderToUpdate) {
      this.isTopic = isTopic
      this.isFolderUpdate = true
      this.folderToUpdate = folderToUpdate
      console.log('is topic ' + this.isTopic)
      if (this.isTopic) {
        this.savedQuestionsFolderForm.topic_id = folderToUpdate.id
        this.savedQuestionsFolderForm.assignment_id = chosenTopicAssignmentId
        console.log(this.savedQuestionsFolderForm)
      } else {
        this.savedQuestionsFolderForm.folder_id = folderToUpdate.id
      }
      this.$bvModal.show(this.modalId)
    },
    async handleDeleteTopic () {
      try {
        const { data } = await axios.post(`/api/assignment-topics/delete/${this.folderToDelete.id}`, {
          move_to_topic_id: this.questionsFolderToMoveQuestionsTo
        })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.$emit('removeTopic',
          this.folderToDelete.id,
          data.move_to_assignment_id,
          data.move_to_assignment_num_questions,
          this.questionsFolderToMoveQuestionsTo,
          data.move_to_topic_num_questions)
        this.$bvModal.hide('modal-init-delete-topic')
      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    async handleDeleteSavedQuestionsFolder () {
      console.log(this.folderToDelete)
      try {
        const { data } = await axios.post(`/api/saved-questions-folders/delete/${this.folderToDelete.id}`, {
          action: this.deleteSavedFolderAction,
          move_to_folder_id: this.questionsFolderToMoveQuestionsTo,
          question_source: this.type
        })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        if (this.deleteSavedFolderAction === 'delete_without_moving') {
          this.savedQuestionsFoldersOptions = this.savedQuestionsFoldersOptions.filter(folder => folder.value !== this.folderToDelete.id)
        }
        this.$emit('resetFolderAction')
        this.$emit('reloadSavedQuestionsFolders', 0)
        this.$bvModal.hide('modal-init-delete-saved-questions-folder')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initDeleteSavedQuestionsFolder (isTopic, topicsOptions, folder) {
      this.folderToDelete = folder
      let options = isTopic ? topicsOptions : this.savedQuestionsFoldersOptions
      let modalId = isTopic ? 'modal-init-delete-topic' : 'modal-init-delete-saved-questions-folder'
      this.deleteFolderOptions = options.filter(folder => ![0, null, this.folderToDelete.id].includes(folder.value))
      console.log(options)
      console.log(this.deleteFolderOptions)
      if (isTopic && (!this.deleteFolderOptions.length || !folder.num_questions)) {
        this.questionsFolderToMoveQuestionsTo = null
        this.handleDeleteTopic()
      } else {
        this.questionsFolderToMoveQuestionsTo = this.deleteFolderOptions[0].value
        this.deleteSavedFolderAction = 'move'
        this.$bvModal.show(modalId)
      }
    },
    async handleCreateAndUpdate () {
      try {
        let name
        let topicId
        let assignmentId
        if (!this.isTopic) {
          this.savedQuestionsFolderForm.type = this.type
        }
        if (this.isTopic) {
          name = this.savedQuestionsFolderForm.name
          topicId = this.savedQuestionsFolderForm.topic_id
          assignmentId = this.savedQuestionsFolderForm.assignment_id
        }
        let url = this.isTopic ? `/api/assignment-topics` : '/api/saved-questions-folders'
        let method = this.isFolderUpdate ? 'patch' : 'post'
        const { data } = await this.savedQuestionsFolderForm[method](url)

        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.savedQuestionsFolderForm = new Form({
            name: ''
          })

          console.log(this.isTopic)

          if (!this.isTopic) {
            this.savedQuestionsFoldersOptions = [{
              text: 'Choose a Favorites folder',
              value: null
            }]
            await this.getSavedQuestionsFolders()
            this.moveToFolderOptions = this.savedQuestionsFoldersOptions.filter(folder => ![null, this.originalFolderId].includes(folder.value))
            this.savedQuestionsFolderToMoveQuestionTo = this.moveToFolderOptions[this.moveToFolderOptions.length - 1].value // right before New Value
            this.savedQuestionsFolder = data.folder_id
          }
          this.$emit('resetFolderAction')
          if (!this.isFolderUpdate && !this.isTopic) {
            this.$emit('savedQuestionsFolderSet', this.savedQuestionsFolder)
          }

          if (this.type === 'my_favorites') {
            await this.$emit('reloadMyFavoritesOptions', this.savedQuestionsFolder)
            this.$emit('savedQuestionsFolderSet', this.savedQuestionsFolder)
          }
          if (this.questionSourceIsMyFavorites || this.type === 'my_questions') {
            this.$emit('reloadSavedQuestionsFolders', this.type)
          }

          if (this.isTopic) {
            console.log(this.isFolderUpdate)
            this.isFolderUpdate
              ? this.$emit('updateTopicInList', name, topicId)
              : this.$emit('addTopicToList', name, assignmentId, data.topic_id)
          }
          this.$bvModal.hide(this.modalId)
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.savedQuestionsFolderForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-add-saved-questions-folder')
        }
      }
    },
    changeSavedQuestionsFolder (savedQuestionsFolder) {
      if (savedQuestionsFolder === 0) {
        this.isFolderUpdate = false
        this.$bvModal.show(this.modalId)
      } else {
        this.$emit('savedQuestionsFolderSet', savedQuestionsFolder)
      }
    },
    async getSavedQuestionsFolders () {
      try {
        const { data } = await axios.get(`/api/saved-questions-folders/${this.type}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let savedQuestionsFolders = data.saved_questions_folders
        for (let i = 0; i < savedQuestionsFolders.length; i++) {
          this.savedQuestionsFoldersOptions.push({
            text: savedQuestionsFolders[i].name,
            value: savedQuestionsFolders[i].id
          })
        }
        this.savedQuestionsFoldersOptions.push({
          text: 'New Folder',
          value: 0
        })
        this.savedQuestionsFolder = this.initSavedQuestionsFolder ? this.initSavedQuestionsFolder : this.savedQuestionsFoldersOptions[0].value
        this.$emit('exportSavedQuestionsFolders', this.savedQuestionsFoldersOptions)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }

}
</script>

<style scoped>

</style>
