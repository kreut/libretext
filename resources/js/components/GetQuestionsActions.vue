<template>
  <span>
    <b-modal
      :id="confirmDeleteModalId"
      :key="confirmDeleteModalId"
      :title="`Confirm Delete Question${questionsToDelete.length === 1 ? '' : 's'}`"
      size="lg"
      @hidden="$emit('reloadCurrentAssignmentQuestions')"
    >
      <b-table
        :key="deletedKey"
        aria-label="Questions To Delete"
        striped
        hover
        responsive
        :no-border-collapse="true"
        :fields="questionsToDeleteFields"
        :items="questionsToDelete"
      >
        <template v-slot:cell(deleted_status)="data">
          <span v-html="data.item.deleted_status"/>
        </template>
      </b-table>
      <template #modal-footer>
        <div v-if="!deletingQuestions" class="float-right">
          <b-button
            size="sm"
            @click="$bvModal.hide(confirmDeleteModalId)"
          >
            Cancel
          </b-button>
          <b-button
            size="sm"
            variant="danger"
            @click="handleDeleteQuestions"
          >
            Delete Question<span v-if="questionsToDelete.length >1">s</span>
          </b-button>
        </div>
        <div class="float-right">
          <span v-if="deletingQuestions">Deleting {{ deletingIndex }} of  {{ questionsToDelete.length }}</span>
          <b-button
            v-if="deletingQuestions"
            size="sm"
            class="ml-2"
            :disabled="!deletedQuestions"
            @click="$bvModal.hide(confirmDeleteModalId)"
          >
            Close
          </b-button>
        </div>
      </template>
    </b-modal>
    <b-modal
      :id="`modal-edit-question-${questionToEdit.id}`"
      :title="`Edit Question &quot;${questionToEdit.title}&quot;`"
      size="xl"
      hide-footer
      no-close-on-backdrop
      @hidden="hideModalEditActions()"
    >
      <CreateQuestion :key="`question-to-edit-${questionToEdit.id}`"
                      :question-to-edit="questionToEdit"
                      :modal-id="'my-questions-question-to-view-questions-editor'"
                      :question-exists-in-own-assignment="questionExistsInOwnAssignment"
                      :question-exists-in-another-instructors-assignment="questionExistsInAnotherInstructorsAssignment"
      />
    </b-modal>
    <span v-if="withinAssignment && showPlusMinusForAddRemove">
      <span v-if="!assignmentQuestion.in_current_assignment">
        <b-button
          variant="primary"
          class="p-1"
          @click.prevent="$emit('addQuestions',[assignmentQuestion])"
        ><span :aria-label="`Add ${assignmentQuestion.title} to the assignment`">+</span>
        </b-button>
      </span>
      <span v-if="assignmentQuestion.in_current_assignment">
        <b-button
          :id="getTooltipTarget('remove-question-from-assignment',assignmentQuestion.question_id)"
          variant="danger"
          class="p-1"
          @click.prevent="$emit('initRemoveAssignmentQuestion',assignmentQuestion)"
        ><span :aria-label="`Remove ${assignmentQuestion.title} from the assignment`">-</span>
        </b-button>
      </span>
    </span>
    <span v-if="questionSource !== 'my_favorites' && user.role !==5 && showHeart">
      <span v-show="!assignmentQuestion.my_favorites_folder_id">
        <a
          :id="getTooltipTarget(`add-to-my-favorites${componentId}`,assignmentQuestion.question_id)"
          href=""
          @click.prevent="$emit('initSaveToMyFavorites',[assignmentQuestion.question_id])"
        >
          <font-awesome-icon
            class="text-muted"
            :icon="heartIcon"
            :size="size"
            :aria-label="`Add ${assignmentQuestion.title} to My Favorites`"
          />
        </a>
         <b-tooltip
           :target="getTooltipTarget(`add-to-my-favorites${componentId}`,assignmentQuestion.question_id)"
           delay="1000"
           triggers="hover focus"
           title="Add to My Favorites"
         >
         Add to My Favorites Folder
        </b-tooltip>
      </span>
      <span v-if="assignmentQuestion.my_favorites_folder_id">
        <a :id="getTooltipTarget('remove-from-my-favorites',assignmentQuestion.question_id)"
           href=""
           @click.prevent="$emit('removeMyFavoritesQuestion',assignmentQuestion.my_favorites_folder_id,assignmentQuestion.question_id)"
        >
          <font-awesome-icon
            class="text-danger"
            :icon="heartIcon"
            :size="size"
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
    <span v-if="questionSource === 'my_favorites' && showTrash">
      <a
        href=""
        @click.prevent="$emit('removeMyFavoritesQuestion',assignmentQuestion.my_favorites_folder_id,assignmentQuestion.question_id)"
      >
        <b-icon icon="trash"
                class="text-muted"
                size="size"
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
    <span v-if="isMe || questionSource === 'my_questions' || (questionSource === 'all_questions' && user.role === 5)">
      <b-tooltip :target="getTooltipTarget(`edit${componentId}`,assignmentQuestion.question_id)"
                 delay="500"
                 triggers="hover focus"
      >
        Edit the question
      </b-tooltip>
      <a :id="getTooltipTarget(`edit${componentId}`,assignmentQuestion.question_id)"
         href=""
         class="pr-1"
         @click.prevent="editQuestionSource(assignmentQuestion)"
      >
        <b-icon class="text-muted"
                icon="pencil"
                :scale="size==='lg' ? 1.25: 1"
                :aria-label="`Edit ${assignmentQuestion.title}`"
        />
      </a>
      <a v-if="showTrash"
         :id="getTooltipTarget(`delete${componentId}`,assignmentQuestion.question_id)"
         href=""
         class="pr-1"
         @click.prevent="initDeleteQuestions([assignmentQuestion.question_id])"
      >
        <b-icon class="text-muted"
                icon="trash"
                :size="size"
                :aria-label="`Delete ${assignmentQuestion.title}`"
        />
      </a>
       <b-tooltip :target="getTooltipTarget(`delete${componentId}`,assignmentQuestion.question_id)"
                  delay="500"
                  triggers="hover focus"
       >
        Delete the question
      </b-tooltip>

    </span>
  </span>
