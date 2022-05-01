<template>
  <p id="required_text">
    The field<span v-if="plural">s</span> marked with an asterisk
    <span v-if="plural">are</span><span v-if="!plural">is</span>
    required.
  </p>
</template>

<script>
export default {
  name: 'RequiredText',
  props: {
    plural: { type: Boolean, default: true }
  }
}
</script>

<style scoped>

</style>
