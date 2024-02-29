<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <AutoRelease :auto-release-form="autoReleaseForm" :course-id="courseId"/>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import AutoRelease from '~/components/AutoRelease.vue'
import Form from 'vform'
import axios from 'axios'

export default {
  metaInfo () {
    return { title: 'Auto-Release' }
  },
  components: {
    AutoRelease,
    Loading
  },
  data: () => ({
    courseId: 0,
    isLoading: true,
    a11yRedirects: [],
    autoReleaseForm: new Form({
      auto_release_shown: null,
      auto_release_show_scores: null,
      auto_release_show_scores_after: null,
      auto_release_solutions_released: null,
      auto_release_solutions_released_after: null,
      auto_release_students_can_view_assignment_statistics: null,
      auto_release_students_can_view_assignment_statistics_after: null
    })
  }),
  async mounted () {
    this.courseId = parseInt(this.$route.params.courseId)
    await this.getAutoReleases()
    this.isLoading = false
  },
  methods: {
    async getAutoReleases () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        const course = data.course
        this.autoReleaseForm = new Form({
          auto_release_shown: course.auto_release_shown,
          auto_release_show_scores: course.auto_release_show_scores,
          auto_release_show_scores_after: course.auto_release_show_scores_after,
          auto_release_solutions_released: course.auto_release_solutions_released,
          auto_release_solutions_released_after: course.auto_release_solutions_released_after,
          auto_release_students_can_view_assignment_statistics: course.auto_release_students_can_view_assignment_statistics,
          auto_release_students_can_view_assignment_statistics_after: course.auto_release_students_can_view_assignment_statistics_after
        })
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
