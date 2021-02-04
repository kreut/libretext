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
        <b-card header="default" header-html="General Information">
          <b-card-text>
            <CourseForm :form="editCourseForm" />
            <b-button class="float-right" variant="primary" @click="updateCourse">
              Update
            </b-button>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import CourseForm from '../../../components/CourseForm'
import Form from 'vform'
import axios from 'axios'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  components: { CourseForm, Loading },
  middleware: 'auth',
  data: () => ({
    isLoading: true,
    courseId: false,
    editCourseForm: new Form({
      name: '',
      start_date: '',
      end_date: '',
      public: '1'
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getCourseInfo(this.courseId)
    console.log(this.courseId)
  },
  methods: {
    async updateCourse () {
      try {
        const { data } = await this.editCourseForm.patch(`/api/courses/${this.courseId}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async getCourseInfo (courseId) {
      try {
        const { data } = await axios.get(`/api/courses/${courseId}`)
        let course = data.course
        this.editCourseForm.name = course.name
        this.editCourseForm.start_date = course.start_date
        this.editCourseForm.end_date = course.end_date
        this.editCourseForm.public = course.public

        console.log(data)
        if (data.type === 'error') {
          this.$noty.error('We were not able to retrieve the course information.')
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

<style scoped>

</style>
