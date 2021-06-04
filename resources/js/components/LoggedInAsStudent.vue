<template>
  <b-alert variant="warning" :show="true">
    <span class="font-italic font-weight-bold">You are currently logged in as {{ studentName }}.  Any submissions you make on their behalf will
      be accepted and scored.
    </span>
  </b-alert>
</template>

<script>
export default {
  name: 'LoggedInAsStudent',
  props: {
    studentName: {
      type: String,
      default: ''
    }
  }
}
</script>

<style scoped>

</style>
