<template>
  <a href="#"
     @click.prevent="$bvModal.show(modalId)"
  >
    <b-icon class="text-muted"
            icon="question-circle"
            :aria-label="ariaLabel"
    />
  </a>
</template>

<script>

export default {
  name: 'QuestionCircleTooltipModal',
  props: {
    ariaLabel: {
      type: String,
      default: ''
    },
    modalId: {
      type: String,
      default: ''
    }
  }
}
</script>

<style scoped>

</style>
