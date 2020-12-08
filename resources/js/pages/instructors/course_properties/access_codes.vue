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
      <div v-if="!isLoading && user.role === 2">
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
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
    course: {},
    isLoading: true
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    this.courseId = this.$route.params.courseId
    await this.getCourse(this.courseId)

    this.isLoading = false
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
