<template>
  <div>
    <div class="vld-parent">
      <!--Use loading instead of isLoading because there's both the assignment and scores loading-->
      <loading :active.sync="loading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
    </div>
  </div>
</template>

<script>
import Form from 'vform/src'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'

export default {
  components: {
    Loading
  },
  data: () => ({
    courseId: 0,
    form: new Form({
      email: '',
      password: ''
    }),
    loading: true
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    this.courseId = this.$route.params.courseId
    await this.canLogIntoCourseAsAnonymousUser()
      ? await this.logInAnonymousUserAndRedirect()
      : this.$noty.error('You are not allowed to log into this course as an anonymous user.')
  },
  methods: {
    async canLogIntoCourseAsAnonymousUser () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}/can-log-into-course-as-anonymous-user`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return data.can_log_into_course_as_anonymous_user
      } catch (error) {
        this.$noty.error(error.message)
      }
      return false
    },
    async logInAnonymousUserAndRedirect () {
      this.form.email = 'anonymous'
      this.form.password = 'anonymous'
      this.form.course_id = this.courseId
      const { data } = await this.form.post('/api/login')
      // Save the token.
      await this.$store.dispatch('auth/saveToken', {
        token: data.token,
        remember: this.remember
      })
      window.location.href = `/students/courses/${this.courseId}/assignments/anonymous-user`
    },
    async setAnonymousUserSession () {
      try {
        const { data } = await axios.post('/api/users/set-anonymous-user-session')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return true
      } catch (error) {
        this.$noty.error(error.message)
      }
      return false
    }
  }
}
</script>

<style scoped>

</style>
