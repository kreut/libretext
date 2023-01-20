<template>
  <div>
    <b-form-group
      label-cols-sm="3"
      label-cols-lg="2"
      label-for="numerical_correct_response"
      label="Correct Response"
    >
      <b-form-row>
        <b-form-input
          id="numerical_correct_response"
          v-model="qtiJson.correctResponse.value"
          type="text"
          :class="{ 'is-invalid': questionForm.errors.has('correct_response')}"
          style="width:100px"
          @keydown="questionForm.errors.clear('correct_response')"
        />
        <has-error :form="questionForm" field="correct_response" />
      </b-form-row>
    </b-form-group>

    <b-form-group
      label-cols-sm="3"
      label-cols-lg="2"
      label-for="numerical_correct_response_margin_of_error"
      label="Margin of Error"
    >
      <b-form-row>
        <b-form-input
          id="numerical_correct_response_margin_of_error"
          v-model="qtiJson.correctResponse.marginOfError"
          style="width:100px"
          type="text"
          :class="{ 'is-invalid': questionForm.errors.has('margin_of_error')}"
          @keydown="questionForm.errors.clear('margin_of_error')"
        />
        <has-error :form="questionForm" field="margin_of_error" />
      </b-form-row>
    </b-form-group>
    <div
      v-if="qtiJson.correctResponse.marginOfError !== ''
        && qtiJson.correctResponse.value !== ''
        && !isNaN(qtiJson.correctResponse.marginOfError)
        && qtiJson.correctResponse.marginOfError >0
        && !isNaN(qtiJson.correctResponse.value)"
      class="mb-3"
    >
      Responses between {{
        1*(parseFloat(qtiJson.correctResponse.value) - parseFloat(qtiJson.correctResponse.marginOfError)).toFixed(4)
      }}
      and {{
        1*(parseFloat(qtiJson.correctResponse.value) + parseFloat(qtiJson.correctResponse.marginOfError)).toFixed(4)
      }} will be marked as correct.
    </div>
  </div>
</template>

<script>
export default {
  name: 'Numerical',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    questionForm: {
      type: Object,
      default: () => {
      }
    }
  }
}
</script>

<style scoped>

</style>
