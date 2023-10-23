<template>
  <div>
    <b-modal id="modal-error"
             title="Error"
             hide-footer
    >
      <p>Your LMS returned the following error:</p>
      <p> {{ errorDescription }}</p>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'LmsAccessGranted',
  data: () => ({
    errorDescription: ''
  }),
  mounted () {
    const queryString = window.location.search
    const urlParams = new URLSearchParams(queryString)
    if (urlParams.has('error')) {
      this.errorDescription = urlParams.get('error_description')
      this.$bvModal.show('modal-error')
    } else {
      this.getAccessToken(urlParams.get('state'), urlParams.get('code'))
    }
  },
  methods: {
    async getAccessToken (courseId, code) {
      try {
        const { data } = await axios.get(`/api/lms-api/access-token/course/${courseId}/code/${code}`)
        if (data.type !== 'success') {
          this.errorDescription = data.message
          this.$bvModal.show('modal-error')
        } else {
          await this.$router.push({ name: 'instructors.assignments.index', params: { courseId: courseId } })
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
