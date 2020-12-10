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
        <b-card header="default" header-html="Graders">
          <b-card-text>
            <b-form ref="form">
              <div v-if="course.graders.length">
                Your current graders:<br>
                <ol id="graders">
                  <li v-for="grader in course.graders" :key="grader.id">
                    {{ grader.first_name }} {{ grader.last_name }} {{ grader.email }}
                    <b-icon icon="trash" @click="deleteGrader(grader.id)" />
                  </li>
                </ol>
              </div>

              <b-form-group
                id="email"
                label-cols-sm="3"
                label-cols-lg="2"
                label="New Grader"
                label-for="email"
              >
                <b-form-input
                  id="email"
                  v-model="graderForm.email"
                  placeholder="Email Address"
                  type="text"
                  :class="{ 'is-invalid': graderForm.errors.has('email') }"
                  @keydown="graderForm.errors.clear('email')"
                />
                <has-error :form="graderForm" field="email" />
              </b-form-group>
              <b-button class="float-right" variant="primary" @click="submitInviteGrader">
                Invite Grader
              </b-button>
              <div v-if="sendingEmail" class="float-left">
                <b-spinner small type="grow" />
                Sending Email..
              </div>
            </b-form>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
    sendingEmail: false,
    isLoading: true,
    graders: {},
    course: { graders: {} },
    graderForm: new Form({
      email: ''
    })
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
      this.graders = this.course.graders
      this.isLoading = false
    },
    async inviteGrader (courseId) {
      this.courseId = courseId
      try {
        const { data } = await axios.get(`/api/grader/${this.courseId}`)
        this.graders = data.graders
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error('We were not able to retrieve your graders.')
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async deleteGrader (userId) {
      try {
        const { data } = await axios.delete(`/api/grader/${this.courseId}/${userId}`)

        if (data.type === 'error') {
          this.$noty.error('We were not able to remove the grader from the course.  Please try again or contact us for assistance.')
          return false
        }
        this.$noty.success(data.message)
        // remove the grad
        this.course.graders = this.course.graders.filter(grader => parseFloat(grader.id) !== parseFloat(userId))
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitInviteGrader (bvModalEvt) {
      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }
      bvModalEvt.preventDefault()
      try {
        this.sendingEmail = true
        const { data } = await this.graderForm.post(`/api/invitations/${this.courseId}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
      this.sendingEmail = false
    }
  }
}
</script>
