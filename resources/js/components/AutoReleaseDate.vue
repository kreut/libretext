<template>
  <div>
    <div v-html="getAutoReleaseDateText()" />
  </div>
</template>

<script>
export default {
  name: 'AutoReleaseDate',
  props: {
    assignment: {
      type: Object,
      default: () => {
      }
    },
    property: {
      type: String,
      default: ''
    },
    autoReleaseActivated: {
      type: Number,
      default: 0
    }
  },
  methods: {
    getAutoReleaseDateText () {
      let assignmentProperty
      switch (this.property) {
        case ('show_date'):
          assignmentProperty = 'shown'
          break
        case ('show_scores_date'):
          assignmentProperty = 'show_scores'
          break
        case ('solutions_released_date'):
          assignmentProperty = 'solutions_released'
          break
        case ('students_can_view_assignment_statistics_date'):
          assignmentProperty = 'students_can_view_assignment_statistics'
          break
      }
      if (this.assignment[assignmentProperty]) {
        return 'Now'
      } else if (this.assignment.auto_release_show_dates &&
        this.assignment.auto_release_show_dates[this.property] &&
        Boolean(this.autoReleaseActivated)) {
        return this.$moment(this.assignment.auto_release_show_dates[this.property], 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') +
          '<br>' +
          this.$moment(this.assignment.auto_release_show_dates[this.property], 'YYYY-MM-DD HH:mm:ss A').format('h:mmA')
      } else {
        return 'N/A'
      }
    }
  }
}
</script>

<style scoped>

</style>
