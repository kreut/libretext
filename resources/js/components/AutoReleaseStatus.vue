<template>
  <span>
    <span v-show="assignmentId">
      <span v-b-tooltip.hover
            class="text-success"
            :title="`The ${item.toLowerCase()} ${isAre} currently shown.  You can manually hide ${itThem} by visiting the Control Panel.`"
      ><b-icon-eye v-show="released" />
      </span>
      <span v-b-tooltip.hover
            class="text-warning"
            :title="`The ${item.toLowerCase()} ${isAre} not currently shown.  You can manually show ${itThem}  by visiting the Control Panel.`"
      ><b-icon-eye-slash v-show="!released" />
      </span>
    </span>
    {{ item }}
  </span>
</template>

<script>
export default {
  name: 'AutoReleaseStatus',
  props: {
    item: {
      type: String,
      default: ''
    },
    released: {
      type: Boolean,
      default: true
    },
    assignmentId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    isAre: 'are',
    itThem: 'them'
  }),
  mounted () {
    if (this.item === 'Assignment') {
      this.isAre = 'is'
      this.itThem = 'it'
    }
  }
}
</script>

<style scoped>

</style>
