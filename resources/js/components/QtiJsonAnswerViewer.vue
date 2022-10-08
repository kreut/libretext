<template>
  <b-modal :id="`qti-answer-${modalId}`"
           size="lg"
           hide-header
  >
    <h2 class="editable">
      Answer
    </h2>
    <QtiJsonQuestionViewer :qti-json="qtiJson"
                           :show-qti-answer="true"
                           :show-submit="false"
                           :show-response-feedback="false"
    />
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