</template>

<script>
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import CreateQuestion from './questions/CreateQuestion'
import axios from 'axios'
import { editQuestionSource, getQuestionToEdit } from '~/helpers/Questions'
import { mapGetters } from 'vuex'
import { v4 as uuidv4 } from 'uuid'

export default {
  name: 'GetQuestionsActions',
  components: { FontAwesomeIcon, CreateQuestion },
  props: {
    showPlusMinusForAddRemove: {
      type: Boolean,
      default: true
    },
    showHeart: {
      type: Boolean,
      default: true
    },
    showTrash: {
      type: Boolean,
      default: true
    },
    size: {
      type: String,
      default: 'sm'
    },
    assignmentQuestions: {
      type: Array,
      default: () => {
      }
    },
    assignmentQuestion: {
      type: Object,
      default: () => {
      }
    },
    heartIcon: {
      type: Object,
      default: () => {
      }
    },
    questionSource: {
      type: String,
      default: ''
    },
    removeMyFavoritesQuestion: {
      type: Function,
      default: () => {
      }
    },
    withinAssignment: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    componentId: '',
    isBetaAssignment: false,
    confirmDeleteModalId: 'confirm-delete-modal',
    deletingIndex: 1,
    deletedKey: 0,
    questionsToDelete: [],
    questionToEdit: {},
    questionExistsInOwnAssignment: false,
    questionExistsInAnotherInstructorsAssignment: false,
    deletingQuestions: false,
    deletedQuestions: false,
    questionsToDeleteFields: [
      {
        key: 'title',
        isRowHeader: true
      },
      'technology',
      {
        key: 'deleted_status',
        label: 'Status'
      }
    ]
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  created () {
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.editQuestionSource = editQuestionSource
    this.getQuestionToEdit = getQuestionToEdit
  },
  mounted () {
    this.componentId = uuidv4()
  },
  methods: {
    hideModalEditActions () {
      this.$emit('reloadCurrentAssignmentQuestions')
      this.$emit('reloadAllQuestions')
      if (this.user.role === 5) {
        axios.delete(`/api/current-question-editor/${this.questionToEdit.id}`)
        clearInterval(window.currentQuestionEditorUpdatedAt)
      }
    },
    async handleDeleteQuestions () {
      this.deletedKey = 0
      this.deletedQuestions = false
      this.deletingQuestions = true
      this.deletingIndex = 1
      for (let i = 0; i < this.questionsToDelete.length; i++) {
        let questionToDelete = this.questionsToDelete[i]
        this.deletingIndex = i + 1
        try {
          const { data } = await axios.delete(`/api/questions/${questionToDelete.id}`)
          this.questionsToDelete[i].deleted_status = data.type !== 'error'
            ? '<span class="text-success">Success</span>'
            : `<span class="text-danger">Error: ${data.message}</span>`
          this.deletedKey = i + 1
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      this.selectedQuestionIds = []
      this.deletedQuestions = true
    },
    initDeleteQuestions (questionIds) {
      this.deletingQuestions = false
      this.deletedQuestions = false
      this.confirmDeleteModalId = `modal-confirm-delete-questions-${questionIds.join()}`
      this.$nextTick(() => {
        this.$bvModal.show(this.confirmDeleteModalId)
      })
      this.questionsToDelete = this.assignmentQuestions.filter(question => questionIds.includes(question.id))
      for (let i = 0; i < this.questionsToDelete.length; i++) {
        this.questionsToDelete[i].deleted_status = 'Pending'
      }
    },
    async getQuestionAssignmentStatus () {
      try {
        const { data } = await axios.get(`/api/questions/${this.questionToEdit.id}/assignment-status`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.questionExistsInOwnAssignment = data.question_exists_in_own_assignment
          this.questionExistsInAnotherInstructorsAssignment = data.question_exists_in_another_instructors_assignment
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getNonTechnologyText () {
      try {
        const { data } = await axios.get(`/api/get-header-html/${this.questionToEdit.id}`)
        this.questionToEdit.non_technology_text = data
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
