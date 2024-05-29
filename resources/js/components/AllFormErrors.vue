<template>
  <div>
    <b-modal
      :id="modalId"
      ref="formErrorsModal"
      title="Form Errors"
      hide-footer
    >
      <p v-show="showPleaseFixMessage">
        Please fix the following errors in your form:
      </p>
      <p v-show="allFormErrors.length === 1">
        {{ allFormErrors[0] }}
      </p>
      <ul v-show="allFormErrors.length > 1">
        <li v-for="formError in allFormErrors" :key="formError">
          <span v-html="formError"/>
        </li>
      </ul>
    </b-modal>
  </div>
</template>

<script>
export default {
  name: 'AllFormErrors',
  props: {
    allFormErrors: {
      type: Array,
      default: function () {
        return []
      }
    },
    showPleaseFixMessage: {
      type: Boolean,
      default: true
    },
    modalId: {
      type: String,
      default: 'modal-form-errors'
    }
  }
}
</script>

<style scoped>

</style>
