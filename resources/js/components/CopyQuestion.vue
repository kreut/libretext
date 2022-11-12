<template>
  <span>
    <b-modal :id="`modal-copy-question-${questionId}`"
             :title="`Copy ${title}`"
             size="lg"
    >
      <div v-if="isMe">
        <span>Acting as</span>
        <toggle-button
          style="margin-bottom:5px"
          tabindex="0"
          :width="100"
          :value="actingAs === user.first_name"
          :sync="true"
          :font-size="14"
          :color="toggleColors"
          :labels="{checked: user.first_name, unchecked: 'Admin'}"
          @change="updateActingAs()"
        />
      </div>
      <div v-if="actingAs === user.first_name">
        <div v-if="assignmentId">
          <b-form-group>
            <b-form-radio-group v-model="addToAssignment" class="pt-2">
              <label class="pr-2">Add to current assignment</label>
              <b-form-radio :value="true">
                Yes
              </b-form-radio>
              <b-form-radio :value="false">
                No
              </b-form-radio>
            </b-form-radio-group>
          </b-form-group>
        </div>
        <div class="inline-flex d-flex">
          Copy question to
          <SavedQuestionsFolders
            type="my_questions"
            class="pl-2"
            :question-source-is-my-favorites="false"
            :modal-id="`modal-for-bulk-import-${questionId}`"
            :default-is-copied-questions-folder="true"
            :folder-to-choose-from="'My Questions'"
            :create-modal-add-saved-questions-folder="true"
            @savedQuestionsFolderSet="setcopyToFolderId"
          />
        </div>
      </div>
      <div v-if="actingAs === 'Admin'">
        <p>The copied question will be moved to the new owner's account.</p>
        <v-select id="owner"
                  v-model="questionEditor"
                  placeholder="Please choose the new owner"
                  :options="questionEditorOptions"
                  style="width:300px"
        />
      </div>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide(`modal-copy-question-${questionId}`)">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary"
                  @click="copyQuestion()"
        >
          Copy
        </b-button>
      </template>

    </b-modal>
    <a :id="`copy-${questionId}`"
       href=""
       style="text-decoration: none"
       @click.prevent="openModalCopyQuestion()"
    ><span class="align-middle">
        <font-awesome-icon
          v-if="bigIcon"
          :id="`copy-${questionId}`"
          class="text-muted"
          :icon="copyIcon"
          style="font-size:24px;"
        />
      </span>
      <font-awesome-icon
        v-if="!bigIcon"
        class="text-muted"
        :icon="copyIcon"
      /></a>
    <b-tooltip :target="`copy-${questionId}`"
               triggers="hover"
               delay="750"
    > <span v-if="canCopy">
        <span v-if="isMe">Make a copy of question {{
          questionId
        }} to your account or that of another instructor's.</span>
        <span v-if="!isMe">Copy {{ title }}.</span>
      </span>
      <span v-if="!canCopy">
        You cannot copy this question since it is not a native ADAPT question.
      </span>
    </b-tooltip>
  </span>
</template>

<script>
import axios from 'axios'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import SavedQuestionsFolders from './SavedQuestionsFolders'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  name: 'CopyQuestion',
  components: {
    FontAwesomeIcon,
    SavedQuestionsFolders,
    ToggleButton
  },
  props: {
    assignmentId: {
      type: Number,
      default: 0
    },
    bigIcon: {
      type: Boolean,
      default: false
    },
    questionId: {
      type: Number,
      default: 0
    },
    title: {
      type: String,
      default: ''
    },
    library: {
      type: String,
      default: ''
    },
    nonTechnology: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    copyToFolderId: null,
    actingAs: '',
    toggleColors: window.config.toggleColors,
    copiedQuestionsFolder: true,
    addToAssignment: true,
    questionEditorOptions: [],
    questionEditor: null,
    copyIcon: faCopy,
    canCopy: false
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.actingAs = this.user.first_name
    this.canCopy = this.library === 'adapt'
  },
  methods: {
    setcopyToFolderId (folderId) {
      this.copyToFolderId = folderId
      console.log(folderId)
    },
    updateActingAs () {
      this.actingAs = this.actingAs === this.user.first_name ? 'Admin' : this.user.first_name
    },
    openModalCopyQuestion () {
      this.getAllQuestionEditors()
      this.$bvModal.show(`modal-copy-question-${this.questionId}`)
    },
    async copyQuestion () {
      try {
        const { data } = await axios.post('/api/questions/copy', {
          acting_as: this.actingAs === 'Admin' ? 'admin' : 'instructor',
          copy_to_folder_id: this.copyToFolderId,
          assignment_id: this.assignmentId,
          question_id: this.questionId,
          question_editor_user_id: this.actingAs === 'Admin' ? this.questionEditor.value : this.user.id
        })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$emit('reloadQuestions')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(`modal-copy-question-${this.questionId}`)
    },
    async getAllQuestionEditors () {
      try {
        const { data } = await axios.get('/api/user/question-editors')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questionEditorOptions = data.question_editors
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
