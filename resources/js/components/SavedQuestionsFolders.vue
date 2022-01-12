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
    <b-modal id="modal-init-delete-saved-questions-folder"
             :title="`Delete the folder ${savedFolderToDelete.name}`"
             size="lg"
    >
      You are about to delete the folder  {{ savedFolderToDelete.name }}. <span v-if="type === 'my_favorites'">Would you like to:</span>
      <b-form-group
        v-if="type === 'my_questions'"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="my_questions_folder"
        label="Move the questions to:"
      ><b-form-select
        id="my_questions_folder"
        v-model="savedQuestionsFolderToMoveQuestionsTo"
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
            v-model="savedQuestionsFolderToMoveQuestionsTo"
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
      id="modal-add-saved-questions-folder"
      :title="isFolderUpdate ? `Update ${folderToUpdate.text}` : 'New Folder'"
      @hide="isFolderUpdate = false;$bvModal.hide('modal-add-saved-questions-folder')"
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
              aria-required="true"
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
          @click="$bvModal.hide('modal-add-saved-questions-folder')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleCreateSavedQuestionsFolder"
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
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-add-saved-questions-folder'"/>
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
    type: {
      type: String,
      default: 'my_favorites'
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
    typeText: '',
    savedQuestionsFolderToMoveQuestionTo: 0,
    moveToFolderOptions: [],
    originalFolderId: 0,
    questionToMoveOrRemove: 0,
    folderToUpdate: '',
    isFolderUpdate: false,
    savedQuestionsFolderToMoveQuestionsTo: null,
    deleteSavedFolderAction: 'move',
    deleteFolderOptions: [],
    savedFolderToDelete: {},
    allFormErrors: [],
    savedQuestionsFolder: 0,
    savedQuestionsFoldersOptions: [],
    savedQuestionsFolderForm: new Form({
      name: ''
    })
  }),
  mounted () {
    this.savedQuestionsFoldersOptions = [{
      text: `Choose a ${this.folderToChooseFrom} folder`,
      value: null
    }]
    this.typeText = this.type ? _.startCase(this.type.replace('_', ' ')) : ''
    if (this.type) {
      this.getSavedQuestionsFolders()
    }
  },
  methods: {
    checkIfCreateNewFolder (folder) {
      if (folder === 0) {
        this.isFolderUpdate = false
        this.$bvModal.show('modal-add-saved-questions-folder')
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
    initUpdateSavedQuestionsFolder (folderId) {
      this.isFolderUpdate = true
      this.folderToUpdate = this.savedQuestionsFoldersOptions.find(folder => folder.value === folderId)
      this.savedQuestionsFolderForm.folder_id = folderId
      this.$bvModal.show('modal-add-saved-questions-folder')
    },
    async handleDeleteSavedQuestionsFolder () {
      console.log(this.savedFolderToDelete)
      try {
        const { data } = await axios.post(`/api/saved-questions-folders/delete/${this.savedFolderToDelete.id}`, {
          action: this.deleteSavedFolderAction,
          move_to_folder_id: this.savedQuestionsFolderToMoveQuestionsTo,
          question_source: this.type
        })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        if (this.deleteSavedFolderAction === 'delete_without_moving') {
          this.savedQuestionsFoldersOptions = this.savedQuestionsFoldersOptions.filter(folder => folder.value !== this.savedFolderToDelete.id)
        }
        this.$emit('resetFolderAction')
        this.$emit('reloadSavedQuestionsFolders', 0)
        this.$bvModal.hide('modal-init-delete-saved-questions-folder')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initDeleteSavedQuestionsFolder (folder) {
      this.savedFolderToDelete = folder
      console.log(folder)
      this.deleteFolderOptions = this.savedQuestionsFoldersOptions.filter(folder => ![0, null, this.savedFolderToDelete.id].includes(folder.value))
      this.savedQuestionsFolderToMoveQuestionsTo = this.deleteFolderOptions[0].value
      this.deleteSavedFolderAction = 'move'
      this.$bvModal.show('modal-init-delete-saved-questions-folder')
    },
    async handleCreateSavedQuestionsFolder () {
      try {
        this.savedQuestionsFolderForm.type = this.type
        let method = this.isFolderUpdate ? 'patch' : 'post'
        const { data } = await this.savedQuestionsFolderForm[method]('/api/saved-questions-folders')

        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.savedQuestionsFolderForm = new Form({
            name: ''
          })
          this.$bvModal.hide('modal-add-saved-questions-folder')
          this.savedQuestionsFoldersOptions = [{
            text: 'Choose a Favorites folder',
            value: null
          }]
          await this.getSavedQuestionsFolders()
          this.moveToFolderOptions = this.savedQuestionsFoldersOptions.filter(folder => ![null, this.originalFolderId].includes(folder.value))
          this.savedQuestionsFolderToMoveQuestionTo = this.moveToFolderOptions[this.moveToFolderOptions.length - 2].value // right before New Value
          this.savedQuestionsFolder = data.folder_id
          this.$emit('resetFolderAction')
          if (!this.isFolderUpdate) {
            this.$emit('savedQuestionsFolderSet', this.savedQuestionsFolder)
          }
          if (this.questionSourceIsMyFavorites || this.type === 'my_questions') {
            this.$emit('reloadSavedQuestionsFolders', this.type)
          }
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
        this.$bvModal.show('modal-add-saved-questions-folder')
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
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }

}
</script>

<style scoped>

</style>
