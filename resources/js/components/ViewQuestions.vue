<template>
  <div>
    <b-modal
      :id="modalId"
      :title="`${type} Question${questionIdsToView.length >1 ? 's' :''}`"
      size="lg"
      :hide-footer="true"
    >
      <div class="overflow-auto">
        <b-pagination
          v-if="questionIdsToView.length >1"
          :key="`current-question-${currentQuestion}`"
          v-model="currentQuestion"
          :total-rows="questionIdsToView.length"
          :per-page="1"
          align="center"
          first-number
          last-number
          limit="17"
          @input="viewQuestion(questionIdsToView[currentQuestion-1])"
        />
      </div>
      <div>
        <iframe v-show="question.non_technology"
                :key="`non-technology-iframe-${question.id}`"
                v-resize="{ log: true, checkOrigin: false }"
                style="height: 30px"
                width="100%"
                :src="question.non_technology_iframe_src"
                frameborder="0"
        />
      </div>

      <div v-if="question.technology_iframe && showQuestion">
        <iframe
          :key="`technology-iframe-${question.id}`"
          v-resize="{ log: true, checkOrigin: false }"
          width="100%"
          :src="question.technology_iframe"
          frameborder="0"
        />
      </div>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import { h5pResizer } from '~/helpers/H5PResizer'
import _ from 'lodash'

export default {
  name: 'ViewQuestions',
  props: {
    modalId: {
      type: String,
      default: 'modal-view-questions'
    },
    questionIdsToView: {
      type: Array,
      default: function () {
        return []
      }
    },
    questionToView: {
      type: Object,
      default: function () {
        return { }
      }
    }
  },
  data: () => ({
    type: 'View',
    currentQuestion: 1,
    showQuestion: false,
    loadingQuestion: false,
    question: {},
    currentPage: 1
  }),
  created () {
    h5pResizer()
  },
  mounted () {
    this.type = this.questionIdsToView.length ? 'View' : 'Preview'
    if (this.questionIdsToView.length) {
      this.viewQuestion(this.questionIdsToView[0])
    }
    if (!_.isEmpty(this.questionToView)) {
      this.showQuestion = false
      this.$bvModal.show(this.modalId)
      this.loadingQuestion = true
      this.question = this.questionToView
      this.showQuestion = true
      this.loadingQuestion = false
      console.error(this.question)
    }
  },
  methods: {
    async viewQuestion (questionId) {
      this.showQuestion = false
      try {
        this.$bvModal.show(this.modalId)
        this.loadingQuestion = true
        const { data } = await axios.get(`/api/questions/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.loadingQuestion = false
          return false
        }
        this.question = data.question
        this.question.question_id = data.question.id
        this.showQuestion = true
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.loadingQuestion = false
    }
  }
}
</script>

<style scoped>

</style>
