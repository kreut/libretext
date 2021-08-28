<template>
  <div>
    <b-modal
      id="modal-form-errors"
      ref="formErrorsModal"
      title="Form Errors"
      hide-footer
    >
      <p class="font-weight-bold font-italic">
        Please fix the following errors in your form:
      </p>
      <ul>
        <li v-for="formError in allFormErrors" :key="formError">
          <span class="text-danger">{{ formError }}</span>
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
      default: function () { return [] }
    }
  }
}
</script>

<style scoped>

</style>
