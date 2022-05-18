<template>
  <div>
    <b-modal
      :id="modalId"
      ref="formErrorsModal"
      title="Form Errors"
      hide-footer
    >
      <p>
        Please fix the following errors in your form:
      </p>
      <ul>
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
    modalId: {
      type: String,
      default: 'modal-form-errors'
    }
  }
}
</script>

<style scoped>

</style>
