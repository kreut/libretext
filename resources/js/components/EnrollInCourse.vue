<template>
  <div>
    <b-modal
      id="modal-enroll-in-course"
      ref="modal"
      ok-title="Submit"
      :ok-only="inIFrame"
      @ok="submitEnrollInCourse"
    >
      <template v-if="inIFrame" #modal-header>
        Enroll In Course
      </template>
      <template v-if="!inIFrame" #modal-title>
        Enroll In Course
      </template>
      <b-form ref="form" @submit="submitEnrollInCourse">
        <p>To access this assignment, please provide the course access code given to you by your instructor.</p>
        <b-form-group
          id="access_code"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Access Code"
          label-for="access_code"
        >
          <b-form-input
            id="access_code"
            v-model="form.access_code"
            type="text"
            :class="{ 'is-invalid': form.errors.has('access_code') }"
            @keydown="form.errors.clear('access_code')"
          />
          <has-error :form="form" field="access_code" />
        </b-form-group>
      </b-form>
    </b-modal>
  </div>
</template>

<script>
import Form from 'vform'

export default {
  props: { getEnrolledInCourses: Function },
  data: () => ({
    inIFrame: false,
    form: new Form({
      access_code: ''
    })
  }),
  mounted () {
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },
  methods: {
    submitEnrollInCourse (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.inIFrame ? this.enrollInCourseViaIFrame() : this.enrollInCourse()
    },
    resetAll () {
      this.getEnrolledInCourses()
      this.form.access_code = ''
      this.form.errors.clear()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide('modal-enroll-in-course')
      })
    },
    async enrollInCourse () {
      try {
        const { data } = await this.form.post('/api/enrollments')
        if (data.validated) {
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            this.resetAll()
          }
        } else {
          if (data.type === 'error') {
            this.$noty.error(data.message)// no access
            this.resetAll()
          }
        }
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async enrollInCourseViaIFrame () {
      try {
        const { data } = await this.form.post('/api/enrollments')
        if (data.validated) {
          location.reload()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
