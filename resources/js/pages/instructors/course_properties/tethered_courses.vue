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
        <b-card header="default" header-html="Alpha Course Import Code" class="mb-5">
          <b-card-text>
            <p>
              Instructors who create Alpha courses control which assignments and assessments are created in their
              associated Beta Courses. If the Alpha course instructor removes an assignment or assessment, this will
              impact
              the scores of the students in the Beta courses. With great power comes great responsibility!
            </p>
            <p>
              To maintain control over who can tether Beta courses to your Alpha course, please provide potential
              instructors with the following import code:
            </p>
            <p class="text-center">
              <span class="font-weight-bold font-italic pr-2">{{ alphaCourseImportCode }}</span>
            <b-button variant="primary" size="sm" @click="refreshImportCode">Refresh</b-button>
            </p>
          </b-card-text>
        </b-card>
        <b-card header="default" header-html="Beta Courses">
          <b-card-text>
            <p>
              Here you can find a list of all of your tethered Beta courses. If this is an Alpha course,
              then every assignment/assessment that is created/removed will be automatically reflected in the
              tethered Beta courses.
            </p>
            <b-table
              v-if="betaCourses.length"
              striped
              hover
              :no-border-collapse="true"
              :fields="fields"
              :items="betaCourses"
            />
            <div v-if="!betaCourses.length">
              <b-alert :show="true" variant="info">
                <span class="font-weight-bold">This course has no tethered Beta courses.</span>
              </b-alert>
            </div>
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
  components: { Loading },
  data: () => ({
    alphaCourseImportCode: '',
    isLoading: true,
    courseId: 0,
    betaCourses: [],
    fields: [
      {
        key: 'name',
        label: 'Course Name'
      },
      {
        key: 'user_name',
        label: 'Instructor Name'
      },
      'email'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getBetaCourses()
    this.getAlphaCourseImportCode()
  },
  methods: {
    async refreshImportCode() {
      try {
        const { data } = await axios.post(`/api/alpha-course-import-codes/refresh/${this.courseId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.alphaCourseImportCode = data.import_code
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getAlphaCourseImportCode () {
      try {
        const { data } = await axios.get(`/api/alpha-course-import-codes/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.alphaCourseImportCode = data.import_code
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getBetaCourses () {
      try {
        const { data } = await axios.get(`/api/beta-courses/get-from-alpha-course/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.betaCourses = data.beta_courses
        this.isLoading = false
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
