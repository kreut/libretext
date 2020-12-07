<template>
  <div>
    <b-card header="default" header-html="Course Access Codes">
      <b-card-text>
        <p>By refreshing your access code, students will no longer be able to sign up using the old access code.</p>
        <p>Current Access code: {{ course.access_code }}</p>
        <b-button class="float-right" variant="primary" @click="refreshAccessCode">
          Refresh Access Code
        </b-button>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  data: () => ({
    course: {}
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getCourse(this.courseId)
  },
  methods: {
    async getCourse (courseId) {
      const { data } = await axios.get(`/api/courses/${courseId}`)
      this.course = data.course
    },
    async refreshAccessCode () {
      try {
        const { data } = await axios.patch('/api/course-access-codes', { course_id: this.courseId })
        if (data.type === 'error') {
          this.$noty.error('We were not able to update your access code.')
          return false
        }
        this.$noty.success(data.message)
        this.course.access_code = data.access_code
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
<style>
body, html {
  overflow: visible;

}

svg:focus, svg:active:focus {
  outline: none !important;
}
</style>
