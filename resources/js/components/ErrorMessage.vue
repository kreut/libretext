<template>
  <div>
    <input type="hidden" class="form-control is-invalid">
    <div class="help-block invalid-feedback" style="font-weight:400">
      {{ message }}
    </div>
  </div>
</template>

<script>
export default {
  name: 'ErrorMessage',
  props: {
    message: {
      type: String,
      default: ''
    }
  }
}
</script>

<style scoped>

</style>
