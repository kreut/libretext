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
        <div v-if="enrollmentsOptions.length">
          <b-card
            header-html="<h2 class=&quot;h7&quot;>A11y</h2>"
            class="mb-4"
          >
            <b-card-text>
              <p>
                ADAPT integrates a variety of open-source technologies, some of which have accessibility issues which
                are beyond our control. To
                remedy this, ADAPT can serve your students modified questions which satisfy web-based accessibility
                requirements assuming that an
                accessible version of the question has been created.
              </p>
              <b-form>
                <RequiredText/>
                <b-form-group
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label-for="assignment_level_apply_to"
                >
                  <template slot="label">
                    Apply To*
                  </template>
                  <b-form-row>
                    <div class="d-flex mt-1">
                      <b-form-select id="a11y"
                                     v-model="student"
                                     cols="5"
                                     required
                                     size="sm"
                                     class="mr-2"
                                     :options="enrollmentsOptions"
                      />
                      <b-button variant="primary"
                                size="sm"
                                @click="updateA11y(student,'add')"
                      >
                        Update
                      </b-button>
                    </div>
                  </b-form-row>
                </b-form-group>
              </b-form>
              <ul v-for="a11y in a11ys"
                  :key="`a11y_${a11y.id}`"
              >
                <li>
                  {{ a11y.name }}
                  <a href="" @click.prevent="updateA11y(a11y.id,'remove')">
                    <b-icon-trash class="text-muted"
                                  :aria-label="`Remove a11y for ${a11y.name}`"
                    />
                  </a>
                </li>
              </ul>
            </b-card-text>
          </b-card>
        </div>
        <div v-else>
          <b-alert show variant="info">
            <span class="font-weight-bold">No students are currently enrolled in this course.</span>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  metaInfo () {
    return { title: 'A11y' }
  },
  components: {
    Loading
  },
  data: () => ({
    student: null,
    enrollmentsOptions: [],
    courseId: 0,
    isLoading: true,
    a11ys: []
  }),
  mounted () {
    this.courseId = parseInt(this.$route.params.courseId)
    this.getEnrolledStudents()
  },
  methods: {
    async updateA11y (studentId, action) {
      if (!studentId) {
        this.$noty.info('Please first choose a student.')
        return false
      }

      try {
        const { data } = await axios.patch(`/api/enrollments/a11y`, {
          'student_user_id': studentId,
          'course_id': this.courseId
        })
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          let student
          switch (action) {
            case ('add'):
              student = this.enrollmentsOptions.find(student => student.value === studentId)
              this.a11ys.push({ id: studentId, name: student.text })
              this.a11ys = this.a11ys.sort((a, b) => (a.name > b.name) ? 1 : -1)
              this.enrollmentsOptions = this.enrollmentsOptions.filter(student => student.value !== studentId)
              this.student = null
              break
            case ('remove'):
              student = this.a11ys.find(student => student.id === studentId)
              this.enrollmentsOptions = this.enrollmentsOptions.filter(student => student.value !== null)
              this.enrollmentsOptions.push({ value: studentId, text: student.name })
              this.enrollmentsOptions = this.enrollmentsOptions.sort((a, b) => (a.text > b.text) ? 1 : -1)
              this.enrollmentOptions = this.enrollmentsOptions.unshift({ value: null, text: 'Please choose a student' })
              this.a11ys = this.a11ys.filter(student => student.id !== studentId)
              break
            default:
              alert(`${action} is not a supported action.  Please try again or contact us for assistance.`)
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getEnrolledStudents () {
      try {
        const { data } = await axios.get(`/api/enrollments/${this.courseId}/details`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        let enrollments = data.enrollments
        this.enrollmentsOptions = [{ value: null, text: 'Please choose a student' }]
        for (let i = 0; i < enrollments.length; i++) {
          let enrollment = enrollments[i]
          enrollment.a11y
            ? this.a11ys.push({ id: enrollment.id, name: enrollment.name })
            : this.enrollmentsOptions.push({ value: enrollment.id, text: enrollment.name })
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
