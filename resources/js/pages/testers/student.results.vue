<template>
  <div class="vld-parent">
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-auto-enroll-student" />
    <b-modal v-if="studentToRemove"
             id="modal-confirm-remove-student"
             :title="`Remove ${studentResults.find(student =>student.id === studentToRemove).name}`"
    >
      <b-alert show variant="danger">
        If you remove this student, all of their submissions and their scores will be removed as well.
      </b-alert>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-remove-student')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="removeStudent()">
          Remove
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-auto-enroll-student"
             title="Create Student"
    >
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="first_name">First Name*
        </label>
        <div class="col-md-7">
          <input id="first_name" v-model="form.first_name"
                 :class="{ 'is-invalid': form.errors.has('first_name') }"
                 class="form-control"
                 required
                 type="text"
                 name="first_name"
                 placeholder="First"
                 autocomplete="on"
          >
          <has-error :form="form" field="first_name" />
        </div>
      </div>
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="last_name">Last Name*
        </label>
        <div class="col-md-7">
          <input id="last_name"
                 v-model="form.last_name"
                 :class="{ 'is-invalid': form.errors.has('last_name') }"
                 required
                 class="form-control"
                 type="text"
                 name="last_name"
                 placeholder="Last"
                 autocomplete="on"
          >
          <has-error :form="form" field="last_name" />
        </div>
      </div>
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="student_id">Student ID*
        </label>
        <div class="col-md-7">
          <input id="student_id" v-model="form.student_id"
                 :class="{ 'is-invalid': form.errors.has('student_id') }"
                 required
                 class="form-control"
                 type="text"
                 name="student_id"
                 autocomplete="on"
          >
          <has-error :form="form" field="student_id" />
        </div>
      </div>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-auto-enroll-student')">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="autoEnrollStudent()">
          Submit
        </b-button>
      </template>
    </b-modal>
    <PageTitle title="Student Results" />
    <div v-if="user && user.role === 6">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <b-container>
          <b-row align-h="end" class="mb-4">
            <b-button variant="primary" size="sm" @click="$bvModal.show('modal-auto-enroll-student')">
              New Student
            </b-button>
          </b-row>

          <div v-if="studentResults.length">
            <b-table
              aria-label="Progress Report"
              striped
              hover
              :no-border-collapse="true"
              :items="studentResults"
              :fields="fields"
            >
              <template v-slot:cell(name)="data">
                <a href="" @click.prevent="loginAsStudentInCourse(data.item.id)">
                  {{ data.item.name }}
                </a>
              </template>
              <template v-slot:cell(actions)="data">
                <a href="" class="pr-2" @click.prevent="emailResultsToInstructor(data.item.id)">
                  <b-icon :id="`email-instructor-${data.item.id}`"
                          icon="envelope"
                          class="text-muted"
                          aria-label="Email results to instructor"
                  />
                </a>
                <b-tooltip :target="`email-instructor-${data.item.id}`"
                           delay="750"
                           triggers="hover"
                >
                  Email the instructor with the results.
                </b-tooltip>
                <a href="" @click.prevent="initRemoveStudent(data.item.id)">
                  <b-icon :id="`remove-student-${data.item.id}`"
                          icon="trash"
                          aria-label="Remove Student"
                          class="text-muted"
                  />
                </a>
                <b-tooltip :target="`remove-student-${data.item.id}`"
                           delay="750"
                           triggers="hover"
                >
                  Completely remove {{ data.item.name }} from ADAPT, including all submission information.
                </b-tooltip>
              </template>
            </b-table>
          </div>
          <div v-if="!studentResults.length">
            <b-alert show variant="info">
              There are currently no student accounts.
            </b-alert>
          </div>
        </b-container>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { mapGetters } from 'vuex'

export default {
  components: {
    Loading,
    AllFormErrors
  },
  data: () => ({
    studentToRemove: 0,
    studentResults: [],
    allFormErrors: [],
    isLoading: true,
    courseId: 0,
    form: new Form({
      name: '',
      studentId: ''
    }),
    fields: [
      {
        key: 'name',
        sortable: true,
        isRowHeader: true
      },
      {
        key: 'student_id',
        label: 'Student ID',
        sortable: true
      },
      'number_submitted',
      'score',
      {
        key: 'created_at',
        label: 'Date Created',
        sortable: true
      },
      'actions'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (this.user.role !== 6) {
      this.isLoading = false
      this.$noty.error('You are not allowed to view this page.')
      return false
    }
    this.courseId = this.$route.params.courseId
    this.getStudentResults()
  },
  methods: {
    initRemoveStudent (userId) {
      this.studentToRemove = userId
      this.$nextTick(() => {
        this.$bvModal.show('modal-confirm-remove-student')
      })
    },
    async removeStudent () {
      try {
        const { data } = await axios.delete(`/api/user/${this.studentToRemove}/course/${this.courseId}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getStudentResults()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.studentToRemove = 0
    },
    async emailResultsToInstructor (userId) {
      try {
        const { data } = await axios.post(`/api/tester/email-results/${userId}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async loginAsStudentInCourse (studentUserId) {
      try {
        const { data } = await axios.post('/api/user/login-as-student-in-course',
          {
            course_id: this.courseId,
            student_user_id: studentUserId
          })

        if (data.type === 'success') {
          // Save the token.
          await this.$store.dispatch('auth/saveToken', {
            token: data.token,
            remember: false
          })

          // Fetch the user.
          await this.$store.dispatch('auth/fetchUser')
          await this.$router.push({ name: 'logged.in.as.student' })
        } else {
          this.$noty.error(data.message)// no access
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async autoEnrollStudent () {
      try {
        const { data } = await this.form.post(`/api/enrollments/auto-enroll/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        await this.$store.dispatch('auth/saveToken', {
          token: data.token,
          remember: false
        })
        await this.$store.dispatch('auth/fetchUser')
        await this.$router.push({ name: 'logged.in.as.student' })
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-auto-enroll-student')
        }
      }
    },
    async getStudentResults () {
      try {
        const { data } = await axios.get(`/api/scores/straight-sum/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.studentResults = data.student_results
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
