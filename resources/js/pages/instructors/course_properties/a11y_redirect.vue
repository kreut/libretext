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
            header-html="<h2 class=&quot;h7&quot;>A11y Redirect</h2>"
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
              <b-form class="pb-2">
                <RequiredText/>
                <b-form-group
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label="Student*"
                >
                  <b-form-select id="a11y"
                                 v-model="student"
                                 style="width:300px"
                                 cols="5"
                                 required
                                 size="sm"
                                 class="mr-2"
                                 :options="enrollmentsOptions"
                  />
                </b-form-group>
                <b-form-group
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label="Redirect to*"
                  label-for="redirect_to"
                >
                  <b-form-radio-group
                    id="redirect_to"
                    v-model="redirectTo"
                    stacked
                  >
                    <b-form-radio name="all_grader_access_level" value="a11y_technology">
                      a11y technology
                    </b-form-radio>
                    <b-form-radio name="all_grader_access_level" value="text_question">
                      text question
                    </b-form-radio>
                  </b-form-radio-group>
                </b-form-group>
                <b-button variant="primary"
                          size="sm"
                          @click="updateA11y(student,'add', redirectTo)"
                >
                  Save A11y Redirect
                </b-button>
              </b-form>
              <div v-if="a11yRedirects.length">
                <b-table
                  :items="a11yRedirects"
                  :fields="a11yFields"
                >
                  <template v-slot:cell(redirect_to)="data">
                    {{ data.item.a11y_redirect.replace('_', ' ') }}
                  </template>
                  <template v-slot:cell(actions)="data">
                    <a href="" @click.prevent="updateA11y(data.item.id,'update',data.item.a11y_redirect )">
                      <b-icon-pencil class="text-muted"
                                     :aria-label="`Update a11y for ${data.item.name}`"
                      />
                    </a>
                    <a href="" @click.prevent="updateA11y(data.item.id,'remove', 0)">
                      <b-icon-trash class="text-muted"
                                    :aria-label="`Remove a11y for ${data.item.name}`"
                      />
                    </a>
                  </template>
                </b-table>
              </div>
              <div v-else>
                <b-alert show variant="info">
                  No students will receive a11y versions of your questions.
                </b-alert>
              </div>
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
    return { title: 'A11y Redirect' }
  },
  components: {
    Loading
  },
  data: () => ({
    redirectTo: 'a11y_technology',
    a11yFields: ['name', 'redirect_to', 'actions'],
    student: null,
    enrollmentsOptions: [],
    courseId: 0,
    isLoading: true,
    a11yRedirects: []
  }),
  mounted () {
    this.courseId = parseInt(this.$route.params.courseId)
    this.getEnrolledStudents()
  },
  methods: {
    async updateA11y (studentId, action, redirectTo = '') {
      if (!studentId) {
        this.$noty.info('Please first choose a student.')
        return false
      }

      try {
        const { data } = await axios.patch(`/api/enrollments/a11y-redirect`, {
          student_user_id: studentId,
          course_id: this.courseId,
          action: action,
          redirect_to: redirectTo
        })
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getEnrolledStudents()
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
        this.a11yRedirects = []
        this.student = null
        this.enrollmentsOptions = [{ value: null, text: 'Please choose a student' }]
        for (let i = 0; i < enrollments.length; i++) {
          let enrollment = enrollments[i]
          let isRedirect = ['text_question', 'a11y_technology'].includes(enrollment.a11y_redirect)
          isRedirect ? this.a11yRedirects.push({
              id: enrollment.id,
              name: enrollment.name,
              a11y_redirect: enrollment.a11y_redirect
            })
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
