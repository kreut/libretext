<template>
  <span>
    <b-modal :id="`modal-copy-question-${questionId}`"
             title="Copy Question"
    >
     <p>The copied question will be moved to the new owner's account.</p>
       <v-select id="owner"
                 v-model="questionEditor"
                 placeholder="Please choose the new owner"
                 :options="questionEditorOptions"
                 style="width:300px"
       />
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide(`modal-copy-question-${questionId}`)">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary"
                  @click="copyQuestion()"
        >
          Copy question
        </b-button>
      </template>

    </b-modal>
  <a v-if="isMe"
     :id="`copy-${questionId}`"
     href=""
     @click.prevent="openModalCopyQuestion()"
  >
    <span v-if="bigIcon" class="align-middle">
      <font-awesome-icon
        :id="`copy-${questionId}`"
        :class="canCopy ? 'text-muted' : 'text-danger'"
        :icon="copyIcon"
        style="font-size:24px;"
      />
      </span>
    <font-awesome-icon
      v-if="!bigIcon"
      :class="canCopy ? 'text-muted' : 'text-danger'"
      :icon="copyIcon"
    />
  </a>
    <b-tooltip :target="`copy-${questionId}`"
               delay="750"
    >
      <span v-if="canCopy">
      Make a copy of question {{ questionId }} to your account or that of another instructor's.
        </span>
      <span v-if="!canCopy">
       You cannot copy this question since it has Header HTML and is not an ADAPT question.
      </span>
    </b-tooltip>
  </span>
</template>

<script>
import axios from 'axios'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  name: 'CopyQuestion',
  components: {
    FontAwesomeIcon
  },
  props: {
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
    questionEditorOptions: [],
    questionEditor: null,
    copyIcon: faCopy,
    canCopy: true
  }),
  computed: {
    isMe: () => window.config.isMe
  },
  mounted () {
    this.canCopy = this.library === 'adapt' || (this.library !== 'adapt' && !this.nonTechnology)
  },
  methods: {
    openModalCopyQuestion () {
      if (!this.canCopy) {
        return false
      }
      this.getAllQuestionEditors()
      this.$bvModal.show(`modal-copy-question-${this.questionId}`)
    },
    async copyQuestion () {
      try {
        const { data } = await axios.post('/api/questions/copy', {
          question_id: this.questionId,
          question_editor_user_id: this.questionEditor.value
        })
        this.$noty[data.type](data.message)
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
