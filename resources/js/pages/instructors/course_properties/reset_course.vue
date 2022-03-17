<template>
  <div>
    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Reset Course</h2>">
      <b-card-text>
        <p>
          We ask that instructors reset their courses after a course has been completed. By resetting the course, all
          student data
          including enrollment information, submissions (both auto-graded and open-ended), and scores will be removed
          from ADAPT.
          And as part of our commitment to maintaining the security of student data, we will automatically reset courses
          that are not manually reset
          within 100 days
          of completion.
        </p>
        <ResetCourse :course="course"
                     :course-id="courseId"
        />
        <b-col class="my-1">
                  <span class="float-right">
                  <b-button size="sm" variant="danger"
                            @click="$bvModal.show('modal-reset-course')"
                  >
                    Reset Course
                  </b-button>
                    </span>
        </b-col>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'
import ResetCourse from '~/components/ResetCourse'

export default {
  metaInfo () {
    return { title: 'Reset Course' }
  },
  components: {
    ResetCourse
  },
  data: () => ({
    courseId: 0,
    course: {}
  }),
  mounted () {
    this.courseId = parseInt(this.$route.params.courseId)
    this.getCourseInfo()
  },
  methods: {
    async getCourseInfo () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.course = data.course
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
