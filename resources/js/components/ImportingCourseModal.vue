<template>
  <b-modal :id="`modal-importing-course-${importedCourse.id}`"
           hide-footer
           no-close-on-backdrop
           no-close-on-esc
           size="lg"
           :title="`${importActioning} ${importedCourse.name}`"
  >
    <b-alert variant="info" :show="importingCourse">
      <b-spinner small type="grow"/>
      Processing...please be patient. {{ importActioning }} the course may take up to 30 seconds to complete.
    </b-alert>
    <b-alert :variant="importingCourseMessage.type === 'success' ? 'success': 'danger'" :show="!importingCourse">
      <div v-html="importingCourseMessage.message"/>
    </b-alert>
  </b-modal>
</template>

<script>
export default {
  name: 'ImportingCourseModal',
  props: {
    importedCourse: {
      type: Object,
      default: () => {
      }
    },
    importingCourseMessage: {
      type: Object,
      default: () => {
      }
    },
    importingCourse: {
      type: Boolean,
      default: false
    },
    importActioning: {
      type: String,
      default: ''
    }
  }
}
</script>

<style scoped>

</style>
