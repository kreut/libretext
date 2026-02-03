<template>
  <b-modal :id="`qti-answer-${modalId}`"
           size="lg"
           hide-header
  >
    <div v-if="['forge_iteration','forge'].includes(JSON.parse(qtiJson).questionType)"
         v-html="JSON.parse(qtiJson).solution_html"
    />
    <div v-else>
      <h2 class="editable">
        Answer
      </h2>
      <QtiJsonQuestionViewer :qti-json="qtiJson"
                             :show-qti-answer="true"
                             :show-submit="false"
                             :show-response-feedback="false"
                             :preview-or-solution="true"
      />
    </div>
    <template #modal-footer="{ ok }">
      <b-button size="sm"
                class="float-right"
                variant="primary"
                @click="$bvModal.hide(`qti-answer-${modalId}`)"
      >
        OK
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import QtiJsonQuestionViewer from '~/components/QtiJsonQuestionViewer'

export default {
  name: 'QtiJsonAnswerViewer',
  components: {
    QtiJsonQuestionViewer
  },
  props: {
    qtiJson: {
      type: String,
      default: ''
    },
    modalId: {
      type: Number,
      default: 0
    }
  }
}
</script>

<style scoped>

</style>
