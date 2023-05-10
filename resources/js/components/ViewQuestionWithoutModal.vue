<template>
  <div>
    <iframe v-show="question.non_technology"
            :key="`non-technology-iframe-${question.id}`"
            v-resize="{ log: false, checkOrigin: false }"
            style="height: 30px"
            width="100%"
            :src="question.non_technology_iframe_src"
            frameborder="0"
    />
    <div v-if="question.technology_iframe_src && showQuestion">
      <iframe
        :key="`technology-iframe-${question.id}`"
        v-resize="{ log: false, checkOrigin: false }"
        width="100%"
        :src="question.technology_iframe_src"
        frameborder="0"
      />
    </div>
    <div v-if="question.technology === 'qti'">
      <QtiJsonQuestionViewer
        :key="`qti-json-question-viewer-${question.id}`"
        :qti-json="question.qti_json"
        :show-qti-answer="true"
        :show-submit="false"
        :show-response-feedback="false"
      />
    </div>
  </div>
</template>

<script>
import { h5pResizer } from '~/helpers/H5PResizer'
import _ from 'lodash'
import QtiJsonQuestionViewer from './QtiJsonQuestionViewer.vue'

export default {
  name: 'ViewQuestionWithoutModal',
  components: { QtiJsonQuestionViewer },
  props: {
    questionToView: {
      type: Object,
      default: function () {
        return {}
      }
    }
  },
  data: () => ({
    showQuestion: false,
    loadingQuestion: false,
    question: {}
  }),
  created () {
    h5pResizer()
  },
  mounted () {
    if (!_.isEmpty(this.questionToView)) {
      this.showQuestion = false
      this.loadingQuestion = true
      this.question = this.questionToView
      this.showQuestion = true
      this.loadingQuestion = false
    }
  }
}
</script>
