<template>
  <span>
    <span v-if="completed">
      <b-icon-check-circle-fill class="text-success" aria-label="Completed"/>
    </span>
    <span v-if="!completed">
      <b-icon-x-circle-fill class="text-danger" aria-label="Not Completed"/>
    </span>
  </span>
</template>

<script>
export default {
  name: 'CompletedIcon',
  props: {
    completed: {
      type: Boolean,
      default: false
    }
  }
}
</script>

<style scoped>

</style>
