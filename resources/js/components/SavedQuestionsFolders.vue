<template>
  <span>
    <b-modal id="modal-move-question-to-new-folder"
             :title="`Move question to new ${typeText} folder`"
    >
      Move the {{ questionToMove.title }} to:   <b-form-select
        id="saved_questions_folders"
        v-model="savedQuestionsFolderToMoveQuestionTo"
        style="width: 300px"
        :options="moveToFolderOptions"
        size="sm"
      />
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-move-question-to-new-folder')"
        >
          Cancel
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
      You are about to delete the folder  {{ savedFolderToDelete.name }}. Would you like to:
      <b-form-group>
        <b-form-radio-group
          v-model="deleteSavedFolderAction"
          aria-describedby="Choose what to do with saved questions in this folder"
          name="choose-action-for-saved-questions-in-deleted-folder"
          stacked
        >
          <b-form-radio value="move">Move the saved questions to:   <b-form-select
            id="saved_questions_folders"
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
    >
      <RequiredText :plural="false" />
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
            <has-error :form="savedQuestionsFolderForm" field="name" />
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
      style="width: 250px"
      :options="savedQuestionsFoldersOptions"
      @change="changeSavedQuestionsFolder($event)"
    />
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-add-saved-questions-folder'" />
  </span>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import AllFormErrors from './AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import _ from 'lodash'

export default {
  name: 'SavedQuestionsFolders',
  components: { AllFormErrors },
  props: {
    type: {
      type: String,
      default: 'my_favorites'
    }
  },
  data: () => ({
    typeText: '',
    savedQuestionsFolderToMoveQuestionTo: 0,
    moveToFolderOptions: [],
    originalFolderId: 0,
    questionToMove: 0,
    folderToUpdate: '',
    isFolderUpdate: false,
    savedQuestionsFolderToMoveQuestionsTo: null,
    deleteSavedFolderAction: 'move',
    deleteFolderOptions: [],
    savedFolderToDelete: {},
    allFormErrors: [],
    savedQuestionsFolder: 0,
    savedQuestionsFoldersOptions: [{
      text: 'Choose a Favorites folder',
      value: null
    }],
    savedQuestionsFolderForm: new Form({
      name: ''
    })
  }),
  mounted () {
    this.typeText = _.startCase(this.type.replace('_', ' '))

    this.getSavedQuestionsFolders()
  },
  methods: {
    async handleMoveQuestionToANewFolder () {
      try {
        const { data } = await axios.patch(`/api/saved-questions-folders/move/${this.questionToMove.question_id}/from/${this.originalFolderId}/to/${this.savedQuestionsFolderToMoveQuestionTo}`)
        if (data.type !== 'error') {
          this.$emit('getCurrentAssignmentQuestionsBasedOnChosenAssignmentOrSavedQuestionsFolder', this.originalFolderId)
          this.$bvModal.hide('modal-move-question-to-new-folder')
        }
        this.$noty.info(data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initMoveSavedQuestion (questionToMove) {
      this.originalFolderId = questionToMove.folder_id
      this.questionToMove = questionToMove
      this.moveToFolderOptions = this.savedQuestionsFoldersOptions.filter(folder => ![0, null, this.originalFolderId].includes(folder.value))
      this.savedQuestionsFolderToMoveQuestionTo = this.moveToFolderOptions[0].value
      this.$bvModal.show('modal-move-question-to-new-folder')
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
          move_to_folder_id: this.savedQuestionsFolderToMoveQuestionsTo
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
          this.savedQuestionsFolder = data.folder_id
          this.$emit('reloadSavedQuestionsFolders', 0)
          this.$emit('resetFolderAction')
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
        this.savedQuestionsFolder = this.savedQuestionsFoldersOptions[0].value
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }

}
</script>

<style scoped>

</style>
