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
    <div v-if="question.technology_iframe && showQuestion">
      <iframe
        :key="`technology-iframe-${question.id}`"
        v-resize="{ log: false, checkOrigin: false }"
        width="100%"
        :src="question.technology_iframe_src"
        frameborder="0"
      />
    </div>
  </div>
</template>

<script>
import { h5pResizer } from '~/helpers/H5PResizer'
import _ from 'lodash'

export default {
  name: 'ViewQuestionWithoutModal',
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
