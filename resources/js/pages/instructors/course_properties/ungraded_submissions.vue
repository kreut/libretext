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
        <b-card header="default" header-html="Ungraded Submissions">
          <b-card-text>
            <p>The following are assignment/questions which have submissions that still need grading:</p>
            <div v-show="ungradedSubmissions.length">
              <ul>
                <span v-html="ungradedSubmissions"></span>
              </ul>
            </div>
            <div v-show="!ungradedSubmissions.length &&!isLoading" class="clearfix">
              <b-alert show variant="info">
                <span class="font-weight-bold">You currently have no ungraded submissions.</span>
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
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
    isLoading: true,
    ungradedSubmissions: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getUngradedAssignments(this.courseId)
  },
  methods: {
    async getUngradedAssignments () {
      try {
        const { data } = await axios.get(`/api/submission-files/ungraded-submissions/${this.courseId}`)
        this.ungradedSubmissions = data.ungraded_submissions
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
