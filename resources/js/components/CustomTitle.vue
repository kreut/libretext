<template>
  <span>
    <b-modal :id="`modal-question-title-${questionId}`"
             title="Update Question Title"
             size="lg"
    >
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="custom_question_title"
        label="Custom Question Title"
      >
        <b-form-row>
          <b-form-input
            id="custom_question_title"
            v-model="customQuestionTitle"
            placeholder="Enter a custom title or a blank to reset the title"
            required
            type="text"
          />
        </b-form-row>
      </b-form-group>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`modal-question-title-${questionId}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleUpdateCustomQuestionTitle('update')"
        >
          Save
        </b-button>
      </template>
    </b-modal>
    <a id="question-title-tooltip" href=""
       @click.prevent="initUpdateCustomTitleModal"
    >
      <b-icon-pencil v-if="title"
                     :class="pencilClass"
                     style="height:20px"
                     aria-label="Update custom title"
      />
    </a>
    <b-tooltip target="question-title-tooltip"
               delay="750"
               triggers="hover"
    >
      Update the question title as it appears in your assignment
    </b-tooltip>
  </span>
</template>

<script>
import axios from 'axios'


export default {
  name: 'CustomTitle',
  props:
    {
      assignmentId: {
        type: Number,
        default: 0
      },
      questionId: {
        type: Number,
        default: 0
      },
      title: {
        type: String,
        default: ''
      },
      pencilClass: {
        type: String,
        default: ''
      }
    },
  data: () => ({
    customQuestionTitle: ''
  }),
  methods: {
    initUpdateCustomTitleModal () {
      this.customQuestionTitle = this.title
      this.$bvModal.show(`modal-question-title-${this.questionId}`)
    },
    async handleUpdateCustomQuestionTitle () {
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/${this.questionId}/custom-title`
          , { custom_question_title: this.customQuestionTitle })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          const newTitle = this.customQuestionTitle ? this.customQuestionTitle : data.original_question_title
          this.$emit('updateCustomQuestionTitle', newTitle)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(`modal-question-title-${this.questionId}`)
    }
  }
}
</script>

<style scoped>

</style>
