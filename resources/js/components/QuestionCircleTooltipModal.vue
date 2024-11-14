<template>
  <span @mouseover="showModal()" @mouseout="mouseOver = false">
    <a href="#" @click.prevent>
      <b-icon :class="colorClass"
              icon="question-circle"
              :aria-label="ariaLabel"
      />
    </a>
  </span>
</template>

<script>

export default {
  name: 'QuestionCircleTooltipModal',
  props: {
    colorClass: {
      type: String,
      default: 'text-muted'
    },
    ariaLabel: {
      type: String,
      default: ''
    },
    modalId: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    mouseOver: true
  }),
  methods: {
    showModal () {
      this.mouseOver = true
      setTimeout(() => {
        if (this.mouseOver) {
          this.$bvModal.show(this.modalId)
        }
      }, 500)
    }
  }
}
</script>

<style scoped>

</style>
